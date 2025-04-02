<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to check user role
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bradford Council | View CSV Data</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Page-specific styles */
        .container h2 {
            margin-bottom: 20px;
            color: #333;
        }
    </style>
</head>
<body>
    <?php 
    $headerPath = dirname(__DIR__) . '/header.php';
    if (file_exists($headerPath)) {
        include $headerPath;
    } else {
        echo "<p style='color: red;'>Error: header.php not found at '$headerPath'</p>";
    }
    ?>

    <div id="content">
        <section id="view-csv" class="section active">
            <div class="container">
                <h2>View CSV Data</h2>
                <table id="csv-table">
                    <tr>
                        <th>Name</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Category</th>
                        <th>Uploaded By</th>
                        <th>Uploaded At</th>
                    </tr>
                    <tr>
                        <td colspan="6">Loading CSV data...</td>
                    </tr>
                </table>
                <button onclick="window.location.href = 'admin.php'">Back to Admin Panel</button>
            </div>
        </section>
    </div>

    <?php 
    $footerPath = dirname(__DIR__) . '/footer.php';
    if (file_exists($footerPath)) {
        include $footerPath;
    } else {
        echo "<p style='color: red;'>Error: footer.php not found at '$footerPath'</p>";
    }
    ?>

    <script src="../js/main.js"></script>
    <script>
        // Page-specific JavaScript
        document.addEventListener('DOMContentLoaded', () => {
            fetchCSVData().then(assets => {
                const csvTable = document.getElementById('csv-table');
                csvTable.innerHTML = '<tr><th>Name</th><th>Latitude</th><th>Longitude</th><th>Category</th><th>Uploaded By</th><th>Uploaded At</th></tr>';
                if (assets.length === 0) {
                    csvTable.innerHTML += '<tr><td colspan="6">No CSV data available.</td></tr>';
                } else {
                    assets.forEach(asset => {
                        csvTable.innerHTML += `
                            <tr>
                                <td>${asset.name}</td>
                                <td>${asset.latitude}</td>
                                <td>${asset.longitude}</td>
                                <td>${asset.category}</td>
                                <td>${asset.uploaded_by_username || 'Unknown'}</td>
                                <td>${asset.uploaded_at}</td>
                            </tr>
                        `;
                    });
                }
            });
        });
    </script>
</body>
</html>