<?php
session_start();

// Функция для генерации случайного логина
function generateLogin() {
    $adjectives = ['Fast', 'Smart', 'Cool', 'Happy', 'Bright', 'Clever', 'Wise', 'Brave', 'Cool', 'Lucky'];
    $nouns = ['Apple', 'Snow', 'Perfume', 'Goose', 'Cat', 'Sugar', 'Muse', 'Hero', 'Star', 'Ghost'];
    $random = rand(100, 999);
    
    return $adjectives[array_rand($adjectives)] . $nouns[array_rand($nouns)] . $random;
}

// Функция для генерации случайного пароля
function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    if ($_GET['ajax'] == 'login') {
        echo json_encode(['value' => generateLogin()]);
    } elseif ($_GET['ajax'] == 'password') {
        echo json_encode(['value' => generatePassword()]);
    }
    exit();
}

// уже авторизован - перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u82388", 'u82388', '5768002', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['generate_login']) && !isset($_POST['generate_password'])) {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);

    //-------------
    if (empty($login)) {
        $error = 'Введите логин';
    } elseif (strlen($login) < 4) {
        $error = 'Логин должен быть не менее 4 символов';
    } elseif (empty($password)) {
        $error = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        // проверка уникальности логина
$stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE login = ?");
$stmt->execute([$login]);

if ($stmt->fetchColumn() > 0) {
    $error = 'Этот логин уже занят';
} else {
    // хеширование пароля
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    try {
        // фиксация аккаунта
        $stmt = $db->prepare("INSERT INTO applications
            (login, password_hash, contract_agreed)
            VALUES (?, ?, 0)");

        $stmt->execute([
            $login,
            $passwordHash
        ]);

        // ПОЛУЧАЕМ ID НОВОГО ПОЛЬЗОВАТЕЛЯ
        $userId = $db->lastInsertId();
        
        // СОХРАНЯЕМ ЛОГИН И ПАРОЛЬ В СЕССИЮ
        $_SESSION['temp_login'] = $login;
        $_SESSION['temp_password'] = $password;
        
        // АВТОМАТИЧЕСКИ АВТОРИЗУЕМ
        $_SESSION['user_id'] = $userId;
        
        // ПЕРЕНАПРАВЛЯЕМ НА АНКЕТУ
        header('Location: index.php');
        exit();
        
    } catch (PDOException $e) {
        $error = 'Ошибка регистрации: ' . $e->getMessage();
    }
}
    }
    //-------------

    // поиск пользователя в БД
    try {
        $stmt = $db->prepare("SELECT id, password_hash FROM applications WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                header('Location: index.php');
                exit();
            } else {
                $error = 'Неверный пароль';
            }
        } else {
            $error = 'Пользователь с таким логином не существует';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка базы данных';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
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
            background-color: #EC9311;
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
        <h1>Вход в систему</h1>

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
