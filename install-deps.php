<?php
declare(strict_types=1);

$root = __DIR__;
$vendorDir = $root . '/vendor';
$cacheDir = $root . '/.package-cache';

$rootRequirements = [
    'laravel/framework' => '^12.0',
    'laravel/tinker' => '^2.10.1',
];

$skipPackages = ['php', 'composer-runtime-api', 'composer-plugin-api'];

$ctx = stream_context_create([
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    'http' => ['follow_location' => 1, 'timeout' => 120, 'header' => "User-Agent: salon-installer/1.0\r\n"],
]);

function httpGet(string $url, $ctx): string
{
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) {
        throw new RuntimeException("Failed to download: {$url}");
    }
    return $data;
}

function normalizeVersion(string $version): string
{
    return ltrim($version, 'v');
}

function versionSatisfies(string $version, string $constraint): bool
{
    $version = normalizeVersion($version);
    $constraint = trim($constraint);

    if ($constraint === '*' || $constraint === '') {
        return true;
    }

    foreach (explode('|', $constraint) as $part) {
        $part = trim($part);
        if (str_starts_with($part, '^')) {
            $base = normalizeVersion(substr($part, 1));
            if (!preg_match('/^(\d+)\.(\d+)(?:\.(\d+))?/', $base, $m)) {
                continue;
            }
            $major = (int) $m[1];
            $upper = ($major + 1) . '.0.0';
            if (version_compare($version, $base, '>=') && version_compare($version, $upper, '<')) {
                return true;
            }
        } elseif (str_starts_with($part, '~')) {
            $base = normalizeVersion(substr($part, 1));
            if (!preg_match('/^(\d+)\.(\d+)/', $base, $m)) {
                continue;
            }
            $upper = $m[1] . '.' . ((int) $m[2] + 1) . '.0';
            if (version_compare($version, $base, '>=') && version_compare($version, $upper, '<')) {
                return true;
            }
        } elseif (version_compare($version, normalizeVersion($part), '==')) {
            return true;
        }
    }

    return false;
}

function getPackageMetas(string $package, $ctx): array
{
    static $cache = [];
    if (isset($cache[$package])) {
        return $cache[$package];
    }
    $url = 'https://repo.packagist.org/p2/' . str_replace('/', '%2F', $package) . '.json';
    $json = json_decode(httpGet($url, $ctx), true);
    $cache[$package] = $json['packages'][$package] ?? [];
    return $cache[$package];
}

function pickBestVersion(array $metas, string $constraint): ?array
{
    $stable = array_filter($metas, function ($meta) {
        $v = strtolower(normalizeVersion($meta['version'] ?? ''));
        return $v !== '' && !str_contains($v, 'dev') && !str_contains($v, 'alpha') && !str_contains($v, 'beta') && !str_contains($v, 'rc');
    });

    usort($stable, fn($a, $b) => version_compare(normalizeVersion($b['version']), normalizeVersion($a['version'])));

    foreach ($stable as $meta) {
        if (versionSatisfies($meta['version'], $constraint)) {
            return $meta;
        }
    }

    return $stable[0] ?? null;
}

function shouldSkip(string $package, array $skipPackages): bool
{
    if (in_array($package, $skipPackages, true)) {
        return true;
    }
    if (str_starts_with($package, 'ext-')) {
        return true;
    }
    return false;
}

function downloadAndExtract(string $package, array $meta, string $vendorDir, string $cacheDir, $ctx): void
{
    $targetDir = $vendorDir . '/' . $package;
    if (is_dir($targetDir) && file_exists($targetDir . '/composer.json')) {
        return;
    }

    $dist = $meta['dist'] ?? null;
    if (!$dist || ($dist['type'] ?? '') !== 'zip') {
        throw new RuntimeException("No zip dist for {$package} " . ($meta['version'] ?? ''));
    }

    if (is_dir($targetDir)) {
        rmdir($targetDir);
    }

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    $filename = md5($dist['url']) . '.zip';
    $path = $cacheDir . '/' . $filename;

    if (!file_exists($path)) {
        echo "  Downloading {$package} {$meta['version']}...\n";
        file_put_contents($path, httpGet($dist['url'], $ctx));
    }

    mkdir($targetDir, 0755, true);

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException("Cannot open zip: {$path}");
    }
    $zip->extractTo($targetDir);
    $zip->close();

    $items = array_diff(scandir($targetDir), ['.', '..']);
    if (count($items) === 1) {
        $sub = $targetDir . '/' . reset($items);
        if (is_dir($sub)) {
            foreach (array_diff(scandir($sub), ['.', '..']) as $item) {
                rename($sub . '/' . $item, $targetDir . '/' . $item);
            }
            rmdir($sub);
        }
    }
}

function generateAutoload(string $vendorDir): void
{
    $composerDir = $vendorDir . '/composer';
    if (!is_dir($composerDir)) {
        mkdir($composerDir, 0755, true);
    }

    $psr4 = [];
    $classmap = [];
    $files = [];

    foreach (glob($vendorDir . '/*/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $composerJson = $dir . '/composer.json';
        if (!file_exists($composerJson)) {
            continue;
        }
        $cfg = json_decode(file_get_contents($composerJson), true);
        if (!$cfg) {
            continue;
        }

        foreach ($cfg['autoload']['psr-4'] ?? [] as $namespace => $paths) {
            foreach ((array) $paths as $path) {
                $psr4[$namespace][] = rtrim($dir . '/' . $path, '/\\');
            }
        }
        foreach ($cfg['autoload']['psr-0'] ?? [] as $namespace => $paths) {
            foreach ((array) $paths as $path) {
                $prefix = str_replace('_', '\\', $namespace);
                $psr4[$prefix . '\\'][] = rtrim($dir . '/' . $path, '/\\');
            }
        }
        foreach ($cfg['autoload']['classmap'] ?? [] as $rel) {
            $full = $dir . '/' . $rel;
            if (is_dir($full)) {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full));
                foreach ($it as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $class = basename($file->getFilename(), '.php');
                        $classmap[$class] = $file->getPathname();
                    }
                }
            } elseif (is_file($full)) {
                $class = basename($full, '.php');
                $classmap[$class] = $full;
            }
        }
        foreach ($cfg['autoload']['files'] ?? [] as $rel) {
            $files[] = $dir . '/' . $rel;
        }
    }

    ksort($psr4);

    $psr4Export = var_export($psr4, true);
    $classmapExport = var_export($classmap, true);
    $filesExport = var_export($files, true);

    file_put_contents($composerDir . '/autoload_psr4.php', "<?php\n\nreturn {$psr4Export};\n");
    file_put_contents($composerDir . '/autoload_classmap.php', "<?php\n\nreturn {$classmapExport};\n");
    file_put_contents($composerDir . '/autoload_files.php', "<?php\n\nreturn {$filesExport};\n");
    file_put_contents($composerDir . '/autoload_static.php', "<?php\n\nnamespace Composer\\Autoload;\n\nclass ComposerStaticInitSalon\n{\n    public static \$prefixLengthsPsr4 = array();\n    public static \$prefixDirsPsr4 = " . var_export($psr4, true) . ";\n    public static \$classMap = " . var_export($classmap, true) . ";\n    public static function getInitializer(\\Composer\\Autoload\\ClassLoader \$loader)\n    {\n        return \\Closure::bind(function () use (\$loader) {\n            \$loader->prefixLengthsPsr4 = [];\n            \$loader->prefixDirsPsr4 = " . var_export($psr4, true) . ";\n            \$loader->classMap = " . var_export($classmap, true) . ";\n        }, null, ClassLoader::class);\n    }\n}\n");

    file_put_contents($composerDir . '/ClassLoader.php', file_get_contents('https://raw.githubusercontent.com/composer/composer/main/src/Composer/Autoload/ClassLoader.php', false, stream_context_create([
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ])));

    file_put_contents($composerDir . '/autoload_real.php', <<<'PHP'
<?php

class ComposerAutoloaderInitSalon
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/ClassLoader.php';

        spl_autoload_register([__CLASS__, 'loadClassLoader'], true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader();
        spl_autoload_unregister([__CLASS__, 'loadClassLoader']);

        $map = require __DIR__ . '/autoload_psr4.php';
        foreach ($map as $namespace => $paths) {
            $loader->setPsr4($namespace, $paths);
        }

        $classMap = require __DIR__ . '/autoload_classmap.php';
        if ($classMap) {
            $loader->addClassMap($classMap);
        }

        $files = require __DIR__ . '/autoload_files.php';
        foreach ($files as $file) {
            require $file;
        }

        $loader->register(true);

        return $loader;
    }
}
PHP);

    file_put_contents($vendorDir . '/autoload.php', <<<'PHP'
<?php

require_once __DIR__ . '/composer/autoload_real.php';

return ComposerAutoloaderInitSalon::getLoader();
PHP);

    echo "Generated autoload files.\n";
}

echo "Installing dependencies...\n";

$installed = [];
$queue = $rootRequirements;

while (!empty($queue)) {
    $package = array_key_first($queue);
    $constraint = $queue[$package];
    unset($queue[$package]);

    if (shouldSkip($package, $skipPackages) || isset($installed[$package])) {
        continue;
    }

    echo "Resolving {$package} ({$constraint})...\n";
    $metas = getPackageMetas($package, $ctx);
    if (empty($metas)) {
        echo "  SKIP (not found): {$package}\n";
        continue;
    }

    $meta = pickBestVersion($metas, $constraint);
    if (!$meta) {
        throw new RuntimeException("Cannot resolve {$package} {$constraint}");
    }

    downloadAndExtract($package, $meta, $vendorDir, $cacheDir, $ctx);
    $installed[$package] = $meta['version'];
    echo "  Installed {$package} {$meta['version']}\n";

    foreach ($meta['require'] ?? [] as $dep => $ver) {
        if (!shouldSkip($dep, $skipPackages) && !isset($installed[$dep]) && !isset($queue[$dep])) {
            $queue[$dep] = $ver;
        }
    }
}

echo "\nTotal installed: " . count($installed) . "\n";
generateAutoload($vendorDir);
echo "Done.\n";
