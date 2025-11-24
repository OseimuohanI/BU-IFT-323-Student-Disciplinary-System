<?php
// This file displays the details of a specific student.

require_once '../../models/Student.php';

if (isset($_GET['id'])) {
    $studentId = $_GET['id'];
    $student = new Student();
    $studentDetails = $student->readStudent($studentId);
} else {
    // Redirect or show an error if no ID is provided
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
    <link rel="stylesheet" href="../../public/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Student Details</h1>
        <?php if ($studentDetails): ?>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($studentDetails['id']); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($studentDetails['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($studentDetails['email']); ?></p>
            <a href="edit.php?id=<?php echo $studentDetails['id']; ?>">Edit</a>
            <a href="index.php">Back to Students List</a>
        <?php else: ?>
            <p>No student found with this ID.</p>
            <a href="index.php">Back to Students List</a>
        <?php endif; ?>
    </div>
</body>
</html>