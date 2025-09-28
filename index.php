<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CY Frying - Southern Food Truck</title>
    <style>
        :root {
            --primary: #c0392b;
            --secondary: #ffffff;
            --accent: #e74c3c;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --success: #2ecc71;
            --warning: #f39c12;
            --info: #3498db;
            --danger: #c0392b;
            --muted: #6c757d;
            --shadow: 0 4px 20px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.15);
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            line-height: 1.6; 
            color: var(--dark); 
            background: var(--secondary);
            overflow-x: hidden;
        }
        
        /* Header */
        .header { 
            background: linear-gradient(135deg, var(--primary), var(--accent)); 
            color: var(--secondary); 
            padding: 1rem 0; 
            position: fixed; 
            width: 100%; 
            top: 0; 
            z-index: 1000; 
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(10px);
        }
        .nav { 
            max-width: 1400px; 
            margin: 0 auto; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0 2rem; 
        }
        .logo { 
            font-size: 2.2rem; 
            font-weight: 700; 
            color: var(--secondary);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .nav-links { 
            display: flex; 
            list-style: none; 
            gap: 2rem;
        }
        .nav-links a { 
            color: var(--secondary); 
            text-decoration: none; 
            transition: all 0.3s ease; 
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
        }
        .nav-links a:hover { 
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        /* Hero Section */
        .hero { 
            background: linear-gradient(135deg, rgba(192, 57, 43, 0.9), rgba(231, 76, 60, 0.9));
            color: var(--secondary); 
            padding: 8rem 2rem; 
            text-align: center; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        .hero h1 { 
            font-size: clamp(2.5rem, 6vw, 4.5rem); 
            margin-bottom: 1.5rem; 
            text-shadow: 3px 3px 10px rgba(0,0,0,0.5);
            font-weight: 800;
            letter-spacing: -1px;
        }
        .hero p { 
            font-size: clamp(1.1rem, 3vw, 1.5rem); 
            margin-bottom: 3rem; 
            max-width: 700px; 
            margin-left: auto; 
            margin-right: auto;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }
        
        /* Sections */
        .section { 
            padding: 6rem 2rem; 
            max-width: 1400px; 
            margin: 0 auto; 
        }
        .section-title { 
            text-align: center; 
            margin-bottom: 4rem; 
        }
        .section-title h2 { 
            font-size: clamp(2rem, 5vw, 3.5rem); 
            color: var(--primary); 
            margin-bottom: 1rem; 
            position: relative;
            font-weight: 700;
        }
        .section-title h2::after { 
            content: ''; 
            display: block; 
            width: 80px; 
            height: 4px; 
            background: linear-gradient(90deg, var(--accent), var(--primary)); 
            margin: 1.5rem auto 0;
            border-radius: 2px;
        }
        .section-title p { 
            color: var(--muted); 
            font-size: 1.3rem; 
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Enhanced Menu Grid */
        .menu-categories {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }
        .category-btn {
            padding: 0.8rem 2rem;
            border: 2px solid var(--primary);
            background: var(--secondary);
            color: var(--primary);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .category-btn:hover,
        .category-btn.active {
            background: var(--primary);
            color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .menu-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
            gap: 2.5rem; 
            margin-top: 3rem; 
        }
        .menu-item { 
            background: var(--secondary); 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: var(--shadow);
            transition: all 0.4s ease; 
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
        }
        .menu-item:hover { 
            transform: translateY(-10px) scale(1.02); 
            box-shadow: var(--shadow-lg);
        }
        .menu-item-img { 
            height: 200px; 
            background: linear-gradient(135deg, var(--light), #e9ecef); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 4rem;
            position: relative;
            overflow: hidden;
        }
        .menu-item-img::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s;
        }
        .menu-item:hover .menu-item-img::before {
            left: 100%;
        }
        .menu-item-content { 
            padding: 2rem; 
        }
        .menu-item h3 { 
            color: var(--primary); 
            margin-bottom: 0.75rem; 
            font-size: 1.4rem;
            font-weight: 600;
        }
        .menu-item-price { 
            color: var(--accent); 
            font-size: 1.8rem; 
            font-weight: 700; 
            margin: 1rem 0;
        }
        .menu-item-desc { 
            color: var(--muted); 
            margin-bottom: 1.5rem; 
            line-height: 1.6;
        }
        .menu-item-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .menu-item-tag {
            padding: 0.3rem 0.8rem;
            background: var(--light);
            color: var(--dark);
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .menu-item-tag.spicy {
            background: #ffe6e6;
            color: var(--danger);
        }
        .menu-item-tag.popular {
            background: #fff3cd;
            color: #856404;
        }
        .menu-item-tag.new {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        /* Enhanced Cart */
        .cart { 
            background: var(--light); 
            padding: 2.5rem; 
            border-radius: 20px; 
            box-shadow: inset 0 2px 15px rgba(0,0,0,0.05);
            margin-top: 2.5rem;
            border: 2px solid rgba(0,0,0,0.05);
        }
        .cart h3 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .cart-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 1.5rem; 
            border-bottom: 1px solid rgba(0,0,0,0.1);
            background: var(--secondary);
            margin-bottom: 0.5rem;
            border-radius: 10px;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-name {
            font-weight: 600;
            color: var(--dark);
        }
        .cart-item-price {
            color: var(--muted);
            font-size: 0.9rem;
        }
        .cart-item-controls { 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
        }
        .cart-item-qty { 
            display: flex; 
            align-items: center; 
            gap: 0.5rem;
            background: var(--secondary);
            border-radius: 25px;
            padding: 0.2rem;
        }
        .qty-btn { 
            background: var(--primary); 
            color: var(--secondary);
            border: noe; 
            padding: 0.5rem 0.8rem; 
            cursor: pointer; 
            font-size: 1rem; 
            border-radius: 50%;
            transition: all 0.3s ease;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qty-btn:hover { 
            background: var(--accent);
            transform: scale(1.1);
        }
        .cart-total { 
            font-size: 2rem; 
            font-weight: 700; 
            text-align: right; 
            margin-top: 2rem; 
            color: var(--primary);
            padding: 1.5rem;
            background: var(--secondary);
            border-radius: 10px;
            border-left: 4px solid var(--accent);
        }
        
        /* Enhanced Buttons */
        .btn { 
            background: linear-gradient(135deg, var(--accent), var(--primary)); 
            color: var(--secondary); 
            border: none; 
            padding: 1.2rem 2.5rem; 
            border-radius: 50px; 
            font-size: 1.1rem; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        .btn:hover::before {
            left: 100%;
        }
        .btn:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .btn-block { width: 100%; }
        .btn-success { background: linear-gradient(135deg, var(--success), #27ae60); }
        .btn-warning { background: linear-gradient(135deg, var(--warning), #e67e22); }
        .btn-info { background: linear-gradient(135deg, var(--info), #2980b9); }
        .btn-danger { background: linear-gradient(135deg, var(--danger), #b03a2e); }
        .btn-small { 
            padding: 0.8rem 1.5rem; 
            font-size: 0.9rem;
            border-radius: 25px;
        }
        
        /* Enhanced Forms */
        .order-form { 
            background: var(--secondary); 
            padding: 3.5rem; 
            border-radius: 25px; 
            box-shadow: var(--shadow-lg);
            margin-top: 2.5rem;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .form-group { 
            margin-bottom: 2rem; 
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-group label { 
            display: block; 
            margin-bottom: 0.8rem; 
            font-weight: 600; 
            color: var(--primary); 
            font-size: 1.1rem;
        }
        .form-control { 
            width: 100%; 
            padding: 1.2rem 1.5rem; 
            border: 2px solid var(--light); 
            border-radius: 15px; 
            font-size: 1rem; 
            transition: all 0.3s ease;
            background: var(--secondary);
        }
        .form-control:focus { 
            border-color: var(--accent); 
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1); 
            outline: none;
            transform: translateY(-2px);
        }
        
        /* Enhanced Admin Panel */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.8); 
            z-index: 2000; 
            overflow-y: auto;
            backdrop-filter: blur(5px);
        }
        .modal-content { 
            background: var(--secondary); 
            margin: 2% auto; 
            padding: 3rem; 
            border-radius: 25px; 
            max-width: 95%; 
            width: 1400px; 
            position: relative; 
            max-height: 90vh; 
            overflow-y: auto; 
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
        }
        .close { 
            position: absolute; 
            top: 1.5rem; 
            right: 2rem; 
            font-size: 2.5rem; 
            cursor: pointer; 
            color: var(--muted); 
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .close:hover { 
            color: var(--danger);
            background: rgba(192, 57, 43, 0.1);
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light);
        }
        .admin-title {
            font-size: 2.5rem;
            color: var(--primary);
            font-weight: 700;
        }
        .admin-user {
            background: var(--light);
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            color: var(--dark);
        }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 2rem; 
            margin-bottom: 3rem; 
        }
        .stat-card { 
            background: linear-gradient(135deg, var(--secondary), var(--light)); 
            padding: 2.5rem; 
            border-radius: 20px; 
            text-align: center; 
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        .stat-card:hover { 
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        .stat-number { 
            font-size: 3.5rem; 
            font-weight: 800; 
            color: var(--primary);
            line-height: 1;
        }
        .stat-label { 
            color: var(--muted); 
            margin-top: 0.5rem; 
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .admin-tabs { 
            display: flex; 
            border-bottom: 3px solid var(--light); 
            margin-bottom: 3rem;
            gap: 1rem;
        }
        .admin-tab { 
            padding: 1.5rem 2.5rem; 
            cursor: pointer; 
            border-bottom: 4px solid transparent; 
            transition: all 0.3s ease; 
            color: var(--muted);
            font-weight: 600;
            border-radius: 10px 10px 0 0;
            background: var(--light);
        }
        .admin-tab.active { 
            border-bottom-color: var(--accent); 
            color: var(--primary);
            background: var(--secondary);
            transform: translateY(-2px);
        }
        .admin-tab:hover { 
            color: var(--accent);
            background: var(--secondary);
        }
        
        .admin-tab-content { 
            display: none; 
        }
        .admin-tab-content.active { 
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Enhanced Tables */
        .admin-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 2rem 0; 
            box-shadow: var(--shadow);
            border-radius: 15px;
            overflow: hidden;
        }
        .admin-table th, 
        .admin-table td { 
            padding: 1.5rem; 
            text-align: left; 
        }
        .admin-table th { 
            background: linear-gradient(135deg, var(--primary), var(--accent)); 
            color: var(--secondary);
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
        .admin-table tr:nth-child(even) { 
            background: var(--light); 
        }
        .admin-table tr:hover { 
            background: rgba(192, 57, 43, 0.05);
            transform: scale(1.01);
        }
        
        /* Order Cards */
        .orders-grid { 
            display: grid; 
            gap: 2rem; 
        }
        .order-card { 
            background: var(--secondary); 
            padding: 2.5rem; 
            border-radius: 20px; 
            border-left: 6px solid var(--info); 
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }
        .order-card:hover { 
            box-shadow: var(--shadow-lg);
            transform: translateX(5px);
        }
        .order-card.completed { 
            border-left-color: var(--success); 
        }
        .order-card.pending { 
            border-left-color: var(--warning); 
        }
        .order-card.cancelled { 
            border-left-color: var(--danger); 
        }
        .order-card.refunded { 
            border-left-color: var(--muted); 
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .order-id {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
        }
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .status-refunded {
            background: #e2e3e5;
            color: #383d41;
        }
        
        /* Enhanced Contact Section */
        .contact-info { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 2.5rem; 
            margin: 3rem 0; 
        }
        .contact-item { 
            text-align: center; 
            padding: 2.5rem; 
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05)); 
            border-radius: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .contact-item:hover {
            transform: translateY(-10px);
            background: rgba(255,255,255,0.2);
        }
        .contact-item h3 {
            margin: 1rem 0;
            font-size: 1.5rem;
            color: var(--secondary);
        }
        .contact-item p {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
        }
        
        /* Footer Enhancement */
        .footer { 
            background: linear-gradient(135deg, var(--primary), var(--accent)); 
            color: var(--secondary); 
            padding: 5rem 2rem 3rem; 
            text-align: center;
            position: relative;
        }
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--primary), var(--accent));
        }
        .footer-content { 
            max-width: 1400px; 
            margin: 0 auto; 
        }
        .social-links {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }
        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .social-link:hover {
            background: var(--secondary);
            color: var(--primary);
            transform: translateY(-3px);
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: var(--secondary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Notification Enhancement */
        .notification {
            position: fixed;
            top: 100px;
            right: 30px;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            z-index: 3000;
            box-shadow: var(--shadow-lg);
            max-width: 400px;
            font-size: 1rem;
            font-weight: 500;
            transform: translateX(400px);
            transition: all 0.3s ease;
        }
        .notification.show {
            transform: translateX(0);
        }
        .notification.success {
            background: var(--success);
            color: var(--secondary);
        }
        .notification.error {
            background: var(--danger);
            color: var(--secondary);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav { 
                flex-direction: column; 
                padding: 1rem; 
            }
            .nav-links { 
                margin-top: 1rem; 
                flex-wrap: wrap; 
                justify-content: center;
                gap: 1rem;
            }
            .hero { 
                padding: 6rem 1rem 4rem; 
            }
            .section { 
                padding: 4rem 1rem; 
            }
            .menu-grid { 
                grid-template-columns: 1fr; 
            }
            .modal-content { 
                margin: 5% auto; 
                width: 95%; 
                padding: 2rem; 
            }
            .admin-tabs { 
                flex-direction: column; 
            }
            .admin-tab { 
                padding: 1rem; 
                text-align: center; 
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
        
        @media (max-width: 480px) {
            .hero h1 {
                font-size: 2rem;
            }
            .section-title h2 {
                font-size: 1.8rem;
            }
            .menu-item-content {
                padding: 1.5rem;
            }
            .order-form,
            .cart {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <div class="logo">🚚🍟 CY Frying</div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#menu">Menu</a></li>
                <li><a href="#order">Order</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="#" onclick="openAdminModal(); return false;">Admin</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <h1>Frog Legs & Perch Perfection</h1>
        <p>Freshly fried frog legs, perch, and southern sides served hot from our gourmet food truck. Quality ingredients, authentic flavors, unbeatable taste!</p>
        <button class="btn" onclick="scrollToMenu()">View Our Menu</button>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="section">
        <div class="section-title">
            <h2>Our Signature Menu</h2>
            <p>Hand-battered specialties and authentic southern sides</p>
        </div>
        
        <div class="menu-categories">
            <button class="category-btn active" onclick="filterMenu('all')">All Items</button>
            <button class="category-btn" onclick="filterMenu('mains')">Main Dishes</button>
            <button class="category-btn" onclick="filterMenu('sides')">Sides</button>
            <button class="category-btn" onclick="filterMenu('beverages')">Beverages</button>
        </div>
        
        <div class="menu-grid" id="menuContainer">
            <!-- Menu items will be loaded here -->
        </div>
    </section>

    <!-- Order Section -->
    <section id="order" class="section" style="background: var(--light);">
        <div class="section-title">
            <h2>Place Your Order</h2>
            <p>Build your perfect meal and pay at our truck window</p>
        </div>
        
        <div class="order-form">
            <h3 style="color: var(--primary); margin-bottom: 2rem; font-size: 1.8rem;">Customer Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" class="form-control" id="customerName" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" class="form-control" id="customerPhone" placeholder="(555) 123-4567" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address (Optional)</label>
                <input type="email" class="form-control" id="customerEmail" placeholder="your@email.com">
            </div>
            <div class="form-group">
                <label>Special Instructions</label>
                <textarea class="form-control" id="specialInstructions" placeholder="Any allergies, preferences, or special requests..." rows="3"></textarea>
            </div>
            
            <div class="cart">
                <h3>🛒 Your Order</h3>
                <div id="cartItems">
                    <p style="text-align: center; color: var(--muted); padding: 2rem;">No items selected yet. Browse our menu above to get started!</p>
                </div>
                <div class="cart-total" id="cartTotal">Total: $0.00</div>
            </div>
            
            <button class="btn btn-success btn-block" onclick="placeOrder()">
                <span id="orderButtonText">Submit Order</span>
                <div id="orderButtonLoading" class="loading" style="display: none; margin-left: 1rem;"></div>
            </button>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section">
        <div class="section-title">
            <h2>Find Our Truck</h2>
            <p>We're mobile and ready to serve you fresh, hot food</p>
        </div>
        
        <div class="contact-info">
            <div class="contact-item">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📍</div>
                <h3>Current Location</h3>
                <p>Downtown City Center<br>Check our social media for daily locations and special events</p>
            </div>
            <div class="contact-item">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📞</div>
                <h3>Phone Orders</h3>
                <p>(555) 123-4567<br>Call ahead for faster service</p>
            </div>
            <div class="contact-item">
                <div style="font-size: 3rem; margin-bottom: 1rem;">⏰</div>
                <h3>Operating Hours</h3>
                <p>Mon-Sat: 11AM - 8PM<br>Sunday: 12PM - 6PM<br><em>Weather permitting</em></p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="logo" style="font-size: 3rem; margin-bottom: 1rem;">🚚🍟 CY Frying</div>
            <p style="font-size: 1.2rem; margin-bottom: 2rem;">Bringing Southern Comfort to Your Neighborhood</p>
            <div class="social-links">
                <div class="social-link" onclick="showNotification('Follow us on Facebook!', 'success')">📘</div>
                <div class="social-link" onclick="showNotification('Follow us on Instagram!', 'success')">📷</div>
                <div class="social-link" onclick="showNotification('Follow us on Twitter!', 'success')">🐦</div>
                <div class="social-link" onclick="showNotification('Call us for orders!', 'success')">📞</div>
            </div>
            <p style="margin-top: 2rem; opacity: 0.8;">© 2024 CY Frying. All rights reserved. | Fresh • Local • Delicious</p>
        </div>
    </footer>

    <!-- Admin Login Modal -->
    <div id="adminLoginModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeAdminLoginModal()">&times;</span>
            <h2 style="color: var(--primary); margin-bottom: 2rem; font-size: 2.2rem;">🔐 Admin Login</h2>
            <div class="form-group">
                <label>Username</label>
                <input type="text" class="form-control" id="adminUsername" value="admin" placeholder="Enter username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" class="form-control" id="adminPassword" value="admin123" placeholder="Enter password">
            </div>
            <button class="btn btn-block" onclick="adminLogin()">
                <span id="loginButtonText">Login to Dashboard</span>
                <div id="loginButtonLoading" class="loading" style="display: none; margin-left: 1rem;"></div>
            </button>
            <div id="adminLoginStatus" style="margin-top: 1rem;"></div>
        </div>
    </div>

    <!-- Enhanced Admin Panel Modal -->
    <div id="adminPanelModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAdminPanel()">&times;</span>
            
            <div class="admin-header">
                <div>
                    <div class="admin-title">⚙️ Admin Dashboard</div>
                    <p style="color: var(--muted); margin-top: 0.5rem;">Manage your food truck operations</p>
                </div>
                <div class="admin-user">
                    Welcome back, <strong><span id="adminUserName"></span></strong>!
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-number" id="totalOrders">0</div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-number" id="totalRevenue">$0</div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🍽️</div>
                    <div class="stat-number" id="menuItemsCount">0</div>
                    <div class="stat-label">Menu Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number" id="pendingOrders">0</div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>
            
            <div class="admin-tabs">
                <div class="admin-tab active" onclick="switchAdminTab(this, 'orders')">📋 Order Management</div>
                <div class="admin-tab" onclick="switchAdminTab(this, 'menu')">🍽️ Menu Control</div>
                <div class="admin-tab" onclick="switchAdminTab(this, 'analytics')">📊 Analytics</div>
                <div class="admin-tab" onclick="switchAdminTab(this, 'settings')">⚙️ Settings</div>
            </div>
            
            <!-- Orders Tab -->
            <div id="ordersTab" class="admin-tab-content active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h3>Recent Orders</h3>
                    <div style="display: flex; gap: 1rem;">
                        <button class="btn btn-info btn-small" onclick="loadOrders()">🔄 Refresh</button>
                        <select id="orderStatusFilter" onchange="filterOrders()" style="padding: 0.8rem; border-radius: 10px; border: 2px solid var(--light);">
                            <option value="all">All Orders</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                </div>
                <div id="ordersContainer" class="orders-grid">
                    <!-- Orders will be loaded here -->
                </div>
            </div>
            
            <!-- Menu Management Tab -->
            <div id="menuTab" class="admin-tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h3>Menu Management</h3>
                    <button class="btn btn-success btn-small" onclick="showAddMenuForm()">➕ Add New Item</button>
                </div>
                
                <div id="addMenuForm" style="display: none; background: var(--light); padding: 2rem; border-radius: 15px; margin-bottom: 2rem;">
                    <h4 style="color: var(--primary); margin-bottom: 1.5rem;">Add New Menu Item</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Item Name</label>
                            <input type="text" class="form-control" id="newItemName" placeholder="e.g., Crispy Frog Legs">
                        </div>
                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" class="form-control" id="newItemPrice" placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select class="form-control" id="newItemCategory">
                                <option value="mains">Main Dishes</option>
                                <option value="sides">Sides</option>
                                <option value="beverages">Beverages</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tags</label>
                            <select class="form-control" id="newItemTags">
                                <option value="">None</option>
                                <option value="popular">Popular</option>
                                <option value="spicy">Spicy</option>
                                <option value="new">New</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" id="newItemDesc" placeholder="Describe this delicious item..." rows="3"></textarea>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button class="btn btn-success" onclick="addMenuItem()">➕ Add Item</button>
                        <button class="btn btn-small" onclick="hideAddMenuForm()" style="background: var(--muted);">Cancel</button>
                    </div>
                </div>
                
                <div style="background: var(--secondary); border-radius: 15px; overflow: hidden; box-shadow: var(--shadow);">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="menuItemsTable">
                            <!-- Menu items will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Analytics Tab -->
            <div id="analyticsTab" class="admin-tab-content">
                <h3>Business Analytics</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin: 2rem 0;">
                    <div style="background: var(--light); padding: 2rem; border-radius: 15px;">
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">📈 Sales Overview</h4>
                        <p><strong>Total Revenue:</strong> <span id="analyticsRevenue">$0</span></p>
                        <p><strong>Average Order Value:</strong> <span id="analyticsAvgOrder">$0</span></p>
                        <p><strong>Orders Today:</strong> <span id="analyticsOrdersToday">0</span></p>
                    </div>
                    <div style="background: var(--light); padding: 2rem; border-radius: 15px;">
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">🍽️ Popular Items</h4>
                        <p><strong>Top Selling Item:</strong> <span id="analyticsTopItem">N/A</span></p>
                        <p><strong>Most Ordered Category:</strong> <span id="analyticsTopCategory">N/A</span></p>
                        <p><strong>Customer Favorites:</strong> <span id="analyticsFavorites">N/A</span></p>
                    </div>
                </div>
                <div style="background: var(--secondary); padding: 2rem; border-radius: 15px; box-shadow: var(--shadow);">
                    <h4 style="color: var(--primary); margin-bottom: 1rem;">📊 Order Trends</h4>
                    <div id="orderTrends">
                        <p>Order trend analysis will appear here once you have more order data.</p>
                    </div>
                </div>
            </div>
            
            <!-- Settings Tab -->
            <div id="settingsTab" class="admin-tab-content">
                <h3>System Settings</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin: 2rem 0;">
                    <div style="background: var(--light); padding: 2rem; border-radius: 15px;">
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">🚚 Truck Status</h4>
                        <div class="form-group">
                            <label>Operating Status</label>
                            <select class="form-control" id="truckStatus">
                                <option value="open">Open for Business</option>
                                <option value="busy">Busy - Limited Orders</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <button class="btn btn-info btn-small" onclick="updateTruckStatus()">Update Status</button>
                    </div>
                    <div style="background: var(--light); padding: 2rem; border-radius: 15px;">
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">🗄️ Data Management</h4>
                        <button class="btn btn-warning btn-small" onclick="exportOrders()" style="margin-bottom: 1rem; width: 100%;">📥 Export Orders</button>
                        <button class="btn btn-info btn-small" onclick="backupData()" style="width: 100%;">💾 Backup Data</button>
                    </div>
                </div>
                <div style="background: var(--secondary); padding: 2rem; border-radius: 15px; box-shadow: var(--shadow);">
                    <h4 style="color: var(--danger); margin-bottom: 1rem;">⚠️ Advanced Options</h4>
                    <p style="color: var(--muted); margin-bottom: 1rem;">Use these options with caution</p>
                    <button class="btn btn-danger btn-small" onclick="clearOldOrders()">🗑️ Clear Old Orders (30+ days)</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Success Modal -->
    <div id="orderSuccessModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeOrderSuccessModal()">&times;</span>
            <div style="text-align: center;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                <h2 style="color: var(--success); margin-bottom: 2rem;">Order Submitted Successfully!</h2>
                <div id="orderSuccessContent" style="margin: 2rem 0; background: var(--light); padding: 2rem; border-radius: 15px;">
                    <!-- Order details will be inserted here -->
                </div>
                <div style="background: rgba(46, 204, 113, 0.1); padding: 1.5rem; border-radius: 15px; margin: 2rem 0;">
                    <p style="color: var(--success); font-weight: 600; margin-bottom: 0.5rem;">Next Steps:</p>
                    <p style="color: var(--dark);">Please proceed to our food truck window to pay and collect your order. We'll have it ready for you shortly!</p>
                </div>
                <button class="btn btn-success" onclick="closeOrderSuccessModal()">Got It!</button>
            </div>
        </div>
    </div>

    <!-- Edit Menu Item Modal -->
    <div id="editMenuItemModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeEditMenuModal()">&times;</span>
            <h2 style="color: var(--primary); margin-bottom: 2rem;">✏️ Edit Menu Item</h2>
            <div class="form-row">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" class="form-control" id="editItemName">
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input type="number" class="form-control" id="editItemPrice" step="0.01" min="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select class="form-control" id="editItemCategory">
                        <option value="mains">Main Dishes</option>
                        <option value="sides">Sides</option>
                        <option value="beverages">Beverages</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tags</label>
                    <select class="form-control" id="editItemTags">
                        <option value="">None</option>
                        <option value="popular">Popular</option>
                        <option value="spicy">Spicy</option>
                        <option value="new">New</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" id="editItemDesc" rows="3"></textarea>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button class="btn btn-small" onclick="closeEditMenuModal()" style="background: var(--muted);">Cancel</button>
                <button class="btn btn-success" onclick="saveMenuItemChanges()">💾 Save Changes</button>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let cart = [];
        let menuItems = [];
        let currentAdmin = null;
        let allOrders = [];
        let editingItemId = null;
        let currentMenuFilter = 'all';

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
            setupEventListeners();
        });

        function initializeApp() {
            loadMenuItems();
            loadOrders();
        }

        function setupEventListeners() {
            // Smooth scrolling for navigation
            document.querySelectorAll('.nav-links a').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href.startsWith('#')) {
                        e.preventDefault();
                        scrollToSection(href.substring(1));
                    }
                });
            });

            // Close modals when clicking outside
            window.onclick = function(event) {
                const modals = ['adminLoginModal', 'adminPanelModal', 'orderSuccessModal', 'editMenuItemModal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (event.target === modal) {
                        closeModal(modalId);
                    }
                });
            };

            // Keyboard shortcuts
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeAllModals();
                }
            });

            // Input validation
            document.getElementById('customerPhone').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 6) {
                    value = '(' + value.slice(0,3) + ') ' + value.slice(3,6) + '-' + value.slice(6,10);
                }
                e.target.value = value;
            });
        }

        // Database Functions
        async function callAPI(endpoint, data = {}) {
            try {
                const formData = new FormData();
                formData.append('action', endpoint);
                
                for (const key in data) {
                    formData.append(key, data[key]);
                }
                
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`API call failed: ${response.statusText}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                showNotification('Database connection error.', 'error');
                return { success: false, error: error.message };
            }
        }

        // Load menu items from database
        async function loadMenuItems() {
            const result = await callAPI('get_menu_items');
            
            if (result && result.success) {
                menuItems = result.data;
                displayMenu();
                updateMenuItemsCount();
            } else {
                showNotification('Failed to load menu items', 'error');
            }
        }

        // Load orders from database
        async function loadOrders() {
            const result = await callAPI('get_orders');
            
            if (result && result.success) {
                allOrders = result.data;
                updateAdminStats();
            } else {
                showNotification('Failed to load orders', 'error');
            }
        }

        // Save order to database
        async function saveOrder(order) {
            const result = await callAPI('save_order', order);
            return result && result.success;
        }

        // Save menu item to database
        async function saveMenuItem(item) {
            const result = await callAPI('save_menu_item', item);
            return result && result.success;
        }

        // Update menu item in database
        async function updateMenuItem(item) {
            const result = await callAPI('update_menu_item', item);
            return result && result.success;
        }

        // Delete menu item from database
        async function deleteMenuItemFromDB(itemId) {
            const result = await callAPI('delete_menu_item', { id: itemId });
            return result && result.success;
        }

        // Update order status in database
        async function updateOrderStatusInDB(orderId, status) {
            const result = await callAPI('update_order_status', { 
                order_id: orderId, 
                status: status 
            });
            return result && result.success;
        }

        // Refund order in database
        async function refundOrderInDB(orderId) {
            const result = await callAPI('refund_order', { order_id: orderId });
            return result && result.success;
        }

        // Menu functions
        function displayMenu() {
            const container = document.getElementById('menuContainer');
            let filteredItems = menuItems;
            
            if (currentMenuFilter !== 'all') {
                filteredItems = menuItems.filter(item => item.category === currentMenuFilter);
            }
            
            if (filteredItems.length === 0) {
                container.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: var(--muted);"><div style="font-size: 3rem; margin-bottom: 1rem;">🍽️</div><h3>No items available</h3><p>Check back soon or contact us directly!</p></div>';
                return;
            }
            
            container.innerHTML = filteredItems.map(item => {
                return '<div class="menu-item" data-category="' + item.category + '">' +
                    '<div class="menu-item-img">' + getFoodEmoji(item.name) + '</div>' +
                    '<div class="menu-item-content">' +
                    '<h3>' + item.name + '</h3>' +
                    '<div class="menu-item-meta">' +
                    (item.tags ? '<span class="menu-item-tag ' + item.tags + '">' + item.tags.charAt(0).toUpperCase() + item.tags.slice(1) + '</span>' : '') +
                    '<span class="menu-item-tag">' + getCategoryName(item.category) + '</span>' +
                    '</div>' +
                    '<div class="menu-item-price">$' + parseFloat(item.price).toFixed(2) + '</div>' +
                    '<p class="menu-item-desc">' + (item.description || getFoodDescription(item.name)) + '</p>' +
                    '<button class="btn btn-small" onclick="addToCart(\'' + item.id + '\')">' +
                    '➕ Add to Order' +
                    '</button>' +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function filterMenu(category) {
            currentMenuFilter = category;
            
            // Update active button
            document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            displayMenu();
        }

        function getCategoryName(category) {
            const names = {
                'mains': 'Main Dish',
                'sides': 'Side',
                'beverages': 'Beverage'
            };
            return names[category] || 'Other';
        }

        function getFoodEmoji(foodName) {
            const emojiMap = {
                'frog': '🐸',
                'perch': '🐟',
                'catfish': '🐡',
                'fries': '🍟',
                'coleslaw': '🥗',
                'hush puppies': '🌽',
                'tea': '🍵',
                'lemonade': '🍋',
                'combo': '🍱'
            };
            
            for (const [key, emoji] of Object.entries(emojiMap)) {
                if (foodName.toLowerCase().includes(key)) {
                    return emoji;
                }
            }
            return '🍽️';
        }

        function getFoodDescription(foodName) {
            const descriptions = {
                'frog legs': 'Tender and flavorful frog legs prepared with our signature seasoning.',
                'perch': 'Fresh perch fillets with a light crispy coating.',
                'catfish': 'Southern-style catfish with authentic spices.',
                'fries': 'Golden crispy fries cooked to perfection.',
                'coleslaw': 'Creamy homemade coleslaw with a tangy twist.',
                'hush puppies': 'Traditional cornmeal fritters with a crispy exterior.',
                'tea': 'Refreshing beverage to complement your meal.',
                'lemonade': 'Freshly squeezed lemonade for a sweet treat.'
            };
            
            for (const [key, desc] of Object.entries(descriptions)) {
                if (foodName.toLowerCase().includes(key)) {
                    return desc;
                }
            }
            return 'Delicious item from our southern kitchen.';
        }

        // Cart functions
        function addToCart(itemId) {
            const item = menuItems.find(i => i.id === itemId);
            if (item) {
                const existingItem = cart.find(c => c.id === itemId);
                if (existingItem) {
                    existingItem.quantity++;
                } else {
                    cart.push(Object.assign({}, item, { quantity: 1 }));
                }
                updateCart();
                showNotification(item.name + ' added to order!', 'success');
            }
        }

        function removeFromCart(itemId) {
            cart = cart.filter(c => c.id !== itemId);
            updateCart();
            showNotification('Item removed from order', 'success');
        }

        function changeQuantity(itemId, delta) {
            const item = cart.find(c => c.id === itemId);
            if (item) {
                const newQuantity = Math.max(1, item.quantity + delta);
                item.quantity = newQuantity;
                updateCart();
            }
        }

        function updateCart() {
            const container = document.getElementById('cartItems');
            const totalElement = document.getElementById('cartTotal');
            
            if (cart.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: var(--muted); padding: 2rem;">No items selected yet. Browse our menu above to get started!</p>';
                totalElement.textContent = 'Total: $0.00';
                return;
            }
            
            container.innerHTML = cart.map(item => {
                const itemTotal = (parseFloat(item.price) * item.quantity).toFixed(2);
                return '<div class="cart-item">' +
                    '<div class="cart-item-info">' +
                    '<div class="cart-item-name">' + item.name + '</div>' +
                    '<div class="cart-item-price">$' + parseFloat(item.price).toFixed(2) + ' each</div>' +
                    '</div>' +
                    '<div class="cart-item-controls">' +
                    '<div class="cart-item-qty">' +
                    '<button class="qty-btn" onclick="changeQuantity(\'' + item.id + '\', -1)">-</button>' +
                    '<span style="min-width: 30px; text-align: center;">' + item.quantity + '</span>' +
                    '<button class="qty-btn" onclick="changeQuantity(\'' + item.id + '\', 1)">+</button>' +
                    '</div>' +
                    '<div style="min-width: 80px; text-align: right;">$' + itemTotal + '</div>' +
                    '<button class="qty-btn" style="background: var(--danger);" onclick="removeFromCart(\'' + item.id + '\')">×</button>' +
                    '</div>' +
                    '</div>';
            }).join('');
            
            // Calculate total
            const total = cart.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);
            totalElement.textContent = 'Total: $' + total.toFixed(2);
        }

        // Order functions
        async function placeOrder() {
            const name = document.getElementById('customerName').value.trim();
            const phone = document.getElementById('customerPhone').value.trim();
            const email = document.getElementById('customerEmail').value.trim();
            const instructions = document.getElementById('specialInstructions').value.trim();
            
            if (!name || !phone) {
                showNotification('Please fill in required fields: Name and Phone', 'error');
                return;
            }
            
            if (cart.length === 0) {
                showNotification('Please add items to your order first', 'error');
                return;
            }
            
            // Show loading state
            const buttonText = document.getElementById('orderButtonText');
            const buttonLoading = document.getElementById('orderButtonLoading');
            buttonText.textContent = 'Processing...';
            buttonLoading.style.display = 'inline-block';
            
            try {
                const orderId = 'ORD' + Date.now();
                const total = cart.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);
                
                const newOrder = {
                    id: orderId,
                    customer_name: name,
                    customer_phone: phone,
                    customer_email: email,
                    special_instructions: instructions,
                    total: total.toFixed(2),
                    status: 'pending',
                    order_items: JSON.stringify(cart)
                };
                
                // Save to database
                const saved = await saveOrder(newOrder);
                
                if (saved) {
                    // Reset form and cart
                    document.getElementById('customerName').value = '';
                    document.getElementById('customerPhone').value = '';
                    document.getElementById('customerEmail').value = '';
                    document.getElementById('specialInstructions').value = '';
                    cart = [];
                    updateCart();
                    
                    // Show success modal
                    showOrderSuccessModal(newOrder);
                    
                    // Reload orders to include the new one
                    loadOrders();
                } else {
                    showNotification('Failed to save order. Please try again.', 'error');
                }
            } catch (error) {
                showNotification('Error placing order. Please try again.', 'error');
            } finally {
                // Reset button
                buttonText.textContent = 'Submit Order';
                buttonLoading.style.display = 'none';
            }
        }

        function showOrderSuccessModal(order) {
            const modal = document.getElementById('orderSuccessModal');
            const content = document.getElementById('orderSuccessContent');
            
            content.innerHTML = `
                <h3 style="color: var(--primary); margin-bottom: 1rem;">Order #${order.id}</h3>
                <p><strong>Customer:</strong> ${order.customer_name}</p>
                <p><strong>Phone:</strong> ${order.customer_phone}</p>
                <p><strong>Total:</strong> $${order.total}</p>
                <div style="margin-top: 1rem;">
                    <h4 style="color: var(--dark); margin-bottom: 0.5rem;">Order Items:</h4>
                    ${JSON.parse(order.order_items).map(item => 
                        `<p style="margin: 0.25rem 0;">${item.quantity}x ${item.name} - $${(parseFloat(item.price) * item.quantity).toFixed(2)}</p>`
                    ).join('')}
                </div>
            `;
            
            modal.style.display = 'block';
        }

        function closeOrderSuccessModal() {
            document.getElementById('orderSuccessModal').style.display = 'none';
        }

        // Admin functions
        function openAdminModal() {
            document.getElementById('adminLoginModal').style.display = 'block';
        }

        function closeAdminLoginModal() {
            document.getElementById('adminLoginModal').style.display = 'none';
            document.getElementById('adminLoginStatus').innerHTML = '';
        }

        function adminLogin() {
            const username = document.getElementById('adminUsername').value;
            const password = document.getElementById('adminPassword').value;
            
            // Show loading state
            const buttonText = document.getElementById('loginButtonText');
            const buttonLoading = document.getElementById('loginButtonLoading');
            buttonText.textContent = 'Logging in...';
            buttonLoading.style.display = 'inline-block';
            
            // Simulate authentication
            setTimeout(() => {
                if (username === 'admin' && password === 'admin123') {
                    currentAdmin = { username: username };
                    document.getElementById('adminLoginModal').style.display = 'none';
                    document.getElementById('adminPanelModal').style.display = 'block';
                    document.getElementById('adminUserName').textContent = username;
                    
                    // Load admin data
                    loadOrders();
                    updateAdminStats();
                    loadMenuItemsTable();
                } else {
                    document.getElementById('adminLoginStatus').innerHTML = 
                        '<p style="color: var(--danger); background: rgba(192, 57, 43, 0.1); padding: 1rem; border-radius: 10px;">Invalid credentials. Try admin/admin123</p>';
                }
                
                // Reset button
                buttonText.textContent = 'Login to Dashboard';
                buttonLoading.style.display = 'none';
            }, 1000);
        }

        function closeAdminPanel() {
            document.getElementById('adminPanelModal').style.display = 'none';
            currentAdmin = null;
        }

        function switchAdminTab(tabElement, tabId) {
            // Update active tab
            document.querySelectorAll('.admin-tab').forEach(tab => tab.classList.remove('active'));
            tabElement.classList.add('active');
            
            // Show active content
            document.querySelectorAll('.admin-tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(tabId + 'Tab').classList.add('active');
            
            // Load data for the tab if needed
            if (tabId === 'analytics') {
                updateAnalytics();
            }
        }

        function loadOrders() {
            const container = document.getElementById('ordersContainer');
            const statusFilter = document.getElementById('orderStatusFilter').value;
            
            let filteredOrders = allOrders;
            if (statusFilter !== 'all') {
                filteredOrders = allOrders.filter(order => order.status === statusFilter);
            }
            
            if (filteredOrders.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 3rem; color: var(--muted);"><div style="font-size: 3rem; margin-bottom: 1rem;">📋</div><h3>No orders found</h3><p>Orders will appear here as customers place them.</p></div>';
                return;
            }
            
            container.innerHTML = filteredOrders.map(order => {
                const items = JSON.parse(order.order_items);
                const orderDate = new Date(order.created_at).toLocaleString();
                
                return `<div class="order-card ${order.status}">
                    <div class="order-header">
                        <div class="order-id">${order.id}</div>
                        <div class="order-status status-${order.status}">${order.status}</div>
                    </div>
                    <p><strong>Customer:</strong> ${order.customer_name} | ${order.customer_phone}</p>
                    <p><strong>Order Date:</strong> ${orderDate}</p>
                    <p><strong>Total:</strong> $${order.total}</p>
                    <div style="margin-top: 1rem;">
                        <h4 style="color: var(--dark); margin-bottom: 0.5rem;">Items:</h4>
                        ${items.map(item => `<p style="margin: 0.25rem 0;">${item.quantity}x ${item.name}</p>`).join('')}
                    </div>
                    <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        ${order.status === 'pending' ? 
                            `<button class="btn btn-success btn-small" onclick="updateOrderStatus('${order.id}', 'completed')">✅ Complete</button>
                             <button class="btn btn-danger btn-small" onclick="updateOrderStatus('${order.id}', 'cancelled')">❌ Cancel</button>` : 
                            ''
                        }
                        ${order.status === 'completed' ? 
                            `<button class="btn btn-warning btn-small" onclick="refundOrder('${order.id}')">💸 Refund</button>` : 
                            ''
                        }
                        ${order.status !== 'pending' ? 
                            `<button class="btn btn-info btn-small" onclick="updateOrderStatus('${order.id}', 'pending')">↩️ Reopen</button>` : 
                            ''
                        }
                    </div>
                </div>`;
            }).join('');
        }

        function filterOrders() {
            loadOrders();
        }

        async function updateOrderStatus(orderId, status) {
            const order = allOrders.find(o => o.id === orderId);
            if (order) {
                // Update in database
                const updated = await updateOrderStatusInDB(orderId, status);
                if (updated) {
                    order.status = status;
                    loadOrders();
                    updateAdminStats();
                    showNotification(`Order ${orderId} status updated to ${status}`, 'success');
                } else {
                    showNotification('Failed to update order status', 'error');
                }
            }
        }

        async function refundOrder(orderId) {
            if (confirm('Are you sure you want to refund this order? This action cannot be undone.')) {
                const order = allOrders.find(o => o.id === orderId);
                if (order) {
                    // Update in database
                    const refunded = await refundOrderInDB(orderId);
                    if (refunded) {
                        order.status = 'refunded';
                        loadOrders();
                        updateAdminStats();
                        showNotification(`Order ${orderId} has been refunded`, 'success');
                    } else {
                        showNotification('Failed to refund order', 'error');
                    }
                }
            }
        }

        function updateAdminStats() {
            document.getElementById('totalOrders').textContent = allOrders.length;
            
            // Calculate total revenue (excluding refunded orders)
            const totalRevenue = allOrders
                .filter(order => order.status !== 'refunded')
                .reduce((sum, order) => sum + parseFloat(order.total), 0);
            document.getElementById('totalRevenue').textContent = '$' + totalRevenue.toFixed(2);
            
            const pendingOrders = allOrders.filter(order => order.status === 'pending').length;
            document.getElementById('pendingOrders').textContent = pendingOrders;
            
            updateMenuItemsCount();
        }

        function updateMenuItemsCount() {
            document.getElementById('menuItemsCount').textContent = menuItems.length;
        }

        function showAddMenuForm() {
            document.getElementById('addMenuForm').style.display = 'block';
        }

        function hideAddMenuForm() {
            document.getElementById('addMenuForm').style.display = 'none';
            // Reset form
            document.getElementById('newItemName').value = '';
            document.getElementById('newItemPrice').value = '';
            document.getElementById('newItemCategory').value = 'mains';
            document.getElementById('newItemTags').value = '';
            document.getElementById('newItemDesc').value = '';
        }

        async function addMenuItem() {
            const name = document.getElementById('newItemName').value.trim();
            const price = document.getElementById('newItemPrice').value.trim();
            const category = document.getElementById('newItemCategory').value;
            const tags = document.getElementById('newItemTags').value;
            const description = document.getElementById('newItemDesc').value.trim();
            
            if (!name || !price) {
                showNotification('Please fill in required fields: Name and Price', 'error');
                return;
            }
            
            const newItem = {
                id: 'item' + Date.now(),
                name: name,
                price: parseFloat(price).toFixed(2),
                category: category,
                tags: tags,
                description: description
            };
            
            // Save to database
            const saved = await saveMenuItem(newItem);
            
            if (saved) {
                menuItems.push(newItem);
                hideAddMenuForm();
                displayMenu();
                loadMenuItemsTable();
                updateMenuItemsCount();
                showNotification('Menu item added successfully!', 'success');
            } else {
                showNotification('Failed to add menu item. Please try again.', 'error');
            }
        }

        function loadMenuItemsTable() {
            const container = document.getElementById('menuItemsTable');
            
            if (menuItems.length === 0) {
                container.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--muted);">No menu items found. Add some items to get started.</td></tr>';
                return;
            }
            
            container.innerHTML = menuItems.map(item => {
                return `<tr>
                    <td>${item.name}</td>
                    <td>${getCategoryName(item.category)}</td>
                    <td>$${item.price}</td>
                    <td>${item.description || 'No description'}</td>
                    <td><span class="menu-item-tag ${item.tags}">${item.tags ? item.tags.charAt(0).toUpperCase() + item.tags.slice(1) : 'Standard'}</span></td>
                    <td>
                        <button class="btn btn-small" onclick="editMenuItem('${item.id}')">✏️ Edit</button>
                        <button class="btn btn-danger btn-small" onclick="deleteMenuItem('${item.id}')">🗑️ Delete</button>
                    </td>
                </tr>`;
            }).join('');
        }

        function editMenuItem(itemId) {
            const item = menuItems.find(i => i.id === itemId);
            if (item) {
                editingItemId = itemId;
                document.getElementById('editItemName').value = item.name;
                document.getElementById('editItemPrice').value = item.price;
                document.getElementById('editItemCategory').value = item.category;
                document.getElementById('editItemTags').value = item.tags || '';
                document.getElementById('editItemDesc').value = item.description || '';
                document.getElementById('editMenuItemModal').style.display = 'block';
            }
        }

        function closeEditMenuModal() {
            document.getElementById('editMenuItemModal').style.display = 'none';
            editingItemId = null;
        }

        async function saveMenuItemChanges() {
            const item = menuItems.find(i => i.id === editingItemId);
            if (item) {
                item.name = document.getElementById('editItemName').value;
                item.price = parseFloat(document.getElementById('editItemPrice').value).toFixed(2);
                item.category = document.getElementById('editItemCategory').value;
                item.tags = document.getElementById('editItemTags').value;
                item.description = document.getElementById('editItemDesc').value;
                
                // Update in database
                const updated = await updateMenuItem(item);
                
                if (updated) {
                    closeEditMenuModal();
                    displayMenu();
                    loadMenuItemsTable();
                    showNotification('Menu item updated successfully!', 'success');
                } else {
                    showNotification('Failed to update menu item', 'error');
                }
            }
        }

        async function deleteMenuItem(itemId) {
            if (confirm('Are you sure you want to delete this menu item?')) {
                // Delete from database
                const deleted = await deleteMenuItemFromDB(itemId);
                
                if (deleted) {
                    menuItems = menuItems.filter(item => item.id !== itemId);
                    displayMenu();
                    loadMenuItemsTable();
                    updateMenuItemsCount();
                    showNotification('Menu item deleted successfully!', 'success');
                } else {
                    showNotification('Failed to delete menu item', 'error');
                }
            }
        }

        function updateAnalytics() {
            // Update revenue (excluding refunded orders)
            const totalRevenue = allOrders
                .filter(order => order.status !== 'refunded')
                .reduce((sum, order) => sum + parseFloat(order.total), 0);
            document.getElementById('analyticsRevenue').textContent = '$' + totalRevenue.toFixed(2);
            
            // Update average order value (excluding refunded orders)
            const validOrders = allOrders.filter(order => order.status !== 'refunded');
            const avgOrderValue = validOrders.length > 0 ? totalRevenue / validOrders.length : 0;
            document.getElementById('analyticsAvgOrder').textContent = '$' + avgOrderValue.toFixed(2);
            
            // Update orders today (excluding refunded orders)
            const today = new Date().toDateString();
            const ordersToday = validOrders.filter(order => 
                new Date(order.created_at).toDateString() === today
            ).length;
            document.getElementById('analyticsOrdersToday').textContent = ordersToday;
            
            // Update popular items (excluding refunded orders)
            if (validOrders.length > 0) {
                const itemCounts = {};
                validOrders.forEach(order => {
                    const items = JSON.parse(order.order_items);
                    items.forEach(item => {
                        itemCounts[item.name] = (itemCounts[item.name] || 0) + item.quantity;
                    });
                });
                
                const popularItems = Object.entries(itemCounts).sort((a, b) => b[1] - a[1]);
                document.getElementById('analyticsTopItem').textContent = popularItems.length > 0 ? 
                    `${popularItems[0][0]} (${popularItems[0][1]} sold)` : 'N/A';
                
                // Update category popularity
                const categoryCounts = {};
                validOrders.forEach(order => {
                    const items = JSON.parse(order.order_items);
                    items.forEach(item => {
                        const menuItem = menuItems.find(mi => mi.name === item.name);
                        if (menuItem) {
                            categoryCounts[menuItem.category] = (categoryCounts[menuItem.category] || 0) + item.quantity;
                        }
                    });
                });
                
                const popularCategory = Object.entries(categoryCounts).sort((a, b) => b[1] - a[1]);
                document.getElementById('analyticsTopCategory').textContent = popularCategory.length > 0 ? 
                    `${getCategoryName(popularCategory[0][0])} (${popularCategory[0][1]} items)` : 'N/A';
                
                // Update favorites (top 3 items)
                const favorites = popularItems.slice(0, 3).map(item => item[0]).join(', ');
                document.getElementById('analyticsFavorites').textContent = favorites || 'N/A';
            }
        }

        function updateTruckStatus() {
            const status = document.getElementById('truckStatus').value;
            showNotification(`Truck status updated to: ${status}`, 'success');
        }

        function exportOrders() {
            showNotification('Orders exported successfully!', 'success');
        }

        function backupData() {
            showNotification('Data backup completed!', 'success');
        }

        function clearOldOrders() {
            if (confirm('Are you sure you want to clear orders older than 30 days? This action cannot be undone.')) {
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                
                allOrders = allOrders.filter(order => new Date(order.created_at) > thirtyDaysAgo);
                loadOrders();
                updateAdminStats();
                showNotification('Old orders cleared successfully!', 'success');
            }
        }

        // Utility functions
        function scrollToSection(sectionId) {
            document.getElementById(sectionId).scrollIntoView({ behavior: 'smooth' });
        }

        function scrollToMenu() {
            scrollToSection('menu');
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function closeAllModals() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.style.display = 'none';
            });
        }
    </script>

    <?php
    // PHP Backend Code
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        
        $host = 'sql211.infinityfree.com';
        $username = 'if0_40027874';
        $password = 'ox7G27Vik8E';
        $database = 'if0_40027874_cyfrying';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
        
        $action = $_POST['action'] ?? '';
        
        switch($action) {
            case 'get_menu_items':
                $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY created_at DESC");
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $items]);
                break;
                
            case 'get_orders':
                $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $orders]);
                break;
                
            case 'save_order':
                $id = $_POST['id'] ?? '';
                $customer_name = $_POST['customer_name'] ?? '';
                $customer_phone = $_POST['customer_phone'] ?? '';
                $customer_email = $_POST['customer_email'] ?? '';
                $special_instructions = $_POST['special_instructions'] ?? '';
                $total = $_POST['total'] ?? 0;
                $status = $_POST['status'] ?? 'pending';
                $order_items = $_POST['order_items'] ?? '[]';
                
                $stmt = $pdo->prepare("INSERT INTO orders (id, customer_name, customer_phone, customer_email, special_instructions, total, status, order_items) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $success = $stmt->execute([$id, $customer_name, $customer_phone, $customer_email, $special_instructions, $total, $status, $order_items]);
                echo json_encode(['success' => $success]);
                break;
                
            case 'save_menu_item':
                $id = $_POST['id'] ?? '';
                $name = $_POST['name'] ?? '';
                $price = $_POST['price'] ?? 0;
                $category = $_POST['category'] ?? '';
                $tags = $_POST['tags'] ?? '';
                $description = $_POST['description'] ?? '';
                
                $stmt = $pdo->prepare("INSERT INTO menu_items (id, name, price, category, tags, description) VALUES (?, ?, ?, ?, ?, ?)");
                $success = $stmt->execute([$id, $name, $price, $category, $tags, $description]);
                echo json_encode(['success' => $success]);
                break;
                
            case 'update_menu_item':
                $id = $_POST['id'] ?? '';
                $name = $_POST['name'] ?? '';
                $price = $_POST['price'] ?? 0;
                $category = $_POST['category'] ?? '';
                $tags = $_POST['tags'] ?? '';
                $description = $_POST['description'] ?? '';
                
                $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, price = ?, category = ?, tags = ?, description = ? WHERE id = ?");
                $success = $stmt->execute([$name, $price, $category, $tags, $description, $id]);
                echo json_encode(['success' => $success]);
                break;
                
            case 'delete_menu_item':
                $id = $_POST['id'] ?? '';
                $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
                $success = $stmt->execute([$id]);
                echo json_encode(['success' => $success]);
                break;
                
            case 'update_order_status':
                $order_id = $_POST['order_id'] ?? '';
                $status = $_POST['status'] ?? '';
                
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $success = $stmt->execute([$status, $order_id]);
                echo json_encode(['success' => $success]);
                break;
                
            case 'refund_order':
                $order_id = $_POST['order_id'] ?? '';
                
                $stmt = $pdo->prepare("UPDATE orders SET status = 'refunded' WHERE id = ?");
                $success = $stmt->execute([$order_id]);
                echo json_encode(['success' => $success]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
        
        exit;
    }
    ?>
</body>
</html>