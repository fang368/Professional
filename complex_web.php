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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['formType']) && $_POST['formType'] === 'сomplexWebForm') {
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

        // Обработка данных заказа
        if (empty($errors)) {
            try {

                $pdo->beginTransaction();
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

                 $pdo->commit();

                header("Location: orders.php");
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = "Ошибка при выполнении запроса: " . $e->getMessage();
            }
        }
    }elseif ($_POST['formType'] === 'consultationForm') {
        // Получаем данные из формы консультации
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

        if (!$consent_consultation) {
            $errors_consultation['consent_consultation'] = "Вы должны согласиться на обработку персональных данных";
        }

        // Обработка данных
        if (empty($errors_consultation)) {
            try {
                $pdo->beginTransaction();

                $sql = "INSERT INTO Orders (user_id, form_type, name, phone, status, order_date) VALUES (:user_id, :form_type, :name_consultation, :phone_consultation, :status, :order_date)";
                $stmt = $pdo->prepare($sql);
                $userId = $_SESSION['user_id'];
                $formType = 'Консультация';
                $status = 'В ожидании';
                $orderDate = date('Y-m-d');
                $stmt->execute([
                    'user_id' => $userId, // Добавили привязку к переменной :user_id
                    'form_type' => $formType,
                    'name_consultation' => $name_consultation,
                    'phone_consultation' => $formattedPhoneConsultation,
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
                 $body = "Заказ на разработку сайта.\nИмя: $name_consultation\nНомер телефона: $formattedPhoneConsultation\n";
                
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
                        Создание дорогих <br> и сложных сайтов
                    </h3>
                </div>
                <p class="complete-solution__text text text_white-bold text_dark-bold">
                    Создаем и запускаем онлайн порталы, <br> веб-сервисы и маркетплейсы под ключ
                </p>

                <!-- Форма -->

                <form action="complex_web.php" method="POST" class="complete-solution__form">
                <?php if (!empty($errors)) { ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error) { ?>
                            <p><?php echo $error; ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>
                <input type="hidden" name="formType" value="сomplexWebForm">
                    <div class="input-container-item">
                        <img src="icons/person-fill.svg" alt="" class="input-container-item__img">
                        <input type="text" placeholder="Имя" name="name" class="complete-solution__input" required>
                    </div>

                    <div class="input-container-item">
                        <img src="icons/telephone-fill.svg" alt="" class="input-container-item__img">
                        <input type="tel" placeholder="Телефон*" name="phone" class="complete-solution__input phone-input" required>
                    </div>

                    <div class="checkbox">
                        <input type="checkbox" id="checkbox_2" name="consent" class="checkbox__input">
                        <label for="checkbox_2" class="checkbox__label checkbox__label_dark">Я согласен на обработку персн. данных</label>
                    </div>

                    <button class="complete-solution__btn btn-main-red">
                        Получить предложение
                    </button>

                </form>
            </div>
            <!-- правая обертка -->
            <div class="complete-solution__right-wrapper">
                <img src="img/top_26.png" alt="">
            </div>
        </div>
    </section>

    <section class="second-section second-section-complex-web">
        <div class="container second-section-complex-web-title__container">
            <p class="second-section-complex-web__title title title_ft-40-white">Какие сайты мы создаем?</p>
            <p class="second-section-complex-web__text text">
                Понятно, что любая студия может создать дорогой сайт, запросто назвав космический гонорар за свои услуги. Мы имеем в виду совершенно иное, говоря о дорогих сайтах мы подразумеваем именно создание сложных сайтов, например таких как:
            </p>

        </div>

        <div class="button__up-container">
            <button type="button" class="up-btn">Вверх</button>
        </div>

        <div class="container second-section-complex-web__container">

            <div class="complex-web__items-box">
                <div class="complex-web-item">
                    <img src="icons/26_icon_0.svg" alt="first-icon" height="58px" width="58px">
                    <p class="complex-web-item__text text">Социальные сети</p>
                </div>

                <div class="complex-web-item">
                    <img src="icons/26_icon_2.svg" alt="first-icon" height="58px" width="58px">
                    <p class="complex-web-item__text text">Инфо-порталы</p>
                </div>

                <div class="complex-web-item">
                    <img src="icons/26_icon_3.svg" alt="first-icon" height="58px" width="58px">
                    <p class="complex-web-item__text text">Почтовые службы</p>
                </div>

                <div class="complex-web-item">
                    <img src="icons/26_icon_4.svg" alt="first-icon" height="58px" width="58px">
                    <p class="complex-web-item__text text">Онлайн игры</p>
                </div>

                <div class="complex-web-item">
                    <img src="icons/26_icon_5.svg" alt="first-icon" height="58px" width="58px">
                    <p class="complex-web-item__text text">Онлайн сервисы</p>
                </div>
            </div>

            <div class="second-section__complex-web-box box-complex-web">
                <div class="strategy-box__left-wrapper">
                    <img src="img/26_block_3 1.png" alt="">
                </div>

                <div class="strategy-box__right-wrapper">
                    <p class="complex-web-box__text text text_white-bold">
                        Сюда же можно отнести и корпоративные ресурсы для больших компаний, в которые встраивается много сервисных решений, и которые имеют двухуровневую структуру, внешняя часть их предназначена исключительно для посетителей, а внутренняя представляет собой рабочую среду для персонала и доступ в нее дается индивидуальный, не говоря уже о том, что он также может быть и многоуровневым.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3 секция -->

    <section class="third-section-complex-web">

        <div class="container third-section-complex-web__title">
            <h3 class="third-section-complex-web__title title-mini-blue">
                Почему стоит обратиться именно к нам?
            </h3>
        </div>
        <div class="container third-section-complex-web__container">
            <div class="third-section-complex-web__box-items">
                <div class="complex-web__box-items-left-wrapper">
                    <img src="img/26_block_4_1.png" alt="">
                </div>
                <div class="complex-web__box-items-right-wrapper">
                    <h4 class="complex-web-box__title title title_mini">Квалифицированные специалисты</h4>
                    <p class="complex-web-box__text text text_dark">
                        Для того чтобы создать сложный сайт нужна опытная и креативная команда, которая будет работать только над вашим проектом, не размениваясь на другие. Мы можем предоставить вам профессионалов как широкого, так и самого узкого профиля, у нас работают только лучшие люди, начиная с менеджеров по работе с клиентами и заканчивая программистами и дизайнерами – все это высококлассные специалисты, работать с которыми одно удовольствие.
                    </p>
                </div>
            </div>

            <div class="third-section-complex-web__box-items">
                <div class="complex-web__box-items-left-wrapper">
                    <img src="img/26_block_4_2 1.png" alt="">
                </div>
                <div class="complex-web__box-items-right-wrapper">
                    <h4 class="complex-web-box__title title title_mini">Решаем любые задачи</h4>
                    <p class="complex-web-box__text text text_dark">
                        Мы не боимся трудных задач, мы их любим, потому что они открывают возможность показать на что мы способны.
                    </p>
                </div>
            </div>

            <div class="third-section-complex-web__box-items">
                <div class="complex-web__box-items-left-wrapper">
                    <img src="img/26_block_4_3 gg1.png" alt="">
                </div>
                <div class="complex-web__box-items-right-wrapper">
                    <h4 class="complex-web-box__title title title_mini">Максимально эффективная работа над проектом</h4>
                    <p class="complex-web-box__text text text_dark">
                        После заключения контракта работа начинается одновременно с нескольких концов, потому что клиента обычно интересуют сжатые сроки. Но нестыковок и недопонимания опасаться не стоит, вся работа строго структурируется и на каждом этапе она проверяется менеджером проекта на предмет соответствия изначальному плану.
                    </p>
                </div>
            </div>

        </div>
    </section>

    <section class="four-section-complex-web">
        <div class="container four-section-complex-web__title-container">
            <h3 class="four-section-complex-web__title title-mini-blue">
                Наше агентство гарантирует
            </h3>
        </div>
        
        <div class="container four-section-complex-web__container">
            <div class="four-section-complex-web__left-wrapper">

                <div class="four-section-complex-web__item">
                    <div class="four-section-complex-web__inside-item">
                        <img src="icons/26_icon_2_1.svg" alt="icon" height="32px">
                        <div class="test-12">
                            <h4 class="four-section-complex-web__title title_mini">Правильный выбор платформы и технологии</h4>
                            <p class="four-section-complex-web__text text text_dark">
                                Это важно, так как надо оставлять пространство для маневра – то что устраивает сегодня может уже не подходить завтра, поэтому мы всегда выбираем платформу и технологию с учетом перспектив ресурса и планов его владельцев.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="four-section-complex-web__item">
                    <div class="four-section-complex-web__inside-item">
                        <img src="icons/26_icon_2_2.svg" alt="icon" height="32px">
                        <div class="test-12">
                            <h4 class="four-section-complex-web__title title_mini">Разработку максимально удобного и интуитивно понятного интерфейса</h4>
                            <p class="four-section-complex-web__text text text_dark">
                                Сайту мало быть просто красивым, через секунду после попадания на него человеку уже должно быть интуитивно понятно, куда кликнуть, чтобы получить то, что он ищет.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="four-section-complex-web__item">
                    <div class="four-section-complex-web__inside-item">
                        <img src="icons/26_icon_2_4.svg" alt="icon" height="32px">
                        <div class="test-12">
                            <h4 class="four-section-complex-web__title title_mini">Гибкость</h4>
                            <p class="four-section-complex-web__text text text_dark">
                                В разных структурах мы можем использовать типовые элементы, а если они не подойдут, то для нас не проблема придумать и создать нужный инструмент с нуля.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="four-section-complex-web__right-wrapper">

                <div class="four-section-complex-web__item">
                    <div class="four-section-complex-web__inside-item">
                        <img src="icons/26_icon_2_5.svg" alt="icon" height="32px">
                        <div class="test-12">
                            <h4 class="four-section-complex-web__title title_mini">Индивидуальный подход</h4>
                            <p class="four-section-complex-web__text text text_dark">
                                Когда речь заходит о сайтах с высокой нагрузкой без этого обойтись нельзя, иначе конечный результат не устроит ни одну из сторон.
                            </p>
                        </div>
                    </div>

                    <div class="four-section-complex-web__inside-item">
                        <img src="icons/26_icon_2_6.svg" alt="icon" height="32px">
                        <div class="test-12">
                            <h4 class="four-section-complex-web__title title_mini">Индивидуальный подход</h4>
                            <p class="four-section-complex-web__text text text_dark">
                                Когда речь заходит о сайтах с высокой нагрузкой без этого обойтись нельзя, иначе конечный результат не устроит ни одну из сторон.
                            </p>
                        </div>
                    </div>

                    <div class="four-section-complex-web__inside-item">
                        <img src="icons/26_icon_2_7.svg" alt="icon" height="32px">
                        <div class="test-12">
                            <h4 class="four-section-complex-web__title title_mini">Честность</h4>
                            <p class="four-section-complex-web__text text text_dark">
                                Мы никогда не беремся за те проекты, которые нам не по плечу. Правда, таких мы еще не встречали.
                            </p>
                        </div>
                    </div>

                    <div class="four-section-complex-web__inside-item">
                        <img src="icons/26_icon_2_8.svg" alt="icon" height="32px">
                        <div class="test-12">
                            <h4 class="four-section-complex-web__title title_mini">Дальнейшую поддержку</h4>
                            <p class="four-section-complex-web__text text text_dark">
                                Мы не бросаем своих партнеров, вы всегда можете доверить нам техническую поддержку созданного ресурса, и быть уверенным в том, что он будет работать как часы.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="five-section">
        <!-- основной контейнер -->
        <div class="container five-section__form-complex-web form__offer">

            <!-- правая обертка -->
            <div class="form-complex-web__right-wrapper">
                <!-- Форма -->

                <h4 class="five-section__complex-web-title title_mini">Начните с нами, получите индивидуальное предложение!</h4>
                <?php if (!empty($errors_consultation)) { ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors_consultation as $error) { ?>
                            <p><?php echo $error; ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>
                <form action="complex_web.php" method="POST" class="form__container">
                <input type="hidden" name="formType" value="consultationForm">
                    <div class="input-container-item__consultation">
                        <input type="text" placeholder="Имя" name="name_consultation" class="form-consultation__input" required>
                        <input type="tel" placeholder="Телефон*" name="phone_consultation" class="form-consultation__input phone-input" required>
                    </div>

                    <div class="form-checkbox-container">
    
                        <button class="complex-web__button btn-main-red btn-form btn_form-offer">
                            Получить предложение
                        </button>

                        <div class="mb-3 form-check">
                            <input type="checkbox" id="consent" name="consent_consultation" class="form-check-input" required>
                            <label for="consent" class="form-check-label">Я согласен на обработку персональных данных</label>
                        </div>

                    </div>

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