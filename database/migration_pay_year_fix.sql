-- --------------------------------------------------------
-- Migration to fix missing pay_year column in payroll table
-- --------------------------------------------------------

USE `hrms_db`;

-- Add pay_year and pay_month columns to payroll table
ALTER TABLE `payroll` 
ADD COLUMN `pay_year` int(4) DEFAULT NULL AFTER `employee_id`,
ADD COLUMN `pay_month` int(2) DEFAULT NULL AFTER `pay_year`;

-- Update existing payroll records with pay_year and pay_month from pay_period_start
UPDATE `payroll` 
SET pay_year = YEAR(pay_period_start),
    pay_month = MONTH(pay_period_start) 
WHERE pay_year IS NULL OR pay_month IS NULL;

-- Add index for pay_year and pay_month for better query performance
ALTER TABLE `payroll`
ADD INDEX `idx_pay_period` (`pay_year`, `pay_month`);

COMMIT;

-- --------------------------------------------------------
-- Verification query (run this to check if migration was successful)
-- --------------------------------------------------------

-- Check if pay_year column exists in payroll table
-- SELECT COUNT(*) as pay_year_column_exists FROM information_schema.columns WHERE table_schema = 'hrms_db' AND table_name = 'payroll' AND column_name = 'pay_year';

-- Check if pay_month column exists in payroll table
-- SELECT COUNT(*) as pay_month_column_exists FROM information_schema.columns WHERE table_schema = 'hrms_db' AND table_name = 'payroll' AND column_name = 'pay_month';

-- Check sample data
-- SELECT id, employee_id, pay_year, pay_month, pay_period_start FROM payroll LIMIT 5;