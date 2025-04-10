<?php
// admin/users/index.php
// Pagina principală pentru gestionarea utilizatorilor

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/User.php';

// Inițializare obiecte
$userObj = new User();

// Parametri paginare și filtrare
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // Utilizatori per pagină
$offset = ($page - 1) * $limit;

// Parametri filtrare
$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Obține utilizatorii
if (!empty($search)) {
    $users = $userObj->searchUsers($search, $limit, $offset);
    $totalUsers = $userObj->countSearchResults($search);
} elseif (!empty($role) && !empty($status)) {
    $users = $userObj->getUsersByRoleAndStatus($role, $status, $limit, $offset);
    $totalUsers = $userObj->countUsersByRoleAndStatus($role, $status);
} elseif (!empty($role)) {
    $users = $userObj->getUsersByRole($role, $limit, $offset);
    $totalUsers = $userObj->countUsersByRole($role);
} elseif (!empty($status)) {
    $users = $userObj->getUsersByStatus($status, $limit, $offset);
    $totalUsers = $userObj->countUsersByStatus($status);
} else {
    $users = $userObj->getAllUsersPaginated($limit, $offset);
    $totalUsers = $userObj->getTotalUsers();
}

// Calculează numărul de pagini
$totalPages = ceil($totalUsers / $limit);

// Titlu pagină
$pageTitle = 'Gestionare Utilizatori - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">Gestionare Utilizatori</h1>
    
    <div>
        <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
            <i class="fas fa-plus mr-1"></i> Adaugă utilizator
        </a>
    </div>
</div>

<!-- Statistici rapide -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                <i class="fas fa-users text-blue-500"></i>
            </div>
            <div class="ml-4">
                <h2 class="font-semibold text-gray-900">Total Utilizatori</h2>
                <p class="text-2xl font-bold"><?php echo $userObj->getTotalUsers(); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                <i class="fas fa-user-shield text-green-500"></i>
            </div>
            <div class="ml-4">
                <h2 class="font-semibold text-gray-900">Administratori</h2>
                <p class="text-2xl font-bold"><?php echo $userObj->countUsersByRole('admin'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-100 rounded-full p-3">
                <i class="fas fa-building text-purple-500"></i>
            </div>
            <div class="ml-4">
                <h2 class="font-semibold text-gray-900">Clienți</h2>
                <p class="text-2xl font-bold"><?php echo $userObj->countUsersByRole('client'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                <i class="fas fa-user-lock text-red-500"></i>
            </div>
            <div class="ml-4">
                <h2 class="font-semibold text-gray-900">Utilizatori Inactivi</h2>
                <p class="text-2xl font-bold"><?php echo $userObj->countUsersByStatus('inactive'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filtre și căutare -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="index.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
            <select id="role" name="role" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toate rolurile</option>
                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                <option value="agent" <?php echo $role === 'agent' ? 'selected' : ''; ?>>Agent</option>
                <option value="client_admin" <?php echo $role === 'client_admin' ? 'selected' : ''; ?>>Administrator Client</option>
                <option value="client" <?php echo $role === 'client' ? 'selected' : ''; ?>>Client</option>
            </select>
        </div>
        
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Toate statusurile</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activ</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactiv</option>
            </select>
        </div>
        
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Caută</label>
            <div class="relative rounded-md shadow-sm">
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Nume, email sau telefon..." 
                       class="block w-full pr-10 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
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

<!-- Lista utilizatori -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Lista utilizatori</h2>
        <p class="text-sm text-gray-500 mt-1">Total: <?php echo $totalUsers; ?> utilizatori</p>
    </div>
    
    <div class="overflow-x-auto">
        <?php if (count($users) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Utilizator
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rol
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Client
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ultima Autentificare
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acțiuni
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-gray-100 rounded-full">
                                        <?php if (!empty($user['profile_image']) && file_exists('../../uploads/users/' . $user['profile_image'])): ?>
                                            <img src="../../uploads/users/<?php echo $user['profile_image']; ?>" alt="<?php echo htmlspecialchars($user['first_name']); ?>" class="h-10 w-10 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="text-gray-500 text-xl">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '-'; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        Administrator
                                    </span>
                                <?php elseif ($user['role'] === 'agent'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Agent
                                    </span>
                                <?php elseif ($user['role'] === 'client_admin'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Admin Client
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Client
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php 
                                if ($user['client_id'] && isset($user['company_name'])) {
                                    echo htmlspecialchars($user['company_name']);
                                } else {
                                    echo $user['role'] === 'admin' || $user['role'] === 'agent' ? 'N/A' : '-';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Activ
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Inactiv
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo !empty($user['last_login']) ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Niciodată'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="view.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2" title="Vizualizare">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-2" title="Editare">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($user['status'] === 'active'): ?>
                                    <a href="status.php?id=<?php echo $user['id']; ?>&action=deactivate" class="text-red-600 hover:text-red-900 status-confirm" data-message="Ești sigur că vrei să dezactivezi acest utilizator?" title="Dezactivare">
                                        <i class="fas fa-user-slash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="status.php?id=<?php echo $user['id']; ?>&action=activate" class="text-green-600 hover:text-green-900 status-confirm" data-message="Ești sigur că vrei să activezi acest utilizator?" title="Activare">
                                        <i class="fas fa-user-check"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginare -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Afișare <span class="font-medium"><?php echo ($page - 1) * $limit + 1; ?></span> - 
                            <span class="font-medium"><?php echo min($page * $limit, $totalUsers); ?></span> din 
                            <span class="font-medium"><?php echo $totalUsers; ?></span> utilizatori
                        </div>
                        
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($role) ? '&role=' . $role : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1 rounded-md bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($role) ? '&role=' . $role : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($role) ? '&role=' . $role : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1 rounded-md bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu au fost găsiți utilizatori</h3>
                <p class="text-gray-600 mb-4">
                    <?php if (!empty($search) || !empty($role) || !empty($status)): ?>
                        Nu există utilizatori care să corespundă criteriilor de filtrare selectate.
                    <?php else: ?>
                        Nu există utilizatori înregistrați în sistem.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || !empty($role) || !empty($status)): ?>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-times mr-1"></i> Resetează filtrele
                    </a>
                <?php else: ?>
                    <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                        <i class="fas fa-plus mr-1"></i> Adaugă primul utilizator
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmare schimbare status
    const statusButtons = document.querySelectorAll('.status-confirm');
    statusButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-message') || 'Ești sigur că vrei să schimbi statusul acestui utilizator?';
            if (!confirm(message)) {
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