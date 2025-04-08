<?php
// client/cart/add_to_cart.php
// Script pentru adăugarea produselor în coș

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

$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

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

// Inițializare coș în sesiune dacă nu există
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Verificare dacă produsul există deja în coș
if (isset($_SESSION['cart'][$product_id])) {
    // Actualizare cantitate
    $_SESSION['cart'][$product_id] += $quantity;
    $message = 'Cantitatea produsului a fost actualizată în coș.';
} else {
    // Adăugare produs nou
    $_SESSION['cart'][$product_id] = $quantity;
    $message = 'Produsul a fost adăugat în coș.';
}

// Returnare răspuns de succes
jsonResponse(true, $message, [
    'cartCount' => count($_SESSION['cart']),
    'product' => [
        'id' => $product['id'],
        'name' => $product['name'],
        'quantity' => $_SESSION['cart'][$product_id]
    ]
]);