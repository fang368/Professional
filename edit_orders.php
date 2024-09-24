<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';
require_once 'vendor/autoload.php'; // Добавляем автозагрузку классов PHPMailer
session_start();
$pdo = getPDO();
$role_id = $_SESSION['role_id'];
$user_id = $_SESSION['user_id'];

// Проверяем, авторизован ли пользователь
if (!isset($user_id)) {
    // Если пользователь не авторизован, перенаправляем его на страницу входа
    redirect('login.php');
}

// Проверка роли пользователя (например, предполагаем, что роль администратора = 2)
if ($role_id != 2) {
    redirect('index.php');
}

// Обработка сортировки
$sort_criteria = $_GET['criteria'] ?? 'date_desc'; // По умолчанию сортируем по дате в порядке убывания

$sql = "SELECT * FROM Orders ";

// Добавляем фильтрацию по статусу, если указан GET-параметр status
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    switch ($status) {
        case 'not_waiting':
            $sql .= "WHERE status = 'Выполняется'";
            break;
        case 'new':
            $sql .= "WHERE status = 'Новый'";
            break;
        case 'completed':
            $sql .= "WHERE status = 'Выполнен'";
            break;
        case 'cancelled':
            $sql .= "WHERE status = 'Отменен'";
            break;
        default:
            // Все заказы (по умолчанию)
            break;
    }
}

// Добавляем сортировку по дате
if ($sort_criteria == 'date_asc') {
    $sql .= " ORDER BY order_date ASC";
} else {
    $sql .= " ORDER BY order_date DESC";
}

$stmt = $pdo->query($sql);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $orderId = intval($_POST['order_id']); 

    if ($action == 'accept') {
        // Принятие заказа
        $defaultAdminContact = '+7 987 48 48 909'; // Контактная информация администратора по умолчанию
        $stmt = $pdo->prepare("UPDATE Orders SET status = 'Выполняется' WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $stmt = $pdo->prepare("INSERT INTO order_admin_contacts (order_id, admin_id, contact_info) VALUES (?, ?, ?)");
        $stmt->execute([$orderId, $user_id, $defaultAdminContact]);

        $newStatus = 'Выполняется'; // Определение нового статуса
        $userId = $user_id; // Получение ID пользователя
        $formType = getOrderDetails($pdo, $orderId)['form_type']; // Получение типа заказа

        // Создаем тело письма
        $body = "Здравствуйте,\n\nСтатус вашего заказа изменен на: $newStatus. Можете посмотреть детали заказа в своем профиле.";

        // Определение переменных для отправки письма
        $subject = "Изменение статуса заказа";

        // Вызываем функцию отправки письма с PDF вложением
        sendEmail($orderId, $userId, $pdo, $body, $subject, $formType, $newStatus);
    } elseif ($action == 'change_status') {
        // Изменение статуса заказа
        $newStatus = $_POST['new_status'];
        $stmt = $pdo->prepare("UPDATE Orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$newStatus, $orderId]);

        $userId = $user_id; // Получение ID пользователя
        $formType = getOrderDetails($pdo, $orderId)['form_type']; // Получение типа заказа

        // Создаем тело письма
        $body = "Здравствуйте,\n\nСтатус вашего заказа изменен на: $newStatus. Можете посмотреть детали заказа в своем профиле.";

        // Определение переменных для отправки письма
        $subject = "Изменение статуса заказа";

        // Вызываем функцию отправки письма с PDF вложением 
        sendEmail($orderId, $userId, $pdo, $body, $subject, $formType, $newStatus);
        
        // Если статус изменен на "Выполнен", перенаправляем на страницу с завершенными заказами
        if ($newStatus == 'Выполнен') {
            redirect('edit_orders.php?status=completed');
        }
    }

    // Перенаправляем пользователя на эту же страницу после выполнения действия
    redirect('edit_orders.php');
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
    <title>Редактирование заказов</title>
</head>

<body>
    <section class="main-section__admin-section">
        <div class="container main-section__nav-container">
            <header class="main-section__header">
                <a href="index.php"><img class="main-section__logo" src="/icons/Logo.svg" alt="Изображение логотипа"></a>
            </header>
            <nav class="main-section__nav nav-menu">
                <a href="About_us.php">
                    <li class="nav-menu__list-item">О компании</li>
                </a>
                <a href="#">
                    <li class="nav-menu__list-item">Вакансии</li>
                </a>
                <a href="#">
                    <li class="nav-menu__list-item">Наши работы</li>
                </a>
                <a href="geo.php">
                    <li class="nav-menu__list-item">Контакты</li>
                </a>
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
                <?php if ($role_id == 2) : ?>
                    <form action="admin_panel.php" id="admin-panel-page" class="profile-left-wrapper__pages">
                        <button class="profile__btn-nav">
                            <img src="icons/chevron-double-left.svg" alt="Назад профиль">
                            <p class="profile-left-wrapper__pages-text text_dark">Админ панель</p>
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($role_id == 2) : ?>
                    <form action="users_edit.php" id="edit-users-page" class="profile-left-wrapper__pages">
                        <button class="profile__btn-nav">
                            <img src="icons/person-fill.svg" alt="Назад профиль">
                            <p class="profile-left-wrapper__pages-text text_dark">Пользователи</p>
                        </button>
                    </form>
                <?php endif; ?>

                <form action="orders.php" class="profile-left-wrapper__pages">
                    <button class="profile__btn-nav">
                        <img src="icons/box-seam-fill.svg" alt="Заказы">
                        <p class="profile-left-wrapper__pages-text text_dark">Заказы</p>
                    </button>
                </form>
            </div>

            <div class="admin__right-wrapper">

                <div class="admin__filter-wrapper">
                    <div class="dropdown sorting-dropdown">
                        <button class="btn sorting-dropdown-toggle" type="button" id="sortingDropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            Сортировка по дате
                        </button>
                        <ul class="dropdown-menu sorting-dropdown-menu" aria-labelledby="sortingDropdownMenuButton">
                            <li><a class="dropdown-item sorting-dropdown-item" href="edit_orders.php?criteria=date_asc&status=<?php echo $_GET['status'] ?? 'all'; ?>">По дате (возрастание)</a></li>
                            <li><a class="dropdown-item sorting-dropdown-item" href="edit_orders.php?criteria=date_desc&status=<?php echo $_GET['status'] ?? 'all'; ?>">По дате (убывание)</a></li>
                        </ul>
                    </div>

                    <div class="dropdown status-filter-dropdown">
                        <button class="btn status-filter-dropdown-toggle" type="button" id="statusFilterDropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            Фильтр статуса
                        </button>
                        <ul class="dropdown-menu status-filter-dropdown-menu" aria-labelledby="statusFilterDropdownMenuButton">
                            <li><a class="dropdown-item status-filter-dropdown-item" href="edit_orders.php?status=all">Все заказы</a></li>
                            <li><a class="dropdown-item status-filter-dropdown-item" href="edit_orders.php?status=not_waiting">Выполняемые заказы</a></li>
                            <li><a class="dropdown-item status-filter-dropdown-item" href="edit_orders.php?status=new">Новые заказы</a></li>
                            <li><a class="dropdown-item status-filter-dropdown-item" href="edit_orders.php?status=completed">Завершенные заказы</a></li>
                            <li><a class="dropdown-item status-filter-dropdown-item" href="edit_orders.php?status=cancelled">Отмененные заказы</a></li>
                        </ul>
                    </div>
                </div>

                <div class="items-container" id="items-container">
                    <?php foreach ($orders as $order) : ?>

                        <div class="admin__container">
                            <div class="admin__date-container">
                                <h3 class="admin__sub">Дата: <?php echo formatDate($order['order_date']); ?></h3>
                            </div>

                            <p>№ заказа: <?php echo $order['order_id']; ?></p>
                            <p>Имя: <?php echo $order['name']; ?></p>
                            <p>Телефон: <?php echo $order['phone']; ?></p>
                            <p>Тип: <?php echo $order['form_type']; ?></p>

                            <form method="post">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <input type="hidden" name="action" value="accept">
                            </form>

                            <form method="post">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <input type="hidden" name="action" value="change_status">
                                <div class="mb-3">
                                    <label for="new_status_<?php echo $order['order_id']; ?>" class="form-label">Новый статус</label>
                                    <select class="form-select" id="new_status_<?php echo $order['order_id']; ?>" name="new_status">
                                        <option value="В ожидании" <?php echo $order['status'] == 'В ожидании' ? 'selected' : ''; ?>>В ожидании</option>
                                        <option value="Выполняется" <?php echo $order['status'] == 'Выполняется' ? 'selected' : ''; ?>>Выполняется</option>
                                        <option value="Отменен" <?php echo $order['status'] == 'Отменен' ? 'selected' : ''; ?>>Отменен</option>
                                        <option value="Выполнен" <?php echo $order['status'] == 'Выполнен' ? 'selected' : ''; ?>>Выполнен</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Изменить статус</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

<script src="js/bootstrap.bundle.js"></script>
<script src="js/swiper-bundle.min.js"></script>
<script src="/js/jquery-3.7.1.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
