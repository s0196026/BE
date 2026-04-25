<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <style>
        body {
            background-color: #ffe9b0;
            margin: 0;
            color: #64400f;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            color: #4e1609;
            background-color: #fcdea8;
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            margin-bottom: 25px;
        }
        .btn {
            background-color: #EC9311;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            margin-bottom: 10px;
            width: 100%;
            text-align: center;
        }

        .btn:hover {
            background-color: #9cd8cc;
        }
        .divinp{
            display: flex;
            align-items: center;
            flex-direction: column;
        }
        a{
            font-size: 14pt;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Вход</h1>
        <div class="divinp">
            <a href="login.php" class="btn">
            Войти как пользователь</a>
        <a href="admin.php" class="btn">
            Войти как администратор</a>   
        </div>
    </div>
</body>
</html>
