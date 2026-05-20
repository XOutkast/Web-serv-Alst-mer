<?php
session_start();

// Tidszom
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Europe/Stockholm');
}

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php?noaccess=1");
    exit;
}

require_once 'json_store.php';

// Får användaren från namn från user
$uname = $_SESSION['username'] ?? null;
// Om ancändaren inte inloogad destroy session och omredigera
if (!$uname) {
    session_destroy();
    header("Location: index.php?noaccess=1");
    exit;
}
$users = load_users(); // Läs in alla användare
$found = null;
foreach ($users as $u) {
    if (($u['username'] ?? null) === $uname) {
        $found = $u;
        break;
    }
}
if (!$found) {
    session_destroy();
    header("Location: index.php?noaccess=1");
    exit;
}

?>
