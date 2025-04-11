<?php
 include 'config.php';
session_start();
ob_start();

 function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Handle user update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    //  check user input
    if (!isset($_POST['user_id']) || !isset($_POST['name']) || !isset($_POST['email'])) {
        die("Invalid request.");
    }

    $user_id = (int) $_POST['user_id'];
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $userType = sanitize($_POST['user_type']);

    // Check if email exists for another user
    $stmt = $conn->prepare("SELECT id FROM user_form WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Email is already in use.";
    } else {
        // Update user information
        if ($password) {
            $stmt = $conn->prepare("UPDATE user_form SET name=?, email=?, password=?, user_type=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $password, $userType, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE user_form SET name=?, email=?, user_type=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $email, $userType, $user_id);
        }

        if ($stmt->execute()) {
            echo "User updated successfully.";
        } else {
            echo "Error updating user: " . $conn->error;
        }
    }
    $stmt->close();
}

// Handle user approval
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_user'])) {
    $user_id = (int) $_POST['user_id'];

    $stmt = $conn->prepare("UPDATE user_form SET is_approved = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $stmt->close();
        // Redirect after approval
        header("Location: admin_users.php");
        exit();
    } else {
        echo "Error updating approval: " . $stmt->error;
    }
}

// Handle user denial  
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deny_user'])) {
    $user_id = (int) $_POST['user_id'];

    $stmt = $conn->prepare("DELETE FROM user_form WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $stmt->close();
        // Redirect after deletion
        header("Location: admin_users.php");
        exit();
    } else {
        echo "Error deleting user: " . $stmt->error;
    }
}

// Fetch users pending approval
$result_pending = $conn->query("SELECT id, name, email FROM user_form WHERE is_approved = 0");

// Fetch all approved users
$result = $conn->query("SELECT id, name, email, user_type FROM user_form WHERE is_approved = 1");
?>

 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Management</title>
    <style>
       
        .addUsers-container {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }

         .addUsers-container label {
            display: block;
            margin-bottom: 10px;
        }

         .addUsers-container input[type="text"],
        .addUsers-container input[type="email"],
        .addUsers-container input[type="password"],
        .addUsers-container input[type="submit"],
        .addUsers-container select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #555;
            border-radius: 5px;
            box-sizing: border-box;
            background-color: #555;
            color: #fff;
        }

         .addUsers-container input[type="text"]:hover,
        .addUsers-container input[type="email"]:hover,
        .addUsers-container input[type="password"]:hover,
        .addUsers-container input[type="submit"]:hover,
        .addUsers-container select:hover,
        .addUsers-container input[type="text"]:focus,
        .addUsers-container input[type="email"]:focus,
        .addUsers-container input[type="password"]:focus,
        .addUsers-container input[type="submit"]:focus,
        .addUsers-container select:focus {
            border-color: #333;
        }

         .button {
            display: inline-block;
            padding: 8px 15px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            margin: 5px;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #0056b3;
        }

         .approve-btn {
            background-color: #28a745;
        }

        .approve-btn:hover {
            background-color: #218838;
        }

         .deny-btn {
            background-color: #e53935;
        }

        .deny-btn:hover {
            background-color: #d32f2f;
        }

         .update-btn {
            background-color: #007bff;
        }

        .update-btn:hover {
            background-color: #0056b3;
        }

         .delete-btn {
            background-color: #dc3545;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

         .logout-btn,
        .back-btn {
            background-color: #6c757d;
        }

        .logout-btn:hover,
        .back-btn:hover {
            background-color: #5a6268;
        }

         table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

<h2>Pending User Approvals</h2>
<table>
    <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Action</th>
    </tr>
    <?php
    // Loop through and display pending users
    while ($pending_user = $result_pending->fetch_assoc()) {
    ?>
        <tr>
            <td><?php echo htmlspecialchars($pending_user['name']); ?></td>
            <td><?php echo htmlspecialchars($pending_user['email']); ?></td>
            <td>
                <form method="POST" action="">
                    <input type="hidden" name="user_id" value="<?php echo $pending_user['id']; ?>">
                     <button type="submit" name="approve_user" class="button approve-btn">Approve</button>
                     <button type="submit" name="deny_user" class="button deny-btn" onclick="return confirm('Are you sure you want to deny and delete this user?');">Deny</button>
                </form>
            </td>
        </tr>
    <?php
    }
    ?>
</table>

<h2>Existing Users (Approved)</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>User Type</th>
        <th>Action</th>
    </tr>
    <?php
    // Loop through and display all approved users
    while ($user = $result->fetch_assoc()) {
    ?>
        <tr>
            <td><?php echo $user['id']; ?></td>
            <td><?php echo htmlspecialchars($user['name']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars($user['user_type']); ?></td>
            <td>
                <a href="admin_users.php?update_user=<?php echo $user['id']; ?>" class="button update-btn">Update</a> | 
                <a href="admin_users.php?delete_user=<?php echo $user['id']; ?>" class="button delete-btn" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
            </td>
        </tr>
    <?php
    }
    ?>
</table>

 <div>
    <a href="logout.php" class="button logout-btn">Logout</a>
    <a href="admin_page.php" class="button back-btn">Go Back</a>
</div>

<?php
// Update and Delete User Handling
if (isset($_GET['update_user'])) {
    $user_id = (int) $_GET['update_user'];
    $result_user = $conn->query("SELECT * FROM user_form WHERE id = $user_id");
    $user = $result_user->fetch_assoc();
    ?>
    <h3>Update User</h3>
    <form action="admin_users.php" method="POST">
        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password"><br>

        <label for="user_type">User Type:</label>
        <select id="user_type" name="user_type" required>
            <option value="user" <?php echo ($user['user_type'] == 'user') ? 'selected' : ''; ?>>User</option>
            <option value="admin" <?php echo ($user['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
        </select><br>

        <input type="submit" name="update_user" value="Update User" class="button update-btn">
    </form>
    <?php
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $user_id = (int) $_GET['delete_user'];
    $stmt = $conn->prepare("DELETE FROM user_form WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        // Redirect after deletion
        header("Location: admin_users.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
    $stmt->close();
}
ob_end_flush();
?>

</body>
</html>
