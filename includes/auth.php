<?php
// includes/auth.php
// Verifică sesiunea și restricționează accesul la paginile protejate

// Inițializare sesiune dacă nu este deja inițializată
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

// Funcție pentru verificarea autentificării
function authenticateUser() {
    // Verifică dacă utilizatorul este autentificat
    if (!isLoggedIn()) {
        // Salvează URL-ul curent pentru redirecționare după autentificare
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        // Redirecționare către pagina de login
        redirect('../login.php');
    }
}

// Funcție pentru restricționarea accesului la zona de administrare
function authenticateAdmin() {
    // Verifică dacă utilizatorul este autentificat
    authenticateUser();
    
    // Verifică dacă utilizatorul are rolul de admin sau agent
    if (!hasRole(['admin', 'agent'])) {
        // Utilizator autentificat, dar fără permisiuni suficiente
        setFlashMessage('error', 'Nu aveți permisiuni pentru a accesa această pagină.');
        
        // Redirecționare către pagina principală a clientului
        redirect('../client/index.php');
    }
}

// Funcție pentru restricționarea accesului la zona de client
function authenticateClient() {
    // Verifică dacă utilizatorul este autentificat
    authenticateUser();
    
    // Verifică dacă utilizatorul are rolul de client_admin sau client_user
    if (!hasRole(['client_admin', 'client_user'])) {
        // Utilizator autentificat, dar fără permisiuni suficiente
        setFlashMessage('error', 'Nu aveți permisiuni pentru a accesa această pagină.');
        
        // Redirecționare către pagina principală de admin
        redirect('../admin/index.php');
    }
}

// Funcție pentru restricționarea accesului doar la adminii clientului
function authenticateClientAdmin() {
    // Verifică dacă utilizatorul este autentificat și este client
    authenticateClient();
    
    // Verifică dacă utilizatorul are rolul de client_admin
    if (!hasRole('client_admin')) {
        // Utilizator client autentificat, dar fără permisiuni de admin
        setFlashMessage('error', 'Această acțiune necesită drepturi de administrator client.');
        
        // Redirecționare către pagina principală a clientului
        redirect('../client/index.php');
    }
}

// Funcție pentru verificarea accesului la datele unui anumit client
function verifyClientAccess($client_id) {
    // Verifică dacă utilizatorul este admin (are acces la toți clienții)
    if (hasRole(['admin', 'agent'])) {
        return true;
    }
    
    // Verifică dacă utilizatorul este client și accesează propriile date
    if (hasRole(['client_admin', 'client_user']) && $_SESSION['client_id'] == $client_id) {
        return true;
    }
    
    // Acces neautorizat
    setFlashMessage('error', 'Nu aveți permisiuni pentru a accesa datele acestui client.');
    
    // Redirecționare în funcție de rolul utilizatorului
    if (hasRole(['client_admin', 'client_user'])) {
        redirect('../client/index.php');
    } else {
        redirect('../admin/index.php');
    }
}

// Funcție pentru verificarea accesului la datele unei anumite locații
function verifyLocationAccess($location_id) {
    // Dacă utilizatorul este admin, are acces la toate locațiile
    if (hasRole(['admin', 'agent'])) {
        return true;
    }
    
    // Pentru utilizatorii client_admin, verificăm dacă locația aparține clientului lor
    if (hasRole('client_admin')) {
        require_once __DIR__ . '/../classes/Client.php';
        $client = new Client();
        $location = $client->getLocationById($location_id);
        
        if ($location && $location['client_id'] == $_SESSION['client_id']) {
            return true;
        }
    }
    
    // Pentru utilizatorii client_user, verificăm dacă este locația lor sau dacă sunt admin client
    if (hasRole('client_user')) {
        // Utilizatorul client_user are acces doar la locația sa sau dacă este admin client
        if ($_SESSION['location_id'] == $location_id) {
            return true;
        }
        
        // Verificăm dacă utilizatorul este asociat cu această locație
        require_once __DIR__ . '/../classes/Client.php';
        $client = new Client();
        $location = $client->getLocationById($location_id);
        
        if ($location && $location['client_id'] == $_SESSION['client_id']) {
            // Utilizatorul poate vedea locațiile clientului său, dar nu poate edita
            return true;
        }
    }
    
    // Acces neautorizat
    setFlashMessage('error', 'Nu aveți permisiuni pentru a accesa datele acestei locații.');
    
    // Redirecționare în funcție de rolul utilizatorului
    if (hasRole(['client_admin', 'client_user'])) {
        redirect('../client/index.php');
    } else {
        redirect('../admin/index.php');
    }
}

// Funcție pentru verificarea accesului la datele unei comenzi
function verifyOrderAccess($order_id) {
    // Dacă utilizatorul este admin, are acces la toate comenzile
    if (hasRole(['admin', 'agent'])) {
        return true;
    }
    
    // Pentru utilizatorii client, verificăm dacă comanda aparține clientului lor
    if (hasRole(['client_admin', 'client_user'])) {
        require_once __DIR__ . '/../classes/Order.php';
        $order = new Order();
        $orderData = $order->getOrderById($order_id);
        
        if ($orderData && $orderData['client_id'] == $_SESSION['client_id']) {
            return true;
        }
    }
    
    // Acces neautorizat
    setFlashMessage('error', 'Nu aveți permisiuni pentru a accesa datele acestei comenzi.');
    
    // Redirecționare în funcție de rolul utilizatorului
    if (hasRole(['client_admin', 'client_user'])) {
        redirect('../client/index.php');
    } else {
        redirect('../admin/index.php');
    }
}

// Funcție pentru verificarea accesului la datele unui aviz
function verifyDeliveryNoteAccess($delivery_note_id) {
    // Dacă utilizatorul este admin, are acces la toate avizele
    if (hasRole(['admin', 'agent'])) {
        return true;
    }
    
    // Pentru utilizatorii client, verificăm dacă avizul aparține clientului lor
    if (hasRole(['client_admin', 'client_user'])) {
        require_once __DIR__ . '/../classes/DeliveryNote.php';
        $deliveryNote = new DeliveryNote();
        $noteData = $deliveryNote->getDeliveryNoteById($delivery_note_id);
        
        if ($noteData && $noteData['client_id'] == $_SESSION['client_id']) {
            return true;
        }
    }
    
    // Acces neautorizat
    setFlashMessage('error', 'Nu aveți permisiuni pentru a accesa datele acestui aviz.');
    
    // Redirecționare în funcție de rolul utilizatorului
    if (hasRole(['client_admin', 'client_user'])) {
        redirect('../client/index.php');
    } else {
        redirect('../admin/index.php');
    }
}