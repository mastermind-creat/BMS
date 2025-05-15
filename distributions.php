<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['action'])) {
        if($_POST['action'] == 'add') {
            $applicant_id = $_POST["applicant_id"];
            $amount = $_POST["amount"];
            $semester = $_POST["semester"];
            $academic_year = $_POST["academic_year"];
            $distribution_date = $_POST["distribution_date"];

            $sql = "INSERT INTO distributions (applicant_id, amount, semester, academic_year, distribution_date, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "idsssi", $applicant_id, $amount, $semester, $academic_year, $distribution_date, $_SESSION["id"]);
                
                if(mysqli_stmt_execute($stmt)){
                    $_SESSION["success"] = "Distribution added successfully.";
                } else {
                    $_SESSION["error"] = "Error adding distribution.";
                }
                mysqli_stmt_close($stmt);
            }
        } elseif($_POST['action'] == 'update_status') {
            $distribution_id = $_POST["distribution_id"];
            $status = $_POST["status"];

            $sql = "UPDATE distributions SET status = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "si", $status, $distribution_id);
                
                if(mysqli_stmt_execute($stmt)){
                    $_SESSION["success"] = "Status updated successfully.";
                } else {
                    $_SESSION["error"] = "Error updating status.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    header("location: distributions.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Distribution - GLA Bursary Management</title>
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
                            <a class="nav-link active" href="distributions.php">
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
                    <h2>Fund Distribution Management</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDistributionModal">
                        <i class="bi bi-plus-circle"></i> Add New Distribution
                    </button>
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

                <!-- Distributions Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Amount</th>
                                        <th>Semester</th>
                                        <th>Academic Year</th>
                                        <th>Distribution Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT d.*, CONCAT(a.first_name, ' ', a.last_name) as applicant_name 
                                           FROM distributions d 
                                           JOIN applicants a ON d.applicant_id = a.id 
                                           ORDER BY d.created_at DESC";
                                    $result = mysqli_query($conn, $sql);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['applicant_name']) . "</td>";
                                        echo "<td>KSh " . number_format($row['amount'], 2) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['academic_year']) . "</td>";
                                        echo "<td>" . date('M d, Y', strtotime($row['distribution_date'])) . "</td>";
                                        echo "<td><span class='status-badge status-" . htmlspecialchars($row['status']) . "'>" . 
                                             ucfirst(htmlspecialchars($row['status'])) . "</span></td>";
                                        echo "<td>
                                                <button type='button' class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#updateStatusModal' data-id='".$row['id']."' data-status='".$row['status']."'>
                                                    <i class='bi bi-pencil'></i>
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

    <!-- Add Distribution Modal -->
    <div class="modal fade" id="addDistributionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Distribution</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="distributions.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Applicant</label>
                            <select class="form-select" name="applicant_id" required>
                                <option value="">Select Applicant</option>
                                <?php
                                $sql = "SELECT id, first_name, last_name FROM applicants WHERE application_status = 'approved'";
                                $result = mysqli_query($conn, $sql);
                                while($row = mysqli_fetch_assoc($result)) {
                                    echo "<option value='".$row['id']."'>".htmlspecialchars($row['first_name']." ".$row['last_name'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (KSh)</label>
                            <input type="number" class="form-control" name="amount" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Semester</label>
                            <select class="form-select" name="semester" required>
                                <option value="Semester 1">Semester 1</option>
                                <option value="Semester 2">Semester 2</option>
                                <option value="Semester 3">Semester 3</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Academic Year</label>
                            <input type="text" class="form-control" name="academic_year" placeholder="2023/2024" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Distribution Date</label>
                            <input type="date" class="form-control" name="distribution_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Distribution</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Distribution Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="distributions.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="distribution_id" id="status_distribution_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="disbursed">Disbursed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update status modal
        document.getElementById('updateStatusModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var status = button.getAttribute('data-status');
            
            document.getElementById('status_distribution_id').value = id;
            this.querySelector('select[name="status"]').value = status;
        });
    </script>
</body>
</html> 