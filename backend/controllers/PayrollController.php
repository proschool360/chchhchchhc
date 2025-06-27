<?php
/**
 * Payroll Controller
 * Handles payroll management operations
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class PayrollController {
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
     * Get payroll records with pagination and filtering
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
                $filters[] = "p.employee_id = ?";
                $filterValues[] = $params['employee_id'];
            } elseif (!in_array($this->user['role'], ['admin', 'hr'])) {
                // Regular employees can only see their own records
                $employeeId = $this->getCurrentEmployeeId();
                if ($employeeId) {
                    $filters[] = "p.employee_id = ?";
                    $filterValues[] = $employeeId;
                }
            }
            
            // Pay period filter
            if (!empty($params['pay_period'])) {
                $filters[] = "p.pay_period = ?";
                $filterValues[] = $params['pay_period'];
            }
            
            // Year filter
            if (!empty($params['year'])) {
                $filters[] = "YEAR(p.pay_period) = ?";
                $filterValues[] = $params['year'];
            }
            
            // Month filter
            if (!empty($params['month'])) {
                $filters[] = "MONTH(p.pay_period) = ?";
                $filterValues[] = $params['month'];
            }
            
            // Status filter
            if (!empty($params['status'])) {
                $filters[] = "p.status = ?";
                $filterValues[] = $params['status'];
            }
            
            // Department filter
            if (!empty($params['department_id']) && in_array($this->user['role'], ['admin', 'hr'])) {
                $filters[] = "e.department_id = ?";
                $filterValues[] = $params['department_id'];
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total 
                          FROM payroll p 
                          JOIN employees e ON p.employee_id = e.id 
                          $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get payroll records
            $query = "SELECT p.id, p.pay_period, p.basic_salary, p.allowances, p.overtime_amount,
                            p.gross_salary, p.pf_deduction, p.esi_deduction, p.tds_deduction,
                            p.professional_tax, p.other_deductions, p.total_deductions,
                            p.net_salary, p.status, p.processed_date, p.created_at,
                            e.employee_id, e.first_name, e.last_name,
                            d.name as department_name, pos.title as position_title
                     FROM payroll p
                     JOIN employees e ON p.employee_id = e.id
                     LEFT JOIN departments d ON e.department_id = d.id
                     LEFT JOIN positions pos ON e.position_id = pos.id
                     $whereClause
                     ORDER BY p.pay_period DESC, e.first_name ASC
                     LIMIT ? OFFSET ?";
            
            $payrollRecords = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($payrollRecords as &$record) {
                $record['employee_name'] = $record['first_name'] . ' ' . $record['last_name'];
                
                // Convert amounts to float
                $amountFields = ['basic_salary', 'allowances', 'overtime_amount', 'gross_salary',
                               'pf_deduction', 'esi_deduction', 'tds_deduction', 'professional_tax',
                               'other_deductions', 'total_deductions', 'net_salary'];
                
                foreach ($amountFields as $field) {
                    $record[$field] = $record[$field] ? (float)$record[$field] : 0.0;
                }
                
                // Remove individual name fields
                unset($record['first_name'], $record['last_name']);
            }
            
            Response::paginated($payrollRecords, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch payroll records: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single payroll record
     */
    public function show($id) {
        try {
            $query = "SELECT p.*, e.employee_id, e.first_name, e.last_name, e.bank_account,
                            d.name as department_name, pos.title as position_title
                     FROM payroll p
                     JOIN employees e ON p.employee_id = e.id
                     LEFT JOIN departments d ON e.department_id = d.id
                     LEFT JOIN positions pos ON e.position_id = pos.id
                     WHERE p.id = ?";
            
            $payroll = $this->db->selectOne($query, [$id]);
            
            if (!$payroll) {
                Response::notFound('Payroll record not found');
                return;
            }
            
            // Check access permissions
            if (!$this->canAccessPayroll($payroll['employee_id'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Format data
            $payroll['employee_name'] = $payroll['first_name'] . ' ' . $payroll['last_name'];
            
            // Convert amounts to float
            $amountFields = ['basic_salary', 'allowances', 'overtime_amount', 'gross_salary',
                           'pf_deduction', 'esi_deduction', 'tds_deduction', 'professional_tax',
                           'other_deductions', 'total_deductions', 'net_salary'];
            
            foreach ($amountFields as $field) {
                $payroll[$field] = $payroll[$field] ? (float)$payroll[$field] : 0.0;
            }
            
            // Get attendance data for this pay period
            $attendanceData = $this->getAttendanceForPayPeriod($payroll['employee_id'], $payroll['pay_period']);
            $payroll['attendance_summary'] = $attendanceData;
            
            Response::success($payroll);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch payroll record: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate payroll for a specific pay period (HR/Admin only)
     */
    public function generate() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'pay_period' => 'required|date',
                'employee_ids' => 'array',
                'department_id' => 'integer'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            $payPeriod = $data['pay_period'];
            
            // Check if payroll already exists for this period
            $existingPayroll = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM payroll WHERE pay_period = ?",
                [$payPeriod]
            );
            
            if ($existingPayroll['count'] > 0) {
                Response::error('Payroll already exists for this pay period', 400);
                return;
            }
            
            // Get employees to process
            $employees = $this->getEmployeesForPayroll($data);
            
            if (empty($employees)) {
                Response::error('No employees found for payroll generation', 400);
                return;
            }
            
            $processedCount = 0;
            $errors = [];
            
            foreach ($employees as $employee) {
                try {
                    $this->generateEmployeePayroll($employee, $payPeriod);
                    $processedCount++;
                } catch (Exception $e) {
                    $errors[] = [
                        'employee_id' => $employee['employee_id'],
                        'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $response = [
                'processed_count' => $processedCount,
                'total_employees' => count($employees),
                'errors' => $errors
            ];
            
            if ($processedCount > 0) {
                Response::success($response, "Payroll generated for {$processedCount} employees");
            } else {
                Response::error('Failed to generate payroll for any employee', 400, $response);
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to generate payroll: ' . $e->getMessage());
        }
    }
    
    /**
     * Update payroll record (HR/Admin only)
     */
    public function update($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Get existing payroll record
            $payroll = $this->db->selectOne(
                "SELECT * FROM payroll WHERE id = ?",
                [$id]
            );
            
            if (!$payroll) {
                Response::notFound('Payroll record not found');
                return;
            }
            
            // Only draft payrolls can be updated
            if ($payroll['status'] !== 'draft') {
                Response::error('Only draft payroll records can be updated', 400);
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'basic_salary' => 'numeric|min:0',
                'allowances' => 'numeric|min:0',
                'overtime_amount' => 'numeric|min:0',
                'other_deductions' => 'numeric|min:0',
                'notes' => 'string|max:1000'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Prepare update data
            $updateFields = [];
            $updateValues = [];
            
            if (isset($data['basic_salary'])) {
                $updateFields[] = "basic_salary = ?";
                $updateValues[] = $data['basic_salary'];
            }
            
            if (isset($data['allowances'])) {
                $updateFields[] = "allowances = ?";
                $updateValues[] = $data['allowances'];
            }
            
            if (isset($data['overtime_amount'])) {
                $updateFields[] = "overtime_amount = ?";
                $updateValues[] = $data['overtime_amount'];
            }
            
            if (isset($data['other_deductions'])) {
                $updateFields[] = "other_deductions = ?";
                $updateValues[] = $data['other_deductions'];
            }
            
            if (isset($data['notes'])) {
                $updateFields[] = "notes = ?";
                $updateValues[] = $data['notes'];
            }
            
            if (empty($updateFields)) {
                Response::error('No valid fields to update', 400);
                return;
            }
            
            // Recalculate totals if amounts changed
            if (isset($data['basic_salary']) || isset($data['allowances']) || 
                isset($data['overtime_amount']) || isset($data['other_deductions'])) {
                
                $basicSalary = $data['basic_salary'] ?? $payroll['basic_salary'];
                $allowances = $data['allowances'] ?? $payroll['allowances'];
                $overtimeAmount = $data['overtime_amount'] ?? $payroll['overtime_amount'];
                $otherDeductions = $data['other_deductions'] ?? $payroll['other_deductions'];
                
                // Get employee for tax calculations
                $employee = $this->db->selectOne(
                    "SELECT * FROM employees WHERE id = ?",
                    [$payroll['employee_id']]
                );
                
                $calculations = $this->calculatePayroll($employee, $basicSalary, $allowances, $overtimeAmount, $otherDeductions);
                
                $updateFields[] = "gross_salary = ?";
                $updateValues[] = $calculations['gross_salary'];
                
                $updateFields[] = "pf_deduction = ?";
                $updateValues[] = $calculations['pf_deduction'];
                
                $updateFields[] = "esi_deduction = ?";
                $updateValues[] = $calculations['esi_deduction'];
                
                $updateFields[] = "tds_deduction = ?";
                $updateValues[] = $calculations['tds_deduction'];
                
                $updateFields[] = "professional_tax = ?";
                $updateValues[] = $calculations['professional_tax'];
                
                $updateFields[] = "total_deductions = ?";
                $updateValues[] = $calculations['total_deductions'];
                
                $updateFields[] = "net_salary = ?";
                $updateValues[] = $calculations['net_salary'];
            }
            
            $updateFields[] = "updated_at = NOW()";
            $updateValues[] = $id;
            
            // Update payroll record
            $query = "UPDATE payroll SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $result = $this->db->update($query, $updateValues);
            
            if ($result) {
                $updatedPayroll = $this->db->selectOne(
                    "SELECT p.*, e.employee_id, e.first_name, e.last_name 
                     FROM payroll p 
                     JOIN employees e ON p.employee_id = e.id 
                     WHERE p.id = ?",
                    [$id]
                );
                
                Response::updated($updatedPayroll, 'Payroll record updated successfully');
            } else {
                Response::serverError('Failed to update payroll record');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update payroll record: ' . $e->getMessage());
        }
    }
    
    /**
     * Approve payroll (HR/Admin only)
     */
    public function approve($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $payroll = $this->db->selectOne(
                "SELECT * FROM payroll WHERE id = ?",
                [$id]
            );
            
            if (!$payroll) {
                Response::notFound('Payroll record not found');
                return;
            }
            
            if ($payroll['status'] !== 'draft') {
                Response::error('Only draft payroll records can be approved', 400);
                return;
            }
            
            // Update status to approved
            $query = "UPDATE payroll SET status = 'approved', processed_date = NOW(), updated_at = NOW() WHERE id = ?";
            $result = $this->db->update($query, [$id]);
            
            if ($result) {
                // Generate payslip
                $this->generatePayslip($id);
                
                Response::success(null, 'Payroll approved successfully');
            } else {
                Response::serverError('Failed to approve payroll');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to approve payroll: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate and download payslip
     */
    public function downloadPayslip($id) {
        try {
            $payroll = $this->db->selectOne(
                "SELECT p.*, e.employee_id, e.first_name, e.last_name, e.email, e.phone,
                        e.bank_account, e.pan_number, e.uan_number,
                        d.name as department_name, pos.title as position_title,
                        c.name as company_name, c.address as company_address
                 FROM payroll p
                 JOIN employees e ON p.employee_id = e.id
                 LEFT JOIN departments d ON e.department_id = d.id
                 LEFT JOIN positions pos ON e.position_id = pos.id
                 CROSS JOIN (SELECT 'HRMS Company' as name, '123 Business Street, City' as address) c
                 WHERE p.id = ?",
                [$id]
            );
            
            if (!$payroll) {
                Response::notFound('Payroll record not found');
                return;
            }
            
            // Check access permissions
            if (!$this->canAccessPayroll($payroll['employee_id'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            if ($payroll['status'] !== 'approved') {
                Response::error('Payslip can only be downloaded for approved payroll', 400);
                return;
            }
            
            // Generate payslip content
            $payslipContent = $this->generatePayslipContent($payroll);
            
            // Set headers for download
            $filename = 'payslip_' . $payroll['employee_id'] . '_' . date('Y_m', strtotime($payroll['pay_period'])) . '.html';
            
            Response::download($payslipContent, $filename, 'text/html');
            
        } catch (Exception $e) {
            Response::serverError('Failed to generate payslip: ' . $e->getMessage());
        }
    }
    
    /**
     * Get payroll reports (HR/Admin only)
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
                    $data = $this->getPayrollSummaryReport($params);
                    break;
                case 'department':
                    $data = $this->getDepartmentPayrollReport($params);
                    break;
                case 'tax':
                    $data = $this->getTaxReport($params);
                    break;
                case 'cost_center':
                    $data = $this->getCostCenterReport($params);
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
    
    /**
     * Get my payslips (for employees)
     */
    public function myPayslips() {
        try {
            $employeeId = $this->getCurrentEmployeeId();
            
            if (!$employeeId) {
                Response::error('Employee record not found', 400);
                return;
            }
            
            $params = Router::getQueryParams();
            
            // Pagination
            $page = max(1, (int)($params['page'] ?? 1));
            $perPage = min(50, max(10, (int)($params['per_page'] ?? 12)));
            $offset = ($page - 1) * $perPage;
            
            // Filters
            $filters = ["p.employee_id = ?", "p.status = 'approved'"];
            $filterValues = [$employeeId];
            
            if (!empty($params['year'])) {
                $filters[] = "YEAR(p.pay_period) = ?";
                $filterValues[] = $params['year'];
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $filters);
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM payroll p $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get payslips
            $query = "SELECT p.id, p.pay_period, p.gross_salary, p.total_deductions, p.net_salary,
                            p.processed_date, p.created_at
                     FROM payroll p
                     $whereClause
                     ORDER BY p.pay_period DESC
                     LIMIT ? OFFSET ?";
            
            $payslips = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($payslips as &$payslip) {
                $payslip['gross_salary'] = (float)$payslip['gross_salary'];
                $payslip['total_deductions'] = (float)$payslip['total_deductions'];
                $payslip['net_salary'] = (float)$payslip['net_salary'];
                $payslip['month_year'] = date('F Y', strtotime($payslip['pay_period']));
            }
            
            Response::paginated($payslips, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch payslips: ' . $e->getMessage());
        }
    }
    
    // Helper methods
    
    private function getCurrentEmployeeId() {
        $query = "SELECT id FROM employees WHERE user_id = ? AND status = 'active'";
        $result = $this->db->selectOne($query, [$this->user['id']]);
        return $result ? $result['id'] : null;
    }
    
    private function canAccessPayroll($employeeId) {
        // Admin and HR can access all records
        if (in_array($this->user['role'], ['admin', 'hr'])) {
            return true;
        }
        
        // Users can access their own records
        $currentEmployeeId = $this->getCurrentEmployeeId();
        return $currentEmployeeId == $employeeId;
    }
    
    private function getEmployeesForPayroll($data) {
        $filters = ["e.status = 'active'"];
        $filterValues = [];
        
        if (!empty($data['employee_ids'])) {
            $placeholders = str_repeat('?,', count($data['employee_ids']) - 1) . '?';
            $filters[] = "e.id IN ($placeholders)";
            $filterValues = array_merge($filterValues, $data['employee_ids']);
        }
        
        if (!empty($data['department_id'])) {
            $filters[] = "e.department_id = ?";
            $filterValues[] = $data['department_id'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $filters);
        
        $query = "SELECT e.*, pos.title as position_title, d.name as department_name
                 FROM employees e
                 LEFT JOIN positions pos ON e.position_id = pos.id
                 LEFT JOIN departments d ON e.department_id = d.id
                 $whereClause
                 ORDER BY e.employee_id";
        
        return $this->db->select($query, $filterValues);
    }
    
    private function generateEmployeePayroll($employee, $payPeriod) {
        // Get attendance data for the pay period
        $attendanceData = $this->getAttendanceForPayPeriod($employee['id'], $payPeriod);
        
        // Calculate basic salary (pro-rated based on attendance)
        $basicSalary = $this->calculateBasicSalary($employee, $attendanceData);
        
        // Calculate allowances
        $allowances = $this->calculateAllowances($employee, $attendanceData);
        
        // Calculate overtime
        $overtimeAmount = $this->calculateOvertime($employee, $attendanceData);
        
        // Calculate deductions and net salary
        $calculations = $this->calculatePayroll($employee, $basicSalary, $allowances, $overtimeAmount);
        
        // Insert payroll record
        $query = "INSERT INTO payroll (
                 employee_id, pay_period, basic_salary, allowances, overtime_amount,
                 gross_salary, pf_deduction, esi_deduction, tds_deduction, professional_tax,
                 other_deductions, total_deductions, net_salary, status, created_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())";
        
        return $this->db->insert($query, [
            $employee['id'],
            $payPeriod,
            $calculations['basic_salary'],
            $calculations['allowances'],
            $calculations['overtime_amount'],
            $calculations['gross_salary'],
            $calculations['pf_deduction'],
            $calculations['esi_deduction'],
            $calculations['tds_deduction'],
            $calculations['professional_tax'],
            $calculations['other_deductions'],
            $calculations['total_deductions'],
            $calculations['net_salary']
        ]);
    }
    
    private function getAttendanceForPayPeriod($employeeId, $payPeriod) {
        $startDate = date('Y-m-01', strtotime($payPeriod));
        $endDate = date('Y-m-t', strtotime($payPeriod));
        
        $query = "SELECT 
                    COUNT(*) as working_days,
                    SUM(CASE WHEN status IN ('present', 'on_time') THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(COALESCE(total_hours, 0)) as total_hours,
                    SUM(COALESCE(overtime_hours, 0)) as overtime_hours
                 FROM attendance 
                 WHERE employee_id = ? AND date BETWEEN ? AND ?";
        
        $result = $this->db->selectOne($query, [$employeeId, $startDate, $endDate]);
        
        return [
            'working_days' => (int)$result['working_days'],
            'present_days' => (int)$result['present_days'],
            'late_days' => (int)$result['late_days'],
            'absent_days' => (int)$result['absent_days'],
            'total_hours' => (float)$result['total_hours'],
            'overtime_hours' => (float)$result['overtime_hours']
        ];
    }
    
    private function calculateBasicSalary($employee, $attendanceData) {
        $monthlySalary = (float)$employee['salary'];
        $workingDaysInMonth = 30; // Can be configured
        
        // Pro-rate based on present days
        if ($attendanceData['working_days'] > 0) {
            $attendanceRatio = $attendanceData['present_days'] / $workingDaysInMonth;
            return $monthlySalary * $attendanceRatio;
        }
        
        return $monthlySalary;
    }
    
    private function calculateAllowances($employee, $attendanceData) {
        // Basic allowances calculation - can be enhanced
        $basicSalary = (float)$employee['salary'];
        $allowancePercentage = 0.20; // 20% of basic salary
        
        return $basicSalary * $allowancePercentage;
    }
    
    private function calculateOvertime($employee, $attendanceData) {
        $hourlyRate = ((float)$employee['salary'] / 30) / 8; // Daily rate / 8 hours
        $overtimeRate = $hourlyRate * 1.5; // 1.5x for overtime
        
        return $attendanceData['overtime_hours'] * $overtimeRate;
    }
    
    private function calculatePayroll($employee, $basicSalary, $allowances, $overtimeAmount, $otherDeductions = 0) {
        $grossSalary = $basicSalary + $allowances + $overtimeAmount;
        
        // PF Calculation (12% of basic salary, max 1800)
        $pfDeduction = min($basicSalary * 0.12, 1800);
        
        // ESI Calculation (0.75% of gross salary, if gross <= 21000)
        $esiDeduction = $grossSalary <= 21000 ? $grossSalary * 0.0075 : 0;
        
        // Professional Tax (state-specific, simplified)
        $professionalTax = $this->calculateProfessionalTax($grossSalary);
        
        // TDS Calculation (simplified)
        $tdsDeduction = $this->calculateTDS($grossSalary, $employee);
        
        $totalDeductions = $pfDeduction + $esiDeduction + $professionalTax + $tdsDeduction + $otherDeductions;
        $netSalary = $grossSalary - $totalDeductions;
        
        return [
            'basic_salary' => round($basicSalary, 2),
            'allowances' => round($allowances, 2),
            'overtime_amount' => round($overtimeAmount, 2),
            'gross_salary' => round($grossSalary, 2),
            'pf_deduction' => round($pfDeduction, 2),
            'esi_deduction' => round($esiDeduction, 2),
            'tds_deduction' => round($tdsDeduction, 2),
            'professional_tax' => round($professionalTax, 2),
            'other_deductions' => round($otherDeductions, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_salary' => round($netSalary, 2)
        ];
    }
    
    private function calculateProfessionalTax($grossSalary) {
        // Simplified professional tax calculation
        if ($grossSalary <= 15000) {
            return 0;
        } elseif ($grossSalary <= 20000) {
            return 150;
        } else {
            return 200;
        }
    }
    
    private function calculateTDS($grossSalary, $employee) {
        // Simplified TDS calculation
        $annualSalary = $grossSalary * 12;
        
        if ($annualSalary <= 250000) {
            return 0;
        } elseif ($annualSalary <= 500000) {
            return ($annualSalary - 250000) * 0.05 / 12;
        } elseif ($annualSalary <= 1000000) {
            return (250000 * 0.05 + ($annualSalary - 500000) * 0.20) / 12;
        } else {
            return (250000 * 0.05 + 500000 * 0.20 + ($annualSalary - 1000000) * 0.30) / 12;
        }
    }
    
    private function generatePayslip($payrollId) {
        // Implementation for generating payslip document
        // This could generate PDF, send email, etc.
    }
    
    private function generatePayslipContent($payroll) {
        // Generate HTML content for payslip
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Payslip - {$payroll['employee_id']}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .employee-info { margin: 20px 0; }
                .payroll-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .payroll-table th, .payroll-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .payroll-table th { background-color: #f2f2f2; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>{$payroll['company_name']}</h1>
                <p>{$payroll['company_address']}</p>
                <h2>PAYSLIP</h2>
            </div>
            
            <div class='employee-info'>
                <p><strong>Employee ID:</strong> {$payroll['employee_id']}</p>
                <p><strong>Employee Name:</strong> {$payroll['first_name']} {$payroll['last_name']}</p>
                <p><strong>Department:</strong> {$payroll['department_name']}</p>
                <p><strong>Position:</strong> {$payroll['position_title']}</p>
                <p><strong>Pay Period:</strong> " . date('F Y', strtotime($payroll['pay_period'])) . "</p>
                <p><strong>PAN:</strong> {$payroll['pan_number']}</p>
                <p><strong>UAN:</strong> {$payroll['uan_number']}</p>
            </div>
            
            <table class='payroll-table'>
                <tr><th colspan='2'>EARNINGS</th></tr>
                <tr><td>Basic Salary</td><td>₹" . number_format($payroll['basic_salary'], 2) . "</td></tr>
                <tr><td>Allowances</td><td>₹" . number_format($payroll['allowances'], 2) . "</td></tr>
                <tr><td>Overtime</td><td>₹" . number_format($payroll['overtime_amount'], 2) . "</td></tr>
                <tr class='total-row'><td>Gross Salary</td><td>₹" . number_format($payroll['gross_salary'], 2) . "</td></tr>
                
                <tr><th colspan='2'>DEDUCTIONS</th></tr>
                <tr><td>PF Deduction</td><td>₹" . number_format($payroll['pf_deduction'], 2) . "</td></tr>
                <tr><td>ESI Deduction</td><td>₹" . number_format($payroll['esi_deduction'], 2) . "</td></tr>
                <tr><td>TDS</td><td>₹" . number_format($payroll['tds_deduction'], 2) . "</td></tr>
                <tr><td>Professional Tax</td><td>₹" . number_format($payroll['professional_tax'], 2) . "</td></tr>
                <tr><td>Other Deductions</td><td>₹" . number_format($payroll['other_deductions'], 2) . "</td></tr>
                <tr class='total-row'><td>Total Deductions</td><td>₹" . number_format($payroll['total_deductions'], 2) . "</td></tr>
                
                <tr class='total-row'><td><strong>NET SALARY</strong></td><td><strong>₹" . number_format($payroll['net_salary'], 2) . "</strong></td></tr>
            </table>
            
            <p><em>This is a computer-generated payslip and does not require a signature.</em></p>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    private function getPayrollSummaryReport($params) {
        $payPeriod = $params['pay_period'] ?? date('Y-m-01');
        
        $query = "SELECT 
                    e.employee_id, e.first_name, e.last_name, d.name as department_name,
                    p.basic_salary, p.allowances, p.overtime_amount, p.gross_salary,
                    p.total_deductions, p.net_salary, p.status
                 FROM employees e
                 LEFT JOIN payroll p ON e.id = p.employee_id AND p.pay_period = ?
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE e.status = 'active'
                 ORDER BY d.name, e.first_name, e.last_name";
        
        return $this->db->select($query, [$payPeriod]);
    }
    
    private function getDepartmentPayrollReport($params) {
        $payPeriod = $params['pay_period'] ?? date('Y-m-01');
        
        $query = "SELECT 
                    d.name as department_name,
                    COUNT(p.id) as employee_count,
                    SUM(p.gross_salary) as total_gross,
                    SUM(p.total_deductions) as total_deductions,
                    SUM(p.net_salary) as total_net,
                    AVG(p.net_salary) as avg_net_salary
                 FROM departments d
                 LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
                 LEFT JOIN payroll p ON e.id = p.employee_id AND p.pay_period = ?
                 GROUP BY d.id
                 ORDER BY d.name";
        
        return $this->db->select($query, [$payPeriod]);
    }
    
    private function getTaxReport($params) {
        $payPeriod = $params['pay_period'] ?? date('Y-m-01');
        
        $query = "SELECT 
                    e.employee_id, e.first_name, e.last_name, e.pan_number,
                    p.gross_salary, p.pf_deduction, p.esi_deduction, 
                    p.tds_deduction, p.professional_tax
                 FROM payroll p
                 JOIN employees e ON p.employee_id = e.id
                 WHERE p.pay_period = ? AND p.status = 'approved'
                 ORDER BY e.employee_id";
        
        return $this->db->select($query, [$payPeriod]);
    }
    
    private function getCostCenterReport($params) {
        $payPeriod = $params['pay_period'] ?? date('Y-m-01');
        
        $query = "SELECT 
                    d.name as cost_center,
                    COUNT(p.id) as headcount,
                    SUM(p.basic_salary) as basic_salary_cost,
                    SUM(p.allowances) as allowances_cost,
                    SUM(p.overtime_amount) as overtime_cost,
                    SUM(p.gross_salary) as total_cost
                 FROM payroll p
                 JOIN employees e ON p.employee_id = e.id
                 JOIN departments d ON e.department_id = d.id
                 WHERE p.pay_period = ? AND p.status = 'approved'
                 GROUP BY d.id
                 ORDER BY total_cost DESC";
        
        return $this->db->select($query, [$payPeriod]);
    }
}

?>