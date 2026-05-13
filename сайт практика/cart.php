<?php
require_once 'includes/config.php';
include 'includes/header.php';

$cart_items = [];
$total_price = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
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
}
?>

<h1 class="fw-bold mb-4">Корзина</h1>

<?php if(empty($cart_items)): ?>
    <div class="card border-0 shadow-sm rounded-4 py-5 text-center">
        <div class="card-body">
            <i class="bi bi-basket3 text-muted" style="font-size: 4rem;"></i>
            <h3 class="mt-3">Ваша корзина пуста</h3>
            <p class="text-muted">Самое время добавить в нее что-нибудь интересное!</p>
            <a href="index.php" class="btn btn-primary fw-bold px-4 mt-2">Перейти к покупкам</a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3 border-0">Товар</th>
                                <th class="py-3 border-0 text-center">Кол-во</th>
                                <th class="py-3 border-0 text-center">Цена</th>
                                <th class="py-3 border-0 text-center">Итого</th>
                                <th class="pe-4 py-3 border-0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cart_items as $item): ?>
                                <tr>
                                    <td class="ps-4 py-4">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            $img_src = $item['image'];
                                            if (filter_var($img_src, FILTER_VALIDATE_URL)) {
                                                $display_img = $img_src;
                                            } else {
                                                $display_img = $item['image'] ? 'uploads/products/'.h($item['image']) : 'https://via.placeholder.com/100?text=Product';
                                            }
                                            ?>
                                            <img src="<?= $display_img ?>" 
                                                 class="rounded-3 me-3" style="width: 60px; height: 60px; object-fit: contain;">
                                            <div>
                                                <h6 class="fw-bold mb-0"><?= h($item['name']) ?></h6>
                                                <span class="text-muted small">ID: <?= $item['id'] ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold"><?= $item['quantity'] ?></span>
                                    </td>
                                    <td class="text-center"><?= number_format($item['price'], 0, '.', ' ') ?> ₽</td>
                                    <td class="text-center fw-bold text-primary"><?= number_format($item['subtotal'], 0, '.', ' ') ?> ₽</td>
                                    <td class="pe-4 text-end">
                                        <a href="remove_from_cart.php?id=<?= $item['id'] ?>" class="btn btn-light btn-sm text-danger rounded-3">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Детали заказа</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Товары (<?= array_sum($_SESSION['cart']) ?>)</span>
                        <span><?= number_format($total_price, 0, '.', ' ') ?> ₽</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="text-muted">Доставка</span>
                        <span class="text-success fw-bold">Бесплатно</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="h5 fw-bold">Итого</span>
                        <span class="h5 fw-bold text-primary text-nowrap"><?= number_format($total_price, 0, '.', ' ') ?> ₽</span>
                    </div>
                    <a href="checkout.php" class="btn btn-primary w-100 fw-bold py-3 rounded-3 shadow-sm">
                        Оформить заказ <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
