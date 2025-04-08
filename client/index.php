<?php
// client/index.php
// Pagina principală a panoului client

// Inițializare sesiune și autentificare client
require_once '../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../classes/Order.php';
require_once '../classes/Client.php';
require_once '../classes/Product.php';
require_once '../classes/User.php';
require_once '../classes/DeliveryNote.php';

// Inițializare obiecte
$orderObj = new Order();
$clientObj = new Client();
$deliveryNoteObj = new DeliveryNote();

// Obține informațiile clientului
$clientData = $clientObj->getClientById($_SESSION['client_id']);

// Obține ultimele comenzi
$clientOrders = $orderObj->getClientOrders($_SESSION['client_id']);
$recentOrders = array_slice($clientOrders, 0, 5);

// Obține ultimele avize
$clientDeliveryNotes = $deliveryNoteObj->getClientDeliveryNotes($_SESSION['client_id']);
$recentDeliveryNotes = array_slice($clientDeliveryNotes, 0, 5);

// Obține locațiile clientului
$clientLocations = $clientObj->getClientLocations($_SESSION['client_id']);

// Statistici
$totalOrders = count($clientOrders);
$pendingOrders = 0;
$approvedOrders = 0;
$rejectedOrders = 0;
$completedOrders = 0;

foreach ($clientOrders as $order) {
    switch ($order['status']) {
        case 'pending':
            $pendingOrders++;
            break;
        case 'approved':
            $approvedOrders++;
            break;
        case 'rejected':
            $rejectedOrders++;
            break;
        case 'completed':
            $completedOrders++;
            break;
    }
}

// Titlu pagină
$pageTitle = 'Panou Client';

// Include header
include_once '../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Bun venit, <?php echo $clientData['company_name']; ?></h1>

<!-- Statistici -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                <i class="fas fa-shopping-cart fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Total Comenzi</p>
                <p class="text-2xl font-semibold"><?php echo $totalOrders; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                <i class="fas fa-clock fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">În așteptare</p>
                <p class="text-2xl font-semibold"><?php echo $pendingOrders; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                <i class="fas fa-check fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Aprobate</p>
                <p class="text-2xl font-semibold"><?php echo $approvedOrders; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                <i class="fas fa-times fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Respinse</p>
                <p class="text-2xl font-semibold"><?php echo $rejectedOrders; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Acțiuni rapide -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Acțiuni rapide</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="products/index.php" class="bg-blue-50 hover:bg-blue-100 text-blue-600 p-4 rounded-lg flex items-center justify-center">
            <i class="fas fa-search mr-2"></i> Caută produse
        </a>
        <a href="cart/index.php" class="bg-green-50 hover:bg-green-100 text-green-600 p-4 rounded-lg flex items-center justify-center">
            <i class="fas fa-shopping-cart mr-2"></i> Vezi coșul
        </a>
        <a href="orders/index.php" class="bg-purple-50 hover:bg-purple-100 text-purple-600 p-4 rounded-lg flex items-center justify-center">
            <i class="fas fa-history mr-2"></i> Istoric comenzi
        </a>
    </div>
</div>

<!-- Comenzi recente și Avize -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Comenzi recente -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold">Comenzi recente</h2>
                <a href="orders/index.php" class="text-blue-600 hover:text-blue-800 text-sm">Vezi toate comenzile</a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <?php if (count($recentOrders) > 0): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Număr comandă
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Data
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="orders/view.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:text-blue-900 font-medium">
                                        <?php echo $order['order_number']; ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($order['order_date'], true); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatAmount($order['total_amount']); ?> Lei
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    Nu există comenzi înregistrate.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Avize recente -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold">Avize recente</h2>
                <a href="delivery-notes/index.php" class="text-blue-600 hover:text-blue-800 text-sm">Vezi toate avizele</a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <?php if (count($recentDeliveryNotes) > 0): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Număr aviz
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Data
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Comandă
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentDeliveryNotes as $note): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="delivery-notes/view.php?id=<?php echo $note['id']; ?>" class="text-blue-600 hover:text-blue-900 font-medium">
                                        <?php echo $note['delivery_note_number']; ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($note['delivery_date'], true); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $note['status'] == 'delivered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $note['status'] == 'delivered' ? 'Livrat' : 'În livrare'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="orders/view.php?id=<?php echo $note['order_id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        <?php echo $note['order_number']; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    Nu există avize înregistrate.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Locații -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Locațiile dvs.</h2>
            <?php if (hasRole('client_admin')): ?>
                <a href="locations/index.php" class="text-blue-600 hover:text-blue-800 text-sm">Gestionează locațiile</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="p-4">
        <?php if (count($clientLocations) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($clientLocations as $location): ?>
                    <div class="border rounded-lg p-4 <?php echo $_SESSION['location_id'] == $location['id'] ? 'bg-blue-50 border-blue-300' : ''; ?>">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-medium text-gray-900"><?php echo $location['name']; ?></h3>
                                <p class="text-sm text-gray-600 mt-1"><?php echo $location['address']; ?></p>
                                
                                <?php if ($location['contact_person']): ?>
                                    <p class="text-sm text-gray-600 mt-2">
                                        <span class="font-medium">Contact:</span> <?php echo $location['contact_person']; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($location['phone']): ?>
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium">Telefon:</span> <?php echo $location['phone']; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($location['email']): ?>
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium">Email:</span> <?php echo $location['email']; ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($_SESSION['location_id'] == $location['id']): ?>
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">Locația dvs.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-gray-500 py-4">
                Nu există locații înregistrate.
                <?php if (hasRole('client_admin')): ?>
                    <a href="locations/add.php" class="text-blue-600 hover:text-blue-800 ml-2">Adaugă prima locație</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once '../includes/footer.php';
?>