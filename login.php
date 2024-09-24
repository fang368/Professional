<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';
session_start();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из формы
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Валидация данных
    $errors = [];

    if (empty($email)) {
        $errors['email'] = "Поле Почта должно быть заполнено";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Неверный формат email";
    }

    if (empty($password)) {
        $errors['password'] = "Поле Пароль должно быть заполнено";
    }

    // Если нет ошибок, продолжаем процесс авторизации
    if (empty($errors)) {
        // Поиск пользователя по электронной почте
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Проверка, найден ли пользователь
        if ($user) {
            // Проверка совпадения пароля
            if (password_verify($password, $user['password'])) {
                // Аутентификация успешна, сохраняем пользователя в сессии
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role_id'] = $user['role_id'];

                // Перенаправляем пользователя на целевую страницу
                redirect('index.php');
            } else {
                // Пароль неверен, выводим сообщение об ошибке
                $errors['password'] = "Неверный логин или пароль";
            }
        } else {
            // Пользователь не найден, выводим сообщение об ошибке
            $errors['email'] = "Пользователь с таким email не найден";
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
    <title>Авторизация</title>
</head>
<body>
    <section class="main-section__orders">
        <div class="container main-section__nav-container">
            <header class="main-section__header">
                <a href="index.php"><img class="main-section__logo" src="/icons/Logo.svg" alt="Изображение логотипа"></a>
            </header>
        </div>
    
        <div class="container login-form__container">
            <form class="login-form" action="login.php" method="post">
                <?php if (isset($errors) && !empty($errors)) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errors as $error) : ?>
                            <p class="alert-text text_dark"><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="password-input-wrapper">
                    <label>Почта</label>
                    <input 
                        type="email" 
                        class="form-content__item <?php echo isset($errors['email']) ? 'error-border' : '' ?>" 
                        name="email"
                        placeholder="Введите почту"
                        value="<?php echo getFieldValueIfValid($errors, 'email'); ?>"
                    >
                </div>

                <div class="password-input-wrapper">
                    <label>Пароль</label>
                    <input 
                        type="password" 
                        class="form-content__item <?php echo isset($errors['password']) ? 'error-border' : '' ?>" 
                        name="password" 
                        placeholder="Введите пароль"
                    >
                </div>
          
                <div class="btn-input-wrapper">
                    <button type="submit" class="btn btn-primary btn_form">Войти</button>
                    <div class="btn-input__container">
                        <p class="form__content-text text_dark">
                            У вас нет аккаунта? - <a href="/register.php">Зарегистрируйтесь</a>
                        </p>
                        <a href="/forgot_password.php" class="fnt_size16-a">Забыли пароль?</a>
                    </div>
                </div>
            </form>
        </div>
    </section>

<script src="js/bootstrap.bundle.js"></script>
<script src="js/swiper-bundle.min.js"></script>
<script src="/js/jquery-3.7.1.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
