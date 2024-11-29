<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

$session_timeout = 3600; 

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php"); 
    exit();
}


$_SESSION['last_activity'] = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']) ? true : false;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
    
        $query = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true); 
                $_SESSION['user_id'] = $user['id'];


                if ($remember_me) {
                    setcookie('user_id', $user['id'], time() + (86400 * 30), "/", "", true, true); // expires in 30 days
                }

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No account found with this email.";
        }
        $query->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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

        .login-container {
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

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #888;
        }

        .toggle-password:hover {
            color: #000;
        }

        .password-container input {
            padding-right: 40px; 
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

        
        @media (max-width: 576px) {
            .login-container {
                padding: 20px;
            }
        footer {
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%); 
            text-align: center; 
            width: 100%;
        
                }
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2 class="text-center mb-4"> welcome back Login to take control </h2>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required aria-label="Email">
        </div>

        <div class="mb-3 password-container">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required aria-label="Password" oninput="checkPasswordStrength()">
            <span class="toggle-password" onclick="togglePassword('password')">&#128065;</span>

            <div class="strength-meter" id="strength-meter">
                <div id="strength-bar"></div>
            </div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="remember_me" class="form-check-input" id="rememberMe">
            <label class="form-check-label" for="rememberMe">Remember me</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>

    <div class="forgot-password">
        <p class="mb-0">
            <a href="forgot_password.php" class="text-decoration-none">Forgot your password?</a>
        </p>
    </div>
    <footer>
        <p>&copy; 2024 Finance Manager.</p>
    </footer>
</div>

<!-- Bootstrap JS -->
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

        
        const strengthLevels = ['#e74c3c', '#f39c12', '#2ecc71', '#27ae60'];
        strengthBar.style.width = `${strength * 25}%`;
        strengthBar.style.backgroundColor = strengthLevels[strength - 1] || '#e0e0e0';
    }

    
    window.onload = function() {
        document.getElementById('email').focus();
    };
</script>

</body>
</html>
