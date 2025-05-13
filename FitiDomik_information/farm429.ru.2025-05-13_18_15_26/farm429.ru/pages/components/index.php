<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
$pageTitle = "Компоненты";
try {
    $stmt = $pdo->query("SELECT * FROM components ORDER BY name");
    $components = $stmt->fetchAll();
    $bought_components = [];
    $not_bought_components = [];
    foreach ($components as $component) {
        if ($component['status'] === 'bought') {
            $bought_components[] = $component;
        } else {
            $not_bought_components[] = $component;
        }
    }
    $not_bought_total = calculateTotalAmount($not_bought_components);
    $bought_total = calculateTotalAmount($bought_components);
    ob_start();
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Компоненты</h1>
        <form action="export.php" method="post" class="d-flex align-items-center">
            <div class="form-check me-3">
                <input class="form-check-input" type="checkbox" name="export_status[]" value="not_bought" id="notBought" checked>
                <label class="form-check-label" for="notBought">Необходимые</label>
            </div>
            <div class="form-check me-3">
                <input class="form-check-input" type="checkbox" name="export_status[]" value="bought" id="bought" checked>
                <label class="form-check-label" for="bought">Купленные</label>
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Экспорт в Excel
            </button>
        </form>
    </div>
    <div class="card mb-4 info-card">
        <div class="card-body">
            <h5 class="card-title">Общая информация</h5>
            <p>Общая стоимость необходимых компонентов: <?= number_format($not_bought_total['total'], 2, ',', ' ') ?> ₽
                <?php if ($not_bought_total['unknown_count'] > 0): ?>
                    <span class="text-warning">(компонентов с неопределенной ценой: <?= $not_bought_total['unknown_count'] ?>)</span>
                <?php endif; ?>
            </p>
            <p>Общая стоимость купленных компонентов: <?= number_format($bought_total['total'], 2, ',', ' ') ?> ₽
                <?php if ($bought_total['unknown_count'] > 0): ?>
                    <span class="text-warning">(компонентов с неопределенной ценой: <?= $bought_total['unknown_count'] ?>)</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <!-- Необходимые компоненты -->
    <h2 class="mb-3">Необходимые компоненты</h2>
    <?php if (empty($not_bought_components)): ?>
        <div class="alert alert-info">Нет необходимых компонентов</div>
    <?php else: ?>
        <div class="table-responsive mb-5 table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Количество</th>
                        <th>Цена</th>
                        <th>Информация</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($not_bought_components as $component): ?>
                        <tr>
                            <td><?= htmlspecialchars($component['name']) ?></td>
                            <td><?= $component['quantity'] ?></td>
                            <td class="price-<?= $component['price_type'] ?>">
                                <?= formatPrice($component['price_type'], $component['price']) ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($component['info'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <!-- Купленные компоненты -->
    <h2 class="mb-3">Купленные компоненты</h2>
    <?php if (empty($bought_components)): ?>
        <div class="alert alert-info">Нет купленных компонентов</div>
    <?php else: ?>
        <div class="table-responsive table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Количество</th>
                        <th>Цена</th>
                        <th>Информация</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bought_components as $component): ?>
                        <tr>
                            <td><?= htmlspecialchars($component['name']) ?></td>
                            <td><?= $component['quantity'] ?></td>
                            <td class="price-<?= $component['price_type'] ?>">
                                <?= formatPrice($component['price_type'], $component['price']) ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($component['info'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<style>
/* Общие стили */
.container {
    color: var(--text-color);
}
h1, h2 {
    color: var(--text-color);
}
/* Стили для карточки информации */
.info-card {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}
.info-card .card-title {
    color: var(--text-color);
    font-size: 1.25rem;
    margin-bottom: 1rem;
}
.info-card p {
    color: var(--text-color);
    margin-bottom: 0.5rem;
}
/* Стили для формы экспорта */
.form-check-label {
    color: var(--text-color);
}
.form-check-input {
    background-color: var(--input-bg);
    border-color: var(--border-color);
}
.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}
/* Стили для таблицы */
.table-container {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}
.table {
    margin-bottom: 0;
    color: var(--text-color);
}
.table thead th {
    background-color: var(--header-bg);
    color: var(--text-color);
    border-bottom: 2px solid var(--border-color);
    padding: 1rem;
    font-weight: 600;
}
.table tbody tr {
    border-bottom: 1px solid var(--border-color);
    transition: background-color 0.2s ease;
}
.table tbody tr:last-child {
    border-bottom: none;
}
.table tbody tr:hover {
    background-color: var(--hover-bg);
}
.table td {
    padding: 1rem;
    vertical-align: middle;
    color: var(--text-color);
}
/* Стили для цен */
.price-bought { 
    color: var(--success-color); 
    font-weight: 500; 
}
.price-custom { 
    color: var(--info-color); 
    font-weight: 500;
}
.price-undefined { 
    color: var(--warning-color); 
    font-weight: 500;
}
/* Dark theme specific styles */
[data-theme="dark"] .info-card {
    background-color: var(--card-bg);
    border-color: var(--border-color);
}
[data-theme="dark"] .table-container {
    background-color: var(--card-bg);
    border-color: var(--border-color);
}
[data-theme="dark"] .table thead th {
    background-color: var(--header-bg);
    border-color: var(--border-color);
}
[data-theme="dark"] .table tbody tr {
    border-color: var(--border-color);
}
[data-theme="dark"] .table tbody tr:hover {
    background-color: var(--hover-bg);
}
/* Alert styles */
.alert {
    background-color: var(--card-bg);
    border-color: var(--border-color);
    color: var(--text-color);
}
.alert-info {
    background-color: var(--info-bg);
    border-color: var(--info-border);
    color: var(--info-text);
}
.text-warning {
    color: var(--warning-color) !important;
}
/* Адаптивность */
@media (max-width: 768px) {
    .table-container {
        padding: 0.5rem;
    }
    .table thead th,
    .table tbody td {
        padding: 0.75rem;
    }
}
</style>
<?php
    $content = ob_get_clean();
    require_once __DIR__ . '/../../layout.php';
} catch(PDOException $e) {
    error_log("Ошибка при получении компонентов: " . $e->getMessage());
    die("Произошла ошибка при загрузке компонентов");
}
?> 