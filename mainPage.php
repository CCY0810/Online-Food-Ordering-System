<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

//Check if user is admin
$isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
?>

<!DOCTYPE html>
<html>

<head>
    <title>Home Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="mainPage.css">
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
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
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
    </style>
</head>

<body class="d-flex flex-column min-vh-100 bg-light">
    <header class="container-fluid bg-dark fixed-top shadow-sm d-flex justify-content-between align-items-center px-4"
        style="height: 70px;">
        <div class="text-white fs-4 fw-bold">CC Food Ordering System</div>
        <nav class="d-flex align-items-center gap-3 gap-lg-5">
            <a href="mainPage.php" class="text-white text-decoration-none fw-medium position-relative">Home</a>
            <?php if ($isAdmin): ?>
                <a href="admin_manage_menu.php" class="text-white text-decoration-none fw-medium position-relative">Manage
                    Menu</a>
                <a href="admin_manage_user.php" class="text-white text-decoration-none fw-medium position-relative">Manage
                    User</a>
                <a href="admin_sales_report.php" class="text-white text-decoration-none fw-medium position-relative">Sales
                    Report</a>
                <a href="admin_feedback.php"
                    class="text-white text-decoration-none fw-medium position-relative">Feedback</a>
            <?php else: ?>
                <a href="menu.php" class="text-white text-decoration-none fw-medium position-relative">Menu</a>

                <a href="order.php" class="text-white text-decoration-none fw-medium position-relative">Order</a>
            <?php endif; ?>
            <div class="d-flex align-items-center gap-4 ms-3">
                <?php if (!$isAdmin): ?>
                    <a href="cart.php"
                        class="header-link text-white text-decoration-none fw-medium d-flex align-items-center gap-2">
                        <img src="assets/cart1.png" alt="Shopping Cart" class="img-fluid"
                            style="width: 24px; height: 24px;">

                        <a href="redirect_orders.php"
                            class="text-white text-decoration-none fw-medium position-relative">Order</a>
                        <div class="d-flex align-items-center gap-4 ms-3">
                            <a href="cart.php"
                                class="header-link text-white text-decoration-none fw-medium d-flex align-items-center gap-2">
                                <img src="assets/cart1.png" alt="Shopping Cart" class="img-fluid"
                                    style="width: 24px; height: 24px;">

                                <span class="d-none d-sm-inline">CART</span>
                            </a>
                        <?php endif; ?>
                        <div class="dropdown">
                            <a href="#"
                                class="header-link text-white text-decoration-none fw-medium d-flex align-items-center gap-2 dropdown-toggle"
                                id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="assets/user2.png" alt="Profile" class="img-fluid"
                                    style="width: 24px; height: 24px;">
                                <span class="d-none d-sm-inline">Profile</span>
                            </a>
                            <?php if (!$isAdmin): ?>

                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                    <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                    <!--li><a class="dropdown-item" href="edit_profile.php">Edit Profile</a></li-->
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            <?php else: ?>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                    <li><a class="dropdown-item" href="admin_profile.php">My Profile</a></li>
                                    <!--li><a class="dropdown-item" href="edit_profile.php">Edit Profile</a></li-->
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
        </nav>
    </header>

    <main class="flex-grow-1">
        <?php if ($isAdmin): ?>
            <section class="min-vh-100 d-flex align-items-center justify-content-center"
                style="background: url('assets/main-page-bg.jpg') no-repeat center center/cover;">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-10 col-lg-8">
                            <div class="welcome-container p-4 p-md-5 text-center text-white rounded-4">
                                <h1 class="display-3 mb-4 mb-md-5 fw-normal">ADMIN DASHBOARD</h1>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <a href="admin_manage_menu.php" class="btn btn-secondary w-100 py-3 mb-2">Manage
                                            Menu</a>
                                        <a href="admin_sales_report.php" class="btn btn-secondary w-100 py-3 mb-2">View
                                            Sales Report</a>
                                        <!--a href="#" class="btn btn-secondary w-100 py-3 mb-2">Manage User</a-->
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <a href="admin_manage_user.php" class="btn btn-secondary w-100 py-3 mb-2">Manage
                                            User</a>
                                        <a href="admin_feedback.php" class="btn btn-secondary w-100 py-3 mb-2">View
                                            Feedback</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <!-- Hero Section -->
            <section class="min-vh-100 d-flex align-items-center justify-content-center"
                style="background: url('assets/main-page-bg.jpg') no-repeat center center/cover;">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-10 col-lg-8">
                            <div class="welcome-container p-4 p-md-5 text-center text-white rounded-4">
                                <h1 class="display-3 mb-4 mb-md-5 fw-normal">WELCOME TO<br>CC RESTAURANT</h1>
                                <a href="menu.php" class="text-decoration-none">
                                    <button class="btn btn-dark px-4 py-2 rounded-pill fw-bold fs-5">ORDER NOW</button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- About Section -->
            <section class="about-section-container position-relative bg-light">
                <div class="about-bg-image position-absolute top-0 start-0 w-100 h-100"
                    style="background: url('assets/about-restaurant.jpg') no-repeat center center/cover;"></div>
                <div class="container position-relative h-100 w-100">
                    <div class="row justify-content-center">
                        <div class="col-12 col-lg-10">
                            <div class="bg-white rounded-4 shadow-sm overflow-hidden w-100 mb-5">
                                <div class="row g-0">
                                    <div class="col-12 col-md-6 order-md-2">
                                        <img src="assets/about-restaurant.jpg" alt="Restaurant Interior"
                                            class="img-fluid h-100 w-100 object-fit-cover">
                                    </div>
                                    <div
                                        class="col-12 col-md-6 order-md-1 p-4 p-md-5 d-flex flex-column justify-content-center">
                                        <h2 class="about-title text-center mb-4 text-dark">ABOUT CC RESTAURANT</h2>
                                        <p class="text-secondary lh-lg mb-4 text-center">
                                            Founded in 2023, CC Restaurant has been serving the community with delicious,
                                            high-quality western meals inspired from various western countries around the
                                            globe. Our passion
                                            in delivering mouth savouring dishes and warm hospitality has made us a beloved
                                            destination
                                            for the local food lovers.
                                        </p>
                                        <p class="text-secondary lh-lg text-center mb-0">
                                            CC Restaurant open daily from 11:00 a.m till 11:00 p.m.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer class="bg-dark text-white py-3 text-center mt-auto">
        <script src="script/footer.js" type="text/javascript"></script>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>