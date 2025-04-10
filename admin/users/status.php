<?php
// admin/users/status.php
// Pagina pentru schimbarea stării (activ/inactiv) unui utilizator

// Inițializare sesiune și autentificare admin
require_once '../../includes/auth.php';
authenticateAdmin();

// Include fișiere necesare
require_once '../../classes/User.php';

// Verificare ID utilizator și status
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['status']) || !in_array($_GET['status'], ['active', 'inactive'])) {
    setFlashMessage('error', 'Parametri invalizi.');
    redirect('index.php');
}

$user_id = (int)$_GET['id'];
$new_status = $_GET['status'];

// Verificare token CSRF
if (!isset($_GET['csrf_token']) || !verifyCSRFToken($_GET['csrf_token'])) {
    setFlashMessage('error', 'Eroare de securitate. Vă rugăm să încercați din nou.');
    redirect('index.php');
}

// Inițializare obiecte
$userObj = new User();

// Obține informațiile utilizatorului
$user = $userObj->getUserById($user_id);

// Verificare existență utilizator
if (!$user) {
    setFlashMessage('error', 'Utilizatorul nu există.');
    redirect('index.php');
}

// Nu permitem dezactivarea propriului cont
if ($user_id == $_SESSION['user_id']) {
    setFlashMessage('error', 'Nu vă puteți dezactiva propriul cont.');
    redirect('view.php?id=' . $user_id);
}

// Procesăm schimbarea statutului
$userData = [
    'id' => $user_id,
    'status' => $new_status
];

$result = $userObj->updateUserStatus($userData);

if ($result) {
    $statusMessage = $new_status === 'active' ? 'activat' : 'dezactivat';
    setFlashMessage('success', 'Utilizatorul a fost ' . $statusMessage . ' cu succes.');
} else {
    setFlashMessage('error', 'A apărut o eroare la actualizarea statusului utilizatorului. Vă rugăm să încercați din nou.');
}

// Redirecționăm către pagina cu detalii utilizator
redirect('view.php?id=' . $user_id);
?>