<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /modules/dashboard.php');
    exit;
}

header('Location: /modules/list.php');
exit;