<?php
// client/locations/get_location_info.php
// Script pentru obținerea informațiilor despre locație

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/Client.php';

// Verificare metodă cerere
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Metodă de cerere invalidă.');
}

// Verificare date primite
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    jsonResponse(false, 'ID locație invalid.');
}

$location_id = (int)$_GET['id'];

// Inițializare obiecte
$clientObj = new Client();

// Verificare existență locație
$location = $clientObj->getLocationById($location_id);
if (!$location) {
    jsonResponse(false, 'Locația nu există.');
}

// Verificare dacă locația aparține clientului
if ($location['client_id'] != $_SESSION['client_id']) {
    jsonResponse(false, 'Nu aveți acces la această locație.');
}

// Returnare informații locație
jsonResponse(true, 'Informații locație obținute cu succes.', [
    'location' => [
        'id' => $location['id'],
        'name' => $location['name'],
        'address' => $location['address'],
        'contact_person' => $location['contact_person'],
        'phone' => $location['phone'],
        'email' => $location['email']
    ]
]);