<?php
$config = include 'config.php';
try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8", $config['db_user'], $config['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ext']) && isset($_POST['password'])) {
        // Создание нового пользователя или смена пароля
        $ext = $_POST['ext'];
        $pass = $_POST['password'];
        $action = $_POST['action'];
        
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO users (extension, password) VALUES (?, ?)");
            $stmt->execute([$ext, $pass]);
            $message = "Пользователь $ext создан.";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE extension = ?");
            $stmt->execute([$pass, $ext]);
            $message = "Пароль для пользователя $ext изменён.";
        }
    }
}

// Получение списка пользователей
$users = $pdo->query("SELECT extension FROM users ORDER BY extension")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="style.css">
<div class="container">
    <h2>Управление пользователями</h2>
    
    <?php if (isset($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <div class="forms-container">
        <div class="form-section">
            <h3>Создать нового пользователя</h3>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <label>Внутренний номер:</label>
                <input type="text" name="ext" required>
                <label>Пароль:</label>
                <input type="text" name="password" required>
                <button type="submit">Создать</button>
            </form>
        </div>
        
        <div class="form-section">
            <h3>Сменить пароль</h3>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <label>Пользователь:</label>
                <select name="ext" required>
                    <option value="">-- Выберите пользователя --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user['extension']) ?>">
                            <?= htmlspecialchars($user['extension']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label>Новый пароль:</label>
                <input type="text" name="password" required>
                <button type="submit">Изменить пароль</button>
            </form>
        </div>
    </div>
    
    <div class="user-list">
        <h3>Существующие пользователи</h3>
        <?php if (count($users) > 0): ?>
            <ul>
                <?php foreach ($users as $user): ?>
                    <li><?= htmlspecialchars($user['extension']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Нет зарегистрированных пользователей</p>
        <?php endif; ?>
    </div>
</div>
