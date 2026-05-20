<?php

$filename = "users.json";
$users = [];
if (file_exists($filename)) {
    $users = json_decode(file_get_contents($filename), true) ?? [];
}

$username = trim($_POST['new_username'] ?? '');
$password = $_POST['new_password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: index.php?regerror=tomt');
    exit;
}

// Kontrollera om användarnamnet redan finns
foreach ($users as $user) {
    if ($user['username'] === $username) {
        header('Location: index.php?regerror=upptaget');
        exit;
    }
}

// Spara ny användare med hashat lösenord
$users[] = [
    "username" => $username,
    "password" => password_hash($password, PASSWORD_DEFAULT),
    "role" => ($username === "Admin" ? "admin" : "user")
];

file_put_contents($filename, json_encode($users, JSON_PRETTY_PRINT));
header('Location: index.php?regsuccess=1');
exit;
?>