-- Migration to add missing break_time column to attendance table
-- This fixes the "Unknown column 'a.break_time'" error
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Add missing break_time column to attendance table
ALTER TABLE `attendance` 
ADD COLUMN `break_time` INT DEFAULT NULL COMMENT 'Break duration in minutes' 
AFTER `break_end`;

-- Update existing records with calculated break time
UPDATE `attendance` 
SET `break_time` = CASE 
    WHEN `break_start` IS NOT NULL AND `break_end` IS NOT NULL 
    THEN TIMESTAMPDIFF(MINUTE, 
        CONCAT(DATE(NOW()), ' ', `break_start`), 
        CONCAT(DATE(NOW()), ' ', `break_end`)
    )
    ELSE NULL 
END
WHERE `break_start` IS NOT NULL AND `break_end` IS NOT NULL;

-- Create index for performance
CREATE INDEX `idx_attendance_break_time` ON `attendance` (`break_time`);

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Break time column migration completed successfully!' as Status;