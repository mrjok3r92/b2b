<?php
// logout.php
// Deconectare utilizator

// Inițializare sesiune
session_start();

// Ștergere toate variabilele de sesiune
$_SESSION = [];

// Distrugere cookie-ul de sesiune
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Distrugere sesiune
session_destroy();

// Redirecționare către pagina de login
header('Location: login.php');
exit;