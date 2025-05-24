
# Digium BLF System

Веб-интерфейс для редактирования персональной адресной книги BLF (Busy Lamp Field) для телефонов **Digium**. Поддерживает авторизацию по внутреннему номеру и паролю, управление до **105 контактов** на пользователя и автоматическую генерацию XML-файла для телефонов.


![Скриншот редактирования](https://github.com/user-attachments/assets/4f6326d7-691d-4f74-89ca-b38058b50a6e)
![Скриншот редактирования](https://github.com/user-attachments/assets/1e68d1f4-320b-4fb1-89f5-0b9e050f35a7)
![Скриншот редактирования SmartBLF](https://github.com/user-attachments/assets/cc06a77b-4c6f-493e-a5df-2d8ab747a552)

## 🚀 Возможности

- 🔐 Авторизация по номеру и паролю
- 📇 Управление списком BLF-контактов
- ✅ Проверка на уникальность и валидность номеров
- 🖨 Автоматическая генерация XML-файла для Digium-телефонов
- 🔄 Мгновенная перезагрузка конфигурации телефонов через Asterisk
- 👤 Админка с управлением пользователями

![скриншот панели администратора](https://github.com/user-attachments/assets/ec1e08b8-8a1d-49c4-bc27-e06b9ccbfbaa)

## 📁 Структура проекта

```bash
digium_blf_system/
├── index.php           # Авторизация
├── editor.php          # Интерфейс редактирования контактов для BFL 
├── editor.css          # Стилизация интерфейса
├── generate.php        # Генерация XML
├── admin.php           # Панель администратора
├── config.php          # Конфигурация подключения к БД
├── style.css           # Стилизация интерфейса
├── create_hash.php     # Первичная генерация пароля для польщователя admin в БД
├── logout.php          
└── ...
```
---
🛠️ Админка
Управление пользователями:

Создание, удаление и смена пароля

Встроенная защита от удаления пользователя **admin**


⚙️ **Установка**
Клонируйте репозиторий:
```
git clone https://github.com/AlexeyAstashov/digium_blf_system.git
```

Настройте подключение к базе данных в **config.php**:
```
return [
    'db_host' => 'localhost',
    'db_name' => 'blf_system',
    'db_user' => 'user',
    'db_pass' => 'password'
];
```
Импортируйте структуру базы данных:
```
mysql -u root -p blf_system < blf_structure.sql
```
Файл **blf_structure.sql** содержит только структуру таблиц.

Дайте права на запись веб-серверу:
```
chown -R asterisk:asterisk /var/www/html/digium_phones
chmod -R 755 /var/www/html/digium_phones
```

## 🔄 Принцип работы

Пользователь входит с внутренним номером и паролем.

Интерфейс **editor.php** позволяет редактировать до 105 BLF-контактов.

При сохранении создаётся XML-файл <ext>.xml.

Asterisk отправляет команду телефонам перезагрузить конфигурацию.

## 🔐 Безопасность
Хеширование паролей (bcrypt)

Проверка номера и пароля при входе

Ограничение удаления пользователя admin

**index.php?logout** — для выхода из сессии

## 📌 Зависимости
**PHP 5.6+**

**MariaDB / MySQL**

Asterisk + Digium телефоны (поддержка XML BLF)

Apache с доступом на /var/www/html/digium_phones/

## 📃 Лицензия
Лицензия: [GNU GPL v3](LICENSE)

-------------------------------------------------------------------------------

# 📞 Digium BLF System

A web interface for managing personal **BLF (Busy Lamp Field)** contact lists for **Digium phones**. Supports authentication by extension and password, up to **105 BLF contacts per user**, and automatic XML generation for phone configuration.

## 🚀 Features

- 🔐 Login by extension and password
- 📇 Manage up to 105 BLF contacts per user
- ✅ Validation and duplicate checks for extensions
- 🖨 Automatic XML file generation for Digium phones
- 🔄 Instant configuration reload via Asterisk
- 👤 Admin panel with user management

---
## 📁 Project Structure
```
digium_blf_system/
├── index.php # Login page
├── editor.php # BLF contact editor
├── editor.css # Editor styling
├── generate.php # XML generation logic
├── admin.php # Admin panel
├── config.php # DB connection settings
├── style.css # Common styles
├── create_hash.php # Initial admin password hash generator
├── logout.php # Logout handler
└── ...
```
---

## 🛠️ Admin Panel

- Create, delete, and change passwords for users
- Admin user (`admin`) is protected from deletion
- Simple and intuitive interface

---

## ⚙️ Installation

1. Clone the repository:

```
git clone https://github.com/AlexeyAstashov/digium_blf_system.git
```
Configure database connection in ***config.php***:

```
return [
    'db_host' => 'localhost',
    'db_name' => 'blf_system',
    'db_user' => 'user',
    'db_pass' => 'password'
];
```

Import database structure:

```
mysql -u root -p blf_system < blf_structure.sql
```
The blf_structure.sql file contains only the database schema.

Set correct file permissions:

```
chown -R asterisk:asterisk /var/www/html/digium_phones
chmod -R 755 /var/www/html/digium_phones
```

🔄 How It Works
User logs in with extension and password.

***editor.php*** interface allows editing up to 105 BLF contacts.

Saving the form generates an XML file: <ext>.xml.

Asterisk triggers phones to reload the updated configuration.

## 🔐 Security
Passwords are hashed with bcrypt

Input validation and access protection

**admin** user cannot be deleted

Supports session logout via **index.php?logout**

## 📌 Requirements
**PHP 5.6+**

**MariaDB / MySQL**

Asterisk with Digium phones (XML BLF support)

Apache or Nginx with access to /var/www/html/digium_phones/


## 📃 License
License: [GNU GPL v3](LICENSE)

## Photo

<img src="https://github.com/user-attachments/assets/966772b1-ce39-4580-9a66-5cea8f65ec41" width="25%">
<img src="https://github.com/user-attachments/assets/dad2fa73-19d9-4e63-b11a-7f0551cace4c" width="25%">
