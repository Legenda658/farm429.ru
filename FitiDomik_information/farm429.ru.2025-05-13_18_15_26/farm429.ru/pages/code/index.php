<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
$pageTitle = "Код";
$current_folder_id = isset($_GET['folder']) ? (int)$_GET['folder'] : null;
function getFolderPath($pdo, $folder_id) {
    $path = [];
    while ($folder_id) {
        $stmt = $pdo->prepare("SELECT id, name, parent_id FROM code_folders WHERE id = ?");
        $stmt->execute([$folder_id]);
        if ($folder = $stmt->fetch()) {
            array_unshift($path, [
                'id' => $folder['id'],
                'name' => $folder['name']
            ]);
            $folder_id = $folder['parent_id'];
        } else {
            break;
        }
    }
    return $path;
}
try {
    $stmt = $pdo->prepare("SELECT id, name FROM code_folders WHERE parent_id " . 
        ($current_folder_id === null ? "IS NULL" : "= ?") . " ORDER BY name");
    if ($current_folder_id === null) {
        $stmt->execute();
    } else {
        $stmt->execute([$current_folder_id]);
    }
    $folders = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT id, title, description, language FROM code_snippets WHERE folder_id " . 
        ($current_folder_id === null ? "IS NULL" : "= ?") . " ORDER BY title");
    if ($current_folder_id === null) {
        $stmt->execute();
    } else {
        $stmt->execute([$current_folder_id]);
    }
    $codes = $stmt->fetchAll();
    $folder_path = $current_folder_id ? getFolderPath($pdo, $current_folder_id) : [];
    ob_start();
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Просмотр кода</h1>
    </div>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="?">Корневая директория</a></li>
            <?php foreach ($folder_path as $folder): ?>
                <li class="breadcrumb-item <?php echo $folder['id'] == $current_folder_id ? 'active' : ''; ?>">
                    <?php if ($folder['id'] == $current_folder_id): ?>
                        <?php echo htmlspecialchars($folder['name']); ?>
                    <?php else: ?>
                        <a href="?folder=<?php echo $folder['id']; ?>"><?php echo htmlspecialchars($folder['name']); ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <div class="row">
        <!-- Список папок -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Папки</h5>
                    <?php if ($current_folder_id): ?>
                    <a href="download.php?folder=<?php echo $current_folder_id; ?>" class="btn btn-sm btn-success">
                        <i class="bi bi-download"></i> Скачать папку
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($folders)): ?>
                        <p class="text-muted">Папок пока нет</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($folders as $folder): ?>
                                <a href="?folder=<?php echo $folder['id']; ?>" class="list-group-item list-group-item-action">
                                    <i class="bi bi-folder-fill text-warning"></i>
                                    <?php echo htmlspecialchars($folder['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Список кода -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Код</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($codes)): ?>
                        <p class="text-muted">Кода пока нет</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($codes as $code): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($code['title']); ?></h6>
                                            <?php if ($code['description']): ?>
                                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($code['description']); ?></p>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                Язык: <?php echo htmlspecialchars($code['language']); ?>
                                            </small>
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary view-code" 
                                                    data-id="<?php echo $code['id']; ?>">
                                                <i class="bi bi-eye"></i> Просмотр
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success copy-code" 
                                                    data-id="<?php echo $code['id']; ?>">
                                                <i class="bi bi-clipboard"></i> Копировать
                                            </button>
                                            <a href="download.php?id=<?php echo $code['id']; ?>" 
                                               class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-download"></i> Скачать
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Модальное окно для просмотра кода -->
<div class="modal fade" id="viewCodeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre><code id="codeContent"></code></pre>
            </div>
        </div>
    </div>
</div>
<!-- Стили -->
<link href="https:
<link href="https:
<link href="https:
<!-- Скрипты -->
<script src="https:
<script src="https:
<script src="https:
<script src="https:
<script src="https:
<script src="https:
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewModal = new bootstrap.Modal(document.getElementById('viewCodeModal'));
    const codeContent = document.getElementById('codeContent');
    document.querySelectorAll('.view-code').forEach(button => {
        button.addEventListener('click', function() {
            const snippetId = this.dataset.id;
            fetch('get_snippet.php?id=' + snippetId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('#viewCodeModal .modal-title').textContent = data.title;
                        codeContent.textContent = data.code;
                        codeContent.className = 'language-' + data.language.toLowerCase();
                        hljs.highlightElement(codeContent);
                        viewModal.show();
                    } else {
                        alert(data.error || 'Ошибка при загрузке кода');
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Ошибка при загрузке кода');
                });
        });
    });
    document.querySelectorAll('.copy-code').forEach(button => {
        button.addEventListener('click', function() {
            const snippetId = this.dataset.id;
            fetch('get_snippet.php?id=' + snippetId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        navigator.clipboard.writeText(data.code)
                            .then(() => {
                                const icon = this.querySelector('i');
                                icon.classList.remove('bi-clipboard');
                                icon.classList.add('bi-check2');
                                setTimeout(() => {
                                    icon.classList.remove('bi-check2');
                                    icon.classList.add('bi-clipboard');
                                }, 2000);
                            })
                            .catch(err => {
                                console.error('Ошибка при копировании:', err);
                                alert('Ошибка при копировании кода');
                            });
                    } else {
                        alert(data.error || 'Ошибка при загрузке кода');
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Ошибка при загрузке кода');
                });
        });
    });
});
</script>
<style>
/* Общие стили */
.container {
    color: var(--text-color);
}
h1, h5, h6 {
    color: var(--text-color);
}
/* Хлебные крошки */
.breadcrumb {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 0.75rem 1rem;
}
.breadcrumb-item a {
    color: var(--link-color);
}
.breadcrumb-item.active {
    color: var(--text-color);
}
/* Карточки */
.card {
    background-color: var(--card-bg);
    border-color: var(--border-color);
}
.card-header {
    background-color: var(--header-bg);
    border-bottom-color: var(--border-color);
}
/* Список групп */
.list-group-item {
    background-color: var(--card-bg);
    border-color: var(--border-color);
    color: var(--text-color);
}
.list-group-item:hover {
    background-color: var(--hover-bg);
}
.list-group-item-action {
    color: var(--text-color);
}
.list-group-item-action:hover {
    background-color: var(--hover-bg);
    color: var(--text-color);
}
/* Текст */
.text-muted {
    color: var(--muted-text) !important;
}
/* Кнопки */
.btn-outline-primary,
.btn-outline-success,
.btn-outline-info {
    border-color: var(--border-color);
    color: var(--text-color);
}
.btn-outline-primary:hover,
.btn-outline-success:hover,
.btn-outline-info:hover {
    background-color: var(--hover-bg);
    border-color: var(--primary-color);
    color: var(--text-color);
}
/* Модальное окно */
.modal-content {
    background-color: var(--card-bg);
    border-color: var(--border-color);
}
.modal-header {
    border-bottom-color: var(--border-color);
    color: var(--text-color);
}
.modal-body {
    color: var(--text-color);
}
.modal-body pre {
    margin: 0;
    padding: 1rem;
    border-radius: 4px;
    max-height: 600px;
    overflow-y: auto;
}
/* Темная тема */
[data-theme="dark"] .modal-content {
    background-color: var(--card-bg);
}
[data-theme="dark"] pre {
    background-color: #272822;
}
[data-theme="dark"] .hljs {
    background-color: #272822;
}
/* Светлая тема */
[data-theme="light"] pre {
    background-color: #f8f9fa;
}
[data-theme="light"] .hljs {
    background-color: #f8f9fa;
    color: #333;
}
</style>
<?php
    $content = ob_get_clean();
    require_once __DIR__ . '/../../layout.php';
} catch(PDOException $e) {
    error_log("Ошибка при получении данных: " . $e->getMessage());
    die("Произошла ошибка при загрузке данных");
}
?> 