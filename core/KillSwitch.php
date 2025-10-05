<?php
declare(strict_types=1);

final class KillSwitch
{
    private const FLAG_FILE = 'kill.flag';
    private const CACHE_TTL = 5; // seconds
    private static ?array $cache = null; // ['on'=>bool,'reason'=>string,'ts'=>int]

    public static function guard(): void
    {
        if (!self::isEngaged()) return;

        http_response_code(503);
        header('Retry-After: 60');
        $reason = htmlspecialchars(self::reason() ?: 'Maintenance', ENT_QUOTES, 'UTF-8');
        echo "<!doctype html><meta charset='utf-8'><title>Temporarily Unavailable</title>
        <style>
          body{font-family:system-ui;margin:5vw;color:#333}
          .wrap{max-width:720px;margin:auto}
          h1{font-weight:800;color:#b91c1c}
          .box{background:#fff1f2;border:1px solid #ffc7cd;padding:1rem;border-radius:10px}
        </style>
        <div class='wrap'><h1>Service Paused</h1>
          <div class='box'>
            <p>The application is temporarily paused by the developer.</p>
            <p><strong>Reason:</strong> {$reason}</p>
            <p>Please try again shortly.</p>
          </div>
        </div>";
        exit;
    }

    public static function isEngaged(): bool { return self::state()['on']; }
    public static function reason(): string  { return self::state()['reason']; }

    public static function engage(string $reason=''): void
    {
        self::setDb('1', $reason);
        self::writeFlag($reason);
        self::$cache = null;
        DiscordLogger::event('Kill Switch Engaged', 'Application entering protective shutdown.', ['reason'=>$reason], 0xE61E2A);
    }

    public static function release(): void
    {
        self::setDb('0', '');
        @unlink(STORAGE_PATH . '/' . self::FLAG_FILE);
        self::$cache = null;
        DiscordLogger::event('Kill Switch Released', 'Application resumed.', [], 0x22C55E);
    }

    /* ---------- internals ---------- */

    private static function state(): array
    {
        if (self::$cache && (time() - (self::$cache['ts'] ?? 0)) < self::CACHE_TTL) return self::$cache;

        $on = false; $reason = '';
        $file = STORAGE_PATH . '/' . self::FLAG_FILE;

        if (is_file($file)) {
            $on = true; $reason = trim((string)@file_get_contents($file));
        } else {
            try {
                if (class_exists('Database')) {
                    $db = Database::getInstance()->mysqli();
                    if ($db) {
                        $stmt = $db->prepare("SELECT `key`,`value` FROM app_settings WHERE `key` IN (?,?)");
                        $k1 = KILL_SWITCH_KEY; $k2 = KILL_REASON_KEY;
                        $stmt->bind_param('ss', $k1, $k2);
                        if ($stmt->execute() && ($res = $stmt->get_result())) {
                            while ($row = $res->fetch_assoc()) {
                                if ($row['key'] === KILL_SWITCH_KEY) $on = ($row['value'] === '1');
                                if ($row['key'] === KILL_REASON_KEY) $reason = (string)$row['value'];
                            }
                        }
                        $stmt?->close();
                    }
                }
            } catch (\Throwable $e) {
                DiscordLogger::log('error', 'KillSwitch DB read failed', ['type'=>get_class($e),'msg'=>$e->getMessage()]);
            }
        }

        return self::$cache = ['on'=>$on, 'reason'=>$reason, 'ts'=>time()];
    }

    private static function setDb(string $on, string $reason): void
    {
        try {
            $db = Database::getInstance()->mysqli();
            if (!$db) return;

            $upsert = fn($k,$v) => tap(function($db,$k,$v){
                $stmt = $db->prepare("INSERT INTO app_settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
                $stmt->bind_param('ss',$k,$v); $stmt->execute(); $stmt->close();
            }, $db, $k, $v);

            $upsert($db, KILL_SWITCH_KEY, $on);
            $upsert($db, KILL_REASON_KEY, $reason);
        } catch (\Throwable $e) {
            DiscordLogger::log('error', 'KillSwitch DB write failed', ['type'=>get_class($e),'msg'=>$e->getMessage()]);
        }
    }

    private static function writeFlag(string $reason): void
    {
        if (!is_dir(STORAGE_PATH)) @mkdir(STORAGE_PATH, 0775, true);
        @file_put_contents(STORAGE_PATH . '/' . self::FLAG_FILE, $reason);
    }
}

/* tiny helper for inline closure call */
function tap(Closure $c, ...$args) { $c(...$args); return true; }
