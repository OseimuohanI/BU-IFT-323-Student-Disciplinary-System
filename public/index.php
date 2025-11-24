<?php
require_once '../src/core/Router.php';
require_once '../src/core/Database.php';
require_once '../src/core/Controller.php';

// Initialize the application
$router = new Router();

// Load routes
require_once '../routes/web.php';

// Handle the request
$router->dispatch($_SERVER['REQUEST_URI']);
?>