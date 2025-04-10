<?php
// admin/delivery-notes/print.php
// Pagina pentru printarea unui aviz de livrare

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/DeliveryNote.php';
require_once '../../classes/Order.php';
require_once '../../classes/Client.php';
require_once '../../classes/Company.php';

// Verificare ID aviz
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID aviz invalid.');
}

$delivery_note_id = (int)$_GET['id'];

// Inițializare obiecte
$deliveryNoteObj = new DeliveryNote();
$orderObj = new Order();
$clientObj = new Client();
$companyObj = new Company();

// Obține informațiile avizului
$deliveryNote = $deliveryNoteObj->getDeliveryNoteById($delivery_note_id);

// Verificare existență aviz
if (!$deliveryNote) {
    die('Avizul nu există.');
}

// Obține detaliile comenzii asociate
$order = $orderObj->getOrderById($deliveryNote['order_id']);

// Obține detaliile clientului
$client = $clientObj->getClientById($deliveryNote['client_id']);

// Obține locația de livrare
$location = $clientObj->getLocationById($deliveryNote['location_id']);

// Obține produsele din aviz
$deliveryNoteItems = $deliveryNoteObj->getDeliveryNoteItems($delivery_note_id);

// Obține informațiile companiei emitente
$company = $companyObj->getCompanyInfo();

// Formatare număr aviz
$deliveryNoteNumber = $deliveryNote['series'] . $deliveryNote['delivery_note_number'];

// Funcție pentru formatarea datei în format românesc
function formatDateRo($date) {
    return date('d.m.Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviz de Livrare <?php echo $deliveryNoteNumber; ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm 0;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10mm;
        }
        
        .header-left {
            width: 60%;
        }
        
        .header-right {
            width: 35%;
            text-align: right;
        }
        
        .document-title {
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            margin: 10mm 0;
            text-transform: uppercase;
        }
        
        .section {
            margin-bottom: 7mm;
        }
        
        .section-title {
            font-weight: bold;
            margin-bottom: 3mm;
            padding-bottom: 1mm;
            border-bottom: 1px solid #ccc;
        }
        
        .company-info, .client-info {
            display: flex;
            flex-direction: column;
        }
        
        .company-name, .client-name {
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 2mm;
        }
        
        .info-row {
            margin-bottom: 1mm;
        }
        
        .label {
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
        }
        
        th, td {
            border: 1px solid #ccc;
            padding: 2mm;
            text-align: left;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-row {
            font-weight: bold;
        }
        
        .notes {
            margin-top: 10mm;
            padding: 3mm;
            border: 1px solid #ccc;
            min-height: 15mm;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 20mm;
        }
        
        .signature-block {
            width: 45%;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 15mm;
            padding-top: 2mm;
            text-align: center;
        }
        
        .page-number {
            text-align: center;
            margin-top: 10mm;
            font-size: 10pt;
            color: #666;
        }
        
        .status-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72pt;
            opacity: 0.15;
            color: red;
            pointer-events: none;
            text-transform: uppercase;
            width: 100%;
            text-align: center;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="padding: 10px; background: #f0f0f0; text-align: center; margin-bottom: 10px;">
        <button onclick="window.print();" style="padding: 5px 10px; cursor: pointer;">Printează Avizul</button>
        <button onclick="window.location='view.php?id=<?php echo $delivery_note_id; ?>';" style="padding: 5px 10px; margin-left: 10px; cursor: pointer;">Înapoi la Aviz</button>
    </div>
    
    <?php if ($deliveryNote['status'] === 'cancelled'): ?>
    <div class="status-watermark">ANULAT</div>
    <?php endif; ?>
    
    <div class="container">
        <div class="header">
            <div class="header-left">
                <div class="company-info">
                    <div class="company-name"><?php echo htmlspecialchars($company['name']); ?></div>
                    <div class="info-row">CUI: <?php echo htmlspecialchars($company['fiscal_code']); ?></div>
                    <div class="info-row">Reg. Com.: <?php echo htmlspecialchars($company['registration_number']); ?></div>
                    <div class="info-row">Adresa: <?php echo htmlspecialchars($company['address']); ?></div>
                    <div class="info-row">Telefon: <?php echo htmlspecialchars($company['phone']); ?></div>
                    <div class="info-row">Email: <?php echo htmlspecialchars($company['email']); ?></div>
                </div>
            </div>
            <div class="header-right">
                <div>
                    <div><span class="label">Nr. Aviz:</span> <?php echo htmlspecialchars($deliveryNoteNumber); ?></div>
                    <div><span class="label">Data emitere:</span> <?php echo formatDateRo($deliveryNote['issue_date']); ?></div>
                    <div><span class="label">Nr. Comandă:</span> <?php echo htmlspecialchars($order['order_number']); ?></div>
                    <?php if (!empty($deliveryNote['delivery_date'])): ?>
                    <div><span class="label">Data livrare:</span> <?php echo formatDateRo($deliveryNote['delivery_date']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="document-title">Aviz de Însoțire a Mărfii</div>
        
        <div class="section">
            <div class="section-title">Client</div>
            <div class="client-info">
                <div class="client-name"><?php echo htmlspecialchars($client['company_name']); ?></div>
                <div class="info-row">CUI: <?php echo htmlspecialchars($client['fiscal_code']); ?></div>
                <div class="info-row">Reg. Com.: <?php echo htmlspecialchars($client['company_code']); ?></div>
                <div class="info-row">Adresa: <?php echo htmlspecialchars($client['address']); ?></div>
                <div class="info-row">Telefon: <?php echo htmlspecialchars($client['phone']); ?></div>
                <div class="info-row">Email: <?php echo htmlspecialchars($client['email']); ?></div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Adresa de livrare</div>
            <div>
                <div><span class="label">Locație:</span> <?php echo htmlspecialchars($location['name']); ?></div>
                <div><span class="label">Adresa:</span> <?php echo htmlspecialchars($location['address']); ?></div>
                <div><span class="label">Persoana de contact:</span> <?php echo htmlspecialchars($location['contact_person']); ?></div>
                <div><span class="label">Telefon:</span> <?php echo htmlspecialchars($location['phone']); ?></div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Produse</div>
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%;">Nr.</th>
                        <th style="width: 15%;">Cod</th>
                        <th style="width: 40%;">Denumire Produs</th>
                        <th style="width: 10%;">U.M.</th>
                        <th class="text-right" style="width: 10%;">Cantitate</th>
                        <th class="text-right" style="width: 10%;">Preț unitar</th>
                        <th class="text-right" style="width: 10%;">Valoare</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    $nr = 1;
                    foreach ($deliveryNoteItems as $item): 
                        $itemTotal = $item['quantity'] * $item['unit_price'];
                        $total += $itemTotal;
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $nr++; ?></td>
                        <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td class="text-right"><?php echo number_format($item['quantity'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?> Lei</td>
                        <td class="text-right"><?php echo number_format($itemTotal, 2); ?> Lei</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="6" class="text-right">Total:</td>
                        <td class="text-right"><?php echo number_format($total, 2); ?> Lei</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if (!empty($deliveryNote['notes'])): ?>
        <div class="section">
            <div class="section-title">Observații</div>
            <div class="notes">
                <?php echo nl2br(htmlspecialchars($deliveryNote['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line">Semnătura și ștampila furnizor</div>
            </div>
            <div class="signature-block">
                <div class="signature-line">Semnătura de primire</div>
            </div>
        </div>
        
        <div class="page-number">Pagina 1 din 1</div>
    </div>
</body>
</html>