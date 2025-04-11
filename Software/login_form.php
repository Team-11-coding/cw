<?php
@include 'config.php';

// Start the session
session_start();

$error = array();

// Check if the login form has been submitted
if(isset($_POST['submit'])){
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Prepare and execute the query to check user credentials
    $stmt = $conn->prepare("SELECT * FROM user_form WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If user credentials are found
    if($result->num_rows > 0){
        $row = $result->fetch_assoc();

        // Check if the account is approved
        if ($row['is_approved'] == 0) {
            $error[] = 'Your account is pending approval. Please wait for an admin to approve your account.';
        } else {
            // Verify the hashed password
            if(password_verify($password, $row['password'])){
                
                // Checking if the user is an admin
                if($row['user_type'] == 'admin'){
                    $_SESSION['admin_name'] = $row['name'];
                    header('location:admin_page.php');
                    exit();
                }
                
                // Checking if the user is a normal user
                elseif($row['user_type'] == 'user'){
                    $_SESSION['user_name'] = $row['name'];
                    header('location:dashB.html');
                    exit();
                }
            } else {
                $error[] = 'Incorrect email or password!';
            }
        }
    } else {
        $error[] = 'Incorrect email or password!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form_container">
        <form action="" method="post">
            <h3>Login now</h3>
            <?php
             if(!empty($error)){
                foreach($error as $error_msg){
                    echo '<span class="error-msg">' . $error_msg . '</span>';
                }
            }
            ?>
            <input type="email" name="email" required placeholder="Enter your email">
            <input type="password" name="password" required placeholder="Enter your password">
            <input type="submit" name="submit" value="Login now" class="form-btn">
            <p>Don't have an account? <a href="register_form.php">Register now</a></p>
        </form>
    </div>
</body>
</html>
