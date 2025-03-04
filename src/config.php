<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

define('SALT', 'ThisIs-A-Salt123');

function salter($string)
{
    return md5($string . SALT);
}

try {
    $dsn = 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];

    $dbConnection = new PDO($dsn, $user, $password);
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}
