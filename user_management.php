<?php
header('Content-Type: application/json');
require 'config.php';
require 'email_config.php'; // PHPMailer integration
session_start();

// Starting with a basic response setup
$response = ['success' => false, 'message' => ''];

try {
    // Check if the user is logged in first
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Unauthorized: No user session';
        error_log("Unauthorized access: No user session");
        echo json_encode($response);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile_details') {
            $first_name = $_POST['first_name'] ?? null;
            $last_name = $_POST['last_name'] ?? null;
            $username = $_POST['username'] ?? null;
            $email = $_POST['email'] ?? null;

            // Grab the current user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $response['message'] = 'User not found';
                echo json_encode($response);
                exit;
            }

            // Use new values if provided, otherwise keep the old ones
            $first_name = $first_name !== null ? $first_name : $user['first_name'];
            $last_name = $last_name !== null ? $last_name : $user['last_name'];
            $username = $username !== null ? $username : $user['username'];
            $email = $email !== null ? $email : $user['email'];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Enter a valid email address';
                echo json_encode($response);
                exit;
            }

            // Check for duplicate username or email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_id]);
            if ($stmt->fetch()) {
                $response['message'] = 'Username or email already taken';
                echo json_encode($response);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$first_name, $last_name, $username, $email, $user_id])) {
                $response['success'] = true;
                $response['message'] = 'Profile details updated successfully';
            } else {
                $response['message'] = 'Failed to update profile details';
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $response['message'] = 'User not found';
                echo json_encode($response);
                exit;
            }

            // Verify the current password
            if (!password_verify($current_password, $user['password'])) {
                $response['message'] = 'Invalid current password';
                echo json_encode($response);
                exit;
            }

            if ($new_password !== $confirm_password) {
                $response['message'] = 'New password and confirmation do not match';
                echo json_encode($response);
                exit;
            }

            // Make sure the new password is strong enough
            if (strlen($new_password) < 8 || !preg_match("/[A-Z]/", $new_password) || !preg_match("/[0-9]/", $new_password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $new_password)) {
                $response['message'] = 'New password must be at least 8 characters with an uppercase letter, a number, and a special character';
                echo json_encode($response);
                exit;
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $response['success'] = true;
                $response['message'] = 'Password updated successfully';
            } else {
                $response['message'] = 'Failed to update password';
            }
        } elseif ($action === 'get_pending_users') {
            // Admin-only action
            if ($_SESSION['role'] !== 'admin') {
                $response['message'] = 'Unauthorized';
                echo json_encode($response);
                exit;
            }
            $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE TRIM(LOWER(status)) = 'pending'");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['users'] = $users ?: [];
        } elseif ($action === 'approve_user') {
            if ($_SESSION['role'] !== 'admin') {
                $response['message'] = 'Unauthorized';
                echo json_encode($response);
                exit;
            }
            $username = $_POST['username'] ?? '';
            $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE username = ? AND TRIM(LOWER(status)) = 'pending'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE username = ? AND TRIM(LOWER(status)) = 'pending'");
                if ($stmt->execute([$username])) {
                    // Send a nice approval email
                    $login_link = "http://localhost/Software/index.php";
                    $subject = "Account Approved";
                    $body = "<h2>Account Approved</h2>
                            <p>Dear {$user['first_name']} {$user['last_name']},</p>
                            <p>Your account with Bradford Council Asset Management has been approved. You can now log in.</p>
                            <p><a href='$login_link'>Login Here</a></p>
                            <p>If you have any questions, please contact support at support@bradford.gov.uk.</p>
                            <p>Best regards,<br>Bradford Council Team</p>";
                    if (sendEmail($user['email'], $subject, $body)) {
                        error_log("Approval email sent to: {$user['email']}");
                    } else {
                        error_log("Failed to send approval email to: {$user['email']}");
                    }

                    $response['success'] = true;
                    $response['message'] = 'User approved successfully.';
                } else {
                    $response['message'] = 'Failed to approve user or user not found.';
                }
            } else {
                $response['message'] = 'User not found or not pending.';
            }
        } elseif ($action === 'decline_user') {
            if ($_SESSION['role'] !== 'admin') {
                $response['message'] = 'Unauthorized';
                echo json_encode($response);
                exit;
            }
            $username = $_POST['username'] ?? '';
            $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE username = ? AND TRIM(LOWER(status)) = 'pending'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE username = ? AND TRIM(LOWER(status)) = 'pending'");
                if ($stmt->execute([$username])) {
                    // Let the user know they’ve been declined
                    $subject = "Account Registration Declined";
                    $body = "<h2>Account Registration Declined</h2>
                            <p>Dear {$user['first_name']} {$user['last_name']},</p>
                            <p>Your account could not be approved due to incomplete information. Please contact support for assistance.</p>
                            <p>Email us at: <a href='mailto:support@bradford.gov.uk'>support@bradford.gov.uk</a></p>
                            <p>Best regards,<br>Bradford Council Team</p>";
                    if (sendEmail($user['email'], $subject, $body)) {
                        error_log("Rejection email sent to: {$user['email']}");
                    } else {
                        error_log("Failed to send rejection email to: {$user['email']}");
                    }

                    $response['success'] = true;
                    $response['message'] = 'User declined successfully.';
                } else {
                    $response['message'] = 'Failed to decline user or user not found.';
                }
            } else {
                $response['message'] = 'User not found or not pending.';
            }
        } elseif ($action === 'change_role') {
            if ($_SESSION['role'] !== 'admin') {
                $response['message'] = 'Unauthorized';
                echo json_encode($response);
                exit;
            }
            $username = $_POST['username'] ?? '';
            $new_role = $_POST['role'] ?? '';
            if (!in_array($new_role, ['user', 'admin'])) {
                $response['message'] = 'Invalid role.';
                echo json_encode($response);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE username = ?");
            if ($stmt->execute([$new_role, $username])) {
                $response['success'] = true;
                $response['message'] = 'Role updated successfully.';
            } else {
                $response['message'] = 'Failed to change role or user not found.';
            }
        } elseif ($action === 'change_department') {
            if ($_SESSION['role'] !== 'admin') {
                $response['message'] = 'Unauthorized';
                echo json_encode($response);
                exit;
            }
            $username = $_POST['username'] ?? '';
            $new_department = $_POST['department'] ?? '';
            $stmt = $pdo->prepare("UPDATE users SET department = ? WHERE username = ?");
            if ($stmt->execute([$new_department, $username])) {
                $response['success'] = true;
                $response['message'] = 'Department updated successfully.';
            } else {
                $response['message'] = 'Failed to change department or user not found.';
            }
        } elseif ($action === 'update_accessibility') {
            $high_contrast = isset($_POST['high_contrast']) ? (int)$_POST['high_contrast'] : 0;
            $font_size = $_POST['font_size'] ?? 'normal';
            $stmt = $pdo->prepare("UPDATE users SET high_contrast = ?, font_size = ? WHERE id = ?");
            if ($stmt->execute([$high_contrast, $font_size, $user_id])) {
                $response['success'] = true;
            } else {
                $response['message'] = 'Failed to update accessibility settings';
            }
        } elseif ($action === 'get_logs') {
            if ($_SESSION['role'] !== 'admin') {
                $response['message'] = 'Unauthorized';
                echo json_encode($response);
                exit;
            }
            // Fetch logs with usernames for admin view
            $stmt = $pdo->prepare("SELECT l.id, l.action, l.timestamp AS created_at, u.username 
                                   FROM logs l 
                                   LEFT JOIN users u ON l.user_id = u.id 
                                   ORDER BY l.timestamp DESC");
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['logs'] = $logs ?: [];
        } elseif ($action === 'get_assets') {
            if ($_SESSION['role'] !== 'admin') {
                $response['message'] = 'Unauthorized';
                echo json_encode($response);
                exit;
            }
            // Pull all assets with uploader info for admin
            $stmt = $pdo->prepare("SELECT a.id, a.name, a.latitude, a.longitude, a.category, a.uploaded_at, u.username AS uploaded_by_username 
                                   FROM assets a 
                                   LEFT JOIN users u ON a.uploaded_by = u.id 
                                   ORDER BY a.uploaded_at DESC");
            $stmt->execute();
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['assets'] = $assets ?: [];
        }
    }

    // Log successful actions (except for fetching logs or assets)
    if ($response['success'] && $action !== 'get_logs' && $action !== 'get_assets') {
        try {
            $stmt = $pdo->prepare("INSERT INTO logs (action, user_id) VALUES (?, ?)");
            $stmt->execute(["Action: $action by user $user_id", $user_id]);
        } catch (PDOException $e) {
            error_log("Failed to log action: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    // Catch any big errors and log them
    $response['message'] = 'Server error: ' . $e->getMessage();
    error_log("Server error in user_management.php: " . $e->getMessage());
}

echo json_encode($response);
?>