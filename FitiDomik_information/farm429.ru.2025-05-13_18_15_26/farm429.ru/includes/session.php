<?php
if (session_status() === PHP_SESSION_NONE) {
    while (ob_get_level()) ob_end_clean();
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        return false;
    }
    if (time() - $_SESSION['last_activity'] > 3600) {
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}
function getCurrentUsername() {
    return isLoggedIn() ? $_SESSION['username'] : null;
}
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
} 