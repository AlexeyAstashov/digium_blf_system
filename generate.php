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

$stmt = $pdo->prepare("SELECT contact_id, first_name FROM contacts WHERE extension = ? ORDER BY id ASC");
$stmt->execute([$ext]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$xml = new DOMDocument('1.0', 'UTF-8');
$contactsNode = $xml->createElement('contacts');
$contactsNode->setAttribute('group_name', "$ext-BLF");
$contactsNode->setAttribute('editable', "1");
$contactsNode->setAttribute('id', "$ext");

foreach ($contacts as $row) {
    if (stripos($row['first_name'], 'free') !== false) continue;

    $contact_id = $row['contact_id'];
    $first_name = htmlspecialchars($row['first_name'], ENT_XML1 | ENT_QUOTES, 'UTF-8');

    $contact = $xml->createElement('contact');
    $contact->setAttribute('id', $contact_id);
    $contact->setAttribute('prefix', "");
    $contact->setAttribute('first_name', $first_name);
    $contact->setAttribute('second_name', "");
    $contact->setAttribute('last_name', "");
    $contact->setAttribute('suffix', "");
    $contact->setAttribute('organization', "");
    $contact->setAttribute('job_title', "");
    $contact->setAttribute('location', "");
    $contact->setAttribute('notes', "");
    $contact->setAttribute('contact_type', "sip");
    $contact->setAttribute('account_id', $contact_id);

    // Условие: если длина номера ≤ 4 символов, добавляем BLF (subscribe_to)
    if (strlen(preg_replace('/\D/', '', $contact_id)) <= 4) {
        $contact->setAttribute('subscribe_to', $contact_id);
    }

    $actions = $xml->createElement('actions');
    $action = $xml->createElement('action');
    $action->setAttribute('id', 'primary');
    $action->setAttribute('dial', $contact_id);
    $action->setAttribute('label', 'CL_ACTN_SIP');
    $action->setAttribute('name', 'CN_ACTN_DIAL');
    $action->setAttribute('transfer_name', 'CN_ACTN_TRANSFER');

    $actions->appendChild($action);
    $contact->appendChild($actions);
    $contactsNode->appendChild($contact);
}

$xml->appendChild($contactsNode);
$xml->formatOutput = true;
file_put_contents("/var/www/html/digium_phones/{$ext}-BLF.xml", $xml->saveXML());

exec("asterisk -rx 'digium_phones reconfigure phone {$ext}'");

header("Location: editor.php");
exit;
