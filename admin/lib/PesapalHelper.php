```php
<?php
// admin/lib/PesapalHelper.php

class PesapalHelper
{
    /**
     * Get the IPN ID for the supplied URL.
     *
     * If the URL is already registered with Pesapal,
     * its existing IPN ID is returned.
     *
     * If it is not registered, this method registers it
     * automatically and returns the new IPN ID.
     */
    public static function getCachedIpnId(
        PesapalClient $client,
        string $ipnUrl
    ): string {

        $config = $client->config();

        $cacheDir = $config['cache_dir']
            ?? (__DIR__ . '/../cache');

        if (!is_dir($cacheDir)) {
            if (
                !mkdir($cacheDir, 0775, true) &&
                !is_dir($cacheDir)
            ) {
                throw new Exception(
                    'Unable to create Pesapal cache directory: ' .
                    $cacheDir
                );
            }
        }

        $cacheFile =
            $cacheDir . '/pesapal_ipn.json';

        /*
         * ----------------------------------------------------
         * 1. Check local cache
         * ----------------------------------------------------
         */
        if (file_exists($cacheFile)) {

            $contents =
                file_get_contents($cacheFile);

            $data =
                json_decode(
                    $contents ?: '',
                    true
                );

            if (
                is_array($data) &&
                !empty($data['ipn_id']) &&
                !empty($data['ipn_url']) &&
                rtrim($data['ipn_url'], '/') ===
                rtrim($ipnUrl, '/')
            ) {
                return $data['ipn_id'];
            }
        }

        /*
         * ----------------------------------------------------
         * 2. Check Pesapal for an already registered IPN
         * ----------------------------------------------------
         */
        try {

            $ipnId =
                $client->getIpnIdByUrl($ipnUrl);

            /*
             * Cache the existing IPN ID.
             */
            self::cacheIpn(
                $cacheFile,
                $ipnId,
                $ipnUrl
            );

            return $ipnId;

        } catch (Throwable $e) {

            /*
             * The URL was not found.
             *
             * We will register it below.
             */
        }

        /*
         * ----------------------------------------------------
         * 3. Register the IPN automatically
         * ----------------------------------------------------
         */
        $result =
            $client->registerIpn(
                $ipnUrl,
                'GET'
            );

        if (
            !is_array($result) ||
            empty($result['ipn_id'])
        ) {
            throw new Exception(
                'Pesapal IPN registration failed: ' .
                json_encode($result)
            );
        }

        $ipnId =
            $result['ipn_id'];

        /*
         * ----------------------------------------------------
         * 4. Cache the newly registered IPN ID
         * ----------------------------------------------------
         */
        self::cacheIpn(
            $cacheFile,
            $ipnId,
            $ipnUrl
        );

        return $ipnId;
    }


    /**
     * Save IPN information locally.
     */
    private static function cacheIpn(
        string $cacheFile,
        string $ipnId,
        string $ipnUrl
    ): void {

        file_put_contents(
            $cacheFile,
            json_encode(
                [
                    'ipn_id' => $ipnId,
                    'ipn_url' => $ipnUrl,
                    'cached_at' => date('c'),
                ],
                JSON_PRETTY_PRINT
            ),
            LOCK_EX
        );
    }
}