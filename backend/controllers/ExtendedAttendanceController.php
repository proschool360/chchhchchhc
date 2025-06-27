<?php
/**
 * Extended Attendance Controller
 * Handles advanced attendance management operations including QR, RFID, Biometric
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class ExtendedAttendanceController {
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
     * Clock in via QR code
     */
    public function clockInQR() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['qr_code', 'device_id'])
                     ->string('qr_code')
                     ->string('device_id');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            // Find employee by QR code
            $stmt = $this->db->prepare("
                SELECT e.*, u.username 
                FROM employees e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.qr_code = ? AND e.status = 'active'
            ");
            $stmt->execute([$data['qr_code']]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                return Response::error('Invalid QR code or employee not found', null, 404);
            }
            
            // Check if already clocked in today
            $today = date('Y-m-d');
            $stmt = $this->db->prepare("
                SELECT id FROM attendance 
                WHERE employee_id = ? AND date = ? AND clock_in IS NOT NULL
            ");
            $stmt->execute([$employee['id'], $today]);
            
            if ($stmt->fetch()) {
                return Response::error('Already clocked in today', null, 400);
            }
            
            // Get work schedule
            $schedule = $this->getEmployeeSchedule($employee['id'], date('N'));
            $scheduledClockIn = $schedule['start_time'] ?? '09:00:00';
            
            // Calculate late minutes
            $currentTime = date('H:i:s');
            $lateMinutes = $this->calculateLateMinutes($currentTime, $scheduledClockIn);
            
            // Calculate salary deduction
            $salaryDeduction = $this->calculateSalaryDeduction($employee['id'], $lateMinutes);
            
            // Record attendance
            $stmt = $this->db->prepare("
                INSERT INTO attendance (
                    employee_id, date, clock_in, attendance_type, device_id, 
                    late_minutes, salary_deduction, scheduled_clock_in, status
                ) VALUES (?, ?, ?, 'qr_code', ?, ?, ?, ?, ?)
            ");
            
            $status = $lateMinutes > 0 ? 'late' : 'present';
            $stmt->execute([
                $employee['id'], $today, $currentTime, $data['device_id'],
                $lateMinutes, $salaryDeduction, $scheduledClockIn, $status
            ]);
            
            $attendanceId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($employee['user_id'], 'clock_in_qr', 
                "Clocked in via QR code. Late: {$lateMinutes} minutes");
            
            return Response::success([
                'attendance_id' => $attendanceId,
                'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                'clock_in_time' => $currentTime,
                'late_minutes' => $lateMinutes,
                'salary_deduction' => $salaryDeduction,
                'status' => $status
            ], 'Clock in successful');
            
        } catch (Exception $e) {
            error_log("QR Clock In Error: " . $e->getMessage());
            return Response::error('Clock in failed', null, 500);
        }
    }
    
    /**
     * Clock in via RFID
     */
    public function clockInRFID() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['rfid_card_id', 'device_id'])
                     ->string('rfid_card_id')
                     ->string('device_id');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            // Find employee by RFID
            $stmt = $this->db->prepare("
                SELECT e.*, u.username 
                FROM employees e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.rfid_card_id = ? AND e.status = 'active'
            ");
            $stmt->execute([$data['rfid_card_id']]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                return Response::error('Invalid RFID card or employee not found', null, 404);
            }
            
            return $this->processClockIn($employee, 'rfid', $data['device_id']);
            
        } catch (Exception $e) {
            error_log("RFID Clock In Error: " . $e->getMessage());
            return Response::error('Clock in failed', null, 500);
        }
    }
    
    /**
     * Clock in via Biometric
     */
    public function clockInBiometric() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['biometric_id', 'device_id'])
                     ->string('biometric_id')
                     ->string('device_id');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            // Find employee by biometric ID
            $stmt = $this->db->prepare("
                SELECT e.*, u.username 
                FROM employees e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.biometric_id = ? AND e.status = 'active'
            ");
            $stmt->execute([$data['biometric_id']]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                return Response::error('Biometric not recognized or employee not found', null, 404);
            }
            
            return $this->processClockIn($employee, 'biometric', $data['device_id']);
            
        } catch (Exception $e) {
            error_log("Biometric Clock In Error: " . $e->getMessage());
            return Response::error('Clock in failed', null, 500);
        }
    }
    
    /**
     * Manual clock in by HR/Admin
     */
    public function clockInManual() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['employee_id', 'clock_in_time'])
                     ->integer('employee_id')
                     ->string('clock_in_time');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            // Get employee
            $stmt = $this->db->prepare("
                SELECT e.*, u.username 
                FROM employees e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.id = ? AND e.status = 'active'
            ");
            $stmt->execute([$data['employee_id']]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                return Response::error('Employee not found', null, 404);
            }
            
            $date = $data['date'] ?? date('Y-m-d');
            $clockInTime = $data['clock_in_time'];
            
            // Check if already exists
            $stmt = $this->db->prepare("
                SELECT id FROM attendance 
                WHERE employee_id = ? AND date = ?
            ");
            $stmt->execute([$employee['id'], $date]);
            
            if ($stmt->fetch()) {
                return Response::error('Attendance record already exists for this date', null, 400);
            }
            
            // Get work schedule
            $dayOfWeek = date('N', strtotime($date));
            $schedule = $this->getEmployeeSchedule($employee['id'], $dayOfWeek);
            $scheduledClockIn = $schedule['start_time'] ?? '09:00:00';
            
            // Calculate late minutes
            $lateMinutes = $this->calculateLateMinutes($clockInTime, $scheduledClockIn);
            
            // Calculate salary deduction
            $salaryDeduction = $this->calculateSalaryDeduction($employee['id'], $lateMinutes);
            
            // Record attendance
            $stmt = $this->db->prepare("
                INSERT INTO attendance (
                    employee_id, date, clock_in, attendance_type, 
                    late_minutes, salary_deduction, scheduled_clock_in, status, notes
                ) VALUES (?, ?, ?, 'manual', ?, ?, ?, ?, ?)
            ");
            
            $status = $lateMinutes > 0 ? 'late' : 'present';
            $notes = "Manual entry by {$this->user['username']}";
            if (!empty($data['notes'])) {
                $notes .= ". " . $data['notes'];
            }
            
            $stmt->execute([
                $employee['id'], $date, $clockInTime,
                $lateMinutes, $salaryDeduction, $scheduledClockIn, $status, $notes
            ]);
            
            $attendanceId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($this->user['id'], 'manual_clock_in', 
                "Manual clock in for {$employee['first_name']} {$employee['last_name']}");
            
            return Response::success([
                'attendance_id' => $attendanceId,
                'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                'clock_in_time' => $clockInTime,
                'late_minutes' => $lateMinutes,
                'salary_deduction' => $salaryDeduction,
                'status' => $status
            ], 'Manual clock in successful');
            
        } catch (Exception $e) {
            error_log("Manual Clock In Error: " . $e->getMessage());
            return Response::error('Manual clock in failed', null, 500);
        }
    }
    
    /**
     * Clock out with overtime calculation
     */
    public function clockOut() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['employee_id'])
                     ->integer('employee_id');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            $today = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            // Get today's attendance record
            $stmt = $this->db->prepare("
                SELECT * FROM attendance 
                WHERE employee_id = ? AND date = ? AND clock_in IS NOT NULL
            ");
            $stmt->execute([$data['employee_id'], $today]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$attendance) {
                return Response::error('No clock in record found for today', null, 404);
            }
            
            if ($attendance['clock_out']) {
                return Response::error('Already clocked out today', null, 400);
            }
            
            // Get work schedule
            $schedule = $this->getEmployeeSchedule($data['employee_id'], date('N'));
            $scheduledClockOut = $schedule['end_time'] ?? '17:00:00';
            
            // Calculate total hours and overtime
            $clockIn = new DateTime($attendance['clock_in']);
            $clockOut = new DateTime($currentTime);
            $totalMinutes = ($clockOut->getTimestamp() - $clockIn->getTimestamp()) / 60;
            $totalHours = $totalMinutes / 60;
            
            // Calculate overtime
            $overtimeMinutes = $this->calculateOvertimeMinutes($currentTime, $scheduledClockOut);
            $overtimeBonus = $this->calculateOvertimeBonus($data['employee_id'], $overtimeMinutes);
            
            // Update attendance record
            $stmt = $this->db->prepare("
                UPDATE attendance SET 
                    clock_out = ?, 
                    total_hours = ?, 
                    hours_worked = ?, 
                    overtime_minutes = ?, 
                    overtime_bonus = ?,
                    scheduled_clock_out = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $currentTime, $totalHours, $totalHours, 
                $overtimeMinutes, $overtimeBonus, $scheduledClockOut, $attendance['id']
            ]);
            
            // Log activity
            $this->logActivity($data['employee_id'], 'clock_out', 
                "Clocked out. Overtime: {$overtimeMinutes} minutes");
            
            return Response::success([
                'clock_out_time' => $currentTime,
                'total_hours' => round($totalHours, 2),
                'overtime_minutes' => $overtimeMinutes,
                'overtime_bonus' => $overtimeBonus
            ], 'Clock out successful');
            
        } catch (Exception $e) {
            error_log("Clock Out Error: " . $e->getMessage());
            return Response::error('Clock out failed', null, 500);
        }
    }
    
    /**
     * Get attendance summary with salary impact
     */
    public function getAttendanceSummary() {
        try {
            $params = $_GET;
            $employeeId = $params['employee_id'] ?? null;
            $month = $params['month'] ?? date('m');
            $year = $params['year'] ?? date('Y');
            
            // Build query based on permissions
            $whereClause = "WHERE MONTH(a.date) = ? AND YEAR(a.date) = ?";
            $queryParams = [$month, $year];
            
            if ($employeeId) {
                $whereClause .= " AND a.employee_id = ?";
                $queryParams[] = $employeeId;
            } elseif ($this->user['role'] === 'employee') {
                // Employees can only see their own data
                $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ?");
                $stmt->execute([$this->user['id']]);
                $emp = $stmt->fetch();
                if ($emp) {
                    $whereClause .= " AND a.employee_id = ?";
                    $queryParams[] = $emp['id'];
                }
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    e.id as employee_id,
                    e.first_name,
                    e.last_name,
                    e.employee_id as emp_code,
                    d.name as department,
                    COUNT(a.id) as total_days,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days,
                    SUM(a.late_minutes) as total_late_minutes,
                    SUM(a.overtime_minutes) as total_overtime_minutes,
                    SUM(a.salary_deduction) as total_deductions,
                    SUM(a.overtime_bonus) as total_overtime_bonus,
                    (SUM(a.overtime_bonus) - SUM(a.salary_deduction)) as net_salary_impact
                FROM employees e
                LEFT JOIN attendance a ON e.id = a.employee_id
                LEFT JOIN departments d ON e.department_id = d.id
                {$whereClause}
                GROUP BY e.id, e.first_name, e.last_name, e.employee_id, d.name
                ORDER BY e.first_name, e.last_name
            ");
            
            $stmt->execute($queryParams);
            $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return Response::success($summary, 'Attendance summary retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Attendance Summary Error: " . $e->getMessage());
            return Response::error('Failed to get attendance summary', null, 500);
        }
    }
    
    /**
     * Generate monthly attendance report
     */
    public function generateMonthlyReport() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['month', 'year'])
                     ->integer('month')
                     ->integer('year');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            $month = $data['month'];
            $year = $data['year'];
            $employeeId = $data['employee_id'] ?? null;
            
            // Check if report already exists
            $whereClause = "WHERE report_month = ? AND report_year = ?";
            $params = [$month, $year];
            
            if ($employeeId) {
                $whereClause .= " AND employee_id = ?";
                $params[] = $employeeId;
            }
            
            $stmt = $this->db->prepare("SELECT id FROM attendance_reports {$whereClause}");
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                // Update existing report
                $this->updateAttendanceReport($month, $year, $employeeId);
            } else {
                // Generate new report
                $this->createAttendanceReport($month, $year, $employeeId);
            }
            
            return Response::success(null, 'Monthly report generated successfully');
            
        } catch (Exception $e) {
            error_log("Generate Report Error: " . $e->getMessage());
            return Response::error('Failed to generate report', null, 500);
        }
    }
    
    // Helper Methods
    
    private function processClockIn($employee, $attendanceType, $deviceId) {
        $today = date('Y-m-d');
        $currentTime = date('H:i:s');
        
        // Check if already clocked in
        $stmt = $this->db->prepare("
            SELECT id FROM attendance 
            WHERE employee_id = ? AND date = ? AND clock_in IS NOT NULL
        ");
        $stmt->execute([$employee['id'], $today]);
        
        if ($stmt->fetch()) {
            return Response::error('Already clocked in today', null, 400);
        }
        
        // Get work schedule
        $schedule = $this->getEmployeeSchedule($employee['id'], date('N'));
        $scheduledClockIn = $schedule['start_time'] ?? '09:00:00';
        
        // Calculate late minutes and deduction
        $lateMinutes = $this->calculateLateMinutes($currentTime, $scheduledClockIn);
        $salaryDeduction = $this->calculateSalaryDeduction($employee['id'], $lateMinutes);
        
        // Record attendance
        $stmt = $this->db->prepare("
            INSERT INTO attendance (
                employee_id, date, clock_in, attendance_type, device_id, 
                late_minutes, salary_deduction, scheduled_clock_in, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $lateMinutes > 0 ? 'late' : 'present';
        $stmt->execute([
            $employee['id'], $today, $currentTime, $attendanceType, $deviceId,
            $lateMinutes, $salaryDeduction, $scheduledClockIn, $status
        ]);
        
        $attendanceId = $this->db->lastInsertId();
        
        // Log activity
        $this->logActivity($employee['user_id'], "clock_in_{$attendanceType}", 
            "Clocked in via {$attendanceType}. Late: {$lateMinutes} minutes");
        
        return Response::success([
            'attendance_id' => $attendanceId,
            'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
            'clock_in_time' => $currentTime,
            'late_minutes' => $lateMinutes,
            'salary_deduction' => $salaryDeduction,
            'status' => $status
        ], 'Clock in successful');
    }
    
    private function getEmployeeSchedule($employeeId, $dayOfWeek) {
        $stmt = $this->db->prepare("
            SELECT * FROM work_schedules 
            WHERE employee_id = ? AND day_of_week = ? 
            AND (effective_to IS NULL OR effective_to >= CURDATE())
            ORDER BY effective_from DESC LIMIT 1
        ");
        $stmt->execute([$employeeId, $dayOfWeek]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    private function calculateLateMinutes($actualTime, $scheduledTime) {
        $actual = new DateTime($actualTime);
        $scheduled = new DateTime($scheduledTime);
        
        if ($actual <= $scheduled) {
            return 0;
        }
        
        $diff = $actual->getTimestamp() - $scheduled->getTimestamp();
        return max(0, floor($diff / 60));
    }
    
    private function calculateOvertimeMinutes($actualTime, $scheduledTime) {
        $actual = new DateTime($actualTime);
        $scheduled = new DateTime($scheduledTime);
        
        if ($actual <= $scheduled) {
            return 0;
        }
        
        $diff = $actual->getTimestamp() - $scheduled->getTimestamp();
        return max(0, floor($diff / 60));
    }
    
    private function calculateSalaryDeduction($employeeId, $lateMinutes) {
        if ($lateMinutes <= 0) return 0;
        
        // Get deduction rules
        $stmt = $this->db->prepare("
            SELECT * FROM salary_deduction_rules 
            WHERE is_active = 1 
            AND effective_from <= CURDATE() 
            AND (effective_to IS NULL OR effective_to >= CURDATE())
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute();
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rule) return 0;
        
        $gracePeriod = $rule['grace_period_minutes'] ?? 0;
        $deductibleMinutes = max(0, $lateMinutes - $gracePeriod);
        
        if ($deductibleMinutes <= 0) return 0;
        
        $deduction = 0;
        switch ($rule['deduction_type']) {
            case 'per_minute':
                $deduction = $deductibleMinutes * $rule['deduction_amount'];
                break;
            case 'per_hour':
                $deduction = ceil($deductibleMinutes / 60) * $rule['deduction_amount'];
                break;
            case 'fixed_amount':
                $deduction = $rule['deduction_amount'];
                break;
        }
        
        // Apply max daily deduction limit
        if ($rule['max_deduction_per_day']) {
            $deduction = min($deduction, $rule['max_deduction_per_day']);
        }
        
        return round($deduction, 2);
    }
    
    private function calculateOvertimeBonus($employeeId, $overtimeMinutes) {
        if ($overtimeMinutes <= 0) return 0;
        
        // Get overtime rules
        $stmt = $this->db->prepare("
            SELECT * FROM overtime_rules 
            WHERE is_active = 1 
            AND effective_from <= CURDATE() 
            AND (effective_to IS NULL OR effective_to >= CURDATE())
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute();
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rule) return 0;
        
        $minOvertimeMinutes = $rule['minimum_overtime_minutes'] ?? 30;
        if ($overtimeMinutes < $minOvertimeMinutes) return 0;
        
        $bonus = 0;
        switch ($rule['overtime_type']) {
            case 'per_minute':
                $bonus = $overtimeMinutes * $rule['overtime_rate'];
                break;
            case 'per_hour':
                $bonus = ($overtimeMinutes / 60) * $rule['overtime_rate'];
                break;
            case 'fixed_amount':
                $bonus = $rule['overtime_rate'];
                break;
        }
        
        return round($bonus, 2);
    }
    
    private function createAttendanceReport($month, $year, $employeeId = null) {
        $whereClause = "WHERE MONTH(a.date) = ? AND YEAR(a.date) = ?";
        $params = [$month, $year];
        
        if ($employeeId) {
            $whereClause .= " AND a.employee_id = ?";
            $params[] = $employeeId;
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                a.employee_id,
                COUNT(a.id) as total_working_days,
                SUM(CASE WHEN a.status = 'present' OR a.status = 'late' THEN 1 ELSE 0 END) as days_present,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as days_absent,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as days_late,
                SUM(a.hours_worked) as total_hours_worked,
                SUM(a.overtime_minutes) / 60 as total_overtime_hours,
                SUM(a.late_minutes) as total_late_minutes,
                SUM(a.salary_deduction) as total_salary_deduction,
                SUM(a.overtime_bonus) as total_overtime_bonus,
                (SUM(a.overtime_bonus) - SUM(a.salary_deduction)) as net_salary_impact
            FROM attendance a
            {$whereClause}
            GROUP BY a.employee_id
        ");
        
        $stmt->execute($params);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Insert report records
        $insertStmt = $this->db->prepare("
            INSERT INTO attendance_reports (
                employee_id, report_month, report_year, total_working_days,
                days_present, days_absent, days_late, total_hours_worked,
                total_overtime_hours, total_late_minutes, total_salary_deduction,
                total_overtime_bonus, net_salary_impact, generated_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($reportData as $data) {
            $insertStmt->execute([
                $data['employee_id'], $month, $year, $data['total_working_days'],
                $data['days_present'], $data['days_absent'], $data['days_late'],
                $data['total_hours_worked'], $data['total_overtime_hours'],
                $data['total_late_minutes'], $data['total_salary_deduction'],
                $data['total_overtime_bonus'], $data['net_salary_impact'],
                $this->user['id']
            ]);
        }
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
}