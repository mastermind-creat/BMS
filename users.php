<?php
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Handle user approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    if($action == 'approve' || $action == 'reject') {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        $sql = "UPDATE users SET approval_status = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $status, $user_id);
            
            if(mysqli_stmt_execute($stmt)){
                $_SESSION["success"] = "User has been " . $status . " successfully.";
            } else {
                $_SESSION["error"] = "Error updating user status.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - GLA Bursary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .nav-link {
            color: rgba(255,255,255,.8);
        }
        .nav-link:hover {
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-pending {
            background-color: #ffd700;
            color: #000;
        }
        .status-approved {
            background-color: #28a745;
            color: #fff;
        }
        .status-rejected {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4>GLA Bursary</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="applicants.php">
                                <i class="bi bi-people"></i> Applicants
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="distributions.php">
                                <i class="bi bi-cash"></i> Fund Distribution
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="bi bi-file-text"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="users.php">
                                <i class="bi bi-person-gear"></i> User Management
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>User Management</h2>
                    <div>
                        Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!
                    </div>
                </div>

                <?php if(isset($_SESSION["success"])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION["success"];
                        unset($_SESSION["success"]);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION["error"])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION["error"];
                        unset($_SESSION["error"]);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM users ORDER BY created_at DESC";
                                    $result = mysqli_query($conn, $sql);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                        echo "<td>" . ucfirst(htmlspecialchars($row['role'])) . "</td>";
                                        echo "<td><span class='status-badge status-" . htmlspecialchars($row['approval_status']) . "'>" . 
                                             ucfirst(htmlspecialchars($row['approval_status'])) . "</span></td>";
                                        echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                        echo "<td>";
                                        if($row['approval_status'] === 'pending') {
                                            echo "<form method='post' style='display: inline;'>";
                                            echo "<input type='hidden' name='user_id' value='" . $row['id'] . "'>";
                                            echo "<input type='hidden' name='action' value='approve'>";
                                            echo "<button type='submit' class='btn btn-sm btn-success me-2'>Approve</button>";
                                            echo "</form>";
                                            
                                            echo "<form method='post' style='display: inline;'>";
                                            echo "<input type='hidden' name='user_id' value='" . $row['id'] . "'>";
                                            echo "<input type='hidden' name='action' value='reject'>";
                                            echo "<button type='submit' class='btn btn-sm btn-danger'>Reject</button>";
                                            echo "</form>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 