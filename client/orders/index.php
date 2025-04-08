
<?php
// client/orders/index.php
// Pagina pentru listarea comenzilor clientului

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/Order.php';
require_once '../../classes/Client.php';

// Inițializare obiecte
$orderObj = new Order();
$clientObj = new Client();

// Filtrare comenzi
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$location_id = isset($_GET['location']) && is_numeric($_GET['location']) ? (int)$_GET['location'] : 0;

// Obține comenzile clientului
$orders = $orderObj->getClientOrders($_SESSION['client_id']);

// Filtrare după status dacă este specificat
if (!empty($status)) {
    $filteredOrders = [];
    foreach ($orders as $order) {
        if ($order['status'] == $status) {
            $filteredOrders[] = $order;
        }
    }
    $orders = $filteredOrders;
}

// Filtrare după locație dacă este specificată
if ($location_id > 0) {
    $filteredOrders = [];
    foreach ($orders as $order) {
        if ($order['location_id'] == $location_id) {
            $filteredOrders[] = $order;
        }
    }
    $orders = $filteredOrders;
}

// Obține locațiile clientului pentru filtrare
$locations = $clientObj->getClientLocations($_SESSION['client_id']);

// Titlu pagină
$pageTitle = 'Comenzile mele - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Comenzile mele</h1>
    
    <a href="../products/index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-shopping-cart mr-1"></i> Adaugă o comandă nouă
    </a>
</div>

<!-- Filtre -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="index.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toate statusurile</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>În așteptare</option>
                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Aprobate</option>
                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Respinse</option>
                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Finalizate</option>
            </select>
        </div>
        
        <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Locație</label>
            <select id="location" name="location" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toate locațiile</option>
                <?php foreach ($locations as $location): ?>
                    <option value="<?php echo $location['id']; ?>" <?php echo $location_id === $location['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($location['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="md:col-span-2 flex items-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md mr-2">
                <i class="fas fa-filter mr-1"></i> Filtrează
            </button>
            
            <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                <i class="fas fa-times mr-1"></i> Resetează
            </a>
        </div>
    </form>
</div>

<!-- Lista comenzi -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Comenzi</h2>
    </div>
    
    <div>
        <?php if (count($orders) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Număr comandă
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Dată
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Locație
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acțiuni
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?php echo $order['order_number']; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($order['order_date'], true); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($order['location_name']); ?>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye mr-1"></i> Detalii
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-shopping-cart fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu aveți comenzi înregistrate</h3>
                <p class="text-gray-600 mb-4">
                    <?php if (!empty($status) || $location_id > 0): ?>
                        Nu există comenzi care să corespundă criteriilor selectate.
                    <?php else: ?>
                        Nu ați plasat încă nicio comandă.
                    <?php endif; ?>
                </p>
                <a href="../products/index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                    <i class="fas fa-shopping-cart mr-1"></i> Plasează prima comandă
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>