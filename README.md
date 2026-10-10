# SpendWise – Personal Expense Tracking and Budget Management System

A beginner-friendly, full-featured web application designed to help individuals track their daily expenses, set and monitor monthly budgets, and analyze spending habits with interactive charts and reports.

---

## 🛠️ Technology Stack
- **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6 `fetch` API), Bootstrap 5 (CDN)
- **Visualization:** Chart.js (CDN)
- **Backend:** Plain PHP (procedural `mysqli` style, REST-style JSON responses)
- **Database:** MySQL
- **Environment:** XAMPP (Apache + MySQL)

---

## 🚀 Key Modules & Features

1. **Authentication & Session Guard (Module 1)**
   - User registration with BCRYPT password hashing.
   - Secure login with session cookies (`$_SESSION['user_id']`).
   - Dynamic shared navigation bar with dark/light mode persistence and session guards.

2. **Categories & Payment Methods (Module 2)**
   - Pre-seeded default categories (*Food & Dining*, *Transportation*, *Rent*, etc.) and payment methods (*Cash*, *UPI*, *Credit Card*, etc.).
   - Ability to add, edit, and delete custom user categories and payment methods.
   - Deletion protection prevents deleting items linked to existing expense records.

3. **Expense Tracking (Module 3)**
   - Record expenses with amount, category, payment method, date, and description.
   - Edit expenses via Bootstrap modals and delete records with confirmation.
   - Instant budget threshold evaluation after each added expense.

4. **Budget Management (Module 4)**
   - Set overall monthly budgets or category-specific spending limits.
   - Visual color-coded progress bars (Green = Safe, Yellow = Warning threshold reached, Red = Over-budget).

5. **Financial Overview Dashboard (Module 5)**
   - Real-time metric cards for:
     - Today's spending
     - This week's spending
     - This month's spending
     - Remaining monthly budget
   - Table of the 5 most recent transactions.

6. **Transaction History & CSV Export (Module 6)**
   - Search description and filter by category, payment method, date range, and amount.
   - Sorting by date or amount with pagination.
   - One-click **Export CSV** generating a spreadsheet download directly from filtered records.

7. **Visual Analytics & Reporting (Module 7)**
   - Category Breakdown Pie Chart.
   - Monthly Spending Bar Chart for the year.
   - Daily Spending Line Chart for the selected month.
   - Key financial summary metrics (average daily spend, top spending category, highest single expense).

8. **Budget Notifications & Alerts (Module 8)**
   - Navbar notification bell with real-time unread badge counter.
   - Notifications dashboard with "Mark All as Read" and deletion options.
   - Automated threshold warning alerts when expenses exceed defined percentages.

9. **Profile & Custom Settings (Module 9)**
   - Edit personal details (name, email, phone).
   - Change password with old password verification.
   - Preferences for currency symbol (e.g., ₹, $, €) and dark/light mode saved to MySQL.
   - Account deletion option with cascade cleanup.

---

## 📁 Project Directory Structure

```text
wt project antigravity/
├── api/                   # Procedural PHP API endpoints returning JSON
│   ├── add_budget.php
│   ├── add_category.php
│   ├── add_expense.php
│   ├── add_payment_method.php
│   ├── category_report.php
│   ├── change_password.php
│   ├── check_session.php
│   ├── daily_report.php
│   ├── dashboard.php
│   ├── delete_account.php
│   ├── delete_budget.php
│   ├── delete_category.php
│   ├── delete_expense.php
│   ├── delete_notification.php
│   ├── delete_payment_method.php
│   ├── export_csv.php
│   ├── get_budgets.php
│   ├── get_categories.php
│   ├── get_expenses.php
│   ├── get_history.php
│   ├── get_notifications.php
│   ├── get_payment_methods.php
│   ├── get_profile.php
│   ├── get_unread_count.php
│   ├── login.php
│   ├── logout.php
│   ├── mark_notification_read.php
│   ├── monthly_report.php
│   ├── register.php
│   ├── update_budget.php
│   ├── update_category.php
│   ├── update_expense.php
│   ├── update_payment_method.php
│   ├── update_profile.php
│   └── update_settings.php
├── assets/
│   ├── css/
│   │   └── style.css      # Custom styling, dark mode support & cards
│   └── js/
│       └── navbar.js      # Shared navbar injection, theme toggle, and session check
├── config/
│   ├── db.php             # MySQL database connection (mysqli)
│   └── helpers.php        # Shared helper functions (budget alert checks)
├── pages/
│   ├── analytics.html     # Chart.js reports
│   ├── budgets.html       # Budget limits and progress bars
│   ├── categories.html    # Manage categories & payment methods
│   ├── dashboard.html     # Summary financial cards & recent expenses
│   ├── expenses.html      # Expense entry & editing
│   ├── history.html       # Filterable table & CSV export
│   ├── notifications.html # Budget threshold alerts
│   └── profile.html       # User settings & security
├── index.html             # Entry point redirecting to login.html
├── login.html             # User login page
├── register.html          # User registration page
├── spendwise.sql          # Complete MySQL database schema & seed data
├── .gitignore
└── README.md
```

---

## 💻 Setup and Installation Instructions

1. **Install and Start XAMPP:**
   - Download and install [XAMPP](https://www.apachefriends.org/).
   - Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.

2. **Clone or Copy the Project:**
   - Copy this project into your XAMPP web root directory:
     ```text
     C:/xampp/htdocs/spendwise/
     ```

3. **Import the Database:**
   - Open your browser and navigate to `http://localhost/phpmyadmin`.
   - Click on the **SQL** tab or create a database named `spendwise`.
   - Import the provided `spendwise.sql` file.

4. **Run the Application:**
   - Navigate to:
     ```text
     http://localhost/spendwise/
     ```
   - Register a new account or log in with your credentials.
