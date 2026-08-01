<?php
// test-credentials.php
require_once 'admin/config/pesapal.php';

$config = require 'admin/config/pesapal.php';
$env = $config['live'] ? 'live' : 'sandbox';

echo "Testing Pesapal Credentials\n";
echo "===========================\n";
echo "Environment: $env\n";
echo "Base URL: " . $config[$env]['base_url'] . "\n";
echo "Consumer Key: " . substr($config[$env]['consumer_key'], 0, 15) . "...\n\n";

// Test authentication
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config[$env]['base_url'] . '/Auth/RequestToken');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'consumer_key' => $config[$env]['consumer_key'],
    'consumer_secret' => $config[$env]['consumer_secret']
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ CURL Error: $error\n";
    exit;
}

$data = json_decode($response, true);
echo "HTTP Status: $httpCode\n";

if ($httpCode === 200 && isset($data['token'])) {
    echo "✅ SUCCESS! Your credentials are valid!\n";
    echo "Token: " . substr($data['token'], 0, 30) . "...\n";
} else {
    echo "❌ FAILED! Your credentials are invalid.\n";
    echo "Response: " . print_r($data, true) . "\n";
}
?>