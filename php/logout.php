<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/session.php';
session_destroy();
session_start();
$_SESSION['flash']      = 'You have been logged out successfully.';
$_SESSION['flash_type'] = 'success';
header('Location: ../login.php');
exit;
