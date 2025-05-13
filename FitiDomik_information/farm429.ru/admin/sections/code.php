<?php
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /admin/index.php');
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'get_folder_content') {
    header('Content-Type: text/html; charset=utf-8');
    ob_clean(); 
    $stmt = $pdo->prepare("SELECT * FROM code_snippets WHERE folder_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_POST['folder_id']]);
    $snippets = $stmt->fetchAll();
    if (empty($snippets)) {
        echo '<p class="text-muted mt-2">В этой папке пока нет файлов</p>';
    } else {
        foreach ($snippets as $snippet) {
            echo '<div class="list-group-item border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">'.htmlspecialchars($snippet['title']).'</h6>';
            if ($snippet['description']) {
                echo '<p class="mb-1 text-muted small">'.htmlspecialchars($snippet['description']).'</p>';
            }
            echo '<small class="text-muted">
                    Язык: '.htmlspecialchars($snippet['language']).' | 
                    Создан: '.date('d.m.Y H:i', strtotime($snippet['created_at'])).'
                </small>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary view-code" 
                                data-id="'.$snippet['id'].'">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success edit-code" 
                                data-id="'.$snippet['id'].'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-code" 
                                data-id="'.$snippet['id'].'">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>';
        }
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create_folder':
                $stmt = $pdo->prepare("INSERT INTO code_folders (name, parent_id) VALUES (?, ?)");
                $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
                $stmt->execute([$_POST['name'], $parent_id]);
                $success = "Папка успешно создана";
                break;
            case 'delete_folder':
                $stmt = $pdo->prepare("DELETE FROM code_snippets WHERE folder_id = ?");
                $stmt->execute([$_POST['id']]);
                $stmt = $pdo->prepare("DELETE FROM code_folders WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = "Папка успешно удалена";
                break;
            case 'create_snippet':
                $stmt = $pdo->prepare("INSERT INTO code_snippets (title, description, code, language, folder_id) VALUES (?, ?, ?, ?, ?)");
                $folder_id = !empty($_POST['folder_id']) ? $_POST['folder_id'] : null;
                $stmt->execute([
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['code'],
                    $_POST['language'],
                    $folder_id
                ]);
                $success = "Сниппет успешно создан";
                break;
            case 'update_snippet':
                $stmt = $pdo->prepare("UPDATE code_snippets SET title = ?, description = ?, code = ?, language = ?, folder_id = ? WHERE id = ?");
                $folder_id = !empty($_POST['folder_id']) ? $_POST['folder_id'] : null;
                $stmt->execute([
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['code'],
                    $_POST['language'],
                    $folder_id,
                    $_POST['id']
                ]);
                $success = "Сниппет успешно обновлен";
                break;
            case 'delete_snippet':
                $stmt = $pdo->prepare("DELETE FROM code_snippets WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = "Сниппет успешно удален";
                break;
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
try {
    $stmt = $pdo->query("SELECT * FROM code_folders ORDER BY parent_id, name");
    $folders = $stmt->fetchAll();
    $folderTree = [];
    $allFolders = [];
    foreach ($folders as $folder) {
        $allFolders[$folder['id']] = $folder;
        $allFolders[$folder['id']]['children'] = [];
    }
    foreach ($allFolders as $id => $folder) {
        if ($folder['parent_id'] === null) {
            $folderTree[] = &$allFolders[$id];
        } else {
            $allFolders[$folder['parent_id']]['children'][] = &$allFolders[$id];
        }
    }
} catch(PDOException $e) {
    $error = "Ошибка при получении списка папок";
}
$current_folder_id = $_GET['folder_id'] ?? null;
try {
    if ($current_folder_id) {
        $stmt = $pdo->prepare("SELECT * FROM code_snippets WHERE folder_id = ? ORDER BY created_at DESC");
        $stmt->execute([$current_folder_id]);
    } else {
        $stmt = $pdo->query("SELECT * FROM code_snippets ORDER BY created_at DESC");
    }
    $snippets = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Ошибка при получении списка сниппетов";
}
function renderFolderTree($folders, $level = 0) {
    $html = '';
    foreach ($folders as $folder) {
            $html .= sprintf(
                '<div class="folder-item" style="margin-left: %dpx;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <a href="?section=code&folder_id=%d" class="folder-name">%s</a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_folder">
                            <input type="hidden" name="id" value="%d">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Вы уверены?\')">Удалить</button>
                        </form>
                    </div>
                    %s
                </div>',
                $level * 20,
                $folder['id'],
                htmlspecialchars($folder['name']),
                $folder['id'],
            !empty($folder['children']) ? renderFolderTree($folder['children'], $level + 1) : ''
            );
    }
    return $html;
}
?>
<div class="container">
    <h1 class="mb-4">Управление кодами</h1>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="row">
        <!-- Блок структуры папок и файлов -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Структура папок</h5>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-primary mb-2 w-100" id="import_folder">
                        <i class="fas fa-folder-upload"></i> Загрузить папку
                    </button>
                    <button type="button" class="btn btn-primary mb-3 w-100" id="import_code">
                        <i class="fas fa-file-import"></i> Загрузить код
                    </button>
                    <form id="folderForm" class="mb-3">
                        <div class="mb-3">
                            <label for="folderName" class="form-label">Название папки</label>
                            <input type="text" class="form-control" id="folderName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="parentFolder" class="form-label">Родительская папка</label>
                            <select class="form-select" id="parentFolder" name="parent_id">
                                <option value="">Корневая директория</option>
                                <?php foreach ($folders as $folder): ?>
                                <option value="<?php echo $folder['id']; ?>"><?php echo htmlspecialchars($folder['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-folder-plus"></i> Создать папку
                        </button>
                    </form>
                    <!-- Список существующих папок -->
                    <div class="list-group mt-3">
                        <a href="?section=code" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-folder text-warning me-2"></i>
                                Корневая директория
                            </div>
                            <?php if (isset($_GET['folder_id']) || isset($_GET['snippet_id'])): ?>
                            <span class="badge bg-primary">Назад</span>
                            <?php endif; ?>
                        </a>
                        <?php foreach ($folders as $folder): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="?section=code&folder_id=<?php echo $folder['id']; ?>" class="text-decoration-none text-body d-flex align-items-center flex-grow-1">
                                <i class="fas fa-folder text-warning me-2"></i>
                                <?php echo htmlspecialchars($folder['name']); ?>
                            </a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_folder">
                                <input type="hidden" name="id" value="<?php echo $folder['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Вы уверены, что хотите удалить эту папку?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                        <?php if (!isset($_GET['folder_id']) && !isset($_GET['snippet_id'])): ?>
                            <?php if (empty($snippets)): ?>
                                <p class="text-muted mt-2">В корневой директории нет файлов</p>
                            <?php else: ?>
                                <?php foreach ($snippets as $snippet): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($snippet['title']); ?></h6>
                                            <?php if ($snippet['description']): ?>
                                                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($snippet['description']); ?></p>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                Язык: <?php echo htmlspecialchars($snippet['language']); ?> | 
                                                Создан: <?php echo date('d.m.Y H:i', strtotime($snippet['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary view-code" 
                                                    data-id="<?php echo $snippet['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success edit-code" 
                                                    data-id="<?php echo $snippet['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-code" 
                                                    data-id="<?php echo $snippet['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (isset($_GET['folder_id'])): ?>
                            <?php if (empty($snippets)): ?>
                                <p class="text-muted mt-2">В этой папке пока нет файлов</p>
                            <?php else: ?>
                                <?php foreach ($snippets as $snippet): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($snippet['title']); ?></h6>
                                            <?php if ($snippet['description']): ?>
                                                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($snippet['description']); ?></p>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                Язык: <?php echo htmlspecialchars($snippet['language']); ?> | 
                                                Создан: <?php echo date('d.m.Y H:i', strtotime($snippet['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary view-code" 
                                                    data-id="<?php echo $snippet['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success edit-code" 
                                                    data-id="<?php echo $snippet['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-code" 
                                                    data-id="<?php echo $snippet['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Блок управления кодом -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Добавление кода</h5>
                </div>
                <div class="card-body">
                    <form id="codeForm">
                        <input type="hidden" name="action" value="create_snippet">
                        <div class="mb-3">
                            <label for="codeFolder" class="form-label">Папка</label>
                            <select class="form-select" id="codeFolder" name="folder_id">
                                <option value="">Корневая директория</option>
                                <?php foreach ($folders as $folder): ?>
                                <option value="<?php echo $folder['id']; ?>"><?php echo htmlspecialchars($folder['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="codeTitle" class="form-label">Название</label>
                            <input type="text" class="form-control" id="codeTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="codeDescription" class="form-label">Описание</label>
                            <textarea class="form-control" id="codeDescription" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="codeLanguage" class="form-label">Язык программирования</label>
                            <select class="form-select" id="codeLanguage" name="language" required>
                                <option value="PHP">PHP</option>
                                <option value="JavaScript">JavaScript</option>
                                <option value="HTML">HTML</option>
                                <option value="CSS">CSS</option>
                                <option value="Python">Python</option>
                                <option value="SQL">SQL</option>
                                <option value="Java">Java</option>
                                <option value="C++">C++</option>
                                <option value="C#">C#</option>
                                <option value="Markdown">Markdown</option>
                                <option value="htaccess">htaccess</option>
                                <option value="Text">Text</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="codeEditor" class="form-label">Код</label>
                            <div id="codeEditor"></div>
                            <input type="hidden" id="codeContent" name="code" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Сохранить код
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
/* Основные цвета для тем */
:root[data-theme="light"] {
    --bg-color: #ffffff;
    --text-color: #212529;
    --border-color: #dee2e6;
    --card-bg: #ffffff;
    --sidebar-bg: #f8f9fa;
    --hover-bg: #e9ecef;
    --muted-text: #6c757d;
    --folder-text: #212529;
    --nav-link-color: #212529;
}
:root[data-theme="dark"] {
    --bg-color: #212529;
    --text-color: #ffffff;
    --border-color: #495057;
    --card-bg: #2c3034;
    --sidebar-bg: #2c3034;
    --hover-bg: #3d4246;
    --muted-text: #adb5bd;
    --folder-text: #ffffff;
    --nav-link-color: #ffffff;
}
/* Общие стили */
.container {
    background-color: var(--bg-color) !important;
    color: var(--text-color);
    min-height: 100vh;
    padding: 20px;
}
[data-theme="dark"] .container,
[data-theme="dark"] body,
[data-theme="dark"] main {
    background-color: #212529 !important;
}
[data-theme="dark"] .content-wrapper {
    background-color: #212529 !important;
}
[data-theme="dark"] .content {
    background-color: #212529 !important;
}
/* Карточки */
.card {
    background-color: var(--card-bg) !important;
    border-color: var(--border-color);
    color: var(--text-color);
}
.card-header {
    background-color: var(--card-bg) !important;
    border-bottom: 1px solid var(--border-color);
}
.card-body {
    background-color: var(--card-bg) !important;
    color: var(--text-color);
}
/* Формы */
.form-control, .form-select {
    background-color: var(--bg-color) !important;
    border-color: var(--border-color);
    color: var(--text-color) !important;
}
.form-control:focus, .form-select:focus {
    background-color: var(--bg-color) !important;
    color: var(--text-color) !important;
    border-color: #0d6efd;
}
/* Модальные окна */
.modal-content {
    background-color: var(--card-bg) !important;
    color: var(--text-color);
}
.modal-header, .modal-footer {
    background-color: var(--card-bg) !important;
    border-color: var(--border-color);
}
/* CodeMirror */
.CodeMirror {
    background-color: var(--bg-color) !important;
    color: var(--text-color) !important;
    border: 1px solid var(--border-color);
}
.CodeMirror-gutters {
    background-color: var(--card-bg) !important;
    border-right: 1px solid var(--border-color) !important;
}
.CodeMirror-linenumbers {
    background-color: var(--card-bg) !important;
}
.CodeMirror-linenumber {
    color: var(--muted-text) !important;
}
/* Списки */
.list-group-item {
    background-color: var(--card-bg) !important;
    border-color: var(--border-color);
    color: var(--text-color) !important;
}
.list-group-item:hover {
    background-color: var(--hover-bg) !important;
}
/* Текст */
.text-muted {
    color: var(--muted-text) !important;
}
h1, h2, h3, h4, h5, h6, .card-title {
    color: var(--text-color) !important;
}
/* Кнопки */
.btn-outline-primary, .btn-outline-danger, .btn-outline-success {
    background-color: transparent !important;
}
.btn-outline-primary:hover, .btn-outline-danger:hover, .btn-outline-success:hover {
    background-color: var(--hover-bg) !important;
    color: var(--text-color) !important;
}
/* Алерты */
.alert {
    background-color: var(--card-bg) !important;
    border-color: var(--border-color);
    color: var(--text-color) !important;
}
/* Превью кода */
#preview-code-container {
    background-color: var(--bg-color) !important;
    border: 1px solid var(--border-color);
}
#codeEditor {
    background-color: var(--bg-color) !important;
    border: 1px solid var(--border-color);
}
/* Описание */
#preview-description {
    color: var(--muted-text) !important;
}
/* Улучшенные стили для папок */
[data-theme="dark"] .folder-name,
[data-theme="dark"] .list-group-item a {
    color: var(--folder-text) !important;
    font-weight: 500;
}
[data-theme="dark"] .fa-folder {
    color: #ffc107 !important;
}
/* Исправление отображения иконок папок и файлов */
.fa-folder, .fa-file-code {
    margin-right: 5px;
}
/* Стили для элементов списка папок */
.list-group a {
    color: var(--folder-text) !important;
    text-decoration: none !important;
}
.list-group a:hover {
    color: #0d6efd !important;
}
/* Стили для навигационных ссылок */
.nav-link {
    color: var(--nav-link-color) !important;
}
.nav-link:hover {
    color: var(--nav-link-color) !important;
    opacity: 0.8;
}
.nav-link.active {
    color: var(--nav-link-color) !important;
    font-weight: 500;
}
</style>
<!-- Подключаем Bootstrap CSS и JS -->
<link href="https:
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Подключаем Font Awesome для иконок -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<!-- Подключаем CodeMirror для редактирования кода -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
<!-- Модальное окно для просмотра/редактирования кода -->
<div class="modal fade" id="codeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактирование кода</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form method="POST" id="editCodeForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_snippet">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Название</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Описание</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_code" class="form-label">Код</label>
                        <textarea class="form-control code-editor" id="edit_code" name="code" rows="10"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_code_file" class="form-label">Или загрузите файл с кодом</label>
                        <input type="file" class="form-control" id="edit_code_file" accept=".php,.js,.html,.css,.py,.sql,.txt,.java,.cpp,.cs,.md,.htaccess">
                        <small class="form-text text-muted">Поддерживаемые форматы: PHP, JavaScript, HTML, CSS, Python, SQL, TXT, Java, C++, C#, Markdown, htaccess</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_language" class="form-label">Язык</label>
                        <select class="form-control" id="edit_language" name="language" required>
                            <option value="PHP">PHP</option>
                            <option value="JavaScript">JavaScript</option>
                            <option value="HTML">HTML</option>
                            <option value="CSS">CSS</option>
                            <option value="Python">Python</option>
                            <option value="SQL">SQL</option>
                            <option value="Java">Java</option>
                            <option value="C++">C++</option>
                            <option value="C#">C#</option>
                            <option value="Markdown">Markdown</option>
                            <option value="htaccess">htaccess</option>
                            <option value="Text">Text</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_folder_id" class="form-label">Папка</label>
                        <select class="form-control" id="edit_folder_id" name="folder_id">
                            <option value="">Корневая директория</option>
                            <?php foreach ($folders as $folder): ?>
                            <option value="<?php echo $folder['id']; ?>"><?php echo htmlspecialchars($folder['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary" id="save_edited_code">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Модальное окно для предпросмотра кода -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Просмотр кода</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <h6 id="preview-title" class="mb-2"></h6>
                <div id="preview-description" class="text-muted mb-3"></div>
                <div id="preview-code-container"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
<!-- Модальное окно выбора папки назначения -->
<div class="modal fade" id="selectFolderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Выберите папку назначения</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="destinationFolder" class="form-label">Папка назначения</label>
                    <select class="form-select" id="destinationFolder">
                        <option value="">Корневая директория</option>
                        <?php foreach ($folders as $folder): ?>
                        <option value="<?php echo $folder['id']; ?>"><?php echo htmlspecialchars($folder['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="confirmFolderUpload">Загрузить</button>
            </div>
        </div>
    </div>
</div>
<script>
let selectedFiles = null;
let folderInput = null;
function getModeForLanguage(language) {
    const modes = {
        'PHP': 'text/x-php',
        'JavaScript': 'text/javascript',
        'HTML': 'text/html',
        'CSS': 'text/css',
        'Python': 'text/x-python',
        'SQL': 'text/x-sql',
        'Java': 'text/x-java',
        'C++': 'text/x-c++src',
        'C#': 'text/x-csharp'
    };
    return modes[language] || 'text/plain';
}
document.addEventListener('DOMContentLoaded', function() {
    window.codeEditor = CodeMirror(document.getElementById('codeEditor'), {
        lineNumbers: true,
        mode: 'text/x-php',
        theme: document.documentElement.getAttribute('data-theme') === 'dark' ? 'monokai' : 'default',
        indentUnit: 4,
        smartIndent: true,
        lineWrapping: true,
        matchBrackets: true,
        autoCloseBrackets: true,
        viewportMargin: Infinity
    });
    window.codeEditor.on('change', function() {
        document.getElementById('codeContent').value = window.codeEditor.getValue();
    });
    const addPassiveListeners = (editor) => {
        const wrapper = editor.getWrapperElement();
        wrapper.addEventListener('touchstart', () => {}, { passive: true });
        wrapper.addEventListener('touchmove', () => {}, { passive: true });
        wrapper.addEventListener('mousewheel', () => {}, { passive: true });
    };
    addPassiveListeners(window.codeEditor);
    document.querySelectorAll('.view-code').forEach(btn => {
        btn.addEventListener('click', function() {
            viewSnippet(this.dataset.id);
        });
    });
    document.querySelectorAll('.edit-code').forEach(btn => {
        btn.addEventListener('click', function() {
            editSnippet(this.dataset.id);
        });
    });
    document.querySelectorAll('.delete-code').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Вы уверены, что хотите удалить этот код?')) {
                deleteSnippet(this.dataset.id);
            }
        });
    });
    document.getElementById('import_code').addEventListener('click', function() {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.php,.js,.html,.css,.py,.sql,.txt,.java,.cpp,.cs,.md,.htaccess';
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);
        fileInput.click();
        fileInput.addEventListener('change', function() {
            const file = fileInput.files[0];
            if (!file) {
                document.body.removeChild(fileInput);
                return;
            }
            const extension = file.name.split('.').pop().toLowerCase();
            const languageMap = {
                'php': 'PHP',
                'js': 'JavaScript',
                'html': 'HTML',
                'css': 'CSS',
                'py': 'Python',
                'sql': 'SQL',
                'java': 'Java',
                'cpp': 'C++',
                'cs': 'C#',
                'md': 'Markdown',
                'htaccess': 'htaccess',
                'txt': 'Text'
            };
            const reader = new FileReader();
            reader.onload = function(e) {
                const fileContent = e.target.result;
                document.getElementById('codeTitle').value = file.name.replace(/\.[^/.]+$/, "");
                window.codeEditor.setValue(fileContent);
                const language = languageMap[extension] || 'Text';
                const languageSelect = document.getElementById('codeLanguage');
                if (languageSelect.querySelector(`option[value="${language}"]`)) {
                    languageSelect.value = language;
                    window.codeEditor.setOption('mode', getModeForLanguage(language));
                }
                document.getElementById('codeContent').value = fileContent;
            };
            reader.readAsText(file);
            document.body.removeChild(fileInput);
        });
    });
    document.getElementById('folderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('action', 'create_folder');
        formData.append('name', document.getElementById('folderName').value.trim());
        formData.append('parent_id', document.getElementById('parentFolder').value);
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети');
            }
            return response.text();
        })
        .then(() => {
            location.reload();
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Ошибка при создании папки');
        });
    });
    document.getElementById('codeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        document.getElementById('codeContent').value = window.codeEditor.getValue();
        const title = document.getElementById('codeTitle').value.trim();
        const code = document.getElementById('codeContent').value.trim();
        const language = document.getElementById('codeLanguage').value;
        if (!title || !code || !language) {
            alert('Пожалуйста, заполните все обязательные поля');
            return;
        }
        const formData = new FormData(this);
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети');
            }
            return response.text();
        })
        .then(() => {
            alert('Код успешно сохранен');
            location.reload();
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Ошибка при сохранении кода');
        });
    });
    document.getElementById('editCodeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (window.editEditor) {
            document.getElementById('edit_code').value = window.editEditor.getValue();
        }
        const title = document.getElementById('edit_title').value.trim();
        const code = document.getElementById('edit_code').value.trim();
        const language = document.getElementById('edit_language').value;
        if (!title || !code || !language) {
            alert('Пожалуйста, заполните все обязательные поля');
            return;
        }
        const formData = new FormData(this);
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети');
            }
            return response.text();
        })
        .then(() => {
            alert('Код успешно обновлен');
            const modal = bootstrap.Modal.getInstance(document.getElementById('codeModal'));
            if (modal) {
                modal.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Ошибка при обновлении кода: ' + error.message);
        });
    });
    setupModalClose();
    document.getElementById('import_folder').addEventListener('click', function() {
        folderInput = document.createElement('input');
        folderInput.type = 'file';
        folderInput.webkitdirectory = true;
        folderInput.multiple = true;
        folderInput.style.display = 'none';
        document.body.appendChild(folderInput);
        folderInput.addEventListener('change', function() {
            selectedFiles = this.files;
            if (selectedFiles.length === 0) {
                document.body.removeChild(folderInput);
                return;
            }
            const modal = new bootstrap.Modal(document.getElementById('selectFolderModal'));
            modal.show();
        });
        folderInput.click();
    });
    document.getElementById('confirmFolderUpload').addEventListener('click', async function() {
        if (!selectedFiles) return;
        const formData = new FormData();
        const basePath = selectedFiles[0].webkitRelativePath.split('/')[0];
        const destinationFolderId = document.getElementById('destinationFolder').value;
        formData.append('folder_name', basePath);
        if (destinationFolderId) {
            formData.append('parent_folder_id', destinationFolderId);
        }
        Array.from(selectedFiles).forEach(file => {
            formData.append('files[]', file);
            formData.append('paths[]', file.webkitRelativePath);
        });
        try {
            const response = await fetch('/pages/code/upload_folder.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                alert('Папка успешно загружена');
                location.reload();
            } else {
                alert('Ошибка при загрузке папки: ' + result.error);
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Ошибка при загрузке папки');
        }
        const modal = bootstrap.Modal.getInstance(document.getElementById('selectFolderModal'));
        modal.hide();
        selectedFiles = null;
        if (folderInput) {
            document.body.removeChild(folderInput);
            folderInput = null;
        }
    });
});
function viewSnippet(snippetId) {
    console.log('Запрос на просмотр сниппета ID:', snippetId);
    fetch('api/get_snippet.php?id=' + snippetId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Неизвестная ошибка');
            }
            document.getElementById('preview-title').textContent = data.title;
            document.getElementById('preview-description').textContent = data.description || '';
            const container = document.getElementById('preview-code-container');
            container.innerHTML = '';
            window.previewEditor = CodeMirror(container, {
                value: data.code,
                mode: getModeForLanguage(data.language),
                theme: document.documentElement.getAttribute('data-theme') === 'dark' ? 'monokai' : 'default',
                lineNumbers: true,
                readOnly: true,
                viewportMargin: Infinity
            });
            addPassiveListeners(window.previewEditor);
            setTimeout(() => {
                window.previewEditor.refresh();
            }, 100);
            const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
        })
        .catch(error => {
            console.error('Ошибка при просмотре сниппета:', error);
            alert('Ошибка при загрузке сниппета: ' + error.message);
        });
}
function editSnippet(snippetId) {
    console.log('Запрос на редактирование сниппета ID:', snippetId);
    fetch('api/get_snippet.php?id=' + snippetId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Неизвестная ошибка');
            }
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_language').value = data.language;
            document.getElementById('edit_folder_id').value = data.folder_id || '';
            if (!window.editEditor) {
                window.editEditor = CodeMirror.fromTextArea(
                    document.getElementById('edit_code'),
                    {
            lineNumbers: true,
                        mode: getModeForLanguage(data.language),
                        theme: document.documentElement.getAttribute('data-theme') === 'dark' ? 'monokai' : 'default',
            indentUnit: 4,
            smartIndent: true,
            lineWrapping: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            viewportMargin: Infinity
                    }
                );
            }
            window.editEditor.setValue(data.code || '');
            window.editEditor.setOption('mode', getModeForLanguage(data.language));
            const editModal = new bootstrap.Modal(document.getElementById('codeModal'));
            editModal.show();
        })
        .catch(error => {
            console.error('Ошибка при редактировании сниппета:', error);
            alert('Ошибка при загрузке сниппета: ' + error.message);
        });
}
function addPassiveListeners(editor) {
    if (!editor) return;
    const wrapper = editor.getWrapperElement();
    wrapper.addEventListener('touchstart', () => {}, { passive: true });
    wrapper.addEventListener('touchmove', () => {}, { passive: true });
    wrapper.addEventListener('mousewheel', () => {}, { passive: true });
}
function deleteSnippet(snippetId) {
    console.log('Запрос на удаление сниппета ID:', snippetId);
    if (!confirm('Вы действительно хотите удалить этот сниппет?')) {
        return;
    }
    const formData = new FormData();
    formData.append('action', 'delete_snippet');
    formData.append('id', snippetId);
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Ошибка сети: ' + response.status);
        }
        return response.text();
    })
    .then(() => {
        alert('Сниппет успешно удален');
        location.reload();
    })
    .catch(error => {
        console.error('Ошибка при удалении сниппета:', error);
        alert('Ошибка при удалении сниппета: ' + error.message);
    });
}
function setupModalClose() {
    const modals = ['previewModal', 'codeModal'];
    modals.forEach(modalId => {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', function () {
                const modalBackdrops = document.querySelectorAll('.modal-backdrop');
                modalBackdrops.forEach(backdrop => {
                    backdrop.remove();
                });
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });
        }
    });
}
</script> 