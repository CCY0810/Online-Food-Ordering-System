<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

require_once("config.php");

// Initialize variables
$cartItems = [];
$grandTotal = 0;

// Fetch cart items for the current user
$user_id = $_SESSION['user_id'];
$sql = "SELECT 
            c.cartID, 
            c.itemID, 
            m.itemName AS name,
            c.variation,
            c.quantity,
            m.itemPrice AS unitPrice,
            (c.quantity * m.itemPrice) AS price
        FROM Cart c
        JOIN Menu m ON c.itemID = m.itemID
        WHERE c.userID = '$user_id'";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cartItems[] = $row;
        $grandTotal += $row['price'];
    }
}

// Create image mapping for cart items
$imageMap = [
    'F001' => 'BeefSteak.jpg',      // Steak
    'F002' => 'spaghetti.jpg',      // Spaghetti
    'F003' => 'chickenBurger.jpg',  // Burger
    'B001' => 'orangejuice.jpg',    // Orange Juice
    'B002' => 'coffee.jpg',         // Coffee
    'B003' => 'tea1.jpg'             // Tea
];

// Define possible variations for each item
$variationsMap = [
    'F001' => ['Well Done', 'Medium Rare', 'Medium', 'Rare'],
    'F002' => ['Marinara', 'Carbonara', 'Aglio e Olio'],
    'F003' => ['Cheese + Mayonnaise', 'Crispy Bacon', 'Sliced Avocado'],
    'B001' => ['Hot', 'Iced'],
    'B002' => ['Hot', 'Iced'],
    'B003' => ['Hot', 'Iced']
];

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Your Cart</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="cart.css">
        <style>
            /* Add custom styles for cart images */
            .cart-item-image-container {
                width: 100px;
                height: 100px;
                border-radius: 8px;
                margin-right: 20px;
                border: 1px solid #eee;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: white;
                overflow: hidden;
            }
            
            .cart-item-image {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }
            
            .cart-item-body {
                display: flex;
                align-items: center;
            }
            
            .item-details {
                flex-grow: 1;
            }
            
            @media (max-width: 768px) {
                .cart-item-body {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .cart-item-image-container {
                    margin-right: 0;
                    margin-bottom: 15px;
                    width: 100%;
                    height: 150px;
                }
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
            
            /* Edit Modal */
            .variation-option {
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .variation-option:hover {
                background-color: #f8f9fa;
            }
            
            .variation-option.selected {
                background-color: #e7f1ff;
                border-color: #0d6efd;
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
        
        <main class="flex-grow-1 py-4">
            <div class="cart-container">
                <h2 class="page-title">Your Shopping Cart</h2>
                
                <div class="d-flex flex-column gap-4">
                    <?php if(count($cartItems) > 0): ?>
                        <?php foreach($cartItems as $item): ?>
                            <div class="cart-item" id="cart-item-<?= $item['cartID'] ?>" data-unit-price="<?= $item['unitPrice'] ?>">
                                <div class="cart-item-header">
                                    <div>
                                        <strong>Cart ID:</strong> <?= $item['cartID'] ?>
                                        <span class="ms-3"><strong>Item ID:</strong> <?= $item['itemID'] ?></span>
                                    </div>
                                    <div class="fw-bold text-primary" id="header-price-<?= $item['cartID'] ?>">RM<?= number_format($item['price'], 2) ?></div>
                                </div>
                                
                                <div class="cart-item-body">
                                    <?php if(isset($imageMap[$item['itemID']])): ?>
                                        <div class="cart-item-image-container">
                                            <img src="assets/<?= $imageMap[$item['itemID']] ?>" 
                                                 alt="<?= $item['name'] ?>" 
                                                 class="cart-item-image">
                                        </div>
                                    <?php else: ?>
                                        <div class="cart-item-image-container">
                                            <i class="fas fa-utensils fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="item-details">
                                        <div class="item-name"><?= $item['name'] ?>
                                    </div>
                                        
                                        <div class="item-details-grid">
                                            <div class="detail-group">
                                                <div class="detail-row">
                                                    <div class="detail-label">Variation:</div>
                                                    <div id="variation-<?= $item['cartID'] ?>"><?= $item['variation'] ?></div>
                                                </div>
                                                
                                                <div class="detail-row">
                                                    <div class="detail-label">Quantity:</div>
                                                    <div id="quantity-<?= $item['cartID'] ?>"><?= $item['quantity'] ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="detail-group">
                                                <div class="detail-row">
                                                    <div class="detail-label">Unit Price:</div>
                                                    <div>RM<?= number_format($item['unitPrice'], 2) ?></div>
                                                </div>
                                                
                                                <div class="detail-row">
                                                    <div class="detail-label">Price:</div>
                                                    <div class="fw-bold" id="price-<?= $item['cartID'] ?>">RM<?= number_format($item['price'], 2) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="item-actions">
                                    <button class="btn-edit edit-btn" data-item="<?= $item['cartID'] ?>" 
                                            data-variation="<?= htmlspecialchars($item['variation']) ?>" 
                                            data-quantity="<?= $item['quantity'] ?>"
                                            data-itemid="<?= $item['itemID'] ?>">
                                        <i class="fas fa-edit me-1"></i> Edit Item
                                    </button>
                                    <button class="btn-remove remove-btn" data-item="<?= $item['cartID'] ?>">
                                        <i class="fas fa-trash-alt me-1"></i> Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Order Summary -->
                        <div class="summary-card">
                            <h5 class="mb-4">Order Summary</h5>
                            
                            <div class="summary-row summary-total">
                                <span>Total Price:</span>
                                <span id="grand-total">RM<?= number_format($grandTotal, 2) ?></span>
                            </div>
                            
                            <!-- Payment Method Selection -->
                            <div class="payment-methods">
                                <div class="payment-title">Select Payment Method</div>
                                <div class="payment-options">
                                    <div class="payment-option" data-method="cards">
                                        <div class="payment-icon">
                                            <i class="far fa-credit-card"></i>
                                        </div>
                                        <div class="payment-name">Cards</div>
                                        <small>Visa, Mastercard, etc.</small>
                                    </div>
                                    
                                    <div class="payment-option" data-method="ewallet">
                                        <div class="payment-icon">
                                            <i class="fas fa-wallet"></i>
                                        </div>
                                        <div class="payment-name">Ewallet</div>
                                        <small>TnG, GrabPay, etc.</small>
                                    </div>
                                    
                                    <div class="payment-option" data-method="cash">
                                        <div class="payment-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="payment-name">Cash</div>
                                        <small>Pay at counter</small>
                                    </div>
                                </div>
                            </div>
                            
                            <button class="btn-checkout">
                                <i class="fas fa-shopping-bag me-2"></i> Place Order
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>Your Cart is Empty</h3>
                            <p class="text-muted">Add delicious food items to your cart from our menu</p>
                            <a href="menu.php" class="btn btn-primary mt-3">Browse Menu</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <!-- Edit Cart Item Modal -->
        <div class="modal fade" id="editCartModal" tabindex="-1" aria-labelledby="editCartModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCartModalLabel">Edit Cart Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editCartForm">
                            <input type="hidden" id="editCartID" name="cartID">
                            
                            <div class="mb-3">
                                <label for="editVariation" class="form-label">Variation</label>
                                <div id="variationOptions" class="d-flex flex-wrap gap-2">
                                    <!-- Variation options will be added dynamically -->
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="editQuantity" class="form-label">Quantity</label>
                                <div class="input-group" style="width: 150px;">
                                    <button class="btn btn-outline-secondary" type="button" id="decreaseQuantity">-</button>
                                    <input type="number" class="form-control text-center" id="editQuantity" name="quantity" min="1" value="1">
                                    <button class="btn btn-outline-secondary" type="button" id="increaseQuantity">+</button>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Toast Notification Container -->
        <div class="toast-container">
            <div id="toastSuccess" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i> <span id="toastMessage">Operation completed successfully!</span>
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
            // Define variations mapping from PHP to JS
            const variationsMap = <?= json_encode($variationsMap) ?>;
            
            // Add interactivity to buttons
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const cartID = this.getAttribute('data-item');
                    const variation = this.getAttribute('data-variation');
                    const quantity = this.getAttribute('data-quantity');
                    const itemID = this.getAttribute('data-itemid');
                    
                    // Set form values
                    document.getElementById('editCartID').value = cartID;
                    document.getElementById('editQuantity').value = quantity;
                    
                    // Clear previous variation options
                    const variationOptions = document.getElementById('variationOptions');
                    variationOptions.innerHTML = '';
                    
                    // Get possible variations for this item
                    const possibleVariations = variationsMap[itemID] || [];
                    
                    // Create variation options
                    possibleVariations.forEach(option => {
                        const optionElement = document.createElement('div');
                        optionElement.classList.add('form-check', 'form-check-inline', 'variation-option', 'p-2', 'border', 'rounded');
                        if (option === variation) {
                            optionElement.classList.add('selected');
                        }
                        optionElement.innerHTML = `
                            <input class="form-check-input" type="radio" name="variation" id="variation-${option}" value="${option}" 
                                ${option === variation ? 'checked' : ''} style="display:none;">
                            <label class="form-check-label" for="variation-${option}">${option}</label>
                        `;
                        
                        optionElement.addEventListener('click', () => {
                            document.querySelectorAll('.variation-option').forEach(opt => {
                                opt.classList.remove('selected');
                            });
                            optionElement.classList.add('selected');
                            document.querySelector(`#variation-${option}`).checked = true;
                        });
                        
                        variationOptions.appendChild(optionElement);
                    });
                    
                    // Show the modal
                    const modal = new bootstrap.Modal(document.getElementById('editCartModal'));
                    modal.show();
                });
            });
            
            // Quantity adjustment in modal
            document.getElementById('decreaseQuantity').addEventListener('click', () => {
                const quantityInput = document.getElementById('editQuantity');
                let quantity = parseInt(quantityInput.value);
                if (quantity > 1) {
                    quantityInput.value = quantity - 1;
                }
            });
            
            document.getElementById('increaseQuantity').addEventListener('click', () => {
                const quantityInput = document.getElementById('editQuantity');
                let quantity = parseInt(quantityInput.value);
                quantityInput.value = quantity + 1;
            });
            
            // Handle edit form submission
            document.getElementById('editCartForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const cartID = document.getElementById('editCartID').value;
                const variation = document.querySelector('input[name="variation"]:checked')?.value;
                const quantity = parseInt(document.getElementById('editQuantity').value);
                
                if (!variation) {
                    showToast('Please select a variation!', false);
                    return;
                }
                
                if (quantity < 1) {
                    showToast('Quantity must be at least 1!', false);
                    return;
                }
                
                // Get unit price from data attribute
                const cartItem = document.getElementById(`cart-item-${cartID}`);
                const unitPrice = parseFloat(cartItem.dataset.unitPrice);
                const newPrice = unitPrice * quantity;
                
                // AJAX call to edit item
                fetch('edit_cart_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cartID=${cartID}&variation=${encodeURIComponent(variation)}&quantity=${quantity}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the UI without reloading
                        document.getElementById(`variation-${cartID}`).textContent = variation;
                        document.getElementById(`quantity-${cartID}`).textContent = quantity;
                        
                        // Update prices
                        document.getElementById(`price-${cartID}`).textContent = 'RM' + newPrice.toFixed(2);
                        document.getElementById(`header-price-${cartID}`).textContent = 'RM' + newPrice.toFixed(2);
                        
                        // Update grand total
                        updateGrandTotal();
                        
                        showToast('Item updated successfully!', true);
                        
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editCartModal'));
                        modal.hide();
                    } else {
                        showToast('Error updating item: ' + (data.message || 'Unknown error'), false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while updating the item.', false);
                });
            });
            
            // Update grand total function
            function updateGrandTotal() {
                let grandTotal = 0;
                
                document.querySelectorAll('.cart-item').forEach(item => {
                    const priceElement = item.querySelector('.cart-item-header .text-primary');
                    const price = parseFloat(priceElement.textContent.replace('RM', ''));
                    grandTotal += price;
                });
                
                document.getElementById('grand-total').textContent = 'RM' + grandTotal.toFixed(2);
            }
            
            // Remove item functionality
            document.querySelectorAll('.remove-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const cartID = this.getAttribute('data-item');
                    if(confirm(`Are you sure you want to remove item ${cartID} from your cart?`)) {
                        // AJAX call to remove item
                        fetch('remove_cart_item.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `cartID=${cartID}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.success) {
                                showToast(`Item ${cartID} removed from cart.`, true);
                                this.closest('.cart-item').remove();
                                
                                // Update grand total
                                updateGrandTotal();
                                
                                // Reload page if no items left
                                if(document.querySelectorAll('.cart-item').length === 0) {
                                    setTimeout(() => {
                                        location.reload();
                                    }, 1500);
                                }
                            } else {
                                showToast('Error removing item: ' + data.message, false);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('An error occurred while removing the item.', false);
                        });
                    }
                });
            });
            
            // Toast function
            function showToast(message, isSuccess) {
                const toastElement = document.getElementById('toastSuccess');
                const toastMessage = document.getElementById('toastMessage');
                
                // Update message and style
                toastMessage.textContent = message;
                toastElement.classList.remove('bg-success', 'bg-danger');
                
                if (isSuccess) {
                    toastElement.classList.add('bg-success');
                } else {
                    toastElement.classList.add('bg-danger');
                }
                
                // Show the toast
                const toast = new bootstrap.Toast(toastElement);
                toast.show();
                
                // Auto hide after 3 seconds
                setTimeout(() => {
                    toast.hide();
                }, 3000);
            }
            
            // Payment method selection
            document.querySelectorAll('.payment-option').forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    document.querySelectorAll('.payment-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                });
            });
            
            // Checkout button
            // Replace the existing checkout button event handler with this:
document.querySelector('.btn-checkout')?.addEventListener('click', function() {
    const selectedPayment = document.querySelector('.payment-option.selected');
    
    if (!selectedPayment) {
        showToast('Please select a payment method', false);
        return;
    }
    
    const method = selectedPayment.getAttribute('data-method');
    
    // Disable button to prevent multiple clicks
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
    
    // AJAX call to place order
    fetch('place_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `paymentMethod=${encodeURIComponent(method)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, true);
            // Remove cart items from UI
            document.querySelectorAll('.cart-item').forEach(item => item.remove());
            // Update grand total to 0
            document.getElementById('grand-total').textContent = 'RM0.00';
            // Hide checkout section
            document.querySelector('.summary-card').style.display = 'none';
            // Redirect to order status page after delay
            setTimeout(() => {
                window.location.href = 'order.php';
            }, 2000);
        } else {
            showToast('Error: ' + data.message, false);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-shopping-bag me-2"></i> Place Order';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', false);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shopping-bag me-2"></i> Place Order';
    });
});
        </script>
    </body>
</html>