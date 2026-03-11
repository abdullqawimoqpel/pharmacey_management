-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2025 at 10:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pharmacy_managements`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Analgesics', 'Pain relief medications', 1, '2025-11-03 02:21:47'),
(2, 'Antibiotics', 'Anti-bacterial medications', 1, '2025-11-03 02:21:47'),
(3, 'Vitamins', 'Nutritional supplements', 1, '2025-11-03 02:21:47'),
(4, 'Cardiovascular', 'Heart and blood pressure medications', 1, '2025-11-03 02:21:47'),
(5, 'Diabetes', 'Diabetes management medications', 1, '2025-11-03 02:21:47'),
(26, 'Antihistamines', 'Allergy medications', 1, '2025-11-03 04:31:14'),
(27, 'Antacids', 'Digestive system medications', 1, '2025-11-03 04:31:14'),
(28, 'Dermatological', 'Skin care medications', 1, '2025-11-03 04:31:14'),
(29, 'Respiratory', 'Asthma and respiratory medications', 1, '2025-11-03 04:31:14'),
(30, 'Mental Health', 'Psychiatric medications', 1, '2025-11-03 04:31:14');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `loyalty_points` int(11) DEFAULT 0,
  `total_purchases` decimal(10,2) DEFAULT 0.00,
  `last_purchase_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `phone`, `email`, `address`, `created_at`, `loyalty_points`, `total_purchases`, `last_purchase_date`) VALUES
(1, 'Ahmed Mohammed', '+96650012345', 'ahmed@email.com', 'Al Olaya, Riyadh, Saudi Arabia', '2025-11-03 03:48:07', 0, 0.00, '2025-10-31'),
(2, 'Fatima Al-Sabah', '+96650012346', 'fatima@email.com', 'Al Nahda, Jeddah, Saudi Arabia', '2025-11-03 03:48:07', 0, 0.00, '2025-10-30'),
(3, 'Ahmed Mohammed', '+96650012345', 'ahmed.mohammed@email.com', 'Riyadh, Al Olaya District, Street 5, Saudi Arabia', '2025-11-03 04:31:14', 150, 1250.75, '2025-10-29'),
(4, 'Fatima Al-Sabah', '+96650012346', 'fatima.alsabah@email.com', 'Jeddah, Tahlia Street, Saudi Arabia', '2025-11-03 04:31:14', 280, 2340.50, '2025-10-27'),
(5, 'Yousef Al-Rashid', '+96650012347', 'yousef.alrashid@email.com', 'Al Khobar, Corniche Road, Saudi Arabia', '2025-11-03 04:31:14', 75, 890.25, '2025-10-27'),
(6, 'Noura Hassan', '+96650012348', 'noura.hassan@email.com', 'Dammam, Al Faisaliyah District, Street 2, Saudi Arabia', '2025-11-03 04:31:14', 420, 3560.80, '2025-10-23'),
(7, 'Khalid Al-Mansour', '+96650012349', 'khalid.mansour@email.com', 'Riyadh, Al Malaz District, Street 10, Saudi Arabia', '2025-11-03 04:31:14', 190, 1675.30, '2025-11-01'),
(8, 'Layla Abdullah', '+96650012350', 'layla.abdullah@email.com', 'Jeddah, Prince Sultan Street, Saudi Arabia', '2025-11-03 04:31:14', 320, 2780.45, '2025-10-30'),
(9, 'Omar Farouk', '+96650012351', 'omar.farouk@email.com', 'Dammam, Al Shati District, Street 3, Saudi Arabia', '2025-11-03 04:31:14', 60, 720.90, NULL),
(10, 'Sara Al-Otaibi', '+96650012352', 'sara.otaibi@email.com', 'Riyadh, King Fahd Road, Saudi Arabia', '2025-11-03 04:31:14', 510, 4120.60, NULL),
(11, 'Ahmed Mohammed', '+96650012345', 'ahmed.mohammed@email.com', 'Riyadh, Al Olaya District, Street 5, Saudi Arabia', '2025-11-03 04:37:16', 150, 1250.75, NULL),
(12, 'Fatima Al-Sabah', '+96650012346', 'fatima.alsabah@email.com', 'Jeddah, Tahlia Street, Saudi Arabia', '2025-11-03 04:37:16', 280, 2340.50, NULL),
(13, 'Yousef Al-Rashid', '+96650012347', 'yousef.alrashid@email.com', 'Al Khobar, Corniche Road, Saudi Arabia', '2025-11-03 04:37:16', 75, 890.25, NULL),
(14, 'Noura Hassan', '+96650012348', 'noura.hassan@email.com', 'Dammam, Al Faisaliyah District, Street 2, Saudi Arabia', '2025-11-03 04:37:16', 420, 3560.80, NULL),
(15, 'Khalid Al-Mansour', '+96650012349', 'khalid.mansour@email.com', 'Riyadh, Al Malaz District, Street 10, Saudi Arabia', '2025-11-03 04:37:16', 190, 1675.30, NULL),
(16, 'Layla Abdullah', '+96650012350', 'layla.abdullah@email.com', 'Jeddah, Prince Sultan Street, Saudi Arabia', '2025-11-03 04:37:16', 320, 2780.45, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `medicine_id` int(11) NOT NULL,
  `medicine_name` varchar(100) NOT NULL,
  `generic_name` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `quantity_in_stock` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 10,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`medicine_id`, `medicine_name`, `generic_name`, `category_id`, `category`, `supplier_id`, `batch_number`, `expiry_date`, `purchase_price`, `selling_price`, `quantity_in_stock`, `min_stock_level`, `description`, `is_active`, `created_at`) VALUES
(1, 'Panadol Extra', 'Paracetamol', NULL, NULL, NULL, 'BATCH001', '2025-12-31', 2.50, 5.00, 96, 10, 'Pain relief and fever reduction', 0, '2025-11-03 03:48:07'),
(2, 'Amoxicillin 500mg', 'Amoxicillin', NULL, NULL, NULL, 'BATCH002', '2025-06-30', 8.00, 15.00, 47, 5, 'Antibiotic for bacterial infections', 1, '2025-11-03 03:48:07'),
(3, 'Vitamin C 1000mg', 'Ascorbic Acid', NULL, NULL, NULL, 'BATCH003', '2025-03-31', 5.00, 10.00, 194, 20, 'Immune system support', 1, '2025-11-03 03:48:07'),
(4, 'Panadol Extra', 'Paracetamol 500mg', 1, NULL, 1, 'BATCH-PAN-001', '2025-12-31', 2.50, 5.00, 148, 20, 'Fast-acting pain relief and fever reduction', 1, '2025-11-03 04:31:14'),
(5, 'Brufen 400mg', 'Ibuprofen 400mg', 1, NULL, 2, 'BATCH-BRF-001', '2025-11-30', 3.20, 6.50, 75, 15, 'Anti-inflammatory pain reliever', 1, '2025-11-03 04:31:14'),
(6, 'Aspirin Protect', 'Acetylsalicylic Acid 100mg', 1, NULL, 3, 'BATCH-ASP-001', '2025-10-31', 4.00, 8.00, 115, 25, 'Cardiovascular protection and pain relief', 1, '2025-11-03 04:31:14'),
(7, 'Amoxicillin 500mg', 'Amoxicillin Trihydrate', 2, NULL, 1, 'BATCH-AMX-001', '2025-09-30', 8.50, 16.00, 60, 10, 'Broad-spectrum antibiotic for bacterial infections', 1, '2025-11-03 04:31:14'),
(8, 'Ciprofloxacin 500mg', 'Ciprofloxacin HCl', 2, NULL, 2, 'BATCH-CIP-001', '2025-08-31', 12.00, 22.50, 43, 8, 'Treatment for various bacterial infections', 1, '2025-11-03 04:31:14'),
(9, 'Azithromycin 250mg', 'Azithromycin Dihydrate', 2, NULL, 4, 'BATCH-AZI-001', '2025-07-31', 15.00, 28.00, 32, 5, 'Macrolide antibiotic for respiratory infections', 1, '2025-11-03 04:31:14'),
(10, 'Vitamin C 1000mg', 'Ascorbic Acid', 3, NULL, 1, 'BATCH-VITC-001', '2026-03-31', 5.50, 11.00, 199, 30, 'Immune system support and antioxidant', 1, '2025-11-03 04:31:14'),
(11, 'Vitamin D3 1000IU', 'Cholecalciferol', 3, NULL, 3, 'BATCH-VITD-001', '2026-04-30', 7.00, 14.00, 175, 25, 'Bone health and immune function', 1, '2025-11-03 04:31:14'),
(12, 'Multivitamin Complex', 'Multiple Vitamins & Minerals', 3, NULL, 5, 'BATCH-MVIT-001', '2026-05-31', 12.50, 25.00, 81, 15, 'Complete daily nutritional supplement', 1, '2025-11-03 04:31:14'),
(13, 'Concor 5mg', 'Bisoprolol Fumarate', 4, NULL, 2, 'BATCH-CON-001', '2025-12-31', 18.00, 35.00, 37, 8, 'Beta blocker for hypertension and heart conditions', 1, '2025-11-03 04:31:14'),
(14, 'Coversyl 5mg', 'Perindopril Erbumine', 4, NULL, 4, 'BATCH-COV-001', '2025-11-30', 22.00, 42.00, 28, 6, 'ACE inhibitor for blood pressure control', 1, '2025-11-03 04:31:14'),
(15, 'Lipitor 20mg', 'Atorvastatin Calcium', 4, NULL, 1, 'BATCH-LIP-001', '2025-10-31', 25.00, 48.00, 32, 10, 'Cholesterol-lowering medication', 1, '2025-11-03 04:31:14'),
(16, 'Glucophage 850mg', 'Metformin HCl', 5, NULL, 3, 'BATCH-GLU-001', '2025-09-30', 6.50, 13.00, 105, 20, 'First-line treatment for type 2 diabetes', 1, '2025-11-03 04:31:14'),
(17, 'Januvia 100mg', 'Sitagliptin Phosphate', 5, NULL, 2, 'BATCH-JAN-001', '2025-08-31', 45.00, 85.00, 22, 5, 'DPP-4 inhibitor for diabetes management', 1, '2025-11-03 04:31:14'),
(18, 'Victoza 6mg/ml', 'Liraglutide', 5, NULL, 5, 'BATCH-VIC-001', '2025-07-31', 120.00, 220.00, 6, 3, 'GLP-1 receptor agonist injection', 1, '2025-11-03 04:31:14'),
(29, 'Panadol Extra', 'Paracetamol 500mg', 1, NULL, 1, 'BATCH-PAN-001', '2025-12-31', 2.50, 5.00, 150, 20, 'Fast-acting pain relief and fever reduction', 1, '2025-11-03 04:37:16'),
(30, 'Brufen 400mg', 'Ibuprofen 400mg', 1, NULL, 2, 'BATCH-BRF-001', '2025-11-30', 3.20, 6.50, 80, 15, 'Anti-inflammatory pain reliever', 1, '2025-11-03 04:37:16'),
(31, 'Aspirin Protect', 'Acetylsalicylic Acid 100mg', 1, NULL, 3, 'BATCH-ASP-001', '2025-10-31', 4.00, 8.00, 120, 25, 'Cardiovascular protection and pain relief', 1, '2025-11-03 04:37:16'),
(32, 'Amoxicillin 500mg', 'Amoxicillin Trihydrate', 2, NULL, 1, 'BATCH-AMX-001', '2025-09-30', 8.50, 16.00, 58, 10, 'Broad-spectrum antibiotic for bacterial infections', 1, '2025-11-03 04:37:16'),
(33, 'Ciprofloxacin 500mg', 'Ciprofloxacin HCl', 2, NULL, 2, 'BATCH-CIP-001', '2025-08-31', 12.00, 22.50, 45, 8, 'Treatment for various bacterial infections', 1, '2025-11-03 04:37:16'),
(34, 'Azithromycin 250mg', 'Azithromycin Dihydrate', 2, NULL, 4, 'BATCH-AZI-001', '2025-07-31', 15.00, 28.00, 34, 5, 'Macrolide antibiotic for respiratory infections', 1, '2025-11-03 04:37:16'),
(35, 'Vitamin C 1000mg', 'Ascorbic Acid', 3, NULL, 1, 'BATCH-VITC-001', '2026-03-31', 5.50, 11.00, 200, 30, 'Immune system support and antioxidant', 0, '2025-11-03 04:37:16'),
(36, 'Vitamin D3 1000IU', 'Cholecalciferol', 3, NULL, 3, 'BATCH-VITD-001', '2026-04-30', 7.00, 14.00, 180, 25, 'Bone health and immune function', 1, '2025-11-03 04:37:16'),
(37, 'Multivitamin Complex', 'Multiple Vitamins & Minerals', 3, NULL, 5, 'BATCH-MVIT-001', '2026-05-31', 12.50, 25.00, 90, 15, 'Complete daily nutritional supplement', 1, '2025-11-03 04:37:16'),
(38, 'Concor 5mg', 'Bisoprolol Fumarate', 4, NULL, 2, 'BATCH-CON-001', '2025-12-31', 18.00, 35.00, 40, 8, 'Beta blocker for hypertension and heart conditions', 1, '2025-11-03 04:37:16'),
(39, 'Coversyl 5mg', 'Perindopril Erbumine', 4, NULL, 4, 'BATCH-COV-001', '2025-11-30', 22.00, 42.00, 35, 6, 'ACE inhibitor for blood pressure control', 1, '2025-11-03 04:37:16'),
(40, 'Lipitor 20mg', 'Atorvastatin Calcium', 4, NULL, 1, 'BATCH-LIP-001', '2025-10-31', 25.00, 48.00, 50, 10, 'Cholesterol-lowering medication', 1, '2025-11-03 04:37:16'),
(41, 'Glucophage 850mg', 'Metformin HCl', 5, NULL, 3, 'BATCH-GLU-001', '2025-09-30', 6.50, 13.00, 110, 20, 'First-line treatment for type 2 diabetes', 1, '2025-11-03 04:37:16'),
(42, 'Januvia 100mg', 'Sitagliptin Phosphate', 5, NULL, 2, 'BATCH-JAN-001', '2025-08-31', 45.00, 85.00, 25, 5, 'DPP-4 inhibitor for diabetes management', 1, '2025-11-03 04:37:16'),
(43, 'Victoza 6mg/ml', 'Liraglutide', 5, NULL, 5, 'BATCH-VIC-001', '2025-07-31', 120.00, 220.00, 15, 3, 'GLP-1 receptor agonist injection', 1, '2025-11-03 04:37:16');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `type` enum('stock','expiry','sale','system') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `type`, `title`, `message`, `priority`, `is_read`, `created_at`) VALUES
(1, 'stock', 'Low Stock Alert', 'Panadol Extra is running low (15 items left)', 'high', 0, '2025-11-03 04:37:16'),
(2, 'stock', 'Reorder Needed', 'Amoxicillin 500mg needs to be reordered (8 items left)', 'medium', 0, '2025-11-03 04:37:16'),
(3, 'expiry', 'Expiry Warning', 'Vitamin C 1000mg expires in 30 days', 'medium', 0, '2025-11-03 04:37:16'),
(4, 'sale', 'High Value Sale', 'A sale worth 78.50 SAR was completed', 'low', 0, '2025-11-03 04:37:16'),
(5, 'system', 'System Update', 'Database backup completed successfully', 'low', 0, '2025-11-03 04:37:16');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `purchase_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `purchase_date` date NOT NULL,
  `received_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`purchase_id`, `supplier_id`, `medicine_id`, `quantity`, `unit_price`, `total_amount`, `purchase_date`, `received_date`) VALUES
(1, 1, 1, 100, 2.30, 0.00, '2024-01-15', '2025-11-03 10:37:16'),
(2, 2, 4, 50, 7.80, 0.00, '2024-01-18', '2025-11-03 10:37:16'),
(3, 3, 7, 200, 4.80, 0.00, '2024-01-20', '2025-11-03 10:37:16'),
(4, 4, 10, 30, 16.50, 0.00, '2024-01-22', '2025-11-03 10:37:16'),
(5, 5, 13, 80, 5.80, 0.00, '2024-01-25', '2025-11-03 10:37:16'),
(6, 1, 2, 60, 2.90, 0.00, '2024-02-01', '2025-11-03 10:37:16'),
(7, 2, 5, 40, 10.50, 0.00, '2024-02-03', '2025-11-03 10:37:16'),
(8, 3, 8, 150, 6.20, 0.00, '2024-02-05', '2025-11-03 10:37:16'),
(9, 4, 11, 25, 20.00, 0.00, '2024-02-08', '2025-11-03 10:37:16'),
(10, 5, 14, 20, 42.00, 0.00, '2024-02-10', '2025-11-03 10:37:16');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','mobile') DEFAULT 'cash',
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `customer_id`, `user_id`, `total_amount`, `discount`, `tax_amount`, `final_amount`, `payment_method`, `sale_date`) VALUES
(2, 6, 1, 53.91, 4.98, 2.70, 51.63, 'card', '2025-10-18 15:37:16'),
(3, 2, 1, 77.63, 3.74, 3.88, 77.77, 'cash', '2025-10-22 15:37:16'),
(4, 1, 7, 19.39, 4.78, 0.97, 15.58, 'cash', '2025-10-31 15:37:16'),
(5, 2, 9, 19.20, 3.38, 0.96, 16.78, 'card', '2025-10-21 15:37:16'),
(6, 2, 10, 71.20, 2.42, 3.56, 72.34, 'card', '2025-10-28 15:37:16'),
(7, 4, 8, 35.48, 3.19, 1.77, 34.06, 'cash', '2025-10-27 15:37:16'),
(8, 3, 10, 65.51, 1.35, 3.28, 67.44, 'cash', '2025-10-04 15:37:16'),
(9, 3, 8, 79.62, 4.63, 3.98, 78.97, 'mobile', '2025-10-29 15:37:16'),
(10, 3, 10, 57.91, 0.94, 2.90, 59.87, 'mobile', '2025-10-15 15:37:16'),
(11, 7, 1, 64.68, 3.50, 3.23, 64.41, 'card', '2025-10-16 15:37:16'),
(12, 8, 9, 44.54, 1.36, 2.23, 45.41, 'card', '2025-10-24 15:37:16'),
(13, 6, 8, 78.23, 4.17, 3.91, 77.97, 'card', '2025-10-08 15:37:16'),
(14, 7, 7, 34.94, 1.99, 1.75, 34.70, 'mobile', '2025-10-29 15:37:16'),
(15, 4, 9, 43.01, 1.21, 2.15, 43.95, 'mobile', '2025-10-26 15:37:16'),
(16, 2, 8, 67.07, 0.01, 3.35, 70.41, 'mobile', '2025-10-28 15:37:16'),
(17, 8, 10, 65.61, 2.72, 3.28, 66.17, 'card', '2025-10-12 15:37:16'),
(18, 2, 7, 34.02, 4.45, 1.70, 31.27, 'cash', '2025-10-07 15:37:16'),
(19, 6, 9, 78.35, 4.62, 3.92, 77.65, 'mobile', '2025-10-23 15:37:16'),
(20, 8, 7, 37.26, 0.19, 1.86, 38.93, 'mobile', '2025-10-16 15:37:16'),
(21, 8, 9, 55.56, 1.06, 2.78, 57.28, 'mobile', '2025-10-30 15:37:16'),
(22, 6, 1, 32.80, 3.91, 1.64, 30.53, 'cash', '2025-10-13 15:37:16'),
(23, 6, 9, 76.76, 2.72, 3.84, 77.88, 'card', '2025-10-13 15:37:16'),
(24, 2, 1, 42.46, 3.25, 2.12, 41.33, 'mobile', '2025-10-30 15:37:16'),
(25, 5, 1, 71.51, 3.89, 3.58, 71.20, 'cash', '2025-10-12 15:37:16'),
(26, 5, 8, 50.77, 3.88, 2.54, 49.43, 'mobile', '2025-10-27 15:37:16'),
(27, 3, 10, 62.40, 2.70, 3.12, 62.82, 'cash', '2025-10-13 15:37:16'),
(28, 7, 10, 33.98, 2.64, 1.70, 33.04, 'card', '2025-11-01 15:37:16'),
(29, 6, 8, 67.53, 2.06, 3.38, 68.85, 'mobile', '2025-10-06 15:37:16'),
(30, 1, 9, 37.69, 2.17, 1.88, 37.40, 'cash', '2025-10-23 15:37:16'),
(31, 3, 7, 64.24, 0.94, 3.21, 66.51, 'card', '2025-10-08 15:37:16'),
(32, NULL, 1, 28.00, 0.00, 1.40, 29.40, 'cash', '2025-11-03 11:05:52'),
(33, NULL, 1, 16.00, 0.00, 0.80, 16.80, 'cash', '2025-11-03 11:08:55'),
(34, NULL, 1, 16.00, 0.00, 0.80, 16.80, 'cash', '2025-11-05 23:15:53');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `sale_item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`sale_item_id`, `sale_id`, `medicine_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 2, 1, 1, 5.00, 5.00),
(2, 2, 4, 2, 5.00, 10.00),
(3, 2, 10, 1, 11.00, 11.00),
(5, 3, 15, 1, 48.00, 48.00),
(6, 3, 16, 2, 13.00, 26.00),
(10, 6, 13, 2, 35.00, 70.00),
(12, 7, 9, 1, 28.00, 28.00),
(13, 7, 14, 1, 42.00, 42.00),
(14, 8, 14, 3, 42.00, 126.00),
(15, 8, 15, 2, 48.00, 96.00),
(17, 9, 9, 2, 28.00, 56.00),
(20, 11, 3, 1, 10.00, 10.00),
(21, 11, 14, 1, 42.00, 42.00),
(23, 12, 3, 3, 10.00, 30.00),
(24, 12, 11, 3, 14.00, 42.00),
(25, 12, 12, 1, 25.00, 25.00),
(27, 13, 12, 3, 25.00, 75.00),
(28, 13, 15, 2, 48.00, 96.00),
(29, 14, 1, 1, 5.00, 5.00),
(30, 14, 18, 2, 220.00, 440.00),
(32, 15, 15, 3, 48.00, 144.00),
(34, 16, 12, 1, 25.00, 25.00),
(35, 16, 14, 1, 42.00, 42.00),
(37, 17, 15, 3, 48.00, 144.00),
(38, 17, 18, 1, 220.00, 220.00),
(39, 18, 3, 2, 10.00, 20.00),
(40, 18, 5, 3, 6.50, 19.50),
(41, 18, 15, 2, 48.00, 96.00),
(43, 19, 6, 3, 8.00, 24.00),
(44, 19, 15, 2, 48.00, 96.00),
(45, 20, 12, 3, 25.00, 75.00),
(46, 20, 16, 3, 13.00, 39.00),
(47, 20, 18, 2, 220.00, 440.00),
(48, 21, 2, 2, 15.00, 30.00),
(50, 22, 5, 1, 6.50, 6.50),
(51, 22, 14, 1, 42.00, 42.00),
(52, 22, 15, 3, 48.00, 144.00),
(54, 24, 2, 1, 15.00, 15.00),
(55, 25, 11, 2, 14.00, 28.00),
(56, 25, 12, 1, 25.00, 25.00),
(57, 25, 13, 1, 35.00, 35.00),
(59, 27, 5, 1, 6.50, 6.50),
(60, 28, 1, 2, 5.00, 10.00),
(61, 29, 8, 2, 22.50, 45.00),
(62, 29, 18, 1, 220.00, 220.00),
(63, 30, 17, 2, 85.00, 170.00),
(64, 31, 6, 2, 8.00, 16.00),
(65, 31, 17, 1, 85.00, 85.00),
(66, 31, 18, 3, 220.00, 660.00),
(67, 32, 34, 1, 28.00, 28.00),
(68, 33, 32, 1, 16.00, 16.00),
(69, 34, 32, 1, 16.00, 16.00);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `is_active`, `created_at`) VALUES
(1, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-03 08:21:47'),
(2, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-03 08:23:42'),
(3, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-03 08:24:23'),
(4, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-03 09:03:16'),
(5, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-03 09:48:07'),
(6, 'MediCare Suppliers', 'Sarah Johnson', '+96612345679', 'info@medicare.com', 'Jeddah, Saudi Arabia', 1, '2025-11-03 09:48:07'),
(7, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'orders@globalpharma.com', 'Industrial Area, Block 3, Riyadh, Saudi Arabia', 1, '2025-11-03 10:31:14'),
(8, 'MediCare Suppliers', 'Sarah Johnson', '+96612345679', 'info@medicare.com', 'Jeddah, Tahlia Street, Saudi Arabia', 1, '2025-11-03 10:31:14'),
(9, 'Saudi Medical Co.', 'Ahmed Al-Farsi', '+96612345680', 'contact@saudimedical.com', 'Al Khobar, Corniche Road, Saudi Arabia', 1, '2025-11-03 10:31:14'),
(10, 'Pharma Gulf', 'Fatima Al-Sabah', '+96612345681', 'sales@pharmagulf.com', 'Riyadh, Olaya Street, Saudi Arabia', 1, '2025-11-03 10:31:14'),
(11, 'Health Plus Distributors', 'Mohammed Hassan', '+96612345682', 'orders@healthplus.com', 'Dammam, Airport Road, Saudi Arabia', 1, '2025-11-03 10:31:14'),
(12, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'orders@globalpharma.com', 'Industrial Area, Block 3, Riyadh, Saudi Arabia', 1, '2025-11-03 10:37:16'),
(13, 'MediCare Suppliers', 'Sarah Johnson', '+96612345679', 'info@medicare.com', 'Jeddah, Tahlia Street, Saudi Arabia', 1, '2025-11-03 10:37:16'),
(14, 'Saudi Medical Co.', 'Ahmed Al-Farsi', '+96612345680', 'contact@saudimedical.com', 'Al Khobar, Corniche Road, Saudi Arabia', 1, '2025-11-03 10:37:16'),
(15, 'Pharma Gulf', 'Fatima Al-Sabah', '+96612345681', 'sales@pharmagulf.com', 'Riyadh, Olaya Street, Saudi Arabia', 1, '2025-11-03 10:37:16'),
(16, 'Health Plus Distributors', 'Mohammed Hassan', '+96612345682', 'orders@healthplus.com', 'Dammam, Airport Road, Saudi Arabia', 1, '2025-11-03 10:37:16'),
(17, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-05 04:38:07'),
(18, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-05 04:40:38'),
(19, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-05 05:15:27'),
(20, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-10 19:27:41'),
(21, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-11 04:59:23'),
(22, 'Global Pharma Distributors', 'John Smith', '+96612345678', 'contact@globalpharma.com', 'Industrial Area, Riyadh, Saudi Arabia', 1, '2025-11-19 01:29:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','pharmacist','assistant') DEFAULT 'assistant',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `role`, `is_active`, `created_at`, `phone`, `last_login`, `updated_at`) VALUES
(1, 'admin', '$2y$10$VPMKzYB1jsGZEWsghw557.8N/d2PZ0Iql3F/52zXhuwhuHVPmYNfe', 'System Administrator', NULL, 'admin', 1, '2025-11-03 06:40:50', NULL, '2025-11-19 01:29:16', '2025-11-19 01:29:16'),
(7, 'pharmacist1', '$2y$10$jCkZhMpIZw/JNy2liGKkTexw2yCoMyW./mTr5EiMUEgyvwS8zmp4K', 'Pharmacist User 1', 'pharmacist1@pharmacy.com', 'pharmacist', 1, '2025-11-03 10:31:14', '+96650011111', NULL, '2025-11-03 10:31:14'),
(8, 'pharmacist2', '$2y$10$PHRDCQTPn9GkNsrJXJfWMOhHdLZU6SQN3bsGAnEC7vd9YXAOnrNsq', 'Pharmacist User 2', 'pharmacist2@pharmacy.com', 'pharmacist', 1, '2025-11-03 10:31:14', '+96650011112', NULL, '2025-11-03 10:31:14'),
(9, 'assistant1', '$2y$10$HdkgEmIPpxVNHsqu/d2C6OSgIo37CDJ.zm0s1MwG94QPqAlYPgWXK', 'Assistant User 1', 'assistant1@pharmacy.com', 'assistant', 1, '2025-11-03 10:31:14', '+96650011113', NULL, '2025-11-03 10:31:14'),
(10, 'assistant2', '$2y$10$sxE22ngustHgMMTT0J86i.UVTznCHwTrZ4K4pyrtW76englB7viUy', 'Assistant User 2', 'assistant2@pharmacy.com', 'assistant', 1, '2025-11-03 10:31:15', '+96650011114', NULL, '2025-11-03 10:31:15'),
(11, 'adm', '$2y$10$9oaAvBsOiBzPXuTq3.xbLOyeMOCjbOjDFg4KXRm3fxRYB.J5QVj0O', 'Abd', 'abd@gmail.com', 'pharmacist', 1, '2025-11-03 11:29:00', '0966772023984', '2025-11-03 14:43:17', '2025-11-03 14:43:17');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
