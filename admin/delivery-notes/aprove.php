<?php
// admin/orders/approve.php
// Pagină pentru aprobarea comenzilor și generarea automată a avizelor

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Order.php';
require_once '../../classes/DeliveryNote.php';

// Inițializare obiecte
$orderObj = new Order();
$deliveryNoteObj = new DeliveryNote();

// Procesare aprobare
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        $order_ids = $_POST['order_ids'] ?? [];
        $generate_delivery_notes = isset($_POST['generate_delivery_notes']) && $_POST['generate_delivery_notes'] == 1;
        
        if (empty($order_ids)) {
            $error = 'Nu ați selectat nicio comandă pentru aprobare.';
        } else {
            $approved_count = 0;
            $delivery_notes_count = 0;
            $failed_count = 0;
            
            foreach ($order_ids as $order_id) {
                // Aprobă comanda
                $result = $orderObj->approveOrder($order_id, $_SESSION['user_id']);
                
                if ($result) {
                    $approved_count++;
                    
                    // Generează aviz automat dacă opțiunea este selectată
                    if ($generate_delivery_notes) {
                        // Obține detaliile comenzii
                        $order = $orderObj->getOrderById($order_id);
                        
                        // Generează aviz
                        if ($order) {
                            // Obține următorul număr de aviz
                            $nextNumber = $deliveryNoteObj->getNextDeliveryNoteNumber('AVL');
                            
                            $deliveryNoteData = [
                                'order_id' => $order_id,
                                'client_id' => $order['client_id'],
                                'location_id' => $order['location_id'],
                                'series' => 'AVL',
                                'delivery_note_number' => $nextNumber,
                                'issue_date' => date('Y-m-d'),
                                'status' => 'draft',
                                'notes' => 'Aviz generat automat pentru comanda #' . $order['order_number'],
                                'created_by' => $_SESSION['user_id']
                            ];
                            
                            $delivery_note_id = $deliveryNoteObj->addDeliveryNote($deliveryNoteData);
                            
                            if ($delivery_note_id) {
                                // Obține toate produsele din comandă
                                $db = new Database();
                                $db->query('SELECT od.*, p.code as product_code, p.name as product_name, p.unit 
                                            FROM order_details od 
                                            JOIN products p ON od.product_id = p.id 
                                            WHERE od.order_id = :order_id');
                                $db->bind(':order_id', $order_id);
                                $orderItems = $db->resultSet();
                                
                                // Adaugă toate produsele în aviz
                                $allItemsAdded = true;
                                foreach ($orderItems as $item) {
                                    $itemData = [
                                        'delivery_note_id' => $delivery_note_id,
                                        'order_item_id' => $item['id'],
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
                                    $delivery_notes_count++;
                                }
                            }
                        }
                    }
                } else {
                    $failed_count++;
                }
            }
            
            // Construiește mesaj de succes
            if ($approved_count > 0) {
                $success = "$approved_count comenzi au fost aprobate cu succes.";
                if ($generate_delivery_notes && $delivery_notes_count > 0) {
                    $success .= " Au fost generate $delivery_notes_count avize de livrare.";
                }
            }
            
            // Construiește mesaj de eroare
            if ($failed_count > 0) {
                $error = "$failed_count comenzi nu au putut fi aprobate.";
            }
        }
    }
}

// Obține comenzile în așteptare
$pendingOrders = $orderObj->getOrdersByStatus('pending');

// Titlu pagină
$pageTitle = 'Aprobare Comenzi - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="../orders/index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de comenzi
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Aprobare Comenzi</h1>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $success; ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Comenzi în așteptare</h2>
    </div>
    
    <?php if (count($pendingOrders) > 0): ?>
        <form method="POST" action="approve.php">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="select-all" class="text-sm font-medium text-gray-700">Selectează toate</label>
                </div>
                
                <div class="mt-3">
                    <div class="flex items-center mb-2">
                        <input type="checkbox" id="generate_delivery_notes" name="generate_delivery_notes" value="1" checked class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="generate_delivery_notes" class="ml-2 text-sm font-medium text-gray-700">Generează automat avize de livrare pentru comenzile aprobate</label>
                    </div>
                    <p class="text-xs text-gray-500">Avizele vor include toate produsele din comandă și vor avea status "Ciornă"</p>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-check mr-1"></i> Aprobă comenzile selectate
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nr. Comandă</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Locație</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valoare</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilizator</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pendingOrders as $order): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="order_ids[]" value="<?php echo $order['id']; ?>" class="order-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="../orders/view.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        <?php echo htmlspecialchars($order['order_number']); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d.m.Y', strtotime($order['order_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['company_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($order['location_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatAmount($order['total_amount']); ?> Lei
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($order['user_name']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php else: ?>
        <div class="p-6 text-center text-gray-500">
            <i class="fas fa-clipboard-list fa-3x text-gray-300 mb-3"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Nu există comenzi în așteptare</h3>
            <p class="text-gray-600 mb-4">Toate comenzile au fost procesate.</p>
            <a href="../orders/index.php" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de comenzi
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            orderCheckboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }
    
    // Check if any checkboxes are selected before submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(event) {
            const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                event.preventDefault();
                alert('Vă rugăm să selectați cel puțin o comandă pentru aprobare.');
            }
        });
    }
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>