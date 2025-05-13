<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID не указан']);
    exit;
}
try {
    $stmt = $pdo->prepare("SELECT code, language FROM code_snippets WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    if ($row = $stmt->fetch()) {
        echo json_encode([
            'code' => $row['code'],
            'language' => $row['language']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Код не найден']);
    }
} catch(PDOException $e) {
    error_log("Ошибка при получении кода: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера']);
}
?> 