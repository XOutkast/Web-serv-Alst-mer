<?php

session_start();

// redirect om redan inloggad
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliotek Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<div class="auth-container">
    <div class="auth-left">
        <div class="auth-branding">
            <h1>Biblioteket</h1>
            <p class="tagline">Din portal till kunskapen</p>
        </div>
    </div>
    
    <div class="auth-right">
        <?php
        // success messages
        if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
            echo '<div class="notice error">Ditt konto har raderats.</div>';
        }
        if (isset($_GET['success']) && $_GET['success'] === 'registered') {
            echo '<div class="notice success">Konto skapat! Du kan logga in nu.</div>';
        }
        // error msgs
        if (isset($_GET['error'])): ?>
            <div class="notice error">
                <?php
                if ($_GET['error'] === 'locked') {
                    echo "Kontot är låst på grund av för många misslyckade inloggningar. Kontakta admin.";
                } elseif ($_GET['error'] === 'invalid') {
                    echo "Felaktigt användarnamn eller lösenord.";
                } elseif ($_GET['error'] === 'exists') {
                    echo "Användarnamnet eller e-postadressen finns redan.";
                } elseif ($_GET['error'] === 'email') {
                    echo "Ogiltig e-postadress.";
                } elseif ($_GET['error'] === 'empty') {
                    echo "Fyll i alla fält.";
                } else {
                    echo "Ett fel uppstod. Försök igen.";
                }
                ?>
            </div>
        <?php endif;
        // visa "ej behörighet" msg om URL har no-acces
        if (isset($_GET['noaccess'])) {
            echo '<div class="notice warning">Du har inte behörighet att komma åt den sidan.</div>';
        }
        ?>
        
        <div class="auth-tabs">
            <button class="tab-btn active" data-tab="login">Logga in</button>
            <button class="tab-btn" data-tab="register">Registrera</button>
        </div>
        
        <div class="auth-forms">
            <!-- Login form -->
            <div class="tab-content active" id="login-tab">
                <form action="account.php" method="post">
                    <input type="hidden" name="action" value="login">
                    <h2>Välkommen tillbaka</h2>
                    <div class="input-group">
                        <label for="login-username">Användarnamn</label>
                        <input type="text" id="login-username" name="username" placeholder="Skriv ditt användarnamn" required>
                    </div>
                    <div class="input-group">
                        <label for="login-password">Lösenord</label>
                        <input type="password" id="login-password" name="password" placeholder="Skriv ditt lösenord" required>
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
            
            <!-- Registreringsformulär -->
            <div class="tab-content" id="register-tab">
                <form action="account.php" method="post">
                    <input type="hidden" name="action" value="register">
                    <h2>Skapa ditt konto</h2>
                    <div class="input-group">
                        <label for="reg-username">Användarnamn</label>
                        <input type="text" id="reg-username" name="username" placeholder="Välj ett användarnamn" required>
                    </div>
                    <div class="input-group">
                        <label for="reg-email">E-post</label>
                        <input type="email" id="reg-email" name="email" placeholder="din@email.se" required>
                    </div>
                    <div class="input-group">
                        <label for="reg-password">Lösenord</label>
                        <input type="password" id="reg-password" name="password" placeholder="Välj ett starkt lösenord" required>
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
</body>
</html>
