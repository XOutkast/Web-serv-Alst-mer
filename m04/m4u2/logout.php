<?php
session_start(); // Startar sessionen
setcookie("rememberme", "", time() - 3600, "/"); // Tar bort rememberme-cookie
session_unset();  // Tömmer sessionen
session_destroy(); // Förstör sessionen
header("Location: index.php"); // Skickar till startsidan
exit;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Logga UT</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../../m02/Favicon-a.jpg" type="image/x-icon">
</head>
<body>

</body>
</html>
