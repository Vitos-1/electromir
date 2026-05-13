<?php
require_once 'includes/config.php';

include 'includes/header.php';

// Фильтрация по категории, поиску и новым фильтрам
$cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$brand_id = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$ram = isset($_GET['ram']) ? $_GET['ram'] : '';
$storage = isset($_GET['storage']) ? $_GET['storage'] : '';
$in_stock = isset($_GET['in_stock']) ? (int)$_GET['in_stock'] : 0;

$query = "SELECT p.*, c.name as category_name, b.name as brand_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN brands b ON p.brand_id = b.id
          WHERE 1=1";

if ($cat_id > 0) $query .= " AND p.category_id = $cat_id";
if ($brand_id > 0) $query .= " AND p.brand_id = $brand_id";
if ($min_price > 0) $query .= " AND p.price >= $min_price";
if ($max_price > 0) $query .= " AND p.price <= $max_price";
if ($in_stock > 0) $query .= " AND p.stock > 0";

// Проверка наличия колонок перед фильтрацией
$check_ram = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'ram'");
if (mysqli_num_rows($check_ram) > 0 && !empty($ram)) {
    $query .= " AND p.ram = '" . mysqli_real_escape_string($conn, $ram) . "'";
}

$check_storage = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'storage'");
if (mysqli_num_rows($check_storage) > 0 && !empty($storage)) {
    $query .= " AND p.storage = '" . mysqli_real_escape_string($conn, $storage) . "'";
}

if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $query .= " AND (p.name LIKE '%$search_safe%' OR p.description LIKE '%$search_safe%')";
}

$query .= " ORDER BY p.created_at DESC";

// Проверка наличия таблиц перед выполнением основного запроса
$check_brands = mysqli_query($conn, "SHOW TABLES LIKE 'brands'");
if (mysqli_num_rows($check_brands) == 0) {
    // Если таблицы брендов нет, упрощаем запрос
    $query = "SELECT p.*, c.name as category_name, 'Unknown' as brand_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE 1=1 ORDER BY p.created_at DESC";
}

$result = mysqli_query($conn, $query);

// Получаем бренды и категории для фильтров (с проверкой)
$all_brands = (mysqli_num_rows($check_brands) > 0) ? mysqli_query($conn, "SELECT * FROM brands ORDER BY name ASC") : false;
$all_categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");

// Проверка наличия колонок ram и storage перед запросом
$check_ram = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'ram'");
$all_rams = (mysqli_num_rows($check_ram) > 0) ? mysqli_query($conn, "SELECT DISTINCT ram FROM products WHERE ram IS NOT NULL AND ram != '' ORDER BY ram ASC") : false;

$check_storage = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'storage'");
$all_storages = (mysqli_num_rows($check_storage) > 0) ? mysqli_query($conn, "SELECT DISTINCT storage FROM products WHERE storage IS NOT NULL AND storage != '' ORDER BY storage ASC") : false;
?>

<!-- Динамический слайдер акций -->
<?php if(empty($search) && $cat_id == 0 && $brand_id == 0): ?>
<div id="promoCarousel" class="carousel slide mb-5 shadow-lg" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="2"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active" data-bs-interval="5000">
            <img src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?q=80&w=2000" class="d-block w-100" alt="Смартфоны">
            <div class="carousel-caption d-none d-md-block text-start">
                <h1 class="display-4 fw-bold">Мир смартфонов</h1>
                <p class="lead">Все новинки от Apple, Samsung и Xiaomi в одном месте.</p>
                <a href="index.php?cat=1" class="btn btn-primary btn-lg">Смотреть все</a>
            </div>
        </div>
        <div class="carousel-item" data-bs-interval="5000">
            <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?q=80&w=2000" class="d-block w-100" alt="Ноутбуки">
            <div class="carousel-caption d-none d-md-block text-start">
                <h1 class="display-4 fw-bold">Мощные ноутбуки</h1>
                <p class="lead">Для работы, учебы и гейминга. Скидки до 20%.</p>
                <a href="index.php?cat=2" class="btn btn-primary btn-lg">Выбрать ноутбук</a>
            </div>
        </div>
        <div class="carousel-item" data-bs-interval="5000">
            <img src="https://images.unsplash.com/photo-1605462863863-10d9e47e15ee?q=80&w=2000" class="d-block w-100" alt="Аксессуары">
            <div class="carousel-caption d-none d-md-block text-start">
                <h1 class="display-4 fw-bold">Аксессуары для гаджетов</h1>
                <p class="lead">Чехлы, наушники и зарядные устройства.</p>
                <a href="index.php?cat=3" class="btn btn-primary btn-lg">В каталог</a>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<!-- Блок преимуществ -->
<div class="row g-4 mb-5 text-center">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="display-5 text-primary mb-3"><i class="bi bi-truck"></i></div>
            <h5 class="fw-bold">Быстрая доставка</h5>
            <p class="text-muted small mb-0">Бесплатно при заказе от 50 000 ₽</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="display-5 text-primary mb-3"><i class="bi bi-shield-check"></i></div>
            <h5 class="fw-bold">Гарантия качества</h5>
            <p class="text-muted small mb-0">Только оригинальная продукция</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="display-5 text-primary mb-3"><i class="bi bi-arrow-repeat"></i></div>
            <h5 class="fw-bold">Обмен и возврат</h5>
            <p class="text-muted small mb-0">В течение 14 дней без лишних вопросов</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="display-5 text-primary mb-3"><i class="bi bi-headset"></i></div>
            <h5 class="fw-bold">Поддержка 24/7</h5>
            <p class="text-muted small mb-0">Всегда на связи с нашими клиентами</p>
        </div>
    </div>
</div>

<!-- Популярные категории -->
<div class="mb-5">
    <h3 class="fw-bold mb-4">Популярные категории</h3>
    <div class="row g-3">
        <?php 
        mysqli_data_seek($all_categories, 0);
        $count = 0;
        while($c = mysqli_fetch_assoc($all_categories)): 
            if($count++ >= 4) break;
        ?>
            <div class="col-md-3">
                <a href="index.php?cat=<?= $c['id'] ?>" class="card border-0 shadow-sm rounded-4 p-3 text-center text-decoration-none bg-white hover-lift">
                    <h6 class="fw-bold mb-0 text-dark"><?= h($c['name']) ?></h6>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Сайдбар с фильтрами -->
    <div class="col-lg-3">
        <div class="filter-sidebar shadow-sm mb-4">
            <h5 class="fw-bold mb-4">Фильтры</h5>
            <form action="index.php" method="GET">
                <input type="hidden" name="cat" value="<?= $cat_id ?>">
                <input type="hidden" name="search" value="<?= h($search) ?>">

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Цена</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="number" name="min_price" class="form-control form-control-sm rounded-3" placeholder="От" value="<?= $min_price > 0 ? $min_price : '' ?>">
                        <span class="text-muted">-</span>
                        <input type="number" name="max_price" class="form-control form-control-sm rounded-3" placeholder="До" value="<?= $max_price > 0 ? $max_price : '' ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Бренд</label>
                    <select name="brand" class="form-select form-select-sm rounded-3">
                        <option value="0">Все бренды</option>
                        <?php if($all_brands && mysqli_num_rows($all_brands) > 0): while($b = mysqli_fetch_assoc($all_brands)): ?>
                            <option value="<?= $b['id'] ?>" <?= $brand_id == $b['id'] ? 'selected' : '' ?>><?= h($b['name']) ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>

                <?php if($all_rams && mysqli_num_rows($all_rams) > 0): ?>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Оперативная память</label>
                    <select name="ram" class="form-select form-select-sm rounded-3">
                        <option value="">Любая</option>
                        <?php mysqli_data_seek($all_rams, 0); while($r = mysqli_fetch_assoc($all_rams)): ?>
                            <option value="<?= h($r['ram']) ?>" <?= $ram == $r['ram'] ? 'selected' : '' ?>><?= h($r['ram']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if($all_storages && mysqli_num_rows($all_storages) > 0): ?>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Встроенная память</label>
                    <select name="storage" class="form-select form-select-sm rounded-3">
                        <option value="">Любая</option>
                        <?php mysqli_data_seek($all_storages, 0); while($s = mysqli_fetch_assoc($all_storages)): ?>
                            <option value="<?= h($s['storage']) ?>" <?= $storage == $s['storage'] ? 'selected' : '' ?>><?= h($s['storage']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="inStockCheck" <?= $in_stock ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-bold text-muted text-uppercase" for="inStockCheck">
                            В наличии
                        </label>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary fw-bold">Применить</button>
                    <a href="index.php" class="btn btn-light btn-sm fw-bold">Сбросить</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="col-lg-9">
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold">
                    <?php 
                    if (!empty($search)) echo 'Результаты поиска: "' . h($search) . '"';
                    elseif ($cat_id > 0) {
                        mysqli_data_seek($all_categories, 0);
                        while($c = mysqli_fetch_assoc($all_categories)) {
                            if($c['id'] == $cat_id) { echo h($c['name']); break; }
                        }
                    }
                    else echo 'Каталог товаров';
                    ?>
                </h2>
                <p class="text-muted small">Найдено товаров: <?= mysqli_num_rows($result) ?></p>
            </div>
        </div>

        <div class="row row-cols-1 g-4">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($p = mysqli_fetch_assoc($result)): ?>
                    <div class="col">
                        <div class="card product-card h-100">
                            <div class="row g-0">
                                <div class="col-md-4 position-relative">
                                    <?php 
                                    $img_src = $p['image'];
                                    $base_url = rtrim(BASE_URL, '/');
                                    
                                    if (!empty($img_src)) {
                                        if (filter_var($img_src, FILTER_VALIDATE_URL)) {
                                            $display_img = $img_src;
                                        } else {
                                            $display_img = $base_url . '/uploads/products/' . h($img_src);
                                        }
                                    } else {
                                        $display_img = 'https://via.placeholder.com/300x300?text=ElectroStore';
                                    }
                                    ?>
                                    <img src="<?= $display_img ?>" class="img-fluid rounded-start p-4" alt="<?= h($p['name']) ?>" style="height: 250px; width: 100%; object-fit: contain;">
                                    <span class="badge bg-primary position-absolute top-0 start-0 m-3 rounded-pill"><?= h($p['category_name']) ?></span>
                                    
                                    <?php if(isset($_SESSION['user_id'])): 
                                        $pid = $p['id'];
                                        $uid = $_SESSION['user_id'];
                                        $check_w = mysqli_query($conn, "SHOW TABLES LIKE 'wishlist'");
                                        $in_wish = false;
                                        if(mysqli_num_rows($check_w) > 0) {
                                            $w_res = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = $uid AND product_id = $pid");
                                            if($w_res) $in_wish = mysqli_num_rows($w_res) > 0;
                                        }
                                    ?>
                                        <a href="toggle_wishlist.php?id=<?= $p['id'] ?>" class="btn btn-white rounded-circle position-absolute top-0 end-0 m-3 shadow-sm <?= $in_wish ? 'text-danger' : 'text-muted' ?>" style="background: #fff; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-heart<?= $in_wish ? '-fill' : '' ?>"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body h-100 d-flex flex-column p-4">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="card-title fw-bold mb-1"><?= h($p['name']) ?></h4>
                                                <div class="text-muted small mb-3"><?= h($p['brand_name'] ?? 'Бренд не указан') ?></div>
                                            </div>
                                            <div class="text-end ms-3" style="min-width: fit-content;">
                                                <div class="h3 fw-bold text-primary mb-0 text-nowrap"><?= number_format($p['price'], 0, '.', ' ') ?> ₽</div>
                                                <span class="text-success small text-nowrap"><i class="bi bi-check-circle me-1"></i>В наличии</span>
                                            </div>
                                        </div>
                                        
                                        <p class="card-text text-muted mb-4 flex-grow-1">
                                            <?= mb_strimwidth(strip_tags($p['description']), 0, 200, "...") ?>
                                        </p>

                                        <div class="d-flex gap-2">
                                            <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-light fw-bold rounded-3 px-4">Подробнее</a>
                                            <form action="add_to_cart.php" method="POST" class="flex-grow-1">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-primary w-100 fw-bold">
                                                    <i class="bi bi-cart-plus me-2"></i>Добавить в корзину
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                    <h3>Ничего не нашли</h3>
                    <p class="text-muted">Попробуйте изменить параметры фильтров.</p>
                    <a href="index.php" class="btn btn-primary mt-3">Сбросить всё</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
