<?php
/**
 * Attendance Controller
 * Handles attendance management operations
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class AttendanceController {
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
     * Get attendance records with pagination and filtering
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
            
            // Date range filter
            if (!empty($params['start_date'])) {
                $filters[] = "a.date >= ?";
                $filterValues[] = $params['start_date'];
            }
            
            if (!empty($params['end_date'])) {
                $filters[] = "a.date <= ?";
                $filterValues[] = $params['end_date'];
            }
            
            // Employee filter (for HR/Admin)
            if (!empty($params['employee_id']) && in_array($this->user['role'], ['admin', 'hr'])) {
                $filters[] = "a.employee_id = ?";
                $filterValues[] = $params['employee_id'];
            } elseif (!in_array($this->user['role'], ['admin', 'hr'])) {
                // Regular employees can only see their own records
                $employeeId = $this->getCurrentEmployeeId();
                if ($employeeId) {
                    $filters[] = "a.employee_id = ?";
                    $filterValues[] = $employeeId;
                }
            }
            
            // Department filter
            if (!empty($params['department_id']) && in_array($this->user['role'], ['admin', 'hr'])) {
                $filters[] = "e.department_id = ?";
                $filterValues[] = $params['department_id'];
            }
            
            // Status filter
            if (!empty($params['status'])) {
                $filters[] = "a.status = ?";
                $filterValues[] = $params['status'];
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total 
                          FROM attendance a 
                          JOIN employees e ON a.employee_id = e.id 
                          $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get attendance records
            $query = "SELECT a.id, a.date, a.check_in_time, a.check_out_time, a.total_hours, 
                            a.break_time, a.overtime_hours, a.status, a.notes,
                            e.employee_id, e.first_name, e.last_name,
                            d.name as department_name
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     LEFT JOIN departments d ON e.department_id = d.id
                     $whereClause
                     ORDER BY a.date DESC, a.check_in_time DESC
                     LIMIT ? OFFSET ?";
            
            $attendance = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($attendance as &$record) {
                $record['employee_name'] = $record['first_name'] . ' ' . $record['last_name'];
                $record['total_hours'] = $record['total_hours'] ? (float)$record['total_hours'] : null;
                $record['overtime_hours'] = $record['overtime_hours'] ? (float)$record['overtime_hours'] : null;
                $record['break_time'] = $record['break_time'] ? (int)$record['break_time'] : null;
            }
            
            Response::paginated($attendance, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch attendance records: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single attendance record
     */
    public function show($id) {
        try {
            $query = "SELECT a.*, e.employee_id, e.first_name, e.last_name, d.name as department_name
                     FROM attendance a
                     JOIN employees e ON a.employee_id = e.id
                     LEFT JOIN departments d ON e.department_id = d.id
                     WHERE a.id = ?";
            
            $attendance = $this->db->selectOne($query, [$id]);
            
            if (!$attendance) {
                Response::notFound('Attendance record not found');
                return;
            }
            
            // Check access permissions
            if (!$this->canAccessAttendance($attendance['employee_id'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Format data
            $attendance['employee_name'] = $attendance['first_name'] . ' ' . $attendance['last_name'];
            $attendance['total_hours'] = $attendance['total_hours'] ? (float)$attendance['total_hours'] : null;
            $attendance['overtime_hours'] = $attendance['overtime_hours'] ? (float)$attendance['overtime_hours'] : null;
            $attendance['break_time'] = $attendance['break_time'] ? (int)$attendance['break_time'] : null;
            
            Response::success($attendance);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch attendance record: ' . $e->getMessage());
        }
    }
    
    /**
     * Employee check-in
     */
    public function checkIn() {
        try {
            $data = Router::getRequestData();
            $employeeId = $this->getCurrentEmployeeId();
            
            if (!$employeeId) {
                Response::error('Employee record not found', 400);
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'location' => 'string|max:255',
                'notes' => 'string|max:500'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            $today = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            // Check if already checked in today
            $existingRecord = $this->db->selectOne(
                "SELECT * FROM attendance WHERE employee_id = ? AND date = ?",
                [$employeeId, $today]
            );
            
            if ($existingRecord) {
                if ($existingRecord['check_in_time']) {
                    Response::error('Already checked in today', 400);
                    return;
                }
            }
            
            // Get work schedule
            $schedule = $this->getWorkSchedule($employeeId);
            $status = $this->determineAttendanceStatus($currentTime, $schedule);
            
            if ($existingRecord) {
                // Update existing record
                $query = "UPDATE attendance SET 
                         check_in_time = ?, status = ?, location = ?, notes = ?, updated_at = NOW() 
                         WHERE id = ?";
                
                $result = $this->db->update($query, [
                    $currentTime,
                    $status,
                    $data['location'] ?? null,
                    $data['notes'] ?? null,
                    $existingRecord['id']
                ]);
                
                $attendanceId = $existingRecord['id'];
            } else {
                // Create new record
                $query = "INSERT INTO attendance (
                         employee_id, date, check_in_time, status, location, notes, created_at
                         ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                $attendanceId = $this->db->insert($query, [
                    $employeeId,
                    $today,
                    $currentTime,
                    $status,
                    $data['location'] ?? null,
                    $data['notes'] ?? null
                ]);
            }
            
            if ($attendanceId) {
                $attendance = $this->db->selectOne(
                    "SELECT * FROM attendance WHERE id = ?",
                    [$attendanceId]
                );
                
                Response::success($attendance, 'Check-in successful');
            } else {
                Response::serverError('Failed to record check-in');
            }
            
        } catch (Exception $e) {
            Response::serverError('Check-in failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Employee check-out
     */
    public function checkOut() {
        try {
            $data = Router::getRequestData();
            $employeeId = $this->getCurrentEmployeeId();
            
            if (!$employeeId) {
                Response::error('Employee record not found', 400);
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'notes' => 'string|max:500'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            $today = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            // Get today's attendance record
            $attendance = $this->db->selectOne(
                "SELECT * FROM attendance WHERE employee_id = ? AND date = ?",
                [$employeeId, $today]
            );
            
            if (!$attendance) {
                Response::error('No check-in record found for today', 400);
                return;
            }
            
            if (!$attendance['check_in_time']) {
                Response::error('Please check-in first', 400);
                return;
            }
            
            if ($attendance['check_out_time']) {
                Response::error('Already checked out today', 400);
                return;
            }
            
            // Calculate total hours
            $checkInTime = new DateTime($attendance['date'] . ' ' . $attendance['check_in_time']);
            $checkOutTime = new DateTime($today . ' ' . $currentTime);
            $interval = $checkInTime->diff($checkOutTime);
            $totalHours = $interval->h + ($interval->i / 60);
            
            // Calculate overtime
            $schedule = $this->getWorkSchedule($employeeId);
            $standardHours = $schedule['hours_per_day'] ?? 8;
            $overtimeHours = max(0, $totalHours - $standardHours);
            
            // Update attendance record
            $query = "UPDATE attendance SET 
                     check_out_time = ?, total_hours = ?, overtime_hours = ?, 
                     notes = CONCAT(COALESCE(notes, ''), ?, ' '), updated_at = NOW() 
                     WHERE id = ?";
            
            $result = $this->db->update($query, [
                $currentTime,
                $totalHours,
                $overtimeHours,
                $data['notes'] ? ' | ' . $data['notes'] : '',
                $attendance['id']
            ]);
            
            if ($result) {
                $updatedAttendance = $this->db->selectOne(
                    "SELECT * FROM attendance WHERE id = ?",
                    [$attendance['id']]
                );
                
                Response::success($updatedAttendance, 'Check-out successful');
            } else {
                Response::serverError('Failed to record check-out');
            }
            
        } catch (Exception $e) {
            Response::serverError('Check-out failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get current user's attendance records
     */
    public function myRecords() {
        try {
            $params = Router::getQueryParams();
            $employeeId = $this->getCurrentEmployeeId();
            
            if (!$employeeId) {
                Response::error('Employee record not found', 400);
                return;
            }
            
            // Pagination
            $page = max(1, (int)($params['page'] ?? 1));
            $perPage = min(100, max(10, (int)($params['per_page'] ?? 20)));
            $offset = ($page - 1) * $perPage;
            
            // Date range filter
            $filters = ["a.employee_id = ?"];
            $filterValues = [$employeeId];
            
            if (!empty($params['start_date'])) {
                $filters[] = "a.date >= ?";
                $filterValues[] = $params['start_date'];
            }
            
            if (!empty($params['end_date'])) {
                $filters[] = "a.date <= ?";
                $filterValues[] = $params['end_date'];
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $filters);
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM attendance a $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get records
            $query = "SELECT a.id, a.date, a.check_in_time, a.check_out_time, a.total_hours, 
                            a.break_time, a.overtime_hours, a.status, a.notes
                     FROM attendance a
                     $whereClause
                     ORDER BY a.date DESC
                     LIMIT ? OFFSET ?";
            
            $attendance = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($attendance as &$record) {
                $record['total_hours'] = $record['total_hours'] ? (float)$record['total_hours'] : null;
                $record['overtime_hours'] = $record['overtime_hours'] ? (float)$record['overtime_hours'] : null;
                $record['break_time'] = $record['break_time'] ? (int)$record['break_time'] : null;
            }
            
            // Get summary statistics
            $summary = $this->getAttendanceSummary($employeeId, $params);
            
            Response::paginated($attendance, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ], 'Attendance records retrieved successfully');
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch attendance records: ' . $e->getMessage());
        }
    }
    
    /**
     * Get attendance reports (HR/Admin only)
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
                    $data = $this->getAttendanceSummaryReport($params);
                    break;
                case 'daily':
                    $data = $this->getDailyAttendanceReport($params);
                    break;
                case 'monthly':
                    $data = $this->getMonthlyAttendanceReport($params);
                    break;
                case 'overtime':
                    $data = $this->getOvertimeReport($params);
                    break;
                case 'late_arrivals':
                    $data = $this->getLateArrivalsReport($params);
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
    
    private function canAccessAttendance($employeeId) {
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
    
    private function getWorkSchedule($employeeId) {
        // Default schedule - can be enhanced to get from employee/department settings
        return [
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'hours_per_day' => 8,
            'grace_period' => 15 // minutes
        ];
    }
    
    private function determineAttendanceStatus($checkInTime, $schedule) {
        $scheduledStart = $schedule['start_time'];
        $gracePeriod = $schedule['grace_period'] ?? 15;
        
        $checkIn = new DateTime($checkInTime);
        $scheduled = new DateTime($scheduledStart);
        $graceTime = clone $scheduled;
        $graceTime->add(new DateInterval('PT' . $gracePeriod . 'M'));
        
        if ($checkIn <= $scheduled) {
            return 'on_time';
        } elseif ($checkIn <= $graceTime) {
            return 'on_time'; // Within grace period
        } else {
            return 'late';
        }
    }
    
    private function getAttendanceSummary($employeeId, $params) {
        $startDate = $params['start_date'] ?? date('Y-m-01'); // First day of current month
        $endDate = $params['end_date'] ?? date('Y-m-d'); // Today
        
        $query = "SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(COALESCE(total_hours, 0)) as total_hours,
                    SUM(COALESCE(overtime_hours, 0)) as total_overtime,
                    AVG(COALESCE(total_hours, 0)) as avg_hours_per_day
                 FROM attendance 
                 WHERE employee_id = ? AND date BETWEEN ? AND ?";
        
        return $this->db->selectOne($query, [$employeeId, $startDate, $endDate]);
    }
    
    private function getAttendanceSummaryReport($params) {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $query = "SELECT 
                    e.employee_id, e.first_name, e.last_name, d.name as department_name,
                    COUNT(a.id) as total_days,
                    SUM(CASE WHEN a.status = 'present' OR a.status = 'on_time' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(COALESCE(a.total_hours, 0)) as total_hours,
                    SUM(COALESCE(a.overtime_hours, 0)) as total_overtime
                 FROM employees e
                 LEFT JOIN attendance a ON e.id = a.employee_id AND a.date BETWEEN ? AND ?
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE e.status = 'active'
                 GROUP BY e.id
                 ORDER BY e.first_name, e.last_name";
        
        return $this->db->select($query, [$startDate, $endDate]);
    }
    
    private function getDailyAttendanceReport($params) {
        $date = $params['date'] ?? date('Y-m-d');
        
        $query = "SELECT 
                    e.employee_id, e.first_name, e.last_name, d.name as department_name,
                    a.check_in_time, a.check_out_time, a.total_hours, a.status
                 FROM employees e
                 LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE e.status = 'active'
                 ORDER BY d.name, e.first_name, e.last_name";
        
        return $this->db->select($query, [$date]);
    }
    
    private function getMonthlyAttendanceReport($params) {
        $month = $params['month'] ?? date('Y-m');
        
        $query = "SELECT 
                    e.employee_id, e.first_name, e.last_name, d.name as department_name,
                    COUNT(a.id) as working_days,
                    SUM(CASE WHEN a.status IN ('present', 'on_time') THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(COALESCE(a.total_hours, 0)) as total_hours,
                    SUM(COALESCE(a.overtime_hours, 0)) as overtime_hours
                 FROM employees e
                 LEFT JOIN attendance a ON e.id = a.employee_id AND DATE_FORMAT(a.date, '%Y-%m') = ?
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE e.status = 'active'
                 GROUP BY e.id
                 ORDER BY d.name, e.first_name, e.last_name";
        
        return $this->db->select($query, [$month]);
    }
    
    private function getOvertimeReport($params) {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $query = "SELECT 
                    e.employee_id, e.first_name, e.last_name, d.name as department_name,
                    SUM(COALESCE(a.overtime_hours, 0)) as total_overtime,
                    COUNT(CASE WHEN a.overtime_hours > 0 THEN 1 END) as overtime_days
                 FROM employees e
                 JOIN attendance a ON e.id = a.employee_id
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE a.date BETWEEN ? AND ? AND a.overtime_hours > 0
                 GROUP BY e.id
                 HAVING total_overtime > 0
                 ORDER BY total_overtime DESC";
        
        return $this->db->select($query, [$startDate, $endDate]);
    }
    
    private function getLateArrivalsReport($params) {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $query = "SELECT 
                    e.employee_id, e.first_name, e.last_name, d.name as department_name,
                    COUNT(*) as late_count,
                    GROUP_CONCAT(a.date ORDER BY a.date DESC SEPARATOR ', ') as late_dates
                 FROM employees e
                 JOIN attendance a ON e.id = a.employee_id
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE a.date BETWEEN ? AND ? AND a.status = 'late'
                 GROUP BY e.id
                 ORDER BY late_count DESC";
        
        return $this->db->select($query, [$startDate, $endDate]);
    }
}

?>