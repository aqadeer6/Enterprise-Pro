<?php
// Database connection settings - pretty standard stuff for a local setup
$host = 'localhost';
$dbname = 'bradford_asset_management';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (empty)

try {
    // Trying to connect to the database with PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Make sure we catch any errors
} catch (PDOException $e) {
    // If it fails, just stop everything and show the error
    die("Connection failed: " . $e->getMessage());
}
?>