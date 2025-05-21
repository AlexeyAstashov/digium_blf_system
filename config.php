<?php
return [
    // Основные настройки подключения
    'db_host' => 'localhost', // или '127.0.0.1', или путь к сокету
    'db_name' => 'blf_system',
    'db_user' => 'freepbxuser',
    'db_pass' => 'Your_Password',
    
    // Альтернативные параметры для разных окружений
    'db_options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];