<?php
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /admin/index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create':
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$_POST['email'], $_POST['username']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Email или имя пользователя уже используются");
                }
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['email'],
                    $_POST['username'],
                    $password_hash,
                    isset($_POST['is_admin']) ? 1 : 0
                ]);
                $success = "Пользователь успешно создан";
                break;
            case 'update':
                $updates = [];
                $params = [];
                if ($_POST['email'] !== $_POST['old_email'] || $_POST['username'] !== $_POST['old_username']) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (email = ? OR username = ?) AND id != ?");
                    $stmt->execute([$_POST['email'], $_POST['username'], $_POST['id']]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Email или имя пользователя уже используются");
                    }
                }
                $updates[] = "first_name = ?";
                $updates[] = "last_name = ?";
                $updates[] = "email = ?";
                $updates[] = "username = ?";
                $updates[] = "is_admin = ?";
                $params = [
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['email'],
                    $_POST['username'],
                    isset($_POST['is_admin']) ? 1 : 0
                ];
                if (!empty($_POST['password'])) {
                    $updates[] = "password = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                $params[] = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?");
                $stmt->execute($params);
                $success = "Данные пользователя обновлены";
                break;
            case 'delete':
                if ($_POST['is_admin']) {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
                    if ($stmt->fetchColumn() <= 1) {
                        throw new Exception("Нельзя удалить последнего администратора");
                    }
                }
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = "Пользователь успешно удален";
                break;
            case 'toggle_lock':
                $stmt = $pdo->prepare("UPDATE users SET is_locked = ?, login_attempts = 0 WHERE id = ?");
                $stmt->execute([$_POST['is_locked'], $_POST['id']]);
                $success = "Статус блокировки пользователя обновлен";
                break;
            case 'toggle_admin':
                $user_id = (int)$_POST['user_id'];
                $is_admin = (int)$_POST['is_admin'];
                if ($user_id !== $_SESSION['user_id']) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
                        $stmt->execute([$is_admin, $user_id]);
                        $success = $is_admin ? "Права администратора успешно предоставлены" : "Права администратора успешно сняты";
                    } catch(PDOException $e) {
                        $error = "Ошибка при изменении прав администратора";
                    }
                } else {
                    $error = "Вы не можете изменить свои собственные права администратора";
                }
                break;
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
try {
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'admins' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn(),
        'locked' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_locked = 1")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn()
    ];
} catch(PDOException $e) {
    $error = "Ошибка при получении статистики";
}
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Ошибка при получении списка пользователей";
}
?>
<div class="container">
    <h1 class="mb-4">Управление пользователями</h1>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Всего пользователей</h5>
                    <h3><?php echo $stats['total']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Администраторы</h5>
                    <h3><?php echo $stats['admins']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Заблокированные</h5>
                    <h3><?php echo $stats['locked']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Активные (30 дней)</h5>
                    <h3><?php echo $stats['active']; ?></h3>
                </div>
            </div>
        </div>
    </div>
    <!-- Форма создания пользователя -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Создать пользователя</h5>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Имя</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Фамилия</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">Имя пользователя</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin">
                                <label class="form-check-label" for="is_admin">Администратор</label>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Создать пользователя</button>
            </form>
        </div>
    </div>
    <!-- Список пользователей -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Список пользователей</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Логин</th>
                            <th>Статус</th>
                            <th>Последний вход</th>
                            <th>Попытки входа</th>
                            <th>Создан</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="badge bg-success">Админ</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <?php if ($user['is_locked']): ?>
                                        <span class="badge bg-danger">Заблокирован</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Активен</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Никогда'; ?></td>
                                <td><?php echo $user['login_attempts']; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $user['id']; ?>">
                                            Редактировать
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_lock">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="is_locked" value="<?php echo $user['is_locked'] ? '0' : '1'; ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <?php echo $user['is_locked'] ? 'Разблокировать' : 'Заблокировать'; ?>
                                            </button>
                                        </form>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_admin">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="is_admin" value="<?php echo $user['is_admin'] ? '0' : '1'; ?>">
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" 
                                                           class="form-check-input" 
                                                           name="is_admin" 
                                                           value="1" 
                                                           <?php echo $user['is_admin'] ? 'checked' : ''; ?>
                                                           onchange="this.form.submit()">
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="is_admin" value="<?php echo $user['is_admin']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены?')">Удалить</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <!-- Модальное окно редактирования -->
                            <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Редактирование пользователя</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="old_email" value="<?php echo $user['email']; ?>">
                                                <input type="hidden" name="old_username" value="<?php echo $user['username']; ?>">
                                                <div class="mb-3">
                                                    <label for="edit_first_name<?php echo $user['id']; ?>" class="form-label">Имя</label>
                                                    <input type="text" class="form-control" id="edit_first_name<?php echo $user['id']; ?>" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_last_name<?php echo $user['id']; ?>" class="form-label">Фамилия</label>
                                                    <input type="text" class="form-control" id="edit_last_name<?php echo $user['id']; ?>" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_email<?php echo $user['id']; ?>" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="edit_email<?php echo $user['id']; ?>" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_username<?php echo $user['id']; ?>" class="form-label">Имя пользователя</label>
                                                    <input type="text" class="form-control" id="edit_username<?php echo $user['id']; ?>" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_password<?php echo $user['id']; ?>" class="form-label">Новый пароль (оставьте пустым, чтобы не менять)</label>
                                                    <input type="password" class="form-control" id="edit_password<?php echo $user['id']; ?>" name="password">
                                                </div>
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" id="edit_is_admin<?php echo $user['id']; ?>" name="is_admin" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="edit_is_admin<?php echo $user['id']; ?>">Администратор</label>
                                                    </div>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<style>
.admin-toggle-form {
    margin: 0;
    padding: 0;
}
.form-switch {
    padding-left: 2.5em;
}
.form-check-input {
    cursor: pointer;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.form-check-input').forEach(function(checkbox) {
        checkbox.addEventListener('change', function(e) {
            if (!confirm('Вы уверены, что хотите ' + (this.checked ? 'предоставить' : 'снять') + ' права администратора?')) {
                e.preventDefault();
                this.checked = !this.checked;
                return false;
            }
        });
    });
});
</script> 