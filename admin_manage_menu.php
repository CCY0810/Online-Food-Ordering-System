<?php
session_start();

require_once("config.php");

// Handle form submissions
$message = '';
$messageType = '';

// Add menu item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_menu') {
    $itemName = mysqli_real_escape_string($conn, $_POST['itemName']);
    $itemPrice = (float)$_POST['itemPrice'];
    $categoryID = (int)$_POST['categoryID'];
    $userID = $_SESSION['user_id'];

    // Get category name from ID
    $catRes = mysqli_query($conn, "SELECT categoryName FROM Category WHERE categoryID = $categoryID");
    $catRow = mysqli_fetch_assoc($catRes);
    $categoryName = $catRow ? $catRow['categoryName'] : '';

    // Generate unique itemID
    $prefix = strtoupper(substr($categoryName, 0, 1));
    $result = mysqli_query($conn,"SELECT itemID FROM Menu WHERE itemID LIKE '$prefix%' ORDER BY itemID DESC LIMIT 1");
    $lastID = 0;
    if ($row = mysqli_fetch_assoc($result)) {
        $lastID = (int)substr($row['itemID'], 1);
    }
    $newID = $prefix . str_pad($lastID + 1, 3, '0', STR_PAD_LEFT);

    $sql = "INSERT INTO Menu (itemID, userID, category, itemName, itemPrice, availability, categoryID) VALUES ('$newID', '$userID', '$categoryName', '$itemName', $itemPrice, 99, $categoryID)";
    if (mysqli_query($conn, $sql)) {
        $message = "Menu item added!";
        $messageType = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

// Delete menu item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_menu') {
    $itemID = mysqli_real_escape_string($conn, $_POST['itemID']);
    // Check for references in OrderDetails
    $check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM OrderDetails WHERE itemID = '$itemID'");
    $row = mysqli_fetch_assoc($check);
    if ($row['cnt'] > 0) {
        $message = "Cannot delete: This menu item is used in order history!";
        $messageType = "danger";
    } else {
        $sql = "DELETE FROM Menu WHERE itemID = '$itemID'";
        if (mysqli_query($conn, $sql)) {
            $message = "Menu item deleted!";
            $messageType = "success";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_category':
                $categoryName = mysqli_real_escape_string($conn, trim($_POST['category_name']));
                $categoryDescription = mysqli_real_escape_string($conn, trim($_POST['category_description']));
                
                if (!empty($categoryName)) {
                    // Check if category already exists
                    $checkSql = "SELECT * FROM Category WHERE categoryName = '$categoryName'";
                    $checkResult = mysqli_query($conn, $checkSql);
                    
                    if (mysqli_num_rows($checkResult) > 0) {
                        $message = "Category already exists!";
                        $messageType = "danger";
                    } else {
                        $sql = "INSERT INTO Category (categoryName, categoryDescription) VALUES ('$categoryName', '$categoryDescription')";
                        if (mysqli_query($conn, $sql)) {
                            $message = "Category added successfully!";
                            $messageType = "success";
                        } else {
                            $message = "Error adding category: " . mysqli_error($conn);
                            $messageType = "danger";
                        }
                    }
                } else {
                    $message = "Category name is required!";
                    $messageType = "danger";
                }
                break;
                
            case 'update_category':
                $categoryId = (int)$_POST['category_id'];
                $categoryName = mysqli_real_escape_string($conn, trim($_POST['category_name']));
                $categoryDescription = mysqli_real_escape_string($conn, trim($_POST['category_description']));
                
                if (!empty($categoryName)) {
                    $sql = "UPDATE Category SET categoryName = '$categoryName', categoryDescription = '$categoryDescription' WHERE categoryID = $categoryId";
                    if (mysqli_query($conn, $sql)) {
                        $message = "Category updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating category: " . mysqli_error($conn);
                        $messageType = "danger";
                    }
                } else {
                    $message = "Category name is required!";
                    $messageType = "danger";
                }
                break;
                
            case 'delete_category':
                $categoryId = (int)$_POST['category_id'];
                
                // Check if category has menu items
                $checkSql = "SELECT COUNT(*) as count FROM Menu WHERE category = (SELECT categoryName FROM Category WHERE categoryID = $categoryId)";
                $checkResult = mysqli_query($conn, $checkSql);
                $row = mysqli_fetch_assoc($checkResult);
                
                if ($row['count'] > 0) {
                    $message = "Cannot delete category. There are menu items using this category!";
                    $messageType = "danger";
                } else {
                    $sql = "DELETE FROM Category WHERE categoryID = $categoryId";
                    if (mysqli_query($conn, $sql)) {
                        $message = "Category deleted successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error deleting category: " . mysqli_error($conn);
                        $messageType = "danger";
                    }
                }
                break;
        }
    }
}

// // Fetch all categories
// $categoriesQuery = "SELECT * FROM Category ORDER BY categoryName";
// $categoriesResult = mysqli_query($conn, $categoriesQuery);
// $categories = [];
// if ($categoriesResult) {
//     while ($row = mysqli_fetch_assoc($categoriesResult)) {
//         $categories[] = $row;
//     }
// }

$categoriesQuery = "SELECT * FROM Category ORDER BY categoryName";
$categoriesResult = mysqli_query($conn, $categoriesQuery);
$categories = [];
if ($categoriesResult) {
    while ($row = mysqli_fetch_assoc($categoriesResult)) {
        $categories[] = $row;
    }
}

// Fetch menu items grouped by category
$menuQuery = "SELECT m.*, c.categoryName as categoryDisplayName 
              FROM Menu m 
              LEFT JOIN Category c ON m.category = c.categoryName 
              ORDER BY m.category, m.itemName";
$menuResult = mysqli_query($conn, $menuQuery);
$menuItems = [];
if ($menuResult) {
    while ($row = mysqli_fetch_assoc($menuResult)) {
        $category = $row['category'] ?: 'Uncategorized';
        if (!isset($menuItems[$category])) {
            $menuItems[$category] = [];
        }
        $menuItems[$category][] = $row;
    }
}

// Update menu item availability (+1 or -1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_availability') {
    $itemID = mysqli_real_escape_string($conn, $_POST['itemID']);
    $change = $_POST['change'] === 'plus' ? 1 : -1;
    // Only allow positive availability
    $sql = "UPDATE Menu SET availability = GREATEST(0, availability + $change) WHERE itemID = '$itemID'";
    if (mysqli_query($conn, $sql)) {
        $message = ($change > 0 ? 'Increased' : 'Decreased') . " quantity!";
        $messageType = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Category Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            padding-top: 70px;
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f8f9fa;
        }

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

        /* Main content styles */
        .admin-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }

        .category-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .menu-item-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            transition: transform 0.2s;
        }

        .menu-item-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-action {
            padding: 5px 10px;
            font-size: 0.875rem;
            border-radius: 4px;
        }

        .category-stats {
            background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge-category {
            font-size: 0.75rem;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="container-fluid bg-dark fixed-top shadow-sm d-flex justify-content-between align-items-center px-4" style="height: 70px;">
        <div class="text-white fs-4 fw-bold">CC Food Ordering System</div>
        <nav class="d-flex align-items-center gap-3 gap-lg-5">
            <a href="mainPage.php" class="text-white text-decoration-none fw-medium position-relative">Home</a>
            <a href="admin_manage_menu.php" class="text-white text-decoration-none fw-medium position-relative">Manage Menu</a>
            <a href="admin_sales_report.php" class="text-white text-decoration-none fw-medium position-relative">Sales Report</a>
            <a href="admin_manage_user.php" class="text-white text-decoration-none fw-medium position-relative">Manage User</a>
            <a href="admin_feedback.php" class="text-white text-decoration-none fw-medium position-relative">Feedback</a>
            <div class="dropdown">
                <a href="#" class="header-link text-white text-decoration-none fw-medium d-flex align-items-center gap-2 dropdown-toggle"
                   id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="assets/user2.png" alt="Profile" class="img-fluid" style="width: 24px; height: 24px;">
                    <span class="d-none d-sm-inline">Profile</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="admin_profile.php">My Profile</a></li>
                    <!--li><a class="dropdown-item" href="edit_profile.php">Edit Profile</a></li-->
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Page Header -->
        <div class="admin-container">
            <div class="admin-header">
                <h1 class="h3 mb-0"><i class="fas fa-tags me-2"></i>Food Category Management</h1>
                <p class="mb-0 mt-2">Manage food categories and view menu items</p>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-number"><?= count($categories) ?></div>
                    <div class="text-muted">Total Categories</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-number"><?= array_sum(array_map('count', $menuItems)) ?></div>
                    <div class="text-muted">Total Menu Items</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-number"><?= count($menuItems) ?></div>
                    <div class="text-muted">Active Categories</div>
                </div>
            </div>
        </div>

        <!-- Category Management Section -->
        <div class="admin-container mb-4">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="fas fa-list me-2"></i>Categories</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus me-2"></i>Add New Category
                    </button>
                </div>

                <div class="row">
                    <?php foreach ($categories as $category): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="category-card p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold text-primary"><?= htmlspecialchars($category['categoryName']) ?></h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="editCategory(<?= $category['categoryID'] ?>, '<?= htmlspecialchars($category['categoryName']) ?>', '<?= htmlspecialchars($category['categoryDescription']) ?>')">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" onclick="deleteCategory(<?= $category['categoryID'] ?>, '<?= htmlspecialchars($category['categoryName']) ?>')">
                                                <i class="fas fa-trash me-2"></i>Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($category['categoryDescription']) ?: 'No description' ?></p>
                            <small class="text-success">
                                <i class="fas fa-utensils me-1"></i>
                                <?php
                                $itemCount = isset($menuItems[$category['categoryName']]) ? count($menuItems[$category['categoryName']]) : 0;
                                echo $itemCount . ' item' . ($itemCount !== 1 ? 's' : '');
                                ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($categories)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No categories found</h5>
                    <p class="text-muted">Add your first category to get started</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Menu Items by Category -->
        <div class="admin-container">
            <div class="p-4">
                <h4 class="mb-4"><i class="fas fa-utensils me-2"></i>Menu Items by Category</h4>
                
                <!-- Add Menu Item Form -->
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="add_menu">
                    <div class="row g-2">
                        <div class="col">
                            <input type="text" name="itemName" class="form-control" placeholder="Item Name" required>
                        </div>
                        <div class="col">
                            <input type="number" step="0.01" name="itemPrice" class="form-control" placeholder="Price" required>
                        </div>
                        <div class="col">
                            <select name="categoryID" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['categoryID'] ?>"><?= htmlspecialchars($cat['categoryName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <button type="submit" class="btn btn-success">Add Item</button>
                        </div>
                    </div>
                </form>
                
                <?php foreach ($menuItems as $categoryName => $items): ?>
                <div class="mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <h5 class="text-primary mb-0 me-3"><?= htmlspecialchars($categoryName) ?></h5>
                        <span class="badge bg-secondary badge-category"><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?></span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Item ID</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><span class="badge bg-info"><?= $item['itemID'] ?></span></td>
                                    <td class="fw-medium"><?= htmlspecialchars($item['itemName']) ?></td>
                                    <td>RM<?= number_format($item['itemPrice'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info me-1" data-bs-toggle="modal" data-bs-target="#qtyModal_<?= $item['itemID'] ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <!-- Quantity Modal -->
                                        <div class="modal fade" id="qtyModal_<?= $item['itemID'] ?>" tabindex="-1" aria-labelledby="qtyModalLabel_<?= $item['itemID'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="qtyModalLabel_<?= $item['itemID'] ?>">Manage Quantity for <?= htmlspecialchars($item['itemName']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <span class="badge bg-secondary mb-3" style="font-size:1.2rem;">Current Quantity: <?= $item['availability'] ?></span>
                                                        <form method="POST" class="d-inline-flex align-items-center justify-content-center gap-2">
                                                            <input type="hidden" name="action" value="update_availability">
                                                            <input type="hidden" name="itemID" value="<?= $item['itemID'] ?>">
                                                            <button type="submit" name="change" value="minus" class="btn btn-outline-warning btn-lg" title="Reduce 1">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                            <button type="submit" name="change" value="plus" class="btn btn-outline-success btn-lg" title="Add 1">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_menu">
                                            <input type="hidden" name="itemID" value="<?= $item['itemID'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($item['itemName']) ?>?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($menuItems)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No menu items found</h5>
                    <p class="text-muted">Add some menu items to see them here</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_category">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Description</label>
                            <textarea class="form-control" id="category_description" name="category_description" rows="3" placeholder="Optional description for this category"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_category">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_category_description" name="category_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<span id="delete_category_name" class="fw-bold"></span>"?</p>
                    <p class="text-muted small">This action cannot be undone. You can only delete categories that have no menu items.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" id="delete_category_id">
                        <button type="submit" class="btn btn-danger">Delete Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-3 text-center mt-5">
        <script src="script/footer.js" type="text/javascript"></script>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(id, name, description) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('edit_category_description').value = description;
            
            const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        }

        function deleteCategory(id, name) {
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_category_name').textContent = name;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
            modal.show();
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>