-- HRMS Sample Data
-- Insert sample data for testing and demonstration

USE `hrms_db`;

-- Insert Leave Types
INSERT INTO `leave_types` (`name`, `description`, `max_days_per_year`, `carry_forward`, `requires_approval`) VALUES
('Annual Leave', 'Yearly vacation leave', 21, 1, 1),
('Sick Leave', 'Medical leave for illness', 12, 0, 0),
('Maternity Leave', 'Maternity leave for female employees', 180, 0, 1),
('Paternity Leave', 'Paternity leave for male employees', 15, 0, 1),
('Casual Leave', 'Short term casual leave', 12, 0, 0),
('Emergency Leave', 'Emergency situations', 5, 0, 1),
('Study Leave', 'Educational purposes', 10, 0, 1);

-- Insert Users
INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`) VALUES
('admin', 'admin@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
('hr_manager', 'hr@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr', 'active'),
('john_doe', 'john.doe@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'active'),
('jane_smith', 'jane.smith@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'active'),
('mike_johnson', 'mike.johnson@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'active'),
('sarah_wilson', 'sarah.wilson@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'active'),
('david_brown', 'david.brown@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'active'),
('lisa_davis', 'lisa.davis@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'active');

-- Insert Departments
INSERT INTO `departments` (`name`, `description`, `budget`, `status`) VALUES
('Human Resources', 'Manages employee relations and policies', 500000.00, 'active'),
('Information Technology', 'Handles all technology infrastructure', 1200000.00, 'active'),
('Finance & Accounting', 'Manages financial operations', 800000.00, 'active'),
('Sales & Marketing', 'Drives business growth and customer acquisition', 1000000.00, 'active'),
('Operations', 'Manages day-to-day business operations', 600000.00, 'active'),
('Research & Development', 'Innovation and product development', 1500000.00, 'active');

-- Insert Positions
INSERT INTO `positions` (`title`, `department_id`, `description`, `requirements`, `min_salary`, `max_salary`) VALUES
('HR Manager', 1, 'Oversees HR operations and policies', 'MBA in HR, 5+ years experience', 80000.00, 120000.00),
('Software Engineer', 2, 'Develops and maintains software applications', 'Bachelor in CS, 2+ years experience', 60000.00, 100000.00),
('Senior Software Engineer', 2, 'Leads development projects and mentors junior developers', 'Bachelor in CS, 5+ years experience', 90000.00, 140000.00),
('Financial Analyst', 3, 'Analyzes financial data and prepares reports', 'Bachelor in Finance, 2+ years experience', 50000.00, 80000.00),
('Sales Executive', 4, 'Manages client relationships and drives sales', 'Bachelor degree, 1+ years experience', 40000.00, 70000.00),
('Operations Manager', 5, 'Oversees operational processes', 'Bachelor degree, 3+ years experience', 70000.00, 110000.00),
('Research Scientist', 6, 'Conducts research and development activities', 'PhD in relevant field, 3+ years experience', 100000.00, 150000.00);

-- Insert Employees
INSERT INTO `employees` (`user_id`, `employee_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `phone`, `address`, `city`, `state`, `postal_code`, `country`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `department_id`, `position_id`, `hire_date`, `employment_type`, `work_location`, `salary`, `status`) VALUES
(1, 'EMP001', 'Admin', 'User', '1985-01-15', 'male', '+1234567890', '123 Admin St', 'New York', 'NY', '10001', 'USA', 'Emergency Contact', '+1234567891', 'Spouse', 1, 1, '2020-01-01', 'full-time', 'office', 100000.00, 'active'),
(2, 'EMP002', 'HR', 'Manager', '1982-03-20', 'female', '+1234567892', '456 HR Ave', 'New York', 'NY', '10002', 'USA', 'John Manager', '+1234567893', 'Spouse', 1, 1, '2020-02-01', 'full-time', 'office', 95000.00, 'active'),
(3, 'EMP003', 'John', 'Doe', '1988-05-10', 'male', '+1234567894', '789 Tech Blvd', 'San Francisco', 'CA', '94101', 'USA', 'Jane Doe', '+1234567895', 'Spouse', 2, 3, '2021-03-15', 'full-time', 'hybrid', 120000.00, 'active'),
(4, 'EMP004', 'Jane', 'Smith', '1990-07-25', 'female', '+1234567896', '321 Dev St', 'San Francisco', 'CA', '94102', 'USA', 'Bob Smith', '+1234567897', 'Brother', 2, 2, '2022-01-10', 'full-time', 'remote', 85000.00, 'active'),
(5, 'EMP005', 'Mike', 'Johnson', '1987-11-30', 'male', '+1234567898', '654 Finance Way', 'Chicago', 'IL', '60601', 'USA', 'Lisa Johnson', '+1234567899', 'Wife', 3, 4, '2021-06-01', 'full-time', 'office', 65000.00, 'active'),
(6, 'EMP006', 'Sarah', 'Wilson', '1992-02-14', 'female', '+1234567800', '987 Sales Dr', 'Los Angeles', 'CA', '90001', 'USA', 'Tom Wilson', '+1234567801', 'Husband', 4, 5, '2022-08-15', 'full-time', 'office', 55000.00, 'active'),
(7, 'EMP007', 'David', 'Brown', '1984-09-05', 'male', '+1234567802', '147 Ops Lane', 'Houston', 'TX', '77001', 'USA', 'Mary Brown', '+1234567803', 'Wife', 5, 6, '2020-11-20', 'full-time', 'office', 90000.00, 'active'),
(8, 'EMP008', 'Lisa', 'Davis', '1989-12-18', 'female', '+1234567804', '258 Research Rd', 'Boston', 'MA', '02101', 'USA', 'Steve Davis', '+1234567805', 'Husband', 6, 7, '2021-09-01', 'full-time', 'office', 125000.00, 'active');

-- Update department managers
UPDATE `departments` SET `manager_id` = 2 WHERE `id` = 1;
UPDATE `departments` SET `manager_id` = 3 WHERE `id` = 2;
UPDATE `departments` SET `manager_id` = 5 WHERE `id` = 3;
UPDATE `departments` SET `manager_id` = 6 WHERE `id` = 4;
UPDATE `departments` SET `manager_id` = 7 WHERE `id` = 5;
UPDATE `departments` SET `manager_id` = 8 WHERE `id` = 6;

-- Update employee managers
UPDATE `employees` SET `manager_id` = 3 WHERE `id` = 4;
UPDATE `employees` SET `manager_id` = 2 WHERE `id` = 5;
UPDATE `employees` SET `manager_id` = 7 WHERE `id` = 6;

-- Insert Attendance Records (Last 30 days)
INSERT INTO `attendance` (`employee_id`, `date`, `clock_in`, `clock_out`, `total_hours`, `overtime_hours`, `status`) VALUES
-- Employee 1 (Admin)
(1, CURDATE() - INTERVAL 29 DAY, '09:00:00', '18:00:00', 8.00, 0.00, 'present'),
(1, CURDATE() - INTERVAL 28 DAY, '09:15:00', '18:15:00', 8.00, 0.00, 'late'),
(1, CURDATE() - INTERVAL 27 DAY, '09:00:00', '19:00:00', 9.00, 1.00, 'present'),
-- Employee 2 (HR Manager)
(2, CURDATE() - INTERVAL 29 DAY, '08:30:00', '17:30:00', 8.00, 0.00, 'present'),
(2, CURDATE() - INTERVAL 28 DAY, '08:30:00', '17:30:00', 8.00, 0.00, 'present'),
(2, CURDATE() - INTERVAL 27 DAY, NULL, NULL, 0.00, 0.00, 'absent'),
-- Employee 3 (John Doe)
(3, CURDATE() - INTERVAL 29 DAY, '10:00:00', '19:00:00', 8.00, 0.00, 'present'),
(3, CURDATE() - INTERVAL 28 DAY, '10:00:00', '19:00:00', 8.00, 0.00, 'present'),
(3, CURDATE() - INTERVAL 27 DAY, '10:30:00', '19:30:00', 8.00, 0.00, 'late'),
-- Employee 4 (Jane Smith)
(4, CURDATE() - INTERVAL 29 DAY, '09:00:00', '17:00:00', 8.00, 0.00, 'present'),
(4, CURDATE() - INTERVAL 28 DAY, '09:00:00', '13:00:00', 4.00, 0.00, 'half-day'),
(4, CURDATE() - INTERVAL 27 DAY, '09:00:00', '17:00:00', 8.00, 0.00, 'present');

-- Insert Leave Requests
INSERT INTO `leave_requests` (`employee_id`, `leave_type_id`, `start_date`, `end_date`, `days_requested`, `reason`, `status`, `approved_by`) VALUES
(4, 1, CURDATE() + INTERVAL 7 DAY, CURDATE() + INTERVAL 11 DAY, 5, 'Family vacation', 'pending', NULL),
(5, 2, CURDATE() - INTERVAL 5 DAY, CURDATE() - INTERVAL 3 DAY, 3, 'Flu symptoms', 'approved', 2),
(6, 5, CURDATE() + INTERVAL 2 DAY, CURDATE() + INTERVAL 2 DAY, 1, 'Personal work', 'approved', 7),
(8, 1, CURDATE() + INTERVAL 15 DAY, CURDATE() + INTERVAL 25 DAY, 10, 'Annual vacation', 'pending', NULL);

-- Insert Payroll Records
INSERT INTO `payroll` (`employee_id`, `pay_period_start`, `pay_period_end`, `basic_salary`, `overtime_amount`, `bonus`, `allowances`, `gross_salary`, `tax_deduction`, `insurance_deduction`, `other_deductions`, `total_deductions`, `net_salary`, `status`, `processed_by`) VALUES
(1, '2024-01-01', '2024-01-31', 100000.00, 500.00, 2000.00, 1000.00, 103500.00, 15525.00, 2000.00, 500.00, 18025.00, 85475.00, 'paid', 2),
(2, '2024-01-01', '2024-01-31', 95000.00, 0.00, 1500.00, 1000.00, 97500.00, 14625.00, 1900.00, 500.00, 17025.00, 80475.00, 'paid', 1),
(3, '2024-01-01', '2024-01-31', 120000.00, 1000.00, 3000.00, 1200.00, 125200.00, 18780.00, 2400.00, 600.00, 21780.00, 103420.00, 'paid', 2),
(4, '2024-01-01', '2024-01-31', 85000.00, 0.00, 1000.00, 800.00, 86800.00, 13020.00, 1700.00, 400.00, 15120.00, 71680.00, 'paid', 2),
(5, '2024-01-01', '2024-01-31', 65000.00, 0.00, 800.00, 600.00, 66400.00, 9960.00, 1300.00, 300.00, 11560.00, 54840.00, 'paid', 2);

-- Insert Performance Reviews
INSERT INTO `performance_reviews` (`employee_id`, `reviewer_id`, `review_period_start`, `review_period_end`, `overall_rating`, `goals_achievement`, `strengths`, `areas_for_improvement`, `status`) VALUES
(3, 2, '2023-01-01', '2023-12-31', 4.5, 'Exceeded all technical goals and delivered 3 major projects on time', 'Strong technical skills, excellent problem-solving, good team collaboration', 'Could improve communication with non-technical stakeholders', 'completed'),
(4, 3, '2023-01-01', '2023-12-31', 4.2, 'Met most development goals, contributed to 2 successful product releases', 'Quick learner, attention to detail, reliable delivery', 'Needs to take more initiative in proposing solutions', 'completed'),
(5, 2, '2023-01-01', '2023-12-31', 4.0, 'Successfully managed financial reporting and budget analysis', 'Strong analytical skills, accurate reporting, meets deadlines', 'Could benefit from advanced Excel training', 'completed'),
(6, 7, '2023-01-01', '2023-12-31', 3.8, 'Achieved 95% of sales targets, acquired 15 new clients', 'Excellent client relationship skills, persistent follow-up', 'Needs to improve product knowledge for technical sales', 'completed');

-- Insert Job Postings
INSERT INTO `job_postings` (`title`, `department_id`, `position_id`, `description`, `requirements`, `salary_min`, `salary_max`, `employment_type`, `location`, `posted_date`, `application_deadline`, `status`, `posted_by`) VALUES
('Senior Software Engineer', 2, 3, 'We are looking for an experienced Senior Software Engineer to join our growing development team. You will be responsible for designing, developing, and maintaining high-quality software applications.', 'Bachelor degree in Computer Science or related field, 5+ years of software development experience, proficiency in modern programming languages, experience with cloud platforms', 90000.00, 140000.00, 'full-time', 'San Francisco, CA', CURDATE() - INTERVAL 10 DAY, CURDATE() + INTERVAL 20 DAY, 'active', 3),
('Marketing Specialist', 4, 5, 'Join our dynamic marketing team to help drive brand awareness and customer acquisition through innovative marketing campaigns and strategies.', 'Bachelor degree in Marketing or related field, 2+ years of marketing experience, proficiency in digital marketing tools, strong analytical skills', 45000.00, 65000.00, 'full-time', 'Los Angeles, CA', CURDATE() - INTERVAL 5 DAY, CURDATE() + INTERVAL 25 DAY, 'active', 6),
('Data Analyst', 6, 4, 'We are seeking a detail-oriented Data Analyst to help us make data-driven decisions and support our research initiatives.', 'Bachelor degree in Statistics, Mathematics, or related field, 2+ years of data analysis experience, proficiency in SQL and Python/R, experience with data visualization tools', 55000.00, 75000.00, 'full-time', 'Boston, MA', CURDATE() - INTERVAL 15 DAY, CURDATE() + INTERVAL 15 DAY, 'active', 8);

-- Insert Job Applications
INSERT INTO `job_applications` (`job_posting_id`, `first_name`, `last_name`, `email`, `phone`, `cover_letter`, `experience_years`, `current_salary`, `expected_salary`, `status`) VALUES
(1, 'Alex', 'Thompson', 'alex.thompson@email.com', '+1555123456', 'I am excited to apply for the Senior Software Engineer position. With 6 years of experience in full-stack development...', 6, 85000.00, 120000.00, 'interview'),
(1, 'Maria', 'Garcia', 'maria.garcia@email.com', '+1555123457', 'As a passionate software engineer with 7 years of experience, I believe I would be a great fit for this role...', 7, 95000.00, 130000.00, 'screening'),
(2, 'Robert', 'Lee', 'robert.lee@email.com', '+1555123458', 'I am writing to express my interest in the Marketing Specialist position. My background in digital marketing...', 3, 50000.00, 60000.00, 'applied'),
(3, 'Emily', 'Chen', 'emily.chen@email.com', '+1555123459', 'I am thrilled to apply for the Data Analyst position. My strong background in statistics and data science...', 4, 60000.00, 70000.00, 'interview');

-- Insert Employee Documents
INSERT INTO `employee_documents` (`employee_id`, `document_type`, `document_name`, `file_path`, `uploaded_by`, `is_confidential`) VALUES
(3, 'Contract', 'Employment Contract - John Doe', '/documents/contracts/john_doe_contract.pdf', 2, 1),
(3, 'ID Proof', 'Passport Copy', '/documents/id_proofs/john_doe_passport.pdf', 2, 1),
(4, 'Contract', 'Employment Contract - Jane Smith', '/documents/contracts/jane_smith_contract.pdf', 2, 1),
(4, 'Certificate', 'Computer Science Degree', '/documents/certificates/jane_smith_degree.pdf', 4, 0),
(5, 'Contract', 'Employment Contract - Mike Johnson', '/documents/contracts/mike_johnson_contract.pdf', 2, 1),
(6, 'Contract', 'Employment Contract - Sarah Wilson', '/documents/contracts/sarah_wilson_contract.pdf', 2, 1),
(7, 'Contract', 'Employment Contract - David Brown', '/documents/contracts/david_brown_contract.pdf', 2, 1),
(8, 'Contract', 'Employment Contract - Lisa Davis', '/documents/contracts/lisa_davis_contract.pdf', 2, 1),
(8, 'Certificate', 'PhD Certificate', '/documents/certificates/lisa_davis_phd.pdf', 8, 0);

COMMIT;