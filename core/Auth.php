<?php
class Auth {
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure' => true, // Requires HTTPS
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
            ]);
        }
        $this->db = Database::getInstance()->getConnection();
    }

    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, password_hash, role, first_name, last_name, is_active
                FROM users WHERE username = ? AND is_active = TRUE LIMIT 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $updateStmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['authenticated'] = true;
                session_regenerate_id(true);
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'name' => trim($user['first_name'] . ' ' . $user['last_name'])
                    ]
                ];
            }
            return ['success' => false, 'error' => 'Invalid credentials'];
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage(), 0);
            return ['success' => false, 'error' => 'Login failed due to a server error'];
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure' => true,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
            ]);
        }
        session_unset();
        session_destroy();
        session_start([
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict'
        ]);
        session_regenerate_id(true);
        return ['success' => true];
    }

    public function isAuthenticated() {
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    public function isAdmin() {
        return $this->isAuthenticated() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function getCurrentUser() {
        if (!$this->isAuthenticated()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }
}
?>