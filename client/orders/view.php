<?php
// client/orders/view.php
// Pagina pentru vizualizarea detaliilor unei comenzi

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/Order.php';
require_once '../../classes/DeliveryNote.php';

// Verificare ID comandă
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID comandă invalid.');
    redirect('index.php');
}

$order_id = (int)$_GET['id'];

// Inițializare obiecte
$orderObj = new Order();
$deliveryNoteObj = new DeliveryNote();

// Obține detaliile comenzii
$order = $orderObj->getOrderById($order_id);

// Verificare existență comandă
if (!$order) {
    setFlashMessage('error', 'Comanda nu există.');
    redirect('index.php');
}

// Verificare dacă comanda aparține clientului
if ($order['client_id'] != $_SESSION['client_id']) {
    setFlashMessage('error', 'Nu aveți acces la această comandă.');
    redirect('index.php');
}

// Obține detaliile comenzii
$orderDetails = $orderObj->getOrderDetails($order_id);

// Obține avizele asociate comenzii
$deliveryNotes = $deliveryNoteObj->getOrderDeliveryNotes($order_id);

// Titlu pagină
$pageTitle = 'Comandă #' . $order['order_number'] . ' - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de comenzi
    </a>
</div>

<!-- Antet comandă -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-900">
            Comandă #<?php echo $order['order_number']; ?>
            
            <?php
            $statusClass = '';
            $statusText = '';
            
            switch ($order['status']) {
                case 'pending':
                    $statusClass = 'bg-yellow-100 text-yellow-800';
                    $statusText = 'În așteptare';
                    break;
                case 'approved':
                    $statusClass = 'bg-green-100 text-green-800';
                    $statusText = 'Aprobată';
                    break;
                case 'rejected':
                    $statusClass = 'bg-red-100 text-red-800';
                    $statusText = 'Respinsă';
                    break;
                case 'completed':
                    $statusClass = 'bg-blue-100 text-blue-800';
                    $statusText = 'Finalizată';
                    break;
            }
            ?>
            <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                <?php echo $statusText; ?>
            </span>
        </h1>
        
        <div>
            <a href="../products/index.php" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-shopping-cart mr-1"></i> Comandă nouă
            </a>
        </div>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded-md">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Informații comandă</h2>
                <div class="space-y-1">
                    <p><span class="font-medium">Data:</span> <?php echo formatDate($order['order_date'], true); ?></p>
                    <p><span class="font-medium">Utilizator:</span> <?php echo htmlspecialchars($order['user_name']); ?></p>
                    <?php if ($order['agent_name']): ?>
                        <p><span class="font-medium">Agent:</span> <?php echo htmlspecialchars($order['agent_name']); ?></p>
                        <p><span class="font-medium">Data aprobării:</span> <?php echo formatDate($order['approval_date'], true); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-md">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Locație livrare</h2>
                <div class="space-y-1">
                    <p><span class="font-medium"><?php echo htmlspecialchars($order['location_name']); ?></span></p>
                    <p><?php echo htmlspecialchars($order['location_address'] ?? ''); ?></p>
                </div>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-md">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Sumar financiar</h2>
                <div class="space-y-1">
                    <p><span class="font-medium">Total:</span> <span class="text-lg"><?php echo formatAmount($order['total_amount']); ?> Lei</span></p>
                    <p><span class="font-medium">Status plată:</span> <span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs">În așteptare</span></p>
                </div>
            </div>
        </div>
        
        <?php if (!empty($order['notes'])): ?>
            <div class="mb-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Note comandă</h2>
                <div class="bg-gray-50 p-4 rounded-md">
                    <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Produse comandă -->
        <div class="mb-6">
            <h2 class="text-sm font-medium text-gray-500 uppercase mb-4">Produse comandate</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Cod
                            </th>
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
                        <?php foreach ($orderDetails as $detail): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($detail['code']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($detail['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $detail['quantity']; ?> <?php echo htmlspecialchars($detail['unit']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatAmount($detail['unit_price']); ?> Lei
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatAmount($detail['amount']); ?> Lei
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right text-sm font-medium text-gray-900">
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
        
        <!-- Avize de livrare -->
        <?php if (count($deliveryNotes) > 0): ?>
            <div>
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-4">Avize de livrare</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Număr aviz
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acțiuni
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($deliveryNotes as $note): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $note['delivery_note_number']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($note['delivery_date'], true); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $note['status'] == 'delivered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $note['status'] == 'delivered' ? 'Livrat' : 'În livrare'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="../delivery-notes/view.php?id=<?php echo $note['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i> Vizualizare
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Butoane acțiuni -->
<div class="flex justify-between mt-6">
    <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la comenzi
    </a>
    
    <div>
        <a href="#" onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md ml-2">
            <i class="fas fa-print mr-1"></i> Printează
        </a>
        
        <a href="../products/index.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md ml-2">
            <i class="fas fa-shopping-cart mr-1"></i> Comandă nouă
        </a>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?><?php
// view.php
