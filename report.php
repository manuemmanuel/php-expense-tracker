<?php
require_once 'db_connect.php';
requireLogin();

$user_id = getCurrentUserId();
$username = getCurrentUsername();
$error_message = '';

// Get filter parameters
$report_type = $_GET['type'] ?? 'monthly';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

// Validate parameters
$allowed_types = ['monthly', 'category', 'yearly'];
if (!in_array($report_type, $allowed_types)) {
    $report_type = 'monthly';
}

$year = (int)$year;
$month = (int)$month;

try {
    // Get total expenses for the user
    $stmt = $pdo->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_expenses = $stmt->fetch()['total_expenses'] ?? 0;
    
    // Get total entries
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_entries FROM expenses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_entries = $stmt->fetch()['total_entries'];
    
    // Get average expense
    $avg_expense = $total_entries > 0 ? $total_expenses / $total_entries : 0;
    
    // Monthly Report - Expenses by month for the selected year
    if ($report_type == 'monthly') {
        $stmt = $pdo->prepare("
            SELECT 
                MONTH(expense_date) as month,
                MONTHNAME(expense_date) as month_name,
                COUNT(*) as expense_count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
            FROM expenses 
            WHERE user_id = ? 
            AND YEAR(expense_date) = ?
            GROUP BY MONTH(expense_date), MONTHNAME(expense_date)
            ORDER BY month ASC
        ");
        $stmt->execute([$user_id, $year]);
        $monthly_data = $stmt->fetchAll();
        
        // Get year-over-year comparison
        $stmt = $pdo->prepare("
            SELECT 
                YEAR(expense_date) as year,
                SUM(amount) as total_amount
            FROM expenses 
            WHERE user_id = ? 
            GROUP BY YEAR(expense_date)
            ORDER BY year DESC
            LIMIT 3
        ");
        $stmt->execute([$user_id]);
        $yearly_comparison = $stmt->fetchAll();
    }
    
    // Category Report - Expenses by category
    elseif ($report_type == 'category') {
        $stmt = $pdo->prepare("
            SELECT 
                c.category_name,
                COUNT(e.expense_id) as expense_count,
                SUM(e.amount) as total_amount,
                AVG(e.amount) as avg_amount,
                MIN(e.amount) as min_amount,
                MAX(e.amount) as max_amount
            FROM categories c
            LEFT JOIN expenses e ON c.category_id = e.category_id AND e.user_id = ?
            GROUP BY c.category_id, c.category_name
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$user_id]);
        $category_data = $stmt->fetchAll();
        
        // Get category trends (last 6 months)
        $stmt = $pdo->prepare("
            SELECT 
                c.category_name,
                DATE_FORMAT(e.expense_date, '%Y-%m') as month,
                SUM(e.amount) as total_amount
            FROM categories c
            LEFT JOIN expenses e ON c.category_id = e.category_id AND e.user_id = ?
            WHERE e.expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY c.category_id, c.category_name, DATE_FORMAT(e.expense_date, '%Y-%m')
            ORDER BY month ASC, total_amount DESC
        ");
        $stmt->execute([$user_id]);
        $category_trends = $stmt->fetchAll();
    }
    
    // Yearly Report - Expenses by year
    elseif ($report_type == 'yearly') {
        $stmt = $pdo->prepare("
            SELECT 
                YEAR(expense_date) as year,
                COUNT(*) as expense_count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount
            FROM expenses 
            WHERE user_id = ? 
            GROUP BY YEAR(expense_date)
            ORDER BY year DESC
        ");
        $stmt->execute([$user_id]);
        $yearly_data = $stmt->fetchAll();
        
        // Get monthly breakdown for the most recent year
        if (!empty($yearly_data)) {
            $recent_year = $yearly_data[0]['year'];
            $stmt = $pdo->prepare("
                SELECT 
                    MONTH(expense_date) as month,
                    MONTHNAME(expense_date) as month_name,
                    SUM(amount) as total_amount
                FROM expenses 
                WHERE user_id = ? 
                AND YEAR(expense_date) = ?
                GROUP BY MONTH(expense_date), MONTHNAME(expense_date)
                ORDER BY month ASC
            ");
            $stmt->execute([$user_id, $recent_year]);
            $recent_year_monthly = $stmt->fetchAll();
        }
    }
    
    // Get available years for dropdown
    $stmt = $pdo->prepare("
        SELECT DISTINCT YEAR(expense_date) as year 
        FROM expenses 
        WHERE user_id = ? 
        ORDER BY year DESC
    ");
    $stmt->execute([$user_id]);
    $available_years = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Error loading report data.";
    $total_expenses = 0;
    $total_entries = 0;
    $avg_expense = 0;
    $monthly_data = [];
    $category_data = [];
    $yearly_data = [];
    $available_years = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Expense Tracker</title>
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
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_expense.php"><i class="bi bi-plus-circle me-1"></i>Add Expense</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_expense.php"><i class="bi bi-table me-1"></i>View Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="report.php"><i class="bi bi-graph-up me-1"></i>Reports</a>
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

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-5 mb-3">Expense Reports</h1>
                <p class="text-muted">Analyze your spending patterns and trends</p>
            </div>
        </div>

        <!-- Report Type Selector -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="type" class="form-label">Report Type</label>
                        <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                            <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                            <option value="category" <?php echo $report_type == 'category' ? 'selected' : ''; ?>>Category Report</option>
                            <option value="yearly" <?php echo $report_type == 'yearly' ? 'selected' : ''; ?>>Yearly Report</option>
                        </select>
                    </div>
                    
                    <?php if ($report_type == 'monthly'): ?>
                        <div class="filter-group">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year" onchange="this.form.submit()">
                                <?php foreach ($available_years as $available_year): ?>
                                    <option value="<?php echo $available_year['year']; ?>" 
                                            <?php echo $year == $available_year['year'] ? 'selected' : ''; ?>>
                                        <?php echo $available_year['year']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card total-expenses">
                    <div class="card-body text-center">
                        <h5 class="card-title text-danger">Total Expenses</h5>
                        <h2 class="display-6 text-danger">₹<?php echo number_format($total_expenses, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card total-entries">
                    <div class="card-body text-center">
                        <h5 class="card-title text-info">Total Entries</h5>
                        <h2 class="display-6 text-info"><?php echo number_format($total_entries); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card avg-expense">
                    <div class="card-body text-center">
                        <h5 class="card-title text-warning">Average Expense</h5>
                        <h2 class="display-6 text-warning">₹<?php echo number_format($avg_expense, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-success">Report Type</h5>
                        <h6 class="text-success text-capitalize"><?php echo $report_type; ?></h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Report -->
        <?php if ($report_type == 'monthly'): ?>
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Monthly Expenses for <?php echo $year; ?></h5>
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
                            <h5 class="mb-0">Year Comparison</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="yearlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Monthly Breakdown</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Expense Count</th>
                                            <th>Total Amount</th>
                                            <th>Average Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($monthly_data as $month_data): ?>
                                            <tr>
                                                <td><?php echo $month_data['month_name']; ?></td>
                                                <td><?php echo number_format($month_data['expense_count']); ?></td>
                                                <td class="fw-bold">₹<?php echo number_format($month_data['total_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format($month_data['avg_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Category Report -->
        <?php if ($report_type == 'category'): ?>
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
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
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Category Trends (Last 6 Months)</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Category Analysis</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Count</th>
                                            <th>Total</th>
                                            <th>Average</th>
                                            <th>Min</th>
                                            <th>Max</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($category_data as $cat_data): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($cat_data['category_name']); ?></span>
                                                </td>
                                                <td><?php echo number_format($cat_data['expense_count']); ?></td>
                                                <td class="fw-bold">₹<?php echo number_format($cat_data['total_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format($cat_data['avg_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format($cat_data['min_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format($cat_data['max_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Yearly Report -->
        <?php if ($report_type == 'yearly'): ?>
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Yearly Expenses</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="yearlyBarChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo isset($recent_year) ? $recent_year : date('Y'); ?> Monthly Breakdown</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="recentYearChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Yearly Analysis</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Year</th>
                                            <th>Count</th>
                                            <th>Total</th>
                                            <th>Average</th>
                                            <th>Min</th>
                                            <th>Max</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($yearly_data as $year_data): ?>
                                            <tr>
                                                <td><?php echo $year_data['year']; ?></td>
                                                <td><?php echo number_format($year_data['expense_count']); ?></td>
                                                <td class="fw-bold">₹<?php echo number_format($year_data['total_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format($year_data['avg_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format($year_data['min_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format($year_data['max_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
        <?php if ($report_type == 'monthly'): ?>
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        
        const monthlyLabels = monthlyData.map(item => item.month_name);
        const monthlyAmounts = monthlyData.map(item => parseFloat(item.total_amount));
        
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Monthly Expenses',
                    data: monthlyAmounts,
                    backgroundColor: '#20c997',
                    borderColor: '#1aa179',
                    borderWidth: 1
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

        // Yearly Comparison Chart
        const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
        const yearlyData = <?php echo json_encode($yearly_comparison); ?>;
        
        const yearlyLabels = yearlyData.map(item => item.year.toString());
        const yearlyAmounts = yearlyData.map(item => parseFloat(item.total_amount));
        
        new Chart(yearlyCtx, {
            type: 'line',
            data: {
                labels: yearlyLabels,
                datasets: [{
                    label: 'Yearly Expenses',
                    data: yearlyAmounts,
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
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
        <?php endif; ?>

        // Category Chart
        <?php if ($report_type == 'category'): ?>
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
        <?php endif; ?>

        // Yearly Bar Chart
        <?php if ($report_type == 'yearly'): ?>
        const yearlyBarCtx = document.getElementById('yearlyBarChart').getContext('2d');
        const yearlyBarData = <?php echo json_encode($yearly_data); ?>;
        
        const yearlyBarLabels = yearlyBarData.map(item => item.year.toString());
        const yearlyBarAmounts = yearlyBarData.map(item => parseFloat(item.total_amount));
        
        new Chart(yearlyBarCtx, {
            type: 'bar',
            data: {
                labels: yearlyBarLabels,
                datasets: [{
                    label: 'Yearly Expenses',
                    data: yearlyBarAmounts,
                    backgroundColor: '#20c997',
                    borderColor: '#1aa179',
                    borderWidth: 1
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
        <?php endif; ?>
    </script>
</body>
</html>
