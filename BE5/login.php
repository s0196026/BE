<?php
session_start();

// Функция для генерации случайного логина
function generateLogin() {
    $adjectives = ['Fast', 'Smart', 'Cool', 'Happy', 'Bright', 'Clever', 'Wise', 'Brave', 'Calm', 'Lucky'];
    $nouns = ['Coder', 'Dev', 'Programmer', 'Hacker', 'Master', 'Wizard', 'Ninja', 'Hero', 'Star', 'Ghost'];
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
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

                $success = true;
            } catch (PDOException $e) {
                $error = 'Ошибка регистрации: ' . $e->getMessage();
            }
        }
    }
    //-------------

    // отладочная информация
    $debug_info .= "Попытка входа: login='$login', password='$password'\n";

    try {
        // поиск пользователя в БД
        $stmt = $db->prepare("SELECT id, password_hash FROM applications WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user) {
            $debug_info .= "Найден пользователь: ID={$user['id']}\n";
            $debug_info .= "Хэш из БД: {$user['password_hash']}\n";
            $debug_info .= "Длина хэша: " . strlen($user['password_hash']) . " символов\n";

            // проверка пароля
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $debug_info .= "Пароль верный, авторизация успешна\n";

                // перенаправление после успешного входа
                header('Location: index.php');
                exit();
            } else {
                $debug_info .= "Ошибка: пароль не совпадает\n";
                $error = 'Неверный пароль';
            }
        } else {
            $debug_info .= "Ошибка: пользователь не найден\n";
            $error = 'Пользователь с таким логином не существует';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка базы данных';
        $debug_info .= "Ошибка БД: " . $e->getMessage() . "\n";
    }

    // логируем отладочную информацию
    error_log($debug_info);
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
//-----------
                    <input type="text" id="login" name="login" value="<?= isset($_COOKIE['error_fio']) ? 'error-field' : '' ?>" required>
                    <button type="submit" class="genbut">Сгенерировать логин</button>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <div class="divinp">
                    <input type="password" id="password" name="password" required>
                    <button type="submit" class="genbut">Сгенерировать пароль</button>
                </div>
            </div>

            <button type="submit">Войти</button>
        </form>

        <div class="register-link">
            Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>
        </div>
    </div>
</body>
</html>
