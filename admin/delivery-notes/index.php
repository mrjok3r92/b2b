<?php
// admin/delivery-notes/index.php
// Pagina pentru gestionarea avizelor de livrare

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/DeliveryNote.php';
require_once '../../classes/Client.php';

// Inițializare obiecte
$deliveryNoteObj = new DeliveryNote();
$clientObj = new Client();

// Parametri paginare și filtrare
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Avize per pagină
$offset = ($page - 1) * $limit;

// Parametri filtrare
$client_id = isset($_GET['client_id']) && is_numeric($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Obține avizele
$deliveryNotes = $deliveryNoteObj->getFilteredDeliveryNotes(
    $client_id, 
    $status, 
    $date_from, 
    $date_to, 
    $search, 
    $limit, 
    $offset
);

// Numără total avize pentru paginare
$totalDeliveryNotes = $deliveryNoteObj->countFilteredDeliveryNotes(
    $client_id, 
    $status, 
    $date_from, 
    $date_to, 
    $search
);

// Calculează numărul de pagini
$totalPages = ceil($totalDeliveryNotes / $limit);

// Obține lista de clienți pentru filtru
$clients = $clientObj->getAllClients();

// Titlu pagină
$pageTitle = 'Gestionare Avize de Livrare - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Gestionare Avize de Livrare</h1>
    
    <div>
        <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
            <i class="fas fa-plus mr-1"></i> Creare Aviz Nou
        </a>
    </div>
</div>

<!-- Filtre și căutare -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="index.php" method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Client -->
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
            
            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Toate statusurile</option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Ciornă</option>
                    <option value="sent" <?php echo $status === 'sent' ? 'selected' : ''; ?>>Trimis</option>
                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Livrat</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Anulat</option>
                </select>
            </div>
            
            <!-- Data început -->
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Data de la</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" 
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <!-- Data sfârșit -->
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Data până la</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" 
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>
        
        <div class="flex items-end justify-between pt-2">
            <div class="w-full md:w-1/3">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Caută</label>
                <div class="relative rounded-md shadow-sm">
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Număr aviz, comandă sau client..." 
                           class="block w-full pr-10 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-filter mr-1"></i> Filtrează
                </button>
                
                <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                    <i class="fas fa-times mr-1"></i> Resetează
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Statistici rapide -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <i class="fas fa-file-alt text-white text-xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total avize</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">
                                <?php echo $deliveryNoteObj->countDeliveryNotesByStatus(''); ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                    <i class="fas fa-file-export text-white text-xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Avize trimise</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">
                                <?php echo $deliveryNoteObj->countDeliveryNotesByStatus('sent'); ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <i class="fas fa-truck text-white text-xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Avize livrate</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">
                                <?php echo $deliveryNoteObj->countDeliveryNotesByStatus('delivered'); ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                    <i class="fas fa-times-circle text-white text-xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Avize anulate</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">
                                <?php echo $deliveryNoteObj->countDeliveryNotesByStatus('cancelled'); ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista avize -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Lista avize de livrare</h2>
        <p class="text-sm text-gray-500 mt-1">Total: <?php echo $totalDeliveryNotes; ?> avize</p>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($deliveryNotes) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nr. Aviz
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Comandă
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Client
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Locație
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data emitere
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
                    <?php foreach ($deliveryNotes as $note): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($note['delivery_note_number']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-blue-600">
                                    <a href="../orders/view.php?id=<?php echo $note['order_id']; ?>" class="hover:underline">
                                        <?php echo htmlspecialchars($note['order_number']); ?>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($note['company_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($note['fiscal_code']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($note['location_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo date('d.m.Y', strtotime($note['issue_date'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $statusLabels = [
                                    'draft' => ['text' => 'Ciornă', 'class' => 'bg-gray-100 text-gray-800'],
                                    'sent' => ['text' => 'Trimis', 'class' => 'bg-yellow-100 text-yellow-800'],
                                    'delivered' => ['text' => 'Livrat', 'class' => 'bg-green-100 text-green-800'],
                                    'cancelled' => ['text' => 'Anulat', 'class' => 'bg-red-100 text-red-800']
                                ];
                                $statusInfo = $statusLabels[$note['status']] ?? ['text' => 'Necunoscut', 'class' => 'bg-gray-100 text-gray-800'];
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusInfo['class']; ?>">
                                    <?php echo $statusInfo['text']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo formatAmount($note['total_amount']); ?> Lei
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="view.php?id=<?php echo $note['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($note['status'] === 'draft'): ?>
                                    <a href="edit.php?id=<?php echo $note['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-2">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="print.php?id=<?php echo $note['id']; ?>" class="text-green-600 hover:text-green-900 mr-2" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <?php if ($note['status'] === 'draft'): ?>
                                    <a href="delete.php?id=<?php echo $note['id']; ?>" class="text-red-600 hover:text-red-900 delete-confirm">
                                        <i class="fas fa-trash"></i>
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
                            <span class="font-medium"><?php echo min($page * $limit, $totalDeliveryNotes); ?></span> din 
                            <span class="font-medium"><?php echo $totalDeliveryNotes; ?></span> avize
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
                <i class="fas fa-file-alt fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu au fost găsite avize de livrare</h3>
                <p class="text-gray-600 mb-4">
                    <?php if (!empty($search) || !empty($client_id) || !empty($status) || !empty($date_from) || !empty($date_to)): ?>
                        Nu există avize care să corespundă criteriilor de filtrare selectate.
                    <?php else: ?>
                        Nu există avize de livrare înregistrate în sistem.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || !empty($client_id) || !empty($status) || !empty($date_from) || !empty($date_to)): ?>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-times mr-1"></i> Resetează filtrele
                    </a>
                <?php else: ?>
                    <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                        <i class="fas fa-plus mr-1"></i> Creare primul aviz
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
            if (!confirm('Ești sigur că vrei să ștergi acest aviz? Această acțiune nu poate fi anulată.')) {
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