<?php
// client/users/index.php
// Pagina pentru gestionarea utilizatorilor clientului

// Inițializare sesiune și autentificare client admin
require_once '../../includes/auth.php';
authenticateClientAdmin();

// Include fișiere necesare
require_once '../../classes/User.php';
require_once '../../classes/Client.php';

// Inițializare obiecte
$userObj = new User();
$clientObj = new Client();

// Obține utilizatorii clientului
$users = $userObj->getClientUsers($_SESSION['client_id']);

// Obține locațiile clientului pentru filtru
$locations = $clientObj->getClientLocations($_SESSION['client_id']);

// Titlu pagină
$pageTitle = 'Utilizatori - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Utilizatori</h1>
    
    <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
        <i class="fas fa-user-plus mr-1"></i> Adaugă utilizator
    </a>
</div>

<!-- Lista utilizatori -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Utilizatori înregistrați</h2>
    </div>
    
    <div class="p-4">
        <?php if (count($users) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nume
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Locație
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rol
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acțiuni
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($user['location_name'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo $user['role'] == 'client_admin' ? 'Administrator' : 'Utilizator'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $user['status'] == 'active' ? 'Activ' : 'Inactiv'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="edit.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i> Editează
                                    </a>
                                    
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="delete.php?id=<?php echo $user['id']; ?>" class="text-red-600 hover:text-red-900 delete-confirm">
                                            <i class="fas fa-trash"></i> Șterge
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu există utilizatori înregistrați</h3>
                <p class="text-gray-600 mb-4">Adăugați primul utilizator pentru a putea gestiona accesul la platformă.</p>
                <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                    <i class="fas fa-user-plus mr-1"></i> Adaugă utilizator
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-500 mt-1"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Informații despre utilizatori</h3>
            <div class="mt-2 text-sm text-blue-700">
                <p>Puteți adăuga mai mulți utilizatori pentru compania dvs., fiecare putând fi asociat cu o locație specifică sau având rol de administrator.</p>
                <p class="mt-1"><strong>Administratori</strong> - pot gestiona toate locațiile, utilizatorii și pot vedea toate comenzile.</p>
                <p class="mt-1"><strong>Utilizatori</strong> - pot plasa comenzi doar pentru locația asociată lor.</p>
                <p class="mt-2">Nu puteți șterge propriul cont de utilizator.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurare confirmare ștergere
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Sunteți sigur că doriți să ștergeți acest utilizator? Această acțiune nu poate fi anulată.')) {
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