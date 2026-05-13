<?php
require_once 'includes/config.php';

// Отключаем строгий режим ошибок на время миграции
mysqli_report(MYSQLI_REPORT_OFF);

echo "<h2>Синхронизация базы данных...</h2>";

try {
    // 0. Создание таблиц если их нет
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        customer_name VARCHAR(255) DEFAULT NULL,
        customer_email VARCHAR(255) DEFAULT NULL,
        customer_address TEXT DEFAULT NULL,
        status ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
        total_price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 1. Проверка и добавление столбцов в таблицу users
    $cols_u = mysqli_query($conn, "SHOW COLUMNS FROM users");
    $existing_cols_u = [];
    while($row = mysqli_fetch_assoc($cols_u)) {
        $existing_cols_u[] = $row['Field'];
    }
    if (!in_array('phone', $existing_cols_u)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email");
        echo "✅ Столбец 'phone' добавлен в 'users'<br>";
    }
    if (!in_array('is_verified', $existing_cols_u)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
        echo "✅ Столбец 'is_verified' добавлен в 'users'<br>";
    }
    if (!in_array('verification_code', $existing_cols_u)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN verification_code VARCHAR(10) DEFAULT NULL");
        echo "✅ Столбец 'verification_code' добавлен в 'users'<br>";
    }
    if (!in_array('avatar', $existing_cols_u)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
        echo "✅ Столбец 'avatar' добавлен в 'users'<br>";
    }

    // Создание папки для аватарок
    $avatar_dir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($avatar_dir)) {
        mkdir($avatar_dir, 0777, true);
        echo "✅ Папка 'uploads/avatars' создана<br>";
    }

    // 2. Создание таблицы support_tickets для чата
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        message TEXT NOT NULL,
        reply TEXT DEFAULT NULL,
        status ENUM('open', 'closed') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ Таблица 'support_tickets' проверена/создана<br>";

    // Проверка и добавление столбцов в таблицу support_tickets
    $cols_t = mysqli_query($conn, "SHOW COLUMNS FROM support_tickets");
    $existing_cols_t = [];
    while($row = mysqli_fetch_assoc($cols_t)) {
        $existing_cols_t[] = $row['Field'];
    }
    if (!in_array('status', $existing_cols_t)) {
        mysqli_query($conn, "ALTER TABLE support_tickets ADD COLUMN status ENUM('open', 'closed') DEFAULT 'open'");
        echo "✅ Столбец 'status' добавлен в 'support_tickets'<br>";
    }
    if (!in_array('is_read', $existing_cols_t)) {
        mysqli_query($conn, "ALTER TABLE support_tickets ADD COLUMN is_read TINYINT(1) DEFAULT 0");
        echo "✅ Столбец 'is_read' добавлен в 'support_tickets'<br>";
    }
    if (!in_array('session_id', $existing_cols_t)) {
        mysqli_query($conn, "ALTER TABLE support_tickets ADD COLUMN session_id VARCHAR(255) DEFAULT NULL");
        echo "✅ Столбец 'session_id' добавлен в 'support_tickets'<br>";
    }

    // Проверка прав на папку uploads
    $uploads_base = __DIR__ . '/uploads/';
    if (!is_dir($uploads_base)) {
        mkdir($uploads_base, 0777, true);
    }
    chmod($uploads_base, 0777);
    chmod($upload_dir, 0777);
    echo "✅ Права на папку 'uploads' обновлены<br>";

    // 1. Проверка и добавление столбцов в таблицу products
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM products");
    $existing_cols = [];
    while($row = mysqli_fetch_assoc($cols)) {
        $existing_cols[] = $row['Field'];
    }

    if (!in_array('stock', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN stock INT DEFAULT 0 AFTER price");
        echo "✅ Столбец 'stock' добавлен в 'products'<br>";
    }
    if (!in_array('brand_id', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN brand_id INT DEFAULT NULL AFTER category_id");
        echo "✅ Столбец 'brand_id' добавлен в 'products'<br>";
    }
    if (!in_array('specifications', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN specifications TEXT DEFAULT NULL AFTER description");
        echo "✅ Столбец 'specifications' добавлен в 'products'<br>";
    } else {
        // На всякий случай принудительно меняем на TEXT, если он стал JSON
        mysqli_query($conn, "ALTER TABLE products MODIFY COLUMN specifications TEXT DEFAULT NULL");
        echo "✅ Столбец 'specifications' изменен на TEXT<br>";
    }
    if (!in_array('ram', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN ram VARCHAR(50) DEFAULT NULL AFTER specifications");
        echo "✅ Столбец 'ram' добавлен в 'products'<br>";
    }
    if (!in_array('storage', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN storage VARCHAR(50) DEFAULT NULL AFTER ram");
        echo "✅ Столбец 'storage' добавлен в 'products'<br>";
    }
    if (!in_array('image', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER stock");
        echo "✅ Столбец 'image' добавлен в 'products'<br>";
    }
    if (!in_array('created_at', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER image");
        echo "✅ Столбец 'created_at' добавлен в 'products'<br>";
    }

    // Проверка папки для загрузки
    $upload_dir = __DIR__ . '/uploads/products/';
    if (!is_dir($upload_dir)) {
        if (!is_dir(__DIR__ . '/uploads/')) {
            mkdir(__DIR__ . '/uploads/', 0777, true);
        }
        if (mkdir($upload_dir, 0777, true)) {
            echo "✅ Папка 'uploads/products' создана<br>";
        } else {
            echo "❌ Не удалось создать папку 'uploads/products'. Создайте её вручную в корне сайта.<br>";
        }
    } else {
        echo "✅ Папка 'uploads/products' уже существует<br>";
    }

    // 2. Проверка и добавление столбцов в таблицу orders
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM orders");
    $existing_cols = [];
    while($row = mysqli_fetch_assoc($cols)) {
        $existing_cols[] = $row['Field'];
    }

    if (!in_array('customer_name', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE orders ADD COLUMN customer_name VARCHAR(255) DEFAULT NULL AFTER user_id");
        echo "✅ Столбец 'customer_name' добавлен в 'orders'<br>";
    }
    if (!in_array('customer_email', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE orders ADD COLUMN customer_email VARCHAR(255) DEFAULT NULL AFTER customer_name");
        echo "✅ Столбец 'customer_email' добавлен в 'orders'<br>";
    }
    if (!in_array('total_price', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE orders ADD COLUMN total_price DECIMAL(10,2) DEFAULT 0.00 AFTER status");
        echo "✅ Столбец 'total_price' добавлен в 'orders'<br>";
    }
    if (!in_array('created_at', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER total_price");
        echo "✅ Столбец 'created_at' добавлен в 'orders'<br>";
    }
    if (!in_array('status', $existing_cols)) {
        mysqli_query($conn, "ALTER TABLE orders ADD COLUMN status ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending' AFTER total_price");
        echo "✅ Столбец 'status' добавлен в 'orders'<br>";
    }

    // 3. Создание таблицы brands если её нет
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS brands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        image VARCHAR(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ Таблица 'brands' проверена/создана<br>";

    // Добавление брендов
    $brands_to_add = ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'ASUS', 'Lenovo', 'HP', 'Sony', 'LG'];
    foreach ($brands_to_add as $brand_name) {
        $check_b = mysqli_query($conn, "SELECT id FROM brands WHERE name = '" . mysqli_real_escape_string($conn, $brand_name) . "'");
        if (mysqli_num_rows($check_b) == 0) {
            mysqli_query($conn, "INSERT INTO brands (name) VALUES ('" . mysqli_real_escape_string($conn, $brand_name) . "')");
            echo "✅ Бренд '$brand_name' добавлен<br>";
        }
    }

    // 4. Создание таблицы wishlist если её нет
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ Таблица 'wishlist' проверена/создана<br>";

    // 5. Создание таблицы reviews если её нет
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL DEFAULT 5,
        comment TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ Таблица 'reviews' проверена/создана<br>";

    // Проверка колонки created_at в reviews
    $cols_r = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'created_at'");
    if (mysqli_num_rows($cols_r) == 0) {
        mysqli_query($conn, "ALTER TABLE reviews ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "✅ Столбец 'created_at' добавлен в 'reviews'<br>";
    }

    // Добавление новых категорий
    $categories_to_add = [
        'Смартфоны', 'Ноутбуки', 'Аксессуары', 'Планшеты', 'Умные часы', 'Телевизоры', 'Аудио', 'Игровые консоли'
    ];
    foreach ($categories_to_add as $cat_name) {
        $check_cat = mysqli_query($conn, "SELECT id FROM categories WHERE name = '" . mysqli_real_escape_string($conn, $cat_name) . "'");
        if (mysqli_num_rows($check_cat) == 0) {
            mysqli_query($conn, "INSERT INTO categories (name) VALUES ('" . mysqli_real_escape_string($conn, $cat_name) . "')");
            echo "✅ Категория '$cat_name' добавлена<br>";
        }
    }

    // Проверка наличия колонки user_id в reviews (если таблица уже была)
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'user_id'");
    if (mysqli_num_rows($cols) == 0) {
        mysqli_query($conn, "ALTER TABLE reviews ADD COLUMN user_id INT NOT NULL AFTER product_id");
        echo "✅ Столбец 'user_id' добавлен в 'reviews'<br>";
    }

    echo "<br><strong>Все исправления применены! Попробуйте снова.</strong>";
    echo "<br><a href='index.php'>Вернуться на сайт</a>";

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>