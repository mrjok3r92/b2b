<?php
// client/delivery-notes/view.php
// Pagina pentru vizualizarea detaliilor unui aviz de livrare

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/DeliveryNote.php';

// Verificare ID aviz
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID aviz invalid.');
    redirect('index.php');
}

$note_id = (int)$_GET['id'];

// Inițializare obiecte
$deliveryNoteObj = new DeliveryNote();

// Obține detaliile avizului
$note = $deliveryNoteObj->getDeliveryNoteById($note_id);

// Verificare existență aviz
if (!$note) {
    setFlashMessage('error', 'Avizul nu există.');
    redirect('index.php');
}

// Verificare dacă avizul aparține clientului
if ($note['client_id'] != $_SESSION['client_id']) {
    setFlashMessage('error', 'Nu aveți acces la acest aviz.');
    redirect('index.php');
}

// Obține detaliile avizului
$noteDetails = $deliveryNoteObj->getDeliveryNoteDetails($note_id);

// Titlu pagină
$pageTitle = 'Aviz #' . $note['delivery_note_number'] . ' - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de avize
    </a>
</div>

<!-- Antet aviz -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-900">
            Aviz de livrare #<?php echo $note['delivery_note_number']; ?>
            
            <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $note['status'] == 'delivered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                <?php echo $note['status'] == 'delivered' ? 'Livrat' : 'În livrare'; ?>
            </span>
        </h1>
        
        <div>
            <a href="../orders/view.php?id=<?php echo $note['order_id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-shopping-cart mr-1"></i> Vezi comanda
            </a>
        </div>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded-md">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Informații aviz</h2>
                <div class="space-y-1">
                    <p><span class="font-medium">Data:</span> <?php echo formatDate($note['delivery_date'], true); ?></p>
                    <p><span class="font-medium">Număr comandă:</span> <?php echo $note['order_number']; ?></p>
                </div>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-md">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Adresă livrare</h2>
                <div class="space-y-1">
                    <p><span class="font-medium"><?php echo htmlspecialchars($note['location_name']); ?></span></p>
                    <p><?php echo htmlspecialchars($note['location_address'] ?? ''); ?></p>
                </div>
            </div>
        </div>
        
        <?php if (!empty($note['notes'])): ?>
            <div class="mb-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Note aviz</h2>
                <div class="bg-gray-50 p-4 rounded-md">
                    <?php echo nl2br(htmlspecialchars($note['notes'])); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Produse aviz -->
        <div class="mb-6">
            <h2 class="text-sm font-medium text-gray-500 uppercase mb-4">Produse livrate</h2>
            
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
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($noteDetails as $detail): ?>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Butoane acțiuni -->
<div class="flex justify-between mt-6">
    <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la avize
    </a>
    
    <div>
        <a href="#" onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md ml-2">
            <i class="fas fa-print mr-1"></i> Printează
        </a>
        
        <a href="../orders/view.php?id=<?php echo $note['order_id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md ml-2">
            <i class="fas fa-shopping-cart mr-1"></i> Vezi comanda
        </a>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>