<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['save'])) {
        print('Сохранено');
    }
    include('index.php');
    exit();
}
/*
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

// Обрабатываем и GET, и POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include('index.php');  // обработка данных
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['save'])) {
        print('Спасибо, результаты сохранены.');
    }
    include('index.php');  // показ формы
}
*/
