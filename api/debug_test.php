<?php
// /api/debug_test.php - Direct API debug
header('Content-Type: text/plain');
echo "=== API DEBUG TEST ===\n\n";

// Force error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');

echo "1. PHP Version: " . PHP_VERSION . "\n";
echo "2. Memory: " . round(memory_get_usage(true)/1024/1024, 2) . "MB\n";

// Test critical files
echo "\n3. FILE CHECK:\n";
$files = [
    '../config/database.php' => 'Config',
    'index.php' => 'API Main',
    '../core/Database.php' => 'Database Class',
    '../core/APIRouter.php' => 'API Router',
    '../core/ModuleLoader.php' => 'Module Loader'
];

foreach ($files as $path => $desc) {
    $fullPath = __DIR__ . '/' . $path;
    $exists = file_exists($fullPath);
    echo "   $desc: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    
    if ($exists) {
        $content = file_get_contents($fullPath);
        echo "        Size: " . strlen($content) . " bytes\n";
        
        // Check for syntax errors
        if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
            $output = [];
            $return = 0;
            exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $return);
            if ($return !== 0) {
                echo "        ⚠️ SYNTAX ERROR: " . implode("\n", $output) . "\n";
            }
        }
    }
}

// Test database connection
echo "\n4. DATABASE TEST:\n";
if (extension_loaded('pdo_mysql')) {
    echo "   PDO MySQL: LOADED\n";
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=cfm_dev", 'cfm_user', 'Jazlynn0902');
        echo "   ✅ Database connected!\n";
        
        // Test basic query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "   ✅ Query test: " . ($result['test'] == 1 ? "PASS" : "FAIL") . "\n";
        
    } catch (PDOException $e) {
        echo "   ❌ Database error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   PDO MySQL: NOT LOADED\n";
}

// Test JSON input
echo "\n5. INPUT TEST:\n";
$input = file_get_contents('php://input');
echo "   Raw input: " . ($input ? $input : "EMPTY") . "\n";
$json = json_decode($input, true);
echo "   JSON decoded: " . (json_last_error() === JSON_ERROR_NONE ? "SUCCESS" : "FAILED - " . json_last_error_msg()) . "\n";

// Test if we can require the main API file
echo "\n6. API BOOTSTRAP TEST:\n";
try {
    require_once 'index.php';
    echo "   ✅ API index.php loaded without fatal error\n";
} catch (Throwable $e) {
    echo "   ❌ API index.php failed:\n";
    echo "      Error: " . $e->getMessage() . "\n";
    echo "      File: " . $e->getFile() . "\n";
    echo "      Line: " . $e->getLine() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
