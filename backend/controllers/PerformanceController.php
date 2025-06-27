<?php
/**
 * Performance Controller
 * Handles performance management operations including goals, appraisals, and reviews
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class PerformanceController {
    private $db;
    private $user;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Set current user context
     */
    public function setUser($user) {
        $this->user = $user;
    }
    
    /**
     * Get performance reviews with pagination
     */
    public function index() {
        try {
            $params = Router::getQueryParams();
            
            // Pagination
            $page = max(1, (int)($params['page'] ?? 1));
            $perPage = min(100, max(10, (int)($params['per_page'] ?? 20)));
            $offset = ($page - 1) * $perPage;
            
            // Filters
            $filters = [];
            $filterValues = [];
            
            // Role-based access control
            if ($this->user['role'] === 'employee') {
                $filters[] = "(pr.employee_id = ? OR pr.reviewer_id = ?)";
                $filterValues[] = $this->user['employee_id'];
                $filterValues[] = $this->user['employee_id'];
            } elseif ($this->user['role'] === 'manager') {
                // Managers can see reviews for their department
                $filters[] = "e.department_id IN (SELECT id FROM departments WHERE manager_id = ?)";
                $filterValues[] = $this->user['employee_id'];
            }
            
            if (!empty($params['employee_id'])) {
                $filters[] = "pr.employee_id = ?";
                $filterValues[] = $params['employee_id'];
            }
            
            if (!empty($params['status'])) {
                $filters[] = "pr.status = ?";
                $filterValues[] = $params['status'];
            }
            
            if (!empty($params['review_period'])) {
                $filters[] = "pr.review_period = ?";
                $filterValues[] = $params['review_period'];
            }
            
            if (!empty($params['year'])) {
                $filters[] = "YEAR(pr.review_date) = ?";
                $filterValues[] = $params['year'];
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total 
                          FROM performance_reviews pr
                          JOIN employees e ON pr.employee_id = e.id
                          $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get reviews
            $query = "SELECT pr.*, 
                            e.first_name, e.last_name, e.employee_id as emp_id,
                            r.first_name as reviewer_first_name, r.last_name as reviewer_last_name,
                            d.name as department_name, p.title as position_title
                     FROM performance_reviews pr
                     JOIN employees e ON pr.employee_id = e.id
                     LEFT JOIN employees r ON pr.reviewer_id = r.id
                     LEFT JOIN departments d ON e.department_id = d.id
                     LEFT JOIN positions p ON e.position_id = p.id
                     $whereClause
                     ORDER BY pr.review_date DESC
                     LIMIT ? OFFSET ?";
            
            $reviews = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($reviews as &$review) {
                $review['employee_name'] = $review['first_name'] . ' ' . $review['last_name'];
                $review['reviewer_name'] = $review['reviewer_first_name'] ? 
                    $review['reviewer_first_name'] . ' ' . $review['reviewer_last_name'] : null;
                
                // Remove individual name fields
                unset($review['first_name'], $review['last_name'], 
                      $review['reviewer_first_name'], $review['reviewer_last_name']);
            }
            
            Response::paginated($reviews, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch performance reviews: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single performance review
     */
    public function show($id) {
        try {
            $query = "SELECT pr.*, 
                            e.first_name, e.last_name, e.employee_id as emp_id,
                            r.first_name as reviewer_first_name, r.last_name as reviewer_last_name,
                            d.name as department_name, p.title as position_title
                     FROM performance_reviews pr
                     JOIN employees e ON pr.employee_id = e.id
                     LEFT JOIN employees r ON pr.reviewer_id = r.id
                     LEFT JOIN departments d ON e.department_id = d.id
                     LEFT JOIN positions p ON e.position_id = p.id
                     WHERE pr.id = ?";
            
            $review = $this->db->selectOne($query, [$id]);
            
            if (!$review) {
                Response::notFound('Performance review not found');
                return;
            }
            
            // Check access permissions
            if ($this->user['role'] === 'employee' && 
                $review['employee_id'] != $this->user['employee_id'] && 
                $review['reviewer_id'] != $this->user['employee_id']) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Format data
            $review['employee_name'] = $review['first_name'] . ' ' . $review['last_name'];
            $review['reviewer_name'] = $review['reviewer_first_name'] ? 
                $review['reviewer_first_name'] . ' ' . $review['reviewer_last_name'] : null;
            
            // Get goals for this review
            $goals = $this->getReviewGoals($id);
            $review['goals'] = $goals;
            
            // Get 360 feedback if available
            $feedback = $this->get360Feedback($id);
            $review['feedback_360'] = $feedback;
            
            // Remove individual name fields
            unset($review['first_name'], $review['last_name'], 
                  $review['reviewer_first_name'], $review['reviewer_last_name']);
            
            Response::success($review);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch performance review: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new performance review
     */
    public function store() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'employee_id' => 'required|integer',
                'reviewer_id' => 'required|integer',
                'review_period' => 'required|in:quarterly,half_yearly,annual',
                'review_date' => 'required|date',
                'goals' => 'string',
                'achievements' => 'string',
                'areas_for_improvement' => 'string',
                'overall_rating' => 'required|numeric|min:1|max:5',
                'comments' => 'string',
                'status' => 'in:draft,submitted,approved,rejected'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Validate employee and reviewer
            $employee = $this->db->selectOne(
                "SELECT id FROM employees WHERE id = ? AND status = 'active'",
                [$data['employee_id']]
            );
            
            if (!$employee) {
                Response::error('Invalid employee selected', 400);
                return;
            }
            
            $reviewer = $this->db->selectOne(
                "SELECT id FROM employees WHERE id = ? AND status = 'active'",
                [$data['reviewer_id']]
            );
            
            if (!$reviewer) {
                Response::error('Invalid reviewer selected', 400);
                return;
            }
            
            // Check if review already exists for this period
            $existing = $this->db->selectOne(
                "SELECT id FROM performance_reviews 
                 WHERE employee_id = ? AND review_period = ? AND YEAR(review_date) = ?",
                [$data['employee_id'], $data['review_period'], date('Y', strtotime($data['review_date']))]
            );
            
            if ($existing) {
                Response::error('Performance review already exists for this period', 400);
                return;
            }
            
            // Create review
            $query = "INSERT INTO performance_reviews 
                     (employee_id, reviewer_id, review_period, review_date, goals, achievements, 
                      areas_for_improvement, overall_rating, comments, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $reviewId = $this->db->insert($query, [
                $data['employee_id'],
                $data['reviewer_id'],
                $data['review_period'],
                $data['review_date'],
                $data['goals'] ?? null,
                $data['achievements'] ?? null,
                $data['areas_for_improvement'] ?? null,
                $data['overall_rating'],
                $data['comments'] ?? null,
                $data['status'] ?? 'draft'
            ]);
            
            if ($reviewId) {
                $review = $this->db->selectOne(
                    "SELECT pr.*, e.first_name, e.last_name, r.first_name as reviewer_first_name, r.last_name as reviewer_last_name
                     FROM performance_reviews pr
                     JOIN employees e ON pr.employee_id = e.id
                     LEFT JOIN employees r ON pr.reviewer_id = r.id
                     WHERE pr.id = ?",
                    [$reviewId]
                );
                
                Response::created($review, 'Performance review created successfully');
            } else {
                Response::serverError('Failed to create performance review');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to create performance review: ' . $e->getMessage());
        }
    }
    
    /**
     * Update performance review
     */
    public function update($id) {
        try {
            $data = Router::getRequestData();
            
            // Check if review exists
            $review = $this->db->selectOne(
                "SELECT * FROM performance_reviews WHERE id = ?",
                [$id]
            );
            
            if (!$review) {
                Response::notFound('Performance review not found');
                return;
            }
            
            // Check permissions
            if ($this->user['role'] === 'employee' && 
                $review['reviewer_id'] != $this->user['employee_id']) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'goals' => 'string',
                'achievements' => 'string',
                'areas_for_improvement' => 'string',
                'overall_rating' => 'numeric|min:1|max:5',
                'comments' => 'string',
                'status' => 'in:draft,submitted,approved,rejected'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Prepare update data
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = ['goals', 'achievements', 'areas_for_improvement', 'overall_rating', 'comments', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                Response::error('No valid fields to update', 400);
                return;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $updateValues[] = $id;
            
            // Update review
            $query = "UPDATE performance_reviews SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $result = $this->db->update($query, $updateValues);
            
            if ($result) {
                $updatedReview = $this->db->selectOne(
                    "SELECT pr.*, e.first_name, e.last_name, r.first_name as reviewer_first_name, r.last_name as reviewer_last_name
                     FROM performance_reviews pr
                     JOIN employees e ON pr.employee_id = e.id
                     LEFT JOIN employees r ON pr.reviewer_id = r.id
                     WHERE pr.id = ?",
                    [$id]
                );
                
                Response::updated($updatedReview, 'Performance review updated successfully');
            } else {
                Response::serverError('Failed to update performance review');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update performance review: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete performance review
     */
    public function delete($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Check if review exists
            $review = $this->db->selectOne(
                "SELECT * FROM performance_reviews WHERE id = ?",
                [$id]
            );
            
            if (!$review) {
                Response::notFound('Performance review not found');
                return;
            }
            
            // Delete review
            $result = $this->db->delete("DELETE FROM performance_reviews WHERE id = ?", [$id]);
            
            if ($result) {
                Response::deleted('Performance review deleted successfully');
            } else {
                Response::serverError('Failed to delete performance review');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to delete performance review: ' . $e->getMessage());
        }
    }
    
    /**
     * Get employee goals
     */
    public function getGoals() {
        try {
            $params = Router::getQueryParams();
            
            // Pagination
            $page = max(1, (int)($params['page'] ?? 1));
            $perPage = min(100, max(10, (int)($params['per_page'] ?? 20)));
            $offset = ($page - 1) * $perPage;
            
            // Filters
            $filters = [];
            $filterValues = [];
            
            // Role-based access control
            if ($this->user['role'] === 'employee') {
                $filters[] = "g.employee_id = ?";
                $filterValues[] = $this->user['employee_id'];
            } elseif ($this->user['role'] === 'manager') {
                $filters[] = "e.department_id IN (SELECT id FROM departments WHERE manager_id = ?)";
                $filterValues[] = $this->user['employee_id'];
            }
            
            if (!empty($params['employee_id'])) {
                $filters[] = "g.employee_id = ?";
                $filterValues[] = $params['employee_id'];
            }
            
            if (!empty($params['status'])) {
                $filters[] = "g.status = ?";
                $filterValues[] = $params['status'];
            }
            
            if (!empty($params['year'])) {
                $filters[] = "YEAR(g.target_date) = ?";
                $filterValues[] = $params['year'];
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Create goals table if not exists (for this example)
            $this->createGoalsTable();
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total 
                          FROM employee_goals g
                          JOIN employees e ON g.employee_id = e.id
                          $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get goals
            $query = "SELECT g.*, 
                            e.first_name, e.last_name, e.employee_id as emp_id,
                            d.name as department_name
                     FROM employee_goals g
                     JOIN employees e ON g.employee_id = e.id
                     LEFT JOIN departments d ON e.department_id = d.id
                     $whereClause
                     ORDER BY g.target_date DESC
                     LIMIT ? OFFSET ?";
            
            $goals = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($goals as &$goal) {
                $goal['employee_name'] = $goal['first_name'] . ' ' . $goal['last_name'];
                $goal['progress_percentage'] = (float)$goal['progress_percentage'];
                
                // Remove individual name fields
                unset($goal['first_name'], $goal['last_name']);
            }
            
            Response::paginated($goals, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch goals: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new goal
     */
    public function storeGoal() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'employee_id' => 'required|integer',
                'title' => 'required|string|max:200',
                'description' => 'string',
                'target_date' => 'required|date',
                'weight' => 'numeric|min:0|max:100',
                'kpi_metric' => 'string|max:100',
                'target_value' => 'string|max:100',
                'status' => 'in:not_started,in_progress,completed,cancelled'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Check permissions
            if ($this->user['role'] === 'employee' && 
                $data['employee_id'] != $this->user['employee_id']) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Validate employee
            $employee = $this->db->selectOne(
                "SELECT id FROM employees WHERE id = ? AND status = 'active'",
                [$data['employee_id']]
            );
            
            if (!$employee) {
                Response::error('Invalid employee selected', 400);
                return;
            }
            
            // Create goals table if not exists
            $this->createGoalsTable();
            
            // Create goal
            $query = "INSERT INTO employee_goals 
                     (employee_id, title, description, target_date, weight, kpi_metric, 
                      target_value, status, progress_percentage, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
            
            $goalId = $this->db->insert($query, [
                $data['employee_id'],
                $data['title'],
                $data['description'] ?? null,
                $data['target_date'],
                $data['weight'] ?? 0,
                $data['kpi_metric'] ?? null,
                $data['target_value'] ?? null,
                $data['status'] ?? 'not_started'
            ]);
            
            if ($goalId) {
                $goal = $this->db->selectOne(
                    "SELECT g.*, e.first_name, e.last_name
                     FROM employee_goals g
                     JOIN employees e ON g.employee_id = e.id
                     WHERE g.id = ?",
                    [$goalId]
                );
                
                Response::created($goal, 'Goal created successfully');
            } else {
                Response::serverError('Failed to create goal');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to create goal: ' . $e->getMessage());
        }
    }
    
    /**
     * Update goal progress
     */
    public function updateGoal($id) {
        try {
            $data = Router::getRequestData();
            
            // Create goals table if not exists
            $this->createGoalsTable();
            
            // Check if goal exists
            $goal = $this->db->selectOne(
                "SELECT * FROM employee_goals WHERE id = ?",
                [$id]
            );
            
            if (!$goal) {
                Response::notFound('Goal not found');
                return;
            }
            
            // Check permissions
            if ($this->user['role'] === 'employee' && 
                $goal['employee_id'] != $this->user['employee_id']) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'progress_percentage' => 'numeric|min:0|max:100',
                'status' => 'in:not_started,in_progress,completed,cancelled',
                'notes' => 'string'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Prepare update data
            $updateFields = [];
            $updateValues = [];
            
            if (isset($data['progress_percentage'])) {
                $updateFields[] = "progress_percentage = ?";
                $updateValues[] = $data['progress_percentage'];
                
                // Auto-update status based on progress
                if ($data['progress_percentage'] == 100) {
                    $updateFields[] = "status = 'completed'";
                } elseif ($data['progress_percentage'] > 0) {
                    $updateFields[] = "status = 'in_progress'";
                }
            }
            
            if (isset($data['status'])) {
                $updateFields[] = "status = ?";
                $updateValues[] = $data['status'];
            }
            
            if (isset($data['notes'])) {
                $updateFields[] = "notes = ?";
                $updateValues[] = $data['notes'];
            }
            
            if (empty($updateFields)) {
                Response::error('No valid fields to update', 400);
                return;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $updateValues[] = $id;
            
            // Update goal
            $query = "UPDATE employee_goals SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $result = $this->db->update($query, $updateValues);
            
            if ($result) {
                $updatedGoal = $this->db->selectOne(
                    "SELECT g.*, e.first_name, e.last_name
                     FROM employee_goals g
                     JOIN employees e ON g.employee_id = e.id
                     WHERE g.id = ?",
                    [$id]
                );
                
                Response::updated($updatedGoal, 'Goal updated successfully');
            } else {
                Response::serverError('Failed to update goal');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update goal: ' . $e->getMessage());
        }
    }
    
    /**
     * Get performance analytics
     */
    public function getAnalytics() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $params = Router::getQueryParams();
            $year = $params['year'] ?? date('Y');
            
            // Overall performance statistics
            $overallStats = $this->db->selectOne(
                "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(overall_rating) as average_rating,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_reviews,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reviews
                 FROM performance_reviews 
                 WHERE YEAR(review_date) = ?",
                [$year]
            );
            
            // Department-wise performance
            $departmentStats = $this->db->select(
                "SELECT d.name as department_name,
                        COUNT(pr.id) as total_reviews,
                        AVG(pr.overall_rating) as average_rating
                 FROM departments d
                 LEFT JOIN employees e ON d.id = e.department_id
                 LEFT JOIN performance_reviews pr ON e.id = pr.employee_id AND YEAR(pr.review_date) = ?
                 GROUP BY d.id, d.name
                 ORDER BY average_rating DESC",
                [$year]
            );
            
            // Rating distribution
            $ratingDistribution = $this->db->select(
                "SELECT 
                    FLOOR(overall_rating) as rating,
                    COUNT(*) as count
                 FROM performance_reviews 
                 WHERE YEAR(review_date) = ?
                 GROUP BY FLOOR(overall_rating)
                 ORDER BY rating",
                [$year]
            );
            
            // Monthly review trends
            $monthlyTrends = $this->db->select(
                "SELECT 
                    MONTH(review_date) as month,
                    COUNT(*) as reviews_count,
                    AVG(overall_rating) as average_rating
                 FROM performance_reviews 
                 WHERE YEAR(review_date) = ?
                 GROUP BY MONTH(review_date)
                 ORDER BY month",
                [$year]
            );
            
            $analytics = [
                'overall_statistics' => [
                    'total_reviews' => (int)$overallStats['total_reviews'],
                    'average_rating' => round((float)$overallStats['average_rating'], 2),
                    'completed_reviews' => (int)$overallStats['completed_reviews'],
                    'pending_reviews' => (int)$overallStats['pending_reviews']
                ],
                'department_performance' => $departmentStats,
                'rating_distribution' => $ratingDistribution,
                'monthly_trends' => $monthlyTrends,
                'year' => $year
            ];
            
            Response::success($analytics);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch performance analytics: ' . $e->getMessage());
        }
    }
    
    // Helper methods
    
    private function getReviewGoals($reviewId) {
        try {
            $this->createGoalsTable();
            
            $query = "SELECT * FROM employee_goals 
                     WHERE employee_id = (SELECT employee_id FROM performance_reviews WHERE id = ?)
                     AND YEAR(target_date) = YEAR((SELECT review_date FROM performance_reviews WHERE id = ?))
                     ORDER BY target_date";
            
            return $this->db->select($query, [$reviewId, $reviewId]);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function get360Feedback($reviewId) {
        try {
            // Create 360 feedback table if not exists
            $this->create360FeedbackTable();
            
            $query = "SELECT f.*, e.first_name, e.last_name, e.employee_id as emp_id
                     FROM performance_360_feedback f
                     JOIN employees e ON f.feedback_provider_id = e.id
                     WHERE f.review_id = ?
                     ORDER BY f.created_at";
            
            return $this->db->select($query, [$reviewId]);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function createGoalsTable() {
        $query = "CREATE TABLE IF NOT EXISTS employee_goals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            target_date DATE NOT NULL,
            weight DECIMAL(5,2) DEFAULT 0,
            kpi_metric VARCHAR(100),
            target_value VARCHAR(100),
            progress_percentage DECIMAL(5,2) DEFAULT 0,
            status ENUM('not_started', 'in_progress', 'completed', 'cancelled') DEFAULT 'not_started',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )";
        
        $this->db->execute($query);
    }
    
    private function create360FeedbackTable() {
        $query = "CREATE TABLE IF NOT EXISTS performance_360_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            feedback_provider_id INT NOT NULL,
            relationship_type ENUM('peer', 'subordinate', 'supervisor', 'client') NOT NULL,
            communication_rating DECIMAL(3,2),
            teamwork_rating DECIMAL(3,2),
            leadership_rating DECIMAL(3,2),
            technical_skills_rating DECIMAL(3,2),
            overall_rating DECIMAL(3,2),
            strengths TEXT,
            areas_for_improvement TEXT,
            comments TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (review_id) REFERENCES performance_reviews(id) ON DELETE CASCADE,
            FOREIGN KEY (feedback_provider_id) REFERENCES employees(id) ON DELETE CASCADE
        )";
        
        $this->db->execute($query);
    }
}

?>