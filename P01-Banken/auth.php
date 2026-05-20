<?php
session_start();
// Tidszon
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Europe/Stockholm');
}
require_once 'json_store.php';

// Vilken åtgärd? (register|login|logout|delete)
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        // Registrera ny användare och ge 1000 kr i startsaldo på deras 'Huvudkonto'
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validering: båda fält måste vara ifyllda
        if (empty($username) || empty($password)) {
            header("Location: index.php?error=empty");
            exit;
        }

        // Kontrollera att användarnamn inte redan finns
        $users = load_users();
        foreach ($users as $u) {
            if (isset($u['username']) && $u['username'] === $username) {
                header("Location: index.php?error=exists");
                exit;
            }
        }

        // Spara ny användare med hashat lösenord
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $users[] = [
            'username' => $username,
            'password' => $passwordHash,
            'role' => 'user'
        ];
        if (!save_users($users)) {
            header("Location: index.php?error=unknown");
            exit;
        }

        // Skapa transaktion för startsaldo (beräkna saldo från transaktioner)
        append_transaction([
            'username' => $username,
            'account' => 'Huvudkonto',
            'amount' => 1000,
            'type' => 'deposit',
            'date' => now_str()
        ]);

        header("Location: index.php?success=registered");
        exit;

    case 'login':
        // Logga in befintlig konto
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Kontroller att lösenord & användnamn är ifyllda och inte tom
        if (empty($username) || empty($password)) {
            header("Location: index.php?error=invalid");
            exit;
        }
        // Hämta användare och matcha namn
        $users = load_users();
        $user = null;
        foreach ($users as $u) {
            if (isset($u['username']) && $u['username'] === $username) {
                $user = $u;
                break;
            }
        }
        // Om användaren inte finns, skicka tillbaka till inloggning och stoppa koden
        if (!$user) {
            header("Location: index.php?error=invalid");
            exit;
        }

        // Verifiera lösenordet mot hash
        if (!password_verify($password, $user['password'])) {
            header("Location: index.php?error=invalid");
            exit;
        }

        // Sätt cookie om "Håll mig inloggad" är valt
        if ($remember) {
            setcookie('username', $user['username'], time() + (60 * 60 * 24 * 3), '/');
        }

        // Spara användardata i session
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['loggedin'] = true;

        header("Location: admin.php");
        exit;

    case 'logout':
        // Logga ut
        setcookie('username', '', time() - 3600, '/');
        session_destroy();
        header("Location: index.php");
        exit;

    case 'delete':
        // Radera användare och transaktioner
        // Hämta användarnamn; om saknas, omdirigera
        $uname = $_SESSION['username'] ?? null;
        if (!$uname) {
            header("Location: index.php");
            exit;
        }

        // Ta bort användare ur users.json
        $users = load_users();
        $users = array_values(array_filter($users, function ($u) use ($uname) {
            return ($u['username'] ?? null) !== $uname;
        }));
        save_users($users);

        // Ta bort användarens transaktioner
        $txs = load_transactions();
        $txs = array_values(array_filter($txs, function ($t) use ($uname) {
            return ($t['username'] ?? null) !== $uname;
        }));
        save_transactions($txs);

        // Ta bort cookie
        setcookie('username', '', time() - 3600, '/');
        
        // Avsluta session och omdirigera med bekräftelse
        session_destroy();
        header("Location: index.php?success=deleted");
        exit;

    default:
        header("Location: index.php");
        exit;
}

?>