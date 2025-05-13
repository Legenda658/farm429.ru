<?php
session_start();
require_once 'config.php';
function canSendMessage($ip_address, &$wait_time = 0) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT last_message_time FROM feedback_cooldown WHERE ip_address = ?");
    $stmt->execute([$ip_address]);
    $result = $stmt->fetch();
    if ($result) {
        $last_time = strtotime($result['last_message_time']);
        $current_time = time();
        $diff = $current_time - $last_time;
        $cooldown = getCooldownTime(); 
        if ($diff < $cooldown) {
            $wait_time = $cooldown - $diff;
            return false;
        }
    }
    return true;
}
$upload_dir = __DIR__ . '/uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $wait_time = 0;
    if (!canSendMessage($ip_address, $wait_time)) {
        $minutes = floor($wait_time / 60);
        $seconds = $wait_time % 60;
        $_SESSION['error'] = "Следующее сообщение можно отправить через {$minutes} мин. {$seconds} сек.";
    } else {
        $name = trim($_POST['name']);
        $message = trim($_POST['message']);
        $type = $_POST['type'];
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'];
            $file_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (in_array($file_ext, $allowed)) {
                $new_filename = uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . '/' . $new_filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_path = 'uploads/' . $new_filename;
                }
            }
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO feedback_messages (name, message, type, image_path, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $message, $type, $image_path, $ip_address]);
            $message_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("REPLACE INTO feedback_cooldown (ip_address, last_message_time) VALUES (?, NOW())");
            $stmt->execute([$ip_address]);
            $telegram_text = "Новое сообщение!\n\nТип: " . ucfirst($type) . "\nОт: " . $name . "\nIP: " . $ip_address . "\n\nСообщение:\n" . $message;
            if ($image_path) {
                sendTelegramPhoto($image_path, $telegram_text);
            } else {
                sendTelegramMessage($telegram_text);
            }
            $_SESSION['success'] = "Спасибо за ваше сообщение! Администратор ответит вам в ближайшее время.";
            header('Location: feedback.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Произошла ошибка при отправке сообщения";
            header('Location: feedback.php');
            exit;
        }
    }
    header('Location: feedback.php');
    exit;
}
$wait_time = 0;
$can_send = canSendMessage($_SERVER['REMOTE_ADDR'], $wait_time);
$stmt = $pdo->prepare("
    SELECT m.*, r.reply_text, r.created_at as reply_date
    FROM feedback_messages m 
    LEFT JOIN feedback_replies r ON m.id = r.message_id 
    WHERE m.ip_address = ? OR m.is_faq = 1
    ORDER BY m.created_at DESC
");
$stmt->execute([$_SERVER['REMOTE_ADDR']]);
$messages = $stmt->fetchAll();
$stmt = $pdo->query("SELECT m.*, r.reply_text FROM feedback_messages m LEFT JOIN feedback_replies r ON m.id = r.message_id WHERE m.is_faq = 1 ORDER BY m.created_at DESC");
$faqs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обратная связь</title>
    <link href="https:
    <style>
        .message-question { background-color: #e3f2fd; }
        .message-error { background-color: #ffebee; }
        .message-suggestion { background-color: #f1f8e9; }
        .feedback-form { max-width: 600px; margin: 0 auto; }
        .faq-section { margin-top: 50px; }
        .user-messages { margin-top: 30px; }
        .message-card { margin-bottom: 20px; }
        .reply-text { background-color: #f8f9fa; padding: 10px; margin-top: 10px; border-left: 3px solid #0d6efd; }
        .cooldown-timer { color: #dc3545; font-size: 0.9em; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="feedback-form">
            <h2 class="mb-4">Обратная связь</h2>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Ваше имя</label>
                    <input type="text" class="form-control" id="name" name="name" required <?= !$can_send ? 'disabled' : '' ?>>
                </div>
                <div class="mb-3">
                    <label for="type" class="form-label">Тип сообщения</label>
                    <select class="form-select" id="type" name="type" required <?= !$can_send ? 'disabled' : '' ?>>
                        <option value="question">Вопрос</option>
                        <option value="error">Ошибка</option>
                        <option value="suggestion">Предложение</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Сообщение</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required <?= !$can_send ? 'disabled' : '' ?>></textarea>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Прикрепить изображение (опционально)</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*" <?= !$can_send ? 'disabled' : '' ?>>
                    <small class="text-muted">Поддерживаемые форматы: JPG, PNG, GIF, WEBP, BMP, TIFF, SVG. Максимальный размер: 10MB</small>
                </div>
                <?php if (!$can_send && $wait_time > 0): ?>
                    <div class="cooldown-timer" id="cooldownTimer" data-wait-time="<?= $wait_time ?>">
                        До следующего сообщения осталось: <span id="timer"></span>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" <?= !$can_send ? 'disabled' : '' ?>>Отправить</button>
            </form>
        </div>
        <!-- Сообщения пользователя -->
        <?php if (!empty($messages)): ?>
            <div class="user-messages" id="userMessages">
                <h3 class="mb-4">Ваши сообщения</h3>
                <?php foreach ($messages as $message): ?>
                    <?php if (!$message['is_faq'] || ($message['is_faq'] && $message['ip_address'] === $_SERVER['REMOTE_ADDR'])): ?>
                        <div class="card message-card message-<?= htmlspecialchars($message['type']) ?>" data-message-id="<?= $message['id'] ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($message['name']) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?= ucfirst(htmlspecialchars($message['type'])) ?> - 
                                    <?= date('d.m.Y H:i', strtotime($message['created_at'])) ?>
                                </h6>
                                <p class="card-text"><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                <?php if ($message['image_path']): ?>
                                    <div class="mb-3">
                                        <a href="<?= htmlspecialchars($message['image_path']) ?>" target="_blank">
                                            <img src="<?= htmlspecialchars($message['image_path']) ?>" alt="Изображение" class="img-fluid" style="max-height: 200px;">
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php if ($message['reply_text']): ?>
                                    <div class="reply-text">
                                        <strong>Ответ администратора (<?= date('d.m.Y H:i', strtotime($message['reply_date'])) ?>):</strong><br>
                                        <?= nl2br(htmlspecialchars($message['reply_text'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($faqs)): ?>
            <div class="faq-section">
                <h3 class="mb-4">Часто задаваемые вопросы</h3>
                <div class="accordion" id="faqAccordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="accordion-item message-<?= htmlspecialchars($faq['type']) ?>">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $index ?>">
                                    <?= htmlspecialchars($faq['message']) ?>
                                </button>
                            </h2>
                            <div id="faq<?= $index ?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <?= nl2br(htmlspecialchars($faq['reply_text'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https:
    <script>
        function checkNewReplies() {
            const lastCheck = localStorage.getItem('lastReplyCheck') || 0;
            fetch('check_replies.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'last_check=' + lastCheck
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.replies.length > 0) {
                    location.reload();
                }
                localStorage.setItem('lastReplyCheck', data.current_time);
            })
            .catch(error => console.error('Ошибка при проверке ответов:', error));
        }
        const cooldownTimer = document.getElementById('cooldownTimer');
        if (cooldownTimer) {
            const waitTime = parseInt(cooldownTimer.dataset.waitTime);
            const timerElement = document.getElementById('timer');
            function updateTimer() {
                let timeLeft = waitTime;
                const timer = setInterval(() => {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timerElement.textContent = `${minutes} мин. ${seconds} сек.`;
                    if (--timeLeft < 0) {
                        clearInterval(timer);
                        location.reload();
                    }
                }, 1000);
            }
            updateTimer();
        }
        setInterval(checkNewReplies, 30000);
        checkNewReplies();
    </script>
</body>
</html> 