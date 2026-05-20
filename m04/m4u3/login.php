<?php
session_start();

$filename = "users.json";
$users = [];
if (file_exists($filename)) {
    $users = json_decode(file_get_contents($filename), true) ?? [];
}

$found = false;
$role = "user";

// Kollar om formuläret har skickats in
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $found = true;
            $role = $user['role'] ?? "user";
            break;
        }
    }
    
    if ($found) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        header("Location: admin.php");
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
    <input type="submit" value="Logga in">
</form>
</body>
</html>

