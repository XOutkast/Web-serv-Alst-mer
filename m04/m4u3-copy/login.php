<?php
/**
 * Syfte: Hanterar manuell inloggning + auto-login via rememberme-cookie. och den har säkerhet: password_verify för lösenord; session_regenerate_id för att motverka fixation.
 **/
session_start();
if (!isset($_SESSION['loggedin']) && isset($_COOKIE['rememberme'])) {
    $username = $_COOKIE['rememberme'];
    $filename = "users.json";
    $role = "användare";
    $found = false;
    if (file_exists($filename)) {
        $users = json_decode(file_get_contents($filename), true) ?? [];
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $found = true;
                $role = $user['role'] ?? "användare";
                break;
            }
        }
    }
    if ($found) {
        if (function_exists('session_regenerate_id')) { @session_regenerate_id(true); }
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        header("Location: admin.php");
        exit;
    } else {
        setcookie("rememberme", "", time() - 3600, "/");
    }
}

$filename = "users.json";
$users = [];
if (file_exists($filename)) {
    $users = json_decode(file_get_contents($filename), true) ?? [];
}

$found = false;
$role = "användare";

// Kollar om formuläret har skickats in
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']); // isset kollar & håller den inloggad om är inktyssad för den "Håll mig inloggad" button

    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $found = true;
            $role = $user['role'] ?? "användare";
            break;
        }
    }
    if ($found) {
        if (function_exists('session_regenerate_id')) { @session_regenerate_id(true); }
        $_SESSION['loggedin'] = true; // Sätter att användaren är inloggad
        $_SESSION['username'] = $username; // Sparar användarnamnet i sessionen
        $_SESSION['role'] = $role; // Sparar rollen i sessionen
    // "Håll mig inloggad" -> sätter cookie
    if ($remember) {
            setcookie("rememberme", $username, time() + (86400 * 7), "/", "", false, true);
        } else {
            setcookie("rememberme", "", time() - 3600, "/"); // Tar bort cookie om den 'r ej ikryssad
        }
        header("Location: admin.php"); // Skicka den till adminsidan
        exit;
    } else {
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
<form method="POST" action="">
    <label for="username">Användarnamn:</label>
    <input type="text" name="username" id="username" required>
    <br>
    <label for="password">Lösenord:</label>
    <input type="password" name="password" id="password" required>
    <br>
    <label for="chk">
        <input type="checkbox" name="remember" id="chk">
        <span class="check">Håll mig inloggad</span>
    </label>
    <br>
    <input type="submit" value="Logga in">
</form>
</body>
</html>

