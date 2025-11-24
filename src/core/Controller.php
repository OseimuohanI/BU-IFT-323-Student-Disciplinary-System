<?php

class Controller {
    protected function response($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function validate($data, $rules) {
        // Implement validation logic here
        // Return true if valid, false otherwise
    }
}