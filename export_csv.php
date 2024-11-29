<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=report.csv');

require 'db.php'; // 

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT category, amount, description, date FROM expenses WHERE user_id = $user_id");

echo "Category, Amount, Description, Date\n";
while ($row = $result->fetch_assoc()) {
    echo "{$row['category']},{$row['amount']},{$row['description']},{$row['date']}\n";
}
?>
