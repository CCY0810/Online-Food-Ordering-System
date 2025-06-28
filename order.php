<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once("config.php");

// Fetch orders from database
$userID = $_SESSION['user_id'];
$orders = [];

// Get orders for current user
$orderQuery = "SELECT orderID, orderTime, orderStatus, total, paymentMethod 
               FROM Orders 
               WHERE userID = ?
               ORDER BY orderTime DESC";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

while ($orderRow = $result->fetch_assoc()) {
    $orderID = $orderRow['orderID'];
    
    // Get order items
    $items = [];
    $itemQuery = "SELECT m.itemName, od.variation 
                  FROM OrderDetails od
                  JOIN Menu m ON od.itemID = m.itemID
                  WHERE od.orderID = ?";
    $itemStmt = $conn->prepare($itemQuery);
    $itemStmt->bind_param("i", $orderID);
    $itemStmt->execute();
    $itemResult = $itemStmt->get_result();
    
    while ($itemRow = $itemResult->fetch_assoc()) {
        $itemName = $itemRow['itemName'];
        if (!empty($itemRow['variation'])) {
            $itemName .= ' (' . $itemRow['variation'] . ')';
        }
        $items[] = $itemName;
    }
    
    // Format order ID for display
    $displayOrderID = 'ORD-' . date('Y', strtotime($orderRow['orderTime'])) . '-' . str_pad($orderID, 4, '0', STR_PAD_LEFT);
    
    // Build order array
    $orders[] = [
        'orderID' => $displayOrderID,
        'dbOrderID' => $orderID,
        'orderTime' => $orderRow['orderTime'],
        'orderStatus' => $orderRow['orderStatus'],
        'paymentMethod' => $orderRow['paymentMethod'],
        'items' => $items,
        'total' => $orderRow['total']
    ];
}

// Fetch notifications for this order
$notifStmt = $conn->prepare("SELECT message, createdAt FROM OrderNotifications WHERE orderID = ? ORDER BY createdAt DESC");
$notifStmt->bind_param("i", $order['dbOrderID']);
$notifStmt->execute();
$notifResult = $notifStmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="order.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <!-- Header -->
    <header class="container-fluid bg-dark fixed-top shadow-sm d-flex justify-content-between align-items-center px-4"
        style="height: 70px;">
        <div class="text-white fs-4 fw-bold">CC Food Ordering System</div>
        <nav class="d-flex align-items-center gap-3 gap-lg-5">
            <a href="mainPage.php" class="text-white text-decoration-none fw-medium position-relative">Home</a>
            <?php if($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'staff') { ?>
                <a href="admin_manage_menu.php" class="text-white text-decoration-none fw-medium position-relative">Manage
                    Menu</a>
            <?php } ?>
            <?php if ($isAdmin): ?>
                <a href="admin_manage_user.php" class="text-white text-decoration-none fw-medium position-relative">Manage
                    User</a>
                <a href="admin_sales_report.php" class="text-white text-decoration-none fw-medium position-relative">Sales
                    Report</a>
                <a href="admin_feedback.php"
                    class="text-white text-decoration-none fw-medium position-relative">Feedback</a>
            <?php endif; ?>
            <?php if (!$isAdmin): ?>
                <a href="menu.php" class="text-white text-decoration-none fw-medium position-relative">Menu</a>    
                <a href="redirect_orders.php" class="text-white text-decoration-none fw-medium position-relative">Order</a>
                <div class="d-flex align-items-center gap-4 ms-3">
                    <a href="cart.php" class="header-link text-white text-decoration-none fw-medium d-flex align-items-center gap-2">
                        <img src="assets/cart1.png" alt="Shopping Cart" class="img-fluid" style="width: 24px; height: 24px;">
                        <span class="d-none d-sm-inline">CART</span>
                    </a>
            <?php endif; ?>
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
    
    <!-- Main Content -->
    <main class="flex-grow-1 py-4">
        <div class="order-container">
            <h2 class="page-title">Your Order Status</h2>
            
            <div class="d-flex flex-column gap-4">
                <?php if(count($orders) > 0): ?>
                    <?php foreach($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <strong>Order ID:</strong> <?= htmlspecialchars($order['orderID']) ?>
                                    <span class="ms-3"><strong>Order Time:</strong> <?= date('M j, Y g:i A', strtotime($order['orderTime'])) ?></span>
                                </div>
                                <div>
                                    <?php 
                                    $statusClass = '';
                                    switch($order['orderStatus']) {
                                        case 'Completed':
                                            $statusClass = 'status-completed';
                                            break;
                                        case 'Ready':
                                            $statusClass = 'status-ready';
                                            break;
                                        case 'Cancelled':
                                            $statusClass = 'status-cancelled';
                                            break;
                                        case 'Pending':
                                            $statusClass = 'status-pending';
                                            break;
                                        case 'Accepted':
                                            $statusClass = 'status-accepted';
                                            break;
                                        case 'Preparing':
                                            $statusClass = 'status-inpreparation';
                                            break;
                                    }
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($order['orderStatus']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if (isset($order['dbOrderID'])): ?>
                                <?php
                                $notifStmt = $conn->prepare("SELECT message, createdAt FROM OrderNotifications WHERE orderID = ? ORDER BY createdAt DESC");
                                $notifStmt->bind_param("i", $order['dbOrderID']);
                                $notifStmt->execute();
                                $notifResult = $notifStmt->get_result();

                                if ($notifResult && $notifResult->num_rows > 0): ?>
                                    <div class="alert alert-info mt-2 mb-3">
                                        <strong>📢 Message from staff:</strong>
                                        <ul class="mb-0">
                                            <?php while ($notif = $notifResult->fetch_assoc()): ?>
                                                <li><em><?= htmlspecialchars($notif['createdAt']) ?></em>: <?= htmlspecialchars($notif['message']) ?></li>
                                            <?php endwhile; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="order-body">
                                <div class="order-detail">
                                    <div class="detail-label">Payment Method:</div>
                                    <div><?= htmlspecialchars($order['paymentMethod']) ?></div>
                                </div>
                                
                                <div class="order-detail">
                                    <div class="detail-label">Items:</div>
                                    <div>
                                        <ul class="items-list">
                                            <?php foreach($order['items'] as $item): ?>
                                                <li><?= htmlspecialchars($item) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="order-detail">
                                    <div class="detail-label">Total Price:</div>
                                    <div class="fw-bold">RM<?= number_format($order['total'], 2) ?></div>
                                </div>
                            </div>
                            
                            <div class="order-actions">
                                <button class="btn-detail" data-bs-toggle="modal" data-bs-target="#orderDetailModal" 
                                        data-order-id="<?= htmlspecialchars($order['orderID']) ?>"
                                        data-db-order-id="<?= htmlspecialchars($order['dbOrderID']) ?>">
                                    <i class="fas fa-info-circle me-1"></i> Order Details
                                </button>
                                <?php if($order['orderStatus'] === 'Completed'): ?>
                                    <button class="btn-feedback" data-bs-toggle="modal" data-bs-target="#feedbackModal" 
                                            data-order-id="<?= htmlspecialchars($order['orderID']) ?>"
                                            data-db-order-id="<?= htmlspecialchars($order['dbOrderID']) ?>">
                                        <i class="fas fa-star me-1"></i> Rate Order
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No Orders Found</h3>
                        <p>You haven't placed any orders yet. Start your culinary journey now!</p>
                        <a href="menu.php" class="btn btn-primary btn-lg px-4 py-2">
                            Browse Menu
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Order Detail Modal -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details - <span id="modalOrderId"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="order-detail">
                                <div class="detail-label">Order Time:</div>
                                <div id="detail-order-time"></div>
                            </div>
                            <div class="order-detail">
                                <div class="detail-label">Status:</div>
                                <div id="detail-order-status"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="order-detail">
                                <div class="detail-label">Payment Method:</div>
                                <div id="detail-payment-method"></div>
                            </div>
                            <div class="order-detail">
                                <div class="detail-label">Total Price:</div>
                                <div class="fw-bold" id="detail-total"></div>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mb-3 border-bottom pb-2">Ordered Items</h6>
                    <ul class="items-list" id="detail-items-list"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rate Your Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="ratingForm" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="orderID" id="dbOrderID">
                        <div class="text-center mb-3">
                            <p>How would you rate your experience with order <strong id="feedback-order-id"></strong>?</p>
                        </div>

                        <div class="rating-stars">
                            <i class="far fa-star rating-star" data-value="1"></i>
                            <i class="far fa-star rating-star" data-value="2"></i>
                            <i class="far fa-star rating-star" data-value="3"></i>
                            <i class="far fa-star rating-star" data-value="4"></i>
                            <i class="far fa-star rating-star" data-value="5"></i>
                        </div>
                        <input type="hidden" name="ratingValue" id="ratingValue" value="0">
                        <div class="rating-text" id="rating-description">Tap a star to rate</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Rating</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-3 text-center mt-auto">
        <script src="script/footer.js" type="text/javascript"></script>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize modals
        const orderDetailModal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
        const feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
        
        // Order Detail Modal
        document.getElementById('orderDetailModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const orderID = button.getAttribute('data-order-id');
            const dbOrderID = button.getAttribute('data-db-order-id');
            
            // Set basic order info
            const orderCard = button.closest('.order-card');
            document.getElementById('modalOrderId').textContent = orderID;
            document.getElementById('detail-order-time').textContent = 
                orderCard.querySelector('.order-header span').textContent.replace('Order Time: ', '');
            
            const statusBadge = orderCard.querySelector('.status-badge');
            document.getElementById('detail-order-status').innerHTML = statusBadge.outerHTML;
            
            document.getElementById('detail-payment-method').textContent = 
                orderCard.querySelector('.order-body .order-detail:nth-child(1) div:last-child').textContent;
            
            document.getElementById('detail-total').textContent = 
                orderCard.querySelector('.order-body .order-detail:last-child div:last-child').textContent;
            
            // Set items list
            const itemsList = document.getElementById('detail-items-list');
            itemsList.innerHTML = '';
            
            const items = orderCard.querySelectorAll('.items-list li');
            items.forEach(item => {
                const li = document.createElement('li');
                li.innerHTML = item.innerHTML;
                itemsList.appendChild(li);
            });
        });
        
        // Feedback Modal
document.getElementById('feedbackModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const orderID = button.getAttribute('data-order-id');
    const dbOrderID = button.getAttribute('data-db-order-id');
    
    document.getElementById('feedback-order-id').textContent = orderID;
    document.getElementById('dbOrderID').value = dbOrderID;
    
    // Reset rating
    document.querySelectorAll('.rating-star').forEach(star => {
        star.classList.remove('fas', 'active');
        star.classList.add('far');
    });
    document.getElementById('ratingValue').value = 0;
    document.getElementById('rating-description').textContent = 'Tap a star to rate';
});

// Star rating
document.querySelectorAll('.rating-star').forEach(star => {
    star.addEventListener('click', function() {
        const value = parseInt(this.getAttribute('data-value'));
        const stars = document.querySelectorAll('.rating-star');
        
        stars.forEach((s, index) => {
            if (index < value) {
                s.classList.remove('far');
                s.classList.add('fas', 'active');
            } else {
                s.classList.remove('fas', 'active');
                s.classList.add('far');
            }
        });
        
        document.getElementById('ratingValue').value = value;
        
        // Update rating description
        const descriptions = [
            "Poor - Needs improvement",
            "Fair - Could be better",
            "Good - Satisfied",
            "Very Good - Enjoyed it",
            "Excellent - Perfect experience"
        ];
        document.getElementById('rating-description').textContent = descriptions[value - 1];
    });
});

// Form submission
document.getElementById('ratingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('submit_rating.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Rating submitted successfully!');
            bootstrap.Modal.getInstance(document.getElementById('feedbackModal')).hide();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
});
    </script>
</body>
</html>