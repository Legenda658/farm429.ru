<?php
ob_start();
// Проверяем, запущена ли сессия
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Проверка авторизации
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die('Доступ запрещен');
}
// Создаем директорию для загрузки, если она не существует
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/gallery/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
// Обработка загрузки изображения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            switch ($_POST['action']) {
                case 'add':
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $title = $_POST['title'] ?? 'Без названия';
                        // Проверка типа файла
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($fileInfo, $_FILES['image']['tmp_name']);
                        finfo_close($fileInfo);
                        if (!in_array($mimeType, $allowedTypes)) {
                            throw new Exception('Недопустимый тип файла. Разрешены только изображения (JPEG, PNG, GIF, WEBP)');
                        }
                        // Генерация уникального имени файла
                        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $filename = uniqid() . '.' . $extension;
                        $uploadPath = $uploadDir . $filename;
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                            $stmt = $pdo->prepare("INSERT INTO photos (title, filename, created_at) VALUES (?, ?, NOW())");
                            $stmt->execute([$title, $filename]);
                            $_SESSION['success'] = 'Изображение успешно загружено';
                        } else {
                            throw new Exception('Ошибка при загрузке файла');
                        }
                    }
                    break;
                case 'delete':
                    if (isset($_POST['id'])) {
                        // Получаем имя файла перед удалением записи
                        $stmt = $pdo->prepare("SELECT filename FROM photos WHERE id = ?");
                        $stmt->execute([$_POST['id']]);
                        $photo = $stmt->fetch();
                        if ($photo) {
                            // Удаляем файл
                            $filePath = $uploadDir . $photo['filename'];
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                            // Удаляем запись из БД
                            $stmt = $pdo->prepare("DELETE FROM photos WHERE id = ?");
                            $stmt->execute([$_POST['id']]);
                            $_SESSION['success'] = 'Изображение успешно удалено';
                        }
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        // Используем JavaScript для редиректа вместо header()
        echo '<script>window.location.href = "index.php?section=gallery";</script>';
        exit;
    }
}
// Получение списка изображений
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT * FROM photos ORDER BY created_at DESC");
    $photos = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
<div class="container-fluid">
    <h2 class="mb-4">Управление галереей</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    <!-- Форма загрузки -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Добавить новое изображение</h5>
            <form action="index.php?section=gallery" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label for="title" class="form-label">Название</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Изображение</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary">Загрузить</button>
            </form>
        </div>
    </div>
    <!-- Галерея -->
    <div class="row">
        <?php foreach ($photos as $photo): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="/uploads/gallery/<?php echo htmlspecialchars($photo['filename']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($photo['title']); ?>"
                         style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($photo['title']); ?></h5>
                        <p class="card-text">
                            <small class="text-muted">
                                Добавлено: <?php echo date('d.m.Y H:i', strtotime($photo['created_at'])); ?>
                            </small>
                        </p>
                        <form action="index.php?section=gallery" method="post" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $photo['id']; ?>">
                            <button type="submit" class="btn btn-danger" 
                                    onclick="return confirm('Вы уверены, что хотите удалить это изображение?')">
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<style>
/* Общие стили */
.container-fluid {
    color: var(--text-color);
}
h2, h5 {
    color: var(--text-color);
}
/* Light theme styles */
[data-theme="light"] .card {
    background-color: #ffffff;
    border-color: #dee2e6;
}
[data-theme="light"] .card-body {
    background-color: #ffffff;
    color: #212529;
}
[data-theme="light"] .card-title {
    color: #212529;
}
[data-theme="light"] .card-text {
    color: #212529;
}
[data-theme="light"] .form-control {
    background-color: #ffffff;
    border-color: #dee2e6;
    color: #212529;
}
[data-theme="light"] .text-muted {
    color: #6c757d !important;
}
/* Dark theme styles */
[data-theme="dark"] .card {
    background-color: #2b2b2b;
    border-color: #404040;
}
[data-theme="dark"] .card-body {
    background-color: #2b2b2b;
    color: #e0e0e0;
}
[data-theme="dark"] .card-title {
    color: #e0e0e0;
}
[data-theme="dark"] .card-text {
    color: #e0e0e0;
}
[data-theme="dark"] .form-control {
    background-color: #1e1e1e;
    border-color: #404040;
    color: #e0e0e0;
}
[data-theme="dark"] .text-muted {
    color: #a0a0a0 !important;
}
/* Карточки */
.card {
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
/* Изображения */
.card-img-top {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-bottom: 1px solid var(--border-color);
}
/* Формы */
.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
/* Кнопки */
.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}
.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    color: #fff;
}
[data-theme="light"] .btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}
[data-theme="light"] .btn-danger:hover {
    background-color: #bb2d3b;
    border-color: #b02a37;
}
/* Алерты */
.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}
[data-theme="dark"] .alert-success {
    background-color: #1e4731;
    border-color: #2a6244;
    color: #d4edda;
}
.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}
[data-theme="dark"] .alert-danger {
    background-color: #471e1e;
    border-color: #622a2a;
    color: #f8d7da;
}
/* Адаптивность */
@media (max-width: 768px) {
    .card-img-top {
        height: 150px;
    }
}
</style> 