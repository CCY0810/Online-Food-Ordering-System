<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff') {
    header("Location: home.php");
    exit();
}

require_once ("config.php");

// Handle update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['itemID'], $_POST['availability'])) {
    $itemID = $_POST['itemID'];
    $availability = $_POST['availability'];

    $stmt = $conn->prepare("UPDATE Menu SET availability = ? WHERE itemID = ?");
    $stmt->bind_param("is", $availability, $itemID);
    $stmt->execute();
    $success = "Availability updated successfully!";
}

// Fetch all menu items
$result = $conn->query("SELECT itemID, itemName, availability FROM Menu ORDER BY itemName ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Food Availability</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            padding-top: 70px;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        nav a:not(.header-link)::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: white;
            transition: width 0.3s ease;
        }

        nav a:not(.header-link):hover::after {
            width: 100%;
        }

        .header-link img {
            transition: transform 0.3s ease;
        }

        .header-link:hover img {
            transform: scale(1.2);
        }

        .welcome-container {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .about-bg-image {
            filter: blur(8px);
            opacity: 0.3;
        }

        .about-title::after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background-color: #e74c3c;
            margin: 10px auto;
        }

        .about-section-container {
            min-height: calc(100vh - 70px);
            display: flex;
            align-items: flex-end;
            padding-bottom: 5rem;
        }

        /* Profile dropdown styles */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }

        .profile-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #343a40;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            overflow: hidden;
        }

        .profile-dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .profile-dropdown-content a:hover {
            background-color: #495057;
        }

        .profile-dropdown:hover .profile-dropdown-content {
            display: block;
        }

        .profile-dropdown-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0;
        }

        .page-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
            color: #2c3e50;
            font-size: 1.8rem;
            margin-top: 20px;
            margin-left: 20px;
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 1275px;
            height: 3px;
            background: linear-gradient(90deg, #3498db, #9b59b6);
            border-radius: 3px;
        }

    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <header class="container-fluid bg-dark fixed-top shadow-sm d-flex justify-content-between align-items-center px-4" style="height: 70px;">
        <div class="text-white fs-4 fw-bold">CC Food Ordering System</div>
        <nav class="d-flex align-items-center gap-3 gap-lg-5">
            <a href="mainPage.php" class="text-white text-decoration-none fw-medium position-relative">Home</a>
            <a href="menu.php" class="text-white text-decoration-none fw-medium position-relative">Menu</a>
            <?php if($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'staff') { ?>
                    <a href="edit_food_availability.php" class="text-white text-decoration-none fw-medium position-relative">Edit</a>
            <?php } ?>
            <a href="redirect_orders.php" class="text-white text-decoration-none fw-medium position-relative">Order</a>
            <div class="d-flex align-items-center gap-4 ms-3">
                <a href="cart.php" class="header-link text-white text-decoration-none fw-medium d-flex align-items-center gap-2">
                    <img src="assets/cart1.png" alt="Shopping Cart" class="img-fluid" style="width: 24px; height: 24px;">
                    <span class="d-none d-sm-inline">CART</span>
                </a>
                <div class="dropdown">
                    <a href="#" class="header-link text-white text-decoration-none fw-medium d-flex align-items-center gap-2 dropdown-toggle"
                    id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="assets/user2.png" alt="Profile" class="img-fluid" style="width: 24px; height: 24px;">
                    <span class="d-none d-sm-inline">Profile</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
                </div>
            </div>
        </nav>
    </header>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="container mt-4">
        <h2 class="page-title">Edit Food Availability</h2>
        <table class="table table-bordered table-light">
            <thead class="table-secondary">
                <tr>
                    <th>Item Name</th>
                    <th>Availability</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <form method="post">
                        <td><?= htmlspecialchars($row['itemName']) ?></td>
                        <td>
                            <input type="number" name="availability" value="<?= $row['availability'] ?>" class="form-control" min="0" required>
                            <input type="hidden" name="itemID" value="<?= $row['itemID'] ?>">
                        </td>
                        <td class="text-center">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <footer class="bg-dark text-white py-3 text-center mt-auto">
        <script src="script/footer.js" type="text/javascript"></script>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
