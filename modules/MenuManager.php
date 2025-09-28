<?php
class MenuManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getMenuItems($filters = []) {
        $whereClauses = ['is_available = TRUE'];
        $params = [];
        
        if (!empty($filters['category'])) {
            $whereClauses[] = 'category = ?';
            $params[] = $filters['category'];
        }
        
        $where = implode(' AND ', $whereClauses);
        $stmt = $this->db->prepare("
            SELECT * FROM menu_items 
            WHERE $where 
            ORDER BY category, name
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function saveMenuItem($itemData) {
        $required = ['id', 'name', 'price', 'category'];
        foreach ($required as $field) {
            if (empty($itemData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO menu_items (id, name, price, category, tags, description) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $itemData['id'],
            $itemData['name'],
            $itemData['price'],
            $itemData['category'],
            $itemData['tags'] ?? '',
            $itemData['description'] ?? ''
        ]);
    }
    
    public function updateMenuItem($itemId, $updateData) {
        $allowedFields = ['name', 'price', 'category', 'tags', 'description', 'is_available'];
        $setParts = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($updateData[$field])) {
                $setParts[] = "$field = ?";
                $params[] = $updateData[$field];
            }
        }
        
        if (empty($setParts)) return false;
        
        $params[] = $itemId;
        $stmt = $this->db->prepare("UPDATE menu_items SET " . implode(', ', $setParts) . " WHERE id = ?");
        return $stmt->execute($params);
    }
    
    public function deleteMenuItem($itemId) {
        $stmt = $this->db->prepare("DELETE FROM menu_items WHERE id = ?");
        return $stmt->execute([$itemId]);
    }
}
?>