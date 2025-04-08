<?php
// client/products/index.php
// Pagina de listare a produselor pentru client

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/Product.php';

// Inițializare obiecte
$productObj = new Product();

// Obține categoriile
$categories = $productObj->getAllCategories();

// Obține toate produsele sau filtrează după categorie dacă este specificată
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($category_id > 0) {
    $products = $productObj->getProductsByCategory($category_id);
} elseif (!empty($search)) {
    $products = $productObj->searchProducts($search);
} else {
    $products = $productObj->getActiveProducts();
}

// Obține prețurile specifice pentru client
$clientPrices = [];
foreach ($products as $product) {
    $clientPrices[$product['id']] = $productObj->getClientPrice($_SESSION['client_id'], $product['id']);
}

// Titlu pagină
$pageTitle = 'Produse - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Produse</h1>
    
    <!-- Căutare -->
    <div class="flex space-x-4">
        <form action="index.php" method="GET" class="flex space-x-2">
            <input type="text" name="search" placeholder="Caută produse..." value="<?php echo htmlspecialchars($search); ?>"
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-search"></i>
            </button>
        </form>
        
        <a href="../cart/index.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center">
            <i class="fas fa-shopping-cart mr-2"></i> Coș
            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                <span class="ml-2 bg-white text-green-700 px-2 py-1 rounded-full text-xs font-bold">
                    <?php echo count($_SESSION['cart']); ?>
                </span>
            <?php endif; ?>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Sidebar cu categorii -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h2 class="text-lg font-semibold mb-4">Categorii</h2>
            <ul class="space-y-2">
                <li>
                    <a href="index.php" class="<?php echo $category_id == 0 && empty($search) ? 'text-blue-600 font-medium' : 'text-gray-700 hover:text-blue-600'; ?> block py-1">
                        Toate produsele
                    </a>
                </li>
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="index.php?category=<?php echo $category['id']; ?>" 
                           class="<?php echo $category_id == $category['id'] ? 'text-blue-600 font-medium' : 'text-gray-700 hover:text-blue-600'; ?> block py-1">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <!-- Lista produse -->
    <div class="lg:col-span-3">
        <?php if (!empty($search)): ?>
            <div class="mb-4">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Rezultate pentru căutarea: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                                <a href="index.php" class="ml-2 font-medium underline">Resetează</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($category_id > 0): ?>
            <div class="mb-4">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Filtrare după categoria: <strong>"<?php echo htmlspecialchars($categories[$category_id-1]['name']); ?>"</strong>
                                <a href="index.php" class="ml-2 font-medium underline">Resetează</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (count($products) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200 hover:shadow-md transition-shadow duration-300">
                        <div class="h-40 bg-gray-200 flex items-center justify-center">
                            <?php if (!empty($product['image']) && file_exists('../../uploads/products/' . $product['image'])): ?>
                                <img src="../../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="max-h-full max-w-full object-contain">
                            <?php else: ?>
                                <div class="text-gray-400 text-center">
                                    <i class="fas fa-box-open fa-3x mb-2"></i>
                                    <p>Fără imagine</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                    <?php echo htmlspecialchars($product['code']); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($product['description'])): ?>
                                <p class="mt-2 text-sm text-gray-600"><?php echo htmlspecialchars(truncateText($product['description'], 100)); ?></p>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <p class="text-lg font-bold text-gray-900"><?php echo formatAmount($clientPrices[$product['id']]); ?> Lei / <?php echo htmlspecialchars($product['unit']); ?></p>
                            </div>
                            
                            <div class="mt-4 flex justify-between items-center">
                                <a href="view.php?id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-eye mr-1"></i> Detalii
                                </a>
                                
                                <button type="button" onclick="addToCart(<?php echo $product['id']; ?>)"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm">
                                    <i class="fas fa-cart-plus mr-1"></i> Adaugă în coș
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <i class="fas fa-search fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu au fost găsite produse</h3>
                <p class="text-gray-600">
                    <?php if (!empty($search)): ?>
                        Nu am găsit produse care să corespundă căutării tale "<?php echo htmlspecialchars($search); ?>".
                    <?php elseif ($category_id > 0): ?>
                        Nu există produse în această categorie.
                    <?php else: ?>
                        Nu există produse disponibile momentan.
                    <?php endif; ?>
                </p>
                <div class="mt-4">
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        Vizualizează toate produsele
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Container pentru alerte -->
<div class="alert-container fixed bottom-4 right-4 z-50"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Adăugare în coș direct
    window.addToCart = function(productId) {
        fetch('../cart/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success', 3000);
                
                // Actualizăm contorul din navbar și butonul coșului
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Eroare la comunicarea cu serverul.', 'error');
        });
    };
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>