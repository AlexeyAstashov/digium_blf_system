<?php
// Подключение к базе данных
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

// Пароль для администратора
$password = '1234'; // Установите здесь желаемый пароль
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Проверяем существование пользователя admin
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE extension = 'admin'");
$stmt->execute();
$adminExists = $stmt->fetchColumn();

if ($adminExists) {
    echo "Пользователь 'admin' уже существует в базе данных.\n";
    echo "Текущий хеш пароля: " . $hashedPassword . "\n\n";
    echo "Чтобы обновить пароль, выполните в MySQL:\n";
    echo "----------------------------------------\n";
    echo "UPDATE users SET password = '" . $hashedPassword . "' WHERE extension = 'admin';\n";
    echo "----------------------------------------\n";
} else {
    // Создаём пользователя admin
    try {
        $stmt = $pdo->prepare("INSERT INTO users (extension, password) VALUES ('admin', ?)");
        $stmt->execute([$hashedPassword]);
        
        echo "Пользователь 'admin' успешно создан.\n";
        echo "Хеш пароля: " . $hashedPassword . "\n";
        echo "Пароль для входа: " . $password . "\n";
    } catch (PDOException $e) {
        echo "Ошибка при создании пользователя: " . $e->getMessage() . "\n";
    }
}

// Проверяем структуру таблицы users
echo "\nПроверка структуры таблицы users:\n";
$stmt = $pdo->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $column) {
    echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    
    // Проверяем размер поля password
    if ($column['Field'] == 'password' && strpos($column['Type'], 'varchar(255)') === false) {
        echo "\nВНИМАНИЕ: Поле password должно быть VARCHAR(255) для хранения хешей.\n";
        echo "Выполните в MySQL:\n";
        echo "ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL;\n";
    }
}
