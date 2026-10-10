# Deploying SpendWise to InfinityFree (Step-by-Step Guide)

InfinityFree provides **100% free Apache, PHP 8.x, and MySQL with phpMyAdmin**, making it completely compatible with SpendWise without any serverless runtime errors.

---

## Step 1: Create a Free Account on InfinityFree
1. Go to **[https://www.infinityfree.com](https://www.infinityfree.com)** and sign up.
2. In your dashboard, click **Create Account**.
3. Choose a domain or free subdomain (e.g., `spendwise-demo.infinityfreeapp.com` or `.epizy.com`).
4. Finish creating the account (it activates in seconds).

---

## Step 2: Create Your MySQL Database
1. In your InfinityFree dashboard for your new account, click **Manage** -> **Control Panel** (or **MySQL Databases**).
2. Under **Create New Database**, enter a name like `spendwise` and click **Create Database**.
3. Note down the database details displayed on that page:
   - **MySQL Host**: (e.g., `sql304.infinityfree.com`)
   - **MySQL User**: (e.g., `if0_38123456`)
   - **MySQL Password**: (Your account vPanel password found under Account Details)
   - **MySQL Database**: (e.g., `if0_38123456_spendwise`)

---

## Step 3: Import the Database Schema
1. In the Control Panel, click on **phpMyAdmin** next to your database.
2. Select your database name on the left panel.
3. Click the **Import** tab at the top.
4. Click **Choose File**, select `spendwise.sql` from your project folder, and click **Go** (or **Import** at the bottom).
5. All 6 tables (`users`, `categories`, `payment_methods`, `expenses`, `budgets`, `notifications`) will be created instantly with default categories and payment methods.

---

## Step 4: Configure `config/db.php`
Open `config/db.php` in your code and enter your InfinityFree database credentials in the `else` section (lines 28–33):

```php
} else {
    // 2. LIVE PRODUCTION ENVIRONMENT (InfinityFree)
    $host     = "sql304.infinityfree.com";         // Your MySQL Host
    $username = "if0_38123456";                    // Your MySQL User
    $password = "your_vpanel_password";            // Your Account Password
    $database = "if0_38123456_spendwise";          // Your Full Database Name
}
```
*(Your local XAMPP will continue to work without any interference, because the code automatically detects localhost!)*

---

## Step 5: Upload Your Files
You have two easy ways to upload:

### Method A: Web File Manager (Quickest)
1. In your InfinityFree account dashboard, click **File Manager**.
2. Open the **`htdocs`** folder (delete any default `index2.html` if present).
3. Upload all the files and folders from your SpendWise project into **`htdocs`**:
   - `index.html`
   - `login.html`
   - `register.html`
   - `.htaccess`
   - `api/`
   - `assets/`
   - `config/`
   - `pages/`
   - `vendor/`

### Method B: FileZilla / FTP
1. In your InfinityFree dashboard, find your **FTP Host**, **FTP User**, and **FTP Password**.
2. Connect using FileZilla and drag-and-drop your project files into `/htdocs/`.

---

## Step 6: Test Your Live App!
Visit your free live URL:
```text
https://your-chosen-subdomain.infinityfreeapp.com/
```
You can now register a new account, log in, add expenses, generate charts, and export PDF reports live!
