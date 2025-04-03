<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'backend/config.php';

$message = '';
$token_valid = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Validate token
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reset) {
        $token_valid = true;
    } else {
        $message = 'Invalid or expired token. Please request a new password reset.';
    }
} else {
    $message = 'No token provided. Please use the link from your email.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } elseif (strlen($new_password) < 8 || !preg_match("/[A-Z]/", $new_password) || !preg_match("/[0-9]/", $new_password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $new_password)) {
        $message = 'Password must be at least 8 characters with an uppercase letter, a number, and a special character.';
    } else {
        // Update the user's password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = (SELECT email FROM password_resets WHERE token = ?)");
        if ($stmt->execute([$hashed_password, $token])) {
            // Delete the used token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            $message = 'Password reset successfully! <a href="index.php">Login here</a>.';
            $token_valid = false; // Hide form after success
        } else {
            $message = 'Failed to reset password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bradford Council | Reset Password</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .reset-container {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            margin: 20px auto;
        }
        .reset-container input {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .reset-container button {
            padding: 10px 20px;
            margin: 10px 0;
            background-color: #006871;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .reset-container button:hover {
            background-color: #004d54;
        }
        .high-contrast .reset-container {
            background: #000;
            color: #fff;
            border: 2px solid #fff;
        }
        .high-contrast .reset-container input {
            background: #000;
            color: #fff;
            border-color: #fff;
        }
        .high-contrast .reset-container button {
            background-color: #fff;
            color: #000;
        }
        .message { color: #ff0000; margin: 10px 0; }
        .message a { color: #006871; text-decoration: underline; }
    </style>
</head>
<body>
    <?php 
    $headerPath = dirname(__DIR__) . '/software/header.php';
    if (file_exists($headerPath)) {
        include $headerPath;
    } else {
        echo "<p style='color: red;'>Error: header.php not found at '$headerPath'</p>";
    }
    ?>

    <div id="content">
        <section id="reset-password" class="section active">
            <div class="reset-container">
                <h2>Reset Password</h2>
                <?php if ($token_valid): ?>
                    <form method="POST" action="">
                        <input type="password" name="new_password" placeholder="New Password" required>
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                        <button type="submit">Reset Password</button>
                    </form>
                <?php endif; ?>
                <?php if ($message): ?>
                    <p class="message"><?php echo $message; ?></p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php 
    $footerPath = dirname(__DIR__) . '/software/footer.php';
    if (file_exists($footerPath)) {
        include $footerPath;
    } else {
        echo "<p style='color: red;'>Error: footer.php not found at '$footerPath'</p>";
    }
    ?>

    <script src="../js/main.js"></script>
</body>
</html>