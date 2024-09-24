<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Валидация email
    if (empty($email)) {
        $errors['email'] = 'Введите ваш email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Неверный формат email';
    }

    if (empty($errors)) {
        $pdo = getPDO();

        // Проверка существования email в базе данных
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = sendPasswordResetEmail($pdo, $email);

            if ($token) {
                // Успешно отправлено письмо с инструкциями
                $success_message = "На ваш email отправлено письмо с инструкциями по восстановлению пароля.";
            } else {
                $errors['email'] = 'Ошибка при отправке email. Попробуйте еще раз.';
            }
        } else {
            $errors['email'] = 'Пользователь с таким email не найден.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/swiper-bundle.min.css">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/style.css">
    <title>Восстановление пароля</title>
</head>
<body>
    <section class="main-section">
        <div class="container main-section__nav-container">
            <header class="main-section__header">
                <a href="index.php"><img class="main-section__logo" src="/icons/Logo.svg" alt="Изображение логотипа"></a>
            </header>
        </div>
    
        <div class="container reset-password__container">
            <h1>Восстановление пароля</h1>
            
            <?php if (!empty($errors)) : ?>
                <div class="reset-password__errors">
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php elseif ($success_message) : ?>
                <div class="reset-password__success">
                    <p><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>

            <form class="reset-password__form" action="" method="post">
                <label for="email">Email:</label><br>
                <input type="email" id="email" name="email" placeholder="Введите свою почту"><br>
                <button type="submit" class="btn btn-primary" value="Отправить письмо">Отправить письмо</button>
            </form>
        </div>


<script src="js/bootstrap.bundle.js"></script>
<script src="js/swiper-bundle.min.js"></script>
<script src="/js/jquery-3.7.1.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
