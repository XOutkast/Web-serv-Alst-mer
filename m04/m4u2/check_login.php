<?php

session_start(); // Startar sessionen
// Om ej inloggad, men "rememberme"-cookie finns, logga in automatiskt
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (isset($_COOKIE['rememberme'])) {
        $_SESSION['loggedin'] = true; // Sätter att användaren är inloggad
        $_SESSION['username'] = $_COOKIE['rememberme']; // Hämtar användarnamn från cookie
        // Sätter roll: admin om Admin, annars user
        $_SESSION['role'] = ($_COOKIE['rememberme'] === 'Admin') ? 'admin' : 'user';
    } else {
        header("Location: index.php?noaccess=1"); // Skickar till startsidan om ej inloggad
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
    <title>Check login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../../m02/Favicon-a.jpg" type="image/x-icon">
</head>
<body>

</body>
</html>
