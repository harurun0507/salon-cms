<?php
/** Download a single Packagist package into vendor/ */
declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php download-package.php vendor/package constraint\n");
    exit(1);
}

$package = $argv[1];
$constraint = $argv[2];
$root = __DIR__;
$vendorDir = $root . '/vendor';
$cacheDir = $root . '/.package-cache';

$ctx = stream_context_create([
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    'http' => ['follow_location' => 1, 'timeout' => 120, 'header' => "User-Agent: salon-setup\r\n"],
]);

function httpGet(string $url, $ctx): string
{
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) {
        throw new RuntimeException("Failed: {$url}");
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
    foreach (explode('|', $constraint) as $part) {
        $part = trim($part);
        if (str_starts_with($part, '^')) {
            $base = normalizeVersion(substr($part, 1));
            if (preg_match('/^(\d+)\.(\d+)/', $base, $m)) {
                $upper = ((int) $m[1] + 1) . '.0.0';
                if (version_compare($version, $base, '>=') && version_compare($version, $upper, '<')) {
                    return true;
                }
            }
        }
    }
    return false;
}

$url = 'https://repo.packagist.org/p2/' . str_replace('/', '%2F', $package) . '.json';
$metas = json_decode(httpGet($url, $ctx), true)['packages'][$package] ?? [];

$stable = array_values(array_filter($metas, fn ($m) => !preg_match('/dev|alpha|beta|rc/i', $m['version'] ?? '')));
usort($stable, fn ($a, $b) => version_compare(normalizeVersion($b['version']), normalizeVersion($a['version'])));

$meta = null;
foreach ($stable as $candidate) {
    if (versionSatisfies($candidate['version'], $constraint)) {
        $meta = $candidate;
        break;
    }
}
$meta ??= $stable[0] ?? null;
if (!$meta) {
    throw new RuntimeException("No version for {$package}");
}

$targetDir = $vendorDir . '/' . $package;
if (is_dir($targetDir) && file_exists($targetDir . '/composer.json')) {
    echo "Already installed: {$package} {$meta['version']}\n";
    exit(0);
}

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$zipUrl = $meta['dist']['url'];
$zipPath = $cacheDir . '/' . md5($zipUrl) . '.zip';
if (!file_exists($zipPath)) {
    echo "Downloading {$package} {$meta['version']}...\n";
    file_put_contents($zipPath, httpGet($zipUrl, $ctx));
}

if (is_dir($targetDir)) {
    array_map('unlink', glob($targetDir . '/*') ?: []);
    @rmdir($targetDir);
}
mkdir($targetDir, 0755, true);

$zip = new ZipArchive();
$zip->open($zipPath);
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

echo "Installed {$package} {$meta['version']}\n";
