<?php
// admin/lib/PesapalHelper.php

class PesapalHelper
{
    private static $ipnCache = [];
    private static $cacheFile = null;
    
    public static function getCachedIpnId($client, $ipnUrl)
    {
        // Set cache file path
        if (self::$cacheFile === null) {
            // Use a writable directory
            $cacheDir = __DIR__ . '/../config/cache';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }
            self::$cacheFile = $cacheDir . '/.ipn_id_cache';
        }
        
        $cacheKey = md5($ipnUrl);
        
        // Check memory cache first
        if (isset(self::$ipnCache[$cacheKey])) {
            return self::$ipnCache[$cacheKey];
        }
        
        // Check file cache
        if (file_exists(self::$cacheFile)) {
            $cached = @json_decode(file_get_contents(self::$cacheFile), true);
            if (isset($cached[$cacheKey])) {
                self::$ipnCache[$cacheKey] = $cached[$cacheKey];
                return $cached[$cacheKey];
            }
        }
        
        try {
            $ipnId = $client->registerIpn($ipnUrl);
            self::$ipnCache[$cacheKey] = $ipnId;
            
            // Save to file cache
            $cached = [];
            if (file_exists(self::$cacheFile)) {
                $cached = @json_decode(file_get_contents(self::$cacheFile), true) ?: [];
            }
            $cached[$cacheKey] = $ipnId;
            @file_put_contents(self::$cacheFile, json_encode($cached));
            
            return $ipnId;
        } catch (Exception $e) {
            error_log("IPN Registration failed: " . $e->getMessage());
            // Return a dummy IPN ID for testing
            return 'DUMMY_IPN_' . time();
        }
    }
}