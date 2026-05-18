<?php
  session_start();
  session_destroy();
  header('Location: navigation.php');
  exit();
?>
