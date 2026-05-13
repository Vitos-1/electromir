<?php
require_once '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Получаем информацию о товаре, чтобы удалить изображение
    $stmt = mysqli_prepare($conn, "SELECT image FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($res);

    if ($product) {
        // Удаляем файл изображения, если он существует
        if ($product['image'] && file_exists('../uploads/products/' . $product['image'])) {
            unlink('../uploads/products/' . $product['image']);
        }

        // Удаляем сам товар
        $delete_stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
        mysqli_stmt_bind_param($delete_stmt, "i", $id);
        mysqli_stmt_execute($delete_stmt);
    }
}

header("Location: products.php?msg=deleted");
exit();