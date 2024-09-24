<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';
session_start();
$pdo = getPDO();

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$role_id = $_SESSION['role_id'];
$user_id = $_SESSION['user_id'] ?? null;

// Если пользователь авторизован, проверяем, заблокирован ли его аккаунт
if ($user_id) {
    $stmt = $pdo->prepare("SELECT is_locked FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Если аккаунт заблокирован, перенаправляем на страницу blocked_users.php
    if ($user && $user['is_locked'] == 1) {
        redirect('blocked_users.php');
    }
}

$errors = [];

// Получаем текущие данные пользователя для сравнения
$user = getUserById($pdo, $user_id);

// Проверяем, если форма отправлена
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_profile'])) {
    // Получение данных из формы
    $full_name = $_POST['full_name'];
    $login = $_POST['login'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['password_confirm'];

    $formattedPhone = preg_replace('/\D/', '', $phone); // Удаляем все символы, кроме цифр
    $formattedPhone = '+7 ' . substr($formattedPhone, 1, 3) . ' ' . substr($formattedPhone, 4, 3) . ' ' . substr($formattedPhone, 7, 2) . ' ' . substr($formattedPhone, 9, 2);

    // Проверка изменений данных
    $dataChanged = false;
    if ($full_name !== $user['full_name'] || 
        $login !== $user['login'] || 
        $email !== $user['email'] || 
        $formattedPhone !== $user['phone'] || 
        (!empty($password) && !password_verify($password, $user['password']))) {
        $dataChanged = true;
    }

    // Если нет изменений, добавляем ошибку
    if (!$dataChanged) {
        $errors['no_changes'] = "Нет данных для обновления";
    }

    // Проверка, если загружено новое изображение и нет ошибок валидации изображения
    if (!empty($_FILES['profile_picture']['name']) && empty($errors['profile_picture'])) {
        $dataChanged = true; // Помечаем, что данные изменились, если новое изображение загружено
        $temp_name = $_FILES['profile_picture']['tmp_name'];
        $file_name = basename($_FILES['profile_picture']['name']);
        $upload_path = 'uploads/' . $file_name;

        // Выполните здесь валидацию изображения
        $image_info = getimagesize($temp_name); // Получаем информацию о загруженном изображении
        if ($image_info === false) {
            $errors['profile_picture'] = "Ошибка при загрузке изображения. Пожалуйста, загрузите правильное изображение.";
        } else {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif']; // Разрешенные типы изображений
            if (!in_array($image_info['mime'], $allowed_types)) {
                $errors['profile_picture'] = "Недопустимый тип изображения. Разрешены только JPEG, PNG и GIF.";
            }
        }

        // Если ошибок с изображением нет, переместите его в директорию и обновите путь в базе данных
        if (empty($errors['profile_picture'])) {
            move_uploaded_file($temp_name, $upload_path);
            $update_query = "UPDATE users SET image = ? WHERE id = ?";
            $stmt = $pdo->prepare($update_query);
            $stmt->execute([$upload_path, $user_id]);

            // Редирект на страницу профиля
            redirect('profile.php');
        } 
    }

    // Валидация данных
    $name_pattern = '/^[а-яА-ЯёЁa-zA-Z]+ [а-яА-ЯёЁa-zA-Z]+ [а-яА-ЯёЁa-zA-Z]+$/u';
    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/';

    if (empty($errors['profile_picture'])) {
        if (empty($full_name)) {
            $errors['full_name'] = "Поле ФИО должно быть заполнено";
        } elseif (!preg_match($name_pattern, $full_name)) {
            $errors['full_name'] = "Неверный формат ФИО";
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

        if (!empty($phone) && !preg_match('/^\+7 \d{3} \d{3} \d{2} \d{2}$/', $formattedPhone)) {
            $errors['phone'] = "Номер телефона должен быть в формате +7 XXX XXX XX XX";
        }

        if (!empty($password) && !preg_match($password_pattern, $password)) {
            $errors['password'] = "Пароль должен содержать минимум 8 символов, хотя бы одну заглавную букву, одну строчную букву и одну цифру";
        }

        if ($password !== $confirm_password) {
            $errors['password_confirm'] = "Пароли не совпадают";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Неверный формат email";
        }
    }

    // Проверка наличия существующего пользователя по email, кроме текущего пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    $existingUser = $stmt->fetch();
    if ($existingUser) {
        $errors['email'] = "Пользователь с таким email уже зарегистрирован";
    }

    // Проверка наличия существующего пользователя по login, кроме текущего пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ? AND id != ?");
    $stmt->execute([$login, $user_id]);
    $existingUser = $stmt->fetch();
    if ($existingUser) {
        $errors['login'] = "Пользователь с таким логином уже зарегистрирован";
    }

    // Проверка наличия существующего пользователя по phone, кроме текущего пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND id != ?");
    $stmt->execute([$formattedPhone, $user_id]);
    $existingUser = $stmt->fetch();
    if ($existingUser) {
        $errors['phone'] = "Пользователь с таким номером телефона уже зарегистрирован";
    }

    // Если ошибок нет и данные изменены, обновляем данные в базе данных
    if (empty($errors) && $dataChanged) {
        $update_query = "UPDATE users SET full_name = ?, login = ?, email = ?, phone = ?";
        $params = [$full_name, $login, $email, $formattedPhone];
    
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_query .= ", password = ?";
            $params[] = $hashed_password;
        }
    
        $update_query .= " WHERE id = ?";
        $params[] = $user_id;
    
        $stmt = $pdo->prepare($update_query);
        $stmt->execute($params);

        $_SESSION['success_message'] = "Данные успешно обновлены";
    
        // Редирект на страницу профиля
        header('Location: profile.php');
        exit();
    }
}

// Здесь продолжается ваш текущий код, который отображает и обновляет профиль пользователя
$user = getUserById($pdo, $user_id);

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
    <title>Document</title>
</head>
<body>
<section class="main-section__profile-section">
        <div class="container main-section__nav-container">
            <header class="main-section__header">
                <a href="index.php"><img class="main-section__logo" src="/icons/Logo.svg" alt="Изображение логотипа"></a>
            </header>
            <nav class="main-section__nav nav-menu">
                    <a href="About_us.php"><li class="nav-menu__list-item">О компании</li></a>
                    <a href="#"><li class="nav-menu__list-item">Вакансии</li></a>
                    <a href="#"><li class="nav-menu__list-item">Наши работы</li></a>
                    <a href="geo.php"><li class="nav-menu__list-item">Контакты</li></a>
            </nav>
            <div class="main-section__search">
                <input type="text" class="search__list-item">
                <img src="icons/Group 139.svg" alt="Поиск" class="search__img">
            </div>

            <div class="main-section__profile">
                <form action="vendor/logout.php">
                    <button class="profile-btn logout_btn">
                        Выйти
                    </button>
                </form>
            </div>
        </div>
        
        <div class="container profile__container">
            <div class="profile__left-wrapper">
                <form action="profile.php" id="profile-page" class="profile-left-wrapper__pages">
                    <button class="profile__btn-nav">
                        <img src="icons/gear.svg" alt="Настройки">
                        <p class="profile-left-wrapper__pages-text text_dark">Настройки</p>
                    </button>
                </form>
            
                <form action="orders.php" id="orders-page" class="profile-left-wrapper__pages">
                    <button class="profile__btn-nav">
                        <img src="icons/box-seam.svg" alt="Заказы">
                        <p class="profile-left-wrapper__pages-text text_dark">Заказы</p>
                    </button>
                </form>

                <?php if ($role_id == 2): ?>
                <form action="admin_panel.php" id="admin-panel-page" class="profile-left-wrapper__pages">
                    <button class="profile__btn-nav">
                        <img src="icons/person-lock.svg" alt="Админ панель">
                        <p class="profile-left-wrapper__pages-text text_dark">Админ панель</p>
                    </button>
                </form>
                <?php endif; ?>
                
            </div>
            
            
            <div class="profile__right-wrapper">
                <div class="profile__user-info">
                    <img src="<?php echo htmlspecialchars($user['image']); ?>" alt="Изображение профиля" height="140px" width="140px" id="profile-picture">
                    <form class="profile-picture-form_form" id="profile-picture-form" action="profile.php" method="POST" enctype="multipart/form-data">
                        <input type="file" name="profile_picture" id="profile_picture_input" style="display: none;" onchange="document.getElementById('profile-picture-form').submit();">
                    </form>

                    <div class="user-info__name-container">
                        <h4 class="user-info__title title_dark-color"><?php echo htmlspecialchars($user['login']); ?></h4>
                        <p class="user-info__text text_dark-color "><?php echo htmlspecialchars($user['full_name']); ?></p>
                    </div>
                </div>
                

                <div class="profile__user-update-info">
                    <form class="profile-form" action="profile.php" method="post" enctype="multipart/form-data">

                        <!-- Вывод ошибок валидации -->
                        <?php if (isset($errors) && !empty($errors)) : ?>
                            <div class="alert alert-danger" role="alert">
                                <?php foreach ($errors as $error) : ?>
                                    <p class="alert-text text_dark"><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Вывод сообщения об успешном обновлении -->
                        <?php if (isset($_SESSION['success_message'])) : ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="password-input-wrapper">
                            <label>ФИО</label>
                            <input 
                                type="text" 
                                name="full_name" 
                                id="full_name" 
                                class="form-content__item form-content__item-white <?php echo isset($errors['full_name']) ? 'error-border' : '' ?>" 
                                placeholder="Иванов Иван Иванович"
                                value="<?php echo htmlspecialchars($user['full_name']); ?>"
                            >
                        </div>
                
                        <div class="password-input-wrapper">
                            <label>Логин</label>
                            <input 
                                type="text" 
                                class="form-content__item form-content__item-white <?php echo isset($errors['login']) ? 'error-border' : '' ?>" 
                                name="login"
                                id="login"
                                placeholder="Введите логин"
                                value="<?php echo htmlspecialchars($user['login']); ?>"
                            >
                        </div>
                    
                        <div class="password-input-wrapper">
                            <label>Почта</label>
                            <input 
                                type="email" 
                                class="form-content__item form-content__item-white <?php echo isset($errors['email']) ? 'error-border' : '' ?>" 
                                name="email"
                                placeholder="Введите почту"
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                            >
                        </div>

                        <div class="password-input-wrapper">
                            <label>Телефон</label>
                            <input 
                                type="text" 
                                class="form-content__item form-content__item-white phone-input <?php echo isset($errors['phone']) ? 'error-border' : '' ?>" 
                                name="phone" 
                                id="phone"
                                placeholder="Введите номер телефона"
                                value="<?php echo htmlspecialchars($user['phone']); ?>"
                            >
                        </div>

                        <div class="password-input-wrapper">
                            <label>Пароль</label>
                            <input 
                                type="password" 
                                class="form-content__item form-content__item-white <?php echo isset($errors['password']) ? 'error-border' : '' ?>" 
                                name="password" 
                                placeholder="Введите пароль"
                            >
                        </div>
                        
                        <div class="password-confirm-input-wrapper">
                            <label>Подтверждение пароля</label>
                            <input 
                                type="password" 
                                class="form-content__item form-content__item-white <?php echo isset($errors['password_confirm']) ? 'error-border' : '' ?>" 
                                name="password_confirm"
                                placeholder="Подтвердите пароль"
                            >
                        </div>
                                    
                        <div class="test-2">
                        <div class="btn-input-wrapper">
                            <button type="submit" class="btn btn-primary btn_form">Обновить профиль</button>
                        </div>
                        <!-- Кнопка удаления профиля -->
                    </form>
                    <form action="profile.php" method="post" id="delete-profile-form">
                        <button type="submit" class="btn btn-danger" name="delete_profile" onclick="return confirm('Вы уверены, что хотите удалить свой профиль?')">Удалить профиль</button>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

<script src="js/bootstrap.bundle.js"></script>
<script src="js/swiper-bundle.min.js"></script>
<script src="/js/jquery-3.7.1.min.js"></script>
<script src="js/script.js"></script>
<script>
    document.getElementById('profile-picture').addEventListener('click', function() {
        document.getElementById('profile_picture_input').click();
    });
</script>
</body>
</html>
