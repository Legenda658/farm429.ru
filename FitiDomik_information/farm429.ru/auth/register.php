<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/debug.php';
if (isLoggedIn()) {
    header('Location: /');
    exit;
}
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Пожалуйста, заполните все поля';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать не менее 6 символов';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Неверный формат email';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Пользователь с таким именем или email уже существует';
                logError("Попытка регистрации существующего пользователя: $username ($email)");
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, username, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$first_name, $last_name, $email, $username, $hashed_password]);
                logUserAction("Зарегистрирован новый пользователь: $username ($email)");
                $success = 'Регистрация успешно завершена. Теперь вы можете войти.';
            }
        } catch(PDOException $e) {
            logError("Ошибка при регистрации: " . $e->getMessage());
            $error = 'Произошла ошибка при регистрации';
        }
    }
}
$pageTitle = "Регистрация";
ob_start();
?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Регистрация</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <form method="post" action="/auth/register.php">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Имя</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Фамилия</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Имя пользователя</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Подтверждение пароля</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Уже есть аккаунт? <a href="/auth/login.php">Войти</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
?> 