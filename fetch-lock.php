<?php
$ctx = stream_context_create([
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    'http' => ['header' => "User-Agent: PHP\r\n"],
]);

$urls = [
    'https://raw.githubusercontent.com/laravel/laravel/12.x/composer.lock',
    'https://raw.githubusercontent.com/laravel/laravel/master/composer.lock',
    'https://raw.githubusercontent.com/laravel/laravel/11.x/composer.lock',
];

foreach ($urls as $url) {
    $content = @file_get_contents($url, false, $ctx);
    echo basename(dirname($url)) . ': ' . ($content ? strlen($content) . ' bytes' : 'FAIL') . "\n";
}

// Get laravel framework versions
$json = json_decode(file_get_contents('https://repo.packagist.org/p2/laravel/framework.json', false, $ctx), true);
$versions = array_keys($json['packages']['laravel/framework'] ?? []);
$versions = array_filter($versions, fn($v) => preg_match('/^v?12\./', $v));
rsort($versions, SORT_NATURAL);
echo "Laravel 12 versions (top 5): " . implode(', ', array_slice($versions, 0, 5)) . "\n";
