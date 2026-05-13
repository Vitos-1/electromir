<?php
require_once '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Авто-фикс БД: убираем строгое требование SKU и добавляем другие колонки если их нет
    $check_cols = mysqli_query($conn, "SHOW COLUMNS FROM products");
    $existing_cols = [];
    while($c = mysqli_fetch_assoc($check_cols)) $existing_cols[] = $c['Field'];
    
    // Если есть колонка sku, сделаем её необязательной (дадим дефолтное значение)
    if (in_array('sku', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE products MODIFY COLUMN sku VARCHAR(50) DEFAULT NULL");
    }
    // Проверим другие важные колонки
    if (!in_array('stock', $existing_cols)) mysqli_query($conn, "ALTER TABLE products ADD COLUMN stock INT DEFAULT 0 AFTER price");
    if (!in_array('specifications', $existing_cols)) mysqli_query($conn, "ALTER TABLE products ADD COLUMN specifications TEXT DEFAULT NULL AFTER description");
    if (!in_array('image', $existing_cols)) mysqli_query($conn, "ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER stock");

    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $description = trim($_POST['description']);
    $specifications = trim($_POST['specifications']);
    if (empty($specifications)) $specifications = null;
    
    $image_name = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Используем __DIR__ для надежного определения пути
        $upload_dir = __DIR__ . '/../uploads/products/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . "_" . uniqid() . "." . $ext;
        $target_path = $upload_dir . $image_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $error = 'Ошибка при сохранении файла. Путь: ' . $target_path . '. Убедитесь, что папка существует и доступна для записи.';
            $image_name = "";
        }
    }

    if (empty($name) || $price <= 0 || $category_id <= 0) {
        $error = 'Заполните обязательные поля корректно';
    }

    if (!$error) {
        // Проверяем наличие колонок перед вставкой
        $check_stock = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'stock'");
        $has_stock = mysqli_num_rows($check_stock) > 0;
        
        $check_specs = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'specifications'");
        $has_specs = mysqli_num_rows($check_specs) > 0;

        $check_image = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'image'");
        $has_image = mysqli_num_rows($check_image) > 0;

        $fields = ["name", "category_id", "description", "price", "created_at"];
        $placeholders = ["?", "?", "?", "?", "NOW()"];
        $types = "sisd";
        $params = [$name, $category_id, $description, $price];

        if ($has_image) {
            $fields[] = "image";
            $placeholders[] = "?";
            $types .= "s";
            $params[] = $image_name;
        }

        if ($has_stock) {
            $fields[] = "stock";
            $placeholders[] = "?";
            $types .= "i";
            $params[] = $stock;
        }
        if ($has_specs) {
            $fields[] = "specifications";
            $placeholders[] = "?";
            $types .= "s";
            $params[] = $specifications;
        }

        $sql = "INSERT INTO products (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            $error = "Ошибка подготовки запроса: " . $conn->error;
        } else {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                header("Location: products.php?added=1");
                exit();
            } else {
                $error = 'Ошибка при сохранении в базу данных: ' . $stmt->error;
            }
        }
    }
}

$categories = mysqli_query($conn, "SELECT * FROM categories");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить товар - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: #212529; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.7); border-radius: 8px; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 p-0 sidebar position-fixed d-none d-md-block">
            <div class="p-4">
                <h5 class="fw-bold text-primary mb-4">ADMIN PANEL</h5>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-2"></i> Дашборд</a></li>
                    <li class="nav-item"><a class="nav-link active" href="products.php"><i class="bi bi-box-seam me-2"></i> Товары</a></li>
                    <li class="nav-item"><a class="nav-link" href="categories.php"><i class="bi bi-tags me-2"></i> Категории</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php"><i class="bi bi-cart me-2"></i> Заказы</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people me-2"></i> Пользователи</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php"><i class="bi bi-house me-2"></i> На сайт</a></li>
                    <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Выход</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 ms-auto p-4 p-md-5">
            <div class="mb-4">
                <a href="products.php" class="text-decoration-none small fw-bold text-muted"><i class="bi bi-arrow-left me-1"></i>Назад к списку</a>
                <h2 class="fw-bold mt-2">Добавление товара</h2>
            </div>

            <?php if($error): ?><div class="alert alert-danger rounded-3 small"><?= $error ?></div><?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Название товара *</label>
                                    <input type="text" name="name" class="form-control bg-light border-0" required placeholder="Напр: iPhone 14 Pro Max">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Описание товара</label>
                                    <textarea name="description" class="form-control bg-light border-0" rows="4" placeholder="Полное описание характеристик и преимуществ..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Характеристики</label>
                                    <textarea name="specifications" class="form-control bg-light border-0" rows="8" placeholder="Используйте [Раздел] для заголовков.&#10;Пример:&#10;[Экран]&#10;Диагональ: 6.7\"&#10;Тип: Super Retina XDR&#10;&#10;[Процессор]&#10;Модель: Apple A17 Pro&#10;Ядер: 6"></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Категория *</label>
                                    <select name="category_id" class="form-select bg-light border-0" required>
                                        <option value="">Выберите категорию</option>
                                        <?php while($c = mysqli_fetch_assoc($categories)): ?>
                                            <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Цена (₽) *</label>
                                    <input type="number" name="price" step="0.01" class="form-control bg-light border-0" required placeholder="0.00">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Количество на складе *</label>
                                    <input type="number" name="stock" class="form-control bg-light border-0" required value="0">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Фото товара</label>
                                    <input type="file" name="image" class="form-control bg-light border-0" accept="image/*">
                                    <div class="form-text small">Рекомендуемый размер: 800x800 px</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 fw-bold py-3 rounded-3 shadow-sm">
                                    <i class="bi bi-check-lg me-2"></i>Сохранить товар
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
