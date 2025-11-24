<?php

class Incident {
    private $id;
    private $studentId;
    private $description;
    private $date;

    public function __construct($studentId, $description, $date) {
        $this->studentId = $studentId;
        $this->description = $description;
        $this->date = $date;
    }

    public function getId() {
        return $this->id;
    }

    public function getStudentId() {
        return $this->studentId;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDate() {
        return $this->date;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setStudentId($studentId) {
        $this->studentId = $studentId;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setDate($date) {
        $this->date = $date;
    }

    public function save() {
        // Code to save the incident to the database
    }

    public static function find($id) {
        // Code to find an incident by ID
    }

    public static function all() {
        // Code to retrieve all incidents
    }

    public function update() {
        // Code to update the incident in the database
    }

    public function delete() {
        // Code to delete the incident from the database
    }
}