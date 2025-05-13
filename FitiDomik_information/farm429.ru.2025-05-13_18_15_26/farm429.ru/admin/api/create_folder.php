<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Неверный метод запроса']);
    exit;
}
try {
    $name = trim($_POST['name'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    if (empty($name)) {
        throw new Exception('Название папки не может быть пустым');
    }
    if ($parent_id !== null) {
        $stmt = $pdo->prepare("SELECT id FROM code_folders WHERE id = ?");
        $stmt->execute([$parent_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Родительская папка не найдена');
        }
    }
    $stmt = $pdo->prepare("INSERT INTO code_folders (name, parent_id) VALUES (?, ?)");
    $stmt->execute([$name, $parent_id]);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Папка успешно создана',
        'folder_id' => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?> 