
<?php

$projectRoot = dirname(__DIR__, 2);
$autoload = $projectRoot . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
}

if (
    class_exists('Dotenv\\Dotenv') &&
    is_file($projectRoot . '/.env')
) {
    Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
}

$verifyToken = trim((string) ($_ENV['WHATSAPP_VERIFY_TOKEN'] ?? ''));

file_put_contents(
    __DIR__ . '/webhook-debug.txt',
    'ENV token loaded: ' . ($verifyToken !== '' ? 'YES' : 'NO') . PHP_EOL .
    'ENV token matches expected: ' .
    (hash_equals('JobSearch_Uganda_Verify_2026_X7p9', $verifyToken) ? 'YES' : 'NO') . PHP_EOL .
    'Request method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . PHP_EOL .
    'Mode received: ' . ($_GET['hub_mode'] ?? $_GET['hub.mode'] ?? 'MISSING') . PHP_EOL .
    'Request token supplied: ' .
    (isset($_GET['hub_verify_token']) || isset($_GET['hub.verify_token']) ? 'YES' : 'NO') .
    PHP_EOL . "---" . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    if (
        $mode === 'subscribe' &&
        $verifyToken !== '' &&
        $token !== '' &&
        hash_equals($verifyToken, (string) $token)
    ) {
        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');
        echo $challenge;
    } else {
        error_log(
            'WhatsApp verification failed. Mode valid: ' .
            ($mode === 'subscribe' ? 'yes' : 'no') .
            '; configured token present: ' .
            ($verifyToken !== '' ? 'yes' : 'no')
        );

        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Verification failed';
    }

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'EVENT_RECEIVED';
    exit;
}

http_response_code(405);
header('Allow: GET, POST');
echo 'Method not allowed';