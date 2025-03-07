<?php
 
@include 'config.php';

// Check if the registration form is submitted
if(isset($_POST['submit'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT); // Securely hash password - bcrypt
    $cpass = password_hash($_POST['cpassword'], PASSWORD_BCRYPT); // Securely hash confirm password   
    $user_type = $_POST['user_type'];

    $select = "SELECT * FROM user_form WHERE email = '$email'";
    $result = mysqli_query($conn, $select);

    // If user already exists - add an error message
    if(mysqli_num_rows($result) > 0 ){
        $error[] = 'User already exists';
    } else {
        if (!password_verify($_POST['password'], $cpass)) {
            $error[] = 'Passwords do not match!';
        } else {
            // Insert user data into the database with is_approved = 0 (pending admin approval)
            $insert = "INSERT INTO user_form(name, email, password, user_type, is_approved) 
                       VALUES('$name', '$email', '$pass', '$user_type', 0)";
            if(mysqli_query($conn, $insert)){
                $success = 'Registration successful! Wait for admin approval before logging in.';
            } else {
                $error[] = 'Registration failed, please try again.';
            }
        }
    }
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Form</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Registration Form Container -->
    <div class="form_container">
        <form action="" method="post">
            <h3>Register now</h3>
            <?php
            // Display error messages 
            if(isset($error)){
                foreach($error as $err){
                    echo '<span class="error-msg">'.$err.'</span>';
                }
            }

            // Display success message
            if(isset($success)){
                echo '<span class="success-msg">'.$success.'</span>';
            }
            ?>
            <input type="text" name="name" required placeholder="Enter your name">
            <input type="email" name="email" required placeholder="Enter your email">
            <input type="password" name="password" required placeholder="Enter your password">
            <input type="password" name="cpassword" required placeholder="Re-enter your password">
            <select name="user_type">
                <option value="user">User</option>
            </select>
            <input type="submit" name="submit" value="Register now" class="form-btn">
            <p>Already have an account? <a href="login_form.php">Login now</a></p>
        </form>
    </div>
</body>
</html>
