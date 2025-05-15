<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Handle report generation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $report_type = $_POST["report_type"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    
    $report_data = array();
    
    switch($report_type) {
        case 'applications':
            $sql = "SELECT a.*, 
                           COUNT(*) as total_applications,
                           SUM(CASE WHEN application_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                           SUM(CASE WHEN application_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                           SUM(CASE WHEN application_status = 'pending' THEN 1 ELSE 0 END) as pending_count
                    FROM applicants a
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY DATE(created_at)";
            break;
            
        case 'distributions':
            $sql = "SELECT d.*, 
                           COUNT(*) as total_distributions,
                           SUM(amount) as total_amount,
                           SUM(CASE WHEN status = 'disbursed' THEN amount ELSE 0 END) as disbursed_amount,
                           SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
                    FROM distributions d
                    WHERE distribution_date BETWEEN ? AND ?
                    GROUP BY DATE(distribution_date)";
            break;
            
        case 'financial':
            $sql = "SELECT 
                           SUM(amount) as total_distributed,
                           COUNT(*) as total_transactions,
                           AVG(amount) as average_amount,
                           MAX(amount) as highest_amount,
                           MIN(amount) as lowest_amount
                    FROM distributions
                    WHERE distribution_date BETWEEN ? AND ?
                    AND status = 'disbursed'";
            break;
    }
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_assoc($result)){
                $report_data[] = $row;
            }
            
            // Save report to database
            $report_json = json_encode($report_data);
            $sql = "INSERT INTO reports (report_type, report_date, report_data, created_by) VALUES (?, CURDATE(), ?, ?)";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ssi", $report_type, $report_json, $_SESSION["id"]);
                mysqli_stmt_execute($stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - GLA Bursary Management</title>
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
                            <a class="nav-link active" href="reports.php">
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
                    <h2>Reports</h2>
                </div>

                <!-- Report Generation Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="reports.php" method="post" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Report Type</label>
                                <select class="form-select" name="report_type" required>
                                    <option value="applications">Applications Report</option>
                                    <option value="distributions">Distributions Report</option>
                                    <option value="financial">Financial Summary</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if(isset($report_data) && !empty($report_data)): ?>
                <!-- Report Results -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">
                                <?php
                                switch($report_type) {
                                    case 'applications':
                                        echo "Applications Report";
                                        break;
                                    case 'distributions':
                                        echo "Distributions Report";
                                        break;
                                    case 'financial':
                                        echo "Financial Summary";
                                        break;
                                }
                                ?>
                            </h5>
                            <button class="btn btn-success" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print Report
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <?php
                                        if($report_type == 'applications') {
                                            echo "<th>Date</th>";
                                            echo "<th>Total Applications</th>";
                                            echo "<th>Approved</th>";
                                            echo "<th>Rejected</th>";
                                            echo "<th>Pending</th>";
                                        } elseif($report_type == 'distributions') {
                                            echo "<th>Date</th>";
                                            echo "<th>Total Distributions</th>";
                                            echo "<th>Total Amount</th>";
                                            echo "<th>Disbursed Amount</th>";
                                            echo "<th>Pending Amount</th>";
                                        } else {
                                            echo "<th>Total Distributed</th>";
                                            echo "<th>Total Transactions</th>";
                                            echo "<th>Average Amount</th>";
                                            echo "<th>Highest Amount</th>";
                                            echo "<th>Lowest Amount</th>";
                                        }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach($report_data as $row) {
                                        echo "<tr>";
                                        if($report_type == 'applications') {
                                            echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                            echo "<td>" . $row['total_applications'] . "</td>";
                                            echo "<td>" . $row['approved_count'] . "</td>";
                                            echo "<td>" . $row['rejected_count'] . "</td>";
                                            echo "<td>" . $row['pending_count'] . "</td>";
                                        } elseif($report_type == 'distributions') {
                                            echo "<td>" . date('M d, Y', strtotime($row['distribution_date'])) . "</td>";
                                            echo "<td>" . $row['total_distributions'] . "</td>";
                                            echo "<td>KSh " . number_format($row['total_amount'], 2) . "</td>";
                                            echo "<td>KSh " . number_format($row['disbursed_amount'], 2) . "</td>";
                                            echo "<td>KSh " . number_format($row['pending_amount'], 2) . "</td>";
                                        } else {
                                            echo "<td>KSh " . number_format($row['total_distributed'], 2) . "</td>";
                                            echo "<td>" . $row['total_transactions'] . "</td>";
                                            echo "<td>KSh " . number_format($row['average_amount'], 2) . "</td>";
                                            echo "<td>KSh " . number_format($row['highest_amount'], 2) . "</td>";
                                            echo "<td>KSh " . number_format($row['lowest_amount'], 2) . "</td>";
                                        }
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Previous Reports -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Previous Reports</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Report Type</th>
                                        <th>Date Generated</th>
                                        <th>Generated By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT r.*, u.username 
                                           FROM reports r 
                                           JOIN users u ON r.created_by = u.id 
                                           ORDER BY r.created_at DESC 
                                           LIMIT 10";
                                    $result = mysqli_query($conn, $sql);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td>" . ucfirst($row['report_type']) . "</td>";
                                        echo "<td>" . date('M d, Y', strtotime($row['report_date'])) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                        echo "<td>
                                                <button type='button' class='btn btn-sm btn-info' onclick='viewReport(".$row['id'].")'>
                                                    <i class='bi bi-eye'></i> View
                                                </button>
                                            </td>";
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
    <script>
        function viewReport(reportId) {
            // Implement report viewing functionality
            alert('View report ' + reportId);
        }
    </script>
</body>
</html>