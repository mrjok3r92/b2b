<?php
// notifications/mark_read.php
// Script pentru marcarea unei notificări ca citită

// Inițializare sesiune și autentificare
require_once '../includes/auth.php';
authenticateUser();

// Include fișiere necesare
require_once '../classes/Notification.php';

// Verificare ID notificare
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID notificare invalid.');
    redirect('index.php');
}

$notification_id = (int)$_GET['id'];

// Inițializare obiecte
$notificationObj = new Notification();

// Obține notificarea
$notification = $notificationObj->getNotificationById($notification_id);

// Verificare existență notificare
if (!$notification) {
    setFlashMessage('error', 'Notificarea nu există.');
    redirect('index.php');
}

// Verificare permisiuni
if ($notification['user_id'] && $notification['user_id'] != $_SESSION['user_id']) {
    setFlashMessage('error', 'Nu aveți permisiunea să accesați această notificare.');
    redirect('index.php');
}

if ($notification['client_id'] && $notification['client_id'] != $_SESSION['client_id']) {
    setFlashMessage('error', 'Nu aveți permisiunea să accesați această notificare.');
    redirect('index.php');
}

// Marchează notificarea ca citită
$result = $notificationObj->markAsRead($notification_id);

if ($result) {
    setFlashMessage('success', 'Notificarea a fost marcată ca citită.');
} else {
    setFlashMessage('error', 'A apărut o eroare la marcarea notificării. Vă rugăm să încercați din nou.');
}

// Redirect înapoi
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
    redirect($_SERVER['HTTP_REFERER']);
} else {
    redirect('index.php');
}