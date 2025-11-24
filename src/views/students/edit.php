<?php
// This file contains a form for editing an existing student's details.

require_once '../../models/Student.php';

if (isset($_GET['id'])) {
    $studentId = $_GET['id'];
    $student = new Student();
    $studentDetails = $student->find($studentId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student->id = $_POST['id'];
    $student->name = $_POST['name'];
    $student->email = $_POST['email'];
    $student->update();
    header('Location: index.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="../../public/css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Edit Student</h2>
        <form action="edit.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $studentDetails['id']; ?>">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo $studentDetails['name']; ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $studentDetails['email']; ?>" required>
            </div>
            <button type="submit">Update Student</button>
        </form>
        <a href="index.php">Back to Student List</a>
    </div>
</body>
</html>