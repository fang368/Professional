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

// Получаем список пользователей из базы данных
$users_stmt = $pdo->query("SELECT * FROM users");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка действий блокировки/разблокировки/удаления пользователя
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = $_POST['user_id'];

    if ($action == 'block') {
        // Блокировка пользователя
        $pdo->query("UPDATE users SET is_locked = 1 WHERE id = $userId");
    } elseif ($action == 'unblock') {
        // Разблокировка пользователя
        $pdo->query("UPDATE users SET is_locked = 0 WHERE id = $userId");
    } elseif ($action == 'delete') {
        // Удаление пользователя
        $pdo->query("DELETE FROM users WHERE id = $userId");
    }

    // Перенаправляем пользователя на эту же страницу после выполнения действия
    redirect('users_edit.php');
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
            <?php if ($role_id == 2): ?>
                    <form action="admin_panel.php" id="admin-panel-page" class="profile-left-wrapper__pages">
                        <button class="profile__btn-nav">
                            <img src="icons/chevron-double-left.svg" alt="Назад профиль">
                            <p class="profile-left-wrapper__pages-text text_dark">Админ панель</p>
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
            <?php foreach ($users as $user): ?>
                <div class="admin__container">
                    <div class="user_info-wrapper">
                        <div class="user-info__item">
                            <p>ФИО:</p>
                            <p><?php echo $user['full_name']; ?></p>
                        </div>

                        <div class="user-info__item">
                            <p>Логин:</p>
                            <p><?php echo $user['login']; ?></p>
                        </div>

                        <div class="user-info__item">
                            <p>Почта:</p>
                            <p><?php echo $user['email']; ?></p>
                        </div>
                        <div class="user-info__item">
                            <p>Телефон:</p>
                            <p><?php echo $user['phone']; ?></p>
                        </div>
                        
                    </div>
                    <form method="post" class="users__edit-users">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <?php if ($user['is_locked']): ?>
                            <button type="submit" name="action" value="unblock" class="btn btn-danger">Разблокировать</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="block" class="btn btn-success">Заблокировать</button>
                        <?php endif; ?>
                        <button type="submit" name="action" value="delete" class="btn btn-danger">Удалить</button>
                    </form>
                </div>
            <?php endforeach; ?>
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

