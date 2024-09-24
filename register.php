<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';
$pdo = getPDO();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из формы
    $full_name = $_POST['full_name'];
    $login = $_POST['login'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['password_confirm'];
    $role_id_user = 1;

    $formattedPhone = preg_replace('/\D/', '', $phone); // Удаляем все символы, кроме цифр
    $formattedPhone = '+7 ' . substr($formattedPhone, 1, 3) . ' ' . substr($formattedPhone, 4, 3) . ' ' . substr($formattedPhone, 7, 2) . ' ' . substr($formattedPhone, 9, 2);

    // Валидация данных
    $name_pattern = '/^[а-яА-ЯёЁa-zA-Z]+ [а-яА-ЯёЁa-zA-Z]+ [а-яА-ЯёЁa-zA-Z]+$/u';
    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/';

    if (empty($full_name)) {
        $errors['full_name'] = "Поле ФИО должно быть заполнено";
    } elseif (!preg_match($name_pattern, $full_name)) {
        $errors['full_name'] = "Неверный формат ФИО ";
    }

    if (empty($login)) {
        $errors['login'] = "Поле Логин должно быть заполнено";
    }
    if (empty($email)) {
        $errors['email'] = "Поле Почта должно быть заполнено";
    }
    if (empty($phone)) {
        $errors['phone'] = "Поле Телефон должно быть заполнено";
    }
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
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Неверный формат email";
    }

    // Проверка наличия существующего пользователя по email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    if ($existingUser) {
        $errors['email'] = "Пользователь с таким email уже зарегистрирован";
    }

    // Проверка наличия существующего пользователя по login
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $existingUser = $stmt->fetch();
    if ($existingUser) {
        $errors['login'] = "Пользователь с таким логином уже зарегистрирован";
    }

    // Проверка существующего пользователя по номеру телефона
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$formattedPhone]);
    $existingUser = $stmt->fetch();
    if ($existingUser) {
        $errors['phone'] = "Пользователь с таким номером телефона уже зарегистрирован";
    }

    // Если ошибок нет, продолжаем с вставкой нового пользователя
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (full_name, login, email, phone, password, role_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $login, $email, $formattedPhone, $hashed_password, $role_id_user]);

        // Перенаправление после успешной регистрации
        redirect("login.php");
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
    <title>Регистрация</title>
</head>
<body>
    <section class="main-section__orders">
        <div class="container main-section__nav-container">
            <header class="main-section__header">
                <a href="index.php"><img class="main-section__logo" src="/icons/Logo.svg" alt="Изображение логотипа"></a>
            </header>
        </div>
        <div class="container login-form__container">
            <form class="login-form" action="register.php" method="post">
                <?php if (isset($errors) && !empty($errors)) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errors as $error) : ?>
                            <p class="alert-text text_dark"><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="password-input-wrapper">
                    <label>ФИО</label>
                    <input
                        type="text"
                        name="full_name"
                        id="full_name"
                        class="form-content__item <?php echo getFieldClass($errors, 'full_name'); ?>"
                        placeholder="Иванов Иван Иванович"
                        value="<?php echo getFieldValueIfValid($errors, 'full_name'); ?>"
                    >
                </div>

                <div class="password-input-wrapper">
                    <label>Логин</label>
                    <input
                        type="text"
                        class="form-content__item <?php echo getFieldClass($errors, 'login'); ?>"
                        name="login"
                        id="login"
                        placeholder="Введите логин"
                        value="<?php echo getFieldValueIfValid($errors, 'login'); ?>"
                    >
                </div>

                <div class="password-input-wrapper">
                    <label>Почта</label>
                    <input
                        type="email"
                        class="form-content__item <?php echo getFieldClass($errors, 'email'); ?>"
                        name="email"
                        placeholder="Введите почту"
                        value="<?php echo getFieldValueIfValid($errors, 'email'); ?>"
                    >
                </div>

                <div class="password-input-wrapper">
                    <label>Телефон</label>
                    <input
                        type="text"
                        class="form-content__item phone-input <?php echo getFieldClass($errors, 'phone'); ?>"
                        name="phone"
                        id="phone"
                        placeholder="Введите номер телефона"
                        value="<?php echo getFieldValueIfValid($errors, 'phone'); ?>"
                    >
                </div>

                <div class="password-input-wrapper">
                    <label>Пароль</label>
                    <input
                        type="password"
                        class="form-content__item <?php echo getFieldClass($errors, 'password'); ?>"
                        name="password"
                        placeholder="Введите пароль"
                    >
                </div>

                <div class="password-confirm-input-wrapper">
                    <label>Подтверждение пароля</label>
                    <input
                        type="password"
                        class="form-content__item <?php echo getFieldClass($errors, 'password_confirm'); ?>"
                        name="password_confirm"
                        placeholder="Подтвердите пароль"
                    >
                </div>

                <div class="btn-input-wrapper">
                    <button type="submit" class="btn btn-primary btn_form">Войти</button>
                    <div class="btn-input__container">
                        <p class="form__content-text text_dark">
                            Уже есть аккаунт? - <a href="/login.php">Авторизируйтесь</a>
                        </p>
                    </div>
                </div>
                <input type="hidden" id="errors" value='<?php echo json_encode($errors); ?>'>
            </form>
        </div>
    </section>

    <script src="js/bootstrap.bundle.js"></script>
    <script src="js/swiper-bundle.min.js"></script>
    <script src="/js/jquery-3.7.1.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
