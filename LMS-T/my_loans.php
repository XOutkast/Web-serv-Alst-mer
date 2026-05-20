<?php

session_start();
require_once 'db_cnnt.php';
global $pdo;

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?noaccess=1');
    exit;
}

// hämta det inloggade användaren id från session
$user_id = $_SESSION['user_id'];

//  Återlämning av bok
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_loan'])) {
    $loan_id = (int)$_POST['loan_id'];
    
    // Hämta låne-information
    $statement = $pdo->prepare("SELECT exemplar_id, förfallodatum FROM lån WHERE lån_id = ? AND användare_id = ? AND återlämnad_datum IS NULL");
    $statement->execute([$loan_id, $user_id]);
    $loan = $statement->fetch();
    
    if ($loan) {
        // Beräkna förseningsavgift
        $fee = 0;
        $due = new DateTime($loan['förfallodatum']);
        $now = new DateTime();
        if ($now > $due) {
            $diff = $now->diff($due);
            $fee = $diff->days * 10;
        }
        
        // Uppdatera lån med återlämningsdatum och avgift
        $pdo->prepare("UPDATE lån SET återlämnad_datum = NOW(), förseningsavgift = ? WHERE lån_id = ?")
            ->execute([$fee, $loan_id]);
            
        // Markera exemplar som tillgängligt
        $pdo->prepare("UPDATE exemplar SET status = 'available' WHERE exemplar_id = ?")
            ->execute([$loan['exemplar_id']]);
            
        $_SESSION['loan_success'] = $fee > 0 ? "Bok återlämnad! Förseningsavgift: {$fee} SEK" : "Bok återlämnad!";
        header('Location: my_loans.php');
        exit;
    } else {
        // Om lånet inte finns eller redan  inlämnat
        $_SESSION['loan_error'] = "Lånet kunde inte hittas eller är redan återlämnat.";
        header('Location: my_loans.php');
        exit;
    }
}

// Förlängning av lån
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extend_loan'])) {
    $loan_id = (int)$_POST['loan_id'];
    
    // Hämta lånet och kontrollera antal tidigare förlängningar
    $statement = $pdo->prepare("SELECT lån_id, förlängningar FROM lån WHERE lån_id = ? AND användare_id = ? AND återlämnad_datum IS NULL");
    $statement->execute([$loan_id, $user_id]);
    $loan = $statement->fetch();
    
    if ($loan) {
        // Max 2 förlängningar tillåtna
        if ($loan['förlängningar'] >= 2) {
            $_SESSION['loan_error'] = "Du kan bara förlänga ett lån 2 gånger!";
        } else {
            // Förläng med 14 dagar
            $pdo->prepare("UPDATE lån SET förfallodatum = DATE_ADD(förfallodatum, INTERVAL 14 DAY), förlängningar = förlängningar + 1 WHERE lån_id = ?")
                ->execute([$loan_id]);
            $remaining = 2 - ($loan['förlängningar'] + 1);
            $_SESSION['loan_success'] = "Lånetiden förlängd med 14 dagar! (" . $remaining . " förlängningar kvar)";
        }
        header('Location: my_loans.php');
        exit;
    } else {
        $_SESSION['loan_error'] = "Lånet kunde inte hittas eller är redan återlämnat.";
        header('Location: my_loans.php');
        exit;
    }
}

// Inkludera header 
include 'header.php';

$message = '';
$message_type = 'success';

// Kontrollera om det finns success-meddelande
if (isset($_SESSION['loan_success'])) {
    $message = $_SESSION['loan_success'];
    $message_type = 'success';
    unset($_SESSION['loan_success']);
}

// Kontrollera om det finns felmeddelande
if (isset($_SESSION['loan_error'])) {
    $message = $_SESSION['loan_error'];
    $message_type = 'error';
    unset($_SESSION['loan_error']);
}

// Hämta användarens aktiva lån 
$statement = $pdo->prepare("SELECT l.lån_id, l.exemplar_id, b.titel, b.bok_id, f.namn AS författare, f.författare_id, l.lånedatum, l.förfallodatum, l.återlämnad_datum, l.förseningsavgift, l.förlängningar FROM lån l JOIN exemplar e ON l.exemplar_id = e.exemplar_id JOIN bok b ON e.bok_id = b.bok_id JOIN författare f ON b.författare_id = f.författare_id WHERE l.användare_id = ? AND l.återlämnad_datum IS NULL ORDER BY l.lånedatum DESC");
$statement->execute([$user_id]);
$loans = $statement->fetchAll();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mina lån - Bibliotek</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Mina lån</h1>
    <?php if ($message): ?>
        <div class="notice <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <table>
        <thead>
            <tr>
                <th>Boktitel</th>
                <th>Författare</th>
                <th>Lånedatum</th>
                <th>Förfallodatum</th>
                <th>Återlämnad</th>
                <th>Förseningsavgift</th>
                <th>Åtgärder</th>
            </tr>
        </thead>
        <tbody>
        <!--lista låner-->
            <?php foreach ($loans as $loan): ?>
                <tr>
                    <td><a href="book.php?id=<?= $loan['bok_id'] ?>"><?= htmlspecialchars($loan['titel']) ?></a></td>
                    <td><a href="authors.php?id=<?= $loan['författare_id'] ?>"><?= htmlspecialchars($loan['författare']) ?></a></td>
                    <td><?= htmlspecialchars($loan['lånedatum']) ?></td>
                    <td><?= htmlspecialchars($loan['förfallodatum']) ?></td>
                    <td><?= $loan['återlämnad_datum'] ? htmlspecialchars($loan['återlämnad_datum']) : 'Ej återlämnad' ?></td>
                    <td><?= $loan['förseningsavgift'] ? htmlspecialchars($loan['förseningsavgift']) . ' SEK' : '-' ?></td>
                    <td>
                        <?php if (!$loan['återlämnad_datum']): ?>
                            <form method="post">
                                <input type="hidden" name="loan_id" value="<?= $loan['lån_id'] ?>">
                                <?php if ($loan['förlängningar'] >= 2): ?>
                                    <button type="button" disabled title="Max 2 förlängningar">Förläng (0/2)</button>
                                <?php else: ?>
                                    <button type="submit" name="extend_loan" onclick="return confirm('Förlänga lånet med 14 dagar?')">Förläng (<?= 2 - $loan['förlängningar'] ?>/2)</button>
                                <?php endif; ?>
                            </form>
                            <form method="post">
                                <input type="hidden" name="loan_id" value="<?= $loan['lån_id'] ?>">
                                <button type="submit" name="return_loan" value="1" onclick="return confirm('Återlämna denna bok?')">Återlämna</button>
                            </form>
                        <?php else: ?>
                            <span class="loan-completed">Avslutad</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
