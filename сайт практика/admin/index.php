<?php
require_once '../includes/config.php';

// Защита админки
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Автоматическая миграция БД при необходимости
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'delivery_method'");
if (mysqli_num_rows($check_columns) == 0) {
    try {
        $conn->query("ALTER TABLE orders MODIFY COLUMN customer_address TEXT NULL");
        $conn->query("ALTER TABLE orders ADD COLUMN delivery_method ENUM('courier', 'pickup') DEFAULT 'courier' AFTER customer_email");
        $conn->query("ALTER TABLE orders ADD COLUMN payment_method ENUM('online', 'on_delivery') DEFAULT 'on_delivery' AFTER delivery_method");
        $conn->query("ALTER TABLE orders ADD COLUMN pickup_point_address TEXT AFTER payment_method");
    } catch (Exception $e) {
        // Ошибка миграции, логируем или игнорируем если уже есть
    }
}

// Получение статистики (с проверкой наличия колонок)
$total_orders = 0;
$total_revenue = 0;
$today_revenue = 0;
$check_orders = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if (mysqli_num_rows($check_orders) > 0) {
    $total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
    
    // Проверка колонки total_price
    $check_tp = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'total_price'");
    if (mysqli_num_rows($check_tp) > 0) {
        // Общая выручка (все, кроме отмененных)
        $total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as sum FROM orders WHERE status != 'cancelled'"))['sum'] ?? 0;
        
        // Выручка за сегодня (все, кроме отмененных)
        $today_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as sum FROM orders WHERE status != 'cancelled' AND DATE(created_at) = CURDATE()"))['sum'] ?? 0;
    }
}

$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];

// Последние заказы
$orders = false;
if (mysqli_num_rows($check_orders) > 0) {
    // Проверка колонки created_at
    $check_ca = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'created_at'");
    if (mysqli_num_rows($check_ca) > 0) {
        $orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
    } else {
        $orders = mysqli_query($conn, "SELECT * FROM orders LIMIT 10");
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора - ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: #212529; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.7); border-radius: 8px; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .card-stat { border: none; border-radius: 15px; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0 sidebar position-fixed d-none d-md-block">
            <div class="p-4">
                <h5 class="fw-bold text-primary mb-4">ADMIN PANEL</h5>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link active" href="index.php"><i class="bi bi-speedometer2 me-2"></i> Дашборд</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-box-seam me-2"></i> Товары</a></li>
                    <li class="nav-item"><a class="nav-link" href="categories.php"><i class="bi bi-tags me-2"></i> Категории</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php"><i class="bi bi-cart me-2"></i> Заказы</a></li>
                    <li class="nav-item"><a class="nav-link" href="reviews.php"><i class="bi bi-star me-2"></i> Отзывы</a></li>
                    <li class="nav-item"><a class="nav-link" href="support.php"><i class="bi bi-chat-dots me-2"></i> Поддержка</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people me-2"></i> Пользователи</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php"><i class="bi bi-house me-2"></i> На сайт</a></li>
                    <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Выход</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-auto p-4 p-md-5">
            <h2 class="fw-bold mb-4">Обзор магазина</h2>

            <!-- Stats -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="card card-stat bg-white shadow-sm p-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3"><i class="bi bi-cart-check fs-3"></i></div>
                            <div>
                                <h6 class="text-muted mb-1 small">Всего заказов</h6>
                                <h3 class="fw-bold mb-0"><?= $total_orders ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat bg-white shadow-sm p-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 me-3"><i class="bi bi-currency-dollar fs-3"></i></div>
                            <div>
                                <h6 class="text-muted mb-1 small">Выручка сегодня</h6>
                                <h3 class="fw-bold mb-0 text-nowrap"><?= number_format($today_revenue, 0, '.', ' ') ?> ₽</h3>
                                <div class="text-muted small mt-1">Всего: <?= number_format($total_revenue, 0, '.', ' ') ?> ₽</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat bg-white shadow-sm p-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 text-info p-3 rounded-3 me-3"><i class="bi bi-laptop fs-3"></i></div>
                            <div>
                                <h6 class="text-muted mb-1 small">Товаров</h6>
                                <h3 class="fw-bold mb-0"><?= $total_products ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat bg-white shadow-sm p-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3"><i class="bi bi-people fs-3"></i></div>
                            <div>
                                <h6 class="text-muted mb-1 small">Пользователей</h6>
                                <h3 class="fw-bold mb-0"><?= $total_users ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Последние заказы</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 border-0">ID</th>
                                <th class="border-0">Клиент</th>
                                <th class="border-0">Email</th>
                                <th class="border-0">Сумма</th>
                                <th class="border-0">Статус</th>
                                <th class="border-0">Дата</th>
                                <th class="pe-4 border-0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($orders && mysqli_num_rows($orders) > 0): while($o = mysqli_fetch_assoc($orders)): ?>
                                <tr>
                                    <td class="ps-4">#<?= $o['id'] ?></td>
                                    <td class="fw-bold"><?= h($o['customer_name'] ?? ($o['user_name'] ?? 'Гость')) ?></td>
                                    <td class="text-muted small"><?= h($o['customer_email'] ?? ($o['email'] ?? 'Не указано')) ?></td>
                                    <td class="text-primary fw-bold">
                                        <?= isset($o['total_price']) ? number_format($o['total_price'], 0, '.', ' ') . ' ₽' : '0 ₽' ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = 'bg-warning';
                                        $status_text = 'Ожидает';
                                        $status = $o['status'] ?? 'pending';
                                        if($status == 'completed') { $status_class = 'bg-success'; $status_text = 'Завершен'; }
                                        if($status == 'cancelled') { $status_class = 'bg-danger'; $status_text = 'Отменен'; }
                                        ?>
                                        <span class="badge <?= $status_class ?> rounded-pill px-3"><?= $status_text ?></span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= (isset($o['created_at']) && $o['created_at']) ? date('d.m.Y H:i', strtotime($o['created_at'])) : '01.01.1970' ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="orders.php" class="btn btn-light btn-sm rounded-3"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Заказов пока нет</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
