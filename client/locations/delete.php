<?php
// client/locations/delete.php
// Script pentru ștergerea unei locații

// Inițializare sesiune și autentificare client admin
require_once '../../includes/auth.php';
authenticateClientAdmin();

// Include fișiere necesare
require_once '../../classes/Client.php';
require_once '../../classes/Order.php';

// Verificare ID locație
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID locație invalid.');
    redirect('index.php');
}

$location_id = (int)$_GET['id'];

// Inițializare obiecte
$clientObj = new Client();
$orderObj = new Order();

// Obține informațiile locației
$location = $clientObj->getLocationById($location_id);

// Verificare existență locație și apartenență la client
if (!$location || $location['client_id'] != $_SESSION['client_id']) {
    setFlashMessage('error', 'Locația nu există sau nu vă aparține.');
    redirect('index.php');
}

// Verificare dacă locația este cea a utilizatorului curent
if ($_SESSION['location_id'] == $location_id) {
    setFlashMessage('error', 'Nu puteți șterge locația asociată contului dvs. curent.');
    redirect('index.php');
}

// Verificare dacă este singura locație a clientului
$clientLocations = $clientObj->getClientLocations($_SESSION['client_id']);
if (count($clientLocations) <= 1) {
    setFlashMessage('error', 'Nu puteți șterge ultima locație a companiei dvs.');
    redirect('index.php');
}

// Verificare dacă există comenzi asociate acestei locații
$locationOrders = $orderObj->getLocationOrders($location_id);
if (count($locationOrders) > 0) {
    setFlashMessage('error', 'Nu puteți șterge această locație deoarece există comenzi asociate.');
    redirect('index.php');
}

// Ștergere locație
$result = $clientObj->deleteLocation($location_id);

if ($result) {
    setFlashMessage('success', 'Locația a fost ștearsă cu succes.');
} else {
    setFlashMessage('error', 'A apărut o eroare la ștergerea locației. Vă rugăm să încercați din nou.');
}

redirect('index.php');