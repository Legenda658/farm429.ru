<?php
while (ob_get_level()) ob_end_clean();
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/debug.php';
if (!isLoggedIn()) {
    header('Location: /');
    exit;
}
$username = getCurrentUsername();
logUserAction("Пользователь $username вышел из системы");
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();
header('Location: /');
exit;
?> 