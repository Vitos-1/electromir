<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = isset($_GET['order_success']) ? 'Заказ успешно оформлен! Спасибо за покупку.' : '';
if (isset($_GET['deleted'])) $success = "Аккаунт успешно удален.";
$error = '';

// Обработка удаления аккаунта
if (isset($_POST['delete_account'])) {
    // Удаляем связанные данные (опционально, зависит от политики)
    mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");
    mysqli_query($conn, "DELETE FROM wishlist WHERE user_id = $user_id");
    
    if (mysqli_query($conn, "DELETE FROM users WHERE id = $user_id")) {
        session_destroy();
        header("Location: index.php?account_deleted=1");
        exit();
    } else {
        $error = "Ошибка при удалении аккаунта.";
    }
}

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Авто-фикс БД: проверяем наличие колонок phone и avatar
    $check_cols = mysqli_query($conn, "SHOW COLUMNS FROM users");
    $existing_cols = [];
    while($c = mysqli_fetch_assoc($check_cols)) $existing_cols[] = $c['Field'];
    
    if (!in_array('phone', $existing_cols)) mysqli_query($conn, "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email");
    if (!in_array('avatar', $existing_cols)) mysqli_query($conn, "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $avatar_path = null;

    // Загрузка аватарки
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatar_dir = 'uploads/avatars/';
        if (!is_dir($avatar_dir)) mkdir($avatar_dir, 0777, true);

        $file_tmp = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $avatar_dir . $new_file_name)) {
                $avatar_path = $new_file_name;
            }
        }
    }

    $sql = "UPDATE users SET name = ?, email = ?, phone = ?";
    $params = [$name, $email, $phone];
    $types = "sss";

    if ($avatar_path) {
        $sql .= ", avatar = ?";
        $params[] = $avatar_path;
        $types .= "s";
    }

    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
        $types .= "s";
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name;
        $success = "Профиль успешно обновлен!";
    } else {
        $error = "Ошибка при обновлении профиля.";
    }
}

// Получение данных пользователя
$user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// Получение истории заказов
$orders = false;
$check_orders = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if (mysqli_num_rows($check_orders) > 0) {
    $check_uid = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'user_id'");
    if (mysqli_num_rows($check_uid) > 0) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $orders = $stmt->get_result();
    }
}

include 'includes/header.php';
?>

<div class="row g-4">
    <div class="col-md-4">
        <!-- Редактирование профиля -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="position-relative mx-auto mb-3" style="width: 100px; height: 100px;">
                <?php if(!empty($user_data['avatar'])): ?>
                    <img src="uploads/avatars/<?= h($user_data['avatar']) ?>" class="rounded-circle shadow-sm w-100 h-100 object-fit-cover" alt="Avatar">
                <?php else: ?>
                    <div class="bg-primary text-dark rounded-circle d-flex align-items-center justify-content-center w-100 h-100" style="font-size: 2rem; font-weight: 800;">
                        <?= mb_substr($user_data['name'], 0, 1) ?>
                    </div>
                <?php endif; ?>
                
                <?php if($user_data['is_verified']): ?>
                    <span class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center border border-white" style="width: 25px; height: 25px; font-size: 0.8rem;" title="Почта подтверждена">
                        <i class="bi bi-patch-check-fill"></i>
                    </span>
                <?php endif; ?>
            </div>
            
            <h5 class="fw-bold text-center mb-1"><?= h($user_data['name']) ?></h5>
            <p class="text-center text-muted small mb-4">
                <?= h($user_data['email']) ?>
                <?php if(!$user_data['is_verified']): ?>
                    <br><a href="verify.php?email=<?= urlencode($user_data['email']) ?>" class="text-warning text-decoration-none fw-bold small">Подтвердить почту</a>
                <?php endif; ?>
            </p>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Сменить аватар</label>
                    <input type="file" name="avatar" class="form-control form-control-sm rounded-3 bg-light border-0">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Имя</label>
                    <input type="text" name="name" class="form-control rounded-3" value="<?= h($user_data['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Email</label>
                    <input type="email" name="email" class="form-control rounded-3" value="<?= h($user_data['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Телефон</label>
                    <input type="text" name="phone" class="form-control rounded-3" placeholder="+7 (___) ___-__-__" value="<?= h($user_data['phone'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Новый пароль</label>
                    <input type="password" name="password" class="form-control rounded-3" placeholder="********">
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3">Сохранить изменения</button>
            </form>
            
            <hr class="my-4">
            <div class="text-center">
                <p class="small mb-2 text-muted"><i class="bi bi-calendar-event me-2"></i>На сайте с: <?= date('d.m.Y', strtotime($user_data['created_at'] ?? date('Y-m-d'))) ?></p>
                <div class="d-flex gap-2">
                    <a href="logout.php" class="btn btn-outline-secondary btn-sm flex-grow-1 rounded-3 fw-bold">Выйти</a>
                    <form method="POST" onsubmit="return confirm('Вы уверены? Все ваши данные будут удалены навсегда!')" class="flex-grow-1">
                        <button type="submit" name="delete_account" class="btn btn-outline-danger btn-sm w-100 rounded-3 fw-bold">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <h3 class="fw-bold mb-4">История заказов</h3>
        
        <?php if($success): ?>
            <div class="alert alert-success rounded-3 mb-4 shadow-sm">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if($orders && mysqli_num_rows($orders) > 0): ?>
            <div class="d-flex flex-column gap-3">
                <?php while($order = mysqli_fetch_assoc($orders)): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold">Заказ #<?= $order['id'] ?></span>
                                <span class="text-muted small ms-3"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span>
                            </div>
                            <?php 
                                $status_map = [
                                    'pending' => ['label' => 'Ожидает', 'bg' => 'warning'],
                                    'processing' => ['label' => 'В обработке', 'bg' => 'primary'],
                                    'shipped' => ['label' => 'Отправлен', 'bg' => 'info'],
                                    'completed' => ['label' => 'Выполнен', 'bg' => 'success'],
                                    'cancelled' => ['label' => 'Отменен', 'bg' => 'danger']
                                ];
                                $s = $status_map[$order['status']] ?? ['label' => $order['status'], 'bg' => 'secondary'];
                            ?>
                            <span class="badge bg-<?= $s['bg'] ?> rounded-pill px-3">
                                <?= $s['label'] ?>
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <?php
                            $order_id = $order['id'];
                            // Используем LEFT JOIN и алиасы, чтобы избежать конфликтов имен колонок
                            $items_res = mysqli_query($conn, "SELECT oi.quantity, oi.price, p.name AS product_name, p.image 
                                                            FROM order_items oi 
                                                            LEFT JOIN products p ON oi.product_id = p.id 
                                                            WHERE oi.order_id = $order_id");
                            while($item = mysqli_fetch_assoc($items_res)): 
                                $p_name = $item['product_name'] ?? 'Товар удален';
                                $p_image = $item['image'] ?? '';
                            ?>
                                <div class="d-flex justify-content-between align-items-center small mb-3">
                                    <div class="d-flex align-items-center">
                                        <?php if($p_image): ?>
                                            <img src="uploads/products/<?= h($p_image) ?>" class="rounded-2 border me-2" style="width: 40px; height: 40px; object-fit: contain;">
                                        <?php else: ?>
                                            <div class="bg-light rounded-2 border me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-box text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?= h($p_name) ?></div>
                                            <div class="text-muted small">x<?= $item['quantity'] ?></div>
                                        </div>
                                    </div>
                                    <span class="fw-bold"><?= number_format($item['price'] * $item['quantity'], 0, '.', ' ') ?> ₽</span>
                                </div>
                            <?php endwhile; ?>
                            <hr>
                            <div class="row g-3 small mb-3">
                                <div class="col-6">
                                    <span class="text-muted d-block">Доставка:</span>
                                    <span class="fw-bold">
                                        <?php 
                                            if (isset($order['delivery_method'])) {
                                                echo $order['delivery_method'] === 'pickup' ? 'Самовывоз (ПВЗ Яндекс)' : 'Курьер';
                                            } else {
                                                // Если колонки нет, определяем по тексту адреса
                                                echo (strpos($order['customer_address'], 'ПВЗ:') === 0) ? 'Самовывоз (ПВЗ Яндекс)' : 'Курьер';
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Оплата:</span>
                                    <span class="fw-bold">
                                        <?php 
                                            if (isset($order['payment_method'])) {
                                                echo $order['payment_method'] === 'online' ? 'Картой онлайн' : 'При получении';
                                            } else {
                                                // Если колонки нет, пробуем найти инфу об оплате в адресе (если мы ее туда добавили) или дефолт
                                                echo (strpos($order['customer_address'], '[Оплата: онлайн]') !== false) ? 'Картой онлайн' : 'При получении';
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div class="col-12">
                                    <span class="text-muted d-block">Адрес / Пункт выдачи:</span>
                                    <span class="fw-bold">
                                        <?php 
                                            $display_addr = $order['customer_address'] ?? 'Не указан';
                                            // Убираем технические метки из адреса для красивого вывода
                                            $display_addr = str_replace(['ПВЗ: ', '[Оплата: онлайн]'], '', $display_addr);
                                            echo nl2br(h($display_addr));
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Итоговая сумма:</span>
                                <span class="h5 fw-bold text-primary mb-0 text-nowrap"><?= number_format($order['total_price'], 0, '.', ' ') ?> ₽</span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <i class="bi bi-box-seam text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Заказов пока нет</h5>
                <p class="text-muted small">История ваших покупок будет отображаться здесь.</p>
                <a href="index.php" class="btn btn-primary btn-sm fw-bold mt-2">Начать покупки</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
