<?php
// admin/clients/index.php
// Pagina pentru listarea clienților în panoul de administrare

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Client.php';
require_once '../../classes/Order.php';
require_once '../../classes/User.php';

// Inițializare obiecte
$clientObj = new Client();
$orderObj = new Order();
$userObj = new User();

// Parametri paginare și filtrare
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Clienți per pagină
$offset = ($page - 1) * $limit;

// Căutare
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Obține clienții
if (!empty($search)) {
    $clients = $clientObj->searchClients($search, $limit, $offset);
    $totalClients = $clientObj->countSearchResults($search);
} else {
    $clients = $clientObj->getAllClientsPaginated($limit, $offset);
    $totalClients = $clientObj->getTotalClients();
}

// Calculează numărul de pagini
$totalPages = ceil($totalClients / $limit);

// Titlu pagină
$pageTitle = 'Gestionare Clienți - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">Gestionare Clienți</h1>
    
    <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
        <i class="fas fa-plus mr-1"></i> Adaugă client
    </a>
</div>

<!-- Filtre și căutare -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="index.php" method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-grow">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Caută client</label>
            <div class="relative">
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Caută după nume companie, cod fiscal, telefon sau email..." 
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-search mr-1"></i> Caută
            </button>
        </div>
    </form>
</div>

<!-- Lista clienți -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Lista clienți</h2>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($clients) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            #
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Companie
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cod fiscal
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contact
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Locații
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Comenzi
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acțiuni
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    $index = ($page - 1) * $limit + 1;
                    foreach ($clients as $client): 
                        // Obține numărul de locații
                        $locations = $clientObj->getClientLocations($client['id']);
                        $locationCount = count($locations);
                        
                        // Obține numărul de comenzi
                        $orderCount = $orderObj->getClientOrderCount($client['id']);
                        
                        // Obține numărul de utilizatori
                        $userCount = $userObj->getClientUserCount($client['id']);
                    ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $index++; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                        <?php echo strtoupper(substr($client['company_name'], 0, 1)); ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($client['company_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Înregistrat: <?php echo formatDate($client['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($client['fiscal_code']); ?>
                                <?php if (!empty($client['company_code'])): ?>
                                    <div class="text-xs"><?php echo htmlspecialchars($client['company_code']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>
                                    <i class="fas fa-phone-alt text-gray-400 mr-1"></i> <?php echo htmlspecialchars($client['phone']); ?>
                                </div>
                                <div>
                                    <i class="fas fa-envelope text-gray-400 mr-1"></i> <?php echo htmlspecialchars($client['email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?php echo $locationCount; ?> locații
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <?php echo $orderCount; ?> comenzi
                                </span>
                                <div class="text-xs mt-1">
                                    <?php echo $userCount; ?> utilizatori
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="view.php?id=<?php echo $client['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye"></i> Vezi
                                </a>
                                <a href="edit.php?id=<?php echo $client['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-edit"></i> Editează
                                </a>
                                <?php if ($orderCount == 0): ?>
                                    <a href="delete.php?id=<?php echo $client['id']; ?>" class="text-red-600 hover:text-red-900 delete-confirm">
                                        <i class="fas fa-trash"></i> Șterge
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
                            <span class="font-medium"><?php echo min($page * $limit, $totalClients); ?></span> din 
                            <span class="font-medium"><?php echo $totalClients; ?></span> clienți
                        </div>
                        
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1 rounded-md bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1 rounded-md bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu au fost găsiți clienți</h3>
                <p class="text-gray-600 mb-4">
                    <?php if (!empty($search)): ?>
                        Nu există clienți care să se potrivească cu criteriile de căutare.
                    <?php else: ?>
                        Nu există clienți înregistrați în sistem.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search)): ?>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-times mr-1"></i> Resetează căutarea
                    </a>
                <?php else: ?>
                    <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                        <i class="fas fa-plus mr-1"></i> Adaugă primul client
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmare ștergere
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Ești sigur că vrei să ștergi acest client? Această acțiune nu poate fi anulată și va șterge toate datele asociate, inclusiv locații și utilizatori.')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>