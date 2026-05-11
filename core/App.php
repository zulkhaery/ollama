<?php

declare(strict_types=1);

namespace Core;

class App
{
    public static function boot(): void
    {
        // Load helpers
        require_once __DIR__ . '/helpers.php';

        // Load environment
        (\Dotenv\Dotenv::createImmutable(dirname(__DIR__)))->load();

        // Error reporting
        self::setupErrorHandling();

        // Resolve and dispatch route
        self::dispatchRoute();
    }

    private static function setupErrorHandling(): void
    {
        if (env('APP_DEBUG', true)) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');

            register_shutdown_function(function () {
                $error = error_get_last();
                
                if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    
                    echo '<pre style="background:#1e1e1e;color:#6bff6b;padding:1rem">';
                    echo htmlspecialchars("Fatal Error: {$error['message']}") . "\n";
                    echo htmlspecialchars("File: {$error['file']} Line: {$error['line']}");
                    echo '</pre>';
                }
            });
            
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }
    }

    private static function dispatchRoute(): void
    {
        $path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';
        $routes = require dirname(__DIR__) . '/app/routes.php';
        $route = $routes[$path] ?? null;

        if (!$route) {
            abort(404);
        }

        if ($route instanceof \Closure) {
            $route();
            return;
        }

        if (!is_array($route) || count($route) !== 2) {
            http_response_code(500);
            exit('Invalid route definition.');
        }

        [$controller, $method] = $route;

        if (!class_exists($controller) || !method_exists($controller, $method)) {
            http_response_code(500);
            exit('Controller or method not found.');
        }

        (new $controller)->$method();
    }
}