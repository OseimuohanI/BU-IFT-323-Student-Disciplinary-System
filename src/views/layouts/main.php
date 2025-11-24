<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Disciplinary System</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <header>
        <h1>Student Disciplinary System</h1>
        <nav>
            <ul>
                <li><a href="/students/index.php">Students</a></li>
                <li><a href="/incidents/index.php">Incidents</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <?php include($view); ?>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Student Disciplinary System</p>
    </footer>
    <script src="/js/app.js"></script>
</body>
</html>