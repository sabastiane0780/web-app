<?php
session_start();
require_once 'db.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$userStmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

$expenseStmt = $conn->prepare("SELECT category, amount, date FROM expenses WHERE user_id = ? ORDER BY date DESC");
$expenseStmt->bind_param("i", $user_id);
$expenseStmt->execute();
$expenses = $expenseStmt->get_result();

$budgetStmt = $conn->prepare("SELECT budget_limit, start_date, end_date FROM budgets WHERE user_id = ? ORDER BY start_date DESC");
$budgetStmt->bind_param("i", $user_id);
$budgetStmt->execute();
$budgets = $budgetStmt->get_result();

if (isset($_GET['export'])) {
    $exportType = $_GET['export'];


    if ($exportType === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="finance_report.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Category', 'Amount', 'Date']);
        while ($expense = $expenses->fetch_assoc()) {
            fputcsv($output, [$expense['category'], $expense['amount'], $expense['date']]);
        }
        fclose($output);
        exit();
    }


    if ($exportType === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="finance_report.xls"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Category', 'Amount', 'Date']);
        while ($expense = $expenses->fetch_assoc()) {
            fputcsv($output, [$expense['category'], $expense['amount'], $expense['date']]);
        }
        fclose($output);
        exit();
    }


    if ($exportType === 'pdf') {
        $html = "<h1 style='text-align: center;'>Personal Finance Report</h1>";

        // User Details Section
        $html .= "<h2>User Details</h2>
                  <p><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</p>
                  <p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";


        $html .= "<h2>Expenses</h2>
                  <table border='1' cellspacing='0' cellpadding='5' style='width: 100%; border-collapse: collapse;'>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>";
        foreach ($expenses as $expense) {
            $html .= "<tr>
                        <td>" . htmlspecialchars($expense['category']) . "</td>
                        <td>" . number_format($expense['amount'], 2) . "</td>
                        <td>" . htmlspecialchars($expense['date']) . "</td>
                      </tr>";
        }
        $html .= "</tbody></table>";

        
        $html .= "<h2>Budgets</h2>
                  <table border='1' cellspacing='0' cellpadding='5' style='width: 100%; border-collapse: collapse;'>
                    <thead>
                        <tr>
                            <th>Budget Limit</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                        </tr>
                    </thead>
                    <tbody>";
        foreach ($budgets as $budget) {
            $html .= "<tr>
                        <td>" . number_format($budget['budget_limit'], 2) . "</td>
                        <td>" . htmlspecialchars($budget['start_date']) . "</td>
                        <td>" . htmlspecialchars($budget['end_date']) . "</td>
                      </tr>";
        }
        $html .= "</tbody></table>";

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("finance_report.pdf", ["Attachment" => true]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Finance Reports</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(120deg, #f3f4f6, #e5e7eb);
            color: #333;
            margin: 0;
            padding: 0;
        }

        h1, h2 {
            text-align: center;
            color: #2c3e50;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #34495e;
            color: #fff;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fefefe;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .actions {
            text-align: center;
            margin-top: 30px;
        }

        .actions a {
            text-decoration: none;
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            margin: 10px;
            display: inline-block;
            border-radius: 5px;
            font-size: 16px;
        }

        .actions a:hover {
            background-color: #45a049;
        }

        .user-details {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #ddd;
        }

        .dashboard-link {
            text-align: left;
            margin-bottom: 20px;
        }

        .dashboard-link a {
            text-decoration: none;
            background-color: #2980B9;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .dashboard-link a:hover {
            background-color: #1c6ea4;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="dashboard-link">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>

    <h1>Personal Finance Reports</h1>

    <!-- User Details Section -->
    <div class="user-details">
        <h2>User Details</h2>
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
    </div>

    <h2>Expenses</h2>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Amount</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($expense = $expenses->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($expense['category']); ?></td>
                    <td><?= number_format($expense['amount'], 2); ?></td>
                    <td><?= htmlspecialchars($expense['date']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Budgets</h2>
    <table>
        <thead>
            <tr>
                <th>Budget Limit</th>
                <th>Start Date</th>
                <th>End Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($budget = $budgets->fetch_assoc()): ?>
                <tr>
                    <td><?= number_format($budget['budget_limit'], 2); ?></td>
                    <td><?= htmlspecialchars($budget['start_date']); ?></td>
                    <td><?= htmlspecialchars($budget['end_date']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="actions">
        <a href="reports.php?export=pdf">Download as PDF</a>
        <a href="reports.php?export=csv">Download as CSV</a>
        <a href="reports.php?export=excel">Download as Excel</a>
    </div>
</div>

</body>
</html>
