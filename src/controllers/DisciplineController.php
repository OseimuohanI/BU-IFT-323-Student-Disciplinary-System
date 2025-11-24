<?php

class DisciplineController extends Controller
{
    public function createIncident($data)
    {
        // Logic to create a new incident
        $incident = new Incident();
        $incident->studentId = $data['studentId'];
        $incident->description = $data['description'];
        $incident->date = $data['date'];
        $incident->save();
    }

    public function readIncidents()
    {
        // Logic to read all incidents
        return Incident::all();
    }

    public function updateIncident($id, $data)
    {
        // Logic to update an existing incident
        $incident = Incident::find($id);
        if ($incident) {
            $incident->studentId = $data['studentId'];
            $incident->description = $data['description'];
            $incident->date = $data['date'];
            $incident->save();
        }
    }

    public function deleteIncident($id)
    {
        // Logic to delete an incident
        $incident = Incident::find($id);
        if ($incident) {
            $incident->delete();
        }
    }
}