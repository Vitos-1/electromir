<?php
require_once 'includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// Получение данных о товаре
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: index.php");
    exit();
}

// Обработка отзыва
$review_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $review_msg = '<div class="alert alert-warning">Войдите, чтобы оставить отзыв.</div>';
    } else {
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);
        $user_id = $_SESSION['user_id'];

        // Авто-фикс БД для отзывов
        $check_reviews_table = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
        if (mysqli_num_rows($check_reviews_table) == 0) {
            mysqli_query($conn, "CREATE TABLE reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                user_id INT NOT NULL,
                rating INT NOT NULL,
                comment TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $check_rev_cols = mysqli_query($conn, "SHOW COLUMNS FROM reviews");
            $rev_cols = [];
            while($c = mysqli_fetch_assoc($check_rev_cols)) $rev_cols[] = $c['Field'];
            if (!in_array('user_id', $rev_cols)) {
                mysqli_query($conn, "ALTER TABLE reviews ADD COLUMN user_id INT NOT NULL AFTER product_id");
            }
            if (in_array('customer_id', $rev_cols)) {
                mysqli_query($conn, "ALTER TABLE reviews MODIFY COLUMN customer_id INT DEFAULT NULL");
            }
        }

        $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $id, $user_id, $rating, $comment);
        if ($stmt->execute()) {
            $review_msg = '<div class="alert alert-success">Отзыв успешно добавлен!</div>';
        }
    }
}

// Получение отзывов
$reviews = [];
$check_reviews = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
if (mysqli_num_rows($check_reviews) > 0) {
    // Проверка колонки user_id
    $check_uid = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'user_id'");
    // Проверка колонки created_at
    $check_ca = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'created_at'");
    $order_by = mysqli_num_rows($check_ca) > 0 ? "ORDER BY r.created_at DESC" : "ORDER BY r.id DESC";

    if (mysqli_num_rows($check_uid) > 0) {
        $reviews_query = $conn->prepare("SELECT r.*, u.name as user_name FROM reviews r 
                                         LEFT JOIN users u ON r.user_id = u.id 
                                         WHERE r.product_id = ? $order_by");
        $reviews_query->bind_param("i", $id);
        $reviews_query->execute();
        $reviews = $reviews_query->get_result();
    } else {
        $reviews_query = $conn->prepare("SELECT r.*, 'Гость' as user_name FROM reviews r 
                                         WHERE r.product_id = ? $order_by");
        $reviews_query->bind_param("i", $id);
        $reviews_query->execute();
        $reviews = $reviews_query->get_result();
    }
}

include 'includes/header.php';
?>

<div class="row g-5">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
            <?php 
             $base_url = rtrim(BASE_URL, '/');
             
             if (!empty($product['image'])) {
                 if (filter_var($product['image'], FILTER_VALIDATE_URL)) {
                     $display_img = $product['image'];
                 } else {
                     // Убедимся, что путь корректный, удалив возможные лишние слеши
                     $clean_image_path = ltrim($product['image'], '/');
                     $display_img = $base_url . '/uploads/products/' . $clean_image_path;
                 }
             } else {
                 $display_img = 'https://via.placeholder.com/600x600?text=No+Image';
             }
             ?>
             <img src="<?= $display_img ?>" class="img-fluid rounded-4 shadow-sm" alt="<?= h($product['name']) ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="ps-md-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Каталог</a></li>
                    <li class="breadcrumb-item active"><?= h($product['category_name']) ?></li>
                </ol>
            </nav>
            <h1 class="fw-bold mb-3"><?= h($product['name']) ?></h1>
            
            <div class="d-flex align-items-center mb-4">
                <div class="text-warning me-2">
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-half"></i>
                </div>
                <span class="text-muted small">(<?= (is_object($reviews) ? mysqli_num_rows($reviews) : 0) ?> отзывов)</span>
            </div>

            <h2 class="text-primary fw-bold mb-4 text-nowrap"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</h2>
            
            <?php if(!empty($product['specifications'])): ?>
            <div class="card border-0 bg-light rounded-4 p-4 mb-4">
                <h6 class="fw-bold mb-3 text-uppercase small text-muted"><i class="bi bi-info-circle me-2"></i>Основные характеристики:</h6>
                <ul class="list-unstyled small mb-0">
                    <?php
                    $quick_specs = explode("\n", trim($product['specifications']));
                    $count = 0;
                    foreach ($quick_specs as $spec) {
                        $spec = trim($spec);
                        if (empty($spec) || strpos($spec, '[') === 0) continue; 
                        $parts = explode(':', $spec, 2);
                        if (count($parts) === 2) {
                            echo '<li class="mb-2 d-flex justify-content-between border-bottom border-white pb-1">';
                            echo '<span class="text-muted">' . h(trim($parts[0])) . ':</span>';
                            echo '<span class="fw-bold text-dark">' . h(trim($parts[1])) . '</span>';
                            echo '</li>';
                            if (++$count >= 6) break; 
                        }
                    }
                    ?>
                </ul>
                <div class="text-center mt-3">
                    <a href="#specs" class="text-decoration-none small fw-bold" onclick="document.getElementById('specs-tab').click()">Все характеристики <i class="bi bi-arrow-down-short"></i></a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Табы для описания и характеристик -->
            <ul class="nav nav-tabs border-0 mb-4" id="productTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold border-0 bg-transparent px-0 me-4" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc">Описание</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold border-0 bg-transparent px-0" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs">Характеристики</button>
                </li>
            </ul>
            <div class="tab-content" id="productTabContent">
                <div class="tab-pane fade show active" id="desc">
                    <p class="text-secondary"><?= nl2br(h($product['description'])) ?></p>
                </div>
                <div class="tab-pane fade" id="specs">
                    <div class="row">
                        <?php
                        if (!empty($product['specifications'])) {
                            $specs = explode("\n", trim($product['specifications']));
                            $current_category = "";
                            foreach ($specs as $spec) {
                                $spec = trim($spec);
                                if (empty($spec)) continue;

                                // Проверка на заголовок категории [Категория]
                                if (preg_match('/^\[(.*)\]$/', $spec, $matches)) {
                                    if ($current_category !== "") echo '</tbody></table></div>';
                                    $current_category = $matches[1];
                                    echo '<div class="col-12 mt-4 mb-2"><h5 class="fw-bold border-bottom pb-2 text-dark">' . h($current_category) . '</h5></div>';
                                    echo '<div class="col-12"><table class="table table-sm table-borderless align-middle"><tbody>';
                                } else {
                                    $parts = explode(':', $spec, 2);
                                    if (count($parts) === 2) {
                                        if ($current_category === "") {
                                            echo '<div class="col-12"><table class="table table-sm table-borderless align-middle"><tbody>';
                                            $current_category = "Общие характеристики";
                                        }
                                        echo '<tr class="border-bottom border-light">';
                                        echo '<th scope="row" class="text-muted fw-normal py-2" style="width: 45%;">' . h(trim($parts[0])) . '</th>';
                                        echo '<td class="py-2 fw-medium">' . h(trim($parts[1])) . '</td>';
                                        echo '</tr>';
                                    }
                                }
                            }
                            if ($current_category !== "") echo '</tbody></table></div>';
                        } else {
                            echo '<div class="col-12 text-center py-5"><p class="text-muted">Характеристики для этого товара еще не заполнены.</p></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="bg-light p-3 rounded-3 mb-4 mt-4">
                <div class="row align-items-center">
                    <div class="col-6">
                        <span class="text-muted small d-block">В наличии</span>
                        <span class="fw-bold"><?= $product['stock'] ?> шт.</span>
                    </div>
                    <div class="col-6 text-end">
                        <span class="badge bg-success">Официальная гарантия</span>
                    </div>
                </div>
            </div>

            <form action="add_to_cart.php" method="POST" class="d-flex gap-3">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="form-control" style="width: 80px;">
                <button type="submit" class="btn btn-primary flex-grow-1 fw-bold py-3">
                    <i class="bi bi-cart-plus me-2"></i>Добавить в корзину
                </button>
            </form>
        </div>
    </div>
</div>

<hr class="my-5">

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h3 class="fw-bold mb-4">Отзывы покупателей</h3>
        
        <?= $review_msg ?>

        <!-- Форма отзыва -->
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-5">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Оставить отзыв</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Оценка</label>
                            <select name="rating" class="form-select border-0 bg-light" required>
                                <option value="5">5 звезд - Отлично</option>
                                <option value="4">4 звезды - Хорошо</option>
                                <option value="3">3 звезды - Нормально</option>
                                <option value="2">2 звезды - Плохо</option>
                                <option value="1">1 звезда - Ужасно</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Ваш комментарий</label>
                            <textarea name="comment" class="form-control border-0 bg-light" rows="3" required placeholder="Поделитесь впечатлениями от покупки..."></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary fw-bold">Отправить отзыв</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-light border text-center py-4 rounded-4 mb-5">
                <p class="mb-2">Хотите оставить отзыв?</p>
                <a href="login.php" class="btn btn-outline-primary btn-sm fw-bold">Войдите в аккаунт</a>
            </div>
        <?php endif; ?>

        <!-- Список отзывов -->
        <?php if(is_object($reviews) && mysqli_num_rows($reviews) > 0): ?>
            <div class="d-flex flex-column gap-4">
                <?php while($r = mysqli_fetch_assoc($reviews)): ?>
                    <div class="d-flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <?= mb_substr($r['user_name'], 0, 1) ?>
                            </div>
                        </div>
                        <div class="flex-grow-1 border-bottom pb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold mb-0"><?= h($r['user_name']) ?></h6>
                                <span class="text-muted small"><?= date('d.m.Y', strtotime($r['created_at'])) ?></span>
                            </div>
                            <div class="text-warning small mb-2">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= $r['rating'] ? '-fill' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-muted mb-0 small"><?= nl2br(h($r['comment'])) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-muted text-center py-4">Пока нет отзывов. Станьте первым!</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
