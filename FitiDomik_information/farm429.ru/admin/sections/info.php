<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create':
                $created_at = $_POST['created_at'] ? date('Y-m-d H:i:s', strtotime($_POST['created_at'])) : date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("INSERT INTO timeline (title, content, created_at) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['title'], $_POST['content'], $created_at]);
                $success = "Запись успешно добавлена";
                break;
            case 'update':
                $created_at = $_POST['created_at'] ? date('Y-m-d H:i:s', strtotime($_POST['created_at'])) : date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("UPDATE timeline SET title = ?, content = ?, created_at = ? WHERE id = ?");
                $stmt->execute([$_POST['title'], $_POST['content'], $created_at, $_POST['id']]);
                $success = "Запись успешно обновлена";
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM timeline WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = "Запись успешно удалена";
                break;
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
try {
    $stmt = $pdo->query("SELECT * FROM timeline ORDER BY created_at DESC");
    $info = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Ошибка при получении списка записей";
    $info = [];
}
?>
<style>
.card-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
}
.card-content.expanded {
    max-height: 1000px; /* Достаточно большое значение для любого контента */
    transition: max-height 0.3s ease-in;
}
.toggle-btn {
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    margin-right: 0.5rem;
    border: none;
    background: none;
    color: var(--text-color);
}
.toggle-btn i {
    transition: transform 0.3s ease;
}
.toggle-btn.expanded i {
    transform: rotate(180deg);
}
.card-header {
    display: flex;
    align-items: center;
    background-color: var(--card-bg);
    border-bottom: 1px solid var(--border-color);
    padding: 1rem;
}
.card-header h5 {
    margin: 0;
}
</style>
<div class="container">
    <h1 class="mb-4">О ферме</h1>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <!-- Форма добавления записи -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Добавить запись</h5>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label for="title" class="form-label">Заголовок</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">Содержание</label>
                    <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="created_at" class="form-label">Дата и время публикации</label>
                    <input type="datetime-local" class="form-control" id="created_at" name="created_at">
                    <div class="form-text">Оставьте пустым для использования текущего времени</div>
                </div>
                <button type="submit" class="btn btn-primary">Добавить запись</button>
            </form>
        </div>
    </div>
    <!-- Список записей -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Список записей</h5>
            <?php if (empty($info)): ?>
                <p class="text-muted">Записей пока нет</p>
            <?php else: ?>
                <?php foreach ($info as $item): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <button class="toggle-btn" onclick="toggleContent(<?php echo $item['id']; ?>)">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <div class="ms-auto">
                                <small class="text-muted me-3">
                                    Дата: <?php echo date('d.m.Y H:i', strtotime($item['created_at'])); ?>
                                </small>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $item['id']; ?>">
                                        Редактировать
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены?')">Удалить</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div id="content-<?php echo $item['id']; ?>" class="card-content">
                            <div class="card-body">
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Модальное окно редактирования -->
                    <div class="modal fade" id="editModal<?php echo $item['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Редактирование записи</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <div class="mb-3">
                                            <label for="edit_title<?php echo $item['id']; ?>" class="form-label">Заголовок</label>
                                            <input type="text" class="form-control" id="edit_title<?php echo $item['id']; ?>" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_content<?php echo $item['id']; ?>" class="form-label">Содержание</label>
                                            <textarea class="form-control" id="edit_content<?php echo $item['id']; ?>" name="content" rows="5" required><?php echo htmlspecialchars($item['content']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_created_at<?php echo $item['id']; ?>" class="form-label">Дата и время публикации</label>
                                            <input type="datetime-local" class="form-control" id="edit_created_at<?php echo $item['id']; ?>" name="created_at" value="<?php echo date('Y-m-d\TH:i', strtotime($item['created_at'])); ?>">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                        <button type="submit" class="btn btn-primary">Сохранить</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Добавляем JavaScript в конец файла -->
<script>
function toggleContent(id) {
    const content = document.getElementById(`content-${id}`);
    const btn = content.previousElementSibling.querySelector('.toggle-btn');
    content.classList.toggle('expanded');
    btn.classList.toggle('expanded');
}
document.addEventListener('DOMContentLoaded', function() {
    const contents = document.querySelectorAll('.card-content');
    contents.forEach(content => {
        content.classList.remove('expanded');
    });
});
</script> 