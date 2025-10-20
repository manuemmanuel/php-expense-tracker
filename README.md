# Expense Tracker Web Application

A fully functional and responsive Expense Tracker Web Application built with HTML, CSS, PHP, and MySQL. This application allows users to register, log in, and manage their personal expenses under different categories with comprehensive reporting features.

## Features

### Core Functionality
- **User Authentication**: Secure registration and login system with password hashing
- **Dashboard**: Summary view with key metrics and interactive charts
- **Add Expenses**: Form to add new expenses with category selection
- **View Expenses**: Searchable, filterable table with pagination
- **Edit Expenses**: Update existing expense records
- **Delete Expenses**: Remove expense records with confirmation
- **Reports**: Comprehensive reporting with multiple chart types

### Database Operations
- **INSERT**: Adding new users and expenses
- **SELECT**: Fetching expenses, categories, and user data
- **UPDATE**: Modifying existing expense records
- **DELETE**: Removing expense records
- **JOIN**: Combining data from expenses and categories tables
- **Aggregate Functions**: SUM(), COUNT(), AVG(), MIN(), MAX()
- **GROUP BY**: Grouping data for reports and analytics

### User Interface
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Modern UI**: Clean, minimal interface with Bootstrap 5
- **Interactive Charts**: Line charts, bar charts, and doughnut charts using Chart.js
- **Color-coded Categories**: Visual distinction for different expense categories
- **Search & Filter**: Advanced filtering by date range, category, and text search
- **Pagination**: Efficient handling of large datasets

## Project Structure

```
Assignment-Expense/
├── index.php              # Login page
├── register.php           # User registration
├── dashboard.php          # Main dashboard with summary
├── add_expense.php        # Add new expense form
├── view_expense.php       # View all expenses with filters
├── edit_expense.php       # Edit existing expense
├── report.php             # Reports and analytics
├── logout.php             # Logout functionality
├── db_connect.php         # Database connection and utilities
├── database_setup.sql     # Database schema and setup
├── css/
│   └── style.css          # Custom styling
├── js/
│   └── chart.js           # Chart utilities and interactions
└── README.md              # This file
```

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or XAMPP/WAMP

### Database Setup

1. **Create Database**:
   ```sql
   CREATE DATABASE expense_tracker;
   ```

2. **Import Schema**:
   - Open phpMyAdmin or MySQL command line
   - Import the `database_setup.sql` file
   - This will create all necessary tables and insert default categories

3. **Configure Database Connection**:
   - Edit `db_connect.php`
   - Update database credentials:
     ```php
     $host = 'localhost';
     $dbname = 'expense_tracker';
     $username = 'your_username';
     $password = 'your_password';
     ```

### Web Server Setup

1. **XAMPP/WAMP**:
   - Place the project folder in `htdocs` (XAMPP) or `www` (WAMP)
   - Start Apache and MySQL services
   - Access via `http://localhost/Assignment-Expense`

2. **Custom Web Server**:
   - Configure virtual host pointing to project directory
   - Ensure PHP and MySQL are properly configured

## Usage

### Getting Started

1. **Access the Application**:
   - Navigate to the project URL
   - You'll see the login page

2. **Create Account**:
   - Click "Sign up" to create a new account
   - Fill in username, email, and password
   - Password must be at least 6 characters

3. **Login**:
   - Use your credentials to log in
   - You'll be redirected to the dashboard

### Using the Application

#### Dashboard
- View total expenses, entry count, top category, and average expense
- Interactive charts showing monthly trends and category breakdown
- Recent expenses list for quick overview

#### Adding Expenses
- Click "Add Expense" in navigation
- Fill in amount, date, category, and optional note
- Use quick-add buttons for common expenses
- Form includes validation and auto-save functionality

#### Viewing Expenses
- Access "View Expenses" to see all your records
- Use filters to search by:
  - Text (note or category)
  - Category dropdown
  - Date range
- Sort by any column (date, category, amount)
- Pagination for large datasets

#### Editing Expenses
- Click "Edit" button on any expense row
- Modify any field and save changes
- Form includes draft auto-save functionality

#### Reports
- Access "Reports" for detailed analytics
- Three report types:
  - **Monthly**: Month-by-month breakdown with year comparison
  - **Category**: Spending by category with trends
  - **Yearly**: Year-over-year analysis
- Interactive charts and detailed tables
- Export and print functionality

## Database Schema

### Tables

#### users
- `user_id` (PK, AUTO_INCREMENT)
- `username` (UNIQUE, NOT NULL)
- `email` (UNIQUE, NOT NULL)
- `password` (HASHED, NOT NULL)
- `created_at` (TIMESTAMP)

#### categories
- `category_id` (PK, AUTO_INCREMENT)
- `category_name` (UNIQUE, NOT NULL)

#### expenses
- `expense_id` (PK, AUTO_INCREMENT)
- `user_id` (FK to users.user_id)
- `category_id` (FK to categories.category_id)
- `amount` (DECIMAL(10,2), NOT NULL)
- `expense_date` (DATE, NOT NULL)
- `note` (TEXT, NULL)
- `created_at` (TIMESTAMP)

### Default Categories
- Food & Dining
- Transportation
- Shopping
- Entertainment
- Bills & Utilities
- Healthcare
- Education
- Travel
- Rent & Housing
- Other

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` function
- **Prepared Statements**: All database queries use prepared statements to prevent SQL injection
- **Session Management**: Secure session handling for user authentication
- **Input Validation**: Server-side validation for all form inputs
- **Access Control**: Pages check for user authentication before displaying content

## Technical Features

### Frontend
- **Bootstrap 5**: Responsive framework
- **Chart.js**: Interactive charts and graphs
- **Custom CSS**: Modern, clean styling with CSS variables
- **JavaScript**: Enhanced user interactions and form handling

### Backend
- **PHP 7.4+**: Server-side logic
- **PDO**: Database abstraction layer
- **MySQL**: Relational database management
- **Session Management**: User authentication and state management

### Performance
- **Database Indexing**: Optimized queries with proper indexes
- **Pagination**: Efficient handling of large datasets
- **Caching**: Browser caching for static assets
- **Responsive Images**: Optimized for different screen sizes

## Browser Support

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check database credentials in `db_connect.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Charts Not Displaying**:
   - Check browser console for JavaScript errors
   - Ensure Chart.js is loading properly
   - Verify data is being passed to charts

3. **Login Issues**:
   - Check if user exists in database
   - Verify password hashing is working
   - Check session configuration

4. **File Permissions**:
   - Ensure web server has read access to all files
   - Check PHP error logs for permission issues

### Debug Mode

To enable debug mode, add this to the top of PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support or questions, please create an issue in the project repository.

---

**Note**: This application is designed for educational purposes and should be properly secured before use in production environments.
