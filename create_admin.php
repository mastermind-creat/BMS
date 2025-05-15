<?php
require_once "config/database.php";

// Admin user details
$username = "admin";
$email = "admin@gla.com";
$password = "martha"; // You should change this password after first login
$role = "admin";

// Check if admin already exists
$sql = "SELECT id FROM users WHERE username = ? OR email = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_store_result($stmt);
        if(mysqli_stmt_num_rows($stmt) > 0){
            echo "Admin user already exists!";
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Insert admin user
$sql = "INSERT INTO users (username, email, password, role, approval_status) VALUES (?, ?, ?, ?, 'approved')";
if($stmt = mysqli_prepare($conn, $sql)){
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashed_password, $role);
    
    if(mysqli_stmt_execute($stmt)){
        echo "Admin user created successfully!<br>";
        echo "Username: " . $username . "<br>";
        echo "Password: " . $password . "<br>";
        echo "<strong>Please change the password after first login!</strong>";
    } else {
        echo "Error creating admin user: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?> 