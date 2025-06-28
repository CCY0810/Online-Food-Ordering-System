<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff') {
    header("Location: oder.php");
    exit();
}

require_once("config.php");

$orders = [];

// Get selected status from GET 
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

$orderQuery = "SELECT orderID, userID, orderTime, orderStatus, total, paymentMethod 
               FROM Orders";

$params = [];
$types = '';

// Add WHERE clause if status is filtered
if (!empty($statusFilter)) {
    $orderQuery .= " WHERE orderStatus = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$orderQuery .= " ORDER BY orderTime DESC";

$stmt = $conn->prepare($orderQuery);

// Bind parameter if needed
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

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

    // Format order ID
    $displayOrderID = 'ORD-' . date('Y', strtotime($orderRow['orderTime'])) . '-' . str_pad($orderID, 4, '0', STR_PAD_LEFT);
    
    // Build order array
    $orders[] = [
        'orderID' => $displayOrderID,
        'dbOrderID' => $orderID,
        'userID' => $orderRow['userID'], 
        'orderTime' => $orderRow['orderTime'],
        'orderStatus' => $orderRow['orderStatus'],
        'paymentMethod' => $orderRow['paymentMethod'],
        'items' => $items,
        'total' => $orderRow['total']
    ];
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Order List</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="order.css">
        <style>
            .btn-notify {
                background: #8e44ad;
                color: white;
                border: none;
                border-radius: 8px;
                padding: 9px 22px;
                font-weight: 600;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 4px 10px rgba(142, 68, 173, 0.2);
            }

            .btn-notify:hover {
                background: #732d91;
                transform: translateY(-3px);
                box-shadow: 0 6px 15px rgba(142, 68, 173, 0.3);
            }
        </style>
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
            <form method="GET" class="mb-4">
                <div class="d-flex align-items-center">
                    <label for="statusFilter" class="me-2 fw-bold text-dark">Filter by Status:</label>
                    <select name="status" id="statusFilter" class="form-select w-auto" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="Pending" <?= $_GET['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Accepted" <?= $_GET['status'] == 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                        <option value="Preparing" <?= $_GET['status'] == 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                        <option value="Ready" <?= $_GET['status'] == 'Ready' ? 'selected' : '' ?>>Ready</option>
                        <option value="Completed" <?= $_GET['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= $_GET['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </form>

            
            <div class="d-flex flex-column gap-4">
                <?php if(count($orders) > 0): ?>
                    <?php foreach($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <strong>Order ID:</strong> <?= htmlspecialchars($order['orderID']) ?>
                                    <span class="ms-3"><strong>Order Time:</strong> <?= date('M j, Y g:i A', strtotime($order['orderTime'])) ?></span>
                                    <span class="ms-3"><strong>User ID:</strong> <?= htmlspecialchars($order['userID']) ?></span>
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
                                 <button class="btn-notify"
                                        data-bs-toggle="modal"
                                        data-bs-target="#notifyModal"
                                        data-order-id="<?= htmlspecialchars($order['dbOrderID']) ?>">
                                    <i class="fas fa-comment-dots"></i> Send Message
                                </button>

                                <button class="btn-detail" data-bs-toggle="modal" data-bs-target="#orderDetailModal" 
                                        data-order-id="<?= htmlspecialchars($order['orderID']) ?>"
                                        data-db-order-id="<?= htmlspecialchars($order['dbOrderID']) ?>">
                                    <i class="fas fa-info-circle me-1"></i> Order Details
                                </button>

                                <?php if($order['orderStatus'] === 'Pending'): ?>
                                    <button class="btn-reject" 
                                            data-order-id="<?= htmlspecialchars($order['orderID']) ?>"
                                            data-db-order-id="<?= htmlspecialchars($order['dbOrderID']) ?>">
                                        <i class="fas fa-times-circle me-1"></i> Reject
                                    </button>
                                    <button class="btn-accept"  
                                            data-order-id="<?= htmlspecialchars($order['orderID']) ?>"
                                            data-db-order-id="<?= htmlspecialchars($order['dbOrderID']) ?>">
                                        <i class="fas fa-check-circle me-1"></i> Accept
                                    </button>
                                <?php elseif(in_array($order['orderStatus'], ['Accepted', 'Preparing', 'Ready'])): ?>
                                    <button class="btn-update"
                                            data-order-id="<?= htmlspecialchars($order['orderID']) ?>"
                                            data-db-order-id="<?= htmlspecialchars($order['dbOrderID']) ?>"
                                            data-current-status="<?= $order['orderStatus'] ?>">
                                        <i class="fas fa-arrow-alt-circle-up me-1"></i> Update Status
                                    </button>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No Orders Found</h3>
                        <p>There are no orders yet</p>
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
    
    <!-- Notification Modal -->
    <div class="modal fade" id="notifyModal" tabindex="-1" aria-labelledby="notifyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="send_notification.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Notification to Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="orderID" id="notify-order-id">
                    <textarea name="message" class="form-control" placeholder="Type message to customer..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Send</button>
                </div>
            </div>
        </form>
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
        
        function updateOrderStatus(orderID, newStatus) {
            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `orderID=${encodeURIComponent(orderID)}&newStatus=${encodeURIComponent(newStatus)}`
            })
            .then(res => res.text())
            .then(response => {
                alert('Order status updated to ' + newStatus);
                location.reload(); // Or update DOM dynamically if preferred
            })
            .catch(err => {
                console.error('Failed:', err);
                alert('Update failed');
            });
        }

        // Accept
        document.querySelectorAll('.btn-accept').forEach(btn => {
            btn.addEventListener('click', () => {
                const orderID = btn.getAttribute('data-db-order-id');
                updateOrderStatus(orderID, 'Accepted');
            });
        });

        // Reject
        document.querySelectorAll('.btn-reject').forEach(btn => {
            btn.addEventListener('click', () => {
                const orderID = btn.getAttribute('data-db-order-id');
                updateOrderStatus(orderID, 'Cancelled');
            });
        });

        // Update Status
        document.querySelectorAll('.btn-update').forEach(btn => {
            btn.addEventListener('click', () => {
                const orderID = btn.getAttribute('data-db-order-id');
                const currentStatus = btn.getAttribute('data-current-status');

                let nextStatus = null;

                switch (currentStatus) {
                case 'Accepted':
                    nextStatus = 'Preparing';
                    break;
                case 'Preparing':
                    nextStatus = 'Ready';
                    break;
                case 'Ready':
                    nextStatus = 'Completed';
                    break;
                default:
                    alert('No further status update possible.');
                    return;
                }

                updateOrderStatus(orderID, nextStatus);
            });
        });

        let lastCheck = new Date().getTime();

        // Check for new orders every 10 seconds
        setInterval(() => {
            const modalsOpen = document.querySelectorAll('.modal.show').length > 0;
            fetch('check_new_orders.php?since=' + lastCheck)
                .then(response => response.json())
                .then(data => {
                    if (data.newOrder && !modalsOpen) {
                        console.log('New order detected! Refreshing...');
                        location.reload(); 
                    }
                    lastCheck = new Date().getTime(); // update timestamp
                })
                .catch(error => console.error('Check failed:', error));
        }, 10000); 

        const notifyModal = document.getElementById('notifyModal');
        notifyModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const orderID = button.getAttribute('data-order-id');
            document.getElementById('notify-order-id').value = orderID;
        });
    </script>
</body>
</html>