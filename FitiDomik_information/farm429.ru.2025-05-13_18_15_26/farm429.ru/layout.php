<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/debug.php';
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['REQUEST_URI'];
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Сайт'; ?></title>
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo $pageDescription ?? 'ФитоДомик - умная мини-ферма для выращивания растений в домашних условиях'; ?>">
    <meta name="keywords" content="<?php echo $pageKeywords ?? 'мини-ферма, умная ферма, автоматизация, растения, гидропоника'; ?>">
    <!-- Google Search Icon -->
    <meta name="google-site-verification" content="your-verification-code">
    <link rel="search" type="application/opensearchdescription+xml" title="ФитоДомик" href="/opensearch.xml">
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo $pageTitle ?? 'ФитоДомик'; ?>">
    <meta property="og:description" content="<?php echo $pageDescription ?? 'Умная мини-ферма для выращивания растений'; ?>">
    <meta property="og:image" content="<?php echo $pageImage ?? '/icon/og-image.jpg'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:site_name" content="ФитоДомик">
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $pageTitle ?? 'ФитоДомик'; ?>">
    <meta name="twitter:description" content="<?php echo $pageDescription ?? 'Умная мини-ферма для выращивания растений'; ?>">
    <meta name="twitter:image" content="<?php echo $pageImage ?? '/icon/og-image.jpg'; ?>">
    <!-- Favicon and App Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/icon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icon/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="/icon/favicon.ico">
    <link rel="manifest" href="/icon/site.webmanifest">
    <link rel="mask-icon" href="/icon/safari-pinned-tab.svg" color="#2ecc71">
    <meta name="msapplication-TileColor" content="#2ecc71">
    <meta name="theme-color" content="#2ecc71">
    <!-- Yandex.Metrika counter -->
    <script>
    (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
    m[i].l=1*new Date();
    for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r)return;}
    k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
    (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
    ym(100844979, "init", {
        clickmap:true,
        trackLinks:true,
        accurateTrackBounce:true
    });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/100844979" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/bootstrap-icons.css">
    <style>
        @font-face {
            font-family: "bootstrap-icons";
            src: url("/assets/fonts/bootstrap-icons/bootstrap-icons.woff2") format("woff2"),
                 url("/assets/fonts/bootstrap-icons/bootstrap-icons.woff") format("woff");
        }
    </style>
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/qr-code.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">Главная</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/pages/news/') !== false ? 'active' : ''; ?>" href="/pages/news/">Новости</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/pages/info/') !== false ? 'active' : ''; ?>" href="/pages/info/">О ферме</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/pages/galery/') !== false ? 'active' : ''; ?>" href="/pages/galery/">Галерея</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/pages/components/') !== false ? 'active' : ''; ?>" href="/pages/components/">Компоненты</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/pages/code/') !== false ? 'active' : ''; ?>" href="/pages/code/">Код</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/pages/feedback/') !== false ? 'active' : ''; ?>" href="/pages/feedback/">Обратная связь</a>
                    </li>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/">Админ-панель</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-light me-2" id="themeToggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <?php if ($isAdmin): ?>
                                    <li><a class="dropdown-item" href="/admin/">Админ-панель</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="/auth/logout.php">Выйти</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="/auth/login.php" class="btn btn-outline-light me-2">Войти</a>
                        <a href="/auth/register.php" class="btn btn-light">Регистрация</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="container mt-4">
        <?php if (!isset($_COOKIE['analytics_notice'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert" id="analytics-notice">
            Мы используем Яндекс.Метрику для анализа посещаемости сайта. Продолжая использовать сайт, вы соглашаетесь с этим.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="acceptAnalytics()"></button>
        </div>
        <?php endif; ?>
        <?php if (isset($content)) echo $content; ?>
    </main>
    <script>
    function acceptAnalytics() {
        document.cookie = "analytics_notice=1; path=/; max-age=31536000";
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/theme.js"></script>
</body>
</html> 