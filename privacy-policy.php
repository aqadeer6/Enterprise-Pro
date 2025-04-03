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
    <title>Bradford Council | Privacy Policy</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Page-specific styles */
        .container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .container h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            color: #333;
        }
        .container p {
            margin-bottom: 15px;
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
        <section id="privacy-policy" class="section active">
            <div class="container">
                <h2>Privacy Policy</h2>
                <p>This Privacy Policy outlines how Bradford Council handles your personal information on the Asset Management platform.</p>

                <h3>1. Data We Collect</h3>
                <p>We collect information such as your email, username, and login activity to manage access and provide services on this platform.</p>

                <h3>2. How We Use Your Data</h3>
                <p>Your data is used to authenticate users, track activity for security and auditing purposes, and improve our services. We do not share your data with third parties except as required by law.</p>

                <h3>3. Data Security</h3>
                <p>We implement measures to protect your data, including secure storage and access controls. However, no system is entirely secure, and we cannot guarantee absolute protection.</p>

                <h3>5. Cookies</h3>
                <p>We use cookies to enhance your experience, as outlined in our GDPR consent modal. You can manage cookie preferences by accepting or declining them on your first visit.</p>

                <h3>6. Changes to This Policy</h3>
                <p>We may update this policy periodically. Check back for the latest version or contact us for clarification.</p>

                <button onclick="window.location.href = '../index.php'">Back to Home</button>
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
        document.addEventListener('DOMContentLoaded', () => {
            // No additional page-specific JavaScript needed
            // main.js handles navigation, contrast toggle, etc.
        });
    </script>
</body>
</html>