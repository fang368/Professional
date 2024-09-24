<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';
session_start();
$pdo = getPDO();

if (!isset($_SESSION['user_id'])) {
    // Если пользователь не авторизован, перенаправляем его на страницу входа
    redirect('login.php');
}

$role_id = $_SESSION['role_id'];
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    
    $sql = "DELETE FROM Orders WHERE order_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);
    redirect('orders.php');
}



// Получаем заказы текущего пользователя из базы данных
$sql = "SELECT * FROM Orders WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);




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
<section class="main-section__orders">
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
            <form action="profile.php">
                <button class="profile-btn">
                    <img src="icons/person-circle.svg" alt="Профиль" class="main-section-profile__img">
                </button>
            </form>
        </div>
    </div>

    <div class="container orders__container">
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
        
        <div class="orders__right-wrapper">
    <?php 
    if (empty($orders)) {
        echo "<p>Заказов нет</p>";
    } else {
        // Получаем классы и стили кнопок для каждого заказа
        $button_data = get_order_button_classes($orders);
        foreach ($orders as $key => $order): ?>
            <div class="orders__right-wrapper-block">
                <div class="orders-eyelash">
                    <div class="orders-eyelash__item">
                        <p class="orders__text text_dark-color">
                            Заказ <?php echo $order['order_id']  ?>  
                            <span class="orders_big">
                                <?php echo $order['form_type']; ?>
                            </span>
                            <span class="orders_mini">
                                от <?php echo formatDate($order['order_date']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="orders-eyelash__item">
                        <button class="orders__eyelash-button <?php echo $button_data[$key]['class']; ?>" style="<?php echo $button_data[$key]['style']; ?>">
                            <?php echo $order['status']; ?>
                        </button>
                    </div>
                    <div class="orders-eyelash__item">
                        <form method="post" class="order-delete-form">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <button type="submit" class="orders-eyelash__button btn_delete">Удалить из истории</button>
                        </form>
                    </div>
                </div>

                <div class="orders__main-wrapper">
                    <p class="orders__admin-contact">
                        Ваше Имя: <?php echo $order['name']?>
                    </p>

                    <p class="orders__admin-contact">
                        Тип заказа: <?php echo $order['form_type'] ?>
                    </p>

                    <p class="orders__admin-contact">
                        Контакты Менеджера: <?php echo $order['admin_contact'] ?? '+7 987 48 48 909'; ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php } ?>
</div>
    </div>
</section>



<script src="js/bootstrap.bundle.js"></script>
<script src="js/swiper-bundle.min.js"></script>
<script src="/js/jquery-3.7.1.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>