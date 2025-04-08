<?php
// client/cart/index.php
// Pagina pentru coșul de cumpărături

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/Product.php';
require_once '../../classes/Client.php';

// Inițializare sesiune coș dacă nu există
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Inițializare obiecte
$productObj = new Product();
$clientObj = new Client();

// Obține detalii pentru produsele din coș
$cartItems = [];
$totalAmount = 0;

foreach ($_SESSION['cart'] as $productId => $quantity) {
    $product = $productObj->getProductById($productId);
    
    if ($product && $product['status'] == 'active') {
        $price = $productObj->getClientPrice($_SESSION['client_id'], $productId);
        $amount = $price * $quantity;
        
        $cartItems[] = [
            'product_id' => $productId,
            'product' => $product,
            'quantity' => $quantity,
            'price' => $price,
            'amount' => $amount
        ];
        
        $totalAmount += $amount;
    } else {
        // Elimină produsele care nu mai sunt active
        unset($_SESSION['cart'][$productId]);
    }
}

// Obținere locații client pentru checkout
$locations = $clientObj->getClientLocations($_SESSION['client_id']);

// Titlu pagină
$pageTitle = 'Coș de cumpărături - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Coș de cumpărături</h1>
    
    <a href="../products/index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Continuă cumpărăturile
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Coș produse -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h2 class="text-lg font-semibold">Produse în coș</h2>
                
                <?php if (count($cartItems) > 0): ?>
                    <button type="button" onclick="clearCart()" class="text-red-600 hover:text-red-800 text-sm">
                        <i class="fas fa-trash-alt mr-1"></i> Golește coșul
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="cart-container p-4">
                <?php if (count($cartItems) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item border-b border-gray-200 pb-4 last:border-b-0 last:pb-0" data-product-id="<?php echo $item['product_id']; ?>">
                                <div class="flex items-start">
                                    <!-- Imagine produs -->
                                    <div class="flex-shrink-0 w-16 h-16 bg-gray-200 rounded-md flex items-center justify-center mr-4">
                                        <?php if (!empty($item['product']['image']) && file_exists('../../uploads/products/' . $item['product']['image'])): ?>
                                            <img src="../../uploads/products/<?php echo $item['product']['image']; ?>" alt="<?php echo htmlspecialchars($item['product']['name']); ?>" class="max-h-full max-w-full object-contain">
                                        <?php else: ?>
                                            <i class="fas fa-box-open text-gray-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Detalii produs -->
                                    <div class="flex-grow">
                                        <h3 class="text-md font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($item['product']['name']); ?>
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars($item['product']['code']); ?> | <?php echo htmlspecialchars($item['product']['unit']); ?>
                                        </p>
                                        <p class="text-sm text-gray-600 mt-1 item-price" data-price="<?php echo $item['price']; ?>">
                                            Preț unitar: <strong><?php echo formatAmount($item['price']); ?> Lei</strong>
                                        </p>
                                        <div class="mt-2 item-total font-semibold">
                                            <?php echo formatAmount($item['amount']); ?> Lei
                                        </div>
                                    </div>
                                    
                                    <!-- Cantitate și acțiuni -->
                                    <div class="flex-shrink-0 ml-4 cart-item-actions">
                                        <div class="flex items-center mb-2">
                                            <button type="button" class="quantity-btn quantity-decrease bg-gray-200 px-2 py-1 rounded-l-md">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="cart-quantity-input w-14 text-center border-y border-gray-300 py-1" 
                                                   value="<?php echo $item['quantity']; ?>" min="1" max="9999">
                                            <button type="button" class="quantity-btn quantity-increase bg-gray-200 px-2 py-1 rounded-r-md">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <button type="button" class="remove-from-cart text-red-600 hover:text-red-800 text-sm w-full text-center">
                                            <i class="fas fa-trash"></i> Elimină
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-shopping-cart fa-3x text-gray-300 mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Coșul tău este gol</h3>
                        <p class="text-gray-600 mb-4">Adaugă produse în coș pentru a putea plasa o comandă.</p>
                        <a href="../products/index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                            <i class="fas fa-shopping-bag mr-2"></i> Caută produse
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sumar comandă -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-4">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold">Sumar comandă</h2>
            </div>
            
            <div class="p-4">
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Produse (<?php echo count($cartItems); ?>)</span>
                        <span class="text-gray-900"><?php echo formatAmount($totalAmount); ?> Lei</span>
                    </div>
                </div>
                
                <div class="border-t border-gray-200 pt-4 mb-4">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold">Total</span>
                        <span class="text-xl font-bold text-gray-900 cart-total"><?php echo formatAmount($totalAmount); ?> Lei</span>
                    </div>
                </div>
                
                <?php if (count($cartItems) > 0): ?>
                    <form id="checkout-form" action="checkout.php" method="POST" class="space-y-4">
                        <input type="hidden" id="total_amount" name="total_amount" value="<?php echo number_format($totalAmount, 2, '.', ''); ?>">
                        
                        <div>
                            <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">Locație livrare</label>
                            
                            <?php if (count($locations) > 0): ?>
                                <select id="location_id" name="location_id" required
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">Selectează locația</option>
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo $location['id']; ?>" <?php echo $_SESSION['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($location['name']); ?> - <?php echo htmlspecialchars($location['address']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <div id="location-info" class="hidden"></div>
                            <?php else: ?>
                                <div class="p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 text-sm">
                                    <p>Nu există locații disponibile. Contactează administratorul.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Observații comandă</label>
                            <textarea id="notes" name="notes" rows="3" 
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="terms" name="terms" type="checkbox" required
                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="terms" class="font-medium text-gray-700">Sunt de acord cu <a href="#" class="text-blue-600 hover:text-blue-500">termenii și condițiile</a></label>
                            </div>
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" 
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-check-circle mr-2"></i> Plasează comanda
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Container pentru alerte -->
<div class="alert-container fixed bottom-4 right-4 z-50"></div>

<!-- Script pentru funcționalități coș -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inițializare funcționalități coș
    const cartItems = document.querySelectorAll('.cart-item');
    
    if (cartItems.length > 0) {
        // Butoane cantitate
        cartItems.forEach(function(item) {
            const productId = item.getAttribute('data-product-id');
            const quantityInput = item.querySelector('.cart-quantity-input');
            const decreaseBtn = item.querySelector('.quantity-decrease');
            const increaseBtn = item.querySelector('.quantity-increase');
            const removeBtn = item.querySelector('.remove-from-cart');
            
            // Modificare cantitate
            quantityInput.addEventListener('change', function() {
                let quantity = parseInt(this.value);
                
                // Validare cantitate
                if (isNaN(quantity) || quantity < 1) {
                    quantity = 1;
                    this.value = 1;
                }
                
                // Actualizare coș
                updateCartQuantity(productId, quantity);
            });
            
            // Buton scădere cantitate
            decreaseBtn.addEventListener('click', function() {
                let quantity = parseInt(quantityInput.value);
                if (quantity > 1) {
                    quantityInput.value = quantity - 1;
                    quantityInput.dispatchEvent(new Event('change'));
                }
            });
            
            // Buton creștere cantitate
            increaseBtn.addEventListener('click', function() {
                let quantity = parseInt(quantityInput.value);
                quantityInput.value = quantity + 1;
                quantityInput.dispatchEvent(new Event('change'));
            });
            
            // Buton eliminare produs
            removeBtn.addEventListener('click', function() {
                removeFromCart(productId);
            });
        });
        
        // Validare formular checkout
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(e) {
                const locationSelect = document.getElementById('location_id');
                
                if (locationSelect && locationSelect.value === '') {
                    e.preventDefault();
                    showAlert('Vă rugăm să selectați o locație pentru livrare.', 'error');
                }
            });
        }
        
        // Inițializare informații locație
        const locationSelect = document.getElementById('location_id');
        if (locationSelect) {
            updateLocationInfo(locationSelect.value);
            
            locationSelect.addEventListener('change', function() {
                updateLocationInfo(this.value);
            });
        }
    }
    
    // Funcție pentru afișarea informațiilor despre locație
    function updateLocationInfo(locationId) {
        const locationInfoContainer = document.getElementById('location-info');
        
        if (locationId === '') {
            locationInfoContainer.innerHTML = '';
            locationInfoContainer.classList.add('hidden');
            return;
        }
        
        // Obține informațiile despre locație
        fetch('../locations/get_location_info.php?id=' + locationId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '<div class="mt-3 p-3 bg-gray-50 text-sm rounded-md">';
                    html += '<p class="font-medium mb-1">' + data.location.name + '</p>';
                    html += '<p>' + data.location.address + '</p>';
                    
                    if (data.location.contact_person) {
                        html += '<p class="mt-2"><span class="font-medium">Contact:</span> ' + data.location.contact_person + '</p>';
                    }
                    
                    if (data.location.phone) {
                        html += '<p><span class="font-medium">Telefon:</span> ' + data.location.phone + '</p>';
                    }
                    
                    html += '</div>';
                    
                    locationInfoContainer.innerHTML = html;
                    locationInfoContainer.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Eroare:', error);
            });
    }
});

// Funcție pentru actualizarea cantității
function updateCartQuantity(productId, quantity) {
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&quantity=' + quantity
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizare UI
            updateCartTotal();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Eroare:', error);
        showAlert('Eroare la comunicarea cu serverul.', 'error');
    });
}

// Funcție pentru eliminarea unui produs din coș
function removeFromCart(productId) {
    if (confirm('Sunteți sigur că doriți să eliminați acest produs din coș?')) {
        fetch('remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Elimină elementul din DOM
                const cartItem = document.querySelector('.cart-item[data-product-id="' + productId + '"]');
                if (cartItem) {
                    cartItem.remove();
                }
                
                // Verifică dacă coșul este gol
                const cartItems = document.querySelectorAll('.cart-item');
                if (cartItems.length === 0) {
                    document.querySelector('.cart-container').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-shopping-cart fa-3x text-gray-300 mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Coșul tău este gol</h3>
                            <p class="text-gray-600 mb-4">Adaugă produse în coș pentru a putea plasa o comandă.</p>
                            <a href="../products/index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                                <i class="fas fa-shopping-bag mr-2"></i> Caută produse
                            </a>
                        </div>
                    `;
                    
                    // Ascunde formularul de checkout
                    document.getElementById('checkout-form').style.display = 'none';
                }
                
                // Actualizare total
                updateCartTotal();
                
                showAlert('Produsul a fost eliminat din coș.', 'success', 3000);
                
                // Reîncarcă pagina pentru a actualiza headerul
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Eroare:', error);
            showAlert('Eroare la comunicarea cu serverul.', 'error');
        });
    }
}

// Funcție pentru golirea coșului
function clearCart() {
    if (confirm('Sunteți sigur că doriți să goliți coșul de cumpărături?')) {
        fetch('clear_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reîncarcă pagina
                window.location.reload();
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Eroare:', error);
            showAlert('Eroare la comunicarea cu serverul.', 'error');
        });
    }
}
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>