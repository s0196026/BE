<?php
header('Content-Type: text/html; charset=UTF-8');

// Валидация данных
$errors = [];
$allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];

// ФИО
if (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s]+$/u', $_POST['fio'])) {
    $errors['fio'] = 'ФИО должно состоять только из букв и пробелов.';
} elseif (strlen($_POST['fio']) > 150) {
    $errors['fio'] = 'ФИО должно быть не длиннее 150 символов.';
}
/*if (empty($_POST['fio'])) {
    $errors['fio'] = 'Заполните поле ФИО.';
} elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s]+$/u', $_POST['fio'])) {
    $errors['fio'] = 'ФИО должно состоять только из букв и пробелов.';
} elseif (strlen($_POST['fio']) > 200) {
    $errors['fio'] = 'ФИО должно быть не длиннее 200 символов.';
}*/

// Телефон
if (!preg_match('/^\+?\d{10,20}$/', $_POST['phone'])) {
    $errors['phone'] = 'Телефон должен состоять из 10-20 цифр.';
}
/*if (empty($_POST['phone'])) {
    $errors['phone'] = 'Заполните поле телефон.';
} elseif (!preg_match('/^\+?\d{10,20}$/', $_POST['phone'])) {
    $errors['phone'] = 'Телефон должен состоять из 10-20 цифр.';
}*/

// Email
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email.';
}
/*if (empty($_POST['email'])) {
    $errors['email'] = 'Заполните поле email.';
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email.';
}*/

// Дата рождения
$birthdate = DateTime::createFromFormat('Y-m-d', $_POST['birthdate']);
$today = new DateTime();
$minAge = new DateTime('-150 years');
if (!$birthdate || $birthdate > $today || $birthdate < $minAge) {
    $errors['birthdate'] = 'Введите корректную дату рождения.';
}
/*if (empty($_POST['birthdate'])) {
    $errors['birthdate'] = 'Заполните поле даты рождения.';
} else {
    $birthdate = DateTime::createFromFormat('Y-m-d', $_POST['birthdate']);
    $today = new DateTime();
    $minAge = new DateTime('-150 years');
    
    if (!$birthdate || $birthdate > $today || $birthdate < $minAge) {
        $errors['birthdate'] = 'Введите корректную дату рождения.';
    }
}*/

// Пол
if (!in_array($_POST['gender'], ['male', 'female'])) {
    $errors['gender'] = 'Выбран недопустимый пол.';
}
/*if (empty($_POST['gender'])) {
    $errors['gender'] = 'Укажите пол.';
} elseif (!in_array($_POST['gender'], ['male', 'female'])) {
    $errors['gender'] = 'Выбран недопустимый пол.';
}*/

// Языки программирования
foreach ($_POST['languages'] as $lang) {
    if (!in_array($lang, $allowedLanguages)) {
        $errors['languages'] = 'Выбран недопустимый язык программирования.';
        break;
    }
}
/*if (empty($_POST['languages'])) {
    $errors['languages'] = 'Выберите хотя бы один язык программирования.';
} else {
    foreach ($_POST['languages'] as $lang) {
        if (!in_array($lang, $allowedLanguages)) {
            $errors['languages'] = 'Выбран недопустимый язык программирования.';
            break;
        }
    }
}*/

// Биография
if (strlen($_POST['bio']) > 5000) {
    $errors['bio'] = 'Биография должна быть не длиннее 5000 символов.';
}
/*if (empty($_POST['bio'])) {
    $errors['bio'] = 'Заполните поле биографии.';
} elseif (strlen($_POST['bio']) > 5000) {
    $errors['bio'] = 'Биография должна быть не длиннее 5000 символов.';
}*/

// Контракт
if (empty($_POST['contract'])) {
    $errors['contract'] = 'Необходимо ознакомиться с контрактом.';
}

if (!empty($errors)) {
    include('index.php');
    exit();
}

// Подключение к базе данных
$user = 'u82388'; // Заменить на ваш логин
$pass = '5768002'; // Заменить на ваш пароль
$dbname = 'u82388'; // Заменить на ваш логин (имя БД)

try {
    $db = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Начало транзакции
    $db->beginTransaction();

    // Вставка основной информации
    $stmt = $db->prepare("INSERT INTO applications (fio, phone, email, birthdate, gender, bio, contract_agreed) 
                          VALUES (:fio, :phone, :email, :birthdate, :gender, :bio, :contract)");
    $stmt->execute([
        ':fio' => $_POST['fio'],
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':birthdate' => $_POST['birthdate'],
        ':gender' => $_POST['gender'],
        ':bio' => $_POST['bio'],
        ':contract' => isset($_POST['contract']) ? 1 : 0
    ]);

    // Получаем ID последней вставленной записи
    $applicationId = $db->lastInsertId();

    // Сначала получаем соответствие названий языков и ID
    $langStmt = $db->prepare("SELECT id FROM programming_languages WHERE name = :name");
    // Вставка языков программирования
    $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (:app_id, :lang_id)");
    foreach ($_POST['languages'] as $lang) {
        // Получаем ID языка
        $langStmt->execute([':name' => $lang]);
        $langId = $langStmt->fetchColumn();
    
        $stmt->execute([
            ':app_id' => $applicationId,
            ':lang_id' => $langId
        ]);
    }

    // Завершение транзакции
    $db->commit();

    // Перенаправление с сообщением об успехе
    header('Location: form.php?save=1');
    exit();
} catch (PDOException $e) {
    // Откат транзакции в случае ошибки
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    print('Ошибка: ' . $e->getMessage());
    exit();
}
