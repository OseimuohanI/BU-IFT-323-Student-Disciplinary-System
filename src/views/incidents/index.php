<?php
require_once '../../../models/Incident.php';

$incidentModel = new Incident();
$incidents = $incidentModel->readIncidents();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidents List</title>
    <link rel="stylesheet" href="../../../public/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Disciplinary Incidents</h1>
        <a href="create.php" class="btn">Create New Incident</a>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student ID</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($incidents as $incident): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($incident['id']); ?></td>
                        <td><?php echo htmlspecialchars($incident['studentId']); ?></td>
                        <td><?php echo htmlspecialchars($incident['description']); ?></td>
                        <td><?php echo htmlspecialchars($incident['date']); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo htmlspecialchars($incident['id']); ?>">Edit</a>
                            <a href="show.php?id=<?php echo htmlspecialchars($incident['id']); ?>">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>