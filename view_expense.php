<?php
require_once 'db_connect.php';
requireLogin();

$user_id = getCurrentUserId();
$username = getCurrentUsername();
$error_message = '';
$success_message = '';

// Handle delete operation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // DELETE operation
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE expense_id = ? AND user_id = ?");
        $stmt->execute([$_GET['delete'], $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $success_message = 'Expense deleted successfully!';
        } else {
            $error_message = 'Expense not found or you do not have permission to delete it.';
        }
    } catch (PDOException $e) {
        $error_message = 'Failed to delete expense.';
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_by = $_GET['sort'] ?? 'expense_date';
$sort_order = $_GET['order'] ?? 'DESC';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get categories for filter dropdown
try {
    $stmt = $pdo->prepare("SELECT category_id, category_name FROM categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error loading categories.";
    $categories = [];
}

// Build query with filters
$where_conditions = ["e.user_id = ?"];
$params = [$user_id];

if (!empty($search)) {
    $where_conditions[] = "(e.note LIKE ? OR c.category_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "e.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "e.expense_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "e.expense_date <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// Validate sort parameters
$allowed_sorts = ['expense_date', 'amount', 'category_name', 'note'];
$allowed_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $allowed_sorts)) {
    $sort_by = 'expense_date';
}
if (!in_array($sort_order, $allowed_orders)) {
    $sort_order = 'DESC';
}

// Get total count for pagination
try {
    $count_sql = "SELECT COUNT(*) FROM expenses e JOIN categories c ON e.category_id = c.category_id WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);
} catch (PDOException $e) {
    $error_message = "Error loading expenses.";
    $total_records = 0;
    $total_pages = 0;
}

// Get expenses with JOIN operation
try {
    $sql = "SELECT e.*, c.category_name 
            FROM expenses e 
            JOIN categories c ON e.category_id = c.category_id 
            WHERE $where_clause 
            ORDER BY $sort_by $sort_order 
            LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error loading expenses.";
    $expenses = [];
}

// Calculate total amount for current filtered results
try {
    $total_sql = "SELECT SUM(e.amount) as total FROM expenses e JOIN categories c ON e.category_id = c.category_id WHERE $where_clause";
    $stmt = $pdo->prepare($total_sql);
    $stmt->execute($params);
    $filtered_total = $stmt->fetchColumn() ?? 0;
} catch (PDOException $e) {
    $filtered_total = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Expenses - Expense Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Expense Tracker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_expense.php"><i class="bi bi-plus-circle me-1"></i>Add Expense</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_expense.php"><i class="bi bi-table me-1"></i>View Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="report.php"><i class="bi bi-graph-up me-1"></i>Reports</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            Welcome, <?php echo htmlspecialchars($username); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-5 mb-3">View Expenses</h1>
                <p class="text-muted">Manage and track your expenses</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by note or category...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" 
                                        <?php echo ($category_filter == $category['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="view_expense.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Total Records</h6>
                        <h4 class="text-primary"><?php echo number_format($total_records); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Filtered Total</h6>
                        <h4 class="text-success">₹<?php echo number_format($filtered_total, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Expenses</h5>
                <a href="add_expense.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add New Expense</a>
            </div>
            <div class="card-body">
                <?php if (empty($expenses)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No expenses found.</p>
                        <a href="add_expense.php" class="btn btn-primary">Add Your First Expense</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'expense_date', 'order' => ($sort_by == 'expense_date' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                                           class="text-white text-decoration-none">
                                            Date
                                            <?php if ($sort_by == 'expense_date'): ?>
                                                <?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'category_name', 'order' => ($sort_by == 'category_name' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                                           class="text-white text-decoration-none">
                                            Category
                                            <?php if ($sort_by == 'category_name'): ?>
                                                <?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'amount', 'order' => ($sort_by == 'amount' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                                           class="text-white text-decoration-none">
                                            Amount
                                            <?php if ($sort_by == 'amount'): ?>
                                                <?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>Note</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($expense['category_name']); ?></span>
                                        </td>
                                        <td class="fw-bold">₹<?php echo number_format($expense['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($expense['note'] ?? ''); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_expense.php?id=<?php echo $expense['expense_id']; ?>" 
                                                   class="btn btn-edit btn-sm"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                                                <a href="?delete=<?php echo $expense['expense_id']; ?>" 
                                                   class="btn btn-delete btn-sm"
                                                   onclick="return confirm('Are you sure you want to delete this expense?')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Expenses pagination">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="mb-0">&copy; 2025 Expense Tracker. Web Assignment.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form on filter change
        document.getElementById('category').addEventListener('change', function() {
            this.form.submit();
        });
        
        // Date validation
        document.getElementById('date_from').addEventListener('change', function() {
            const dateTo = document.getElementById('date_to');
            if (this.value && dateTo.value && this.value > dateTo.value) {
                dateTo.value = this.value;
            }
        });
        
        document.getElementById('date_to').addEventListener('change', function() {
            const dateFrom = document.getElementById('date_from');
            if (this.value && dateFrom.value && this.value < dateFrom.value) {
                dateFrom.value = this.value;
            }
        });
    </script>
</body>
</html>
