<?php
// includes/functions.php
// Funcții ajutătoare pentru aplicație

/**
 * Redirecționare către o pagină
 * @param string $page Pagina către care se face redirecționarea
 * @return void
 */
function redirect($page) {
    header('Location: ' . $page);
    exit;
}

/**
 * Verifică dacă un utilizator este autentificat
 * @return bool Returnează true dacă utilizatorul este autentificat, altfel false
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifică dacă utilizatorul autentificat are rolul specificat
 * @param string|array $roles Rolul sau rolurile acceptate
 * @return bool Returnează true dacă utilizatorul are rolul specificat, altfel false
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles);
    } else {
        return $_SESSION['role'] == $roles;
    }
}

/**
 * Restricționează accesul la pagină doar pentru utilizatorii cu rolul specificat
 * @param string|array $roles Rolul sau rolurile acceptate
 * @param string $redirect Pagina către care se face redirecționarea (implicit login.php)
 * @return void
 */
function requireRole($roles, $redirect = '../login.php') {
    if (!hasRole($roles)) {
        redirect($redirect);
    }
}

/**
 * Formatează o sumă în format monetar
 * @param float $amount Suma care trebuie formatată
 * @return string Suma formatată
 */
function formatAmount($amount) {
    return number_format($amount, 2, ',', '.');
}

/**
 * Formatează o dată în format românesc
 * @param string $date Data în format MySQL (Y-m-d H:i:s)
 * @param bool $includeTime Dacă se include și ora în formatare
 * @return string Data formatată
 */
function formatDate($date, $includeTime = false) {
    if (!$date) {
        return '';
    }
    
    $format = 'd.m.Y';
    if ($includeTime) {
        $format .= ' H:i';
    }
    
    return date($format, strtotime($date));
}

/**
 * Generează un token CSRF pentru protecția formularelor
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verifică validitatea unui token CSRF
 * @param string $token Token CSRF de verificat
 * @return bool Returnează true dacă tokenul este valid, altfel false
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}

/**
 * Sanitizează input-ul utilizatorului pentru a preveni XSS
 * @param string $input Input-ul care trebuie sanitizat
 * @return string Input-ul sanitizat
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generează un mesaj flash care va fi afișat în pagina următoare
 * @param string $type Tipul mesajului (success, error, warning, info)
 * @param string $message Conținutul mesajului
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Afișează mesajul flash și îl șterge din sesiune
 * @return string HTML-ul mesajului flash sau string gol dacă nu există mesaj
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $alertClass = 'bg-gray-100 border-gray-500 text-gray-700';
        
        switch ($message['type']) {
            case 'success':
                $alertClass = 'bg-green-100 border-green-500 text-green-700';
                $icon = '<i class="fas fa-check-circle"></i>';
                break;
            case 'error':
                $alertClass = 'bg-red-100 border-red-500 text-red-700';
                $icon = '<i class="fas fa-exclamation-circle"></i>';
                break;
            case 'warning':
                $alertClass = 'bg-yellow-100 border-yellow-500 text-yellow-700';
                $icon = '<i class="fas fa-exclamation-triangle"></i>';
                break;
            case 'info':
                $alertClass = 'bg-blue-100 border-blue-500 text-blue-700';
                $icon = '<i class="fas fa-info-circle"></i>';
                break;
        }
        
        return '<div class="' . $alertClass . ' px-4 py-3 mb-4 rounded border" role="alert">
                    <div class="flex">
                        <div class="py-1 mr-2">' . $icon . '</div>
                        <div>' . $message['message'] . '</div>
                    </div>
                </div>';
    }
    
    return '';
}

/**
 * Verifică dacă o cerere este de tip AJAX
 * @return bool Returnează true dacă cererea este AJAX, altfel false
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Generează un răspuns JSON pentru cererile AJAX
 * @param bool $success Dacă cererea a fost procesată cu succes
 * @param string $message Mesajul de răspuns
 * @param array $data Date adiționale pentru răspuns
 * @return void
 */
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Generează un select HTML populat cu opțiuni
 * @param string $name Numele select-ului
 * @param array $options Opțiunile disponibile (array asociativ id => text)
 * @param mixed $selected Valoarea selectată
 * @param array $attributes Atribute adiționale pentru select
 * @return string HTML-ul select-ului
 */
function generateSelect($name, $options, $selected = '', $attributes = []) {
    $attrs = '';
    foreach ($attributes as $key => $value) {
        $attrs .= " $key=\"$value\"";
    }
    
    $html = "<select name=\"$name\"$attrs>";
    
    foreach ($options as $value => $text) {
        $selectedAttr = $selected == $value ? ' selected' : '';
        $html .= "<option value=\"$value\"$selectedAttr>$text</option>";
    }
    
    $html .= '</select>';
    
    return $html;
}

/**
 * Obține URL-ul de bază al aplicației
 * @return string URL-ul de bază
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol . $domainName;
}

/**
 * Truncheaza un text la lungimea specificată
 * @param string $text Textul care trebuie truncheat
 * @param int $length Lungimea maximă
 * @param string $append Textul care se adaugă la sfârșit dacă textul este truncheat
 * @return string Textul truncheat
 */
function truncateText($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $append;
}

/**
 * Generează o pagină de eroare 404
 * @return void
 */
function show404() {
    header("HTTP/1.0 404 Not Found");
    include_once '../includes/404.php';
    exit;
}