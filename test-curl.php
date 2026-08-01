<?php
$ch = curl_init('https://repo.packagist.org/packages.json');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CAINFO => 'c:/salon/cacert.pem',
]);
$r = curl_exec($ch);
echo curl_error($ch) ?: 'OK: ' . strlen($r) . " bytes\n";
curl_close($ch);

$ch2 = curl_init('https://repo.packagist.org/packages.json');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
]);
$r2 = curl_exec($ch2);
echo curl_error($ch2) ?: 'OK no verify: ' . strlen($r2) . " bytes\n";
curl_close($ch2);
