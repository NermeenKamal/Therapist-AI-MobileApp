<?php
// In 98-105.php

$server = "127.0.0.1,1433";
$database = "meal";
$username = "nrmm";
$password = "YourStaticPassword123!";

try {
    $conn = new PDO("sqlsrv:Server=$server;Database=$database", $username, $password);
    echo "Connected successfully!";
} catch (PDOException $e) {
    echo "Failed: " . $e->getMessage();
}
