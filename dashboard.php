<?php
require_once 'db_connect.php';
requireLogin();

$user_id = getCurrentUserId();
$username = getCurrentUsername();

// Get dashboard statistics
try {
    // Total expenses
    $stmt = $pdo->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_expenses = $stmt->fetch()['total_expenses'] ?? 0;
    
    // Total entries
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_entries FROM expenses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_entries = $stmt->fetch()['total_entries'];
    
    // Top spending category
    $stmt = $pdo->prepare("
        SELECT c.category_name, SUM(e.amount) as total_amount 
        FROM expenses e 
        JOIN categories c ON e.category_id = c.category_id 
        WHERE e.user_id = ? 
        GROUP BY c.category_id, c.category_name 
        ORDER BY total_amount DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $top_category = $stmt->fetch();
    
    // Average expense
    $avg_expense = $total_entries > 0 ? $total_expenses / $total_entries : 0;
    
    // Recent expenses (last 5)
    $stmt = $pdo->prepare("
        SELECT e.*, c.category_name 
        FROM expenses e 
        JOIN categories c ON e.category_id = c.category_id 
        WHERE e.user_id = ? 
        ORDER BY e.expense_date DESC, e.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_expenses = $stmt->fetchAll();
    
    // Monthly expenses for chart (last 6 months)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(expense_date, '%Y-%m') as month,
            SUM(amount) as total_amount
        FROM expenses 
        WHERE user_id = ? 
        AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $monthly_data = $stmt->fetchAll();
    
    // Category expenses for pie chart
    $stmt = $pdo->prepare("
        SELECT c.category_name, SUM(e.amount) as total_amount 
        FROM expenses e 
        JOIN categories c ON e.category_id = c.category_id 
        WHERE e.user_id = ? 
        GROUP BY c.category_id, c.category_name 
        ORDER BY total_amount DESC
    ");
    $stmt->execute([$user_id]);
    $category_data = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Error loading dashboard data.";
    $total_expenses = 0;
    $total_entries = 0;
    $top_category = null;
    $avg_expense = 0;
    $recent_expenses = [];
    $monthly_data = [];
    $category_data = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Expense Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_expense.php"><i class="bi bi-plus-circle me-1"></i>Add Expense</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_expense.php"><i class="bi bi-table me-1"></i>View Expenses</a>
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
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-4 text-center mb-3">Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
                <p class="text-center text-muted">Here's your expense summary</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row row-cols-2 row-cols-md-2 row-cols-lg-4 g-3 mb-4">
            <div class="col">
                <div class="card stats-card total-expenses h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title text-danger">Total Expenses</h5>
                        <h2 class="display-6 text-danger">₹<?php echo number_format($total_expenses, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stats-card total-entries h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title text-info">Total Entries</h5>
                        <h2 class="display-6 text-info"><?php echo number_format($total_entries); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stats-card top-category h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title text-success">Top Category</h5>
                        <h6 class="text-success"><?php echo $top_category ? htmlspecialchars($top_category['category_name']) : 'N/A'; ?></h6>
                        <small class="text-muted">₹<?php echo $top_category ? number_format($top_category['total_amount'], 2) : '0.00'; ?></small>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stats-card avg-expense h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title text-warning">Avg Expense</h5>
                        <h2 class="display-6 text-warning">₹<?php echo number_format($avg_expense, 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Monthly Expenses Trend</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Expenses by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Expenses -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Expenses</h5>
                        <a href="view_expense.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-ul me-1"></i>View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_expenses)): ?>
                            <div class="text-center py-4">
                                <p class="text-muted">No expenses recorded yet.</p>
                                <a href="add_expense.php" class="btn btn-primary">Add Your First Expense</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Category</th>
                                            <th>Amount</th>
                                            <th class="d-none d-md-table-cell">Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_expenses as $expense): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($expense['category_name']); ?></span>
                                                </td>
                                                <td class="fw-bold">₹<?php echo number_format($expense['amount'], 2); ?></td>
                                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($expense['note'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
    <script src="js/chart.js"></script>
    <script>
        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        
        const monthlyLabels = monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        
                        const monthlyAmounts = monthlyData.map(item => parseFloat(item.total_amount));
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Monthly Expenses',
                    data: monthlyAmounts,
                    borderColor: '#20c997',
                    backgroundColor: 'rgba(32, 201, 151, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + value.toFixed(2); }
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($category_data); ?>;
        
        const categoryLabels = categoryData.map(item => item.category_name);
        const categoryAmounts = categoryData.map(item => parseFloat(item.total_amount));
        
        const colors = [
            '#20c997', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1',
            '#fd7e14', '#20c997', '#6c757d', '#e83e8c', '#28a745'
        ];
        
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryAmounts,
                    backgroundColor: colors.slice(0, categoryLabels.length),
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
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
