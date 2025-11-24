<?php

use PHPUnit\Framework\TestCase;
require_once '../src/models/Student.php';

class StudentTest extends TestCase
{
    protected $student;

    protected function setUp(): void
    {
        $this->student = new Student();
    }

    public function testCanCreateStudent()
    {
        $this->student->setName('John Doe');
        $this->student->setEmail('john.doe@example.com');
        $this->assertEquals('John Doe', $this->student->getName());
        $this->assertEquals('john.doe@example.com', $this->student->getEmail());
    }

    public function testCanUpdateStudent()
    {
        $this->student->setName('Jane Doe');
        $this->student->setEmail('jane.doe@example.com');
        $this->student->update(); // Assuming update method exists
        $this->assertEquals('Jane Doe', $this->student->getName());
        $this->assertEquals('jane.doe@example.com', $this->student->getEmail());
    }

    public function testCanDeleteStudent()
    {
        $this->student->setId(1); // Assuming an ID is set for deletion
        $this->student->delete(); // Assuming delete method exists
        $this->assertNull($this->student->find(1)); // Assuming find method exists
    }

    public function testCanRetrieveStudent()
    {
        $this->student->setId(1); // Assuming an ID is set for retrieval
        $retrievedStudent = $this->student->find(1); // Assuming find method exists
        $this->assertNotNull($retrievedStudent);
    }
}