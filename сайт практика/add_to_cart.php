<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id > 0) {
        // Проверка наличия товара и колонки stock
        $check_stock = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'stock'");
        $has_stock_col = mysqli_num_rows($check_stock) > 0;
        
        $can_add = true;
        if ($has_stock_col) {
            $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if (!$res || $res['stock'] < $quantity) {
                $can_add = false;
            }
        }

        if ($can_add) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
        }
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit();
?>
