<?php
require_once '../includes/config.php';

// Защита админки
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Удаление пользователя
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Нельзя удалить самого себя
    if ($id !== $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $id");
        header("Location: users.php?msg=deleted");
        exit();
    }
}

// Изменение роли
if (isset($_POST['change_role'])) {
    $id = (int)$_POST['user_id'];
    $new_role = $_POST['role'] === 'admin' ? 'admin' : 'user';
    mysqli_query($conn, "UPDATE users SET role = '$new_role' WHERE id = $id");
    header("Location: users.php?msg=updated");
    exit();
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями - ElectroStore</title>
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
                    <li class="nav-item"><a class="nav-link active" href="users.php"><i class="bi bi-people me-2"></i> Пользователи</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php"><i class="bi bi-house me-2"></i> На сайт</a></li>
                    <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Выход</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-auto p-4 p-md-5">
            <h2 class="fw-bold mb-4">Управление пользователями</h2>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
                    <?= $_GET['msg'] === 'deleted' ? 'Пользователь успешно удален' : 'Роль успешно обновлена' ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Имя</th>
                                <th>Email</th>
                                <th>Роль</th>
                                <th class="text-end pe-4">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td class="ps-4"><?= $user['id'] ?></td>
                                    <td><?= h($user['name']) ?></td>
                                    <td><?= h($user['email']) ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                            <?= h($user['role']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <form action="users.php" method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="role" value="<?= $user['role'] === 'admin' ? 'user' : 'admin' ?>">
                                                <button type="submit" name="change_role" class="btn btn-sm btn-outline-secondary rounded-pill">
                                                    Сделать <?= $user['role'] === 'admin' ? 'юзером' : 'админом' ?>
                                                </button>
                                            </form>
                                            <?php if($user['id'] !== $_SESSION['user_id']): ?>
                                                <a href="users.php?delete=<?= $user['id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger rounded-pill" 
                                                   onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
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
