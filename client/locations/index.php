<?php
// client/locations/index.php
// Pagina pentru gestionarea locațiilor clientului

// Inițializare sesiune și autentificare client admin
require_once '../../includes/auth.php';
authenticateClientAdmin();

// Include fișiere necesare
require_once '../../classes/Client.php';

// Inițializare obiecte
$clientObj = new Client();

// Obține locațiile clientului
$locations = $clientObj->getClientLocations($_SESSION['client_id']);

// Titlu pagină
$pageTitle = 'Locații - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Locațiile mele</h1>
    
    <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
        <i class="fas fa-plus mr-1"></i> Adaugă locație
    </a>
</div>

<!-- Lista locații -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Locații înregistrate</h2>
    </div>
    
    <div class="p-4">
        <?php if (count($locations) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($locations as $location): ?>
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow duration-300 <?php echo $_SESSION['location_id'] == $location['id'] ? 'bg-blue-50 border-blue-300' : ''; ?>">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-medium text-lg"><?php echo htmlspecialchars($location['name']); ?></h3>
                            
                            <?php if ($_SESSION['location_id'] == $location['id']): ?>
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                    Locația dvs.
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-gray-600"><?php echo htmlspecialchars($location['address']); ?></p>
                            
                            <?php if ($location['contact_person']): ?>
                                <p class="text-gray-600 mt-2">
                                    <span class="font-medium">Contact:</span> <?php echo htmlspecialchars($location['contact_person']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($location['phone']): ?>
                                <p class="text-gray-600">
                                    <span class="font-medium">Telefon:</span> <?php echo htmlspecialchars($location['phone']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($location['email']): ?>
                                <p class="text-gray-600">
                                    <span class="font-medium">Email:</span> <?php echo htmlspecialchars($location['email']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex justify-end space-x-2 pt-2 border-t border-gray-100">
                            <a href="edit.php?id=<?php echo $location['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i> Editează
                            </a>
                            
                            <?php if (count($locations) > 1): ?>
                                <a href="delete.php?id=<?php echo $location['id']; ?>" class="text-red-600 hover:text-red-800 delete-confirm">
                                    <i class="fas fa-trash"></i> Șterge
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-map-marker-alt fa-3x text-gray-300 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nu există locații înregistrate</h3>
                <p class="text-gray-600 mb-4">Adăugați prima locație pentru a putea plasa comenzi.</p>
                <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                    <i class="fas fa-plus mr-1"></i> Adaugă locație
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
            <h3 class="text-sm font-medium text-blue-800">Informații despre locații</h3>
            <div class="mt-2 text-sm text-blue-700">
                <p>Locațiile sunt adresele unde pot fi livrate produsele comandate. Pentru fiecare locație, puteți avea utilizatori specifici care pot plasa comenzi în numele acesteia.</p>
                <p class="mt-1">Locația marcată ca fiind "Locația dvs." este cea asociată contului dvs. curent.</p>
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
            if (!confirm('Sunteți sigur că doriți să ștergeți această locație? Această acțiune nu poate fi anulată.')) {
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