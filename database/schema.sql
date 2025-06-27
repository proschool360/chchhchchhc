

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','hr','manager','employee') NOT NULL DEFAULT 'employee',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `user_activities`
-- --------------------------------------------------------

CREATE TABLE `user_activities` (
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
-- Table structure for table `departments`
-- --------------------------------------------------------

CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `manager_id` int(11) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `manager_id` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `positions`
-- --------------------------------------------------------

CREATE TABLE `positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `description` text,
  `requirements` text,
  `min_salary` decimal(10,2) DEFAULT NULL,
  `max_salary` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `employees`
-- --------------------------------------------------------

CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL UNIQUE,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `hire_date` date NOT NULL,
  `termination_date` date DEFAULT NULL,
  `employment_type` enum('full-time','part-time','contract','intern') NOT NULL DEFAULT 'full-time',
  `work_location` enum('office','remote','hybrid') NOT NULL DEFAULT 'office',
  `salary` decimal(10,2) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','terminated') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `department_id` (`department_id`),
  KEY `position_id` (`position_id`),
  KEY `manager_id` (`manager_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `attendance`
-- --------------------------------------------------------

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `total_hours` decimal(4,2) DEFAULT NULL,
  `hours_worked` decimal(4,2) DEFAULT NULL,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `status` enum('present','absent','late','half-day','holiday') NOT NULL DEFAULT 'present',
  `notes` text,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_date` (`employee_id`, `date`),
  KEY `employee_id` (`employee_id`),
  KEY `approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `leave_types`
-- --------------------------------------------------------

CREATE TABLE `leave_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `days_per_year` int(11) DEFAULT NULL,
  `max_days_per_year` int(11) DEFAULT NULL,
  `default_days` int(11) DEFAULT NULL,
  `carry_forward` tinyint(1) DEFAULT 0,
  `requires_approval` tinyint(1) DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `leave_balances`
-- --------------------------------------------------------

CREATE TABLE `leave_balances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `balance` decimal(5,2) NOT NULL DEFAULT 0.00,
  `used` decimal(5,2) NOT NULL DEFAULT 0.00,
  `year` int(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_leave_type_year` (`employee_id`, `leave_type_id`, `year`),
  KEY `employee_id` (`employee_id`),
  KEY `leave_type_id` (`leave_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `employee_leave_balances`
-- --------------------------------------------------------

CREATE TABLE `employee_leave_balances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `allocated_days` decimal(5,2) NOT NULL DEFAULT 0.00,
  `used_days` decimal(5,2) NOT NULL DEFAULT 0.00,
  `remaining_days` decimal(5,2) NOT NULL DEFAULT 0.00,
  `year` int(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_leave_type_year` (`employee_id`, `leave_type_id`, `year`),
  KEY `employee_id` (`employee_id`),
  KEY `leave_type_id` (`leave_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `leave_requests`
-- --------------------------------------------------------

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int(11) NOT NULL,
  `reason` text,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `leave_type_id` (`leave_type_id`),
  KEY `approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payroll`
-- --------------------------------------------------------

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `pay_year` int(4) DEFAULT NULL,
  `pay_month` int(2) DEFAULT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `overtime_amount` decimal(10,2) DEFAULT 0.00,
  `bonus` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `gross_salary` decimal(10,2) NOT NULL,
  `tax_deduction` decimal(10,2) DEFAULT 0.00,
  `pf_deduction` decimal(10,2) DEFAULT 0.00,
  `esi_deduction` decimal(10,2) DEFAULT 0.00,
  `tds_deduction` decimal(10,2) DEFAULT 0.00,
  `professional_tax` decimal(10,2) DEFAULT 0.00,
  `insurance_deduction` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `total_deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL,
  `status` enum('draft','processed','paid') NOT NULL DEFAULT 'draft',
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `processed_by` (`processed_by`),
  KEY `pay_year_month` (`pay_year`, `pay_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `performance_reviews`
-- --------------------------------------------------------

CREATE TABLE `performance_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `review_period_start` date NOT NULL,
  `review_period_end` date NOT NULL,
  `overall_rating` decimal(3,2) DEFAULT NULL,
  `goals_achievement` text,
  `strengths` text,
  `areas_for_improvement` text,
  `development_plan` text,
  `employee_comments` text,
  `status` enum('draft','submitted','completed') NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `reviewer_id` (`reviewer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `training_programs`
-- --------------------------------------------------------

CREATE TABLE `training_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category` enum('technical','soft_skills','compliance','leadership','safety','orientation') NOT NULL,
  `level` enum('beginner','intermediate','advanced') NOT NULL,
  `duration_hours` decimal(5,2) NOT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `prerequisites` text,
  `learning_objectives` text,
  `certification_available` tinyint(1) DEFAULT 0,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `category` (`category`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `training_modules`
-- --------------------------------------------------------

CREATE TABLE `training_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `content` text,
  `sequence_order` int(11) NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `module_type` enum('video','document','quiz','assignment','discussion') DEFAULT 'document',
  `is_mandatory` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `training_enrollments`
-- --------------------------------------------------------

CREATE TABLE `training_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('enrolled','in_progress','completed','dropped') DEFAULT 'enrolled',
  `feedback` text,
  `rating` decimal(3,2) DEFAULT NULL,
  `certificate_issued` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`program_id`, `employee_id`),
  KEY `program_id` (`program_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `job_postings`
-- --------------------------------------------------------

CREATE TABLE `job_postings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `requirements` text,
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `employment_type` enum('full-time','part-time','contract','intern') NOT NULL DEFAULT 'full-time',
  `location` varchar(100) DEFAULT NULL,
  `posted_date` date NOT NULL,
  `application_deadline` date DEFAULT NULL,
  `status` enum('active','closed','draft') NOT NULL DEFAULT 'draft',
  `posted_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `position_id` (`position_id`),
  KEY `posted_by` (`posted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `job_applications`
-- --------------------------------------------------------

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_posting_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `cover_letter` text,
  `experience_years` int(11) DEFAULT NULL,
  `current_salary` decimal(10,2) DEFAULT NULL,
  `expected_salary` decimal(10,2) DEFAULT NULL,
  `status` enum('applied','screening','interview','rejected','hired') NOT NULL DEFAULT 'applied',
  `notes` text,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `job_posting_id` (`job_posting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `employee_documents`
-- --------------------------------------------------------

CREATE TABLE `employee_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_confidential` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add Foreign Key Constraints
-- --------------------------------------------------------

ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `departments`
  ADD CONSTRAINT `departments_manager_fk` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

ALTER TABLE `positions`
  ADD CONSTRAINT `positions_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

ALTER TABLE `employees`
  ADD CONSTRAINT `employees_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_position_fk` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_manager_fk` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_approved_by_fk` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_balances_leave_type_fk` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE;

ALTER TABLE `employee_leave_balances`
  ADD CONSTRAINT `employee_leave_balances_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_leave_balances_leave_type_fk` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE;

ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_leave_type_fk` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_approved_by_fk` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_processed_by_fk` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

ALTER TABLE `performance_reviews`
  ADD CONSTRAINT `performance_reviews_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_reviews_reviewer_fk` FOREIGN KEY (`reviewer_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

ALTER TABLE `training_programs`
  ADD CONSTRAINT `training_programs_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

ALTER TABLE `training_modules`
  ADD CONSTRAINT `training_modules_program_fk` FOREIGN KEY (`program_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE;

ALTER TABLE `training_enrollments`
  ADD CONSTRAINT `training_enrollments_program_fk` FOREIGN KEY (`program_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `training_enrollments_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

ALTER TABLE `job_postings`
  ADD CONSTRAINT `job_postings_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_postings_position_fk` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_postings_posted_by_fk` FOREIGN KEY (`posted_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_job_posting_fk` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings` (`id`) ON DELETE CASCADE;

ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_documents_uploaded_by_fk` FOREIGN KEY (`uploaded_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Sample Data
-- --------------------------------------------------------

-- Insert sample users
INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`) VALUES
('admin', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
('hr_manager', 'hr@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr', 'active'),
('john_doe', 'john.doe@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'active'),
('jane_smith', 'jane.smith@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'active'),
('mike_wilson', 'mike.wilson@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'active');

-- Insert sample departments
INSERT INTO `departments` (`name`, `description`, `budget`, `status`) VALUES
('Information Technology', 'Handles all IT operations and software development', 500000.00, 'active'),
('Human Resources', 'Manages employee relations and organizational development', 200000.00, 'active'),
('Finance', 'Manages financial operations and accounting', 300000.00, 'active'),
('Marketing', 'Handles marketing campaigns and brand management', 250000.00, 'active'),
('Sales', 'Manages sales operations and customer relationships', 400000.00, 'active');

-- Insert sample positions
INSERT INTO `positions` (`title`, `department_id`, `description`, `min_salary`, `max_salary`, `status`) VALUES
('Software Developer', 1, 'Develops and maintains software applications', 60000.00, 120000.00, 'active'),
('IT Manager', 1, 'Manages IT department and technology strategy', 80000.00, 150000.00, 'active'),
('HR Manager', 2, 'Manages human resources operations', 70000.00, 130000.00, 'active'),
('HR Specialist', 2, 'Handles recruitment and employee relations', 50000.00, 90000.00, 'active'),
('Financial Analyst', 3, 'Analyzes financial data and prepares reports', 55000.00, 100000.00, 'active'),
('Marketing Manager', 4, 'Manages marketing campaigns and strategies', 65000.00, 120000.00, 'active'),
('Sales Representative', 5, 'Manages customer relationships and sales', 40000.00, 80000.00, 'active');

-- Insert sample employees
INSERT INTO `employees` (`user_id`, `employee_id`, `first_name`, `last_name`, `email`, `date_of_birth`, `gender`, `phone`, `address`, `department_id`, `position_id`, `hire_date`, `employment_type`, `salary`, `status`) VALUES
(1, 'EMP001', 'Admin', 'User', 'admin@company.com', '1980-01-15', 'male', '+1234567890', '123 Admin St, City, State', 1, 2, '2020-01-01', 'full-time', 120000.00, 'active'),
(2, 'EMP002', 'HR', 'Manager', 'hr@company.com', '1985-03-20', 'female', '+1234567891', '456 HR Ave, City, State', 2, 3, '2020-02-01', 'full-time', 100000.00, 'active'),
(3, 'EMP003', 'John', 'Doe', 'john.doe@company.com', '1990-05-10', 'male', '+1234567892', '789 Manager Blvd, City, State', 1, 2, '2021-03-15', 'full-time', 95000.00, 'active'),
(4, 'EMP004', 'Jane', 'Smith', 'jane.smith@company.com', '1992-08-25', 'female', '+1234567893', '321 Employee St, City, State', 1, 1, '2022-01-10', 'full-time', 75000.00, 'active'),
(5, 'EMP005', 'Mike', 'Wilson', 'mike.wilson@company.com', '1988-12-05', 'male', '+1234567894', '654 Worker Ave, City, State', 5, 7, '2022-06-01', 'full-time', 65000.00, 'active');

-- Update manager references
UPDATE `departments` SET `manager_id` = 1 WHERE `id` = 1;
UPDATE `departments` SET `manager_id` = 2 WHERE `id` = 2;
UPDATE `employees` SET `manager_id` = 1 WHERE `id` IN (3, 4);
UPDATE `employees` SET `manager_id` = 3 WHERE `id` = 4;

-- Insert sample leave types
INSERT INTO `leave_types` (`name`, `description`, `days_per_year`, `max_days_per_year`, `default_days`, `carry_forward`, `requires_approval`, `status`) VALUES
('Annual Leave', 'Yearly vacation leave', 21, 21, 21, 1, 1, 'active'),
('Sick Leave', 'Medical leave for illness', 10, 10, 10, 0, 0, 'active'),
('Personal Leave', 'Personal time off', 5, 5, 5, 0, 1, 'active'),
('Maternity Leave', 'Maternity leave for new mothers', 90, 90, 90, 0, 1, 'active'),
('Paternity Leave', 'Paternity leave for new fathers', 15, 15, 15, 0, 1, 'active');

-- Insert sample leave balances
INSERT INTO `leave_balances` (`employee_id`, `leave_type_id`, `balance`, `used`, `year`) VALUES
(1, 1, 21.00, 5.00, 2024),
(1, 2, 10.00, 2.00, 2024),
(2, 1, 21.00, 8.00, 2024),
(2, 2, 10.00, 1.00, 2024),
(3, 1, 21.00, 3.00, 2024),
(3, 2, 10.00, 0.00, 2024),
(4, 1, 21.00, 7.00, 2024),
(4, 2, 10.00, 3.00, 2024),
(5, 1, 21.00, 4.00, 2024),
(5, 2, 10.00, 1.00, 2024);

-- Insert sample employee leave balances
INSERT INTO `employee_leave_balances` (`employee_id`, `leave_type_id`, `allocated_days`, `used_days`, `remaining_days`, `year`) VALUES
(1, 1, 21.00, 5.00, 16.00, 2024),
(1, 2, 10.00, 2.00, 8.00, 2024),
(2, 1, 21.00, 8.00, 13.00, 2024),
(2, 2, 10.00, 1.00, 9.00, 2024),
(3, 1, 21.00, 3.00, 18.00, 2024),
(3, 2, 10.00, 0.00, 10.00, 2024),
(4, 1, 21.00, 7.00, 14.00, 2024),
(4, 2, 10.00, 3.00, 7.00, 2024),
(5, 1, 21.00, 4.00, 17.00, 2024),
(5, 2, 10.00, 1.00, 9.00, 2024);

-- Insert sample attendance records
INSERT INTO `attendance` (`employee_id`, `date`, `clock_in`, `clock_out`, `total_hours`, `hours_worked`, `status`) VALUES
(1, '2024-01-15', '09:00:00', '17:30:00', 8.50, 8.50, 'present'),
(1, '2024-01-16', '09:15:00', '17:30:00', 8.25, 8.25, 'late'),
(2, '2024-01-15', '08:30:00', '17:00:00', 8.50, 8.50, 'present'),
(2, '2024-01-16', '08:45:00', '17:15:00', 8.50, 8.50, 'present'),
(3, '2024-01-15', '09:00:00', '18:00:00', 9.00, 9.00, 'present'),
(3, '2024-01-16', NULL, NULL, 0.00, 0.00, 'absent'),
(4, '2024-01-15', '09:30:00', '17:30:00', 8.00, 8.00, 'late'),
(4, '2024-01-16', '09:00:00', '17:00:00', 8.00, 8.00, 'present'),
(5, '2024-01-15', '08:45:00', '17:15:00', 8.50, 8.50, 'present'),
(5, '2024-01-16', '09:00:00', '17:30:00', 8.50, 8.50, 'present');

-- Insert sample payroll records
INSERT INTO `payroll` (`employee_id`, `pay_period_start`, `pay_period_end`, `pay_year`, `pay_month`, `basic_salary`, `gross_salary`, `tax_deduction`, `net_salary`, `status`) VALUES
(1, '2024-01-01', '2024-01-31', 2024, 1, 10000.00, 10000.00, 2000.00, 8000.00, 'paid'),
(2, '2024-01-01', '2024-01-31', 2024, 1, 8333.33, 8333.33, 1666.67, 6666.66, 'paid'),
(3, '2024-01-01', '2024-01-31', 2024, 1, 7916.67, 7916.67, 1583.33, 6333.34, 'paid'),
(4, '2024-01-01', '2024-01-31', 2024, 1, 6250.00, 6250.00, 1250.00, 5000.00, 'paid'),
(5, '2024-01-01', '2024-01-31', 2024, 1, 5416.67, 5416.67, 1083.33, 4333.34, 'paid');

-- Insert sample training programs
INSERT INTO `training_programs` (`title`, `description`, `category`, `level`, `duration_hours`, `max_participants`, `status`, `created_by`) VALUES
('PHP Development Fundamentals', 'Learn the basics of PHP programming and web development', 'technical', 'beginner', 40.00, 20, 'published', 1),
('Leadership Skills', 'Develop essential leadership and management skills', 'leadership', 'intermediate', 24.00, 15, 'published', 2),
('Data Security Compliance', 'Understanding data protection and security compliance', 'compliance', 'intermediate', 16.00, 25, 'published', 1),
('Advanced JavaScript', 'Advanced concepts in JavaScript and modern frameworks', 'technical', 'advanced', 60.00, 12, 'published', 1),
('Communication Skills', 'Improve workplace communication and presentation skills', 'soft_skills', 'beginner', 20.00, 30, 'published', 2);

-- Insert sample training enrollments
INSERT INTO `training_enrollments` (`program_id`, `employee_id`, `status`, `progress_percentage`) VALUES
(1, 4, 'in_progress', 65.00),
(1, 5, 'completed', 100.00),
(2, 3, 'enrolled', 0.00),
(3, 1, 'completed', 100.00),
(3, 2, 'in_progress', 75.00),
(4, 1, 'in_progress', 40.00),
(5, 4, 'completed', 100.00),
(5, 5, 'enrolled', 0.00);

-- Insert sample user activities
INSERT INTO `user_activities` (`user_id`, `action`, `description`, `ip_address`, `user_agent`) VALUES
(1, 'login', 'User logged into the system', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(2, 'create_employee', 'Created new employee record for Jane Smith', '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(3, 'update_profile', 'Updated personal profile information', '192.168.1.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'),
(4, 'submit_leave_request', 'Submitted leave request for annual leave', '192.168.1.103', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X)'),
(5, 'clock_in', 'Clocked in for work', '192.168.1.104', 'Mozilla/5.0 (Android 11; Mobile; rv:68.0)');

-- Insert sample performance reviews
INSERT INTO `performance_reviews` (`employee_id`, `reviewer_id`, `review_period_start`, `review_period_end`, `overall_rating`, `goals_achievement`, `strengths`, `areas_for_improvement`, `status`) VALUES
(4, 3, '2023-01-01', '2023-12-31', 4.2, 'Exceeded most development goals and delivered projects on time', 'Strong technical skills, good team collaboration', 'Could improve communication with stakeholders', 'completed'),
(5, 1, '2023-07-01', '2023-12-31', 3.8, 'Met sales targets and maintained good customer relationships', 'Excellent customer service, persistent in follow-ups', 'Needs to improve product knowledge', 'completed');

COMMIT;