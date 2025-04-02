<?php
header('Content-Type: application/json');
require 'config.php';
session_start();

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_csv') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            if ($handle === false) {
                $response['message'] = 'Failed to open CSV file';
                echo json_encode($response);
                exit;
            }

            $headers = fgetcsv($handle); // Skip header row
            if (!$headers || count($headers) < 3) {
                $response['message'] = 'Invalid CSV format: Expected at least 3 columns (name, latitude, longitude)';
                fclose($handle);
                echo json_encode($response);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO assets (name, latitude, longitude, category, uploaded_by) VALUES (?, ?, ?, ?, ?)");
            $rowCount = 0;

            try {
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 3) {
                        continue; // Skip rows with insufficient columns
                    }

                    $name = trim($row[0] ?? 'Unknown');
                    $latitude = floatval($row[1] ?? 0);
                    $longitude = floatval($row[2] ?? 0);

                    // Validate latitude and longitude
                    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                        continue; // Skip invalid coordinates
                    }

                    $category = 'other';
                    if (stripos($name, 'school') !== false) {
                        $category = 'school';
                    } elseif (stripos($name, 'hospital') !== false) {
                        $category = 'hospital';
                    } elseif (stripos($name, 'park') !== false) {
                        $category = 'park';
                    }

                    $stmt->execute([$name, $latitude, $longitude, $category, $user_id]);
                    $rowCount++;
                }
                fclose($handle);

                if ($rowCount > 0) {
                    $response['success'] = true;
                    $response['message'] = "Successfully uploaded $rowCount assets";
                } else {
                    $response['message'] = 'No valid assets found in CSV';
                }
            } catch (PDOException $e) {
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Failed to upload CSV: No file or upload error';
        }
    } elseif ($action === 'upload_individual') {
        $name = trim($_POST['name'] ?? '');
        $latitude = floatval($_POST['latitude'] ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);

        if ($name && $latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {
            $category = 'other';
            if (stripos($name, 'school') !== false) {
                $category = 'school';
            } elseif (stripos($name, 'hospital') !== false) {
                $category = 'hospital';
            } elseif (stripos($name, 'park') !== false) {
                $category = 'park';
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO assets (name, latitude, longitude, category, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $latitude, $longitude, $category, $user_id])) {
                    $response['success'] = true;
                    $response['message'] = 'Individual asset uploaded successfully';
                } else {
                    $response['message'] = 'Failed to upload individual asset';
                }
            } catch (PDOException $e) {
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Invalid asset data: Ensure name is provided and coordinates are valid';
        }
    } else {
        $response['message'] = 'Invalid action';
    }
}

if ($response['success']) {
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (action, user_id) VALUES (?, ?)");
        $stmt->execute(["Action: $action by user $user_id", $user_id]);
    } catch (PDOException $e) {
        // Log the error but don't fail the response
        error_log('Failed to log action: ' . $e->getMessage());
    }
}

echo json_encode($response);
?>