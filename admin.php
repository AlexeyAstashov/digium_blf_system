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

$message = '';
$error = '';
$formData = []; // Для сохранения данных формы после неудачной отправки

// Получение текущих настроек по умолчанию SmartBLF
$defaultSettings = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM blf_default_settings");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Если таблица пуста, создаем запись с id=1
        $pdo->exec("INSERT INTO blf_default_settings (id, pickupcall, myintercom, idle_led_color, idle_led_state, idle_ringtone, ringing_led_color, ringing_led_state, ringing_ringtone, busy_led_color, busy_led_state, busy_ringtone, hold_led_color, hold_led_state) VALUES (1, 1, 1, 'green', 'on', 'Digium', 'red', 'fast', 'Techno', 'red', 'on', 'Techno', 'amber', 'slow')");
    }

    // Всегда получаем запись с id=1
    $stmt = $pdo->query("SELECT * FROM blf_default_settings WHERE id = 1");
    $defaultSettings = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Ошибка при загрузке настроек по умолчанию: " . $e->getMessage();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST; // Сохраняем данные формы

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
                        $formData = []; // Очищаем данные формы после успешного создания
                    }
                    break;

                case 'update':
                    if (isset($_POST['ext'], $_POST['password']) && !empty($_POST['password'])) {
                        $ext = $_POST['ext'];
                        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT); // PHP всегда получает 'password' благодаря JS
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
                    } else if ($_POST['ext'] === 'admin') {
                        $error = "Невозможно удалить пользователя 'admin'.";
                    }
                    break;

                case 'update_blf_defaults':
                    $pickupcall = isset($_POST['default_pickupcall']) ? 1 : 0;
                    $myintercom = isset($_POST['default_myintercom']) ? 1 : 0;
                    $idle_led_color = $_POST['default_idle_led_color'];
                    $idle_led_state = $_POST['default_idle_led_state'];
                    $idle_ringtone = $_POST['default_idle_ringtone'];
                    $ringing_led_color = $_POST['default_ringing_led_color'];
                    $ringing_led_state = $_POST['default_ringing_led_state'];
                    $ringing_ringtone = $_POST['default_ringing_ringtone'];
                    $busy_led_color = $_POST['default_busy_led_color'];
                    $busy_led_state = $_POST['default_busy_led_state'];
                    $busy_ringtone = $_POST['default_busy_ringtone'];
                    $hold_led_color = $_POST['default_hold_led_color'];
                    $hold_led_state = $_POST['default_hold_led_state'];

                    $stmt = $pdo->prepare("UPDATE blf_default_settings SET
                        pickupcall = ?,
                        myintercom = ?,
                        idle_led_color = ?,
                        idle_led_state = ?,
                        idle_ringtone = ?,
                        ringing_led_color = ?,
                        ringing_led_state = ?,
                        ringing_ringtone = ?,
                        busy_led_color = ?,
                        busy_led_state = ?,
                        busy_ringtone = ?,
                        hold_led_color = ?,
                        hold_led_state = ?
                        WHERE id = 1"); // Всегда обновляем единственную запись

                    $stmt->execute([
                        $pickupcall, $myintercom,
                        $idle_led_color, $idle_led_state, $idle_ringtone,
                        $ringing_led_color, $ringing_led_state, $ringing_ringtone,
                        $busy_led_color, $busy_led_state, $busy_ringtone,
                        $hold_led_color, $hold_led_state
                    ]);
                    $message = "Настройки SmartBLF по умолчанию обновлены.";

                    // Обновляем defaultSettings после успешного обновления
                    $stmt = $pdo->query("SELECT * FROM blf_default_settings WHERE id = 1");
                    $defaultSettings = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <style>
    </style>
</head>
<body>
<div class="container">
    <h2>Управление пользователями Digium Phones</h2>

    <?php if ($message): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="forms-container">
        <div class="form-section">
            <h3>Создать нового пользователя</h3>
            <form method="post" id="createUserForm" autocomplete="off">
                <input type="hidden" name="action" value="create">
                <label for="new_ext">Внутренний номер:</label>
                <input type="text" name="ext" id="new_ext" required pattern="\d+" title="Только цифры"
                       value="<?= isset($formData['ext']) ? htmlspecialchars($formData['ext']) : '' ?>">
                <label for="new_password">Пароль:</label>
                <input type="password" name="password" id="new_password" required autocomplete="new-password">
                <div class="form-buttons">
                    <button type="submit">Создать</button>
                    <button type="reset" class="reset-btn">Очистить</button>
                </div>
            </form>
        </div>

        <div class="form-section user-management">
            <h3>Изменить/удалить пользователя</h3>
            <form method="post" id="userManagementForm" autocomplete="off">
                <label for="userSelect">Пользователь:</label>
                <select name="ext" id="userSelect" required>
                    <option value="">-- Выберите пользователя --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user['extension']) ?>">
                            <?= htmlspecialchars($user['extension']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="password-field" id="passwordField" style="display: none;">
                    <div id="singlePasswordField">
                        <label for="update_password">Новый пароль:</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="update_password" autocomplete="new-password">
                            <span class="toggle-password" onclick="togglePasswordVisibility('update_password')">👁️</span>
                        </div>
                    </div>
                    <div id="adminPasswordFields" style="display: none;">
                        <label for="admin_password">Новый пароль администратора:</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="admin_password" id="admin_password" autocomplete="new-password">
                            <span class="toggle-password" onclick="togglePasswordVisibility('admin_password')">👁️</span>
                        </div>
                        <label for="confirm_admin_password">Подтвердите пароль:</label>
                        <input type="password" name="confirm_admin_password_unused" id="confirm_admin_password" autocomplete="new-password">
                        </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" name="action" value="update" class="update-btn">Изменить пароль</button>
                    <button type="submit" name="action" value="delete" class="delete-btn" id="deleteBtn" disabled>Удалить пользователя</button>
                </div>
            </form>
        </div>
    </div>

    <hr>

    <button type="button" class="show-blf-settings-btn" id="toggleBlfSettings">Показать/Скрыть настройки SmartBLF по умолчанию</button>

    <div class="blf-settings-container" id="blfSettingsContainer" style="display: none;" >
        <h3>Настройки SmartBLF по умолчанию для новых контактов</h3>
        <form method="post">
            <input type="hidden" name="action" value="update_blf_defaults">

            <div class="blf-settings-grid">
                <div class="blf-group">
                    <h4>Дополнительные настройки приложений</h4>
                    <div class="blf-item">
                        <label for="default_pickupcall">Pickup Call:</label>
                        <div class="input-wrapper">
                            <input type="checkbox" name="default_pickupcall" id="default_pickupcall" value="1" <?= ($defaultSettings['pickupcall'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="blf-item">
                        <label for="default_myintercom">My Intercom:</label>
                        <div class="input-wrapper">
                            <input type="checkbox" name="default_myintercom" id="default_myintercom" value="1" <?= ($defaultSettings['myintercom'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>

                <div class="blf-group">
                    <h4>Состояние: Idle</h4>
                    <div class="blf-item">
                        <label for="default_idle_led_color">LED Color:</label>
                        <div class="input-wrapper">
                            <select name="default_idle_led_color" id="default_idle_led_color">
                                <option value="green" <?= (($defaultSettings['idle_led_color'] ?? 'green') == 'green') ? 'selected' : '' ?>>Green</option>
                                <option value="amber" <?= (($defaultSettings['idle_led_color'] ?? 'green') == 'amber') ? 'selected' : '' ?>>Amber</option>
                                <option value="red" <?= (($defaultSettings['idle_led_color'] ?? 'green') == 'red') ? 'selected' : '' ?>>Red</option>
                            </select>
                        </div>
                    </div>
                    <div class="blf-item">
                        <label for="default_idle_led_state">LED State:</label>
                        <div class="input-wrapper">
                            <select name="default_idle_led_state" id="default_idle_led_state">
                                <option value="on" <?= (($defaultSettings['idle_led_state'] ?? 'on') == 'on') ? 'selected' : '' ?>>On</option>
                                <option value="off" <?= (($defaultSettings['idle_led_state'] ?? 'on') == 'off') ? 'selected' : '' ?>>Off</option>
                            </select>
                        </div>
                    </div>
                    <div class="blf-item">
                        <label for="default_idle_ringtone">Ringtone:</label>
                        <div class="input-wrapper">
                            <select name="default_idle_ringtone" id="default_idle_ringtone">
                                <option value="Alarm" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Alarm') ? 'selected' : '' ?>>Alarm</option>
                                <option value="Chimes" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Chimes') ? 'selected' : '' ?>>Chimes</option>
                                <option value="Digium" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Digium') ? 'selected' : '' ?>>Digium</option>
                                <option value="GuitarStrum" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'GuitarStrum') ? 'selected' : '' ?>>GuitarStrum</option>
                                <option value="Jingle" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Jingle') ? 'selected' : '' ?>>Jingle</option>
                                <option value="Office2" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Office2') ? 'selected' : '' ?>>Office2</option>
                                <option value="Office" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Office') ? 'selected' : '' ?>>Office</option>
                                <option value="RotaryPhone" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'RotaryPhone') ? 'selected' : '' ?>>RotaryPhone</option>
                                <option value="SteelDrum" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'SteelDrum') ? 'selected' : '' ?>>SteelDrum</option>
                                <option value="Techno" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Techno') ? 'selected' : '' ?>>Techno</option>
                                <option value="Theme" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Theme') ? 'selected' : '' ?>>Theme</option>
                                <option value="Tweedle" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Tweedle') ? 'selected' : '' ?>>Tweedle</option>
                                <option value="Twinkle" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Twinkle') ? 'selected' : '' ?>>Twinkle</option>
                                <option value="Vibe" <?= (($defaultSettings['idle_ringtone'] ?? 'Digium') == 'Vibe') ? 'selected' : '' ?>>Vibe</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="blf-group">
                    <h4>Состояние: Ringing</h4>
                    <div class="blf-item">
                        <label for="default_ringing_led_color">LED Color:</label>
                        <div class="input-wrapper">
                            <select name="default_ringing_led_color" id="default_ringing_led_color">
                                <option value="green" <?= (($defaultSettings['ringing_led_color'] ?? 'red') == 'green') ? 'selected' : '' ?>>Green</option>
                                <option value="amber" <?= (($defaultSettings['ringing_led_color'] ?? 'red') == 'amber') ? 'selected' : '' ?>>Amber</option>
                                <option value="red" <?= (($defaultSettings['ringing_led_color'] ?? 'red') == 'red') ? 'selected' : '' ?>>Red</option>
                            </select>
                        </div>
                    </div>
                    <div class="blf-item">
                        <label for="default_ringing_led_state">LED State:</label>
                        <div class="input-wrapper">
                            <select name="default_ringing_led_state" id="default_ringing_led_state">
                                <option value="fast" <?= (($defaultSettings['ringing_led_state'] ?? 'fast') == 'fast') ? 'selected' : '' ?>>Fast</option>
                                <option value="slow" <?= (($defaultSettings['ringing_led_state'] ?? 'fast') == 'slow') ? 'selected' : '' ?>>Slow</option>
                                <option value="on" <?= (($defaultSettings['ringing_led_state'] ?? 'fast') == 'on') ? 'selected' : '' ?>>On</option>
                                <option value="off" <?= (($defaultSettings['ringing_led_state'] ?? 'fast') == 'off') ? 'selected' : '' ?>>Off</option>
                            </select>
                        </div>
                    </div>
                    <div class="blf-item">
                        <label for="default_ringing_ringtone">Ringtone:</label>
                        <div class="input-wrapper">
                            <select name="default_ringing_ringtone" id="default_ringing_ringtone">
                                <option value="Alarm" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Alarm') ? 'selected' : '' ?>>Alarm</option>
                                <option value="Chimes" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Chimes') ? 'selected' : '' ?>>Chimes</option>
                                <option value="Digium" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Digium') ? 'selected' : '' ?>>Digium</option>
                                <option value="GuitarStrum" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'GuitarStrum') ? 'selected' : '' ?>>GuitarStrum</option>
                                <option value="Jingle" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Jingle') ? 'selected' : '' ?>>Jingle</option>
                                <option value="Office2" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Office2') ? 'selected' : '' ?>>Office2</option>
                                <option value="Office" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Office') ? 'selected' : '' ?>>Office</option>
                                <option value="RotaryPhone" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'RotaryPhone') ? 'selected' : '' ?>>RotaryPhone</option>
                                <option value="SteelDrum" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'SteelDrum') ? 'selected' : '' ?>>SteelDrum</option>
                                <option value="Techno" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Techno') ? 'selected' : '' ?>>Techno</option>
                                <option value="Theme" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Theme') ? 'selected' : '' ?>>Theme</option>
                                <option value="Tweedle" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Tweedle') ? 'selected' : '' ?>>Tweedle</option>
                                <option value="Twinkle" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Twinkle') ? 'selected' : '' ?>>Twinkle</option>
                                <option value="Vibe" <?= (($defaultSettings['ringing_ringtone'] ?? 'Techno') == 'Vibe') ? 'selected' : '' ?>>Vibe</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="blf-group">
                    <h4>Состояние: Busy</h4>
                    <div class="blf-item">
                        <label for="default_busy_led_color">LED Color:</label>
                        <div class="input-wrapper">
                            <select name="default_busy_led_color" id="default_busy_led_color">
                                <option value="green" <?= (($defaultSettings['busy_led_color'] ?? 'red') == 'green') ? 'selected' : '' ?>>Green</option>
                                <option value="amber" <?= (($defaultSettings['busy_led_color'] ?? 'red') == 'amber') ? 'selected' : '' ?>>Amber</option>
                                <option value="red" <?= (($defaultSettings['busy_led_color'] ?? 'red') == 'red') ? 'selected' : '' ?>>Red</option>
                            </select>
                        </div>
                    </div>
                    <div class="blf-item">
                        <label for="default_busy_led_state">LED State:</label>
                        <div class="input-wrapper">
                            <select name="default_busy_led_state" id="default_busy_led_state">
                                <option value="on" <?= (($defaultSettings['busy_led_state'] ?? 'on') == 'on') ? 'selected' : '' ?>>On</option>
                                <option value="off" <?= (($defaultSettings['busy_led_state'] ?? 'on') == 'off') ? 'selected' : '' ?>>Off</option>
                            </select>
                        </div>
                    </div>
                    <div class="blf-item">
                        <label for="default_busy_ringtone">Ringtone:</label>
                        <div class="input-wrapper">
                            <select name="default_busy_ringtone" id="default_busy_ringtone">
                                <option value="Alarm" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Alarm') ? 'selected' : '' ?>>Alarm</option>
                                <option value="Chimes" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Chimes') ? 'selected' : '' ?>>Chimes</option>
                                <option value="Digium" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Digium') ? 'selected' : '' ?>>Digium</option>
                                <option value="GuitarStrum" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'GuitarStrum') ? 'selected' : '' ?>>GuitarStrum</option>
                                <option value="Jingle" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Jingle') ? 'selected' : '' ?>>Jingle</option>
                                <option value="Office2" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Office2') ? 'selected' : '' ?>>Office2</option>
                                <option value="Office" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Office') ? 'selected' : '' ?>>Office</option>
                                <option value="RotaryPhone" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'RotaryPhone') ? 'selected' : '' ?>>RotaryPhone</option>
                                <option value="SteelDrum" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'SteelDrum') ? 'selected' : '' ?>>SteelDrum</option>
                                <option value="Techno" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Techno') ? 'selected' : '' ?>>Techno</option>
                                <option value="Theme" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Theme') ? 'selected' : '' ?>>Theme</option>
                                <option value="Tweedle" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Tweedle') ? 'selected' : '' ?>>Tweedle</option>
                                <option value="Twinkle" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Twinkle') ? 'selected' : '' ?>>Twinkle</option>
                                <option value="Vibe" <?= (($defaultSettings['busy_ringtone'] ?? 'Techno') == 'Vibe') ? 'selected' : '' ?>>Vibe</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="blf-group">
                    <h4>Состояние: On Hold</h4>
                    <div class="blf-item">
                        <label for="default_hold_led_color">LED Color:</label>
                        <div class="input-wrapper">
                            <select name="default_hold_led_color" id="default_hold_led_color">
                                <option value="green" <?= (($defaultSettings['hold_led_color'] ?? 'amber') == 'green') ? 'selected' : '' ?>>Green</option>
                                <option value="amber" <?= (($defaultSettings['hold_led_color'] ?? 'amber') == 'amber') ? 'selected' : '' ?>>Amber</option>
                                <option value="red" <?= (($defaultSettings['hold_led_color'] ?? 'amber') == 'red') ? 'selected' : '' ?>>Red</option>
                            </select>
                        </div>
                    </div>
                    <div class="blf-item">
                        <label for="default_hold_led_state">LED State:</label>
                        <div class="input-wrapper">
                            <select name="default_hold_led_state" id="default_hold_led_state">
                                <option value="fast" <?= (($defaultSettings['hold_led_state'] ?? 'slow') == 'fast') ? 'selected' : '' ?>>Fast</option>
                                <option value="slow" <?= (($defaultSettings['hold_led_state'] ?? 'slow') == 'slow') ? 'selected' : '' ?>>Slow</option>
                                <option value="on" <?= (($defaultSettings['hold_led_state'] ?? 'slow') == 'on') ? 'selected' : '' ?>>On</option>
                                <option value="off" <?= (($defaultSettings['hold_led_state'] ?? 'slow') == 'off') ? 'selected' : '' ?>>Off</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-buttons" style="margin-top: 20px;">
                <button type="submit">Сохранить настройки по умолчанию</button>
            </div>
        </form>
    </div>

    <div  style="margin-top: 20px; text-align: center;">
        <a href="index.php?logout" class="logout-btn">Выйти из админки</a>
    </div>
</div>

<script>
// Clear form after successful submission
if (window.history.replaceState && <?= !empty($message) ? 'true' : 'false' ?>) {
    window.history.replaceState(null, null, window.location.href);
    // Only reset the 'create user' form if a message is shown from that action
    if (document.referrer.includes('admin.php') && document.referrer.includes('action=create')) {
        document.getElementById('createUserForm').reset();
    }
}

document.getElementById('userSelect').addEventListener('change', function() {
    const selectedUser = this.value;
    const deleteBtn = document.getElementById('deleteBtn');
    const passwordFieldContainer = document.getElementById('passwordField');
    const singlePasswordField = document.getElementById('singlePasswordField');
    const adminPasswordFields = document.getElementById('adminPasswordFields');
    const updatePasswordInput = document.getElementById('update_password');
    const adminPasswordInput = document.getElementById('admin_password');
    const confirmAdminPasswordInput = document.getElementById('confirm_admin_password');

    // Отключаем кнопку удаления для admin или пустого выбора
    deleteBtn.disabled = (selectedUser === 'admin' || selectedUser === '');

    // Показываем/скрываем контейнер полей пароля
    passwordFieldContainer.style.display = selectedUser ? 'block' : 'none';

    // Сбрасываем значения полей при смене пользователя
    updatePasswordInput.value = '';
    adminPasswordInput.value = '';
    confirmAdminPasswordInput.value = '';

    // Определяем, какие поля показывать
    if (selectedUser === 'admin') {
        singlePasswordField.style.display = 'none';
        adminPasswordFields.style.display = 'block';
        // Устанавливаем name="password" для поля админа, которое будет отправлено
        adminPasswordInput.name = 'password';
        // Убираем name у других полей, чтобы они не отправлялись
        updatePasswordInput.name = 'unused_update_password';
        confirmAdminPasswordInput.name = 'unused_confirm_admin_password'; // Это поле только для JS валидации
    } else {
        singlePasswordField.style.display = 'block';
        adminPasswordFields.style.display = 'none';
        // Устанавливаем name="password" для обычного поля
        updatePasswordInput.name = 'password';
        // Убираем name у полей админа
        adminPasswordInput.name = 'unused_admin_password';
        confirmAdminPasswordInput.name = 'unused_confirm_admin_password'; // Это поле только для JS валидации
    }
});

document.getElementById('userManagementForm').addEventListener('submit', function(e) {
    const action = e.submitter.value;
    const selectedUser = document.getElementById('userSelect').value;

    if (action === 'update') {
        if (selectedUser === 'admin') {
            const adminPassword = document.getElementById('admin_password').value;
            const confirmAdminPassword = document.getElementById('confirm_admin_password').value; // Это поле для JS валидации

            if (!adminPassword || !confirmAdminPassword) {
                alert('Пожалуйста, заполните оба поля для пароля администратора.');
                e.preventDefault();
                return;
            }

            if (adminPassword !== confirmAdminPassword) {
                alert('Пароли не совпадают. Пожалуйста, проверьте ввод.');
                e.preventDefault();
                return;
            }

            if (!confirm('Вы уверены, что хотите изменить пароль администратора?')) {
                e.preventDefault();
                return;
            }

        } else { // Для обычных пользователей
            const passwordInput = document.getElementById('update_password');
            if (!passwordInput.value) {
                alert('Для изменения пароля введите новый пароль');
                e.preventDefault();
                return;
            }
        }
    }
    // Клиентская валидация для удаления админа (PHP тоже это обрабатывает)
    if (action === 'delete' && selectedUser === 'admin') {
        alert('Невозможно удалить пользователя "admin".');
        e.preventDefault();
    }
});

// Функция для переключения видимости пароля
function togglePasswordVisibility(id) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}

// Добавляем функциональность для кнопки скрытия/показа настроек BLF
document.getElementById('toggleBlfSettings').addEventListener('click', function() {
    const blfSettingsContainer = document.getElementById('blfSettingsContainer');
    if (blfSettingsContainer.style.display === 'none') {
        blfSettingsContainer.style.display = 'block';
    } else {
        blfSettingsContainer.style.display = 'none';
    }
});
</script>
</body>
</html>
