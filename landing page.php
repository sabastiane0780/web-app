<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : null;

$hour = date("H");
if ($hour >= 5 && $hour < 12) {
    $greeting = "Good Morning!";
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = "Good Afternoon!";
} else {
    $greeting = "Good Evening!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Welcome to Your Finance Manager - Track expenses and set budgets effortlessly.">
    <meta name="keywords" content="finance, budget tracker, student financial management, personal finance app">
    <meta name="robots" content="index, follow">
    <title>Welcome to personal Finance Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            text-align: center;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
        }

        h1 {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

        .message {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .cta-buttons {
            margin-top: 20px;
        }

        .cta-buttons a {
            display: inline-block;
            text-decoration: none;
            padding: 12px 25px;
            margin: 10px 5px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            color: #fff;
            transition: background-color 0.3s ease;
        }

        .login-btn {
            background-color: #007bff;
        }

        .signup-btn {
            background-color: #28a745;
        }

        .login-btn:hover {
            background-color: #0056b3;
        }

        .signup-btn:hover {
            background-color: #218838;
        }

        footer {
            margin-top: 30px;
            font-size: 14px;
            color: #333;
        }

        @media (max-width: 600px) {
            .cta-buttons a {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1><?php echo $greeting; ?> WELCOME TO PERSONAL FINANCE APP</h1>
    <p>TAKE CONTROL OF YOUR FINANCES RIGHT AT YOUR HAND</p>
    
    <?php if ($message): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>

    <div class="cta-buttons">
        <a href="login.php" class="login-btn" aria-label="Login to your account">Login</a>
        <a href="register.php" class="signup-btn" aria-label="Sign up for a new account">Register</a>
    </div>

    <footer>
        <p>&copy; 2024 Finance Manager.</p>
    </footer>
</div>

</body>
</html>
