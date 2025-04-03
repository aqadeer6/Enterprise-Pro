<?php
header('Content-Type: application/json');
require 'config.php';
session_start();

$response = ['success' => false, 'assets' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$stmt = $pdo->query("SELECT name, latitude AS lat, longitude AS lon, category FROM assets");
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$response['success'] = true;
$response['assets'] = $assets;

echo json_encode($response);
?>