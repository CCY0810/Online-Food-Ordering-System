<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if($_SESSION['user_role'] != 'admin') {
    header("Location: mainPage.php");
    exit();
}

require_once("config.php");

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = addUser($conn, $_POST);
                break;
            case 'update':
                $result = updateUser($conn, $_POST);
                break;
            case 'delete':
                $result = deleteUser($conn, $_POST['userID']);
                break;
            case 'bulk_delete':
                $result = bulkDeleteUsers($conn, $_POST['selected_users']);
                break;
        }
        $message = $result['message'];
        $messageType = $result['type'];
    }
}

// Function add new user
function addUser($conn, $data)
{
    $userID = $conn->real_escape_string($data['userID']);
    $role = $conn->real_escape_string($data['role']);
    $name = $conn->real_escape_string($data['name']);
    $age = intval($data['age']);
    $email = $conn->real_escape_string($data['email']);
    $contactNumber = $conn->real_escape_string($data['contactNumber']);
    $address = $conn->real_escape_string($data['address']);

    //this only check the userID and email exits or not in database 
    $checkSql = "SELECT userID, email FROM User WHERE userID = '$userID' OR email = '$email'";
    $checkResult = $conn->query($checkSql);

    if ($checkResult->num_rows > 0) {
        return ['message' => 'User ID or Email already exists!', 'type' => 'error'];
    }

    $sql = "INSERT INTO User (userID, role, name, age, email, contactNumber, address) 
            VALUES ('$userID', '$role', '$name', $age, '$email', '$contactNumber', '$address')";

    if ($conn->query($sql) === TRUE) {
        return ['message' => 'User added successfully!', 'type' => 'success'];
    } else {
        return ['message' => 'Error: ' . $conn->error, 'type' => 'error'];
    }
}

// Function to update user (after edit the user info)
function updateUser($conn, $data)
{
    $userID = $conn->real_escape_string($data['userID']);
    $role = $conn->real_escape_string($data['role']);
    $name = $conn->real_escape_string($data['name']);
    $age = intval($data['age']);
    $email = $conn->real_escape_string($data['email']);
    $contactNumber = $conn->real_escape_string($data['contactNumber']);
    $address = $conn->real_escape_string($data['address']);

    $sql = "INSERT INTO User (userID, role, name, age, email, contactNumber, address) 
            VALUES ('$userID', '$role', '$name', $age, '$email', '$contactNumber', '$address')";


    $sql = "UPDATE User SET role='$role', name='$name', age=$age, email='$email', 
            contactNumber='$contactNumber', address='$address' WHERE userID='$userID'";

    if ($conn->query($sql) === TRUE) {
        return ['message' => 'User updated successfully!', 'type' => 'success'];
    } else {
        return ['message' => 'Error: ' . $conn->error, 'type' => 'error'];
    }
}

// Function to delete user 
function deleteUser($conn, $userID)
{
    $userID = $conn->real_escape_string($userID);
    $sql = "DELETE FROM User WHERE userID='$userID'";

    if ($conn->query($sql) === TRUE) {
        return ['message' => 'User deleted successfully!', 'type' => 'success'];
    } else {
        return ['message' => 'Error: ' . $conn->error, 'type' => 'error'];
    }
}

// Function to bulk delete users (selected many)
function bulkDeleteUsers($conn, $userIDs)
{
    if (empty($userIDs)) {
        return ['message' => 'No users selected for deletion!', 'type' => 'error'];
    }

    $userIDsStr = "'" . implode("','", array_map([$conn, 'real_escape_string'], $userIDs)) . "'";
    $sql = "DELETE FROM User WHERE userID IN ($userIDsStr)";

    if ($conn->query($sql) === TRUE) {
        $count = $conn->affected_rows;
        return ['message' => "$count user(s) deleted successfully!", 'type' => 'success'];
    } else {
        return ['message' => 'Error: ' . $conn->error, 'type' => 'error'];
    }
}

// Get all users with search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$roleFilter = isset($_GET['role_filter']) ? $_GET['role_filter'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'userID';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

$sql = "SELECT * FROM User WHERE 1=1";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (userID LIKE '%$search%' OR name LIKE '%$search%' OR email LIKE '%$search%')";
}

if (!empty($roleFilter)) {
    $roleFilter = $conn->real_escape_string($roleFilter);
    $sql .= " AND role = '$roleFilter'";
}

$sql .= " ORDER BY $sortBy $sortOrder";
$result = $conn->query($sql);

$editUser = null;
if (isset($_GET['edit'])) {
    $editID = $conn->real_escape_string($_GET['edit']);
    $editSql = "SELECT * FROM User WHERE userID = '$editID'";
    $editResult = $conn->query($editSql);
    if ($editResult->num_rows > 0) {
        $editUser = $editResult->fetch_assoc();
    }
}

// Get statistics
$statsSql = "SELECT role, COUNT(*) as count FROM User GROUP BY role";
$statsResult = $conn->query($statsSql);
$stats = [];
while ($row = $statsResult->fetch_assoc()) {
    $stats[$row['role']] = $row['count'];
}
?>

<!DOCTYPE html>
<head>
    <title>User Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>
    <!-- Header -->
    <header class="container-fluid bg-dark fixed-top shadow-sm d-flex justify-content-between align-items-center px-4"
        style="height: 70px;">
        <div class="text-white fs-4 fw-bold">CC Food Ordering System</div>
        <nav class="d-flex align-items-center gap-3 gap-lg-5">
            <a href="mainPage.php" class="text-white text-decoration-none fw-medium position-relative">Home</a>
            <a href="admin_manage_menu.php" class="text-white text-decoration-none fw-medium position-relative">Manage
                Menu</a>
            <a href="admin_manage_user.php" class="text-white text-decoration-none fw-medium position-relative">Manage
                User</a>
            <a href="admin_sales_report.php" class="text-white text-decoration-none fw-medium position-relative">Sales
                Report</a>
            <a href="admin_feedback.php"
                class="text-white text-decoration-none fw-medium position-relative">Feedback</a>
            <div class="dropdown">
                <a href="#"
                    class="header-link text-white text-decoration-none fw-medium d-flex align-items-center gap-2 dropdown-toggle"
                    id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="assets/user2.png" alt="Profile" class="img-fluid" style="width: 24px; height: 24px;">
                    <span class="d-none d-sm-inline">Profile</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="admin_profile.php">My Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="admin-container">
            <div class="admin-header">
                <h1 class="h3 mb-0"><i class="fas fa-users-cog me-3"></i>User Management System</h1>
                <p class="mb-0 mt-2">Admin Panel - Manage Users Efficiently</p>
            </div>
        </div>


        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show"
                role="alert">
                <i
                    class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card text-center">
                    <div class="stat-icon stat-total mx-auto">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <h2 class="fw-bold text-dark"><?php echo array_sum($stats); ?></h2>
                    <p class="text-muted mb-0">Total Users</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card text-center">
                    <div class="stat-icon stat-admin mx-auto">
                        <i class="fas fa-user-shield text-white"></i>
                    </div>
                    <h2 class="fw-bold text-dark"><?php echo isset($stats['admin']) ? $stats['admin'] : 0; ?></h2>
                    <p class="text-muted mb-0">Administrators</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card text-center">
                    <div class="stat-icon stat-staff mx-auto">
                        <i class="fas fa-user-tie text-white"></i>
                    </div>
                    <h2 class="fw-bold text-dark"><?php echo isset($stats['staff']) ? $stats['staff'] : 0; ?></h2>
                    <p class="text-muted mb-0">Staff Members</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stats-card text-center">
                    <div class="stat-icon stat-customer mx-auto">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <h2 class="fw-bold text-dark"><?php echo isset($stats['customer']) ? $stats['customer'] : 0; ?>
                    </h2>
                    <p class="text-muted mb-0">Customers</p>
                </div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="controls-section">
            <div class="row align-items-center mb-4 ">
                <div class="col-md-6">
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus me-2"></i>Add New User
                        </button>
                        <button class="btn btn-danger" onclick="bulkDelete()">
                            <i class="fas fa-trash me-2"></i>Delete Selected
                        </button>

                    </div>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="d-flex gap-2 justify-content-md-end">
                        <div class="input-group" style="max-width: 300px;">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Search users..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <select name="role_filter" class="form-select" style="max-width: 150px;">
                            <option value="">All Roles</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin
                            </option>
                            <option value="staff" <?php echo $roleFilter === 'staff' ? 'selected' : ''; ?>>Staff
                            </option>
                            <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>
                                Customer
                            </option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                        </button>
                        <a href="?" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-container ">
                <div class="table-responsive ">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="selectAll"
                                        onchange="toggleSelectAll()"></th>
                                <th onclick="sortTable('userID')" class="user-select-none">
                                    <i class="fas fa-id-card me-2"></i>User ID <i class="fas fa-sort"></i>
                                </th>
                                <th onclick="sortTable('role')" class="user-select-none">
                                    <i class="fas fa-user-tag me-2"></i>Role <i class="fas fa-sort"></i>
                                </th>
                                <th onclick="sortTable('name')" class="user-select-none">
                                    <i class="fas fa-user me-2"></i>Name <i class="fas fa-sort"></i>
                                </th>
                                <th onclick="sortTable('age')" class="user-select-none">
                                    <i class="fas fa-birthday-cake me-2"></i>Age <i class="fas fa-sort"></i>
                                </th>
                                <th onclick="sortTable('email')" class="user-select-none">
                                    <i class="fas fa-envelope me-2"></i>Email <i class="fas fa-sort"></i>
                                </th>
                                <th onclick="sortTable('contactNumber')" class="user-select-none">
                                    <i class="fas fa-phone me-2"></i>Contact <i class="fas fa-sort"></i>
                                </th>
                                <th onclick="sortTable('address')" class="user-select-none">
                                    <i class="fas fa-map-marker-alt me-2"></i>Address <i class="fas fa-sort"></i>
                                </th>
                                <th class="text-center">
                                    <i class="fas fa-cogs me-2"></i>Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input userCheckbox"
                                                value="<?php echo $row['userID']; ?>"></td>
                                        <td><strong><?php echo htmlspecialchars($row['userID']); ?></strong></td>
                                        <td><span
                                                class="badge badge-<?php echo $row['role']; ?> text-white"><?php echo ucfirst($row['role']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo $row['age']; ?> years</td>
                                        <td><a href="mailto:<?php echo $row['email']; ?>"
                                                class="text-decoration-none"><?php echo htmlspecialchars($row['email']); ?></a>
                                        </td>
                                        <td><a href="tel:<?php echo $row['contactNumber']; ?>"
                                                class="text-decoration-none"><?php echo htmlspecialchars($row['contactNumber']); ?></a>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($row['address']); ?></small></td>
                                        <td class="text-center">

                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#editModal"
                                                onclick="editUser(
                                                        '<?php echo htmlspecialchars($row['userID'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['role'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>',
                                                        '<?php echo $row['age']; ?>',
                                                        '<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['contactNumber'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['address'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm action-btn"
                                                onclick="deleteUser('<?php echo $row['userID']; ?>')" title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No users found</h5>
                                        <small class="text-muted">Try adjusting your search criteria</small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">
                            <i class="fas fa-user-plus me-2"></i>Add New User
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-id-card me-2"></i>User
                                            ID</label>
                                        <input type="text" class="form-control" name="userID" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-user-tag me-2"></i>Role</label>
                                        <select class="form-select" name="role" required>
                                            <option value="">Select Role</option>
                                            <option value="customer">Customer</option>
                                            <option value="staff">Staff</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-user me-2"></i>Full
                                            Name</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-birthday-cake me-2"></i>Age</label>
                                        <input type="number" class="form-control" name="age" min="1" max="150" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-envelope me-2"></i>Email
                                    Address</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-phone me-2"></i>Contact
                                    Number</label>
                                <input type="text" class="form-control" name="contactNumber" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Address</label>
                                <textarea class="form-control" name="address" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Add User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">
                            <i class="fas fa-user-edit me-2"></i>Edit User
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" id="editForm">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="userID" id="editUserID">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-id-card me-2"></i>User ID</label>
                                        <input type="text" class="form-control" id="editUserIDDisplay" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-user-tag me-2"></i>Role</label>
                                        <select class="form-select" name="role" id="editRole" required>
                                            <option value="">Select Role</option>
                                            <option value="customer">Customer</option>
                                            <option value="staff">Staff</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-user me-2"></i>Full Name</label>
                                        <input type="text" class="form-control" name="name" id="editName" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-birthday-cake me-2"></i>Age</label>
                                        <input type="number" class="form-control" name="age" id="editAge" min="1"
                                            max="150" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-envelope me-2"></i>Email Address</label>
                                <input type="email" class="form-control" name="email" id="editEmail" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-phone me-2"></i>Contact Number</label>
                                <input type="text" class="form-control" name="contactNumber" id="editContactNumber"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Address</label>
                                <textarea class="form-control" name="address" id="editAddress" rows="3"
                                    required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        //tick for all (select all)
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.getElementsByClassName('userCheckbox');

            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = selectAll.checked;
            }
        }

        function bulkDelete() {
            const checkboxes = document.getElementsByClassName('userCheckbox');
            const selected = [];

            for (let i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) {
                    selected.push(checkboxes[i].value);
                }
            }

            if (selected.length === 0) {
                alert('Please select users to delete');
                return;
            }

            if (confirm(`Are you sure you want to delete ${selected.length} user(s)?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="bulk_delete">';

                selected.forEach(userID => {
                    form.innerHTML += `<input type="hidden" name="selected_users[]" value="${userID}">`;
                });

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Delete single user
        function deleteUser(userID) {
            if (confirm('Are you sure you want to delete this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="userID" value="${userID}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Edit user
        function editUser(userID, role, name, age, email, contactNumber, address) {
            document.getElementById('editUserID').value = userID;
            document.getElementById('editUserIDDisplay').value = userID;
            document.getElementById('editRole').value = role;
            document.getElementById('editName').value = name;
            document.getElementById('editAge').value = age;
            document.getElementById('editEmail').value = email;
            document.getElementById('editContactNumber').value = contactNumber;
            document.getElementById('editAddress').value = address;
        }

        // Sort table
        function sortTable(column) {
            const url = new URL(window.location);
            const currentSort = url.searchParams.get('sort');
            const currentOrder = url.searchParams.get('order');

            let newOrder = 'ASC';
            if (currentSort === column && currentOrder === 'ASC') {
                newOrder = 'DESC';
            }
            url.searchParams.set('sort', column);
            url.searchParams.set('order', newOrder);
            window.location.href = url.toString();
        }

        <?php if ($editUser): ?>
            // Auto-open edit modal if edit parameter is present
            editUser('<?php echo $editUser['userID']; ?>');
        <?php endif; ?>
    </script>
</body>
</html>

<?php
$conn->close();
?>