<?php
// templates/modals/login.php
?>
<div class="modal" id="loginModal">
    <h2>Login to <?php echo APP_NAME; ?></h2>
    <div id="loginError" class="error-message" style="display: none;"></div>
    <form id="loginForm">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" class="btn">Login</button>
        <button type="button" class="btn btn-secondary" onclick="closeLoginModal()">Close</button>
    </form>
</div>
<div class="modal-overlay" id="loginModalOverlay"></div>