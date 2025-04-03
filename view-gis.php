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
    <title>Bradford Council | View GIS Data</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* GIS-specific styles */
        #map {
            width: 1550px;
            height: 650px;
            max-width: 1550px;
            margin: 20px auto;
            border-radius: 8px;
            display: block;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
        }

        button {
            padding: 12px 20px;
            background: #006871;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px auto;
            font-size: 16px;
            transition: background 0.3s;
            display: block; /* Center the button */
        }

        button:hover, button:focus {
            background: #00555d;
            outline: 2px solid #00555d;
        }

        /* High contrast mode adjustments */
        .high-contrast #map {
            filter: invert(100%) hue-rotate(180deg);
        }

        .high-contrast button {
            filter: invert(100%) hue-rotate(180deg);
            background-color: #333;
            color: #fff;
            border-color: #fff;
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
        <section id="view-gis" class="section active">
            <div id="map"></div>
            <button onclick="window.location.href = 'admin.php'">Back to Admin Panel</button>
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
        // Fetch GIS data (reusing fetchCSVData from main.js)
        function initializeMap() {
            fetchCSVData().then(assets => {
                const map = L.map('map', {
                    center: [53.795, -1.759], // Center on Bradford
                    zoom: 13, // Default zoom level
                    minZoom: 12, // Prevent zooming out too far
                    maxZoom: 16, // Limit zooming in
                    maxBounds: [
                        [53.740, -1.795], // SW corner of Bradford
                        [53.850, -1.695]  // NE corner of Bradford
                    ],
                    maxBoundsViscosity: 1.0 // Prevents panning outside bounds
                });

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(map);

                // Add markers for each asset
                if (assets.length === 0) {
                    console.log('No GIS data available.');
                } else {
                    assets.forEach(asset => {
                        if (asset.latitude && asset.longitude) {
                            const marker = L.marker([asset.latitude, asset.longitude]).addTo(map);
                            marker.bindPopup(`
                                <b>${asset.name}</b><br>
                                Category: ${asset.category}<br>
                                Uploaded By: ${asset.uploaded_by_username || 'Unknown'}<br>
                                Uploaded At: ${asset.uploaded_at}
                            `);
                        } else {
                            console.warn(`Invalid coordinates for asset: ${asset.name}`);
                        }
                    });
                }

                // Apply high contrast if active
                if (localStorage.getItem('isHighContrast') === 'true') {
                    document.getElementById('map').classList.add('high-contrast');
                }
            }).catch(error => {
                console.error('Error initializing map:', error);
            });
        }

        // Initialize map on page load
        document.addEventListener('DOMContentLoaded', () => {
            initializeMap();
        });
    </script>
</body>
</html>