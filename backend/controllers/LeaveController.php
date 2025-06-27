<?php
/**
 * Leave Controller
 * Handles leave management operations
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class LeaveController {
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
     * Get leave requests with pagination and filtering
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
            
            // Employee filter (for HR/Admin)
            if (!empty($params['employee_id']) && in_array($this->user['role'], ['admin', 'hr'])) {
                $filters[] = "lr.employee_id = ?";
                $filterValues[] = $params['employee_id'];
            } elseif (!in_array($this->user['role'], ['admin', 'hr'])) {
                // Regular employees can only see their own requests
                $employeeId = $this->getCurrentEmployeeId();
                if ($employeeId) {
                    $filters[] = "lr.employee_id = ?";
                    $filterValues[] = $employeeId;
                }
            }
            
            // Status filter
            if (!empty($params['status'])) {
                $filters[] = "lr.status = ?";
                $filterValues[] = $params['status'];
            }
            
            // Leave type filter
            if (!empty($params['leave_type_id'])) {
                $filters[] = "lr.leave_type_id = ?";
                $filterValues[] = $params['leave_type_id'];
            }
            
            // Date range filter
            if (!empty($params['start_date'])) {
                $filters[] = "lr.start_date >= ?";
                $filterValues[] = $params['start_date'];
            }
            
            if (!empty($params['end_date'])) {
                $filters[] = "lr.end_date <= ?";
                $filterValues[] = $params['end_date'];
            }
            
            // Department filter
            if (!empty($params['department_id']) && in_array($this->user['role'], ['admin', 'hr'])) {
                $filters[] = "e.department_id = ?";
                $filterValues[] = $params['department_id'];
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total 
                          FROM leave_requests lr 
                          JOIN employees e ON lr.employee_id = e.id 
                          $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get leave requests
            $query = "SELECT lr.id, lr.start_date, lr.end_date, lr.days_requested, lr.reason, 
                            lr.status, lr.applied_date, lr.approved_date, lr.approved_by,
                            lr.rejection_reason, lr.created_at,
                            e.employee_id, e.first_name, e.last_name,
                            lt.name as leave_type_name, lt.code as leave_type_code,
                            d.name as department_name,
                            approver.first_name as approver_first_name,
                            approver.last_name as approver_last_name
                     FROM leave_requests lr
                     JOIN employees e ON lr.employee_id = e.id
                     JOIN leave_types lt ON lr.leave_type_id = lt.id
                     LEFT JOIN departments d ON e.department_id = d.id
                     LEFT JOIN employees approver ON lr.approved_by = approver.id
                     $whereClause
                     ORDER BY lr.applied_date DESC
                     LIMIT ? OFFSET ?";
            
            $leaveRequests = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($leaveRequests as &$request) {
                $request['employee_name'] = $request['first_name'] . ' ' . $request['last_name'];
                $request['approver_name'] = $request['approver_first_name'] ? 
                    $request['approver_first_name'] . ' ' . $request['approver_last_name'] : null;
                $request['days_requested'] = (float)$request['days_requested'];
                
                // Remove individual name fields
                unset($request['first_name'], $request['last_name']);
                unset($request['approver_first_name'], $request['approver_last_name']);
            }
            
            Response::paginated($leaveRequests, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch leave requests: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single leave request
     */
    public function show($id) {
        try {
            $query = "SELECT lr.*, e.employee_id, e.first_name, e.last_name,
                            lt.name as leave_type_name, lt.code as leave_type_code,
                            d.name as department_name,
                            approver.first_name as approver_first_name,
                            approver.last_name as approver_last_name
                     FROM leave_requests lr
                     JOIN employees e ON lr.employee_id = e.id
                     JOIN leave_types lt ON lr.leave_type_id = lt.id
                     LEFT JOIN departments d ON e.department_id = d.id
                     LEFT JOIN employees approver ON lr.approved_by = approver.id
                     WHERE lr.id = ?";
            
            $leaveRequest = $this->db->selectOne($query, [$id]);
            
            if (!$leaveRequest) {
                Response::notFound('Leave request not found');
                return;
            }
            
            // Check access permissions
            if (!$this->canAccessLeaveRequest($leaveRequest['employee_id'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Format data
            $leaveRequest['employee_name'] = $leaveRequest['first_name'] . ' ' . $leaveRequest['last_name'];
            $leaveRequest['approver_name'] = $leaveRequest['approver_first_name'] ? 
                $leaveRequest['approver_first_name'] . ' ' . $leaveRequest['approver_last_name'] : null;
            $leaveRequest['days_requested'] = (float)$leaveRequest['days_requested'];
            
            // Remove individual name fields
            unset($leaveRequest['first_name'], $leaveRequest['last_name']);
            unset($leaveRequest['approver_first_name'], $leaveRequest['approver_last_name']);
            
            Response::success($leaveRequest);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch leave request: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new leave request
     */
    public function store() {
        try {
            $data = Router::getRequestData();
            $employeeId = $this->getCurrentEmployeeId();
            
            if (!$employeeId) {
                Response::error('Employee record not found', 400);
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'leave_type_id' => 'required|integer',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'reason' => 'required|string|max:1000',
                'days_requested' => 'numeric|min:0.5'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Validate dates
            $startDate = new DateTime($data['start_date']);
            $endDate = new DateTime($data['end_date']);
            $today = new DateTime();
            
            if ($startDate < $today) {
                Response::error('Start date cannot be in the past', 400);
                return;
            }
            
            if ($endDate < $startDate) {
                Response::error('End date cannot be before start date', 400);
                return;
            }
            
            // Calculate days if not provided
            if (!isset($data['days_requested'])) {
                $data['days_requested'] = $this->calculateLeaveDays($startDate, $endDate);
            }
            
            // Validate leave type
            $leaveType = $this->db->selectOne(
                "SELECT * FROM leave_types WHERE id = ? AND status = 'active'",
                [$data['leave_type_id']]
            );
            
            if (!$leaveType) {
                Response::error('Invalid leave type', 400);
                return;
            }
            
            // Check leave balance
            $balance = $this->getLeaveBalance($employeeId, $data['leave_type_id']);
            if ($balance < $data['days_requested']) {
                Response::error('Insufficient leave balance. Available: ' . $balance . ' days', 400);
                return;
            }
            
            // Check for overlapping leave requests
            $overlapping = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM leave_requests 
                 WHERE employee_id = ? AND status IN ('pending', 'approved') 
                 AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?))",
                [$employeeId, $data['start_date'], $data['start_date'], $data['end_date'], $data['end_date']]
            );
            
            if ($overlapping['count'] > 0) {
                Response::error('Leave request overlaps with existing request', 400);
                return;
            }
            
            // Create leave request
            $query = "INSERT INTO leave_requests (
                     employee_id, leave_type_id, start_date, end_date, days_requested, 
                     reason, status, applied_date, created_at
                     ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())";
            
            $leaveRequestId = $this->db->insert($query, [
                $employeeId,
                $data['leave_type_id'],
                $data['start_date'],
                $data['end_date'],
                $data['days_requested'],
                $data['reason']
            ]);
            
            if ($leaveRequestId) {
                $leaveRequest = $this->db->selectOne(
                    "SELECT lr.*, lt.name as leave_type_name 
                     FROM leave_requests lr 
                     JOIN leave_types lt ON lr.leave_type_id = lt.id 
                     WHERE lr.id = ?",
                    [$leaveRequestId]
                );
                
                // Send notification to manager/HR
                $this->sendLeaveRequestNotification($leaveRequest);
                
                Response::created($leaveRequest, 'Leave request submitted successfully');
            } else {
                Response::serverError('Failed to create leave request');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to create leave request: ' . $e->getMessage());
        }
    }
    
    /**
     * Update leave request (for pending requests only)
     */
    public function update($id) {
        try {
            $data = Router::getRequestData();
            
            // Get existing leave request
            $leaveRequest = $this->db->selectOne(
                "SELECT * FROM leave_requests WHERE id = ?",
                [$id]
            );
            
            if (!$leaveRequest) {
                Response::notFound('Leave request not found');
                return;
            }
            
            // Check access permissions
            if (!$this->canAccessLeaveRequest($leaveRequest['employee_id'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Only pending requests can be updated
            if ($leaveRequest['status'] !== 'pending') {
                Response::error('Only pending leave requests can be updated', 400);
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'leave_type_id' => 'integer',
                'start_date' => 'date',
                'end_date' => 'date',
                'reason' => 'string|max:1000',
                'days_requested' => 'numeric|min:0.5'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Prepare update data
            $updateFields = [];
            $updateValues = [];
            
            if (isset($data['leave_type_id'])) {
                $updateFields[] = "leave_type_id = ?";
                $updateValues[] = $data['leave_type_id'];
            }
            
            if (isset($data['start_date'])) {
                $updateFields[] = "start_date = ?";
                $updateValues[] = $data['start_date'];
            }
            
            if (isset($data['end_date'])) {
                $updateFields[] = "end_date = ?";
                $updateValues[] = $data['end_date'];
            }
            
            if (isset($data['reason'])) {
                $updateFields[] = "reason = ?";
                $updateValues[] = $data['reason'];
            }
            
            if (isset($data['days_requested'])) {
                $updateFields[] = "days_requested = ?";
                $updateValues[] = $data['days_requested'];
            }
            
            if (empty($updateFields)) {
                Response::error('No valid fields to update', 400);
                return;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $updateValues[] = $id;
            
            // Update leave request
            $query = "UPDATE leave_requests SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $result = $this->db->update($query, $updateValues);
            
            if ($result) {
                $updatedRequest = $this->db->selectOne(
                    "SELECT lr.*, lt.name as leave_type_name 
                     FROM leave_requests lr 
                     JOIN leave_types lt ON lr.leave_type_id = lt.id 
                     WHERE lr.id = ?",
                    [$id]
                );
                
                Response::updated($updatedRequest, 'Leave request updated successfully');
            } else {
                Response::serverError('Failed to update leave request');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update leave request: ' . $e->getMessage());
        }
    }
    
    /**
     * Approve or reject leave request (HR/Manager only)
     */
    public function updateStatus($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'status' => 'required|in:approved,rejected',
                'rejection_reason' => 'string|max:500'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Get leave request
            $leaveRequest = $this->db->selectOne(
                "SELECT lr.*, e.employee_id, e.first_name, e.last_name 
                 FROM leave_requests lr 
                 JOIN employees e ON lr.employee_id = e.id 
                 WHERE lr.id = ?",
                [$id]
            );
            
            if (!$leaveRequest) {
                Response::notFound('Leave request not found');
                return;
            }
            
            if ($leaveRequest['status'] !== 'pending') {
                Response::error('Leave request has already been processed', 400);
                return;
            }
            
            // Check if manager can approve this request
            if ($this->user['role'] === 'manager') {
                $currentEmployeeId = $this->getCurrentEmployeeId();
                $canApprove = $this->db->selectOne(
                    "SELECT COUNT(*) as count FROM employees 
                     WHERE id = ? AND manager_id = ?",
                    [$leaveRequest['employee_id'], $currentEmployeeId]
                );
                
                if (!$canApprove || $canApprove['count'] == 0) {
                    Response::forbidden('You can only approve requests for your subordinates');
                    return;
                }
            }
            
            $approverId = $this->getCurrentEmployeeId();
            
            // Update leave request status
            $query = "UPDATE leave_requests SET 
                     status = ?, approved_by = ?, approved_date = NOW(), 
                     rejection_reason = ?, updated_at = NOW() 
                     WHERE id = ?";
            
            $result = $this->db->update($query, [
                $data['status'],
                $approverId,
                $data['rejection_reason'] ?? null,
                $id
            ]);
            
            if ($result) {
                // Update leave balance if approved
                if ($data['status'] === 'approved') {
                    $this->updateLeaveBalance(
                        $leaveRequest['employee_id'], 
                        $leaveRequest['leave_type_id'], 
                        -$leaveRequest['days_requested']
                    );
                }
                
                $updatedRequest = $this->db->selectOne(
                    "SELECT lr.*, lt.name as leave_type_name, 
                            approver.first_name as approver_first_name,
                            approver.last_name as approver_last_name
                     FROM leave_requests lr 
                     JOIN leave_types lt ON lr.leave_type_id = lt.id 
                     LEFT JOIN employees approver ON lr.approved_by = approver.id
                     WHERE lr.id = ?",
                    [$id]
                );
                
                // Send notification to employee
                $this->sendLeaveStatusNotification($updatedRequest);
                
                Response::updated($updatedRequest, 'Leave request ' . $data['status'] . ' successfully');
            } else {
                Response::serverError('Failed to update leave request status');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update leave request status: ' . $e->getMessage());
        }
    }
    
    /**
     * Cancel leave request (employee only, for pending/approved requests)
     */
    public function cancel($id) {
        try {
            $leaveRequest = $this->db->selectOne(
                "SELECT * FROM leave_requests WHERE id = ?",
                [$id]
            );
            
            if (!$leaveRequest) {
                Response::notFound('Leave request not found');
                return;
            }
            
            // Check if user can cancel this request
            $currentEmployeeId = $this->getCurrentEmployeeId();
            if ($leaveRequest['employee_id'] != $currentEmployeeId) {
                Response::forbidden('You can only cancel your own leave requests');
                return;
            }
            
            if (!in_array($leaveRequest['status'], ['pending', 'approved'])) {
                Response::error('Only pending or approved requests can be cancelled', 400);
                return;
            }
            
            // Check if leave has already started
            $today = new DateTime();
            $startDate = new DateTime($leaveRequest['start_date']);
            
            if ($startDate <= $today) {
                Response::error('Cannot cancel leave that has already started', 400);
                return;
            }
            
            // Cancel the request
            $query = "UPDATE leave_requests SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $result = $this->db->update($query, [$id]);
            
            if ($result) {
                // Restore leave balance if it was approved
                if ($leaveRequest['status'] === 'approved') {
                    $this->updateLeaveBalance(
                        $leaveRequest['employee_id'], 
                        $leaveRequest['leave_type_id'], 
                        $leaveRequest['days_requested']
                    );
                }
                
                Response::success(null, 'Leave request cancelled successfully');
            } else {
                Response::serverError('Failed to cancel leave request');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to cancel leave request: ' . $e->getMessage());
        }
    }
    
    /**
     * Get leave types
     */
    public function getLeaveTypes() {
        try {
            $query = "SELECT * FROM leave_types WHERE status = 'active' ORDER BY name";
            $leaveTypes = $this->db->select($query);
            
            Response::success($leaveTypes);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch leave types: ' . $e->getMessage());
        }
    }
    
    /**
     * Get leave balance for current user or specific employee
     */
    public function getBalance($employeeId = null) {
        try {
            if (!$employeeId) {
                $employeeId = $this->getCurrentEmployeeId();
            }
            
            if (!$employeeId) {
                Response::error('Employee record not found', 400);
                return;
            }
            
            // Check access permissions
            if (!$this->canAccessLeaveRequest($employeeId)) {
                Response::forbidden('Access denied');
                return;
            }
            
            $query = "SELECT lt.id, lt.name, lt.code, lt.days_per_year,
                            COALESCE(lb.balance, lt.days_per_year) as current_balance,
                            COALESCE(lb.used, 0) as used_days,
                            COALESCE(lb.carried_forward, 0) as carried_forward
                     FROM leave_types lt
                     LEFT JOIN leave_balances lb ON lt.id = lb.leave_type_id AND lb.employee_id = ?
                     WHERE lt.status = 'active'
                     ORDER BY lt.name";
            
            $balances = $this->db->select($query, [$employeeId]);
            
            // Format data
            foreach ($balances as &$balance) {
                $balance['days_per_year'] = (float)$balance['days_per_year'];
                $balance['current_balance'] = (float)$balance['current_balance'];
                $balance['used_days'] = (float)$balance['used_days'];
                $balance['carried_forward'] = (float)$balance['carried_forward'];
            }
            
            Response::success($balances);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch leave balance: ' . $e->getMessage());
        }
    }
    
    /**
     * Get leave reports (HR/Admin only)
     */
    public function reports() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $params = Router::getQueryParams();
            $reportType = $params['type'] ?? 'summary';
            
            switch ($reportType) {
                case 'summary':
                    $data = $this->getLeaveSummaryReport($params);
                    break;
                case 'balance':
                    $data = $this->getLeaveBalanceReport($params);
                    break;
                case 'trends':
                    $data = $this->getLeaveTrendsReport($params);
                    break;
                case 'department':
                    $data = $this->getDepartmentLeaveReport($params);
                    break;
                default:
                    Response::error('Invalid report type', 400);
                    return;
            }
            
            Response::success($data, 'Report generated successfully');
            
        } catch (Exception $e) {
            Response::serverError('Failed to generate report: ' . $e->getMessage());
        }
    }
    
    // Helper methods
    
    private function getCurrentEmployeeId() {
        $query = "SELECT id FROM employees WHERE user_id = ? AND status = 'active'";
        $result = $this->db->selectOne($query, [$this->user['id']]);
        return $result ? $result['id'] : null;
    }
    
    private function canAccessLeaveRequest($employeeId) {
        // Admin and HR can access all records
        if (in_array($this->user['role'], ['admin', 'hr'])) {
            return true;
        }
        
        // Users can access their own records
        $currentEmployeeId = $this->getCurrentEmployeeId();
        if ($currentEmployeeId == $employeeId) {
            return true;
        }
        
        // Managers can access their subordinates' records
        if ($this->user['role'] === 'manager') {
            $subordinate = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM employees WHERE id = ? AND manager_id = ?",
                [$employeeId, $currentEmployeeId]
            );
            
            return $subordinate && $subordinate['count'] > 0;
        }
        
        return false;
    }
    
    private function calculateLeaveDays($startDate, $endDate) {
        $interval = $startDate->diff($endDate);
        return $interval->days + 1; // Include both start and end dates
    }
    
    private function getLeaveBalance($employeeId, $leaveTypeId) {
        $query = "SELECT COALESCE(lb.balance, lt.days_per_year) as balance
                 FROM leave_types lt
                 LEFT JOIN leave_balances lb ON lt.id = lb.leave_type_id AND lb.employee_id = ?
                 WHERE lt.id = ?";
        
        $result = $this->db->selectOne($query, [$employeeId, $leaveTypeId]);
        return $result ? (float)$result['balance'] : 0;
    }
    
    private function updateLeaveBalance($employeeId, $leaveTypeId, $change) {
        // Check if balance record exists
        $existing = $this->db->selectOne(
            "SELECT * FROM leave_balances WHERE employee_id = ? AND leave_type_id = ?",
            [$employeeId, $leaveTypeId]
        );
        
        if ($existing) {
            // Update existing balance
            $query = "UPDATE leave_balances SET 
                     balance = balance + ?, used = used - ?, updated_at = NOW() 
                     WHERE employee_id = ? AND leave_type_id = ?";
            
            $this->db->update($query, [$change, $change, $employeeId, $leaveTypeId]);
        } else {
            // Create new balance record
            $leaveType = $this->db->selectOne(
                "SELECT days_per_year FROM leave_types WHERE id = ?",
                [$leaveTypeId]
            );
            
            if ($leaveType) {
                $query = "INSERT INTO leave_balances (
                         employee_id, leave_type_id, balance, used, year, created_at
                         ) VALUES (?, ?, ?, ?, ?, NOW())";
                
                $this->db->insert($query, [
                    $employeeId,
                    $leaveTypeId,
                    $leaveType['days_per_year'] + $change,
                    -$change,
                    date('Y')
                ]);
            }
        }
    }
    
    private function sendLeaveRequestNotification($leaveRequest) {
        // Implementation for sending notifications
        // This could integrate with email service, push notifications, etc.
    }
    
    private function sendLeaveStatusNotification($leaveRequest) {
        // Implementation for sending status update notifications
    }
    
    private function getLeaveSummaryReport($params) {
        $startDate = $params['start_date'] ?? date('Y-01-01');
        $endDate = $params['end_date'] ?? date('Y-12-31');
        
        $query = "SELECT 
                    e.employee_id, e.first_name, e.last_name, d.name as department_name,
                    COUNT(lr.id) as total_requests,
                    SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as approved_days,
                    SUM(CASE WHEN lr.status = 'pending' THEN lr.days_requested ELSE 0 END) as pending_days,
                    SUM(CASE WHEN lr.status = 'rejected' THEN lr.days_requested ELSE 0 END) as rejected_days
                 FROM employees e
                 LEFT JOIN leave_requests lr ON e.id = lr.employee_id 
                    AND lr.start_date BETWEEN ? AND ?
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE e.status = 'active'
                 GROUP BY e.id
                 ORDER BY e.first_name, e.last_name";
        
        return $this->db->select($query, [$startDate, $endDate]);
    }
    
    private function getLeaveBalanceReport($params) {
        $query = "SELECT 
                    e.employee_id, e.first_name, e.last_name, d.name as department_name,
                    lt.name as leave_type_name,
                    COALESCE(lb.balance, lt.days_per_year) as current_balance,
                    COALESCE(lb.used, 0) as used_days,
                    lt.days_per_year as allocated_days
                 FROM employees e
                 CROSS JOIN leave_types lt
                 LEFT JOIN leave_balances lb ON e.id = lb.employee_id AND lt.id = lb.leave_type_id
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE e.status = 'active' AND lt.status = 'active'
                 ORDER BY e.first_name, e.last_name, lt.name";
        
        return $this->db->select($query);
    }
    
    private function getLeaveTrendsReport($params) {
        $year = $params['year'] ?? date('Y');
        
        $query = "SELECT 
                    MONTH(lr.start_date) as month,
                    MONTHNAME(lr.start_date) as month_name,
                    COUNT(lr.id) as total_requests,
                    SUM(lr.days_requested) as total_days,
                    lt.name as leave_type_name
                 FROM leave_requests lr
                 JOIN leave_types lt ON lr.leave_type_id = lt.id
                 WHERE YEAR(lr.start_date) = ? AND lr.status = 'approved'
                 GROUP BY MONTH(lr.start_date), lr.leave_type_id
                 ORDER BY month, lt.name";
        
        return $this->db->select($query, [$year]);
    }
    
    private function getDepartmentLeaveReport($params) {
        $startDate = $params['start_date'] ?? date('Y-01-01');
        $endDate = $params['end_date'] ?? date('Y-12-31');
        
        $query = "SELECT 
                    d.name as department_name,
                    COUNT(DISTINCT e.id) as total_employees,
                    COUNT(lr.id) as total_requests,
                    SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as approved_days,
                    AVG(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE NULL END) as avg_days_per_request
                 FROM departments d
                 LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
                 LEFT JOIN leave_requests lr ON e.id = lr.employee_id 
                    AND lr.start_date BETWEEN ? AND ?
                 GROUP BY d.id
                 ORDER BY d.name";
        
        return $this->db->select($query, [$startDate, $endDate]);
    }
}

?>