<?php
header('Content-Type: application/json');
require 'config.php';
session_start();

// Setting up the response, we'll fill it with a report if all goes well
$response = ['success' => false, 'report' => ''];

// Only admins can see this, so check that first
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

// Grab all the logs, newest first
$stmt = $pdo->query("SELECT action, timestamp FROM logs ORDER BY timestamp DESC");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Start building a simple text report
$report = "Activity Report\n\n";
foreach ($logs as $log) {
    $report .= "Action: {$log['action']} at {$log['timestamp']}\n";
}

// Looks good, send it back
$response['success'] = true;
$response['report'] = $report;

echo json_encode($response);
?>