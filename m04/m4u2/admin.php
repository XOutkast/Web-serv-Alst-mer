<?php
require "check_login.php"; // Kollar att användaren är inloggad
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Adminsida</title>
    <link rel="icon" href="../../m02/Favicon-a.jpg" type="image/x-icon" />
</head>
<body>
<h2>Välkommen <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2> <!-- Visar användarnamn -->
<h3>Du är nu inloggad<span class="sp">, </span>välkommen</h3>
<p>Din roll: <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong></p> <!-- Visar roll -->
<a href="logout.php">Logga ut</a> <!-- Logga ut-länk -->
</body>
</html>
