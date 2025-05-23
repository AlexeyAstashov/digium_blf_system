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

$stmt = $pdo->prepare("SELECT * FROM contacts WHERE extension = ? ORDER BY id ASC");
$stmt->execute([$ext]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Генерация стандартного BLF файла
$xml = new DOMDocument('1.0', 'UTF-8');
$contactsNode = $xml->createElement('contacts');
$contactsNode->setAttribute('group_name', "$ext-BLF");
$contactsNode->setAttribute('editable', "0");
$contactsNode->setAttribute('id', "$ext");

foreach ($contacts as $row) {
    if (stripos($row['first_name'], 'free') !== false) continue;

    $contact_id = $row['contact_id'];
    $first_name = htmlspecialchars($row['first_name'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $last_name = htmlspecialchars($row['last_name'] ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $second_name = htmlspecialchars($row['second_name'] ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $organization = htmlspecialchars($row['organization'] ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $job_title = htmlspecialchars($row['job_title'] ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $location = htmlspecialchars($row['location'] ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $notes = htmlspecialchars($row['notes'] ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');

    $contact = $xml->createElement('contact');
    $contact->setAttribute('id', $contact_id);
    $contact->setAttribute('prefix', "");
    $contact->setAttribute('first_name', $first_name);
    $contact->setAttribute('last_name', $last_name);
    $contact->setAttribute('second_name', $second_name);
    $contact->setAttribute('organization', $organization);
    $contact->setAttribute('job_title', $job_title);
    $contact->setAttribute('location', $location);
    $contact->setAttribute('notes', $notes);
    $contact->setAttribute('contact_type', "sip");
    $contact->setAttribute('account_id', $contact_id);

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

    if ($row['pickupcall']) {
        $pickupAction = $xml->createElement('action');
        $pickupAction->setAttribute('id', 'pickupcall');
        $pickupAction->setAttribute('dial', $contact_id);
        $pickupAction->setAttribute('dial_prefix', '**');
        $pickupAction->setAttribute('label', 'Pickup');
        $pickupAction->setAttribute('name', 'Pickup');
        $actions->appendChild($pickupAction);
    }

    if ($row['myintercom']) {
        $intercomAction = $xml->createElement('action');
        $intercomAction->setAttribute('id', 'myintercom');
        $intercomAction->setAttribute('dial', $contact_id);
        $intercomAction->setAttribute('dial_prefix', '*80');
        $intercomAction->setAttribute('label', 'Intercom');
        $intercomAction->setAttribute('name', 'Intercom');
        $actions->appendChild($intercomAction);
    }

    $actions->appendChild($action);
    $contact->appendChild($actions);
    $contactsNode->appendChild($contact);
}

$xml->appendChild($contactsNode);
$xml->formatOutput = true;
file_put_contents("/var/www/html/digium_phones/{$ext}-BLF.xml", $xml->saveXML());

// Генерация smartBLF.xml
$smartBlfXml = new DOMDocument('1.0', 'UTF-8');
$configNode = $smartBlfXml->createElement('config');
$smartBlf = $smartBlfXml->createElement('smart_blf');
$blfItems = $smartBlfXml->createElement('blf_items');

$mainIndex = 1;
$sideIndex = 0;
$paging = 1;

foreach ($contacts as $row) {
    // Определяем location и index
    if ($mainIndex <= 5) {
        $location = 'main';
        $index = $mainIndex;
        $mainIndex++;
    } else {
        $location = 'side';
        $index = $sideIndex;
        $sideIndex++;
        
        if ($sideIndex > 9) {
            $sideIndex = 0;
            $paging++;
        }
    }
    
    $blfItem = $smartBlfXml->createElement('blf_item');
    $blfItem->setAttribute('location', $location);
    $blfItem->setAttribute('index', $index);
    $blfItem->setAttribute('paging', $paging);
    $blfItem->setAttribute('contact_id', $row['contact_id']);
    
    // Behaviors
    $behaviors = $smartBlfXml->createElement('behaviors');
    
    $behavior1 = $smartBlfXml->createElement('behavior');
    $behavior1->setAttribute('phone_state', 'idle');
    $behavior1->setAttribute('target_status', 'idle');
    $behavior1->setAttribute('press_action', 'primary');
    $behavior1->setAttribute('press_function', 'dial');
    $behaviors->appendChild($behavior1);
    
    if ($row['myintercom']) {
        $behavior2 = $smartBlfXml->createElement('behavior');
        $behavior2->setAttribute('phone_state', 'idle');
        $behavior2->setAttribute('target_status', 'idle');
        $behavior2->setAttribute('long_press_action', 'myintercom');
        $behavior2->setAttribute('long_press_function', 'dial');
        $behaviors->appendChild($behavior2);
    }
    
    if ($row['pickupcall']) {
        $behavior3 = $smartBlfXml->createElement('behavior');
        $behavior3->setAttribute('target_status', 'ringing');
        $behavior3->setAttribute('press_action', 'pickupcall');
        $behavior3->setAttribute('press_function', 'dial');
        $behaviors->appendChild($behavior3);
        
        $behavior4 = $smartBlfXml->createElement('behavior');
        $behavior4->setAttribute('target_status', 'ringing');
        $behavior4->setAttribute('long_press_action', 'pickupcall');
        $behavior4->setAttribute('long_press_function', 'dial');
        $behaviors->appendChild($behavior4);
    }
    
    $blfItem->appendChild($behaviors);
    
    // Indicators
    $indicators = $smartBlfXml->createElement('indicators');
    
    // Idle indicator
    $indicator1 = $smartBlfXml->createElement('indicator');
    $indicator1->setAttribute('target_status', 'idle');
    $indicator1->setAttribute('ring', '0');
    $indicator1->setAttribute('ringtone_id', $row['idle_ringtone']);
    $indicator1->setAttribute('led_color', $row['idle_led_color']);
    $indicator1->setAttribute('led_state', $row['idle_led_state']);
    $indicators->appendChild($indicator1);
    
    // Ringing indicator
    $indicator2 = $smartBlfXml->createElement('indicator');
    $indicator2->setAttribute('target_status', 'ringing');
    $indicator2->setAttribute('ring', '0');
    $indicator2->setAttribute('ringtone_id', $row['ringing_ringtone']);
    $indicator2->setAttribute('led_color', $row['ringing_led_color']);
    $indicator2->setAttribute('led_state', $row['ringing_led_state']);
    $indicators->appendChild($indicator2);
    
    // Busy indicator
    $indicator3 = $smartBlfXml->createElement('indicator');
    $indicator3->setAttribute('target_status', 'on_the_phone');
    $indicator3->setAttribute('ring', '0');
    $indicator3->setAttribute('ringtone_id', $row['busy_ringtone']);
    $indicator3->setAttribute('led_color', $row['busy_led_color']);
    $indicator3->setAttribute('led_state', $row['busy_led_state']);
    $indicators->appendChild($indicator3);
    
    // Hold indicator
    $indicator4 = $smartBlfXml->createElement('indicator');
    $indicator4->setAttribute('target_status', 'on_hold');
    $indicator4->setAttribute('ring', '0');
    $indicator4->setAttribute('ringtone_id', $row['busy_ringtone']);
    $indicator4->setAttribute('led_color', $row['hold_led_color']);
    $indicator4->setAttribute('led_state', $row['hold_led_state']);
    $indicators->appendChild($indicator4);
    
    $blfItem->appendChild($indicators);
    $blfItems->appendChild($blfItem);
}

$smartBlf->appendChild($blfItems);
$configNode->appendChild($smartBlf);
$smartBlfXml->appendChild($configNode);
$smartBlfXml->formatOutput = true;
file_put_contents("/var/www/html/digium_phones/{$ext}-smartBLF.xml", $smartBlfXml->saveXML());

// Обновляем конфигурацию телефона через Asterisk
exec("asterisk -rx 'digium_phones reconfigure phone {$ext}'");

header("Location: editor.php");
exit;
