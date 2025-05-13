<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
$pageTitle = "Обратная связь";
try {
    ob_start();
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("
        SELECT m.*, r.reply_text 
        FROM feedback_messages m 
        LEFT JOIN feedback_replies r ON m.id = r.message_id 
        WHERE m.is_faq = 1 
        ORDER BY m.created_at DESC
    ");
    $faq = $stmt->fetchAll();
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
                $upload_dir = __DIR__ . '/../../uploads/feedback/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $filename = uniqid() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path = 'uploads/feedback/' . $filename;
                } else {
                    throw new Exception("Ошибка при загрузке файла");
                }
            }
            $stmt = $pdo->prepare("INSERT INTO feedback_messages (name, message, type, image_path, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['message'], $_POST['type'], $image_path, $ip]);
            $stmt = $pdo->prepare("REPLACE INTO feedback_cooldown (ip_address, last_message_time) VALUES (?, NOW())");
            $stmt->execute([$ip]);
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
                    $telegram_photo = new CURLFile(__DIR__ . '/../../' . $image_path);
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
            $success = "Сообщение успешно отправлено";
            $_POST = array();
        } catch(Exception $e) {
            $error = $e->getMessage();
        }
    }
?>
<div class="container mt-4">
    <h1 class="text-center mb-4">Обратная связь</h1>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Ваше имя</label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Тип сообщения</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="question" <?php echo (isset($_POST['type']) && $_POST['type'] === 'question') ? 'selected' : ''; ?>>Вопрос</option>
                                <option value="suggestion" <?php echo (isset($_POST['type']) && $_POST['type'] === 'suggestion') ? 'selected' : ''; ?>>Предложение</option>
                                <option value="error" <?php echo (isset($_POST['type']) && $_POST['type'] === 'error') ? 'selected' : ''; ?>>Сообщить об ошибке</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Сообщение</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
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
    </div>
</div>
<!-- FAQ секция -->
<?php if (!empty($faq)): ?>
    <div class="faq-section mt-5">
        <div class="container">
            <h2 class="text-center mb-4">Часто задаваемые вопросы</h2>
            <div class="row">
                <?php foreach ($faq as $item): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title text-primary"><?php echo htmlspecialchars($item['message']); ?></h5>
                                <?php if ($item['reply_text']): ?>
                                    <p class="card-text text-muted"><?php echo nl2br(htmlspecialchars($item['reply_text'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php
    $content = ob_get_clean();
    require_once __DIR__ . '/../../layout.php';
} catch(Exception $e) {
    error_log("Ошибка на странице обратной связи: " . $e->getMessage());
    die("Произошла ошибка при загрузке страницы");
}
?> 