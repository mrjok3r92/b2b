<?php
// admin/clients/add.php
// Pagina pentru adăugarea unui client nou

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/Client.php';
require_once '../../classes/User.php';

// Inițializare obiecte
$clientObj = new Client();
$userObj = new User();

// Inițializare variabile
$error = '';
$success = '';
$formData = [
    'company_name' => '',
    'company_code' => '',
    'fiscal_code' => '',
    'address' => '',
    'phone' => '',
    'email' => '',
    'location_name' => 'Sediu principal',
    'contact_person' => '',
    'location_phone' => '',
    'location_email' => '',
    'user_first_name' => '',
    'user_last_name' => '',
    'user_email' => '',
    'user_password' => ''
];

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Eroare de securitate. Vă rugăm să încercați din nou.';
    } else {
        // Preluare date formular
        $formData = [
            'company_name' => sanitizeInput($_POST['company_name'] ?? ''),
            'company_code' => sanitizeInput($_POST['company_code'] ?? ''),
            'fiscal_code' => sanitizeInput($_POST['fiscal_code'] ?? ''),
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'location_name' => sanitizeInput($_POST['location_name'] ?? 'Sediu principal'),
            'contact_person' => sanitizeInput($_POST['contact_person'] ?? ''),
            'location_phone' => sanitizeInput($_POST['location_phone'] ?? ''),
            'location_email' => sanitizeInput($_POST['location_email'] ?? ''),
            'user_first_name' => sanitizeInput($_POST['user_first_name'] ?? ''),
            'user_last_name' => sanitizeInput($_POST['user_last_name'] ?? ''),
            'user_email' => sanitizeInput($_POST['user_email'] ?? ''),
            'user_password' => $_POST['user_password'] ?? ''
        ];
        
        // Validare date
        $errors = [];
        
        // Validare informații companie
        if (empty($formData['company_name'])) {
            $errors[] = 'Numele companiei este obligatoriu.';
        }
        
        if (empty($formData['fiscal_code'])) {
            $errors[] = 'Codul fiscal este obligatoriu.';
        }
        
        if (empty($formData['address'])) {
            $errors[] = 'Adresa companiei este obligatorie.';
        }
        
        if (empty($formData['phone'])) {
            $errors[] = 'Numărul de telefon este obligatoriu.';
        }
        
        if (empty($formData['email'])) {
            $errors[] = 'Emailul companiei este obligatoriu.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Emailul companiei nu este valid.';
        }
        
        // Validare informații locație
        if (empty($formData['location_name'])) {
            $errors[] = 'Numele locației este obligatoriu.';
        }
        
        // Validare informații utilizator
        if (!empty($formData['user_email']) || !empty($formData['user_password'])) {
            // Dacă se creează un utilizator, toate câmpurile sunt obligatorii
            if (empty($formData['user_first_name'])) {
                $errors[] = 'Prenumele utilizatorului este obligatoriu.';
            }
            
            if (empty($formData['user_last_name'])) {
                $errors[] = 'Numele utilizatorului este obligatoriu.';
            }
            
            if (empty($formData['user_email'])) {
                $errors[] = 'Emailul utilizatorului este obligatoriu.';
            } elseif (!filter_var($formData['user_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Emailul utilizatorului nu este valid.';
            } elseif ($userObj->findUserByEmail($formData['user_email'])) {
                $errors[] = 'Există deja un utilizator cu această adresă de email.';
            }
            
            if (empty($formData['user_password'])) {
                $errors[] = 'Parola utilizatorului este obligatorie.';
            } elseif (strlen($formData['user_password']) < 8) {
                $errors[] = 'Parola trebuie să aibă cel puțin 8 caractere.';
            }
        }
        
        // Dacă nu există erori, procesăm adăugarea
        if (empty($errors)) {
            // Începe tranzacția
            $db = new Database();
            $db->beginTransaction();
            
            try {
                // Adăugare client
                $clientData = [
                    'company_name' => $formData['company_name'],
                    'company_code' => $formData['company_code'],
                    'fiscal_code' => $formData['fiscal_code'],
                    'address' => $formData['address'],
                    'phone' => $formData['phone'],
                    'email' => $formData['email']
                ];
                
                $client_id = $clientObj->addClient($clientData);
                
                if (!$client_id) {
                    throw new Exception('Eroare la adăugarea clientului.');
                }
                
                // Adăugare locație
                $locationData = [
                    'client_id' => $client_id,
                    'name' => $formData['location_name'],
                    'address' => $formData['address'], // Folosim aceeași adresă ca și pentru companie
                    'contact_person' => $formData['contact_person'],
                    'phone' => !empty($formData['location_phone']) ? $formData['location_phone'] : $formData['phone'],
                    'email' => !empty($formData['location_email']) ? $formData['location_email'] : $formData['email']
                ];
                
                $location_id = $clientObj->addLocation($locationData);
                
                if (!$location_id) {
                    throw new Exception('Eroare la adăugarea locației.');
                }
                
                // Adăugare utilizator (dacă s-au completat datele)
                if (!empty($formData['user_email']) && !empty($formData['user_password'])) {
                    $userData = [
                        'client_id' => $client_id,
                        'location_id' => $location_id,
                        'first_name' => $formData['user_first_name'],
                        'last_name' => $formData['user_last_name'],
                        'email' => $formData['user_email'],
                        'password' => $formData['user_password'],
                        'role' => 'client_admin',
                        'status' => 'active'
                    ];
                    
                    $user_id = $userObj->register($userData);
                    
                    if (!$user_id) {
                        throw new Exception('Eroare la adăugarea utilizatorului.');
                    }
                }
                
                // Commit tranzacția
                $db->endTransaction();
                
                // Setăm mesajul de succes
                $success = 'Clientul a fost adăugat cu succes.';
                
                // Reset formular
                $formData = [
                    'company_name' => '',
                    'company_code' => '',
                    'fiscal_code' => '',
                    'address' => '',
                    'phone' => '',
                    'email' => '',
                    'location_name' => 'Sediu principal',
                    'contact_person' => '',
                    'location_phone' => '',
                    'location_email' => '',
                    'user_first_name' => '',
                    'user_last_name' => '',
                    'user_email' => '',
                    'user_password' => ''
                ];
            } catch (Exception $e) {
                // Rollback tranzacția în caz de eroare
                $db->cancelTransaction();
                $error = 'Eroare la adăugarea clientului: ' . $e->getMessage();
            }
        } else {
            // Concatenăm toate erorile
            $error = implode('<br>', $errors);
        }
    }
}

// Titlu pagină
$pageTitle = 'Adaugă Client Nou - Panou de Administrare';

// Include header
include_once '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de clienți
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Adaugă Client Nou</h1>

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
                <p class="mt-2">
                    <a href="index.php" class="text-green-700 font-medium underline">
                        <i class="fas fa-arrow-left mr-1"></i> Înapoi la lista de clienți
                    </a> 
                    sau 
                    <a href="add.php" class="text-green-700 font-medium underline">
                        <i class="fas fa-plus mr-1"></i> Adaugă alt client
                    </a>
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="add.php" class="space-y-6">
    <!-- CSRF token -->
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <!-- Informații companie -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Informații companie</h2>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Nume companie <span class="text-red-500">*</span></label>
                    <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($formData['company_name']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="company_code" class="block text-sm font-medium text-gray-700 mb-1">Număr înregistrare</label>
                    <input type="text" id="company_code" name="company_code" value="<?php echo htmlspecialchars($formData['company_code']); ?>"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="fiscal_code" class="block text-sm font-medium text-gray-700 mb-1">Cod fiscal <span class="text-red-500">*</span></label>
                    <input type="text" id="fiscal_code" name="fiscal_code" value="<?php echo htmlspecialchars($formData['fiscal_code']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon <span class="text-red-500">*</span></label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresă <span class="text-red-500">*</span></label>
                    <textarea id="address" name="address" rows="3" required
                              class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"><?php echo htmlspecialchars($formData['address']); ?></textarea>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Informații locație principală -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Informații locație principală</h2>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="location_name" class="block text-sm font-medium text-gray-700 mb-1">Nume locație <span class="text-red-500">*</span></label>
                    <input type="text" id="location_name" name="location_name" value="<?php echo htmlspecialchars($formData['location_name']); ?>" required
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Ex: Sediu central, Depozit, Punct de lucru</p>
                </div>
                
                <div>
                    <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Persoană de contact</label>
                    <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($formData['contact_person']); ?>"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="location_phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon locație</label>
                    <input type="text" id="location_phone" name="location_phone" value="<?php echo htmlspecialchars($formData['location_phone']); ?>"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Dacă este diferit de telefonul companiei</p>
                </div>
                
                <div>
                    <label for="location_email" class="block text-sm font-medium text-gray-700 mb-1">Email locație</label>
                    <input type="email" id="location_email" name="location_email" value="<?php echo htmlspecialchars($formData['location_email']); ?>"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Dacă este diferit de emailul companiei</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Informații utilizator administrator -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold">Informații utilizator administrator</h2>
        </div>
        
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">
                Opțional, puteți crea un cont de administrator pentru client. Acest utilizator va putea gestiona comenzile, locațiile și utilizatorii companiei.
                Dacă nu creați un utilizator acum, clientul va trebui să se înregistreze singur.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="user_first_name" class="block text-sm font-medium text-gray-700 mb-1">Prenume</label>
                    <input type="text" id="user_first_name" name="user_first_name" value="<?php echo htmlspecialchars($formData['user_first_name']); ?>"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="user_last_name" class="block text-sm font-medium text-gray-700 mb-1">Nume</label>
                    <input type="text" id="user_last_name" name="user_last_name" value="<?php echo htmlspecialchars($formData['user_last_name']); ?>"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="user_email" class="block text-sm font-medium text-gray-700 mb-1">Email utilizator</label>
                    <input type="email" id="user_email" name="user_email" value="<?php echo htmlspecialchars($formData['user_email']); ?>"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="user_password" class="block text-sm font-medium text-gray-700 mb-1">Parolă</label>
                    <input type="password" id="user_password" name="user_password" value="<?php echo htmlspecialchars($formData['user_password']); ?>"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Minim 8 caractere</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Butoane -->
    <div class="flex justify-end space-x-3">
        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Anulează
        </a>
        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Adaugă client
        </button>
    </div>
</form>

<?php
// Include footer
include_once '../../includes/footer.php';
?>