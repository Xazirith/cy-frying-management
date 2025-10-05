<?php
namespace App\API;

use App\Core\ModuleLoader;
use App\Core\APIRouter;
use App\Core\Database;
use App\Modules\MenuManager;
use App\Modules\OrderManager;
use Exception;

// Start output buffering with error handling
if (!ob_start()) {
    error_log("API: Failed to start output buffering");
}

// Enhanced error reporting with logging
ini_set('display_errors', '0'); // Don't display errors in production
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// Set comprehensive error reporting based on environment
$isDebug = defined('APP_DEBUG') && APP_DEBUG;
if ($isDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CORS headers - more restrictive in production
if ($isDebug) {
    header('Access-Control-Allow-Origin: *');
} else {
    $allowed_origins = ['https://yourdomain.com']; // Set your production domain
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
    }
}

header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enhanced logging function
function api_log(string $level, string $message, array $context = []): void {
    $logFile = __DIR__ . '/../logs/api_debug.log';
    $timestamp = date('Y-m-d H:i:s.v');
    $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(8));
    
    // Sanitize context (remove sensitive data)
    $safeContext = [];
    foreach ($context as $key => $value) {
        if (preg_match('/pass(word)?|secret|token|key|auth|credit.?card|ssn/i', $key)) {
            $safeContext[$key] = '***REDACTED***';
        } elseif (is_array($value) || is_object($value)) {
            $safeContext[$key] = json_encode($value, JSON_UNESCAPED_SLASHES);
        } else {
            $safeContext[$key] = (string)$value;
        }
    }
    
    $logEntry = sprintf(
        "[%s] [%s] [%s] %s %s\n",
        $timestamp,
        $requestId,
        strtoupper($level),
        $message,
        $safeContext ? json_encode($safeContext, JSON_UNESCAPED_SLASHES) : ''
    );
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Also log to system log for errors
    if ($level === 'error' || $level === 'fatal') {
        error_log("API {$level}: {$message} - " . json_encode($safeContext));
    }
}

// Performance monitoring
$apiStartTime = microtime(true);
$memoryStart = memory_get_usage(true);

// Global exception handler for API
set_exception_handler(function (Throwable $e) {
    api_log('fatal', 'Uncaught exception', [
        'type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => defined('APP_DEBUG') && APP_DEBUG ? $e->getTraceAsString() : 'hidden',
        'code' => $e->getCode()
    ]);
    
    http_response_code(500);
    $response = ['success' => false, 'error' => 'Internal server error'];
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $response['debug'] = [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace()
        ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
    exit;
});

// Error handler for converting errors to exceptions
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    api_log('info', 'API request started', [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);

    // Load dependencies with validation
    $requiredFiles = [
        '../config/database.php',
        '../core/Database.php',
        '../core/APIRouter.php',
        '../core/ModuleLoader.php',
        '../core/Auth.php',
        '../modules/MenuManager.php',
        '../modules/OrderManager.php'
    ];

    foreach ($requiredFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (!file_exists($fullPath)) {
            throw new Exception("Required file missing: {$file}");
        }
        require_once $fullPath;
    }

    // Initialize components
    api_log('debug', 'Initializing components');
    
    $router = new APIRouter();
    $menuManager = new MenuManager();
    $orderManager = new OrderManager();
    $auth = ModuleLoader::getModule('Auth');

    if (!$auth) {
        throw new Exception('Auth module not available');
    }

    // Parse input data
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $requestData = array_merge($_POST, $input);
    
    // Sanitize request data
    $requestData = array_map(function($value) {
        if (is_string($value)) {
            return filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        return $value;
    }, $requestData);

    api_log('debug', 'Request data parsed', [
        'action' => $requestData['action'] ?? 'missing',
        'data_keys' => array_keys($requestData),
        'input_size' => strlen(file_get_contents('php://input'))
    ]);

    // Validate request
    if (!isset($requestData['action'])) {
        api_log('warning', 'Missing action in request', ['data' => array_keys($requestData)]);
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Missing action parameter',
            'required' => ['action'],
            'received' => array_keys($requestData)
        ], JSON_UNESCAPED_SLASHES);
        ob_end_flush();
        exit;
    }

    // Define API routes with enhanced error handling
    $router->addRoute('debug', function () use ($apiStartTime, $memoryStart) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT 1 as test, NOW() as db_time, VERSION() as db_version");
            $dbInfo = $stmt->fetch();
            $dbStatus = $dbInfo ? 'connected' : 'failed';
        } catch (Exception $e) {
            $dbStatus = 'failed: ' . $e->getMessage();
            $dbInfo = null;
        }

        $executionTime = round((microtime(true) - $apiStartTime) * 1000, 2);
        $memoryUsage = round((memory_get_usage(true) - $memoryStart) / 1024 / 1024, 2);
        $memoryPeak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        return [
            'success' => true,
            'debug_info' => [
                'php' => [
                    'version' => phpversion(),
                    'memory_usage' => "{$memoryUsage}MB",
                    'memory_peak' => "{$memoryPeak}MB",
                    'execution_time' => "{$executionTime}ms",
                    'extensions' => [
                        'pdo_mysql' => extension_loaded('pdo_mysql'),
                        'json' => extension_loaded('json'),
                        'session' => extension_loaded('session')
                    ]
                ],
                'database' => [
                    'status' => $dbStatus,
                    'version' => $dbInfo['db_version'] ?? 'unknown',
                    'time' => $dbInfo['db_time'] ?? 'unknown'
                ],
                'session' => [
                    'status' => session_status(),
                    'active' => session_status() === PHP_SESSION_ACTIVE,
                    'id' => session_id() ?: 'none'
                ],
                'files' => [
                    'APIRouter' => file_exists(__DIR__ . '/../core/APIRouter.php'),
                    'ModuleLoader' => file_exists(__DIR__ . '/../core/ModuleLoader.php'),
                    'MenuManager' => file_exists(__DIR__ . '/../modules/MenuManager.php'),
                    'OrderManager' => file_exists(__DIR__ . '/../modules/OrderManager.php')
                ]
            ]
        ];
    });

    $router->addRoute('getMenuItems', function ($data) use ($menuManager) {
        api_log('info', 'getMenuItems called', ['filters' => $data ?? []]);
        
        try {
            $items = $menuManager->getMenuItems($data);
            
            api_log('debug', 'getMenuItems completed', [
                'count' => count($items),
                'categories' => array_unique(array_column($items, 'category'))
            ]);
            
            return [
                'success' => true, 
                'data' => $items,
                'meta' => [
                    'count' => count($items),
                    'timestamp' => date('c')
                ]
            ];
        } catch (Exception $e) {
            api_log('error', 'getMenuItems failed', [
                'error' => $e->getMessage(),
                'filters' => $data
            ]);
            
            return [
                'success' => false, 
                'error' => 'Failed to fetch menu items',
                'debug' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : null
            ];
        }
    });

    $router->addRoute('login', function ($data) use ($auth) {
        api_log('info', 'Login attempt', ['username' => $data['username'] ?? 'missing']);
        
        if (!isset($data['username']) || !isset($data['password'])) {
            api_log('warning', 'Login missing credentials', [
                'provided' => array_keys($data),
                'required' => ['username', 'password']
            ]);
            
            return [
                'success' => false, 
                'error' => 'Missing username or password',
                'required' => ['username', 'password']
            ];
        }

        try {
            $username = filter_var($data['username'], FILTER_SANITIZE_STRING);
            $password = $data['password']; // Don't sanitize password
            
            $result = $auth->login($username, $password);
            
            if ($result['success']) {
                api_log('info', 'Login successful', ['username' => $username]);
            } else {
                api_log('warning', 'Login failed', ['username' => $username, 'reason' => $result['error'] ?? 'unknown']);
            }
            
            return $result;
        } catch (Exception $e) {
            api_log('error', 'Login exception', [
                'username' => $data['username'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false, 
                'error' => 'Login processing failed',
                'debug' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : null
            ];
        }
    });

    // Add more routes with similar error handling
    $router->addRoute('createOrder', function ($data) use ($orderManager, $auth) {
        api_log('info', 'Create order request');
        
        // Validate authentication
        if (!$auth->isAuthenticated()) {
            return ['success' => false, 'error' => 'Authentication required'];
        }
        
        try {
            $result = $orderManager->createOrder($data);
            api_log('info', 'Order created', ['order_id' => $result['order_id'] ?? 'unknown']);
            return $result;
        } catch (Exception $e) {
            api_log('error', 'Order creation failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Order creation failed'];
        }
    });

    // Handle the request
    api_log('debug', 'Routing request', ['action' => $requestData['action']]);
    
    $response = $router->handleRequest($requestData);
    
    // Add performance data to response in debug mode
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $response['_debug'] = [
            'execution_time_ms' => round((microtime(true) - $apiStartTime) * 1000, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(8)),
            'timestamp' => date('c')
        ];
    }

    api_log('debug', 'Request completed', [
        'action' => $requestData['action'],
        'success' => $response['success'] ?? false,
        'response_size' => strlen(json_encode($response))
    ]);

    // Send response
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    api_log('fatal', 'API bootstrap failed', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => defined('APP_DEBUG') && APP_DEBUG ? $e->getTraceAsString() : 'hidden'
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'API initialization failed',
        'debug' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : null
    ], JSON_UNESCAPED_SLASHES);
} finally {
    // Ensure proper cleanup
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    
    // Log completion
    $totalTime = round((microtime(true) - $apiStartTime) * 1000, 2);
    api_log('info', 'API request completed', [
        'execution_time_ms' => $totalTime,
        'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
    ]);
}
?>
