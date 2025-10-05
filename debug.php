<?php
// find_error.php - Isolate the error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 Finding the 500 Error</h1>";
echo "<pre>";

// Test 1: Basic PHP functionality
echo "=== TEST 1: BASIC PHP ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory: " . memory_get_usage() . "\n";
echo "✅ Basic PHP working\n\n";

// Test 2: File includes
echo "=== TEST 2: FILE INCLUDES ===\n";
$files_to_test = [
    'config/database.php',
    'core/Database.php',
    'index.php'
];

foreach ($files_to_test as $file) {
    echo "Testing: $file ... ";
    if (file_exists($file)) {
        // Check for syntax errors
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ Syntax OK\n";
        } else {
            echo "❌ SYNTAX ERROR: $output\n";
        }
    } else {
        echo "❌ FILE MISSING\n";
    }
}

echo "\n=== TEST 3: CONFIG FILE ===\n";
if (file_exists('config/database.php')) {
    echo "Config file exists. Contents:\n";
    $content = file_get_contents('config/database.php');
    echo substr($content, 0, 500) . "...\n";
    
    // Try to include it
    echo "Including config... ";
    try {
        include 'config/database.php';
        echo "✅ Included successfully\n";
        
        // Check constants
        if (defined('DB_HOST')) {
            echo "DB_HOST = " . DB_HOST . "\n";
        } else {
            echo "❌ DB_HOST not defined\n";
        }
    } catch (Exception $e) {
        echo "❌ Include failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Config file not found\n";
}

echo "\n=== TEST 4: DATABASE EXTENSIONS ===\n";
echo "pdo_mysql: " . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "\n";
echo "mysqli: " . (extension_loaded('mysqli') ? '✅' : '❌') . "\n";

echo "\n=== DEBUG COMPLETE ===\n";
echo "</pre>";
?>