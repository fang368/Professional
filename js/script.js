document.addEventListener('DOMContentLoaded', () => {
   // Определение текущего пути страницы
    const currentPath = window.location.pathname;
    // Получение ссылок на кнопки профиля и заказов
    const profileButton = document.getElementById('profile-page');
    const ordersButton = document.getElementById('orders-page');

    // Проверка наличия элементов перед добавлением класса
    if (profileButton && currentPath.includes('profile.php')) {
        profileButton.classList.add('active');
    }
    if (ordersButton && currentPath.includes('orders.php')) {
        ordersButton.classList.add('active');
    }


    // Функция сохранения состояния переключателей в локальное хранилище
    function saveToggleState(toggleStates) {
        localStorage.setItem('toggleStates', JSON.stringify(toggleStates));
    }

    // Функция загрузки состояния переключателей из локального хранилища
    function loadToggleState() {
        return JSON.parse(localStorage.getItem('toggleStates')) || {};
    }

    // Получение всех переключателей на странице
    const toggleSwitches = document.querySelectorAll('.toggle-switch');
    // Загрузка состояния переключателей
    const toggleStates = loadToggleState();

    // Проход по каждому переключателю и добавление обработчика события для сохранения состояния
    toggleSwitches.forEach((toggle) => {
        const toggleId = toggle.id;
        const toggleState = toggleStates[toggleId];

        // Установка начального состояния переключателя
        if (toggleState) {
            toggle.src = 'icons/toggle-on.svg';
        }

        // Обработчик события клика по переключателю
        toggle.addEventListener('click', () => {
            const isOn = toggle.src.includes('toggle-on.svg');
            toggle.src = isOn ? 'icons/toggle-off.svg' : 'icons/toggle-on.svg';
            toggleStates[toggleId] = !isOn;

            // Сохранение состояния переключателей
            saveToggleState(toggleStates);
        });
    });

    // Функция для открытия/закрытия выпадающего меню
    function toggleDropdown(e) {
        const _d = $(e.target).closest('.dropdown'),
            _m = $('.dropdown-menu', _d);
        setTimeout(function () {
            const shouldOpen = e.type !== 'click' && _d.is(':hover');
            _m.toggleClass('show', shouldOpen);
            _d.toggleClass('show', shouldOpen);
            $('[data-toggle="dropdown"]', _d).attr('aria-expanded', shouldOpen);
        }, e.type === 'mouseleave' ? 1 : 0);
    }

    // Назначение обработчиков событий для открытия/закрытия выпадающего меню
    $('body')
        .on('mouseenter mouseleave', '.dropdown', toggleDropdown)
        .on('click', '.dropdown-menu button', toggleDropdown);

    // Инициализация слайдера Swiper
    const swiper = new Swiper('.swiper', {
        direction: 'horizontal',
        loop: true,
        pagination: {
            el: '.swiper-pagination',
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
    });

    // Получение всех элементов с атрибутом data-speed
    const scrollImages = document.querySelectorAll('.scroll-images');

    // Установка начальной позиции для каждого элемента с атрибутом data-top
    scrollImages.forEach((imageContainer) => {
        const top = imageContainer.getAttribute('data-top');
        imageContainer.style.top = top;
    });

    // Функция для обновления параллакс-эффекта при прокрутке страницы
    function updateParallax() {
        const scrollPos = window.scrollY;

        // Проход по каждому элементу с атрибутом data-speed и обновление его позиции и прозрачности
        scrollImages.forEach((imageContainer) => {
            const speed = imageContainer.getAttribute('data-speed');
            const yPos = -scrollPos * speed * 0.5;
            imageContainer.style.transform = `translateY(${yPos}px)`;

            const rect = imageContainer.getBoundingClientRect();
            if (rect.top > window.innerHeight || rect.bottom < 0) {
                imageContainer.style.opacity = '0';
            } else {
                imageContainer.style.opacity = '1';
            }
        });

        requestAnimationFrame(updateParallax);
    }

    // Вызов функции обновления параллакс-эффекта
    requestAnimationFrame(updateParallax);

    // Здесь добавляем новый код для обработки ошибок валидации
    const errorsInput = document.getElementById('errors');
    if (errorsInput) {
        const errorsExist = JSON.parse(errorsInput.value);
        const formContentItems = document.querySelectorAll('.form-content__item');

        // Далее вы можете выполнить любую логику на основе переменной errorsExist
        if (errorsExist) {
            formContentItems.forEach((input, index) => {
                if (errorsExist[index]) {
                    input.classList.add('error-border');
                }
            });
        }
    }

   // Функция для обработки модальных окон
    function setupModal(modalId, buttonClass, closeClass) {
        const modal = document.getElementById(modalId);
        const buttons = document.querySelectorAll(buttonClass);
        const span = modal.querySelector(closeClass);

        if (!modal || buttons.length === 0 || !span) return;

        // Открыть модальное окно при клике на любую кнопку с указанным классом
        buttons.forEach(btn => {
            btn.addEventListener('click', function(event) {
                event.preventDefault(); // предотвращаем переход по ссылке

                // Закрываем все открытые модальные окна
                closeAllModals();

                // Открываем текущее модальное окно
                modal.style.display = "flex";
            });
        });

        // Закрыть модальное окно при клике на <span> (x)
        span.addEventListener('click', function() {
            modal.style.display = "none";
        });

        // Закрыть модальное окно при клике вне его
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        });
    }

    // Функция для закрытия всех модальных окон
    function closeAllModals() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = "none";
        });
    }

    const modalMappings = [
        { modalId: "orderModal", buttonClass: ".slaider-menu.btn-main-red", closeClass: ".close" },
        { modalId: "contextAdModal", buttonClass: ".context-btn-main-red", closeClass: ".close" },
        { modalId: "seoPromotionModal", buttonClass: ".seo-btn-main-red", closeClass: ".close" },
        { modalId: "contextAdModal-adversting", buttonClass: ".context-btn-adversting", closeClass: ".close" },
        { modalId: "WebsiteSupportModal", buttonClass: ".btn_website", closeClass: ".close" },
    ];

    modalMappings.forEach(mapping => {
        const { modalId, buttonClass, closeClass } = mapping;
        const modalExists = document.getElementById(modalId);
        if (modalExists) {
            setupModal(modalId, buttonClass, closeClass);
        }
    });

    const phoneInputs = document.querySelectorAll('.phone-input');

    phoneInputs.forEach(phoneInput => {
        phoneInput.addEventListener('input', () => {
            let phoneNumber = phoneInput.value.replace(/\D/g, ''); // Удаление всех символов, кроме цифр
            
            if (phoneNumber.length > 11) {
                phoneNumber = phoneNumber.slice(0, 11); // Ограничение длины номера до 11 цифр
            }

            let formattedPhoneNumber = '';
            if (phoneNumber.length > 0 && !phoneNumber.startsWith('7')) {
                // Если номер не начинается с '7', добавляем '+7' и форматируем
                formattedPhoneNumber = phoneNumber.replace(/(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})/, '+7 $2 $3-$4-$5');
            } else {
                // В противном случае, просто форматируем номер
                formattedPhoneNumber = phoneNumber.replace(/(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})/, '+$1 $2 $3-$4-$5');
            }

            phoneInput.value = formattedPhoneNumber;
        });

        phoneInput.addEventListener('keydown', (event) => {
            let phoneNumber = phoneInput.value.replace(/\D/g, ''); // Удаление всех символов, кроме цифр
            if (phoneNumber.length >= 11 && event.key !== 'Backspace' && event.key !== 'Delete') {
                event.preventDefault(); // Блокировка ввода, если длина уже равна 11 и нажата не клавиша удаления
            }
        });
    });

    const timeInputs = document.querySelectorAll('[id^="time"]');
    timeInputs.forEach(timeInput => {
        timeInput.addEventListener('input', function() {
            let value = timeInput.value.replace(/\D/g, ''); // Удалить все нецифровые символы
            if (value.length > 4) {
                value = value.substring(0, 4); // Ограничить длину значения до 4 цифр
            }

            if (value.length === 0) {
                timeInput.value = '';
            } else if (value.length <= 2) {
                timeInput.value = value;
            } else {
                timeInput.value = value.substring(0, 2) + ':' + value.substring(2, 4);
            }
        });

        timeInput.addEventListener('keydown', (event) => {
            if ((event.key < '0' || event.key > '9') && event.key !== 'Backspace' && event.key !== 'Delete') {
                event.preventDefault(); // Запретить ввод любых символов, кроме цифр и клавиш удаления
            }
        });
    });

    // Добавление функциональности для выпадающего меню сортировки
    const sortMenu = document.getElementById('sort-menu');
    const itemsContainer = document.getElementById('items-container');

    const urlParams = new URLSearchParams(window.location.search);
    const currentSortingCriteria = urlParams.get('criteria') || 'date_desc'; 

    const sortingDropdownItems = document.querySelectorAll('.sorting-dropdown-item');
    sortingDropdownItems.forEach(item => {
        const href = item.getAttribute('href');
        const newHref = `${href.split('?')[0]}?${href.split('?')[1]}&criteria=${currentSortingCriteria}`;
        item.setAttribute('href', newHref);
    });

    const upButtonContainer = document.querySelector('.button__up-container');
    const upButton = document.querySelector('.up-btn');
    const secondSection = document.querySelector('.second-section');

    if (!upButtonContainer || !upButton) {
        console.error("Кнопка 'наверх' или контейнер не найдены");
        return;
    }

    window.addEventListener('scroll', () => {
        const scrollPosition = window.scrollY;
        console.log('Текущая позиция прокрутки:', scrollPosition);

        let showButton = false;

        // Появление кнопки через 2000 пикселей
        if (scrollPosition > 2000) {
            showButton = true;
        }

        // Проверка достижения второй секции
        if (secondSection) {
            const secondSectionTop = secondSection.getBoundingClientRect().top + scrollPosition;
            console.log('Верх второй секции:', secondSectionTop);
            if (scrollPosition >= secondSectionTop) {
                showButton = true;
            }
        }

        if (showButton) {
            upButtonContainer.classList.add('show');
        } else {
            upButtonContainer.classList.remove('show');
        }
    });

    // Добавляем обработчик для кнопки "наверх"
    upButton.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
