<?php
require __DIR__.'/core/DiscordLogger.php';

// Set directly here just for this test (or rely on your .env / FPM env)
putenv('DISCORD_WEBHOOK_URL=YOUR_WEBHOOK_URL_HERE');

DiscordLogger::log('info', 'Hello from CY Frying test!', [
  'reqId' => bin2hex(random_bytes(4)),
  'host'  => gethostname(),
  'ip'    => $_SERVER['SERVER_ADDR'] ?? 'cli',
]);

echo "sent\n";
