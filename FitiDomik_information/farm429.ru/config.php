<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u3073667_info');
define('DB_USER', 'u3073667_info');
define('DB_PASS', 'info658!');
define('TELEGRAM_BOT_TOKEN', '7927288160:AAFnNFSrlkeowVF-qHrAWxTK3OYwmexOU4g');
define('TELEGRAM_CHAT_ID', '7886808180');
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    is_locked BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(100) NULL,
    reset_token_expires TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
try {
    $pdo->exec($sql);
} catch(PDOException $e) {
    die("Ошибка при создании таблицы: " . $e->getMessage());
}
$sql = "CREATE TABLE IF NOT EXISTS components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NULL,
    price_type ENUM('bought', 'custom', 'undefined') DEFAULT 'undefined',
    status ENUM('bought', 'not_bought') DEFAULT 'not_bought',
    info TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
try {
    $pdo->exec($sql);
} catch(PDOException $e) {
    error_log("Ошибка при создании таблицы components: " . $e->getMessage());
}
$sql = "CREATE TABLE IF NOT EXISTS code (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    content TEXT NOT NULL,
    language VARCHAR(50) DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
try {
    $pdo->exec($sql);
} catch(PDOException $e) {
    error_log("Ошибка при создании таблицы code: " . $e->getMessage());
}
$sql = "CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) NULL,
    status ENUM('new', 'in_progress', 'completed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
try {
    $pdo->exec($sql);
} catch(PDOException $e) {
    error_log("Ошибка при создании таблицы feedback: " . $e->getMessage());
}
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
function sendTelegramMessage($message) {
    $url = "https:
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}
function isUsernameUnique($pdo, $username) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn() == 0;
}
function isEmailUnique($pdo, $email) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() == 0;
}
function isPasswordStrong($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}
function isEmailValid($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
function formatPrice($price_type, $price) {
    switch ($price_type) {
        case 'bought':
            return 'Куплено';
        case 'custom':
            return number_format($price, 2, ',', ' ') . ' ₽';
        case 'undefined':
        default:
            return 'Неизвестно';
    }
}
function formatStatus($status) {
    return $status === 'bought' ? 'Куплено' : 'Не куплено';
}
function formatPriceType($price_type) {
    switch ($price_type) {
        case 'bought':
            return 'Куплено';
        case 'custom':
            return 'Указать цену';
        case 'undefined':
        default:
            return 'Цена не определена';
    }
}
function calculateTotalAmount($components) {
    $total = 0;
    $unknown_count = 0;
    foreach ($components as $component) {
        if ($component['price_type'] === 'custom' && $component['price'] !== null) {
            $total += $component['price'] * $component['quantity'];
        } elseif ($component['price_type'] === 'undefined') {
            $unknown_count += $component['quantity'];
        }
    }
    return [
        'total' => $total,
        'unknown_count' => $unknown_count
    ];
} 