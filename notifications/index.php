<?php
// notifications/index.php
// Pagina pentru listarea tuturor notificărilor

// Inițializare sesiune și autentificare
require_once '../includes/auth.php';
authenticateUser();

// Include fișiere necesare
require_once '../classes/Notification.php';

// Inițializare obiecte
$notificationObj = new Notification();

// Paginare
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Notificări per pagină
$offset = ($page - 1) * $limit;

// Filtrare
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$read_status = isset($_GET['read']) ? trim($_GET['read']) : '';

// Obține numărul total de notificări pentru paginare
if (empty($type) && empty($read_status)) {
    $total_notifications = $notificationObj->getTotalUserNotifications($_SESSION['user_id']);
} else {
    $total_notifications = $notificationObj->getTotalFilteredNotifications(
        $_SESSION['user_id'], 
        $type, 
        $read_status === 'read' ? 1 : ($read_status === 'unread' ? 0 : null)
    );
}

// Calculează numărul total de pagini
$total_pages = ceil($total_notifications / $limit);

// Asigură-te că pagina curentă este validă
if ($page < 1) {
    $page = 1;
} elseif ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

// Obține notificările
$notifications = $notificationObj->getFilteredUserNotifications(
    $_SESSION['user_id'], 
    $limit, 
    $offset, 
    $type, 
    $read_status === 'read' ? 1 : ($read_status === 'unread' ? 0 : null)
);

// Tipuri de notificări pentru filtrare
$notification_types = [
    'order_new' => 'Comandă nouă',
    'order_approved' => 'Comandă aprobată',
    'order_rejected' => 'Comandă respinsă',
    'delivery_note' => 'Aviz de livrare',
    'system' => 'Sistem',
    'price_change' => 'Modificare preț',
    'product_new' => 'Produs nou',
    'message' => 'Mesaj'
];

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

// Funcție pentru formatarea datei de creare
function formatNotificationDate($created_at) {
    $timestamp = strtotime($created_at);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'acum câteva secunde';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "acum $minutes " . ($minutes == 1 ? 'minut' : 'minute');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "acum $hours " . ($hours == 1 ? 'oră' : 'ore');
    } elseif ($diff < 172800) {
        return 'ieri la ' . date('H:i', $timestamp);
    } else {
        return date('d.m.Y H:i', $timestamp);
    }
}

// Titlu pagină
$pageTitle = 'Notificări - Platformă B2B';

// Include header
include_once '../includes/header.php';
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">Notificări</h1>
    
    <?php if ($total_notifications > 0): ?>
        <a href="mark_all_read.php" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-check-double mr-1"></i> Marchează toate ca citite
        </a>
    <?php endif; ?>
</div>

<!-- Filtre -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="index.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tip notificare</label>
            <select id="type" name="type" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toate tipurile</option>
                <?php foreach ($notification_types as $key => $value): ?>
                    <option value="<?php echo $key; ?>" <?php echo $type === $key ? 'selected' : ''; ?>>
                        <?php echo $value; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="read" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="read" name="read" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toate statusurile</option>
                <option value="read" <?php echo $read_status === 'read' ? 'selected' : ''; ?>>Citite</option>
                <option value="unread" <?php echo $read_status === 'unread' ? 'selected' : ''; ?>>Necitite</option>
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

<!-- Lista notificări -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Toate notificările</h2>
    </div>
    
    <div>
        <?php if (count($notifications) > 0): ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $icon = $notificationIcons[$notification['type']] ?? 'bell';
                    $colorClass = $notificationColors[$notification['type']] ?? 'bg-gray-100 text-gray-600';
                    $isRead = $notification['is_read'] == 1;
                    ?>
                    <div class="p-4 hover:bg-gray-50 <?php echo $isRead ? '' : 'bg-blue-50'; ?>">
                        <div class="flex">
                            <div class="flex-shrink-0 mr-4">
                                <div class="h-12 w-12 rounded-full <?php echo $colorClass; ?> flex items-center justify-center">
                                    <i class="fas fa-<?php echo $icon; ?> fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <p class="text-base font-medium text-gray-900">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo formatNotificationDate($notification['created_at']); ?>
                                    </p>
                                </div>
                                <p class="text-sm text-gray-700 mt-1">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
                                <div class="mt-2 flex justify-between items-center">
                                    <div class="flex space-x-3">
                                        <?php if ($notification['link']): ?>
                                            <a href="<?php echo $basePath . $notification['link']; ?>" class="text-sm text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-external-link-alt mr-1"></i> Vezi detalii
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!$isRead): ?>
                                            <a href="mark_read.php?id=<?php echo $notification['id']; ?>" class="text-sm text-green-600 hover:text-green-800">
                                                <i class="fas fa-check mr-1"></i> Marchează ca citit
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <a href="delete.php?id=<?php echo $notification['id']; ?>" class="text-sm text-red-600 hover:text-red-800 delete-confirm">
                                        <i class="fas fa-trash mr-1"></i> Șterge
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginare -->
            <?php if ($total_pages > 1): ?>
                <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Afișare <span class="font-medium"><?php echo ($page - 1) * $limit + 1; ?></span> - 
                                <span class="font-medium"><?php echo min($page * $limit, $total_notifications); ?></span> din 
                                <span class="font-medium"><?php echo $total_notifications; ?></span> rezultate
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <!-- Buton pagina anterioară -->
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $type ? '&type=' . $type : ''; ?><?php echo $read_status ? '&read=' . $read_status : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Anterior</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                        <span class="sr-only">Anterior</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Numerele paginilor -->
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">
                                            <?php echo $i; ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?><?php echo $type ? '&type=' . $type : ''; ?><?php echo $read_status ? '&read=' . $read_status : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <!-- Buton pagina următoare -->
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $type ? '&type=' . $type : ''; ?><?php echo $read_status ? '&read=' . $read_status : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Următor</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                        <span class="sr-only">Următor</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-bell-slash fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu aveți notificări</h3>
                <p class="text-gray-600 mb-4">
                    <?php if (!empty($type) || !empty($read_status)): ?>
                        Nu există notificări care să corespundă criteriilor selectate.
                    <?php else: ?>
                        Nu aveți notificări momentan.
                    <?php endif; ?>
                </p>
                <?php if (!empty($type) || !empty($read_status)): ?>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-filter mr-1"></i> Resetează filtrele
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
            if (!confirm('Sunteți sigur că doriți să ștergeți această notificare?')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>