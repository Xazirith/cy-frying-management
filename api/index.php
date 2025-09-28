<?php
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

// Get all menu items
$router->addRoute('getMenuItems', function ($data) use ($menuManager) {
    return ['success' => true, 'data' => $menuManager->getMenuItems($data)];
});

// Add a new menu item (admin only)
$router->addRoute('addMenuItem', function ($data) use ($menuManager, $auth) {
    if (!$auth->isAdmin()) {
        return ['success' => false, 'error' => 'Unauthorized'];
    }
    try {
        $itemData = [
            'id' => 'item_' . uniqid(),
            'name' => $data['name'],
            'price' => $data['price'],
            'category' => $data['category'],
            'description' => $data['description'] ?? '',
            'tags' => $data['tags'] ?? ''
        ];
        $menuManager->saveMenuItem($itemData);
        return ['success' => true, 'data' => $itemData];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
});

// Update a menu item (admin only)
$router->addRoute('updateMenuItem', function ($data) use ($menuManager, $auth) {
    if (!$auth->isAdmin()) {
        return ['success' => false, 'error' => 'Unauthorized'];
    }
    try {
        $itemId = $data['id'];
        $updateData = [
            'name' => $data['name'] ?? null,
            'price' => $data['price'] ?? null,
            'category' => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
            'tags' => $data['tags'] ?? null,
            'is_available' => isset($data['is_available']) ? (bool)$data['is_available'] : null
        ];
        $menuManager->updateMenuItem($itemId, array_filter($updateData, fn($value) => !is_null($value)));
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
});

// Delete a menu item (admin only)
$router->addRoute('deleteMenuItem', function ($data) use ($menuManager, $auth) {
    if (!$auth->isAdmin()) {
        return ['success' => false, 'error' => 'Unauthorized'];
    }
    try {
        $menuManager->deleteMenuItem($data['id']);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
});

// Save an order
$router->addRoute('saveOrder', function ($data) use ($orderManager, $auth) {
    try {
        $orderData = [
            'id' => 'order_' . uniqid(),
            'user_id' => $auth->isAuthenticated() ? $auth->getCurrentUser()['id'] : null,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? '',
            'special_instructions' => $data['special_instructions'] ?? '',
            'total' => $data['total'] ?? 0,
            'order_items' => $data['order_items'] ?? []
        ];
        $orderManager->saveOrder($orderData);
        return ['success' => true, 'data' => $orderData];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
});

// Login
$router->addRoute('login', function ($data) use ($auth) {
    return $auth->login($data['username'], $data['password']);
});

// Logout
$router->addRoute('logout', function () use ($auth) {
    return $auth->logout();
});

// Handle JSON input
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$requestData = array_merge($_POST, $input);
$response = $router->handleRequest($requestData);
echo json_encode($response);
?>