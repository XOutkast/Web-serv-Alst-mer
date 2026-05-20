<?php
include 'db.php';

$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'about', 'products', 'contact'];

if (!in_array($page, $allowed_pages)) {
    echo "Page not found!";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">

    <title>Test Website - <?php echo ucfirst($page); ?></title>
</head>
<body>
<header>
    <div class="logo">My Test Site</div>
    <nav>
        <a href="index.php?page=home">Home</a>
        <a href="index.php?page=about">About</a>
        <a href="index.php?page=products">Products</a>
        <a href="index.php?page=contact">Contact</a>
    </nav>
</header>

<main>
    <?php include "pages/{$page}.php"; ?>
</main>

<footer>
    <p>&copy; 2025 My Test Site</p>
</footer>

<script src="assets/js/script.js"></script>
</body>
</html>
