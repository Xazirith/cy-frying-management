<?php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once 'config/database.php';
require_once 'core/Database.php';
require_once 'core/Auth.php';
require_once 'core/ModuleLoader.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// Load modules
$modules = ModuleLoader::loadCoreModules();
$auth = $modules['Auth'];
$currentUser = $auth->getCurrentUser();

// Check if this is an API request
if (isset($_POST['action']) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
    require_once 'api/index.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <title><?php echo APP_NAME; ?> - Southern Food Truck</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <section id="home" class="hero">
        <h1>Frog Legs & Perch Perfection</h1>
        <p>Freshly fried frog legs, perch, and southern sides served hot from our gourmet food truck.</p>
        <button class="btn" onclick="scrollToMenu()">View Our Menu</button>
    </section>

    <section id="menu" class="section">
        <div class="section-title">
            <h2>Our Signature Menu</h2>
            <p>Hand-battered specialties and authentic southern sides</p>
        </div>
        <div class="menu-grid" id="menuContainer">
            <!-- Menu loaded via JavaScript -->
        </div>
    </section>

    <section id="order" class="section">
        <div class="section-title">
            <h2>Place Your Order</h2>
            <p>Build your perfect meal and pay at our truck window</p>
        </div>
        <form class="order-form">
            <label for="customer_name">Name</label>
            <input type="text" id="customer_name" name="customer_name" required>
            <label for="customer_phone">Phone</label>
            <input type="tel" id="customer_phone" name="customer_phone" required>
            <label for="customer_email">Email (optional)</label>
            <input type="email" id="customer_email" name="customer_email">
            <label for="special_instructions">Special Instructions</label>
            <textarea id="special_instructions" name="special_instructions"></textarea>
            <button type="submit" class="btn">Submit Order</button>
        </form>
    </section>

    <?php include 'templates/modals/admin.php'; ?>
    <?php include 'templates/modals/login.php'; ?>
    
    <script src="/assets/js/app.js?v=<?php echo time(); ?>"></script>
    <script>
        // Pass PHP data to JavaScript
        const AppConfig = {
            currentUser: <?php echo json_encode($currentUser); ?>,
            isAuthenticated: <?php echo $auth->isAuthenticated() ? 'true' : 'false'; ?>,
            isAdmin: <?php echo $auth->isAdmin() ? 'true' : 'false'; ?>,
            apiUrl: '/api/index.php'
        };
    </script>
</body>
</html>