<?php
declare(strict_types=1);

$vendorDir = __DIR__ . '/vendor';
$composerDir = $vendorDir . '/composer';
if (!is_dir($composerDir)) {
    mkdir($composerDir, 0755, true);
}

$psr4 = [];
$classmap = [];
$files = [];

$scanComposerJson = function (string $baseDir, array $cfg) use (&$psr4, &$classmap, &$files): void {
    foreach ($cfg['autoload']['psr-4'] ?? [] as $namespace => $paths) {
        foreach ((array) $paths as $path) {
            $psr4[$namespace][] = rtrim($baseDir . '/' . $path, '/\\');
        }
    }
    foreach ($cfg['autoload']['psr-0'] ?? [] as $namespace => $paths) {
        foreach ((array) $paths as $path) {
            $prefix = str_replace('_', '\\', $namespace);
            $psr4[$prefix . '\\'][] = rtrim($baseDir . '/' . $path, '/\\');
        }
    }
    foreach ($cfg['autoload']['classmap'] ?? [] as $rel) {
        $full = $baseDir . '/' . $rel;
        if (!file_exists($full)) {
            continue;
        }
        $paths = is_dir($full) ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full)) : [$full];
        foreach ($paths as $file) {
            if (is_string($file)) {
                $filePath = $file;
            } else {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $filePath = $file->getPathname();
            }
            $code = file_get_contents($filePath);
            $namespace = '';
            if (preg_match('/namespace\s+([^;]+);/', $code, $m)) {
                $namespace = trim($m[1]) . '\\';
            }
            if (preg_match('/\b(class|interface|trait|enum)\s+(\w+)/', $code, $m)) {
                $classmap[$namespace . $m[2]] = $filePath;
            }
        }
    }
    foreach ($cfg['autoload']['files'] ?? [] as $rel) {
        $files[] = $baseDir . '/' . $rel;
    }
};

$projectComposer = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
if ($projectComposer) {
    $scanComposerJson(__DIR__, $projectComposer);
}

foreach (glob($vendorDir . '/*/*', GLOB_ONLYDIR) ?: [] as $dir) {
    $composerJson = $dir . '/composer.json';
    if (!file_exists($composerJson)) {
        continue;
    }
    $cfg = json_decode(file_get_contents($composerJson), true);
    if (!$cfg) {
        continue;
    }

    $scanComposerJson($dir, $cfg);
}

ksort($psr4);
file_put_contents($composerDir . '/autoload_psr4.php', "<?php\n\nreturn " . var_export($psr4, true) . ";\n");
file_put_contents($composerDir . '/autoload_classmap.php', "<?php\n\nreturn " . var_export($classmap, true) . ";\n");
file_put_contents($composerDir . '/autoload_files.php', "<?php\n\nreturn " . var_export($files, true) . ";\n");

if (!file_exists($composerDir . '/ClassLoader.php')) {
    file_put_contents($composerDir . '/ClassLoader.php', file_get_contents('https://raw.githubusercontent.com/composer/composer/main/src/Composer/Autoload/ClassLoader.php', false, stream_context_create([
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ])));
}

file_put_contents($composerDir . '/autoload_real.php', <<<'PHP'
<?php

class ComposerAutoloaderInitSalon
{
    private static $loader;

    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/ClassLoader.php';
        self::$loader = $loader = new \Composer\Autoload\ClassLoader();

        foreach (require __DIR__ . '/autoload_psr4.php' as $namespace => $paths) {
            $loader->setPsr4($namespace, $paths);
        }

        $classMap = require __DIR__ . '/autoload_classmap.php';
        if ($classMap) {
            $loader->addClassMap($classMap);
        }

        foreach (require __DIR__ . '/autoload_files.php' as $file) {
            require $file;
        }

        $loader->register(true);

        return self::$loader;
    }
}
PHP);

file_put_contents($vendorDir . '/autoload.php', <<<'PHP'
<?php

require_once __DIR__ . '/composer/autoload_real.php';

return ComposerAutoloaderInitSalon::getLoader();
PHP);

echo "Autoload regenerated.\n";
