<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/qr_code.php';
$pageTitle = "ФитоДомик - Умная мини-ферма для выращивания растений";
$pageDescription = "ФитоДомик - умная мини-ферма для выращивания растений в домашних условиях. Автоматизированное выращивание растений с системой климат-контроля, автополивом и LED освещением.";
$pageKeywords = "ФитоДомик, умная мини-ферма, автоматизированное выращивание растений, система климат-контроля, датчики температуры, датчики влажности, датчики CO₂";
$ymGoals = [
    'view_main_page' => true,
    'view_features' => true,
    'click_telegram' => true
];
ob_start();
?>
<!-- Добавляем цели для Яндекс.Метрики -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    ym(100844979, 'reachGoal', 'view_main_page');
    const features = document.querySelectorAll('.card-title');
    features.forEach(feature => {
        feature.addEventListener('mouseenter', function() {
            ym(100844979, 'reachGoal', 'view_features');
        });
    });
    const telegramLink = document.querySelector('a[href="https:
    if (telegramLink) {
        telegramLink.addEventListener('click', function() {
            ym(100844979, 'reachGoal', 'click_telegram');
        });
    }
});
</script>
<div class="container py-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="text-center mb-4">🌿 Добро пожаловать в ФитоДомик – ваш персональный уголок природы прямо у вас дома! 🏡</h1>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <p class="lead text-center">Представляем умную мини-ферму, которая автоматизирует уход за растениями и минимизирует ваше участие. Благодаря передовым технологиям, ФитоДомик обеспечивает идеальные условия для выращивания свежих овощей и зелени без лишних хлопот.</p>
                </div>
            </div>
            <h2 class="text-center mb-4">✨ Ключевые особенности ФитоДомика:</h2>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title">🌡️ Контроль климата</h3>
                            <p class="card-text">Датчики температуры, влажности и CO₂ поддерживают оптимальные условия для роста растений.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title">💧 Автополив</h3>
                            <p class="card-text">Погружной насос и капельная система равномерно снабжают растения водой, предотвращая пересыхание и переувлажнение.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title">💡 Умное освещение</h3>
                            <p class="card-text">Светодиодная лампа регулирует световой режим, обеспечивая растениям необходимый спектр для фотосинтеза.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title">👁️ Машинное зрение</h3>
                            <p class="card-text">Встроенная камера анализирует рост и состояние растений, позволяя своевременно реагировать на их потребности.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title">🔋 Резервное питание</h3>
                            <p class="card-text">Аккумулятор поддерживает работу системы при отключении электроэнергии, гарантируя бесперебойный уход за вашими растениями.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title">📱 Удаленный доступ</h3>
                            <p class="card-text">Управляйте всеми параметрами через удобный веб-интерфейс с любого устройства, где бы вы ни находились.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <p class="lead">ФитоДомик – это шаг в будущее, где технологии заботятся о вашем комфорте и здоровье. Создайте свой собственный оазис свежести и зелени прямо у себя дома!</p>
                    <h2 class="mt-4">🌱 Начните выращивать с удовольствием уже сегодня! 🌱</h2>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h3 class="mb-3">Сканируйте QR-код для связи с нами в Telegram</h3>
                    <div class="d-flex justify-content-center">
                        <?php echo generate_qr_code('https:
                    </div>
                    <p class="mt-3">Или перейдите по ссылке: <a href="https:
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?> 