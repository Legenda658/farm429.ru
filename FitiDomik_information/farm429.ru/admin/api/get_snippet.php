<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit;
}
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID не указан']);
    exit;
}
try {
    $stmt = $pdo->prepare("SELECT * FROM code_snippets WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    if ($snippet = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'success' => true,
            'id' => (int)$snippet['id'],
            'title' => $snippet['title'],
            'description' => $snippet['description'],
            'code' => $snippet['code'],
            'language' => $snippet['language'],
            'folder_id' => $snippet['folder_id'] ? (int)$snippet['folder_id'] : null
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Сниппет не найден']);
    }
} catch(PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
} catch(Exception $e) {
    error_log('General error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Внутренняя ошибка сервера']);
} 