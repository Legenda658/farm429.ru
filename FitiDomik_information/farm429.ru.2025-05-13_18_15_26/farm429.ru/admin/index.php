<?php
session_start();
require_once '../config.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}
$theme = $_SESSION['theme'] ?? 'light';
if (isset($_POST['theme'])) {
    $theme = $_POST['theme'];
    $_SESSION['theme'] = $theme;
}
$section = $_GET['section'] ?? 'news';
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <!-- Иконки сайта -->
    <link rel="apple-touch-icon" sizes="180x180" href="/icon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icon/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="/icon/favicon.ico">
    <link rel="manifest" href="/icon/site.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root[data-theme="light"] {
            --bg-color: #ffffff;
            --text-color: #000000;
            --sidebar-bg: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --hover-bg: #e9ecef;
            --muted-text: #6c757d;
        }
        :root[data-theme="dark"] {
            --bg-color: #212529;
            --text-color: #ffffff;
            --sidebar-bg: #2c3034;
            --card-bg: #343a40;
            --border-color: #495057;
            --hover-bg: #3d4246;
            --muted-text: #adb5bd;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
        }
        .sidebar {
            background-color: var(--sidebar-bg);
            min-height: 100vh;
            padding: 20px;
            border-right: 1px solid var(--border-color);
        }
        .card {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        .nav-link {
            color: var(--text-color);
            border-radius: 6px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s ease;
        }
        .nav-link:hover {
            color: #0d6efd;
            background-color: var(--hover-bg);
        }
        .nav-link.active {
            background-color: #0d6efd !important;
            color: #ffffff !important;
        }
        .form-check-label {
            color: var(--text-color);
        }
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .home-button {
            display: block;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s ease;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
        }
        .home-button:hover {
            background-color: var(--hover-bg);
            color: #0d6efd;
        }
        .home-button i {
            margin-right: 0.5rem;
        }
        /* Стили для текстовых областей и инпутов */
        .form-control {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-color);
        }
        .form-control:focus {
            background-color: var(--card-bg);
            border-color: #0d6efd;
            color: var(--text-color);
        }
        /* Стили для кода */
        pre, code {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 1rem;
        }
        /* Стили для таблиц */
        .table {
            color: var(--text-color);
        }
        .table td, .table th {
            border-color: var(--border-color);
        }
        .text-muted {
            color: var(--muted-text) !important;
        }
        /* Стили для форм в темной теме */
        [data-theme="dark"] .form-select,
        [data-theme="dark"] .form-control {
            background-color: var(--card-bg) !important;
            border-color: var(--border-color) !important;
            color: var(--text-color) !important;
        }
        [data-theme="dark"] .form-select option {
            background-color: var(--card-bg) !important;
            color: var(--text-color) !important;
        }
        [data-theme="dark"] .modal-content {
            background-color: var(--card-bg) !important;
            border-color: var(--border-color) !important;
        }
        [data-theme="dark"] .modal-header,
        [data-theme="dark"] .modal-footer {
            border-color: var(--border-color) !important;
            background-color: var(--sidebar-bg) !important;
        }
        [data-theme="dark"] .modal-body {
            background-color: var(--card-bg) !important;
        }
        [data-theme="dark"] .form-label {
            color: var(--text-color) !important;
        }
        [data-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        [data-theme="dark"] .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        }
        /* Стили для disabled состояний */
        [data-theme="dark"] .form-control:disabled,
        [data-theme="dark"] .form-control[readonly],
        [data-theme="dark"] .form-select:disabled {
            background-color: var(--sidebar-bg) !important;
            color: var(--muted-text) !important;
        }
        /* Стили для фокуса */
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: var(--card-bg) !important;
            border-color: #0d6efd !important;
            color: var(--text-color) !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
        /* Стили для таблиц в темной теме */
        [data-theme="dark"] .table {
            color: var(--text-color) !important;
            border-color: var(--border-color) !important;
        }
        [data-theme="dark"] .table td,
        [data-theme="dark"] .table th {
            border-color: var(--border-color) !important;
            color: var(--text-color) !important;
            background-color: var(--card-bg) !important;
        }
        [data-theme="dark"] .table thead th {
            background-color: var(--sidebar-bg) !important;
            border-bottom: 2px solid var(--border-color) !important;
        }
        [data-theme="dark"] .table tbody tr {
            background-color: var(--card-bg) !important;
        }
        [data-theme="dark"] .table tbody tr:hover {
            background-color: var(--hover-bg) !important;
        }
        [data-theme="dark"] .table-responsive {
            background-color: var(--card-bg) !important;
        }
        /* Стили для кнопок в таблице */
        [data-theme="dark"] .table .btn-primary {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            color: #ffffff !important;
        }
        [data-theme="dark"] .table .btn-danger {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: #ffffff !important;
        }
        [data-theme="dark"] .table .btn-primary:hover {
            background-color: #0b5ed7 !important;
            border-color: #0a58ca !important;
        }
        [data-theme="dark"] .table .btn-danger:hover {
            background-color: #bb2d3b !important;
            border-color: #b02a37 !important;
        }
        /* Улучшенные стили для модальных окон в темной теме */
        [data-theme="dark"] .modal-content {
            background-color: var(--card-bg) !important;
            border: 1px solid var(--border-color) !important;
        }
        [data-theme="dark"] .modal-header,
        [data-theme="dark"] .modal-footer {
            border-color: var(--border-color) !important;
            background-color: var(--sidebar-bg) !important;
        }
        [data-theme="dark"] .modal-body {
            background-color: var(--card-bg) !important;
            color: var(--text-color) !important;
        }
        [data-theme="dark"] .modal-title {
            color: var(--text-color) !important;
        }
        /* Улучшенные стили для форм в темной теме */
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
            color: var(--text-color) !important;
        }
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: var(--sidebar-bg) !important;
            border-color: #0d6efd !important;
            color: var(--text-color) !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
        [data-theme="dark"] .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        }
        [data-theme="dark"] .form-select option {
            background-color: var(--sidebar-bg) !important;
            color: var(--text-color) !important;
        }
        [data-theme="dark"] .form-label {
            color: var(--text-color) !important;
        }
        /* Стили для кнопок в темной теме */
        [data-theme="dark"] .btn-secondary {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
            color: var(--text-color) !important;
        }
        [data-theme="dark"] .btn-secondary:hover {
            background-color: var(--hover-bg) !important;
            border-color: var(--border-color) !important;
        }
        [data-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%) !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Боковое меню -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h4 class="mb-4">Админ-панель</h4>
                <!-- Кнопка возврата на главную -->
                <a href="/" class="home-button">
                    <i class="bi bi-house-door"></i>
                    На главную
                </a>
                <!-- Переключатель темы -->
                <form method="POST" class="mb-4">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="theme" value="light" id="lightTheme" <?php echo $theme === 'light' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="lightTheme">Светлая тема</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="theme" value="dark" id="darkTheme" <?php echo $theme === 'dark' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="darkTheme">Темная тема</label>
                    </div>
                </form>
                <!-- Меню навигации -->
                <div class="nav flex-column nav-pills">
                    <a class="nav-link <?php echo $section === 'news' ? 'active' : ''; ?>" href="?section=news">Новости</a>
                    <a class="nav-link <?php echo $section === 'info' ? 'active' : ''; ?>" href="?section=info">О ферме</a>
                    <a class="nav-link <?php echo $section === 'gallery' ? 'active' : ''; ?>" href="?section=gallery">Галерея</a>
                    <a class="nav-link <?php echo $section === 'code' ? 'active' : ''; ?>" href="?section=code">Код</a>
                    <a class="nav-link <?php echo $section === 'components' ? 'active' : ''; ?>" href="?section=components">Компоненты</a>
                    <a class="nav-link <?php echo $section === 'feedback' ? 'active' : ''; ?>" href="?section=feedback">Обратная связь</a>
                    <a class="nav-link <?php echo $section === 'users' ? 'active' : ''; ?>" href="?section=users">Пользователи</a>
                </div>
            </div>
            <!-- Основной контент -->
            <div class="col-md-9 col-lg-10 p-4">
                <?php
                switch ($section) {
                    case 'news':
                        include 'sections/news.php';
                        break;
                    case 'info':
                        include 'sections/info.php';
                        break;
                    case 'gallery':
                        include 'sections/gallery.php';
                        break;
                    case 'code':
                        include 'sections/code.php';
                        break;
                    case 'components':
                        include 'sections/components.php';
                        break;
                    case 'feedback':
                        include 'sections/feedback.php';
                        break;
                    case 'users':
                        include 'sections/users.php';
                        break;
                    default:
                        include 'sections/dashboard.php';
                        break;
                }
                ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('input[name="theme"]').forEach(input => {
            input.addEventListener('change', () => input.form.submit());
        });
    </script>
</body>
</html> 