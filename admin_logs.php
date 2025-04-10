<?php
header('Content-Type: application/json');
require 'config.php';
session_start();

// Setting up a basic response array to send back as JSON
$response = ['success' => false, 'logs' => []];

// Quick check to make sure the user is logged in and has admin rights
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

// Grabbing the logs from the database, newest first
$stmt = $pdo->query("SELECT action, timestamp FROM logs ORDER BY timestamp DESC");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If we got here, everything worked, so update the response
$response['success'] = true;
$response['logs'] = $logs;

// Send the JSON response back to the client
echo json_encode($response);
?>