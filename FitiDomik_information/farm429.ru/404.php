<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config.php';
http_response_code(404);
$pageTitle = "Страница не найдена";
ob_start();
?>
<div class="error-container">
    <div class="error-content">
        <div class="error-code">
            <div class="number">4</div>
            <div class="zero-container">
                <div class="zero"></div>
            </div>
            <div class="number">4</div>
        </div>
        <h1 class="error-title">Страница не найдена</h1>
        <p class="error-message">Извините, запрашиваемая страница не существует или была перемещена.</p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary btn-lg">
                <i class="bi bi-house-door"></i> На главную
            </a>
            <button onclick="history.back()" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-arrow-left"></i> Назад
            </button>
        </div>
        <div class="error-sections">
            <div class="section">
                <h3>Возможно, вы искали:</h3>
                <ul class="section-list">
                    <li><a href="/pages/news/"><i class="bi bi-newspaper"></i> Новости</a></li>
                    <li><a href="/pages/info/"><i class="bi bi-info-circle"></i> О ферме</a></li>
                    <li><a href="/pages/galery/"><i class="bi bi-images"></i> Галерея</a></li>
                    <li><a href="/pages/components/"><i class="bi bi-gear"></i> Компоненты</a></li>
                    <li><a href="/pages/code/"><i class="bi bi-code-square"></i> Код</a></li>
                    <li><a href="/pages/feedback/"><i class="bi bi-chat-dots"></i> Обратная связь</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<style>
.error-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background-color: transparent;
}
.error-content {
    text-align: center;
    max-width: 800px;
    width: 100%;
    background-color: transparent;
}
.error-code {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2rem;
    position: relative;
    gap: 2rem;
}
.error-code .number {
    font-size: 12rem;
    font-weight: bold;
    color: #2ecc71;
    line-height: 1;
}
.zero-container {
    position: relative;
    width: 12rem;
    height: 12rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
.zero {
    position: relative;
    width: 100%;
    height: 100%;
    border: 1.5rem solid #2ecc71;
    border-radius: 50%;
    background-color: transparent;
}
.error-title {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--bs-body-color);
}
.error-message {
    font-size: 1.25rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2rem;
}
.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 3rem;
}
.error-actions .btn {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
}
.error-sections {
    background-color: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 1rem;
    padding: 2rem;
    margin-top: 2rem;
}
.section h3 {
    color: var(--bs-body-color);
    margin-bottom: 1.5rem;
}
.section-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}
.section-list li a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    color: var(--bs-primary);
    text-decoration: none;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    transition: all 0.2s ease-in-out;
}
.section-list li a:hover {
    background-color: var(--bs-primary);
    color: white;
    transform: translateY(-2px);
}
.section-list li a i {
    font-size: 1.2rem;
}
/* Стили для тёмной темы */
[data-theme="dark"] {
    background-color: #121212;
}
[data-theme="dark"] .error-container,
[data-theme="dark"] .error-content,
[data-theme="dark"] .error-sections {
    background-color: transparent;
}
[data-theme="dark"] .error-title,
[data-theme="dark"] .error-message,
[data-theme="dark"] .section h3 {
    color: #ffffff;
}
[data-theme="dark"] .error-sections {
    border-color: #333333;
}
[data-theme="dark"] .section-list li a {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: #333333;
    color: #ffffff;
}
[data-theme="dark"] .section-list li a:hover {
    background-color: #2ecc71;
    border-color: #2ecc71;
    color: #ffffff;
}
[data-theme="dark"] .btn-outline-primary {
    color: #ffffff;
    border-color: #ffffff;
    background-color: transparent;
}
[data-theme="dark"] .btn-outline-primary:hover {
    background-color: #ffffff;
    color: #121212;
}
@media (max-width: 768px) {
    .error-code .number {
        font-size: 8rem;
    }
    .zero-container {
        width: 8rem;
        height: 8rem;
    }
    .error-title {
        font-size: 2rem;
    }
    .error-actions {
        flex-direction: column;
    }
    .section-list {
        grid-template-columns: 1fr;
    }
}
</style>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?> 