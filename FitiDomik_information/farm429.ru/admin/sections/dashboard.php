<?php
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM news");
    $news_count = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM feedback");
    $feedback_count = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $users_count = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM components");
    $components_count = $stmt->fetchColumn();
} catch(PDOException $e) {
    $error = "Ошибка при получении статистики";
}
?>
<div class="container">
    <h1 class="mb-4">Добро пожаловать в админ-панель</h1>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Новости</h5>
                    <p class="card-text">Всего новостей: <?php echo $news_count; ?></p>
                    <a href="?section=news" class="btn btn-primary">Управление</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Обратная связь</h5>
                    <p class="card-text">Сообщений: <?php echo $feedback_count; ?></p>
                    <a href="?section=feedback" class="btn btn-primary">Управление</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Пользователи</h5>
                    <p class="card-text">Всего пользователей: <?php echo $users_count; ?></p>
                    <a href="?section=users" class="btn btn-primary">Управление</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Компоненты</h5>
                    <p class="card-text">Всего компонентов: <?php echo $components_count; ?></p>
                    <a href="?section=components" class="btn btn-primary">Управление</a>
                </div>
            </div>
        </div>
    </div>
</div> 