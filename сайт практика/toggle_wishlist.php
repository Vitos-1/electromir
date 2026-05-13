<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    // Проверяем, есть ли уже в избранном
    $stmt = mysqli_prepare($conn, "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) > 0) {
        // Удаляем
        $delete = mysqli_prepare($conn, "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($delete, "ii", $user_id, $product_id);
        mysqli_stmt_execute($delete);
    } else {
        // Добавляем
        $insert = mysqli_prepare($conn, "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($insert, "ii", $user_id, $product_id);
        mysqli_stmt_execute($insert);
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit();
