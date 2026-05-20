<?php
session_start();

$filename = "users.json";
$users = file_exists($filename) ? (json_decode(file_get_contents($filename), true) ?? []) : [];
$username = $_SESSION['username'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $current = $_POST['current_password'] ?? '';
  $new = $_POST['new_password'] ?? '';
  $updated = false;

  foreach ($users as &$user) {
    if (($user['username'] ?? '') === $username) {
      if (password_verify($current, $user['password']) && $new !== '') {
        $user['password'] = password_hash($new, PASSWORD_DEFAULT);
        $updated = true;
      }
      break;
    }
  }
  unset($user);

  if ($updated) {
    file_put_contents($filename, json_encode($users, JSON_PRETTY_PRINT));
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
    if (isset($_GET['error'])) {
      echo '<div class="notice error">Fel: Ogiltigt nuvarande lösenord.</div>';
    }
  ?>
  <form method="post" autocomplete="off">
    <input type="password" name="current_password" placeholder="Nuvarande lösenord" required>
    <input type="password" name="new_password" placeholder="Nytt lösenord" required>
    <button type="submit">Uppdatera lösenord</button>
  </form>
  <a href="admin.php" class="back-link">Tillbaka</a>
</div>
</body>
</html>
