<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$search_term = "%$query%";
$stmt = mysqli_prepare($conn, "SELECT id, name, price, image FROM products WHERE name LIKE ? LIMIT 5");
mysqli_stmt_bind_param($stmt, "s", $search_term);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$suggestions = [];
$base_url = rtrim(BASE_URL, '/');

while ($row = mysqli_fetch_assoc($result)) {
    $image = $row['image'];
    $display_img = '';
    
    if (!empty($image)) {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $display_img = $image;
        } else {
            $display_img = $base_url . '/uploads/products/' . $image;
        }
    } else {
        $display_img = 'https://via.placeholder.com/100x100?text=No+Image';
    }

    $suggestions[] = [
        'id' => $row['id'],
        'name' => h($row['name']),
        'price' => number_format($row['price'], 0, '.', ' ') . ' ₽',
        'image' => $display_img
    ];
}

echo json_encode($suggestions);
?>
