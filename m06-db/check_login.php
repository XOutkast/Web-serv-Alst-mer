<?php
session_start();

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php?noaccess=1");
    exit;
}
?>
