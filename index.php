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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['extension'], $_POST['password'])) {
        $ext = $_POST['extension'];
        $password = $_POST['password'];
        
        // Получаем хеш пароля из БД
        $stmt = $pdo->prepare("SELECT password FROM users WHERE extension = ?");
        $stmt->execute([$ext]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Проверяем пароль
            if (password_verify($password, $user['password'])) {
                $_SESSION['extension'] = $ext;
                
                // Если это администратор - перенаправляем в админку
                if ($ext === 'admin') {
                    $_SESSION['admin_logged_in'] = true;
                    header("Location: admin.php");
                    exit;
                }
                
                // Для обычных пользователей - в редактор
                header("Location: editor.php");
                exit;
            } else {
                $error = "Неверный пароль!";
            }
        } else {
            $error = "Пользователь не найден!";
        }
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
    <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.4);
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background-color: #fefefe;
        padding: 20px;
        border-radius: 5px;
        width: 300px;
    }
    .admin-btn {
        background-color: #ff9800;
        color: white;
    }
    </style>
</head>
<body>
<div class="container">
    <h2>Выберите номер телефона</h2>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="user-list">
        <?php foreach ($users as $user): ?>
            <div class="user-item">
                <button class="user-btn <?= $user === 'admin' ? 'admin-btn' : '' ?>" 
                        onclick="showPasswordModal('<?= htmlspecialchars($user) ?>')">
                    <?= htmlspecialchars($user) ?>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Модальное окно для ввода пароля -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <h3>Введите пароль для <span id="modalExt"></span></h3>
        <form method="post" id="passwordForm">
            <input type="hidden" name="extension" id="extensionInput">
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit" class="submit-btn">Войти</button>
        </form>
    </div>
</div>

<script>
function showPasswordModal(ext) {
    document.getElementById('modalExt').textContent = ext;
    document.getElementById('extensionInput').value = ext;
    document.getElementById('passwordModal').style.display = 'flex';
}

// Закрытие модального окна при клике вне его
window.onclick = function(event) {
    const modal = document.getElementById('passwordModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>
</body>
</html>
