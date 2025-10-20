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
