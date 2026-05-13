<?php
require_once '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Добавление категории
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO categories (name) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        header("Location: categories.php?msg=added");
        exit();
    }
}

// Удаление категории
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Проверяем, есть ли товары в этой категории
    $check = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    mysqli_stmt_bind_param($check, "i", $id);
    mysqli_stmt_execute($check);
    $res = mysqli_stmt_get_result($check);
    $count = mysqli_fetch_assoc($res)['count'];

    if ($count > 0) {
        header("Location: categories.php?err=has_products");
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        header("Location: categories.php?msg=deleted");
    }
    exit();
}

$categories = mysqli_query($conn, "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count FROM categories c ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление категориями - ElectroStore Admin</title>
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
                    <li class="nav-item"><a class="nav-link active" href="categories.php"><i class="bi bi-tags me-2"></i> Категории</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php"><i class="bi bi-cart me-2"></i> Заказы</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people me-2"></i> Пользователи</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php"><i class="bi bi-house me-2"></i> На сайт</a></li>
                    <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Выход</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 ms-auto p-4 p-md-5">
            <h2 class="fw-bold mb-4">Управление категориями</h2>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
                    <?= $_GET['msg'] == 'added' ? 'Категория успешно добавлена!' : 'Категория удалена!' ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['err'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
                    Нельзя удалить категорию, в которой есть товары!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Add Category Form -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold mb-3">Добавить новую</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">Название</label>
                                <input type="text" name="name" class="form-control rounded-3" placeholder="Напр: Смартфоны" required>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary w-100 fw-bold rounded-3">
                                <i class="bi bi-plus-lg me-2"></i>Добавить
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 border-0">ID</th>
                                        <th class="border-0">Название</th>
                                        <th class="border-0 text-center">Товаров</th>
                                        <th class="pe-4 border-0 text-end">Действие</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($c = mysqli_fetch_assoc($categories)): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small">#<?= $c['id'] ?></td>
                                            <td class="fw-bold"><?= h($c['name']) ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border rounded-pill px-3"><?= $c['product_count'] ?></span>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <a href="categories.php?delete=<?= $c['id'] ?>" 
                                                   class="btn btn-light btn-sm text-danger rounded-3"
                                                   onclick="return confirm('Вы уверены? Это действие нельзя отменить.')">
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>