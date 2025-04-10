<?php
// admin/delivery-notes/cancel.php
// Pagina pentru anularea unui aviz de livrare

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/DeliveryNote.php';
require_once '../../classes/Order.php';
require_once '../../classes/Client.php';

// Verificare ID aviz
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID aviz invalid.');
    redirect('index.php');
}

$delivery_note_id = (int)$_GET['id'];

// Inițializare obiecte
$deliveryNoteObj = new DeliveryNote();
$orderObj = new Order();
$clientObj = new Client();

// Obține informațiile avizului
$deliveryNote = $deliveryNoteObj->getDeliveryNoteById($delivery_note_id);

// Verificare existență aviz
if (!$deliveryNote) {
    setFlashMessage('error', 'Avizul nu există.');
    redirect('index.php');
}

// Verifică dacă avizul poate fi anulat (doar avizele cu status draft sau sent)
if ($deliveryNote['status'] === 'cancelled' || $deliveryNote['status'] === 'delivered') {
    setFlashMessage('error', 'Acest aviz nu poate fi anulat deoarece are statusul ' . $deliveryNote['status'] . '.');
    redirect('view.php?id=' . $delivery_note_id);
}

// Verificare confirmare
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
$csrf_valid = isset($_GET['csrf_token']) && verifyCSRFToken($_GET['csrf_token']);

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'cancel_reason' => ''
];

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Preluare date formular
        $formData = [
            'cancel_reason' => sanitizeInput($_POST['cancel_reason'] ?? '')
        ];
        
        // Anulează avizul
        $result = $deliveryNoteObj->cancelDeliveryNote($delivery_note_id, $formData['cancel_reason']);
        
        if ($result) {
            setFlashMessage('success', 'Avizul a fost anulat cu succes.');
            redirect('view.php?id=' . $delivery_note_id);
        } else {
            $error = 'A apărut o eroare la anularea avizului. Vă rugăm să încercați din nou.';
        }
    }
}

// Obține detaliile comenzii asociate
$order = $orderObj->getOrderById($deliveryNote['order_id']);

// Obține detaliile clientului
$client = $clientObj->getClientById($deliveryNote['client_id']);

// Titlu pagină
$pageTitle = 'Anulare Aviz de Livrare - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="view.php?id=<?php echo $delivery_note_id; ?>" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la detalii aviz
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Anulare Aviz de Livrare</h1>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-red-50">
        <h2 class="text-lg font-semibold text-red-700">Atenție!</h2>
    </div>
    
    <div class="p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-3xl text-yellow-500 mr-4"></i>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    Ești sigur că vrei să anulezi acest aviz de livrare?
                </h3>
                
                <div class="bg-gray-100 rounded-md p-4 mb-4">
                    <div class="flex flex-col md:flex-row">
                        <div class="mb-4 md:mb-0 md:mr-8">
                            <h4 class="font-medium text-gray-700">Aviz:</h4>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($deliveryNote['series'] . $deliveryNote['delivery_note_number']); ?></p>
                            <p class="text-sm text-gray-500">Data: <?php echo date('d.m.Y', strtotime($deliveryNote['issue_date'])); ?></p>
                        </div>
                        
                        <div class="mb-4 md:mb-0 md:mr-8">
                            <h4 class="font-medium text-gray-700">Comandă:</h4>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($order['order_number']); ?></p>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-gray-700">Client:</h4>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($client['company_name']); ?></p>
                        </div>
                    </div>
                </div>
                
                <p class="text-gray-600 mb-4">
                    <strong>Notă:</strong> Anularea unui aviz este o acțiune ireversibilă. 
                    Produsele incluse în acest aviz vor fi readăugate la cantitățile disponibile pentru avizare din comanda asociată.
                </p>
                
                <form method="POST" action="cancel.php?id=<?php echo $delivery_note_id; ?>" class="mt-6">
                    <!-- CSRF token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-6">
                        <label for="cancel_reason" class="block text-sm font-medium text-gray-700">Motiv anulare</label>
                        <textarea id="cancel_reason" name="cancel_reason" rows="3" 
                                  class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                  placeholder="Introduceți motivul pentru anularea acestui aviz..."><?php echo htmlspecialchars($formData['cancel_reason']); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <a href="view.php?id=<?php echo $delivery_note_id; ?>" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Anulează
                        </a>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-times-circle mr-1"></i> Anulează avizul
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>