<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];


$categories = ['Tuition', 'Groceries', 'Entertainment', 'Feeding', 'Transport', 'Hostel', 'Others'];

$query = $conn->prepare("
    SELECT b.category, b.budget_limit, COALESCE(SUM(e.amount), 0) AS spent
    FROM budgets b
    LEFT JOIN expenses e ON b.user_id = e.user_id AND b.category = e.category
    WHERE b.user_id = ?
    GROUP BY b.category, b.budget_limit
");
$query->bind_param("i", $user_id);
$query->execute();
$budgets_result = $query->get_result();

$budgets = [];
$notifications = [];
while ($row = $budgets_result->fetch_assoc()) {
    $row['remaining'] = max($row['budget_limit'] - $row['spent'], 0);
    $budgets[] = $row;


    if ($row['spent'] >= $row['budget_limit']) {
        $notifications[] = "You have exceeded your budget for {$row['category']}.";
    } elseif ($row['spent'] >= 0.9 * $row['budget_limit']) {
        $notifications[] = "You are nearing your budget limit for {$row['category']}.";
    }
}
$query->close();

$edit_mode = false;
$edit_id = null;
$edit_category = '';
$edit_amount = '';
$edit_date = '';
$edit_time_period = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $expense_id = $_POST['expense_id'] ?? null;

    
        if ($action === 'edit') {
            $edit_mode = true;
            $query = $conn->prepare("SELECT id, category, amount, date, time_period FROM expenses WHERE id = ? AND user_id = ?");
            $query->bind_param("ii", $expense_id, $user_id);
            $query->execute();
            $result = $query->get_result();

            if ($row = $result->fetch_assoc()) {
                $edit_id = $row['id'];
                $edit_category = $row['category'];
                $edit_amount = $row['amount'];
                $edit_date = $row['date'];
                $edit_time_period = $row['time_period'];
            }
            $query->close();
        }

       
        if ($action === 'delete') {
            $query = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
            $query->bind_param("ii", $expense_id, $user_id);
            $query->execute();
            $query->close();
            header("Location: expenses.php");
            exit();
        }
    } else {
       
        $category = $_POST['category'];
        $amount = $_POST['amount'];
        $date = $_POST['date'];
        $time_period = $_POST['time_period']; 

        if ($edit_mode && $edit_id) {
            $query = $conn->prepare("UPDATE expenses SET category = ?, amount = ?, date = ?, time_period = ? WHERE id = ? AND user_id = ?");
            $query->bind_param("ssdiii", $category, $amount, $date, $time_period, $edit_id, $user_id);
        } else {
            $query = $conn->prepare("INSERT INTO expenses (user_id, category, amount, date, time_period) VALUES (?, ?, ?, ?, ?)");
            $query->bind_param("isdsi", $user_id, $category, $amount, $date, $time_period);
        }
        $query->execute();
        $query->close();

        header("Location: expenses.php");
        exit();
    }
}


$search_category = $_GET['search_category'] ?? '';
$search_start_date = $_GET['search_start_date'] ?? '';
$search_end_date = $_GET['search_end_date'] ?? '';
$search_amount = $_GET['search_amount'] ?? '';
$time_filter = $_GET['time_filter'] ?? '';


$query_str = "SELECT id, category, amount, date, time_period FROM expenses WHERE user_id = ?";


$filters = [];
$params = ["i", $user_id];  

if ($search_category) {
    $query_str .= " AND category = ?";
    $filters[] = $search_category;
}

// Filter by amount
if ($search_amount) {
    $query_str .= " AND amount >= ?";
    $filters[] = $search_amount;
}


if ($time_filter) {
    if ($time_filter === 'day') {
        $query_str .= " AND DATE(date) = CURDATE()";
    } elseif ($time_filter === 'week') {
        $query_str .= " AND YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($time_filter === 'month') {
        $query_str .= " AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
    } elseif ($time_filter === 'year') {
        $query_str .= " AND YEAR(date) = YEAR(CURDATE())";
    }
}


if ($search_start_date) {
    $query_str .= " AND date >= ?";
    $filters[] = $search_start_date;
}

if ($search_end_date) {
    $query_str .= " AND date <= ?";
    $filters[] = $search_end_date;
}


$types = 'i';  
foreach ($filters as $filter) {
    $types .= is_numeric($filter) ? 'd' : 's';  
}


$query = $conn->prepare($query_str);
$query->bind_param($types, $user_id, ...$filters);
$query->execute();
$expenses_result = $query->get_result();
$expenses = $expenses_result->fetch_all(MYSQLI_ASSOC);
$query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .notifications {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            border-radius: 5px;
        }

        .side-by-side {
            display: flex;
            justify-content: space-between;
        }

        .side-by-side .col-md-6 {
            width: 48%;
        }

        .notification-and-filter {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .budget-table {
            margin-top: 20px;
        }

        .budget-table th, .budget-table td {
            text-align: center;
        }

        .budget-table .exceeded {
            color: red;
        }

        .budget-table .nearing {
            color: orange;
        }

        .budget-table .normal {
            color: green;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1>Expense Management</h1>

    
    <div class="mt-3">
        <a href="dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
    </div>

   
    <div class="notification-and-filter">
       
        <?php if (!empty($notifications)): ?>
            <div class="notifications" style="width: 70%;">
                <ul>
                    <?php foreach ($notifications as $notification): ?>
                        <li><?= htmlspecialchars($notification); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">Filter Expenses</button>
    </div>


    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Expenses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="GET">
                        <div class="mb-3">
                            <label for="search_category" class="form-label">Category</label>
                            <input type="text" class="form-control" name="search_category" value="<?= htmlspecialchars($search_category) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="search_amount" class="form-label">Amount</label>
                            <input type="number" class="form-control" name="search_amount" value="<?= htmlspecialchars($search_amount) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="search_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="search_start_date" value="<?= htmlspecialchars($search_start_date) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="search_end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" name="search_end_date" value="<?= htmlspecialchars($search_end_date) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="time_filter" class="form-label">Time Filter</label>
                            <select class="form-control" name="time_filter">
                                <option value="">Select Time Period</option>
                                <option value="day" <?= ($time_filter == 'day' ? 'selected' : '') ?>>Today</option>
                                <option value="week" <?= ($time_filter == 'week' ? 'selected' : '') ?>>This Week</option>
                                <option value="month" <?= ($time_filter == 'month' ? 'selected' : '') ?>>This Month</option>
                                <option value="year" <?= ($time_filter == 'year' ? 'selected' : '') ?>>This Year</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
    <div class="side-by-side">
        
        <div class="col-md-6">
            <h3>Budget Overview</h3>
            <table class="table table-bordered budget-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Budget Limit</th>
                        <th>Spent</th>
                        <th>Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($budgets as $budget): ?>
                        <tr class="<?= $budget['spent'] >= $budget['budget_limit'] ? 'exceeded' : ($budget['spent'] >= 0.9 * $budget['budget_limit'] ? 'nearing' : 'normal') ?>">
                            <td><?= htmlspecialchars($budget['category']) ?></td>
                            <td><?= number_format($budget['budget_limit'], 2) ?></td>
                            <td><?= number_format($budget['spent'], 2) ?></td>
                            <td><?= number_format($budget['remaining'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Expense Form -->
        <div class="col-md-6">
            <h3><?= $edit_mode ? 'Edit Expense' : 'Add Expense' ?></h3>
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-control" name="category" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category ?>" <?= $category == $edit_category ? 'selected' : '' ?>><?= $category ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" class="form-control" name="amount" value="<?= htmlspecialchars($edit_amount) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($edit_date) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="time_period" class="form-label">Time Period</label>
                    <input type="text" class="form-control" name="time_period" value="<?= htmlspecialchars($edit_time_period) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Update Expense' : 'Add Expense' ?></button>
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="expense_id" value="<?= $edit_id ?>">
                    <input type="hidden" name="action" value="edit">
                <?php endif; ?>
            </form>
        </div>
    </div>

    
    <h3 class="mt-4">Expense Records</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Category</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Time Period</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?= htmlspecialchars($expense['category']) ?></td>
                    <td><?= number_format($expense['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($expense['date']) ?></td>
                    <td><?= htmlspecialchars($expense['time_period']) ?></td>
                    <td>
                        <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="expense_id" value="<?= $expense['id'] ?>">
                            <input type="hidden" name="action" value="edit">
                            <button type="submit" class="btn btn-warning btn-sm">Edit</button>
                        </form>
                        <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="expense_id" value="<?= $expense['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
