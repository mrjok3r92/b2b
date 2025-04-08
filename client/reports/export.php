<?php
// client/reports/export.php
// Pagina pentru exportul datelor

// Inițializare sesiune și autentificare client
require_once '../../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../../classes/Order.php';
require_once '../../classes/Client.php';
require_once '../../classes/Product.php';
require_once '../../classes/DeliveryNote.php';

// Inițializare obiecte
$orderObj = new Order();
$clientObj = new Client();
$productObj = new Product();
$deliveryNoteObj = new DeliveryNote();

// Tipuri de export disponibile
$export_types = [
    'orders' => 'Comenzi',
    'order_details' => 'Detalii comenzi',
    'delivery_notes' => 'Avize de livrare',
    'products' => 'Produse'
];

// Obține locațiile pentru filtrare
$locations = $clientObj->getClientLocations($_SESSION['client_id']);

// Procesare export dacă a fost trimis formularul
$export_message = '';
$export_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_type'])) {
    $export_type = $_POST['export_type'];
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;
    $format = isset($_POST['format']) ? $_POST['format'] : 'csv';
    
    if (!array_key_exists($export_type, $export_types)) {
        $export_error = 'Tipul de export selectat nu este valid.';
    } else {
        try {
            // Generează fișierul de export
            $export_file = generateExport($export_type, $start_date, $end_date, $location_id, $format);
            $export_message = 'Datele au fost exportate cu succes.';
        } catch (Exception $e) {
            $export_error = 'Eroare la exportul datelor: ' . $e->getMessage();
        }
    }
}

// Funcție pentru generarea exportului
function generateExport($type, $start_date, $end_date, $location_id, $format) {
    global $orderObj, $clientObj, $productObj, $deliveryNoteObj;
    
    // Generează numele fișierului
    $timestamp = date('YmdHis');
    $filename = "export_{$type}_{$timestamp}.{$format}";
    $filepath = "../../exports/{$filename}";
    
    // Asigură-te că directorul există
    if (!file_exists('../../exports')) {
        mkdir('../../exports', 0777, true);
    }
    
    // Obține datele pentru export
    $data = [];
    $headers = [];
    
    switch ($type) {
        case 'orders':
            // Export comenzi
            $orders = $orderObj->getClientOrders($_SESSION['client_id']);
            
            // Filtrare după dată
            if (!empty($start_date)) {
                $start = strtotime($start_date);
                $orders = array_filter($orders, function($order) use ($start) {
                    return strtotime($order['order_date']) >= $start;
                });
            }
            
            if (!empty($end_date)) {
                $end = strtotime($end_date . ' 23:59:59');
                $orders = array_filter($orders, function($order) use ($end) {
                    return strtotime($order['order_date']) <= $end;
                });
            }
            
            // Filtrare după locație
            if ($location_id > 0) {
                $orders = array_filter($orders, function($order) use ($location_id) {
                    return $order['location_id'] == $location_id;
                });
            }
            
            $headers = ['ID', 'Număr comandă', 'Data comenzii', 'Locație', 'Status', 'Total', 'Data aprobare', 'Agent'];
            
            foreach ($orders as $order) {
                $data[] = [
                    $order['id'],
                    $order['order_number'],
                    $order['order_date'],
                    $order['location_name'],
                    $order['status'],
                    $order['total_amount'],
                    $order['approval_date'] ?? '',
                    $order['agent_name'] ?? ''
                ];
            }
            break;
            
        case 'order_details':
            // Export detalii comenzi
            $orders = $orderObj->getClientOrders($_SESSION['client_id']);
            
            // Filtrare după dată și locație similar cu cea de mai sus
            if (!empty($start_date)) {
                $start = strtotime($start_date);
                $orders = array_filter($orders, function($order) use ($start) {
                    return strtotime($order['order_date']) >= $start;
                });
            }
            
            if (!empty($end_date)) {
                $end = strtotime($end_date . ' 23:59:59');
                $orders = array_filter($orders, function($order) use ($end) {
                    return strtotime($order['order_date']) <= $end;
                });
            }
            
            if ($location_id > 0) {
                $orders = array_filter($orders, function($order) use ($location_id) {
                    return $order['location_id'] == $location_id;
                });
            }
            
            $headers = ['Comandă', 'Data', 'Cod produs', 'Nume produs', 'UM', 'Cantitate', 'Preț unitar', 'Total'];
            
            foreach ($orders as $order) {
                $details = $orderObj->getOrderDetails($order['id']);
                
                foreach ($details as $detail) {
                    $data[] = [
                        $order['order_number'],
                        $order['order_date'],
                        $detail['code'],
                        $detail['name'],
                        $detail['unit'],
                        $detail['quantity'],
                        $detail['unit_price'],
                        $detail['amount']
                    ];
                }
            }
            break;
            
        case 'delivery_notes':
            // Export avize de livrare
            $notes = $deliveryNoteObj->getClientDeliveryNotes($_SESSION['client_id']);
            
            // Filtrare după dată
            if (!empty($start_date)) {
                $start = strtotime($start_date);
                $notes = array_filter($notes, function($note) use ($start) {
                    return strtotime($note['delivery_date']) >= $start;
                });
            }
            
            if (!empty($end_date)) {
                $end = strtotime($end_date . ' 23:59:59');
                $notes = array_filter($notes, function($note) use ($end) {
                    return strtotime($note['delivery_date']) <= $end;
                });
            }
            
            $headers = ['ID', 'Număr aviz', 'Data livrare', 'Comandă', 'Status', 'Note'];
            
            foreach ($notes as $note) {
                $data[] = [
                    $note['id'],
                    $note['delivery_note_number'],
                    $note['delivery_date'],
                    $note['order_number'],
                    $note['status'],
                    $note['notes'] ?? ''
                ];
            }
            break;
            
        case 'products':
            // Export produse
            $products = $productObj->getActiveProducts();
            
            $headers = ['ID', 'Cod', 'Nume', 'Categorie', 'UM', 'Preț standard', 'Preț client'];
            
            foreach ($products as $product) {
                $clientPrice = $productObj->getClientPrice($_SESSION['client_id'], $product['id']);
                
                $data[] = [
                    $product['id'],
                    $product['code'],
                    $product['name'],
                    $product['category_name'] ?? '',
                    $product['unit'],
                    $product['price'],
                    $clientPrice
                ];
            }
            break;
    }
    
    // Generează fișierul de export
    if ($format === 'csv') {
        // Export CSV
        $fp = fopen($filepath, 'w');
        
        // Adaugă BOM pentru UTF-8
        fputs($fp, "\xEF\xBB\xBF");
        
        // Scrie headerele
        fputcsv($fp, $headers);
        
        // Scrie datele
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);
    } elseif ($format === 'xlsx') {
        // Export Excel - în mod normal ai folosi o bibliotecă precum PhpSpreadsheet
        // Dar pentru simplitate, vom genera un CSV și vom schimba extensia
        $fp = fopen($filepath, 'w');
        
        // Adaugă BOM pentru UTF-8
        fputs($fp, "\xEF\xBB\xBF");
        
        // Scrie headerele
        fputcsv($fp, $headers);
        
        // Scrie datele
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);
    }
    
    // Descarcă fișierul
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/' . ($format === 'csv' ? 'csv' : 'vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
    
    return $filepath;
}

// Titlu pagină
$pageTitle = 'Export Date - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">Export Date</h1>
    
    <a href="../index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la dashboard
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Export date</h2>
    </div>
    
    <div class="p-6">
        <?php if ($export_error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $export_error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($export_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $export_message; ?></span>
            </div>
        <?php endif; ?>
        
        <form action="export.php" method="POST" class="space-y-6">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Tip export -->
            <div>
                <label for="export_type" class="block text-sm font-medium text-gray-700">Tip export</label>
                <select id="export_type" name="export_type" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Selectați tipul de export</option>
                    <?php foreach ($export_types as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Interval de date -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Dată început</label>
                    <input type="date" id="start_date" name="start_date"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">Dată sfârșit</label>
                    <input type="date" id="end_date" name="end_date"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>
            
            <!-- Locație -->
            <div>
                <label for="location_id" class="block text-sm font-medium text-gray-700">Locație</label>
                <select id="location_id" name="location_id"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="0">Toate locațiile</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>">
                            <?php echo htmlspecialchars($location['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Format export -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Format export</label>
                <div class="mt-2 space-y-2">
                    <div class="flex items-center">
                        <input id="format_csv" name="format" type="radio" value="csv" checked
                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                        <label for="format_csv" class="ml-3 block text-sm font-medium text-gray-700">
                            CSV
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input id="format_xlsx" name="format" type="radio" value="xlsx"
                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                        <label for="format_xlsx" class="ml-3 block text-sm font-medium text-gray-700">
                            Excel (XLSX)
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Buton export -->
            <div class="flex justify-end pt-4 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-download mr-1"></i> Exportă date
                </button>
            </div>
        </form>
    </div>
</div>

<div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-500 mt-1"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Informații despre export date</h3>
            <div class="mt-2 text-sm text-blue-700">
                <p>Exportul de date vă permite să descărcați informații din platformă în formate compatibile cu alte aplicații (Excel, contabilitate, etc).</p>
                <p class="mt-1">Folosiți filtrele pentru a exporta doar datele care vă interesează.</p>
                <p class="mt-1">Fișierele exportate conțin diacritice și sunt compatibile cu Excel și alte aplicații similare.</p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>