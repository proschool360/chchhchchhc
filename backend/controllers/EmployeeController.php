<?php
/**
 * Employee Controller
 * Handles employee management operations
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class EmployeeController {
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
     * Get all employees with pagination and filtering
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
            
            if (!empty($params['department_id'])) {
                $filters[] = "e.department_id = ?";
                $filterValues[] = $params['department_id'];
            }
            
            if (!empty($params['position_id'])) {
                $filters[] = "e.position_id = ?";
                $filterValues[] = $params['position_id'];
            }
            
            if (!empty($params['status'])) {
                $filters[] = "e.status = ?";
                $filterValues[] = $params['status'];
            }
            
            if (!empty($params['search'])) {
                $filters[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ? OR e.email LIKE ?)";
                $searchTerm = '%' . $params['search'] . '%';
                $filterValues = array_merge($filterValues, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM employees e $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get employees
            $query = "SELECT e.id, e.employee_id, e.first_name, e.last_name, e.email, e.phone, 
                            e.date_of_birth, e.hire_date, e.salary, e.status, e.address,
                            d.name as department_name, p.title as position_title,
                            CASE WHEN e.manager_id IS NOT NULL THEN 
                                CONCAT(m.first_name, ' ', m.last_name) 
                            ELSE NULL END as manager_name,
                            e.created_at, e.updated_at
                     FROM employees e
                     LEFT JOIN departments d ON e.department_id = d.id
                     LEFT JOIN positions p ON e.position_id = p.id
                     LEFT JOIN employees m ON e.manager_id = m.id
                     $whereClause
                     ORDER BY e.created_at DESC
                     LIMIT ? OFFSET ?";
            
            $employees = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($employees as &$employee) {
                $employee['full_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
                $employee['salary'] = $employee['salary'] ? (float)$employee['salary'] : null;
            }
            
            Response::paginated($employees, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch employees: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single employee by ID
     */
    public function show($id) {
        try {
            $employee = $this->getEmployeeById($id);
            
            if (!$employee) {
                Response::notFound('Employee not found');
                return;
            }
            
            // Check access permissions
            if (!$this->canAccessEmployee($employee['id'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Get additional employee details
            $employee['documents'] = $this->getEmployeeDocuments($id);
            $employee['leave_balance'] = $this->getLeaveBalance($id);
            $employee['recent_attendance'] = $this->getRecentAttendance($id, 7);
            
            Response::success($employee);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch employee: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new employee
     */
    public function store() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'email' => 'required|email|max:100',
                'phone' => 'string|phone',
                'date_of_birth' => 'date',
                'hire_date' => 'required|date',
                'department_id' => 'required|integer',
                'position_id' => 'required|integer',
                'salary' => 'numeric|min:0',
                'address' => 'string|max:500',
                'emergency_contact_name' => 'string|max:100',
                'emergency_contact_phone' => 'string|phone',
                'bank_account_number' => 'string|max:50',
                'bank_name' => 'string|max:100',
                'pan_number' => 'string|max:20',
                'aadhar_number' => 'string|max:20'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                Response::validationError(['email' => ['Email already exists']]);
                return;
            }
            
            // Validate department and position exist
            if (!$this->departmentExists($data['department_id'])) {
                Response::validationError(['department_id' => ['Department not found']]);
                return;
            }
            
            if (!$this->positionExists($data['position_id'])) {
                Response::validationError(['position_id' => ['Position not found']]);
                return;
            }
            

            
            $this->db->beginTransaction();
            
            // Generate employee ID
            $employeeId = $this->generateEmployeeId();
            
            // Create user account for employee
            // Generate username from email if not provided
            if (empty($data['username'])) {
                $data['username'] = explode('@', $data['email'])[0];
            }
            $userId = $this->createUserAccount($data);
            
            // Insert employee
            $query = "INSERT INTO employees (
                        user_id, employee_id, first_name, last_name, email, phone, 
                        date_of_birth, hire_date, department_id, position_id, 
                        salary, address, emergency_contact_name, emergency_contact_phone,
                        bank_account_number, bank_name, pan_number, aadhar_number,
                        status, created_at
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
            
            $employeeDbId = $this->db->insert($query, [
                $userId,
                $employeeId,
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['hire_date'],
                $data['department_id'],
                $data['position_id'],
                $data['salary'] ?? null,
                $data['address'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['emergency_contact_phone'] ?? null,
                $data['bank_account_number'] ?? null,
                $data['bank_name'] ?? null,
                $data['pan_number'] ?? null,
                $data['aadhar_number'] ?? null
            ]);
            
            // Initialize leave balances
            $this->initializeLeaveBalances($employeeDbId);
            
            $this->db->commit();
            
            // Get created employee
            $employee = $this->getEmployeeById($employeeDbId);
            
            Response::created($employee, 'Employee created successfully');
            
        } catch (Exception $e) {
            $this->db->rollback();
            Response::serverError('Failed to create employee: ' . $e->getMessage());
        }
    }
    
    /**
     * Update employee
     */
    public function update($id) {
        try {
            $employee = $this->getEmployeeById($id);
            
            if (!$employee) {
                Response::notFound('Employee not found');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'first_name' => 'string|max:50',
                'last_name' => 'string|max:50',
                'email' => 'email|max:100',
                'phone' => 'string|phone',
                'date_of_birth' => 'date',
                'department_id' => 'integer',
                'position_id' => 'integer',
                'salary' => 'numeric|min:0',
                'address' => 'string|max:500',
                'emergency_contact_name' => 'string|max:100',
                'emergency_contact_phone' => 'string|phone',
                'bank_account_number' => 'string|max:50',
                'bank_name' => 'string|max:100',
                'pan_number' => 'string|max:20',
                'aadhar_number' => 'string|max:20',
                'status' => 'string|in:active,inactive,terminated'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Check if email already exists for another employee
            if (isset($data['email']) && $this->emailExistsForOther($data['email'], $id)) {
                Response::validationError(['email' => ['Email already exists']]);
                return;
            }
            
            // Validate references
            if (isset($data['department_id']) && !$this->departmentExists($data['department_id'])) {
                Response::validationError(['department_id' => ['Department not found']]);
                return;
            }
            
            if (isset($data['position_id']) && !$this->positionExists($data['position_id'])) {
                Response::validationError(['position_id' => ['Position not found']]);
                return;
            }
            

            
            // Build update query
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = [
                'first_name', 'last_name', 'email', 'phone', 'date_of_birth',
                'department_id', 'position_id', 'salary', 'address',
                'emergency_contact_name', 'emergency_contact_phone', 'bank_account_number',
                'bank_name', 'pan_number', 'aadhar_number', 'status'
            ];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                Response::error('No fields to update', 400);
                return;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $updateValues[] = $id;
            
            $query = "UPDATE employees SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $result = $this->db->update($query, $updateValues);
            
            if ($result) {
                $updatedEmployee = $this->getEmployeeById($id);
                Response::updated($updatedEmployee, 'Employee updated successfully');
            } else {
                Response::serverError('Failed to update employee');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update employee: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete employee (soft delete)
     */
    public function delete($id) {
        try {
            $employee = $this->getEmployeeById($id);
            
            if (!$employee) {
                Response::notFound('Employee not found');
                return;
            }
            
            // Soft delete by updating status
            $query = "UPDATE employees SET status = 'terminated', updated_at = NOW() WHERE id = ?";
            $result = $this->db->update($query, [$id]);
            
            if ($result) {
                // Also deactivate user account if exists
                if ($employee['user_id']) {
                    $this->db->update(
                        "UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?",
                        [$employee['user_id']]
                    );
                }
                
                Response::deleted('Employee deleted successfully');
            } else {
                Response::serverError('Failed to delete employee');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to delete employee: ' . $e->getMessage());
        }
    }
    
    /**
     * Get employee documents
     */
    public function getDocuments($id) {
        try {
            $employee = $this->getEmployeeById($id);
            
            if (!$employee) {
                Response::notFound('Employee not found');
                return;
            }
            
            // Check access permissions
            if (!$this->canAccessEmployee($id)) {
                Response::forbidden('Access denied');
                return;
            }
            
            $documents = $this->getEmployeeDocuments($id);
            
            Response::success($documents);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch documents: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload employee document
     */
    public function uploadDocument($id) {
        try {
            $employee = $this->getEmployeeById($id);
            
            if (!$employee) {
                Response::notFound('Employee not found');
                return;
            }
            
            // Check access permissions
            if (!$this->canAccessEmployee($id)) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'document_type' => 'required|string|in:resume,id_proof,address_proof,education,experience,other',
                'document_name' => 'required|string|max:255'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Validate file upload
            if (!isset($_FILES['document'])) {
                Response::validationError(['document' => ['Document file is required']]);
                return;
            }
            
            $file = $_FILES['document'];
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            $fileValidation = Validator::validateFileUpload($file, $allowedTypes, $maxSize);
            
            if (!$fileValidation['valid']) {
                Response::validationError(['document' => $fileValidation['errors']]);
                return;
            }
            
            // Create upload directory
            $uploadDir = __DIR__ . '/../uploads/documents/' . $id . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Save document record
                $query = "INSERT INTO employee_documents (
                            employee_id, document_type, document_name, file_name, 
                            file_path, file_size, uploaded_by, created_at
                         ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $documentId = $this->db->insert($query, [
                    $id,
                    $data['document_type'],
                    $data['document_name'],
                    $file['name'],
                    $filePath,
                    $file['size'],
                    $this->user['id']
                ]);
                
                $document = $this->db->selectOne(
                    "SELECT * FROM employee_documents WHERE id = ?",
                    [$documentId]
                );
                
                Response::created($document, 'Document uploaded successfully');
            } else {
                Response::serverError('Failed to upload document');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to upload document: ' . $e->getMessage());
        }
    }
    
    // Helper methods
    
    private function getEmployeeById($id) {
        $query = "SELECT e.*, d.name as department_name, p.title as position_title,
                        CASE WHEN e.manager_id IS NOT NULL THEN 
                            CONCAT(m.first_name, ' ', m.last_name) 
                        ELSE NULL END as manager_name
                 FROM employees e
                 LEFT JOIN departments d ON e.department_id = d.id
                 LEFT JOIN positions p ON e.position_id = p.id
                 LEFT JOIN employees m ON e.manager_id = m.id
                 WHERE e.id = ?";
        
        $employee = $this->db->selectOne($query, [$id]);
        
        if ($employee) {
            $employee['full_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
            $employee['salary'] = $employee['salary'] ? (float)$employee['salary'] : null;
        }
        
        return $employee;
    }
    
    private function canAccessEmployee($employeeId) {
        // Admin and HR can access all employees
        if (in_array($this->user['role'], ['admin', 'hr'])) {
            return true;
        }
        
        // Users can access their own record
        $employee = $this->db->selectOne(
            "SELECT user_id FROM employees WHERE id = ?",
            [$employeeId]
        );
        
        if ($employee && $employee['user_id'] == $this->user['id']) {
            return true;
        }
        
        // Managers can access their subordinates
        if ($this->user['role'] === 'manager') {
            $subordinate = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM employees WHERE id = ? AND manager_id = (
                    SELECT id FROM employees WHERE user_id = ?
                )",
                [$employeeId, $this->user['id']]
            );
            
            return $subordinate && $subordinate['count'] > 0;
        }
        
        return false;
    }
    
    private function emailExists($email) {
        $query = "SELECT COUNT(*) as count FROM employees WHERE email = ?";
        $result = $this->db->selectOne($query, [$email]);
        return $result && $result['count'] > 0;
    }
    
    private function emailExistsForOther($email, $excludeId) {
        $query = "SELECT COUNT(*) as count FROM employees WHERE email = ? AND id != ?";
        $result = $this->db->selectOne($query, [$email, $excludeId]);
        return $result && $result['count'] > 0;
    }
    
    private function departmentExists($id) {
        $query = "SELECT COUNT(*) as count FROM departments WHERE id = ?";
        $result = $this->db->selectOne($query, [$id]);
        return $result && $result['count'] > 0;
    }
    
    private function positionExists($id) {
        $query = "SELECT COUNT(*) as count FROM positions WHERE id = ?";
        $result = $this->db->selectOne($query, [$id]);
        return $result && $result['count'] > 0;
    }
    
    private function employeeExists($id) {
        $query = "SELECT COUNT(*) as count FROM employees WHERE id = ? AND status = 'active'";
        $result = $this->db->selectOne($query, [$id]);
        return $result && $result['count'] > 0;
    }
    
    private function generateEmployeeId() {
        do {
            $employeeId = 'EMP' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM employees WHERE employee_id = ?",
                [$employeeId]
            );
        } while ($exists && $exists['count'] > 0);
        
        return $employeeId;
    }
    
    private function createUserAccount($data) {
        $hashedPassword = password_hash($data['password'] ?? 'temp123', PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, role, status, created_at) 
                 VALUES (?, ?, ?, 'employee', 'active', NOW())";
        
        return $this->db->insert($query, [
            $data['username'],
            $data['email'],
            $hashedPassword
        ]);
    }
    
    private function initializeLeaveBalances($employeeId) {
        // Get all leave types
        $leaveTypes = $this->db->select("SELECT * FROM leave_types WHERE status = 'active'");
        
        foreach ($leaveTypes as $leaveType) {
            $query = "INSERT INTO employee_leave_balances (employee_id, leave_type_id, allocated_days, used_days, remaining_days, year) 
                     VALUES (?, ?, ?, 0, ?, YEAR(NOW()))";
            
            $this->db->insert($query, [
                $employeeId,
                $leaveType['id'],
                $leaveType['default_days'],
                $leaveType['default_days']
            ]);
        }
    }
    
    private function getEmployeeDocuments($employeeId) {
        $query = "SELECT id, document_type, document_name, file_name, file_size, created_at 
                 FROM employee_documents 
                 WHERE employee_id = ? 
                 ORDER BY created_at DESC";
        
        return $this->db->select($query, [$employeeId]);
    }
    
    private function getLeaveBalance($employeeId) {
        $query = "SELECT lt.name, elb.allocated_days, elb.used_days, elb.remaining_days 
                 FROM employee_leave_balances elb 
                 JOIN leave_types lt ON elb.leave_type_id = lt.id 
                 WHERE elb.employee_id = ? AND elb.year = YEAR(NOW())";
        
        return $this->db->select($query, [$employeeId]);
    }
    
    private function getRecentAttendance($employeeId, $days = 7) {
        $query = "SELECT date, check_in_time, check_out_time, total_hours, status 
                 FROM attendance 
                 WHERE employee_id = ? AND date >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                 ORDER BY date DESC";
        
        return $this->db->select($query, [$employeeId, $days]);
    }
}

?>