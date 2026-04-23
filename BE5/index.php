<?php
session_start();

$showCredentials = false;
$tempLogin = '';
$tempPassword = '';

if (isset($_SESSION['temp_login']) && isset($_SESSION['temp_password'])) {
    $showCredentials = true;
    $tempLogin = $_SESSION['temp_login'];
    $tempPassword = $_SESSION['temp_password'];
    // Удаляем из сессии, чтобы не показывать при следующем входе
    unset($_SESSION['temp_login']);
    unset($_SESSION['temp_password']);
}

$isFirstVisit = !isset($_COOKIE['form_initialized']);

if ($isFirstVisit) {
    // кука, что форма уже посещалась
    setcookie('form_initialized', '1', time() + 3600 * 24 * 30, '/'); // на 30 дней

    // очистка ошибок
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'error_') === 0 || strpos($name, 'form_') === 0) {
            setcookie($name, '', time() - 3600, '/');
        }
    }
}
// перенаправление если не авторизован
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// функции для работы с cookies
function setFormCookie($name, $value, $expire = 0) {
    setcookie("form_$name", $value, $expire, '/');
}

function setErrorCookie($name, $message) {
    setcookie("error_$name", $message, 0, '/');
}

// заполнение значений полей
function getFieldValue($fieldName, $userData, $dbFieldName = null) {
    $dbField = $dbFieldName ?: $fieldName;
    
    if (isset($_COOKIE["form_$fieldName"])) {
        return htmlspecialchars($_COOKIE["form_$fieldName"]);
    }
    
    if ($userData && isset($userData[$dbField]) && $userData[$dbField] !== null) {
        return htmlspecialchars($userData[$dbField]);
    }
    return '';
}

// подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u82388", 'u82388', '5768002', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
// очистка ошибок
if (!isset($_GET['form_submitted'])) {
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'error_') === 0) {
            setcookie($name, '', time() - 3600, '/');
        }
    }
}
// загрузка данных пользователя
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();

// загрузка выбранных языков
$langStmt = $db->prepare("SELECT pl.name FROM application_languages al
                         JOIN programming_languages pl ON al.language_id = pl.id
                         WHERE al.application_id = ?");
$langStmt->execute([$_SESSION['user_id']]);
$userLanguages = $langStmt->fetchAll(PDO::FETCH_COLUMN);

// обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    $allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];

    // валидация ФИО
    if (empty($_POST['fio'] ?? '')) {
        $errors['fio'] = 'Заполните ФИО';
        setErrorCookie('fio', $errors['fio']);
    } elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s]+$/u', $_POST['fio'])) {
        $errors['fio'] = 'Допустимы только буквы и пробелы';
        setErrorCookie('fio', $errors['fio']);
    }
    setFormCookie('fio', $_POST['fio'] ?? '');

    // валидация телефона
    if (empty($_POST['phone'] ?? '')) {
        $errors['phone'] = 'Заполните телефон';
        setErrorCookie('phone', $errors['phone']);
    } elseif (!preg_match('/^\+?\d{10,15}$/', $_POST['phone'])) {
        $errors['phone'] = 'От 10 до 15 цифр, можно с +';
        setErrorCookie('phone', $errors['phone']);
    }
    setFormCookie('phone', $_POST['phone'] ?? '');

    // валидация email
    if (empty($_POST['email'] ?? '')) {
        $errors['email'] = 'Заполните email';
        setErrorCookie('email', $errors['email']);
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
        setErrorCookie('email', $errors['email']);
    }
    setFormCookie('email', $_POST['email'] ?? '');

    // валидация др
    if (empty($_POST['birthdate'] ?? '')) {
        $errors['birthdate'] = 'Укажите дату рождения';
        setErrorCookie('birthdate', $errors['birthdate']);
    }
    setFormCookie('birthdate', $_POST['birthdate'] ?? '');

    // валидация пола
    if (empty($_POST['gender'] ?? '')) {
        $errors['gender'] = 'Укажите пол';
        setErrorCookie('gender', $errors['gender']);
    }
    setFormCookie('gender', $_POST['gender'] ?? '');

    // валидация яп
    $languages = isset($_POST['languages']) && is_array($_POST['languages']) ? $_POST['languages'] : [];
    if (empty($languages)) {
        $errors['languages'] = 'Выберите хотя бы один язык';
        setErrorCookie('languages', $errors['languages']);
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $allowedLanguages)) {
                $errors['languages'] = 'Выбран недопустимый язык';
                setErrorCookie('languages', $errors['languages']);
                break;
            }
        }
    }
    setFormCookie('languages', !empty($languages) ? implode(',', $languages) : '');

    // валидация био
    if (empty($_POST['bio'] ?? '')) {
        $errors['bio'] = 'Заполните биографию';
        setErrorCookie('bio', $errors['bio']);
    }
    setFormCookie('bio', $_POST['bio'] ?? '');

    // валидация чекбокса
    if (empty($_POST['contract'] ?? '')) {
        $errors['contract'] = 'Необходимо согласие';
        setErrorCookie('contract', $errors['contract']);
    }

    // ошибки - редирект
    if (!empty($errors)) {
    header('Location: index.php?form_submitted=1');
    exit();
}

    // ошибок нет - сохраняем в БД
    try {
        $db->beginTransaction();

        // обновление основной информации
        $stmt = $db->prepare("UPDATE applications SET
            fio = ?, phone = ?, email = ?, birthdate = ?,
            gender = ?, bio = ?, contract_agreed = ?
            WHERE id = ?");

        $stmt->execute([
            $_POST['fio'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['birthdate'],
            $_POST['gender'],
            $_POST['bio'],
            isset($_POST['contract']) ? 1 : 0,
            $_SESSION['user_id']
        ]);

        // обновление яп
        $db->prepare("DELETE FROM application_languages WHERE application_id = ?")
           ->execute([$_SESSION['user_id']]);

        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id)
                            SELECT ?, id FROM programming_languages WHERE name = ?");
        foreach ($languages as $lang) {
            $stmt->execute([$_SESSION['user_id'], $lang]);
        }

        $db->commit();

        // очистка куков после успешного сохранения
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'form_') === 0 || strpos($name, 'error_') === 0) {
                setcookie($name, '', time() - 3600, '/');
            }
        }

        header('Location: index.php?success=1');
        exit();

    } catch (PDOException $e) {
        $db->rollBack();
        setErrorCookie('db', 'Ошибка сохранения: '.$e->getMessage());
        header('Location: index.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Анкета</h1>
            <a href="logout.php" class="logout-btn">
                Выйти
            </a>
        </header>
        <?php if ($showCredentials): ?>
    <div class="success-reg">
        <strong>Вы успешно зарегистрировались!</strong><br>
        Ваш логин: <strong><?= htmlspecialchars($tempLogin) ?></strong><br>
        Ваш пароль: <strong><?= htmlspecialchars($tempPassword) ?></strong>
    </div>
<?php endif; ?>

        <?php if (isset($_COOKIE['error_db'])): ?>
            <div class="alert error">
                <?= htmlspecialchars($_COOKIE['error_db']) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- ФИО -->
<div class="form-group">
    <label for="fio">ФИО:</label>
    <input type="text" id="fio" name="fio"
           value="<?= getFieldValue('fio', $userData, 'fio') ?>"
           class="<?= isset($_COOKIE['error_fio']) ? 'error-field' : '' ?>">
    <?php if (isset($_COOKIE['error_fio'])): ?>
        <div class="error"><?= htmlspecialchars($_COOKIE['error_fio']) ?></div>
    <?php endif; ?>
</div>

<!-- Телефон -->
<div class="form-group">
    <label for="phone">Телефон:</label>
    <input type="tel" id="phone" name="phone"
           value="<?= getFieldValue('phone', $userData, 'phone') ?>"
           class="<?= isset($_COOKIE['error_phone']) ? 'error-field' : '' ?>">
    <?php if (isset($_COOKIE['error_phone'])): ?>
        <div class="error"><?= htmlspecialchars($_COOKIE['error_phone']) ?></div>
    <?php endif; ?>
</div>

<!-- Email -->
<div class="form-group">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email"
           value="<?= getFieldValue('email', $userData, 'email') ?>"
           class="<?= isset($_COOKIE['error_email']) ? 'error-field' : '' ?>">
    <?php if (isset($_COOKIE['error_email'])): ?>
        <div class="error"><?= htmlspecialchars($_COOKIE['error_email']) ?></div>
    <?php endif; ?>
</div>

<!-- Дата рождения -->
<div class="form-group">
    <label for="birthdate">Дата рождения:</label>
    <input type="date" id="birthdate" name="birthdate"
           value="<?= getFieldValue('birthdate', $userData, 'birthdate') ?>"
           class="<?= isset($_COOKIE['error_birthdate']) ? 'error-field' : '' ?>">
    <?php if (isset($_COOKIE['error_birthdate'])): ?>
        <div class="error"><?= htmlspecialchars($_COOKIE['error_birthdate']) ?></div>
    <?php endif; ?>
</div>

<!-- Пол -->
<div class="form-group">
    <label>Пол:</label>
    <div class="radio-group">
        <?php $genderValue = getFieldValue('gender', $userData, 'gender'); ?>
        <input type="radio" id="male" name="gender" value="male"
               <?= ($genderValue == 'male') ? 'checked' : '' ?>>
        <label for="male">Мужской</label>
    </div>
    <div class="radio-group">
        <input type="radio" id="female" name="gender" value="female"
               <?= ($genderValue == 'female') ? 'checked' : '' ?>>
        <label for="female">Женский</label>
    </div>
    <?php if (isset($_COOKIE['error_gender'])): ?>
        <div class="error"><?= htmlspecialchars($_COOKIE['error_gender']) ?></div>
    <?php endif; ?>
</div>

<!-- Языки программирования -->
<div class="form-group">
    <label for="languages">Любимые языки программирования:</label>
    <select id="languages" name="languages[]" multiple size="5"
            class="<?= isset($_COOKIE['error_languages']) ? 'error-field' : '' ?>">
        <?php
        $options = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
        
        // Приоритет: COOKIE > БД
        $selectedLangs = [];
        if (isset($_COOKIE['form_languages']) && !empty($_COOKIE['form_languages'])) {
            $selectedLangs = explode(',', $_COOKIE['form_languages']);
        } elseif ($userLanguages && !empty($userLanguages)) {
            $selectedLangs = $userLanguages;
        }
        
        foreach ($options as $lang): ?>
            <option value="<?= $lang ?>" <?= in_array($lang, $selectedLangs) ? 'selected' : '' ?>>
                <?= $lang ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($_COOKIE['error_languages'])): ?>
        <div class="error"><?= htmlspecialchars($_COOKIE['error_languages']) ?></div>
    <?php endif; ?>
</div>

<!-- Биография -->
<div class="form-group">
    <label for="bio">Биография:</label>
    <textarea id="bio" name="bio" class="<?= isset($_COOKIE['error_bio']) ? 'error-field' : '' ?>"><?= 
        getFieldValue('bio', $userData, 'bio') 
    ?></textarea>
    <?php if (isset($_COOKIE['error_bio'])): ?>
        <div class="error"><?= htmlspecialchars($_COOKIE['error_bio']) ?></div>
    <?php endif; ?>
</div>

<!-- Согласие -->
<div class="form-group">
    <div class="checkbox-group">
        <?php $contractValue = getFieldValue('contract', $userData, 'contract_agreed'); ?>
        <input type="checkbox" id="contract" name="contract" value="1"
               <?= ($contractValue == '1' || $contractValue == 1) ? 'checked' : '' ?>>
        <label for="contract">С контрактом ознакомлен(-а)</label>
    </div>
    <?php if (isset($_COOKIE['error_contract'])): ?>
        <div class="error"><?= htmlspecialchars($_COOKIE['error_contract']) ?></div>
    <?php endif; ?>
</div>
  <button type="submit">Сохранить данные</button>
<?php if (isset($_GET['success'])): ?>
    <div class="success-message">
        Данные сохранены!
    </div>
<?php endif; ?>
</div>
        </form>
    </div>
</body>
</html>
