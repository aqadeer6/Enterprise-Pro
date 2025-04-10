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
    <title>Bradford Council | CSV Upload</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        #csv-status {
            margin-top: 10px;
        }
        /* Match screenshot width and styling */
        .csv-container {
            width: 100%;
            max-width: 600px; /* Matches screenshot width */
            padding: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            margin: 20px auto;
        }
        #individual-upload input {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        /* Ensure individual upload is hidden by default */
        #individual-upload {
            display: none; /* Initially hidden, shown only for regular users */
        }
        /* High contrast adjustments */
        .high-contrast .csv-container {
            background: #000;
            color: #fff;
            border: 2px solid #fff;
        }
        .high-contrast #individual-upload input {
            background: #000;
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
        <section id="csv-upload" class="section active">
            <div class="csv-container">
                <h2>Upload CSV Data</h2>
                <input type="file" id="csvFile" accept=".csv">
                <button onclick="showCSVPreview()">Preview</button>
                <button onclick="uploadCSV()">Upload</button>
                <div id="csv-preview" style="display: none;">
                    <h3>CSV Preview</h3>
                    <table id="preview-table"></table>
                    <button onclick="submitCSV()">Submit</button>
                    <button onclick="cancelPreview()">Cancel</button>
                    <button onclick="modifyData()">Modify Data</button>
                </div>
                <div id="individual-upload" role="region" aria-label="Individual Asset Upload">
                    <h3>Individual Asset Upload</h3>
                    <input type="text" placeholder="Asset Name" id="asset-name">
                    <input type="text" placeholder="Latitude" id="asset-lat">
                    <input type="text" placeholder="Longitude" id="asset-lon">
                    <button onclick="uploadIndividualAsset()">Upload</button>
                    <button onclick="cancelIndividualUpload()">Cancel</button>
                </div>
                <p id="csv-status"></p>
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
        // CSV-specific JavaScript (all logic self-contained within this file)
        let previewData = []; // Store parsed CSV data for preview and submission

        // Custom CSV parsing function
        function parseCSV(file, callback) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                console.log("Raw CSV content:", text);

                // Split the text into lines
                const lines = text.split(/\r\n|\n/).filter(line => line.trim() !== '');
                if (lines.length === 0) {
                    callback({ errors: [{ message: "CSV file is empty" }], data: [] });
                    return;
                }

                // Get headers from the first line
                const headers = lines[0].split(',').map(header => header.trim());
                if (headers.length < 3) {
                    callback({ errors: [{ message: "CSV must have at least 3 columns: name, latitude, longitude" }], data: [] });
                    return;
                }

                // Parse the remaining lines into objects
                const data = [];
                for (let i = 1; i < lines.length; i++) {
                    const row = lines[i].split(',').map(cell => cell.trim());
                    if (row.length < headers.length) {
                        continue; // Skip rows with insufficient columns
                    }
                    const rowData = {};
                    headers.forEach((header, index) => {
                        rowData[header] = row[index] || '';
                    });
                    data.push(rowData);
                }

                callback({ errors: [], data });
            };
            reader.onerror = function(e) {
                callback({ errors: [{ message: "Error reading CSV file: " + e.message }], data: [] });
            };
            reader.readAsText(file);
        }

        // Convert previewData array back to CSV string
        function arrayToCSV(data) {
            if (!data || data.length === 0) return '';
            const headers = Object.keys(data[0]);
            const csvRows = [];
            // Add headers
            csvRows.push(headers.join(','));
            // Add data rows
            data.forEach(row => {
                const values = headers.map(header => row[header] || '');
                csvRows.push(values.join(','));
            });
            return csvRows.join('\n');
        }

        function uploadCSV() {
            const fileInput = document.getElementById("csvFile");
            const statusElement = document.getElementById("csv-status");

            if (fileInput.files.length === 0 && previewData.length === 0) {
                statusElement.innerText = "Please select a CSV file or preview and modify data first.";
                return;
            }

            const formData = new FormData();
            formData.append('action', 'upload_csv');

            // If previewData exists (i.e., data was previewed and possibly modified), use it
            if (previewData.length > 0) {
                const csvContent = arrayToCSV(previewData);
                const blob = new Blob([csvContent], { type: 'text/csv' });
                formData.append('csv_file', blob, 'modified_assets.csv');
                console.log("Uploading modified CSV data:", csvContent);
            } else {
                // Otherwise, use the original file
                formData.append('csv_file', fileInput.files[0]);
                console.log("Uploading original CSV file:", fileInput.files[0].name);
            }

            fetch('../backend/csv_upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusElement.innerText = data.message || "CSV uploaded successfully!";
                    logAction(`Uploaded CSV with ${previewData.length > 0 ? previewData.length : 'original file'} assets at ${new Date().toISOString()}`);
                    fileInput.value = ''; // Clear the file input
                    previewData = []; // Clear previewData after successful upload
                } else {
                    statusElement.innerText = data.message || "Failed to upload CSV.";
                }
            })
            .catch(error => {
                console.error('Error uploading CSV:', error);
                statusElement.innerText = "Error uploading CSV.";
            });
        }

        function showCSVPreview() {
            const fileInput = document.getElementById("csvFile");
            const statusElement = document.getElementById("csv-status");

            if (!fileInput.files.length) {
                statusElement.innerText = "Please select a CSV file.";
                console.log("No file selected for preview.");
                return;
            }

            console.log("Starting CSV preview for file:", fileInput.files[0].name);
            parseCSV(fileInput.files[0], function(results) {
                console.log("Parse results:", results);

                if (results.errors.length > 0) {
                    console.error("CSV parsing errors:", results.errors);
                    statusElement.innerText = "Error parsing CSV: " + results.errors[0].message;
                    return;
                }

                if (!results.data || !Array.isArray(results.data) || results.data.length === 0) {
                    statusElement.innerText = "No valid data found in CSV.";
                    console.log("No valid data to preview:", results.data);
                    return;
                }

                previewData = results.data;
                console.log("Preview data set:", previewData);
                displayPreview(previewData);
            });
        }

        function displayPreview(data) {
            const table = document.getElementById("preview-table");
            if (data.length === 0) {
                table.innerHTML = '<tr><td>No data to preview</td></tr>';
                return;
            }

            const headers = Object.keys(data[0]);
            table.innerHTML = '<tr>' + headers.map(key => `<th>${key}</th>`).join('') + '</tr>';
            data.forEach(row => {
                table.innerHTML += '<tr>' + headers.map(key => `<td>${row[key] || ''}</td>`).join('') + '</tr>';
            });
            document.getElementById("csv-preview").style.display = "block";
        }

        function submitCSV() {
            const table = document.getElementById("preview-table");
            // If the table is editable, save the changes to previewData
            if (table.contentEditable === "true") {
                table.contentEditable = false;
                const headers = Object.keys(previewData[0]);
                const rows = Array.from(table.rows).slice(1); // Skip header row
                previewData = rows.map(row => {
                    const rowData = {};
                    Array.from(row.cells).forEach((cell, index) => {
                        rowData[headers[index]] = cell.innerText;
                    });
                    return rowData;
                });
                logAction(`Finished modifying data at ${new Date().toISOString()}`);
                alert("Data modifications saved to preview. Click Upload to apply changes.");
            }
            // Clear the preview display, but keep previewData for upload
            document.getElementById("csv-status").innerText = "CSV preview submitted. Click Upload to save changes.";
            document.getElementById("csv-preview").style.display = "none";
            logAction(`Submitted CSV preview at ${new Date().toISOString()}`);
        }

        function cancelPreview() {
            document.getElementById("csv-preview").style.display = "none";
            const table = document.getElementById("preview-table");
            table.contentEditable = false; // Disable editing if active
            previewData = []; // Discard changes
        }

        function modifyData() {
            const table = document.getElementById("preview-table");
            table.contentEditable = true;
            logAction(`Started modifying data at ${new Date().toISOString()}`);
            document.getElementById("csv-status").innerText = "Editing enabled. Modify the table and click Submit to save changes, or Cancel to discard.";
        }

        function uploadIndividualAsset() {
            const name = document.getElementById("asset-name").value;
            const lat = parseFloat(document.getElementById("asset-lat").value);
            const lon = parseFloat(document.getElementById("asset-lon").value);
            const statusElement = document.getElementById("csv-status");

            if (!name || isNaN(lat) || isNaN(lon)) {
                statusElement.innerText = "Please enter valid name, latitude, and longitude.";
                return;
            }

            const formData = new FormData();
            formData.append('action', 'upload_individual');
            formData.append('name', name);
            formData.append('latitude', lat);
            formData.append('longitude', lon);

            fetch('../backend/csv_upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusElement.innerText = data.message || "Individual asset uploaded successfully!";
                    logAction(`Uploaded individual asset ${name} at ${new Date().toISOString()}`);
                    document.getElementById("individual-upload").style.display = "none";
                    // Clear the form
                    document.getElementById("asset-name").value = '';
                    document.getElementById("asset-lat").value = '';
                    document.getElementById("asset-lon").value = '';
                } else {
                    statusElement.innerText = data.message || "Failed to upload individual asset.";
                }
            })
            .catch(error => {
                console.error('Error uploading individual asset:', error);
                statusElement.innerText = "Error uploading individual asset.";
            });
        }

        function cancelIndividualUpload() {
            window.location.href = '../index.php';
        }

        // Fallback to hide individual upload if admin
        window.addEventListener('load', () => {
            console.log(`Window load - isLoggedIn: ${isLoggedIn}, isAdminLoggedIn: ${isAdminLoggedIn}`);
            if (typeof isAdminLoggedIn !== 'undefined' && isAdminLoggedIn) {
                const individualUpload = document.getElementById('individual-upload');
                if (individualUpload) {
                    individualUpload.style.display = 'none';
                    console.log('Fallback: Hiding individual upload for admin');
                }
            }
        });
    </script>
</body>
</html>