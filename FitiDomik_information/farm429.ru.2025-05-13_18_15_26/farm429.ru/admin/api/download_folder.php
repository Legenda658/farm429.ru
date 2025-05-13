<?php
session_start();
require_once '../../../config/database.php';
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    exit('Доступ запрещен');
}
$folder_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$folder_id) {
    http_response_code(400);
    exit('ID папки не указан');
}
try {
    $stmt = $pdo->prepare("SELECT * FROM code_folders WHERE id = ?");
    $stmt->execute([$folder_id]);
    $folder = $stmt->fetch();
    if (!$folder) {
        http_response_code(404);
        exit('Папка не найдена');
    }
    $stmt = $pdo->prepare("SELECT * FROM code_snippets WHERE folder_id = ?");
    $stmt->execute([$folder_id]);
    $snippets = $stmt->fetchAll();
    $temp_dir = sys_get_temp_dir() . '/code_export_' . uniqid();
    mkdir($temp_dir);
    foreach ($snippets as $snippet) {
        $extension = strtolower($snippet['language']);
        switch ($extension) {
            case 'javascript':
                $ext = '.js';
                break;
            case 'html':
                $ext = '.html';
                break;
            case 'css':
                $ext = '.css';
                break;
            case 'python':
                $ext = '.py';
                break;
            case 'sql':
                $ext = '.sql';
                break;
            case 'php':
            default:
                $ext = '.php';
                break;
        }
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $snippet['title']) . $ext;
        file_put_contents($temp_dir . '/' . $filename, $snippet['code']);
    }
    $zip_file = sys_get_temp_dir() . '/code_export_' . uniqid() . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($temp_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($temp_dir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        array_map('unlink', glob("$temp_dir/*.*"));
        rmdir($temp_dir);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $folder['name'] . '.zip"');
        header('Content-Length: ' . filesize($zip_file));
        readfile($zip_file);
        unlink($zip_file);
    } else {
        throw new Exception('Не удалось создать архив');
    }
} catch (Exception $e) {
    http_response_code(500);
    exit('Ошибка: ' . $e->getMessage());
}
?> 