<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход пользователя</title>
    <style>
        body {
            background-color: #7ACBDC;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
        }
        h1 {
            text-align: center;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #E12885;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #9cd8cc;
        }
        .error {
            border: 2px solid red;
            border-radius: 4px;
            color: red;
            margin: 15px 0;
            padding: 10px;
            text-align: center;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: #8c4566;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
.genbut{
margin-left: 10px;
width: 320px;
}
.divinp{
display: flex;
            justify-content: space-between;
            align-items: center;
}
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Вход пользователя</h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="login">Логин:</label>
                <div class="divinp">
                    <input type="text" id="login" name="login" required>
                    <button type="button" class="genbut" onclick="generateField('login')">Сгенерировать логин</button>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <div class="divinp">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="genbut" onclick="generateField('password')">Сгенерировать пароль</button>
                </div>
            </div>

            <button type="submit">Войти</button>
        </form>
    </div>
    <script>
    function generateField(type) {
        fetch('?ajax=' + type)
            .then(response => response.json())
            .then(data => {
                document.getElementById(type).value = data.value;
            });
    }
    </script>
</body>
</html>
