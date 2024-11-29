<?php
session_start();
require_once 'db.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];


$timeout_duration = 1800; 
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time(); 


$edit_mode = false;
$edit_category = '';
$edit_budget_limit = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
if ($_POST['action'] === 'save') {
        $category = $_POST['category'];
        $budget_limit = $_POST['budget_limit'];

       
        $query = $conn->prepare("SELECT * FROM expenses WHERE user_id =? AND category =?");
        $query->bind_param("is", $user_id, $category);
$query->execute();
        $result = $query->get_result();

        if ($result->num_rows == 0) {
            $error_message = "This category does not exist in your expenses. Please choose a valid category.";
} elseif (!is_numeric($budget_limit) || $budget_limit <= 0) {
            $error_message = "Budget limit must be a positive number.";
        } else {
$query = $conn->prepare("SELECT * FROM budgets WHERE user_id =? AND category =?");
            $query->bind_param("is", $user_id, $category);
            $query->execute();
            $result = $query->get_result();

if ($result->num_rows > 0) {
                $error_message = "This category already has a budget set.";
            } else {
$query = $conn->prepare("INSERT INTO budgets (user_id, category, budget_limit) VALUES (?,?,?)");
                $query->bind_param("isd", $user_id, $category, $budget_limit);
$query->execute();
                $query->close();
                header("Location: budget.php");
                exit();
}
        }
    }

    if ($_POST['action'] === 'delete') {
        $category = $_POST['category'];
        $query = $conn->prepare("DELETE FROM budgets WHERE user_id = ? AND category = ?");
$query->bind_param("is", $user_id, $category);
        $query->execute();
        $query->close();
        header("Location: budgets.php");
        exit();
    }

   
    if ($_POST['action'] === 'edit') {
        $edit_mode = true;
$edit_category = $_POST['category'];

        $query = $conn->prepare("SELECT budget_limit FROM budgets WHERE user_id =? AND category =?");
        $query->bind_param("is", $user_id, $edit_category);
        $query->execute();
        $result = $query->get_result();

if ($row = $result->fetch_assoc()) {
            $edit_budget_limit = $row['budget_limit'];
        }
        $query->close();
    }
}

$query = $conn->prepare("SELECT DISTINCT category FROM expenses WHERE user_id =?");
$query->bind_param("i", $user_id);
$query->execute();
$categories_result = $query->get_result();
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
$query->close();





$query = $conn->prepare("
    SELECT
b.category,
        b.budget_limit,
        COALESCE(SUM(e.amount), 0) AS total_spent
    FROM budgets b
    LEFT JOIN expenses e
        ON b.user_id = e.user_id AND b.category = e.category
    WHERE b.user_id =?
GROUP BY b.category, b.budget_limit
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$budgets = $result->fetch_all(MYSQLI_ASSOC);
$query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center">
<h1>Budget Management</h1>
            <a href="dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger mt-3">
<?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="row mt-4">
            <!-- Budget Form -->
<div class="col-md-6">
                <form method="POST">
                    <h3><?= $edit_mode? 'Edit Budget' : 'Add / Update Budget';?></h3>
<div class="mb-3">
                        <label for="category" class="form-label">Category
<select name="category" id="category" class="form-control" required <?= $edit_mode? 'disabled' : '';?>>
                            <option value="" disabled selected>Select a category</option>
<?php foreach ($categories as $cat):?>
<option value="<?= htmlspecialchars($cat['category']); ?>" <?= $edit_category == $cat['category'] ? 'selected' : ''; ?>>
<?= htmlspecialchars($cat['category']); ?>
</option>
                            <?php endforeach;?>
</select>
                </div>
                <div class="mb-3 ">
<label for="budget_limit" class="form-label">Budget Limit ($)</label>
                        <input type="number" name="budget_limit" id="budget_limit" class="form-control"
value="<?= htmlspecialchars($edit_budget_limit); ?>" required>
                    </div>
<input type="hidden" name="action" value="save">
                    <button type="submit" class="btn btn-primary"><?= $edit_mode? 'Update Budget' : 'Save Budget';?></button>
<?php if ($edit_mode):?>
                        <a href="budgets.php" class="btn btn-secondary">Cancel
<?php endif;?>
                </form>
            </div>

            <!-- Budget Overview -->
<div class="col-md-6">
                <h3>Budget Overview</h3>
                <table class="table table-bordered">
                    <thead>
<tr>
                        <th>Category</th>
<th>Limit ($)</th>
                        <th>Spent ($)</th>
<th>Remaining ($)</th>
                            <th>Status
<th>Actions</th>
                        </tr>
</thead>
                    <tbody>
<?php foreach ($budgets as $budget): 
                            $remaining = $budget['budget_limit'] - $budget['total_spent'];
$status = $remaining >= 0? 'Within Budget' : 'Exceeded';
$status_class = $remaining >= 0? 'text-success' : 'text-danger';
                       ?>
<tr>
                                <td><?= htmlspecialchars($budget['category']);?></td>
<?= number_format($budget['budget_limit'], 2); ?>
<td>$<?= number_format($budget['total_spent'], 2); ?></td>
<td>$<?= number_format($remaining, 2); ?></td>
<td class="<?= $status_class; ?>"><?= $status; ?></td>
<td>
<form method="POST" style="display: inline-block;">
<input type="hidden" name="category" value="<?= htmlspecialchars($budget['category']);?>">
<input type="hidden" name="action" value="edit">
<button type="submit" class="btn btn-sm btn-warning">Edit</button>
</form>
<form method="POST" style="display: inline-block;">
<input type="hidden" name="category" value="<?= htmlspecialchars($budget['category']);?>">
<input type="hidden" name="action" value="delete">
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
</form>
                                </td>
</tr>
                        <?php endforeach; ?>
</tbody>
                </table>
            </div>
        </div>

        <div class="mt-5">
<h3>Budget vs. Expenditure</h3>
            <canvas id="budgetChart" width="400" height="200"></canvas>
        </div>
    </div>

    <script>
        const data =
labels: <?= json_encode(array_column($budgets, 'category'));?>,
            datasets: [
                {
                    label: 'Budget Limit',
data: <?= json_encode(array_column($budgets, 'budget_limit'));?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
{
                    label: 'Total Spent',
                    data: <?= json_encode(array_column($budgets, 'total_spent'));?>,
backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
borderWidth: 1
                }
            ]
        };

        const config =
type: 'bar',
            data: data,
            options: {
                responsive: true,
plugins: {
                    legend: {
                        position: 'top',
},
                    title: {
                        display: true,
text: 'Budget vs. Expenditure'
                    }
                }
}
        };

        const budgetChart = new Chart(
            document.getElementById('budgetChart'),
            config
        );
    </script>
</body>
</html>
