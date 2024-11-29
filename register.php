<?php
session_start();
require_once 'db.php';

$error = ''; 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);


    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "Password must be at least 8 characters long and include at least one uppercase letter and one number.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
    
        $check_email_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email_query->bind_param("s", $email);
        $check_email_query->execute();
        $check_email_result = $check_email_query->get_result();


        $check_username_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_username_query->bind_param("s", $username);
        $check_username_query->execute();
        $check_username_result = $check_username_query->get_result();

        if ($check_email_result->num_rows > 0) {
            $error = "Email is already registered. Please use a different email address.";
        } elseif ($check_username_result->num_rows > 0) {
            $error = "Username is already taken. Please choose another.";
        } else {
        
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $query = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $query->bind_param("sss", $username, $email, $hashed_password);

            if ($query->execute()) {
                $_SESSION['user_id'] = $conn->insert_id; 
                header("Location: dashboard.php"); 
                exit();
            } else {
                $error = "Error registering the user. Please try again later.";
            }
            $query->close();
        }

        $check_email_query->close();
        $check_username_query->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .register-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #888;
        }

        .toggle-password:hover {
            color: #000;
        }

        .strength-meter {
            margin-top: 10px;
            height: 5px;
            width: 100%;
            background-color: #e0e0e0;
        }

        .strength-meter div {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }

        .strength-meter .weak {
            background-color: #e74c3c;
        }

        .strength-meter .medium {
            background-color: #f39c12;
        }

        .strength-meter .strong {
            background-color: #2ecc71;
        }

    
        @media (max-width: 576px) {
            .register-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="register-container">
    <h2 class="text-center mb-4">Register and smile with us </h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>

        <div class="mb-3 password-container">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required oninput="checkPasswordStrength()">
            <span class="toggle-password" onclick="togglePassword('password')">&#128065;</span>

            <div class="strength-meter" id="strength-meter">
                <div id="strength-bar"></div>
            </div>
        </div>

        <div class="mb-3 password-container">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
            <span class="toggle-password" onclick="togglePassword('confirm_password')">&#128065;</span>
        </div>

        <button type="submit" class="btn btn-success w-100">Register</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const type = field.type === 'password' ? 'text' : 'password';
        field.type = type;
    }

    function checkPasswordStrength() {
        const password = document.getElementById('password').value;
        const strengthBar = document.getElementById('strength-bar');
        let strength = 0;

        if (password.length >= 8) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;

        const strengthLevels = ['weak', 'medium', 'strong'];
        const strengthColors = ['#e74c3c', '#f39c12', '#2ecc71'];
        strengthBar.style.width = `${strength * 25}%`;
        strengthBar.className = strengthLevels[strength - 1] || 'weak'
        strengthBar.style.backgroundColor = strengthColors[strength - 1] || '#e0e0e0';
    }


    window.onload = function() {
        document.getElementById('email').focus();
    };
</script>

</body>
</html>
