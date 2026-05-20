<?php
/**
 * Syfte: Skapar nytt användarkonto och hashar lösenord (password_hash).
 */
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
// Spara ny användare med hashat lösenord (rolen är admin om användnamn är 'Admin')
$users[] = [
    "username" => $username,
    "password" => password_hash($password, PASSWORD_DEFAULT),
    "role" => ($username === "Admin" ? "admin" : "användare")
];
// Skriv med fil-lås (LOCK_EX)
file_put_contents($filename, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
header('Location: index.php?regsuccess=1');
exit;
?>
