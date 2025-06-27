<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

require_once("config.php");

$foodItems = [];
$beverageItems = [];
$filterItems = [];

$sql = "SELECT * FROM Menu WHERE category='food'";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $foodItems[] = $row;
    }
}

$sql = "SELECT * FROM Menu WHERE category='beverage'";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $beverageItems[] = $row;
    }
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = mysqli_real_escape_string($conn, $_GET['search']);
    $sql = "SELECT * FROM Menu WHERE itemName LIKE '%$searchTerm%'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $filterItems[] = $row;
        }
    } 
}

mysqli_close($conn);

$imageMap = [
    'Steak' => 'BeefSteak.jpg',
    'Spaghetti' => 'spaghetti.jpg',
    'Burger' => 'chickenBurger.jpg',
    'Orange Juice' => 'orangejuice.jpg',
    'Coffee' => 'coffee.jpg',
    'Tea' => 'tea1.jpg'
];
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Menu</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                padding-top: 70px;
                font-family: 'Segoe UI', Arial, sans-serif;
            }
            
            .MenuIntro-section {
                background-image: url("assets/menuIntro-bg.avif");
                background-size: cover;
                background-position: center;
                height: 600px;
                position: relative;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .MenuIntro-section::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
            }
            
            .MenuIntro-content {
                position: relative;
                z-index: 1;
                text-align: center;
                padding: 20px;
                width: 100%;
                max-width: 800px;
            }
            
            .search-bar {
                padding: 15px 25px;
                border-radius: 30px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            }
            
            .category-btn {
                margin-top: 60px;
                margin-left: 20px;
                margin-right: 50px;
                padding: 10px 30px;
                background-color: rgba(255, 255, 255, 0.2);
                color: white;
                border: 2px solid white;
                border-radius: 30px;
                font-weight: 600;
                transition: all 0.4s;
                backdrop-filter: blur(5px);
            }
            
            .category-btn:hover {
                background-color: rgba(255, 255, 255, 0.3);
            }
            
            .category-btn:active {
                background-color: #e74c3c;
                border-color: #e74c3c;
            }
            
            .menu-category {
                scroll-margin-top: 100px;
            }
            
            .category-title {
                font-size: 2rem;
                color: #2c3e50;
                position: relative;
                margin-bottom: 30px;
            }
            
            .category-title::after {
                content: '';
                display: block;
                width: 100px;
                height: 4px;
                background-color: #e74c3c;
                margin: 15px auto;
            }
            
            .menu-item {
                height: 100%;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                transition: transform 0.3s;
                display: flex;
                flex-direction: column;
                background: white;
            }
            
            .menu-item:hover {
                transform: translateY(-10px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            }
            
            .item-image-container {
                height: 280px; 
                overflow: hidden;
            }
            
            .item-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.5s ease;
            }
            
            .menu-item:hover .item-image {
                transform: scale(1.1);
            }
            
            .item-details {
                padding: 25px;
                text-align: center;
                flex-grow: 1;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            
            .item-name {
                font-size: 1.4rem;
                font-weight: 700;
                color: #2c3e50;
                margin-bottom: 15px;
            }
            
            .item-price {
                font-size: 1.3rem;
                color: #e74c3c;
                font-weight: 700;
                margin-top: 15px;
            }
            
            /* Header styles */
            header {
                height: 70px;
            }
            
            nav > a {
                position: relative;
                color: white;
                text-decoration: none;
                font-weight: 500;
            }
            
            nav > a::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 0;
                height: 2px;
                background-color: white;
                transition: width 0.3s ease;
            }
            
            nav > a:hover::after {
                width: 100%;
            }
            
            .header-link {
                color: white;
                text-decoration: none;
                font-weight: 500;
            }
            
            .header-link img {
                transition: transform 0.3s ease;
            }
            
            .header-link:hover img {
                transform: scale(1.2);
            }
            
            /* Menu items grid */
            .menu-items-row {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .menu-item-col {
                display: flex;
                padding: 15px;
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
            
            .menu-item-link {
                display: block;
                height: 100%;
                text-decoration: none;
                color: inherit;
            }
            
            .menu-item-link:hover {
                text-decoration: none;
                color: inherit;
            }
            
            @media (min-width: 768px) {
                .menu-item-col {
                    flex: 0 0 50%;
                    max-width: 50%;
                }
            }
            
            @media (min-width: 992px) {
                .menu-item-col {
                    flex: 0 0 33.333%;
                    max-width: 33.333%;
                }
            }
        </style>
    </head>
    <body>
        <header class="container-fluid bg-dark fixed-top shadow-sm d-flex justify-content-between align-items-center px-4" style="height: 70px;">
            <div class="text-white fs-4 fw-bold">CC Food Ordering System</div>
            <nav class="d-flex align-items-center gap-3 gap-lg-5">
                <a href="mainPage.php" class="text-white text-decoration-none fw-medium position-relative">Home</a>
                <a href="menu.php" class="text-white text-decoration-none fw-medium position-relative">Menu</a>
<<<<<<< Updated upstream
=======
<<<<<<< HEAD
                <?php if($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'staff') { ?>
                    <a href="edit_food_availability.php" class="text-white text-decoration-none fw-medium position-relative">Edit</a>
                <?php } ?>
=======
>>>>>>> 35abe96a4753a42711900242db63415d473b6e8d
>>>>>>> Stashed changes
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
        
        <div class="MenuIntro-section">
            <div class="MenuIntro-content">
                <h1 class="display-4 mb-4">Discover Our Menu</h1>
                <div class="search-section w-100 mx-auto" style="max-width: 600px;">
                    <form action="" method="GET" class="d-flex justify-content-center align-items-center">
                        <input type="text" name="search" class="search-bar form-control border-0 m-3" value="<?php if(isset($_GET['search'])){echo $_GET['search'];} ?>" placeholder="Search for food or beverages..." >
                        <button class="category-btn m-3" type="reset" onclick="resetSearch()">Reset</button>
                    </form>
                    <div class="category-buttons d-flex justify-content-center gap-3">
                        <button class="category-btn active" onclick="scrollToSection('food')">Food</button>
                        <button class="category-btn" onclick="scrollToSection('beverage')">Beverages</button>
                    </div>
                </div>
            </div>
        </div>
<<<<<<< HEAD
        
=======

<<<<<<< Updated upstream
=======
>>>>>>> 35abe96a4753a42711900242db63415d473b6e8d
>>>>>>> Stashed changes
        <?php 
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                ?>
                <div class="menu-category py-5" id="filter">
                    <div class="container">
                        <h2 class="category-title text-center">Search</h2>
                        <div class="row menu-items-row">
                            <?php foreach ($filterItems as $item): ?>
                            <div class="col-12 col-md-6 col-lg-4 menu-item-col">
                                <a href="menu_detail.php?itemID=<?= $item['itemID'] ?>" class="menu-item-link">
                                    <div class="menu-item w-100">
                                        <div class="item-image-container">
                                            <img src="assets/<?= $imageMap[$item['itemName']] ?>" 
                                                alt="<?= $item['itemName'] ?>" 
                                                class="item-image">
                                        </div>
                                        <div class="item-details">
                                            <h3 class="item-name"><?= $item['itemName'] ?></h3>
                                            <p class="item-price">RM<?= number_format($item['itemPrice'], 2) ?></p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
<<<<<<< Updated upstream
=======
<<<<<<< HEAD
                    </div>
                </div>
                <?php
            } else {
                ?>
                <!-- Food Menu Section -->
                <div class="menu-category py-5" id="food">
                    <div class="container">
                        <h2 class="category-title text-center">Food</h2>
                        <div class="row menu-items-row">
                            <?php foreach ($foodItems as $item): ?>
=======
>>>>>>> Stashed changes
                    </div>
                </div>
                <?php
            } else {
                ?>
                <!-- Food Menu Section -->
                <div class="menu-category py-5" id="food">
                    <div class="container">
                        <h2 class="category-title text-center">Food</h2>
                        <div class="row menu-items-row">
                            <?php foreach ($foodItems as $item): ?>
                            <div class="col-12 col-md-6 col-lg-4 menu-item-col">
                                <a href="menu_detail.php?itemID=<?= $item['itemID'] ?>" class="menu-item-link">
                                    <div class="menu-item w-100">
                                        <div class="item-image-container">
                                            <img src="assets/<?= $imageMap[$item['itemName']] ?>" 
                                                alt="<?= $item['itemName'] ?>" 
                                                class="item-image">
                                        </div>
                                        <div class="item-details">
                                            <h3 class="item-name"><?= $item['itemName'] ?></h3>
                                            <p class="item-price">RM<?= number_format($item['itemPrice'], 2) ?></p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Beverages Menu Section -->
                <div class="menu-category py-5 bg-light" id="beverage">
                    <div class="container">
                        <h2 class="category-title text-center">Beverages</h2>
                        <div class="row menu-items-row">
                            <?php foreach ($beverageItems as $item): ?>
<<<<<<< Updated upstream
=======
>>>>>>> 35abe96a4753a42711900242db63415d473b6e8d
>>>>>>> Stashed changes
                            <div class="col-12 col-md-6 col-lg-4 menu-item-col">
                                <a href="menu_detail.php?itemID=<?= $item['itemID'] ?>" class="menu-item-link">
                                    <div class="menu-item w-100">
                                        <div class="item-image-container">
                                            <img src="assets/<?= $imageMap[$item['itemName']] ?>" 
                                                alt="<?= $item['itemName'] ?>" 
                                                class="item-image">
                                        </div>
                                        <div class="item-details">
                                            <h3 class="item-name"><?= $item['itemName'] ?></h3>
                                            <p class="item-price">RM<?= number_format($item['itemPrice'], 2) ?></p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
<<<<<<< Updated upstream
=======
<<<<<<< HEAD
                </div>

                <!-- Beverages Menu Section -->
                <div class="menu-category py-5 bg-light" id="beverage">
                    <div class="container">
                        <h2 class="category-title text-center">Beverages</h2>
                        <div class="row menu-items-row">
                            <?php foreach ($beverageItems as $item): ?>
                            <div class="col-12 col-md-6 col-lg-4 menu-item-col">
                                <a href="menu_detail.php?itemID=<?= $item['itemID'] ?>" class="menu-item-link">
                                    <div class="menu-item w-100">
                                        <div class="item-image-container">
                                            <img src="assets/<?= $imageMap[$item['itemName']] ?>" 
                                                alt="<?= $item['itemName'] ?>" 
                                                class="item-image">
                                        </div>
                                        <div class="item-details">
                                            <h3 class="item-name"><?= $item['itemName'] ?></h3>
                                            <p class="item-price">RM<?= number_format($item['itemPrice'], 2) ?></p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
=======
>>>>>>> 35abe96a4753a42711900242db63415d473b6e8d
>>>>>>> Stashed changes
                </div>  
                <?php 
            } 
        ?>

        <footer class="bg-dark text-white py-3 text-center mt-auto">
            <script src="script/footer.js" type="text/javascript"></script>
        </footer>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            function scrollToSection(sectionId) {
                const section = document.getElementById(sectionId);
                if (section) {
                    // Smooth scroll to section
                    section.scrollIntoView({ behavior: 'smooth' });
                }
            }

            function resetSearch() {
                // Reset the search input and reload the page
                const searchInput = document.querySelector('input[name="search"]');
                searchInput.value = '';
                window.location.href = 'menu.php';
            }
        </script>
    </body>
</html>