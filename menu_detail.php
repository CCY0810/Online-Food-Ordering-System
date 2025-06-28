<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

require_once("config.php");

$itemID = isset($_GET['itemID']) ? $_GET['itemID'] : null;

if (!$itemID) {
    header("Location: menu.php");
    exit();
}

// Fetch item details from database
$query = "SELECT * FROM Menu WHERE itemID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $itemID);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    die("Item not found in database");
}

$itemName = $item['itemName'];
$itemPrice = $item['itemPrice'];
$category = $item['category'];

// Map item IDs to specific data
$itemDetails = [
    // Steak
    'F001' => [ 
        'hero_image' => 'BeefSteak.jpg',
        'description' => 'Beef steaks cooked over high heat on grill, creating a caramelized crust while keeping the inside juicy',
        'variation_title' => 'Steak Cooking Levels',
        'variations' => ['Well Done', 'Medium Rare', 'Medium', 'Rare']
    ],
    // Spaghetti
    'F002' => [ 
        'hero_image' => 'spaghetti-selection.jpg',
        'description' => 'Classic long, thin and cylindrical Italian pasta paired with various sauce choices',
        'variation_title' => 'Sauce choices',
        'variations' => ['Marinara', 'Carbonara', 'Aglio e Olio']
    ],
    // Burger
    'F003' => [ 
        'hero_image' => 'chickenBurger-selection.jpg',
        'description' => 'A crispy chicken patty served on hamburger bun with fried egg, savouring sauces and various topping selections',
        'variation_title' => 'Topping selection',
        'variations' => ['Cheese + Mayonnaise', 'Crispy Bacon', 'Sliced Avocado']
    ],
    // Orange Juice
    'B001' => [
        'hero_image' => 'oranges-selection.jpg',
        'description' => 'Natural fresh orange juice squizzed from fresh tropical oranges',
        'variation_title' => 'Select one',
        'variations' => ['Hot', 'Iced']
    ],
    // Coffee
    'B002' => [ 
        'hero_image' => 'coffee-selection2.jpg',
        'description' => 'Rich, aromatic coffee crafted to perfection delivering smooth, velvety texture',
        'variation_title' => 'Select one',
        'variations' => ['Hot', 'Iced']
    ],
    // Tea
    'B003' => [ 
        'hero_image' => 'tea-selection1.jpg',
        'description' => 'Handcrafted tea where each cup is a delicate balance of fragnant aromas and soothing flavors',
        'variation_title' => 'Select one',
        'variations' => ['Hot', 'Iced']
    ]
];

$details = $itemDetails[$itemID];

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= ucfirst($itemName) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
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

            /* Food item styling */
            .food-hero {
                height: 500px;
                background-size: cover;
                background-repeat: no-repeat;
                background-position: center;
                position: relative;
            }
            
            .food-hero::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.4);
            }
            
            .food-hero-content {
                position: relative;
                z-index: 1;
                color: white;
            }
            
            .variation-card {
                transition: transform 0.3s, box-shadow 0.3s;
                cursor: pointer;
                height: 100%;
            }
            
            .variation-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }
            
            .variation-card.selected {
                border: 2px solid #e74c3c;
                background-color: #f8f9fa;
            }
            
            .add-to-cart-btn {
                transition: all 0.3s;
            }
            
            .add-to-cart-btn:hover {
                transform: scale(1.05);
            }
            
            .four-columns .col-md-3 {
                flex: 0 0 25%;
                max-width: 25%;
            }
            
            /* Toast notification */
            .toast-container {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 1050;
            }
            
            .toast {
                transition: transform 0.3s, opacity 0.3s;
            }
        </style>
    </head>
    <body class="d-flex flex-column min-vh-100 bg-light">
        <header class="container-fluid bg-dark fixed-top shadow-sm d-flex justify-content-between align-items-center px-4" style="height: 70px;">
            <div class="text-white fs-4 fw-bold">CC Food Ordering System</div>
            <nav class="d-flex align-items-center gap-3 gap-lg-5">
                <a href="mainPage.php" class="text-white text-decoration-none fw-medium position-relative">Home</a>
                <a href="menu.php" class="text-white text-decoration-none fw-medium position-relative">Menu</a>
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
        
        <main class="flex-grow-1">
            <!-- Food Hero Section -->
            <section class="food-hero d-flex align-items-end pb-5" style="background-image: url('assets/<?= $details['hero_image'] ?>');">
                <div class="container food-hero-content">
                    <h1 class="display-3 fw-bold mb-3"><?= $itemName ?></h1>
                    <p class="lead mb-4"><?= $details['description'] ?></p>
                    <div class="d-flex align-items-center gap-3">
                    </div>
                </div>
            </section>
            
            <!-- Food Variations Section -->
            <section class="py-5">
                <div class="container">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2 class="fw-bold mb-4">Choose Your Variation</h2>
                            
                            <!-- Variation Selection -->
                            <div class="mb-5">
                                <h4 class="mb-3"><?= $details['variation_title'] ?></h4>
                                <div class="row g-3">
                                    <?php foreach ($details['variations'] as $index => $variation): ?>
                                    <div class="col-md-4">
                                        <div class="card variation-card p-3 <?= $index === 0 ? 'selected' : '' ?>" 
                                             onclick="selectVariation(this, '<?= $variation ?>')">
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?= $variation ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Quantity -->
                            <div class="mb-5 d-flex align-items-center">
                                <h4 class="mb-0 me-3">Quantity:</h4>
                                <div class="input-group" style="width: 120px;">
                                    <button class="btn btn-outline-secondary" type="button" onclick="adjustQuantity(-1)">-</button>
                                    <input type="text" class="form-control text-center" value="1" id="quantity">
                                    <button class="btn btn-outline-secondary" type="button" onclick="adjustQuantity(1)">+</button>
                                </div>
                            </div>
                            
                            <!-- Total and Add to Cart -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1">Total:</h4>
                                    <h2 class="text-danger fw-bold" id="totalPrice">RM<?= number_format($itemPrice, 2) ?></h2>
                                </div>
                                <button class="btn btn-danger btn-lg px-5 py-3 add-to-cart-btn" onclick="addToCart()">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        
        <!-- Toast Notification Container -->
        <div class="toast-container">
            <div id="toastSuccess" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i> Item added to cart successfully!
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            <div id="toastError" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-exclamation-circle me-2"></i> <span id="errorMessage">Please select a variation!</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
        
        <footer class="bg-dark text-white py-3 text-center mt-auto">
           <script src="script/footer.js" type="text/javascript"></script>
        </footer>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Initialize variables
            let selectedVariation = "<?= $details['variations'][0] ?>";
            const basePrice = <?= $itemPrice ?>;
            const itemID = "<?= $itemID ?>";
            const itemName = "<?= $itemName ?>";
            
            // Variation selection
            function selectVariation(element, variation) {
                document.querySelectorAll('.variation-card').forEach(card => {
                    card.classList.remove('selected');
                });
                element.classList.add('selected');
                selectedVariation = variation;
            }
            
            // Quantity adjustment
            function adjustQuantity(change) {
                const quantityInput = document.getElementById('quantity');
                let quantity = parseInt(quantityInput.value) + change;
                if (quantity < 1) quantity = 1;
                quantityInput.value = quantity;
                updateTotal();
            }
            
            // Update total price based on quantity only
            function updateTotal() {
                const quantity = parseInt(document.getElementById('quantity').value);
                const total = basePrice * quantity;
                document.getElementById('totalPrice').textContent = 'RM' + total.toFixed(2);
            }
            
            // Add to cart function
            function addToCart() {
                const quantity = parseInt(document.getElementById('quantity').value);
                
                // Validate quantity
                if (quantity < 1) {
                    showError("Quantity must be at least 1");
                    return;
                }
                
                // Create form data
                const formData = new FormData();
                formData.append('itemID', itemID);
                formData.append('variation', selectedVariation);
                formData.append('quantity', quantity);
                
                // Send AJAX request
                fetch('add_to_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess();
                    } else {
                        showError(data.message || 'Failed to add item to cart');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('An error occurred. Please try again.');
                });
            }
            
            // Show success toast
            function showSuccess() {
                const toast = new bootstrap.Toast(document.getElementById('toastSuccess'));
                toast.show();
                
                // Auto hide after 3 seconds
                setTimeout(() => {
                    toast.hide();
                }, 3000);
            }
            
            // Show error toast
            function showError(message) {
                document.getElementById('errorMessage').textContent = message;
                const toast = new bootstrap.Toast(document.getElementById('toastError'));
                toast.show();
                
                // Auto hide after 3 seconds
                setTimeout(() => {
                    toast.hide();
                }, 3000);
            }
        </script>
    </body>
</html>