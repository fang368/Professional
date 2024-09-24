<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заблокированный аккаунт</title>
    <!-- Подключение ваших стилей -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .blocked-container {
            max-width: 400px;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .blocked-title {
            margin-top: 0;
            margin-bottom: 20px;
            color: #d32f2f;
        }

        .blocked-text {
            margin-bottom: 20px;
        }

        .blocked-btn {
            background-color: #d32f2f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-right: 10px;
        }

        .blocked-btn:hover {
            background-color: #b71c1c;
        }

        .logout-form {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="blocked-container">
        <h2 class="blocked-title">Ваш аккаунт заблокирован</h2>
        <p class="blocked-text">Пожалуйста, свяжитесь с администратором для получения дополнительной информации.</p>
        <a href="mailto:oleg.pytalev@gmail.com" class="blocked-btn">Написать администратору</a>
        
        <!-- Форма для выхода из аккаунта -->
        <form action="vendor/logout.php" method="post" class="logout-form">
            <button type="submit" class="blocked-btn">Выйти</button>
        </form>
    </div>
</body>
</html>