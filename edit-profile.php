<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to check if user is logged in
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch user data to prepopulate the form
require '../backend/config.php';
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT first_name, last_name, username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // If user not found, log out and redirect
    session_destroy();
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bradford Council | Edit Profile</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Edit Profile-specific styles */
        .container {
            width: 100%;
            padding: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            margin: 20px auto;
            opacity: 0;
            animation: fadeIn 0.5s ease-in forwards;
            z-index: 100;
        }

        .container h2, .container h3 {
            margin-bottom: 20px;
            color: white; /* Override shared .container h2 from style.css */
        }

        .input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .container input {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: block;
            position: relative;
            margin-left: auto;
            margin-right: auto;
        }

        .error-message {
            display: none;
            position: absolute;
            background: #ff0000;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .button-group {
            margin-top: 20px;
        }

        .container button {
            width: 100%;
            padding: 12px;
            background: #006871;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 0;
            font-size: 16px;
            transition: background 0.3s;
        }

        .container button:hover, .container button:focus {
            background: #00555d;
            outline: 2px solid #00555d;
        }

        /* High Contrast Mode specific to Edit Profile */
        .high-contrast .container {
            filter: invert(100%) hue-rotate(180deg);
            background-color: #000;
            color: #fff;
        }

        .high-contrast .container input, .high-contrast .container button {
            filter: invert(100%) hue-rotate(180deg);
            background-color: #333;
            color: #fff;
            border-color: #fff;
        }

        .high-contrast .container button:focus, .high-contrast .container input:focus {
            outline: 2px solid #fff;
        }

        .high-contrast .error-message {
            background: #00ff00;
            color: #000;
        }

        /* Add styling for the eye icon */
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 16px;
            color: #666;
        }
        .toggle-password:hover {
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
        <section id="edit-profile" class="section active">
            <div class="container" role="region" aria-label="Edit Profile Form">
                <h2 role="heading" aria-level="2">Edit Profile</h2>

                <div class="input-wrapper">
                    <input type="text" placeholder="First Name" required aria-required="true" aria-label="First Name" id="edit-first-name" tabindex="0" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                    <div class="error-message" id="edit-first-name-error" role="alert">Enter a valid first name</div>
                </div>

                <div class="input-wrapper">
                    <input type="text" placeholder="Last Name" required aria-required="true" aria-label="Last Name" id="edit-last-name" tabindex="0" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                    <div class="error-message" id="edit-last-name-error" role="alert">Enter a valid last name</div>
                </div>

                <div class="input-wrapper">
                    <input type="text" placeholder="Username" required aria-required="true" aria-label="Username" id="edit-username" tabindex="0" value="<?php echo htmlspecialchars($user['username']); ?>">
                    <div class="error-message" id="edit-username-error" role="alert">Enter a valid username</div>
                </div>

                <div class="input-wrapper">
                    <input type="email" placeholder="Email" required aria-required="true" aria-label="Email" id="edit-email" tabindex="0" value="<?php echo htmlspecialchars($user['email']); ?>">
                    <div class="error-message" id="edit-email-error" role="alert">Enter a valid email address (e.g., must include @)</div>
                </div>

                <div class="button-group">
                    <button onclick="saveProfileDetails()" tabindex="0" aria-label="Save Profile Details">Save Details</button>
                </div>

                <h3>Change Password</h3>

                <div class="input-wrapper">
                    <input type="password" placeholder="Current Password" required aria-required="true" aria-label="Current Password" id="edit-current-password" tabindex="0">
                    <span class="toggle-password" onclick="togglePassword('edit-current-password')">👁️</span>
                    <div class="error-message" id="edit-current-password-error" role="alert">Enter your current password</div>
                </div>

                <div class="input-wrapper">
                    <input type="password" placeholder="New Password" aria-label="New Password" id="edit-new-password" tabindex="0">
                    <span class="toggle-password" onclick="togglePassword('edit-new-password')">👁️</span>
                    <div class="error-message" id="edit-new-password-error" role="alert">New password must be at least 8 characters with an uppercase letter and a number</div>
                </div>

                <div class="input-wrapper">
                    <input type="password" placeholder="Confirm New Password" aria-label="Confirm New Password" id="edit-confirm-password" tabindex="0">
                    <span class="toggle-password" onclick="togglePassword('edit-confirm-password')">👁️</span>
                    <div class="error-message" id="edit-confirm-password-error" role="alert">Passwords must match</div>
                </div>

                <div class="button-group">
                    <button onclick="changePassword()" tabindex="0" aria-label="Change Password">Change Password</button>
                    <button onclick="cancelEditProfile()" tabindex="0" aria-label="Cancel Profile Changes">Cancel</button>
                </div>
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
        // Edit Profile page-specific JavaScript
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = window.location.pathname.split('/').pop().replace('.php', '').replace('.html', '') || 'index';
            if (!isLoggedIn && !isAdminLoggedIn && currentPage !== 'index' && currentPage !== 'privacy-policy') {
                alert("Please log in to access this section.");
                window.location.href = '../index.php';
                return;
            }
            showSection('edit-profile');
        });

        function saveProfileDetails() {
            const firstName = document.getElementById('edit-first-name').value;
            const lastName = document.getElementById('edit-last-name').value;
            const username = document.getElementById('edit-username').value;
            const email = document.getElementById('edit-email').value;

            // Client-side validation
            if (!firstName) {
                showErrorMessage(document.getElementById('edit-first-name-error'), 'Enter a valid first name');
                return;
            }
            document.getElementById('edit-first-name-error').style.display = 'none';

            if (!lastName) {
                showErrorMessage(document.getElementById('edit-last-name-error'), 'Enter a valid last name');
                return;
            }
            document.getElementById('edit-last-name-error').style.display = 'none';

            if (!username) {
                showErrorMessage(document.getElementById('edit-username-error'), 'Enter a valid username');
                return;
            }
            document.getElementById('edit-username-error').style.display = 'none';

            if (!isValidEmail(email)) {
                showErrorMessage(document.getElementById('edit-email-error'), 'Enter a valid email address (e.g., must include @)');
                return;
            }
            document.getElementById('edit-email-error').style.display = 'none';

            fetch('../backend/user_management.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_profile_details&first_name=${encodeURIComponent(firstName)}&last_name=${encodeURIComponent(lastName)}&username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Profile details updated successfully.');
                    logAction(`Profile details updated for ${email} at ${new Date().toISOString()}`);
                    window.location.reload(); // Refresh to show updated data
                } else {
                    if (data.message.includes('email')) {
                        showErrorMessage(document.getElementById('edit-email-error'), data.message);
                    } else if (data.message.includes('username')) {
                        showErrorMessage(document.getElementById('edit-username-error'), data.message);
                    } else {
                        alert(data.message || 'Failed to update profile details.');
                    }
                }
            })
            .catch(error => {
                console.error('Error updating profile details:', error);
                alert('Error updating profile details.');
            });
        }

        function changePassword() {
            const currentPassword = document.getElementById('edit-current-password').value;
            const newPassword = document.getElementById('edit-new-password').value;
            const confirmPassword = document.getElementById('edit-confirm-password').value;

            // Client-side validation
            if (!currentPassword) {
                showErrorMessage(document.getElementById('edit-current-password-error'), 'Enter your current password');
                return;
            }
            document.getElementById('edit-current-password-error').style.display = 'none';

            if (newPassword && !isValidPassword(newPassword)) {
                showErrorMessage(document.getElementById('edit-new-password-error'), 'New password must be at least 8 characters with an uppercase letter, a number, and a special character');
                return;
            }
            document.getElementById('edit-new-password-error').style.display = 'none';

            if (newPassword !== confirmPassword) {
                showErrorMessage(document.getElementById('edit-confirm-password-error'), 'Passwords must match');
                return;
            }
            document.getElementById('edit-confirm-password-error').style.display = 'none';

            fetch('../backend/user_management.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=change_password&current_password=${encodeURIComponent(currentPassword)}&new_password=${encodeURIComponent(newPassword)}&confirm_password=${encodeURIComponent(confirmPassword)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Password updated successfully.');
                    logAction(`Password updated at ${new Date().toISOString()}`);
                    window.location.reload(); // Refresh to clear password fields
                } else {
                    if (data.message.includes('current password')) {
                        showErrorMessage(document.getElementById('edit-current-password-error'), data.message);
                    } else if (data.message.includes('match')) {
                        showErrorMessage(document.getElementById('edit-confirm-password-error'), data.message);
                    } else if (data.message.includes('New password must be')) {
                        showErrorMessage(document.getElementById('edit-new-password-error'), data.message);
                    } else {
                        alert(data.message || 'Failed to update password.');
                    }
                }
            })
            .catch(error => {
                console.error('Error updating password:', error);
                alert('Error updating password.');
            });
        }

        function cancelEditProfile() {
            window.location.href = '../index.php';
        }
    </script>
</body>
</html>