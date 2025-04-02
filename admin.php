<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bradford Council | Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* Admin-specific styles */
        .admin-container {
            width: 100%;
            max-width: 1200px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #006871; /* Changed to match button color */
        }
        button {
            padding: 5px 10px;
            margin: 0 5px;
            cursor: pointer;
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
        <section id="admin" class="section active">
            <div class="admin-container">
                <h2>Admin Panel</h2>
                <!-- User Approval Section -->
                <h3>User Approval</h3>
                <table id="admin-approval-table">
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                    <tr>
                        <td colspan="4">Loading pending users...</td>
                    </tr>
                </table>

                <!-- Change User Role Section -->
                <h3>Change User Role</h3>
                <input type="text" id="change-role-username" placeholder="Username">
                <select id="role-select">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button onclick="changeUserRole()">Change Role</button>

                <!-- Change User Department Section -->
                <h3>Change User Department</h3>
                <input type="text" id="change-department-username" placeholder="Username">
                <select id="department-select">
                    <option value="" disabled selected>Select Department</option>
                    <option value="Department of Adult Social Care">Department of Adult Social Care</option>
                    <option value="Department of Children’s Services">Department of Children’s Services</option>
                    <option value="Department of Corporate Resources">Department of Corporate Resources</option>
                    <option value="Department of Place">Department of Place</option>
                </select>
                <button onclick="changeUserDepartment()">Change Department</button>

                <!-- View Logs Button -->
                <button onclick="window.location.href = 'view-logs.php'">View Logs</button>

                <!-- View CSV and GIS Data -->
                <button onclick="showCSVData()">View CSV Data</button>
                <button onclick="showGISData()">View GIS Data</button>

                <!-- Generate Report -->
                <button onclick="generateReport()">Generate Report</button>
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../js/main.js"></script>
    <script>
        /* Admin-specific JavaScript */
        function changeUserRole() {
            const username = document.getElementById('change-role-username').value;
            const newRole = document.getElementById('role-select').value;
            fetch('../backend/user_management.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=change_role&username=${encodeURIComponent(username)}&role=${encodeURIComponent(newRole)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    logAction(`Changed role of ${username} to ${newRole} at ${new Date().toISOString()}`);
                    alert(`Role of ${username} changed to ${newRole}.`);
                    fetchPendingUsers();
                } else {
                    alert(data.message || 'Failed to change role.');
                }
            })
            .catch(error => {
                console.error('Error changing role:', error);
                alert('Error changing role.');
            });
        }

        function changeUserDepartment() {
            const username = document.getElementById('change-department-username').value;
            const newDepartment = document.getElementById('department-select').value;
            if (!newDepartment) {
                alert('Please select a department.');
                return;
            }
            fetch('../backend/user_management.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=change_department&username=${encodeURIComponent(username)}&department=${encodeURIComponent(newDepartment)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    logAction(`Changed department of ${username} to ${newDepartment} at ${new Date().toISOString()}`);
                    alert(`Department of ${username} changed to ${newDepartment}.`);
                    fetchPendingUsers();
                } else {
                    alert(data.message || 'Failed to change department.');
                }
            })
            .catch(error => {
                console.error('Error changing department:', error);
                alert('Error changing department.');
            });
        }

        function showCSVData() {
            window.location.href = 'view-csv.php';
        }

        function showGISData() {
            window.location.href = 'view-gis.php';
        }

        function generateReport() {
            fetchLogs().then(logs => {
                if (logs.length === 0) {
                    alert('No log data available to generate a report.');
                    return;
                }

                // Prepare CSV content
                const headers = "ID,Action,Timestamp,Username\n";
                const rows = logs.map(log => 
                    `${log.id || ''},"${log.action || ''}","${log.created_at || ''}","${log.username || 'Unknown'}"`
                ).join("\n");
                const csvContent = "data:text/csv;charset=utf-8," + headers + rows;

                // Create and trigger download
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", `activity_report_${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Log the action
                logAction(`Generated activity report at ${new Date().toISOString()}`);
            }).catch(error => {
                console.error('Error generating report:', error);
                alert('Failed to generate report.');
            });
        }
    </script>
</body>
</html>