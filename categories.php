<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $color = $_POST['color'];

    $stmt = $conn->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $name, $color);
    if ($stmt->execute()) {
        $message = "Category added successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}


$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, name, color FROM categories WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id, $name, $color);

// Edit Category
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt_edit = $conn->prepare("SELECT name, color FROM categories WHERE id = ?");
    $stmt_edit->bind_param("i", $edit_id);
    $stmt_edit->execute();
    $stmt_edit->store_result();
    $stmt_edit->bind_result($edit_name, $edit_color);
    $stmt_edit->fetch();
    $stmt_edit->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_category'])) {
    $edit_id = $_POST['edit_id'];
    $name = $_POST['name'];
    $color = $_POST['color'];

    $stmt_update = $conn->prepare("UPDATE categories SET name = ?, color = ? WHERE id = ?");
    $stmt_update->bind_param("ssi", $name, $color, $edit_id);
    if ($stmt_update->execute()) {
        header('Location: categories.php');
        exit();
    } else {
        $message = "Error updating category: " . $stmt_update->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>
</head>
<body>

<h1>Manage Your Categories</h1>

<?php if (isset($message)) { echo "<p>$message</p>"; } ?>

<h2>Add New Category</h2>
<form method="POST" action="">
    <input type="text" name="name" placeholder="Category Name" required><br>
    <input type="color" name="color" value="#ffffff" required><br>
    <button type="submit" name="add_category">Add Category</button>
</form>

<?php if (isset($edit_id)): ?>
    <h2>Edit Category</h2>
    <form method="POST" action="">
        <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
        <input type="text" name="name" value="<?php echo $edit_name; ?>" placeholder="Category Name" required><br>
        <input type="color" name="color" value="<?php echo $edit_color; ?>" required><br>
        <button type="submit" name="edit_category">Update Category</button>
    </form>
<?php endif; ?>

<h2>Your Categories</h2>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Color</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($stmt->fetch()): ?>
            <tr>
                <td><?php echo htmlspecialchars($name); ?></td>
                <td style="background-color: <?php echo htmlspecialchars($color); ?>;"><?php echo htmlspecialchars($color); ?></td>
                <td>
                    <a href="?edit_id=<?php echo $id; ?>">Edit</a> | 
                    <a href="?delete_id=<?php echo $id; ?>" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
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
