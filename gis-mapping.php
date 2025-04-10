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
    <title>Bradford Council | GIS Mapping</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* GIS-specific styles updated */
        .gis-container {
            width: fit-content; /* Adjust width to fit content (layer options) */
            max-width: 500px; /* Increased from 400px to fit all checkboxes on one line */
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.8); /* Semi-transparent white for overlay effect */
            border-radius: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            margin: 20px auto;
            position: relative;
            z-index: 1;
        }
        #gis-mapping {
            display: flex;
            flex-direction: column;
            align-items: center; /* Ensure all children (map and container) are centered */
            width: 100%;
            min-height: 100vh; /* Ensure section takes full height for centering */
            justify-content: center; /* Vertically center content if needed */
        }
        .map-wrapper {
            text-align: center; /* Center the map horizontally */
            width: 100%;
            position: relative; /* Ensure positioning context for #map */
        }
        #map {
            width: 1550px;
            height: 650px;
            max-width: 1550px;
            margin: 20px auto;
            border-radius: 10px;
            border: 2px solid #006871;
            display: block;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            z-index: 0; /* Ensure map stays below the layer controls */
        }
        .layer-controls {
            margin: 10px 0;
        }
        .layer-controls label {
            margin-right: 15px;
            font-weight: bold; /* Make the 3 words bold */
        }
        /* High contrast adjustments */
        .high-contrast #map {
            filter: invert(100%) hue-rotate(180deg);
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
        <section id="gis-mapping" class="section active">
            <h2>GIS Mapping</h2>
            <div class="gis-container">
                <div class="layer-controls" id="layer-controls">
                    <label><input type="checkbox" checked onchange="toggleLayer('schools')"> Schools</label>
                    <label><input type="checkbox" checked onchange="toggleLayer('hospitals')"> Hospitals</label>
                    <label><input type="checkbox" checked onchange="toggleLayer('parks')"> Parks</label>
                    <label><input type="checkbox" checked onchange="toggleLayer('others')"> Others</label>
                </div>
            </div>
            <div class="map-wrapper">
                <div id="map"></div>
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
        // GIS-specific JavaScript (all logic self-contained within this file)
        let assets = []; // Store assets fetched from the backend
        let map; // Map variable
        let layers = {}; // Object to store layer groups for toggling

        function fetchAssetsForMap() {
            fetch('../backend/gis_data.php', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    assets = data.assets; // Update the assets array with data from the backend
                    console.log('Assets fetched:', assets);
                    initializeMap(); // Call the map initialization function after fetching assets
                } else {
                    console.error('Failed to fetch assets:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching assets:', error);
            });
        }

        function initializeMap() {
            // Check if map already exists and remove it to prevent duplicates
            if (typeof map !== 'undefined' && map) {
                map.remove();
            }

            // Initialize the map with the same settings as before
            map = L.map('map', {
                center: [53.795, -1.759], // Bradford coordinates
                zoom: 13,  // Default zoom level
                minZoom: 12,  // Prevent zooming in too much
                maxZoom: 16,  // Limit zooming out
                maxBounds: [
                    [53.740, -1.795], // SW corner of Bradford bounding box
                    [53.850, -1.695]  // NE corner of Bradford bounding box
                ],
                maxBoundsViscosity: 1.0 // Prevents the map from going outside the bounds
            });

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            // Define layer groups for different categories
            layers['schools'] = L.layerGroup();
            layers['hospitals'] = L.layerGroup();
            layers['parks'] = L.layerGroup();
            layers['others'] = L.layerGroup();

            // Add markers to the appropriate layer based on category
            assets.forEach(asset => {
                const marker = L.marker([asset.lat, asset.lon]).bindPopup(asset.name);
                if (asset.category === 'school') {
                    layers['schools'].addLayer(marker);
                } else if (asset.category === 'hospital') {
                    layers['hospitals'].addLayer(marker);
                } else if (asset.category === 'park') {
                    layers['parks'].addLayer(marker);
                } else {
                    layers['others'].addLayer(marker);
                }
            });

            // Add layers to the map (default: all layers are visible)
            layers['schools'].addTo(map);
            layers['hospitals'].addTo(map);
            layers['parks'].addTo(map);
            layers['others'].addTo(map);

            // Apply high contrast if enabled (using global isHighContrast from main.js)
            if (isHighContrast) {
                const elements = document.querySelectorAll('*');
                elements.forEach(element => element.classList.add('high-contrast'));
            }
        }

        function toggleLayer(layerName) {
            if (map && layers[layerName]) {
                const checkbox = document.querySelector(`input[onchange="toggleLayer('${layerName}')"]`);
                if (checkbox.checked) {
                    map.addLayer(layers[layerName]);
                } else {
                    map.removeLayer(layers[layerName]);
                }
            }
        }

        // Initialize the map on page load
        document.addEventListener('DOMContentLoaded', () => {
            fetchAssetsForMap();
        });
    </script>
</body>
</html>