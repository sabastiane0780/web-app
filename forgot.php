<?php
require 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $reset_token = bin2hex(random_bytes(32));
        $stmt->close();

        // Store reset token in the database
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
        $stmt->bind_param("ss", $reset_token, $email);
        $stmt->execute();

        // Send email to the user
        $reset_link = "https://yourwebsite.com/reset_password.php?token=$reset_token";
        mail($email, "Password Reset Request", "Click this link to reset your password: $reset_link");

        echo "A password reset link has been sent to your email.";
    } else {
        echo "No account found with this email.";
    }
    $stmt->close();
}
?>
