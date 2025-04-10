<?php
// admin/users/edit.php
// Pagina pentru editarea unui utilizator existent

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/User.php';
require_once '../../classes/Client.php';

// Verificare ID utilizator
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID utilizator invalid.');
    redirect('index.php');
}

$user_id = (int)$_GET['id'];

// Inițializare obiecte
$userObj = new User();
$clientObj = new Client();

// Obține informațiile utilizatorului
$user = $userObj->getUserById($user_id);

// Verificare existență utilizator
if (!$user) {
    setFlashMessage('error', 'Utilizatorul nu există.');
    redirect('index.php');
}

// Nu permitem unui admin să-și schimbe propriul rol (pentru a evita situația fără administratori)
$isSelfEdit = ($_SESSION['user_id'] == $user_id);

// Obține lista de clienți
$clients = $clientObj->getAllClients();

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'client_id' => $user['client_id'],
    'location_id' => $user['location_id'],
    'status' => $user['status'],
    'change_password' => false,
    'password' => '',
    'confirm_password' => ''
];

// Parametru pentru a verifica dacă locațiile clientului au fost încărcate prin AJAX
$loadLocations = isset($_GET['load_locations']) && is_numeric($_GET['load_locations']);

if ($loadLocations) {
    $client_id = (int)$_GET['load_locations'];
    $locations = $clientObj->getClientLocations($client_id);
    
    // Returnăm rezultatul ca JSON
    header('Content-Type: application/json');
    echo json_encode($locations);
    exit;
}

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Preluare date formular
        $formData = [
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'role' => $isSelfEdit ? $user['role'] : sanitizeInput($_POST['role'] ?? ''),
            'client_id' => !empty($_POST['client_id']) && is_numeric($_POST['client_id']) ? (int)$_POST['client_id'] : null,
            'location_id' => !empty($_POST['location_id']) && is_numeric($_POST['location_id']) ? (int)$_POST['location_id'] : null,
            'status' => $isSelfEdit ? $user['status'] : sanitizeInput($_POST['status'] ?? 'active'),
            'change_password' => isset($_POST['change_password']),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
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
            $errors[] = 'Adresa de email este invalidă.';
        } elseif ($formData['email'] !== $user['email'] && $userObj->emailExists($formData['email'])) {
            $errors[] = 'Această adresă de email este deja înregistrată pentru alt utilizator.';
        }
        
        // Validare parolă dacă schimbarea parolei a fost selectată
        if ($formData['change_password']) {
            if (empty($formData['password'])) {
                $errors[] = 'Parola este obligatorie.';
            } elseif (strlen($formData['password']) < 6) {
                $errors[] = 'Parola trebuie să aibă cel puțin 6 caractere.';
            } elseif ($formData['password'] !== $formData['confirm_password']) {
                $errors[] = 'Parolele nu coincid.';
            }
        }
        
        // Validări specifice rolurilor client
        if (in_array($formData['role'], ['client_admin', 'client_user'])) {
            if (empty($formData['client_id'])) {
                $errors[] = 'Pentru utilizatorii clienților, selectarea unui client este obligatorie.';
            }
            
            if ($formData['role'] === 'client_user' && empty($formData['location_id'])) {
                $errors[] = 'Pentru utilizatorii de tip client_user, selectarea unei locații este obligatorie.';
            }
        } else {
            // Pentru alte roluri, resetăm client_id și location_id
            $formData['client_id'] = null;
            $formData['location_id'] = null;
        }
        
        // Dacă nu există erori, procesăm actualizarea
        if (empty($errors)) {
            // Pregătire date utilizator
            $userData = [
                'id' => $user_id,
                'first_name' => $formData['first_name'],
                'last_name' => $formData['last_name'],
                'email' => $formData['email'],
                'role' => $formData['role'],
                'client_id' => $formData['client_id'],
                'location_id' => $formData['location_id'],
                'status' => $formData['status']
            ];
            
            // Actualizare parolă dacă este cazul
            if ($formData['change_password']) {
                $userData['password'] = password_hash($formData['password'], PASSWORD_DEFAULT);
            }
            
            // Actualizăm utilizatorul
            $result = $userObj->updateUser($userData);
            
            if ($result) {
                setFlashMessage('success', 'Utilizatorul a fost actualizat cu succes.');
                redirect('index.php');
            } else {
                $error = 'A apărut o eroare la actualizarea utilizatorului. Vă rugăm să încercați din nou.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Obține locațiile pentru client
$locations = [];
if (!empty($formData['client_id'])) {
    $locations = $clientObj->getClientLocations($formData['client_id']);
}

// Titlu pagină
$pageTitle = 'Editare Utilizator - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de utilizatori
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Editare Utilizator</h1>

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
        <h2 class="text-lg font-semibold">Informații utilizator</h2>
    </div>
    
    <div class="p-6">
        <form method="POST" action="edit.php?id=<?php echo $user_id; ?>" class="space-y-6">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Prenume -->
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">Prenume <span class="text-red-500">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($formData['first_name']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <!-- Nume -->
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">Nume <span class="text-red-500">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($formData['last_name']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <!-- Email -->
                <div class="md:col-span-2">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <!-- Checkbox schimbare parolă -->
                <div class="md:col-span-2">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input id="change_password" name="change_password" type="checkbox" <?php echo $formData['change_password'] ? 'checked' : ''; ?>
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="change_password" class="font-medium text-gray-700">Schimbă parola</label>
                            <p class="text-gray-500">Bifați această opțiune pentru a schimba parola utilizatorului.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Parola -->
                <div id="password_container" class="<?php echo !$formData['change_password'] ? 'hidden' : ''; ?>">
                    <label for="password" class="block text-sm font-medium text-gray-700">Parolă nouă <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" <?php echo $formData['change_password'] ? 'required' : ''; ?>
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Minim 6 caractere</p>
                </div>
                
                <!-- Confirmare parolă -->
                <div id="confirm_password_container" class="<?php echo !$formData['change_password'] ? 'hidden' : ''; ?>">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmare parolă nouă <span class="text-red-500">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" <?php echo $formData['change_password'] ? 'required' : ''; ?>
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <!-- Rol -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Rol <span class="text-red-500">*</span></label>
                    <select id="role" name="role" required <?php echo $isSelfEdit ? 'disabled' : ''; ?>
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">-- Selectați rolul --</option>
                        <option value="admin" <?php echo $formData['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        <option value="agent" <?php echo $formData['role'] === 'agent' ? 'selected' : ''; ?>>Agent</option>
                        <option value="client_admin" <?php echo $formData['role'] === 'client_admin' ? 'selected' : ''; ?>>Administrator Client</option>
                        <option value="client_user" <?php echo $formData['role'] === 'client_user' ? 'selected' : ''; ?>>Utilizator Client</option>
                    </select>
                    <?php if ($isSelfEdit): ?>
                        <p class="mt-1 text-xs text-gray-500">Nu vă puteți schimba propriul rol.</p>
                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($formData['role']); ?>">
                    <?php endif; ?>
                </div>
                
                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" <?php echo $isSelfEdit ? 'disabled' : ''; ?>
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Activ</option>
                        <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactiv</option>
                    </select>
                    <?php if ($isSelfEdit): ?>
                        <p class="mt-1 text-xs text-gray-500">Nu vă puteți dezactiva propriul cont.</p>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($formData['status']); ?>">
                    <?php endif; ?>
                </div>
                
                <!-- Client (doar pentru client_admin și client_user) -->
                <div id="client_container" class="<?php echo !in_array($formData['role'], ['client_admin', 'client_user']) ? 'hidden' : ''; ?>">
                    <label for="client_id" class="block text-sm font-medium text-gray-700">Client <span class="text-red-500">*</span></label>
                    <select id="client_id" name="client_id"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">-- Selectați clientul --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $formData['client_id'] == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Locație (doar pentru client_user) -->
                <div id="location_container" class="<?php echo $formData['role'] !== 'client_user' ? 'hidden' : ''; ?>">
                    <label for="location_id" class="block text-sm font-medium text-gray-700">Locație <span class="text-red-500">*</span></label>
                    <select id="location_id" name="location_id"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">-- Selectați clientul mai întâi --</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['id']; ?>" <?php echo $formData['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
    const changePasswordCheckbox = document.getElementById('change_password');
    const passwordContainer = document.getElementById('password_container');
    const confirmPasswordContainer = document.getElementById('confirm_password_container');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    const roleSelect = document.getElementById('role');
    const clientContainer = document.getElementById('client_container');
    const locationContainer = document.getElementById('location_container');
    const clientSelect = document.getElementById('client_id');
    const locationSelect = document.getElementById('location_id');
    
    // Funcție pentru a actualiza vizibilitatea câmpurilor de parolă
    function updatePasswordFields() {
        if (changePasswordCheckbox.checked) {
            passwordContainer.classList.remove('hidden');
            confirmPasswordContainer.classList.remove('hidden');
            passwordInput.required = true;
            confirmPasswordInput.required = true;
        } else {
            passwordContainer.classList.add('hidden');
            confirmPasswordContainer.classList.add('hidden');
            passwordInput.required = false;
            confirmPasswordInput.required = false;
        }
    }
    
    // Funcție pentru a actualiza vizibilitatea containerelor în funcție de rol
    function updateContainers() {
        const role = roleSelect.value;
        
        // Pentru rolurile client_admin și client_user, afișăm selecția clientului
        if (role === 'client_admin' || role === 'client_user') {
            clientContainer.classList.remove('hidden');
        } else {
            clientContainer.classList.add('hidden');
        }
        
        // Doar pentru client_user afișăm și selecția locației
        if (role === 'client_user') {
            locationContainer.classList.remove('hidden');
        } else {
            locationContainer.classList.add('hidden');
        }
    }
    
    // Funcție pentru a încărca locațiile unui client
    function loadLocations() {
        const clientId = clientSelect.value;
        
        if (clientId) {
            // Resetăm opțiunile de locație
            locationSelect.innerHTML = '<option value="">-- Se încarcă locațiile... --</option>';
            
            // Facem cererea AJAX pentru a obține locațiile
            fetch('edit.php?id=<?php echo $user_id; ?>&load_locations=' + clientId)
                .then(response => response.json())
                .then(data => {
                    // Golim select-ul
                    locationSelect.innerHTML = '';
                    
                    // Adăugăm opțiunea implicită
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = '-- Selectați locația --';
                    locationSelect.appendChild(defaultOption);
                    
                    // Adăugăm locațiile
                    data.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location.id;
                        option.textContent = location.name;
                        locationSelect.appendChild(option);
                        
                        // Selectăm locația curentă dacă există
                        if (location.id == <?php echo $formData['location_id'] ? $formData['location_id'] : 0; ?>) {
                            option.selected = true;
                        }
                    });
                })
                .catch(error => {
                    console.error('Eroare la încărcarea locațiilor:', error);
                    locationSelect.innerHTML = '<option value="">-- Eroare la încărcarea locațiilor --</option>';
                });
        } else {
            locationSelect.innerHTML = '<option value="">-- Selectați clientul mai întâi --</option>';
        }
    }
    
    // Actualizăm câmpurile de parolă la schimbarea checkboxului
    changePasswordCheckbox.addEventListener('change', updatePasswordFields);
    
    // Actualizăm containerele la schimbarea rolului
    roleSelect.addEventListener('change', updateContainers);
    
    // Încărcăm locațiile la schimbarea clientului
    clientSelect.addEventListener('change', loadLocations);
    
    // Inițial actualizăm câmpurile
    updatePasswordFields();
    updateContainers();
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>