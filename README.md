# PHP Billing System

A simple, modern, and responsive billing system built with PHP and MySQL. It includes an admin panel for managing users, products, and invoices, and a user dashboard for viewing invoices.

## Features

-   **User Authentication:** Secure user registration and login.
-   **Admin Panel:**
    -   Manage products (Create, Read, Update, Delete).
    -   View registered users.
    -   Create and manage invoices for users.
    -   View detailed invoices.
-   **User Dashboard:**
    -   View a list of personal invoices.
    -   View detailed invoice information.
-   **Responsive Design:** Built with Bootstrap 5 for a clean and mobile-friendly experience.
-   **Ready for Deployment:** Designed to be easily deployed on shared hosting like InfinityFree.

## Project Structure

```
/
|-- config/
|   `-- db.php            # Database connection and configuration
|-- public/
|   |-- admin/            # Admin-only pages (products, users, invoices)
|   |   |-- _nav.php
|   |   |-- add_product.php
|   |   |-- create_invoice.php
|   |   |-- ... (handler files)
|   |-- index.php         # Main landing page
|   |-- login.php
|   |-- register.php
|   |-- dashboard.php
|   |-- ... (handler files)
|-- src/
|   |-- User.php          # User class for DB operations
|   |-- Product.php       # Product class for DB operations
|   |-- Invoice.php       # Invoice class for DB operations
|-- database.sql          # SQL script to create database tables
`-- README.md             # This file
```

## Deployment Instructions (for InfinityFree)

Follow these steps to deploy the application to your InfinityFree account or any similar hosting provider.

### Step 1: Create a Database

1.  Log in to your InfinityFree control panel (cPanel).
2.  Go to the "MySQL Databases" section.
3.  Create a new database. Note down the **database name** (e.g., `if0_..._billing`).
4.  InfinityFree automatically assigns a **database user** and **host**. You can find these details on the same page. Note down the **DB Host** (e.g., `sql201.infinityfree.com`), **DB User**, and your account **password**.

### Step 2: Import the Database Tables

1.  From the control panel, open **phpMyAdmin**.
2.  Select the database you just created from the left-hand sidebar.
3.  Click on the **Import** tab at the top.
4.  Click "Choose File" and select the `database.sql` file from this project.
5.  Click **Go** to start the import. The tables (`users`, `products`, `invoices`, `invoice_items`) will be created.

### Step 3: Configure the Application

1.  Open the `config/db.php` file.
2.  Replace the placeholder values with your actual database credentials that you noted in Step 1.

    ```php
    // Example configuration
    define('DB_HOST', 'sql201.infinityfree.com');
    define('DB_USER', 'if0_12345678');
    define('DB_PASS', 'YourAccountPassword');
    define('DB_NAME', 'if0_12345678_billing');
    ```

### Step 4: Upload the Files

1.  Using an FTP client (like FileZilla) or the InfinityFree File Manager, navigate to the `htdocs` directory in your hosting account.
2.  Upload the **entire project folder** (the one containing this `README.md` file) into the `htdocs` directory. You can rename it to something simple, like `billing`.
3.  Your file structure on the server should now look like this:
    ```
    htdocs/
    |-- billing/
    |   |-- public/
    |   |-- src/
    |   |-- config/
    |   |-- database.sql
    |   |-- ...
    ```

### Step 5: Access the Site & Create an Admin Account

1.  Visit your website by navigating to the `public` subdirectory, for example: `http://yourdomain.infinityfreeapp.com/billing/public/`
2.  Click on **Register** and create a new account. This will be your admin account.
3.  Go back to **phpMyAdmin** in your control panel.
4.  Open the `users` table and find the account you just created.
5.  Click **Edit**.
6.  Change the value in the `role` column from `user` to `admin`.
7.  Click **Go** to save the change.

You can now log in with this account and you will have access to the Admin Panel from the dashboard.