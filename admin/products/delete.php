<?php
// admin/products/delete.php
// Pagina pentru ștergerea unui produs

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Product.php';

// Verificare ID produs
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID produs invalid.');
    redirect('index.php');
}

$product_id = (int)$_GET['id'];

// Inițializare obiecte
$productObj = new Product();

// Obține informațiile produsului
$product = $productObj->getProductById($product_id);

// Verificare existență produs
if (!$product) {
    setFlashMessage('error', 'Produsul nu există.');
    redirect('index.php');
}

// Verificare confirmare
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
$csrf_valid = isset($_GET['csrf_token']) && verifyCSRFToken($_GET['csrf_token']);

// Verificare dacă produsul are comenzi asociate
$hasOrders = $productObj->hasOrders($product_id);

// Dacă utilizatorul a confirmat și token-ul CSRF este valid
if ($confirmed && $csrf_valid) {
    // Verificare dacă produsul are comenzi asociate
    if ($hasOrders) {
        setFlashMessage('error', 'Acest produs nu poate fi șters deoarece are comenzi asociate. Puteți dezactiva produsul în loc să-l ștergeți.');
        redirect('view.php?id=' . $product_id);
    }
    
    // Ștergere produs
    $result = $productObj->deleteProduct($product_id);
    
    if ($result) {
        // Ștergere imagine asociată
        if (!empty($product['image'])) {
            $imagePath = '../../uploads/products/' . $product['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        setFlashMessage('success', 'Produsul a fost șters cu succes.');
    } else {
        setFlashMessage('error', 'A apărut o eroare la ștergerea produsului. Vă rugăm să încercați din nou.');
    }
    
    redirect('index.php');
}

// Titlu pagină
$pageTitle = 'Ștergere Produs - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="view.php?id=<?php echo $product_id; ?>" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la detalii produs
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Confirmare Ștergere Produs</h1>

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
                    Ești sigur că vrei să ștergi acest produs?
                </h3>
                
                <div class="bg-gray-100 rounded-md p-4 mb-4">
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
                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h4>
                            <p class="text-sm text-gray-500">Cod: <?php echo htmlspecialchars($product['code']); ?></p>
                            <p class="text-sm font-medium text-green-600 mt-1">Preț: <?php echo formatAmount($product['price']); ?> Lei</p>
                        </div>
                    </div>
                </div>
                
                <?php if ($hasOrders): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-ban text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">
                                    <strong>Acest produs nu poate fi șters!</strong> Are comenzi asociate în sistem.
                                </p>
                                <p class="text-sm text-red-700 mt-2">
                                    În loc să ștergeți produsul, vă recomandăm să îl dezactivați pentru a păstra istoricul comenzilor.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <a href="view.php?id=<?php echo $product_id; ?>" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Înapoi la detalii produs
                        </a>
                        <a href="edit.php?id=<?php echo $product_id; ?>" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-edit mr-1"></i> Editează produsul
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600 mb-4">
                        Această acțiune va șterge permanent produsul din baza de date. Toate prețurile specifice pentru clienți vor fi de asemenea șterse.
                    </p>
                    <p class="text-gray-600 mb-4">
                        <strong>Notă:</strong> Această acțiune nu poate fi anulată.
                    </p>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <a href="view.php?id=<?php echo $product_id; ?>" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Anulează
                        </a>
                        <a href="delete.php?id=<?php echo $product_id; ?>&confirm=yes&csrf_token=<?php echo generateCSRFToken(); ?>" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-trash mr-1"></i> Șterge produsul
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>