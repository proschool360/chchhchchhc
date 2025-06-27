-- =====================================================
-- HRMS Extended Features Migration Script
-- Features: Advanced Attendance System, ID Cards, Settings, Reporting
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. ATTENDANCE SYSTEM EXTENSIONS
-- =====================================================

-- Add new columns to existing attendance table
ALTER TABLE `attendance` 
ADD COLUMN `attendance_type` ENUM('manual', 'qr_code', 'rfid', 'biometric') DEFAULT 'manual' AFTER `status`,
ADD COLUMN `device_id` VARCHAR(100) DEFAULT NULL AFTER `attendance_type`,
ADD COLUMN `location_lat` DECIMAL(10, 8) DEFAULT NULL AFTER `device_id`,
ADD COLUMN `location_lng` DECIMAL(11, 8) DEFAULT NULL AFTER `location_lat`,
ADD COLUMN `late_minutes` INT DEFAULT 0 AFTER `location_lng`,
ADD COLUMN `overtime_minutes` INT DEFAULT 0 AFTER `late_minutes`,
ADD COLUMN `salary_deduction` DECIMAL(10,2) DEFAULT 0.00 AFTER `overtime_minutes`,
ADD COLUMN `overtime_bonus` DECIMAL(10,2) DEFAULT 0.00 AFTER `salary_deduction`,
ADD COLUMN `scheduled_clock_in` TIME DEFAULT NULL AFTER `overtime_bonus`,
ADD COLUMN `scheduled_clock_out` TIME DEFAULT NULL AFTER `scheduled_clock_in`;

-- =====================================================
-- 2. EMPLOYEE QR CODES AND RFID
-- =====================================================

-- Add QR code and RFID fields to employees table
ALTER TABLE `employees` 
ADD COLUMN `qr_code` VARCHAR(255) UNIQUE DEFAULT NULL AFTER `profile_picture`,
ADD COLUMN `rfid_card_id` VARCHAR(100) UNIQUE DEFAULT NULL AFTER `qr_code`,
ADD COLUMN `biometric_id` VARCHAR(100) UNIQUE DEFAULT NULL AFTER `rfid_card_id`;

-- =====================================================
-- 3. ATTENDANCE DEVICES TABLE
-- =====================================================

CREATE TABLE `attendance_devices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `device_name` VARCHAR(100) NOT NULL,
  `device_type` ENUM('qr_scanner', 'rfid_reader', 'biometric', 'mobile_app') NOT NULL,
  `device_id` VARCHAR(100) NOT NULL UNIQUE,
  `location` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `api_endpoint` VARCHAR(255) DEFAULT NULL,
  `api_key` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
  `last_sync` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `device_type` (`device_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. ID CARD TEMPLATES TABLE
-- =====================================================

CREATE TABLE `id_card_templates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `template_name` VARCHAR(100) NOT NULL,
  `template_data` JSON NOT NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_by` INT(11) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. EMPLOYEE ID CARDS TABLE
-- =====================================================

CREATE TABLE `employee_id_cards` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `template_id` INT(11) NOT NULL,
  `card_number` VARCHAR(50) NOT NULL UNIQUE,
  `issue_date` DATE NOT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `qr_code_data` TEXT DEFAULT NULL,
  `card_image_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'expired', 'revoked') DEFAULT 'active',
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_card` (`employee_id`, `card_number`),
  KEY `employee_id` (`employee_id`),
  KEY `template_id` (`template_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. SYSTEM SETTINGS TABLE
-- =====================================================

CREATE TABLE `system_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
  `category` VARCHAR(50) DEFAULT 'general',
  `description` TEXT DEFAULT NULL,
  `is_editable` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. WORK SCHEDULES TABLE
-- =====================================================

CREATE TABLE `work_schedules` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `day_of_week` TINYINT(1) NOT NULL COMMENT '1=Monday, 7=Sunday',
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `break_duration` INT DEFAULT 60 COMMENT 'Break duration in minutes',
  `is_working_day` TINYINT(1) DEFAULT 1,
  `effective_from` DATE NOT NULL,
  `effective_to` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `day_of_week` (`day_of_week`),
  KEY `effective_dates` (`effective_from`, `effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. ATTENDANCE REPORTS TABLE
-- =====================================================

CREATE TABLE `attendance_reports` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `report_month` TINYINT(2) NOT NULL,
  `report_year` YEAR NOT NULL,
  `total_working_days` INT DEFAULT 0,
  `days_present` INT DEFAULT 0,
  `days_absent` INT DEFAULT 0,
  `days_late` INT DEFAULT 0,
  `total_hours_worked` DECIMAL(8,2) DEFAULT 0.00,
  `total_overtime_hours` DECIMAL(8,2) DEFAULT 0.00,
  `total_late_minutes` INT DEFAULT 0,
  `total_salary_deduction` DECIMAL(10,2) DEFAULT 0.00,
  `total_overtime_bonus` DECIMAL(10,2) DEFAULT 0.00,
  `net_salary_impact` DECIMAL(10,2) DEFAULT 0.00,
  `generated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `generated_by` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_month_year` (`employee_id`, `report_month`, `report_year`),
  KEY `employee_id` (`employee_id`),
  KEY `report_period` (`report_month`, `report_year`),
  KEY `generated_by` (`generated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. SALARY DEDUCTION RULES TABLE
-- =====================================================

CREATE TABLE `salary_deduction_rules` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rule_name` VARCHAR(100) NOT NULL,
  `deduction_type` ENUM('per_minute', 'per_hour', 'fixed_amount', 'percentage') NOT NULL,
  `deduction_amount` DECIMAL(10,2) NOT NULL,
  `grace_period_minutes` INT DEFAULT 0,
  `max_deduction_per_day` DECIMAL(10,2) DEFAULT NULL,
  `applies_to_departments` JSON DEFAULT NULL,
  `applies_to_positions` JSON DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `effective_from` DATE NOT NULL,
  `effective_to` DATE DEFAULT NULL,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `deduction_type` (`deduction_type`),
  KEY `is_active` (`is_active`),
  KEY `effective_dates` (`effective_from`, `effective_to`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. OVERTIME RULES TABLE
-- =====================================================

CREATE TABLE `overtime_rules` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rule_name` VARCHAR(100) NOT NULL,
  `overtime_type` ENUM('per_minute', 'per_hour', 'fixed_amount', 'percentage') NOT NULL,
  `overtime_rate` DECIMAL(10,2) NOT NULL,
  `minimum_overtime_minutes` INT DEFAULT 30,
  `max_overtime_per_day` INT DEFAULT NULL COMMENT 'Max overtime minutes per day',
  `requires_approval` TINYINT(1) DEFAULT 1,
  `applies_to_departments` JSON DEFAULT NULL,
  `applies_to_positions` JSON DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `effective_from` DATE NOT NULL,
  `effective_to` DATE DEFAULT NULL,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `overtime_type` (`overtime_type`),
  KEY `is_active` (`is_active`),
  KEY `effective_dates` (`effective_from`, `effective_to`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. FOREIGN KEY CONSTRAINTS
-- =====================================================

ALTER TABLE `employee_id_cards`
ADD CONSTRAINT `fk_id_cards_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_id_cards_template` FOREIGN KEY (`template_id`) REFERENCES `id_card_templates` (`id`) ON DELETE RESTRICT,
ADD CONSTRAINT `fk_id_cards_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

ALTER TABLE `id_card_templates`
ADD CONSTRAINT `fk_templates_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

ALTER TABLE `work_schedules`
ADD CONSTRAINT `fk_schedules_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

ALTER TABLE `attendance_reports`
ADD CONSTRAINT `fk_reports_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_reports_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

ALTER TABLE `salary_deduction_rules`
ADD CONSTRAINT `fk_deduction_rules_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

ALTER TABLE `overtime_rules`
ADD CONSTRAINT `fk_overtime_rules_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

-- =====================================================
-- 12. INSERT DEFAULT SYSTEM SETTINGS
-- =====================================================

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`) VALUES
-- Attendance Settings
('attendance_grace_period', '15', 'number', 'attendance', 'Grace period in minutes before marking as late'),
('attendance_auto_clock_out', '1', 'boolean', 'attendance', 'Automatically clock out employees at end of shift'),
('attendance_location_tracking', '0', 'boolean', 'attendance', 'Enable location tracking for attendance'),
('attendance_photo_required', '0', 'boolean', 'attendance', 'Require photo capture during clock in/out'),

-- Late Deduction Settings
('late_deduction_enabled', '1', 'boolean', 'deductions', 'Enable automatic salary deductions for late arrivals'),
('late_deduction_type', 'per_minute', 'string', 'deductions', 'Type of late deduction calculation'),
('late_deduction_amount', '5.00', 'number', 'deductions', 'Deduction amount per minute/hour'),
('late_grace_period', '10', 'number', 'deductions', 'Grace period in minutes before deductions apply'),
('max_daily_deduction', '100.00', 'number', 'deductions', 'Maximum deduction amount per day'),

-- Overtime Settings
('overtime_enabled', '1', 'boolean', 'overtime', 'Enable overtime tracking and calculations'),
('overtime_type', 'per_hour', 'string', 'overtime', 'Type of overtime calculation'),
('overtime_rate', '25.00', 'number', 'overtime', 'Overtime rate per hour/minute'),
('overtime_approval_required', '1', 'boolean', 'overtime', 'Require approval for overtime claims'),
('min_overtime_minutes', '30', 'number', 'overtime', 'Minimum minutes to qualify for overtime'),
('max_daily_overtime', '240', 'number', 'overtime', 'Maximum overtime minutes per day'),

-- ID Card Settings
('id_card_validity_years', '2', 'number', 'id_cards', 'ID card validity period in years'),
('id_card_auto_generate', '1', 'boolean', 'id_cards', 'Automatically generate ID cards for new employees'),
('id_card_qr_enabled', '1', 'boolean', 'id_cards', 'Include QR code in ID cards'),

-- Working Hours
('default_work_start', '09:00:00', 'string', 'schedule', 'Default work start time'),
('default_work_end', '17:00:00', 'string', 'schedule', 'Default work end time'),
('default_break_duration', '60', 'number', 'schedule', 'Default break duration in minutes'),

-- Reporting
('report_auto_generate', '1', 'boolean', 'reports', 'Automatically generate monthly attendance reports'),
('report_email_enabled', '0', 'boolean', 'reports', 'Email reports to managers'),
('report_retention_months', '24', 'number', 'reports', 'Number of months to retain reports');

-- =====================================================
-- 13. INSERT DEFAULT ID CARD TEMPLATE
-- =====================================================

INSERT INTO `id_card_templates` (`template_name`, `template_data`, `is_default`, `created_by`) VALUES
('Default Employee ID Card', '{
  "width": 350,
  "height": 220,
  "background_color": "#ffffff",
  "elements": [
    {
      "type": "text",
      "content": "COMPANY NAME",
      "x": 175,
      "y": 20,
      "font_size": 16,
      "font_weight": "bold",
      "color": "#2c3e50",
      "align": "center"
    },
    {
      "type": "photo",
      "x": 20,
      "y": 50,
      "width": 80,
      "height": 100,
      "border_radius": 5
    },
    {
      "type": "text",
      "content": "{{first_name}} {{last_name}}",
      "x": 120,
      "y": 60,
      "font_size": 14,
      "font_weight": "bold",
      "color": "#2c3e50"
    },
    {
      "type": "text",
      "content": "ID: {{employee_id}}",
      "x": 120,
      "y": 80,
      "font_size": 12,
      "color": "#7f8c8d"
    },
    {
      "type": "text",
      "content": "{{department}}",
      "x": 120,
      "y": 100,
      "font_size": 12,
      "color": "#7f8c8d"
    },
    {
      "type": "text",
      "content": "{{position}}",
      "x": 120,
      "y": 120,
      "font_size": 12,
      "color": "#7f8c8d"
    },
    {
      "type": "qr_code",
      "x": 270,
      "y": 50,
      "size": 60,
      "data": "{{qr_code}}"
    },
    {
      "type": "text",
      "content": "Valid Until: {{expiry_date}}",
      "x": 175,
      "y": 190,
      "font_size": 10,
      "color": "#95a5a6",
      "align": "center"
    }
  ]
}', 1, 1);

-- =====================================================
-- 14. INSERT DEFAULT DEDUCTION AND OVERTIME RULES
-- =====================================================

INSERT INTO `salary_deduction_rules` (`rule_name`, `deduction_type`, `deduction_amount`, `grace_period_minutes`, `max_deduction_per_day`, `effective_from`, `created_by`) VALUES
('Standard Late Deduction', 'per_minute', 2.00, 10, 50.00, CURDATE(), 1),
('Management Late Deduction', 'per_minute', 5.00, 5, 100.00, CURDATE(), 1);

INSERT INTO `overtime_rules` (`rule_name`, `overtime_type`, `overtime_rate`, `minimum_overtime_minutes`, `max_overtime_per_day`, `requires_approval`, `effective_from`, `created_by`) VALUES
('Standard Overtime', 'per_hour', 25.00, 30, 240, 1, CURDATE(), 1),
('Weekend Overtime', 'per_hour', 35.00, 15, 480, 1, CURDATE(), 1);

-- =====================================================
-- 15. INSERT SAMPLE ATTENDANCE DEVICES
-- =====================================================

INSERT INTO `attendance_devices` (`device_name`, `device_type`, `device_id`, `location`, `status`) VALUES
('Main Entrance QR Scanner', 'qr_scanner', 'QR001', 'Main Building Entrance', 'active'),
('Office RFID Reader', 'rfid_reader', 'RFID001', 'Office Floor 1', 'active'),
('Biometric Scanner - HR', 'biometric', 'BIO001', 'HR Department', 'active'),
('Mobile App', 'mobile_app', 'MOBILE001', 'Remote/Mobile', 'active');

-- =====================================================
-- 16. UPDATE EXISTING EMPLOYEES WITH QR CODES
-- =====================================================

UPDATE `employees` SET 
  `qr_code` = CONCAT('QR_', `employee_id`, '_', UNIX_TIMESTAMP()),
  `rfid_card_id` = CONCAT('RFID_', `employee_id`)
WHERE `status` = 'active';

-- =====================================================
-- 17. INSERT DEFAULT WORK SCHEDULES
-- =====================================================

INSERT INTO `work_schedules` (`employee_id`, `day_of_week`, `start_time`, `end_time`, `break_duration`, `effective_from`)
SELECT 
  e.id,
  d.day_num,
  '09:00:00',
  '17:00:00',
  60,
  CURDATE()
FROM `employees` e
CROSS JOIN (
  SELECT 1 as day_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
) d
WHERE e.status = 'active';

-- =====================================================
-- 18. CREATE INDEXES FOR PERFORMANCE
-- =====================================================

CREATE INDEX `idx_attendance_employee_date_type` ON `attendance` (`employee_id`, `date`, `attendance_type`);
CREATE INDEX `idx_attendance_late_overtime` ON `attendance` (`late_minutes`, `overtime_minutes`);
CREATE INDEX `idx_employees_qr_rfid` ON `employees` (`qr_code`, `rfid_card_id`);
CREATE INDEX `idx_settings_category_key` ON `system_settings` (`category`, `setting_key`);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

SELECT 'HRMS Extended Features Migration Completed Successfully!' as Status;