<?php
session_start();
require_once 'db.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];


function fetchQuery($conn, $query, $types, ...$params) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $upload_dir = 'uploads/';
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

   
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);

  
    if (in_array(strtolower($file_extension), $allowed_extensions)) {
        $new_filename = $user_id . '-' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $new_filename;

       
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Update the profile picture in the database
            $query = "UPDATE users SET profile_picture = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('si', $new_filename, $user_id);
            $stmt->execute();
            $stmt->close();

        
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Failed to upload the image.";
        }
    } else {
        $error_message = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
    }
}
$user = fetchQuery($conn, "SELECT username, email, profile_picture FROM users WHERE id = ?", "i", $user_id)->fetch_assoc();


$total_spent = fetchQuery($conn, "SELECT SUM(amount) as total_spent FROM expenses WHERE user_id = ?", "i", $user_id)->fetch_assoc()['total_spent'] ?? 0;


$remaining_budget = fetchQuery($conn, "
    SELECT COALESCE(SUM(budget_limit), 0) - COALESCE(SUM(amount), 0) AS remaining_budget
    FROM budgets b
    LEFT JOIN expenses e 
        ON b.user_id = e.user_id AND b.category = e.category
    WHERE b.user_id = ?
", "i", $user_id)->fetch_assoc()['remaining_budget'] ?? 0;


$current_month_spent = fetchQuery($conn, "
    SELECT SUM(amount) as current_month_spent
    FROM expenses
    WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
", "i", $user_id)->fetch_assoc()['current_month_spent'] ?? 0;

$category_data = fetchQuery($conn, "SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? GROUP BY category", "i", $user_id);
$categories = [];
$spending = [];
while ($row = $category_data->fetch_assoc()) {
    $categories[] = $row['category'];
    $spending[] = $row['total'];
}

$monthly_data = fetchQuery($conn, "
    SELECT DATE_FORMAT(date, '%Y-%m') as month, SUM(amount) as total
    FROM expenses
    WHERE user_id = ?
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY date ASC
", "i", $user_id);
$months = [];
$monthly_spending = [];
while ($row = $monthly_data->fetch_assoc()) {
    $months[] = $row['month'];
    $monthly_spending[] = $row['total'];
}

$budget_status = '';
$alert_message = '';
if ($remaining_budget < 0) {
    $budget_status = 'exceeded';
    $alert_message = 'You have exceeded your budget. Please review your expenses!';
} elseif ($remaining_budget < 0.1 * $total_spent) {
    $budget_status = 'low';
    $alert_message = 'Your remaining budget is low. Consider adjusting your spending.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        #wrapper {
            display: flex;
            min-height: 100vh;
        }
        #sidebar {
            width: 250px;
            background-color: #34495e;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        #sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        #sidebar ul li {
            margin: 15px 0;
        }
        #sidebar ul li a {
            color: white;
            text-decoration: none;
            font-weight: normal;
        }
        #sidebar ul li.active a {
            font-weight: bold;
            color: #18bc9c;
        }
        #profile-section {
            text-align: center;
            margin-bottom: 20px;
        }
        #profile-section img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            cursor: pointer; 
        }
        #content {
            flex-grow: 1;
            padding: 20px;
            background-color: white;
        }
        #footer {
            background-color: #34495e;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        canvas {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div id="wrapper">
    
        <div id="sidebar">
            <div id="profile-section">
                
                <img src="uploads/<?= !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'default-profile.png'; ?>" alt="Profile Picture" onclick="document.getElementById('profilePictureInput').click();">
                <h4><?= htmlspecialchars($user['username']); ?></h4>
                <p><?= htmlspecialchars($user['email']); ?></p>

            
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" style="display: none;" onchange="this.form.submit();">
                </form>

            
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger mt-2"><?= htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
            </div>
            <ul>
                <li class="active"><a href="dashboard.php">Dashboard</a></li>
                <li><a href="expenses.php">Expenses</a></li>
                <li><a href="budgets.php">Budgets</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="profile.php">Profile</a></li>
            </ul>
            <form action="logout.php" method="POST">
                <button class="btn btn-danger w-100">Logout</button>
            </form>
        </div>

        <div id="content">
            <h1>WELCOME BACK TAKE CONTROL OF IT JUST AT HERE AN NOW</h1>

    
            <?php if (!empty($alert_message)): ?>
                <div class="alert <?= $budget_status == 'exceeded' ? 'alert-danger' : 'alert-warning'; ?>" role="alert">
                    <strong>Notice:</strong> <?= htmlspecialchars($alert_message); ?>
                </div>
            <?php endif; ?>

        
            <div class="row">
                <div class="col-md-4">
                    <div class="card text-bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Spending</h5>
                            <p class="card-text">$<?= number_format($total_spent, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Remaining Budget</h5>
                            <p class="card-text">$<?= number_format($remaining_budget, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">This Month's Spending</h5>
                            <p class="card-text">$<?= number_format($current_month_spent, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        
            <div class="row">
                <div class="col-md-6">
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="col-md-6">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>


    <div id="footer">
        <p>&copy; <?= date('Y'); ?> Personal Finance Management App.</p>
    </div>

    <script>
        var categoryChart = new Chart(document.getElementById('categoryChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($categories); ?>,
                datasets: [{
                    data: <?= json_encode($spending); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#FF9F40']
                }]
            }
        });

        var monthlyChart = new Chart(document.getElementById('monthlyChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($months); ?>,
                datasets: [{
                    label: 'Monthly Spending',
                    data: <?= json_encode($monthly_spending); ?>,
                    backgroundColor: '#36A2EB'
                }]
            }
        });
    </script>
</body>
</html>
