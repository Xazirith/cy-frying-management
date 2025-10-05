<?php
declare(strict_types=1);

final class DiscordLogger
{
    private static function webhook(): string {
        return (string)(getenv('DISCORD_WEBHOOK_URL') ?: (defined('DISCORD_WEBHOOK_URL') ? DISCORD_WEBHOOK_URL : ''));
    }

    private static function storagePath(): string {
        return defined('STORAGE_PATH') ? STORAGE_PATH : __DIR__ . '/../storage';
    }

    private static function throttleSeconds(): int {
        $v = getenv('DISCORD_LOG_THROTTLE') ?: (defined('DISCORD_LOG_THROTTLE') ? DISCORD_LOG_THROTTLE : 30);
        return max(0, (int)$v);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $url = self::webhook();
        if ($url === '') return; // not configured

        $payload = self::buildPayload($level, $message, $context);

        // Throttle duplicate messages
        $throttleKey = substr(hash('sha256', json_encode([$level, $message, $context])), 0, 16);
        $flagDir = self::storagePath() . '/discord-flags';
        @mkdir($flagDir, 0775, true);
        $flagFile = $flagDir . '/' . $throttleKey . '.flag';
        $now = time();
        $since = is_file($flagFile) ? (int)@file_get_contents($flagFile) : 0;
        if ($since && ($now - $since) < self::throttleSeconds()) return;
        @file_put_contents($flagFile, (string)$now);

        // Prefer cURL; fallback to streams
        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'User-Agent: CY-Frying/1.0'
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT_MS => 800,
                    CURLOPT_TIMEOUT_MS => 1500,
                ]);
                $res = curl_exec($ch);
                $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                // If rate-limited (429), we ignore (best-effort). For debugging, you can write to a local log.
                return;
            }

            // Fallback
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\nUser-Agent: CY-Frying/1.0\r\n",
                    'content' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                    'timeout' => 1.5,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);
            @file_get_contents($url, false, $ctx);
        } catch (\Throwable $e) {
            // Never throw from logger
            error_log('[DiscordLogger] '.$e->getMessage());
        }
    }

    private static function buildPayload(string $level, string $message, array $context): array
    {
        // Flatten & redact
        $safeCtx = [];
        foreach ($context as $k => $v) {
            if (preg_match('/pass(word)?|secret|token|cookie|key/i', (string)$k)) {
                $safeCtx[$k] = '***REDACTED***';
            } else {
                $safeCtx[$k] = is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_SLASHES);
            }
        }

        $content = '';
        // Keep under 2000 chars
        $line = strtoupper($level).': '.$message;
        if (strlen($line) <= 1800) {
            $content = $line;
        }

        // Prefer rich embed so it’s readable in Discord
        $embed = [
            'title' => strtoupper($level),
            'description' => substr($message, 0, 2048),
            'color' => match (strtolower($level)) {
                'error','fatal' => 0xE74C3C,
                'warn','warning' => 0xF1C40F,
                'info' => 0x3498DB,
                'debug' => 0x95A5A6,
                default => 0xE67E22,
            },
            'fields' => [],
            'footer' => ['text' => 'CY Frying • '.date('c')],
        ];

        foreach ($safeCtx as $k => $v) {
            if ($v === '' || $v === null) continue;
            $embed['fields'][] = [
                'name' => (string)$k,
                'value' => '```'.substr((string)$v, 0, 1000).'```',
                'inline' => false
            ];
            if (count($embed['fields']) >= 10) break; // stay within Discord limits
        }

        return [
            'username' => 'CY Frying Logs',
            'content'  => $content,
            'embeds'   => [$embed],
        ];
    }
}
