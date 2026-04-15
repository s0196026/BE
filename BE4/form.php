<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['clear'])) {
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'form_') === 0 || strpos($name, 'error_') === 0) {
            setcookie($name, '', time() - 3600, '/');
        }
    }
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'index.php';
    exit();
}

header('Location: index.php');
exit();
?>
