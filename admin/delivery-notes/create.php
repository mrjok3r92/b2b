<?php
// admin/delivery-notes/create.php
// Pagina pentru crearea unui nou aviz de livrare

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/DeliveryNote.php';
require_once '../../classes/Order.php';
require_once '../../classes/Client.php';

// Inițializare obiecte
$deliveryNoteObj = new DeliveryNote();
$orderObj = new Order();
$clientObj = new Client();

// Obține comenzile aprobate fără avize sau cu avize incomplete
$pendingOrders = $orderObj->getOrdersWithoutFullDeliveryNotes();

// Generează seria pentru noul aviz
$defaultSeries = "AV";
$nextNumber = $deliveryNoteObj->getNextDeliveryNoteNumber($defaultSeries);
$defaultDeliveryNoteNumber = $defaultSeries . sprintf('%06d', $nextNumber);

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'order_id' => isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0,
    'series' => $defaultSeries,
    'delivery_note_number' => $defaultDeliveryNoteNumber,
    'issue_date' => date('Y-m-d'),
    'notes' => ''
];

// Dacă avem un ID de comandă, pregătim datele specifice
if ($formData['order_id'] > 0) {
    $order = $orderObj->getOrderById($formData['order_id']);
    
    if (!$order) {
        setFlashMessage('error', 'Comanda selectată nu există.');
        redirect('index.php');
    }
    
    // Verifică dacă comanda este aprobată
    if ($order['status'] !== 'approved') {
        setFlashMessage('error', 'Puteți crea avize doar pentru comenzi aprobate.');
        redirect('index.php');
    }
    
    // Obține clientul și locația
    $client = $clientObj->getClientById($order['client_id']);
    $location = $clientObj->getLocationById($order['location_id']);
    
    // Obține produsele din comandă
    $orderItems = $orderObj->getOrderItems($formData['order_id']);
    
    // Verifică care produse pot fi incluse în aviz (care nu au fost deja livrate complet)
    $availableItems = [];
    foreach ($orderItems as $item) {
        $deliveredQuantity = $deliveryNoteObj->getDeliveredQuantityForOrderItem($item['id']);
        $remainingQuantity = $item['quantity'] - $deliveredQuantity;
        
        if ($remainingQuantity > 0) {
            $item['remaining_quantity'] = $remainingQuantity;
            $item['delivered_quantity'] = $deliveredQuantity;
            $availableItems[] = $item;
        }
    }
    
    // Dacă nu există produse disponibile pentru aviz
    if (empty($availableItems)) {
        setFlashMessage('error', 'Toate produsele din această comandă au fost deja livrate. Nu se poate crea un nou aviz.');
        redirect('index.php');
    }
}

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Preluare date formular
        $formData = [
            'order_id' => isset($_POST['order_id']) && is_numeric($_POST['order_id']) ? (int)$_POST['order_id'] : 0,
            'series' => sanitizeInput($_POST['series'] ?? ''),
            'delivery_note_number' => sanitizeInput($_POST['delivery_note_number'] ?? ''),
            'issue_date' => sanitizeInput($_POST['issue_date'] ?? ''),
            'notes' => sanitizeInput($_POST['notes'] ?? '')
        ];
        
        // Validare date
        $errors = [];
        
        if ($formData['order_id'] <= 0) {
            $errors[] = 'Selectați o comandă validă.';
        }
        
        if (empty($formData['series'])) {
            $errors[] = 'Seria avizului este obligatorie.';
        }
        
        if (empty($formData['delivery_note_number'])) {
            $errors[] = 'Numărul avizului este obligatoriu.';
        } else {
            // Verifică dacă numărul de aviz există deja
            if ($deliveryNoteObj->deliveryNoteNumberExists($formData['series'], $formData['delivery_note_number'])) {
                $errors[] = 'Acest număr de aviz există deja. Vă rugăm să folosiți un alt număr.';
            }
        }
        
        if (empty($formData['issue_date'])) {
            $errors[] = 'Data emiterii este obligatorie.';
        } elseif (!validateDate($formData['issue_date'])) {
            $errors[] = 'Formatul datei de emitere este invalid.';
        }
        
        // Obține produsele selectate din formular
        $selectedItems = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'item_') === 0 && is_numeric(substr($key, 5)) && $value == 1) {
                $itemId = (int)substr($key, 5);
                $quantity = isset($_POST['quantity_' . $itemId]) ? (float)$_POST['quantity_' . $itemId] : 0;
                
                if ($quantity <= 0) {
                    $errors[] = 'Cantitatea pentru produsele selectate trebuie să fie mai mare decât 0.';
                } else {
                    $selectedItems[$itemId] = $quantity;
                }
            }
        }
        
        if (empty($selectedItems)) {
            $errors[] = 'Trebuie să selectați cel puțin un produs.';
        }
        
        // Dacă nu există erori, procesăm crearea avizului
        if (empty($errors)) {
            // Obține comanda
            $order = $orderObj->getOrderById($formData['order_id']);
            
            // Pregătire date aviz
            $deliveryNoteData = [
                'order_id' => $formData['order_id'],
                'client_id' => $order['client_id'],
                'location_id' => $order['location_id'],
                'series' => $formData['series'],
                'delivery_note_number' => $formData['delivery_note_number'],
                'issue_date' => $formData['issue_date'],
                'status' => 'draft',
                'notes' => $formData['notes'],
                'created_by' => $_SESSION['user_id'] ?? null
            ];
            
            // Creează avizul
            $deliveryNoteId = $deliveryNoteObj->addDeliveryNote($deliveryNoteData);
            
            if ($deliveryNoteId) {
                // Adaugă produsele în aviz
                $itemsResult = true;
                
                foreach ($selectedItems as $itemId => $quantity) {
                    $orderItem = $orderObj->getOrderItemById($itemId);
                    
                    if ($orderItem) {
                        $deliveryNoteItemData = [
                            'delivery_note_id' => $deliveryNoteId,
                            'order_item_id' => $itemId,
                            'product_id' => $orderItem['product_id'],
                            'product_code' => $orderItem['product_code'],
                            'product_name' => $orderItem['product_name'],
                            'unit' => $orderItem['unit'],
                            'quantity' => $quantity,
                            'unit_price' => $orderItem['unit_price']
                        ];
                        
                        $result = $deliveryNoteObj->addDeliveryNoteItem($deliveryNoteItemData);
                        
                        if (!$result) {
                            $itemsResult = false;
                        }
                    } else {
                        $itemsResult = false;
                    }
                }
                
                if ($itemsResult) {
                    setFlashMessage('success', 'Avizul de livrare a fost creat cu succes.');
                    redirect('view.php?id=' . $deliveryNoteId);
                } else {
                    $error = 'A apărut o eroare la adăugarea produselor în aviz. Vă rugăm să verificați și să încercați din nou.';
                    // Șterge avizul creat pentru a evita avize incomplete
                    $deliveryNoteObj->deleteDeliveryNote($deliveryNoteId);
                }
            } else {
                $error = 'A apărut o eroare la crearea avizului. Vă rugăm să încercați din nou.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Titlu pagină
$pageTitle = 'Creare Aviz de Livrare - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div