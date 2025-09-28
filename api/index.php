<?php
namespace App\API;

use App\Core\ModuleLoader;
use App\Core\APIRouter;
use App\Core\Database;
use App\Modules\MenuManager;
use App\Modules\OrderManager;
use Exception;

ob_start();
ini_set('display_errors', 1); // Temporary for debugging
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    error_log("Starting API request processing");
    require_once '../config/database.php';
    require_once '../core/Database.php';
    require_once '../core/APIRouter.php';
    require_once '../core/ModuleLoader.php';
    require_once '../core/Auth.php';
    require_once '../modules/MenuManager.php';
    require_once '../modules/OrderManager.php';

    $router = new APIRouter();
    $menuManager = new MenuManager();
    $orderManager = new OrderManager();
    $auth = ModuleLoader::getModule('Auth');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $requestData = array_merge($_POST, $input);
    error_log("Request data: " . json_encode($requestData));
    if (!isset($requestData['action'])) {
        error_log("Missing action in request: " . json_encode($requestData));
        echo json_encode(['success' => false, 'error' => 'Missing action']);
        ob_end_flush();
        exit;
    }

    $router->addRoute('debug', function () {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT 1");
            $dbStatus = $stmt->fetch() ? 'connected' : 'failed';
        } catch (Exception $e) {
            $dbStatus = 'failed: ' . $e->getMessage();
        }
        return [
            'success' => true,
            'php_version' => phpversion(),
            'pdo_available' => extension_loaded('pdo_mysql'),
            'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
            'database_status' => $dbStatus,
            'files_exist' => [
                'APIRouter' => file_exists(__DIR__ . '/../core/APIRouter.php'),
                'ModuleLoader' => file_exists(__DIR__ . '/../core/ModuleLoader.php'),
                'MenuManager' => file_exists(__DIR__ . '/../modules/MenuManager.php'),
                'OrderManager' => file_exists(__DIR__ . '/../modules/OrderManager.php')
            ]
        ];
    });

    $router->addRoute('getMenuItems', function ($data) use ($menuManager) {
        try {
            return ['success' => true, 'data' => $menuManager->getMenuItems($data)];
        } catch (Exception $e) {
            error_log("getMenuItems error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to fetch menu items'];
        }
    });

    $router->addRoute('login', function ($data) use ($auth) {
        if (!isset($data['username']) || !isset($data['password'])) {
            error_log("Missing username or password in login: " . json_encode($data));
            return ['success' => false, 'error' => 'Missing username or password'];
        }
        try {
            return $auth->login(
                filter_var($data['username'], FILTER_SANITIZE_STRING),
                $data['password']
            );
        } catch (Exception $e) {
            error_log("Login route error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Login failed'];
        }
    });

    error_log("Handling request: " . json_encode($requestData));
    $response = $router->handleRequest($requestData);
    echo json_encode($response);
    ob_end_flush();
} catch (Exception $e) {
    error_log("API error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
    ob_end_flush();
}
?>