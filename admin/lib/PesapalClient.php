<?php
// admin/lib/PesapalClient.php

class PesapalClient
{
    private $consumerKey;
    private $consumerSecret;
    private $baseUrl;
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/pesapal.php';
        $env = $this->config['live'] === true ? 'live' : 'sandbox';
        
        $this->consumerKey    = $this->config[$env]['consumer_key'];
        $this->consumerSecret = $this->config[$env]['consumer_secret'];
        $this->baseUrl        = $this->config[$env]['base_url'];
    }

    public function config()
    {
        return $this->config;
    }

    private function request($method, $path, $data = [], $token = null)
    {
        $url = $this->baseUrl . $path;

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init();
        $opts = [
            CURLOPT_URL            => ($method === 'GET' && $data) ? $url . '?' . http_build_query($data) : $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false, // Set to true in production
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new RuntimeException("Pesapal request failed: $curlErr");
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 400 || $decoded === null) {
            throw new RuntimeException("Pesapal API error (HTTP $httpCode) on $path: " . ($response ?: 'empty response'));
        }

        return $decoded;
    }

    public function getAccessToken()
    {
        $resp = $this->request('POST', '/Auth/RequestToken', [
            'consumer_key'    => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ]);
        if (empty($resp['token'])) {
            throw new RuntimeException('Pesapal auth failed: ' . json_encode($resp));
        }
        return $resp['token'];
    }

    public function registerIpn($ipnUrl)
    {
        $token = $this->getAccessToken();
        $resp  = $this->request('POST', '/URLSetup/RegisterIPN', [
            'url'                   => $ipnUrl,
            'ipn_notification_type' => 'GET',
        ], $token);
        if (empty($resp['ipn_id'])) {
            throw new RuntimeException('Pesapal IPN registration failed: ' . json_encode($resp));
        }
        return $resp['ipn_id'];
    }

    /**
     * Submit order to Pesapal
     */
   public function submitOrder($orderData)
{
    $token = $this->getAccessToken();
    
    // Validate required fields
    $required = ['id', 'amount', 'currency', 'description', 'callback_url', 'notification_id'];
    foreach ($required as $field) {
        if (empty($orderData[$field])) {
            throw new RuntimeException("Missing required field: $field");
        }
    }
    
    $resp = $this->request('POST', '/Transactions/SubmitOrderRequest', $orderData, $token);

    if (empty($resp['redirect_url'])) {
        throw new RuntimeException('Pesapal order submission failed: ' . json_encode($resp));
    }

    return $resp;
}

    public function getTransactionStatus($orderTrackingId)
    {
        $token = $this->getAccessToken();
        return $this->request('GET', '/Transactions/GetTransactionStatus', [
            'orderTrackingId' => $orderTrackingId,
        ], $token);
    }
}