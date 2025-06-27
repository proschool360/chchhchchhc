<?php
/**
 * Settings Controller
 * Handles system settings management including salary deduction rules, overtime settings, etc.
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class SettingsController {
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
     * Get all system settings
     */
    public function getSettings() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $category = $_GET['category'] ?? null;
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($category) {
                $whereClause .= " AND category = ?";
                $params[] = $category;
            }
            
            $stmt = $this->db->prepare("
                SELECT setting_key, setting_value, category, description, data_type
                FROM system_settings 
                {$whereClause}
                ORDER BY category, setting_key
            ");
            $stmt->execute($params);
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group settings by category
            $groupedSettings = [];
            foreach ($settings as $setting) {
                $category = $setting['category'];
                if (!isset($groupedSettings[$category])) {
                    $groupedSettings[$category] = [];
                }
                
                // Parse value based on data type
                $value = $setting['setting_value'];
                switch ($setting['data_type']) {
                    case 'boolean':
                        $value = (bool) $value;
                        break;
                    case 'integer':
                        $value = (int) $value;
                        break;
                    case 'decimal':
                        $value = (float) $value;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }
                
                $groupedSettings[$category][$setting['setting_key']] = [
                    'value' => $value,
                    'description' => $setting['description'],
                    'data_type' => $setting['data_type']
                ];
            }
            
            return Response::success($groupedSettings, 'Settings retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Get Settings Error: " . $e->getMessage());
            return Response::error('Failed to retrieve settings', null, 500);
        }
    }
    
    /**
     * Update system settings
     */
    public function updateSettings() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['settings'])
                     ->array('settings');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            $this->db->beginTransaction();
            
            try {
                foreach ($data['settings'] as $key => $value) {
                    // Get current setting info
                    $stmt = $this->db->prepare("
                        SELECT data_type FROM system_settings WHERE setting_key = ?
                    ");
                    $stmt->execute([$key]);
                    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$setting) {
                        continue; // Skip unknown settings
                    }
                    
                    // Format value based on data type
                    $formattedValue = $this->formatSettingValue($value, $setting['data_type']);
                    
                    // Update setting
                    $stmt = $this->db->prepare("
                        UPDATE system_settings 
                        SET setting_value = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE setting_key = ?
                    ");
                    $stmt->execute([$formattedValue, $key]);
                }
                
                $this->db->commit();
                
                // Log activity
                $this->logActivity($this->user['id'], 'update_settings', 
                    'Updated system settings: ' . implode(', ', array_keys($data['settings'])));
                
                return Response::success(null, 'Settings updated successfully');
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Update Settings Error: " . $e->getMessage());
            return Response::error('Failed to update settings', null, 500);
        }
    }
    
    /**
     * Get salary deduction rules
     */
    public function getDeductionRules() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM salary_deduction_rules 
                WHERE is_active = 1
                ORDER BY effective_from DESC
            ");
            $stmt->execute();
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return Response::success($rules, 'Deduction rules retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Get Deduction Rules Error: " . $e->getMessage());
            return Response::error('Failed to retrieve deduction rules', null, 500);
        }
    }
    
    /**
     * Create salary deduction rule
     */
    public function createDeductionRule() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['rule_name', 'deduction_type', 'deduction_amount'])
                     ->string('rule_name')
                     ->in('deduction_type', ['per_minute', 'per_hour', 'fixed_amount'])
                     ->numeric('deduction_amount');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            // Deactivate existing rules if this is set as active
            if (!empty($data['is_active'])) {
                $stmt = $this->db->prepare("UPDATE salary_deduction_rules SET is_active = 0");
                $stmt->execute();
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO salary_deduction_rules (
                    rule_name, description, deduction_type, deduction_amount,
                    grace_period_minutes, max_deduction_per_day, effective_from,
                    effective_to, is_active, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['rule_name'],
                $data['description'] ?? '',
                $data['deduction_type'],
                $data['deduction_amount'],
                $data['grace_period_minutes'] ?? 0,
                $data['max_deduction_per_day'] ?? null,
                $data['effective_from'] ?? date('Y-m-d'),
                $data['effective_to'] ?? null,
                !empty($data['is_active']) ? 1 : 0,
                $this->user['id']
            ]);
            
            $ruleId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($this->user['id'], 'create_deduction_rule', 
                "Created salary deduction rule: {$data['rule_name']}");
            
            return Response::success([
                'rule_id' => $ruleId,
                'rule_name' => $data['rule_name']
            ], 'Deduction rule created successfully');
            
        } catch (Exception $e) {
            error_log("Create Deduction Rule Error: " . $e->getMessage());
            return Response::error('Failed to create deduction rule', null, 500);
        }
    }
    
    /**
     * Get overtime rules
     */
    public function getOvertimeRules() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM overtime_rules 
                WHERE is_active = 1
                ORDER BY effective_from DESC
            ");
            $stmt->execute();
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return Response::success($rules, 'Overtime rules retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Get Overtime Rules Error: " . $e->getMessage());
            return Response::error('Failed to retrieve overtime rules', null, 500);
        }
    }
    
    /**
     * Create overtime rule
     */
    public function createOvertimeRule() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['rule_name', 'overtime_type', 'overtime_rate'])
                     ->string('rule_name')
                     ->in('overtime_type', ['per_minute', 'per_hour', 'fixed_amount'])
                     ->numeric('overtime_rate');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            // Deactivate existing rules if this is set as active
            if (!empty($data['is_active'])) {
                $stmt = $this->db->prepare("UPDATE overtime_rules SET is_active = 0");
                $stmt->execute();
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO overtime_rules (
                    rule_name, description, overtime_type, overtime_rate,
                    minimum_overtime_minutes, max_overtime_per_day, effective_from,
                    effective_to, is_active, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['rule_name'],
                $data['description'] ?? '',
                $data['overtime_type'],
                $data['overtime_rate'],
                $data['minimum_overtime_minutes'] ?? 30,
                $data['max_overtime_per_day'] ?? null,
                $data['effective_from'] ?? date('Y-m-d'),
                $data['effective_to'] ?? null,
                !empty($data['is_active']) ? 1 : 0,
                $this->user['id']
            ]);
            
            $ruleId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($this->user['id'], 'create_overtime_rule', 
                "Created overtime rule: {$data['rule_name']}");
            
            return Response::success([
                'rule_id' => $ruleId,
                'rule_name' => $data['rule_name']
            ], 'Overtime rule created successfully');
            
        } catch (Exception $e) {
            error_log("Create Overtime Rule Error: " . $e->getMessage());
            return Response::error('Failed to create overtime rule', null, 500);
        }
    }
    
    /**
     * Get attendance devices
     */
    public function getAttendanceDevices() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM attendance_devices 
                WHERE is_active = 1
                ORDER BY device_name
            ");
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return Response::success($devices, 'Attendance devices retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Get Attendance Devices Error: " . $e->getMessage());
            return Response::error('Failed to retrieve attendance devices', null, 500);
        }
    }
    
    /**
     * Create attendance device
     */
    public function createAttendanceDevice() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['device_name', 'device_type', 'location'])
                     ->string('device_name')
                     ->in('device_type', ['qr_scanner', 'rfid_reader', 'biometric', 'manual'])
                     ->string('location');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            // Generate unique device ID
            $deviceId = $this->generateDeviceId($data['device_type']);
            
            $stmt = $this->db->prepare("
                INSERT INTO attendance_devices (
                    device_id, device_name, device_type, location, description,
                    ip_address, is_active, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, 1, ?)
            ");
            
            $stmt->execute([
                $deviceId,
                $data['device_name'],
                $data['device_type'],
                $data['location'],
                $data['description'] ?? '',
                $data['ip_address'] ?? null,
                $this->user['id']
            ]);
            
            $id = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($this->user['id'], 'create_attendance_device', 
                "Created attendance device: {$data['device_name']}");
            
            return Response::success([
                'id' => $id,
                'device_id' => $deviceId,
                'device_name' => $data['device_name']
            ], 'Attendance device created successfully');
            
        } catch (Exception $e) {
            error_log("Create Attendance Device Error: " . $e->getMessage());
            return Response::error('Failed to create attendance device', null, 500);
        }
    }
    
    /**
     * Get work schedules
     */
    public function getWorkSchedules() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $employeeId = $_GET['employee_id'] ?? null;
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($employeeId) {
                $whereClause .= " AND ws.employee_id = ?";
                $params[] = $employeeId;
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    ws.*, e.first_name, e.last_name, e.employee_id as emp_code
                FROM work_schedules ws
                JOIN employees e ON ws.employee_id = e.id
                {$whereClause}
                ORDER BY e.first_name, e.last_name, ws.day_of_week
            ");
            $stmt->execute($params);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by employee
            $groupedSchedules = [];
            foreach ($schedules as $schedule) {
                $empKey = $schedule['employee_id'];
                if (!isset($groupedSchedules[$empKey])) {
                    $groupedSchedules[$empKey] = [
                        'employee_id' => $schedule['employee_id'],
                        'employee_name' => $schedule['first_name'] . ' ' . $schedule['last_name'],
                        'employee_code' => $schedule['emp_code'],
                        'schedules' => []
                    ];
                }
                
                $groupedSchedules[$empKey]['schedules'][] = [
                    'id' => $schedule['id'],
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'break_start' => $schedule['break_start'],
                    'break_end' => $schedule['break_end'],
                    'is_working_day' => $schedule['is_working_day'],
                    'effective_from' => $schedule['effective_from'],
                    'effective_to' => $schedule['effective_to']
                ];
            }
            
            return Response::success(array_values($groupedSchedules), 'Work schedules retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Get Work Schedules Error: " . $e->getMessage());
            return Response::error('Failed to retrieve work schedules', null, 500);
        }
    }
    
    /**
     * Create/Update work schedule
     */
    public function saveWorkSchedule() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['employee_id', 'schedules'])
                     ->integer('employee_id')
                     ->array('schedules');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            $this->db->beginTransaction();
            
            try {
                // Deactivate existing schedules
                $stmt = $this->db->prepare("
                    UPDATE work_schedules 
                    SET effective_to = CURDATE() 
                    WHERE employee_id = ? AND (effective_to IS NULL OR effective_to > CURDATE())
                ");
                $stmt->execute([$data['employee_id']]);
                
                // Insert new schedules
                $insertStmt = $this->db->prepare("
                    INSERT INTO work_schedules (
                        employee_id, day_of_week, start_time, end_time,
                        break_start, break_end, is_working_day, effective_from
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($data['schedules'] as $schedule) {
                    $insertStmt->execute([
                        $data['employee_id'],
                        $schedule['day_of_week'],
                        $schedule['start_time'] ?? null,
                        $schedule['end_time'] ?? null,
                        $schedule['break_start'] ?? null,
                        $schedule['break_end'] ?? null,
                        !empty($schedule['is_working_day']) ? 1 : 0,
                        $data['effective_from'] ?? date('Y-m-d')
                    ]);
                }
                
                $this->db->commit();
                
                // Log activity
                $this->logActivity($this->user['id'], 'save_work_schedule', 
                    "Updated work schedule for employee ID: {$data['employee_id']}");
                
                return Response::success(null, 'Work schedule saved successfully');
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Save Work Schedule Error: " . $e->getMessage());
            return Response::error('Failed to save work schedule', null, 500);
        }
    }
    
    // Helper Methods
    
    private function formatSettingValue($value, $dataType) {
        switch ($dataType) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'integer':
                return (string) intval($value);
            case 'decimal':
                return (string) floatval($value);
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }
    
    private function generateDeviceId($deviceType) {
        $prefix = strtoupper(substr($deviceType, 0, 3));
        $timestamp = time();
        $random = mt_rand(100, 999);
        return "{$prefix}_{$timestamp}_{$random}";
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