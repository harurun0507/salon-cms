<?php
$ctx = stream_context_create([
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    'http' => ['follow_location' => 1, 'header' => "User-Agent: PHP\r\n"],
]);

$urls = [
    'https://repo.packagist.org/p2/laravel/framework.json',
    'http://repo.packagist.org/p2/laravel/framework.json',
];

foreach ($urls as $url) {
    echo "Testing: $url\n";
    $r = @file_get_contents($url, false, $ctx);
    echo $r ? 'OK: ' . strlen($r) . " bytes\n" : "FAIL\n";
}
