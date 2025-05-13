<?php
require_once 'config.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $pdo->prepare("SELECT last_message_time FROM feedback_cooldown WHERE ip_address = ?");
$stmt->execute([$ip]);
$cooldown = $stmt->fetch();
$stmt = $pdo->query("SELECT cooldown_time FROM settings WHERE id = 1");
$settings = $stmt->fetch();
$cooldown_time = $settings['cooldown_time'] ?? 600; 
if ($cooldown && time() - strtotime($cooldown['last_message_time']) < $cooldown_time) {
    $wait_time = $cooldown_time - (time() - strtotime($cooldown['last_message_time']));
    $error = "Пожалуйста, подождите " . $wait_time . " секунд перед отправкой нового сообщения";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($error)) {
    try {
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff', 'image/svg+xml'];
            $max_size = 10 * 1024 * 1024; 
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                throw new Exception("Неподдерживаемый формат файла. Разрешены: JPG, PNG, GIF, WEBP, BMP, TIFF, SVG");
            }
            if ($_FILES['image']['size'] > $max_size) {
                throw new Exception("Размер файла превышает 10MB");
            }
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            } else {
                throw new Exception("Ошибка при загрузке файла");
            }
        }
        $stmt = $pdo->prepare("INSERT INTO feedback_messages (name, message, type, image_path, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['message'], $_POST['type'], $image_path, $ip]);
        $stmt = $pdo->prepare("REPLACE INTO feedback_cooldown (ip_address, last_message_time) VALUES (?, NOW())");
        $stmt->execute([$ip]);
        $success = "Сообщение успешно отправлено";
        if (defined('TELEGRAM_BOT_TOKEN') && defined('TELEGRAM_CHAT_ID')) {
            $telegram_message = "📷 Фото: " . ($image_path ? basename($image_path) : "нет фото") . "\n";
            $telegram_message .= "👤 Имя: " . htmlspecialchars($_POST['name']) . "\n";
            $telegram_message .= "📝 Тип: " . htmlspecialchars($_POST['type']) . "\n";
            $telegram_message .= "💬 Сообщение: " . htmlspecialchars($_POST['message']) . "\n";
            $telegram_message .= "🌐 IP: " . $ip;
            $telegram_data = [
                'chat_id' => TELEGRAM_CHAT_ID,
                'text' => $telegram_message,
                'parse_mode' => 'HTML'
            ];
            $ch = curl_init("https:
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $telegram_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
            if ($image_path) {
                $telegram_photo = new CURLFile($image_path);
                $telegram_photo_data = [
                    'chat_id' => TELEGRAM_CHAT_ID,
                    'photo' => $telegram_photo,
                    'caption' => $telegram_message,
                    'parse_mode' => 'HTML'
                ];
                $ch = curl_init("https:
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $telegram_photo_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
try {
    $stmt = $pdo->query("
        SELECT m.*, r.reply_text 
        FROM feedback_messages m 
        LEFT JOIN feedback_replies r ON m.id = r.message_id 
        WHERE m.is_faq = 1 
        ORDER BY m.created_at DESC
    ");
    $faq = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Ошибка при получении FAQ";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обратная связь</title>
    <link href="https:
    <style>
        .faq-item:not(:last-child) {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4">Обратная связь</h1>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-6">
                <!-- Форма обратной связи -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Отправить сообщение</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Ваше имя</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="type" class="form-label">Тип сообщения</label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="question">Вопрос</option>
                                    <option value="suggestion">Предложение</option>
                                    <option value="error">Сообщить об ошибке</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Сообщение</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Изображение (необязательно)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">Поддерживаемые форматы: JPG, PNG, GIF, WEBP, BMP, TIFF, SVG. Максимальный размер: 10MB</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Отправить</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <!-- FAQ -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Часто задаваемые вопросы</h5>
                        <?php if (empty($faq)): ?>
                            <p class="text-muted">FAQ пока пуст</p>
                        <?php else: ?>
                            <?php foreach ($faq as $item): ?>
                                <div class="faq-item">
                                    <h6><?php echo htmlspecialchars($item['message']); ?></h6>
                                    <?php if ($item['reply_text']): ?>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($item['reply_text'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https:
</body>
</html> 