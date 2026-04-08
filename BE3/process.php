<?php
header('Content-Type: text/html; charset=UTF-8');

$errors = [];
$allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];

//фио
if (!empty($_POST['fio'])){
    if (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s]+$/u', $_POST['fio'])) {
        $errors['fio'] = 'ФИО должно состоять только из букв и пробелов.';
    } elseif (strlen($_POST['fio']) > 150) {
        $errors['fio'] = 'ФИО должно быть не длиннее 150 символов.';
    }
}

//телефон
if (!empty($_POST['phone']) && !preg_match('/^\+?\d{10,20}$/', $_POST['phone'])) {
    $errors['phone'] = 'Телефон должен состоять из 10-20 цифр.';
}

//email
if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email.';
}

//др
if(!empty($_POST['birthdate'])){
   $birthdate = DateTime::createFromFormat('Y-m-d', $_POST['birthdate']);
    $today = new DateTime();
    $minAge = new DateTime('-150 years');
    if (!$birthdate || $birthdate > $today || $birthdate < $minAge) {
        $errors['birthdate'] = 'Введите корректную дату рождения.';
    }
}

//пол
if (!empty($_POST['gender']) && !in_array($_POST['gender'], ['male', 'female'])) {
    $errors['gender'] = 'Выбран недопустимый пол.';
}

//яп
if(!empty($_POST['languages'])){
    foreach ($_POST['languages'] as $lang) {
        if (!in_array($lang, $allowedLanguages)) {
            $errors['languages'] = 'Выбран недопустимый язык программирования.';
            break;
        }
    }
}

//био
if (!empty($_POST['bio']) && strlen($_POST['bio']) > 5000) {
    $errors['bio'] = 'Биография должна быть не длиннее 5000 символов.';
}

//чекбокс
if (empty($_POST['contract'])) {
    $errors['contract'] = 'Необходимо ознакомиться с контрактом.';
}

if (!empty($errors)) {
    include('index.php');
    exit();
}

//бд
$user = 'u82388';
$pass = '5768002';
$dbname = 'u82388';

try {
    $db = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $db->beginTransaction();

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

    $applicationId = $db->lastInsertId();

    $langStmt = $db->prepare("SELECT id FROM programming_languages WHERE name = :name");
    $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (:app_id, :lang_id)");
    foreach ($_POST['languages'] as $lang) {
        $langStmt->execute([':name' => $lang]);
        $langId = $langStmt->fetchColumn();
        $stmt->execute([
            ':app_id' => $applicationId,
            ':lang_id' => $langId
        ]);
    }

    $db->commit();

    header('Location: form.php?save=1');
    exit();
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    print('Ошибка: ' . $e->getMessage());
    exit();
}
