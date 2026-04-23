<?php
session_start();
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    die("Требуется авторизация");
}

$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

$db = new PDO("mysql:host=localhost;dbname=u82388", 'u82388', '5768002', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Проверка из БД
$stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    die("Неверные учетные данные");
}

// 1. Проверка авторизации
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// 2. Подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u82388", 'u82388', '5768002', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// 3. Обработка удаления
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
        $db->commit();
        header("Location: admin.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        die("Ошибка при удалении: " . $e->getMessage());
    }
}

// 4. Получение данных
$users = $db->query("SELECT * FROM applications ORDER BY id")->fetchAll();
$stats = $db->query("
    SELECT pl.name, COUNT(al.application_id) as user_count
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.name
    ORDER BY user_count DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #EC9311;
            --danger: #f72585;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --border-radius: 4px;
        }

        body {
            color: #4e1609;
            background-color: #ffe9b0;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #EC9311;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #9cd8cc;
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success {
            background-color: #EC9311;
            color: white;
            font-weight: bold;
        }

        .alert.error {
            background-color: rgba(247, 37, 133, 0.2);
            border-left: 4px solid var(--danger);
            color: #a11a56;
        }

        form {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .error {
            color: var(--danger);
            font-size: 14px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .submit-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Стили для админ-таблицы */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .admin-table th {
            background-color: #EC9311;
            color: white;
        }

        .admin-table tr:hover {
            background-color: #f5f5f5;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .edit-btn {
            background-color: green;
            color: white;
        }

        .delete-btn {
            background-color: red;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            margin: 5px 0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            form {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Админ-панель</h1>
            <a href="logout.php" class="logout-btn">
                Выйти
            </a>
        </header>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert success">
                Пользователь успешно удален!
            </div>
        <?php endif; ?>

        <h2>Статистика по языкам</h2>
        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <h3><?= htmlspecialchars($stat['name']) ?></h3>
                    <div class="stat-value"><?= $stat['user_count'] ?></div>
                    <p> пользователей</p>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>Список пользователей</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['fio']) ?></td>
                        <td><?= htmlspecialchars($user['phone']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= $user['birthdate'] ?></td>
                        <td><?= $user['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                        <td>
                            <a href="edit_user.php?id=<?= $user['id'] ?>" class="action-btn edit-btn">
                                Ред.
                            </a>
                            <a href="admin.php?delete=<?= $user['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Удалить этого пользователя?')">
                                Удал.
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
