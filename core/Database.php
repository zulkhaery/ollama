<?php

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    /**
     * Get singleton PDO instance
     */
    public static function connection(): PDO
    {
        if (self::$pdo === null) {

            $driver = env('DB_DRIVER', 'mysql');

            try {

                switch ($driver) {

                    case 'pgsql':

                        $dsn = sprintf(
                            "pgsql:host=%s;port=%s;dbname=%s",
                            env('DB_HOST', 'localhost'),
                            env('DB_PORT', '5432'),
                            env('DB_NAME', 'pluto_db')
                        );

                        break;

                    case 'sqlite':

                        $dsn = "sqlite:" . env('DB_DATABASE', 'database.sqlite');

                        break;
                    case 'sqlsrv':

                        $dsn = sprintf(
                            "sqlsrv:Server=%s,%s;Database=%s",
                            env('DB_HOST', 'localhost'),
                            env('DB_PORT', '1433'),
                            env('DB_NAME', 'pluto_db')
                        );

                        break;                        

                    case 'mysql':
                    default:

                        $dsn = sprintf(
                            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                            env('DB_HOST', 'localhost'),
                            env('DB_PORT', '3306'),
                            env('DB_NAME', 'pluto_db')
                        );

                        break;
                }

                self::$pdo = new PDO(
                    $dsn,
                    env('DB_USER', 'root'),
                    env('DB_PASS', ''),
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );

            } catch (PDOException $e) {

                if (env('APP_DEBUG', true)) {

                    die("Database Connection Error: " . $e->getMessage());

                } else {

                    http_response_code(500);
                    die("Internal Server Error");

                }
            }
        }

        return self::$pdo;
    }

    /**
     * Run SELECT query
     */
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Run INSERT / UPDATE / DELETE
     */
    public static function exec(string $sql, array $params = []): int|string
    {
        $stmt = self::connection()->prepare($sql);

        $stmt->execute($params);

        $sqlType = strtoupper(strtok(trim($sql), ' '));

        if ($sqlType === 'INSERT') {
            return self::connection()->lastInsertId();
        }

        return $stmt->rowCount();
    }
}