document.addEventListener('DOMContentLoaded', () => {
    console.log('app.js loaded'); // Debug: Confirm script loads
    const menuContainer = document.getElementById('menuContainer');
    const loginForm = document.getElementById('loginForm');
    const orderForm = document.querySelector('.order-form');
    const adminForm = document.getElementById('adminForm');
    const adminMenuContainer = document.getElementById('adminMenuItems');
    let selectedItems = [];

    // Fetch and display menu items
    function loadMenu() {
        if (!menuContainer) {
            console.error('Menu container not found');
            return;
        }
        fetch(AppConfig.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'getMenuItems' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    menuContainer.innerHTML = '';
                    data.data.forEach(item => {
                        const itemDiv = document.createElement('div');
                        itemDiv.className = 'menu-item';
                        itemDiv.dataset.id = item.id;
                        itemDiv.innerHTML = `
                            <h3>${item.name}</h3>
                            <p>${item.description || 'No description'}</p>
                            <p class="price">$${parseFloat(item.price).toFixed(2)}</p>
                            <button class="btn add-to-order">Add to Order</button>
                        `;
                        menuContainer.appendChild(itemDiv);
                    });

                    document.querySelectorAll('.add-to-order').forEach(button => {
                        button.addEventListener('click', () => {
                            const itemDiv = button.parentElement;
                            const itemId = itemDiv.dataset.id;
                            const itemName = itemDiv.querySelector('h3').textContent;
                            const itemPrice = parseFloat(itemDiv.querySelector('.price').textContent.replace('$', ''));
                            selectedItems.push({ id: itemId, name: itemName, price: itemPrice });
                            updateOrderSummary();
                        });
                    });
                } else {
                    menuContainer.innerHTML = '<p>Error loading menu: ' + data.error + '</p>';
                }
            })
            .catch(error => {
                menuContainer.innerHTML = '<p>Error loading menu: ' + error.message + '</p>';
            });
    }

    // Update order summary
    function updateOrderSummary() {
        if (!orderForm) return;
        const summary = document.createElement('div');
        summary.className = 'order-summary';
        summary.innerHTML = '<h3>Selected Items</h3>';
        if (selectedItems.length === 0) {
            summary.innerHTML += '<p>No items selected</p>';
        } else {
            const ul = document.createElement('ul');
            let total = 0;
            selectedItems.forEach((item, index) => {
                total += item.price;
                ul.innerHTML += `<li>${item.name} - $${item.price.toFixed(2)} <button class="btn remove-item" data-index="${index}">Remove</button></li>`;
            });
            summary.innerHTML += `<ul>${ul.innerHTML}</ul><p>Total: $${total.toFixed(2)}</p>`;
        }
        const existingSummary = document.querySelector('.order-summary');
        if (existingSummary) existingSummary.remove();
        orderForm.prepend(summary);

        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', () => {
                const index = parseInt(button.dataset.index);
                selectedItems.splice(index, 1);
                updateOrderSummary();
            });
        });
    }

    // Load menu items in admin panel
    function loadAdminMenuItems() {
        if (!adminMenuContainer) return;
        adminMenuContainer.innerHTML = '<h3>Current Menu Items</h3>';
        fetch(AppConfig.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'getMenuItems' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ul = document.createElement('ul');
                    data.data.forEach(item => {
                        ul.innerHTML += `
                            <li>
                                ${item.name} - $${parseFloat(item.price).toFixed(2)} (${item.category})
                                <button class="btn edit-item" data-id="${item.id}" data-name="${item.name}" data-price="${item.price}" data-category="${item.category}" data-description="${item.description || ''}" data-tags="${item.tags || ''}" data-is_available="${item.is_available ? 'true' : 'false'}">Edit</button>
                                <button class="btn delete-item" data-id="${item.id}">Delete</button>
                            </li>`;
                    });
                    adminMenuContainer.appendChild(ul);

                    document.querySelectorAll('.edit-item').forEach(button => {
                        button.addEventListener('click', () => {
                            document.getElementById('menuItemId').value = button.dataset.id;
                            document.getElementById('menuItemName').value = button.dataset.name;
                            document.getElementById('menuItemPrice').value = button.dataset.price;
                            document.getElementById('menuItemCategory').value = button.dataset.category;
                            document.getElementById('menuItemDescription').value = button.dataset.description;
                            document.getElementById('menuItemTags').value = button.dataset.tags;
                            document.getElementById('menuItemIsAvailable').checked = button.dataset.is_available === 'true';
                        });
                    });

                    document.querySelectorAll('.delete-item').forEach(button => {
                        button.addEventListener('click', () => {
                            if (confirm('Are you sure you want to delete this item?')) {
                                fetch(AppConfig.apiUrl, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ action: 'deleteMenuItem', id: button.dataset.id })
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            alert('Item deleted successfully!');
                                            loadAdminMenuItems();
                                            loadMenu();
                                        } else {
                                            alert('Error deleting item: ' + data.error);
                                        }
                                    });
                            }
                        });
                    });
                } else {
                    adminMenuContainer.innerHTML += '<p>Error loading menu items: ' + data.error + '</p>';
                }
            })
            .catch(error => {
                adminMenuContainer.innerHTML += '<p>Error loading menu items: ' + error.message + '</p>';
            });
    }

    // Scroll to menu
    window.scrollToMenu = () => {
        const menuSection = document.getElementById('menu');
        if (menuSection) {
            menuSection.scrollIntoView({ behavior: 'smooth' });
        }
    };

    // Modal functions
    window.showLoginModal = () => {
        console.log('showLoginModal called'); // Debug: Confirm function runs
        const loginModal = document.getElementById('loginModal');
        const loginModalOverlay = document.getElementById('loginModalOverlay');
        const loginError = document.getElementById('loginError');
        if (loginModal && loginModalOverlay) {
            loginModal.style.display = 'block';
            loginModalOverlay.style.display = 'block';
            if (loginError) loginError.style.display = 'none';
        } else {
            console.error('Login modal elements not found');
        }
    };

    window.closeLoginModal = () => {
        const loginModal = document.getElementById('loginModal');
        const loginModalOverlay = document.getElementById('loginModalOverlay');
        if (loginModal && loginModalOverlay) {
            loginModal.style.display = 'none';
            loginModalOverlay.style.display = 'none';
            if (loginForm) loginForm.reset();
        }
    };

    window.showAdminModal = () => {
        if (!AppConfig.isAdmin) {
            alert('You must be an admin to access this panel.');
            return;
        }
        const adminModal = document.getElementById('adminModal');
        const adminModalOverlay = document.getElementById('adminModalOverlay');
        if (adminModal && adminModalOverlay) {
            adminModal.style.display = 'block';
            adminModalOverlay.style.display = 'block';
            loadAdminMenuItems();
        } else {
            console.error('Admin modal elements not found');
        }
    };

    window.closeAdminModal = () => {
        const adminModal = document.getElementById('adminModal');
        const adminModalOverlay = document.getElementById('adminModalOverlay');
        if (adminModal && adminModalOverlay) {
            adminModal.style.display = 'none';
            adminModalOverlay.style.display = 'none';
            if (adminForm) adminForm.reset();
        }
    };

    window.logout = () => {
        fetch(AppConfig.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        }).then(() => location.reload());
    };

    // Handle login form submission
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(loginForm);
            const username = formData.get('username');
            const password = formData.get('password');
            const loginError = document.getElementById('loginError');

            fetch(AppConfig.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'login', username, password })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Login successful!');
                        closeLoginModal();
                        location.reload();
                    } else {
                        if (loginError) {
                            loginError.textContent = 'Login failed: ' + data.error;
                            loginError.style.display = 'block';
                        } else {
                            alert('Login failed: ' + data.error);
                        }
                    }
                })
                .catch(() => {
                    if (loginError) {
                        loginError.textContent = 'Error during login';
                        loginError.style.display = 'block';
                    } else {
                        alert('Error during login');
                    }
                });
        });
    }

    // Handle order form submission
    if (orderForm) {
        orderForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(orderForm);
            const total = selectedItems.reduce((sum, item) => sum + item.price, 0);
            const orderData = {
                action: 'saveOrder',
                customer_name: formData.get('customer_name'),
                customer_phone: formData.get('customer_phone'),
                customer_email: formData.get('customer_email') || '',
                special_instructions: formData.get('special_instructions') || '',
                total: total,
                order_items: selectedItems
            };

            fetch(AppConfig.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order submitted successfully!');
                        selectedItems = [];
                        updateOrderSummary();
                        orderForm.reset();
                    } else {
                        alert('Error submitting order: ' + data.error);
                    }
                })
                .catch(() => alert('Error submitting order'));
        });
    }

    // Handle admin form submission
    if (adminForm) {
        adminForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const itemId = document.getElementById('menuItemId').value;
            const formData = new FormData(adminForm);
            const itemData = {
                name: formData.get('name'),
                price: parseFloat(formData.get('price')),
                category: formData.get('category'),
                description: formData.get('description'),
                tags: formData.get('tags'),
                is_available: formData.get('is_available') === 'on'
            };

            const action = itemId ? 'updateMenuItem' : 'addMenuItem';
            if (itemId) itemData.id = itemId;

            fetch(AppConfig.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ...itemData })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(itemId ? 'Item updated successfully!' : 'Item added successfully!');
                        adminForm.reset();
                        document.getElementById('menuItemId').value = '';
                        loadAdminMenuItems();
                        loadMenu();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(() => alert('Error saving item'));
        });
    }

    // Initial load
    loadMenu();
});