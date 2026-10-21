<?php

declare(strict_types=1);

/**
 * The database connection.
 *
 * The credentials are read from the environment, so the same image runs
 * locally, in the test stack and in a deployment without a change in the
 * source. The local defaults match the compose stack and are only used when
 * the environment does not say anything.
 */

$dbHost = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'mysql';
$dbUsername = getenv('DB_USER') !== false ? getenv('DB_USER') : 'user';
$dbPassword = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : 'pass';
$dbName = getenv('DB_DATABASE') !== false ? getenv('DB_DATABASE') : 'test';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'The database is not reachable: ' . $exception->getMessage();
    exit;
}
