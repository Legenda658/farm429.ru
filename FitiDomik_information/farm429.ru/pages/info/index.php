<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
$pageTitle = "О ферме";

try {
    $stmt = $pdo->query("SELECT * FROM timeline ORDER BY created_at DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
?>

<div class="container mt-4 project-info">
    <h1 class="text-center mb-4">О проекте</h1>
    
    <div class="text-end mb-3">
        <button id="sortToggle" class="btn btn-outline-primary">
            <i class="bi bi-sort-down"></i> Сортировка: от нового к старому
        </button>
    </div>
    
    <div class="timeline">
        <div class="timeline-container">
            <?php foreach($events as $event): ?>
                <div class="card event">
                    <div class="event-header" onclick="toggleEvent(<?= $event['id'] ?>)">
                        <div class="header-content">
                            <h2><?= htmlspecialchars($event['title']) ?></h2>
                            <div class="header-date">
                                <?= date('d.m.Y H:i', strtotime($event['created_at'])) ?>
                            </div>
                        </div>
                        <button class="toggle-btn">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                    <div class="event-body" id="event-<?= $event['id'] ?>">
                        <div class="event-content">
                            <?= nl2br(htmlspecialchars($event['content'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
}

.timeline-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.event {
    position: relative;
    width: 100%;
    padding: 20px;
    border-radius: 12px;
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.event:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.event-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
}

.header-content {
    flex-grow: 1;
    margin-right: 15px;
}

.header-date {
    font-size: 0.9em;
    color: var(--muted-text);
    margin-top: 5px;
}

.event-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-color);
}

.toggle-btn {
    background: none;
    border: none;
    color: var(--text-color);
    cursor: pointer;
    padding: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    width: 32px;
    height: 32px;
}

.toggle-btn:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.toggle-btn i {
    transition: transform 0.3s ease;
    font-size: 1.2rem;
}

.event-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.5s ease-out;
    padding: 0;
}

.event-body.expanded {
    max-height: 2000px;
    padding: 20px 0 0 0;
}

.event-content {
    white-space: normal;
    line-height: 1.7;
    color: var(--text-color);
    background-color: var(--content-bg);
    padding: 20px;
    border-radius: 8px;
    margin: 0;
}

.event-date {
    font-size: 0.9em;
    margin-top: 15px;
    color: var(--muted-text);
    text-align: right;
    padding-right: 10px;
}

/* Light theme styles */
[data-theme="light"] .event {
    background-color: #ffffff;
    border-color: #e0e4e8;
}

[data-theme="light"] .event-content {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
}

[data-theme="light"] .event-header h2 {
    color: #1a1a1a;
}

[data-theme="light"] .toggle-btn {
    color: #1a1a1a;
}

[data-theme="light"] .toggle-btn:hover {
    background-color: #f0f0f0;
}

[data-theme="light"] .event-date {
    color: #666666;
}

/* Dark theme styles */
[data-theme="dark"] .event {
    background-color: #2b2b2b;
    border-color: #404040;
}

[data-theme="dark"] .event-content {
    background-color: #1e1e1e;
    border: 1px solid #333333;
}

[data-theme="dark"] .event-header h2 {
    color: #e0e0e0;
}

[data-theme="dark"] .toggle-btn {
    color: #e0e0e0;
}

[data-theme="dark"] .toggle-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .event-date {
    color: #888888;
}

@media (max-width: 768px) {
    .timeline {
        padding: 20px;
    }
    
    .event {
        width: 100%;
        margin: 0;
    }
    
    .event-header h2 {
        font-size: 1.1rem;
    }
}

#sortToggle {
    transition: all 0.3s ease;
}

#sortToggle i {
    transition: transform 0.3s ease;
}

#sortToggle.asc i {
    transform: rotate(180deg);
}
</style>

<script>
function toggleEvent(eventId) {
    const eventBody = document.getElementById(`event-${eventId}`);
    const toggleBtn = eventBody.previousElementSibling.querySelector('.toggle-btn i');
    
    eventBody.classList.toggle('expanded');
    toggleBtn.style.transform = eventBody.classList.contains('expanded') 
        ? 'rotate(180deg)' 
        : 'rotate(0deg)';
}

document.addEventListener('DOMContentLoaded', function() {
    const sortToggle = document.getElementById('sortToggle');
    const timelineContainer = document.querySelector('.timeline-container');
    let isNewestFirst = true;

    sortToggle.addEventListener('click', function() {
        const events = Array.from(timelineContainer.children);
        events.reverse();
        events.forEach(event => timelineContainer.appendChild(event));
        
        isNewestFirst = !isNewestFirst;
        sortToggle.innerHTML = `<i class="bi bi-sort-${isNewestFirst ? 'down' : 'up'}"></i> Сортировка: ${isNewestFirst ? 'от нового к старому' : 'от старого к новому'}`;
    });
});
</script>

<?php
    $content = ob_get_clean();
    require_once __DIR__ . '/../../layout.php';
} catch(PDOException $e) {
    error_log("Ошибка: " . $e->getMessage());
    die("Ошибка при получении данных");
}
?> 