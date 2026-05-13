<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Если пользователь не залогинен, пытаемся найти его ID в сессии или используем null
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $session_id = session_id();
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['error' => 'Сообщение не может быть пустым']);
        exit;
    }

    // Проверка наличия таблицы и колонки session_id
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'support_tickets'");
    if (mysqli_num_rows($check_table) == 0) {
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS support_tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            session_id VARCHAR(255) DEFAULT NULL,
            message TEXT NOT NULL,
            reply TEXT DEFAULT NULL,
            status ENUM('open', 'closed') DEFAULT 'open',
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        // Проверяем наличие колонки session_id если таблица уже есть
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM support_tickets LIKE 'session_id'");
        if (mysqli_num_rows($check_col) == 0) {
            mysqli_query($conn, "ALTER TABLE support_tickets ADD COLUMN session_id VARCHAR(255) DEFAULT NULL AFTER user_id");
        }
        // Также проверим is_read
        $check_read = mysqli_query($conn, "SHOW COLUMNS FROM support_tickets LIKE 'is_read'");
        if (mysqli_num_rows($check_read) == 0) {
            mysqli_query($conn, "ALTER TABLE support_tickets ADD COLUMN is_read TINYINT(1) DEFAULT 0");
        }
    }

    try {
        $sql = "INSERT INTO support_tickets (user_id, session_id, message, status) VALUES (?, ?, ?, 'open')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $session_id, $message);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Сообщение отправлено!']);
        } else {
            echo json_encode(['error' => 'Ошибка выполнения: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Ошибка БД: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $session_id = session_id();
    
    $where = $user_id ? "user_id = $user_id" : "session_id = '$session_id'";

    // Если передан параметр mark_read, помечаем сообщения как прочитанные
    if (isset($_GET['mark_read'])) {
        mysqli_query($conn, "UPDATE support_tickets SET is_read = 1 WHERE ($where) AND reply IS NOT NULL");
    }

    // Получаем последние 10 сообщений
    $result = mysqli_query($conn, "SELECT * FROM support_tickets WHERE ($where) ORDER BY created_at DESC LIMIT 10");
    $messages = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
    }

    // Проверяем наличие непрочитанных ответов от админа
    $unread_count = 0;
    $unread_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM support_tickets WHERE ($where) AND reply IS NOT NULL AND is_read = 0");
    if ($unread_check) {
        $unread_count = mysqli_fetch_assoc($unread_check)['count'];
    }

    echo json_encode([
        'messages' => array_reverse($messages),
        'unread' => (int)$unread_count
    ]);
    exit;
}
?>