<?php
session_start();
require_once 'config.php';

// Kontrollera att användaren är inloggad
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php?noaccess=1");
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

global $pdo;

switch ($action) {
    case 'deposit':
            // Insättning på konto
            $accountId = (int)($_POST['account'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);

            // Kontrollera att beloppet är giltigt
            if ($amount <= 0) {
                $_SESSION['error'] = "Beloppet måste vara större än 0";
                header("Location: admin.php");
                exit;
            }

            // Kontrollera att kontot tillhör användaren
            $Qstmts = $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND user_id = ?");
            $Qstmts->execute([$accountId, $userId]);
            if (!$Qstmts->fetch()) {
                $_SESSION['error'] = "Kontot hittades inte";
                header("Location: admin.php");
                exit;
            }

            // Uppdatera saldo
            $Qstmts = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
            $Qstmts->execute([$amount, $accountId]);

            // Skapa transaktion
            $Qstmts = $pdo->prepare("
                INSERT INTO transactions (user_id, account_id, type, amount, date) 
                VALUES (?, ?, 'deposit', ?, NOW())
            ");
            $Qstmts->execute([$userId, $accountId, $amount]);

            $_SESSION['message'] = "Insättning genomförd! +" . $amount . " kr";
            header("Location: admin.php");
            exit;

        case 'withdraw':
            // Uttag från konto
            $accountId = (int)($_POST['account'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);

            // Kontrollera att beloppet är giltigt
            if ($amount <= 0) {
                $_SESSION['error'] = "Beloppet måste vara större än 0";
                header("Location: admin.php");
                exit;
            }

            // Hämta nuvarande saldo
            $Qstmts = $pdo->prepare("SELECT balance, account_name FROM accounts WHERE id = ? AND user_id = ?");
            $Qstmts->execute([$accountId, $userId]);
            $account = $Qstmts->fetch();

            if (!$account) {
                $_SESSION['error'] = "Kontot hittades inte";
                header("Location: admin.php");
                exit;
            }

            // Kontrollera om tillräckligt saldo finns
            if ($account['balance'] < $amount) {
                $_SESSION['error'] = "Otillräckligt saldo. Du har " . $account['balance'] . " kr på " . htmlspecialchars($account['account_name']);
                header("Location: admin.php");
                exit;
            }

            // Uppdatera saldo
            $Qstmts = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $Qstmts->execute([$amount, $accountId]);

            // Skapa transaktion
            $Qstmts = $pdo->prepare("
                INSERT INTO transactions (user_id, account_id, type, amount, date) 
                VALUES (?, ?, 'withdraw', ?, NOW())
            ");
            $Qstmts->execute([$userId, $accountId, -$amount]);

            $_SESSION['message'] = "Uttag genomfört! -" . $amount . " kr";
            header("Location: admin.php");
            exit;

        case 'transfer':
            // Överföring mellan konton
            $fromAccountId = (int)($_POST['from_account'] ?? 0);
            $toAccountId = (int)($_POST['to_account'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);

            // Kontrollera att beloppet är giltigt
            if ($amount <= 0) {
                $_SESSION['error'] = "Beloppet måste vara större än 0";
                header("Location: admin.php");
                exit;
            }

            // Förhindra överföring till samma konto
            if ($fromAccountId === $toAccountId) {
                $_SESSION['error'] = "Kan inte överföra till samma konto";
                header("Location: admin.php");
                exit;
            }

            // Hämta avsändarkonto
            $Qstmts = $pdo->prepare("SELECT balance, account_name FROM accounts WHERE id = ? AND user_id = ?");
            $Qstmts->execute([$fromAccountId, $userId]);
            $fromAccount = $Qstmts->fetch();

            if (!$fromAccount) {
                $_SESSION['error'] = "Avsändarkontot hittades inte";
                header("Location: admin.php");
                exit;
            }

            // Kontrollera om tillräckligt saldo finns
            if ($fromAccount['balance'] < $amount) {
                $_SESSION['error'] = "Otillräckligt saldo på " . htmlspecialchars($fromAccount['account_name']) . ". Du har " . $fromAccount['balance'] . " kr";
                header("Location: admin.php");
                exit;
            }

            // Kontrollera att mottagarkontot tillhör användaren
            $Qstmts = $pdo->prepare("SELECT account_name FROM accounts WHERE id = ? AND user_id = ?");
            $Qstmts->execute([$toAccountId, $userId]);
            $toAccount = $Qstmts->fetch();

            if (!$toAccount) {
                $_SESSION['error'] = "Mottagarkontot hittades inte";
                header("Location: admin.php");
                exit;
            }

            // Dra från avsändarkonto
            $Qstmts = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $Qstmts->execute([$amount, $fromAccountId]);

            // Lägg till på mottagarkonto
            $Qstmts = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
            $Qstmts->execute([$amount, $toAccountId]);

            // Skapa transaktion för avsändare
            $Qstmts = $pdo->prepare("
                INSERT INTO transactions (user_id, account_id, type, amount, date) 
                VALUES (?, ?, 'transfer-out', ?, NOW())
            ");
            $Qstmts->execute([$userId, $fromAccountId, -$amount]);

            // Skapa transaktion för mottagare
            $Qstmts = $pdo->prepare("
                INSERT INTO transactions (user_id, account_id, type, amount, date) 
                VALUES (?, ?, 'transfer-in', ?, NOW())
            ");
            $Qstmts->execute([$userId, $toAccountId, $amount]);

            $_SESSION['message'] = "Överföring genomförd! " . $amount . " kr från " .
                htmlspecialchars($fromAccount['account_name']) . " till " .
                htmlspecialchars($toAccount['account_name']);
            header("Location: admin.php");
            exit;

        case 'open-account':
            // Skapa nytt konto
            $accountName = trim($_POST['account_name'] ?? '');

            // Kontrollera att kontonamn är ifyllt
            if (empty($accountName)) {
                $_SESSION['error'] = "Kontonamn får inte vara tomt";
                header("Location: admin.php");
                exit;
            }

            // Kontrollera om kontot redan finns
            $Qstmts = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ? AND account_name = ?");
            $Qstmts->execute([$userId, $accountName]);
            if ($Qstmts->fetch()) {
                $_SESSION['error'] = "Ett konto med namnet '" . htmlspecialchars($accountName) . "' finns redan";
                header("Location: admin.php");
                exit;
            }

            // Skapa konto
            $Qstmts = $pdo->prepare("
                INSERT INTO accounts (user_id, account_name, balance, created_at) 
                VALUES (?, ?, 0, NOW())
            ");
            $Qstmts->execute([$userId, $accountName]);

            $newAccountId = $pdo->lastInsertId();

            // Skapa transaktion för kontoöppning
            $Qstmts = $pdo->prepare("
                INSERT INTO transactions (user_id, account_id, type, amount, date) 
                VALUES (?, ?, 'account-open', 0, NOW())
            ");
            $Qstmts->execute([$userId, $newAccountId]);

            $_SESSION['message'] = "Nytt konto skapat! " . htmlspecialchars($accountName);
            header("Location: admin.php");
            exit;

        default:
            header("Location: admin.php");
            exit;
}
