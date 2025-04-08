<?php
// notifications/mark_all_read.php
// Script pentru marcarea tuturor notificărilor ca citite

// Inițializare sesiune și autentificare
require_once '../includes/auth.php';
authenticateUser();

// Include fișiere necesare
require_once '../classes/Notification.php';

// Inițializare obiecte
$notificationObj = new Notification();

// Marchează toate notificările ca citite
$result = $notificationObj->markAllAsRead($_SESSION['user_id']);

if ($result) {
    setFlashMessage('success', 'Toate notificările au fost marcate ca citite.');
} else {
    setFlashMessage('error', 'A apărut o eroare la marcarea notificărilor. Vă rugăm să încercați din nou.');
}

// Redirect înapoi
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
    redirect($_SERVER['HTTP_REFERER']);
} else {
    redirect('index.php');
}