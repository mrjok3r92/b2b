<?php
// client/cart/clear_cart.php
// Script pentru golirea coșului de cumpărături

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Verificare metodă cerere
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metodă de cerere invalidă.');
}

// Golire coș
$_SESSION['cart'] = [];

// Returnare răspuns de succes
jsonResponse(true, 'Coșul a fost golit cu succes.', [
    'cartCount' => count($_SESSION['cart'])
]);