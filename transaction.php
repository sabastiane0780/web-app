<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_transaction'])) {
    $user_id = $_SESSION['user_id'];
    $expense_id = $_POST['expense_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];

    $stmt = $conn->prepare("INSERT INTO transactions (expense_id, amount, payment_method) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $expense_id, $amount, $payment_method);
    if ($stmt->execute()) {
        $message = "Transaction added successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}


$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT t.id, e.category, t.amount, t.payment_method, t.transaction_date FROM transactions t JOIN expenses e ON t.expense_id = e.id WHERE e.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id, $category, $amount, $payment_method, $transaction_date);

// Edit Transaction
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt_edit = $conn->prepare("SELECT expense_id, amount, payment_method FROM transactions WHERE id = ?");
    $stmt_edit->bind_param("i", $edit_id);
    $stmt_edit->execute();
    $stmt_edit->store_result();
    $stmt_edit->bind_result($expense_id, $edit_amount, $edit_payment_method);
    $stmt_edit->fetch();
    $stmt_edit->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_transaction'])) {
    $edit_id = $_POST['edit_id'];
    $expense_id = $_POST['expense_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];

    $stmt_update = $conn->prepare("UPDATE transactions SET expense_id = ?, amount = ?, payment_method = ? WHERE id = ?");
    $stmt_update->bind_param("idsi", $expense_id, $amount, $payment_method, $edit_id);
    if ($stmt_update->execute()) {
        header('Location: transactions.php');
        exit();
    } else {
        $message = "Error updating transaction: " . $stmt_update->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
</head>
<body>

<h1>Manage Your Transactions</h1>

<?php if (isset($message)) { echo "<p>$message</p>"; } ?>

<h2>Add New Transaction</h2>
<form method="POST" action="">
    <select name="expense_id" required>
        <?php
        $stmt_expenses = $conn->prepare("SELECT id, category FROM expenses WHERE user_id = ?");
        $stmt_expenses->bind_param("i", $user_id);
        $stmt_expenses->execute();
        $stmt_expenses->store_result();
        $stmt_expenses->bind_result($expense_id, $category);
        while ($stmt_expenses->fetch()) {
            echo "<option value=\"$expense_id\">$category</option>";
        }
        ?>
    </select><br>
    <input type="number" name="amount" placeholder="Amount" required><br>
    <input type="text" name="payment_method" placeholder="Payment Method" required><br>
    <button type="submit" name="add_transaction">Add Transaction</button>
</form>

<?php if (isset($edit_id)): ?>
    <h2>Edit Transaction</h2>
    <form method="POST" action="">
        <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
        <select name="expense_id" required>
            <?php
            $stmt_expenses = $conn->prepare("SELECT id, category FROM expenses WHERE user_id = ?");
            $stmt_expenses->bind_param("i", $user_id);
            $stmt_expenses->execute();
            $stmt_expenses->store_result();
            $stmt_expenses->bind_result($expense_id, $category);
            while ($stmt_expenses->fetch()) {
                $selected = ($expense_id == $expense_id) ? 'selected' : '';
                echo "<option value=\"$expense_id\" $selected>$category</option>";
            }
            ?>
        </select><br>
        <input type="number" name="amount" value="<?php echo $edit_amount; ?>" placeholder="Amount" required><br>
        <input type="text" name="payment_method" value="<?php echo $edit_payment_method; ?>" placeholder="Payment Method" required><br>
        <button type="submit" name="edit_transaction">Update Transaction</button>
    </form>
<?php endif; ?>

<h2>Your Transactions</h2>
<table>
    <thead>
        <tr>
            <th>Category</th>
            <th>Amount</th>
            <th>Payment Method</th>
            <th>Transaction Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($stmt->fetch()): ?>
            <tr>
                <td><?php echo htmlspecialchars($category); ?></td>
                <td>$<?php echo number_format($amount, 2); ?></td>
                <td><?php echo htmlspecialchars($payment_method); ?></td>
                <td><?php echo htmlspecialchars($transaction_date); ?></td>
                <td>
                    <a href="?edit_id=<?php echo $id; ?>">Edit</a> | 
                    <a href="?delete_id=<?php echo $id; ?>" onclick="return confirm('Are you sure you want to delete this transaction?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>

<?php
$conn->close();
?>
