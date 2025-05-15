<?php
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Validate input
    if(empty($username) || empty($email) || empty($password) || empty($confirm_password)){
        $_SESSION["error"] = "Please fill all required fields.";
        header("location: ../index.php");
        exit;
    }
    
    if($password != $confirm_password){
        $_SESSION["error"] = "Passwords do not match.";
        header("location: ../index.php");
        exit;
    }
    
    // Check if username exists
    $sql = "SELECT id FROM users WHERE username = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $username);
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) > 0){
                $_SESSION["error"] = "This username is already taken.";
                header("location: ../index.php");
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Check if email exists
    $sql = "SELECT id FROM users WHERE email = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $email);
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) > 0){
                $_SESSION["error"] = "This email is already registered.";
                header("location: ../index.php");
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Insert new user with pending status
    $sql = "INSERT INTO users (username, email, password, role, approval_status) VALUES (?, ?, ?, 'staff', 'pending')";
    if($stmt = mysqli_prepare($conn, $sql)){
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);
        
        if(mysqli_stmt_execute($stmt)){
            $_SESSION["success"] = "Registration successful! Please wait for admin approval before logging in.";
            header("location: ../index.php");
        } else {
            $_SESSION["error"] = "Something went wrong. Please try again later.";
            header("location: ../index.php");
        }
        
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);
?> 