<?php

date_default_timezone_set('Europe/Warsaw');

set_exception_handler(function ($e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo 'Wystąpił błąd aplikacji. Spróbuj ponownie później.';
});

session_start();
require_once __DIR__ . '/../public/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
sprawdz_admin();

$stmt = $pdo->prepare("SELECT role, active FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$konto = $stmt->fetch();
if (!$konto || $konto['role'] !== 'admin' || (int) $konto['active'] !== 1) {
    $_SESSION = [];
    session_destroy();
    header('Location: ../login.php');
    exit;
}
