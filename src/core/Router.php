<?php

class Router {
    private $routes = [];

    public function addRoute($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function resolve($method, $path) {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                $controllerName = $route['controller'];
                $actionName = $route['action'];
                $controller = new $controllerName();
                return $controller->$actionName();
            }
        }
        http_response_code(404);
        echo "404 Not Found";
    }
}