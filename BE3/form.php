<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include('process.php');
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $showSuccess = !empty($_GET['save']);
    include('index.php');
}
