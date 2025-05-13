<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
$pageTitle = "Галерея";
try {
    $stmt = $pdo->query("SELECT * FROM photos ORDER BY created_at DESC");
    $photos = $stmt->fetchAll();
    ob_start();
?>
<div class="container gallery-container">
    <h1 class="gallery-title">Фотогалерея</h1>
    <div class="row">
        <?php if (empty($photos)): ?>
            <div class="col-md-12">
                <div class="alert alert-info">
                    <h4 class="alert-heading">В галерее пока нет фотографий</h4>
                    <p>Загляните позже, чтобы увидеть новые фотографии.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($photos as $photo): ?>
                <div class="col-md-4">
                    <div class="gallery-card">
                        <div class="image-container">
                            <img src="/uploads/gallery/<?php echo htmlspecialchars($photo['filename']); ?>" 
                                 alt="<?php echo htmlspecialchars($photo['title']); ?>"
                                 class="gallery-image"
                                 onclick="openLightbox(this, '<?php echo htmlspecialchars($photo['title']); ?>')">
                        </div>
                        <div class="gallery-card-title">
                            <?php echo htmlspecialchars($photo['title']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <!-- Лайтбокс для просмотра фотографий -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <span class="close-btn">&times;</span>
        <img id="lightbox-img" src="" alt="">
        <div id="lightbox-title" class="lightbox-title"></div>
    </div>
</div>
<style>
/* Общие стили */
.gallery-container {
    padding: 2rem 0;
}
.gallery-title {
    color: var(--text-color);
    margin-bottom: 2rem;
    text-align: center;
}
/* Стили для карточек */
.gallery-card {
    margin-bottom: 2rem;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease;
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    padding-bottom: 10px;
}
.gallery-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}
.gallery-card-title {
    padding: 10px 15px;
    font-size: 1.1rem;
    color: var(--text-color);
    text-align: center;
    font-weight: 500;
}
/* Стили для изображений */
.gallery-image {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 8px 8px 0 0;
    transition: transform 0.3s ease;
}
.gallery-card:hover .gallery-image {
    transform: scale(1.02);
}
.image-container {
    position: relative;
    background-color: transparent;
    border-radius: 8px 8px 0 0;
    overflow: hidden;
}
/* Светлая тема */
[data-theme="light"] .gallery-card {
    background-color: #ffffff;
    border: 1px solid #dee2e6;
}
[data-theme="light"] .gallery-card-title {
    color: #212529;
}
/* Темная тема */
[data-theme="dark"] .gallery-card {
    background-color: #2b2b2b;
    border: 1px solid #404040;
}
[data-theme="dark"] .gallery-card-title {
    color: #e0e0e0;
}
/* Адаптивность */
@media (max-width: 768px) {
    .gallery-image {
        height: 200px;
    }
    .gallery-card-title {
        font-size: 1rem;
    }
}
.lightbox {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.95);
    z-index: 1000;
    text-align: center;
    backdrop-filter: blur(5px);
}
.lightbox img {
    max-width: 90%;
    max-height: 90vh;
    margin-top: 5vh;
    border-radius: 8px;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
}
.lightbox-title {
    color: var(--text-color-light);
    font-size: 1.5em;
    margin-top: 20px;
    padding: 0 20px;
}
.close-btn {
    position: absolute;
    top: 20px;
    right: 30px;
    color: var(--text-color-light);
    font-size: 40px;
    cursor: pointer;
    transition: opacity 0.2s ease;
}
.close-btn:hover {
    opacity: 0.8;
}
</style>
<script>
function openLightbox(img, title) {
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxTitle = document.getElementById('lightbox-title');
    lightboxImg.src = img.src;
    lightboxTitle.textContent = title;
    lightbox.style.display = 'block';
    document.body.style.overflow = 'hidden'; 
    lightboxImg.onclick = function(e) {
        e.stopPropagation();
    };
}
function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = ''; 
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
});
</script>
<?php
    $content = ob_get_clean();
    require_once __DIR__ . '/../../layout.php';
} catch(PDOException $e) {
    error_log("Ошибка БД: " . $e->getMessage());
    die("Ошибка при получении списка фотографий");
}
?> 