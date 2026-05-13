<?php
// Настройки подключения к базе данных
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '12345';
$db_name = 'electronics_store';

// Установка соединения с MySQL
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Включаем выброс исключений для mysqli (важно для транзакций)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Проверка соединения
if (!$conn) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
}

// Установка кодировки
mysqli_set_charset($conn, "utf8mb4");

// Старт сессии для работы с корзиной и авторизацией
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Функция для безопасного вывода данных (защита от XSS)
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Константа базового URL (измените, если сайт в подпапке)
define('BASE_URL', 'http://localhost/electronics/');

// Настройки SMTP Gmail (необходимо для отправки кода подтверждения)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465); // или 587 для TLS
define('SMTP_USER', 'your_gmail@gmail.com'); // ЗАМЕНИТЕ на ваш Gmail
define('SMTP_PASS', 'your_app_password');    // ЗАМЕНИТЕ на "пароль приложения" Gmail
define('SMTP_FROM', 'your_gmail@gmail.com');
define('SMTP_FROM_NAME', 'ELECTRO STORE');
?>
