<?php
error_reporting(0);
ini_set('display_errors', 0);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}
require_once '../../config.php';
try {
    $data = json_decode(file_get_contents('php:
    if (!isset($data['id']) || !isset($data['title']) || !isset($data['code']) || !isset($data['language']) || !isset($data['folder_id'])) {
        throw new Exception('Не все обязательные поля заполнены');
    }
    $id = (int)$data['id'];
    $title = trim($data['title']);
    $code = $data['code'];
    $language = trim($data['language']);
    $folderId = (int)$data['folder_id'];
    $stmt = $pdo->prepare("SELECT id FROM code_snippets WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Код не найден');
    }
    $stmt = $pdo->prepare("SELECT id FROM code_folders WHERE id = ?");
    $stmt->execute([$folderId]);
    if (!$stmt->fetch()) {
        throw new Exception('Папка не найдена');
    }
    $stmt = $pdo->prepare("UPDATE code_snippets SET title = ?, code = ?, language = ?, folder_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$title, $code, $language, $folderId, $id]);
    echo json_encode([
        'success' => true,
        'message' => 'Код успешно обновлен'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 