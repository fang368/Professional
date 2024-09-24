<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';
session_start();
$pdo = getPDO();
$role_id = $_SESSION['role_id'];

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    // Если пользователь не авторизован, перенаправляем его на страницу входа
    redirect('login.php');
}

// Проверка роли пользователя (например, предполагаем, что роль администратора = 2)
if ($role_id != 2) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'] ?? null;

// Если пользователь авторизован, проверяем, заблокирован ли его аккаунт
if ($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT is_locked FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Если аккаунт заблокирован, перенаправляем на страницу blocked_users.php
    if ($user && $user['is_locked'] == 1) {
        redirect('blocked_users.php');
    }
}


// Получаем статистические данные из базы данных
// Количество зарегистрированных пользователей
$user_count_stmt = $pdo->query("SELECT COUNT(*) AS user_count FROM users");
$user_count = $user_count_stmt->fetch(PDO::FETCH_ASSOC)['user_count'];

// Количество заказов (предполагаем, что у вас есть таблица orders)
$order_count_stmt = $pdo->query("SELECT COUNT(*) AS order_count FROM orders");
$order_count = $order_count_stmt->fetch(PDO::FETCH_ASSOC)['order_count'];


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
    <title>Admin Panel</title>
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
                <?php if ($role_id == 2): ?>
                    <form action="profile.php" id="admin-panel-page" class="profile-left-wrapper__pages">
                        <button class="profile__btn-nav">
                            <img src="icons/chevron-double-left.svg" alt="Назад профиль">
                            <p class="profile-left-wrapper__pages-text text_dark">В профиль</p>
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($role_id == 2): ?>
                    <form action="users_edit.php" id="edit-users-page" class="profile-left-wrapper__pages">
                        <button class="profile__btn-nav">
                            <img src="icons/person-fill.svg" alt="Назад профиль">
                            <p class="profile-left-wrapper__pages-text text_dark">Пользователи</p>
                        </button>
                    </form>
                <?php endif; ?>
            
                <form action="edit_orders.php" id="orders-page" class="profile-left-wrapper__pages">
                    <button class="profile__btn-nav">
                        <img src="icons/box-seam-fill.svg" alt="Заказы">
                        <p class="profile-left-wrapper__pages-text text_dark">Заказы</p>
                    </button>
                </form>

               
            </div>
            
            <div class="admin__right-wrapper">
                <div class="admin__wrapper">
                    <div class="admin-elipse">
                        Кол-во пользователей:
                        <?php echo $user_count; ?>
                    </div>

                    <div class="admin-elipse">
                        Всего заказов на сайте:
                        <?php echo $order_count; ?>
                    </div>

                    <div class="admin-elipse">
                        Всего заказов на сайте:
                        <?php echo $order_count; ?>
                </div>
            </div>
        </div>
    </section>

<script src="js/bootstrap.bundle.js"></script>
<script src="js/swiper-bundle.min.js"></script>
<script src="/js/jquery-3.7.1.min.js"></script>
<script src="js/script.js"></script>
<script>
</script>
</body>
</html>
