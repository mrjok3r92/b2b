<?php

require_once '../../includes/auth.php';
authenticateAdmin();

// Include necessary files
require_once '../../classes/Order.php';
require_once '../../classes/DeliveryNote.php';
require_once '../../classes/Product.php';
require_once '../../classes/User.php';
require_once '../../classes/Client.php';
require_once '../../classes/Location.php';

// Initialize objects
$orderObj = new Order();
$deliveryNoteObj = new DeliveryNote();
$productObj = new Product();
$userObj = new User();
$clientObj = new Client();
$locationObj = new Location();

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Verify order exists and is approved
$order = $orderObj->getOrderById($order_id);
if (!$order || $order['status'] !== 'approved') {
    setFlashMessage('error', 'Comanda nu există sau nu este aprobată pentru livrare.');
    redirect('index.php');
}

// Get order details directly
$db = new Database();
$db->query('SELECT od.*, p.code as product_code, p.name as product_name, p.unit 
            FROM order_details od 
            JOIN products p ON od.product_id = p.id 
            WHERE od.order_id = :order_id');
$db->bind(':order_id', $order_id);
$orderItems = $db->resultSet();

// Get client information
$client = $clientObj->getClientById($order['client_id']);

// Get location information
$location = $locationObj->getLocationById($order['location_id']);

// Process form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Get form data
        $series = sanitizeInput($_POST['series'] ?? '');
        $delivery_note_number = sanitizeInput($_POST['delivery_note_number'] ?? '');
        $issue_date = sanitizeInput($_POST['issue_date'] ?? '');
        $notes = sanitizeInput($_POST['notes'] ?? '');
        $selected_items = $_POST['items'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        
        // Validate data
        $errors = [];
        
        if (empty($series)) {
            $errors[] = 'Seria avizului este obligatorie.';
        }
        
        if (empty($delivery_note_number)) {
            $errors[] = 'Numărul avizului este obligatoriu.';
        }
        
        if (empty($issue_date)) {
            $errors[] = 'Data emiterii este obligatorie.';
        } elseif (!validateDate($issue_date)) {
            $errors[] = 'Formatul datei de emitere este invalid.';
        }
        
        if (empty($selected_items)) {
            $errors[] = 'Trebuie să selectați cel puțin un produs pentru aviz.';
        }
        
        // Validate quantities
        $validItems = [];
        foreach ($selected_items as $index => $item_id) {
            if (!isset($quantities[$item_id]) || $quantities[$item_id] <= 0) {
                $errors[] = 'Cantitatea pentru fiecare produs trebuie să fie mai mare decât 0.';
                break;
            }
            
            // Get item details
            $db->query('SELECT * FROM order_details WHERE id = :id');
            $db->bind(':id', $item_id);
            $orderItem = $db->single();
            
            // Get delivered quantity
            $db->query('SELECT COALESCE(SUM(quantity), 0) as delivered_qty 
                        FROM delivery_note_items 
                        JOIN delivery_notes ON delivery_note_items.delivery_note_id = delivery_notes.id 
                        WHERE delivery_note_items.order_item_id = :order_item_id 
                        AND delivery_notes.status != "cancelled"');
            $db->bind(':order_item_id', $item_id);
            $result = $db->single();
            $deliveredQuantity = $result['delivered_qty'];
            
            $remainingQuantity = $orderItem['quantity'] - $deliveredQuantity;
            
            if ($quantities[$item_id] > $remainingQuantity) {
                $errors[] = 'Cantitatea pentru produsul ' . $orderItem['product_name'] . ' depășește cantitatea rămasă de livrat.';
                break;
            }
            
            // Get product details
            $db->query('SELECT code, name, unit FROM products WHERE id = :id');
            $db->bind(':id', $orderItem['product_id']);
            $product = $db->single();
            
            $validItems[] = [
                'order_item_id' => $item_id,
                'product_id' => $orderItem['product_id'],
                'product_code' => $product['code'],
                'product_name' => $product['name'],
                'unit' => $product['unit'],
                'quantity' => $quantities[$item_id],
                'unit_price' => $orderItem['unit_price']
            ];
        }
        
        // If no errors, create delivery note
        if (empty($errors)) {
            $deliveryNoteData = [
                'order_id' => $order_id,
                'client_id' => $order['client_id'],
                'location_id' => $order['location_id'],
                'series' => $series,
                'delivery_note_number' => $delivery_note_number,
                'issue_date' => $issue_date,
                'status' => 'draft',
                'notes' => $notes,
                'created_by' => $_SESSION['user_id']
            ];
            
            $delivery_note_id = $deliveryNoteObj->addDeliveryNote($deliveryNoteData);
            
            if ($delivery_note_id) {
                // Add delivery note items
                $allItemsAdded = true;
                foreach ($validItems as $item) {
                    $itemData = [
                        'delivery_note_id' => $delivery_note_id,
                        'order_item_id' => $item['order_item_id'],
                        'product_id' => $item['product_id'],
                        'product_code' => $item['product_code'],
                        'product_name' => $item['product_name'],
                        'unit' => $item['unit'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price']
                    ];
                    
                    if (!$deliveryNoteObj->addDeliveryNoteItem($itemData)) {
                        $allItemsAdded = false;
                    }
                }
                
                if ($allItemsAdded) {
                    setFlashMessage('success', 'Avizul de livrare a fost creat cu succes.');
                    redirect('view.php?id=' . $delivery_note_id);
                } else {
                    $error = 'A apărut o eroare la adăugarea produselor în aviz.';
                    // Try to delete the delivery note if items could not be added
                    $deliveryNoteObj->deleteDeliveryNote($delivery_note_id);
                }
            } else {
                $error = 'A apărut o eroare la crearea avizului de livrare.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Get next delivery note number
$nextDeliveryNoteNumber = $deliveryNoteObj->getNextDeliveryNoteNumber('AVL');

// Page title
$pageTitle = 'Adăugare Aviz de Livrare - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de avize
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Adăugare Aviz de Livrare pentru Comanda #<?php echo htmlspecialchars($order['order_number']); ?></h1>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Order Information -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Informații Comandă</h2>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 block">Număr comandă:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($order['order_number']); ?></span>
                </div>
                <div>
                    <span class="text-gray-500 block">Data comenzii:</span>
                    <span class="font-medium"><?php echo date('d.m.Y', strtotime($order['order_date'])); ?></span>
                </div>
                <div>
                    <span class="text-gray-500 block">Status:</span>
                    <span class="font-medium">
                        <?php if ($order['status'] === 'approved'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Aprobată
                            </span>
                        <?php else: ?>
                            <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div>
                    <span class="text-gray-500 block">Valoare totală:</span>
                    <span class="font-medium"><?php echo formatAmount($order['total_amount']); ?> Lei</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Client Information -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Informații Client</h2>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 block">Denumire:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($client['company_name']); ?></span>
                </div>
                <div>
                    <span class="text-gray-500 block">Cod fiscal:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($client['fiscal_code']); ?></span>
                </div>
                <div>
                    <span class="text-gray-500 block">Adresă:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($client['address']); ?></span>
                </div>
                <div>
                    <span class="text-gray-500 block">Contact:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($client['phone']); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Location Information -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Informații Locație</h2>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 block">Nume locație:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($location['name']); ?></span>
                </div>
                <div>
                    <span class="text-gray-500 block">Adresă:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($location['address']); ?></span>
                </div>
                <div>
                    <span class="text-gray-500 block">Persoană contact:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($location['contact_person']); ?></span>
                </div>
                <div>
                    <span class="text-gray-500 block">Telefon:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($location['phone']); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="add.php?order_id=<?php echo $order_id; ?>">
    <!-- CSRF token -->
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Delivery Note Details -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold">Detalii Aviz</h2>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="series" class="block text-sm font-medium text-gray-700">Serie aviz <span class="text-red-500">*</span></label>
                        <input type="text" id="series" name="series" value="AVL" required
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="delivery_note_number" class="block text-sm font-medium text-gray-700">Număr aviz <span class="text-red-500">*</span></label>
                        <input type="text" id="delivery_note_number" name="delivery_note_number" value="<?php echo $nextDeliveryNoteNumber; ?>" required
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="issue_date" class="block text-sm font-medium text-gray-700">Data emitere <span class="text-red-500">*</span></label>
                        <input type="date" id="issue_date" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>
                <div class="mt-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700">Observații</label>
                    <textarea id="notes" name="notes" rows="3" 
                              class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Products from Order -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-semibold">Produse din Comandă</h2>
            <div>
                <button type="button" id="select-all" class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-2 rounded mr-2">
                    Selectează toate
                </button>
                <button type="button" id="deselect-all" class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-2 rounded">
                    Deselectează toate
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="toggle-all" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
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
                            Comandat
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Livrat Anterior
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rămas
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cantitate Aviz
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($orderItems as $item): ?>
                        <?php 
                        $deliveredQuantity = $deliveryNoteObj->getDeliveredQuantityForOrderItem($item['id']);
                        $remainingQuantity = $item['quantity'] - $deliveredQuantity;
                        if ($remainingQuantity <= 0) continue; // Skip fully delivered items
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="items[]" value="<?php echo $item['id']; ?>" class="item-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($item['product_code']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($item['unit']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $item['quantity']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $deliveredQuantity; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="remaining-quantity" data-value="<?php echo $remainingQuantity; ?>"><?php echo $remainingQuantity; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                       value="<?php echo $remainingQuantity; ?>" 
                                       min="0.01" step="0.01" max="<?php echo $remainingQuantity; ?>"
                                       data-item-id="<?php echo $item['id']; ?>"
                                       class="quantity-input focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                       style="width: 100px;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Submit Buttons -->
    <div class="flex justify-end space-x-3 mt-6">
        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Anulează
        </a>
        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-save mr-1"></i> Salvează aviz
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleAllCheckbox = document.getElementById('toggle-all');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const selectAllBtn = document.getElementById('select-all');
        const deselectAllBtn = document.getElementById('deselect-all');
        const quantityInputs = document.querySelectorAll('.quantity-input');
        
        // Toggle all checkboxes
        toggleAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            itemCheckboxes.forEach(function(checkbox) {
                checkbox.checked = isChecked;
            });
        });
        
        // Select all button
        selectAllBtn.addEventListener('click', function() {
            itemCheckboxes.forEach(function(checkbox) {
                checkbox.checked = true;
            });
            toggleAllCheckbox.checked = true;
        });
        
        // Deselect all button
        deselectAllBtn.addEventListener('click', function() {
            itemCheckboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
            toggleAllCheckbox.checked = false;
        });
        
        // Validate quantity inputs
        quantityInputs.forEach(function(input) {
            input.addEventListener('input', function() {
                const remainingElem = this.closest('tr').querySelector('.remaining-quantity');
                const remainingQty = parseFloat(remainingElem.dataset.value);
                const inputValue = parseFloat(this.value);
                
                if (inputValue < 0) {
                    this.value = 0;
                } else if (inputValue > remainingQty) {
                    this.value = remainingQty;
                }
            });
        });
        
        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            let hasCheckedItems = false;
            
            itemCheckboxes.forEach(function(checkbox) {
                if (checkbox.checked) {
                    hasCheckedItems = true;
                }
            });
            
            if (!hasCheckedItems) {
                event.preventDefault();
                alert('Trebuie să selectați cel puțin un produs pentru avizul de livrare.');
            }
        });
    });
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>