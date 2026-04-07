<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

// Обрабатываем и GET, и POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include('process.php');  // обработка данных
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['save'])) {
        print('Спасибо, результаты сохранены.');
    }
    include('index.php');  // показ формы
}

/*
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['save'])) {
        print('Сохранено');
    }
    include('process.php');
    exit();
}
*/
