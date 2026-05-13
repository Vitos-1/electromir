<?php
require_once 'includes/config.php';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

$cart_items = [];
$total_price = 0;
$ids = array_keys($_SESSION['cart']);
$ids_str = implode(',', $ids);
$query = "SELECT * FROM products WHERE id IN ($ids_str)";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $qty = $_SESSION['cart'][$row['id']];
    $row['quantity'] = $qty;
    $row['subtotal'] = $row['price'] * $qty;
    $total_price += $row['subtotal'];
    $cart_items[] = $row;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name']);
    $email = trim($_POST['customer_email']);
    $delivery_method = $_POST['delivery_method'] ?? 'courier';
    $payment_method = $_POST['payment_method'] ?? 'on_delivery';
    $address = ($delivery_method === 'courier') ? trim($_POST['customer_address']) : null;
    $pickup_point = ($delivery_method === 'pickup') ? trim($_POST['pickup_point_address']) : null;
    $user_id = $_SESSION['user_id'] ?? null;

    if (empty($name) || empty($email) || ($delivery_method === 'courier' && empty($address)) || ($delivery_method === 'pickup' && empty($pickup_point))) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } else {
        try {
            // Проверка и создание таблицы заказов если её нет
            mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT DEFAULT NULL,
                total_price DECIMAL(10,2) DEFAULT 0.00,
                customer_name VARCHAR(255) DEFAULT NULL,
                customer_email VARCHAR(255) DEFAULT NULL,
                customer_address TEXT DEFAULT NULL,
                delivery_method VARCHAR(50) DEFAULT 'courier',
                payment_method VARCHAR(50) DEFAULT 'on_delivery',
                pickup_point_address TEXT DEFAULT NULL,
                status ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Проверка и авто-фикс БД для таблицы заказов (если таблица уже была)
            $check_o = mysqli_query($conn, "SHOW COLUMNS FROM orders");
            $o_cols = [];
            while($c = mysqli_fetch_assoc($check_o)) $o_cols[] = $c['Field'];
            
            // Обеспечим наличие всех необходимых колонок
            if (!in_array('user_id', $o_cols)) mysqli_query($conn, "ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL AFTER id");
            if (in_array('customer_id', $o_cols)) mysqli_query($conn, "ALTER TABLE orders MODIFY COLUMN customer_id INT DEFAULT NULL");
            if (!in_array('customer_name', $o_cols)) mysqli_query($conn, "ALTER TABLE orders ADD COLUMN customer_name VARCHAR(255) DEFAULT NULL");
            if (!in_array('customer_email', $o_cols)) mysqli_query($conn, "ALTER TABLE orders ADD COLUMN customer_email VARCHAR(255) DEFAULT NULL");
            if (!in_array('customer_address', $o_cols)) mysqli_query($conn, "ALTER TABLE orders ADD COLUMN customer_address TEXT DEFAULT NULL");
            
            // Принудительно меняем ENUM на VARCHAR для избежания ошибок с усечением данных
            mysqli_query($conn, "ALTER TABLE orders MODIFY COLUMN delivery_method VARCHAR(50) DEFAULT 'courier'");
            mysqli_query($conn, "ALTER TABLE orders MODIFY COLUMN payment_method VARCHAR(50) DEFAULT 'on_delivery'");
            
            if (!in_array('pickup_point_address', $o_cols)) mysqli_query($conn, "ALTER TABLE orders ADD COLUMN pickup_point_address TEXT DEFAULT NULL");
            if (!in_array('status', $o_cols)) mysqli_query($conn, "ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'pending'");
            if (!in_array('created_at', $o_cols)) mysqli_query($conn, "ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

            // Проверка таблицы order_items
            $check_oi_table = mysqli_query($conn, "SHOW TABLES LIKE 'order_items'");
            if (mysqli_num_rows($check_oi_table) == 0) {
                mysqli_query($conn, "CREATE TABLE order_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    product_id INT NOT NULL,
                    quantity INT NOT NULL,
                    price DECIMAL(10,2) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } else {
                // Проверка колонок в order_items
                $check_oi_cols = mysqli_query($conn, "SHOW COLUMNS FROM order_items");
                $oi_cols = [];
                while($c = mysqli_fetch_assoc($check_oi_cols)) $oi_cols[] = $c['Field'];
                
                if (!in_array('price', $oi_cols)) {
                    mysqli_query($conn, "ALTER TABLE order_items ADD COLUMN price DECIMAL(10,2) NOT NULL AFTER quantity");
                }
                
                if (in_array('price_per_unit', $oi_cols)) {
                    mysqli_query($conn, "ALTER TABLE order_items MODIFY COLUMN price_per_unit DECIMAL(10,2) DEFAULT NULL");
                }

                // Универсальный фикс для любых других лишних колонок, которые могут мешать
                $required_cols = ['id', 'order_id', 'product_id', 'quantity', 'price'];
                foreach ($oi_cols as $col) {
                    if (!in_array($col, $required_cols)) {
                        mysqli_query($conn, "ALTER TABLE order_items MODIFY COLUMN `$col` TEXT DEFAULT NULL");
                    }
                }
            }

            // Начинаем транзакцию только для данных (INSERT/UPDATE)
            mysqli_begin_transaction($conn);

            // Вставка заказа
            $sql = "INSERT INTO orders (user_id, total_price, customer_name, customer_email, customer_address, delivery_method, payment_method, pickup_point_address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("idssssss", $user_id, $total_price, $name, $email, $address, $delivery_method, $payment_method, $pickup_point);
            
            if (!$stmt) {
                throw new Exception("Ошибка БД (prepare): " . $conn->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Ошибка БД (execute): " . $stmt->error);
            }
            
            $order_id = $conn->insert_id;

            // Добавление товаров в заказ и уменьшение остатков
            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($cart_items as $item) {
                $item_stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
                $item_stmt->execute();

                $update_stock->bind_param("ii", $item['quantity'], $item['id']);
                $update_stock->execute();
            }

            mysqli_commit($conn);
            unset($_SESSION['cart']);
            header("Location: profile.php?order_success=1");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = 'Ошибка при оформлении заказа: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <h1 class="fw-bold mb-4 text-center">Оформление заказа</h1>
        
        <?php if($error): ?>
            <div class="alert alert-danger rounded-3 mb-4"><?= $error ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <h5 class="fw-bold mb-4">Данные покупателя</h5>
                        <form id="checkoutForm" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">ФИО получателя</label>
                                <input type="text" name="customer_name" id="customer_name" class="form-control bg-light border-0 py-2" 
                                       value="<?= h($_SESSION['user_name'] ?? '') ?>" required placeholder="Иванов Иван Иванович">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Email</label>
                                <input type="email" name="customer_email" id="customer_email" class="form-control bg-light border-0 py-2" 
                                       value="<?= h($_SESSION['user_email'] ?? '') ?>" required placeholder="example@mail.ru">
                            </div>
                            <h5 class="fw-bold mb-4 mt-5">Способ доставки</h5>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="delivery_method" id="deliveryCourier" value="courier" checked>
                                <label class="form-check-label fw-bold" for="deliveryCourier">
                                    Доставка курьером
                                </label>
                            </div>
                            <div class="mb-3" id="addressBlock">
                                <label class="form-label small text-muted">Адрес доставки</label>
                                <textarea name="customer_address" id="customer_address" class="form-control bg-light border-0" rows="3" placeholder="Город, улица, дом, квартира"></textarea>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="delivery_method" id="deliveryPickup" value="pickup">
                                <label class="form-check-label fw-bold" for="deliveryPickup">
                                    Забрать в пункте выдачи Яндекс Маркета
                                </label>
                            </div>
                            <div id="pickupBlock" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Выберите пункт выдачи</label>
                                    <select name="pickup_point_address" id="pickupPointAddress" class="form-select bg-light border-0">
                                        <option value="">Выберите адрес ПВЗ...</option>
                                        <option value="ул. Пушкина, д. 10 (ПВЗ Яндекс Маркет)">ул. Пушкина, д. 10</option>
                                        <option value="пр. Ленина, д. 25 (ПВЗ Яндекс Маркет)">пр. Ленина, д. 25</option>
                                        <option value="ул. Гагарина, д. 5 (ПВЗ Яндекс Маркет)">ул. Гагарина, д. 5</option>
                                        <option value="ул. Мира, д. 12 (ПВЗ Яндекс Маркет)">ул. Мира, д. 12</option>
                                    </select>
                                </div>
                            </div>
                            
                            <h5 class="fw-bold mb-4 mt-5">Способ оплаты</h5>
                            <div class="card border-0 bg-light p-3 rounded-3 mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paymentOnline" value="online">
                                    <label class="form-check-label fw-bold" for="paymentOnline">
                                        <i class="bi bi-credit-card me-2"></i> Оплата сразу (картой онлайн)
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paymentOnDelivery" value="on_delivery" checked>
                                    <label class="form-check-label fw-bold" for="paymentOnDelivery">
                                        <i class="bi bi-cash-stack me-2"></i> При получении
                                    </label>
                                </div>
                            </div>
                            
                            <button type="button" id="confirmOrderBtn" class="btn btn-primary w-100 fw-bold py-3 rounded-3 shadow-sm mt-4">
                                Подтвердить заказ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 100px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Ваш заказ</h5>
                        <div class="d-flex flex-column gap-3 mb-4">
                            <?php foreach($cart_items as $item): ?>
                                <div class="d-flex align-items-center">
                                    <div class="position-relative">
                                        <img src="<?= $item['image'] ? 'uploads/products/'.h($item['image']) : 'https://via.placeholder.com/50x50' ?>" 
                                             class="rounded-2 border" style="width: 50px; height: 50px; object-fit: contain;">
                                        <span class="badge rounded-pill bg-secondary position-absolute top-0 start-100 translate-middle">
                                            <?= $item['quantity'] ?>
                                        </span>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <h6 class="small fw-bold mb-0 text-truncate" style="max-width: 150px;"><?= h($item['name']) ?></h6>
                                        <span class="text-muted small"><?= number_format($item['price'], 0, '.', ' ') ?> ₽</span>
                                    </div>
                                    <div class="fw-bold small text-primary">
                                        <?= number_format($item['subtotal'], 0, '.', ' ') ?> ₽
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Итого по товарам</span>
                            <span><?= number_format($total_price, 0, '.', ' ') ?> ₽</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-muted">Доставка</span>
                            <span class="text-success fw-bold">Бесплатно</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 fw-bold mb-0">К оплате</span>
                            <span class="h4 fw-bold text-primary mb-0 text-nowrap"><?= number_format($total_price, 0, '.', ' ') ?> ₽</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Модальное окно оплаты -->
<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Оплата заказа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="h3 fw-bold text-primary mb-1"><?= number_format($total_price, 0, '.', ' ') ?> ₽</div>
                    <div class="text-muted small">К оплате по заказу</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Номер карты</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-credit-card"></i></span>
                        <input type="text" class="form-control bg-light border-0" id="cardNum" placeholder="0000 0000 0000 0000">
                    </div>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Срок действия</label>
                        <input type="text" class="form-control bg-light border-0" id="cardExp" placeholder="ММ/ГГ">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">CVV/CVC</label>
                        <input type="password" class="form-control bg-light border-0" id="cardCvv" placeholder="***">
                    </div>
                </div>

                <button type="button" id="payBtn" class="btn btn-primary w-100 fw-bold py-3 rounded-3 mb-3">
                    <span id="payBtnText">Оплатить</span>
                    <div id="payBtnLoader" class="spinner-border spinner-border-sm d-none" role="status"></div>
                </button>
                
                <div class="text-center">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b7/MasterCard_Logo.svg" height="20" class="me-3">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" height="15" class="me-3">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Mir-logo.svg" height="15">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkoutForm');
    const confirmOrderBtn = document.getElementById('confirmOrderBtn');
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const payBtn = document.getElementById('payBtn');
    const payBtnText = document.getElementById('payBtnText');
    const payBtnLoader = document.getElementById('payBtnLoader');

    const deliveryCourier = document.getElementById('deliveryCourier');
    const deliveryPickup = document.getElementById('deliveryPickup');
    const addressBlock = document.getElementById('addressBlock');
    const pickupBlock = document.getElementById('pickupBlock');
    const addressTextarea = document.getElementById('customer_address');
    const pickupAddressSelect = document.getElementById('pickupPointAddress');

    function toggleDeliveryMethods() {
        if (deliveryCourier.checked) {
            addressBlock.style.display = 'block';
            pickupBlock.style.display = 'none';
            addressTextarea.required = true;
            pickupAddressSelect.required = false;
        } else {
            addressBlock.style.display = 'none';
            pickupBlock.style.display = 'block';
            addressTextarea.required = false;
            pickupAddressSelect.required = true;
        }
    }

    deliveryCourier.addEventListener('change', toggleDeliveryMethods);
    deliveryPickup.addEventListener('change', toggleDeliveryMethods);

    // Инициализация
    toggleDeliveryMethods();

    confirmOrderBtn.addEventListener('click', function() {
        // Базовая валидация формы
        if (!checkoutForm.checkValidity()) {
            checkoutForm.reportValidity();
            return;
        }

        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (paymentMethod === 'online') {
            paymentModal.show();
        } else {
            checkoutForm.submit();
        }
    });

    payBtn.addEventListener('click', function() {
        const cardNum = document.getElementById('cardNum').value;
        const cardExp = document.getElementById('cardExp').value;
        const cardCvv = document.getElementById('cardCvv').value;

        if (cardNum.length < 16 || cardExp.length < 5 || cardCvv.length < 3) {
            alert('Пожалуйста, введите корректные данные карты');
            return;
        }

        // Имитация процесса оплаты
        payBtn.disabled = true;
        payBtnText.classList.add('d-none');
        payBtnLoader.classList.remove('d-none');

        setTimeout(() => {
            payBtnLoader.classList.add('d-none');
            payBtnText.textContent = 'Оплачено!';
            payBtnText.classList.remove('d-none');
            payBtn.classList.remove('btn-primary');
            payBtn.classList.add('btn-success');

            setTimeout(() => {
                checkoutForm.submit();
            }, 1000);
        }, 2000);
    });

    // Маски для ввода карты (упрощенно)
    document.getElementById('cardNum').addEventListener('input', function (e) {
        e.target.value = e.target.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim().slice(0, 19);
    });
    document.getElementById('cardExp').addEventListener('input', function (e) {
        e.target.value = e.target.value.replace(/[^\d]/g, '').replace(/(.{2})/, '$1/').slice(0, 5);
    });
    document.getElementById('cardCvv').addEventListener('input', function (e) {
        e.target.value = e.target.value.replace(/[^\d]/g, '').slice(0, 3);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
