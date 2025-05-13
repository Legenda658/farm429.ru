<?php
ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');
ini_set('post_max_size', '512M');
ini_set('upload_max_filesize', '512M');
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
require_once '../../includes/session.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Неверный метод запроса']);
    exit;
}
try {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $code = $_POST['code'] ?? '';
    $language = $_POST['language'] ?? '';
    $folder_id = !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
    if (empty($title)) {
        throw new Exception('Название не может быть пустым');
    }
    if (empty($code)) {
        throw new Exception('Код не может быть пустым');
    }
    if (empty($language)) {
        throw new Exception('Выберите язык программирования');
    }
    if ($folder_id !== null) {
        $stmt = $pdo->prepare("SELECT id FROM code_folders WHERE id = ?");
        $stmt->execute([$folder_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Указанная папка не найдена');
        }
    }
    $stmt = $pdo->prepare("INSERT INTO code_snippets (title, description, code, language, folder_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $code, $language, $folder_id]);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Код успешно сохранен',
        'snippet_id' => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} 