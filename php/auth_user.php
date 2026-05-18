<?php
require_once __DIR__ . '/session.php';
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/php/') !== false ? '../' : '') . 'login.php');
    exit;
}
