<?php
/**
 * WhatsApp Cloud API webhook endpoint.
 *
 * - GET  : Meta verification handshake (hub.challenge).
 * - POST : Incoming message/status notifications from Meta.
 */

require_once __DIR__ . '/admin/config/connection.php';

// Verify token must match the value configured in the Meta dashboard.
$verifyToken = $_ENV['WHATSAPP_VERIFY_TOKEN'] ?? 'change-me';

/* ---------------- Verification (GET) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $mode      = $_GET['hub_mode']         ?? '';
    $token     = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge']    ?? '';

    if ($mode === 'subscribe' && hash_equals($verifyToken, $token)) {
        http_response_code(200);
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    }

    http_response_code(403);
    echo 'Verification failed';
    exit;
}

/* ---------------- Incoming events (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payload = file_get_contents('php://input');
    $data    = json_decode($payload, true);

    // Log the raw payload for debugging / later processing.
    error_log('WhatsApp webhook: ' . $payload);

    // Acknowledge receipt so Meta does not retry.
    http_response_code(200);
    echo 'EVENT_RECEIVED';
    exit;
}

http_response_code(405);
echo 'Method Not Allowed';
