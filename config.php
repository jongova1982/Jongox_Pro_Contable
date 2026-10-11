<?php
declare(strict_types=1);

// Local defaults work with a standard XAMPP installation. Override them with
// environment variables on other machines; never commit real credentials.
const DB_HOST = getenv('JONGOX_DB_HOST') ?: 'localhost';
const DB_PORT = getenv('JONGOX_DB_PORT') ?: '3306';
const DB_NAME = getenv('JONGOX_DB_NAME') ?: 'jongox_contable';
const DB_USER = getenv('JONGOX_DB_USER') ?: 'root';
const DB_PASS = getenv('JONGOX_DB_PASS') ?: '';

date_default_timezone_set('America/Bogota');

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10,
            ]
        );
    } catch (PDOException $ex) {
        throw new RuntimeException('Conexión a la base de datos falló: ' . $ex->getMessage(), 0, $ex);
    }

    return $pdo;
}

function money($value): string
{
    return '$ ' . number_format((float)$value, 0, ',', '.');
}
