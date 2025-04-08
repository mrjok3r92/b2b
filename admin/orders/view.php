<?php
// admin/orders/view.php
// Pagina pentru vizualizarea detaliilor unei comenzi

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Order.php';
require_once '../../classes/Client.php';
require_once '../../classes/DeliveryNote.php';

// Verificare ID comandă
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID comandă invalid.');
    redirect('index.php');
}

$order_id = (int)$_GET['id'];

// Inițializare obiecte
$orderObj = new Order();
$clientObj = new Client();
$deliveryNoteObj = new DeliveryNote();

// Obține detaliile comenzii
$order = $orderObj->getOrderById($order_id);

// Verificare existență comandă
if (!$order) {
    setFlashMessage('error', 'Comanda nu există.');
    redirect('index.php');
}

// Obține detaliile comenzii
$orderDetails = $orderObj->getOrderDetails($order_id);

// Obține avizele asociate comenzii
$deliveryNotes = $deliveryNoteObj->getOrderDeliveryNotes($order_id);

// Obține locația
$location = $clientObj->getLocationById($order['location_id']);

// Obține utilizatorul care a plasat comanda
require_once '../../classes/User.php';
$userObj = new User();
$user = $userObj->getUserById($order['user_id']);

// Obține agentul care a aprobat comanda (dacă există)
$agent = null;
if ($order['agent_id']) {
    $agent = $userObj->getUserById($order['agent_id']);
}

// Titlu pagină
$pageTitle = 'Vizualizare Comandă #' . $order['order_number'] . ' - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de comenzi
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
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
        
        <div class="flex space-x-2">
            <?php if ($order['status'] === 'pending'): ?>
                <a href="approve.php?id=<?php echo $order['id']; ?>" class="px-3 py-1 bg-green-600 text-white rounded-md text-sm">
                    <i class="fas fa-check mr-1"></i> Aprobă
                </a>
                
                <a href="reject.php?id=<?php echo $order['id']; ?>" class="px-3 py-1 bg-red-600 text-white rounded-md text-sm">
                    <i class="fas fa-times mr-1"></i> Respinge
                </a>
            <?php elseif ($order['status'] === 'approved'): ?>
                <a href="../delivery-notes/add.php?order_id=<?php echo $order['id']; ?>" class="px-3 py-1 bg-indigo-600 text-white rounded-md text-sm">
                    <i class="fas fa-truck mr-1"></i> Generează aviz
                </a>
            <?php endif; ?>
            
            <a href="print.php?id=<?php echo $order['id']; ?>" class="px-3 py-1 bg-gray-600 text-white rounded-md text-sm">
                <i class="fas fa-print mr-1"></i> Printează
            </a>
        </div>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Informații comandă -->
            <div class="bg-gray-50 p-4 rounded-md">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Informații comandă</h2>
                <div class="space-y-1">
                    <p><span class="font-medium">Data comenzii:</span> <?php echo formatDate($order['order_date'], true); ?></p>
                    <p><span class="font-medium">Status:</span> <?php echo $statusText; ?></p>
                    
                    <?php if ($order['status'] == 'approved' || $order['status'] == 'completed'): ?>
                        <p><span class="font-medium">Data aprobării:</span> <?php echo formatDate($order['approval_date'], true); ?></p>
                        <?php if ($agent): ?>
                            <p><span class="font-medium">Aprobat de:</span> <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($user): ?>
                        <p><span class="font-medium">Comandă plasată de:</span> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informații client -->
            <div class="bg-gray-50 p-4 rounded-md">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Informații client</h2>
                <div class="space-y-1">
                    <p class="font-medium"><?php echo htmlspecialchars($order['company_name']); ?></p>
                    <p><span class="font-medium">Cod fiscal:</span> <?php echo htmlspecialchars($order['fiscal_code'] ?? ''); ?></p>
                    <p><span class="font-medium">Telefon:</span> <?php echo htmlspecialchars($order['phone'] ?? ''); ?></p>
                    <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($order['email'] ?? ''); ?></p>
                </div>
            </div>
            
            <!-- Informații livrare -->
            <div class="bg-gray-50 p-4 rounded-md">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Informații livrare</h2>
                <div class="space-y-1">
                    <p class="font-medium"><?php echo htmlspecialchars($location['name'] ?? ''); ?></p>
                    <p><?php echo htmlspecialchars($location['address'] ?? ''); ?></p>
                    
                    <?php if (!empty($location['contact_person'])): ?>
                        <p><span class="font-medium">Persoană de contact:</span> <?php echo htmlspecialchars($location['contact_person']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($location['phone'])): ?>
                        <p><span class="font-medium">Telefon:</span> <?php echo htmlspecialchars($location['phone']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($location['email'])): ?>
                        <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($location['email']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($order['notes'])): ?>
            <!-- Note comandă -->
            <div class="mb-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Note comandă</h2>
                <div class="bg-gray-50 p-4 rounded-md">
                    <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Produse comandate -->
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($detail['code']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($detail['name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $detail['quantity']; ?> <?php echo htmlspecialchars($detail['unit']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatAmount($detail['unit_price']); ?> Lei
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
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
            <div class="mb-6">
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
                                        <a href="../delivery-notes/view.php?id=<?php echo $note['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-eye"></i> Vezi
                                        </a>
                                        
                                        <?php if ($note['status'] != 'delivered'): ?>
                                            <a href="../delivery-notes/mark_delivered.php?id=<?php echo $note['id']; ?>" class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-check"></i> Marchează ca livrat
                                            </a>
                                        <?php endif; ?>
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

<?php
// Include footer
include_once '../../includes/footer.php';
?>