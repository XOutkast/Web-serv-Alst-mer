<?php
/**
 * Syfte: radera nuvarande användare (och loggar ut) efter CSRF-validering.
 * Säkerhet: Kräver inloggning via check_login + CSRF-token.
 */
require 'check_login.php';
// CSRF-validering – endast giltiga formulär från vår sida tillåts
$csrf = $_POST['csrf'] ?? '';
if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    header('Location: admin.php');
    exit;
}
$filename = "users.json";
$users = [];
if (file_exists($filename)) {
    $users = json_decode(file_get_contents($filename), true) ?? [];
}
$username = $_POST['username'] ?? '';
// Filtrera bort den användare som man ska raderas
$newUsers = [];
foreach ($users as $user) {
    if ($user['username'] !== $username) {
        $newUsers[] = $user;
    }
}
// Skriv tillbaka listan utan användaren
file_put_contents($filename, json_encode($newUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
// Rensa cookies/session och skicka tillbaka till startsidan
setcookie("rememberme", "", time() - 3600, "/");
session_unset();
session_destroy();
header('Location: index.php?deleted=1');
exit;
?>
