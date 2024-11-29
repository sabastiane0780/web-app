<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'];


    $token = bin2hex(random_bytes(16)); 


    $expiry = date("Y-m-d H:i:s", strtotime("+1 hour")); 

    $conn = new mysqli('localhost', 'root', '', 'SABZ'); // Replace 'SABZ' with your database name
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


    $sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $token, $expiry, $email);
    $stmt->execute();


    $mail = new PHPMailer(true);
    try {
    
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;  
        $mail->Username = 'your-email@gmail.com';  
        $mail->Password = 'your-app-password';  
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  
        $mail->Port = 587;  

    
        $mail->setFrom('your-email@gmail.com', 'Your Name'); 
        $mail->addAddress($email);  

        
        $mail->isHTML(true);  
        $mail->Subject = 'Password Reset Request';
        $mail->Body    = 'Please click the link to reset your password: <a href="http://yourwebsite.com/reset_password.php?token=' . $token . '">Reset Password</a>';

    
        $mail->send();
        echo 'Reset link has been sent to your email address.';
    } catch (Exception $e) {
        echo "Failed to send reset email. Please try again later.";
        echo "Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body>
    <h1>Password Reset</h1>

    <form method="POST" action="">
        <label for="email">Enter your email address:</label>
        <input type="email" name="email" id="email" required placeholder="Enter your email" />
        <button type="submit">Send Reset Link</button>
    </form>

</body>
</html>
