<?php
require_once __DIR__ . '/../../config.php';
function logError($error, $details = null) {
    $errorLog = date('Y-m-d H:i:s') . " - " . $error . "\n";
    if ($details) {
        $errorLog .= "Детали: " . print_r($details, true) . "\n";
    }
    $errorLog .= "--------------------\n";
    error_log($errorLog, 3, __DIR__ . '/../../logs/download_errors.log');
}
function getSafePath($basePath, $filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $basePath . '/' . $filename;
}
function transliterate($text) {
    $cyr = [
        'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
        'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
        'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
        'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я', ' '
    ];
    $lat = [
        'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p',
        'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya',
        'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P',
        'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','E','Yu','Ya', '_'
    ];
    return str_replace($cyr, $lat, $text);
}
function getFolderContents($pdo, $folder_id, $base_path = '') {
    $result = [
        'files' => [],
        'folders' => []
    ];
    $stmt = $pdo->prepare("SELECT id, name FROM code_folders WHERE parent_id = ?");
    $stmt->execute([$folder_id]);
    $subfolders = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT title, code, language FROM code_snippets WHERE folder_id = ?");
    $stmt->execute([$folder_id]);
    $files = $stmt->fetchAll();
    foreach ($files as $file) {
        $result['files'][] = [
            'title' => $file['title'],
            'code' => $file['code'],
            'language' => $file['language'],
            'path' => $base_path
        ];
    }
    foreach ($subfolders as $subfolder) {
        $subfolder_path = $base_path . '/' . transliterate($subfolder['name']);
        $result['folders'][] = [
            'name' => $subfolder['name'],
            'path' => $subfolder_path
        ];
        $subfolder_contents = getFolderContents($pdo, $subfolder['id'], $subfolder_path);
        $result['files'] = array_merge($result['files'], $subfolder_contents['files']);
        $result['folders'] = array_merge($result['folders'], $subfolder_contents['folders']);
    }
    return $result;
}
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT title, code, language FROM code_snippets WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        if ($row = $stmt->fetch()) {
            $extensions = [
                'PHP' => 'php',
                'JavaScript' => 'js',
                'HTML' => 'html',
                'CSS' => 'css',
                'Python' => 'py',
                'SQL' => 'sql',
                'Markdown' => 'md',
                'htaccess' => 'htaccess',
                'Text' => 'txt'
            ];
            if (strtolower($row['title']) === '.htaccess') {
                $filename = '.htaccess';
            } else {
                $extension = $extensions[$row['language']] ?? 'txt';
                $filename = transliterate($row['title']) . '.' . $extension;
            }
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($row['code']));
            echo $row['code'];
        } else {
            logError("Код не найден", ['id' => $_GET['id']]);
            die('Код не найден');
        }
    } catch(PDOException $e) {
        logError("Ошибка при скачивании кода", ['error' => $e->getMessage(), 'id' => $_GET['id']]);
        die('Ошибка сервера: проблема с базой данных');
    }
} elseif (isset($_GET['folder'])) {
    try {
        $folder_id = (int)$_GET['folder'];
        $stmt = $pdo->prepare("SELECT id, name FROM code_folders WHERE id = ?");
        $stmt->execute([$folder_id]);
        $folder = $stmt->fetch();
        if (!$folder) {
            logError("Папка не найдена", ['folder_id' => $folder_id]);
            die('Папка не найдена');
        }
        $temp_dir = __DIR__ . '/../../temp/downloads/' . $folder_id;
        if (!file_exists($temp_dir)) {
            if (!mkdir($temp_dir, 0777, true)) {
                logError("Не удалось создать временную директорию", ['temp_dir' => $temp_dir]);
                die('Ошибка создания временной директории');
            }
        }
        $contents = getFolderContents($pdo, $folder_id);
        if (empty($contents['files']) && empty($contents['folders'])) {
            logError("В папке нет файлов и подпапок", ['folder_id' => $folder_id]);
            die('В папке нет файлов и подпапок');
        }
        $extensions = [
            'PHP' => 'php',
            'JavaScript' => 'js',
            'HTML' => 'html',
            'CSS' => 'css',
            'Python' => 'py',
            'SQL' => 'sql',
            'Markdown' => 'md',
            'htaccess' => 'htaccess',
            'Text' => 'txt'
        ];
        $zip_filename = transliterate($folder['name']) . '.zip';
        $zip_path = getSafePath($temp_dir, $zip_filename);
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            logError("Не удалось создать ZIP архив", ['zip_path' => $zip_path]);
            die('Не удалось создать ZIP архив');
        }
        foreach ($contents['folders'] as $dir) {
            $zip->addEmptyDir(ltrim($dir['path'], '/'));
        }
        foreach ($contents['files'] as $file) {
            try {
                $extension = $extensions[$file['language']] ?? 'txt';
                $filename = transliterate($file['title']) . '.' . $extension;
                $filepath = ltrim($file['path'] . '/' . $filename, '/');
                if (!$zip->addFromString($filepath, $file['code'])) {
                    logError("Ошибка добавления файла в архив", [
                        'filename' => $filepath
                    ]);
                }
            } catch (Exception $e) {
                logError("Ошибка при добавлении файла в архив", [
                    'error' => $e->getMessage(),
                    'filename' => $filepath ?? 'unknown'
                ]);
                continue;
            }
        }
        $zip->close();
        if (!file_exists($zip_path) || filesize($zip_path) === 0) {
            logError("Созданный ZIP архив пуст или не существует", ['zip_path' => $zip_path]);
            die('Ошибка при создании архива');
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_path));
        if (readfile($zip_path) === false) {
            logError("Ошибка отправки файла пользователю", ['zip_path' => $zip_path]);
            die('Ошибка при скачивании файла');
        }
        array_map('unlink', glob("$temp_dir/*.*"));
        rmdir($temp_dir);
        exit;
    } catch(PDOException $e) {
        logError("Ошибка базы данных", [
            'error' => $e->getMessage(),
            'folder_id' => $folder_id ?? null
        ]);
        die('Ошибка сервера: проблема с базой данных - ' . $e->getMessage());
    } catch(Exception $e) {
        logError("Общая ошибка", [
            'error' => $e->getMessage(),
            'folder_id' => $folder_id ?? null
        ]);
        die('Ошибка сервера: ' . $e->getMessage());
    }
} else {
    logError("Не указан ID или folder");
    die('ID не указан');
}
?> 