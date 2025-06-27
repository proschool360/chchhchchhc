<?php
/**
 * Training Controller
 * Handles training and development operations including modules, enrollments, and certifications
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class TrainingController {
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
     * Get training programs with pagination
     */
    public function getPrograms() {
        try {
            $params = Router::getQueryParams();
            
            // Pagination
            $page = max(1, (int)($params['page'] ?? 1));
            $perPage = min(100, max(10, (int)($params['per_page'] ?? 20)));
            $offset = ($page - 1) * $perPage;
            
            // Filters
            $filters = [];
            $filterValues = [];
            
            if (!empty($params['category'])) {
                $filters[] = "tp.category = ?";
                $filterValues[] = $params['category'];
            }
            
            if (!empty($params['status'])) {
                $filters[] = "tp.status = ?";
                $filterValues[] = $params['status'];
            }
            
            if (!empty($params['level'])) {
                $filters[] = "tp.level = ?";
                $filterValues[] = $params['level'];
            }
            
            if (!empty($params['search'])) {
                $filters[] = "(tp.title LIKE ? OR tp.description LIKE ?)";
                $searchTerm = '%' . $params['search'] . '%';
                $filterValues[] = $searchTerm;
                $filterValues[] = $searchTerm;
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Create training tables if not exist
            $this->createTrainingTables();
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM training_programs tp $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get training programs
            $query = "SELECT tp.*, 
                            COUNT(te.id) as enrollment_count,
                            COUNT(CASE WHEN te.status = 'completed' THEN 1 END) as completion_count
                     FROM training_programs tp
                     LEFT JOIN training_enrollments te ON tp.id = te.program_id
                     $whereClause
                     GROUP BY tp.id
                     ORDER BY tp.created_at DESC
                     LIMIT ? OFFSET ?";
            
            $programs = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($programs as &$program) {
                $program['enrollment_count'] = (int)$program['enrollment_count'];
                $program['completion_count'] = (int)$program['completion_count'];
                $program['completion_rate'] = $program['enrollment_count'] > 0 ? 
                    round(($program['completion_count'] / $program['enrollment_count']) * 100, 2) : 0;
            }
            
            Response::paginated($programs, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch training programs: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single training program
     */
    public function showProgram($id) {
        try {
            $this->createTrainingTables();
            
            $query = "SELECT tp.*, 
                            COUNT(te.id) as enrollment_count,
                            COUNT(CASE WHEN te.status = 'completed' THEN 1 END) as completion_count
                     FROM training_programs tp
                     LEFT JOIN training_enrollments te ON tp.id = te.program_id
                     WHERE tp.id = ?
                     GROUP BY tp.id";
            
            $program = $this->db->selectOne($query, [$id]);
            
            if (!$program) {
                Response::notFound('Training program not found');
                return;
            }
            
            // Format data
            $program['enrollment_count'] = (int)$program['enrollment_count'];
            $program['completion_count'] = (int)$program['completion_count'];
            $program['completion_rate'] = $program['enrollment_count'] > 0 ? 
                round(($program['completion_count'] / $program['enrollment_count']) * 100, 2) : 0;
            
            // Get modules for this program
            $modules = $this->db->select(
                "SELECT * FROM training_modules WHERE program_id = ? ORDER BY sequence_order",
                [$id]
            );
            $program['modules'] = $modules;
            
            // Check if current user is enrolled
            if ($this->user['role'] === 'employee') {
                $enrollment = $this->db->selectOne(
                    "SELECT * FROM training_enrollments WHERE program_id = ? AND employee_id = ?",
                    [$id, $this->user['employee_id']]
                );
                $program['user_enrollment'] = $enrollment;
            }
            
            Response::success($program);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch training program: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new training program
     */
    public function storeProgram() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'title' => 'required|string|max:200',
                'description' => 'required|string',
                'category' => 'required|in:technical,soft_skills,compliance,leadership,safety,orientation',
                'level' => 'required|in:beginner,intermediate,advanced',
                'duration_hours' => 'required|numeric|min:0.5|max:1000',
                'max_participants' => 'integer|min:1',
                'prerequisites' => 'string',
                'learning_objectives' => 'string',
                'certification_available' => 'boolean',
                'status' => 'in:draft,published,archived'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            $this->createTrainingTables();
            
            // Create training program
            $query = "INSERT INTO training_programs 
                     (title, description, category, level, duration_hours, max_participants, 
                      prerequisites, learning_objectives, certification_available, status, 
                      created_by, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $programId = $this->db->insert($query, [
                $data['title'],
                $data['description'],
                $data['category'],
                $data['level'],
                $data['duration_hours'],
                $data['max_participants'] ?? null,
                $data['prerequisites'] ?? null,
                $data['learning_objectives'] ?? null,
                $data['certification_available'] ?? false,
                $data['status'] ?? 'draft',
                $this->user['employee_id']
            ]);
            
            if ($programId) {
                $program = $this->db->selectOne(
                    "SELECT * FROM training_programs WHERE id = ?",
                    [$programId]
                );
                
                Response::created($program, 'Training program created successfully');
            } else {
                Response::serverError('Failed to create training program');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to create training program: ' . $e->getMessage());
        }
    }
    
    /**
     * Update training program
     */
    public function updateProgram($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            $this->createTrainingTables();
            
            // Check if program exists
            $program = $this->db->selectOne(
                "SELECT * FROM training_programs WHERE id = ?",
                [$id]
            );
            
            if (!$program) {
                Response::notFound('Training program not found');
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'title' => 'string|max:200',
                'description' => 'string',
                'category' => 'in:technical,soft_skills,compliance,leadership,safety,orientation',
                'level' => 'in:beginner,intermediate,advanced',
                'duration_hours' => 'numeric|min:0.5|max:1000',
                'max_participants' => 'integer|min:1',
                'prerequisites' => 'string',
                'learning_objectives' => 'string',
                'certification_available' => 'boolean',
                'status' => 'in:draft,published,archived'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Prepare update data
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = [
                'title', 'description', 'category', 'level', 'duration_hours',
                'max_participants', 'prerequisites', 'learning_objectives',
                'certification_available', 'status'
            ];
            
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
            
            // Update program
            $query = "UPDATE training_programs SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $result = $this->db->update($query, $updateValues);
            
            if ($result) {
                $updatedProgram = $this->db->selectOne(
                    "SELECT * FROM training_programs WHERE id = ?",
                    [$id]
                );
                
                Response::updated($updatedProgram, 'Training program updated successfully');
            } else {
                Response::serverError('Failed to update training program');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update training program: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete training program
     */
    public function deleteProgram($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $this->createTrainingTables();
            
            // Check if program exists
            $program = $this->db->selectOne(
                "SELECT * FROM training_programs WHERE id = ?",
                [$id]
            );
            
            if (!$program) {
                Response::notFound('Training program not found');
                return;
            }
            
            // Check if there are enrollments
            $enrollmentCount = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM training_enrollments WHERE program_id = ?",
                [$id]
            );
            
            if ($enrollmentCount['count'] > 0) {
                Response::error('Cannot delete training program with existing enrollments', 400);
                return;
            }
            
            // Delete program
            $result = $this->db->delete("DELETE FROM training_programs WHERE id = ?", [$id]);
            
            if ($result) {
                Response::deleted('Training program deleted successfully');
            } else {
                Response::serverError('Failed to delete training program');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to delete training program: ' . $e->getMessage());
        }
    }
    
    /**
     * Enroll in training program
     */
    public function enrollProgram() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'program_id' => 'required|integer',
                'employee_id' => 'integer'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            $this->createTrainingTables();
            
            // Determine employee ID
            $employeeId = $data['employee_id'] ?? $this->user['employee_id'];
            
            // Check permissions
            if ($this->user['role'] === 'employee' && $employeeId != $this->user['employee_id']) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Validate program
            $program = $this->db->selectOne(
                "SELECT * FROM training_programs WHERE id = ? AND status = 'published'",
                [$data['program_id']]
            );
            
            if (!$program) {
                Response::error('Training program not found or not available', 400);
                return;
            }
            
            // Check if already enrolled
            $existing = $this->db->selectOne(
                "SELECT id FROM training_enrollments WHERE program_id = ? AND employee_id = ?",
                [$data['program_id'], $employeeId]
            );
            
            if ($existing) {
                Response::error('Already enrolled in this program', 400);
                return;
            }
            
            // Check max participants limit
            if ($program['max_participants']) {
                $currentEnrollments = $this->db->selectOne(
                    "SELECT COUNT(*) as count FROM training_enrollments WHERE program_id = ?",
                    [$data['program_id']]
                );
                
                if ($currentEnrollments['count'] >= $program['max_participants']) {
                    Response::error('Training program is full', 400);
                    return;
                }
            }
            
            // Create enrollment
            $query = "INSERT INTO training_enrollments 
                     (program_id, employee_id, enrolled_at, status) 
                     VALUES (?, ?, NOW(), 'enrolled')";
            
            $enrollmentId = $this->db->insert($query, [
                $data['program_id'],
                $employeeId
            ]);
            
            if ($enrollmentId) {
                $enrollment = $this->db->selectOne(
                    "SELECT te.*, tp.title as program_title, e.first_name, e.last_name
                     FROM training_enrollments te
                     JOIN training_programs tp ON te.program_id = tp.id
                     JOIN employees e ON te.employee_id = e.id
                     WHERE te.id = ?",
                    [$enrollmentId]
                );
                
                Response::created($enrollment, 'Enrolled in training program successfully');
            } else {
                Response::serverError('Failed to enroll in training program');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to enroll in training program: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user enrollments
     */
    public function getEnrollments() {
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
                $filters[] = "te.employee_id = ?";
                $filterValues[] = $this->user['employee_id'];
            } elseif ($this->user['role'] === 'manager') {
                $filters[] = "e.department_id IN (SELECT id FROM departments WHERE manager_id = ?)";
                $filterValues[] = $this->user['employee_id'];
            }
            
            if (!empty($params['employee_id'])) {
                $filters[] = "te.employee_id = ?";
                $filterValues[] = $params['employee_id'];
            }
            
            if (!empty($params['status'])) {
                $filters[] = "te.status = ?";
                $filterValues[] = $params['status'];
            }
            
            if (!empty($params['program_id'])) {
                $filters[] = "te.program_id = ?";
                $filterValues[] = $params['program_id'];
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            $this->createTrainingTables();
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total 
                          FROM training_enrollments te
                          JOIN employees e ON te.employee_id = e.id
                          $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get enrollments
            $query = "SELECT te.*, 
                            tp.title as program_title, tp.category, tp.level, tp.duration_hours,
                            e.first_name, e.last_name, e.employee_id as emp_id,
                            d.name as department_name
                     FROM training_enrollments te
                     JOIN training_programs tp ON te.program_id = tp.id
                     JOIN employees e ON te.employee_id = e.id
                     LEFT JOIN departments d ON e.department_id = d.id
                     $whereClause
                     ORDER BY te.enrolled_at DESC
                     LIMIT ? OFFSET ?";
            
            $enrollments = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($enrollments as &$enrollment) {
                $enrollment['employee_name'] = $enrollment['first_name'] . ' ' . $enrollment['last_name'];
                $enrollment['progress_percentage'] = (float)$enrollment['progress_percentage'];
                
                // Remove individual name fields
                unset($enrollment['first_name'], $enrollment['last_name']);
            }
            
            Response::paginated($enrollments, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch enrollments: ' . $e->getMessage());
        }
    }
    
    /**
     * Update enrollment progress
     */
    public function updateProgress($enrollmentId) {
        try {
            $data = Router::getRequestData();
            
            $this->createTrainingTables();
            
            // Check if enrollment exists
            $enrollment = $this->db->selectOne(
                "SELECT * FROM training_enrollments WHERE id = ?",
                [$enrollmentId]
            );
            
            if (!$enrollment) {
                Response::notFound('Enrollment not found');
                return;
            }
            
            // Check permissions
            if ($this->user['role'] === 'employee' && 
                $enrollment['employee_id'] != $this->user['employee_id']) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'progress_percentage' => 'required|numeric|min:0|max:100',
                'status' => 'in:enrolled,in_progress,completed,dropped',
                'feedback' => 'string',
                'rating' => 'numeric|min:1|max:5'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Auto-update status based on progress
            $status = $data['status'] ?? $enrollment['status'];
            if ($data['progress_percentage'] == 100) {
                $status = 'completed';
            } elseif ($data['progress_percentage'] > 0) {
                $status = 'in_progress';
            }
            
            // Update enrollment
            $query = "UPDATE training_enrollments 
                     SET progress_percentage = ?, status = ?, feedback = ?, rating = ?, 
                         completed_at = CASE WHEN ? = 'completed' AND completed_at IS NULL THEN NOW() ELSE completed_at END,
                         updated_at = NOW()
                     WHERE id = ?";
            
            $result = $this->db->update($query, [
                $data['progress_percentage'],
                $status,
                $data['feedback'] ?? null,
                $data['rating'] ?? null,
                $status,
                $enrollmentId
            ]);
            
            if ($result) {
                $updatedEnrollment = $this->db->selectOne(
                    "SELECT te.*, tp.title as program_title, e.first_name, e.last_name
                     FROM training_enrollments te
                     JOIN training_programs tp ON te.program_id = tp.id
                     JOIN employees e ON te.employee_id = e.id
                     WHERE te.id = ?",
                    [$enrollmentId]
                );
                
                Response::updated($updatedEnrollment, 'Training progress updated successfully');
            } else {
                Response::serverError('Failed to update training progress');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update training progress: ' . $e->getMessage());
        }
    }
    
    /**
     * Get skill gap analysis
     */
    public function getSkillGapAnalysis() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $params = Router::getQueryParams();
            $departmentId = $params['department_id'] ?? null;
            
            $this->createSkillsTable();
            
            // Get skill requirements by position
            $skillRequirements = $this->db->select(
                "SELECT p.title as position_title, p.id as position_id,
                        sr.skill_name, sr.required_level,
                        COUNT(e.id) as employee_count
                 FROM positions p
                 LEFT JOIN skill_requirements sr ON p.id = sr.position_id
                 LEFT JOIN employees e ON p.id = e.position_id AND e.status = 'active'
                 " . ($departmentId ? "WHERE p.department_id = ?" : "") . "
                 GROUP BY p.id, sr.skill_name
                 ORDER BY p.title, sr.skill_name",
                $departmentId ? [$departmentId] : []
            );
            
            // Get current employee skills
            $employeeSkills = $this->db->select(
                "SELECT e.id as employee_id, e.first_name, e.last_name, e.position_id,
                        es.skill_name, es.current_level
                 FROM employees e
                 LEFT JOIN employee_skills es ON e.id = es.employee_id
                 WHERE e.status = 'active'
                 " . ($departmentId ? "AND e.department_id = ?" : "") . "
                 ORDER BY e.first_name, e.last_name, es.skill_name",
                $departmentId ? [$departmentId] : []
            );
            
            // Calculate skill gaps
            $skillGaps = [];
            $skillLevels = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];
            
            foreach ($skillRequirements as $requirement) {
                if (!$requirement['skill_name']) continue;
                
                $positionId = $requirement['position_id'];
                $skillName = $requirement['skill_name'];
                $requiredLevel = $skillLevels[$requirement['required_level']] ?? 1;
                
                // Find employees in this position
                $positionEmployees = array_filter($employeeSkills, function($emp) use ($positionId) {
                    return $emp['position_id'] == $positionId;
                });
                
                $gapCount = 0;
                $totalEmployees = 0;
                
                foreach ($positionEmployees as $employee) {
                    $totalEmployees++;
                    $currentLevel = 0;
                    
                    // Find employee's current skill level
                    $employeeSkill = array_filter($employeeSkills, function($skill) use ($employee, $skillName) {
                        return $skill['employee_id'] == $employee['employee_id'] && 
                               $skill['skill_name'] == $skillName;
                    });
                    
                    if (!empty($employeeSkill)) {
                        $currentLevel = $skillLevels[reset($employeeSkill)['current_level']] ?? 0;
                    }
                    
                    if ($currentLevel < $requiredLevel) {
                        $gapCount++;
                    }
                }
                
                if ($totalEmployees > 0) {
                    $skillGaps[] = [
                        'position_title' => $requirement['position_title'],
                        'skill_name' => $skillName,
                        'required_level' => $requirement['required_level'],
                        'total_employees' => $totalEmployees,
                        'employees_with_gap' => $gapCount,
                        'gap_percentage' => round(($gapCount / $totalEmployees) * 100, 2)
                    ];
                }
            }
            
            // Get recommended training programs
            $recommendedTraining = $this->getRecommendedTraining($skillGaps);
            
            $analysis = [
                'skill_gaps' => $skillGaps,
                'recommended_training' => $recommendedTraining,
                'summary' => [
                    'total_skills_analyzed' => count($skillGaps),
                    'critical_gaps' => count(array_filter($skillGaps, function($gap) {
                        return $gap['gap_percentage'] > 50;
                    })),
                    'moderate_gaps' => count(array_filter($skillGaps, function($gap) {
                        return $gap['gap_percentage'] > 25 && $gap['gap_percentage'] <= 50;
                    }))
                ]
            ];
            
            Response::success($analysis);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch skill gap analysis: ' . $e->getMessage());
        }
    }
    
    /**
     * Get training analytics
     */
    public function getAnalytics() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $params = Router::getQueryParams();
            $year = $params['year'] ?? date('Y');
            
            $this->createTrainingTables();
            
            // Overall training statistics
            $overallStats = $this->db->selectOne(
                "SELECT 
                    COUNT(DISTINCT tp.id) as total_programs,
                    COUNT(DISTINCT te.id) as total_enrollments,
                    COUNT(CASE WHEN te.status = 'completed' THEN 1 END) as completed_enrollments,
                    AVG(te.progress_percentage) as average_progress,
                    AVG(te.rating) as average_rating
                 FROM training_programs tp
                 LEFT JOIN training_enrollments te ON tp.id = te.program_id
                 WHERE YEAR(tp.created_at) = ?",
                [$year]
            );
            
            // Category-wise statistics
            $categoryStats = $this->db->select(
                "SELECT tp.category,
                        COUNT(DISTINCT tp.id) as program_count,
                        COUNT(te.id) as enrollment_count,
                        COUNT(CASE WHEN te.status = 'completed' THEN 1 END) as completion_count,
                        AVG(te.rating) as average_rating
                 FROM training_programs tp
                 LEFT JOIN training_enrollments te ON tp.id = te.program_id
                 WHERE YEAR(tp.created_at) = ?
                 GROUP BY tp.category
                 ORDER BY enrollment_count DESC",
                [$year]
            );
            
            // Monthly enrollment trends
            $monthlyTrends = $this->db->select(
                "SELECT 
                    MONTH(te.enrolled_at) as month,
                    COUNT(te.id) as enrollments,
                    COUNT(CASE WHEN te.status = 'completed' THEN 1 END) as completions
                 FROM training_enrollments te
                 WHERE YEAR(te.enrolled_at) = ?
                 GROUP BY MONTH(te.enrolled_at)
                 ORDER BY month",
                [$year]
            );
            
            // Department-wise training participation
            $departmentStats = $this->db->select(
                "SELECT d.name as department_name,
                        COUNT(DISTINCT te.id) as enrollments,
                        COUNT(CASE WHEN te.status = 'completed' THEN 1 END) as completions,
                        AVG(te.progress_percentage) as average_progress
                 FROM departments d
                 LEFT JOIN employees e ON d.id = e.department_id
                 LEFT JOIN training_enrollments te ON e.id = te.employee_id AND YEAR(te.enrolled_at) = ?
                 GROUP BY d.id, d.name
                 ORDER BY enrollments DESC",
                [$year]
            );
            
            $analytics = [
                'overall_statistics' => [
                    'total_programs' => (int)$overallStats['total_programs'],
                    'total_enrollments' => (int)$overallStats['total_enrollments'],
                    'completed_enrollments' => (int)$overallStats['completed_enrollments'],
                    'completion_rate' => $overallStats['total_enrollments'] > 0 ? 
                        round(($overallStats['completed_enrollments'] / $overallStats['total_enrollments']) * 100, 2) : 0,
                    'average_progress' => round((float)$overallStats['average_progress'], 2),
                    'average_rating' => round((float)$overallStats['average_rating'], 2)
                ],
                'category_statistics' => $categoryStats,
                'monthly_trends' => $monthlyTrends,
                'department_statistics' => $departmentStats,
                'year' => $year
            ];
            
            Response::success($analytics);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch training analytics: ' . $e->getMessage());
        }
    }
    
    // Helper methods
    
    private function getRecommendedTraining($skillGaps) {
        try {
            $recommendations = [];
            
            foreach ($skillGaps as $gap) {
                if ($gap['gap_percentage'] > 25) { // Only recommend for significant gaps
                    $programs = $this->db->select(
                        "SELECT tp.id, tp.title, tp.category, tp.level, tp.duration_hours
                         FROM training_programs tp
                         WHERE tp.status = 'published' 
                         AND (tp.title LIKE ? OR tp.description LIKE ? OR tp.learning_objectives LIKE ?)
                         ORDER BY tp.level, tp.duration_hours
                         LIMIT 3",
                        [
                            '%' . $gap['skill_name'] . '%',
                            '%' . $gap['skill_name'] . '%',
                            '%' . $gap['skill_name'] . '%'
                        ]
                    );
                    
                    if (!empty($programs)) {
                        $recommendations[] = [
                            'skill_gap' => $gap,
                            'recommended_programs' => $programs
                        ];
                    }
                }
            }
            
            return $recommendations;
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function createTrainingTables() {
        // Training programs table
        $query1 = "CREATE TABLE IF NOT EXISTS training_programs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            category ENUM('technical', 'soft_skills', 'compliance', 'leadership', 'safety', 'orientation') NOT NULL,
            level ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
            duration_hours DECIMAL(5,2) NOT NULL,
            max_participants INT,
            prerequisites TEXT,
            learning_objectives TEXT,
            certification_available BOOLEAN DEFAULT FALSE,
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES employees(id) ON DELETE SET NULL
        )";
        
        // Training modules table
        $query2 = "CREATE TABLE IF NOT EXISTS training_modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            program_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            content TEXT,
            sequence_order INT NOT NULL,
            duration_minutes INT,
            module_type ENUM('video', 'document', 'quiz', 'assignment', 'discussion') DEFAULT 'document',
            is_mandatory BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE
        )";
        
        // Training enrollments table
        $query3 = "CREATE TABLE IF NOT EXISTS training_enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            program_id INT NOT NULL,
            employee_id INT NOT NULL,
            enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            progress_percentage DECIMAL(5,2) DEFAULT 0,
            status ENUM('enrolled', 'in_progress', 'completed', 'dropped') DEFAULT 'enrolled',
            feedback TEXT,
            rating DECIMAL(3,2),
            certificate_issued BOOLEAN DEFAULT FALSE,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            UNIQUE KEY unique_enrollment (program_id, employee_id)
        )";
        
        $this->db->execute($query1);
        $this->db->execute($query2);
        $this->db->execute($query3);
    }
    
    private function createSkillsTable() {
        // Skill requirements table
        $query1 = "CREATE TABLE IF NOT EXISTS skill_requirements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            position_id INT NOT NULL,
            skill_name VARCHAR(100) NOT NULL,
            required_level ENUM('beginner', 'intermediate', 'advanced', 'expert') NOT NULL,
            is_mandatory BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
        )";
        
        // Employee skills table
        $query2 = "CREATE TABLE IF NOT EXISTS employee_skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            skill_name VARCHAR(100) NOT NULL,
            current_level ENUM('beginner', 'intermediate', 'advanced', 'expert') NOT NULL,
            certified BOOLEAN DEFAULT FALSE,
            certification_date DATE,
            last_assessed_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            UNIQUE KEY unique_employee_skill (employee_id, skill_name)
        )";
        
        $this->db->execute($query1);
        $this->db->execute($query2);
    }
}

?>