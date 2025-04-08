<?php
// index.php
// Pagina principală - redirecționează către zona corectă în funcție de rolul utilizatorului

// Inițializare sesiune
session_start();

require_once 'includes/functions.php';

// Verificare autentificare
if (isLoggedIn()) {
    // Redirecționare în funcție de rol
    if (hasRole(['admin', 'agent'])) {
        redirect('admin/index.php');
    } else {
        redirect('client/index.php');
    }
} else {
    // Utilizator neautentificat, redirecționare către login
    redirect('login.php');
}