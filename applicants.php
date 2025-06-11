<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $first_name = trim($_POST["first_name"]);
            $last_name = trim($_POST["last_name"]);
            $email = trim($_POST["email"]);
            $phone = trim($_POST["phone"]);
            $address = trim($_POST["address"]);
            $institution = trim($_POST["institution"]);
            $course = trim($_POST["course"]);
            $year_of_study = trim($_POST["year_of_study"]);

            // Check for duplicate email or phone
            $check_sql = "SELECT id FROM applicants WHERE email = ? OR phone = ?";
            if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
                mysqli_stmt_bind_param($check_stmt, "ss", $email, $phone);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    $_SESSION["duplicate_error"] = "Email address or phone number already exists!";
                    mysqli_stmt_close($check_stmt);
                    header("location: applicants.php?duplicate=1");
                    exit;
                }
                mysqli_stmt_close($check_stmt);
            }

            $sql = "INSERT INTO applicants (first_name, last_name, email, phone, address, institution, course, year_of_study) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssssi", $first_name, $last_name, $email, $phone, $address, $institution, $course, $year_of_study);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION["success"] = "Applicant added successfully.";
                } else {
                    $_SESSION["error"] = "Error adding applicant.";
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($_POST['action'] == 'update_status') {
            $applicant_id = $_POST["applicant_id"];
            $status = $_POST["status"];

            $sql = "UPDATE applicants SET application_status = ? WHERE id = ?";

            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $status, $applicant_id);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION["success"] = "Status updated successfully.";
                } else {
                    $_SESSION["error"] = "Error updating status.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    header("location: applicants.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicants - GLA Bursary Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }

        .nav-link {
            color: rgba(255, 255, 255, .8);
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
                            <a class="nav-link active" href="applicants.php">
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
                        <?php if ($_SESSION["role"] === "admin"): ?>
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
                    <h2>Applicants Management</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#addApplicantModal">
                        <i class="bi bi-plus-circle"></i> Add New Applicant
                    </button>
                </div>

                <?php if (isset($_SESSION["success"])): ?>
                    <div class="alert alert-success">
                        <?php
                        echo $_SESSION["success"];
                        unset($_SESSION["success"]);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION["error"])): ?>
                    <div class="alert alert-danger">
                        <?php
                        echo $_SESSION["error"];
                        unset($_SESSION["error"]);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Applicants Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Institution</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM applicants ORDER BY created_at DESC";
                                    $result = mysqli_query($conn, $sql);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['institution']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['course']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['year_of_study']) . "</td>";
                                        echo "<td><span class='status-badge status-" . htmlspecialchars($row['application_status']) . "'>" .
                                            ucfirst(htmlspecialchars($row['application_status'])) . "</span></td>";
                                        echo "<td>
                                                <button type='button' class='btn btn-sm btn-info' data-bs-toggle='modal' data-bs-target='#viewApplicantModal' data-id='" . $row['id'] . "'>
                                                    <i class='bi bi-eye'></i>
                                                </button>
                                                <button type='button' class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#updateStatusModal' data-id='" . $row['id'] . "' data-status='" . $row['application_status'] . "'>
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

    <!-- Add Applicant Modal -->
    <div class="modal fade" id="addApplicantModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Applicant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="applicants.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Institution</label>
                            <input type="text" class="form-control" name="institution" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course</label>
                            <input type="text" class="form-control" name="course">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Year of Study</label>
                            <input type="text" class="form-control" name="year_of_study" min="1" max="6" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Applicant</button>
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
                    <h5 class="modal-title">Update Application Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="applicants.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="applicant_id" id="status_applicant_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
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
        document.getElementById('updateStatusModal').addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var status = button.getAttribute('data-status');

            document.getElementById('status_applicant_id').value = id;
            this.querySelector('select[name="status"]').value = status;
        });

        // SweetAlert for duplicate email/phone
        <?php if (isset($_GET['duplicate']) && isset($_SESSION["duplicate_error"])): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Duplicate Entry',
                text: '<?php echo $_SESSION["duplicate_error"]; ?>'
            });
        <?php unset($_SESSION["duplicate_error"]);
        endif; ?>
    </script>
</body>

</html>