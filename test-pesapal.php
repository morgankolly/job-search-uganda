<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$url = rtrim($_ENV['PESAPAL_BASE_URL'], '/') . '/api/Auth/RequestToken';

$data = [
    'consumer_key' => $_ENV['PESAPAL_CONSUMER_KEY'],
    'consumer_secret' => $_ENV['PESAPAL_CONSUMER_SECRET']
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);

echo "<pre>";
echo $response;