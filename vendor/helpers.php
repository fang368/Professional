<?php
require 'connect.php';
require 'vendor/autoload.php';
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getPDO(): PDO
{
    try {
        return new \PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8;dbname=' . DB_NAME, DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION] );
    } catch (\PDOException $e) {
        die("Connection error: {$e->getMessage()}");
    }
}

function redirect(string $path)
{
    header("Location: $path");
    die();
}

function getUserById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT full_name, login, email, phone, image FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Функция возвращает классы и стили кнопки для каждого заказа в зависимости от их статуса
function get_order_button_classes($orders) {
    $button_data = [];
    foreach ($orders as $order) {
        $status = $order['status'];
        switch ($status) {
            case 'В ожидании':
                $button_data[] = ['class' => 'btn_pending', 'style' => 'background: #0460B4;'];
                break;
            case 'Выполнен':
                $button_data[] = ['class' => 'btn_completed', 'style' => 'background: #20A200;'];
                break;
            case 'Отменен':
                $button_data[] = ['class' => 'btn_cancelled', 'style' => 'background: #ef3e3a;'];
                break;
            case 'Выполняется':
                $button_data[] = ['class' => 'btn_treatment', 'style' => 'background: #6A52FF;'];
                break;
            default:
                $button_data[] = ['class' => '', 'style' => '']; 
                break;
        }
    }
    return $button_data;
}

function currentUser()
{
    $pdo = getPDO();

    if (!isset($_SESSION['user'])) {
        return false;
    }

    $userId = $_SESSION['user']['id'] ?? null;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
}

// Функция для получения email пользователя по его ID
function getUserEmailById($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $user['email'];
}

// Функция для получения ID заказа по его номеру
function getOrderIdByNumber($pdo, $orderNumber) {
    $stmt = $pdo->prepare("SELECT order_id FROM Orders WHERE order_id = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $order['order_id'];
}

// Получение данных о заказе
function getOrderDetails($pdo, $orderId) {
    $stmt = $pdo->prepare("SELECT `order_id`, `form_type`, `status`, `order_date`, `name` FROM `Orders` WHERE `order_id` = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function formatDate($date)
{
    // Преобразуем строку даты в объект DateTime
    $dateTime = new DateTime($date);

    // Форматируем дату в день-месяц-год
    return $dateTime->format('d.m.Y');
}


function sendEmail($orderId, $userId, $pdo, $body, $subject, $formType, $status)
{
    $mail = new PHPMailer(true);

    try {
        // Настройки SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Адрес SMTP сервера
        $mail->SMTPAuth   = true;
        $mail->Username   = 'oleg.pytalev@gmail.com'; // Ваш адрес электронной почты
        $mail->Password   = 'bnfv pauq ompn jmnb'; // Пароль от почты
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Установка кодировки
        $mail->CharSet = 'UTF-8';

        // Отправитель
        $mail->setFrom('oleg.pytalev@gmail.com', 'Рекламное агентство "Профессионал"'); // Ваш адрес электронной почты и имя отправителя

        // Получатель
        $to = getUserEmailById($pdo, $userId);
        $mail->addAddress($to);

        // Тема письма
        $mail->Subject = $subject;

        // Тело письма
        $body .= "\n\nТип заказа: $formType\nНомер заказа: #$orderId\n\nПосмотреть все заказы: http://proffesional/orders.php";
        $mail->Body = $body;

        // Если статус заказа "Выполняется", добавляем PDF-вложение
        if ($status == 'Выполнен') {
            // Получаем детали заказа для PDF
            $orderDetails = getOrderDetails($pdo, $orderId);

            // Генерируем путь к временному файлу
            $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'order_details_' . $orderId . '.pdf';

            // Генерируем PDF
            generatePDF($orderDetails,  $orderId, $filePath);

            // Прикрепляем PDF к письму
            $mail->addAttachment($filePath);
        }

        // Отправка письма
        $mail->send();
        echo 'Письмо успешно отправлено';
    } catch (Exception $e) {
        echo "Ошибка при отправке письма: {$mail->ErrorInfo}";
    }
}

function getOrder($orderId) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM Orders WHERE order_id = :order_id");
    $stmt->execute(['order_id' => $orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function generatePDF($orderDetails, $orderId, $filePath)
{
    // Создаем новый PDF-документ
    $pdf = new TCPDF();

    // Устанавливаем свойства документа
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Рекламное агенство Проффесионал');
    $pdf->SetTitle('Order Receipt');
    $pdf->SetSubject('Order Receipt');
    $pdf->SetKeywords('TCPDF, PDF, order, receipt');

    // Устанавливаем отступы
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Устанавливаем шрифт, поддерживающий кириллицу
    $pdf->SetFont('dejavusans', '', 12);

    // Добавляем страницу
    $pdf->AddPage();

    // Генерируем случайную цену от 15000 до 150000 рублей
    $randomPrice = rand(15000, 150000);

    // Создаем HTML содержимое с форматированием
    $html = '
    <style>
        .receipt {
            font-family: DejaVu Sans, sans-serif;
            width: 100%;
            border: 1px solid #ddd;
            padding: 20px;
            margin: 0 auto;
        }
        .receipt h1 {
            text-align: center;
            font-size: 24px;
        }
        .receipt p {
            font-size: 16px;
        }
        .receipt .price {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
        }
    </style>
    <div class="receipt">
        <h1>Чек заказа</h1>
        <p>Номер заказа: ' . htmlspecialchars($orderDetails['order_id'], ENT_QUOTES, 'UTF-8') . '</p>
        <p>Тип заказа: ' . htmlspecialchars($orderDetails['form_type'], ENT_QUOTES, 'UTF-8') . '</p>
        <p>Дата заказа: ' . htmlspecialchars(formatDate($orderDetails['order_date']), ENT_QUOTES, 'UTF-8') . '</p>
        <p class="price">Цена: ' . number_format($randomPrice, 2, ',', ' ') . ' руб.</p>
    </div>';

    // Выводим HTML содержимое
    $pdf->writeHTML($html, true, false, true, false, '');

    // Сохраняем PDF в файл
    $pdf->Output($filePath, 'F');
}

// Функция для отправки email с токеном для восстановления пароля
function sendPasswordResetEmail($pdo, $email){
    $mail = new PHPMailer(true);

    try {
        // Настройки SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Адрес SMTP сервера
        $mail->SMTPAuth   = true;
        $mail->Username   = 'oleg.pytalev@gmail.com'; // Ваш email (отправитель)
        $mail->Password   = 'bnfv pauq ompn jmnb'; // Пароль от почты
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Установка кодировки
        $mail->CharSet = 'UTF-8';

        // Отправитель
        $mail->setFrom('oleg.pytalev@gmail.com', 'Рекламное агентство "Профессионал"'); // Ваш email и имя отправителя

        // Получатель
        $mail->addAddress($email);

        // Тема письма
        $mail->Subject = 'Восстановление пароля';

        // Генерация токена для восстановления пароля
        $token = bin2hex(random_bytes(32));

        // Сохранение токена в базе данных
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Дата и время истечения через 1 час
        $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expiresAt]);

        // Тело письма
        $reset_link = "http://proffesional/reset_password.php?token=" . $token;
        $mail->Body = "Для восстановления пароля перейдите по ссылке: <a href='$reset_link'>$reset_link</a>";
        $mail->AltBody = "Для восстановления пароля перейдите по ссылке:\n$reset_link"; // Вариант без HTML

        // Отправка письма
        $mail->isHTML(true); // Указываем, что письмо в формате HTML
        $mail->send();
        
        // Вернуть токен для дальнейшей обработки (например, сохранение в сессию или передача в форму сброса пароля)
        return $token;

    } catch (Exception $e) {
        echo "Ошибка при отправке письма: {$mail->ErrorInfo}";
        return null;
    }
}

function getFieldValueIfValid($errors, $fieldName) {
    return empty($errors[$fieldName]) && isset($_POST[$fieldName]) ? htmlspecialchars($_POST[$fieldName], ENT_QUOTES) : '';
}

function getFieldClass($errors, $fieldName) {
    return isset($errors[$fieldName]) ? 'error-border' : '';
}



?>

