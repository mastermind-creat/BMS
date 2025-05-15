<?php
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    
    $sql = "SELECT id, username, password, role, approval_status FROM users WHERE username = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $username);
        
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role, $approval_status);
                if(mysqli_stmt_fetch($stmt)){
                    if(password_verify($password, $hashed_password)){
                        if($approval_status === 'pending') {
                            $_SESSION["error"] = "Your account is pending approval. Please wait for admin approval.";
                            header("location: ../index.php");
                            exit;
                        } elseif($approval_status === 'rejected') {
                            $_SESSION["error"] = "Your account has been rejected. Please contact the administrator.";
                            header("location: ../index.php");
                            exit;
                        }
                        
                        session_start();
                        
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $username;
                        $_SESSION["role"] = $role;
                        
                        header("location: ../dashboard.php");
                    } else {
                        $_SESSION["error"] = "Invalid username or password.";
                        header("location: ../index.php");
                    }
                }
            } else {
                $_SESSION["error"] = "Invalid username or password.";
                header("location: ../index.php");
            }
        } else {
            $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
            header("location: ../index.php");
        }

        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);
?> 