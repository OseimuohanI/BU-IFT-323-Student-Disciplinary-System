<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Student List</h1>
        <a href="create.php" class="btn">Add New Student</a>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch students from the database
                // Assuming $students is an array of student objects
                foreach ($students as $student) {
                    echo "<tr>
                            <td>{$student->id}</td>
                            <td>{$student->name}</td>
                            <td>{$student->email}</td>
                            <td>
                                <a href='edit.php?id={$student->id}' class='btn'>Edit</a>
                                <a href='show.php?id={$student->id}' class='btn'>View</a>
                                <a href='delete.php?id={$student->id}' class='btn btn-danger'>Delete</a>
                            </td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <script src="/js/app.js"></script>
</body>
</html>