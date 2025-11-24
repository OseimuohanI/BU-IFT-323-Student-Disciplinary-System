<?php
// This file contains a form for editing an existing incident.

require_once '../../../models/Incident.php';

if (isset($_GET['id'])) {
    $incidentId = $_GET['id'];
    $incident = new Incident();
    $incidentData = $incident->find($incidentId);
} else {
    // Redirect to incidents index if no ID is provided
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['description'];
    $date = $_POST['date'];

    $incident->update($incidentId, $description, $date);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Incident</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Edit Incident</h1>
        <form action="" method="POST">
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($incidentData['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($incidentData['date']); ?>" required>
            </div>
            <button type="submit">Update Incident</button>
        </form>
        <a href="index.php">Back to Incidents</a>
    </div>
</body>
</html>