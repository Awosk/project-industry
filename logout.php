<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/log.php';
logCikis($pdo, 'lite');
session_destroy();
header('Location: ' . ROOT_URL . 'login.php');
exit;
