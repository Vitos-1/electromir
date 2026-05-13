<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElectroStore - Магазин электроники</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary-color: #f8c008; --secondary-color: #212529; --accent-color: #dc3545; --bg-color: #f0f2f5; --card-bg: #ffffff; --text-color: #212529; }
        [data-bs-theme="dark"] { --bg-color: #121212; --card-bg: #1e1e1e; --text-color: #f8f9fa; --primary-color: #ffcc00; }
        
        body { background: var(--bg-color); color: var(--text-color); font-family: 'Inter', 'Segoe UI', sans-serif; transition: background 0.3s, color 0.3s; }
        .navbar { backdrop-filter: blur(15px); background: var(--card-bg) !important; border-bottom: 2px solid var(--primary-color); }
        .product-card { transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); border: none; border-radius: 24px; overflow: hidden; background: var(--card-bg); color: var(--text-color); box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .card, .modal-content, .dropdown-menu, .filter-sidebar { background-color: var(--card-bg) !important; color: var(--text-color) !important; border: none !important; }
        .form-control, .form-select { background-color: var(--bg-color) !important; color: var(--text-color) !important; border: 1px solid rgba(128,128,128,0.2) !important; }
        .text-muted { color: rgba(128,128,128,0.7) !important; }
        .text-secondary { color: rgba(100,100,100,0.9) !important; }
        [data-bs-theme="dark"] .text-muted { color: rgba(255,255,255,0.5) !important; }
        [data-bs-theme="dark"] .text-secondary { color: rgba(255,255,255,0.8) !important; }
        [data-bs-theme="dark"] .text-dark, [data-bs-theme="dark"] .text-black, [data-bs-theme="dark"] .card-title, [data-bs-theme="dark"] h1, [data-bs-theme="dark"] h2, [data-bs-theme="dark"] h3, [data-bs-theme="dark"] h4, [data-bs-theme="dark"] h5, [data-bs-theme="dark"] h6 { color: var(--text-color) !important; }
        [data-bs-theme="dark"] .btn-light { background-color: rgba(255,255,255,0.1); border: none; color: var(--text-color); }
        [data-bs-theme="dark"] .bg-light { background-color: rgba(255,255,255,0.05) !important; color: var(--text-color) !important; }
        
        .nav-link { font-weight: 600; color: var(--text-color) !important; padding: 10px 15px !important; }
        .nav-link:hover { color: var(--primary-color) !important; }
        .navbar-brand span { color: var(--text-color) !important; }
        
        .navbar-brand img { height: 50px; object-fit: contain; }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 30px 60px rgba(0,0,0,0.12); }
        .cart-badge { font-size: 0.75rem; position: absolute; top: -5px; right: -5px; padding: 0.5em 0.7em; background: var(--accent-color) !important; border: 2px solid var(--card-bg); }
        .btn-primary { background-color: var(--primary-color); border: none; color: #000 !important; border-radius: 14px; padding: 12px 24px; font-weight: 700; transition: all 0.3s; }
        .btn-primary:hover { background-color: #e5af07; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(248, 192, 8, 0.3); color: #000 !important; }
        
        /* Исправление баннеров в темной теме */
        [data-bs-theme="dark"] .carousel-caption { background: rgba(0,0,0,0.7) !important; border-radius: 20px; padding: 20px; }
        [data-bs-theme="dark"] .carousel-caption h2, [data-bs-theme="dark"] .carousel-caption p { color: #fff !important; }
        .filter-sidebar { background: #fff; border-radius: 24px; padding: 25px; position: sticky; top: 100px; }
        .carousel-item { height: 400px; border-radius: 30px; overflow: hidden; }
        .carousel-item img { object-fit: cover; height: 100%; width: 100%; }
        .carousel-caption { background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); border-radius: 20px; padding: 30px; bottom: 40px; }
        
        .hover-lift { transition: transform 0.2s; }
        .hover-lift:hover { transform: translateY(-5px); }
        
        /* Поиск с подсказками */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            z-index: 1000;
            display: none;
            overflow: hidden;
            margin-top: 10px;
        }
        .search-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
            transition: background 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }
        .search-item:last-child { border-bottom: none; }
        .search-item:hover { background: #f8f9fa; color: var(--primary-color); }
        .search-item img { width: 45px; height: 45px; object-fit: contain; margin-right: 15px; background: #fff; border-radius: 8px; }
        .search-item-info { display: flex; flex-direction: column; }
        .search-item-name { font-weight: 600; font-size: 0.95rem; line-height: 1.2; }
        .search-item-price { font-size: 0.85rem; color: #666; margin-top: 3px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light sticky-top mb-4 py-3">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/logo.png" alt="ЭЛЕКТРО МИР" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <span style="display:none; font-weight: 800; font-size: 1.5rem;">ЭЛЕКТРО <span style="color: var(--primary-color);">МИР</span></span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Каталог</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="catDropdown" role="button" data-bs-toggle="dropdown">Категории</a>
                    <ul class="dropdown-menu border-0 shadow-sm">
                        <?php
                        $header_cats = mysqli_query($conn, "SELECT * FROM categories");
                        while($hc = mysqli_fetch_assoc($header_cats)): ?>
                            <li><a class="dropdown-item" href="index.php?cat=<?= $hc['id'] ?>"><?= h($hc['name']) ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </li>
            </ul>

            <!-- Поиск -->
            <form action="index.php" method="GET" class="d-flex mx-auto col-lg-5 mb-2 mb-lg-0 px-lg-3 position-relative" id="searchForm">
                <div class="input-group">
                    <input type="text" name="search" id="searchInput" class="form-control border-0 bg-light rounded-start-pill ps-4" 
                           placeholder="Поиск товаров..." value="<?= isset($_GET['search']) ? h($_GET['search']) : '' ?>" autocomplete="off">
                    <button class="btn btn-light border-0 bg-light rounded-end-pill pe-4" type="submit">
                        <i class="bi bi-search text-primary"></i>
                    </button>
                </div>
                <div id="searchResults" class="search-results"></div>
            </form>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('searchInput');
                const searchResults = document.getElementById('searchResults');
                let timeout = null;

                searchInput.addEventListener('input', function() {
                    clearTimeout(timeout);
                    const query = this.value.trim();

                    if (query.length < 2) {
                        searchResults.style.display = 'none';
                        return;
                    }

                    timeout = setTimeout(() => {
                        fetch(`search_suggestions.php?q=${encodeURIComponent(query)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.length > 0) {
                                    searchResults.innerHTML = data.map(item => `
                                        <a href="product.php?id=${item.id}" class="search-item">
                                            <img src="${item.image}" alt="${item.name}">
                                            <div class="search-item-info">
                                                <span class="search-item-name">${item.name}</span>
                                                <span class="search-item-price">${item.price}</span>
                                            </div>
                                        </a>
                                    `).join('');
                                    searchResults.style.display = 'block';
                                } else {
                                    searchResults.style.display = 'none';
                                }
                            });
                    }, 300);
                });

                // Закрытие при клике вне
        document.addEventListener('click', function(e) {
            if (!document.getElementById('searchForm').contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // Логика тёмной темы
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;
        
        // Проверка сохраненной темы
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-bs-theme', savedTheme);
        updateThemeIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
                themeToggle.classList.replace('btn-light', 'btn-dark');
            } else {
                themeIcon.classList.replace('bi-sun-fill', 'bi-moon-fill');
                themeToggle.classList.replace('btn-dark', 'btn-light');
            }
        }
    });
    </script>

            <div class="d-flex align-items-center">
                <!-- Переключатель темы -->
                <button class="btn btn-light me-3 rounded-circle" id="themeToggle" title="Переключить тему">
                    <i class="bi bi-moon-fill" id="themeIcon"></i>
                </button>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="wishlist.php" class="btn btn-light me-3 position-relative">
                        <i class="bi bi-heart fs-5 text-danger"></i>
                        <?php 
                        $w_user_id = $_SESSION['user_id'];
                        // Проверка существования таблицы перед запросом
                        $check_wishlist = mysqli_query($conn, "SHOW TABLES LIKE 'wishlist'");
                        if(mysqli_num_rows($check_wishlist) > 0) {
                            $w_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM wishlist WHERE user_id = $w_user_id");
                            if($w_res) {
                                $w_count = mysqli_fetch_assoc($w_res)['count'];
                                if($w_count > 0): ?>
                                    <span class="badge rounded-pill bg-danger cart-badge"><?= $w_count ?></span>
                                <?php endif;
                            }
                        }
                        ?>
                    </a>
                <?php endif; ?>

                <a href="cart.php" class="btn btn-light me-3 position-relative">
                    <i class="bi bi-basket3 fs-5"></i>
                    <?php 
                    $cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
                    if($cart_count > 0): ?>
                        <span class="badge rounded-pill bg-danger cart-badge"><?= $cart_count ?></span>
                    <?php endif; ?>
                </a>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i> <?= h($_SESSION['user_name']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-clock-history me-2"></i>Заказы</a></li>
                            <?php if($_SESSION['role'] === 'admin'): ?>
                                <li><a class="dropdown-item text-danger" href="admin/index.php"><i class="bi bi-shield-lock me-2"></i>Админка</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Выйти</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary me-2">Войти</a>
                    <a href="register.php" class="btn btn-primary">Регистрация</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container min-vh-100">
