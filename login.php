<?php
// login.php
require_once 'config/database.php';
require_once 'classes/User.php';
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

// Procesare formular login
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validare date
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Verifică dacă toate câmpurile sunt completate
    if (empty($email) || empty($password)) {
        $error = 'Vă rugăm să completați toate câmpurile.';
    } else {
        // Autentificare utilizator
        $user = new User();
        $userData = $user->login($email, $password);
        
        if ($userData) {
            // Setare sesiune
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_name'] = $userData['first_name'] . ' ' . $userData['last_name'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['role'] = $userData['role'];
            $_SESSION['client_id'] = $userData['client_id'];
            $_SESSION['location_id'] = $userData['location_id'];
            
            // Redirecționare în funcție de rol
            if ($userData['role'] == 'admin' || $userData['role'] == 'agent') {
                redirect('admin/index.php');
            } else {
                redirect('client/index.php');
            }
        } else {
            $error = 'Email sau parolă incorecte.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Platformă B2B</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-lg shadow-md">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Platformă B2B
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Autentificare în cont
                </p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <form class="mt-8 space-y-6" action="login.php" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 
                                      placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none 
                                      focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                               placeholder="Email">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Parolă</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 
                                      placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none 
                                      focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                               placeholder="Parolă">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent 
                                   text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt"></i>
                        </span>
                        Autentificare
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html><?php
// login.php
