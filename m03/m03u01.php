<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmäl dig</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../m02/Favicon-a.jpg" type="image/png">
</head>
<body>
<div class="container">
    <h1>Registrera dig</h1>
<!--    En form och action länk till Rsltat.php och method POST-->
    <form action="Rsltat.php" method="POST">
        <div class="form-group">
            <label for="firstname">Förnamn</label>
            <input type="text" id="firstname" name="firstname" required>
        </div>

        <div class="form-group">
            <label for="lastname">Efternamn</label>
            <input type="text" id="lastname" name="lastname" required>
        </div>

        <div class="form-group">
            <label for="birthdate">Födelsedatum</label>
            <input type="date" id="birthdate" name="birthdate" required>
        </div>

        <div class="form-group">
            <label for="email">E-post</label>
            <input type="email" id="email" name="email" placeholder="fri villigt">
        </div>

        <div class="form-group">
            <label for="password">Lösenord</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="radio-group">
            <label><input type="radio" name="gender" value="Man" required> Man</label>
            <label><input type="radio" name="gender" value="Kvinna"> Kvinna</label>
            <label><input type="radio" name="gender" value="Annat"> Annat</label>
        </div>

        <div class="checkbox-group">
            <label><input type="checkbox" name="terms"> Jag accepterar användarvillkoren</label>
        </div>

        <button type="submit">Skicka</button>
    </form>
</div>
</body>
</html>
