<?php
/**
 * Flöde: GET visar formulär. POST validerar nuvarande lösenord och uppdaterar till nytt (hash).
 * Säkerhet: Session krävs. CSRF-token genereras och valideras. Lösenord hashas med password_hash.
 */
session_start();

$filename = "users.json";
$users = file_exists($filename) ? (json_decode(file_get_contents($filename), true) ?? []) : [];
$username = $_SESSION['username'] ?? '';

// CSRF: generera token om saknas
if (!isset($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF-validering
  $csrf = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf'], $csrf)) {
    header('Location: change_password.php?error=1');
    exit;
  }

  $current = $_POST['current_password'] ?? '';
  $new = $_POST['new_password'] ?? '';
  $updated = false;

  // Minimal lösenordspolicy: minst 6 tecken (gäller bara när man vill andra sitt lösenord och inet första gången när man skapar den.
  if (strlen($new) < 6) {
    header('Location: change_password.php?error=1');
    exit;
  }

  foreach ($users as &$user) {
    if (($user['username'] ?? '') === $username) {
      if (password_verify($current, $user['password'] ?? '') && $new !== '') {
        $user['password'] = password_hash($new, PASSWORD_DEFAULT);
        $updated = true;
      }
      break; // hittade användaren, kan lämna loopen
    }
  }
  unset($user); // unset referens

  if ($updated) {
    file_put_contents($filename, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    header('Location: admin.php?pwchange=success');
    exit;
  }
  header('Location: change_password.php?error=1');
  exit;
}
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Byt lösenord</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../../m02/Favicon-a.jpg" type="image/x-icon">
</head>
<body>
<div class="pwchange-wrap">
  <h2>Byt lösenord</h2>
  <?php
    // Fel: kan vara fel nuvarande lösenord, för kort nytt lösenord eller ogiltig CSRF-token
    if (isset($_GET['error'])) {
      echo '<div class="notice error">Fel: Ogiltigt nuvarande lösenord, för kort nytt lösenord eller sessionsfel.</div>';
    }
  ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
    <input type="password" name="current_password" placeholder="Nuvarande lösenord" required>
    <input type="password" name="new_password" placeholder="Nytt lösenord" required>
    <button type="submit">Uppdatera lösenord</button>
  </form>
  <a href="admin.php" class="back-link">Tillbaka</a>
</div>
</body>
</html>
