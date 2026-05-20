<?php
session_start();
require_once 'db_cnnt.php';
global $pdo;

$action = $_POST['action'] ?? '';
$error = '';
$success = '';

// salt pass, custom hashing
function customPasswordHash($pwd)
{
    $saltBefore = "12Aq@y";
    $saltAfter = "ö%$";
    return sha1($saltBefore . $pwd . $saltAfter);
}

function customPasswordVerify($pwd, $hash)
{
    return customPasswordHash($pwd) === $hash;
}

// register new users - validate input & create account
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Fyll i alla fält.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ogiltig e-postadress.';
    } elseif (strlen($password) < 4) {
        $error = 'Lösenordet måste vara minst 4 tecken långt.';
    } else {
        // Kontrollera om användarnamn eller e-post redan finns
        $statement = $pdo->prepare("SELECT användare_id FROM användare WHERE användare_namn = ? OR email = ?");
        $statement->execute([$username, $email]);
        if ($statement->fetch()) {
            $error = 'Användarnamnet eller e-postadressen finns redan.';
        } else {
            // Endast SHA1 kryptering
            $sha1Hash = customPasswordHash($password);
            $statement = $pdo->prepare("INSERT INTO användare (användare_namn, email, sha1_hash, roll_id, skapad) VALUES (?, ?, ?, 2, NOW())");
            $statement->execute([$username, $email, $sha1Hash]);
            $success = 'Konto skapat! Du kan nu logga in.';
        }
    }
}

// login
if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // validate input then fetch user from the db
    if (empty($username) || empty($password)) {
        $error = 'Felaktigt användarnamn eller lösenord.';
    } else {
        $statement = $pdo->prepare("SELECT a.*, r.roll_namn FROM användare a JOIN roll r ON a.roll_id = r.roll_id WHERE a.användare_namn = ?");
        $statement->execute([$username]);
        $user = $statement->fetch();

        if ($user && !empty($user['sha1_hash'])) {
            $valid = customPasswordVerify($password, $user['sha1_hash']);
        } else {
            $valid = false;
        }
        // check login - if valid set session & cookies (remember me = 7 days)
        if (!$user || !$valid) {
                $error = 'Felaktigt användarnamn eller lösenord.';
            } else {
                if ($remember) {
                    $expire = time() + (7 * 24 * 60 * 60);
                    setcookie('user_id', $user['användare_id'], $expire, '/');
                    setcookie('username', $user['användare_namn'], $expire, '/');
                    setcookie('role', $user['roll_namn'], $expire, '/');
            }

            $_SESSION['user_id'] = $user['användare_id'];
            $_SESSION['username'] = $user['användare_namn'];
            $_SESSION['role'] = $user['roll_namn'];
            $_SESSION['loggedin'] = true;
            header("Location: home.php");
            exit;
        }
    }
}

// Kontrollera om användaren är inloggad
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

// Logga ut användare
if ($action === 'logout') {
    // Radera alla cookies
    setcookie('user_id', '', time() - 3600, '/');
    setcookie('username', '', time() - 3600, '/');
    setcookie('role', '', time() - 3600, '/');
    session_destroy();
    header("Location: index.php");
    exit;
}

// Byt lösenord (kräver att användaren är inloggad)
if ($action === 'change_password') {
    if (!$isLoggedIn) {
        header("Location: index.php?noaccess=1");
        exit;
    }
    // få lösenord fields från formen
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validera lösenord fältet
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Fyll i alla fält.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Lösenorden matchar inte.';
    } else {
        // Hämta nuvarande lösenord från databasen
        $statement = $pdo->prepare("SELECT sha1_hash FROM användare WHERE användare_id = ?");
        $statement->execute([$_SESSION['user_id']]);
        $user = $statement->fetch();

        // Verifiera nuvarande lösenord
        $valid = false;
        if (!empty($user['sha1_hash'])) {
            $valid = customPasswordVerify($currentPassword, $user['sha1_hash']);
        }

        if (!$valid) {
            $error = 'Fel nuvarande lösenord.';
        } else {
            // Uppdatera med nytt lösenord (endast SHA1)
            $sha1Hash = customPasswordHash($newPassword);
            $statement = $pdo->prepare("UPDATE användare SET sha1_hash = ? WHERE användare_id = ?");
            $statement->execute([$sha1Hash, $_SESSION['user_id']]);
            $success = 'Lösenordet har ändrats!';
        }
    }
}

// Radera konto (kräver inloggning)
if ($action === 'delete_account') {
    if (!$isLoggedIn) {
        header("Location: index.php?noaccess=1");
        exit;
    }
    // Ta bort användaren från databasen
    $statement = $pdo->prepare("DELETE FROM användare WHERE användare_id = ?");
    $statement->execute([$_SESSION['user_id']]);

    // Radera cookies och session
    setcookie('user_id', '', time() - 3600, '/');
    setcookie('username', '', time() - 3600, '/');
    setcookie('role', '', time() - 3600, '/');
    session_destroy();
    header("Location: index.php?success=deleted");
    exit;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konto & Hantering</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<?php if (!$isLoggedIn): ?>
    <div class="auth-container">
        <div class="auth-left">
            <div class="auth-branding">
                <h1>Biblioteket</h1>
                <p class="tagline">Din portal till kunskapen</p>
            </div>
        </div>

        <div class="auth-right">
            <?php if ($error) echo '<div class="notice error">' . $error . '</div>'; ?>
            <?php if ($success) echo '<div class="notice success">' . $success . '</div>'; ?>

            <div class="auth-tabs">
                <button class="tab-btn active" data-tab="login">Logga in</button>
                <button class="tab-btn" data-tab="register">Registrera</button>
            </div>

            <div class="auth-forms">
                <!-- Login Form -->
                <div class="tab-content active" id="login-tab">
                    <form action="account.php" method="post">
                        <input type="hidden" name="action" value="login">
                        <h2>Välkommen tillbaka</h2>
                        <div class="input-group">
                            <label for="login-username">Användarnamn</label>
                            <input type="text" id="login-username" name="username" placeholder="Skriv ditt användarnamn"
                                   required>
                        </div>
                        <div class="input-group">
                            <label for="login-password">Lösenord</label>
                            <input type="password" id="login-password" name="password" placeholder="Skriv ditt lösenord"
                                   required>
                        </div>
                        <div class="form-footer">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember">
                                <span>Kom ihåg mig</span>
                            </label>
                        </div>
                        <button type="submit" class="auth-btn">Logga in</button>
                    </form>
                </div>

                <!-- Register Form -->
                <div class="tab-content" id="register-tab">
                    <form action="account.php" method="post">
                        <input type="hidden" name="action" value="register">
                        <h2>Skapa ditt konto</h2>
                        <div class="input-group">
                            <label for="reg-username">Användarnamn</label>
                            <input type="text" id="reg-username" name="username" placeholder="Välj ett användarnamn"
                                   required>
                        </div>
                        <div class="input-group">
                            <label for="reg-email">E-post</label>
                            <input type="email" id="reg-email" name="email" placeholder="din@email.se" required>
                        </div>
                        <div class="input-group">
                            <label for="reg-password">Lösenord</label>
                            <input type="password" id="reg-password" name="password"
                                   placeholder="Välj ett starkt lösenord" required>
                        </div>
                        <button type="submit" class="auth-btn">Skapa konto</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;

                // Update buttons
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Update content
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                document.getElementById(tab + '-tab').classList.add('active');
            });
        });
    </script>
<?php else: ?>
    <div class="center-wrap" style="background: white;">
        <?php if ($error) echo '<div class="notice error">' . $error . '</div>'; ?>
        <?php if ($success) echo '<div class="notice success">' . $success . '</div>'; ?>
        <!-- Change Password Form -->
        <form action="account.php" method="post">
            <input type="hidden" name="action" value="change_password">
            <h2>Byt lösenord</h2>
            <input type="password" name="current_password" placeholder="Nuvarande lösenord" required>
            <input type="password" name="new_password" placeholder="Nytt lösenord" required>
            <input type="password" name="confirm_password" placeholder="Bekräfta nytt lösenord" required>
            <button type="submit">Byt lösenord</button>
        </form>
        <!-- Delete Account Form -->
        <form action="account.php" method="post"
              onsubmit="return confirm('Är du säker på att du vill radera ditt konto? Detta kan inte ångras.');">
            <input type="hidden" name="action" value="delete_account">
            <button type="submit" style="margin-top:1em;">Radera konto</button>
        </form>
        <form action="account.php" method="post" style="margin-top:1em;">
            <input type="hidden" name="action" value="logout">
            <button type="submit">Logga ut</button>
        </form>
    </div>
<?php endif; ?>
</body>
</html>
