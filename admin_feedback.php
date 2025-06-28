<?php
session_start();

require_once("config.php");

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderID = $_POST['orderID'];
    $newStatus = $_POST['newStatus'];

    $updateQuery = "UPDATE Orders SET orderStatus = ? WHERE orderID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $newStatus, $orderID);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Order status updated successfully'];
    } else {
        $response = ['success' => false, 'message' => 'Failed to update order status'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fetch all orders with ratings
$ordersQuery = "SELECT o.orderID, o.userID, o.orderTime, o.orderStatus, o.total, o.paymentMethod,
                       o.rating, u.name, u.email
                FROM Orders o
                LEFT JOIN User u ON o.userID = u.userID
                ORDER BY o.orderTime DESC";

$result = $conn->query($ordersQuery);
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orderID = $row['orderID'];

    $itemsQuery = "SELECT m.itemName, od.variation, od.quantity, od.price
                   FROM OrderDetails od
                   JOIN Menu m ON od.itemID = m.itemID
                   WHERE od.orderID = ?";
    $itemStmt = $conn->prepare($itemsQuery);
    $itemStmt->bind_param("i", $orderID);
    $itemStmt->execute();
    $itemResult = $itemStmt->get_result();

    $items = [];
    while ($itemRow = $itemResult->fetch_assoc()) {
        $itemName = $itemRow['itemName'];
        if (!empty($itemRow['variation'])) {
            $itemName .= ' (' . $itemRow['variation'] . ')';
        }
        $items[] = [
            'name' => $itemName,
            'quantity' => $itemRow['quantity'],
            'price' => $itemRow['price']
        ];
    }

    $displayOrderID = 'ORD-' . date('Y', strtotime($row['orderTime'])) . '-' . str_pad($orderID, 4, '0', STR_PAD_LEFT);

    $orders[] = [
        'orderID' => $orderID,
        'displayOrderID' => $displayOrderID,
        'userID' => $row['userID'],
        'username' => $row['name'],
        'email' => $row['email'],
        'orderTime' => $row['orderTime'],
        'orderStatus' => $row['orderStatus'],
        'paymentMethod' => $row['paymentMethod'],
        'total' => $row['total'],
        'rating' => $row['rating'],
        'items' => $items
    ];
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin - View Feedback & Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    <!-- Main Content-->
    <div class="container my-4">
        <div class="admin-container">
            <div class="admin-header">
                <h1 class="h3 mb-0"><i class="fas fa-star me-2"></i>Feedback</h1>
                <p class="mb-0 mt-2">View order status and feedback from customers</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4 row-cols-5">
            <div class="col ">
                <div class="stats-card">
                    <div class="stats-number" id="total-orders">0</div>
                    <div class="text-muted">Total Orders</div>
                </div>
            </div>
            <div class="col ">
                <div class="stats-card">
                    <div class="stats-number" id="pending-orders">0</div>
                    <div class="text-muted">Non-completed Orders</div>
                </div>
            </div>
            <div class="col ">
                <div class="stats-card">
                    <div class="stats-number" id="completed-orders">0</div>
                    <div class="text-muted">Completed Orders</div>
                </div>
            </div>
            <div class="col ">
                <div class="stats-card">
                    <div class="stats-number" id="cancelled-orders">0</div>
                    <div class="text-muted">Cancelled Orders</div>
                </div>
            </div>
            <div class="col ">
                <div class="stats-card">
                    <div class="stats-number" id="rated-orders">0</div>
                    <div class="text-muted">Rated Orders</div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs filter-tabs" id="orderTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button"
                    role="tab">
                    All Orders
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rated-tab" data-bs-toggle="tab" data-bs-target="#rated" type="button"
                    role="tab">
                    With Ratings
                </button>
            </li>
        </ul>

        <!-- Orders Content -->
        <div class="tab-content" id="orderTabsContent">
            <!-- All Orders Tab -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <div id="orders-container">
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card" data-status="<?= strtolower(str_replace(' ', '', $order['orderStatus'])) ?>"
                                data-rated="<?= $order['rating'] ? 'true' : 'false' ?>">
                                <div class="order-header">
                                    <div>
                                        <strong><?= htmlspecialchars($order['displayOrderID']) ?></strong>
                                        <span class="ms-3 text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($order['username']) ?>
                                        </span>
                                        <span class="ms-3 text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('M j, Y g:i A', strtotime($order['orderTime'])) ?>
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if ($order['rating']): ?>
                                            <div class="rating-display">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?= $i <= $order['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2 text-muted">(<?= $order['rating'] ?>/5)</span>
                                            </div>
                                        <?php endif; ?>
                                        <span
                                            class="status-badge status-<?= strtolower(str_replace(' ', '', $order['orderStatus'])) ?>">
                                            <?= htmlspecialchars($order['orderStatus']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="order-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-3">Order Items:</h6>
                                            <ul class="items-list">
                                                <?php foreach ($order['items'] as $item): ?>
                                                    <li>
                                                        <span><?= htmlspecialchars($item['name']) ?></span>
                                                        <span class="text-muted">x<?= $item['quantity'] ?> -
                                                            RM<?= number_format($item['price'], 2) ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <strong>Customer:</strong> <?= htmlspecialchars($order['username']) ?>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Email:</strong> <?= htmlspecialchars($order['email']) ?>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Payment:</strong> <?= htmlspecialchars($order['paymentMethod']) ?>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Total:</strong> <span
                                                    class="fw-bold text-success">RM<?= number_format($order['total'], 2) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
        <!--be careful with this upper or lower case should based on staff update status(Inpreparation) but also can no need for this fucntion bzc it is being control by staff-->
                                <?php if ($order['orderStatus'] === 'Inpreparation'): ?>
                                    <div class="order-actions">
                                        <button class="btn btn-status"
                                            onclick="updateOrderStatus(<?= $order['orderID'] ?>, 'Completed', this)">
                                            <i class="fas fa-check me-1"></i>
                                            Mark as Completed
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                            <h4>No Orders Found</h4>
                            <p>No orders have been placed yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- With Ratings Tab -->
            <div class="tab-pane fade" id="rated" role="tabpanel">
                <div id="rated-orders-container">
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <?php if ($order['rating']): // Only show orders with ratings ?>
                                <div class="order-card rated-order"
                                    data-status="<?= strtolower(str_replace(' ', '', $order['orderStatus'])) ?>" data-rated="true">
                                    <div class="order-header">
                                        <div>
                                            <strong><?= htmlspecialchars($order['displayOrderID']) ?></strong>
                                            <span class="ms-3 text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?= htmlspecialchars($order['username']) ?>
                                            </span>
                                            <span class="ms-3 text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('M j, Y g:i A', strtotime($order['orderTime'])) ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rating-display">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?= $i <= $order['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2 text-muted">(<?= $order['rating'] ?>/5)</span>
                                            </div>
                                            <span
                                                class="status-badge status-<?= strtolower(str_replace(' ', '', $order['orderStatus'])) ?>">
                                                <?= htmlspecialchars($order['orderStatus']) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="order-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="mb-3">Order Items:</h6>
                                                <ul class="items-list">
                                                    <?php foreach ($order['items'] as $item): ?>
                                                        <li>
                                                            <span><?= htmlspecialchars($item['name']) ?></span>
                                                            <span class="text-muted">x<?= $item['quantity'] ?> -
                                                                RM<?= number_format($item['price'], 2) ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-2">
                                                    <strong>Customer:</strong> <?= htmlspecialchars($order['username']) ?>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Email:</strong> <?= htmlspecialchars($order['email']) ?>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Payment:</strong> <?= htmlspecialchars($order['paymentMethod']) ?>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Total:</strong> <span
                                                        class="fw-bold text-success">RM<?= number_format($order['total'], 2) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-star fa-3x mb-3"></i>
                            <h4>No Rated Orders Found</h4>
                            <p>No orders have been rated yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateOrderStatus(orderID, newStatus, button) {
            if (confirm(`Are you sure you want to mark this order as ${newStatus}?`)) {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('orderID', orderID);
                formData.append('newStatus', newStatus);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const orderCards = document.querySelectorAll(`.order-card[data-order-id="${orderID}"]`);
                            orderCards.forEach(orderCard => {
                                const statusBadge = orderCard.querySelector('.status-badge');
                                statusBadge.textContent = newStatus;
                                statusBadge.className = `status-badge status-${newStatus.toLowerCase().replace(' ', '')}`;

                                orderCard.setAttribute('data-status', newStatus.toLowerCase().replace(' ', ''));

                                const actionButton = orderCard.querySelector('.order-actions');
                                if (actionButton) {
                                    actionButton.remove();
                                }
                            });

                            updateStatistics();

                            alert('Order status updated successfully!');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the order status.');
                    });
            }
        }

        // Update statistics
        function updateStatistics() {
            const orders = document.querySelectorAll('#all .order-card'); // Only count orders in "All" tab
            let totalOrders = orders.length;
            let pendingOrders = 0;
            let completedOrders = 0;
            let cancelledOrders = 0;
            let ratedOrders = 0;

            orders.forEach(order => {
                const status = order.getAttribute('data-status');
                const rated = order.getAttribute('data-rated') === 'true';

                if (status === 'inpreparation' || status === 'ready' || status === 'pending' || status === 'accepted') {
                    pendingOrders++;
                }
                if (status === 'cancelled') {
                    cancelledOrders++;
                }
                if (status === 'completed') {
                    completedOrders++;
                }
                if (rated) {
                    ratedOrders++;
                }
            });

            document.getElementById('total-orders').textContent = totalOrders;
            document.getElementById('pending-orders').textContent = pendingOrders;
            document.getElementById('completed-orders').textContent = completedOrders;
            document.getElementById('cancelled-orders').textContent = cancelledOrders;
            document.getElementById('rated-orders').textContent = ratedOrders;
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateStatistics();
        });
    </script>
</body>

</html>