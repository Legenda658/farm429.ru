<?php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die('Доступ запрещен');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT INTO components (name, status, price_type, price, quantity, info, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['status'],
                        $_POST['price_type'],
                        $_POST['price_type'] === 'custom' ? $_POST['price'] : null,
                        $_POST['quantity'],
                        $_POST['info']
                    ]);
                    $_SESSION['success'] = 'Компонент успешно добавлен';
                    break;
                case 'update':
                    $stmt = $pdo->prepare("UPDATE components SET name = ?, status = ?, price_type = ?, price = ?, quantity = ?, info = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['status'],
                        $_POST['price_type'],
                        $_POST['price_type'] === 'custom' ? $_POST['price'] : null,
                        $_POST['quantity'],
                        $_POST['info'],
                        $_POST['id']
                    ]);
                    $_SESSION['success'] = 'Компонент успешно обновлен';
                    break;
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM components WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $_SESSION['success'] = 'Компонент успешно удален';
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        echo '<script>window.location.href = "index.php?section=components";</script>';
        exit;
    }
}
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT * FROM components WHERE status = 'bought' ORDER BY created_at DESC");
    $purchasedComponents = $stmt->fetchAll();
    $stmt = $pdo->query("SELECT * FROM components WHERE status = 'not_bought' ORDER BY created_at DESC");
    $unpurchasedComponents = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
function getPriceTypeDisplay($type) {
    switch($type) {
        case 'custom':
            return 'Указать цену';
        case 'undefined':
            return 'Цена не определена';
        default:
            return 'Цена не определена';
    }
}
function getStatusDisplay($status) {
    switch($status) {
        case 'bought':
            return 'Куплено';
        case 'not_bought':
            return 'Не куплено';
        default:
            return 'Не куплено';
    }
}
?>
<style>
/* Стили для темной темы */
[data-bs-theme="dark"] .modal .modal-content,
[data-bs-theme="dark"] .modal .modal-header,
[data-bs-theme="dark"] .modal .modal-body,
[data-bs-theme="dark"] .modal .modal-footer {
    background-color: #212529 !important;
    color: #e9ecef !important;
    border-color: #373b3e !important;
}
[data-bs-theme="dark"] .modal .modal-title {
    color: #e9ecef !important;
}
[data-bs-theme="dark"] .modal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%) !important;
}
[data-bs-theme="dark"] .form-select,
[data-bs-theme="dark"] .form-control,
[data-bs-theme="dark"] select,
[data-bs-theme="dark"] input[type="text"],
[data-bs-theme="dark"] input[type="number"],
[data-bs-theme="dark"] textarea {
    background-color: #2b3035 !important;
    border-color: #495057 !important;
    color: #e9ecef !important;
}
[data-bs-theme="dark"] .form-select option {
    background-color: #2b3035 !important;
    color: #e9ecef !important;
}
[data-bs-theme="dark"] .form-label {
    color: #e9ecef !important;
}
[data-bs-theme="dark"] .form-select:focus,
[data-bs-theme="dark"] .form-control:focus {
    background-color: #2b3035 !important;
    border-color: #0d6efd !important;
    color: #e9ecef !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}
[data-bs-theme="dark"] .card {
    background-color: #212529 !important;
    border-color: #373b3e !important;
}
[data-bs-theme="dark"] .card-header {
    background-color: #2b3035 !important;
    border-bottom-color: #373b3e !important;
}
[data-bs-theme="dark"] .table {
    color: #e9ecef !important;
}
[data-bs-theme="dark"] .table > :not(caption) > * > * {
    background-color: #212529 !important;
    color: #e9ecef !important;
    border-bottom-color: #373b3e !important;
}
[data-bs-theme="dark"] .btn-secondary {
    background-color: #495057 !important;
    border-color: #495057 !important;
    color: #fff !important;
}
[data-bs-theme="dark"] .btn-secondary:hover {
    background-color: #5c636a !important;
    border-color: #5c636a !important;
}
[data-bs-theme="dark"] .text-muted {
    color: #adb5bd !important;
}
/* Стили для выпадающих списков в темной теме */
[data-bs-theme="dark"] .form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23e9ecef' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
}
/* Стили для placeholder в темной теме */
[data-bs-theme="dark"] .form-control::placeholder {
    color: #6c757d !important;
    opacity: 1;
}
/* Стили для disabled состояний в темной теме */
[data-bs-theme="dark"] .form-control:disabled,
[data-bs-theme="dark"] .form-control[readonly],
[data-bs-theme="dark"] .form-select:disabled {
    background-color: #343a40 !important;
    opacity: 0.7;
}
/* Стили для модального backdrop в темной теме */
[data-bs-theme="dark"] .modal-backdrop {
    background-color: rgba(0, 0, 0, 0.7) !important;
}
/* Анимация для модальных окон в темной теме */
[data-bs-theme="dark"] .modal.fade .modal-dialog {
    transition: transform .3s ease-out !important;
}
/* Стили для скроллбара в темной теме */
[data-bs-theme="dark"] ::-webkit-scrollbar {
    width: 12px;
}
[data-bs-theme="dark"] ::-webkit-scrollbar-track {
    background: #2b3035;
}
[data-bs-theme="dark"] ::-webkit-scrollbar-thumb {
    background-color: #495057;
    border-radius: 6px;
    border: 3px solid #2b3035;
}
[data-bs-theme="dark"] ::-webkit-scrollbar-thumb:hover {
    background-color: #5c636a;
}
</style>
<div class="container-fluid">
    <h2 class="mb-4">Управление компонентами</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    <!-- Форма добавления -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Добавить новый компонент</h5>
            <form action="index.php?section=components" method="post">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label for="name" class="form-label">Название</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Статус</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="not_bought">Не куплено</option>
                        <option value="bought">Куплено</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="price_type" class="form-label">Тип цены</label>
                    <select class="form-select" id="price_type" name="price_type" required>
                        <option value="undefined">Цена не определена</option>
                        <option value="custom">Указать цену</option>
                    </select>
                </div>
                <div class="mb-3 price-input" style="display: none;">
                    <label for="price" class="form-label">Цена</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0">
                </div>
                <div class="mb-3">
                    <label for="quantity" class="form-label">Количество</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="info" class="form-label">Информация</label>
                    <textarea class="form-control" id="info" name="info" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Добавить</button>
            </form>
        </div>
    </div>
    <!-- Экспорт в Excel -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Экспорт в Excel</h5>
            <form action="export.php" method="post">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="export_status[]" value="bought" id="export_bought" checked>
                    <label class="form-check-label" for="export_bought">Купленные компоненты</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="export_status[]" value="not_bought" id="export_not_bought" checked>
                    <label class="form-check-label" for="export_not_bought">Не купленные компоненты</label>
                </div>
                <button type="submit" class="btn btn-success">Экспортировать в Excel</button>
            </form>
        </div>
    </div>
    <!-- Список компонентов -->
    <div class="row">
        <!-- Купленные компоненты -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Купленные компоненты</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($purchasedComponents)): ?>
                        <p class="text-muted">Купленных компонентов пока нет</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Количество</th>
                                        <th>Цена</th>
                                        <th>Тип цены</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchasedComponents as $component): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($component['name']); ?></td>
                                            <td><?php echo $component['quantity']; ?></td>
                                            <td><?php echo $component['price'] ? number_format($component['price'], 2) . ' ₽' : '-'; ?></td>
                                            <td><?php echo getPriceTypeDisplay($component['price_type']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $component['id']; ?>">
                                                    Редактировать
                                                </button>
                                                <form action="index.php?section=components" method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $component['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Вы уверены, что хотите удалить этот компонент?')">
                                                        Удалить
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <!-- Модальное окно редактирования -->
                                        <div class="modal fade" id="editModal<?php echo $component['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Редактирование компонента</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="index.php?section=components" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="update">
                                                            <input type="hidden" name="id" value="<?php echo $component['id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="edit_name<?php echo $component['id']; ?>" class="form-label">Название</label>
                                                                <input type="text" class="form-control" id="edit_name<?php echo $component['id']; ?>" 
                                                                       name="name" value="<?php echo htmlspecialchars($component['name']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_status<?php echo $component['id']; ?>" class="form-label">Статус</label>
                                                                <select class="form-select" id="edit_status<?php echo $component['id']; ?>" name="status" required>
                                                                    <option value="not_bought" <?php echo $component['status'] === 'not_bought' ? 'selected' : ''; ?>>Не куплено</option>
                                                                    <option value="bought" <?php echo $component['status'] === 'bought' ? 'selected' : ''; ?>>Куплено</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_price_type<?php echo $component['id']; ?>" class="form-label">Тип цены</label>
                                                                <select class="form-select" id="edit_price_type<?php echo $component['id']; ?>" name="price_type" required>
                                                                    <option value="undefined" <?php echo $component['price_type'] === 'undefined' ? 'selected' : ''; ?>>Цена не определена</option>
                                                                    <option value="custom" <?php echo $component['price_type'] === 'custom' ? 'selected' : ''; ?>>Указать цену</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3 price-input">
                                                                <label for="edit_price<?php echo $component['id']; ?>" class="form-label">Цена</label>
                                                                <input type="number" class="form-control" id="edit_price<?php echo $component['id']; ?>" 
                                                                       name="price" value="<?php echo $component['price']; ?>" step="0.01" min="0">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_quantity<?php echo $component['id']; ?>" class="form-label">Количество</label>
                                                                <input type="number" class="form-control" id="edit_quantity<?php echo $component['id']; ?>" 
                                                                       name="quantity" value="<?php echo $component['quantity']; ?>" min="1" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_info<?php echo $component['id']; ?>" class="form-label">Информация</label>
                                                                <textarea class="form-control" id="edit_info<?php echo $component['id']; ?>" 
                                                                          name="info" rows="3"><?php echo htmlspecialchars($component['info']); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                                            <button type="submit" class="btn btn-primary">Сохранить</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Некупленные компоненты -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Некупленные компоненты</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($unpurchasedComponents)): ?>
                        <p class="text-muted">Некупленных компонентов пока нет</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Количество</th>
                                        <th>Цена</th>
                                        <th>Тип цены</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unpurchasedComponents as $component): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($component['name']); ?></td>
                                            <td><?php echo $component['quantity']; ?></td>
                                            <td><?php echo $component['price'] ? number_format($component['price'], 2) . ' ₽' : '-'; ?></td>
                                            <td><?php echo getPriceTypeDisplay($component['price_type']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $component['id']; ?>">
                                                    Редактировать
                                                </button>
                                                <form action="index.php?section=components" method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $component['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Вы уверены, что хотите удалить этот компонент?')">
                                                        Удалить
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <!-- Модальное окно редактирования -->
                                        <div class="modal fade" id="editModal<?php echo $component['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Редактирование компонента</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="index.php?section=components" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="update">
                                                            <input type="hidden" name="id" value="<?php echo $component['id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="edit_name<?php echo $component['id']; ?>" class="form-label">Название</label>
                                                                <input type="text" class="form-control" id="edit_name<?php echo $component['id']; ?>" 
                                                                       name="name" value="<?php echo htmlspecialchars($component['name']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_status<?php echo $component['id']; ?>" class="form-label">Статус</label>
                                                                <select class="form-select" id="edit_status<?php echo $component['id']; ?>" name="status" required>
                                                                    <option value="not_bought" <?php echo $component['status'] === 'not_bought' ? 'selected' : ''; ?>>Не куплено</option>
                                                                    <option value="bought" <?php echo $component['status'] === 'bought' ? 'selected' : ''; ?>>Куплено</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_price_type<?php echo $component['id']; ?>" class="form-label">Тип цены</label>
                                                                <select class="form-select" id="edit_price_type<?php echo $component['id']; ?>" name="price_type" required>
                                                                    <option value="undefined" <?php echo $component['price_type'] === 'undefined' ? 'selected' : ''; ?>>Цена не определена</option>
                                                                    <option value="custom" <?php echo $component['price_type'] === 'custom' ? 'selected' : ''; ?>>Указать цену</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3 price-input">
                                                                <label for="edit_price<?php echo $component['id']; ?>" class="form-label">Цена</label>
                                                                <input type="number" class="form-control" id="edit_price<?php echo $component['id']; ?>" 
                                                                       name="price" value="<?php echo $component['price']; ?>" step="0.01" min="0">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_quantity<?php echo $component['id']; ?>" class="form-label">Количество</label>
                                                                <input type="number" class="form-control" id="edit_quantity<?php echo $component['id']; ?>" 
                                                                       name="quantity" value="<?php echo $component['quantity']; ?>" min="1" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_info<?php echo $component['id']; ?>" class="form-label">Информация</label>
                                                                <textarea class="form-control" id="edit_info<?php echo $component['id']; ?>" 
                                                                          name="info" rows="3"><?php echo htmlspecialchars($component['info']); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                                            <button type="submit" class="btn btn-primary">Сохранить</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updatePriceInputVisibility(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return; 
        const form = select.closest('form');
        if (!form) return; 
        const priceInput = form.querySelector('.price-input');
        if (!priceInput) return; 
        if (select.value === 'custom') {
            priceInput.style.display = 'block';
        } else {
            priceInput.style.display = 'none';
        }
    }
    document.querySelectorAll('select[name="price_type"]').forEach(select => {
        if (select) { 
            select.addEventListener('change', function() {
                updatePriceInputVisibility(this.id);
            });
            updatePriceInputVisibility(select.id);
        }
    });
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
                this.querySelectorAll('.form-control, .form-select').forEach(element => {
                    element.style.backgroundColor = '#2b3035';
                    element.style.borderColor = '#495057';
                    element.style.color = '#e9ecef';
                });
                this.querySelector('.modal-content').style.backgroundColor = '#212529';
                this.querySelector('.modal-header').style.backgroundColor = '#212529';
                this.querySelector('.modal-body').style.backgroundColor = '#212529';
                this.querySelector('.modal-footer').style.backgroundColor = '#212529';
                this.querySelectorAll('.form-label, .modal-title').forEach(element => {
                    element.style.color = '#e9ecef';
                });
            }
        });
    });
});
</script> 