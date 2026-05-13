<?php
require_once '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}

$error = '';
$success = '';

// Удаление отзыва
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM reviews WHERE id = $id");
    header("Location: reviews.php?deleted=1");
    exit();
}

// Получение списка отзывов с проверкой существования таблицы и колонок
$reviews = false;
$check_r = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
if (mysqli_num_rows($check_r) > 0) {
    // Проверка колонки created_at
    $check_ca = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'created_at'");
    $order_by = mysqli_num_rows($check_ca) > 0 ? "ORDER BY r.created_at DESC" : "ORDER BY r.id DESC";

    // Проверка колонки user_id
    $check_uid = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'user_id'");
    if (mysqli_num_rows($check_uid) > 0) {
        $reviews = mysqli_query($conn, "SELECT r.*, p.name as product_name, u.name as user_name 
                                        FROM reviews r 
                                        LEFT JOIN products p ON r.product_id = p.id 
                                        LEFT JOIN users u ON r.user_id = u.id 
                                        $order_by");
    } else {
        $reviews = mysqli_query($conn, "SELECT r.*, p.name as product_name, 'Гость' as user_name 
                                        FROM reviews r 
                                        LEFT JOIN products p ON r.product_id = p.id 
                                        $order_by");
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление отзывами - Admin</title>
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
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0 sidebar position-fixed d-none d-md-block">
            <div class="p-4">
                <h5 class="fw-bold text-primary mb-4">ADMIN PANEL</h5>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-2"></i> Дашборд</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-box-seam me-2"></i> Товары</a></li>
                    <li class="nav-item"><a class="nav-link" href="categories.php"><i class="bi bi-tags me-2"></i> Категории</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php"><i class="bi bi-cart me-2"></i> Заказы</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reviews.php"><i class="bi bi-star me-2"></i> Отзывы</a></li>
                    <li class="nav-item"><a class="nav-link" href="support.php"><i class="bi bi-chat-dots me-2"></i> Поддержка</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people me-2"></i> Пользователи</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php"><i class="bi bi-house me-2"></i> На сайт</a></li>
                    <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Выход</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-auto p-4 p-md-5">
            <h2 class="fw-bold mb-4">Управление отзывами</h2>

            <?php if(isset($_GET['deleted'])): ?>
                <div class="alert alert-success rounded-4 border-0 shadow-sm">Отзыв успешно удален!</div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Товар</th>
                                <th>Пользователь</th>
                                <th>Оценка</th>
                                <th>Комментарий</th>
                                <th>Дата</th>
                                <th class="pe-4 text-end">Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($reviews && mysqli_num_rows($reviews) > 0): ?>
                                <?php while($r = mysqli_fetch_assoc($reviews)): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold small"><?= h($r['product_name']) ?></td>
                                        <td><?= h($r['user_name']) ?></td>
                                        <td>
                                            <div class="text-warning small">
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                    <i class="bi bi-star<?= $i <= $r['rating'] ? '-fill' : '' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td class="small text-muted" style="max-width: 300px;"><?= h($r['comment']) ?></td>
                                        <td class="small">
                                            <?= (isset($r['created_at']) && $r['created_at']) ? date('d.m.Y H:i', strtotime($r['created_at'])) : '01.01.1970' ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="?delete=<?= $r['id'] ?>" class="btn btn-light btn-sm text-danger rounded-3" onclick="return confirm('Удалить этот отзыв?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Отзывов пока нет. Убедитесь, что запустили <a href="../fix_db.php">fix_db.php</a></td></tr>
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