<?php
// admin/users/view.php
// Pagina pentru vizualizarea detaliilor unui utilizator

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/User.php';
require_once '../../classes/Client.php';
require_once '../../classes/Order.php';

// Verificare ID utilizator
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID utilizator invalid.');
    redirect('index.php');
}

$user_id = (int)$_GET['id'];

// Inițializare obiecte
$userObj = new User();
$clientObj = new Client();
$orderObj = new Order();

// Obține informațiile utilizatorului
$user = $userObj->getUserById($user_id);

// Verificare existență utilizator
if (!$user) {
    setFlashMessage('error', 'Utilizatorul nu există.');
    redirect('index.php');
}

// Obține informații suplimentare
$client = null;
$location = null;
$recentOrders = [];
$totalOrders = 0;

if (!empty($user['client_id'])) {
    $client = $clientObj->getClientById($user['client_id']);
    
    if (!empty($user['location_id'])) {
        $location = $clientObj->getLocationById($user['location_id']);
    }
    
    // Obține comenzile recente ale utilizatorului
    $recentOrders = $orderObj->getOrdersByUser($user_id, 5);
    $totalOrders = $orderObj->countOrdersByUser($user_id);
}

// Verifică dacă sunt afișate mesaje flash
$success = isset($_SESSION['flash_messages']['success']) ? $_SESSION['flash_messages']['success'] : '';
$error = isset($_SESSION['flash_messages']['error']) ? $_SESSION['flash_messages']['error'] : '';

// Dacă există mesaje flash, le elimină după afișare
if (isset($_SESSION['flash_messages']['success'])) {
    unset($_SESSION['flash_messages']['success']);
}
if (isset($_SESSION['flash_messages']['error'])) {
    unset($_SESSION['flash_messages']['error']);
}

// Titlu pagină
$pageTitle = 'Detalii Utilizator - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de utilizatori
    </a>
</div>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $success; ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Detalii Utilizator</h1>
    
    <div class="flex space-x-2">
        <a href="edit.php?id=<?php echo $user_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
            <i class="fas fa-edit mr-1"></i> Editează
        </a>
        
        <?php if ($user_id != $_SESSION['user_id']): ?>
            <?php if ($user['status'] === 'active'): ?>
                <a href="status.php?id=<?php echo $user_id; ?>&status=inactive&csrf_token=<?php echo generateCSRFToken(); ?>" 
                   class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-ban mr-1"></i> Dezactivează
                </a>
            <?php else: ?>
                <a href="status.php?id=<?php echo $user_id; ?>&status=active&csrf_token=<?php echo generateCSRFToken(); ?>" 
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-check-circle mr-1"></i> Activează
                </a>
            <?php endif; ?>
            
            <a href="delete.php?id=<?php echo $user_id; ?>" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md delete-confirm">
                <i class="fas fa-trash mr-1"></i> Șterge
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Status utilizator -->
<div class="mb-6">
    <?php 
    $statusLabels = [
        'active' => ['text' => 'Activ', 'class' => 'bg-green-100 text-green-800 border-green-300'],
        'inactive' => ['text' => 'Inactiv', 'class' => 'bg-red-100 text-red-800 border-red-300']
    ];
    $statusInfo = $statusLabels[$user['status']] ?? ['text' => 'Necunoscut', 'class' => 'bg-gray-100 text-gray-800 border-gray-300'];
    ?>
    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $statusInfo['class']; ?> border">
        <?php echo $statusInfo['text']; ?>
    </span>
    
    <?php 
    $roleLabels = [
        'admin' => ['text' => 'Administrator', 'class' => 'bg-purple-100 text-purple-800 border-purple-300'],
        'agent' => ['text' => 'Agent', 'class' => 'bg-green-100 text-green-800 border-green-300'],
        'client_admin' => ['text' => 'Administrator Client', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
        'client_user' => ['text' => 'Utilizator Client', 'class' => 'bg-yellow-100 text-yellow-800 border-yellow-300']
    ];
    $roleInfo = $roleLabels[$user['role']] ?? ['text' => 'Necunoscut', 'class' => 'bg-gray-100 text-gray-800 border-gray-300'];
    ?>
    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $roleInfo['class']; ?> border ml-2">
        <?php echo $roleInfo['text']; ?>
    </span>
</div>

<!-- Informații generale -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Informații generale</h2>
    </div>
    
    <div class="p-6">
        <div class="flex items-center mb-8">
            <div class="flex-shrink-0 h-24 w-24 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-2xl font-bold">
                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
            </div>
            <div class="ml-6">
                <h3 class="text-xl font-medium text-gray-900"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                <?php if ($client): ?>
                    <p class="text-gray-500 mt-1">
                        Client: <a href="../clients/view.php?id=<?php echo $client['id']; ?>" class="text-blue-600 hover:underline">
                            <?php echo htmlspecialchars($client['company_name']); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-base font-medium text-gray-900 mb-3">Detalii cont</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3">
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Rol</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $roleInfo['text']; ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $statusInfo['text']; ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Data înregistrării</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Ultima actualizare</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo date('d.m.Y H:i', strtotime($user['updated_at'])); ?></dd>
                    </div>
                </dl>
            </div>
            
            <?php if ($client): ?>
            <div>
                <h3 class="text-base font-medium text-gray-900 mb-3">Informații client</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3">
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Companie</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="../clients/view.php?id=<?php echo $client['id']; ?>" class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($client['company_name']); ?>
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">CUI</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($client['fiscal_code']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Telefon companie</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($client['phone']); ?></dd>
                    </div>
                    <?php if ($location): ?>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Locație</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($location['name']); ?></dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Adresă locație</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($location['address']); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (in_array($user['role'], ['client_admin', 'client_user']) && !empty($recentOrders)): ?>
<!-- Comenzi recente -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
        <h2 class="text-lg font-semibold">Comenzi recente</h2>
        <?php if ($totalOrders > count($recentOrders)): ?>
            <a href="../orders/index.php?user_id=<?php echo $user_id; ?>" class="text-sm text-blue-600 hover:underline">
                Vezi toate comenzile (<?php echo $totalOrders; ?>)
            </a>
        <?php endif; ?>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nr. Comandă
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Data
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Locație
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acțiuni
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($order['order_number']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo date('d.m.Y', strtotime($order['order_date'])); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($order['location_name']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $statusLabels = [
                                'pending' => ['text' => 'În așteptare', 'class' => 'bg-yellow-100 text-yellow-800'],
                                'approved' => ['text' => 'Aprobată', 'class' => 'bg-green-100 text-green-800'],
                                'rejected' => ['text' => 'Respinsă', 'class' => 'bg-red-100 text-red-800'],
                                'completed' => ['text' => 'Finalizată', 'class' => 'bg-blue-100 text-blue-800']
                            ];
                            $statusInfo = $statusLabels[$order['status']] ?? ['text' => 'Necunoscut', 'class' => 'bg-gray-100 text-gray-800'];
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusInfo['class']; ?>">
                                <?php echo $statusInfo['text']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php echo formatAmount($order['total_amount']); ?> Lei
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                            <a href="../orders/view.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmare ștergere
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Ești sigur că vrei să ștergi acest utilizator? Această acțiune nu poate fi anulată.')) {
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