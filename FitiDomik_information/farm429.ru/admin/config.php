<?php
error_reporting(0);
ini_set('display_errors', 0);
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    die('Ошибка подключения к базе данных');
}
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'code_files'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS code_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_path VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_type VARCHAR(50),
            content LONGTEXT,
            parent_folder INT DEFAULT NULL,
            is_directory BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
    }
} catch (PDOException $e) {
    error_log('Table creation error: ' . $e->getMessage());
    die('Ошибка при создании таблицы');
} 