<?php
// includes/notifications.php
require_once __DIR__ . '/../classes/Notification.php';

// Verifică dacă utilizatorul este autentificat
if (isLoggedIn()) {
    $notificationObj = new Notification();
    $unreadCount = $notificationObj->getUnreadCount($_SESSION['user_id']);
    $notifications = $notificationObj->getUserNotifications($_SESSION['user_id'], 5);
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
?>

<!-- Dropdown notificări pentru desktop -->
<div class="hidden sm:ml-4 sm:flex sm:items-center">
    <div class="ml-3 relative">
        <div>
            <button type="button" class="dropdown-toggle-notification bg-white flex text-sm rounded-full focus:outline-none" id="notification-menu-button" aria-expanded="false" aria-haspopup="true">
                <span class="sr-only">Open notifications</span>
                <div class="relative">
                    <i class="fas fa-bell text-gray-500 text-xl"></i>
                    <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full h-5 w-5 flex items-center justify-center text-xs">
                            <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </button>
        </div>
        
        <div class="dropdown-menu-notification hidden origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10" role="menu" aria-orientation="vertical" aria-labelledby="notification-menu-button" tabindex="-1">
            <div class="p-3 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-base font-semibold text-gray-800">Notificări</h3>
                
                <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                    <a href="<?php echo $basePath; ?>notifications/mark_all_read.php" class="text-xs text-blue-600 hover:text-blue-800">
                        Marchează toate ca citite
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="max-h-96 overflow-y-auto">
                <?php if (isset($notifications) && count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                        $icon = $notificationIcons[$notification['type']] ?? 'bell';
                        $colorClass = $notificationColors[$notification['type']] ?? 'bg-gray-100 text-gray-600';
                        $isRead = $notification['is_read'] == 1;
                        ?>
                        <a href="<?php echo $basePath . 'notifications/view.php?id=' . $notification['id']; ?>" class="block px-4 py-3 hover:bg-gray-50 <?php echo $isRead ? '' : 'bg-blue-50'; ?>">
                            <div class="flex">
                                <div class="flex-shrink-0 mr-3">
                                    <div class="h-10 w-10 rounded-full <?php echo $colorClass; ?> flex items-center justify-center">
                                        <i class="fas fa-<?php echo $icon; ?>"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500 truncate">
                                        <?php echo htmlspecialchars(substr($notification['message'], 0, 60)) . (strlen($notification['message']) > 60 ? '...' : ''); ?>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <?php echo formatNotificationDate($notification['created_at']); ?>
                                    </p>
                                </div>
                                <?php if (!$isRead): ?>
                                    <div class="flex-shrink-0 ml-2">
                                        <div class="h-2 w-2 rounded-full bg-blue-600"></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-gray-500">
                        Nu aveți notificări.
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="p-2 border-t border-gray-200">
                <a href="<?php echo $basePath; ?>notifications/" class="block w-full text-center bg-gray-50 hover:bg-gray-100 py-2 text-sm text-gray-700 rounded">
                    Vezi toate notificările
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown notificări
    const notificationToggle = document.querySelector('.dropdown-toggle-notification');
    const notificationMenu = document.querySelector('.dropdown-menu-notification');
    
    if (notificationToggle && notificationMenu) {
        notificationToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            notificationMenu.classList.toggle('hidden');
        });
        
        // Ascunde dropdown-ul când se face click în altă parte
        document.addEventListener('click', function(e) {
            if (!notificationToggle.contains(e.target) && !notificationMenu.contains(e.target)) {
                notificationMenu.classList.add('hidden');
            }
        });
    }
});
</script>