<?php
require_once __DIR__ . '/session.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/php/') !== false ? '../' : '') . 'login.php');
    exit;
}
