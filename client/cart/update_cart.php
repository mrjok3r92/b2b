<?php
// client/cart/update_cart.php
// Script pentru actualizarea cantității produselor în coș

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/Product.php';

// Verificare metodă cerere
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metodă de cerere invalidă.');
}

// Verificare date primite
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    jsonResponse(false, 'ID produs invalid.');
}

if (!isset($_POST['quantity']) || !is_numeric($_POST['quantity'])) {
    jsonResponse(false, 'Cantitate invalidă.');
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];

// Validare cantitate
if ($quantity < 1) {
    jsonResponse(false, 'Cantitatea trebuie să fie cel puțin 1.');
}

// Inițializare obiecte
$productObj = new Product();

// Verificare existență produs
$product = $productObj->getProductById($product_id);
if (!$product || $product['status'] !== 'active') {
    jsonResponse(false, 'Produsul nu există sau nu este disponibil.');
}

// Verificare existență coș
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Verificare dacă produsul există în coș
if (!isset($_SESSION['cart'][$product_id])) {
    jsonResponse(false, 'Produsul nu există în coșul dvs.');
}

// Actualizare cantitate
$_SESSION['cart'][$product_id] = $quantity;

// Calculare total nou
$total = 0;
foreach ($_SESSION['cart'] as $pid => $qty) {
    $price = $productObj->getClientPrice($_SESSION['client_id'], $pid);
    $total += $price * $qty;
}

// Returnare răspuns de succes
jsonResponse(true, 'Cantitatea a fost actualizată.', [
    'cartCount' => count($_SESSION['cart']),
    'product' => [
        'id' => $product['id'],
        'name' => $product['name'],
        'quantity' => $quantity
    ],
    'total' => $total
]);