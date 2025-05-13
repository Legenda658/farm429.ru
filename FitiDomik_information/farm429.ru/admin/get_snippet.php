<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    exit('Доступ запрещен');
}
if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('ID не указан');
}
try {
    $stmt = $pdo->prepare("SELECT * FROM code_snippets WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $snippet = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$snippet) {
        http_response_code(404);
        exit('Сниппет не найден');
    }
    header('Content-Type: application/json');
    echo json_encode($snippet);
} catch(PDOException $e) {
    http_response_code(500);
    exit('Ошибка при получении данных');
} 