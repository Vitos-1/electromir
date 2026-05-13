<?php
/**
 * Вспомогательный файл для отправки писем
 * Для работы этого файла требуется библиотека PHPMailer.
 * Чтобы установить её:
 * 1. Скачайте PHPMailer с https://github.com/PHPMailer/PHPMailer
 * 2. Скопируйте папку src в папку includes/PHPMailer
 */

// Пытаемся подключить PHPMailer (если он есть)
$phpmailer_path = __DIR__ . '/PHPMailer/src/';
if (file_exists($phpmailer_path . 'PHPMailer.php')) {
    require_once $phpmailer_path . 'Exception.php';
    require_once $phpmailer_path . 'PHPMailer.php';
    require_once $phpmailer_path . 'SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Функция отправки кода подтверждения на почту
 * 
 * @param string $to Почта получателя
 * @param string $code 6-значный код подтверждения
 * @return bool Результат отправки
 */
function sendVerificationEmail($to, $code) {
    global $phpmailer_path;
    
    // Если PHPMailer установлен
    if (file_exists($phpmailer_path . 'PHPMailer.php')) {
        $mail = new PHPMailer(true);
        try {
            // Настройки сервера
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
            $mail->Port       = SMTP_PORT;

            // Получатели
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);

            // Содержимое
            $mail->isHTML(true);
            $mail->Subject = 'Код подтверждения регистрации - ELECTRO STORE';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                    <h2 style='color: #0d6efd; text-align: center;'>Добро пожаловать в ELECTRO STORE!</h2>
                    <p>Здравствуйте!</p>
                    <p>Вы начали регистрацию на нашем сайте. Для завершения подтвердите ваш Email адрес.</p>
                    <div style='background: #f8f9fa; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0;'>
                        <span style='font-size: 32px; font-weight: bold; letter-spacing: 10px; color: #212529;'>$code</span>
                    </div>
                    <p style='color: #6c757d; font-size: 14px;'>Если вы не регистрировались у нас, просто проигнорируйте это письмо.</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='text-align: center; color: #6c757d; font-size: 12px;'>&copy; " . date('Y') . " ELECTRO STORE. Все права защищены.</p>
                </div>
            ";

            return $mail->send();
        } catch (Exception $e) {
            // Логируем ошибку если нужно: $e->getMessage()
            error_log("PHPMailer Error: " . $e->getMessage());
            return false;
        }
    } else {
        // Запасной вариант: встроенная функция mail()
        // На локальных серверах часто не работает без доп. настройки!
        $subject = 'Код подтверждения регистрации - ELECTRO STORE';
        $message = "Ваш код подтверждения: $code";
        $headers = "From: " . SMTP_FROM . "\r\n" .
                   "Reply-To: " . SMTP_FROM . "\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        
        return @mail($to, $subject, $message, $headers);
    }
}
