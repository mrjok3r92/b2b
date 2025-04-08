<?php
// client/users/add.php
// Pagina pentru adăugarea unui utilizator nou

// Inițializare sesiune și autentificare client admin
require_once '../../includes/auth.php';
authenticateClientAdmin();

// Include fișiere necesare
require_once '../../classes/User.php';
require_once '../../classes/Client.php';

// Inițializare obiecte
$userObj = new User();
$clientObj = new Client();

// Obține locațiile clientului
$locations = $clientObj->getClientLocations($_SESSION['client_id']);

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => '',
    'location_id' => '',
    'role' => 'client_user',
    'status' => 'active'
];

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Preluare și sanitizare date formular
        $formData = [
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'location_id' => isset($_POST['location_id']) && is_numeric($_POST['location_id']) ? (int)$_POST['location_id'] : null,
            'role' => $_POST['role'] ?? 'client_user',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        // Validare date
        $errors = [];
        
        if (empty($formData['first_name'])) {
            $errors[] = 'Prenumele este obligatoriu.';
        }
        
        if (empty($formData['last_name'])) {
            $errors[] = 'Numele este obligatoriu.';
        }
        
        if (empty($formData['email'])) {
            $errors[] = 'Adresa de email este obligatorie.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresa de email nu este validă.';
        } elseif ($userObj->findUserByEmail($formData['email'])) {
            $errors[] = 'Există deja un utilizator cu această adresă de email.';
        }
        
        if (empty($formData['password'])) {
            $errors[] = 'Parola este obligatorie.';
        } elseif (strlen($formData['password']) < 8) {
            $errors[] = 'Parola trebuie să aibă minim 8 caractere.';
        }
        
        if ($formData['password'] !== $formData['confirm_password']) {
            $errors[] = 'Parolele nu coincid.';
        }
        
        if ($formData['role'] === 'client_user' && empty($formData['location_id'])) {
            $errors[] = 'Locația este obligatorie pentru utilizatori standard.';
        }
        
        // Validează dacă locația aparține clientului
        if (!empty($formData['location_id'])) {
            $location = $clientObj->getLocationById($formData['location_id']);
            if (!$location || $location['client_id'] != $_SESSION['client_id']) {
                $errors[] = 'Locația selectată nu este validă.';
            }
        }
        
        // Validare rol
        if (!in_array($formData['role'], ['client_admin', 'client_user'])) {
            $errors[] = 'Rolul selectat nu este valid.';
        }
        
        // Validare status
        if (!in_array($formData['status'], ['active', 'inactive'])) {
            $errors[] = 'Statusul selectat nu este valid.';
        }
        
        // Dacă nu există erori, procesăm adăugarea
        if (empty($errors)) {
            $userData = [
                'client_id' => $_SESSION['client_id'],
                'location_id' => $formData['role'] === 'client_admin' ? null : $formData['location_id'],
                'first_name' => $formData['first_name'],
                'last_name' => $formData['last_name'],
                'email' => $formData['email'],
                'password' => $formData['password'],
                'role' => $formData['role'],
                'status' => $formData['status']
            ];
            
            $user_id = $userObj->register($userData);
            
            if ($user_id) {
                setFlashMessage('success', 'Utilizatorul a fost adăugat cu succes.');
                redirect('index.php');
            } else {
                $error = 'A apărut o eroare la adăugarea utilizatorului. Vă rugăm să încercați din nou.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Titlu pagină
$pageTitle = 'Adaugă utilizator - Platformă B2B';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la utilizatori
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <h1 class="text-xl font-bold text-gray-900">Adaugă utilizator nou</h1>
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
        
        <form action="add.php" method="POST" class="space-y-6">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Informații personale -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">Prenume</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($formData['first_name']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">Nume</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($formData['last_name']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
            </div>
            
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required
                       class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                <p class="mt-1 text-xs text-gray-500">Adresa de email va fi folosită pentru autentificare.</p>
            </div>
            
            <!-- Parolă -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Parolă</label>
                    <input type="password" id="password" name="password" required minlength="8"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Minim 8 caractere.</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmare parolă</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
            </div>
            
            <!-- Rolul utilizatorului -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Rol utilizator</label>
                <div class="mt-2 space-y-2">
                    <div class="flex items-center">
                        <input id="role_user" name="role" type="radio" value="client_user" <?php echo $formData['role'] === 'client_user' ? 'checked' : ''; ?>
                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                        <label for="role_user" class="ml-3 block text-sm font-medium text-gray-700">
                            Utilizator standard
                            <p class="text-xs text-gray-500">Poate plasa comenzi doar pentru locația asociată.</p>
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input id="role_admin" name="role" type="radio" value="client_admin" <?php echo $formData['role'] === 'client_admin' ? 'checked' : ''; ?>
                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                        <label for="role_admin" class="ml-3 block text-sm font-medium text-gray-700">
                            Administrator client
                            <p class="text-xs text-gray-500">Poate gestiona utilizatori, locații și poate vedea toate comenzile companiei.</p>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Locație -->
            <div id="location_container" class="<?php echo $formData['role'] === 'client_admin' ? 'hidden' : ''; ?>">
                <label for="location_id" class="block text-sm font-medium text-gray-700">Locație</label>
                <select id="location_id" name="location_id" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <option value="">Selectează locația</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>" <?php echo $formData['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($location['name']); ?> - <?php echo htmlspecialchars($location['address']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-xs text-gray-500">Utilizatorul va putea plasa comenzi doar pentru această locație.</p>
            </div>
            
            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select id="status" name="status" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Activ</option>
                    <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactiv</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Utilizatorii inactivi nu se pot autentifica în platformă.</p>
            </div>
            
            <!-- Butoane -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                    Anulează
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-save mr-1"></i> Salvează
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Afișare/ascundere selector locație în funcție de rol
    const roleRadios = document.querySelectorAll('input[name="role"]');
    const locationContainer = document.getElementById('location_container');
    
    roleRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.value === 'client_user') {
                locationContainer.classList.remove('hidden');
            } else {
                locationContainer.classList.add('hidden');
            }
        });
    });
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>