<?php
// börja session och sätta tidszon
session_start();
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Europe/Stockholm');
}
// lägg till JSON-hanteringsfilen
require_once 'json_store.php';

// Kontrollera inloggning (krävs för att nå åtgärderna)
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['username'])) {
    header("Location: index.php?noaccess=1");
    exit;
}

// Hämta "action" från URL:en (GET) och "username" från sessionen
$action = $_GET['action'] ?? '';
$username = $_SESSION['username'];

// switch statements
switch ($action) {
    case 'deposit':
        // Insättning till valt konto
        $accountName = trim($_POST['account'] ?? '');
        $amount = (int)($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            $_SESSION['error'] = "Beloppet måste vara större än 0";
            header("Location: admin.php");
            exit;
        }
        
        // Kontrollera att kontot finns för användaren
        $balances = compute_balances_for_user($username);
        if (!array_key_exists($accountName, $balances)) {
            $_SESSION['error'] = "Kontot hittades inte";
            header("Location: admin.php");
            exit;
        }

        // Skapa händelse (saldo beräknas alltid från händelser)
        append_transaction([
            'username' => $username,
            'account' => $accountName,
            'amount' => $amount,
            'type' => 'deposit',
            'date' => now_str(),
        ]);

        $_SESSION['message'] = "Insättning genomförd! +" . $amount . " kr";
        header("Location: admin.php");
        exit;

    case 'withdraw':
        // Uttag från valt konto
        $accountName = trim($_POST['account'] ?? '');
        $amount = (int)($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            $_SESSION['error'] = "Beloppet måste vara större än 0";
            header("Location: admin.php");
            exit;
        }
        
        // Kontrollera att kontot finns och att saldot räcker
        $balances = compute_balances_for_user($username);
        if (!array_key_exists($accountName, $balances)) {
            $_SESSION['error'] = "Kontot hittades inte";
            header("Location: admin.php");
            exit;
        }
        
        if ($balances[$accountName] < $amount) {
            $_SESSION['error'] = "Otillräckligt saldo. Du har " . $balances[$accountName] . " kr på " . htmlspecialchars($accountName);
            header("Location: admin.php");
            exit;
        }

        // Skapa händelse (negativt belopp för uttag)
        append_transaction([
            'username' => $username,
            'account' => $accountName,
            'amount' => -$amount,
            'type' => 'withdraw',
            'date' => now_str(),
        ]);
        
        $_SESSION['message'] = "Uttag genomfört! -" . $amount . " kr";
        header("Location: admin.php");
        exit;

    case 'transfer':
        // Överföring: välj från-konto alltid, till-konto bara om till dig själv
        $fromAccount = trim($_POST['from_account'] ?? '');
        $toUser = trim($_POST['to_user'] ?? '');
        $toAccount = trim($_POST['to_account'] ?? '');
        $amount = (int)($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            $_SESSION['error'] = "Beloppet måste vara större än 0";
            header("Location: admin.php");
            exit;
        }
        
        if ($fromAccount === '') {
            $_SESSION['error'] = "Välj från vilket konto du vill överföra";
            header("Location: admin.php");
            exit;
        }
        
        if ($toUser === '') {
            $_SESSION['error'] = "Ange mottagarens användarnamn";
            header("Location: admin.php");
            exit;
        }

        // Kontroll av avsändarkonto och saldo
        $senderBalances = compute_balances_for_user($username);
        if (!array_key_exists($fromAccount, $senderBalances)) {
            $_SESSION['error'] = "Avsändarkontot hittades inte";
            header("Location: admin.php");
            exit;
        }
        
        if ($senderBalances[$fromAccount] < $amount) {
            $_SESSION['error'] = "Otillräckligt saldo på " . htmlspecialchars($fromAccount) . ". Du har " . $senderBalances[$fromAccount] . " kr";
            header("Location: admin.php");
            exit;
        }

        // Om till dig själv: välj till-konto, annars alltid Huvudkonto
        if ($toUser === $username) {
            if ($toAccount === '') {
                $_SESSION['error'] = "Välj till vilket av dina konton du vill överföra";
                header("Location: admin.php");
                exit;
            }
            if ($toAccount === $fromAccount) {
                $_SESSION['error'] = "Du kan inte överföra till samma konto";
                header("Location: admin.php");
                exit;
            }
            $recipientUser = $username;
            $recipientAccount = $toAccount;
        } else {
            // Kontrollera att mottagaren finns
            $allUsers = load_users();
            $recipientExists = false;
            foreach ($allUsers as $u) {
                if (($u['username'] ?? null) === $toUser) {
                    $recipientExists = true;
                    break;
                }
            }
            
            if (!$recipientExists) {
                $_SESSION['error'] = "Mottagaren finns inte";
                header("Location: admin.php");
                exit;
            }
            $recipientUser = $toUser;
            $recipientAccount = 'Huvudkonto';
        }

        // Skapa två händelser (en för avsändare, en för mottagare)
        append_transaction([
            'username' => $username,
            'account' => $fromAccount,
            'amount' => -$amount,
            'type' => 'transfer-out',
            'date' => now_str(),
        ]);
        append_transaction([
            'username' => $recipientUser,
            'account' => $recipientAccount,
            'amount' => $amount,
            'type' => 'transfer-in',
            'date' => now_str(),
        ]);
        
        // Bekräftelsemeddelande för överföring
        if ($recipientUser === $username) {
            $_SESSION['message'] = "Överföring genomförd! " . $amount . " kr från $fromAccount till $recipientAccount";
        } else {
            $_SESSION['message'] = "Överföring genomförd! " . $amount . " kr från $fromAccount till " .
                htmlspecialchars($recipientUser) . " (Huvudkonto)";
        }
        header("Location: admin.php");
        exit;

    case 'open-account':
        // Skapa ett nytt konto (0 kr) via händelse
        $accountName = trim($_POST['account_name'] ?? '');

        if (empty($accountName)) {
            $_SESSION['error'] = "Kontonamn får inte vara tomt";
            header("Location: admin.php");
            exit;
        }

        // Kontrollera om kontot redan finns för användaren
        $balances = compute_balances_for_user($username);
        if (array_key_exists($accountName, $balances)) {
            $_SESSION['error'] = "Ett konto med namnet '" . htmlspecialchars($accountName) . "' finns redan";
            header("Location: admin.php");
            exit;
        }

        // Skapa kontot genom en händelse (0 kr).
        append_transaction([
            'username' => $username,
            'account' => $accountName,
            'amount' => 0,
            'type' => 'account-open',
            'date' => now_str(),
        ]);

        $_SESSION['message'] = "Nytt konto skapat! " . htmlspecialchars($accountName);
        header("Location: admin.php");
        exit;

    default:
        header("Location: admin.php");
        exit;
}
?>