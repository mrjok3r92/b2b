<?php
// admin/orders/index.php
// Pagina pentru gestionarea comenzilor în panoul de administrare

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Order.php';
require_once '../../classes/Client.php';

// Inițializare obiecte
$orderObj = new Order();
$clientObj = new Client();

// Parametri filtrare
$client_id = isset($_GET['client_id']) && is_numeric($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Parametri paginare
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Comenzi per pagină
$offset = ($page - 1) * $limit;

// Obține comenzile filtrate
$orders = $orderObj->getFilteredOrders($client_id, $status, $date_from, $date_to, $search, $limit, $offset);
$totalOrders = $orderObj->countFilteredOrders($client_id, $status, $date_from, $date_to, $search);

// Calculează numărul de pagini
$totalPages = ceil($totalOrders / $limit);

// Obține clienții pentru filtru
$clients = $clientObj->getAllClients();

// Titlu pagină
$pageTitle = 'Gestionare Comenzi - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">Gestionare Comenzi</h1>
    
    <div>
        <a href="../reports/orders.php" class="text-blue-600 hover:text-blue-800 mr-4">
            <i class="fas fa-chart-bar mr-1"></i> Raport comenzi
        </a>
    </div>
</div>

<!-- Filtre -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="index.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client</label>
            <select id="client_id" name="client_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toți clienții</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['id']; ?>" <?php echo $client_id == $client['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['company_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
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
            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">De la data</label>
            <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" 
                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
        
        <div>
            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Până la data</label>
            <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" 
                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
        
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Caută</label>
            <div class="relative rounded-md shadow-sm">
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Număr comandă..." 
                       class="block w-full pr-10 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
        </div>
        
        <div class="md:col-span-2 lg:col-span-5 flex justify-end space-x-3">
            <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Resetează
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Filtrează
            </button>
        </div>
    </form>
</div>

<!-- Statistici -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                <i class="fas fa-clock fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">În așteptare</p>
                <p class="text-2xl font-semibold"><?php echo $orderObj->countOrdersByStatus('pending'); ?></p>
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
                <p class="text-2xl font-semibold"><?php echo $orderObj->countOrdersByStatus('approved'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                <i class="fas fa-truck fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Finalizate</p>
                <p class="text-2xl font-semibold"><?php echo $orderObj->countOrdersByStatus('completed'); ?></p>
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
                <p class="text-2xl font-semibold"><?php echo $orderObj->countOrdersByStatus('rejected'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Lista comenzi -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Lista comenzi</h2>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($orders) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Număr comandă
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Client
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acțiuni
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo $order['order_number']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['company_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($order['location_name']); ?></div>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="view.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye"></i> Vezi
                                </a>
                                
                                <?php if ($order['status'] === 'pending'): ?>
                                    <a href="approve.php?id=<?php echo $order['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">
                                        <i class="fas fa-check"></i> Aprobă
                                    </a>
                                    <a href="reject.php?id=<?php echo $order['id']; ?>" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-times"></i> Respinge
                                    </a>
                                <?php elseif ($order['status'] === 'approved'): ?>
                                    <a href="../delivery-notes/add.php?order_id=<?php echo $order['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-truck"></i> Generează aviz
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginare -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Afișare <span class="font-medium"><?php echo ($page - 1) * $limit + 1; ?></span> - 
                            <span class="font-medium"><?php echo min($page * $limit, $totalOrders); ?></span> din 
                            <span class="font-medium"><?php echo $totalOrders; ?></span> comenzi
                        </div>
                        
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($client_id) ? '&client_id=' . $client_id : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1 rounded-md bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($client_id) ? '&client_id=' . $client_id : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($client_id) ? '&client_id=' . $client_id : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1 rounded-md bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu au fost găsite comenzi</h3>
                <p class="text-gray-600 mb-4">
                    <?php if (!empty($search) || !empty($client_id) || !empty($status) || !empty($date_from) || !empty($date_to)): ?>
                        Nu există comenzi care să corespundă criteriilor de filtrare selectate.
                    <?php else: ?>
                        Nu există comenzi înregistrate în sistem.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || !empty($client_id) || !empty($status) || !empty($date_from) || !empty($date_to)): ?>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-times mr-1"></i> Resetează filtrele
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>