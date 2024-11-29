<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle Add/Update Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $username = $_POST['username'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $date_of_birth = $_POST['date_of_birth'];
    $profile_picture = null;


    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = uniqid() . '-' . basename($_FILES['profile_picture']['name']);
        $destinationPath = $uploadDir . $fileName;

    
        if (move_uploaded_file($fileTmpPath, $destinationPath)) {
            $profile_picture = $destinationPath;
        } else {
            $message = "Error uploading file.";
        }
    }

    $stmt = $conn->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        
        $stmt = $conn->prepare("
            UPDATE user_profiles 
            SET username = ?, first_name = ?, last_name = ?, date_of_birth = ?, profile_picture = ? 
            WHERE user_id = ?
        ");
        $stmt->bind_param("sssssi", $username, $first_name, $last_name, $date_of_birth, $profile_picture, $user_id);
    } else {
       
        $stmt = $conn->prepare("
            INSERT INTO user_profiles (user_id, username, first_name, last_name, date_of_birth, profile_picture) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssss", $user_id, $username, $first_name, $last_name, $date_of_birth, $profile_picture);
    }

    if ($stmt->execute()) {
        $message = "Profile saved successfully!";
    } else {
        $message = "Error saving profile: " . $stmt->error;
    }
    $stmt->close();
}


$stmt = $conn->prepare("
    SELECT username, first_name, last_name, date_of_birth, profile_picture 
    FROM user_profiles 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($username, $first_name, $last_name, $date_of_birth, $profile_picture);
$stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #eaf3ff;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #007bff;
        }
        p.message {
            text-align: center;
            color: #007bff;
            font-weight: bold;
        }
        p.error {
            text-align: center;
            color: #d9534f;
            font-weight: bold;
        }
        form {
            margin-top: 20px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        input[type="text"], input[type="date"], input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            display: block;
            width: 100%;
            background-color: #007bff;
            color: white;
            padding: 10px;
            font-size: 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .profile-picture {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-picture img {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 4px solid #007bff;
        }
        .display-info {
            margin-top: 20px;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .action-buttons a {
            text-decoration: none;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
        }
        .action-buttons a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Profile</h1>
        
        <?php if (!empty($message)) { ?>
            <p class="<?= strpos($message, 'Error') !== false ? 'error' : 'message'; ?>">
                <?= htmlspecialchars($message); ?>
            </p>
        <?php } ?>

        <?php if (!isset($_GET['edit'])) { ?>
            <div class="display-info">
                <div class="profile-picture">
                    <img src="<?= htmlspecialchars($profile_picture ?: 'https://via.placeholder.com/150'); ?>" alt="Profile Picture">
                </div>
                <p><strong>Username:</strong> <?= htmlspecialchars($username); ?></p>
                <p><strong>First Name:</strong> <?= htmlspecialchars($first_name); ?></p>
                <p><strong>Last Name:</strong> <?= htmlspecialchars($last_name); ?></p>
                <p><strong>Date of Birth:</strong> <?= htmlspecialchars($date_of_birth); ?></p>
                <div class="action-buttons">
                    <a href="?edit=1">Edit Profile</a>
                    <a href="dashboard.php">Go to Dashboard</a>
                </div>
            </div>
        <?php } else { ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username); ?>" required>

                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name); ?>" required>

                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name); ?>" required>

                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($date_of_birth); ?>" required>

                <label for="profile_picture">Profile Picture</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">

                <button type="submit" name="save_profile">Save Profile</button>
            </form>
        <?php } ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
