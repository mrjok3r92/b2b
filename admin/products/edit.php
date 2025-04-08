<?php
// admin/products/edit.php
// Pagina pentru editarea unui produs existent

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Product.php';

// Verificare ID produs
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID produs invalid.');
    redirect('index.php');
}

$product_id = (int)$_GET['id'];

// Inițializare obiecte
$productObj = new Product();

// Obține informațiile produsului
$product = $productObj->getProductById($product_id);

// Verificare existență produs
if (!$product) {
    setFlashMessage('error', 'Produsul nu există.');
    redirect('index.php');
}

// Obține categoriile pentru selectare
$categories = $productObj->getAllCategories();

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'category_id' => $product['category_id'],
    'code' => $product['code'],
    'name' => $product['name'],
    'description' => $product['description'],
    'unit' => $product['unit'],
    'price' => $product['price'],
    'status' => $product['status']
];

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Preluare date formular
        $formData = [
            'category_id' => isset($_POST['category_id']) && is_numeric($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'code' => sanitizeInput($_POST['code'] ?? ''),
            'name' => sanitizeInput($_POST['name'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'unit' => sanitizeInput($_POST['unit'] ?? ''),
            'price' => isset($_POST['price']) ? (float)str_replace(',', '.', $_POST['price']) : 0,
            'status' => $_POST['status'] ?? 'active',
        ];
        
        // Validare date
        $errors = [];
        
        if (empty($formData['code'])) {
            $errors[] = 'Codul produsului este obligatoriu.';
        }
        
        if (empty($formData['name'])) {
            $errors[] = 'Numele produsului este obligatoriu.';
        }
        
        if (empty($formData['unit'])) {
            $errors[] = 'Unitatea de măsură este obligatorie.';
        }
        
        if ($formData['price'] <= 0) {
            $errors[] = 'Prețul trebuie să fie mai mare decât 0.';
        }
        
        // Verificare imagine nouă
        $updateImage = false;
        $imagePath = $product['image']; // Păstrăm imaginea existentă dacă nu se încarcă una nouă
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                $errors[] = 'Formatul imaginii nu este acceptat. Vă rugăm să încărcați o imagine în format JPG, PNG sau GIF.';
            } elseif ($_FILES['image']['size'] > $maxSize) {
                $errors[] = 'Dimensiunea imaginii depășește limita de 2MB.';
            } else {
                // Generare nume unic pentru imagine
                $imageExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = 'product_' . time() . '_' . uniqid() . '.' . $imageExtension;
                $uploadDir = '../../uploads/products/';
                
                // Verificare și creare director dacă nu există
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $imagePath = $imageName;
                $updateImage = true;
                
                // Încărcare imagine
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName)) {
                    $errors[] = 'A apărut o eroare la încărcarea imaginii. Vă rugăm să încercați din nou.';
                    $imagePath = $product['image']; // Revenire la imaginea existentă
                    $updateImage = false;
                }
            }
        }
        
        // Dacă nu există erori, procesăm actualizarea
        if (empty($errors)) {
            $productData = [
                'id' => $product_id,
                'category_id' => $formData['category_id'],
                'code' => $formData['code'],
                'name' => $formData['name'],
                'description' => $formData['description'],
                'unit' => $formData['unit'],
                'price' => $formData['price'],
                'status' => $formData['status']
            ];
            
            $result = $productObj->updateProduct($productData);
            
            // Actualizare imagine dacă s-a încărcat una nouă
            if ($result && $updateImage) {
                $imageResult = $productObj->updateProductImage($product_id, $imagePath);
                
                // Ștergere imagine veche dacă există
                if ($imageResult && !empty($product['image']) && file_exists('../../uploads/products/' . $product['image'])) {
                    unlink('../../uploads/products/' . $product['image']);
                }
            }
            
            if ($result) {
                setFlashMessage('success', 'Produsul a fost actualizat cu succes.');
                redirect('index.php');
            } else {
                $error = 'A apărut o eroare la actualizarea produsului. Vă rugăm să încercați din nou.';
                
                // Ștergere imagine nouă în caz de eroare
                if ($updateImage && file_exists('../../uploads/products/' . $imagePath)) {
                    unlink('../../uploads/products/' . $imagePath);
                }
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Titlu pagină
$pageTitle = 'Editare Produs - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de produse
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Editare Produs</h1>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <div class="flex">
            <div class="py-1">
                <i class="fas fa-check-circle mr-1"></i>
            </div>
            <div>
                <p class="font-bold">Succes!</p>
                <p class="text-sm"><?php echo $success; ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold">Informații produs</h2>
    </div>
    
    <div class="p-6">
        <form method="POST" action="edit.php?id=<?php echo $product_id; ?>" enctype="multipart/form-data" class="space-y-6">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Categorie -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Categorie</label>
                    <select id="category_id" name="category_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">-- Selectează categoria --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $formData['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Cod produs -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Cod produs <span class="text-red-500">*</span></label>
                    <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($formData['code']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <!-- Nume produs -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nume produs <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <!-- Descriere -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">Descriere</label>
                    <textarea id="description" name="description" rows="4"
                              class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                </div>
                
                <!-- Unitate măsură -->
                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700">Unitate de măsură <span class="text-red-500">*</span></label>
                    <input type="text" id="unit" name="unit" value="<?php echo htmlspecialchars($formData['unit']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Ex: buc, kg, l, m</p>
                </div>
                
                <!-- Preț -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Preț (Lei) <span class="text-red-500">*</span></label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input type="text" id="price" name="price" value="<?php echo htmlspecialchars($formData['price']); ?>" required
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">Lei</span>
                        </div>
                    </div>
                </div>
                
                <!-- Imagine produs -->
                <div class="md:col-span-2">
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Imagine produs</label>
                    
                    <?php if (!empty($product['image']) && file_exists('../../uploads/products/' . $product['image'])): ?>
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Imagine curentă:</p>
                            <div class="flex items-center">
                                <img src="../../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="h-24 w-24 object-cover rounded-md border border-gray-300">
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-1 flex items-center">
                        <div class="w-full">
                            <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/gif"
                                   class="py-2 px-3 border border-gray-300 rounded-md w-full">
                            <p class="mt-1 text-xs text-gray-500">Format acceptat: JPG, PNG sau GIF. Dimensiune maximă: 2MB</p>
                            <p class="text-xs text-gray-500">Lăsați gol pentru a păstra imaginea curentă.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Status -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center">
                            <input id="status_active" name="status" type="radio" value="active" <?php echo $formData['status'] === 'active' ? 'checked' : ''; ?>
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                            <label for="status_active" class="ml-3 block text-sm font-medium text-gray-700">
                                Activ
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input id="status_inactive" name="status" type="radio" value="inactive" <?php echo $formData['status'] === 'inactive' ? 'checked' : ''; ?>
                                   class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300">
                            <label for="status_inactive" class="ml-3 block text-sm font-medium text-gray-700">
                                Inactiv
                            </label>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Produsele inactive nu vor fi vizibile pentru clienți</p>
                </div>
            </div>
            
            <!-- Butoane -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Anulează
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-save mr-1"></i> Salvează modificările
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validare preț - accept doar numere și punct/virgulă
    const priceInput = document.getElementById('price');
    priceInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9.,]/g, '');
    });
    
    // Preview imagine
    const imageInput = document.getElementById('image');
    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Aici puteți adăuga cod pentru afișarea unui preview al imaginii noi
            }
            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>