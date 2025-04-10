<?php
// admin/products/view.php
// Pagina pentru vizualizarea detaliată a unui produs

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Product.php';
require_once '../../classes/Client.php';

// Verificare ID produs
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID produs invalid.');
    redirect('index.php');
}

$product_id = (int)$_GET['id'];

// Inițializare obiecte
$productObj = new Product();
$clientObj = new Client();

// Obține informațiile produsului
$product = $productObj->getProductById($product_id);

// Verificare existență produs
if (!$product) {
    setFlashMessage('error', 'Produsul nu există.');
    redirect('index.php');
}

// Obține categoria produsului
$category = null;
if (!empty($product['category_id'])) {
    $category = $productObj->getCategoryById($product['category_id']);
}

// Obține prețurile specifice pentru clienți
$clientSpecificPrices = $productObj->getClientSpecificPrices($product_id);

// Obține istoricul comenzilor pentru acest produs
$orderHistory = $productObj->getProductOrderHistory($product_id, 10);

// Titlu pagină
$pageTitle = 'Detalii Produs - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de produse
    </a>
</div>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Detalii Produs</h1>
    
    <div class="flex space-x-2">
        <a href="edit.php?id=<?php echo $product_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
            <i class="fas fa-edit mr-1"></i> Editează
        </a>
        <a href="delete.php?id=<?php echo $product_id; ?>" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md delete-confirm">
            <i class="fas fa-trash mr-1"></i> Șterge
        </a>
    </div>
</div>

<!-- Informații generale -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Informații generale</h2>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Cod produs -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Cod produs</h3>
                        <p class="mt-1 text-lg font-medium text-gray-900"><?php echo htmlspecialchars($product['code']); ?></p>
                    </div>
                    
                    <!-- Categorie -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Categorie</h3>
                        <p class="mt-1 text-lg font-medium text-gray-900">
                            <?php echo $category ? htmlspecialchars($category['name']) : 'Necategorizat'; ?>
                        </p>
                    </div>
                    
                    <!-- Nume produs -->
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-medium text-gray-500">Nume produs</h3>
                        <p class="mt-1 text-lg font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></p>
                    </div>
                    
                    <!-- Descriere -->
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-medium text-gray-500">Descriere</h3>
                        <div class="mt-1 text-base text-gray-700">
                            <?php if (!empty($product['description'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            <?php else: ?>
                                <p class="italic text-gray-500">Nicio descriere disponibilă</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Unitate de măsură -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Unitate de măsură</h3>
                        <p class="mt-1 text-lg font-medium text-gray-900"><?php echo htmlspecialchars($product['unit']); ?></p>
                    </div>
                    
                    <!-- Preț standard -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Preț standard</h3>
                        <p class="mt-1 text-lg font-medium text-green-600"><?php echo formatAmount($product['price']); ?> Lei</p>
                    </div>
                    
                    <!-- Status -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Status</h3>
                        <div class="mt-1">
                            <?php if ($product['status'] === 'active'): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Activ
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Inactiv
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Data adăugare -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Data adăugare</h3>
                        <p class="mt-1 text-base text-gray-700">
                            <?php echo date('d.m.Y H:i', strtotime($product['created_at'])); ?>
                        </p>
                    </div>
                    
                    <!-- Data actualizare -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Ultima actualizare</h3>
                        <p class="mt-1 text-base text-gray-700">
                            <?php echo date('d.m.Y H:i', strtotime($product['updated_at'])); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Imagine produs -->
            <div class="flex justify-center">
                <div class="w-full max-w-xs">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Imagine produs</h3>
                    <?php if (!empty($product['image']) && file_exists('../../uploads/products/' . $product['image'])): ?>
                        <img src="../../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="mx-auto h-48 w-48 object-cover rounded-md border border-gray-300">
                    <?php else: ?>
                        <div class="mx-auto h-48 w-48 bg-gray-200 rounded-md flex items-center justify-center">
                            <i class="fas fa-box fa-3x text-gray-400"></i>
                        </div>
                        <p class="text-center mt-2 text-sm text-gray-500 italic">Nicio imagine disponibilă</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Prețuri specifice clienților -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
        <h2 class="text-lg font-semibold">Prețuri specifice pentru clienți</h2>
        <a href="price_add.php?product_id=<?php echo $product_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 text-sm rounded-md">
            <i class="fas fa-plus mr-1"></i> Adaugă preț specific
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($clientSpecificPrices) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Client
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Preț specific (Lei)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Diferență vs. standard
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data actualizare
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acțiuni
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($clientSpecificPrices as $price): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($price['company_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($price['fiscal_code']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-green-600">
                                    <?php echo formatAmount($price['price']); ?> Lei
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $diff = $price['price'] - $product['price'];
                                $diffPercent = ($product['price'] > 0) ? ($diff / $product['price'] * 100) : 0;
                                $isDiscount = $diff < 0;
                                ?>
                                <div class="text-sm font-medium <?php echo $isDiscount ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $isDiscount ? '-' : '+'; ?><?php echo formatAmount(abs($diff)); ?> Lei
                                    (<?php echo $isDiscount ? '-' : '+'; ?><?php echo number_format(abs($diffPercent), 2); ?>%)
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d.m.Y H:i', strtotime($price['updated_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="price_edit.php?id=<?php echo $price['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-2">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="price_delete.php?id=<?php echo $price['id']; ?>" class="text-red-600 hover:text-red-900 delete-price-confirm">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-tags fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu există prețuri specifice pentru clienți</h3>
                <p class="text-gray-600 mb-4">
                    Toate comenzile vor folosi prețul standard de <?php echo formatAmount($product['price']); ?> Lei.
                </p>
                <a href="price_add.php?product_id=<?php echo $product_id; ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-plus mr-1"></i> Adaugă primul preț specific
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Istoric comenzi -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Istoric comenzi recente</h2>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($orderHistory) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nr. comandă
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Client
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cantitate
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Preț unitar
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($orderHistory as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="../orders/view.php?id=<?php echo $order['order_id']; ?>" class="text-blue-600 hover:text-blue-900 font-medium">
                                    <?php echo htmlspecialchars($order['order_number']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($order['company_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d.m.Y', strtotime($order['order_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo number_format($order['quantity'], 2); ?> <?php echo htmlspecialchars($product['unit']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo formatAmount($order['unit_price']); ?> Lei
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo formatAmount($order['total']); ?> Lei
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'delivered' => 'bg-blue-100 text-blue-800'
                                ];
                                $statusLabels = [
                                    'pending' => 'În așteptare',
                                    'approved' => 'Aprobată',
                                    'rejected' => 'Respinsă',
                                    'delivered' => 'Livrată'
                                ];
                                $statusClass = $statusClasses[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                $statusLabel = $statusLabels[$order['status']] ?? 'Necunoscut';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="p-4 border-t border-gray-200 bg-gray-50 text-right">
                <a href="../reports/product_orders.php?id=<?php echo $product_id; ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-file-alt mr-1"></i> Vezi raportul complet
                </a>
            </div>
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-shopping-cart fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu există comenzi pentru acest produs</h3>
                <p class="text-gray-600">
                    Acest produs nu a fost comandat încă de niciun client.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmare ștergere produs
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Ești sigur că vrei să ștergi acest produs? Această acțiune nu poate fi anulată.')) {
                e.preventDefault();
            }
        });
    });
    
    // Confirmare ștergere preț specific
    const deletePriceButtons = document.querySelectorAll('.delete-price-confirm');
    deletePriceButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Ești sigur că vrei să ștergi acest preț specific? Clientul va reveni la prețul standard.')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>