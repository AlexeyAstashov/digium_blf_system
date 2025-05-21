<?php
session_start();
if (!isset($_SESSION['extension'])) {
    header("Location: index.php");
    exit;
}

$ext = $_SESSION['extension'];
$config = require 'config.php';

try {
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8", $config['db_user'], $config['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit;
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE extension = ? AND contact_id = ?");
        $stmt->execute([$ext, $_POST['delete']]);
    } elseif (isset($_POST['edit'])) {
        $_SESSION['edit_id'] = $_POST['edit'];
    } elseif (isset($_POST['update'], $_POST['contact_id'], $_POST['first_name'])) {
        // Валидация номера
        if (!preg_match('/^\+?\d+$/', $_POST['contact_id'])) {
            $_SESSION['error'] = "Номер должен содержать только цифры и может начинаться с +";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE extension = ? AND contact_id = ? AND contact_id != ?");
            $stmt->execute([$ext, $_POST['contact_id'], $_POST['update']]);
            $exists = $stmt->fetchColumn();
            
            if ($exists == 0) {
                $stmt = $pdo->prepare("UPDATE contacts SET contact_id = ?, first_name = ? WHERE extension = ? AND contact_id = ?");
                $stmt->execute([$_POST['contact_id'], $_POST['first_name'], $ext, $_POST['update']]);
            } else {
                $_SESSION['error'] = "Контакт с таким номером уже существует";
            }
            unset($_SESSION['edit_id']);
        }
    } elseif (isset($_POST['contact_id'], $_POST['first_name'])) {
        // Валидация номера
        if (!preg_match('/^\+?\d+$/', $_POST['contact_id'])) {
            $_SESSION['error'] = "Номер должен содержать только цифры и может начинаться с +";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE extension = ? AND contact_id = ?");
            $stmt->execute([$ext, $_POST['contact_id']]);
            $exists = $stmt->fetchColumn();
            
            if ($exists == 0) {
                $stmt = $pdo->prepare("INSERT INTO contacts (extension, contact_id, first_name) VALUES (?, ?, ?)");
                $stmt->execute([$ext, $_POST['contact_id'], $_POST['first_name']]);
            } else {
                $_SESSION['error'] = "Контакт с таким номером уже существует";
            }
        }
    }
}

$contacts = $pdo->prepare("SELECT id, contact_id, first_name FROM contacts WHERE extension = ? ORDER BY id ASC");
$contacts->execute([$ext]);
$contacts = $contacts->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование BLF</title>
    <link rel="stylesheet" href="editor.css">
    <script>
    function validateNumber(input) {
        // Разрешаем: цифры, + в начале, Backspace, Delete, Tab
        if (!/^\+?\d*$/.test(input.value)) {
            input.value = input.value.replace(/[^\d\+]/g, '');
            // Удаляем + если он не в начале
            if (input.value.indexOf('+') > 0) {
                input.value = input.value.replace('+', '');
            }
            // Удаляем лишние +
            if ((input.value.match(/\+/g) || []).length > 1) {
                input.value = input.value.replace(/\+/g, '');
                input.value = '+' + input.value;
            }
        }
    }
    </script>
</head>
<body>
<div class="container">
    <h2>Адресная книга BLF для номера <?= htmlspecialchars($ext) ?></h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="button-container">
        <form method="post">
            <button type="submit" name="logout" class="logout-btn">Выход</button>
        </form>
    </div>

    <table>
        <tr><th>№</th><th>Номер</th><th>Имя</th><th>Действия</th></tr>
        <?php foreach ($contacts as $index => $row): ?>
            <?php if (isset($_SESSION['edit_id']) && $_SESSION['edit_id'] == $row['contact_id']): ?>
                <tr>
                    <form method="post">
                        <td><?= $index + 1 ?></td>
                        <td><input type="text" name="contact_id" value="<?= htmlspecialchars($row['contact_id']) ?>" 
                                  required oninput="validateNumber(this)"></td>
                        <td><input type="text" name="first_name" value="<?= htmlspecialchars($row['first_name']) ?>" required></td>
                        <td class="actions">
                            <input type="hidden" name="update" value="<?= $row['contact_id'] ?>">
                            <button type="submit" class="save-btn">Сохранить</button>
                            <button type="button" class="cancel-btn" onclick="window.location.href='editor.php'">Отмена</button>
                        </td>
                    </form>
                </tr>
            <?php else: ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($row['contact_id']) ?></td>
                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                    <td class="actions">
                        <form method="post" style="display:inline;">
                            <button name="edit" value="<?= $row['contact_id'] ?>" class="edit-btn">Изменить</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <button name="delete" value="<?= $row['contact_id'] ?>" class="delete-btn">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <tr>
            <form method="post">
                <td>#</td>
                <td><input type="text" name="contact_id" required oninput="validateNumber(this)"></td>
                <td><input type="text" name="first_name" required></td>
                <td><button type="submit" class="add-btn">Добавить</button></td>
            </form>
        </tr>
    </table>
    
    <form method="post" action="generate.php">
        <button type="submit" class="update-btn">Обновить BLF</button>
    </form>
</div>
</body>
</html>
