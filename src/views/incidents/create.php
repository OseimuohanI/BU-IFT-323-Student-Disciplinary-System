<?php
// This file contains a form for creating a new incident.

require_once '../../models/Incident.php';
require_once '../../models/Student.php';

$students = Student::getAllStudents(); // Assuming this method exists to fetch all students

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Incident</title>
    <link rel="stylesheet" href="../../public/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Create New Incident</h1>
        <form action="../../controllers/DisciplineController.php?action=create" method="POST">
            <div class="form-group">
                <label for="studentId">Student:</label>
                <select name="studentId" id="studentId" required>
                    <option value="">Select a student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student->id; ?>"><?= $student->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea name="description" id="description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" name="date" id="date" required>
            </div>
            <button type="submit">Create Incident</button>
        </form>
        <a href="index.php">Back to Incidents</a>
    </div>
</body>
</html>