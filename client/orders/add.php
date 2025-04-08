<?php
// client/orders/add.php
// Pagina pentru adăugarea manuală a unei comenzi

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/Order.php';
require_once '../../classes/Client.php';
require_once '../../classes/Product.php';
require_once '../../classes/Notification.php';

// Inițializare obiecte
$orderObj = new Order();
$clientObj = new Client();
$productObj = new Product();
$notificationObj = new Notification();

// Obține locațiile disponibile pentru client
$locations = $clientObj->getClientLocations($_SESSION['client_id']);

// Verifică dacă există locații
if (count($locations) == 0) {
    setFlashMessage('error', 'Nu există locații disponibile pentru plasarea comenzii. Contactați administratorul.');
    redirect('index.php');
}

// Obține produsele disponibile
$products = $productObj->getActiveProducts();

// Pregătește array-ul pentru produse cu prețurile specifice clientului
$clientProducts = [];
foreach ($products as $product) {
    $clientPrice = $productObj->getClientPrice($_SESSION['client_id'], $product['id']);
    $product['client_price'] = $clientPrice;
    $clientProducts[] = $product;
}

// Procesare formular
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Verificare date de bază
        if (!isset($_POST['location_id']) || !is_numeric($_POST['location_id'])) {
            $error = 'Vă rugăm să selectați o locație de livrare.';
        } else {
            $location_id = (int)$_POST['location_id'];
            
            // Verifică dacă locația aparține clientului
            $locationValid = false;
            foreach ($locations as $location) {
                if ($location['id'] == $location_id) {
                    $locationValid = true;
                    break;
                }
            }
            
            if (!$locationValid) {
                $error = 'Locația selectată nu este validă.';
            } else {
                // Verificare produse
                if (!isset($_POST['product_ids']) || !is_array($_POST['product_ids']) || count($_POST['product_ids']) === 0) {
                    $error = 'Vă rugăm să selectați cel puțin un produs.';
                } else {
                    $product_ids = $_POST['product_ids'];
                    $quantities = $_POST['quantities'] ?? [];
                    $notes = sanitizeInput($_POST['notes'] ?? '');
                    
                    $orderProducts = [];
                    $total_amount = 0;
                    
                    // Validare și pregătire produse pentru comandă
                    foreach ($product_ids as $key => $product_id) {
                        if (!isset($quantities[$key]) || !is_numeric($quantities[$key]) || $quantities[$key] <= 0) {
                            $error = 'Cantitatea pentru fiecare produs trebuie să fie un număr pozitiv.';
                            break;
                        }
                        
                        $product_id = (int)$product_id;
                        $quantity = (float)$quantities[$key];
                        
                        // Verifică dacă produsul există și este activ
                        $product = $productObj->getProductById($product_id);
                        if (!$product || $product['status'] !== 'active') {
                            $error = 'Unul dintre produsele selectate nu este disponibil.';
                            break;
                        }
                        
                        // Obține prețul specific clientului
                        $price = $productObj->getClientPrice($_SESSION['client_id'], $product_id);
                        
                        // Calculează suma
                        $amount = $price * $quantity;
                        $total_amount += $amount;
                        
                        // Adaugă produsul în lista pentru comandă
                        $orderProducts[] = [
                            'product_id' => $product_id,
                            'quantity' => $quantity,
                            'unit_price' => $price
                        ];
                    }
                    
                    // Dacă nu sunt erori, procesăm comanda
                    if (empty($error)) {
                        // Pregătire date comandă
                        $orderData = [
                            'client_id' => $_SESSION['client_id'],
                            'location_id' => $location_id,
                            'user_id' => $_SESSION['user_id'],
                            'notes' => $notes,
                            'total_amount' => $total_amount,
                            'products' => $orderProducts
                        ];
                        
                        // Adaugă comanda
                        $order_id = $orderObj->addOrder($orderData);
                        
                        if ($order_id) {
                            // Obține detaliile complete ale comenzii pentru notificare
                            $order = $orderObj->getOrderById($order_id);
                            
                            // Creare notificare pentru administratori
                            $notificationObj->createOrderNotification(
                                $order_id, 
                                $order['order_number'],
                                $_SESSION['client_id']
                            );
                            
                            setFlashMessage('success', 'Comanda a fost plasată cu succes.');
                            redirect('view.php?id=' . $order_id);
                        } else {
                            $error = 'A apărut o eroare la plasarea comenzii. Vă rugăm să încercați din nou.';
                        }
                    }
                }
            }
        }
    }
}

// Titlu pagină
$pageTitle = 'Adaugă comandă - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de comenzi
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Adaugă comandă nouă</h1>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $success; ?></span>
    </div>
<?php endif; ?>

<form id="order-form" action="add.php" method="POST" class="space-y-6">
    <!-- CSRF token -->
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <!-- Secțiunea de locație -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Locație livrare</h2>
        </div>
        
        <div class="p-6">
            <div class="max-w-lg">
                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">Selectează locația de livrare</label>
                <select id="location_id" name="location_id" required
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">-- Selectează locația --</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>" <?php echo ($_SESSION['location_id'] == $location['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($location['name']); ?> - <?php echo htmlspecialchars($location['address']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div id="location-details" class="mt-4 p-4 bg-gray-50 rounded-md hidden">
                    <!-- Detaliile locației vor fi încărcate aici prin JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Secțiunea de produse -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Produse</h2>
        </div>
        
        <div class="p-6">
            <div class="mb-4">
                <label for="product-search" class="block text-sm font-medium text-gray-700 mb-1">Caută produse</label>
                <div class="relative">
                    <input type="text" id="product-search" placeholder="Caută după nume sau cod produs..." 
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <div id="products-container" class="space-y-4 mb-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Selectează
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cod
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Produs
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    U.M.
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Preț
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cantitate
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="products-list">
                            <?php foreach ($clientProducts as $product): ?>
                                <tr class="product-row" data-product-id="<?php echo $product['id']; ?>" data-product-name="<?php echo htmlspecialchars($product['name']); ?>" data-product-code="<?php echo htmlspecialchars($product['code']); ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>" class="product-checkbox focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($product['code']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <?php if (!empty($product['category_name'])): ?>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($product['category_name']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($product['unit']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 product-price" data-price="<?php echo $product['client_price']; ?>">
                                        <?php echo formatAmount($product['client_price']); ?> Lei
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="number" name="quantities[]" value="1" min="0.1" step="0.1" 
                                               class="product-quantity block w-20 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" disabled>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 product-total">
                                        <?php echo formatAmount($product['client_price']); ?> Lei
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                <div class="text-lg font-bold">
                    Total comandă: <span id="order-total">0.00</span> Lei
                </div>
                
                <div>
                    <button type="button" id="select-all" class="text-blue-600 hover:text-blue-800 mr-4">
                        <i class="fas fa-check-square mr-1"></i> Selectează toate
                    </button>
                    <button type="button" id="clear-all" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times-square mr-1"></i> Deselectează toate
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Secțiunea de note -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Note comandă</h2>
        </div>
        
        <div class="p-6">
            <textarea id="notes" name="notes" rows="3" placeholder="Adăugați observații pentru această comandă..."
                      class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
        </div>
    </div>
    
    <!-- Buton trimitere -->
    <div class="flex justify-end space-x-3">
        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Anulează
        </a>
        <button type="submit" id="submit-order" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Plasează comanda
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referințe la elemente
    const locationSelect = document.getElementById('location_id');
    const locationDetails = document.getElementById('location-details');
    const productSearch = document.getElementById('product-search');
    const productRows = document.querySelectorAll('.product-row');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const selectAllBtn = document.getElementById('select-all');
    const clearAllBtn = document.getElementById('clear-all');
    const orderTotal = document.getElementById('order-total');
    const orderForm = document.getElementById('order-form');
    
    // Funcție pentru afișarea detaliilor locației
    function showLocationDetails() {
        const locationId = locationSelect.value;
        if (!locationId) {
            locationDetails.classList.add('hidden');
            return;
        }
        
        // Obține detaliile locației prin AJAX
        fetch(`../locations/get_location_info.php?id=${locationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = `<div class="text-sm">`;
                    html += `<p class="font-medium">${data.location.name}</p>`;
                    html += `<p>${data.location.address}</p>`;
                    
                    if (data.location.contact_person) {
                        html += `<p class="mt-2"><span class="font-medium">Contact:</span> ${data.location.contact_person}</p>`;
                    }
                    
                    if (data.location.phone) {
                        html += `<p><span class="font-medium">Telefon:</span> ${data.location.phone}</p>`;
                    }
                    
                    html += `</div>`;
                    
                    locationDetails.innerHTML = html;
                    locationDetails.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    // Ascultător pentru schimbarea locației
    if (locationSelect) {
        locationSelect.addEventListener('change', showLocationDetails);
        // Afișează detaliile locației inițiale dacă este selectată
        if (locationSelect.value) {
            showLocationDetails();
        }
    }
    
    // Funcție pentru căutarea produselor
    function filterProducts() {
        const searchTerm = productSearch.value.toLowerCase();
        
        productRows.forEach(row => {
            const productName = row.getAttribute('data-product-name').toLowerCase();
            const productCode = row.getAttribute('data-product-code').toLowerCase();
            
            if (productName.includes(searchTerm) || productCode.includes(searchTerm) || searchTerm === '') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Ascultător pentru căutarea produselor
    if (productSearch) {
        productSearch.addEventListener('input', filterProducts);
    }
    
    // Funcție pentru actualizarea stării câmpurilor de cantitate
    function updateQuantityFields() {
        document.querySelectorAll('.product-checkbox').forEach(checkbox => {
            const row = checkbox.closest('.product-row');
            const quantityInput = row.querySelector('.product-quantity');
            
            if (checkbox.checked) {
                quantityInput.disabled = false;
            } else {
                quantityInput.disabled = true;
            }
        });
        
        // Actualizare total comandă
        calculateOrderTotal();
    }
    
    // Funcție pentru calcularea totalului comenzii
    function calculateOrderTotal() {
        let total = 0;
        
        document.querySelectorAll('.product-checkbox:checked').forEach(checkbox => {
            const row = checkbox.closest('.product-row');
            const price = parseFloat(row.querySelector('.product-price').getAttribute('data-price'));
            const quantity = parseFloat(row.querySelector('.product-quantity').value);
            const itemTotal = price * quantity;
            
            // Actualizează totalul produsului
            row.querySelector('.product-total').textContent = formatAmount(itemTotal) + ' Lei';
            
            total += itemTotal;
        });
        
        orderTotal.textContent = formatAmount(total);
    }
    
    // Funcție pentru formatarea sumelor
    function formatAmount(amount) {
        return amount.toLocaleString('ro-RO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Ascultători pentru checkbox-uri produse
    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateQuantityFields);
    });
    
    // Ascultători pentru cantități
    document.querySelectorAll('.product-quantity').forEach(input => {
        input.addEventListener('input', calculateOrderTotal);
    });
    
    // Ascultător pentru butonul "Selectează toate"
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            productCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('.product-row');
                if (row.style.display !== 'none') {  // Selectează doar produsele vizibile
                    checkbox.checked = true;
                }
            });
            updateQuantityFields();
        });
    }
    
    // Ascultător pentru butonul "Deselectează toate"
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function() {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateQuantityFields();
        });
    }
    
    // Validare formular înainte de trimitere
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            const selectedProducts = document.querySelectorAll('.product-checkbox:checked');
            
            if (!locationSelect.value) {
                e.preventDefault();
                alert('Vă rugăm să selectați o locație de livrare.');
                return;
            }
            
            if (selectedProducts.length === 0) {
                e.preventDefault();
                alert('Vă rugăm să selectați cel puțin un produs.');
                return;
            }
            
            // Verifică dacă toate cantitățile sunt valide
            let validQuantities = true;
            selectedProducts.forEach(checkbox => {
                const row = checkbox.closest('.product-row');
                const quantityInput = row.querySelector('.product-quantity');
                const quantity = parseFloat(quantityInput.value);
                
                if (isNaN(quantity) || quantity <= 0) {
                    validQuantities = false;
                }
            });
            
            if (!validQuantities) {
                e.preventDefault();
                alert('Vă rugăm să introduceți cantități valide pentru toate produsele selectate.');
                return;
            }
        });
    }
    
    // Inițializare
    updateQuantityFields();
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>