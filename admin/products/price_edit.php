<?php
// admin/products/price_edit.php
// Pagina pentru editarea unui preț specific pentru un client

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
$product = $productObj->getProductById($specificPrice['product_id']);

// Verificare existență produs
if (!$product) {
    setFlashMessage('error', 'Produsul nu există.');
    redirect('index.php');
}

// Obține clientul
$client = $clientObj->getClientById($specificPrice['client_id']);

// Verificare existență client
if (!$client) {
    setFlashMessage('error', 'Clientul nu există.');
    redirect('view.php?id=' . $product['id']);
}

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'price' => $specificPrice['price']
];

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Preluare date formular
        $formData = [
            'price' => isset($_POST['price']) ? (float)str_replace(',', '.', $_POST['price']) : 0,
        ];
        
        // Validare date
        $errors = [];
        
        if ($formData['price'] <= 0) {
            $errors[] = 'Prețul trebuie să fie mai mare decât 0.';
        }
        
        // Dacă nu există erori, procesăm actualizarea
        if (empty($errors)) {
            $priceData = [
                'id' => $price_id,
                'price' => $formData['price']
            ];
            
            $result = $productObj->updateClientSpecificPrice($priceData);
            
            if ($result) {
                setFlashMessage('success', 'Prețul specific a fost actualizat cu succes.');
                redirect('view.php?id=' . $product['id']);
            } else {
                $error = 'A apărut o eroare la actualizarea prețului specific. Vă rugăm să încercați din nou.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Titlu pagină
$pageTitle = 'Editare Preț Specific - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="view.php?id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la detalii produs
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Editare Preț Specific</h1>

<!-- Informații produs și client -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <div class="flex flex-col md:flex-row">
        <div class="md:w-1/2 mb-4 md:mb-0 md:pr-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Informații produs</h2>
            <div class="flex items-center">
                <?php if (!empty($product['image']) && file_exists('../../uploads/products/' . $product['image'])): ?>
                    <img src="../../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="h-16 w-16 object-cover rounded-md mr-4">
                <?php else: ?>
                    <div class="h-16 w-16 bg-gray-200 rounded-md flex items-center justify-center mr-4">
                        <i class="fas fa-box text-gray-400"></i>
                    </div>
                <?php endif; ?>
                
                <div>
                    <h3 class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="text-sm text-gray-500">Cod: <?php echo htmlspecialchars($product['code']); ?></p>
                    <p class="text-sm font-medium text-green-600 mt-1">Preț standard: <?php echo formatAmount($product['price']); ?> Lei</p>
                </div>
            </div>
        </div>
        
        <div class="md:w-1/2 md:pl-4 md:border-l md:border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Informații client</h2>
            <div>
                <h3 class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($client['company_name']); ?></h3>
                <p class="text-sm text-gray-500">CUI: <?php echo htmlspecialchars($client['fiscal_code']); ?></p>
                <p class="text-sm text-gray-500">Email: <?php echo htmlspecialchars($client['email']); ?></p>
                <p class="text-sm text-gray-500">Telefon: <?php echo htmlspecialchars($client['phone']); ?></p>
            </div>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <div class="flex">
            <div class="py-1">
                <i class="fas fa-check-circle mr-1"></i>
            </div>
            <div>
                <p class="font-bold">Succes!</p>
                <p class="text-sm"><?php echo $success; ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Editare preț specific</h2>
    </div>
    
    <div class="p-6">
        <form method="POST" action="price_edit.php?id=<?php echo $price_id; ?>" class="space-y-6">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Preț specific -->
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700">Preț specific (Lei) <span class="text-red-500">*</span></label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <input type="text" id="price" name="price" value="<?php echo htmlspecialchars($formData['price']); ?>" required
                           class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">Lei</span>
                    </div>
                </div>
                <div class="mt-2 flex items-center text-sm">
                    <span class="text-gray-600 mr-2">Preț standard:</span>
                    <span class="font-medium text-gray-900"><?php echo formatAmount($product['price']); ?> Lei</span>
                    
                    <?php 
                    $diff = $formData['price'] - $product['price'];
                    $diffPercent = ($product['price'] > 0) ? ($diff / $product['price'] * 100) : 0;
                    $isDiscount = $diff < 0;
                    ?>
                    
                    <span class="mx-2 text-gray-500">|</span>
                    
                    <span class="text-gray-600 mr-2">Diferență:</span>
                    <span class="font-medium <?php echo $isDiscount ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo $isDiscount ? '-' : '+'; ?><?php echo formatAmount(abs($diff)); ?> Lei
                        (<?php echo $isDiscount ? '-' : '+'; ?><?php echo number_format(abs($diffPercent), 2); ?>%)
                    </span>
                </div>
            </div>
            
            <!-- Butoane -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="view.php?id=<?php echo $product['id']; ?>" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Anulează
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-save mr-1"></i> Salvează prețul
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validare preț - accept doar numere și punct/virgulă
    const priceInput = document.getElementById('price');
    priceInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9.,]/g, '');
    });
    
    // Actualizare diferență în timp real
    priceInput.addEventListener('input', function() {
        // Implementare opțională pentru calcularea diferenței în timp real
    });
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>