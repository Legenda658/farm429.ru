<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID не указан']);
    exit;
}
try {
    $stmt = $pdo->prepare("SELECT title, code, language FROM code_snippets WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    if ($snippet = $stmt->fetch()) {
        echo json_encode([
            'success' => true,
            'title' => $snippet['title'],
            'code' => $snippet['code'],
            'language' => $snippet['language']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Сниппет не найден']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка при получении данных']);
} 