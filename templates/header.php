<?php
// templates/header.php
?>
<header>
    <h1><?php echo APP_NAME; ?> - Southern Food Truck</h1>
    <nav>
        <ul>
            <li><a href="#home">Home</a></li>
            <li><a href="#menu">Menu</a></li>
            <li><a href="#order">Order</a></li>
            <?php if ($auth->isAuthenticated()): ?>
                <li><a href="#" onclick="logout()">Logout</a></li>
            <?php else: ?>
                <li><a href="#" onclick="showLoginModal()">Login</a></li>
            <?php endif; ?>
            <?php if ($auth->isAdmin()): ?>
                <li><a href="#" onclick="showAdminModal()">Admin</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>