<?php
// includes/header.php
// Header comun pentru toate paginile

// Inițializare sesiune dacă nu este deja inițializată
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

// Determină dacă suntem în zona de admin sau client
$isAdmin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
$isClient = strpos($_SERVER['REQUEST_URI'], '/client/') !== false;

// Calea către directorul principal
$basePath = $isAdmin || $isClient ? '/' : '';

// Titlul paginii (poate fi suprascris înainte de includerea header-ului)
$pageTitle = $pageTitle ?? 'Platformă B2B';
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
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/custom.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?php echo $basePath . ($isAdmin ? 'admin' : ($isClient ? 'client' : '')); ?>/index.php" class="text-xl font-bold text-blue-600">
                            Platformă B2B
                        </a>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <?php if (hasRole(['admin', 'agent'])): ?>
                                <!-- Meniu admin -->
                                <a href="<?php echo $basePath; ?>admin/clients/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Clienți
                                </a>
                                <a href="<?php echo $basePath; ?>admin/products/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Produse
                                </a>
                                <a href="<?php echo $basePath; ?>admin/orders/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Comenzi
                                </a>
                                <a href="<?php echo $basePath; ?>admin/delivery-notes/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Avize
                                </a>
                                <a href="<?php echo $basePath; ?>admin/users/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Utilizatori
                                </a>
                            <?php elseif (hasRole(['client_admin', 'client_user'])): ?>
                                <!-- Meniu client -->
                                <a href="<?php echo $basePath; ?>client/products/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Produse
                                </a>
                                <a href="<?php echo $basePath; ?>client/cart/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Coș
                                </a>
                                <a href="<?php echo $basePath; ?>client/orders/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Comenzi
                                </a>
                                <a href="<?php echo $basePath; ?>client/delivery-notes/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Avize
                                </a>
                                <?php if (hasRole('client_admin')): ?>
                                    <a href="<?php echo $basePath; ?>client/locations/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                        Locații
                                    </a>
                                    <a href="<?php echo $basePath; ?>client/users/" class="border-transparent text-gray-500 hover:border-blue-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                        Utilizatori
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="ml-3 relative">
                            <div>
                                <button type="button" class="dropdown-toggle bg-white flex text-sm rounded-full focus:outline-none" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Open user menu</span>
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                            <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                        </div>
                                        <span class="ml-2"><?php echo $_SESSION['user_name']; ?></span>
                                        <svg class="ml-1 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </div>
                            <div class="dropdown-menu hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                                <a href="<?php echo $basePath; ?><?php echo $isAdmin ? 'admin' : 'client'; ?>/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Profil</a>
                                <a href="<?php echo $basePath; ?>logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Deconectare</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flex items-center">
                        <a href="<?php echo $basePath; ?>login.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            Autentificare
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Mobile menu button -->
                <div class="-mr-2 flex items-center sm:hidden">
                    <button type="button" class="mobile-menu-button bg-white inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="hidden sm:hidden" id="mobile-menu">
            <?php if (isLoggedIn()): ?>
                <div class="pt-2 pb-3 space-y-1">
                    <?php if (hasRole(['admin', 'agent'])): ?>
                        <!-- Meniu admin -->
                        <a href="<?php echo $basePath; ?>admin/clients/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                            Clienți
                        </a>
                        <a href="<?php echo $basePath; ?>admin/products/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                            Produse
                        </a>
                        <a href="<?php echo $basePath; ?>admin/orders/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                            Comenzi
                        </a>
                        <a href="<?php echo $basePath; ?>admin/delivery-notes/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                            Avize
                        </a>
                        <a href="<?php echo $basePath; ?>admin/users/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                            Utilizatori
                        </a>
                    <?php elseif (hasRole(['client_admin', 'client_user'])): ?>
                        <!-- Meniu client -->
                        <a href="<?php echo $basePath; ?>client/products/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                            Produse
                        </a>
                        <a href="<?php echo $basePath; ?>client/cart/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                            Coș
                        </a>
                        <a href="<?php echo $basePath; ?>client/orders/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                            Comenzi
                        </a>
                        <a href="<?php echo $basePath; ?>client/delivery-notes/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                            Avize
                        </a>
                        <?php if (hasRole('client_admin')): ?>
                            <a href="<?php echo $basePath; ?>client/locations/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                                Locații
                            </a>
                            <a href="<?php echo $basePath; ?>client/users/" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                                Utilizatori
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="pt-4 pb-3 border-t border-gray-200">
                    <div class="flex items-center px-4">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-base font-medium text-gray-800"><?php echo $_SESSION['user_name']; ?></div>
                            <div class="text-sm font-medium text-gray-500"><?php echo $_SESSION['email']; ?></div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <a href="<?php echo $basePath; ?><?php echo $isAdmin ? 'admin' : 'client'; ?>/profile.php" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                            Profil
                        </a>
                        <a href="<?php echo $basePath; ?>logout.php" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                            Deconectare
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="pt-2 pb-3 space-y-1">
                    <a href="<?php echo $basePath; ?>login.php" class="text-gray-700 hover:bg-gray-50 hover:text-blue-600 block px-3 py-2 rounded-md text-base font-medium">
                        Autentificare
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Flash messages -->
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <?php echo getFlashMessage(); ?>
    </div>

    <!-- Main content -->
    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-6">