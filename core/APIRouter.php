<?php
class APIRouter {
    private $routes = [];
    
    public function addRoute($action, $callback) {
        $this->routes[$action] = $callback;
    }
    
    public function handleRequest($requestData) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'error' => 'Method not allowed'];
        }
        
        $action = $requestData['action'] ?? '';
        
        if (empty($action) || !isset($this->routes[$action])) {
            return ['success' => false, 'error' => 'Invalid action: ' . $action];
        }
        
        try {
            unset($requestData['action']);
            $result = call_user_func($this->routes[$action], $requestData);
            
            return is_array($result) ? $result : ['success' => true, 'data' => $result];
            
        } catch (Exception $e) {
            error_log("API Error for action $action: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error'];
        }
    }
}
?>