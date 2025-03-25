<?php
require_once("includes/setup.php");

$email = "";
$password = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        

        $query = "SELECT * FROM users WHERE email = '$email' ";
        $result = mysqli_query($connection, $query);

        if ($result) {
            if(mysqli_num_rows($result) > 0) {
                $row=mysqli_fetch_assoc($result);
                $db_hashed_password = $row['password'];
                
                if (password_verify($password, $db_hashed_password)) {
                    if ($row['status'] === 'Verified') {
                        session_start(); 
                        $type=$row['role'];
                        $_SESSION['login'] = true;

                        $_SESSION['user_id'] = $row['user_id']; 
                        $_SESSION['department_id'] = $row['department_id'];
                        $_SESSION['email'] = $row['email'];
                        $_SESSION['type'] = $row['role'];

                        switch ($type) {
                            case 'Admin':
                                echo "<script>location.href='admin.php'</script>";
                                 $_SESSION['admin'] = true;
                                exit();
                                break;
                            case 'General User':
                                echo "<script>location.href='home.php'</script>";
                                exit();
                                break;
                        }
                    } else {
                        $error_message = "Your account is not yet verified.";
                    }
                } else {
                    $error_message = "Invalid email or password.";
                }
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            $error_message = "ERROR: Could not execute $query. " . mysqli_error($connection);
        }
    }
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <Title>Login|Bradford Council</Title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .error-banner {
            display: <?php echo ($error_message) ? 'block' : 'none'; ?>;
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body class="main">

    <?php require_once("includes/navbar.php"); ?>

    <div class="error-banner">
        <?php echo $error_message; ?>
    </div>

    <div class="log">
        <form action="login.php" method="post">
            <label class="placeholder">Email</label>
            <input class="log input" type="email" name="email"/>
            <label class="placeholder">Password</label>
            <input class="log input" type="password" name="password">
            <button class="log button" type="submit">Log In</button>
        </form>

    </div>

    <div class="register-text">
        Don't have an account? <a href="register.php">Register here</a>
    </div>

    <div class="faqs-text">
        Have any questions? <a href="faqs.php">Visit FAQS here</a>
    </div>

    <div class="fp-text">
        <a href="forgottenpass.php">Forgotten Password?</a>
    </div>

</body>

<?php require_once("includes/footer.php"); ?>
