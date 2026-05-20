<?php
require 'check_login.php';

$filename = "users.json";
$users = [];
if (file_exists($filename)) {
    $users = json_decode(file_get_contents($filename), true) ?? [];
}

$username = $_POST['username'] ?? '';

// Filtrera bort den användare som ska raderas
$newUsers = [];
foreach ($users as $user) {
    if ($user['username'] !== $username) {
        $newUsers[] = $user;
    }
}

// Skriv tillbaka listan utan användaren
file_put_contents($filename, json_encode($newUsers, JSON_PRETTY_PRINT));

// Rensa session och skicka tillbaka till startsidan
session_unset();
session_destroy();
header('Location: index.php?deleted=1');
exit;
?>
