<?php

@include ' config.php';

session_start();

if(!isset($_SESSION['admin_name'])){
    header('location:login_form.php');
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=Admin page">
    <title>Admin page</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="content">
            <h3><span>Admin Page</span></h3>
            <h1>Welcome <span><?php echo $_SESSION['admin_name'] ?></span></h1>
    
            <a href="admin_users.php" class="btn">Users Panel</a>
            <a href="dashB.html" class="btn">Dashboard Panel</a>
            <a href="login_form.php" class="btn">Logout</a>

        </div>
    </div>
    
</body>
</html>