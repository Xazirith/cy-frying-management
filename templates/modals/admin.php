<?php
// templates/modals/admin.php
?>
<div class="modal" id="adminModal">
    <h2>Admin Panel</h2>
    <form id="adminForm">
        <input type="hidden" id="menuItemId" name="id">
        <label for="menuItemName">Item Name</label>
        <input type="text" id="menuItemName" name="name" required>
        <label for="menuItemPrice">Price</label>
        <input type="number" id="menuItemPrice" name="price" step="0.01" required>
        <label for="menuItemCategory">Category</label>
        <select id="menuItemCategory" name="category" required>
            <option value="mains">Mains</option>
            <option value="sides">Sides</option>
            <option value="beverages">Beverages</option>
        </select>
        <label for="menuItemDescription">Description</label>
        <textarea id="menuItemDescription" name="description"></textarea>
        <label for="menuItemTags">Tags (comma-separated)</label>
        <input type="text" id="menuItemTags" name="tags">
        <label><input type="checkbox" id="menuItemIsAvailable" name="is_available" checked> Available</label>
        <button type="submit" class="btn">Save Item</button>
        <button type="button" class="btn" onclick="closeAdminModal()">Close</button>
    </form>
    <div id="adminMenuItems"></div>
</div>
<div class="modal-overlay" id="adminModalOverlay"></div>