<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';
session_start();
$pdo = getPDO();

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
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['formType']) && $_POST['formType'] === 'webSupportForm') {
        // Получаем данные из формы заказа
        $name = htmlspecialchars($_POST['name']);
        $phone = htmlspecialchars($_POST['phone']);
        $time = htmlspecialchars($_POST['time']);

        $formattedPhone = preg_replace('/\D/', '', $phone); // Удаляем все символы, кроме цифр
        $formattedPhone = '+7 ' . substr($formattedPhone, 1, 3) . ' ' . substr($formattedPhone, 4, 3) . ' ' . substr($formattedPhone, 7, 2) . ' ' . substr($formattedPhone, 9, 2);

        // Валидация данных заказа
        if (empty($name)) {
            $errors[] = "Имя обязательно для заполнения";
        } 

        if (!empty($phone) && !preg_match('/^\+7 \d{3} \d{3} \d{2} \d{2}$/', $formattedPhone)) {
            $errors['phone'] = "Номер телефона должен быть в формате +7 XXX XXX XX XX";
        }

        if (!isset($_POST['consent'])) {
            $errors[] = "Необходимо согласиться на обработку персональных данных";
        }

        // Обработка данных заказа
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                $sql = "INSERT INTO Orders (user_id, form_type, name, phone, time, status, order_date) VALUES (:user_id, :form_type, :name, :phone, :time, :status, :order_date)";
                $stmt = $pdo->prepare($sql);
                $userId = $_SESSION['user_id'];
                $formType = 'Поддержка сайтов';
                $status = 'В ожидании'; // устанавливаем статус заказа
                $orderDate = date('Y-m-d'); // текущая дата
                $stmt->execute(['user_id' => $userId, 'form_type' => $formType, 'name' => $name, 'phone' => $phone, 'time' => $time, 'status' => $status, 'order_date' => $orderDate]);

                $orderId = $pdo->lastInsertId();

                $sqlOrderDetails = "INSERT INTO order_details (order_id, product_name, quantity) VALUES (:order_id, :product_name, :quantity)";
                $stmtOrderDetails = $pdo->prepare($sqlOrderDetails);
                $quantity = 1;
                $stmtOrderDetails->execute([
                    ':order_id' => $orderId,
                    ':product_name' => $formType,
                    ':quantity' => $quantity
                ]);

                $sqlOrderHistory = "INSERT INTO order_history (order_id, status, change_date) VALUES (:order_id, :status, :change_date)";
                $stmtOrderHistory = $pdo->prepare($sqlOrderHistory);
                $stmtOrderHistory->execute([
                    ':order_id' => $orderId,
                    ':status' => $status,
                    ':change_date' => date('Y-m-d H:i:s')
                ]);

                 // Создаем тело письма
                 $body = "Заказ на разработку сайта.\nИмя: $name\nНомер телефона: $formattedPhone\nВремя звонка: $time";
                
                 // Определение переменных для отправки письма
                 $orderId = $pdo->lastInsertId();
                 $subject = "Новый заказ";
 
                 // Вызываем функцию отправки письма
                 sendEmail($orderId, $userId, $pdo, $body, $subject, $formType, $status);
                 
                 // Определение переменных для отправки письма
                 $orderId = $pdo->lastInsertId();
                 $subject = "Новый заказ";


                 $pdo->commit();
                header("Location: orders.php");
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = "Ошибка при выполнении запроса: " . $e->getMessage();
            }
        }
        
    }elseif($_POST['formType'] === 'webSupportForm-second'){
         // Получаем данные из формы заказа
         $name = htmlspecialchars($_POST['name']);
         $phone = htmlspecialchars($_POST['phone']);
         $time = htmlspecialchars($_POST['time']);
 
         $formattedPhone = preg_replace('/\D/', '', $phone); // Удаляем все символы, кроме цифр
         $formattedPhone = '+7 ' . substr($formattedPhone, 1, 3) . ' ' . substr($formattedPhone, 4, 3) . ' ' . substr($formattedPhone, 7, 2) . ' ' . substr($formattedPhone, 9, 2);
 
         // Валидация данных заказа
         if (empty($name)) {
             $errors[] = "Имя обязательно для заполнения";
         } 
 
         if (!empty($phone) && !preg_match('/^\+7 \d{3} \d{3} \d{2} \d{2}$/', $formattedPhone)) {
             $errors['phone'] = "Номер телефона должен быть в формате +7 XXX XXX XX XX";
         }
 
         if (empty($time)) {
             $errors[] = "Время звонка обязательно для заполнения";
         } elseif (!preg_match("/^[\d]{2}:[\d]{2}$/", $time)) {
             $errors[] = "Некорректный формат времени (ожидается ЧЧ:ММ)";
         }
 
         if (!isset($_POST['consent'])) {
             $errors[] = "Необходимо согласиться на обработку персональных данных";
         }
 
         // Обработка данных заказа
         if (empty($errors)) {
             try {
                $pdo ->beginTransaction();
                 $sql = "INSERT INTO Orders (user_id, form_type, name, phone, time, status, order_date) VALUES (:user_id, :form_type, :name, :phone, :time, :status, :order_date)";
                 $stmt = $pdo->prepare($sql);
                 $userId = $_SESSION['user_id'];
                 $formType = 'Разработка сайтов';
                 $status = 'В ожидании'; // устанавливаем статус заказа
                 $orderDate = date('Y-m-d'); // текущая дата
                 $stmt->execute(['user_id' => $userId, 'form_type' => $formType, 'name' => $name, 'phone' => $phone, 'time' => $time, 'status' => $status, 'order_date' => $orderDate]);

                $orderId = $pdo->lastInsertId();

                $sqlOrderDetails = "INSERT INTO order_details (order_id, product_name, quantity) VALUES (:order_id, :product_name, :quantity)";
                $stmtOrderDetails = $pdo->prepare($sqlOrderDetails);
                $quantity = 1;
                $stmtOrderDetails->execute([
                    ':order_id' => $orderId,
                    ':product_name' => $formType,
                    ':quantity' => $quantity
                ]);

                $sqlOrderHistory = "INSERT INTO order_history (order_id, status, change_date) VALUES (:order_id, :status, :change_date)";
                $stmtOrderHistory = $pdo->prepare($sqlOrderHistory);
                $stmtOrderHistory->execute([
                    ':order_id' => $orderId,
                    ':status' => $status,
                    ':change_date' => date('Y-m-d H:i:s')
                ]);

                 // Создаем тело письма
                 $body = "Заказ на разработку сайта.\nИмя: $name\nНомер телефона: $formattedPhone\nВремя звонка: $time";
                
                 // Определение переменных для отправки письма
                 $orderId = $pdo->lastInsertId();
                 $subject = "Новый заказ";
 
                 // Вызываем функцию отправки письма
                 sendEmail($orderId, $userId, $pdo, $body, $subject, $formType, $status);
                 
                 // Определение переменных для отправки письма
                 $orderId = $pdo->lastInsertId();
                 $subject = "Новый заказ";

                 $pdo ->commit();
                 header("Location: orders.php");
                 exit();
             } catch (PDOException $e) {
                $pdo ->rollBack();
                 $errors[] = "Ошибка при выполнении запроса: " . $e->getMessage();
             }
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
    <title>Document</title>
</head>
<body>
    
    <section class="main-section__website-support">
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

        <div class="container main-section__dropdown-menu">
            <div class="main-section-dropdown__background">
                <!-- Dropdown 1 -->
                <div class="dropdown dropdown-main">
                    <button class="dropdown-toggle" type="button" id="dropdownMenuButton1" aria-haspopup="true" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="#0460B4" class="bi bi-envelope dropdown__img" viewBox="0 0 16 16">
                            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/>
                          </svg>  Контекстная реклама
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                        <div class="dropdown-item__container">
                            <form method="post" action="advertising.php">
                                <button class="dropdown-item" type="submit" name="menu_option" value="option1">Стоимость контекстной <br> рекламы</button>
                            </form>
                        </div>
                    </div>
                </div>
            
                <!-- Dropdown 2 -->
                <div class="dropdown">
                    <button class="dropdown-toggle dropdown-toggle_second" type="button" id="dropdownMenuButton2" aria-haspopup="true" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="#0460B4" class="bi bi-graph-up-arrow dropdown__img" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5"/>
                          </svg> Продвижение сайтов
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                        <div class="dropdown-item__container">
                            <form method="post" action="strategy.php">
                                <button class="dropdown-item" type="submit" name="menu_option" value="option6">Стратегия продвижения</button>
                            </form>
                        </div>
                    </div>
                </div>
        
                <!-- Dropdown 3 -->
                <div class="dropdown">
                    <button class="dropdown-toggle dropdown-toggle_second" type="button" id="dropdownMenuButton3" aria-haspopup="true" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="#0460B4" class="bi bi-code-slash dropdown__img" viewBox="0 0 16 16">
                            <path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0m6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0"/>
                        </svg> Создание сайтов
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
                        <div class="dropdown-item__container">
                            <form method="post" action="complex_web.php">
                                <button class="dropdown-item" type="submit" name="menu_option" value="option7">Сложный сайт</button>
                            </form>
                        </div>
                    </div>
                </div>
        
                <!-- Dropdown 4 -->
                <div class="dropdown">
                    <button class="dropdown-toggle dropdown-toggle_third" type="button" id="dropdownMenuButton4" aria-haspopup="true" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="#0460B4" class="bi bi-bezier2 dropdown__img" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 2.5A1.5 1.5 0 0 1 2.5 1h1A1.5 1.5 0 0 1 5 2.5h4.134a1 1 0 1 1 0 1h-2.01q.269.27.484.605C8.246 5.097 8.5 6.459 8.5 8c0 1.993.257 3.092.713 3.7.356.476.895.721 1.787.784A1.5 1.5 0 0 1 12.5 11h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5H6.866a1 1 0 1 1 0-1h1.711a3 3 0 0 1-.165-.2C7.743 11.407 7.5 10.007 7.5 8c0-1.46-.246-2.597-.733-3.355-.39-.605-.952-1-1.767-1.112A1.5 1.5 0 0 1 3.5 5h-1A1.5 1.5 0 0 1 1 3.5zM2.5 2a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm10 10a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z"/>
                          </svg> Другие услуги
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
                        <div class="dropdown-item__container">
                            <form method="post" action="website_support.php">
                                <button class="dropdown-item" type="submit" name="menu_option" value="option10">Поддержка сайтов</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        

        
        <div class="container four-section__complete-solution">
            <!-- Левая обертка -->
            <div class="complete-solution__left-wrapper">
                <div class="container four-section__subtitle-main">
                    <h3 class="third-section__about-trust__subtitle-main subtitle sub_white text_blue">
                        Поддержка <br> сайтов
                    </h3>
                </div>
                <p class="complete-solution__text text text_white-bold text_dark-bold">
                    Гарантия бесперебойной работы <br> вашего ресурса
                </p>
                <!-- Форма -->
                <form action="website_support.php" method="POST" class="complete-solution__form">
                <?php if (!empty($errors)) { ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error) { ?>
                            <p><?php echo $error; ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>
                <input type="hidden" name="formType" value="webSupportForm">
                    <div class="input-container-item">
                        <img src="icons/person-fill.svg" alt="" class="input-container-item__img">
                        <input type="text" placeholder="Имя" name="name" class="complete-solution__input" required>
                    </div>

                    <div class="input-container-item">
                        <img src="icons/telephone-fill.svg" alt="" class="input-container-item__img">
                        <input type="tel" placeholder="Телефон*" name="phone" class="complete-solution__input phone-input" require>
                    </div>

                    <div class="checkbox">
                        <input type="checkbox" id="checkbox_2" name="consent" class="checkbox__input" required>
                        <label for="checkbox_2" class="checkbox__label checkbox__label_dark">Я согласен на обработку персн. данных</label>
                    </div>

                    <button class="complete-solution__btn btn-main-red">
                        Получить предложение
                    </button>

                </form>
            </div>
            <!-- правая обертка -->
            <div class="complete-solution__right-wrapper">
                <img src="img/page_support_satellite.png" alt="">
            </div>
        </div>
    </section>

    <!-- 2 секция -->

    <section class="second-section second-section-website-support">
        <div class="container second-section-website-support__title-container">
            <h3 class="second-section-website-support__title title_ft-40-white">Для чего нужно сопровождение сайта?</h3>
        </div>

        <div class="button__up-container">
            <button type="button" class="up-btn">Вверх</button>
        </div>

        <div class="container second-section-website-support__container">
            <div class="second-section-website-support__left-wrapper">
                <div class="website-support__item-container">
                    <img src="icons/maintenance_1 1.svg" alt="icon" height="64px">
                    <div class="test-12">
                        <h4 class="website-support__title fnt-size_24">Отслеживание и профилактика сбоев в работе</h4>
                        <p class="website-support__text text">
                            Наши специалисты постоянно следят за «самочувствием» сайта: исправляют ошибки, в случае сбоев в короткие сроки возвращают ресурс к работе.
                        </p>
                    </div>
                </div>
                
                <div class="website-support__item-container">
                    <img src="icons/maintenance_2.svg" alt="icon" height="64px">
                    <div class="test-12">
                        <h4 class="website-support__title fnt-size_24">Контентная поддержка</h4>
                        <p class="website-support__text text">
                            Быстро реагируем на заявки, добавляем новую информацию, вносим правки или устраняем ошибки.
                        </p>
                    </div>
                </div>

                <div class="website-support__item-container">
                    <img src="icons/maintenance_3.svg" alt="icon" height="64px">
                    <div class="test-12">
                        <h4 class="website-support__title fnt-size_24">Оптимизация, ускорение быстродействия</h4>
                        <p class="website-support__text text">
                            Вашим посетителям не придется долго ждать загрузки, выполнения основных действий. Пользоваться сайтом будет легко и удобно.
                        </p>
                    </div>
                </div>
            </div>

            

            <div class="second-section-website-support__right-wrapper">
                <div class="website-support__item-container">
                    <img src="icons/maintenance_4.svg" alt="icon" height="64px">
                    <div class="test-12">
                        <h4 class="website-support__title fnt-size_24">Резервное копирование, антивирусные проверки</h4>
                        <p class="website-support__text text">
                            Эти услуги входят в стоимость ведения сайта, проводятся несколько раз в месяц. Все уязвимости на сайте будут вовремя обнаружены и закрыты.
                        </p>
                    </div>
                </div>

                <div class="website-support__item-container">
                    <img src="icons/maintenance_5.svg" alt="icon" height="64px">
                    <div class="test-12">
                        <h4 class="website-support__title fnt-size_24">Регулярное тестирование форм и этапов оформления заказа</h4>
                        <p class="website-support__text text">
                            Вы больше не будете терять клиентов из-за неработоспособности корзины или иной досадной ошибки.
                        </p>
                    </div>
                </div>

                <div class="second-section-website-support__img-container">
                    <img src="img/we_offer.png" alt="">
                </div>
            </div>
        </div>

    </section>

    <!-- 3 секция -->

    <div class="container modal__container">
        <!-- Модальное окно -->
        <div id="WebsiteSupportModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 class="modal-header">ЗАКАЗ ТЕХНИЧЕСКОЙ ПОДДЕРЖКИ</h2>
                <form action="website_support.php" method="POST" class="modal-form">
                    <?php if (!empty($errors)) { ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error) { ?>
                                <p><?php echo $error; ?></p>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <input type="hidden" name="formType" value="webSupportForm-second">
                    <div class="mb-3">
                        <input type="text" id="name" name="name" class="form-control input_modal" placeholder="Имя" required>
                    </div>
                    <div class="mb-3">
                        <input type="tel" id="phone" name="phone" class="form-control input_modal phone-input" placeholder="Телефон" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" id="time" name="time" class="form-control input_modal" placeholder="Время звонка" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" id="consent" name="consent" class="form-check-input" required>
                        <label for="consent" class="form-check-label">Я согласен на обработку персональных данных</label>
                    </div>
                    <button type="submit" class="btn-main-red">Перезвонить мне</button>
                </form>
            </div>
        </div>
    </div>

    <section class="third-section-website-support">
        <div class="container third-section-website-support__title-container">
            <h3 class="third-section-website-support__title title_ft-40-white title_dark">Тарифы технической поддержки</h3>
        </div>
        <div class="container third-section-website-support__container">

            <!-- Карточка -->
            <div class="third-section-website-support__card">
                <div class="box-item__container">
                    <h4 class="third-section-website-support__title fnt-size_24">Эконом</h4>
                    <p class="third-section-website-support__text text text_white">7 000 руб</p>
                    <div class="box-item__item-container">
                        <img src="icons/white_clock.png" alt="time" height="20px">
                        <p class="third-section-website-support__text-box-item text">1 час в месяц</p>
                    </div>
                </div>

                <div class="third-section-website-support__card-items-container">
                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Контроль работы сайта</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Восстановление при сбоях</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Защита от вирусов</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim 4.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_grey">Персональный менеджер</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Доработка функционала</p>
                    </div>

                    <form action="" class="complex-web__button-form">
                        <button class="complex-web__button btn-main-red btn-form btn_form-offer btn_website">
                            ЗАКАЗАТЬ
                        </button>
                    </form>
                </div>
            </div>

            <!-- Карточка 2 -->
            <div class="third-section-website-support__card">
                <div class="box-item__container">
                    <h4 class="third-section-website-support__title fnt-size_24">Стандарт</h4>
                    <p class="third-section-website-support__text text text_white">30 000 руб</p>
                    <div class="box-item__item-container">
                        <img src="icons/white_clock.png" alt="time" height="20px">
                        <p class="third-section-website-support__text-box-item text">10 часов в месяц</p>
                    </div>
                </div>

                <div class="third-section-website-support__card-items-container">
                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Контроль работы сайта</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Восстановление при сбоях</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Защита от вирусов</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Персональный менеджер</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Доработка функционала</p>
                    </div>

                    <form action="" class="complex-web__button-form">
                        <button class="complex-web__button btn-main-red btn-form btn_form-offer btn_website">
                            ЗАКАЗАТЬ
                        </button>
                    </form>
                </div>
            </div>

            <!-- Карточка 3 -->
            <div class="third-section-website-support__card">
                <div class="box-item__container">
                    <h4 class="third-section-website-support__title fnt-size_24">Премиум</h4>
                    <p class="third-section-website-support__text text text_white">60 000 руб</p>
                    <div class="box-item__item-container">
                        <img src="icons/white_clock.png" alt="time" height="20px">
                        <p class="third-section-website-support__text-box-item text">25 часов в месяц</p>
                    </div>
                </div>

                <div class="third-section-website-support__card-items-container">
                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Контроль работы сайта</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Восстановление при сбоях</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Защита от вирусов</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Персональный менеджер</p>
                    </div>

                    <div class="card-items-container__item">
                        <img src="img/point_aim.png" alt="icon" height="20px">
                        <p class="card-items-container__text text_dark">Доработка функционала</p>
                    </div>

                    <form action="" class="complex-web__button-form">
                        <button class="complex-web__button btn-main-red btn-form btn_form-offer btn_website">
                            ЗАКАЗАТЬ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="four-section-website-support">
        <div class="container four-section-website-support__container">
            <div class="four-section-website-support__left-wrapper">
                <h5 class="four-section-website-support__title fnt-size_32-blue">Какие проблемы решаем?</h5>
                <ul class="four-section-website-support__ul-container">
                    <li class="text_dark">Нужно четкое планирование, прозрачность и подробная аналитика работы сайта?</li>
                
                    <li class="text_dark">Не хватает рук для решения всех возникающих проблем и задач?</li>
            
                    <li class="text_dark">Сайт не отображает актуальную информацию, не приносит прибыли и новых клиентов?</li>
                
                    <li class="text_dark">Периодически «всё ломается»?</li>
                </ul>
            </div>

            <div class="four-section-website-support__img-container">
                <img src="img/anger_plus_3.svg" alt="positon__img">
            </div>

            <div class="four-section-website-support__right-wrapper">
                <h5 class="four-section-website-support__title fnt-size_32-blue">Какие проблемы решаем?</h5>
                <ul class="four-section-website-support__ul-container">
                    <li class="text_dark">Нужно четкое планирование, прозрачность и подробная аналитика работы сайта?</li>
                
                    <li class="text_dark">Не хватает рук для решения всех возникающих проблем и задач?</li>
            
                    <li class="text_dark">Сайт не отображает актуальную информацию, не приносит прибыли и новых клиентов?</li>
                
                    <li class="text_dark">Периодически «всё ломается»?</li>
                </ul>
            </div>
        </div>
    </section>

   

    <footer class="footer">
        <div class="container footer-content">

            <div class="footer__left-wrapper">
                <h6 class="footer__title title-footer">КОНТАКТЫ</h6>

                <div class="footer__text-container">
                    <img src="icons/telephone.svg" alt="" class="footer__img-icons">
                    <p class="footer__text text_small">Тел.: +7 (495) 023-87-66</p>
                </div>

                <div class="footer__text-container">
                    <img src="icons/telephone.svg" alt="" class="footer__img-icons">
                    <p class="footer__text text_small">Тел.: +7 (987) 48-48-909</p>
                </div>

                <div class="footer__text-container">
                    <img src="icons/email.svg" alt="" class="footer__img-icons">
                    <p class="footer__text text_small">E-mail: info@gmail.com</p>
                </div>

                <div class="footer__text-container">
                    <img src="icons/geo.svg" alt="" class="footer__img-icons">
                    <p class="footer__text text_small">Адрес: г. Москва, Дербеневская наб., д.7,стр.23, офис 45ИНН / КПП 7709982085 / 772501001</p>
                </div>

            </div>


            <div class="footer__right-wrapper">
                
                <h6 class="footer__title title-footer">Быстрые ссылки</h6>

                <div class="footer-inside__container">
                    <div class="footer-inside__left-wrapper">
                        <p class="footer-inside-text text_very-small">Контекстная реклама</p>
                        <p class="footer-inside-text text_very-small">Реклама Яндекс Директ</p>
                        <p class="footer-inside-text text_very-small">Google реклама</p>
                        <p class="footer-inside-text text_very-small">Аудит сайтов</p>
                        <p class="footer-inside-text text_very-small">Стоимость <br> контекстной рекламы</p>
                        
                    </div>
                    
                    <div class="footer-inside__center-wrapper">
                        <p class="footer-inside-text text_very-small">Стратегия продвижения</p>
                        <p class="footer-inside-text text_very-small">Продвижение сайтов</p>
                        <p class="footer-inside-text text_very-small">Поддержка сайтов</p>
                        <p class="footer-inside-text text_very-small">Создание сайтов</p>
                        <p class="footer-inside-text text_very-small">Сложный сайт</p>
                    </div>

                    
                    <div class="footer-inside__right-wrapper">
                        <p class="footer-inside-text text_very-small">О компании</p>
                        <p class="footer-inside-text text_very-small">Наши работы</p>
                        <p class="footer-inside-text text_very-small">Контакты</p>
                        <p class="footer-inside-text text_very-small">Блог</p>
                        <p class="footer-inside-text text_very-small">Согласие на обработку <br> персональных данных</p>
                    </div>
                </div>

                
            </div>
            
        </div>


        <div class="container footer-description__line">
            <div class="footer__line"></div>
        </div>

        <div class="container footer-description">
            <div class="footer-content__left-wrapper">
                <img src="icons/logo 2.svg" alt="">
                <div class="footer-content__line"></div>
                <p class="footer-text text_very-small">@ 2024-2024 ООО «ПРОФЕССИОНАЛ» <br>
                    Все права защищены.</p>
            </div>

            <div class="footer-content__right-wrapper">
                <a href="#"><img src="icons/google.svg" alt="Google" class="footer__img-icons"></a>
                <a href="#"><img src="icons/telegram.svg" alt="telegram" class="footer__img-icons"></a>
                <a href="#"><img src="icons/instagram.svg" alt="instagram" class="footer__img-icons"></a>
                <a href="#"><img src="icons/whatsapp.svg" alt="whatsapp" class="footer__img-icons"></a>
            </div>

        </div>
    </footer>

<script src="js/bootstrap.bundle.js"></script>
<script src="js/swiper-bundle.min.js"></script>
<script src="/js/jquery-3.7.1.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>