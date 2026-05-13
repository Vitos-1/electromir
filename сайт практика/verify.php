<?php
require_once 'includes/config.php';

$error = '';
$success = '';
$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $code = trim($_POST['code']);

    if (empty($email) || empty($code)) {
        $error = 'Пожалуйста, введите код подтверждения';
    } else {
        // Авто-фикс БД при проверке
        $check_ver = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_verified'");
        if (mysqli_num_rows($check_ver) == 0) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN verification_code VARCHAR(10) DEFAULT NULL");
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?");
            $update->bind_param("i", $user['id']);
            if ($update->execute()) {
                header("Location: login.php?verified=1");
                exit();
            } else {
                $error = 'Ошибка при подтверждении. Попробуйте позже.';
            }
        } else {
            $error = 'Неверный код подтверждения или Email';
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold text-center mb-2">Подтверждение</h2>
                <p class="text-center text-muted small mb-4">Мы отправили код на ваш Email. Пожалуйста, введите его ниже.</p>
                
                <?php if($error): ?>
                    <div class="alert alert-danger rounded-3 small"><?= $error ?></div>
                <?php endif; ?>

                <form action="verify.php" method="POST">
                    <input type="hidden" name="email" value="<?= h($email) ?>">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-center d-block">Код подтверждения</label>
                        <input type="text" name="code" class="form-control bg-light border-0 text-center fs-4 fw-bold tracking-widest" required placeholder="000000" maxlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-3 rounded-3 shadow-sm mb-3">Подтвердить</button>
                    <p class="text-center text-muted small mb-0">Не получили код? <a href="register.php" class="text-decoration-none fw-bold">Начать заново</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.tracking-widest { letter-spacing: 0.5em; }
</style>

<?php include 'includes/footer.php'; ?>
