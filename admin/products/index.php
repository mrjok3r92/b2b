<?php
// admin/products/index.php
// Pagina pentru listarea produselor în panoul de administrare

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Product.php';

// Inițializare obiecte
$productObj = new Product();

// Parametri paginare și filtrare
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Produse per pagină
$offset = ($page - 1) * $limit;

// Parametri filtrare
$category_id = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Obține produsele
if (!empty($search)) {
    $products = $productObj->searchProducts($search, $limit, $offset);
    $totalProducts = $productObj->countSearchResults($search);
} elseif ($category_id > 0) {
    $products = $productObj->getProductsByCategory($category_id, $limit, $offset);
    $totalProducts = $productObj->countProductsByCategory($category_id);
} elseif (!empty($status)) {
    $products = $productObj->getProductsByStatus($status, $limit, $offset);
    $totalProducts = $productObj->countProductsByStatus($status);
} else {
    $products = $productObj->getAllProductsPaginated($limit, $offset);
    $totalProducts = $productObj->getTotalProducts();
}

// Calculează numărul de pagini
$totalPages = ceil($totalProducts / $limit);

// Obține categoriile pentru filtru
$categories = $productObj->getAllCategories();

// Titlu pagină
$pageTitle = 'Gestionare Produse - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">Gestionare Produse</h1>
    
    <div>
        <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md mr-2">
            <i class="fas fa-plus mr-1"></i> Adaugă produs
        </a>
        <a href="categories.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md">
            <i class="fas fa-tags mr-1"></i> Categorii
        </a>
    </div>
</div>

<!-- Filtre și căutare -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="index.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Categorie</label>
            <select id="category" name="category" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toate categoriile</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toate statusurile</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Caută</label>
            <div class="relative rounded-md shadow-sm">
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Cod, nume sau descriere..." 
                       class="block w-full pr-10 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md mr-2">
                <i class="fas fa-filter mr-1"></i> Filtrează
            </button>
            
            <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                <i class="fas fa-times mr-1"></i> Resetează
            </a>
        </div>
    </form>
</div>

<!-- Lista produse -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Lista produse</h2>
        <p class="text-sm text-gray-500 mt-1">Total: <?php echo $totalProducts; ?> produse</p>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($products) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Imagine
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cod
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Produs
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Categorie
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            U.M.
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Preț standard
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acțiuni
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="h-10 w-10 bg-gray-200 rounded-md flex items-center justify-center">
                                    <?php if (!empty($product['image']) && file_exists('../../uploads/products/' . $product['image'])): ?>
                                        <img src="../../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-10 w-10 object-cover rounded-md">
                                    <?php else: ?>
                                        <i class="fas fa-box text-gray-400"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($product['code']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                <?php if (!empty($product['description'])): ?>
                                    <div class="text-xs text-gray-500 truncate max-w-xs" title="<?php echo htmlspecialchars($product['description']); ?>">
                                        <?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Necategorizat'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($product['unit']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo formatAmount($product['price']); ?> Lei
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($product['status'] == 'active'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Activ
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Inactiv
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="view.php?id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $product['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-2">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $product['id']; ?>" class="text-red-600 hover:text-red-900 delete-confirm">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginare -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Afișare <span class="font-medium"><?php echo ($page - 1) * $limit + 1; ?></span> - 
                            <span class="font-medium"><?php echo min($page * $limit, $totalProducts); ?></span> din 
                            <span class="font-medium"><?php echo $totalProducts; ?></span> produse
                        </div>
                        
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1 rounded-md bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1 rounded-md bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-box-open fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu au fost găsite produse</h3>
                <p class="text-gray-600 mb-4">
                    <?php if (!empty($search) || !empty($category_id) || !empty($status)): ?>
                        Nu există produse care să corespundă criteriilor de filtrare selectate.
                    <?php else: ?>
                        Nu există produse înregistrate în sistem.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || !empty($category_id) || !empty($status)): ?>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-times mr-1"></i> Resetează filtrele
                    </a>
                <?php else: ?>
                    <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                        <i class="fas fa-plus mr-1"></i> Adaugă primul produs
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmare ștergere
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Ești sigur că vrei să ștergi acest produs? Această acțiune nu poate fi anulată.')) {
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