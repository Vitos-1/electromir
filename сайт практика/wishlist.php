<?php
require_once 'includes/config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получение избранных товаров
$query = "SELECT p.*, c.name as category_name FROM products p 
          JOIN wishlist w ON p.id = w.product_id 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE w.user_id = $user_id
          ORDER BY w.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="fw-bold">Моё избранное</h1>
        <p class="text-muted">Сохранено товаров: <?= mysqli_num_rows($result) ?></p>
    </div>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
    <?php if(mysqli_num_rows($result) > 0): ?>
        <?php while($p = mysqli_fetch_assoc($result)): ?>
            <div class="col">
                <div class="card h-100 product-card shadow-sm border-0">
                    <div class="position-relative">
                        <?php 
                        $img_src = $p['image'];
                        if (filter_var($img_src, FILTER_VALIDATE_URL)) {
                            $display_img = $img_src;
                        } else {
                            $display_img = $p['image'] ? 'uploads/products/'.h($p['image']) : 'https://via.placeholder.com/300x200?text=ElectroStore';
                        }
                        ?>
                        <img src="<?= $display_img ?>" 
                             class="card-img-top p-3" alt="<?= h($p['name']) ?>" style="height: 220px; object-fit: contain;">
                        <span class="badge bg-primary position-absolute top-0 start-0 m-3"><?= h($p['category_name']) ?></span>
                        <!-- Кнопка удаления из избранного -->
                        <a href="toggle_wishlist.php?id=<?= $p['id'] ?>" class="btn btn-light rounded-circle position-absolute top-0 end-0 m-3 shadow-sm text-danger">
                            <i class="bi bi-heart-fill"></i>
                        </a>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold text-truncate" title="<?= h($p['name']) ?>"><?= h($p['name']) ?></h5>
                        <p class="card-text text-muted small flex-grow-1">
                            <?= mb_strimwidth(h($p['description']), 0, 70, "...") ?>
                        </p>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="h4 mb-0 text-primary fw-bold text-nowrap"><?= number_format($p['price'], 0, '.', ' ') ?> ₽</span>
                                <span class="text-muted small">В наличии: <?= $p['stock'] ?></span>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary border-2 fw-bold">Подробнее</a>
                                <form action="add_to_cart.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                                        <i class="bi bi-cart-plus me-2"></i>В корзину
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <i class="bi bi-heart fs-1 text-muted mb-3 d-block"></i>
            <h3>Список пуст</h3>
            <p class="text-muted">Вы пока не добавили ни одного товара в избранное.</p>
            <a href="index.php" class="btn btn-primary mt-3">Перейти в каталог</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
