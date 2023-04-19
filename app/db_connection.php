<?php
// Database configuration
$dbHost     = "mysql";
$dbUsername = "user";
$dbPassword = "pass";
$dbName     = "test";

// Create database connection
$db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}