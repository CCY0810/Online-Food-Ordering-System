<?php
session_start();

require_once("config.php");

// Date range filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$period = isset($_GET['period']) ? $_GET['period'] : 'month';

// Sales Statistics
$totalSales = 0;
$totalOrders = 0;
$completedOrders = 0;
$avgOrderValue = 0;

// Get total sales and orders
$salesQuery = "SELECT 
    COUNT(*) as total_orders,
    COUNT(CASE WHEN orderStatus = 'Completed' THEN 1 END) as completed_orders,
    COALESCE(SUM(CASE WHEN orderStatus = 'Completed' THEN total ELSE 0 END), 0) as total_sales
    FROM Orders 
    WHERE DATE(orderTime) BETWEEN ? AND ?";

$stmt = $conn->prepare($salesQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$salesData = $result->fetch_assoc();

$totalOrders = $salesData['total_orders'];
$completedOrders = $salesData['completed_orders'];
$totalSales = $salesData['total_sales'];
$avgOrderValue = $completedOrders > 0 ? $totalSales / $completedOrders : 0;

// Daily Sales Data for Chart
$dailySales = [];
$dailySalesQuery = "SELECT 
    DATE(orderTime) as order_date,
    COUNT(*) as orders_count,
    COALESCE(SUM(CASE WHEN orderStatus = 'Completed' THEN total ELSE 0 END), 0) as daily_sales
    FROM Orders 
    WHERE DATE(orderTime) BETWEEN ? AND ?
    GROUP BY DATE(orderTime)
    ORDER BY order_date";

$stmt = $conn->prepare($dailySalesQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $dailySales[] = $row;
}

// Top Selling Items
$topItems = [];
$topItemsQuery = "SELECT 
    m.itemName,
    SUM(od.quantity) as total_quantity,
    COALESCE(SUM(od.quantity * od.price), 0) as total_revenue
    FROM OrderDetails od
    JOIN Menu m ON od.itemID = m.itemID
    JOIN Orders o ON od.orderID = o.orderID
    WHERE o.orderStatus = 'Completed' AND DATE(o.orderTime) BETWEEN ? AND ?
    GROUP BY m.itemID, m.itemName
    ORDER BY total_quantity DESC
    LIMIT 10";

$stmt = $conn->prepare($topItemsQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $topItems[] = $row;
}

// Sales by Category
$categorySales = [];
$categoryQuery = "SELECT 
    c.categoryName,
    COUNT(od.orderDetailID) as items_sold,
    COALESCE(SUM(od.quantity * od.price), 0) as category_revenue
    FROM OrderDetails od
    JOIN Menu m ON od.itemID = m.itemID
    JOIN Category c ON m.categoryID = c.categoryID
    JOIN Orders o ON od.orderID = o.orderID
    WHERE o.orderStatus = 'Completed' AND DATE(o.orderTime) BETWEEN ? AND ?
    GROUP BY c.categoryID, c.categoryName
    ORDER BY category_revenue DESC";

$stmt = $conn->prepare($categoryQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $categorySales[] = $row;
}

// Payment Method Statistics
$paymentStats = [];
$paymentQuery = "SELECT 
    paymentMethod,
    COUNT(*) as method_count,
    COALESCE(SUM(total), 0) as method_revenue
    FROM Orders 
    WHERE orderStatus = 'Completed' AND DATE(orderTime) BETWEEN ? AND ?
    GROUP BY paymentMethod
    ORDER BY method_revenue DESC";

$stmt = $conn->prepare($paymentQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $paymentStats[] = $row;
}

// Order Status Distribution
$statusStats = [];
$statusQuery = "SELECT 
    orderStatus,
    COUNT(*) as status_count
    FROM Orders 
    WHERE DATE(orderTime) BETWEEN ? AND ?
    GROUP BY orderStatus
    ORDER BY status_count DESC";

$stmt = $conn->prepare($statusQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $statusStats[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Sales Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid;
        }

        .metric-card:hover {
            transform: translateY(-5px);
        }

        .metric-card.sales {
            border-left-color: #00b894;
        }

        .metric-card.orders {
            border-left-color: #667eea;
        }

        .metric-card.average {
            border-left-color:#fde73d;
        }

        .metric-card.completed {
            border-left-color: #00cec9;
        }

        .metric-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .export-buttons {
            gap: 0.5rem;
        }

        .date-filter-form {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
    </style>
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
            <a href="admin_sales_report.php" class="text-white text-decoration-none fw-medium position-relative">Sales
                Report</a>
            <a href="admin_manage_user.php" class="text-white text-decoration-none fw-medium position-relative">Manage
                User</a>
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
    <main class="container my-4">
        <div class="admin-container">
            <div class="admin-header">
                <h1 class="h3 mb-0"><i class="fas fa-chart-bar me-2"></i>Sales Report Dashboard</h1>
                <p class="mb-0 mt-2">Comprehensive sales analytics and performance metrics</p>
            </div>
        </div>

        <!-- Date Filter Form -->
        <div class="date-filter-form">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date"
                        value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date"
                        value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-3">
                    <label for="period" class="form-label">Quick Select</label>
                    <select class="form-select" id="period" name="period">
                        <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>This Week</option>
                        <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>This Month</option>
                        <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>This Year</option>
                        <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="metric-card sales">
                    <div class="metric-number text-success">RM<?= number_format($totalSales, 2) ?></div>
                    <div class="metric-label">Total Sales</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="metric-card orders">
                    <div class="metric-number text-primary"><?= number_format($totalOrders) ?></div>
                    <div class="metric-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="metric-card completed">
                    <div class="metric-number text-info"><?= number_format($completedOrders) ?></div>
                    <div class="metric-label">Completed Orders</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="metric-card average">
                    <div class="metric-number text-warning">RM<?= number_format($avgOrderValue, 2) ?></div>
                    <div class="metric-label">Average Order Value</div>
                </div>
            </div>
        </div>

        <div class="admin-container  p-4">
            <!-- Export Buttons -->
            <div class="d-flex justify-content-end mb-4 export-buttons">
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print Report
                </button>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <!-- Sales Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="table-container">
                        <h5 class="section-header">Sales Trend</h5>
                        <div class="p-3">
                            <div class="chart-container">
                                <canvas id="dailySalesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Status Distribution -->
                <div class="col-lg-4 mb-4">
                    <div class="table-container">
                        <h5 class="section-header">Order Status Distribution</h5>
                        <div class="p-3">
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Tables Row -->
            <div class="row">
                <!-- Top Selling Items -->
                <div class="col-lg-6 mb-4">
                    <div class="table-container">
                        <h5 class="section-header">Top Selling Items</h5>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Quantity Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topItems)): ?>
                                        <tr>    
                                            <td colspan="3" class="text-center py-4 text-muted">
                                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                No sales data available for the selected period
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($topItems as $index => $item): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                                    <?= htmlspecialchars($item['itemName']) ?>
                                                </td>
                                                <td><?= number_format($item['total_quantity']) ?></td>
                                                <td class="fw-bold text-success">
                                                    RM<?= number_format($item['total_revenue'], 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sales by Category -->
                <div class="col-lg-6 mb-4">
                    <div class="table-container">
                        <h5 class="section-header">Sales by Category</h5>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th>Items Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categorySales)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">
                                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                No category data available
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categorySales as $category): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-category bg-info me-2">
                                                        <?= htmlspecialchars($category['categoryName']) ?>
                                                    </span>
                                                </td>
                                                <td><?= number_format($category['items_sold']) ?></td>
                                                <td class="fw-bold text-success">
                                                    RM<?= number_format($category['category_revenue'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="table-container">
                        <h5 class="section-header">Payment Method Statistics</h5>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Payment Method</th>
                                        <th>Number of Orders</th>
                                        <th>Total Revenue</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($paymentStats)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="fas fa-credit-card fa-2x mb-2"></i><br>
                                                No payment data available
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php
                                        $totalPaymentRevenue = array_sum(array_column($paymentStats, 'method_revenue'));
                                        foreach ($paymentStats as $payment):
                                            $percentage = $totalPaymentRevenue > 0 ? ($payment['method_revenue'] / $totalPaymentRevenue) * 100 : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <i
                                                        class="fas fa-<?= $payment['paymentMethod'] === 'Cash' ? 'money-bill' : 'credit-card' ?> me-2 text-primary"></i>
                                                    <?= htmlspecialchars($payment['paymentMethod']) ?>
                                                </td>
                                                <td><?= number_format($payment['method_count']) ?></td>
                                                <td class="fw-bold text-success">
                                                    RM<?= number_format($payment['method_revenue'], 2) ?></td>
                                                <td>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-primary" style="width: <?= $percentage ?>%">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted"><?= number_format($percentage, 1) ?>%</small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Daily Sales Chart
        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        const dailySalesData = <?= json_encode($dailySales) ?>;

        new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: dailySalesData.map(item => item.order_date),
                datasets: [{
                    label: 'Daily Sales (RM)',
                    data: dailySalesData.map(item => parseFloat(item.daily_sales)),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Number of Orders',
                    data: dailySalesData.map(item => parseInt(item.orders_count)),
                    borderColor: '#00b894',
                    backgroundColor: 'rgba(0, 184, 148, 0.1)',
                    borderWidth: 2,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Sales (RM)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Order Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = <?= json_encode($statusStats) ?>;

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(item => item.orderStatus),
                datasets: [{
                    data: statusData.map(item => parseInt(item.status_count)),
                    backgroundColor: [
                        '#00b894', // Completed - Green
                        '#667eea', // Pending - Blue
                        '#fd79a8', // In Preparation - Pink
                        '#00cec9', // Ready - Teal
                        '#74b9ff', // Accepted - Light Blue
                        '#e17055'  // Cancelled - Orange Red
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Period selector change handler
        document.getElementById('period').addEventListener('change', function () {
            const period = this.value;
            const today = new Date();
            let startDate, endDate;

            switch (period) {
                case 'today':
                    startDate = endDate = today.toISOString().split('T')[0];
                    break;
                case 'week':
                    const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
                    startDate = weekStart.toISOString().split('T')[0];
                    endDate = new Date().toISOString().split('T')[0];
                    break;
                case 'month':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                    endDate = new Date().toISOString().split('T')[0];
                    break;
                case 'year':
                    startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                    endDate = new Date().toISOString().split('T')[0];
                    break;
                default:
                    return; // Custom - let user set dates manually
            }

            document.getElementById('start_date').value = startDate;
            document.getElementById('end_date').value = endDate;
        });
    </script>
</body>

</html>