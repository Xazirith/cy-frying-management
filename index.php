<?php
declare(strict_types=1);

/**
 * CY Frying — Front Controller (v2)
 * - Security headers
 * - .env loading (composer or fallback)
 * - App config, Discord logging, Kill Switch
 * - Robust error handling + performance logs
 * - Modular bootstrap + API routing + diagnostics
 */

/* ---------------- Security Headers ---------------- */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer-when-downgrade');

/* ---------------- Paths & Boot Vars ---------------- */
$APP_START = microtime(true);
$REQ_ID    = bin2hex(random_bytes(6));
$ROOT      = __DIR__;
$LOG_DIR   = $ROOT . '/logs';
$APP_LOG   = $LOG_DIR . '/app.log';
$PERF_LOG  = $LOG_DIR . '/performance.log';

if (!is_dir($LOG_DIR)) {
    @mkdir($LOG_DIR, 0775, true);
    @file_put_contents($LOG_DIR.'/.htaccess', "Deny from all\n");
}
function app_req_id(): string { global $REQ_ID; return $REQ_ID; }

/* ---------------- Optional .env Loading ----------------
 * Uses vlucas/phpdotenv if available; otherwise a tiny fallback.
 */
if (is_file($ROOT.'/vendor/autoload.php')) {
    require_once $ROOT.'/vendor/autoload.php';
}
if (class_exists('Dotenv\\Dotenv')) {
    try { Dotenv\Dotenv::createImmutable($ROOT)->safeLoad(); } catch (Throwable $e) { error_log('[ENV] '.$e->getMessage()); }
} else {
    $envFile = $ROOT.'/.env';
    if (is_file($envFile) && is_readable($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if ($line[0] === '#' || !str_contains($line, '=')) continue;
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $v = trim($v, " \t\n\r\0\x0B\"'");
            if ($k !== '') { putenv("$k=$v"); $_ENV[$k] = $v; $_SERVER[$k] = $v; }
        }
    }
}

/* ---------------- Debug Mode & CIDR Check ---------------- */
$APP_DEBUG = (string)(getenv('APP_DEBUG') ?: '0') === '1';
$DEBUG_IP_WHITELIST = ['127.0.0.1', '::1', '10.0.0.0/8', '192.168.0.0/16'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$is_debug_allowed = $APP_DEBUG && ipAllowed($client_ip, $DEBUG_IP_WHITELIST);

function ipAllowed(string $ip, array $allow): bool {
    foreach ($allow as $range) {
        if (strpos($range, '/') === false) { if ($ip === $range) return true; continue; }
        [$subnet, $bits] = explode('/', $range, 2);
        $ipLong = ip2long($ip); $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) continue;
        $mask = -1 << (32 - (int)$bits);
        if (($ipLong & $mask) === ($subnetLong & $mask)) return true;
    }
    return false;
}

/* ---------------- App Config + Discord + Kill Switch ---------------- */
require_once $ROOT.'/config/app.php';            // defines APP_NAME/APP_VERSION/Discord env keys/etc.
require_once $ROOT.'/core/DiscordLogger.php';    // fire-and-forget Discord logger (uses DISCORD_WEBHOOK_URL)
require_once $ROOT.'/core/KillSwitch.php';       // KillSwitch::guard() exits with 503 if engaged

/* ---------------- Logging Helpers ---------------- */
function app_log(string $level, string $msg, array $ctx = []): void {
    global $APP_LOG, $REQ_ID, $LOG_DIR, $APP_DEBUG;
    $safe = [];
    foreach ($ctx as $k => $v) {
        $kn = (string)$k;
        $safe[$kn] = preg_match('/pass(word)?|secret|token|cookie|key/i', $kn)
            ? '***REDACTED***'
            : (is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_SLASHES));
    }
    $line = sprintf("[%s] [%s] [%s] %s %s\n", date('c'), $REQ_ID, strtoupper($level), $msg, $safe ? json_encode($safe, JSON_UNESCAPED_SLASHES) : '');
    @file_put_contents($APP_LOG, $line, FILE_APPEND);
    try { DiscordLogger::log($level, $msg, ['reqId'=> $REQ_ID] + $safe); } catch (Throwable $e) {}
    if ($APP_DEBUG) @file_put_contents($LOG_DIR."/{$level}.log", $line, FILE_APPEND);
}
function perf_log(string $operation, float $start_time, array $context = []): void {
    global $PERF_LOG, $REQ_ID;
    $ms = round((microtime(true) - $start_time)*1000, 2);
    $line = sprintf("[%s] [%s] %s took %.2fms %s\n", date('c'), $REQ_ID, $operation, $ms, $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : '');
    @file_put_contents($PERF_LOG, $line, FILE_APPEND);
}

/* ---------------- Error / Exception Handling ---------------- */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    $names = [E_ERROR=>'E_ERROR',E_WARNING=>'E_WARNING',E_PARSE=>'E_PARSE',E_NOTICE=>'E_NOTICE',E_CORE_ERROR=>'E_CORE_ERROR',E_CORE_WARNING=>'E_CORE_WARNING',E_COMPILE_ERROR=>'E_COMPILE_ERROR',E_COMPILE_WARNING=>'E_COMPILE_WARNING',E_USER_ERROR=>'E_USER_ERROR',E_USER_WARNING=>'E_USER_WARNING',E_USER_NOTICE=>'E_USER_NOTICE',E_STRICT=>'E_STRICT',E_RECOVERABLE_ERROR=>'E_RECOVERABLE_ERROR',E_DEPRECATED=>'E_DEPRECATED',E_USER_DEPRECATED=>'E_USER_DEPRECATED'];
    app_log('error', "PHP ".($names[$errno] ?? "E_{$errno}").": {$errstr}", ['file'=>$errfile,'line'=>$errline,'errno'=>$errno]);
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
set_exception_handler(function(Throwable $ex) use ($is_debug_allowed) {
    app_log('error', 'Uncaught exception', [
        'type'=>get_class($ex),'msg'=>$ex->getMessage(),'file'=>$ex->getFile(),
        'line'=>$ex->getLine(),'trace'=>$is_debug_allowed ? $ex->getTraceAsString() : '(hidden)','code'=>$ex->getCode()
    ]);
    http_response_code(500);
    if (is_api_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'error'=>$is_debug_allowed ? [
            'type'=>get_class($ex),'msg'=>$ex->getMessage(),'file'=>$ex->getFile(),'line'=>$ex->getLine(),'trace'=>$ex->getTrace()
        ] : 'Server error','reqId'=>app_req_id()], JSON_UNESCAPED_SLASHES);
    } else {
        echo "<!doctype html><meta charset='utf-8'><title>Server Error</title><style>body{font-family:system-ui;max-width:800px;margin:2rem auto;padding:1rem}.box{background:#fee;border:1px solid #fcc;padding:1rem;border-radius:6px}pre{white-space:pre-wrap;background:#f5f5f5;padding:1rem}</style><div class='box'><h1>Server Error</h1><p><b>Request ID:</b> ".htmlspecialchars(app_req_id())."</p>";
        echo $is_debug_allowed ? "<pre>".htmlspecialchars((string)$ex)."</pre>" : "<p>Please contact support if this persists.</p>";
        echo "</div>";
    }
    exit;
});
register_shutdown_function(function() use ($is_debug_allowed) {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        app_log('fatal', 'Shutdown fatal', $e);
        http_response_code(500);
        if (is_api_request()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>false,'error'=>$is_debug_allowed ? $e['message'] : 'Server error','reqId'=>app_req_id()]);
        } else {
            echo "<!doctype html><meta charset='utf-8'><h1>Server Error</h1><p>Request ID: ".htmlspecialchars(app_req_id())."</p>";
            if ($is_debug_allowed) echo "<pre>".htmlspecialchars($e['message'].' @ '.$e['file'].':'.$e['line'])."</pre>";
        }
    }
});

/* ---------------- Small Helpers ---------------- */
function is_api_request(): bool {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (!empty($_POST['action'])) return true;
    if (stripos($ct, 'application/json') !== false) return true;
    if (isset($_GET['api']) || isset($_GET['ajax'])) return true;
    return (bool)preg_match('#^/api(/|$)#', $path);
}

/* ---------------- Load DB Config Early ---------------- */
$cfg_start = microtime(true);
require_once $ROOT.'/config/database.php';   // defines DB_* constants
perf_log('Load config', $cfg_start);

/* ---------------- Kill Switch Guard ---------------- */
KillSwitch::guard(); // outputs 503 & exits if engaged

/* ---------------- Lightweight Special Routes ---------------- */
$__path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

/* /~~~~~ diagnostics (localhost only) */
if ($__path === '/~~~~~') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1','::1'], true)) { http_response_code(403); exit('Forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== CY Frying Advanced Diagnostics ===\n";
    echo "Request ID: ".app_req_id()."\n";
    echo "Time: ".date('c')."\n";
    echo "PHP: ".PHP_VERSION." (Zend ".zend_version().")\n";
    echo "Server: ".php_uname()."\n";
    echo "Memory: ".round(memory_get_usage(true)/1048576,2)."MB / ".round(memory_get_peak_usage(true)/1048576,2)."MB peak\n";
    $exts = ['mysqli','pdo_mysql','json','session','filter','openssl','sodium'];
    echo "Extensions: "; foreach ($exts as $ext) echo $ext.'='.(extension_loaded($ext)?'yes':'NO').' '; echo "\n";
    echo "App: ".(defined('APP_NAME')?APP_NAME:'CY Frying')." v".(defined('APP_VERSION')?APP_VERSION:'?')."\n";
    echo "Debug: ".($GLOBALS['APP_DEBUG'] ? 'ENABLED' : 'disabled')."\n";
    // DB quick probe
    $dbReport = 'skipped'; $t0=microtime(true);
    if (defined('DB_HOST') && extension_loaded('mysqli')) {
        if (function_exists('mysqli_report')) @mysqli_report(MYSQLI_REPORT_OFF);
        $m=@new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, defined('DB_PORT')?(int)DB_PORT:3306);
        if ($m && !$m->connect_errno) {
            $ver=$m->server_info; $stats=['users'=>'?','menu_items'=>'?','orders'=>'?'];
            if ($res=$m->query("SELECT (SELECT COUNT(*) FROM users) users,(SELECT COUNT(*) FROM menu_items) menu_items,(SELECT COUNT(*) FROM orders) orders")) $stats=$res->fetch_assoc() ?: $stats;
            $m->close(); $dbReport="connected (MySQL {$ver}, users: {$stats['users']}, menu_items: {$stats['menu_items']}, orders: {$stats['orders']})";
        } else { $dbReport='ERROR '.($m?$m->connect_errno:'n/a').': '.($m?$m->connect_error:'constructor failed'); }
    }
    echo "Database: {$dbReport} (".round((microtime(true)-$t0)*1000,2)."ms)\n";
    echo "File System:\n";
    foreach (['/','/config','/core','/api','/templates','/assets','/logs','/storage'] as $p) {
        $full=$ROOT.$p; $status=is_dir($full)?'dir':(is_file($full)?'file':'MISSING'); $perms=file_exists($full)?substr(sprintf('%o', fileperms($full)),-4):'----';
        echo "  {$p}: {$status} ({$perms})\n";
    }
    echo "\n=== Recent Errors (last 10) ===\n";
    if (file_exists($APP_LOG)) {
        $lines=@file($APP_LOG, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) ?: [];
        $err=array_filter($lines, fn($l)=>stripos($l,'[ERROR]')!==false||stripos($l,'[FATAL]')!==false);
        echo implode("\n", array_slice($err, -10))."\n";
    }
    exit;
}

/* Discord slash/interactions endpoint (kill-switch control, etc.) */
if ($__path === '/api/discord') {
    require_once $ROOT.'/api/discord.php';
    exit;
}

/* ---------------- Core Bootstrap ---------------- */
$boot_start = microtime(true);
require_once $ROOT.'/core/Database.php';
require_once $ROOT.'/core/Auth.php';
require_once $ROOT.'/core/ModuleLoader.php';
perf_log('Bootstrap core', $boot_start);

/* ---------------- Session (secure; allow HTTP locally) ---------------- */
$ss_start = microtime(true);
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_start([
        'cookie_secure'   => $isHttps,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'name'            => 'cfm_sid',
        'gc_maxlifetime'  => 3600,
    ]);
}
perf_log('Session start', $ss_start);

/* ---------------- Load Modules Safely ---------------- */
$modules = []; $auth = null; $currentUser = null; $db = null;
try {
    $t = microtime(true);
    $modules = ModuleLoader::loadCoreModules();   // Ensure Auth is constructed with DB inside ModuleLoader
    if (!isset($modules['Auth'])) throw new RuntimeException('Auth module missing from ModuleLoader');
    $auth = $modules['Auth'];
    $currentUser = $auth->getCurrentUser();
    if (class_exists('Database')) $db = Database::getInstance();

    perf_log('Load modules', $t, [
        'modules_loaded'=>count($modules),
        'user_authenticated'=>$auth->isAuthenticated(),
        'user_role'=>$currentUser['role'] ?? 'guest',
    ]);
} catch (Throwable $e) {
    app_log('error', 'Bootstrap failure', ['msg'=>$e->getMessage(), 'trace'=>$is_debug_allowed ? $e->getTraceAsString() : 'hidden']);
    throw $e;
}

/* ---------------- API Routing ---------------- */
if (is_api_request()) {
    $api_t = microtime(true);
    require_once $ROOT.'/api/index.php';
    perf_log('API request', $api_t, ['endpoint'=>($_SERVER['REQUEST_URI'] ?? 'unknown'),'method'=>($_SERVER['REQUEST_METHOD'] ?? 'unknown')]);
    exit;
}

/* ---------------- Page Render ---------------- */
$APP_TIME_MS = round((microtime(true) - $APP_START) * 1000, 1);
$title = (defined('APP_NAME') ? APP_NAME : 'CY Frying') . ' - Southern Food Truck';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-Content-Type-Options" content="nosniff">
  <meta http-equiv="X-Frame-Options" content="DENY">
  <meta http-equiv="X-XSS-Protection" content="1; mode=block">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
  <?php if ($is_debug_allowed): ?><link rel="stylesheet" href="/assets/css/debug.css?v=<?= time() ?>"><?php endif; ?>
</head>
<body>
<?php include $ROOT.'/templates/header.php'; ?>

<section id="home" class="hero">
  <h1>Frog Legs &amp; Perch Perfection</h1>
  <p>Freshly fried frog legs, perch, and southern sides served hot from our gourmet food truck.</p>
  <button class="btn" onclick="scrollToMenu()">View Our Menu</button>
</section>

<section id="menu" class="section">
  <div class="section-title">
    <h2>Our Signature Menu</h2>
    <p>Hand-battered specialties and authentic southern sides</p>
  </div>
  <div class="menu-grid" id="menuContainer"><!-- populated by JS --></div>
</section>

<section id="order" class="section">
  <div class="section-title">
    <h2>Place Your Order</h2>
    <p>Build your perfect meal and pay at our truck window</p>
  </div>
  <form class="order-form" id="orderForm" autocomplete="off" novalidate>
    <label for="customer_name">Name</label>
    <input id="customer_name" name="customer_name" required>
    <label for="customer_phone">Phone</label>
    <input id="customer_phone" name="customer_phone" type="tel" required>
    <label for="customer_email">Email (optional)</label>
    <input id="customer_email" name="customer_email" type="email">
    <label for="special_instructions">Special Instructions</label>
    <textarea id="special_instructions" name="special_instructions"></textarea>
    <button type="submit" class="btn">Submit Order</button>
  </form>
</section>

<?php include $ROOT.'/templates/modals/admin.php'; ?>
<?php include $ROOT.'/templates/modals/login.php'; ?>

<script>
const AppConfig = {
  currentUser: <?= json_encode($currentUser, JSON_INVALID_UTF8_SUBSTITUTE) ?>,
  isAuthenticated: <?= $auth && $auth->isAuthenticated() ? 'true':'false' ?>,
  isAdmin: <?= $auth && $auth->isAdmin() ? 'true':'false' ?>,
  apiUrl: '/api/index.php',
  reqId: '<?= htmlspecialchars(app_req_id(), ENT_QUOTES, "UTF-8"); ?>',
  renderTimeMs: <?= json_encode($APP_TIME_MS) ?>,
  debugEnabled: <?= $is_debug_allowed ? 'true':'false' ?>
};
</script>
<script src="/assets/js/app.js?v=<?= time() ?>"></script>

<?php if ($is_debug_allowed): ?>
<div id="debug-panel">
  <div class="debug-header">
    <div class="debug-tabs">
      <button class="debug-tab active" data-tab="overview">Overview</button>
      <button class="debug-tab" data-tab="performance">Performance</button>
      <button class="debug-tab" data-tab="database">Database</button>
      <button class="debug-tab" data-tab="session">Session</button>
      <button class="debug-tab" data-tab="logs">Logs</button>
      <button class="debug-tab" data-tab="phpinfo">PHP Info</button>
    </div>
    <div class="debug-controls">
      <button id="debug-toggle" title="Toggle Debug Panel">−</button>
      <button id="debug-clear" title="Clear Local Storage">🗑️</button>
    </div>
  </div>
  <div class="debug-content">
    <div id="tab-overview" class="debug-tab-content active">
      <h3>Application Overview</h3>
      <div class="debug-grid">
        <div class="debug-card"><h4>Request</h4>
          <table>
            <tr><td>ID:</td><td><?= app_req_id() ?></td></tr>
            <tr><td>Time:</td><td><?= $APP_TIME_MS ?>ms</td></tr>
            <tr><td>Client IP:</td><td><?= htmlspecialchars($client_ip) ?></td></tr>
            <tr><td>User Agent:</td><td><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') ?></td></tr>
          </table>
        </div>
        <div class="debug-card"><h4>PHP Env</h4>
          <table>
            <tr><td>Version:</td><td><?= PHP_VERSION ?></td></tr>
            <tr><td>Memory:</td><td><?= round(memory_get_usage(true)/1048576,2) ?>MB / <?= round(memory_get_peak_usage(true)/1048576,2) ?>MB</td></tr>
            <tr><td>Included Files:</td><td><?= count(get_included_files()) ?></td></tr>
            <tr><td>Ext:</td><td>MySQLi=<?= extension_loaded('mysqli')?'✓':'✗' ?> PDO=<?= extension_loaded('pdo_mysql')?'✓':'✗' ?></td></tr>
          </table>
        </div>
        <div class="debug-card"><h4>Database</h4>
          <table>
            <tr><td>Status:</td><td><?= $db ? 'Connected':'Disconnected' ?></td></tr>
            <tr><td>Queries:</td><td><?= ($db && method_exists($db,'getQueryLog')) ? count($db->getQueryLog()) : 'N/A' ?></td></tr>
          </table>
        </div>
        <div class="debug-card"><h4>User</h4>
          <table>
            <tr><td>Authenticated:</td><td><?= $auth && $auth->isAuthenticated() ? 'Yes':'No' ?></td></tr>
            <tr><td>Role:</td><td><?= htmlspecialchars($currentUser['role'] ?? 'guest') ?></td></tr>
            <tr><td>User ID:</td><td><?= htmlspecialchars($currentUser['id'] ?? 'N/A') ?></td></tr>
          </table>
        </div>
      </div>
    </div>
    <div id="tab-performance" class="debug-tab-content">
      <h3>Performance Metrics</h3>
      <div id="performance-chart"></div>
      <button onclick="loadPerformanceData()">Refresh Metrics</button>
    </div>
    <div id="tab-database" class="debug-tab-content">
      <h3>Database Information</h3>
      <pre id="database-info"><?php
        if ($db && method_exists($db, 'getQueryLog')) {
            echo htmlspecialchars(json_encode($db->getQueryLog(), JSON_PRETTY_PRINT));
        } else {
            echo "Query logging not available";
        }
      ?></pre>
    </div>
    <div id="tab-session" class="debug-tab-content">
      <h3>Session Data</h3>
      <pre><?= htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT)) ?></pre>
    </div>
    <div id="tab-logs" class="debug-tab-content">
      <h3>Recent Logs</h3>
      <div id="log-viewer">
        <button onclick="loadLogs()">Load Recent Logs</button>
        <div id="log-content"></div>
      </div>
    </div>
    <div id="tab-phpinfo" class="debug-tab-content">
      <h3>PHP Configuration</h3>
      <iframe src="/~~~~~?phpinfo=1" style="width:100%;height:400px;border:1px solid #ccc;"></iframe>
    </div>
  </div>
</div>
<script src="/assets/js/debug.js?v=<?= time() ?>"></script>
<?php endif; ?>
</body>
</html>
