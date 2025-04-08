<?php
// admin/index.php
// Pagina principală a panoului de administrare

// Inițializare sesiune și autentificare admin
require_once '../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../classes/Order.php';
require_once '../classes/Client.php';
require_once '../classes/Product.php';
require_once '../classes/User.php';
require_once '../classes/DeliveryNote.php';

// Inițializare obiecte
$orderObj = new Order();
$clientObj = new Client();
$productObj = new Product();
$userObj = new User();
$deliveryNoteObj = new DeliveryNote();

// Obține statistici
$pendingOrders = $orderObj->getPendingOrders();
$totalOrders = count($orderObj->getAllOrders());
$totalClients = count($clientObj->getAllClients());
$totalProducts = count($productObj->getAllProducts());
$totalUsers = count($userObj->getAllUsers());
$totalDeliveryNotes = count($deliveryNoteObj->getAllDeliveryNotes());

// Obține comenzile recent aprobate
$recentApprovedOrders = $orderObj->getRecentApprovedOrders(5);

// Obține clienții recent adăugați
$recentClients = $clientObj->getRecentClients(5);

// Titlu pagină
$pageTitle = 'Panou de Administrare';

// Include header
include_once '../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Panou de Administrare</h1>

<!-- Statistici -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                <i class="fas fa-building fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Clienți</p>
                <p class="text-2xl font-semibold"><?php echo $totalClients; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                <i class="fas fa-box fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Produse</p>
                <p class="text-2xl font-semibold"><?php echo $totalProducts; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                <i class="fas fa-shopping-cart fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Comenzi</p>
                <p class="text-2xl font-semibold"><?php echo $totalOrders; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-500 mr-4">
                <i class="fas fa-users fa-2x"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase">Utilizatori</p>
                <p class="text-2xl font-semibold"><?php echo $totalUsers; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Comenzi în așteptare -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Comenzi în așteptare</h2>
            <a href="orders/index.php" class="text-blue-600 hover:text-blue-800 text-sm">Vezi toate comenzile</a>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($pendingOrders) > 0): ?>
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
                            Data comenzii
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
                    <?php foreach ($pendingOrders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo $order['order_number']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php echo htmlspecialchars($order['company_name']); ?><br>
                                <span class="text-xs text-gray-500"><?php echo htmlspecialchars($order['location_name']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php echo formatDate($order['order_date'], true); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php echo formatAmount($order['total_amount']); ?> Lei
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="orders/view.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye"></i> Vezi
                                </a>
                                <a href="orders/approve.php?id=<?php echo $order['id']; ?>" class="text-green-600 hover:text-green-900">
                                    <i class="fas fa-check"></i> Aprobă
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                Nu există comenzi în așteptare.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Activitate recentă -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Comenzi recent aprobate -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold">Comenzi recent aprobate</h2>
                <a href="orders/index.php?status=approved" class="text-blue-600 hover:text-blue-800 text-sm">Vezi toate</a>
            </div>
        </div>
        
        <div class="p-4">
            <?php if (count($recentApprovedOrders) > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($recentApprovedOrders as $order): ?>
                        <li class="py-3">
                            <div class="flex justify-between">
                                <div>
                                    <a href="orders/view.php?id=<?php echo $order['id']; ?>" class="font-medium text-blue-600 hover:text-blue-900">
                                        <?php echo $order['order_number']; ?>
                                    </a>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($order['company_name']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium"><?php echo formatAmount($order['total_amount']); ?> Lei</p>
                                    <p class="text-xs text-gray-500"><?php echo formatDate($order['approval_date'], true); ?></p>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="text-center text-gray-500 py-4">
                    Nu există comenzi aprobate recent.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Clienți recenți -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold">Clienți recenți</h2>
                <a href="clients/index.php" class="text-blue-600 hover:text-blue-800 text-sm">Vezi toți clienții</a>
            </div>
        </div>
        
        <div class="p-4">
            <?php if (count($recentClients) > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($recentClients as $client): ?>
                        <li class="py-3">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600">
                                    <?php echo strtoupper(substr($client['company_name'], 0, 1)); ?>
                                </div>
                                <div class="ml-3">
                                    <a href="clients/view.php?id=<?php echo $client['id']; ?>" class="font-medium text-blue-600 hover:text-blue-900">
                                        <?php echo htmlspecialchars($client['company_name']); ?>
                                    </a>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($client['email']); ?></p>
                                </div>
                                <div class="ml-auto">
                                    <p class="text-xs text-gray-500"><?php echo formatDate($client['created_at']); ?></p>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="text-center text-gray-500 py-4">
                    Nu există clienți adăugați recent.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Acțiuni rapide -->
<div class="bg-white rounded-lg shadow-sm p-6 mt-6">
    <h2 class="text-lg font-semibold mb-4">Acțiuni rapide</h2>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="clients/add.php" class="bg-blue-50 hover:bg-blue-100 text-blue-600 p-4 rounded-lg flex flex-col items-center justify-center text-center">
            <i class="fas fa-user-plus fa-2x mb-2"></i>
            <span>Adaugă client</span>
        </a>
        
        <a href="products/add.php" class="bg-green-50 hover:bg-green-100 text-green-600 p-4 rounded-lg flex flex-col items-center justify-center text-center">
            <i class="fas fa-box-open fa-2x mb-2"></i>
            <span>Adaugă produs</span>
        </a>
        
        <a href="orders/index.php" class="bg-yellow-50 hover:bg-yellow-100 text-yellow-600 p-4 rounded-lg flex flex-col items-center justify-center text-center">
            <i class="fas fa-clipboard-list fa-2x mb-2"></i>
            <span>Gestionare comenzi</span>
        </a>
        
        <a href="reports/index.php" class="bg-purple-50 hover:bg-purple-100 text-purple-600 p-4 rounded-lg flex flex-col items-center justify-center text-center">
            <i class="fas fa-chart-bar fa-2x mb-2"></i>
            <span>Rapoarte</span>
        </a>
    </div>
</div>

<?php
// Include footer
include_once '../includes/footer.php';
?>