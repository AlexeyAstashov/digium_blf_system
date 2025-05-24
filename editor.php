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

// Загрузка настроек по умолчанию
$defaultSettings = [];
try {
    $stmt = $pdo->query("SELECT * FROM blf_default_settings WHERE id = 1");
    $defaultSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$defaultSettings) {
        $defaultSettings = [
            'pickupcall' => 1,
            'myintercom' => 1,
            'idle_led_color' => 'green',
            'idle_led_state' => 'on',
            'idle_ringtone' => 'Digium',
            'ringing_led_color' => 'red',
            'ringing_led_state' => 'fast',
            'ringing_ringtone' => 'Techno',
            'busy_led_color' => 'red',
            'busy_led_state' => 'on',
            'busy_ringtone' => 'Techno',
            'hold_led_color' => 'amber',
            'hold_led_state' => 'slow',
            'hold_ringtone' => 'Techno'
        ];
    }
} catch (PDOException $e) {
    $defaultSettings = [
        'pickupcall' => 1,
        'myintercom' => 1,
        'idle_led_color' => 'green',
        'idle_led_state' => 'on',
        'idle_ringtone' => 'Digium',
        'ringing_led_color' => 'red',
        'ringing_led_state' => 'fast',
        'ringing_ringtone' => 'Techno',
        'busy_led_color' => 'red',
        'busy_led_state' => 'on',
        'busy_ringtone' => 'Techno',
        'hold_led_color' => 'amber',
        'hold_led_state' => 'slow',
        'hold_ringtone' => 'Techno'
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit;
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE extension = ? AND contact_id = ?");
        $stmt->execute([$ext, $_POST['delete']]);
        $_SESSION['message'] = "Контакт удален";
        header("Location: editor.php");
        exit;
    } elseif (isset($_POST['edit'])) {
        $_SESSION['edit_id'] = $_POST['edit'];
        header("Location: editor.php");
        exit;
    } elseif (isset($_POST['update_settings'], $_POST['contact_id'])) {
        $stmt = $pdo->prepare("UPDATE contacts SET 
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
            hold_led_state = ?,
            hold_ringtone = ?
            WHERE extension = ? AND contact_id = ?");
        
        $stmt->execute([
            isset($_POST['pickupcall']) ? 1 : 0,
            isset($_POST['myintercom']) ? 1 : 0,
            $_POST['idle_led_color'] ?? 'green',
            $_POST['idle_led_state'] ?? 'on',
            $_POST['idle_ringtone'] ?? 'Digium',
            $_POST['ringing_led_color'] ?? 'red',
            $_POST['ringing_led_state'] ?? 'fast',
            $_POST['ringing_ringtone'] ?? 'Techno',
            $_POST['busy_led_color'] ?? 'red',
            $_POST['busy_led_state'] ?? 'on',
            $_POST['busy_ringtone'] ?? 'Techno',
            $_POST['hold_led_color'] ?? 'amber',
            $_POST['hold_led_state'] ?? 'slow',
            $_POST['hold_ringtone'] ?? 'Techno',
            $ext,
            $_POST['contact_id']
        ]);
        
        $_SESSION['message'] = "Настройки SmartBLF сохранены";
        header("Location: editor.php");
        exit;
    } elseif (isset($_POST['update_contact'], $_POST['original_contact_id'], $_POST['contact_id'], $_POST['first_name'])) {
        if (!preg_match('/^\+?\d+$/', $_POST['contact_id'])) {
            $_SESSION['error'] = "Номер должен содержать только цифры и может начинаться с +";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE extension = ? AND contact_id = ? AND contact_id != ?");
            $stmt->execute([$ext, $_POST['contact_id'], $_POST['original_contact_id']]);
            $exists = $stmt->fetchColumn();
            
            if ($exists == 0) {
                $stmt = $pdo->prepare("UPDATE contacts SET 
                    contact_id = ?, 
                    first_name = ?,
                    last_name = ?,
                    second_name = ?,
                    organization = ?,
                    job_title = ?,
                    location = ?,
                    notes = ?
                    WHERE extension = ? AND contact_id = ?");
                
                $stmt->execute([
                    $_POST['contact_id'],
                    $_POST['first_name'],
                    $_POST['last_name'] ?? null,
                    $_POST['second_name'] ?? null,
                    $_POST['organization'] ?? null,
                    $_POST['job_title'] ?? null,
                    $_POST['location'] ?? null,
                    $_POST['notes'] ?? null,
                    $ext,
                    $_POST['original_contact_id']
                ]);
                
                unset($_SESSION['edit_id']);
                $_SESSION['message'] = "Контакт обновлен";
            } else {
                $_SESSION['error'] = "Контакт с таким номером уже существует";
            }
        }
        header("Location: editor.php");
        exit;
    } elseif (isset($_POST['add_contact'], $_POST['contact_id'], $_POST['first_name'])) {
        if (!preg_match('/^\+?\d+$/', $_POST['contact_id'])) {
            $_SESSION['error'] = "Номер должен содержать только цифры и может начинаться с +";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE extension = ? AND contact_id = ?");
            $stmt->execute([$ext, $_POST['contact_id']]);
            $exists = $stmt->fetchColumn();
            
            if ($exists == 0) {
                $stmt = $pdo->prepare("INSERT INTO contacts (
                    extension, contact_id, first_name, last_name, second_name, 
                    organization, job_title, location, notes,
                    pickupcall, myintercom, idle_led_color, idle_led_state, idle_ringtone,
                    ringing_led_color, ringing_led_state, ringing_ringtone,
                    busy_led_color, busy_led_state, busy_ringtone,
                    hold_led_color, hold_led_state, hold_ringtone
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $ext,
                    $_POST['contact_id'],
                    $_POST['first_name'],
                    $_POST['last_name'] ?? null,
                    $_POST['second_name'] ?? null,
                    $_POST['organization'] ?? null,
                    $_POST['job_title'] ?? null,
                    $_POST['location'] ?? null,
                    $_POST['notes'] ?? null,
                    $defaultSettings['pickupcall'],
                    $defaultSettings['myintercom'],
                    $defaultSettings['idle_led_color'],
                    $defaultSettings['idle_led_state'],
                    $defaultSettings['idle_ringtone'],
                    $defaultSettings['ringing_led_color'],
                    $defaultSettings['ringing_led_state'],
                    $defaultSettings['ringing_ringtone'],
                    $defaultSettings['busy_led_color'],
                    $defaultSettings['busy_led_state'],
                    $defaultSettings['busy_ringtone'],
                    $defaultSettings['hold_led_color'],
                    $defaultSettings['hold_led_state'],
                    $defaultSettings['hold_ringtone']
                ]);
                
                $_SESSION['message'] = "Контакт добавлен";
            } else {
                $_SESSION['error'] = "Контакт с таким номером уже существует";
            }
        }
        header("Location: editor.php");
        exit;
    }
}

if (isset($_GET['cancel_edit'])) {
    unset($_SESSION['edit_id']);
    header("Location: editor.php");
    exit;
}

$contacts = $pdo->prepare("SELECT * FROM contacts WHERE extension = ? ORDER BY id ASC");
$contacts->execute([$ext]);
$contacts = $contacts->fetchAll(PDO::FETCH_ASSOC);

$ringtones = ['Alarm', 'Chimes', 'Digium', 'GuitarStrum', 'Jingle', 'Office2', 'Office', 'RotaryPhone', 'SteelDrum', 'Techno', 'Theme', 'Tweedle', 'Twinkle', 'Vibe'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование BLF</title>
    <link rel="stylesheet" href="editor.css">
    <style>
    </style>
    <script>
    function validateNumber(input) {
        let value = input.value;
        let plusIndex = value.indexOf('+');

        if (plusIndex !== -1) {
            value = '+' + value.replace(/\+/g, '').replace(/\D/g, '');
        } else {
            value = value.replace(/\D/g, '');
        }
        input.value = value;
    }

    function toggleSettings(rowId) {
        const settings = document.getElementById('settings-' + rowId);
        settings.classList.toggle('hidden');
    }

    function cancelEdit() {
        window.location.href = 'editor.php?cancel_edit=1';
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
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="success-message"><?= htmlspecialchars($_SESSION['message']) ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <div class="button-container">
        <form method="post">
            <button type="submit" name="logout" class="logout-btn">Выход</button>
        </form>
    </div>

    <table>
        <thead>
            <tr><th>№</th><th>Номер</th><th>Имя</th><th>Доп. информация</th><th>Действия</th></tr>
        </thead>
        <tbody>
            <?php foreach ($contacts as $index => $row): ?>
                <?php if (isset($_SESSION['edit_id']) && $_SESSION['edit_id'] == $row['contact_id']): ?>
                    <tr class="editing-row">
                        <td><?= $index + 1 ?></td>
                        <form method="post">
                            <input type="hidden" name="original_contact_id" value="<?= htmlspecialchars($row['contact_id']) ?>">
                            <input type="hidden" name="update_contact" value="1">
                            <td><input type="text" name="contact_id" value="<?= htmlspecialchars($row['contact_id']) ?>" 
                                     required oninput="validateNumber(this)"></td>
                            <td>
                                <div class="name-fields">
                                    <input type="text" name="first_name" value="<?= htmlspecialchars($row['first_name']) ?>" required placeholder="First Name">
                                    <input type="text" name="last_name" value="<?= htmlspecialchars($row['last_name']) ?>" placeholder="Last Name">
                                    <input type="text" name="second_name" value="<?= htmlspecialchars($row['second_name']) ?>" placeholder="Second Name">
                                </div>
                            </td>
                            <td>
                                <input type="text" name="organization" value="<?= htmlspecialchars($row['organization']) ?>" placeholder="Organization">
                                <input type="text" name="job_title" value="<?= htmlspecialchars($row['job_title']) ?>" placeholder="Job Title">
                                <input type="text" name="location" value="<?= htmlspecialchars($row['location']) ?>" placeholder="Location">
                                <textarea name="notes" placeholder="Notes"><?= htmlspecialchars($row['notes']) ?></textarea>
                            </td>
                            <td class="contact-actions">
                                <button type="submit" class="save-btn">Сохранить</button>
                                <button type="button" class="cancel-btn" onclick="cancelEdit()">Отмена</button>
                                <button type="button" onclick="toggleSettings('<?= $row['id'] ?>')" class="settings-btn">SmartBLF</button>
                            </td>
                        </form>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($row['contact_id']) ?></td>
                        <td>
                            <?= htmlspecialchars($row['first_name']) ?>
                            <?= !empty($row['last_name']) ? '<br>' . htmlspecialchars($row['last_name']) : '' ?>
                            <?= !empty($row['second_name']) ? '<br>' . htmlspecialchars($row['second_name']) : '' ?>
                        </td>
                        <td>
                            <?= !empty($row['organization']) ? htmlspecialchars($row['organization']) : '' ?>
                            <?= !empty($row['job_title']) ? ' ('.htmlspecialchars($row['job_title']).')' : '' ?>
                            <?= !empty($row['location']) ? ' ['.htmlspecialchars($row['location']).']' : '' ?>
                            <?= !empty($row['notes']) ? '<br><small>'.htmlspecialchars($row['notes']).'</small>' : '' ?>
                        </td>

			<td>
			    <div class="contact-actions">
			        <form method="post">
			            <button name="edit" value="<?= $row['contact_id'] ?>" class="edit-btn">Изменить</button>
			        </form>
			        <form method="post">
			            <button name="delete" value="<?= $row['contact_id'] ?>" class="delete-btn" onclick="return confirm('Вы уверены?')">Удалить</button>
			        </form>
			            <button type="button" onclick="toggleSettings('<?= $row['id'] ?>')" class="settings-btn">SmartBLF</button>
			    </div>
			</td>
                <?php endif; ?>
                
                <tr id="settings-<?= $row['id'] ?>" class="hidden">
                    <td colspan="5">
                        <div class="smartblf-settings">
                            <h3>SmartBLF Settings for <?= htmlspecialchars($row['contact_id']) ?></h3>
                            <form method="post">
                                <input type="hidden" name="update_settings" value="1">
                                <input type="hidden" name="contact_id" value="<?= $row['contact_id'] ?>">
                                <div class="settings-grid">
                                    <div class="setting-group">
                                        <h4>Applications</h4>
                                        <label><input type="checkbox" name="pickupcall" value="1" <?= $row['pickupcall'] ? 'checked' : '' ?>> Pickup Call (**)</label>
                                        <label><input type="checkbox" name="myintercom" value="1" <?= $row['myintercom'] ? 'checked' : '' ?>> My Intercom (*80)</label>
                                    </div>
                                    
                                    <div class="setting-group">
                                        <h4>Idle State</h4>
                                        <label>LED Color:
                                            <select name="idle_led_color">
                                                <option value="green" <?= $row['idle_led_color'] == 'green' ? 'selected' : '' ?>>Green</option>
                                                <option value="amber" <?= $row['idle_led_color'] == 'amber' ? 'selected' : '' ?>>Amber</option>
                                                <option value="red" <?= $row['idle_led_color'] == 'red' ? 'selected' : '' ?>>Red</option>
                                            </select>
                                        </label>
                                        <label>LED State:
                                            <select name="idle_led_state">
                                                <option value="on" <?= $row['idle_led_state'] == 'on' ? 'selected' : '' ?>>On</option>
                                                <option value="off" <?= $row['idle_led_state'] == 'off' ? 'selected' : '' ?>>Off</option>
                                            </select>
                                        </label>
                                        <label>Ringtone:
                                            <select name="idle_ringtone">
                                                <?php foreach ($ringtones as $rt): ?>
                                                    <option value="<?= $rt ?>" <?= $row['idle_ringtone'] == $rt ? 'selected' : '' ?>><?= $rt ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>
                                    
                                    <div class="setting-group">
                                        <h4>Ringing State</h4>
                                        <label>LED Color:
                                            <select name="ringing_led_color">
                                                <option value="green" <?= $row['ringing_led_color'] == 'green' ? 'selected' : '' ?>>Green</option>
                                                <option value="amber" <?= $row['ringing_led_color'] == 'amber' ? 'selected' : '' ?>>Amber</option>
                                                <option value="red" <?= $row['ringing_led_color'] == 'red' ? 'selected' : '' ?>>Red</option>
                                            </select>
                                        </label>
                                        <label>LED State:
                                            <select name="ringing_led_state">
                                                <option value="fast" <?= $row['ringing_led_state'] == 'fast' ? 'selected' : '' ?>>Fast</option>
                                                <option value="slow" <?= $row['ringing_led_state'] == 'slow' ? 'selected' : '' ?>>Slow</option>
                                                <option value="on" <?= $row['ringing_led_state'] == 'on' ? 'selected' : '' ?>>On</option>
                                                <option value="off" <?= $row['ringing_led_state'] == 'off' ? 'selected' : '' ?>>Off</option>
                                            </select>
                                        </label>
                                        <label>Ringtone:
                                            <select name="ringing_ringtone">
                                                <?php foreach ($ringtones as $rt): ?>
                                                    <option value="<?= $rt ?>" <?= $row['ringing_ringtone'] == $rt ? 'selected' : '' ?>><?= $rt ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>
                                    
                                    <div class="setting-group">
                                        <h4>Busy State</h4>
                                        <label>LED Color:
                                            <select name="busy_led_color">
                                                <option value="green" <?= $row['busy_led_color'] == 'green' ? 'selected' : '' ?>>Green</option>
                                                <option value="amber" <?= $row['busy_led_color'] == 'amber' ? 'selected' : '' ?>>Amber</option>
                                                <option value="red" <?= $row['busy_led_color'] == 'red' ? 'selected' : '' ?>>Red</option>
                                            </select>
                                        </label>
                                        <label>LED State:
                                            <select name="busy_led_state">
                                                <option value="on" <?= $row['busy_led_state'] == 'on' ? 'selected' : '' ?>>On</option>
                                                <option value="off" <?= $row['busy_led_state'] == 'off' ? 'selected' : '' ?>>Off</option>
                                            </select>
                                        </label>
                                        <label>Ringtone:
                                            <select name="busy_ringtone">
                                                <?php foreach ($ringtones as $rt): ?>
                                                    <option value="<?= $rt ?>" <?= $row['busy_ringtone'] == $rt ? 'selected' : '' ?>><?= $rt ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>
                                    
                                    <div class="setting-group">
                                        <h4>Hold State</h4>
                                        <label>LED Color:
                                            <select name="hold_led_color">
                                                <option value="green" <?= $row['hold_led_color'] == 'green' ? 'selected' : '' ?>>Green</option>
                                                <option value="amber" <?= $row['hold_led_color'] == 'amber' ? 'selected' : '' ?>>Amber</option>
                                                <option value="red" <?= $row['hold_led_color'] == 'red' ? 'selected' : '' ?>>Red</option>
                                            </select>
                                        </label>
                                        <label>LED State:
                                            <select name="hold_led_state">
                                                <option value="slow" <?= $row['hold_led_state'] == 'slow' ? 'selected' : '' ?>>Slow</option>
                                                <option value="fast" <?= $row['hold_led_state'] == 'fast' ? 'selected' : '' ?>>Fast</option>
                                                <option value="on" <?= $row['hold_led_state'] == 'on' ? 'selected' : '' ?>>On</option>
                                                <option value="off" <?= $row['hold_led_state'] == 'off' ? 'selected' : '' ?>>Off</option>
                                            </select>
                                        </label>
                                        <label>Ringtone:
                                            <select name="hold_ringtone">
                                                <?php foreach ($ringtones as $rt): ?>
                                                    <option value="<?= $rt ?>" <?= ($row['hold_ringtone'] ?? 'Techno') == $rt ? 'selected' : '' ?>><?= $rt ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>
                                </div>
                                <div style="text-align: right; margin-top: 15px;">
                                    <button type="submit" class="save-btn">Сохранить настройки</button>
                                    <button type="button" onclick="toggleSettings('<?= $row['id'] ?>')" class="cancel-btn">Закрыть</button>
                                </div>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <tr>
                <form method="post">
                    <td>#</td>
                    <td><input type="text" name="contact_id" required oninput="validateNumber(this)"></td>
                    <td>
                        <div class="name-fields">
                            <input type="text" name="first_name" required placeholder="First Name">
                            <input type="text" name="last_name" placeholder="Last Name">
                            <input type="text" name="second_name" placeholder="Second Name">
                        </div>
                    </td>
                    <td>
                        <input type="text" name="organization" placeholder="Organization">
                        <input type="text" name="job_title" placeholder="Job Title">
                        <input type="text" name="location" placeholder="Location">
                        <textarea name="notes" placeholder="Notes"></textarea>
                    </td>
                    <td><button type="submit" name="add_contact" class="add-btn">Добавить</button></td>
                </form>
            </tr>
        </tbody>
    </table>

    <form method="post" action="generate.php">
        <button type="submit" class="update-btn">Обновить BLF</button>
    </form>
</div>
</body>
</html>
