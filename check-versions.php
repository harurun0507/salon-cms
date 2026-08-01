<?php
$ctx = stream_context_create([
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    'http' => ['header' => "User-Agent: PHP\r\n"],
]);
$json = json_decode(file_get_contents('https://repo.packagist.org/p2/laravel/framework.json', false, $ctx), true);
$packages = $json['packages']['laravel/framework'];
echo "Count: " . count($packages) . "\n";
foreach (array_slice($packages, 0, 3) as $meta) {
    echo "version=" . ($meta['version'] ?? 'N/A') . " name=" . ($meta['name'] ?? 'N/A') . "\n";
}
// Find version 12
foreach ($packages as $meta) {
    $v = $meta['version'] ?? '';
    if (preg_match('/^v?12\./', $v)) {
        echo "Found 12.x: $v dist=" . ($meta['dist']['url'] ?? 'none') . "\n";
        break;
    }
}
