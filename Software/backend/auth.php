<?php
header('Content-Type: application/json');
require 'config.php';
require 'email_config.php';

session_start();

// Basic response setup for JSON output
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $credentials = $_POST['credentials'] ?? '';
        $password = $_POST['password'] ?? '';

        error_log("Login attempt - Credentials: $credentials, Password: $password");

        // Check if user exists by username or email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?)");
        $stmt->execute([$credentials, $credentials]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            error_log("User found: " . json_encode($user));
            if ($user['status'] === 'pending') {
                $response['message'] = 'Your account is pending admin approval.';
                error_log("Login failed - Account pending approval for user: $credentials");
            } elseif (password_verify($password, $user['password'])) {
                // Login worked, set session vars and send success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $response['success'] = true;
                $response['role'] = $user['role'];
                $response['username'] = $user['username'];
                error_log("Login successful for user: $credentials");
            } else {
                $response['message'] = 'Invalid email, username, or password';
                error_log("Login failed - Invalid password for user: $credentials");
            }
        } else {
            $response['message'] = 'Invalid email, username, or password';
            error_log("Login failed - Invalid credentials");
        }
    } elseif ($action === 'signup') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $department = $_POST['department'] ?? '';

        // Quick email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Enter a valid email address';
            echo json_encode($response);
            exit;
        }

        // Password strength check - gotta make sure it's decent
        if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
            $response['message'] = 'Password must be at least 8 characters with an uppercase letter, a number, and a special character';
            echo json_encode($response);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $response['message'] = 'Email or username already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, department, first_name, last_name, status) VALUES (?, ?, ?, 'user', ?, ?, ?, 'pending')");
            if ($stmt->execute([$username, $email, $hashed_password, $department, $first_name, $last_name])) {
                // Send a nice welcome email
                $subject = "Welcome to Bradford Council Asset Management";
                $body = "<h2>Thank You for Signing Up!</h2>
                        <p>Dear $first_name $last_name,</p>
                        <p>Thank you for signing up with Bradford Council Asset Management! Your account is pending admin approval. We’ll notify you once it’s approved.</p>
                        <p>Best regards,<br>Bradford Council Team</p>";
                if (sendEmail($email, $subject, $body)) {
                    $response['success'] = true;
                    $response['message'] = 'Your account has been created and is pending admin approval. Check your email for confirmation.';
                    error_log("Signup successful and email sent for user: $username, email: $email");
                } else {
                    $response['success'] = true;
                    $response['message'] = 'Your account has been created and is pending admin approval. (Email sending failed)';
                    error_log("Signup successful but email failed for user: $username, email: $email");
                }
            } else {
                $response['message'] = 'Signup failed';
                error_log("Signup failed for user: $username");
            }
        }
    } elseif ($action === 'logout') {
        // Simple logout, just kill the session
        session_destroy();
        $response['success'] = true;
        error_log("User logged out");
    } elseif ($action === 'reset_password') {
        $email = $_POST['email'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Create a reset token and set it to expire in an hour
            $token = bin2hex(random_bytes(16));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
            $stmt->execute([$email, $token, $expires_at, $token, $expires_at]);

            // Email the reset link to the user
            $reset_link = "http://localhost:8083/Software/reset_password.php?token=$token"; // Updated to localhost
            $subject = "Password Reset Request";
            $body = "<h2>Password Reset</h2>
                    <p>We received a request to reset your password. Click the link below to reset it:</p>
                    <p><a href='$reset_link'>Reset Password</a></p>
                    <p>This link will expire in 1 hour. If you didn’t request this, ignore this email.</p>
                    <p>Best regards,<br>Bradford Council Team</p>";
            if (sendEmail($email, $subject, $body)) {
                $response['success'] = true;
                $response['message'] = 'Password reset link sent! Check your email.';
                error_log("Password reset email sent to: $email");
            } else {
                $response['message'] = 'Failed to send reset email.';
                error_log("Password reset email failed for: $email");
            }
        } else {
            $response['message'] = 'Email not found';
            error_log("Password reset failed - email not found: $email");
        }
    }
}

echo json_encode($response);
?>
