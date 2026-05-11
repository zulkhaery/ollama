<?php
declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false) {
            return $default;
        }

        // Convert boolean strings
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }

        return $value;
    }
}

if (!function_exists('view')) {
    function view(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../app/Views/' . str_replace('.', '/', $view) . '.php';
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        if ($code === 404) {
            require __DIR__ . '/../app/Views/error/404.php';
        } else {
            echo "<h1>{$code} - {$message}</h1>";
        }
        exit;
    }
}

if (!class_exists('Database')) {
    class_alias(Core\Database::class, 'Database');
}