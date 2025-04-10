<?php
// admin/users/add.php
// Pagina pentru adăugarea unui utilizator nou

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/User.php';
require_once '../../classes/Client.php';
require_once '../../classes/Location.php';

// Inițializare obiecte
$userObj = new User();
$clientObj = new Client();
$locationObj = new Location();

// Obține lista de clienți
$clients = $clientObj->getAllClients();

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'role' => '',
    'client_id' => '',
    'location_id' => '',
    'status' => 'active',
    'password' => '',
    'confirm_password' => ''
];

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
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'role' => sanitizeInput($_POST['role'] ?? ''),
            'client_id' => isset($_POST['client_id']) && is_numeric($_POST['client_id']) ? (int)$_POST['client_id'] : null,
            'location_id' => isset($_POST['location_id']) && is_numeric($_POST['location_id']) ? (int)$_POST['location_id'] : null,
            'status' => sanitizeInput($_POST['status'] ?? 'active'),
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
            $errors[] = 'Adresa de email nu este validă.';
        } elseif ($userObj->emailExists($formData['email'])) {
            $errors[] = 'Această adresă de email este deja folosită.';
        }
        
        if (empty($formData['role'])) {
            $errors[] = 'Rolul utilizatorului este obligatoriu.';
        }
        
        // Validări specifice rolului
        if (in_array($formData['role'], ['client', 'client_admin'])) {
            if (empty($formData['client_id'])) {
                $errors[] = 'Selectarea unui client este obligatorie pentru acest rol.';
            }
            
            if ($formData['role'] === 'client' && empty($formData['location_id'])) {
                $errors[] = 'Selectarea unei locații este obligatorie pentru utilizator client.';
            }
        } else {
            // Pentru admin și agent, anulăm client_id și location_id
            $formData['client_id'] = null;
            $formData['location_id'] = null;
        }
        
        // Validare parolă
        if (empty($formData['password'])) {
            $errors[] = 'Parola este obligatorie.';
        } elseif (strlen($formData['password']) < 8) {
            $errors[] = 'Parola trebuie să conțină minim 8 caractere.';
        } elseif ($formData['password'] !== $formData['confirm_password']) {
            $errors[] = 'Parolele introduse nu coincid.';
        }
        
        // Verificare imagine profil
        $profileImage = '';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
                $errors[] = 'Formatul imaginii nu este acceptat. Vă rugăm să încărcați o imagine în format JPG, PNG sau GIF.';
            } elseif ($_FILES['profile_image']['size'] > $maxSize) {
                $errors[] = 'Dimensiunea imaginii depășește limita de 2MB.';
            } else {
                // Generare nume unic pentru imagine
                $imageExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $imageName = 'user_' . time() . '_' . uniqid() . '.' . $imageExtension;
                $uploadDir = '../../uploads/users/';
                
                // Verificare și creare director dacă nu există
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $profileImage = $imageName;
                
                // Încărcare imagine
                if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $imageName)) {
                    $errors[] = 'A apărut o eroare la încărcarea imaginii. Vă rugăm să încercați din nou.';
                    $profileImage = '';
                }
            }
        }
        
        // Dacă nu există erori, procesăm adăugarea
        if (empty($errors)) {
            $userData = [
                'first_name' => $formData['first_name'],
                'last_name' => $formData['last_name'],
                'email' => $formData['email'],
                'phone' => $formData['phone'],
                'role' => $formData['role'],
                'client_id' => $formData['client_id'],
                'location_id' => $formData['location_id'],
                'status' => $formData['status'],
                'password' => $formData['password'],
                'profile_image' => $profileImage
            ];
            
            $user_id = $userObj->addUser($userData);
            
            if ($user_id) {
                setFlashMessage('success', 'Utilizatorul a fost adăugat cu succes.');
                redirect('index.php');
            } else {
                $error = 'A apărut o eroare la adăugarea utilizatorului. Vă rugăm să încercați din nou.';
                
                // Ștergere imagine în caz de eroare
                if (!empty($profileImage) && file_exists($uploadDir . $imageName)) {
                    unlink($uploadDir . $imageName);
                }
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Titlu pagină
$pageTitle = 'Adaugă Utilizator Nou - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de utilizatori
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Adaugă Utilizator Nou</h1>

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
        <form method="POST" action="add.php" enctype="multipart/form-data" class="space-y-6">
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
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <!-- Telefon -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Telefon</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <!-- Rol utilizator -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Rol <span class="text-red-500">*</span></label>
                    <select id="role" name="role" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">-- Selectează rolul --</option>
                        <option value="admin" <?php echo $formData['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        <option value="agent" <?php echo $formData['role'] === 'agent' ? 'selected' : ''; ?>>Agent</option>
                        <option value="client_admin" <?php echo $formData['role'] === 'client_admin' ? 'selected' : ''; ?>>Administrator Client</option>
                        <option value="client" <?php echo $formData['role'] === 'client' ? 'selected' : ''; ?>>Client</option>
                    </select>
                </div>
                
                <!-- Client asociat - vizibil doar pentru rolurile client și client_admin -->
                <div id="client_section" class="<?php echo in_array($formData['role'], ['client', 'client_admin']) ? '' : 'hidden'; ?>">
                    <label for="client_id" class="block text-sm font-medium text-gray-700">Client <span class="text-red-500">*</span></label>
                    <select id="client_id" name="client_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">-- Selectează clientul --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $formData['client_id'] == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['company_name']); ?> (<?php echo htmlspecialchars($client['fiscal_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Locație client - vizibil doar pentru rolul client -->
                <div id="location_section" class="<?php echo $formData['role'] === 'client' ? '' : 'hidden'; ?>">
                    <label for="location_id" class="block text-sm font-medium text-gray-700">Locație <span class="text-red-500">*</span></label>
                    <select id="location_id" name="location_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">-- Selectează locația --</option>
                        <?php 
                        if (!empty($formData['client_id'])) {
                            $locations = $locationObj->getClientLocations($formData['client_id']);
                            foreach ($locations as $location): 
                        ?>
                            <option value="<?php echo $location['id']; ?>" <?php echo $formData['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php 
                            endforeach;
                        }
                        ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Locația va fi disponibilă după selectarea clientului</p>
                </div>
                
                <!-- Parolă -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Parolă <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Minim 8 caractere</p>
                </div>
                
                <!-- Confirmare parolă -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmare parolă <span class="text-red-500">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <!-- Imagine profil -->
                <div class="md:col-span-2">
                    <label for="profile_image" class="block text-sm font-medium text-gray-700">Imagine profil</label>
                    <div class="mt-1">
                        <input type="file" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif"
                               class="py-2 px-3 border border-gray-300 rounded-md w-full">
                        <p class="mt-1 text-xs text-gray-500">Format acceptat: JPG, PNG sau GIF. Dimensiune maximă: 2MB</p>
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
                </div>
            </div>
            
            <!-- Butoane -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Anulează
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-save mr-1"></i> Salvează utilizatorul
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const clientSection = document.getElementById('client_section');
    const locationSection = document.getElementById('location_section');
    const clientSelect = document.getElementById('client_id');
    const locationSelect = document.getElementById('location_id');
    
    // Afișare/ascundere secțiuni în funcție de rol
    roleSelect.addEventListener('change', function() {
        const role = this.value;
        if (role === 'client' || role === 'client_admin') {
            clientSection.classList.remove('hidden');
        } else {
            clientSection.classList.add('hidden');
            clientSelect.value = '';
        }
        
        if (role === 'client') {
            locationSection.classList.remove('hidden');
        } else {
            locationSection.classList.add('hidden');
            locationSelect.value = '';
        }
    });
    
    // Încărcare locații la schimbarea clientului
    clientSelect.addEventListener('change', function() {
        const clientId = this.value;
        locationSelect.innerHTML = '<option value="">-- Selectează locația --</option>';
        
        if (clientId && roleSelect.value === 'client') {
            // Cerere AJAX pentru a obține locațiile clientului
            fetch('../../ajax/get_client_locations.php?client_id=' + clientId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.locations.length > 0) {
                        data.locations.forEach(location => {
                            const option = document.createElement('option');
                            option.value = location.id;
                            option.textContent = location.name;
                            locationSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Eroare:', error));
        }
    });
});
</script>

<?php
// Include footer
include_once '../../includes/footer.php';
?>