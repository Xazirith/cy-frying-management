<?php
class OrderManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getOrders($filters = []) {
        $whereClauses = ['1=1'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $whereClauses[] = 'status = ?';
            $params[] = $filters['status'];
        }
        
        $where = implode(' AND ', $whereClauses);
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE $where ORDER BY created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function saveOrder($orderData) {
        $required = ['id', 'customer_name', 'customer_phone', 'total', 'order_items'];
        foreach ($required as $field) {
            if (empty($orderData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        $auth = ModuleLoader::getModule('Auth');
        $currentUser = $auth ? $auth->getCurrentUser() : null;
        
        $stmt = $this->db->prepare("
            INSERT INTO orders (id, user_id, customer_name, customer_phone, customer_email, 
                              special_instructions, total, order_items) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $orderData['id'],
            $currentUser ? $currentUser['id'] : null,
            $orderData['customer_name'],
            $orderData['customer_phone'],
            $orderData['customer_email'] ?? '',
            $orderData['special_instructions'] ?? '',
            $orderData['total'],
            json_encode($orderData['order_items'])
        ]);
    }
    
    public function updateOrderStatus($orderId, $status) {
        $validStatuses = ['pending', 'completed', 'cancelled', 'refunded'];
        if (!in_array($status, $validStatuses)) return false;
        
        $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $orderId]);
    }
}
?>