<?php
// client/cart/checkout.php
// Pagina pentru finalizarea comenzii (checkout)

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/Product.php';
require_once '../../classes/Client.php';
require_once '../../classes/Order.php';

// Verificare metodă cerere
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Metodă de cerere invalidă.');
    redirect('index.php');
}

// Verificare existență coș
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    setFlashMessage('error', 'Coșul dvs. este gol.');
    redirect('index.php');
}

// Verificare date primite
if (!isset($_POST['location_id']) || !is_numeric($_POST['location_id'])) {
    setFlashMessage('error', 'Locație invalidă.');
    redirect('index.php');
}

if (!isset($_POST['total_amount']) || !is_numeric($_POST['total_amount'])) {
    setFlashMessage('error', 'Total comandă invalid.');
    redirect('index.php');
}

if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
    setFlashMessage('error', 'Trebuie să acceptați termenii și condițiile.');
    redirect('index.php');
}

$location_id = (int)$_POST['location_id'];
$total_amount = (float)$_POST['total_amount'];
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Inițializare obiecte
$productObj = new Product();
$clientObj = new Client();
$orderObj = new Order();

// Verificare existență locație
$location = $clientObj->getLocationById($location_id);
if (!$location || $location['client_id'] != $_SESSION['client_id']) {
    setFlashMessage('error', 'Locația selectată nu vă aparține.');
    redirect('index.php');
}

// Pregătire produse pentru comandă
$orderProducts = [];
$calculatedTotal = 0;

foreach ($_SESSION['cart'] as $productId => $quantity) {
    $product = $productObj->getProductById($productId);
    
    if ($product && $product['status'] == 'active') {
        $price = $productObj->getClientPrice($_SESSION['client_id'], $productId);
        $amount = $price * $quantity;
        
        $orderProducts[] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $price
        ];
        
        $calculatedTotal += $amount;
    }
}

// Verificare dacă suma calculată corespunde cu cea din formular
if (abs($calculatedTotal - $total_amount) > 0.01) {
    setFlashMessage('error', 'Suma totală nu corespunde cu produsele din coș. Vă rugăm să reîncărcați pagina.');
    redirect('index.php');
}

// Pregătire date comandă
$orderData = [
    'client_id' => $_SESSION['client_id'],
    'location_id' => $location_id,
    'user_id' => $_SESSION['user_id'],
    'notes' => $notes,
    'total_amount' => $calculatedTotal,
    'products' => $orderProducts
];

// Plasare comandă
$order_id = $orderObj->addOrder($orderData);

if ($order_id) {
    // Comandă plasată cu succes
    // Golire coș
    $_SESSION['cart'] = [];
    
    // Redirecționare către pagina de confirmare
    setFlashMessage('success', 'Comanda a fost plasată cu succes.');
    redirect('../orders/view.php?id=' . $order_id);
} else {
    // Eroare la plasarea comenzii
    setFlashMessage('error', 'A apărut o eroare la plasarea comenzii. Vă rugăm să încercați din nou.');
    redirect('index.php');
}