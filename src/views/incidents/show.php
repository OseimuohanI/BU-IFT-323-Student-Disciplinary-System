<?php
// This file displays the details of a specific incident.

// Include necessary files
require_once '../../models/Incident.php';

// Check if the incident ID is provided
if (isset($_GET['id'])) {
    $incidentId = $_GET['id'];
    $incidentModel = new Incident();
    $incident = $incidentModel->readIncident($incidentId);

    if ($incident) {
        // Display incident details
        echo "<h1>Incident Details</h1>";
        echo "<p><strong>ID:</strong> " . htmlspecialchars($incident['id']) . "</p>";
        echo "<p><strong>Student ID:</strong> " . htmlspecialchars($incident['studentId']) . "</p>";
        echo "<p><strong>Description:</strong> " . htmlspecialchars($incident['description']) . "</p>";
        echo "<p><strong>Date:</strong> " . htmlspecialchars($incident['date']) . "</p>";
    } else {
        echo "<p>Incident not found.</p>";
    }
} else {
    echo "<p>No incident ID provided.</p>";
}
?>