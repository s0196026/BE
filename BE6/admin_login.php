<?php
session_start();

// Подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u82388", 'u82388', '5768002', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Очистка от невидимых символов
    $password = preg_replace('/[^\x20-\x7E]/', '', $password);

    // Проверка учетных данных
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin) {
        // Проверка пароля с подробным логированием
        if (password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['login_attempts'] = 0;
            header('Location: admin.php');
            exit();
        } else {
            // Логирование неудачной попытки
            error_log("Failed admin login attempt. Username: $username, IP: {$_SERVER['REMOTE_ADDR']}");
            $error = 'Неверный пароль';
        }
    } else {
        $error = 'Пользователь не найден';
    }
} 
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель</title>
    <style>
        :root {
            --primary: #EC9311;
            --error: red;
            --background: #ffe9b0;
        }
        body {
            color: #4e1609;
            background-color: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            background-color: #fcdea8;
            padding: 2rem;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
        }
        button:hover {
            background-color: #red;
        }
        .error {
            color: var(--error);
            margin: 1rem 0;
            padding: 0.75rem;
            border: solid red;
            border-radius: 4px;
        }
        .attempts-warning {
            color: red;
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1><i class="fas fa-lock"></i> Вход администратора</h1>

        
            <form method="POST">
                <div class="form-group">
                    <label for="username">Логин:</label>
                    <input type="text" id="username" name="username" required
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit">
                    Войти
                </button>
            </form>
        
    </div>
</body>
</html>
