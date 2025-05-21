<?php
session_start();

// Перенаправляем если не авторизованы как админ
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$config = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8", 
        $config['db_user'], 
        $config['db_pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Очистка POST-данных после обработки
$formData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    if (isset($_POST['ext'], $_POST['password'])) {
                        $ext = $_POST['ext'];
                        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (extension, password) VALUES (?, ?)");
                        $stmt->execute([$ext, $pass]);
                        $message = "Пользователь $ext создан.";
                        // Очищаем данные формы после успешного создания
                        $formData = [];
                    }
                    break;
                    
                case 'update':
                    if (isset($_POST['ext'], $_POST['password']) && !empty($_POST['password'])) {
                        $ext = $_POST['ext'];
                        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE extension = ?");
                        $stmt->execute([$pass, $ext]);
                        $message = "Пароль для пользователя $ext изменён.";
                    } else {
                        $error = "Пароль не может быть пустым";
                    }
                    break;
                    
                case 'delete':
                    if (isset($_POST['ext']) && $_POST['ext'] !== 'admin') {
                        $ext = $_POST['ext'];
                        $stmt = $pdo->prepare("DELETE FROM users WHERE extension = ?");
                        $stmt->execute([$ext]);
                        $message = "Пользователь $ext удалён.";
                    }
                    break;
                    
                case 'update_admin':
                    if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
                        $pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE extension = 'admin'");
                        $stmt->execute([$pass]);
                        $message = "Пароль администратора изменён.";
                    } else {
                        $error = "Пароль не может быть пустым";
                    }
                    break;
            }
        } catch (PDOException $e) {
            $error = "Ошибка: " . $e->getMessage();
        }
    }
}

// Получаем список всех пользователей
$users = $pdo->query("SELECT extension FROM users ORDER BY extension")
            ->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями Digium Phones</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Управление пользователями Digium Phones</h2>
    
    <?php if (isset($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="forms-container">
        <div class="form-section">
            <h3>Создать нового пользователя</h3>
            <form method="post" id="createUserForm" autocomplete="off">
                <input type="hidden" name="action" value="create">
                <label>Внутренний номер:</label>
                <input type="text" name="ext" required pattern="\d+" title="Только цифры"
                       value="<?= isset($formData['ext']) ? htmlspecialchars($formData['ext']) : '' ?>">
                <label>Пароль:</label>
                <input type="password" name="password" required autocomplete="new-password">
		<div class="form-buttons">
            	    <button type="submit">Создать</button>
                    <button type="reset" class="reset-btn">Очистить</button>
		</div>
            </form>
        </div>
        
        <div class="form-section user-management">
            <h3>Выберите пользователя</h3>
            <form method="post" id="userManagementForm" autocomplete="off">
                <label>Пользователь:</label>
                <select name="ext" id="userSelect" required>
                    <option value="">-- Выберите пользователя --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user['extension']) ?>">
                            <?= htmlspecialchars($user['extension']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="password-field" id="passwordField">
                    <label>Новый пароль:</label>
                    <input type="password" name="password" autocomplete="new-password">
                </div>
                
                <div class="form-buttons">
                    <button type="submit" name="action" value="update" class="update-btn">Изменить пароль</button>
                    <button type="submit" name="action" value="delete" class="delete-btn" id="deleteBtn" disabled>Удалить пользователя</button>
                </div>
            </form>
        </div>
<!--
        <div class="form-section admin-password">
            <h3>Изменить пароль администратора</h3>
            <form method="post" autocomplete="off">
                <input type="hidden" name="action" value="update_admin">
                <label>Новый пароль:</label>
                <input type="password" name="new_password" required autocomplete="new-password">
                <button type="submit" class="admin-btn">Изменить пароль админа</button>
            </form>
        </div>
    </div>
-->
    <div class="user-list">
<!--
        <h3>Текущие пользователи</h3>
        <?php if ($users): ?>
            <ul>
                <?php foreach ($users as $user): ?>
                    <li><?= htmlspecialchars($user['extension']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Нет пользователей</p>
        <?php endif; ?>
-->    
    </div>
    <div style="margin-top: 20px;">
        <a href="index.php?logout" class="logout-btn">Выйти из админки</a>
    </div>
</div>

<script>
// Очистка формы после успешной отправки
if (window.history.replaceState && <?= isset($message) ? 'true' : 'false' ?>) {
    window.history.replaceState(null, null, window.location.href);
    document.getElementById('createUserForm').reset();
}

document.getElementById('userSelect').addEventListener('change', function() {
    const selectedUser = this.value;
    const deleteBtn = document.getElementById('deleteBtn');
    const passwordField = document.getElementById('passwordField');
    
    deleteBtn.disabled = (selectedUser === 'admin' || selectedUser === '');
    passwordField.style.display = selectedUser ? 'block' : 'none';
    
    // Очищаем поле пароля при смене пользователя
    if (passwordField.style.display === 'block') {
        passwordField.querySelector('input').value = '';
    }
});

document.getElementById('userManagementForm').addEventListener('submit', function(e) {
    const action = e.submitter.value;
    const password = document.querySelector('#userManagementForm input[name="password"]').value;
    
    if (action === 'update' && !password) {
        alert('Для изменения пароля введите новый пароль');
        e.preventDefault();
    }
});
</script>
</body>
</html>
