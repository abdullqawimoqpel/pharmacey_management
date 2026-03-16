# Pharmacy Management System

A professional, secure, and modern Pharmacy Management System designed for pharmacies and medical stores. Built with PHP and MySQL, it offers a clean user interface and robust inventory control.

## 🌟 Key Features

### 📊 Business Dashboard
*   **Real-time Analytics:** Monitor total sales, revenue, and customer count.
*   **Automated Alerts:** Get immediate notifications for low stock, expiring medicines, and expired products.
*   **Daily Summaries:** Track today's performance with revenue and invoice counts.

### 💰 Professional Point of Sale (POS)
*   **Quick Search Interface:** Find medications instantly by name or category.
*   **Smart Cart:** Handles taxes (5% VAT), discounts, and subtotal calculations automatically.
*   **Instant Stock Update:** Inventory levels are deducted automatically upon sale completion.
*   **Invoice Management:** Generates professional receipts with transaction details.

### 📦 Comprehensive Inventory Management
*   **Full CRUD Support:** Add, edit, or remove medicines with ease.
*   **Batch Tracking:** Keep track of batch numbers and specific stock levels.
*   **Expiry Control:** Automatic categorisation of stock by expiry status (Safe, Expiring Soon, Expired).
*   **Supplier & Customer DB:** Maintain records for wholesale suppliers and regular customers.

### 🌍 Localization
*   **Bilingual Support:** Full support for English (LTR) and Arabic (RTL).
*   **Dynamic UI:** The interface shifts layout based on the selected language.

### 🔐 Enterprise-Grade Security
*   **Role-Based Access (RBAC):** Distinct permissions for Admins, Pharmacists, and Assistants.
*   **Data Protection:** Secure password hashing (BCRYPT) and CSRF token verification for all forms.

## 🛠️ Tech Stack
*   **Backend:** PHP 7.4+
*   **Database:** MySQL (MariaDB) using PDO Extension
*   **Frontend:** Bootstrap 5, Vanilla JavaScript, CSS3
*   **Icons:** Bootstrap Icons

## 💻 Installation Guide

### Prerequisites
*   A local server environment (XAMPP, WAMP, or Laragon).
*   PHP 7.4 or higher.
*   MySQL/MariaDB.

### Setup Instructions
1.  **Download:** Clone or download the repository to your `htdocs` directory.
2.  **Database Setup:**
    *   Create a database named `pharmacy_managements`.
    *   Import the provided `pharmacy_managements.sql` file via phpMyAdmin.
3.  **Config:** Update `config/constants.php` with your local URL.
4.  **Run:** Access the project via `localhost/pharmacy_management`.

### Default Accounts
*   **Admin:** `admin` / `admin123`
*   **Staff:** `staff` / `staff123`

## 📄 License
This software is provided for educational and business management purposes.

## ✍️ Author
**Abdullqawi Moqbel**
[GitHub Profile](https://github.com/abdullqawimoqpel)
