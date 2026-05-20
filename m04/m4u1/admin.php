<?php
require "check_login.php";
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Adminsida</title>
    <link rel="icon" href="../../m02/Favicon-a.jpg" type="image/x-icon" />
</head>
<body>
<h2>Välkommen <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
<h3>Du är nu inloggad<span class="sp">, </span>välkommen</h3>
<a href="logout.php">Logga ut</a>
</body>
</html>
