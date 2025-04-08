<?php
// notifications/view.php
// Pagina pentru vizualizarea detaliilor unei notificări

// Inițializare sesiune și autentificare
require_once '../includes/auth.php';
authenticateUser();

// Include fișiere necesare
require_once '../classes/Notification.php';

// Verificare ID notificare
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID notificare invalid.');
    redirect('index.php');
}

$notification_id = (int)$_GET['id'];

// Inițializare obiecte
$notificationObj = new Notification();

// Obține notificarea
$notification = $notificationObj->getNotificationById($notification_id);

// Verificare existență notificare
if (!$notification) {
    setFlashMessage('error', 'Notificarea nu există.');
    redirect('index.php');
}

// Verificare permisiuni
if ($notification['user_id'] && $notification['user_id'] != $_SESSION['user_id']) {
    setFlashMessage('error', 'Nu aveți permisiunea să accesați această notificare.');
    redirect('index.php');
}

if ($notification['client_id'] && $notification['client_id'] != $_SESSION['client_id']) {
    setFlashMessage('error', 'Nu aveți permisiunea să accesați această notificare.');
    redirect('index.php');
}

// Marchează notificarea ca citită dacă nu e deja
if ($notification['is_read'] == 0) {
    $notificationObj->markAsRead($notification_id);
}

// Format pentru tipurile de notificări
$notificationIcons = [
    'order_new' => 'shopping-cart',
    'order_approved' => 'check-circle',
    'order_rejected' => 'times-circle',
    'delivery_note' => 'truck',
    'system' => 'info-circle',
    'price_change' => 'tag',
    'product_new' => 'box',
    'message' => 'envelope'
];

$notificationColors = [
    'order_new' => 'bg-blue-100 text-blue-600',
    'order_approved' => 'bg-green-100 text-green-600',
    'order_rejected' => 'bg-red-100 text-red-600',
    'delivery_note' => 'bg-yellow-100 text-yellow-600',
    'system' => 'bg-gray-100 text-gray-600',
    'price_change' => 'bg-purple-100 text-purple-600',
    'product_new' => 'bg-indigo-100 text-indigo-600',
    'message' => 'bg-blue-100 text-blue-600'
];

$icon = $notificationIcons[$notification['type']] ?? 'bell';
$colorClass = $notificationColors[$notification['type']] ?? 'bg-gray-100 text-gray-600';

// Titlu pagină
$pageTitle = 'Notificare - Platformă B2B';

// Include header
include_once '../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la notificări
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h1 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($notification['title']); ?></h1>
    </div>
    
    <div class="p-6">
        <div class="flex items-start mb-6">
            <div class="flex-shrink-0 mr-4">
                <div class="h-16 w-16 rounded-full <?php echo $colorClass; ?> flex items-center justify-center">
                    <i class="fas fa-<?php echo $icon; ?> fa-2x"></i>
                </div>
            </div>
            <div class="flex-1">
                <p class="text-lg text-gray-900"><?php echo htmlspecialchars($notification['message']); ?></p>
                <p class="text-sm text-gray-500 mt-2">
                    Primită la <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                </p>
            </div>
        </div>
        
        <?php if ($notification['link']): ?>
            <div class="mt-6 border-t border-gray-200 pt-6">
                <a href="<?php echo $basePath . $notification['link']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-external-link-alt mr-1"></i> Vezi detalii
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
        <a href="delete.php?id=<?php echo $notification['id']; ?>" class="text-red-600 hover:text-red-800 delete-confirm">
            <i class="fas fa-trash mr-1"></i> Șterge notificarea
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmare ștergere
    const deleteButton = document.querySelector('.delete-confirm');
    if (deleteButton) {
        deleteButton.addEventListener('click', function(e) {
            if (!confirm('Sunteți sigur că doriți să ștergeți această notificare?')) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>