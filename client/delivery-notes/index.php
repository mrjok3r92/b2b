<?php
// client/delivery-notes/index.php
// Pagina pentru listarea avizelor de livrare

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/DeliveryNote.php';
require_once '../../classes/Client.php';

// Inițializare obiecte
$deliveryNoteObj = new DeliveryNote();
$clientObj = new Client();

// Filtrare avize
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$location_id = isset($_GET['location']) && is_numeric($_GET['location']) ? (int)$_GET['location'] : 0;

// Obține avizele clientului
$deliveryNotes = $deliveryNoteObj->getClientDeliveryNotes($_SESSION['client_id']);

// Filtrare după status dacă este specificat
if (!empty($status)) {
    $filteredNotes = [];
    foreach ($deliveryNotes as $note) {
        if ($note['status'] == $status) {
            $filteredNotes[] = $note;
        }
    }
    $deliveryNotes = $filteredNotes;
}

// Obține locațiile clientului pentru filtrare
$locations = $clientObj->getClientLocations($_SESSION['client_id']);

// Titlu pagină
$pageTitle = 'Avize de livrare - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Avize de livrare</h1>
    
    <a href="../orders/index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-shopping-cart mr-1"></i> Vezi comenzile
    </a>
</div>

<!-- Filtre -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="index.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toate statusurile</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>În livrare</option>
                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Livrate</option>
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
        
        <div class="flex items-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md mr-2">
                <i class="fas fa-filter mr-1"></i> Filtrează
            </button>
            
            <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                <i class="fas fa-times mr-1"></i> Resetează
            </a>
        </div>
    </form>
</div>

<!-- Lista avize -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Avize de livrare</h2>
    </div>
    
    <div>
        <?php if (count($deliveryNotes) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Număr aviz
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Dată
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Comandă
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Locație
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?php echo $note['delivery_note_number']; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($note['delivery_date'], true); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="../orders/view.php?id=<?php echo $note['order_id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        <?php echo $note['order_number']; ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($note['location_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $note['status'] == 'delivered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $note['status'] == 'delivered' ? 'Livrat' : 'În livrare'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view.php?id=<?php echo $note['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
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
                <i class="fas fa-file-invoice fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu aveți avize de livrare</h3>
                <p class="text-gray-600 mb-4">
                    <?php if (!empty($status) || $location_id > 0): ?>
                        Nu există avize care să corespundă criteriilor selectate.
                    <?php else: ?>
                        Nu au fost generate încă avize de livrare pentru comenzile dvs.
                    <?php endif; ?>
                </p>
                <a href="../orders/index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                    <i class="fas fa-shopping-cart mr-1"></i> Vezi comenzile
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>