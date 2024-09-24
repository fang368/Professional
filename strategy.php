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
$errors_consultation = [];


if (isset($_POST['formType']) && $_POST['formType'] === 'seoPromotionForm') {
    // Обработка формы seoPromotionForm
    $name_seo = htmlspecialchars($_POST['name_seo']);
    $phone_seo = htmlspecialchars($_POST['phone_seo']);

    $formattedPhone = preg_replace('/\D/', '', $phone_seo); // Удаляем все символы, кроме цифр
    $formattedPhone = '+7 ' . substr($formattedPhone, 1, 3) . ' ' . substr($formattedPhone, 4, 3) . ' ' . substr($formattedPhone, 7, 2) . ' ' . substr($formattedPhone, 9, 2);

    // Валидация данных заказа
    if (empty($name_seo)) {
        $errors[] = "Имя обязательно для заполнения";
    }

    if (!empty($phone_seo) && !preg_match('/^\+7 \d{3} \d{3} \d{2} \d{2}$/', $formattedPhone)) {
        $errors[] = "Номер телефона должен быть в формате +7 XXX XXX XX XX";
    }

    // Обработка данных заказа
    if (empty($errors)) {
        try {

            $pdo->beginTransaction();

            $sql = "INSERT INTO Orders (user_id, form_type, name, phone, status, order_date) VALUES (:user_id, :form_type, :name, :phone, :status, :order_date)";
            $stmt = $pdo->prepare($sql);
            $userId = $_SESSION['user_id'];
            $formType = 'SEO продвижение';
            $status = 'В ожидании'; // устанавливаем статус заказа
            $orderDate = date('Y-m-d'); // текущая дата
            $stmt->execute([
                'user_id' => $userId,
                'form_type' => $formType,
                'name' => $name_seo,
                'phone' => $formattedPhone,
                'status' => $status,
                'order_date' => $orderDate
            ]);

            // Создаем тело письма
            $body = "Заказ на разработку сайта.\nИмя: $name_seo\nНомер телефона: $phone_seo\nВремя звонка: $time";

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
} elseif (isset($_POST['formType']) && $_POST['formType'] === 'consultationForm') {
    // Обработка формы consultationForm
    $name_consultation = htmlspecialchars($_POST['name_consultation']);
    $phone_consultation = htmlspecialchars($_POST['phone_consultation']);
    $textarea_consultation = htmlspecialchars($_POST['textarea_consultation']);
    $consent_consultation = isset($_POST['consent_consultation']) ? true : false;

    // Удаляем все символы, кроме цифр, и форматируем номер телефона
    $formattedPhoneConsultation = preg_replace('/\D/', '', $phone_consultation);
    $formattedPhoneConsultation = '+7 ' . substr($formattedPhoneConsultation, 1, 3) . ' ' . substr($formattedPhoneConsultation, 4, 3) . ' ' . substr($formattedPhoneConsultation, 7, 2) . ' ' . substr($formattedPhoneConsultation, 9, 2);

    if (empty($name_consultation)) {
        $errors_consultation['name_consultation'] = "Имя обязательно для заполнения";
    }

    if (!empty($phone_consultation) && !preg_match('/^\+7 \d{3} \d{3} \d{2} \d{2}$/', $formattedPhoneConsultation)) {
        $errors_consultation['phone_consultation'] = "Номер телефона должен быть в формате +7 XXX XXX XX XX";
    }

    if (empty($textarea_consultation)) {
        $errors_consultation['textarea_consultation'] = "Вы забыли написать ваш вопрос";
    }

    if (!$consent_consultation) {
        $errors_consultation['consent_consultation'] = "Вы должны согласиться на обработку персональных данных";
    }

    // Обработка данных
    if (empty($errors_consultation)) {
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO Orders (user_id, form_type, name, phone, question_content, status, order_date) VALUES (:user_id, :form_type, :name, :phone, :question_content, :status, :order_date)";
            $stmt = $pdo->prepare($sql);
            $userId = $_SESSION['user_id'];
            $formType = 'Консультация';
            $status = 'В ожидании';
            $orderDate = date('Y-m-d');
            $stmt->execute([
                'user_id' => $userId,
                'form_type' => $formType,
                'name' => $name_consultation,
                'phone' => $formattedPhoneConsultation,
                'question_content' => $textarea_consultation,
                'status' => $status,
                'order_date' => $orderDate
            ]);

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
             $body = "Заказ на разработку сайта.\nИмя: $name_consultation\nНомер телефона: $formattedPhoneConsultation\nВремя звонка: $time";
                
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
            $errors_consultation[] = "Ошибка при выполнении запроса: " . $e->getMessage();
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
    
    <section class="main-section__strategy">
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
                    Стратегия продвижения сайтов
                </h3>
            </div>
            <p class="complete-solution__text text text_white-bold text_dark-bold">
                Получите готовый план действий <br> по раскрутке вашего сайта.
            </p>

            <!-- Форма -->
            <form action="strategy.php" method="POST" class="complete-solution__form">
                    <?php if (!empty($errors_consultation)) { ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors_consultation as $error) { ?>
                                <p><?php echo $error; ?></p>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <input type="hidden" name="formType" value="seoPromotionForm">
                    <div class="input-container-item">
                        <img src="icons/person-fill.svg" alt="" class="input-container-item__img">
                        <input type="text" placeholder="Имя" name="name_seo" class="complete-solution__input" required> 
                    </div>

                    <div class="input-container-item">
                        <img src="icons/telephone-fill.svg" alt="" class="input-container-item__img">
                        <input type="tel" placeholder="Телефон*" name="phone_seo" class="complete-solution__input phone-input" required>
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
                <img src="img/strategy_robot.png" alt="">
            </div>
        </div>

    </section>

<!-- 2 секция -->

    <section class="second-section second-section__strategy">

        <div class="button__up-container">
            <button type="button" class="up-btn">Вверх</button>
        </div>

        <div class="container second-section-strategy__container">
            <div class="second-section__strategy-box">
                <div class="strategy-box__left-wrapper">
                    <img src="img/seo_balance.png" alt="">
                </div>

                <div class="strategy-box__right-wrapper">
                    <p class="strategy-box__text text text_white-bold">Если вы имеете некоторые навыки в SEO и желание заниматься раскруткой сайта самостоятельно, мы разработаем для вас план по внутренней и внешней оптимизации ресурса для достижения необходимых результатов. Готовая стратегия продвижения интернет-магазина или любого другого онлайн-проекта включает в себя технические задания и практические рекомендации, следуя которым вы сможете реализовать все этапы продвижения без привлечения сторонних специалистов.
                    </p>
                </div>
            </div>
        </div>
    </section>
 
<!-- 3 секция -->

<section class="third-section__strategy">
    <div class="container third-section-strategy__container">

        <div class="third-section-strategy__left-wrapper">
            <h2 class="third-section-strategy__title-main title-blue strategy_title">Для чего нужна стратегия продвижения в Интернете?</h2>
            <p class="third-section-strategy__text text text_dark">
                Любая стратегия продвижения – это пошаговый план действий, позволяющий привлечь на сайт большое количество посетителей. Причем при грамотной реализации, это будут не абстрактные пользователи, а люди, заинтересованные в товарах или услугах вашей компании
            </p>

            <p class="third-section-strategy__text text text_dark">
                Пользователи вводят определенные запросы – и вместо главной страницы вашего ресурса попадают на раздел с нужными им товарами, что снижает риск потери потенциальных покупателей. SEO стратегия – это инструмент, благодаря которому ваш проект станет эффективным каналом продаж.
            </p>
        </div>

        <div class="third-section-strategy__right-wrapper">
            <img src="img/website_promotion_work.png" alt="max-click">
        </div>
        
    </div>
</section>

<section class="four-section__strategy">
    <div class="container four-section-strategy__title-container">
        <h3 class="four-section-strategy__title-main title-mini-blue">Как мы разрабатываем стратегии продвижения</h3>
        <p class="four-section-strategy__text-main text text_dark-bold">
            Разработка стратегии <span class="span-map">SEO продвижения</span> – это целый комплекс задач, которые мы решаем. В него входит:
        </p>
    </div>

    <div class="container four-section-strategy__container">
        <div class="four-section-strategy__left-wrapper">
                <div class="four-section-strategy__box">
                    <div class="four-section-strategy-box__item">
                        <p class="four-section-strategy-box__text number-text">01</p>
                        <p class="four-section-strategy-text-box text text_dark">
                            Сбор семантического ядра с распределением запросов по посадочным страницам: чем обширнее СЯ, тем больше покупателей удастся привести на сайт.
                        </p>
                    </div>
                </div>

                <div class="four-section-strategy__box">
                    <div class="four-section-strategy-box__item">
                        <p class="four-section-strategy-box__text number-text">02</p>
                        <p class="four-section-strategy-text-box text text_dark">
                            ТЗ по оптимизации структуры, меню и URL страниц.
                        </p>
                    </div>
                </div>

                <div class="four-section-strategy__box">
                    <div class="four-section-strategy-box__item">
                        <p class="four-section-strategy-box__text number-text">03</p>
                        <p class="four-section-strategy-text-box text text_dark">
                            ТЗ по внутренней оптимизации (содержимое тегов title, description, keywords, h1-h6, пагинация, атрибуты alt и title для изображений, правильное формирование заголовков на странице, ТЗ по подготовке контента с целью улучшения качества ответов на запросы пользователей и т.д.) для типовых страниц сайта.
                        </p>
                    </div>
                </div>

                <div class="four-section-strategy__box">
                    <div class="four-section-strategy-box__item">
                        <p class="four-section-strategy-box__text number-text">04</p>
                        <p class="four-section-strategy-text-box text text_dark">
                            Инструкции по улучшению сниппетов в поисковой выдаче.
                        </p>
                    </div>
                </div>


        </div>

        <div class="four-section-strategy__right-wrapper">
            <div class="four-section-strategy__box">
                <div class="four-section-strategy-box__item">
                    <p class="four-section-strategy-box__text number-text">05</p>
                    <p class="four-section-strategy-text-box text text_dark">
                        Инструкции по технической оптимизации сайта с точки зрения SEO (301 редирект, robots.txt, 404 ошибка, скорость загрузки страниц и т.д.).
                    </p>
                </div>
            </div>

            <div class="four-section-strategy__box">
                <div class="four-section-strategy-box__item">
                    <p class="four-section-strategy-box__text number-text">06</p>
                    <p class="four-section-strategy-text-box text text_dark">
                        Инструкции по улучшению качества и удобства сайта с целью увеличения конверсии и конечных продаж.
                    </p>
                </div>
            </div>

            <div class="four-section-strategy__box">
                <div class="four-section-strategy-box__item">
                    <p class="four-section-strategy-box__text number-text">07</p>
                    <p class="four-section-strategy-text-box text text_dark">
                        ТЗ по перелинковке, анализ и инструкции по ее корректировке.
                    </p>
                </div>
            </div>

            <div class="four-section-strategy__box">
                <div class="four-section-strategy-box__item">
                    <p class="four-section-strategy-box__text number-text">08</p>
                    <p class="four-section-strategy-text-box text text_dark">
                        Рекомендации по внешней оптимизации (методика наращивания ссылочной массы).
                    </p>
                </div>
            </div>

            <div class="four-section-strategy__box">
                <div class="four-section-strategy-box__item">
                    <p class="four-section-strategy-box__text number-text">09</p>
                    <p class="four-section-strategy-text-box text text_dark">
                        Рекомендации по улучшению коммерческих и поведенческих факторов ранжирования.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="five-section-strategy">

    <div class="container five-section-strategy__title-container">
        <h2 class="five-section-strategy__title-second title_ft-40-white">Стоимость и сроки разработкиe</h2>
    </div>
    <div class="container five-section-strategy__container">
        <div class="second-section__strategy-box">
            <div class="strategy-box__left-wrapper">
                <p class="strategy-box__text text text_white-bold">
                    Так как каждый проект уникален, то и разработка стратегии продвижения сайта проводится после глубокого изучения специфики конкретного бизнеса, ниши, целевой аудитории. Стоимость мероприятий по разработке зависит от целей и задач, сложности их реализации, а также конкретных результатов, которые необходимо будет достигнуть в ходе реализации предложенной стратегии. Обычно цена услуг начинается от 50 тыс. рублей. Срок от 15 рабочих дней.
                </p>
            </div>

            <div class="strategy-box__right-wrapper">
                <img src="img/22_block_6.png" alt="code" height="240px">
            </div>
        </div>
    </div>
</section>


<section class="five-section">
    <!-- основной контейнер -->
    <div class="container five-section__form-consultation">
        <!-- Левая обертка -->
        <div class="form-consultation__left-wrapper">
            <h4 class="form-consultation__title title-mini-blue title_dark">Начните с нами</h4>
            <p class="form-consultation__text text_dark">
                Оставьте заявку сегодня – и вскоре у вас на руках будет готовый алгоритм действий, с помощью которого можно будет повысить эффективность продаж и добиться значительного роста прибыли.
            </p>
        </div>

        <!-- правая обертка -->
        <div class="form-consultation__right-wrapper">

            <!-- Форма -->
            <form action="strategy.php" method="POST">
            <?php if (!empty($errors_consultation)) { ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors_consultation as $error) { ?>
                        <p><?php echo $error; ?></p>
                    <?php } ?>
                </div>
            <?php } ?>
            <input type="hidden" name="formType" value="consultationForm">

                <div class="input-container-item__consultation">
                    <input type="text" placeholder="Имя" name="name_consultation"  class="form-consultation__input" required>
                    <input type="tel" placeholder="Телефон*" name="phone_consultation" class="form-consultation__input phone-input" required>
                </div>

                <textarea name="textarea_consultation" id="" cols="30" rows="10" placeholder="Напишите ваш вопрос..." class="form-consultation__input-textarea" required></textarea>

                <div class="mb-3 form-check">
                    <input type="checkbox" id="consent" name="consent_consultation" class="form-check-input" required>
                    <label for="consent" class="form-check-label">Я согласен на обработку персональных данных</label>
                </div>

                <button class="consultation__button btn-main-red btn-form">
                    ЗАКАЗАТЬ ОБРАТНЫЙ ЗВОНОК
                </button>

            </form>
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