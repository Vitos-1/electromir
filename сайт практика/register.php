<?php
require_once 'includes/config.php';
require_once 'includes/mail_helper.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный формат Email';
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Пароль слишком простой. Должно быть не менее 8 символов, включая заглавную букву, строчную букву и цифру.';
    } else {
        // Проверка наличия колонок верификации в БД (авто-фикс)
        $check_ver = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_verified'");
        if (mysqli_num_rows($check_ver) == 0) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN verification_code VARCHAR(10) DEFAULT NULL");
        }

        // Проверка, существует ли пользователь
        $stmt = mysqli_prepare($conn, "SELECT id, is_verified FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            if ($user['is_verified'] == 0) {
                // Если пользователь уже есть, но не подтвержден, перенаправляем на подтверждение
                header("Location: verify.php?email=" . urlencode($email));
                exit();
            }
            $error = 'Пользователь с таким Email уже зарегистрирован';
        } else {
            // Генерация кода подтверждения (6 цифр)
            $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Отправка EMAIL
            $email_sent = sendVerificationEmail($email, $verification_code);
            
            // Мы сохраняем код в базе, чтобы пользователь мог ввести его на следующей странице
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';
            $is_verified = 0;
            
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, is_verified, verification_code) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert_stmt, "ssssis", $name, $email, $hashed_password, $role, $is_verified, $verification_code);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                // Для учебных целей покажем код пользователю, если письмо не отправилось (на локальном сервере)
                if (!$email_sent) {
                    $_SESSION['last_verification_code'] = $verification_code;
                }
                header("Location: verify.php?email=" . urlencode($email));
                exit();
            } else {
                $error = 'Ошибка при регистрации. Попробуйте позже.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold text-center mb-4">Регистрация</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-danger rounded-3"><?= $error ?></div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ваше имя</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-person"></i></span>
                            <input type="text" name="name" class="form-control bg-light border-0" required placeholder="Иван Иванов">
                        </div>
                    </div>
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
                        <div class="form-text small mt-2">Минимум 8 символов: A-Z, a-z, 0-9.</div>
                    </div>
                    
                    <?php if(isset($_SESSION['last_verification_code'])): ?>
                    <div class="alert alert-info py-2 small mb-4">
                        <i class="bi bi-info-circle me-2"></i> Тестовый код подтверждения: <strong><?= $_SESSION['last_verification_code'] ?></strong>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-3 rounded-3 shadow-sm mb-3">Создать аккаунт</button>
                    <p class="text-center text-muted small mb-0">Уже есть аккаунт? <a href="login.php" class="text-decoration-none fw-bold">Войти</a></p>
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
