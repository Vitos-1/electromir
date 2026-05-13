<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = isset($_GET['registered']) ? 'Регистрация прошла успешно! Код отправлен на вашу почту.' : '';
if (isset($_GET['verified'])) {
    $success = 'Почта успешно подтверждена! Теперь вы можете войти.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Введите Email и пароль';
    } else {
        // Авто-фикс БД при входе
        $check_ver = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_verified'");
        if (mysqli_num_rows($check_ver) == 0) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 1"); // Существующим ставим 1
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN verification_code VARCHAR(10) DEFAULT NULL");
        }

        $stmt = mysqli_prepare($conn, "SELECT id, name, password, role, is_verified FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Проверка верификации
            if ($user['is_verified'] == 0 && $email !== 'barinbruno@gmail.com') {
                header("Location: verify.php?email=" . urlencode($email));
                exit();
            }

            // ВРЕМЕННЫЙ ОБХОД ДЛЯ АДМИНИСТРАТОРА
            if ($email === 'barinbruno@gmail.com' && $password === '12345') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = 'admin'; // Принудительно ставим роль admin
                
                header("Location: index.php");
                exit();
            }

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: index.php");
                exit();
            } else {
                $error = 'Неверный пароль';
            }
        } else {
            $error = 'Пользователь не найден';
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold text-center mb-4">Вход</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-danger rounded-3 small"><?= $error ?></div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="alert alert-success rounded-3 small"><?= $success ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email адрес</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control bg-light border-0" required placeholder="example@mail.ru">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Пароль</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control bg-light border-0" required placeholder="••••••••">
                            <button class="btn btn-light border-0" type="button" onclick="togglePassword()">
                                <i class="bi bi-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-3 rounded-3 shadow-sm mb-3">Войти</button>
                    <p class="text-center text-muted small mb-0">Нет аккаунта? <a href="register.php" class="text-decoration-none fw-bold">Зарегистрироваться</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.classList.remove('bi-eye');
        passwordIcon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordIcon.classList.remove('bi-eye-slash');
        passwordIcon.classList.add('bi-eye');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
