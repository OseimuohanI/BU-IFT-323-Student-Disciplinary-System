<?php

use App\Controllers\StudentController;
use App\Controllers\DisciplineController;

$router = new Router();

// Student Routes
$router->get('/students', [StudentController::class, 'readStudents']);
$router->get('/students/create', [StudentController::class, 'createStudent']);
$router->post('/students', [StudentController::class, 'storeStudent']);
$router->get('/students/edit/{id}', [StudentController::class, 'editStudent']);
$router->post('/students/update/{id}', [StudentController::class, 'updateStudent']);
$router->get('/students/show/{id}', [StudentController::class, 'showStudent']);
$router->post('/students/delete/{id}', [StudentController::class, 'deleteStudent']);

// Incident Routes
$router->get('/incidents', [DisciplineController::class, 'readIncidents']);
$router->get('/incidents/create', [DisciplineController::class, 'createIncident']);
$router->post('/incidents', [DisciplineController::class, 'storeIncident']);
$router->get('/incidents/edit/{id}', [DisciplineController::class, 'editIncident']);
$router->post('/incidents/update/{id}', [DisciplineController::class, 'updateIncident']);
$router->get('/incidents/show/{id}', [DisciplineController::class, 'showIncident']);
$router->post('/incidents/delete/{id}', [DisciplineController::class, 'deleteIncident']);