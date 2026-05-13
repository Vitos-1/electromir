<?php
require_once '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Обновление статуса заказа
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    // Принудительно исправляем тип колонки status на VARCHAR, если это еще не сделано
    mysqli_query($conn, "ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
    
    $allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    if (in_array($status, $allowed_statuses)) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: orders.php?msg=updated&status_updated=1");
            exit();
        }
    }
}

// Получение списка заказов (с проверкой наличия колонок)
$orders_result = false;
$check_orders = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if (mysqli_num_rows($check_orders) > 0) {
    // Проверка наличия колонок
    $check_uid = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'user_id'");
    $has_uid = mysqli_num_rows($check_uid) > 0;
    
    $check_cn = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'customer_name'");
    $has_cn = mysqli_num_rows($check_cn) > 0;

    $check_ce = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'customer_email'");
    $has_ce = mysqli_num_rows($check_ce) > 0;

    $check_ca = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'created_at'");
    $order_by = mysqli_num_rows($check_ca) > 0 ? "ORDER BY created_at DESC" : "ORDER BY id DESC";

    if ($has_uid) {
        $orders_result = mysqli_query($conn, "SELECT o.*, u.name as user_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id $order_by");
    } elseif ($has_cn || $has_ce) {
        $select = "*";
        if ($has_cn) $select .= ", customer_name as user_name";
        else $select .= ", 'Гость' as user_name";
        if ($has_ce) $select .= ", customer_email as email";
        else $select .= ", 'Не указано' as email";
        $orders_result = mysqli_query($conn, "SELECT $select FROM orders $order_by");
    } else {
        $orders_result = mysqli_query($conn, "SELECT *, 'Гость' as user_name, 'Не указано' as email FROM orders $order_by");
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'completed': return 'bg-success';
        case 'shipped': return 'bg-info';
        case 'processing': return 'bg-primary';
        case 'cancelled': return 'bg-danger';
        case 'pending':
        default: return 'bg-warning';
    }
}

function getStatusLabel($status) {
    $labels = [
        'pending' => 'Ожидает',
        'processing' => 'В обработке',
        'shipped' => 'Отправлен',
        'completed' => 'Завершен',
        'cancelled' => 'Отменен'
    ];
    return $labels[$status] ?? ucfirst($status);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление заказами - ElectroStore Admin</title>
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
                    <li class="nav-item"><a class="nav-link active" href="orders.php"><i class="bi bi-cart me-2"></i> Заказы</a></li>
                    <li class="nav-item"><a class="nav-link" href="reviews.php"><i class="bi bi-star me-2"></i> Отзывы</a></li>
                    <li class="nav-item"><a class="nav-link" href="support.php"><i class="bi bi-chat-dots me-2"></i> Поддержка</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people me-2"></i> Пользователи</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php"><i class="bi bi-house me-2"></i> На сайт</a></li>
                    <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Выход</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 ms-auto p-4 p-md-5">
            <h2 class="fw-bold mb-4">Управление заказами</h2>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
                    Статус заказа успешно обновлен!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 border-0">ID</th>
                                <th class="border-0">Клиент</th>
                                <th class="border-0">Сумма</th>
                                <th class="border-0">Статус</th>
                                <th class="border-0">Дата</th>
                                <th class="pe-4 border-0 text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $modals = [];
                            if ($orders_result) {
                                while($o = mysqli_fetch_assoc($orders_result)):
                                    $modals[] = $o;
                            ?>
                                <tr>
                                    <td class="ps-4 text-muted small">#<?= $o['id'] ?></td>
                                    <td>
                                        <div class="fw-bold"><?= h($o['customer_name'] ?? ($o['user_name'] ?? 'Гость')) ?></div>
                                        <div class="small text-muted">Email: <?= h($o['customer_email'] ?? ($o['email'] ?? 'Не указано')) ?></div>
                                    </td>
                                    <td class="fw-bold text-primary"><?= isset($o['total_price']) ? number_format($o['total_price'], 0, '.', ' ') : '0' ?> ₽</td>
                                    <td>
                                        <form method="POST" class="d-flex">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <input type="hidden" name="update_status" value="1">
                                            <?php $status = $o['status'] ?? 'pending'; ?>
                                            <select name="status" class="form-select form-select-sm border-0 <?= getStatusBadgeClass($status) ?> text-white" onchange="this.form.submit()">
                                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Ожидает</option>
                                                <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>В обработке</option>
                                                <option value="shipped" <?= $status === 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Завершен</option>
                                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="small text-muted"><?= (isset($o['created_at']) && $o['created_at']) ? date('d.m.Y H:i', strtotime($o['created_at'])) : '01.01.1970' ?></td>
                                    <td class="pe-4 text-end">
                                        <button type="button" class="btn btn-light btn-sm text-primary rounded-3" 
                                                data-bs-toggle="modal" data-bs-target="#orderModal<?= $o['id'] ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            } else {
                                echo "<tr><td colspan='6' class='text-center p-5'>Заказов пока нет.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals outside the table -->
<?php foreach($modals as $o): ?>
    <div class="modal fade" id="orderModal<?= $o['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Заказ #<?= $o['id'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-muted small text-uppercase mb-3">Информация о заказе</h6>
                            <p class="mb-1"><strong>Имя:</strong> <?= h($o['customer_name'] ?? ($o['user_name'] ?? 'Не указано')) ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?= h($o['customer_email'] ?? ($o['email'] ?? 'Не указано')) ?></p>
                            <p class="mb-1"><strong>Доставка:</strong> <?= (isset($o['delivery_method']) && $o['delivery_method'] === 'pickup') ? 'Самовывоз (ПВЗ Яндекс)' : 'Курьер' ?></p>
                            <?php if(isset($o['delivery_method']) && $o['delivery_method'] === 'pickup'): ?>
                                <p class="mb-1"><strong>Пункт выдачи:</strong> <?= h($o['pickup_point_address'] ?? 'Не указан') ?></p>
                            <?php else: ?>
                                <p class="mb-1"><strong>Адрес:</strong> <?= nl2br(h($o['customer_address'] ?? 'Не указан')) ?></p>
                            <?php endif; ?>
                            <p class="mb-0"><strong>Оплата:</strong> <?= (isset($o['payment_method']) && $o['payment_method'] === 'online') ? 'Картой онлайн' : 'При получении' ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-muted small text-uppercase mb-3">Смена статуса</h6>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <div class="input-group">
                                    <select name="status" class="form-select rounded-start-3">
                                        <option value="pending" <?= $o['status'] == 'pending' ? 'selected' : '' ?>>Ожидает</option>
                                        <option value="processing" <?= $o['status'] == 'processing' ? 'selected' : '' ?>>В обработке</option>
                                        <option value="shipped" <?= $o['status'] == 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                                        <option value="completed" <?= $o['status'] == 'completed' ? 'selected' : '' ?>>Завершен</option>
                                        <option value="cancelled" <?= $o['status'] == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-primary rounded-end-3">Ок</button>
                                </div>
                            </form>
                        </div>
                        <div class="col-12">
                            <h6 class="fw-bold text-muted small text-uppercase mb-3">Товары в заказе</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th>Товар</th>
                                            <th class="text-center">Кол-во</th>
                                            <th class="text-end">Цена</th>
                                            <th class="text-end">Итого</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $items = mysqli_query($conn, "SELECT oi.*, p.name AS product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ".$o['id']);
                                        if ($items):
                                            while($item = mysqli_fetch_assoc($items)):
                                        ?>
                                            <tr>
                                                <td><?= h($item['product_name'] ?? 'Товар удален') ?></td>
                                                <td class="text-center"><?= $item['quantity'] ?></td>
                                                <td class="text-end"><?= number_format($item['price'], 0, '.', ' ') ?> ₽</td>
                                                <td class="text-end fw-bold"><?= number_format($item['price'] * $item['quantity'], 0, '.', ' ') ?> ₽</td>
                                            </tr>
                                        <?php 
                                            endwhile; 
                                        endif;
                                        ?>
                                    </tbody>
                                    <tfoot class="border-top">
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold py-3">Общая стоимость:</td>
                                            <td class="text-end fw-bold text-primary py-3 fs-5"><?= number_format($o['total_price'], 0, '.', ' ') ?> ₽</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>