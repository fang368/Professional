<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';
session_start();
$pdo = getPDO();
$errors = [];
$errors_completeSolution =[];
$errors_consultation = [];

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


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['formType']) && $_POST['formType'] === 'contextAdForm') {
        // Обработка формы seoPromotionForm
        $name_context = htmlspecialchars($_POST['name_context']);
        $phone_context = htmlspecialchars($_POST['phone_context']);
        $time_context = htmlspecialchars($_POST['time_context']);

        $formattedPhone = preg_replace('/\D/', '', $phone); // Удаляем все символы, кроме цифр
        $formattedPhone = '+7 ' . substr($formattedPhone, 1, 3) . ' ' . substr($formattedPhone, 4, 3) . ' ' . substr($formattedPhone, 7, 2) . ' ' . substr($formattedPhone, 9, 2);

        // Валидация данных заказа
        if (empty($name_context)) {
            $errors[] = "Имя обязательно для заполнения";
        } 

        if (!empty($phone_context) && !preg_match('/^\+7 \d{3} \d{3} \d{2} \d{2}$/', $formattedPhone)) {
            $errors['phone'] = "Номер телефона должен быть в формате +7 XXX XXX XX XX";
        }


        // Обработка данных заказа
        if (empty($errors)) {
            try {

                $pdo->beginTransaction();

                $sql = "INSERT INTO Orders (user_id, form_type,  phone, time, status, order_date, name) VALUES (:user_id, :form_type, :phone, :time, :status, :order_date, :name)";
                $stmt = $pdo->prepare($sql);
                $userId = $_SESSION['user_id'];
                $formType = 'Контекстная реклама';
                $status = 'В ожидании'; // устанавливаем статус заказа
                $orderDate = date('Y-m-d'); // текущая дата
                $stmt->execute(['user_id' => $userId, 'form_type' => $formType, 'phone' => $phone_context, 'time' => $time_context, 'status' => $status, 'order_date' => $orderDate, 'name' => $name_context]);

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
                 $body = "Заказ на разработку сайта.\nИмя: $name_context\nНомер телефона: $formattedPhone\nВремя звонка: $time_context";
                
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
                $sql = "INSERT INTO Orders (user_id, form_type, phone, question_content, status, order_date, name) VALUES (:user_id, :form_type, :phone_consultation, :textarea_consultation, :status, :order_date, :name)";
                $stmt = $pdo->prepare($sql);
                $userId = $_SESSION['user_id'];
                $formType = 'Консультация';
                $status = 'В ожидании';
                $orderDate = date('Y-m-d');
                $stmt->execute([
                    'user_id' => $userId, 
                    'form_type' => $formType,
                    'phone_consultation' => $formattedPhoneConsultation,
                    'textarea_consultation' => $textarea_consultation,
                    'status' => $status,
                    'order_date' => $orderDate,
                    'name' => $name_consultation
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
    
    <section class="main-section__adversting">
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
                            <path fill-rule="evenodd" d="M1 2.5A1.5 1.5 0 0 1 2.5 1h1A1.5 1.5 0 0 1 5 2.5h4.134a1 1 0 1 1 0 1h-2.01q.269.27.484.605C8.246 5.097 8.5 6.459 8.5 8c0 1.993.257 3.092.713 3.7.356.476.895.721 1.787.784A1.5 1.5 0 0 1 12.5 11h1a1.5  1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5H6.866a1 1 0 1 1 0-1h1.711a3 3 0 0 1-.165-.2C7.743 11.407 7.5 10.007 7.5 8c0-1.46-.246-2.597-.733-3.355-.39-.605-.952-1-1.767-1.112A1.5 1.5 0 0 1 3.5 5h-1A1.5 1.5 0 0 1 1 3.5zM2.5 2a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm10 10a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z"/>
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
                        Сколько стоит контекстная реклама
                    </h3>
                </div>
                <p class="complete-solution__text text text_white-bold text_dark-bold">
                    Рассчитаем максимально эффективный <br> бюджет вашей рекламной кампании
                </p>

                <!-- Форма -->

                <form action="advertising.php" class="complete-solution__form" method="POST">
                <?php if (!empty($errors)) { ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error) { ?>
                            <p><?php echo $error; ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>
                <input type="hidden" name="formType" value="contextAdForm">

                    <div class="input-container-item">
                        <img src="icons/person-fill.svg" alt="" class="input-container-item__img">
                        <input type="text" placeholder="Имя" id="name_context" name="name_context" class="complete-solution__input" required>
                    </div>

                    <div class="input-container-item">
                        <img src="icons/telephone-fill.svg" alt="" class="input-container-item__img">
                        <input type="tel" placeholder="Телефон*" id="phone_context" name="phone_context" class="complete-solution__input phone-input" required>
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
                <img src="img/context_price_01.png" alt="">
            </div>
        </div>
        
    </section>


    <section class="second-section second-section__adversting">

        <div class="button__up-container">
            <button type="button" class="up-btn">Вверх</button>
        </div>

        <div class="container second-section-adversting__container">
            <div class="second-section-adversting__wrapper-first">
                <h3 class="second-section__adversting__subtitle title-mini-blue">Стоимость контекстной рекламы</h3>
                <p class="second-section__adversting__text text_dark text_context">Она является динамическим показателем. Это значит, что устойчивой суммы нет, она то уменьшается, то увеличивается в зависимости от внешних и внутренних факторов.</p>
            </div>

            <div class="second-section-adversting__second-container">
                <div class="second-section-adversting__left-wrapper">
                    <img src="img/context_price_02.png" alt="Изображение компьютера" >
                </div>

                <div class="second-section-adversting__right-wrapper">
                    <p class="second-section-adversting__text text_bold">Зависит от:</p>
                    <div class="second-section-adversting__item">
                        <img src="icons/ico_context_1 1.svg" alt="context_icon">
                        <p class="second-section-adversting__text text_context-icon">выбранного способа продвижения – через Яндекс.Директ или Google Ads;</p>
                    </div>

                    <div class="second-section-adversting__item">
                        <img src="icons/ico_context_2 1.svg" alt="context_icon">
                        <p class="second-section-adversting__text text_context-icon">специфики бизнеса: особенности ниши, уровень конкуренции, сильные и слабые стороны продукта, УТП;</p>
                    </div>

                    <div class="second-section-adversting__item">
                        <img src="icons/ico_context_3 1.svg" alt="context_icon">
                        <p class="second-section-adversting__text text_context-icon">особенностей составления объявления и многого другого.</p>
                    </div>
                </div>
            </div>

            


            <div class="second-section-adversting__wrapper-first">
                <h3 class="second-section__adversting__subtitle title-mini-blue">Модели оплаты</h3>
                <p class="second-section__adversting__text text_dark text_context">Цена рекламы в интернете частично зависит от способа оплаты:</p>
            </div>


            <div class="second-section-adversting-third__main-container">

                <div class="second-section-adversting__third-container">
                    <div class="second-section-adversting__elipse-wrapper">
                        <div class="second-section-adversting__elipse">
                            <p class="second-section-adversting__text text_elipse">CPC</p>
                        </div>
                    </div>
                    <p class="second-section__adversting-elipse__text text_dark text_context">заказчик оплачивает переходы по рекламной ссылке: чем их больше, тем активнее расходуется бюджет</p>
                </div>

                <div class="second-section-adversting__third-container">
                    <div class="second-section-adversting__elipse-wrapper">
                        <div class="second-section-adversting__elipse">
                            <p class="second-section-adversting__text text_elipse">CPM</p>
                        </div>
                    </div>
                    <p class="second-section__adversting-elipse__text text_dark text_context">фиксированная оплата за 1000 показов рекламных материалов целевой аудитории</p>
                </div>

                <div class="second-section-adversting__third-container">
                    <div class="second-section-adversting__elipse-wrapper">
                        <div class="second-section-adversting__elipse">
                            <p class="second-section-adversting__text text_elipse">CPA</p>
                        </div>
                    </div>
                    <p class="second-section__adversting-elipse__text text_dark text_context">оплата за совершение целевого действия (конверсии): посетитель зарегистрировался, подписался, положил товар в корзину и т.д.</p>
                </div>
            </div>

            <div class="adversting__block-item block_item">
                <div class="about-us-block__img-container">
                    <img src="img/context_price_03.png" alt="Изображение о нас">
                </div>

                <p class="about-us-block-item__text text text_white-bold">
                    Наши специалисты помогут определить наиболее оптимальную модель оплаты с учетом специфики вашего бизнеса. К примеру, для товарной рекламы эффективнее работают CPC и CPA – они «делают» продажи, повышают прибыль. Для рекламных кампаний, где важен охват аудитории, лучше выбрать CPM.
                </p>
            </div>

        </div>
    </section>

    <section class="third-section__adversting">

        <div class="containeспаr">
            <h2 class="third-section-adversting__title dark_blue">Сколько стоит контекстная реклама в Яндексе?</h2>
        </div>

        <div class="container third-section-adversting__container">
            <div class="third-section-adversting__left-wrapper">
                <img src="img/context_price_04.png" alt="Изображение яндекса" class="context-price__img-4">

                <div class="third-section-adversting__box">
                    <p class="third-section-adversting-box__title block_title">Цены кликов</p>
                    <div class="adversting-box__items">
                        <div class="adversting-box__line"></div>
                        <img src="icons/Vectoradversting.svg" alt="Фигура" class="adversting-vector__img">
                    </div>

                    <div class="adversting-box-wrapper">
                        <div class="adversting-box-wrapper__left">
                            <p class="adversting-box-wrapper__text text_white">30 копеек</p>
                            <p class="adversting-box-wrapper__text value_text">Мин. цена клика</p>
                        </div>

                        <div class="adversting-box-wrapper__right">
                            <p class="adversting-box-wrapper__text text_white">Неограничена</p>
                            <p class="adversting-box-wrapper__text value_text">Макс. цена клика</p>
                        </div>
                    </div>
                    
                </div>
            </div>

            <div class="third-section-adversting__right-wrapper">
                <p class="third-section-adversting__text text text_adversting">
                    Для «Директа» работают две модели: CPC (в большинстве случаев) и CPM. Первый вариант обеспечивает таргетированные клики, что при хорошем оффере влечет за собой повышение конверсии. Пользователь по тексту объявления понимает, какой товар или услугу рекламирует компания, поэтому нажимает на него только в том случае, если заинтересован в продукте.
                </p>
                <p class="third-section-adversting__text text text_adversting">
                    Стоимость рекламы в интернете посредством Яндекс.Директ по умолчанию выставляется на минимум. Рекламодатель может самостоятельно изменить значение в соответствии с рекомендациями системы или собственным опытом.
                </p>
                <p class="third-section-adversting__subtitle subtitle_blue-dark">Что нужно знать</p>
                <div class="third-section-adversting-text__container">
                    <div class="third-section-adversting__wrapper">
                        <img src="icons/li_tri.svg" alt="Стрелка">
                        <p class="third-section-adversting__text-item text text_adversting">
                            Стоимость клика определяется по принципу аукциона: кто больше заплатил, того и «показали».
                        </p>
                    </div>

                    <div class="third-section-adversting__wrapper">
                        <img src="icons/li_tri.svg" alt="Стрелка">
                        <p class="third-section-adversting__text-item text text_adversting">
                            Сервис снижает расценки для объявлений, по которым больше переходов
                        </p>
                    </div>

                    <div class="third-section-adversting__wrapper">
                        <img src="icons/li_tri.svg" alt="Стрелка">
                        <p class="third-section-adversting__text-item text text_adversting">
                            Стоимость 1000 показов также устанавливается на аукционе. Только победитель оплачивает не свою ставку, а предыдущего «игрока»+10 копеек (цена шага на торгах).
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <!-- google -->

        <div class="container">
            <h2 class="third-section-adversting__title dark_blue">Сколько стоит реклама в Google Ads?</h2>
        </div>

        <div class="container third-section-adversting__container">
            <div class="third-section-adversting__left-wrapper">
                <p class="third-section-adversting__text text text_adversting">
                    Для рекламы через Google применимы две модели – CPC и CPM. Для некоторых сфер действует CPA (например, если потребителю нужно скачать приложение). Минимальные и максимальные расценки отсутствуют – можно указать любую сумму. 
                </p>
            
                <p class="third-section-adversting__subtitle subtitle_blue-dark">Что нужно знать</p>
                <div class="third-section-adversting-text__container">
                    <div class="third-section-adversting__wrapper">
                        <img src="icons/li_tri.svg" alt="Стрелка">
                        <p class="third-section-adversting__text-item text text_adversting">
                            Цена формируется в результате торгов.
                        </p>
                    </div>

                    <div class="third-section-adversting__wrapper">
                        <img src="icons/li_tri.svg" alt="Стрелка">
                        <p class="third-section-adversting__text-item text text_adversting">
                            Google учитывает рейтинг объявлений – совокупность ставки, качества рекламы, а также текущей или возможной кликабельности (максимальный рейтинг – 10).
                        </p>
                    </div>

                    <div class="third-section-adversting__wrapper">
                        <img src="icons/li_tri.svg" alt="Стрелка">
                        <p class="third-section-adversting__text-item text text_adversting">
                            Контекстная реклама обойдется дешевле, если уровень конкуренции невысок.
                        </p>
                    </div>

                    <div class="third-section-adversting__wrapper">
                        <img src="icons/li_tri.svg" alt="Стрелка">
                        <p class="third-section-adversting__text-item text text_adversting">
                            Google сравнивает между собой разные способы оплаты (например, клики и показы) за одно и то же место, и победителем назначает того рекламодателя, кто принесет сервису большую прибыль.
                        </p>
                    </div>
                </div>

            </div>

            <div class="third-section-adversting__right-wrapper">
                <img src="img/context_price_05.png" alt="Изображение яндекса" class="context-price__img-4">
            </div>
        </div>
    </section>


    <!-- Модальное окно контекстная реклама -->
    <div class="container modal__container">
    <div id="contextAdModal-adversting" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
      
                <h2 class="modal-header">Контекстная реклама</h2>

            <form action="advertising.php" method="POST" class="modal-form">
            <input type="hidden" name="formType" value="contextAdForm">
                <div class="mb-3">
                    <input type="text" id="name_context" name="name_context" class="form-control input_modal" placeholder="Имя" required>
                </div>
                <div class="mb-3">
                    <input type="tel" id="phone_context" name="phone_context" class="form-control input_modal phone-input" placeholder="Телефон" required>
                </div>
                <div class="mb-3">
                    <input type="text" id="time_context" name="time_context" class="form-control input_modal" placeholder="Время звонка" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" id="consent_context" name="consent" class="form-check-input" required>
                    <label for="consent_context" class="form-check-label">Я согласен на обработку персональных данных</label>
                </div>
                <button type="submit" class="btn-main-red">Перезвонить мне</button>
            </form>
        </div>
    </div>
</div>

    <section class="four-section-adversting">
        <div class="container">
            <h2 class="third-section-adversting__title dark_blue title_blue">Тарифы на настройку и ведение рекламных кампаний</h2>
        </div>
        <div class="container four-section-adversting__container">

            <div class="adversting-card card_item">
                <h3 class="four-section-adversting__title white_blue-title">Стандарт</h3>
                <div class="minin-box__adversting-card">
                    <img src="icons/wallet_blue 1.svg" alt="Цена">
                    <p class="four-section-advesrting__text text_price">Бюджет от 50 000 ₽</p>
                </div>
                <p class="four-section-adversting-card__text text_smal-dark">Сайт услуг, монотоварный сайт, среднеконкурентная ниша</p>
                <div class="adversting-card__item">
                    <img src="icons/clock-fill.svg" alt="Цена">
                    <p class="four-section-advesrting__text fnt-size_18">Настройка от 3 дней</p>
                </div>
                <div class="adversting-card-description__container">
                    <p class="four-section-advesrting__text fnt-size_18">В тариф включено:</p>
                    <p class="four-section-adversting__text text_dark">
                        Регистрация в сервисах Яндекса, настройка Яндекс Метрики, установка счётчика на сайт
                    </p>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Поиск</p>
                        <p class="four-section-adversting__text text_dark">До 700 ключевых запросов</p>
                        <p class="four-section-adversting__text text_dark">До 150 объявлений</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">РСЯ</p>
                        <p class="four-section-adversting__text text_dark">текстово-графические баннеры</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Ретаргетинг</p>
                        <p class="four-section-adversting__text text_dark">текстово-графические баннеры</p>
                    </div>
                </div>

                <div class="adversting__eyelash">
                    <p class="adversting-eyelash__text text_blue-white">20 000 ₽</p>
                    <button class="complete-solution__btn btn-main-red context-btn-adversting">
                        ЗАКАЗАТЬ
                    </button>
                </div>
            </div>

            <!-- 2 карточка -->

            <div class="adversting-card card_item">
                <h3 class="four-section-adversting__title white_blue-title">Интернет-магазин</h3>
                <div class="minin-box__adversting-card">
                    <img src="icons/wallet_blue 1.svg" alt="Цена">
                    <p class="four-section-advesrting__text text_price">Бюджет от 100 000 ₽</p>
                </div>
                <p class="four-section-adversting-card__text text_smal-dark">Интернет-магазин, сайт услуг, высококонкурентная ниша</p>
                <div class="adversting-card__item">
                    <img src="icons/clock-fill.svg" alt="Цена">
                    <p class="four-section-advesrting__text fnt-size_18">Настройка от 5 дней</p>
                </div>
                <div class="adversting-card-description__container">
                    <p class="four-section-advesrting__text fnt-size_18">В тариф включено:</p>
                    <p class="four-section-adversting__text text_dark">
                        Регистрация в сервисах Яндекса, настройка Яндекс Метрики, установка счётчика на сайт
                    </p>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Поиск</p>
                        <p class="four-section-adversting__text text_dark">До 3000 ключевых запросов</p>
                        <p class="four-section-adversting__text text_dark">До 250 объявлений</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">РСЯ</p>
                        <p class="four-section-adversting__text text_dark">текстово-графические баннеры</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Ретаргетинг</p>
                        <p class="four-section-adversting__text text_dark">текстово-графические баннеры</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Товарные объявления</p>
                        <p class="four-section-adversting__text text_dark">на основе фида данных</p>
                    </div>

                </div>

                <div class="adversting__eyelash">
                    <p class="adversting-eyelash__text text_blue-white">25 000 ₽</p>
                    <button class="complete-solution__btn btn-main-red context-btn-adversting ">
                        ЗАКАЗАТЬ
                    </button>
                </div>
            </div>

            <!-- 3 карточка -->

            <div class="adversting-card card_item">
                <h3 class="four-section-adversting__title white_blue-title">Эксперт</h3>
                <div class="minin-box__adversting-card">
                    <img src="icons/wallet_blue 1.svg" alt="Цена">
                    <p class="four-section-advesrting__text text_price">Бюджет от 150 000 ₽</p>
                </div>
                <p class="four-section-adversting-card__text text_smal-dark">Любой тип сайта, высококонкруентная ниша</p>
                <div class="adversting-card__item">
                    <img src="icons/clock-fill.svg" alt="Цена">
                    <p class="four-section-advesrting__text fnt-size_18">Настройка от 7 дней</p>
                </div>
                <div class="adversting-card-description__container">
                    <p class="four-section-advesrting__text fnt-size_18">В тариф включено:</p>
                    <p class="four-section-adversting__text text_dark">
                        Регистрация в сервисах Яндекса, настройка Яндекс Метрики, установка счётчика на сайт
                    </p>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Поиск</p>
                        <p class="four-section-adversting__text text_dark">До 3500 ключевых запросов</p>
                        <p class="four-section-adversting__text text_dark">До 300 объявлений</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">РСЯ</p>
                        <p class="four-section-adversting__text text_dark">текстово-графические баннеры</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Ретаргетинг</p>
                        <p class="four-section-adversting__text text_dark">текстово-графические баннеры</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Товарные объявления</p>
                        <p class="four-section-adversting__text text_dark">на основе фида данных</p>
                    </div>
                </div>

                <div class="adversting__eyelash">
                    <p class="adversting-eyelash__text text_blue-white">35 000 ₽</p>
                    <button class="complete-solution__btn btn-main-red context-btn-adversting ">
                        ЗАКАЗАТЬ
                    </button>
                </div>
            </div>

            <!-- 4 карточка -->

            <div class="adversting-card card_item">
                <h3 class="four-section-adversting__title white_blue-title">Премиум</h3>
                <div class="minin-box__adversting-card">
                    <img src="icons/wallet_blue 1.svg" alt="Цена">
                    <p class="four-section-advesrting__text text_price">Бюджет от 200 000 ₽</p>
                </div>
                <p class="four-section-adversting-card__text text_smal-dark">Любой тип сайта, индивидуальные проекты</p>
                <div class="adversting-card__item">
                    <img src="icons/clock-fill.svg" alt="Цена">
                    <p class="four-section-advesrting__text fnt-size_18">Настройка от 7 дней</p>
                </div>
                <div class="adversting-card-description__container">
                    <p class="four-section-advesrting__text fnt-size_18">В тариф включено:</p>
                    <p class="four-section-adversting__text text_dark">
                        Регистрация в сервисах Яндекса, настройка Яндекс Метрики, установка счётчика на сайт
                    </p>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Поиск</p>
                        <p class="four-section-adversting__text text_dark">До 5000 ключевых запросов</p>
                        <p class="four-section-adversting__text text_dark">До 450 объявлений</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">РСЯ</p>
                        <p class="four-section-adversting__text text_dark">текстово-графические баннеры</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Ретаргетинг</p>
                        <p class="four-section-adversting__text text_dark">текстово-графические баннеры</p>
                    </div>

                    <div class="adversting-item__container">
                        <p class="four-section-advesrting__text fnt-size_18">Ретаргетинг</p>
                        <p class="four-section-adversting__text text_dark">текстово-графические баннеры</p>
                    </div>

                </div>
                <div class="adversting__eyelash">
                    <p class="adversting-eyelash__text text_blue-white">50 000 ₽</p>
                    <button class="complete-solution__btn btn-main-red context-btn-adversting ">
                        ЗАКАЗАТЬ
                    </button>
                </div>
            </div>
        </div>
    </section>


    <section class="five-section__adversting">
        <div class="container five-section__adversting-title-container">
            <h2 class="five-section-adversting__title title_ft-40-white">Что вы получите от сотрудничества с нами?</h2>
            <p class="five-section-adversting__subtitle subtitle_ft32">Заказывая рекламу в интернете по выгодной цене в нашем агентстве, вы:</p>
        </div>
        <div class="container five-section-adversting__container">
            <div class="five-section-adversting__box">
                <div class="five-section-adversting-box__item">
                    <img src="icons/check2.svg" alt="галочка">
                    <p class="five-section-adversting__text text">
                        Узнаете причины неэффективности уже настроенных кампаний, а также утечки денег из бюджета;
                    </p>
                </div>

                <div class="five-section-adversting-box__item">
                    <img src="icons/check2.svg" alt="галочка">
                    <p class="five-section-adversting__text text">Снизите стоимость клика или показа;</p>
                </div>

                <div class="five-section-adversting-box__item">
                    <img src="icons/check2.svg" alt="галочка">
                    <p class="five-section-adversting__text text">Получите рост числа посетителей, звонков, обращений;</p>
                </div>
                
                <div class="five-section-adversting-box__item">
                    <img src="icons/check2.svg" alt="галочка">
                    <p class="five-section-adversting__text text">Сможете привлечь новых клиентов уже через 2-3 дня с начала нашего сотрудничества;</p>
                </div>

                <div class="five-section-adversting-box__item">
                    <img src="icons/check2.svg" alt="галочка">
                    <p class="five-section-adversting__text text">Получите юридические и финансовые гарантии результата;</p>
                </div>

                <div class="five-section-adversting-box__item">
                    <img src="icons/check2.svg" alt="галочка">
                    <p class="five-section-adversting__text text">
                        Сможете в короткие сроки донести информацию об акциях, скидках до максимального количества заинтересованных пользователей;
                    </p>
                </div>

                <div class="five-section-adversting-box__item">
                    <img src="icons/check2.svg" alt="галочка">
                    <p class="five-section-adversting__text text">
                        Улучшите узнаваемость и репутацию своей компании, бренда, торговой марки.
                    </p>
                </div>
            </div>
            <div class="five-section-adversting__right-wrapper">
                <img src="/img/context_price_10.png" alt="">
            </div>
        </div>
    </section>

    <section class="five-section">
        <!-- основной контейнер -->
        <div class="container five-section__form-consultation">
            <!-- Левая обертка -->
            <div class="form-consultation__left-wrapper">
                <h4 class="form-consultation__title title-mini-blue title_dark">Нужна <br> консультация?</h4>
                <p class="form-consultation__text text_dark-color">Не знаете, какой вариант продвижения <br> подходит вашему бизнесу?</p>
            </div>

            <!-- правая обертка -->
            <div class="form-consultation__right-wrapper">

                <!-- Форма -->
                <form action="advertising.php" method="POST">
                <?php if (!empty($errors_consultation)) { ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors_consultation as $error) { ?>
                            <p><?php echo $error; ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>
                <input type="hidden" name="formType" value="consultationForm">
                
                    <div class="input-container-item__consultation">
                        <input type="text" placeholder="Имя"  name="name_consultation" class="form-consultation__input" required>
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