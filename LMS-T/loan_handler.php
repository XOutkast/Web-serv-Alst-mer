<?php

session_start();
require_once 'db_cnnt.php';
global $pdo;
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?noaccess=1');
    exit;
}

if (!isset($_POST['book_id']) || empty($_POST['book_id'])) {
    $_SESSION['loan_error'] = 'Ingen bok vald.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'home.php'));
    exit;
}

$book_id = (int)$_POST['book_id'];
$user_id = $_SESSION['user_id'];

// raadi copy-ga (find available exemplar)
$statement = $pdo->prepare("SELECT exemplar_id FROM exemplar WHERE bok_id = ? AND status = 'available' LIMIT 1");
$statement->execute([$book_id]);
$exemplar = $statement->fetch();

if (!$exemplar) {
    $_SESSION['loan_error'] = 'Ingen tillgänglig kopia av denna bok finns just nu.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'home.php'));
    exit;
}

$exemplar_id = $exemplar['exemplar_id'];

// check if user already has this book
$statement = $pdo->prepare("SELECT COUNT(*) as count FROM lån l JOIN exemplar e ON l.exemplar_id = e.exemplar_id WHERE l.användare_id = ? AND e.bok_id = ? AND l.återlämnad_datum IS NULL");
$statement->execute([$user_id, $book_id]);
$existing = $statement->fetch();

if ($existing['count'] > 0) {
    $_SESSION['loan_error'] = 'Du har redan lånat denna bok.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'home.php'));
    exit;
}

// markera bok exemplen som utlånat
$statement = $pdo->prepare("UPDATE exemplar SET status = 'loaned' WHERE exemplar_id = ?");
$statement->execute([$exemplar_id]);

// 14 dagar lån
$statement = $pdo->prepare("INSERT INTO lån (exemplar_id, användare_id, lånedatum, förfallodatum) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY))");
$statement->execute([$exemplar_id, $user_id]);

$_SESSION['loan_success'] = 'Boken har lånats! Återlämnas senast ' . date('Y-m-d', strtotime('+14 days')) . '.';
header('Location: my_loans.php');
exit;
