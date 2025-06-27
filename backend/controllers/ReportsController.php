<?php
/**
 * Reports Controller
 * Handles HR analytics, dashboards, and various reports generation
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class ReportsController {
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
     * Get HR dashboard data
     */
    public function getDashboard() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $params = Router::getQueryParams();
            $year = $params['year'] ?? date('Y');
            $month = $params['month'] ?? date('m');
            
            // Employee statistics
            $employeeStats = $this->getEmployeeStatistics($year, $month);
            
            // Attendance statistics
            $attendanceStats = $this->getAttendanceStatistics($year, $month);
            
            // Leave statistics
            $leaveStats = $this->getLeaveStatistics($year, $month);
            
            // Payroll statistics
            $payrollStats = $this->getPayrollStatistics($year, $month);
            
            // Recent activities
            $recentActivities = $this->getRecentActivities();
            
            Response::success([
                'employees' => $employeeStats,
                'attendance' => $attendanceStats,
                'leaves' => $leaveStats,
                'payroll' => $payrollStats,
                'recent_activities' => $recentActivities
            ]);
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Get daily attendance report with salary impact
     */
    public function getDailyAttendanceReport() {
        try {
            $date = $_GET['date'] ?? date('Y-m-d');
            $departmentId = $_GET['department_id'] ?? null;
            
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return Response::error('Invalid date format. Use YYYY-MM-DD', null, 400);
            }
            
            $whereClause = "WHERE a.date = ?";
            $params = [$date];
            
            if ($departmentId) {
                $whereClause .= " AND e.department_id = ?";
                $params[] = $departmentId;
            }
            
            // Check permissions for employee role
            if ($this->user['role'] === 'employee') {
                $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ?");
                $stmt->execute([$this->user['id']]);
                $emp = $stmt->fetch();
                if ($emp) {
                    $whereClause .= " AND e.id = ?";
                    $params[] = $emp['id'];
                }
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    e.id as employee_id,
                    e.employee_id as emp_code,
                    e.first_name,
                    e.last_name,
                    d.name as department,
                    p.title as position,
                    a.clock_in,
                    a.clock_out,
                    a.scheduled_clock_in,
                    a.scheduled_clock_out,
                    a.hours_worked,
                    a.late_minutes,
                    a.overtime_minutes,
                    a.salary_deduction,
                    a.overtime_bonus,
                    a.status,
                    a.attendance_type,
                    a.device_id,
                    a.notes,
                    CASE 
                        WHEN a.id IS NULL THEN 'absent'
                        ELSE a.status
                    END as final_status
                FROM employees e
                LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                WHERE e.status = 'active'
                " . ($departmentId ? " AND e.department_id = ?" : "") . "
                ORDER BY d.name, e.first_name, e.last_name
            ");
            
            $queryParams = [$date];
            if ($departmentId) {
                $queryParams[] = $departmentId;
            }
            
            $stmt->execute($queryParams);
            $attendanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary statistics
            $summary = [
                'total_employees' => count($attendanceData),
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'total_hours_worked' => 0,
                'total_late_minutes' => 0,
                'total_overtime_minutes' => 0,
                'total_salary_deduction' => 0,
                'total_overtime_bonus' => 0,
                'net_salary_impact' => 0
            ];
            
            foreach ($attendanceData as &$record) {
                $status = $record['final_status'];
                
                switch ($status) {
                    case 'present':
                        $summary['present']++;
                        break;
                    case 'late':
                        $summary['late']++;
                        $summary['present']++; // Late is also present
                        break;
                    case 'absent':
                        $summary['absent']++;
                        break;
                }
                
                if ($record['hours_worked']) {
                    $summary['total_hours_worked'] += $record['hours_worked'];
                }
                if ($record['late_minutes']) {
                    $summary['total_late_minutes'] += $record['late_minutes'];
                }
                if ($record['overtime_minutes']) {
                    $summary['total_overtime_minutes'] += $record['overtime_minutes'];
                }
                if ($record['salary_deduction']) {
                    $summary['total_salary_deduction'] += $record['salary_deduction'];
                }
                if ($record['overtime_bonus']) {
                    $summary['total_overtime_bonus'] += $record['overtime_bonus'];
                }
                
                // Calculate net salary impact for this employee
                $record['net_salary_impact'] = ($record['overtime_bonus'] ?? 0) - ($record['salary_deduction'] ?? 0);
            }
            
            $summary['net_salary_impact'] = $summary['total_overtime_bonus'] - $summary['total_salary_deduction'];
            $summary['attendance_percentage'] = $summary['total_employees'] > 0 
                ? round(($summary['present'] / $summary['total_employees']) * 100, 2) 
                : 0;
            
            return Response::success([
                'date' => $date,
                'summary' => $summary,
                'attendance_data' => $attendanceData
            ], 'Daily report retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Daily Report Error: " . $e->getMessage());
            return Response::error('Failed to generate daily report', null, 500);
        }
    }
    
    /**
     * Get weekly attendance report
     */
    public function getWeeklyAttendanceReport() {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('sunday this week'));
            $departmentId = $_GET['department_id'] ?? null;
            
            // Validate date formats
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                return Response::error('Invalid date format. Use YYYY-MM-DD', null, 400);
            }
            
            $whereClause = "WHERE a.date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
            
            if ($departmentId) {
                $whereClause .= " AND e.department_id = ?";
                $params[] = $departmentId;
            }
            
            // Check permissions for employee role
            if ($this->user['role'] === 'employee') {
                $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ?");
                $stmt->execute([$this->user['id']]);
                $emp = $stmt->fetch();
                if ($emp) {
                    $whereClause .= " AND e.id = ?";
                    $params[] = $emp['id'];
                }
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    e.id as employee_id,
                    e.employee_id as emp_code,
                    e.first_name,
                    e.last_name,
                    d.name as department,
                    COUNT(a.id) as days_worked,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as days_present,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as days_late,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as days_absent,
                    SUM(a.hours_worked) as total_hours,
                    SUM(a.late_minutes) as total_late_minutes,
                    SUM(a.overtime_minutes) as total_overtime_minutes,
                    SUM(a.salary_deduction) as total_deduction,
                    SUM(a.overtime_bonus) as total_bonus,
                    (SUM(a.overtime_bonus) - SUM(a.salary_deduction)) as net_impact
                FROM employees e
                LEFT JOIN attendance a ON e.id = a.employee_id
                LEFT JOIN departments d ON e.department_id = d.id
                {$whereClause}
                AND e.status = 'active'
                GROUP BY e.id, e.employee_id, e.first_name, e.last_name, d.name
                ORDER BY d.name, e.first_name, e.last_name
            ");
            
            $stmt->execute($params);
            $weeklyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate working days in the period
            $workingDays = $this->calculateWorkingDays($startDate, $endDate);
            
            // Calculate summary
            $summary = [
                'period' => $startDate . ' to ' . $endDate,
                'working_days' => $workingDays,
                'total_employees' => count($weeklyData),
                'total_hours_worked' => 0,
                'total_late_minutes' => 0,
                'total_overtime_minutes' => 0,
                'total_salary_deduction' => 0,
                'total_overtime_bonus' => 0,
                'net_salary_impact' => 0,
                'average_attendance_rate' => 0
            ];
            
            $totalAttendanceRate = 0;
            foreach ($weeklyData as &$record) {
                $summary['total_hours_worked'] += $record['total_hours'] ?? 0;
                $summary['total_late_minutes'] += $record['total_late_minutes'] ?? 0;
                $summary['total_overtime_minutes'] += $record['total_overtime_minutes'] ?? 0;
                $summary['total_salary_deduction'] += $record['total_deduction'] ?? 0;
                $summary['total_overtime_bonus'] += $record['total_bonus'] ?? 0;
                
                // Calculate attendance rate for this employee
                $attendanceRate = $workingDays > 0 
                    ? round((($record['days_present'] + $record['days_late']) / $workingDays) * 100, 2)
                    : 0;
                $record['attendance_rate'] = $attendanceRate;
                $totalAttendanceRate += $attendanceRate;
            }
            
            $summary['net_salary_impact'] = $summary['total_overtime_bonus'] - $summary['total_salary_deduction'];
            $summary['average_attendance_rate'] = count($weeklyData) > 0 
                ? round($totalAttendanceRate / count($weeklyData), 2) 
                : 0;
            
            return Response::success([
                'summary' => $summary,
                'weekly_data' => $weeklyData
            ], 'Weekly report retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Weekly Report Error: " . $e->getMessage());
            return Response::error('Failed to generate weekly report', null, 500);
        }
    }
    
    /**
     * Get monthly attendance report
     */
    public function getMonthlyAttendanceReport() {
        try {
            $month = $_GET['month'] ?? date('m');
            $year = $_GET['year'] ?? date('Y');
            $departmentId = $_GET['department_id'] ?? null;
            
            // Validate month and year
            if (!is_numeric($month) || $month < 1 || $month > 12) {
                return Response::error('Invalid month. Use 1-12', null, 400);
            }
            if (!is_numeric($year) || $year < 2000 || $year > 2100) {
                return Response::error('Invalid year', null, 400);
            }
            
            $whereClause = "WHERE MONTH(a.date) = ? AND YEAR(a.date) = ?";
            $params = [$month, $year];
            
            if ($departmentId) {
                $whereClause .= " AND e.department_id = ?";
                $params[] = $departmentId;
            }
            
            // Check permissions for employee role
            if ($this->user['role'] === 'employee') {
                $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ?");
                $stmt->execute([$this->user['id']]);
                $emp = $stmt->fetch();
                if ($emp) {
                    $whereClause .= " AND e.id = ?";
                    $params[] = $emp['id'];
                }
            }
            
            // Get monthly data
            $stmt = $this->db->prepare("
                SELECT 
                    e.id as employee_id,
                    e.employee_id as emp_code,
                    e.first_name,
                    e.last_name,
                    d.name as department,
                    p.title as position,
                    COUNT(a.id) as total_working_days,
                    SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) as days_attended,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as days_on_time,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as days_late,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as days_absent,
                    SUM(a.hours_worked) as total_hours_worked,
                    AVG(a.hours_worked) as avg_daily_hours,
                    SUM(a.late_minutes) as total_late_minutes,
                    AVG(CASE WHEN a.late_minutes > 0 THEN a.late_minutes END) as avg_late_minutes,
                    SUM(a.overtime_minutes) as total_overtime_minutes,
                    SUM(a.salary_deduction) as total_salary_deduction,
                    SUM(a.overtime_bonus) as total_overtime_bonus,
                    (SUM(a.overtime_bonus) - SUM(a.salary_deduction)) as net_salary_impact,
                    MIN(a.date) as first_attendance,
                    MAX(a.date) as last_attendance
                FROM employees e
                LEFT JOIN attendance a ON e.id = a.employee_id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                {$whereClause}
                AND e.status = 'active'
                GROUP BY e.id, e.employee_id, e.first_name, e.last_name, d.name, p.title
                ORDER BY d.name, e.first_name, e.last_name
            ");
            
            $stmt->execute($params);
            $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate working days in the month
            $workingDays = $this->calculateWorkingDaysInMonth($month, $year);
            
            // Calculate summary and additional metrics
            $summary = [
                'month' => $month,
                'year' => $year,
                'working_days' => $workingDays,
                'total_employees' => count($monthlyData),
                'total_hours_worked' => 0,
                'total_late_minutes' => 0,
                'total_overtime_minutes' => 0,
                'total_salary_deduction' => 0,
                'total_overtime_bonus' => 0,
                'net_salary_impact' => 0,
                'average_attendance_rate' => 0,
                'punctuality_rate' => 0
            ];
            
            $totalAttendanceRate = 0;
            $totalPunctualityRate = 0;
            
            foreach ($monthlyData as &$record) {
                // Calculate rates and percentages
                $attendanceRate = $workingDays > 0 
                    ? round(($record['days_attended'] / $workingDays) * 100, 2)
                    : 0;
                $punctualityRate = $record['days_attended'] > 0 
                    ? round(($record['days_on_time'] / $record['days_attended']) * 100, 2)
                    : 0;
                
                $record['attendance_rate'] = $attendanceRate;
                $record['punctuality_rate'] = $punctualityRate;
                $record['avg_daily_hours'] = round($record['avg_daily_hours'] ?? 0, 2);
                $record['avg_late_minutes'] = round($record['avg_late_minutes'] ?? 0, 1);
                
                // Add to summary totals
                $summary['total_hours_worked'] += $record['total_hours_worked'] ?? 0;
                $summary['total_late_minutes'] += $record['total_late_minutes'] ?? 0;
                $summary['total_overtime_minutes'] += $record['total_overtime_minutes'] ?? 0;
                $summary['total_salary_deduction'] += $record['total_salary_deduction'] ?? 0;
                $summary['total_overtime_bonus'] += $record['total_overtime_bonus'] ?? 0;
                
                $totalAttendanceRate += $attendanceRate;
                $totalPunctualityRate += $punctualityRate;
            }
            
            $summary['net_salary_impact'] = $summary['total_overtime_bonus'] - $summary['total_salary_deduction'];
            $summary['average_attendance_rate'] = count($monthlyData) > 0 
                ? round($totalAttendanceRate / count($monthlyData), 2) 
                : 0;
            $summary['punctuality_rate'] = count($monthlyData) > 0 
                ? round($totalPunctualityRate / count($monthlyData), 2) 
                : 0;
            
            return Response::success([
                'summary' => $summary,
                'monthly_data' => $monthlyData
            ], 'Monthly report retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Monthly Report Error: " . $e->getMessage());
            return Response::error('Failed to generate monthly report', null, 500);
        }
    }
    
    /**
     * Export attendance data with salary impact
     */
    public function exportAttendanceData() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $format = $_GET['format'] ?? 'csv';
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            $departmentId = $_GET['department_id'] ?? null;
            
            if (!in_array($format, ['csv', 'excel', 'pdf'])) {
                return Response::error('Invalid export format. Use csv, excel, or pdf', null, 400);
            }
            
            // Get export data
            $whereClause = "WHERE a.date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
            
            if ($departmentId) {
                $whereClause .= " AND e.department_id = ?";
                $params[] = $departmentId;
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    e.employee_id as 'Employee ID',
                    CONCAT(e.first_name, ' ', e.last_name) as 'Employee Name',
                    d.name as 'Department',
                    p.title as 'Position',
                    a.date as 'Date',
                    a.clock_in as 'Clock In',
                    a.clock_out as 'Clock Out',
                    a.scheduled_clock_in as 'Scheduled In',
                    a.scheduled_clock_out as 'Scheduled Out',
                    a.hours_worked as 'Hours Worked',
                    a.late_minutes as 'Late Minutes',
                    a.overtime_minutes as 'Overtime Minutes',
                    a.salary_deduction as 'Salary Deduction',
                    a.overtime_bonus as 'Overtime Bonus',
                    (a.overtime_bonus - a.salary_deduction) as 'Net Impact',
                    a.status as 'Status',
                    a.attendance_type as 'Attendance Type',
                    a.notes as 'Notes'
                FROM attendance a
                JOIN employees e ON a.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                {$whereClause}
                ORDER BY a.date, e.first_name, e.last_name
            ");
            
            $stmt->execute($params);
            $exportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate export file
            $exportResult = $this->generateExportFile($exportData, $format, $startDate, $endDate);
            
            // Log activity
            $this->logActivity($this->user['id'], 'export_attendance_data', 
                "Exported attendance data ({$format}) for period {$startDate} to {$endDate}");
            
            return Response::success([
                'export_url' => $exportResult['url'],
                'filename' => $exportResult['filename'],
                'record_count' => count($exportData)
            ], 'Attendance data exported successfully');
            
        } catch (Exception $e) {
            error_log("Export Attendance Data Error: " . $e->getMessage());
            return Response::error('Failed to export attendance data', null, 500);
        }
    }
    
    // Helper Methods for Attendance Reports
    
    private function calculateWorkingDays($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $workingDays = 0;
        
        while ($start <= $end) {
            $dayOfWeek = $start->format('N'); // 1 = Monday, 7 = Sunday
            if ($dayOfWeek < 6) { // Monday to Friday
                $workingDays++;
            }
            $start->add(new DateInterval('P1D'));
        }
        
        return $workingDays;
    }
    
    private function calculateWorkingDaysInMonth($month, $year) {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        return $this->calculateWorkingDays($startDate, $endDate);
    }
    
    private function generateExportFile($data, $format, $startDate, $endDate) {
        $filename = "attendance_report_{$startDate}_to_{$endDate}.{$format}";
        $url = "/downloads/reports/{$filename}";
        
        // In a real implementation, you would:
        // 1. Generate the actual file based on format (CSV, Excel, PDF)
        // 2. Save to file system or return as download
        // 3. Use appropriate libraries (PhpSpreadsheet for Excel, TCPDF for PDF)
        
        return [
            'url' => $url,
            'filename' => $filename
        ];
    }
    
    private function logActivity($userId, $action, $description) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_activities (user_id, action, description, ip_address) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $action, $description, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        } catch (Exception $e) {
            error_log("Activity Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get recruitment statistics
     */
    private function getRecruitmentStatistics($year, $month) {
        // Implementation for recruitment statistics
        return [];
    }
    
    /**
     * Get performance statistics
     */
    private function getPerformanceStatistics($year) {
        // Implementation for performance statistics
        return [];
    }
    
    /**
     * Get training statistics
     */
    private function getTrainingStatistics($year, $month) {
        // Implementation for training statistics
        return [];
    }
    

    

    
    /**
     * Get employee statistics
     */
    private function getEmployeeStatistics($year, $month) {
        try {
            $stats = $this->db->prepare(
                "SELECT 
                    COUNT(*) as total_employees,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_employees,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_employees,
                    COUNT(DISTINCT department_id) as total_departments
                 FROM employees"
            );
            $stats->execute();
            $result = $stats->fetch();
            
            return [
                'total_employees' => (int)$result['total_employees'],
                'active_employees' => (int)$result['active_employees'],
                'inactive_employees' => (int)$result['inactive_employees'],
                'total_departments' => (int)$result['total_departments']
            ];
        } catch (Exception $e) {
            error_log('Employee statistics error: ' . $e->getMessage());
            return [
                'total_employees' => 0,
                'active_employees' => 0,
                'inactive_employees' => 0,
                'total_departments' => 0
            ];
        }
    }
    
    /**
     * Get attendance statistics
     */
    private function getAttendanceStatistics($year, $month) {
        try {
            $stats = $this->db->prepare(
                "SELECT 
                    COUNT(*) as total_records,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                    AVG(hours_worked) as avg_hours_worked
                 FROM attendance 
                 WHERE YEAR(date) = ? AND MONTH(date) = ?"
            );
            $stats->execute([$year, $month]);
            $result = $stats->fetch();
            
            return [
                'total_records' => (int)$result['total_records'],
                'present_count' => (int)$result['present_count'],
                'absent_count' => (int)$result['absent_count'],
                'late_count' => (int)$result['late_count'],
                'avg_hours_worked' => round((float)$result['avg_hours_worked'], 2),
                'attendance_rate' => $result['total_records'] > 0 ? round(($result['present_count'] + $result['late_count']) / $result['total_records'] * 100, 2) : 0
            ];
        } catch (Exception $e) {
            error_log('Attendance statistics error: ' . $e->getMessage());
            return [
                'total_records' => 0,
                'present_count' => 0,
                'absent_count' => 0,
                'late_count' => 0,
                'avg_hours_worked' => 0,
                'attendance_rate' => 0
            ];
        }
    }
    
    /**
     * Get leave statistics
     */
    private function getLeaveStatistics($year, $month) {
        try {
            $stats = $this->db->prepare(
                "SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
                    SUM(CASE WHEN status = 'approved' THEN days_requested ELSE 0 END) as total_days_taken
                 FROM leave_requests 
                 WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?"
            );
            $stats->execute([$year, $month]);
            $result = $stats->fetch();
            
            return [
                'total_requests' => (int)$result['total_requests'],
                'approved_requests' => (int)$result['approved_requests'],
                'pending_requests' => (int)$result['pending_requests'],
                'rejected_requests' => (int)$result['rejected_requests'],
                'total_days_taken' => (int)$result['total_days_taken'],
                'approval_rate' => $result['total_requests'] > 0 ? round($result['approved_requests'] / $result['total_requests'] * 100, 2) : 0
            ];
        } catch (Exception $e) {
            error_log('Leave statistics error: ' . $e->getMessage());
            return [
                'total_requests' => 0,
                'approved_requests' => 0,
                'pending_requests' => 0,
                'rejected_requests' => 0,
                'total_days_taken' => 0,
                'approval_rate' => 0
            ];
        }
    }
    

    
    /**
     * Get employee headcount report
     */
    public function getHeadcountReport() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $params = Router::getQueryParams();
            $year = $params['year'] ?? date('Y');
            $groupBy = $params['group_by'] ?? 'department'; // department, position, gender, age_group
            
            // Current headcount
            $currentHeadcount = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM employees WHERE status = 'active'"
            );
            
            // Headcount by group
            $headcountByGroup = [];
            
            switch ($groupBy) {
                case 'department':
                    $headcountByGroup = $this->db->select(
                        "SELECT d.name as group_name, COUNT(e.id) as count
                         FROM departments d
                         LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
                         GROUP BY d.id, d.name
                         ORDER BY count DESC"
                    );
                    break;
                    
                case 'position':
                    $headcountByGroup = $this->db->select(
                        "SELECT p.title as group_name, COUNT(e.id) as count
                         FROM positions p
                         LEFT JOIN employees e ON p.id = e.position_id AND e.status = 'active'
                         GROUP BY p.id, p.title
                         ORDER BY count DESC"
                    );
                    break;
                    
                case 'gender':
                    $headcountByGroup = $this->db->select(
                        "SELECT 
                            CASE 
                                WHEN gender = 'male' THEN 'Male'
                                WHEN gender = 'female' THEN 'Female'
                                ELSE 'Other'
                            END as group_name,
                            COUNT(*) as count
                         FROM employees 
                         WHERE status = 'active'
                         GROUP BY gender
                         ORDER BY count DESC"
                    );
                    break;
                    
                case 'age_group':
                    $headcountByGroup = $this->db->select(
                        "SELECT 
                            CASE 
                                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 25 THEN 'Under 25'
                                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                                ELSE '55+'
                            END as group_name,
                            COUNT(*) as count
                         FROM employees 
                         WHERE status = 'active' AND date_of_birth IS NOT NULL
                         GROUP BY group_name
                         ORDER BY count DESC"
                    );
                    break;
            }
            
            // Monthly headcount trend
            $monthlyTrend = $this->db->select(
                "SELECT 
                    MONTH(hire_date) as month,
                    COUNT(*) as hires,
                    (SELECT COUNT(*) FROM employees e2 
                     WHERE e2.termination_date IS NOT NULL 
                     AND MONTH(e2.termination_date) = MONTH(e1.hire_date)
                     AND YEAR(e2.termination_date) = ?) as terminations
                 FROM employees e1
                 WHERE YEAR(hire_date) = ?
                 GROUP BY MONTH(hire_date)
                 ORDER BY month",
                [$year, $year]
            );
            
            // Calculate net change for each month
            foreach ($monthlyTrend as &$trend) {
                $trend['net_change'] = $trend['hires'] - $trend['terminations'];
                $trend['month_name'] = date('F', mktime(0, 0, 0, $trend['month'], 1));
            }
            
            $report = [
                'current_headcount' => (int)$currentHeadcount['total'],
                'group_by' => $groupBy,
                'headcount_by_group' => $headcountByGroup,
                'monthly_trend' => $monthlyTrend,
                'year' => $year,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            Response::success($report);
            
        } catch (Exception $e) {
            Response::serverError('Failed to generate headcount report: ' . $e->getMessage());
        }
    }
    
    /**
     * Get attrition report
     */
    public function getAttritionReport() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $params = Router::getQueryParams();
            $year = $params['year'] ?? date('Y');
            $period = $params['period'] ?? 'yearly'; // monthly, quarterly, yearly
            
            // Overall attrition statistics
            $totalEmployees = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM employees WHERE YEAR(hire_date) <= ?",
                [$year]
            );
            
            $terminations = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM employees 
                 WHERE termination_date IS NOT NULL AND YEAR(termination_date) = ?",
                [$year]
            );
            
            $attritionRate = $totalEmployees['total'] > 0 ? 
                round(($terminations['total'] / $totalEmployees['total']) * 100, 2) : 0;
            
            // Attrition by department
            $departmentAttrition = $this->db->select(
                "SELECT d.name as department_name,
                        COUNT(CASE WHEN e.termination_date IS NULL THEN 1 END) as active_employees,
                        COUNT(CASE WHEN YEAR(e.termination_date) = ? THEN 1 END) as terminations,
                        ROUND(
                            (COUNT(CASE WHEN YEAR(e.termination_date) = ? THEN 1 END) / 
                             NULLIF(COUNT(CASE WHEN e.termination_date IS NULL OR YEAR(e.termination_date) = ? THEN 1 END), 0)) * 100, 2
                        ) as attrition_rate
                 FROM departments d
                 LEFT JOIN employees e ON d.id = e.department_id
                 GROUP BY d.id, d.name
                 ORDER BY attrition_rate DESC",
                [$year, $year, $year]
            );
            
            // Attrition reasons
            $attritionReasons = $this->db->select(
                "SELECT 
                    COALESCE(termination_reason, 'Not Specified') as reason,
                    COUNT(*) as count,
                    ROUND((COUNT(*) / ?) * 100, 2) as percentage
                 FROM employees 
                 WHERE termination_date IS NOT NULL AND YEAR(termination_date) = ?
                 GROUP BY termination_reason
                 ORDER BY count DESC",
                [$terminations['total'] ?: 1, $year]
            );
            
            // Tenure analysis of terminated employees
            $tenureAnalysis = $this->db->select(
                "SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(MONTH, hire_date, termination_date) < 6 THEN '0-6 months'
                        WHEN TIMESTAMPDIFF(MONTH, hire_date, termination_date) < 12 THEN '6-12 months'
                        WHEN TIMESTAMPDIFF(MONTH, hire_date, termination_date) < 24 THEN '1-2 years'
                        WHEN TIMESTAMPDIFF(MONTH, hire_date, termination_date) < 60 THEN '2-5 years'
                        ELSE '5+ years'
                    END as tenure_group,
                    COUNT(*) as count
                 FROM employees 
                 WHERE termination_date IS NOT NULL AND YEAR(termination_date) = ?
                 GROUP BY tenure_group
                 ORDER BY count DESC",
                [$year]
            );
            
            // Period-wise attrition trend
            $periodTrend = [];
            if ($period === 'monthly') {
                $periodTrend = $this->db->select(
                    "SELECT 
                        MONTH(termination_date) as period,
                        COUNT(*) as terminations
                     FROM employees 
                     WHERE termination_date IS NOT NULL AND YEAR(termination_date) = ?
                     GROUP BY MONTH(termination_date)
                     ORDER BY period",
                    [$year]
                );
                
                foreach ($periodTrend as &$trend) {
                    $trend['period_name'] = date('F', mktime(0, 0, 0, $trend['period'], 1));
                }
            }
            
            $report = [
                'overall_statistics' => [
                    'total_employees' => (int)$totalEmployees['total'],
                    'total_terminations' => (int)$terminations['total'],
                    'attrition_rate' => $attritionRate
                ],
                'department_attrition' => $departmentAttrition,
                'attrition_reasons' => $attritionReasons,
                'tenure_analysis' => $tenureAnalysis,
                'period_trend' => $periodTrend,
                'year' => $year,
                'period' => $period,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            Response::success($report);
            
        } catch (Exception $e) {
            Response::serverError('Failed to generate attrition report: ' . $e->getMessage());
        }
    }
    
    /**
     * Get leave trends report
     */
    public function getLeaveTrendsReport() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $params = Router::getQueryParams();
            $year = $params['year'] ?? date('Y');
            
            // Overall leave statistics
            $overallStats = $this->db->selectOne(
                "SELECT 
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_requests,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
                    SUM(CASE WHEN status = 'approved' THEN days_requested ELSE 0 END) as total_days_taken
                 FROM leave_requests 
                 WHERE YEAR(start_date) = ?",
                [$year]
            );
            
            // Leave by type
            $leaveByType = $this->db->select(
                "SELECT lt.name as leave_type,
                        COUNT(lr.id) as request_count,
                        SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as days_taken,
                        AVG(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE NULL END) as avg_days_per_request
                 FROM leave_types lt
                 LEFT JOIN leave_requests lr ON lt.id = lr.leave_type_id AND YEAR(lr.start_date) = ?
                 GROUP BY lt.id, lt.name
                 ORDER BY days_taken DESC",
                [$year]
            );
            
            // Monthly leave trends
            $monthlyTrends = $this->db->select(
                "SELECT 
                    MONTH(start_date) as month,
                    COUNT(*) as requests,
                    SUM(CASE WHEN status = 'approved' THEN days_requested ELSE 0 END) as days_taken
                 FROM leave_requests 
                 WHERE YEAR(start_date) = ?
                 GROUP BY MONTH(start_date)
                 ORDER BY month",
                [$year]
            );
            
            foreach ($monthlyTrends as &$trend) {
                $trend['month_name'] = date('F', mktime(0, 0, 0, $trend['month'], 1));
            }
            
            // Department-wise leave analysis
            $departmentAnalysis = $this->db->select(
                "SELECT d.name as department_name,
                        COUNT(lr.id) as total_requests,
                        SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as total_days,
                        AVG(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE NULL END) as avg_days_per_employee
                 FROM departments d
                 LEFT JOIN employees e ON d.id = e.department_id
                 LEFT JOIN leave_requests lr ON e.id = lr.employee_id AND YEAR(lr.start_date) = ?
                 GROUP BY d.id, d.name
                 ORDER BY total_days DESC",
                [$year]
            );
            
            // Top leave takers
            $topLeaveTakers = $this->db->select(
                "SELECT e.first_name, e.last_name, e.employee_id,
                        d.name as department_name,
                        COUNT(lr.id) as request_count,
                        SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as total_days
                 FROM employees e
                 LEFT JOIN departments d ON e.department_id = d.id
                 LEFT JOIN leave_requests lr ON e.id = lr.employee_id AND YEAR(lr.start_date) = ?
                 WHERE e.status = 'active'
                 GROUP BY e.id
                 HAVING total_days > 0
                 ORDER BY total_days DESC
                 LIMIT 10",
                [$year]
            );
            
            foreach ($topLeaveTakers as &$employee) {
                $employee['employee_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
                unset($employee['first_name'], $employee['last_name']);
            }
            
            $report = [
                'overall_statistics' => [
                    'total_requests' => (int)$overallStats['total_requests'],
                    'approved_requests' => (int)$overallStats['approved_requests'],
                    'rejected_requests' => (int)$overallStats['rejected_requests'],
                    'pending_requests' => (int)$overallStats['pending_requests'],
                    'total_days_taken' => (int)$overallStats['total_days_taken'],
                    'approval_rate' => $overallStats['total_requests'] > 0 ? 
                        round(($overallStats['approved_requests'] / $overallStats['total_requests']) * 100, 2) : 0
                ],
                'leave_by_type' => $leaveByType,
                'monthly_trends' => $monthlyTrends,
                'department_analysis' => $departmentAnalysis,
                'top_leave_takers' => $topLeaveTakers,
                'year' => $year,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            Response::success($report);
            
        } catch (Exception $e) {
            Response::serverError('Failed to generate leave trends report: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate custom MIS report
     */
    public function generateMISReport() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'report_type' => 'required|in:employee,attendance,payroll,leave,performance,recruitment,training',
                'date_from' => 'required|date',
                'date_to' => 'required|date',
                'format' => 'in:json,csv,pdf',
                'filters' => 'array'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            $reportType = $data['report_type'];
            $dateFrom = $data['date_from'];
            $dateTo = $data['date_to'];
            $format = $data['format'] ?? 'json';
            $filters = $data['filters'] ?? [];
            
            $reportData = [];
            
            switch ($reportType) {
                case 'employee':
                    $reportData = $this->generateEmployeeMISReport($dateFrom, $dateTo, $filters);
                    break;
                case 'attendance':
                    $reportData = $this->generateAttendanceMISReport($dateFrom, $dateTo, $filters);
                    break;
                case 'payroll':
                    $reportData = $this->generatePayrollMISReport($dateFrom, $dateTo, $filters);
                    break;
                case 'leave':
                    $reportData = $this->generateLeaveMISReport($dateFrom, $dateTo, $filters);
                    break;
                case 'performance':
                    $reportData = $this->generatePerformanceMISReport($dateFrom, $dateTo, $filters);
                    break;
                case 'recruitment':
                    $reportData = $this->generateRecruitmentMISReport($dateFrom, $dateTo, $filters);
                    break;
                case 'training':
                    $reportData = $this->generateTrainingMISReport($dateFrom, $dateTo, $filters);
                    break;
            }
            
            $report = [
                'report_type' => $reportType,
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ],
                'filters_applied' => $filters,
                'data' => $reportData,
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $this->user['first_name'] . ' ' . $this->user['last_name']
            ];
            
            if ($format === 'csv') {
                $this->downloadCSVReport($report);
            } else {
                Response::success($report);
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to generate MIS report: ' . $e->getMessage());
        }
    }
    
    // Helper methods for statistics
    
    private function getEmployeeStats($year, $month) {
        $stats = $this->db->selectOne(
            "SELECT 
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_employees,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_employees,
                COUNT(CASE WHEN YEAR(hire_date) = ? AND MONTH(hire_date) = ? THEN 1 END) as new_hires,
                COUNT(CASE WHEN termination_date IS NOT NULL AND YEAR(termination_date) = ? AND MONTH(termination_date) = ? THEN 1 END) as terminations
             FROM employees",
            [$year, $month, $year, $month]
        );
        
        return [
            'active_employees' => (int)$stats['active_employees'],
            'inactive_employees' => (int)$stats['inactive_employees'],
            'new_hires' => (int)$stats['new_hires'],
            'terminations' => (int)$stats['terminations'],
            'net_change' => (int)$stats['new_hires'] - (int)$stats['terminations']
        ];
    }
    
    private function calculateAttendanceStatistics($year, $month) {
        $stats = $this->db->selectOne(
            "SELECT 
                COUNT(*) as total_records,
                AVG(hours_worked) as avg_hours_worked,
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as late_arrivals
             FROM attendance 
             WHERE YEAR(date) = ? AND MONTH(date) = ?",
            [$year, $month]
        );
        
        return [
            'total_records' => (int)$stats['total_records'],
            'average_hours_worked' => round((float)$stats['avg_hours_worked'], 2),
            'present_days' => (int)$stats['present_days'],
            'absent_days' => (int)$stats['absent_days'],
            'late_arrivals' => (int)$stats['late_arrivals'],
            'attendance_rate' => $stats['total_records'] > 0 ? 
                round(($stats['present_days'] / $stats['total_records']) * 100, 2) : 0
        ];
    }
    
    private function calculateLeaveStatistics($year, $month) {
        $stats = $this->db->selectOne(
            "SELECT 
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
                SUM(CASE WHEN status = 'approved' THEN days_requested ELSE 0 END) as total_days_approved
             FROM leave_requests 
             WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?",
            [$year, $month]
        );
        
        return [
            'total_requests' => (int)$stats['total_requests'],
            'approved_requests' => (int)$stats['approved_requests'],
            'pending_requests' => (int)$stats['pending_requests'],
            'total_days_approved' => (int)$stats['total_days_approved'],
            'approval_rate' => $stats['total_requests'] > 0 ? 
                round(($stats['approved_requests'] / $stats['total_requests']) * 100, 2) : 0
        ];
    }
    
    private function getPayrollStatistics($year, $month) {
        $stats = $this->db->selectOne(
            "SELECT 
                COUNT(*) as total_payrolls,
                SUM(gross_salary) as total_gross,
                SUM(net_salary) as total_net,
                AVG(gross_salary) as avg_gross_salary,
                AVG(net_salary) as avg_net_salary
             FROM payroll 
             WHERE pay_year = ? AND pay_month = ?",
            [$year, $month]
        );
        
        return [
            'total_payrolls' => (int)$stats['total_payrolls'],
            'total_gross_salary' => round((float)$stats['total_gross'], 2),
            'total_net_salary' => round((float)$stats['total_net'], 2),
            'average_gross_salary' => round((float)$stats['avg_gross_salary'], 2),
            'average_net_salary' => round((float)$stats['avg_net_salary'], 2)
        ];
    }
    
    private function getRecruitmentStats($year, $month) {
        $stats = $this->db->selectOne(
            "SELECT 
                COUNT(DISTINCT jp.id) as total_job_postings,
                COUNT(ja.id) as total_applications,
                COUNT(CASE WHEN ja.status = 'hired' THEN 1 END) as successful_hires
             FROM job_postings jp
             LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
             WHERE YEAR(jp.created_at) = ? AND MONTH(jp.created_at) = ?",
            [$year, $month]
        );
        
        return [
            'total_job_postings' => (int)$stats['total_job_postings'],
            'total_applications' => (int)$stats['total_applications'],
            'successful_hires' => (int)$stats['successful_hires'],
            'hire_rate' => $stats['total_applications'] > 0 ? 
                round(($stats['successful_hires'] / $stats['total_applications']) * 100, 2) : 0
        ];
    }
    
    private function getPerformanceStats($year) {
        $stats = $this->db->selectOne(
            "SELECT 
                COUNT(*) as total_reviews,
                AVG(overall_rating) as avg_rating,
                COUNT(CASE WHEN overall_rating >= 4 THEN 1 END) as high_performers
             FROM performance_reviews 
             WHERE review_year = ?",
            [$year]
        );
        
        return [
            'total_reviews' => (int)$stats['total_reviews'],
            'average_rating' => round((float)$stats['avg_rating'], 2),
            'high_performers' => (int)$stats['high_performers'],
            'high_performer_rate' => $stats['total_reviews'] > 0 ? 
                round(($stats['high_performers'] / $stats['total_reviews']) * 100, 2) : 0
        ];
    }
    
    private function getTrainingStats($year, $month) {
        // Create training tables if they don't exist
        $this->createTrainingTablesIfNotExist();
        
        $stats = $this->db->selectOne(
            "SELECT 
                COUNT(DISTINCT tp.id) as total_programs,
                COUNT(te.id) as total_enrollments,
                COUNT(CASE WHEN te.status = 'completed' THEN 1 END) as completed_trainings
             FROM training_programs tp
             LEFT JOIN training_enrollments te ON tp.id = te.program_id
             WHERE YEAR(tp.created_at) = ? AND MONTH(tp.created_at) = ?",
            [$year, $month]
        );
        
        return [
            'total_programs' => (int)$stats['total_programs'],
            'total_enrollments' => (int)$stats['total_enrollments'],
            'completed_trainings' => (int)$stats['completed_trainings'],
            'completion_rate' => $stats['total_enrollments'] > 0 ? 
                round(($stats['completed_trainings'] / $stats['total_enrollments']) * 100, 2) : 0
        ];
    }
    
    private function getRecentActivities() {
        $activities = [];
        
        try {
            // Recent hires (last 30 days)
            $recentHires = $this->db->select(
                "SELECT 'hire' as type, 
                        CONCAT(first_name, ' ', last_name, ' joined as ', p.title) as description, 
                        hire_date as date
                 FROM employees e
                 LEFT JOIN positions p ON e.position_id = p.id
                 WHERE hire_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 ORDER BY hire_date DESC
                 LIMIT 3"
            );
            
            // Recent leave requests (last 7 days)
            $recentLeaves = $this->db->select(
                "SELECT 'leave' as type, 
                        CONCAT(e.first_name, ' ', e.last_name, ' requested ', lt.name, ' leave') as description,
                        lr.created_at as date
                 FROM leave_requests lr
                 JOIN employees e ON lr.employee_id = e.id
                 JOIN leave_types lt ON lr.leave_type_id = lt.id
                 WHERE lr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 ORDER BY lr.created_at DESC
                 LIMIT 3"
            );
            
            // Recent attendance (today's check-ins)
            $recentAttendance = $this->db->select(
                "SELECT 'attendance' as type,
                        CONCAT(e.first_name, ' ', e.last_name, ' checked in at ', TIME_FORMAT(a.check_in, '%H:%i')) as description,
                        CONCAT(CURDATE(), ' ', a.check_in) as date
                 FROM attendance a
                 JOIN employees e ON a.employee_id = e.id
                 WHERE a.date = CURDATE() AND a.check_in IS NOT NULL
                 ORDER BY a.check_in DESC
                 LIMIT 2"
            );
            
            // Recent payroll processing (last 30 days)
            $recentPayroll = $this->db->select(
                "SELECT 'payroll' as type,
                        CONCAT('Payroll processed for ', DATE_FORMAT(pay_period_start, '%M %Y')) as description,
                        created_at as date
                 FROM payroll
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 ORDER BY created_at DESC
                 LIMIT 2"
            );
            
            $activities = array_merge($recentHires, $recentLeaves, $recentAttendance, $recentPayroll);
            
            // Sort by date (most recent first)
            usort($activities, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            return array_slice($activities, 0, 8);
            
        } catch (Exception $e) {
            error_log("Recent Activities Error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getHRAlerts() {
        $alerts = [];
        
        // Pending leave requests
        $pendingLeaves = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'"
        );
        
        if ($pendingLeaves['count'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => $pendingLeaves['count'] . ' pending leave requests require approval',
                'action_url' => '/leave/requests?status=pending'
            ];
        }
        
        // Employees with no attendance today
        $absentToday = $this->db->selectOne(
            "SELECT COUNT(*) as count 
             FROM employees e
             LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = CURDATE()
             WHERE e.status = 'active' AND a.id IS NULL"
        );
        
        if ($absentToday['count'] > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => $absentToday['count'] . ' employees have not marked attendance today',
                'action_url' => '/attendance/today'
            ];
        }
        
        // Upcoming performance reviews
        $upcomingReviews = $this->db->selectOne(
            "SELECT COUNT(*) as count 
             FROM employees 
             WHERE status = 'active' 
             AND id NOT IN (
                 SELECT employee_id FROM performance_reviews 
                 WHERE review_year = YEAR(CURDATE())
             )"
        );
        
        if ($upcomingReviews['count'] > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => $upcomingReviews['count'] . ' employees pending performance review',
                'action_url' => '/performance/reviews'
            ];
        }
        
        return $alerts;
    }
    
    // MIS Report generators
    
    private function generateEmployeeMISReport($dateFrom, $dateTo, $filters) {
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "e.status = ?";
            $params[] = $filters['status'];
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        return $this->db->select(
            "SELECT e.employee_id, e.first_name, e.last_name, e.email, e.phone,
                    d.name as department, p.title as position, e.hire_date, e.status
             FROM employees e
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN positions p ON e.position_id = p.id
             $whereClause
             ORDER BY e.employee_id",
            $params
        );
    }
    
    private function generateAttendanceMISReport($dateFrom, $dateTo, $filters) {
        $whereConditions = ["a.date BETWEEN ? AND ?"];
        $params = [$dateFrom, $dateTo];
        
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        return $this->db->select(
            "SELECT e.employee_id, e.first_name, e.last_name, d.name as department,
                    a.date, a.check_in_time, a.check_out_time, a.hours_worked, a.status
             FROM attendance a
             JOIN employees e ON a.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             $whereClause
             ORDER BY a.date DESC, e.employee_id",
            $params
        );
    }
    
    private function generatePayrollMISReport($dateFrom, $dateTo, $filters) {
        $whereConditions = ["STR_TO_DATE(CONCAT(p.pay_year, '-', p.pay_month, '-01'), '%Y-%m-%d') BETWEEN ? AND ?"];
        $params = [$dateFrom, $dateTo];
        
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        return $this->db->select(
            "SELECT e.employee_id, e.first_name, e.last_name, d.name as department,
                    p.pay_year, p.pay_month, p.basic_salary, p.gross_salary, p.net_salary,
                    p.pf_deduction, p.esi_deduction, p.tds_deduction, p.professional_tax
             FROM payroll p
             JOIN employees e ON p.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             $whereClause
             ORDER BY p.pay_year DESC, p.pay_month DESC, e.employee_id",
            $params
        );
    }
    
    private function generateLeaveMISReport($dateFrom, $dateTo, $filters) {
        $whereConditions = ["lr.start_date BETWEEN ? AND ?"];
        $params = [$dateFrom, $dateTo];
        
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        if (!empty($filters['leave_type_id'])) {
            $whereConditions[] = "lr.leave_type_id = ?";
            $params[] = $filters['leave_type_id'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        return $this->db->select(
            "SELECT e.employee_id, e.first_name, e.last_name, d.name as department,
                    lt.name as leave_type, lr.start_date, lr.end_date, lr.days_requested,
                    lr.reason, lr.status, lr.approved_by
             FROM leave_requests lr
             JOIN employees e ON lr.employee_id = e.id
             JOIN leave_types lt ON lr.leave_type_id = lt.id
             LEFT JOIN departments d ON e.department_id = d.id
             $whereClause
             ORDER BY lr.start_date DESC",
            $params
        );
    }
    
    private function generatePerformanceMISReport($dateFrom, $dateTo, $filters) {
        $whereConditions = ["pr.review_date BETWEEN ? AND ?"];
        $params = [$dateFrom, $dateTo];
        
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        return $this->db->select(
            "SELECT e.employee_id, e.first_name, e.last_name, d.name as department,
                    pr.review_year, pr.review_period, pr.overall_rating, pr.goals_achievement,
                    pr.strengths, pr.areas_for_improvement
             FROM performance_reviews pr
             JOIN employees e ON pr.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             $whereClause
             ORDER BY pr.review_date DESC",
            $params
        );
    }
    
    private function generateRecruitmentMISReport($dateFrom, $dateTo, $filters) {
        $whereConditions = ["jp.created_at BETWEEN ? AND ?"];
        $params = [$dateFrom, $dateTo];
        
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "jp.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        return $this->db->select(
            "SELECT jp.title as job_title, d.name as department, jp.status as job_status,
                    ja.applicant_name, ja.email, ja.phone, ja.status as application_status,
                    ja.applied_at
             FROM job_postings jp
             LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
             LEFT JOIN departments d ON jp.department_id = d.id
             $whereClause
             ORDER BY jp.created_at DESC",
            $params
        );
    }
    
    private function generateTrainingMISReport($dateFrom, $dateTo, $filters) {
        $this->createTrainingTablesIfNotExist();
        
        $whereConditions = ["te.enrolled_at BETWEEN ? AND ?"];
        $params = [$dateFrom, $dateTo];
        
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        return $this->db->select(
            "SELECT e.employee_id, e.first_name, e.last_name, d.name as department,
                    tp.title as training_program, tp.category, te.enrolled_at, te.completed_at,
                    te.progress_percentage, te.status, te.rating
             FROM training_enrollments te
             JOIN employees e ON te.employee_id = e.id
             JOIN training_programs tp ON te.program_id = tp.id
             LEFT JOIN departments d ON e.department_id = d.id
             $whereClause
             ORDER BY te.enrolled_at DESC",
            $params
        );
    }
    
    private function downloadCSVReport($report) {
        $filename = $report['report_type'] . '_report_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($report['data'])) {
            // Write headers
            fputcsv($output, array_keys($report['data'][0]));
            
            // Write data
            foreach ($report['data'] as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
    
    private function createTrainingTablesIfNotExist() {
        $query1 = "CREATE TABLE IF NOT EXISTS training_programs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            category VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $query2 = "CREATE TABLE IF NOT EXISTS training_enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            program_id INT,
            employee_id INT,
            enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            progress_percentage DECIMAL(5,2) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'enrolled',
            rating DECIMAL(3,2)
        )";
        
        $this->db->execute($query1);
        $this->db->execute($query2);
    }
}

?>