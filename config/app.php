<?php
declare(strict_types=1);

// Discord webhook for logs (errors, deploys, admin actions)
define('DISCORD_WEBHOOK_URL', getenv('DISCORD_WEBHOOK_URL') ?: '');

// Discord Interactions Public Key (hex) – from Discord Dev Portal
define('DISCORD_PUBLIC_KEY', getenv('DISCORD_PUBLIC_KEY') ?: '');

// Only this Discord user ID may toggle kill switch
define('DISCORD_ALLOWED_USER_ID', getenv('DISCORD_ALLOWED_USER_ID') ?: '');

// Throttle duplicate logs (seconds)
define('DISCORD_LOG_THROTTLE', 30);

// Writable storage (flag lives here)
define('STORAGE_PATH', __DIR__ . '/../storage');

// Settings keys in DB
define('KILL_SWITCH_KEY', 'kill_switch');
define('KILL_REASON_KEY', 'kill_reason');
