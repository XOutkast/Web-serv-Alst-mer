<?php
session_start();
require_once 'config.php';
$action = $_GET['action'] ?? '';

global $pdo;

switch ($action) {
    case 'register':
            // Registrera ny användare
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Validera fält
            if (empty($username) || empty($email) || empty($password)) {
                header("Location: index.php?error=empty");
                exit;
            }

            // Kontrollera om användarnamn eller email redan finns
            $Qstmts = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $Qstmts->execute([$username, $email]);
            if ($Qstmts->fetch()) {
                header("Location: index.php?error=exists");
                exit;
            }

            // Skapa användare med hashat lösenord
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $Qstmts = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, created_at) 
                VALUES (?, ?, ?, 'user', NOW())
            ");
            $Qstmts->execute([$username, $email, $passwordHash]);
            $userId = $pdo->lastInsertId();

            // Skapa huvudkonto med startsaldo
            $Qstmts = $pdo->prepare("
                INSERT INTO accounts (user_id, account_name, balance, created_at) 
                VALUES (?, 'Huvudkonto', 1000.00, NOW())
            ");
            $Qstmts->execute([$userId]);
            $accountId = $pdo->lastInsertId();

            // Skapa starttransaktion
            $Qstmts = $pdo->prepare("
                INSERT INTO transactions (user_id, account_id, type, amount, date) 
                VALUES (?, ?, 'account-open', 1000.00, NOW())
            ");
            $Qstmts->execute([$userId, $accountId]);

            header("Location: index.php?success=registered");
            exit;

        case 'login':
            // Logga in användare
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);

            if (empty($username) || empty($password)) {
                header("Location: index.php?error=invalid");
                exit;
            }

            // Hämta användare
            $Qstmts = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $Qstmts->execute([$username]);
            $user = $Qstmts->fetch();

            if (!$user) {
                header("Location: index.php?error=invalid");
                exit;
            }

            // Verifiera lösenord
            if (!password_verify($password, $user['password_hash'])) {
                header("Location: index.php?error=invalid");
                exit;
            }

            // Uppdatera senaste inloggning
            $Qstmts = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $Qstmts->execute([$user['id']]);

            // Spara cookie om "Håll mig inloggad" är ikryssad
            if ($remember) {
                setcookie('username', $user['username'], time() + (30 * 24 * 60 * 60), '/');
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['loggedin'] = true;

            header("Location: admin.php");
            exit;

        case 'logout':
            // Logga ut användare
            setcookie('username', '', time() - 3600, '/');
            session_destroy();
            header("Location: index.php");
            exit;

        case 'delete':
            // Radera användare och tillhörande data
            if (!isset($_SESSION['user_id'])) {
                header("Location: index.php");
                exit;
            }

            $userId = $_SESSION['user_id'];
            
            // CASCADE DELETE tar bort accounts + transactions automatiskt
            $Qstmts = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $Qstmts->execute([$userId]);

            setcookie('username', '', time() - 3600, '/');
            session_destroy();
            header("Location: index.php?success=deleted");
            exit;

        default:
            header("Location: index.php");
            exit;
}