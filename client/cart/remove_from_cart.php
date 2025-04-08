<?php
// client/cart/remove_from_cart.php
// Script pentru eliminarea produselor din coș

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Verificare metodă cerere
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metodă de cerere invalidă.');
}

// Verificare date primite
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    jsonResponse(false, 'ID produs invalid.');
}

$product_id = (int)$_POST['product_id'];

// Verificare existență coș
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Verificare dacă produsul există în coș
if (!isset($_SESSION['cart'][$product_id])) {
    jsonResponse(false, 'Produsul nu există în coșul dvs.');
}

// Eliminare produs din coș
unset($_SESSION['cart'][$product_id]);

// Returnare răspuns de succes
jsonResponse(true, 'Produsul a fost eliminat din coș.', [
    'cartCount' => count($_SESSION['cart'])
]);