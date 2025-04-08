<?php
// client/products/view.php
// Pagina de vizualizare detalii produs pentru client

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

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

// Obține detaliile produsului
$product = $productObj->getProductById($product_id);

// Verificare existență produs
if (!$product || $product['status'] !== 'active') {
    setFlashMessage('error', 'Produsul nu există sau nu este disponibil.');
    redirect('index.php');
}

// Obține prețul specific pentru client
$clientPrice = $productObj->getClientPrice($_SESSION['client_id'], $product_id);

// Obține alte produse din aceeași categorie
$relatedProducts = $productObj->getProductsByCategory($product['category_id']);
// Eliminăm produsul curent din lista produselor asemănătoare
foreach ($relatedProducts as $key => $relatedProduct) {
    if ($relatedProduct['id'] == $product_id) {
        unset($relatedProducts[$key]);
        break;
    }
}
// Limităm la maximum 4 produse asemănătoare
$relatedProducts = array_slice($relatedProducts, 0, 4);

// Titlu pagină
$pageTitle = $product['name'] . ' - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de produse
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
        <!-- Imaginea produsului -->
        <div class="flex items-center justify-center bg-gray-100 rounded-lg p-4 h-80">
            <?php if (!empty($product['image']) && file_exists('../../uploads/products/' . $product['image'])): ?>
                <img src="../../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="max-h-full max-w-full object-contain">
            <?php else: ?>
                <div class="text-gray-400 text-center">
                    <i class="fas fa-box-open fa-5x mb-3"></i>
                    <p class="text-lg">Fără imagine</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Detalii produs -->
        <div>
            <div class="mb-4">
                <h1 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="flex items-center space-x-2">
                    <span class="bg-gray-100 text-gray-800 text-sm font-semibold px-2.5 py-0.5 rounded">
                        Cod: <?php echo htmlspecialchars($product['code']); ?>
                    </span>
                    <?php if (!empty($product['category_name'])): ?>
                        <span class="bg-blue-100 text-blue-800 text-sm font-semibold px-2.5 py-0.5 rounded">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($product['description'])): ?>
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Descriere</h2>
                    <div class="text-gray-700 space-y-2">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Specificații</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Unitate de măsură</p>
                        <p class="font-medium"><?php echo htmlspecialchars($product['unit']); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Preț per unitate</p>
                        <p class="font-bold text-lg"><?php echo formatAmount($clientPrice); ?> Lei</p>
                    </div>
                </div>
            </div>
            
            <!-- Formular pentru adăugare în coș -->
            <form id="add-to-cart-form" class="mt-6">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <div class="flex items-center mb-4">
                    <label for="quantity" class="mr-4 font-medium">Cantitate:</label>
                    <div class="flex items-center">
                        <button type="button" class="quantity-btn quantity-decrease bg-gray-200 px-3 py-1 rounded-l-md">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="9999"
                               class="form-control w-20 text-center border-y border-gray-300 py-1">
                        <button type="button" class="quantity-btn quantity-increase bg-gray-200 px-3 py-1 rounded-r-md">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                        <i class="fas fa-cart-plus mr-2"></i> Adaugă în coș
                    </button>
                    
                    <a href="../cart/index.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center">
                        <i class="fas fa-shopping-cart mr-2"></i> Vezi coșul
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="ml-1 bg-white text-green-700 px-2 py-0 rounded-full text-xs font-bold">
                                <?php echo count($_SESSION['cart']); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Produse asemănătoare -->
<?php if (count($relatedProducts) > 0): ?>
    <div class="mt-10">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Produse asemănătoare</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200 hover:shadow-md transition-shadow duration-300">
                    <div class="h-32 bg-gray-200 flex items-center justify-center">
                        <?php if (!empty($relatedProduct['image']) && file_exists('../../uploads/products/' . $relatedProduct['image'])): ?>
                            <img src="../../uploads/products/<?php echo $relatedProduct['image']; ?>" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>" class="max-h-full max-w-full object-contain">
                        <?php else: ?>
                            <div class="text-gray-400 text-center">
                                <i class="fas fa-box-open fa-2x"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-4">
                        <h3 class="text-md font-semibold text-gray-900"><?php echo htmlspecialchars($relatedProduct['name']); ?></h3>
                        
                        <div class="mt-2">
                            <p class="font-bold text-gray-900"><?php echo formatAmount($productObj->getClientPrice($_SESSION['client_id'], $relatedProduct['id'])); ?> Lei</p>
                        </div>
                        
                        <div class="mt-3 flex justify-between items-center">
                            <a href="view.php?id=<?php echo $relatedProduct['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-eye mr-1"></i> Detalii
                            </a>
                            
                            <button type="button" onclick="addToCart(<?php echo $relatedProduct['id']; ?>)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded-md text-xs">
                                <i class="fas fa-cart-plus mr-1"></i> Adaugă
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Container pentru alerte -->
<div class="alert-container fixed bottom-4 right-4 z-50"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manipulare cantitate
    const quantityInput = document.getElementById('quantity');
    
    document.querySelector('.quantity-decrease').addEventListener('click', function() {
        let quantity = parseInt(quantityInput.value);
        if (quantity > 1) {
            quantityInput.value = quantity - 1;
        }
    });
    
    document.querySelector('.quantity-increase').addEventListener('click', function() {
        let quantity = parseInt(quantityInput.value);
        quantityInput.value = quantity + 1;
    });
    
    // Formular adăugare în coș
    document.getElementById('add-to-cart-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const productId = this.querySelector('[name="product_id"]').value;
        const quantity = this.querySelector('[name="quantity"]').value;
        
        addToCartWithQuantity(productId, quantity);
    });
    
    // Funcție pentru adăugare în coș cu cantitate specificată
    window.addToCartWithQuantity = function(productId, quantity) {
        fetch('../cart/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}`
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
    
    // Adăugare în coș direct pentru produsele asemănătoare
    window.addToCart = function(productId) {
        addToCartWithQuantity(productId, 1);
    };
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>