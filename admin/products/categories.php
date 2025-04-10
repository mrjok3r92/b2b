<?php
// admin/products/categories.php
// Pagina pentru gestionarea categoriilor de produse

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Product.php';

// Inițializare obiecte
$productObj = new Product();

// Acțiuni CRUD pentru categorii
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$category_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'name' => '',
    'description' => '',
    'parent_id' => null
];

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Preluare date formular
        $formData = [
            'name' => sanitizeInput($_POST['name'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? '')
        ];
        
        // Validare date
        $errors = [];
        
        if (empty($formData['name'])) {
            $errors[] = 'Numele categoriei este obligatoriu.';
        }
        
        // Procesare în funcție de acțiune
        if (empty($errors)) {
            if ($action === 'add') {
                // Adăugare categorie nouă
                $result = $productObj->addCategory($formData);
                
                if ($result) {
                    setFlashMessage('success', 'Categoria a fost adăugată cu succes.');
                    redirect('categories.php');
                } else {
                    $error = 'A apărut o eroare la adăugarea categoriei. Vă rugăm să încercați din nou.';
                }
            } elseif ($action === 'edit' && $category_id > 0) {
                // Actualizare categorie existentă
                $formData['id'] = $category_id;
                $result = $productObj->updateCategory($formData);
                
                if ($result) {
                    setFlashMessage('success', 'Categoria a fost actualizată cu succes.');
                    redirect('categories.php');
                } else {
                    $error = 'A apărut o eroare la actualizarea categoriei. Vă rugăm să încercați din nou.';
                }
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
} elseif ($action === 'delete' && $category_id > 0 && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Verificare token CSRF pentru ștergere
    if (!isset($_GET['csrf_token']) || !verifyCSRFToken($_GET['csrf_token'])) {
        setFlashMessage('error', 'Eroare de securitate. Vă rugăm să încercați din nou.');
        redirect('categories.php');
    }
    
    // Verificare dacă categoria are produse asociate
    $productCount = $productObj->countProductsByCategory($category_id);
    
    if ($productCount > 0) {
        setFlashMessage('error', 'Categoria nu poate fi ștearsă deoarece are produse asociate. Vă rugăm să mutați produsele în altă categorie înainte de ștergere.');
        redirect('categories.php');
    }
    
    // Ștergere categorie
    $result = $productObj->deleteCategory($category_id);
    
    if ($result) {
        setFlashMessage('success', 'Categoria a fost ștearsă cu succes.');
    } else {
        setFlashMessage('error', 'A apărut o eroare la ștergerea categoriei. Vă rugăm să încercați din nou.');
    }
    
    redirect('categories.php');
} elseif ($action === 'edit' && $category_id > 0) {
    // Obținere date categorie pentru editare
    $category = $productObj->getCategoryById($category_id);
    
    if (!$category) {
        setFlashMessage('error', 'Categoria nu există.');
        redirect('categories.php');
    }
    
    $formData = [
        'name' => $category['name'],
        'description' => $category['description']
    ];
}

// Obține toate categoriile pentru afișare
$categories = $productObj->getAllCategories();

// Titlu pagină
$pageTitle = 'Gestionare Categorii - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de produse
    </a>
</div>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Gestionare Categorii</h1>
    
    <div>
        <?php if ($action === 'list'): ?>
            <a href="categories.php?action=add" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-plus mr-1"></i> Adaugă categorie
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['flash_messages']['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $_SESSION['flash_messages']['success']; ?></span>
    </div>
    <?php unset($_SESSION['flash_messages']['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_messages']['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $_SESSION['flash_messages']['error']; ?></span>
    </div>
    <?php unset($_SESSION['flash_messages']['error']); ?>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- Lista categoriilor -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Lista categoriilor</h2>
        </div>
        
        <div class="overflow-x-auto">
            <?php if (count($categories) > 0): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nume categorie
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Descriere
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nr. produse
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acțiuni
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($categories as $category): ?>
                            <?php $productCount = $productObj->countProductsByCategory($category['id']); ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500">
                                        <?php 
                                        if (!empty($category['description'])) {
                                            echo htmlspecialchars(mb_substr($category['description'], 0, 100));
                                            if (mb_strlen($category['description']) > 100) {
                                                echo '...';
                                            }
                                        } else {
                                            echo '<span class="italic text-gray-400">Fără descriere</span>';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $productCount; ?> produse
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-edit"></i> Editează
                                    </a>
                                    <?php if ($productCount == 0): ?>
                                        <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>&confirm=yes&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                           class="text-red-600 hover:text-red-900 delete-confirm">
                                            <i class="fas fa-trash"></i> Șterge
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 cursor-not-allowed" title="Categoria nu poate fi ștearsă deoarece are produse asociate">
                                            <i class="fas fa-trash"></i> Șterge
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-tag fa-3x text-gray-300 mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Nu există categorii</h3>
                    <p class="text-gray-600 mb-4">
                        Nu există categorii înregistrate în sistem.
                    </p>
                    <a href="categories.php?action=add" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">
                        <i class="fas fa-plus mr-1"></i> Adaugă prima categorie
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- Formular adăugare/editare categorie -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">
                <?php echo $action === 'add' ? 'Adaugă categorie nouă' : 'Editează categoria'; ?>
            </h2>
        </div>
        
        <div class="p-6">
            <form method="POST" action="categories.php?action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $category_id : ''; ?>" class="space-y-6">
                <!-- CSRF token -->
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nume categorie -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nume categorie <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" required
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    
                    <!-- Descriere -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Descriere</label>
                        <textarea id="description" name="description" rows="3"
                                  class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Opțional. O scurtă descriere a categoriei.</p>
                    </div>
                </div>
                
                <!-- Butoane -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="categories.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Anulează
                    </a>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-1"></i> 
                        <?php echo $action === 'add' ? 'Adaugă categoria' : 'Salvează modificările'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmare ștergere categorie
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Ești sigur că vrei să ștergi această categorie? Această acțiune nu poate fi anulată.')) {
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