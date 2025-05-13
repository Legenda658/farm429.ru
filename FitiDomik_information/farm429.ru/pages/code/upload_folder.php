<?php
ini_set('upload_max_filesize', '1G');
ini_set('post_max_size', '1G');
ini_set('memory_limit', '1G');
ini_set('max_execution_time', 300); 
ini_set('max_input_time', 300);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die(json_encode(['success' => false, 'error' => 'Доступ запрещен']));
}
function getLanguageByExtension($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $languageMap = [
        'php' => 'PHP',
        'js' => 'JavaScript',
        'html' => 'HTML',
        'css' => 'CSS',
        'py' => 'Python',
        'sql' => 'SQL',
        'java' => 'Java',
        'cpp' => 'C++',
        'cs' => 'C#',
        'md' => 'Markdown',
        'htaccess' => 'htaccess',
        'txt' => 'Text'
    ];
    return $languageMap[$extension] ?? 'Text';
}
function createFolder($pdo, $name, $parent_id = null) {
    $stmt = $pdo->prepare("INSERT INTO code_folders (name, parent_id) VALUES (?, ?)");
    $stmt->execute([$name, $parent_id]);
    return $pdo->lastInsertId();
}
try {
    if (!isset($_POST['folder_name']) || !isset($_FILES['files'])) {
        throw new Exception('Некорректные данные');
    }
    $pdo->beginTransaction();
    $parent_folder_id = !empty($_POST['parent_folder_id']) ? $_POST['parent_folder_id'] : null;
    if ($parent_folder_id) {
        $stmt = $pdo->prepare("SELECT id FROM code_folders WHERE id = ?");
        $stmt->execute([$parent_folder_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Указанная родительская папка не существует');
        }
    }
    $root_folder_id = createFolder($pdo, $_POST['folder_name'], $parent_folder_id);
    $folders = [];
    $folders['/'] = $root_folder_id;
    $total_size = 0;
    foreach ($_FILES['files']['size'] as $size) {
        $total_size += $size;
    }
    if ($total_size > 1073741824) { 
        throw new Exception('Общий размер файлов превышает 1 ГБ');
    }
    for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
        $relativePath = $_POST['paths'][$i];
        $pathParts = explode('/', $relativePath);
        array_shift($pathParts); 
        if (count($pathParts) > 1) {
            $currentPath = '';
            for ($j = 0; $j < count($pathParts) - 1; $j++) {
                $previousPath = $currentPath;
                $currentPath .= '/' . $pathParts[$j];
                if (!isset($folders[$currentPath])) {
                    $parent_id = $folders[$previousPath] ?? $root_folder_id;
                    $folders[$currentPath] = createFolder($pdo, $pathParts[$j], $parent_id);
                }
            }
            $folder_id = $folders[$currentPath];
        } else {
            $folder_id = $root_folder_id;
        }
        $content = file_get_contents($_FILES['files']['tmp_name'][$i]);
        $filename = $pathParts[count($pathParts) - 1];
        $language = getLanguageByExtension($filename);
        $stmt = $pdo->prepare("INSERT INTO code_snippets (title, code, language, folder_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$filename, $content, $language, $folder_id]);
    }
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 