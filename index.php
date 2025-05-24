<?php
session_start();
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$config = require 'config.php';

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8", 
                  $config['db_user'], $config['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extension'], $_POST['password'])) {
    $ext = $_POST['extension'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE extension = ?");
    $stmt->execute([$ext]);
    $user = $stmt->fetch();
    
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['extension'] = $ext;
            
            if ($ext === 'admin') {
                $_SESSION['admin_logged_in'] = true;
                header("Location: admin.php");
                exit;
            }
            
            header("Location: editor.php");
            exit;
        } else {
            $error = "Неверный пароль!";
        }
    } else {
        $error = "Пользователь не найден!";
    }
}

$users = $pdo->query("SELECT extension FROM users ORDER BY extension")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Авторизация</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Выберите номер телефона</h2>
    
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="user-list">
        <?php foreach ($users as $user): ?>
            <div class="user-item">
                <button class="user-btn <?= $user === 'admin' ? 'admin-btn' : 'select-btn' ?>" 
                        onclick="showPasswordModal('<?= htmlspecialchars($user) ?>')">
                    <?= htmlspecialchars($user) ?>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="passwordModal" class="modal hidden">
    <div class="modal-content">
        <h3>Введите пароль для <span id="modalExt"></span></h3>
        <form method="post" id="passwordForm">
            <input type="hidden" name="extension" id="extensionInput">
            <input type="password" name="password" placeholder="Пароль" required>
            <div class="form-buttons">
                <button type="submit" class="submit-btn">Войти</button>
                <button type="button" onclick="closeModal()" class="cancel-btn">Отмена</button>
            </div>
        </form>
    </div>
</div>

<script>
function showPasswordModal(ext) {
    document.getElementById('modalExt').textContent = ext;
    document.getElementById('extensionInput').value = ext;
    document.getElementById('passwordModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('passwordModal').classList.add('hidden');
}

window.onclick = function(event) {
    const modal = document.getElementById('passwordModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>
</body>
</html>
