<?php
// client/locations/edit.php
// Pagina pentru editarea unei locații

// Inițializare sesiune și autentificare client admin
require_once '../../includes/auth.php';
authenticateClientAdmin();

// Include fișiere necesare
require_once '../../classes/Client.php';

// Verificare ID locație
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID locație invalid.');
    redirect('index.php');
}

$location_id = (int)$_GET['id'];

// Inițializare obiecte
$clientObj = new Client();

// Obține informațiile locației
$location = $clientObj->getLocationById($location_id);

// Verificare existență locație și apartenență la client
if (!$location || $location['client_id'] != $_SESSION['client_id']) {
    setFlashMessage('error', 'Locația nu există sau nu vă aparține.');
    redirect('index.php');
}

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'name' => $location['name'],
    'address' => $location['address'],
    'contact_person' => $location['contact_person'],
    'phone' => $location['phone'],
    'email' => $location['email']
];

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Preluare și sanitizare date formular
        $formData = [
            'name' => sanitizeInput($_POST['name'] ?? ''),
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'contact_person' => sanitizeInput($_POST['contact_person'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? '')
        ];
        
        // Validare date
        $errors = [];
        
        if (empty($formData['name'])) {
            $errors[] = 'Numele locației este obligatoriu.';
        }
        
        if (empty($formData['address'])) {
            $errors[] = 'Adresa este obligatorie.';
        }
        
        if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresa de email nu este validă.';
        }
        
        // Dacă nu există erori, procesăm actualizarea
        if (empty($errors)) {
            $locationData = [
                'id' => $location_id,
                'client_id' => $_SESSION['client_id'],
                'name' => $formData['name'],
                'address' => $formData['address'],
                'contact_person' => $formData['contact_person'],
                'phone' => $formData['phone'],
                'email' => $formData['email']
            ];
            
            $result = $clientObj->updateLocation($locationData);
            
            if ($result) {
                setFlashMessage('success', 'Locația a fost actualizată cu succes.');
                redirect('index.php');
            } else {
                $error = 'A apărut o eroare la actualizarea locației. Vă rugăm să încercați din nou.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Titlu pagină
$pageTitle = 'Editare locație - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la locații
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h1 class="text-xl font-bold text-gray-900">Editare locație</h1>
    </div>
    
    <div class="p-6">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <form action="edit.php?id=<?php echo $location_id; ?>" method="POST" class="space-y-6">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Nume locație -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nume locație</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" required
                       class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                <p class="mt-1 text-xs text-gray-500">Ex: Sediu central, Depozit, Punct de lucru, etc.</p>
            </div>
            
            <!-- Adresa -->
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700">Adresă</label>
                <textarea id="address" name="address" rows="3" required
                          class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"><?php echo htmlspecialchars($formData['address']); ?></textarea>
            </div>
            
            <!-- Persoană de contact -->
            <div>
                <label for="contact_person" class="block text-sm font-medium text-gray-700">Persoană de contact</label>
                <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($formData['contact_person']); ?>"
                       class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            
            <!-- Telefon -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Telefon</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>"
                       class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>"
                       class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            
            <!-- Butoane -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                    Anulează
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-save mr-1"></i> Salvează modificările
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?>