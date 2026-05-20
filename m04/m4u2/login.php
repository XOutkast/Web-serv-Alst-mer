<?php
session_start(); // Startar sessionen

// Skapar arrayer för användarnamn, lösenord och roller
$usernames = ["Admin", "User1", "User2"];
$passwords = ["1234", "pass1", "pass2"];
$roles = ["admin", "user", "user"];

// Kollar om formuläret har skickats in
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']); // Kollar om "Håll mig inloggad" är ikryssad

    $found = false; // Variabel för att se om användaren hittas
    for ($i = 0; $i < count($usernames); $i++) {
        // Jämför inmatat användarnamn och lösenord med arrayerna
        if ($usernames[$i] === $username && $passwords[$i] === $password) {
            $found = true;
            $_SESSION['loggedin'] = true; // Sätter att användaren är inloggad
            $_SESSION['username'] = $username; // Sparar användarnamnet i sessionen
            $_SESSION['role'] = $roles[$i]; // Sparar rollen i sessionen
            // Om "Håll mig inloggad" är ikryssad, skapa cookie
            if ($remember) {
                setcookie("rememberme", $username, time() + (86400 * 7), "/", "", false, true);
            } else {
                setcookie("rememberme", "", time() - 3600, "/"); // Tar bort cookie om ej ikryssad
            }
            header("Location: admin.php"); // Skickar till adminsidan
            exit;
        }
    }
    // Om användaren inte hittas, skicka tillbaka till index.php med felmeddelande
    if (!$found) {
        header("Location: index.php?error=1");
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Inloggningsapplikation</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../../m02/Favicon-a.jpg" type="image/x-icon">
</head>
<body>

</body>
</html>
