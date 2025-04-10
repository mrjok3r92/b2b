<?php
// admin/products/price_delete.php
// Pagina pentru ștergerea unui preț specific pentru un client

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Product.php';
require_once '../../classes/Client.php';

// Verificare ID preț
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID preț invalid.');
    redirect('index.php');
}

$price_id = (int)$_GET['id'];

// Inițializare obiecte
$productObj = new Product();
$clientObj = new Client();

// Obține informațiile despre prețul specific
$specificPrice = $productObj->getClientSpecificPriceById($price_id);

// Verificare existență preț specific
if (!$specificPrice) {
    setFlashMessage('error', 'Prețul specific nu există.');
    redirect('index.php');
}

// Obține produsul
$product_id = $specificPrice['product_id'];
$product = $productObj->getProductById($product_id);

// Verificare existență produs
if (!$product) {
    setFlashMessage('error', 'Produsul nu există.');
    redirect('index.php');
}

// Verificare confirmare
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
$csrf_valid = isset($_GET['csrf_token']) && verifyCSRFToken($_GET['csrf_token']);

// Dacă utilizatorul a confirmat și token-ul CSRF este valid, ștergem prețul specific
if ($confirmed && $csrf_valid) {
    $result = $productObj->deleteClientSpecificPrice($price_id);
    
    if ($result) {
        setFlashMessage('success', 'Prețul specific a fost șters cu succes. Clientul va folosi acum prețul standard al produsului.');
    } else {
        setFlashMessage('error', 'A apărut o eroare la ștergerea prețului specific. Vă rugăm să încercați din nou.');
    }
    
    redirect('view.php?id=' . $product_id);
}

// Obține clientul
$client = $clientObj->getClientById($specificPrice['client_id']);

// Titlu pagină
$pageTitle = 'Ștergere Preț Specific - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="view.php?id=<?php echo $product_id; ?>" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la detalii produs
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Confirmare Ștergere Preț Specific</h1>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-red-50">
        <h2 class="text-lg font-semibold text-red-700">Atenție!</h2>
    </div>
    
    <div class="p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-3xl text-yellow-500 mr-4"></i>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    Ești sigur că vrei să ștergi acest preț specific?
                </h3>
                
                <div class="bg-gray-100 rounded-md p-4 mb-4">
                    <div class="flex flex-col md:flex-row">
                        <div class="md:w-1/2 mb-4 md:mb-0 md:pr-4">
                            <h4 class="font-medium text-gray-700">Produs:</h4>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></p>
                            <p class="text-sm text-gray-500">Cod: <?php echo htmlspecialchars($product['code']); ?></p>
                            <p class="text-sm font-medium text-green-600 mt-1">Preț standard: <?php echo formatAmount($product['price']); ?> Lei</p>
                        </div>
                        
                        <div class="md:w-1/2 md:pl-4 md:border-l md:border-gray-300">
                            <h4 class="font-medium text-gray-700">Client:</h4>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($client['company_name']); ?></p>
                            <p class="text-sm text-gray-500">CUI: <?php echo htmlspecialchars($client['fiscal_code']); ?></p>
                            <p class="text-sm font-medium text-blue-600 mt-1">
                                Preț specific: <?php echo formatAmount($specificPrice['price']); ?> Lei
                                
                                <?php 
                                $diff = $specificPrice['price'] - $product['price'];
                                $diffPercent = ($product['price'] > 0) ? ($diff / $product['price'] * 100) : 0;
                                $isDiscount = $diff < 0;
                                ?>
                                
                                <span class="<?php echo $isDiscount ? 'text-green-600' : 'text-red-600'; ?>">
                                    (<?php echo $isDiscount ? '-' : '+'; ?><?php echo number_format(abs($diffPercent), 2); ?>%)
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <p class="text-gray-600 mb-2">
                    După ștergerea acestui preț specific, clientul <strong><?php echo htmlspecialchars($client['company_name']); ?></strong> 
                    va folosi prețul standard al produsului de <strong><?php echo formatAmount($product['price']); ?> Lei</strong>.
                </p>
                
                <p class="text-gray-600 mb-4">
                    <strong>Notă:</strong> Această acțiune nu poate fi anulată.
                </p>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <a href="view.php?id=<?php echo $product_id; ?>" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Anulează
                    </a>
                    <a href="price_delete.php?id=<?php echo $price_id; ?>&confirm=yes&csrf_token=<?php echo generateCSRFToken(); ?>" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="fas fa-trash mr-1"></i> Șterge prețul specific
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>