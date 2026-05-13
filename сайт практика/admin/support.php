<?php
require_once '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}

$success = '';

// Обработка ответа на тикет
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $reply = trim($_POST['reply']);
    
    $stmt = $conn->prepare("UPDATE support_tickets SET reply = ?, status = 'closed' WHERE id = ?");
    $stmt->bind_param("si", $reply, $ticket_id);
    if ($stmt->execute()) {
        $success = "Ответ отправлен!";
    }
}

// Обработка очистки истории
if (isset($_POST['clear_history'])) {
    mysqli_query($conn, "DELETE FROM support_tickets");
    $success = "История обращений полностью очищена!";
}

// Получение тикетов с проверкой существования таблицы
$tickets = false;
$check_t = mysqli_query($conn, "SHOW TABLES LIKE 'support_tickets'");
if (mysqli_num_rows($check_t) > 0) {
    $tickets = mysqli_query($conn, "SELECT t.*, u.name as user_name 
                                   FROM support_tickets t 
                                   LEFT JOIN users u ON t.user_id = u.id 
                                   ORDER BY t.created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Поддержка - Admin</title>
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
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0 sidebar position-fixed d-none d-md-block">
            <div class="p-4">
                <h5 class="fw-bold text-primary mb-4">ADMIN PANEL</h5>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-2"></i> Дашборд</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-box-seam me-2"></i> Товары</a></li>
                    <li class="nav-item"><a class="nav-link" href="categories.php"><i class="bi bi-tags me-2"></i> Категории</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php"><i class="bi bi-cart me-2"></i> Заказы</a></li>
                    <li class="nav-item"><a class="nav-link" href="reviews.php"><i class="bi bi-star me-2"></i> Отзывы</a></li>
                    <li class="nav-item"><a class="nav-link active" href="support.php"><i class="bi bi-chat-dots me-2"></i> Поддержка</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people me-2"></i> Пользователи</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php"><i class="bi bi-house me-2"></i> На сайт</a></li>
                    <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Выход</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 ms-auto p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Обращения в поддержку</h2>
                <form method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить ВСЕ обращения?')">
                    <button type="submit" name="clear_history" class="btn btn-outline-danger rounded-pill px-4 fw-bold">
                        <i class="bi bi-trash me-2"></i> Очистить историю
                    </button>
                </form>
            </div>

            <?php if($success): ?>
                <div class="alert alert-success rounded-4 shadow-sm border-0"><?= $success ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <?php if($tickets && mysqli_num_rows($tickets) > 0): ?>
                    <?php while($t = mysqli_fetch_assoc($tickets)): ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1">Пользователь: <?= h($t['user_name'] ?? 'Гость (' . substr($t['session_id'], 0, 8) . '...)') ?></h6>
                                            <span class="text-muted small"><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></span>
                                        </div>
                                        <span class="badge rounded-pill px-3 <?= $t['status'] === 'open' ? 'bg-warning' : 'bg-success' ?>">
                                            <?= $t['status'] === 'open' ? 'Открыт' : 'Закрыт' ?>
                                        </span>
                                    </div>
                                    <div class="bg-light p-3 rounded-4 mb-4 small">
                                        <strong>Вопрос:</strong><br>
                                        <?= nl2br(h($t['message'])) ?>
                                    </div>
                                    
                                    <?php if($t['reply']): ?>
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-4 mb-0 small">
                                            <strong class="text-primary">Ваш ответ:</strong><br>
                                            <?= nl2br(h($t['reply'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                            <div class="mb-3">
                                                <textarea name="reply" class="form-control rounded-4 border-0 bg-light" rows="3" placeholder="Напишите ответ..." required></textarea>
                                            </div>
                                            <button type="submit" name="send_reply" class="btn btn-primary fw-bold rounded-pill px-4">Отправить ответ</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">Обращений пока нет. Пожалуйста, убедитесь, что вы запустили <a href="../fix_db.php">fix_db.php</a>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>