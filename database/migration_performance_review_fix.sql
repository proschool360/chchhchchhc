-- --------------------------------------------------------
-- Migration to fix missing columns in performance_reviews table
-- --------------------------------------------------------

-- Add missing columns to performance_reviews table
ALTER TABLE `performance_reviews` 
ADD COLUMN `review_year` int(4) DEFAULT NULL AFTER `review_period_end`,
ADD COLUMN `review_period` varchar(50) DEFAULT NULL AFTER `review_year`;

-- Update existing records to populate review_year from review_period_start
UPDATE `performance_reviews` 
SET `review_year` = YEAR(`review_period_start`),
    `review_period` = CONCAT(YEAR(`review_period_start`), '-', LPAD(MONTH(`review_period_start`), 2, '0'))
WHERE `review_year` IS NULL;

-- Add index for better performance on review_year queries
CREATE INDEX `idx_review_year` ON `performance_reviews` (`review_year`);
CREATE INDEX `idx_review_period` ON `performance_reviews` (`review_period`);

-- --------------------------------------------------------
-- Verification queries
-- --------------------------------------------------------

-- Check if columns were added successfully
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'performance_reviews' 
    AND COLUMN_NAME IN ('review_year', 'review_period');

-- Check if data was populated correctly
SELECT 
    COUNT(*) as total_records,
    COUNT(review_year) as records_with_review_year,
    COUNT(review_period) as records_with_review_period
FROM `performance_reviews`;

-- Sample data check
SELECT 
    id,
    review_period_start,
    review_period_end,
    review_year,
    review_period
FROM `performance_reviews` 
LIMIT 5;

COMMIT;