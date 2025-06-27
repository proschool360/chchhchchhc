-- Migration script to fix missing tables and columns
-- Run this script to resolve database errors

USE `hrms_db`;

-- --------------------------------------------------------
-- Create missing user_activities table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add missing email column to employees table
-- --------------------------------------------------------

ALTER TABLE `employees` 
ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `last_name`,
ADD UNIQUE KEY `email` (`email`);

-- --------------------------------------------------------
-- Add missing hours_worked column to attendance table
-- --------------------------------------------------------

ALTER TABLE `attendance` 
ADD COLUMN `hours_worked` decimal(4,2) DEFAULT NULL AFTER `total_hours`;

-- --------------------------------------------------------
-- Add missing columns to payroll table
-- --------------------------------------------------------

ALTER TABLE `payroll` 
ADD COLUMN `pay_year` int(4) DEFAULT NULL AFTER `employee_id`,
ADD COLUMN `pay_month` int(2) DEFAULT NULL AFTER `pay_year`,
ADD COLUMN `pf_deduction` decimal(10,2) DEFAULT 0.00 AFTER `insurance_deduction`,
ADD COLUMN `esi_deduction` decimal(10,2) DEFAULT 0.00 AFTER `pf_deduction`,
ADD COLUMN `tds_deduction` decimal(10,2) DEFAULT 0.00 AFTER `esi_deduction`,
ADD COLUMN `professional_tax` decimal(10,2) DEFAULT 0.00 AFTER `tds_deduction`;

-- Add index for pay_year and pay_month
ALTER TABLE `payroll`
ADD INDEX `pay_period` (`pay_year`, `pay_month`);

-- --------------------------------------------------------
-- Add foreign key constraint for user_activities
-- --------------------------------------------------------

ALTER TABLE `user_activities`
ADD CONSTRAINT `user_activities_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Update existing data (optional)
-- --------------------------------------------------------

-- Copy email from users table to employees table where missing
UPDATE `employees` e 
INNER JOIN `users` u ON e.user_id = u.id 
SET e.email = u.email 
WHERE e.email IS NULL;

-- Calculate hours_worked from total_hours where missing
UPDATE `attendance` 
SET hours_worked = total_hours 
WHERE hours_worked IS NULL AND total_hours IS NOT NULL;

-- Update payroll records with pay_year and pay_month from pay_period_start
UPDATE `payroll` 
SET pay_year = YEAR(pay_period_start), 
    pay_month = MONTH(pay_period_start) 
WHERE pay_year IS NULL OR pay_month IS NULL;

COMMIT;

-- --------------------------------------------------------
-- Verification queries (run these to check if migration was successful)
-- --------------------------------------------------------

-- Check if user_activities table exists
-- SELECT COUNT(*) as user_activities_exists FROM information_schema.tables WHERE table_schema = 'hrms_db' AND table_name = 'user_activities';

-- Check if email column exists in employees table
-- SELECT COUNT(*) as email_column_exists FROM information_schema.columns WHERE table_schema = 'hrms_db' AND table_name = 'employees' AND column_name = 'email';

-- Check if hours_worked column exists in attendance table
-- SELECT COUNT(*) as hours_worked_column_exists FROM information_schema.columns WHERE table_schema = 'hrms_db' AND table_name = 'attendance' AND column_name = 'hours_worked';

-- Check if pay_year column exists in payroll table
-- SELECT COUNT(*) as pay_year_column_exists FROM information_schema.columns WHERE table_schema = 'hrms_db' AND table_name = 'payroll' AND column_name = 'pay_year';

-- Check if pay_month column exists in payroll table
-- SELECT COUNT(*) as pay_month_column_exists FROM information_schema.columns WHERE table_schema = 'hrms_db' AND table_name = 'payroll' AND column_name = 'pay_month';