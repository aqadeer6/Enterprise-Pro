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
    <title>Bradford Council | View Logs</title>
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
        <section id="view-logs" class="section active">
            <div class="container">
                <h2>View Logs</h2>
                <table id="logs-table">
                    <tr>
                        <th>Username</th>
                        <th>Action</th>
                        <th>Timestamp</th>
                    </tr>
                    <tr>
                        <td colspan="3">Loading logs...</td>
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
            fetchLogs().then(logs => {
                const logsTable = document.getElementById('logs-table');
                logsTable.innerHTML = '<tr><th>Username</th><th>Action</th><th>Timestamp</th></tr>';
                if (logs.length === 0) {
                    logsTable.innerHTML += '<tr><td colspan="3">No logs available.</td></tr>';
                } else {
                    logs.forEach(log => {
                        logsTable.innerHTML += `
                            <tr>
                                <td>${log.username}</td>
                                <td>${log.action}</td>
                                <td>${log.created_at}</td>
                            </tr>
                        `;
                    });
                }
            });
        });
    </script>
</body>
</html>