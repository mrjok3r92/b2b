<?php
// admin/orders/approve.php
// Pagina pentru aprobarea unei comenzi

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Order.php';
require_once '../../classes/Notification.php';

// Verificare ID comandă
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID comandă invalid.');
    redirect('index.php');
}

$order_id = (int)$_GET['id'];

// Inițializare obiecte
$orderObj = new Order();
$notificationObj = new Notification();

// Obține detaliile comenzii
$order = $orderObj->getOrderById($order_id);

// Verificare existență comandă
if (!$order) {
    setFlashMessage('error', 'Comanda nu există.');
    redirect('index.php');
}

// Verificare dacă comanda este în așteptare
if ($order['status'] !== 'pending') {
    setFlashMessage('error', 'Această comandă nu este în așteptare și nu poate fi aprobată.');
    redirect('view.php?id=' . $order_id);
}

// Procesare formular
$error = '';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Obține notele
        $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';
        
        // Aprobă comanda
        $result = $orderObj->approveOrder($order_id, $_SESSION['user_id'], $notes);
        
        if ($result) {
            // Creare notificare
            $notificationObj->createOrderApprovedNotification(
                $order_id, 
                $order['order_number'], 
                $order['client_id'], 
                $order['user_id']
            );
            
            setFlashMessage('success', 'Comanda a fost aprobată cu succes.');
            redirect('view.php?id=' . $order_id);
        } else {
            $error = 'A apărut o eroare la aprobarea comenzii. Vă rugăm să încercați din nou.';
        }
    }
}

// Titlu pagină
$pageTitle = 'Aprobare Comandă #' . $order['order_number'] . ' - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="view.php?id=<?php echo $order_id; ?>" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la detalii comandă
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h1 class="text-xl font-bold text-gray-900">Aprobare comandă #<?php echo $order['order_number']; ?></h1>
    </div>
    
    <div class="p-6">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <div class="mb-6">
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Sunteți pe cale să aprobați comanda <strong>#<?php echo $order['order_number']; ?></strong> în valoare de 
                            <strong><?php echo formatAmount($order['total_amount']); ?> Lei</strong> pentru clientul 
                            <strong><?php echo htmlspecialchars($order['company_name']); ?></strong>.
                        </p>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="approve.php?id=<?php echo $order_id; ?>">
                <!-- CSRF token -->
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <!-- Note aprobare -->
                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Note (opțional)</label>
                    <textarea id="notes" name="notes" rows="3" 
                              class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                              placeholder="Adăugați note sau comentarii pentru această aprobare..."><?php echo htmlspecialchars($notes); ?></textarea>
                    <p class="mt-1 text-xs text-gray-500">Aceste note vor fi vizibile pentru client în detaliile comenzii.</p>
                </div>
                
                <!-- Butoane -->
                <div class="flex justify-end space-x-3">
                    <a href="view.php?id=<?php echo $order_id; ?>" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Anulează
                    </a>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-check mr-1"></i> Aprobă comanda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sumar comandă -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Sumar comandă</h2>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Informații client</h3>
                <div class="bg-gray-50 p-4 rounded-md">
                    <p class="font-medium"><?php echo htmlspecialchars($order['company_name']); ?></p>
                    <p>Cod fiscal: <?php echo htmlspecialchars($order['fiscal_code'] ?? ''); ?></p>
                    <p>Comandă plasată la: <?php echo formatDate($order['order_date'], true); ?></p>
                </div>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Informații livrare</h3>
                <div class="bg-gray-50 p-4 rounded-md">
                    <p class="font-medium"><?php echo htmlspecialchars($order['location_name']); ?></p>
                    <p><?php echo htmlspecialchars($order['location_address'] ?? ''); ?></p>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Produse comandate</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Produs
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Cantitate
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Preț unitar
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $orderDetails = $orderObj->getOrderDetails($order_id);
                        foreach ($orderDetails as $detail): 
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($detail['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($detail['code']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $detail['quantity']; ?> <?php echo htmlspecialchars($detail['unit']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatAmount($detail['unit_price']); ?> Lei
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo formatAmount($detail['amount']); ?> Lei
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-900">
                                Total:
                            </td>
                            <td class="px-6 py-3 text-left text-sm font-bold text-gray-900">
                                <?php echo formatAmount($order['total_amount']); ?> Lei
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>