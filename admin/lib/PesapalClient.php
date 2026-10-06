<?php
// admin/lib/PesapalClient.php

class PesapalClient
{
    private array $config;
    private string $baseUrl;
    private string $cacheDir;
    private string $tokenFile;
    private string $tokenLockFile;

    public function __construct()
    {
        $configFile = __DIR__ . '/../config/pesapal.php';

        if (!file_exists($configFile)) {
            throw new Exception(
                'Pesapal configuration file not found: ' . $configFile
            );
        }

        $this->config = require $configFile;

        if (empty($this->config['base_url'])) {
            throw new Exception('Pesapal base URL is missing.');
        }

        /*
         * Remove any accidental /api from the end.
         *
         * This protects against:
         *
         * /pesapalv3/api/api/Auth/RequestToken
         */
        $this->baseUrl = rtrim(
            preg_replace(
                '#/api/?$#i',
                '',
                trim($this->config['base_url'])
            ),
            '/'
        );

        $this->cacheDir = $this->config['cache_dir']
            ?? (__DIR__ . '/../cache');

        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0775, true) && !is_dir($this->cacheDir)) {
                throw new Exception(
                    'Unable to create Pesapal cache directory: ' .
                    $this->cacheDir
                );
            }
        }

        $this->tokenFile = $this->cacheDir . '/pesapal_token.json';
        $this->tokenLockFile = $this->cacheDir . '/pesapal_token.lock';
    }

    /**
     * Return configuration.
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * Get Pesapal API base URL.
     *
     * Example:
     * https://cybqa.pesapal.com/pesapalv3
     */
    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    
    public function getToken(): string
    {
        /*
         * First attempt to use cached token.
         */
        $cached = $this->readCachedToken();

        if ($cached !== null) {
            return $cached;
        }

       
        $lockHandle = fopen($this->tokenLockFile, 'c');

        if ($lockHandle === false) {
            throw new Exception('Unable to create Pesapal token lock.');
        }

        try {
            if (!flock($lockHandle, LOCK_EX)) {
                throw new Exception('Unable to lock Pesapal token cache.');
            }

            /*
             * Another request may have refreshed the token
             * while we were waiting for the lock.
             */
            $cached = $this->readCachedToken();

            if ($cached !== null) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);

                return $cached;
            }

            $token = $this->requestNewToken();

            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);

            return $token;

        } catch (Throwable $e) {

            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);

            throw $e;
        }
    }

    /**
     * Request a new Pesapal authentication token.
     */
    private function requestNewToken(): string
    {
        $url = $this->baseUrl . '/api/Auth/RequestToken';

        $payload = [
            'consumer_key' => $this->config['consumer_key'] ?? '',
            'consumer_secret' => $this->config['consumer_secret'] ?? '',
        ];

        if (
            empty($payload['consumer_key']) ||
            empty($payload['consumer_secret'])
        ) {
            throw new Exception(
                'Pesapal consumer_key or consumer_secret is missing.'
            );
        }

        $response = $this->request(
            'POST',
            $url,
            $payload,
            false
        );

        if (empty($response['token'])) {
            throw new Exception(
                'Pesapal authentication failed: ' .
                json_encode($response)
            );
        }

        /*
         * Pesapal supplies expiryDate.
         * We subtract 30 seconds to avoid using a token
         * that is about to expire.
         */
        $expiresAt = time() + (4 * 60);

        if (!empty($response['expiryDate'])) {
            $timestamp = strtotime($response['expiryDate']);

            if ($timestamp !== false) {
                $expiresAt = $timestamp - 30;
            }
        }

        $cache = [
            'token' => $response['token'],
            'expires_at' => $expiresAt,
        ];

        file_put_contents(
            $this->tokenFile,
            json_encode($cache, JSON_PRETTY_PRINT),
            LOCK_EX
        );

        return $response['token'];
    }

    /**
     * Read cached token.
     */
    private function readCachedToken(): ?string
    {
        if (!file_exists($this->tokenFile)) {
            return null;
        }

        $contents = file_get_contents($this->tokenFile);

        if ($contents === false || trim($contents) === '') {
            return null;
        }

        $data = json_decode($contents, true);

        if (!is_array($data)) {
            return null;
        }

        if (
            empty($data['token']) ||
            empty($data['expires_at'])
        ) {
            return null;
        }

        if ((int) $data['expires_at'] <= time()) {
            return null;
        }

        return $data['token'];
    }

    /**
     * Get all registered IPNs.
     */
    public function getRegisteredIpns(): array
    {
        $token = $this->getToken();

        $url = $this->baseUrl . '/api/URLSetup/GetIpnList';

        $response = $this->request(
            'GET',
            $url,
            null,
            true,
            $token
        );

        /*
         * Pesapal returns an array.
         */
        if (!is_array($response)) {
            throw new Exception(
                'Invalid Pesapal IPN response: ' .
                json_encode($response)
            );
        }

        return $response;
    }

    /**
     * Find IPN ID using the configured IPN URL.
     */
    public function getIpnIdByUrl(string $ipnUrl): string
    {
        $ipns = $this->getRegisteredIpns();

        foreach ($ipns as $ipn) {

            if (
                !empty($ipn['url']) &&
                rtrim($ipn['url'], '/') === rtrim($ipnUrl, '/') &&
                !empty($ipn['ipn_id'])
            ) {
                return $ipn['ipn_id'];
            }
        }

        throw new Exception(
            'The configured IPN URL is not registered with Pesapal: ' .
            $ipnUrl
        );
    }

    /**
     * Submit payment order.
     */
    public function submitOrder(array $orderData): array
    {
        $token = $this->getToken();

        $url = $this->baseUrl .
            '/api/Transactions/SubmitOrderRequest';

        return $this->request(
            'POST',
            $url,
            $orderData,
            true,
            $token
        );
    }

    /**
     * Get transaction status.
     */
    public function getTransactionStatus(
        string $orderTrackingId
    ): array {

        if ($orderTrackingId === '') {
            throw new Exception(
                'OrderTrackingId is required.'
            );
        }

        $token = $this->getToken();

        $url = $this->baseUrl .
            '/api/Transactions/GetTransactionStatus' .
            '?orderTrackingId=' .
            rawurlencode($orderTrackingId);

        return $this->request(
            'GET',
            $url,
            null,
            true,
            $token
        );
    }

    /**
     * Generic HTTP request.
     */
    private function request(
        string $method,
        string $url,
        ?array $payload = null,
        bool $authenticated = true,
        ?string $token = null
    ): array {

        $ch = curl_init();

        if ($ch === false) {
            throw new Exception('Unable to initialize cURL.');
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if ($authenticated) {
            if (empty($token)) {
                throw new Exception(
                    'Pesapal authentication token is missing.'
                );
            }

            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => false,
        ];

        if ($method === 'POST') {

            $options[CURLOPT_POST] = true;

            if ($payload !== null) {
                $options[CURLOPT_POSTFIELDS] =
                    json_encode(
                        $payload,
                        JSON_UNESCAPED_SLASHES
                    );
            }
        }

        curl_setopt_array($ch, $options);

        $rawResponse = curl_exec($ch);

        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        curl_close($ch);

        if ($rawResponse === false) {
            throw new Exception(
                'Pesapal connection failed: ' . $curlError
            );
        }

        $response = json_decode(
            $rawResponse,
            true
        );

        if (!is_array($response)) {
            throw new Exception(
                'Invalid response from Pesapal. HTTP ' .
                $httpCode .
                ': ' .
                $rawResponse
            );
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception(
                'Pesapal API error. HTTP ' .
                $httpCode .
                ': ' .
                $rawResponse
            );
        }

        /*
         * Pesapal can return an error object even with HTTP 200.
         */
        if (
            isset($response['error']) &&
            $response['error'] !== null &&
            $response['error'] !== ''
        ) {
            throw new Exception(
                'Pesapal API error: ' .
                json_encode($response['error'])
            );
        }

        return $response;
    }


    /**
     * Register an IPN URL with Pesapal.
     *
     * @param string $ipnUrl
     * @param string $notificationType GET or POST
     * @return array
     */
    public function registerIpn(
        string $ipnUrl,
        string $notificationType = 'GET'
    ): array {

        $ipnUrl = trim($ipnUrl);

        if ($ipnUrl === '') {
            throw new Exception(
                'Pesapal IPN URL cannot be empty.'
            );
        }

        $notificationType =
            strtoupper(trim($notificationType));

        if (
            !in_array(
                $notificationType,
                ['GET', 'POST'],
                true
            )
        ) {
            throw new Exception(
                'Pesapal IPN notification type must be GET or POST.'
            );
        }

        $token =
            $this->getToken();

        $url =
            $this->baseUrl .
            '/api/URLSetup/RegisterIPN';

        $payload = [
            'url' => $ipnUrl,
            'ipn_notification_type' =>
                $notificationType,
        ];

        $response =
            $this->request(
                'POST',
                $url,
                $payload,
                true,
                $token
            );

        /*
         * Pesapal should return an ipn_id.
         */
        if (empty($response['ipn_id'])) {

            throw new Exception(
                'Pesapal did not return an IPN ID: ' .
                json_encode($response)
            );
        }

        return $response;
    }
}

