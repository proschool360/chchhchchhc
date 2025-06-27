<?php
/**
 * Position Controller
 * Handles position management operations
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class PositionController {
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
     * Get all positions with pagination
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
            
            if (!empty($params['status'])) {
                $filters[] = "p.status = ?";
                $filterValues[] = $params['status'];
            }
            
            if (!empty($params['department_id'])) {
                $filters[] = "p.department_id = ?";
                $filterValues[] = $params['department_id'];
            }
            
            if (!empty($params['search'])) {
                $filters[] = "(p.title LIKE ? OR p.description LIKE ?)";
                $searchTerm = '%' . $params['search'] . '%';
                $filterValues[] = $searchTerm;
                $filterValues[] = $searchTerm;
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM positions p $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get positions with employee count and department info
            $query = "SELECT p.id, p.title, p.description, p.department_id, p.min_salary, p.max_salary, p.status, p.created_at,
                            COUNT(e.id) as employee_count,
                            d.name as department_name
                     FROM positions p
                     LEFT JOIN employees e ON p.id = e.position_id AND e.status = 'active'
                     LEFT JOIN departments d ON p.department_id = d.id
                     $whereClause
                     GROUP BY p.id
                     ORDER BY p.title
                     LIMIT ? OFFSET ?";
            
            $positions = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($positions as &$position) {
                $position['employee_count'] = (int)$position['employee_count'];
                $position['min_salary'] = $position['min_salary'] ? (float)$position['min_salary'] : null;
                $position['max_salary'] = $position['max_salary'] ? (float)$position['max_salary'] : null;
            }
            
            Response::paginated($positions, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch positions: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single position by ID
     */
    public function show($id) {
        try {
            $position = $this->db->selectOne(
                "SELECT p.*, d.name as department_name
                 FROM positions p
                 LEFT JOIN departments d ON p.department_id = d.id
                 WHERE p.id = ?",
                [$id]
            );
            
            if (!$position) {
                Response::notFound('Position not found');
                return;
            }
            
            // Get employee count
            $employeeCount = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM employees WHERE position_id = ? AND status = 'active'",
                [$id]
            );
            
            $position['employee_count'] = (int)$employeeCount['count'];
            $position['min_salary'] = $position['min_salary'] ? (float)$position['min_salary'] : null;
            $position['max_salary'] = $position['max_salary'] ? (float)$position['max_salary'] : null;
            
            Response::success($position);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch position: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new position
     */
    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validation rules
            $rules = [
                'title' => 'required|string|max:100',
                'description' => 'string',
                'department_id' => 'required|integer',
                'min_salary' => 'numeric|min:0',
                'max_salary' => 'numeric|min:0',
                'status' => 'string|in:active,inactive'
            ];
            
            $validator = new Validator();
            if (!$validator->validate($data, $rules)) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Check if department exists
            $department = $this->db->selectOne(
                "SELECT id FROM departments WHERE id = ?",
                [$data['department_id']]
            );
            
            if (!$department) {
                Response::error('Department not found', 400);
                return;
            }
            
            // Check if position title already exists in department
            $existing = $this->db->selectOne(
                "SELECT id FROM positions WHERE title = ? AND department_id = ?",
                [$data['title'], $data['department_id']]
            );
            
            if ($existing) {
                Response::error('Position title already exists in this department', 400);
                return;
            }
            
            // Validate salary range
            if (isset($data['min_salary']) && isset($data['max_salary']) && 
                $data['min_salary'] > $data['max_salary']) {
                Response::error('Minimum salary cannot be greater than maximum salary', 400);
                return;
            }
            
            // Create position
            $query = "INSERT INTO positions (title, description, department_id, min_salary, max_salary, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $positionId = $this->db->insert($query, [
                $data['title'],
                $data['description'] ?? null,
                $data['department_id'],
                $data['min_salary'] ?? null,
                $data['max_salary'] ?? null,
                $data['status'] ?? 'active'
            ]);
            
            if ($positionId) {
                $position = $this->db->selectOne(
                    "SELECT p.*, d.name as department_name
                     FROM positions p
                     LEFT JOIN departments d ON p.department_id = d.id
                     WHERE p.id = ?",
                    [$positionId]
                );
                
                $position['employee_count'] = 0;
                $position['min_salary'] = $position['min_salary'] ? (float)$position['min_salary'] : null;
                $position['max_salary'] = $position['max_salary'] ? (float)$position['max_salary'] : null;
                
                Response::created($position, 'Position created successfully');
            } else {
                Response::serverError('Failed to create position');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to create position: ' . $e->getMessage());
        }
    }
    
    /**
     * Update position
     */
    public function update($id) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if position exists
            $position = $this->db->selectOne(
                "SELECT * FROM positions WHERE id = ?",
                [$id]
            );
            
            if (!$position) {
                Response::notFound('Position not found');
                return;
            }
            
            // Validation rules
            $rules = [
                'title' => 'string|max:100',
                'description' => 'string',
                'department_id' => 'integer',
                'min_salary' => 'numeric|min:0',
                'max_salary' => 'numeric|min:0',
                'status' => 'string|in:active,inactive'
            ];
            
            $validator = new Validator();
            if (!$validator->validate($data, $rules)) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Check if department exists (if provided)
            if (isset($data['department_id'])) {
                $department = $this->db->selectOne(
                    "SELECT id FROM departments WHERE id = ?",
                    [$data['department_id']]
                );
                
                if (!$department) {
                    Response::error('Department not found', 400);
                    return;
                }
            }
            
            // Check if position title already exists in department (excluding current)
            if (isset($data['title'])) {
                $title = $data['title'];
                $departmentId = $data['department_id'] ?? $position['department_id'];
                
                $existing = $this->db->selectOne(
                    "SELECT id FROM positions WHERE title = ? AND department_id = ? AND id != ?",
                    [$title, $departmentId, $id]
                );
                
                if ($existing) {
                    Response::error('Position title already exists in this department', 400);
                    return;
                }
            }
            
            // Validate salary range
            $minSalary = $data['min_salary'] ?? $position['min_salary'];
            $maxSalary = $data['max_salary'] ?? $position['max_salary'];
            
            if ($minSalary && $maxSalary && $minSalary > $maxSalary) {
                Response::error('Minimum salary cannot be greater than maximum salary', 400);
                return;
            }
            
            // Build update query
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = ['title', 'description', 'department_id', 'min_salary', 'max_salary', 'status'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
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
            
            // Update position
            $query = "UPDATE positions SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $result = $this->db->update($query, $updateValues);
            
            if ($result) {
                $updatedPosition = $this->db->selectOne(
                    "SELECT p.*, d.name as department_name
                     FROM positions p
                     LEFT JOIN departments d ON p.department_id = d.id
                     WHERE p.id = ?",
                    [$id]
                );
                
                // Get employee count
                $employeeCount = $this->db->selectOne(
                    "SELECT COUNT(*) as count FROM employees WHERE position_id = ? AND status = 'active'",
                    [$id]
                );
                
                $updatedPosition['employee_count'] = (int)$employeeCount['count'];
                $updatedPosition['min_salary'] = $updatedPosition['min_salary'] ? (float)$updatedPosition['min_salary'] : null;
                $updatedPosition['max_salary'] = $updatedPosition['max_salary'] ? (float)$updatedPosition['max_salary'] : null;
                
                Response::updated($updatedPosition, 'Position updated successfully');
            } else {
                Response::serverError('Failed to update position');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update position: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete position
     */
    public function delete($id) {
        try {
            // Check if position exists
            $position = $this->db->selectOne(
                "SELECT * FROM positions WHERE id = ?",
                [$id]
            );
            
            if (!$position) {
                Response::notFound('Position not found');
                return;
            }
            
            // Check if position has employees
            $employeeCount = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM employees WHERE position_id = ? AND status = 'active'",
                [$id]
            );
            
            if ($employeeCount['count'] > 0) {
                Response::error('Cannot delete position with active employees', 400);
                return;
            }
            
            // Delete position
            $result = $this->db->delete("DELETE FROM positions WHERE id = ?", [$id]);
            
            if ($result) {
                Response::deleted('Position deleted successfully');
            } else {
                Response::serverError('Failed to delete position');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to delete position: ' . $e->getMessage());
        }
    }
}
?>