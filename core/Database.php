<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false
                ]
            );
            $this->initializeTables();
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage(), 0);
            throw new Exception("Database connection error. Please try again later.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function initializeTables() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(50) PRIMARY KEY,
                username VARCHAR(100) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                phone VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE,
                INDEX idx_username (username),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS menu_items (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                category ENUM('mains', 'sides', 'beverages') NOT NULL,
                tags VARCHAR(100),
                is_available BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_category (category)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS orders (
                id VARCHAR(50) PRIMARY KEY,
                user_id VARCHAR(50),
                customer_name VARCHAR(255) NOT NULL,
                customer_phone VARCHAR(20) NOT NULL,
                customer_email VARCHAR(255),
                special_instructions TEXT,
                total DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
                order_items JSON NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        foreach ($tables as $tableSQL) {
            try {
                $this->pdo->exec($tableSQL);
            } catch (PDOException $e) {
                error_log("Table creation error: " . $e->getMessage(), 0);
            }
        }

        $this->createDefaultAdmin();
    }

    private function createDefaultAdmin() {
        try {
            $checkAdmin = $this->pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $checkAdmin->execute(['admin']);
            if ($checkAdmin->rowCount() === 0) {
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT, ['cost' => 12]);
                $adminId = 'user_' . bin2hex(random_bytes(8));
                $createAdmin = $this->pdo->prepare(
                    "INSERT INTO users (id, username, password_hash, role, first_name, last_name, is_active)
                    VALUES (?, ?, ?, 'admin', 'System', 'Admin', TRUE)"
                );
                $createAdmin->execute([$adminId, 'admin', $hashedPassword]);
            }
        } catch (PDOException $e) {
            error_log("Default admin creation error: " . $e->getMessage(), 0);
        }
    }
}
?>
