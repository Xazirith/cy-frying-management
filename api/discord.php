<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/DiscordLogger.php';
require_once __DIR__ . '/../core/KillSwitch.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'Method Not Allowed']); exit;
}

$body = file_get_contents('php://input') ?: '';
$sign = $_SERVER['HTTP_X_SIGNATURE_ED25519'] ?? '';
$ts   = $_SERVER['HTTP_X_SIGNATURE_TIMESTAMP'] ?? '';

if (!DISCORD_PUBLIC_KEY || !function_exists('sodium_crypto_sign_verify_detached')) {
    http_response_code(500);
    echo json_encode(['error'=>'Discord signature verification not configured']); exit;
}

if (!verify_signature($sign, $ts, $body, DISCORD_PUBLIC_KEY)) {
    http_response_code(401);
    echo json_encode(['error'=>'invalid request signature']); exit;
}

$payload = json_decode($body, true) ?? [];

// PING -> PONG
if (($payload['type'] ?? null) === 1) { echo json_encode(['type'=>1]); exit; }

if (($payload['type'] ?? null) !== 2) {
    echo json_encode(['type'=>4,'data'=>['content'=>'Unsupported interaction.']]); exit;
}

$cmd = strtolower($payload['data']['name'] ?? '');
$userId = (string)($payload['member']['user']['id'] ?? $payload['user']['id'] ?? '');
if (DISCORD_ALLOWED_USER_ID && $userId !== DISCORD_ALLOWED_USER_ID) {
    echo json_encode(['type'=>4,'data'=>['flags'=>64,'content'=>'Not allowed.']]); exit;
}

switch ($cmd) {
    case 'kill':
        $reason = trim((string)($payload['data']['options'][0]['value'] ?? ''));
        KillSwitch::engage($reason);
        echo json_encode(['type'=>4,'data'=>['flags'=>64,'content'=>"🔴 Kill switch engaged.\nReason: ".($reason ?: '(none)')]]);
        break;
    case 'resume':
        KillSwitch::release();
        echo json_encode(['type'=>4,'data'=>['flags'=>64,'content'=>"🟢 Application resumed."]]);
        break;
    default:
        echo json_encode(['type'=>4,'data'=>['content'=>'Unknown command.']]);
}

function verify_signature(string $sigHex, string $timestamp, string $body, string $pubHex): bool {
    $sig = sodium_hex2bin($sigHex);
    $pk  = sodium_hex2bin($pubHex);
    return sodium_crypto_sign_verify_detached($sig, $timestamp . $body, $pk);
}
