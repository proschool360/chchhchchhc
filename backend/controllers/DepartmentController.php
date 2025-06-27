<?php
/**
 * Department Controller
 * Handles department and position management operations
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class DepartmentController {
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
     * Get all departments with pagination
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
                $filters[] = "d.status = ?";
                $filterValues[] = $params['status'];
            }
            
            if (!empty($params['search'])) {
                $filters[] = "(d.name LIKE ? OR d.description LIKE ?)";
                $searchTerm = '%' . $params['search'] . '%';
                $filterValues[] = $searchTerm;
                $filterValues[] = $searchTerm;
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM departments d $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get departments with employee count
            $query = "SELECT d.id, d.name, d.description, d.manager_id, d.status, d.created_at,
                            COUNT(e.id) as employee_count,
                            m.first_name as manager_first_name,
                            m.last_name as manager_last_name
                     FROM departments d
                     LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
                     LEFT JOIN employees m ON d.manager_id = m.id
                     $whereClause
                     GROUP BY d.id
                     ORDER BY d.name
                     LIMIT ? OFFSET ?";
            
            $departments = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($departments as &$department) {
                $department['manager_name'] = $department['manager_first_name'] ? 
                    $department['manager_first_name'] . ' ' . $department['manager_last_name'] : null;
                $department['employee_count'] = (int)$department['employee_count'];
                
                // Remove individual name fields
                unset($department['manager_first_name'], $department['manager_last_name']);
            }
            
            Response::paginated($departments, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch departments: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single department
     */
    public function show($id) {
        try {
            $query = "SELECT d.*, 
                            m.first_name as manager_first_name,
                            m.last_name as manager_last_name,
                            m.employee_id as manager_employee_id
                     FROM departments d
                     LEFT JOIN employees m ON d.manager_id = m.id
                     WHERE d.id = ?";
            
            $department = $this->db->selectOne($query, [$id]);
            
            if (!$department) {
                Response::notFound('Department not found');
                return;
            }
            
            // Format data
            $department['manager_name'] = $department['manager_first_name'] ? 
                $department['manager_first_name'] . ' ' . $department['manager_last_name'] : null;
            
            // Get department statistics
            $stats = $this->getDepartmentStats($id);
            $department['statistics'] = $stats;
            
            // Get positions in this department
            $positions = $this->db->select(
                "SELECT id, title, description, status FROM positions WHERE department_id = ? ORDER BY title",
                [$id]
            );
            $department['positions'] = $positions;
            
            // Remove individual name fields
            unset($department['manager_first_name'], $department['manager_last_name']);
            
            Response::success($department);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch department: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new department
     */
    public function store() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'name' => 'required|string|max:100',
                'description' => 'string|max:500',
                'manager_id' => 'integer',
                'status' => 'in:active,inactive'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Check if department name already exists
            $existing = $this->db->selectOne(
                "SELECT id FROM departments WHERE name = ?",
                [$data['name']]
            );
            
            if ($existing) {
                Response::error('Department name already exists', 400);
                return;
            }
            
            // Validate manager if provided
            if (!empty($data['manager_id'])) {
                $manager = $this->db->selectOne(
                    "SELECT id FROM employees WHERE id = ? AND status = 'active'",
                    [$data['manager_id']]
                );
                
                if (!$manager) {
                    Response::error('Invalid manager selected', 400);
                    return;
                }
            }
            
            // Create department
            $query = "INSERT INTO departments (name, description, manager_id, status, created_at) 
                     VALUES (?, ?, ?, ?, NOW())";
            
            $departmentId = $this->db->insert($query, [
                $data['name'],
                $data['description'] ?? null,
                $data['manager_id'] ?? null,
                $data['status'] ?? 'active'
            ]);
            
            if ($departmentId) {
                $department = $this->db->selectOne(
                    "SELECT d.*, m.first_name as manager_first_name, m.last_name as manager_last_name
                     FROM departments d
                     LEFT JOIN employees m ON d.manager_id = m.id
                     WHERE d.id = ?",
                    [$departmentId]
                );
                
                Response::created($department, 'Department created successfully');
            } else {
                Response::serverError('Failed to create department');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to create department: ' . $e->getMessage());
        }
    }
    
    /**
     * Update department
     */
    public function update($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Check if department exists
            $department = $this->db->selectOne(
                "SELECT * FROM departments WHERE id = ?",
                [$id]
            );
            
            if (!$department) {
                Response::notFound('Department not found');
                return;
            }
            
            // Clean empty strings to null for optional fields
            if (isset($data['manager_id']) && $data['manager_id'] === '') {
                $data['manager_id'] = null;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'name' => 'string|max:100',
                'description' => 'string|max:500',
                'manager_id' => 'integer',
                'status' => 'in:active,inactive'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Check if department name already exists (excluding current)
            if (isset($data['name']) && $data['name'] !== $department['name']) {
                $existing = $this->db->selectOne(
                    "SELECT id FROM departments WHERE name = ? AND id != ?",
                    [$data['name'], $id]
                );
                
                if ($existing) {
                    Response::error('Department name already exists', 400);
                    return;
                }
            }
            
            // Validate manager if provided
            if (isset($data['manager_id']) && $data['manager_id']) {
                $manager = $this->db->selectOne(
                    "SELECT id FROM employees WHERE id = ? AND status = 'active'",
                    [$data['manager_id']]
                );
                
                if (!$manager) {
                    Response::error('Invalid manager selected', 400);
                    return;
                }
            }
            
            // Prepare update data
            $updateFields = [];
            $updateValues = [];
            
            if (isset($data['name'])) {
                $updateFields[] = "name = ?";
                $updateValues[] = $data['name'];
            }
            
            if (isset($data['description'])) {
                $updateFields[] = "description = ?";
                $updateValues[] = $data['description'];
            }
            
            if (isset($data['manager_id'])) {
                $updateFields[] = "manager_id = ?";
                $updateValues[] = $data['manager_id'] ?: null;
            }
            
            if (isset($data['status'])) {
                $updateFields[] = "status = ?";
                $updateValues[] = $data['status'];
            }
            
            if (empty($updateFields)) {
                Response::error('No valid fields to update', 400);
                return;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $updateValues[] = $id;
            
            // Update department
            $query = "UPDATE departments SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $result = $this->db->update($query, $updateValues);
            
            if ($result) {
                $updatedDepartment = $this->db->selectOne(
                    "SELECT d.*, m.first_name as manager_first_name, m.last_name as manager_last_name
                     FROM departments d
                     LEFT JOIN employees m ON d.manager_id = m.id
                     WHERE d.id = ?",
                    [$id]
                );
                
                Response::updated($updatedDepartment, 'Department updated successfully');
            } else {
                Response::serverError('Failed to update department');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update department: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete department
     */
    public function delete($id) {
        try {
            if (!in_array($this->user['role'], ['admin'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Check if department exists
            $department = $this->db->selectOne(
                "SELECT * FROM departments WHERE id = ?",
                [$id]
            );
            
            if (!$department) {
                Response::notFound('Department not found');
                return;
            }
            
            // Check if department has employees
            $employeeCount = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM employees WHERE department_id = ? AND status = 'active'",
                [$id]
            );
            
            if ($employeeCount['count'] > 0) {
                Response::error('Cannot delete department with active employees', 400);
                return;
            }
            
            // Delete department
            $result = $this->db->delete("DELETE FROM departments WHERE id = ?", [$id]);
            
            if ($result) {
                Response::deleted('Department deleted successfully');
            } else {
                Response::serverError('Failed to delete department');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to delete department: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all positions
     */
    public function getPositions() {
        try {
            $params = Router::getQueryParams();
            
            // Pagination
            $page = max(1, (int)($params['page'] ?? 1));
            $perPage = min(100, max(10, (int)($params['per_page'] ?? 20)));
            $offset = ($page - 1) * $perPage;
            
            // Filters
            $filters = [];
            $filterValues = [];
            
            if (!empty($params['department_id'])) {
                $filters[] = "p.department_id = ?";
                $filterValues[] = $params['department_id'];
            }
            
            if (!empty($params['status'])) {
                $filters[] = "p.status = ?";
                $filterValues[] = $params['status'];
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
            
            // Get positions
            $query = "SELECT p.id, p.title, p.description, p.department_id, p.status, p.created_at,
                            d.name as department_name,
                            COUNT(e.id) as employee_count
                     FROM positions p
                     LEFT JOIN departments d ON p.department_id = d.id
                     LEFT JOIN employees e ON p.id = e.position_id AND e.status = 'active'
                     $whereClause
                     GROUP BY p.id
                     ORDER BY d.name, p.title
                     LIMIT ? OFFSET ?";
            
            $positions = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($positions as &$position) {
                $position['employee_count'] = (int)$position['employee_count'];
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
     * Create new position
     */
    public function storePosition() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'title' => 'required|string|max:100',
                'description' => 'string|max:500',
                'department_id' => 'required|integer',
                'status' => 'in:active,inactive'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Validate department
            $department = $this->db->selectOne(
                "SELECT id FROM departments WHERE id = ? AND status = 'active'",
                [$data['department_id']]
            );
            
            if (!$department) {
                Response::error('Invalid department selected', 400);
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
            
            // Create position
            $query = "INSERT INTO positions (title, description, department_id, status, created_at) 
                     VALUES (?, ?, ?, ?, NOW())";
            
            $positionId = $this->db->insert($query, [
                $data['title'],
                $data['description'] ?? null,
                $data['department_id'],
                $data['status'] ?? 'active'
            ]);
            
            if ($positionId) {
                $position = $this->db->selectOne(
                    "SELECT p.*, d.name as department_name
                     FROM positions p
                     JOIN departments d ON p.department_id = d.id
                     WHERE p.id = ?",
                    [$positionId]
                );
                
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
    public function updatePosition($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Check if position exists
            $position = $this->db->selectOne(
                "SELECT * FROM positions WHERE id = ?",
                [$id]
            );
            
            if (!$position) {
                Response::notFound('Position not found');
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'title' => 'string|max:100',
                'description' => 'string|max:500',
                'department_id' => 'integer',
                'status' => 'in:active,inactive'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Validate department if provided
            if (isset($data['department_id'])) {
                $department = $this->db->selectOne(
                    "SELECT id FROM departments WHERE id = ? AND status = 'active'",
                    [$data['department_id']]
                );
                
                if (!$department) {
                    Response::error('Invalid department selected', 400);
                    return;
                }
            }
            
            // Check if position title already exists in department (excluding current)
            if (isset($data['title']) || isset($data['department_id'])) {
                $title = $data['title'] ?? $position['title'];
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
            
            // Prepare update data
            $updateFields = [];
            $updateValues = [];
            
            if (isset($data['title'])) {
                $updateFields[] = "title = ?";
                $updateValues[] = $data['title'];
            }
            
            if (isset($data['description'])) {
                $updateFields[] = "description = ?";
                $updateValues[] = $data['description'];
            }
            
            if (isset($data['department_id'])) {
                $updateFields[] = "department_id = ?";
                $updateValues[] = $data['department_id'];
            }
            
            if (isset($data['status'])) {
                $updateFields[] = "status = ?";
                $updateValues[] = $data['status'];
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
                     JOIN departments d ON p.department_id = d.id
                     WHERE p.id = ?",
                    [$id]
                );
                
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
    public function deletePosition($id) {
        try {
            if (!in_array($this->user['role'], ['admin'])) {
                Response::forbidden('Access denied');
                return;
            }
            
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
    
    // Helper methods
    
    /**
     * Get employees that can be managers
     */
    public function getManagers() {
        try {
            $query = "SELECT id, employee_id, first_name, last_name, 
                            CONCAT(first_name, ' ', last_name, 
                                CASE WHEN employee_id IS NOT NULL 
                                    THEN CONCAT(' (', employee_id, ')') 
                                    ELSE '' 
                                END
                            ) as full_name,
                            department_id, position_id
                     FROM employees 
                     WHERE status = 'active' 
                     ORDER BY first_name, last_name";
            
            $managers = $this->db->select($query);
            
            Response::success($managers);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch managers: ' . $e->getMessage());
        }
    }
    
    /**
     * Get department statistics
     */
    private function getDepartmentStats($departmentId) {
        try {
            $stats = [];
            
            // Employee count
            $employeeCount = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM employees WHERE department_id = ? AND status = 'active'",
                [$departmentId]
            );
            $stats['employee_count'] = (int)$employeeCount['count'];
            
            // Position count
            $positionCount = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM positions WHERE department_id = ? AND status = 'active'",
                [$departmentId]
            );
            $stats['position_count'] = (int)$positionCount['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            return [];
        }
    }
}

?>