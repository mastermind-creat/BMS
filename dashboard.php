<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GLA Bursary Management</title>
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
        .status-disbursed {
            background-color: #17a2b8;
            color: #fff;
        }
        .status-cancelled {
            background-color: #6c757d;
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
                            <a class="nav-link active" href="dashboard.php">
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
                        <?php if($_SESSION["role"] === "admin"): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-person-gear"></i> User Management
                            </a>
                        </li>
                        <?php endif; ?>
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
                    <h2>Dashboard</h2>
                    <div>
                        Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Applicants</h5>
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM applicants";
                                $result = mysqli_query($conn, $sql);
                                $row = mysqli_fetch_assoc($result);
                                ?>
                                <h2 class="card-text"><?php echo $row['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Distributions</h5>
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM distributions";
                                $result = mysqli_query($conn, $sql);
                                $row = mysqli_fetch_assoc($result);
                                ?>
                                <h2 class="card-text"><?php echo $row['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending Applications</h5>
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM applicants WHERE application_status = 'pending'";
                                $result = mysqli_query($conn, $sql);
                                $row = mysqli_fetch_assoc($result);
                                ?>
                                <h2 class="card-text"><?php echo $row['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Recent Applications
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Institution</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT * FROM applicants ORDER BY created_at DESC LIMIT 5";
                                            $result = mysqli_query($conn, $sql);
                                            while($row = mysqli_fetch_assoc($result)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['institution']) . "</td>";
                                                echo "<td><span class='status-badge status-" . htmlspecialchars($row['application_status']) . "'>" . 
                                                     ucfirst(htmlspecialchars($row['application_status'])) . "</span></td>";
                                                echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Recent Distributions
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Applicant</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT d.*, CONCAT(a.first_name, ' ', a.last_name) as applicant_name 
                                                   FROM distributions d 
                                                   JOIN applicants a ON d.applicant_id = a.id 
                                                   ORDER BY d.created_at DESC LIMIT 5";
                                            $result = mysqli_query($conn, $sql);
                                            while($row = mysqli_fetch_assoc($result)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['applicant_name']) . "</td>";
                                                echo "<td>KSh " . number_format($row['amount'], 2) . "</td>";
                                                echo "<td><span class='status-badge status-" . htmlspecialchars($row['status']) . "'>" . 
                                                     ucfirst(htmlspecialchars($row['status'])) . "</span></td>";
                                                echo "<td>" . date('M d, Y', strtotime($row['distribution_date'])) . "</td>";
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 