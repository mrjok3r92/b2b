<?php
// client/profile.php
// Pagina pentru editarea profilului utilizatorului

// Inițializare sesiune și autentificare client
require_once '../includes/auth.php';
authenticateClient();

// Include fișiere necesare
require_once '../classes/User.php';

// Inițializare obiecte
$userObj = new User();

// Obține informațiile utilizatorului
$user = $userObj->getUserById($_SESSION['user_id']);

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'email' => $user['email'],
    'current_password' => '',
    'new_password' => '',
    'confirm_password' => ''
];

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Determinăm ce formular a fost trimis
        $formType = $_POST['form_type'] ?? '';
        
        if ($formType === 'profile') {
            // Actualizare profil
            
            // Preluare și sanitizare date formular
            $formData['first_name'] = sanitizeInput($_POST['first_name'] ?? '');
            $formData['last_name'] = sanitizeInput($_POST['last_name'] ?? '');
            $formData['email'] = sanitizeInput($_POST['email'] ?? '');
            
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
            } elseif ($formData['email'] !== $user['email'] && $userObj->findUserByEmail($formData['email'])) {
                $errors[] = 'Există deja un utilizator cu această adresă de email.';
            }
            
            // Dacă nu există erori, procesăm actualizarea
            if (empty($errors)) {
                $userData = [
                    'id' => $_SESSION['user_id'],
                    'client_id' => $user['client_id'],
                    'location_id' => $user['location_id'],
                    'first_name' => $formData['first_name'],
                    'last_name' => $formData['last_name'],
                    'email' => $formData['email'],
                    'role' => $user['role'],
                    'status' => $user['status']
                ];
                
                $result = $userObj->updateUser($userData);
                
                if ($result) {
                    // Actualizare sesiune
                    $_SESSION['user_name'] = $formData['first_name'] . ' ' . $formData['last_name'];
                    $_SESSION['email'] = $formData['email'];
                    
                    $success = 'Profilul a fost actualizat cu succes.';
                } else {
                    $error = 'A apărut o eroare la actualizarea profilului. Vă rugăm să încercați din nou.';
                }
            } else {
                $error = implode('<br>', $errors);
            }
        } elseif ($formType === 'password') {
            // Schimbare parolă
            
            // Preluare date formular
            $formData['current_password'] = $_POST['current_password'] ?? '';
            $formData['new_password'] = $_POST['new_password'] ?? '';
            $formData['confirm_password'] = $_POST['confirm_password'] ?? '';
            
            // Validare date
            $errors = [];
            
            if (empty($formData['current_password'])) {
                $errors[] = 'Parola curentă este obligatorie.';
            } else {
                // Verificare parolă curentă
                $checkUser = $userObj->login($user['email'], $formData['current_password']);
                if (!$checkUser) {
                    $errors[] = 'Parola curentă este incorectă.';
                }
            }
            
            if (empty($formData['new_password'])) {
                $errors[] = 'Parola nouă este obligatorie.';
            } elseif (strlen($formData['new_password']) < 8) {
                $errors[] = 'Parola nouă trebuie să aibă minim 8 caractere.';
            }
            
            if ($formData['new_password'] !== $formData['confirm_password']) {
                $errors[] = 'Parolele noi nu coincid.';
            }
            
            // Dacă nu există erori, procesăm schimbarea parolei
            if (empty($errors)) {
                $userData = [
                    'id' => $_SESSION['user_id'],
                    'client_id' => $user['client_id'],
                    'location_id' => $user['location_id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'password' => $formData['new_password'],
                    'role' => $user['role'],
                    'status' => $user['status']
                ];
                
                $result = $userObj->updateUser($userData);
                
                if ($result) {
                    $success = 'Parola a fost schimbată cu succes.';
                    
                    // Reset form data for password fields
                    $formData['current_password'] = '';
                    $formData['new_password'] = '';
                    $formData['confirm_password'] = '';
                } else {
                    $error = 'A apărut o eroare la schimbarea parolei. Vă rugăm să încercați din nou.';
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
}

// Titlu pagină
$pageTitle = 'Profil Utilizator - Platformă B2B';

// Include header
include_once '../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la dashboard
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Profil Utilizator</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informații cont -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold">Informații cont</h2>
            </div>
            
            <div class="p-6">
                <div class="flex items-center mb-6">
                    <div class="h-20 w-20 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                    
                    <div class="ml-4">
                        <h3 class="text-lg font-medium"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-500">Rol:</span>
                        <p class="font-medium">
                            <?php echo $user['role'] == 'client_admin' ? 'Administrator Client' : 'Utilizator Standard'; ?>
                        </p>
                    </div>
                    
                    <?php if ($user['location_id']): ?>
                        <div>
                            <span class="text-sm text-gray-500">Locație:</span>
                            <p class="font-medium" id="user-location">
                                <?php 
                                require_once '../classes/Client.php';
                                $clientObj = new Client();
                                $location = $clientObj->getLocationById($user['location_id']);
                                echo htmlspecialchars($location['name'] ?? 'Nedefinită');
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <span class="text-sm text-gray-500">Cont creat la:</span>
                        <p class="font-medium"><?php echo formatDate($user['created_at'], true); ?></p>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-500">Ultima actualizare:</span>
                        <p class="font-medium"><?php echo formatDate($user['updated_at'], true); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulare -->
    <div class="lg:col-span-2 space-y-6">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Formular date personale -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold">Date personale</h2>
            </div>
            
            <div class="p-6">
                <form action="profile.php" method="POST" class="space-y-4">
                    <!-- CSRF token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="form_type" value="profile">
                    
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
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    
                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-save mr-1"></i> Salvează modificările
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Formular schimbare parolă -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold">Schimbare parolă</h2>
            </div>
            
            <div class="p-6">
                <form action="profile.php" method="POST" class="space-y-4">
                    <!-- CSRF token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="form_type" value="password">
                    
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Parolă curentă</label>
                        <input type="password" id="current_password" name="current_password" required
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">Parolă nouă</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8"
                                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            <p class="mt-1 text-xs text-gray-500">Minim 8 caractere.</p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmare parolă nouă</label>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-key mr-1"></i> Schimbă parola
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/footer.php';
?>