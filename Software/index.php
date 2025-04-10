<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bradford Council | Asset Management</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        #welcome-message {
            display: none; /* Hidden by default, shown via JavaScript */
        }
        #welcome-message h2 {
            font-size: 2em;
            margin-bottom: 10px;
            color: #333; /* Darker color for better contrast on white background */
        }
        #welcome-message p {
            font-size: 1.2em;
            color: #666; /* Slightly lighter color for subheading */
        }
        #welcome-message .container {
            text-align: center; /* Center the text inside the container */
            padding: 30px; /* Add padding to match typical container styling */
        }
        /* Add success message styling */
        #forgot-success {
            display: none;
            color: green;
            text-align: center;
            margin-top: 10px;
        }
        /* Add styling for the eye icon */
        .input-wrapper {
            position: relative;
        }
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
    <?php include 'header.php'; ?>

    <div id="content">
        <section id="home" class="section">
            <!-- Welcome message section with container class -->
            <div id="welcome-message">
                <div class="container">
                    <h2 id="welcome-text"></h2>
                    <p>Manage your Assets with Bradford Council</p>
                </div>
            </div>

            <div id="login-box" class="container">
                <h2>Login</h2>
                <div class="input-wrapper">
                    <input type="text" placeholder="Email or Username" id="login-credentials">
                    <span id="login-error" class="error-message"></span>
                </div>
                <div class="input-wrapper">
                    <input type="password" placeholder="Password" id="login-password">
                    <span class="toggle-password" onclick="togglePassword('login-password')">👁️</span>
                    <span id="password-error" class="error-message"></span>
                </div>
                <button onclick="login()">Login</button>
                <button onclick="showSignup()">Sign Up</button>
                <p class="toggle" onclick="showForgotPassword()">Forgot Password?</p>
            </div>

            <div id="signup-box" class="container" style="display: none;">
                <h2>Sign Up</h2>
                <div class="input-wrapper">
                    <input type="text" placeholder="First Name" id="signup-first-name">
                </div>
                <div class="input-wrapper">
                    <input type="text" placeholder="Last Name" id="signup-last-name">
                </div>
                <div class="input-wrapper">
                    <input type="text" placeholder="Username" id="signup-username">
                    <span id="signup-error" class="error-message"></span>
                </div>
                <div class="input-wrapper">
                    <input type="email" placeholder="Email" id="signup-email">
                </div>
                <select id="signup-department">
                    <option value="" disabled selected>Select Department</option>
                    <option value="Department of Adult Social Care">Department of Adult Social Care</option>
                    <option value="Department of Children’s Services">Department of Children’s Services</option>
                    <option value="Department of Corporate Resources">Department of Corporate Resources</option>
                    <option value="Department of Place">Department of Place</option>
                </select>
                <div class="input-wrapper">
                    <input type="password" placeholder="Password" id="signup-password">
                    <span class="toggle-password" onclick="togglePassword('signup-password')">👁️</span>
                    <span id="signup-password-error" class="error-message"></span>
                </div>
                <div class="input-wrapper">
                    <input type="password" placeholder="Confirm Password" id="signup-confirm-password">
                    <span class="toggle-password" onclick="togglePassword('signup-confirm-password')">👁️</span>
                </div>
                <button onclick="signup()">Sign Up</button>
                <p class="toggle" onclick="showLogin()">Back to Login</p>
            </div>

            <div id="forgot-box" class="container" style="display: none;">
                <h3>Forgot Password</h3>
                <input type="email" placeholder="Email" id="forgot-email">
                <button onclick="resetPassword()">Reset Password</button>
                <p id="forgot-success">A password reset link has been sent to your email.</p>
                <p class="toggle" onclick="showLogin()">Back to Login</p>
            </div>
        </section>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>