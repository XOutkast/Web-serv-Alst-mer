<?php
/**
 * Syfte: Inkluderas av sidor som kräver inloggning. Säkerställer aktiv session.
 * Auto-login: Försöker logga in via rememberme-cookie om session saknas.
 */
session_start();
// Om ej inloggad men cookie finns -> försök auto-login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (isset($_COOKIE['rememberme'])) {
        $username = $_COOKIE['rememberme'];
        $filename = "users.json";
        $users = [];
        if (file_exists($filename)) {
            $users = json_decode(file_get_contents($filename), true) ?? [];
        }
        $found = false;
        $role = "användare";
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $found = true;
                $role = $user['role'] ?? "användare";
                break;
            }
        }
        if ($found) {
            // Regenerera ID för att förhindra session fixation
            if (function_exists('session_regenerate_id')) { @session_regenerate_id(true); }
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
        } else {
            setcookie("rememberme", "", time() - 3600, "/"); // Ogiltig cookie, ta bort
            header("Location: index.php?noaccess=1");
            exit;
        }
    } else {
        header("Location: index.php?noaccess=1");
        exit;
    }
}
?>