<?php
require_once 'vendor/connect.php';
require_once 'vendor/helpers.php';
session_start();
$pdo = getPDO();

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
$errors_completeSolution = [];
$errors_consultation = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['formType'])) {
        $formType = $_POST['formType'];

        // Function to format phone number
        function formatPhoneNumber($phone) {
            $formattedPhone = preg_replace('/\D/', '', $phone); 
            return '+7 ' . substr($formattedPhone, 1, 3) . ' ' . substr($formattedPhone, 4, 3) . ' ' . substr($formattedPhone, 7, 2) . ' ' . substr($formattedPhone, 9, 2);
        }

        // Common validation function
        function validatePhoneNumber($phone) {
            $formattedPhone = formatPhoneNumber($phone);
            return preg_match('/^\+7 \d{3} \d{3} \d{2} \d{2}$/', $formattedPhone);
        }

        if ($formType === 'сomplexWebForm') {
            $name = htmlspecialchars($_POST['name']);
            $phone = htmlspecialchars($_POST['phone']);
            $time = htmlspecialchars($_POST['time']);
            $formattedPhone = formatPhoneNumber($phone);

            if (empty($name)) {
                $errors[] = "Имя обязательно для заполнения";
            }

            if (!empty($phone) && !validatePhoneNumber($phone)) {
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

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    $sqlOrder = "INSERT INTO Orders (user_id, form_type, time, status, order_date, phone, name) VALUES (:user_id, :form_type, :time, :status, :order_date, :phone, :name)";
                    $stmtOrder = $pdo->prepare($sqlOrder);
                    $userId = $_SESSION['user_id'];
                    $formType = 'Разработка сайтов';
                    $status = 'В ожидании';
                    $orderDate = date('Y-m-d');
                    $stmtOrder->execute([
                        ':user_id' => $userId,
                        ':form_type' => $formType,
                        ':time' => $time,
                        ':status' => $status,
                        ':order_date' => $orderDate,
                        ':phone' => $formattedPhone,
                        ':name' => $name
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

                    $body = "Заказ на разработку сайта.\nИмя: $name\nНомер телефона: $formattedPhone\nВремя звонка: $time";
                    $subject = "Новый заказ";
                    sendEmail($orderId, $userId, $pdo, $body, $subject, $formType, $status);

                    $pdo->commit();

                    header("Location: orders.php");
                    exit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errors[] = "Ошибка при выполнении запроса: " . $e->getMessage();
                }
            }
        } elseif ($formType === 'contextAdForm') {
            // Обработка формы contextAdForm
            $name_context = htmlspecialchars($_POST['name_context']);
            $phone_context = htmlspecialchars($_POST['phone_context']);
            $time_context = htmlspecialchars($_POST['time_context']);
            $formattedPhone = formatPhoneNumber($phone_context);

            if (empty($name_context)) {
                $errors[] = "Имя обязательно для заполнения";
            }

            if (!empty($phone_context) && !validatePhoneNumber($phone_context)) {
                $errors['phone'] = "Номер телефона должен быть в формате +7 XXX XXX XX XX";
            }

            if (empty($time_context)) {
                $errors[] = "Время звонка обязательно для заполнения";
            } elseif (!preg_match("/^[\d]{2}:[\d]{2}$/", $time_context)) {
                $errors[] = "Некорректный формат времени (ожидается ЧЧ:ММ)";
            }

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    $sql = "INSERT INTO Orders (user_id, form_type, time, status, order_date, phone, name) VALUES (:user_id, :form_type, :time, :status, :order_date, :phone, :name)";
                    $stmt = $pdo->prepare($sql);
                    $userId = $_SESSION['user_id'];
                    $formType = 'Контекстная реклама';
                    $status = 'В ожидании';
                    $orderDate = date('Y-m-d');
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':form_type' => $formType,
                        ':time' => $time_context,
                        ':status' => $status,
                        ':order_date' => $orderDate,
                        ':phone' => $formattedPhone,
                        ':name' => $name_context
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

                    $orderId = $pdo->lastInsertId();
                    $body = "Заказ на контекстную рекламу.\nИмя: $name_context\nНомер телефона: $formattedPhone\nВремя звонка: $time_context";
                    $subject = "Новый заказ";
                    sendEmail($orderId, $userId, $pdo, $body, $subject, $formType, $status);

                    $pdo->commit();

                    header("Location: orders.php");
                    exit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errors[] = "Ошибка при выполнении запроса: " . $e->getMessage();
                }
            }
        } elseif ($formType === 'seoPromotionForm') {
            // Обработка формы seoPromotionForm
            $name_seo = htmlspecialchars($_POST['name_seo']);
            $phone_seo = htmlspecialchars($_POST['phone_seo']);
            $time_seo = htmlspecialchars($_POST['time_seo']);
            $formattedPhone = formatPhoneNumber($phone_seo);

            if (empty($name_seo)) {
                $errors[] = "Имя обязательно для заполнения";
            }

            if (!empty($phone_seo) && !validatePhoneNumber($phone_seo)) {
                $errors['phone'] = "Номер телефона должен быть в формате +7 XXX XXX XX XX";
            }

            if (empty($time_seo)) {
                $errors[] = "Время звонка обязательно для заполнения";
            } elseif (!preg_match("/^[\d]{2}:[\d]{2}$/", $time_seo)) {
                $errors[] = "Некорректный формат времени (ожидается ЧЧ:ММ)";
            }

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    $sql = "INSERT INTO Orders (user_id, form_type, time, status, order_date, phone, name) VALUES (:user_id, :form_type, :time, :status, :order_date, :phone, :name)";
                    $stmt = $pdo->prepare($sql);
                    $userId = $_SESSION['user_id'];
                    $formType = 'SEO-продвижение';
                    $status = 'В ожидании';
                    $orderDate = date('Y-m-d');
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':form_type' => $formType,
                        ':time' => $time_seo,
                        ':status' => $status,
                        ':order_date' => $orderDate,
                        ':phone' => $formattedPhone,
                        ':name' => $name_seo
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

                    $orderId = $pdo->lastInsertId();
                    $body = "Заказ на SEO-продвижение.\nИмя: $name_seo\nНомер телефона: $formattedPhone\nВремя звонка: $time_seo";
                    $subject = "Новый заказ";
                    sendEmail($orderId, $userId, $pdo, $body, $subject, $formType, $status);

                    $pdo->commit();

                    header("Location: orders.php");
                    exit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errors[] = "Ошибка при выполнении запроса: " . $e->getMessage();
                }
            }
        } elseif ($formType === 'completeSolutionForm') {
            // Обработка формы completeSolutionForm
            $name_solution = htmlspecialchars($_POST['complete-solution__name']);
            $phone_solution = htmlspecialchars($_POST['complete-solution__phone']);
            $agree_solution = isset($_POST['consent_comleteSolution']) ? true : false;
            $formattedPhone = formatPhoneNumber($phone_solution);

            if (empty($name_solution)) {
                $errors_completeSolution['complete-solution__name'] = "Имя обязательно для заполнения";
            }

            if (!empty($phone_solution) && !validatePhoneNumber($phone_solution)) {
                $errors_completeSolution['complete-solution__phone'] = "Номер телефона должен быть в формате +7 XXX XXX XX XX";
            }

            if (!$agree_solution) {
                $errors_completeSolution['consent_comleteSolution'] = "Вы должны согласиться на обработку персональных данных";
            }

            if (empty($errors_completeSolution)) {
                try {
                    $pdo->beginTransaction();

                    $sql = "INSERT INTO Orders (user_id, form_type, phone, status, order_date, name) VALUES (:user_id, :form_type, :phone, :status, :order_date, :name)";
                    $stmt = $pdo->prepare($sql);
                    $userId = $_SESSION['user_id'];
                    $formType = 'Комплексное решение';
                    $status = 'В ожидании';
                    $orderDate = date('Y-m-d');
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':form_type' => $formType,
                        ':phone' => $formattedPhone,
                        ':status' => $status,
                        ':order_date' => $orderDate,
                        ':name' => $name_solution
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

                    $orderId = $pdo->lastInsertId();
                    $body = "Заказ на комплексное решение.\nИмя: $name_solution\nНомер телефона: $formattedPhone";
                    $subject = "Новый заказ";
                    sendEmail($orderId, $userId, $pdo, $body, $subject, $formType, $status);

                    $pdo->commit();

                    header("Location: orders.php");
                    exit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errors_completeSolution[] = "Ошибка при выполнении запроса: " . $e->getMessage();
                }
            }
        } elseif ($formType === 'consultationForm') {
            // Обработка формы consultationForm
            $name_consultation = htmlspecialchars($_POST['name_consultation']);
            $phone_consultation = htmlspecialchars($_POST['phone_consultation']);
            $textarea_consultation = htmlspecialchars($_POST['textarea_consultation']);
            $consent_consultation = isset($_POST['consent_consultation']) ? true : false;
            $formattedPhoneConsultation = formatPhoneNumber($phone_consultation);

            if (empty($name_consultation)) {
                $errors_consultation['name_consultation'] = "Имя обязательно для заполнения";
            }

            if (!empty($phone_consultation) && !validatePhoneNumber($phone_consultation)) {
                $errors_consultation['phone_consultation'] = "Номер телефона должен быть в формате +7 XXX XXX XX XX";
            }

            if (empty($textarea_consultation)) {
                $errors_consultation['textarea_consultation'] = "Вы забыли написать ваш вопрос";
            }

            if (!$consent_consultation) {
                $errors_consultation['consent_consultation'] = "Вы должны согласиться на обработку персональных данных";
            }

            if (empty($errors_consultation)) {
                try {
                    $pdo->beginTransaction();

                    $sql = "INSERT INTO Orders (user_id, form_type, phone, question_content, status, order_date, name) VALUES (:user_id, :form_type, :phone, :question_content, :status, :order_date, :name)";
                    $stmt = $pdo->prepare($sql);
                    $userId = $_SESSION['user_id'];
                    $formType = 'Консультация';
                    $status = 'В ожидании';
                    $orderDate = date('Y-m-d');
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':form_type' => $formType,
                        ':phone' => $formattedPhoneConsultation,
                        ':question_content' => $textarea_consultation,
                        ':status' => $status,
                        ':order_date' => $orderDate,
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


                    $body = "Запрос на консультацию.\nИмя: $name_consultation\nНомер телефона: $formattedPhoneConsultation\nВопрос: $textarea_consultation";
                    $subject = "Новый запрос на консультацию";
                    sendEmail($orderId, $userId, $pdo, $body, $subject, $formType, $status);

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
    
    <section class="main-section">
        <div class="scroll-images scroll-image-left" data-speed="4" data-top="120%">
            <img src="icons/el-1.svg" alt="Image 1" class="scroll-image" height="180px">
        </div>
        <div class="scroll-images scroll-image-right image_right2" data-speed="4" data-top="95%" >
            <img src="icons/el-2.png" alt="Image 2" class="scroll-image">
        </div>
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

      
<div class="container modal__container">
    <!-- Модальное окно -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-header">Разработка сайтов</h2>
            <form action="index.php" method="POST" class="modal-form">
            <?php if (!empty($errors)) { ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error) { ?>
                        <p><?php echo $error; ?></p>
                    <?php } ?>
                </div>
            <?php } ?>
            <input type="hidden" name="formType" value="сomplexWebForm">
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

<div class="container modal__container">
    <!-- New modal for Контекстная реклама -->
    <div id="contextAdModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
      
                <h2 class="modal-header">Контекстная реклама</h2>

            <form action="index.php" method="POST" class="modal-form">
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

<div class="container modal__container">
    <!-- New modal for SEO продвижение -->
    <div id="seoPromotionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
          
                <h2 class="modal-header">SEO  продвижение</h2>
     
            <form action="index.php" method="POST" class="modal-form">
            <input type="hidden" name="formType" value="seoPromotionForm">
                <div class="mb-3">
                    <input type="text" id="name_seo" name="name_seo" class="form-control input_modal" placeholder="Имя" required>
                </div>
                <div class="mb-3">
                    <input type="tel" id="phone_seo" name="phone_seo" class="form-control input_modal phone-input" placeholder="Телефон" required>
                </div>
                <div class="mb-3">
                    <input type="text" id="time_seo" name="time_seo" class="form-control input_modal" placeholder="Время звонка" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" id="consent_seo" name="consent" class="form-check-input" required>
                    <label for="consent_seo" class="form-check-label">Я согласен на обработку персональных данных</label>
                </div>
                <button type="submit" class="btn-main-red">Перезвонить мне</button>
            </form>
        </div>
    </div>
</div>


        <div class="container swiper__buttons">
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>


        <div class="container swiper">
            <!-- Additional required wrapper -->
            <div class="swiper-wrapper">
              <!-- Slides -->
              <div class="swiper-slide">
                    <div class="swiper-slide__left-wrapper">
                        <h1 class="slaider-menu__title title-mini-blue title_mini-blue2">Разработка</h1>
                        <h2 class="slaider-menu__title title-blue title-blue2">САЙТОВ</h2>
                        <p class="slaider-menu__text text-dark">Экспертная <br> консультанция в <br> подарок</p>
                            <button type="button" class="slaider-menu btn-main-red">
                                ЗАКАЗАТЬ
                        </form>
                    </div>
                    <div class="swiper-slide__right-wrapper">
                        <img src="img/cover_sites.png" alt="sites" class="swiper-slide__img">
                    </div>
                </div>

              <div class="swiper-slide">
                <div class="swiper-slide__left-wrapper">
                    <h1 class="slaider-menu__title title-mini-blue title_mini-blue2">Контекстная</h1>
                    <h2 class="slaider-menu__title title-blue title-blue2">Реклама</h2>
                    <p class="slaider-menu__text text-dark">Экспертная <br> консультанция в <br> подарок</p>
                    <form action="#">
                        <button class="slaider-menu btn-main-red context-btn-main-red">
                            ЗАКАЗАТЬ
                        </button>
                     </form>
                </div>

                <div class="swiper-slide__right-wrapper">
                    <img src="img/cover_context.png" alt="Context" class="swiper-slide__img">
                </div>

              </div>
              <div class="swiper-slide">
                
                <div class="swiper-slide__left-wrapper">
                    <h1 class="slaider-menu__title title-blue title-blue2">CEO</h1>
                    <h2 class="slaider-menu__title title-mini-blue title_mini-blue2 ">Продвижение</h2>
                    <p class="slaider-menu__text text-dark">Экспертная <br> консультанция в <br> подарок</p>
                    <form action="#">
                        <button class="slaider-menu btn-main-red seo-btn-main-red ">
                            ЗАКАЗАТЬ
                        </button>
                     </form>
                </div>
                <div class="swiper-slide__right-wrapper">
                    <img src="img/cover_seo.png" alt="SEO" class="swiper-slide__img">
                </div>
              </div>
            </div>
    
            <div class="swiper-pagination"></div>
        
    
          </div>
    </section>

    <section class="second-section">

        <div class="button__up-container">
            <button type="button" class="up-btn">Вверх</button>
        </div>

        <div class="scroll-images scroll-image-left" data-speed="4" data-top="240%">
                <img src="icons/el-8.svg" alt="Image 1" class="scroll-image">
        </div>
        <div class="scroll-images scroll-image-right image_right2" data-speed="4" data-top="300%" >
            <img src="icons/el-9.svg" alt="Image 2" class="scroll-image" height="80px">
        </div>
        
        <div class="scroll-images scroll-image-right image_right2" data-speed="4" data-top="500%" >
            <img src="icons/el-2.png" alt="Image 2" class="scroll-image">
        </div>

        <div class="container second-section__title-container">
            <h2 class="second-section-about-us__title title-medium">Рекламное агенство <br> полного цикла</h2>
        </div>

        <div class="container second-section__about-us">
            <div class="about-us__item-sort">
                <div class="about-us__left-wrapper-first">
                        <img src="img/item_sort-1.png" alt="Изображение 1">
                </div>

                <div class="about-us__right-wrapper-first">
                    <h3 class="about-us__subtitle subtitle">СОЗДАНИЕ</h3>
                    <div class="line-sub__item"><img src="/icons/name_right 1.svg" alt="Vector-right" class="line-sub-item__img"></div>
                    <div class="line-sub"></div>
                    <div class="about-us__text text text_bold">Наша студия по разработке сайтов работает на результат: вы ставите задачи, мы решаем их в рамках выделенного бюджета. Подбираем формат, разрабатываем уникальный дизайн, наполнение, функционал. Вы получаете ресурс, отвечающий запросам вашего бизнеса.</div>
                </div>
            </div>

        <div class="about-us__item-sort">
            <div class="about-us__left-wrapper-first">
                <h3 class="about-us__subtitle-second subtitle">ПРОДВИЖЕНИЕ</h3>
                <div class="line-sub__item-second"><img src="/icons/name_left 1.svg" alt="Vector-right" class="line-sub-item__img"></div>
                <div class="line-sub"></div>
                <div class="about-us__text-second text text_bold">Разработка и продвижение сайтов под ключ: все виды услуг для роста трафика, числа заявок и прибыли клиента. Улучшаем узнаваемость <br> и репутацию бренда. Отслеживаем качество лидов, ищем эффективные каналы рекламы с учетом ниши и лимитов бюджета.</div>
            </div>

            <div class="about-us__right-wrapper-first">
                    <img src="img/item_sort-2.png" alt="Изображение 1">
            </div>
        </div>


        <div class="about-us__item-sort">
            <div class="about-us__left-wrapper-first">
                    <img src="img/item_sort-3.png" alt="Изображение 1">
                    
            </div>

            <div class="about-us__right-wrapper-first">
                <h3 class="about-us__subtitle subtitle">КОНТЕКСТНАЯ РЕКЛАМА</h3>
                <div class="line-sub__item"><img src="/icons/name_right 1.svg" alt="Vector-right" class="line-sub-item__img"></div>
                <div class="line-sub"></div>
                <div class="about-us__text text text_bold">Приводим целевой трафик и обеспечиваем взрывной рост продаж. Наше агентство интернет рекламы в Москве гарантирует точную настройку кампаний по ключевым запросам, отобранным вручную, а также грамотный ретаргетинг.</div>
            </div>
        </div>


        <div class="about-us__item-sort">
            <div class="about-us__left-wrapper-first">
                <h3 class="about-us__subtitle-second subtitle">ПОДДЕРЖКА</h3>
                <div class="line-sub__item-second"><img src="/icons/name_left 1.svg" alt="Vector-right" class="line-sub-item__img"></div>
                <div class="line-sub"></div>
                <div class="about-us__text-second text text_bold">Обеспечиваем круглосуточную работоспособность ресурса. Специалисты нашего рекламного агентства решают текущие задачи по обновлению контента, оптимизации, рекламе в интернете для клиентов из Москвы и всей России
                </div>
            </div>

            <div class="about-us__right-wrapper-first">
                    <img src="img/item_sort-4.png" alt="Изображение 1">
            </div>
        </div>

            <div class="abouts-us__wrapper-circle">
                <!-- 1 эллипс -->
                <div class="container-for-circle">
                    <div class="first-cirle">
                        <p class="first-cirle__text text text_circle">14+</p>
                    </div>
                    <div class="wrapper-circle__text text text_circle-small">Лет радуем клиетов <br> показателями продаж</div>
                </div>

                <!-- 2 эллипс -->
                <div class="container-for-circle">
                    <div class="first-cirle">
                        <p class="first-cirle__text text text_circle">100+</p>
                    </div>
                    <div class="wrapper-circle__text text text_circle-small">Клиентов под <br> нашей заботой</div>
                </div>

                <!-- 3 эллипс -->
                <div class="container-for-circle">
                    <div class="first-cirle">
                        <p class="first-cirle__text text text_circle">200+</p>
                    </div>
                    <div class="wrapper-circle__text text text_circle-small">Проектов <br> продвинуто в ТОП</div>
                </div>
            </div>
        </div>
    </section>


    <section class="third-section">
        <!-- Title -->
        <div class="container third-section__title-container">
            <h3 class="third-section__about-trust__subtitle-main subtitle subtitle_blue">
                Почему нам доверяют?
            </h3>
        </div>
        <!-- Основной контейнер -->
        <div class="container third-section__about-trust">
            <!-- левая обертка -->
            <div class="about-trust__left-wrapper">
                <div class="container-for-content__left-wrapper">
                    <h4 class="about-trust__subtitle subtitle_blue subtitle_mini">Глубокое погружение</h4>
                    <p class="about-trust__text text_dark ">Изучаем бизнес клиента. Разрабатываем индивидуальную стратегию рекламы. Вникаем в специфику продукта, целевой аудитории. Наша студия по созданию и продвижению сайтов гарантирует максимальные результаты в короткие сроки.</p>
                </div>
                    <h4 class="about-trust__subtitle subtitle_blue subtitle_mini">Глубокое погружение</h4>
                    <p class="about-trust__text text_dark ">Изучаем бизнес клиента. Разрабатываем индивидуальную стратегию рекламы. Вникаем в специфику продукта, целевой аудитории. Наша студия по созданию и продвижению сайтов гарантирует максимальные результаты в короткие сроки.</p>
            </div>
            <!-- Центральная обертка -->
            <div class="about-trust__center-wrapper">
                <img src="/img/why.png" alt="">
            </div>

            <!-- правая обертка -->
            <div class="about-trust__right-wrapper">
                <div class="container-for-content__right-wrapper">
                    <h4 class="about-trust__subtitle subtitle_blue subtitle_mini title_red">Рост продаж</h4>
                    <p class="about-trust__text text_dark">Только проверенные методики продвижения сайта и реклама с персональной настройкой. Работа на результат. Обращаясь в нашу компанию по созданию и продвижению сайтов, вы получаете гарантии окупаемости.</p>
                </div>
                
                <div class="container-for-content__right-wrapper">
                    <h4 class="about-trust__subtitle subtitle_blue subtitle_mini">Юридические и <br> финансовые гарантии</h4>
                    <p class="about-trust__text text_dark">Заключаем официальный договор с фиксацией условий, обязательств, сроков и стоимости.</p>
                </div>

                <h4 class="about-trust__subtitle subtitle_blue subtitle_mini">Юридические и <br> финансовые гарантии</h4>
                <p class="about-trust__text text_dark">Заключаем официальный договор с фиксацией условий, обязательств, сроков и стоимости.</p>
            </div>
        </div>

        <!-- Title -->
        <div class="container second-section__our-clients__title-container">
            <h3 class="third-section__about-trust__subtitle-main subtitle subtitle_blue subtitle_dark">
                Наши клиенты
            </h3>
        </div>

        <!-- Основной контейнер -->
        <div class="container second-section__our-clients">
            <div class="our-clients__first-wrapper">
                <img src="icons/STROY.svg" alt="СТРОЯ">
                <img src="icons/PRO32.svg" alt="PRO32">
                <img src="icons/DIMAKS.svg" alt="ДИМАКС">
                <img src="icons/WINLEE.svg" alt="WINLEE">
                <img src="icons/ASG.svg" alt="ASG">
            </div>

            <div class="our-clients__second-wrapper">
                <img src="icons/PRIMEBEE.svg" alt="PRIMEEEF">
                <img src="icons/SAVEWOOD.svg" alt="SAVEWOOD">
                <img src="icons/MARSEL.svg" alt="MARSEL">
                <img src="icons/JACMOTORS.svg" alt="JACMOTORS">
                <img src="icons/adidas.svg" alt="ADIDAS">
            </div>
        </div>
    </section>


    <section class="four-section">
        <!-- Основной контейнер -->
        <div class="container four-section__complete-solution">

            <!-- Левая обертка -->
        <div class="complete-solution__left-wrapper">
            <div class="container four-section__subtitle-main">
                <h3 class="third-section__about-trust__subtitle-main title_ft-40-white ">
                    НУЖНО КОМПЛЕКСНОЕ <br> РЕШЕНИЕ?
                </h3>
            </div>
            <p class="complete-solution__text text text_white-bold">
                Наш менеджер свяжется с вами, задаст необходимые <br> вопросы и ответит на ваши
            </p>

            <!-- Форма -->
            <form action="index.php" class="complete-solution__form" method="POST">
                <?php if (!empty($errors_completeSolution)) { ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors_completeSolution as $error) { ?>
                            <p><?php echo $error; ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>
                <input type="hidden" name="formType" value="completeSolutionForm">
                <div class="input-container-item">
                    <img src="icons/person-fill.svg" alt="" class="input-container-item__img">
                    <input type="text" placeholder="Имя" id="name" name="complete-solution__name" class="complete-solution__input" required>
                </div>

                <div class="input-container-item">
                    <img src="icons/telephone-fill.svg" id="phone" alt="" class="input-container-item__img">
                    <input type="tel" placeholder="Телефон*" name="complete-solution__phone" class="complete-solution__input phone-input" required>
                </div>

                <div class="checkbox">
                    <input type="checkbox" id="checkbox_1" name="consent_comleteSolution" class="checkbox__input" required>
                    <label for="checkbox_1" class="checkbox__label">Я согласен на обработку персн. данных</label>
                </div>

                <button class="complete-solution__btn btn-main-red ">
                    ЗАКАЗАТЬ
                </button>
            </form>
        </div>
        <!-- правая обертка -->
        <div class="complete-solution__right-wrapper">
            <img src="img/form-complex.png" alt="">
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
                <form action="index.php" method="POST">
                <?php if (!empty($errors_consultation)) { ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors_consultation as $error) { ?>
                            <p><?php echo $error; ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>
                <input type="hidden" name="formType" value="consultationForm">
                    <div class="input-container-item__consultation">
                        <input type="text" placeholder="Имя" name="name_consultation" class="form-consultation__input" required>
                        <input type="tel" placeholder="Телефон*" name="phone_consultation" class="form-consultation__input phone-input" required>
                    </div>

                    <textarea name="textarea_consultation" id="" cols="30" rows="10" placeholder="Напишите ваш вопрос..."  class="form-consultation__input-textarea" required></textarea>

                    <div class="checkbox checkbox_input">
                        <input type="checkbox" name="consent_consultation" id="checkbox_2" class="checkbox__input" required>
                        <label for="checkbox_2" class="checkbox__label checkbox__label_dark">Я согласен на обработку персональных данных</label>
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