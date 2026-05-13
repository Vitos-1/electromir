<?php
require_once '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$products = mysqli_query($conn, "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление товарами - ElectroStore Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: #212529; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.7); border-radius: 8px; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (Same as index.php) -->
        <div class="col-md-3 col-lg-2 p-0 sidebar position-fixed d-none d-md-block">
            <div class="p-4">
                <h5 class="fw-bold text-primary mb-4">ADMIN PANEL</h5>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-2"></i> Дашборд</a></li>
                    <li class="nav-item"><a class="nav-link active" href="products.php"><i class="bi bi-box-seam me-2"></i> Товары</a></li>
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

        <div class="col-md-9 col-lg-10 ms-auto p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Управление товарами</h2>
                <a href="product_add.php" class="btn btn-primary fw-bold shadow-sm rounded-3">
                    <i class="bi bi-plus-lg me-2"></i>Добавить товар
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 border-0">Фото</th>
                                <th class="border-0">Название</th>
                                <th class="border-0">Категория</th>
                                <th class="border-0">Цена</th>
                                <th class="border-0">Склад</th>
                                <th class="pe-4 border-0 text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($p = mysqli_fetch_assoc($products)): ?>
                                <tr>
                                    <td class="ps-4">
                                        <?php 
                                        $base_url = rtrim(BASE_URL, '/');
                                        $display_img = 'https://via.placeholder.com/50x50';
                                        if (!empty($p['image'])) {
                                            // Путь от корня сайта для file_exists
                                            $file_path = __DIR__ . '/../uploads/products/' . $p['image'];
                                            if (file_exists($file_path)) {
                                                $display_img = $base_url . '/uploads/products/' . h($p['image']);
                                            }
                                        }
                                        ?>
                                        <img src="<?= $display_img ?>" 
                                             class="rounded-3 border shadow-sm" style="width: 50px; height: 50px; object-fit: contain; background: white;">
                                    </td>
                                    <td>
                                        <h6 class="fw-bold mb-0"><?= h($p['name']) ?></h6>
                                        <span class="text-muted small">ID: <?= $p['id'] ?></span>
                                    </td>
                                    <td><span class="badge bg-light text-dark rounded-pill border"><?= h($p['category_name']) ?></span></td>
                                    <td class="fw-bold text-primary"><?= number_format($p['price'], 0, '.', ' ') ?> ₽</td>
                                    <td class="text-center">
                                        <?php if(isset($p['stock'])): ?>
                                            <span class="badge bg-<?= $p['stock'] > 10 ? 'success' : ($p['stock'] > 0 ? 'warning' : 'danger') ?> bg-opacity-10 text-<?= $p['stock'] > 10 ? 'success' : ($p['stock'] > 0 ? 'warning' : 'danger') ?> rounded-pill">
                                                <?= $p['stock'] ?> шт.
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="product_edit.php?id=<?= $p['id'] ?>" class="btn btn-light btn-sm text-primary rounded-3 me-2">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="product_delete.php?id=<?= $p['id'] ?>" class="btn btn-light btn-sm text-danger rounded-3" 
                                           onclick="return confirm('Вы уверены, что хотите удалить этот товар?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
