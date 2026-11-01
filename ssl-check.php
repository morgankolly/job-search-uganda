<?php
header('Content-Type: text/plain');

echo "PHP version: " . PHP_VERSION . PHP_EOL;
echo "Loaded php.ini: " . (php_ini_loaded_file() ?: 'None') . PHP_EOL;
echo "curl.cainfo: " . ini_get('curl.cainfo') . PHP_EOL;
echo "openssl.cafile: " . ini_get('openssl.cafile') . PHP_EOL;

$ch = curl_init('https://cybqa.pesapal.com/pesapalv3/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

curl_exec($ch);

echo "HTTP status: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
echo "cURL error: " . curl_error($ch) . PHP_EOL;

curl_close($ch);
