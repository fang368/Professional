<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];


    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/';

    if (empty($password)) {
        $errors['password'] = "Поле Пароль должно быть заполнено";
    } elseif (!preg_match($password_pattern, $password)) {
        $errors['password'] = "Пароль должен содержать минимум 8 символов, хотя бы одну заглавную букву, одну строчную букву и одну цифру";
    }

    if (empty($confirm_password)) {
        $errors['password_confirm'] = "Поле Подтверждение пароля должно быть заполнено";
    }
    if ($password !== $confirm_password) {
        $errors['password_confirm'] = "Пароли не совпадают";
    }

    if (empty($errors)) {
        $pdo = getPDO();

        // Проверка токена в базе данных
        $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = :token AND expires_at > NOW()");
        $stmt->execute(['token' => $token]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($token_data) {
            // Найден действительный токен, обновляем пароль пользователя
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
            $stmt->execute(['password' => $hashed_password, 'email' => $token_data['email']]);

            // Удаляем использованный токен из базы данных
            $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = :token");
            $stmt->execute(['token' => $token]);

            header('Location: login.php');
            exit();
        } else {
            $errors['token'] = 'Неверный или просроченный токен для сброса пароля.';
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
    <title>Сброс пароля</title>
</head>
<body>
    <section class="main-section">
        <div class="container main-section__nav-container">
            <header class="main-section__header">
                <a href="index.php"><img class="main-section__logo" src="/icons/Logo.svg" alt="Изображение логотипа"></a>
            </header>
        </div>
    
        <div class="container reset-password__container">
            <h1>Сброс пароля</h1>
    
            <?php if (!empty($errors)) : ?>
                <div class="reset-password__errors">
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
    
            <form class="reset-password__form" action="" method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'], ENT_QUOTES); ?>">
                <label for="password">Новый пароль:</label><br>
                <input type="password" id="password" name="password" placeholder="Введите пароль"><br>
                <label for="confirm_password">Подтвердите пароль:</label><br>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Подтвердите пароль"><br>
                <button type="submit" class="btn btn-primary" value="Изменить пароль">Изменить пароль</button>
            </form>
        </div>
    </section>


<script src="js/bootstrap.bundle.js"></script>
<script src="js/swiper-bundle.min.js"></script>
<script src="/js/jquery-3.7.1.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>