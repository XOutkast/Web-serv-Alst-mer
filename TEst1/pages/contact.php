<h1>Contact Us</h1>
<form id="contactForm" method="post">
    <input type="text" name="name" placeholder="Your Name" required>
    <input type="email" name="email" placeholder="Your Email" required>
    <textarea name="message" placeholder="Your Message" required></textarea>
    <button type="submit">Send</button>
</form>
<div id="formResponse">
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $message = $conn->real_escape_string($_POST['message']);

        $sql = "INSERT INTO messages (name, email, message) VALUES ('$name', '$email', '$message')";
        if ($conn->query($sql)) {
            echo "Thank you, $name! Your message has been sent.";
        } else {
            echo "Sorry, there was an error. Please try again later.";
        }
    }
    ?>
</div>
