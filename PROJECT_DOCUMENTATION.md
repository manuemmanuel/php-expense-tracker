
## Web Programming Assignment

---

## Group Members

| Name              | Roll Number |
| ----------------- | ----------- |
| [Lidiya Reju]     | [40]        |
| [Manu Emmanuel]   | [41]        |
| [Maria Rose Alex] | [42]        |

---

## Project Overview

### Introduction
The Expense Tracker Web Application is a comprehensive personal finance management system built using modern web technologies. This application allows users to register, log in, and efficiently manage their personal expenses across different categories with detailed reporting and analytics features.

### Project Objectives
- Develop a fully functional web application for expense management
- Implement secure user authentication and session management
- Create an intuitive user interface with responsive design
- Provide comprehensive reporting and analytics capabilities
- Demonstrate proficiency in web development technologies

### Technology Stack
- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Libraries**: Chart.js for data visualization
- **Development Tools**: XAMPP for local development

### Key Features
1. **User Authentication System**
   - Secure registration and login
   - Password hashing for security
   - Session management

2. **Expense Management**
   - Add new expenses with categories
   - View all expenses with filtering and search
   - Edit existing expense records
   - Delete expense entries

3. **Dashboard & Analytics**
   - Summary statistics and metrics
   - Interactive charts and graphs
   - Recent expenses overview
   - Monthly trends analysis

4. **Reporting System**
   - Monthly expense reports
   - Category-wise spending analysis
   - Year-over-year comparisons
   - Export and print functionality

5. **User Interface**
   - Responsive design for all devices
   - Modern, clean interface
   - Intuitive navigation
   - Color-coded categories

### Database Design
The application uses a relational database with three main tables:
- **users**: Stores user account information
- **categories**: Manages expense categories
- **expenses**: Records all expense transactions

### Security Features
- Password hashing using PHP's `password_hash()`
- Prepared statements to prevent SQL injection
- Input validation and sanitization
- Session-based authentication
- Access control for protected pages

---

## Code Files

### 1. Database Connection (`db_connect.php`)

```php
<?php
// Database connection configuration
$host = 'localhost';
$dbname = 'expense_tracker';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current username
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}
?>

```

### 2. Database Setup (`database_setup.sql`)

```sql
-- Create database
CREATE DATABASE IF NOT EXISTS expense_tracker;
USE expense_tracker;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) UNIQUE NOT NULL
);

-- Create expenses table
CREATE TABLE IF NOT EXISTS expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT
);

-- Insert default categories
INSERT INTO categories (category_name) VALUES 
('Food & Dining'),
('Transportation'),
('Shopping'),
('Entertainment'),
('Bills & Utilities'),
('Healthcare'),
('Education'),
('Travel'),
('Rent & Housing'),
('Other')
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

-- Create indexes for better performance
CREATE INDEX idx_expenses_user_date ON expenses(user_id, expense_date);
CREATE INDEX idx_expenses_category ON expenses(category_id);
CREATE INDEX idx_expenses_amount ON expenses(amount);

```

### 3. Login Page (`index.php`)

```php
<?php
require_once 'db_connect.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <h2 class="auth-title">Expense Tracker</h2>
                        <p class="text-muted">Sign in to your account</p>
                    </div>
                    
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
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" name="login" class="btn btn-primary w-100 mb-3"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</button>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? <a href="register.php" class="auth-link">Sign up</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/chart.js"></script>
</body>
</html>

```

### 4. User Registration (`register.php`)

```php
<?php
require_once 'db_connect.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'Please fill in all fields.';
    } elseif (strlen($username) < 3) {
        $error_message = 'Username must be at least 3 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error_message = 'Username or email already exists.';
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);
                
                $success_message = 'Registration successful! You can now log in.';
                // Clear form data
                $_POST = array();
            }
        } catch (PDOException $e) {
            $error_message = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker - Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <h2 class="auth-title">Create Account</h2>
                        <p class="text-muted">Sign up for Expense Tracker</p>
                    </div>
                    
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
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="register" class="btn btn-primary w-100 mb-3"><i class="bi bi-person-plus me-2"></i>Create Account</button>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account? <a href="index.php" class="auth-link">Sign in</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/chart.js"></script>
</body>
</html>

```

### 5. Dashboard (`dashboard.php`)

```php
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

```

### 6. Add Expense (`add_expense.php`)

```php
<?php
require_once 'db_connect.php';
requireLogin();

$user_id = getCurrentUserId();
$username = getCurrentUsername();
$error_message = '';
$success_message = '';

// Get categories for dropdown
try {
    $stmt = $pdo->prepare("SELECT category_id, category_name FROM categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error loading categories.";
    $categories = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
    $amount = trim($_POST['amount']);
    $category_id = $_POST['category_id'];
    $expense_date = $_POST['expense_date'];
    $note = trim($_POST['note']);
    
    // Validation
    if (empty($amount) || empty($category_id) || empty($expense_date)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error_message = 'Please enter a valid amount.';
    } elseif (!strtotime($expense_date)) {
        $error_message = 'Please enter a valid date.';
    } else {
        try {
            // INSERT operation
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, category_id, amount, expense_date, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $category_id, $amount, $expense_date, $note]);
            
            $success_message = 'Expense added successfully!';
            
            // Clear form data
            $_POST = array();
            
        } catch (PDOException $e) {
            $error_message = 'Failed to add expense. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - Expense Tracker</title>
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
                        <a class="nav-link active" href="add_expense.php"><i class="bi bi-plus-circle me-1"></i>Add Expense</a>
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Add New Expense</h4>
                    </div>
                    <div class="card-body">
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
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="amount" class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                               step="0.01" min="0.01" 
                                               value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="expense_date" class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="expense_date" name="expense_date" 
                                           value="<?php echo htmlspecialchars($_POST['expense_date'] ?? date('Y-m-d')); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>" 
                                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="note" class="form-label">Note (Optional)</label>
                                <textarea class="form-control" id="note" name="note" rows="3" 
                                          placeholder="Add a note about this expense..."><?php echo htmlspecialchars($_POST['note'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-outline-secondary me-md-2"><i class="bi bi-x-circle me-1"></i>Cancel</a>
                                <button type="submit" name="add_expense" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i>Add Expense
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Quick Add Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Add Common Expenses</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-outline-primary w-100 quick-add" data-amount="5.00" data-category="Food & Dining"><i class="bi bi-cup-hot me-1"></i>Coffee ₹5.00</button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-outline-primary w-100 quick-add" data-amount="15.00" data-category="Food & Dining"><i class="bi bi-egg-fried me-1"></i>Lunch ₹15.00</button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-outline-primary w-100 quick-add" data-amount="50.00" data-category="Transportation"><i class="bi bi-fuel-pump me-1"></i>Gas ₹50.00</button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-outline-primary w-100 quick-add" data-amount="25.00" data-category="Entertainment"><i class="bi bi-film me-1"></i>Movie ₹25.00</button>
                            </div>
                        </div>
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
        // Quick add functionality
        document.querySelectorAll('.quick-add').forEach(button => {
            button.addEventListener('click', function() {
                const amount = this.dataset.amount;
                const categoryName = this.dataset.category;
                
                // Set amount
                document.getElementById('amount').value = amount;
                
                // Find and select category
                const categorySelect = document.getElementById('category_id');
                for (let option of categorySelect.options) {
                    if (option.text === categoryName) {
                        option.selected = true;
                        break;
                    }
                }
                
                // Focus on note field
                document.getElementById('note').focus();
            });
        });
        
        // Auto-focus on amount field
        document.getElementById('amount').focus();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);
            const category = document.getElementById('category_id').value;
            const date = document.getElementById('expense_date').value;
            
            if (!amount || amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount.');
                document.getElementById('amount').focus();
                return false;
            }
            
            if (!category) {
                e.preventDefault();
                alert('Please select a category.');
                document.getElementById('category_id').focus();
                return false;
            }
            
            if (!date) {
                e.preventDefault();
                alert('Please select a date.');
                document.getElementById('expense_date').focus();
                return false;
            }
        });
    </script>
</body>
</html>

```

### 7. View Expenses (`view_expense.php`)

```php
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

```

### 8. Edit Expense (`edit_expense.php`)

```php
<?php
require_once 'db_connect.php';
requireLogin();

$user_id = getCurrentUserId();
$username = getCurrentUsername();
$error_message = '';
$success_message = '';

// Get expense ID from URL
$expense_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$expense_id) {
    header('Location: view_expense.php');
    exit();
}

// Get categories for dropdown
try {
    $stmt = $pdo->prepare("SELECT category_id, category_name FROM categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error loading categories.";
    $categories = [];
}

// Get current expense data
try {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE expense_id = ? AND user_id = ?");
    $stmt->execute([$expense_id, $user_id]);
    $expense = $stmt->fetch();
    
    if (!$expense) {
        header('Location: view_expense.php');
        exit();
    }
} catch (PDOException $e) {
    header('Location: view_expense.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_expense'])) {
    $amount = trim($_POST['amount']);
    $category_id = $_POST['category_id'];
    $expense_date = $_POST['expense_date'];
    $note = trim($_POST['note']);
    
    // Validation
    if (empty($amount) || empty($category_id) || empty($expense_date)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error_message = 'Please enter a valid amount.';
    } elseif (!strtotime($expense_date)) {
        $error_message = 'Please enter a valid date.';
    } else {
        try {
            // UPDATE operation
            $stmt = $pdo->prepare("UPDATE expenses SET category_id = ?, amount = ?, expense_date = ?, note = ? WHERE expense_id = ? AND user_id = ?");
            $stmt->execute([$category_id, $amount, $expense_date, $note, $expense_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $success_message = 'Expense updated successfully!';
                
                // Update local expense data
                $expense['category_id'] = $category_id;
                $expense['amount'] = $amount;
                $expense['expense_date'] = $expense_date;
                $expense['note'] = $note;
            } else {
                $error_message = 'Failed to update expense.';
            }
        } catch (PDOException $e) {
            $error_message = 'Failed to update expense. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Expense - Expense Tracker</title>
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Edit Expense</h4>
                    </div>
                    <div class="card-body">
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
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="amount" class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                               step="0.01" min="0.01" 
                                               value="<?php echo htmlspecialchars($expense['amount']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="expense_date" class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="expense_date" name="expense_date" 
                                           value="<?php echo htmlspecialchars($expense['expense_date']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>" 
                                                <?php echo ($expense['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="note" class="form-label">Note (Optional)</label>
                                <textarea class="form-control" id="note" name="note" rows="3" 
                                          placeholder="Add a note about this expense..."><?php echo htmlspecialchars($expense['note'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($expense['created_at'])); ?>
                                </small>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="view_expense.php" class="btn btn-outline-secondary me-md-2"><i class="bi bi-x-circle me-1"></i>Cancel</a>
                                <button type="submit" name="update_expense" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i>Update Expense
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Expense History -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Expense Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Expense ID:</strong> #<?php echo $expense['expense_id']; ?></p>
                                <p><strong>Amount:</strong> ₹<?php echo number_format($expense['amount'], 2); ?></p>
                                <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($expense['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($expense['created_at'])); ?></p>
                                <p><strong>Note:</strong> <?php echo htmlspecialchars($expense['note'] ?? 'No note'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="mb-0">&copy; 2024 Expense Tracker. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/chart.js"></script>
    <script>
        // Auto-focus on amount field
        document.getElementById('amount').focus();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);
            const category = document.getElementById('category_id').value;
            const date = document.getElementById('expense_date').value;
            
            if (!amount || amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount.');
                document.getElementById('amount').focus();
                return false;
            }
            
            if (!category) {
                e.preventDefault();
                alert('Please select a category.');
                document.getElementById('category_id').focus();
                return false;
            }
            
            if (!date) {
                e.preventDefault();
                alert('Please select a date.');
                document.getElementById('expense_date').focus();
                return false;
            }
        });
        
        // Auto-save draft functionality (optional)
        let draftTimer;
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(draftTimer);
                draftTimer = setTimeout(() => {
                    // Save draft to localStorage
                    const formData = new FormData(form);
                    const draft = {};
                    for (let [key, value] of formData.entries()) {
                        draft[key] = value;
                    }
                    localStorage.setItem('expense_draft_<?php echo $expense_id; ?>', JSON.stringify(draft));
                }, 1000);
            });
        });
        
        // Load draft on page load
        window.addEventListener('load', function() {
            const draft = localStorage.getItem('expense_draft_<?php echo $expense_id; ?>');
            if (draft) {
                const draftData = JSON.parse(draft);
                Object.keys(draftData).forEach(key => {
                    const element = document.querySelector(`[name="${key}"]`);
                    if (element) {
                        element.value = draftData[key];
                    }
                });
            }
        });
        
        // Clear draft on successful submit
        form.addEventListener('submit', function() {
            localStorage.removeItem('expense_draft_<?php echo $expense_id; ?>');
        });
    </script>
</body>
</html>

```

### 9. Reports (`report.php`)

```php
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

```

### 10. Logout (`logout.php`)

```php
<?php
require_once 'db_connect.php';

// Destroy session and redirect to login
session_destroy();
header('Location: index.php');
exit();
?>

```

### 11. CSS Styling (`css/style.css`)

```css
/* Custom CSS for Expense Tracker */

:root {
    --primary-color: #20c997;
    --primary-dark: #1aa179;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #17a2b8;
    --light-bg: #f8f9fa;
    --dark-bg: #343a40;
    --border-color: #dee2e6;
    --text-muted: #6c757d;
    --shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
}

/* Global Styles */
body {
    font-family: 'Bricolage Grotesque', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--light-bg);
    color: #333;
    line-height: 1.6;
}

/* Authentication Pages */
.auth-body {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
}

.auth-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: var(--shadow-lg);
    border: none;
}

.auth-title {
    color: var(--primary-color);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.auth-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.auth-link:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

/* Navigation */
.navbar {
    background: white !important;
    box-shadow: var(--shadow);
    border-bottom: 1px solid var(--border-color);
}

.navbar-brand {
    color: var(--primary-color) !important;
    font-weight: 700;
    font-size: 1.5rem;
}

.navbar-nav .nav-link {
    color: var(--secondary-color) !important;
    font-weight: 500;
    margin: 0 0.5rem;
    transition: color 0.3s ease;
}

.navbar-nav .nav-link:hover {
    color: var(--primary-color) !important;
}

.navbar-nav .nav-link.active {
    color: var(--primary-color) !important;
}

/* Main Content */
.main-content {
    padding: 2rem 0;
    min-height: calc(100vh - 76px);
}

/* Cards */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.card-header {
    background: var(--primary-color);
    color: white;
    border-radius: 15px 15px 0 0 !important;
    border: none;
    font-weight: 600;
    padding: 0.75rem 1.25rem;
}

/* Dashboard Cards */
.dashboard-card {
    text-align: center;
    padding: 1.5rem;
}

.dashboard-card .card-body {
    padding: 2rem 1rem;
}

.dashboard-card .display-4 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.dashboard-card .text-muted {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Stats Cards */
.stats-card {
    border-left: 4px solid var(--primary-color);
}

.stats-card.total-expenses {
    border-left-color: var(--danger-color);
}

.stats-card.total-entries {
    border-left-color: var(--info-color);
}

.stats-card.top-category {
    border-left-color: var(--success-color);
}

.stats-card.avg-expense {
    border-left-color: var(--warning-color);
}

/* Forms */
.form-control {
    border-radius: 10px;
    border: 2px solid var(--border-color);
    padding: 0.75rem 1rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.25);
}

.form-label {
    font-weight: 600;
    color: var(--secondary-color);
    margin-bottom: 0.5rem;
}

/* Buttons */
.btn {
    border-radius: 10px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
    transform: translateY(-1px);
}

.btn-outline-primary {
    color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-outline-primary:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

/* Tables */
.table {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

.table thead th {
    background-color: var(--primary-color);
    color: white;
    border: none;
    font-weight: 600;
    padding: 1rem;
}

.table tbody td {
    padding: 1rem;
    border-color: var(--border-color);
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: rgba(32, 201, 151, 0.05);
}

.table tbody tr:nth-child(even) {
    background-color: rgba(248, 249, 250, 0.5);
}

/* Ensure consistent inner spacing for cards */
.card .card-body {
    padding-left: 1.25rem;
    padding-right: 1.25rem;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-edit {
    background-color: var(--info-color);
    border-color: var(--info-color);
    color: white;
}

.btn-edit:hover {
    background-color: #138496;
    border-color: #117a8b;
    color: white;
}

.btn-delete {
    background-color: var(--danger-color);
    border-color: var(--danger-color);
    color: white;
}

.btn-delete:hover {
    background-color: #c82333;
    border-color: #bd2130;
    color: white;
}

/* Charts */
.chart-container {
    position: relative;
    height: 400px;
    margin: 1rem 0;
}

/* Alerts */
.alert {
    border-radius: 10px;
    border: none;
    font-weight: 500;
}

.alert-success {
    background-color: rgba(40, 167, 69, 0.1);
    color: var(--success-color);
    border-left: 4px solid var(--success-color);
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: var(--danger-color);
    border-left: 4px solid var(--danger-color);
}

.alert-info {
    background-color: rgba(23, 162, 184, 0.1);
    color: var(--info-color);
    border-left: 4px solid var(--info-color);
}

/* Filters */
.filter-section {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
}

.filter-row {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

/* Pagination */
.pagination {
    justify-content: center;
    margin-top: 2rem;
}

.page-link {
    color: var(--primary-color);
    border-color: var(--border-color);
    border-radius: 10px;
    margin: 0 0.25rem;
}

.page-link:hover {
    color: var(--primary-dark);
    background-color: rgba(32, 201, 151, 0.1);
    border-color: var(--primary-color);
}

.page-item.active .page-link {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Footer */
.footer {
    background-color: var(--dark-bg);
    color: white;
    text-align: center;
    padding: 2rem 0;
    margin-top: 3rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        padding: 1rem 0;
    }
    
    .auth-card {
        margin: 1rem;
        padding: 1.5rem;
    }
    
    .dashboard-card .display-4 {
        font-size: 2rem;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .table-responsive {
        border-radius: 15px;
    }
}

@media (max-width: 576px) {
    .navbar-brand {
        font-size: 1.25rem;
    }
    
    .card-body { padding: 1rem 1rem; }
    
    /* Compact dashboard headings and stats on phones */
    h1.display-4, .display-4, .display-5, .display-6 {
        font-size: 1.5rem;
    }
    .stats-card .card-title { font-size: 0.95rem; }
    .stats-card .display-6 { font-size: 1.25rem; }
    .stats-card .card-body { padding: 0.75rem; }
    .mb-4 { margin-bottom: 1rem !important; }
    .main-content h1.display-4 { margin-bottom: 0.5rem; }
    .main-content p.text-muted { margin-bottom: 1rem; }
    
    .btn {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .chart-container { height: 240px; }
    .footer { padding: 1rem 0; }
}

/* Tablet optimization */
@media (min-width: 769px) and (max-width: 991.98px) {
    .chart-container { height: 340px; }
}

/* Loading Spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--light-bg);
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.slide-in {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(-100%);
    }
    to {
        transform: translateX(0);
    }
}

```

### 12. JavaScript (`js/chart.js`)

```javascript
// Chart.js configuration and utility functions for Expense Tracker

// Global chart configuration
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6c757d';

// Color palette for charts
const chartColors = {
    primary: '#20c997',
    primaryDark: '#1aa179',
    secondary: '#6c757d',
    success: '#28a745',
    danger: '#dc3545',
    warning: '#ffc107',
    info: '#17a2b8',
    light: '#f8f9fa',
    dark: '#343a40'
};

// Extended color palette for multiple datasets
const extendedColors = [
    '#20c997', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1',
    '#fd7e14', '#20c997', '#6c757d', '#e83e8c', '#28a745',
    '#20c997', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1'
];

// Utility function to format currency
function formatCurrency(value) {
    return '₹' + parseFloat(value).toFixed(2);
}

// Utility function to format numbers
function formatNumber(value) {
    return parseFloat(value).toLocaleString();
}

// Create a responsive line chart
function createLineChart(ctx, data, options = {}) {
    const defaultOptions = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    };
    
    return new Chart(ctx, Object.assign(defaultOptions, options));
}

// Create a responsive bar chart
function createBarChart(ctx, data, options = {}) {
    const defaultOptions = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    };
    
    return new Chart(ctx, Object.assign(defaultOptions, options));
}

// Create a responsive doughnut chart
function createDoughnutChart(ctx, data, options = {}) {
    const defaultOptions = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const dataset = data.datasets[0];
                                    const value = dataset.data[i];
                                    const total = dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    
                                    return {
                                        text: `${label}: ${formatCurrency(value)} (${percentage}%)`,
                                        fillStyle: dataset.backgroundColor[i],
                                        strokeStyle: dataset.borderColor || '#fff',
                                        lineWidth: dataset.borderWidth || 2,
                                        pointStyle: 'circle',
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${formatCurrency(context.parsed)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    };
    
    return new Chart(ctx, Object.assign(defaultOptions, options));
}

// Create a responsive pie chart
function createPieChart(ctx, data, options = {}) {
    const defaultOptions = {
        type: 'pie',
        data: data,
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
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${formatCurrency(context.parsed)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    };
    
    return new Chart(ctx, Object.assign(defaultOptions, options));
}

// Animation utilities
function fadeIn(element, duration = 300) {
    element.style.opacity = 0;
    element.style.display = 'block';
    
    let start = performance.now();
    
    function animate(timestamp) {
        let progress = (timestamp - start) / duration;
        
        if (progress < 1) {
            element.style.opacity = progress;
            requestAnimationFrame(animate);
        } else {
            element.style.opacity = 1;
        }
    }
    
    requestAnimationFrame(animate);
}

function slideIn(element, direction = 'left', duration = 300) {
    const startPosition = direction === 'left' ? -100 : 100;
    element.style.transform = `translateX(${startPosition}px)`;
    element.style.opacity = 0;
    element.style.display = 'block';
    
    let start = performance.now();
    
    function animate(timestamp) {
        let progress = (timestamp - start) / duration;
        
        if (progress < 1) {
            const currentPosition = startPosition * (1 - progress);
            element.style.transform = `translateX(${currentPosition}px)`;
            element.style.opacity = progress;
            requestAnimationFrame(animate);
        } else {
            element.style.transform = 'translateX(0)';
            element.style.opacity = 1;
        }
    }
    
    requestAnimationFrame(animate);
}

// Form validation utilities
function validateAmount(amount) {
    const num = parseFloat(amount);
    return !isNaN(num) && num > 0;
}

function validateDate(dateString) {
    const date = new Date(dateString);
    return date instanceof Date && !isNaN(date);
}

function validateRequired(value) {
    return value && value.trim().length > 0;
}

// Local storage utilities for draft saving
function saveDraft(formId, data) {
    try {
        localStorage.setItem(`draft_${formId}`, JSON.stringify(data));
    } catch (e) {
        console.warn('Could not save draft:', e);
    }
}

function loadDraft(formId) {
    try {
        const draft = localStorage.getItem(`draft_${formId}`);
        return draft ? JSON.parse(draft) : null;
    } catch (e) {
        console.warn('Could not load draft:', e);
        return null;
    }
}

function clearDraft(formId) {
    try {
        localStorage.removeItem(`draft_${formId}`);
    } catch (e) {
        console.warn('Could not clear draft:', e);
    }
}

// Auto-save functionality for forms
function enableAutoSave(formId, interval = 2000) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    let saveTimer;
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(() => {
                const formData = new FormData(form);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                saveDraft(formId, data);
            }, interval);
        });
    });
}

// Load draft data into form
function loadDraftIntoForm(formId) {
    const form = document.getElementById(formId);
    const draft = loadDraft(formId);
    
    if (form && draft) {
        Object.keys(draft).forEach(key => {
            const element = form.querySelector(`[name="${key}"]`);
            if (element) {
                element.value = draft[key];
            }
        });
    }
}

// Export chart as image
function exportChart(chart, filename = 'chart.png') {
    const link = document.createElement('a');
    link.download = filename;
    link.href = chart.toBase64Image();
    link.click();
}

// Print chart
function printChart(chart) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Chart Print</title>
                <style>
                    body { margin: 0; padding: 20px; text-align: center; }
                    img { max-width: 100%; height: auto; }
                </style>
            </head>
            <body>
                <img src="${chart.toBase64Image()}" alt="Chart">
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Responsive chart resize handler
function handleChartResize(charts) {
    let resizeTimer;
    
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            charts.forEach(chart => {
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        }, 250);
    });
}

// Initialize tooltips for Bootstrap
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize popovers for Bootstrap
function initializePopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// Smooth scroll to element
function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Debounce function for performance optimization
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

// Throttle function for performance optimization
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Initialize all common functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    initializeTooltips();
    initializePopovers();
    
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            }
        });
    });
    
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in');
        }, index * 100);
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.classList.contains('alert-success')) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            }
        }, 5000);
    });
});

// Export functions for global use
window.ExpenseTracker = {
    createLineChart,
    createBarChart,
    createDoughnutChart,
    createPieChart,
    formatCurrency,
    formatNumber,
    fadeIn,
    slideIn,
    validateAmount,
    validateDate,
    validateRequired,
    saveDraft,
    loadDraft,
    clearDraft,
    enableAutoSave,
    loadDraftIntoForm,
    exportChart,
    printChart,
    handleChartResize,
    smoothScrollTo,
    debounce,
    throttle,
    chartColors,
    extendedColors
};

```

---

## Screenshots

### Login Page
![Login Page Screenshot](screenshots/login.png)
*Caption: User login interface with form validation*

### Registration Page
![Registration Page Screenshot](screenshots/register.png)
*Caption: User registration form with validation*

### Dashboard
![Dashboard Screenshot](screenshots/dashboard.png)
*Caption: Main dashboard showing statistics and charts*

### Add Expense
![Add Expense Screenshot](screenshots/add_expense.png)
*Caption: Form for adding new expenses*

### View Expenses
![View Expenses Screenshot](screenshots/view_expenses.png)
*Caption: List of all expenses with filtering options*

### Reports
![Reports Screenshot](screenshots/reports.png)
*Caption: Comprehensive reporting with charts and analytics*


---

## Installation & Setup Instructions

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or XAMPP/WAMP

### Setup Steps
1. **Database Setup**
   - Create MySQL database named `expense_tracker`
   - Import the `database_setup.sql` file
   - Configure database credentials in `db_connect.php`

2. **Web Server Setup**
   - Place project files in web server directory
   - Start Apache and MySQL services
   - Access application via web browser

3. **Configuration**
   - Update database connection settings
   - Ensure proper file permissions
   - Test all functionality

---

## Features Demonstration

### User Authentication
- Secure login and registration system
- Password hashing for security
- Session management

### Expense Management
- Add expenses with categories and notes
- View all expenses with search and filter
- Edit and delete expense records
- Form validation and error handling

### Dashboard Analytics
- Total expenses and entry count
- Top spending category
- Average expense calculation
- Interactive charts and graphs

### Reporting System
- Monthly expense trends
- Category-wise spending analysis
- Year-over-year comparisons
- Export and print functionality

### Responsive Design
- Mobile-friendly interface
- Bootstrap 5 framework
- Modern UI/UX design
- Cross-browser compatibility

---

## Technical Implementation

### Database Operations
- **INSERT**: Adding new users and expenses
- **SELECT**: Fetching data with JOINs and aggregations
- **UPDATE**: Modifying existing records
- **DELETE**: Removing expense entries
- **GROUP BY**: Data aggregation for reports

### Security Measures
- Password hashing using `password_hash()`
- Prepared statements for SQL injection prevention
- Input validation and sanitization
- Session-based authentication
- Access control for protected pages

### Performance Optimization
- Database indexing for faster queries
- Pagination for large datasets
- Efficient data loading
- Browser caching for static assets

---

## Conclusion

This Expense Tracker Web Application successfully demonstrates the implementation of a complete web application using modern technologies. The project showcases:

- **Full-stack development** with PHP, MySQL, HTML, CSS, and JavaScript
- **Security best practices** including password hashing and SQL injection prevention
- **Responsive design** that works across all devices
- **Data visualization** with interactive charts and reports
- **User experience** with intuitive navigation and modern UI

The application provides a solid foundation for personal finance management and can be extended with additional features such as budget tracking, goal setting, and advanced analytics.

---

## References

- PHP Documentation: https://www.php.net/docs.php
- MySQL Documentation: https://dev.mysql.com/doc/
- Bootstrap Documentation: https://getbootstrap.com/docs/
- Chart.js Documentation: https://www.chartjs.org/docs/
- Web Development Best Practices

---

*This project was developed as part of a Web Development assignment, demonstrating proficiency in full-stack web development technologies and best practices.*
