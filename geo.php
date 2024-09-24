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


        <div class="container about-geo__container">

            <div class="scroll-images scroll-image-left" data-speed="4" data-top="35%">
                <img src="icons/29-el-1.svg" alt="Image 1" class="scroll-image" height="160px">
            </div>
            <div class="scroll-images scroll-image-right img_ball" data-speed="4" data-top="50%" >
                <img src="icons/el-6.png" alt="Image 2" class="scroll-image" height="169px">
            </div>

            <h2 class="about-us__title title-mini-blue">Наши контакты</h2>
            <div class="geo-line line-main"></div>

            <div class="about-geo__wrapper">

                <div class="about-geo-wrapper__inside-left-wrapper">
                    <div class="about-geo__item-container">
                        <img src="icons/geo-fill.svg" alt="geo" class="geo__img">
                        <p class="about-geo__text city-name">Москва</p>
                    </div>
                    <p class="about-geo__text city-info">
                        115114, Дербеневская наб., д.7, стр.23, оф.45 <br> с 9:00 до 19:00 <br> Бесплатная гостевая парковка
                    </p>
                </div>

                <div class="about-geo-wrapper__inside-center-wrapper">
                    <div class="about-geo__item-container">
                        <img src="icons/geo-fill.svg" alt="geo">
                        <p class="about-geo__text city-name">Уфа</p>
                    </div>
                    <p class="about-geo__text city-info">
                        432071, ул. Кирова, д.65, оф.401 <br> с 8:00 до 17:00 мск <br>
                        <span class="about-geo__span span-map"><a href="#">Смотреть на карте</a></span>
                    </p>
                </div>

                <div class="about-geo-wrapper__inside-right-wrapper">
                    <div class="about-geo__item-container">
                        <img src="/icons/phone-fill.svg" alt="Телефон">
                        <p class="about-geo__text city-info geo_tel">
                            +7 (495) 023-93-87
                        </p>
                    </div>

                    <div class="about-geo__item-container">
                        <img src="/icons/phone-fill.svg" alt="Телефон">
                        <p class="about-geo__text city-info geo_tel">
                            +7 (495) 023-93-87
                        </p>
                    </div>

                </div>
            </div>

            <div class="about-geo__block-item">
                <div class="about-geo-block-item__left-wrapper">
                    <p class="about-geo-block__text text text_line-height">
                        Наш офис по адресу Дербеневская наб. 7, стр. 23 находится позади «ступенчатого» здания, оформленного светло-коричневой плиткой. Чтобы попасть на охраняемую территорию, пройдите через открытые ворота (черные) с установленным шлагбаумом. Они располагаются сразу после здания с зелеными стеклами (дом 5, стр. 6).
                        Если по какой-то причине ворота закрыты, пожалуйста, воспользуйтесь переговорным устройством для связи с охраной: оно закреплено справа, на стене здания.
                        При необходимости на территорию можно въехать на машине – гостевая парковка абсолютно бесплатна.
                    </p>
                </div>

                <div class="about-geo-block-item__left-wrapper">
                    <img src="img/contact_img.png" alt="Изображение Контактов">
                </div>

            </div>

        </div>
        <div class="about-geo__map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d13032.789932191654!2d55.96594526206771!3d54.72908750413159!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x43d93b1cbbd6c60f%3A0x62b1dd12a3728852!2z0KPQmtCh0JjQktCi!5e0!3m2!1sru!2sru!4v1714486018091!5m2!1sru!2sru" width="100%" height="800" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
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
                    <p class="footer__text text_small">Адрес: г. Москва, Дербеневская наб., д.7,стр.23, офис 45ИНН / КПП 7709982085 / 772501001</p>
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