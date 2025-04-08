<?php
// register.php
// Pagina de înregistrare pentru clienți noi

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Client.php';
require_once 'includes/functions.php';

// Inițializare sesiune
session_start();

// Redirecționare dacă utilizatorul este deja autentificat
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'agent') {
        redirect('admin/index.php');
    } else {
        redirect('client/index.php');
    }
}

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
    'first_name' => '',
    'last_name' => '',
    'user_email' => '',
    'password' => '',
    'confirm_password' => '',
    'terms' => false
];

// Procesare formular de înregistrare
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Preluare date formular
    $formData = [
        'company_name' => trim($_POST['company_name'] ?? ''),
        'company_code' => trim($_POST['company_code'] ?? ''),
        'fiscal_code' => trim($_POST['fiscal_code'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'user_email' => trim($_POST['user_email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'terms' => isset($_POST['terms'])
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
    
    // Validare informații utilizator
    if (empty($formData['first_name'])) {
        $errors[] = 'Prenumele este obligatoriu.';
    }
    
    if (empty($formData['last_name'])) {
        $errors[] = 'Numele este obligatoriu.';
    }
    
    if (empty($formData['user_email'])) {
        $errors[] = 'Emailul utilizatorului este obligatoriu.';
    } elseif (!filter_var($formData['user_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Emailul utilizatorului nu este valid.';
    }
    
    if (empty($formData['password'])) {
        $errors[] = 'Parola este obligatorie.';
    } elseif (strlen($formData['password']) < 8) {
        $errors[] = 'Parola trebuie să aibă minim 8 caractere.';
    }
    
    if (empty($formData['confirm_password'])) {
        $errors[] = 'Confirmarea parolei este obligatorie.';
    } elseif ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'Parolele nu coincid.';
    }
    
    if (!$formData['terms']) {
        $errors[] = 'Trebuie să acceptați termenii și condițiile.';
    }
    
    // Dacă nu există erori, procesăm înregistrarea
    if (empty($errors)) {
        // Inițializare obiecte
        $user = new User();
        $client = new Client();
        
        // Verificare dacă utilizatorul există deja
        if ($user->findUserByEmail($formData['user_email'])) {
            $error = 'Există deja un cont cu această adresă de email.';
        } else {
            try {
                // Start transaction
                $db = new Database();
                $db->beginTransaction();
                
                // Înregistrare client
                $clientData = [
                    'company_name' => $formData['company_name'],
                    'company_code' => $formData['company_code'],
                    'fiscal_code' => $formData['fiscal_code'],
                    'address' => $formData['address'],
                    'phone' => $formData['phone'],
                    'email' => $formData['email']
                ];
                
                $client_id = $client->addClient($clientData);
                
                if (!$client_id) {
                    throw new Exception('Eroare la înregistrarea clientului.');
                }
                
                // Adăugare locație implicită
                $locationData = [
                    'client_id' => $client_id,
                    'name' => 'Sediu principal',
                    'address' => $formData['address'],
                    'contact_person' => $formData['first_name'] . ' ' . $formData['last_name'],
                    'phone' => $formData['phone'],
                    'email' => $formData['email']
                ];
                
                $location_id = $client->addLocation($locationData);
                
                if (!$location_id) {
                    throw new Exception('Eroare la adăugarea locației.');
                }
                
                // Înregistrare utilizator admin client
                $userData = [
                    'client_id' => $client_id,
                    'location_id' => $location_id,
                    'first_name' => $formData['first_name'],
                    'last_name' => $formData['last_name'],
                    'email' => $formData['user_email'],
                    'password' => $formData['password'],
                    'role' => 'client_admin'
                ];
                
                $user_id = $user->register($userData);
                
                if (!$user_id) {
                    throw new Exception('Eroare la înregistrarea utilizatorului.');
                }
                
                // Commit transaction
                $db->endTransaction();
                
                // Afișare mesaj de succes
                $success = 'Înregistrare realizată cu succes! Vă puteți autentifica acum.';
                
                // Reset formular
                $formData = [
                    'company_name' => '',
                    'company_code' => '',
                    'fiscal_code' => '',
                    'address' => '',
                    'phone' => '',
                    'email' => '',
                    'first_name' => '',
                    'last_name' => '',
                    'user_email' => '',
                    'password' => '',
                    'confirm_password' => '',
                    'terms' => false
                ];
                
            } catch (Exception $e) {
                // Rollback transaction
                $db->cancelTransaction();
                $error = 'Eroare la înregistrare: ' . $e->getMessage();
            }
        }
    } else {
        // Concatenăm toate erorile într-un singur mesaj
        $error = implode('<br>', $errors);
    }
}

// Titlu pagină
$pageTitle = 'Înregistrare - Platformă B2B';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-4 mb-4">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Înregistrare Client Nou
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Creați un cont pentru a accesa platforma B2B
                </p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                    <div class="mt-2">
                        <a href="login.php" class="text-green-700 font-medium underline">Mergi la pagina de autentificare</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!$success): ?>
            <div class="max-w-4xl w-full bg-white p-8 rounded-lg shadow-md">
                <form method="POST" action="register.php" class="space-y-8">
                    <!-- Informații companie -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informații companie</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="company_name" class="block text-sm font-medium text-gray-700">Nume companie</label>
                                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($formData['company_name']); ?>" required
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            
                            <div>
                                <label for="company_code" class="block text-sm font-medium text-gray-700">Număr înregistrare</label>
                                <input type="text" id="company_code" name="company_code" value="<?php echo htmlspecialchars($formData['company_code']); ?>"
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            
                            <div>
                                <label for="fiscal_code" class="block text-sm font-medium text-gray-700">Cod fiscal</label>
                                <input type="text" id="fiscal_code" name="fiscal_code" value="<?php echo htmlspecialchars($formData['fiscal_code']); ?>" required
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Telefon</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>" required
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email companie</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700">Adresă</label>
                                <textarea id="address" name="address" rows="3" required
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"><?php echo htmlspecialchars($formData['address']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informații utilizator -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informații utilizator administrator</h3>
                        <p class="text-sm text-gray-600 mb-4">Aceste date vor fi folosite pentru a crea un cont de administrator pentru compania dumneavoastră.</p>
                        
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
                            
                            <div class="md:col-span-2">
                                <label for="user_email" class="block text-sm font-medium text-gray-700">Email utilizator</label>
                                <input type="email" id="user_email" name="user_email" value="<?php echo htmlspecialchars($formData['user_email']); ?>" required
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">Parolă</label>
                                <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($formData['password']); ?>" required minlength="8"
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <p class="mt-1 text-xs text-gray-500">Minim 8 caractere</p>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmare parolă</label>
                                <input type="password" id="confirm_password" name="confirm_password" value="<?php echo htmlspecialchars($formData['confirm_password']); ?>" required
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Termeni și condiții -->
                    <div>
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="terms" name="terms" type="checkbox" <?php echo $formData['terms'] ? 'checked' : ''; ?> required
                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="terms" class="font-medium text-gray-700">Sunt de acord cu <a href="#" class="text-blue-600 hover:text-blue-500">termenii și condițiile</a> de utilizare a platformei</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Butoane acțiuni -->
                    <div class="flex space-x-4">
                        <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Înregistrare
                        </button>
                        <a href="login.php" class="group relative w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Anulare
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="mt-6">
                <p class="text-center text-sm text-gray-600">
                    Aveți deja un cont? <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Autentificați-vă</a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>