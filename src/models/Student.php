<?php

class Student {
    private $id;
    private $name;
    private $email;

    public function __construct($name, $email, $id = null) {
        $this->name = $name;
        $this->email = $email;
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getEmail() {
        return $this->email;
    }

    public function save() {
        // Code to save the student to the database
    }

    public static function findAll() {
        // Code to retrieve all students from the database
    }

    public static function findById($id) {
        // Code to retrieve a student by ID from the database
    }

    public function update() {
        // Code to update the student's details in the database
    }

    public function delete() {
        // Code to delete the student from the database
    }
}