<?php
/**
 * HRMS API Router
 * Main entry point for all API requests
 */

// Define access constant
if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set content type
header('Content-Type: application/json; charset=utf-8');


class Router {
    private $routes = [];
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->setupRoutes();
    }
    
    /**
     * Setup all API routes
     */
    private function setupRoutes() {
        // Authentication routes
        $this->addRoute('POST', '/auth/login', 'AuthController', 'login', false);
        $this->addRoute('POST', '/auth/register', 'AuthController', 'register', false);
        $this->addRoute('POST', '/auth/forgot-password', 'AuthController', 'forgotPassword', false);
        $this->addRoute('POST', '/auth/reset-password', 'AuthController', 'resetPassword', false);
        $this->addRoute('POST', '/auth/refresh', 'AuthController', 'refresh', false);
        $this->addRoute('POST', '/auth/logout', 'AuthController', 'logout', true);
        $this->addRoute('GET', '/auth/me', 'AuthController', 'me', true);
        $this->addRoute('GET', '/auth/profile', 'AuthController', 'me', true);
        $this->addRoute('PUT', '/auth/change-password', 'AuthController', 'changePassword', true);
        
        // Employee routes
        $this->addRoute('GET', '/employees', 'EmployeeController', 'index', true);
        $this->addRoute('GET', '/employees/{id}', 'EmployeeController', 'show', true);
        $this->addRoute('POST', '/employees', 'EmployeeController', 'store', true, 'hr');
        $this->addRoute('PUT', '/employees/{id}', 'EmployeeController', 'update', true, 'hr');
        $this->addRoute('DELETE', '/employees/{id}', 'EmployeeController', 'delete', true, 'admin');
        $this->addRoute('GET', '/employees/{id}/documents', 'EmployeeController', 'getDocuments', true);
        $this->addRoute('POST', '/employees/{id}/documents', 'EmployeeController', 'uploadDocument', true);
        
        // Department routes
        $this->addRoute('GET', '/departments', 'DepartmentController', 'index', true);
        $this->addRoute('GET', '/departments/managers', 'DepartmentController', 'getManagers', true);
        $this->addRoute('GET', '/departments/{id}', 'DepartmentController', 'show', true);
        $this->addRoute('POST', '/departments', 'DepartmentController', 'store', true, 'hr');
        $this->addRoute('PUT', '/departments/{id}', 'DepartmentController', 'update', true, 'hr');
        $this->addRoute('DELETE', '/departments/{id}', 'DepartmentController', 'delete', true, 'admin');
        
        // Position routes
        $this->addRoute('GET', '/positions', 'PositionController', 'index', true);
        $this->addRoute('GET', '/positions/{id}', 'PositionController', 'show', true);
        $this->addRoute('POST', '/positions', 'PositionController', 'store', true, 'hr');
        $this->addRoute('PUT', '/positions/{id}', 'PositionController', 'update', true, 'hr');
        $this->addRoute('DELETE', '/positions/{id}', 'PositionController', 'delete', true, 'admin');
        
        // Attendance routes
        $this->addRoute('GET', '/attendance', 'AttendanceController', 'index', true);
        $this->addRoute('GET', '/attendance/{id}', 'AttendanceController', 'show', true);
        $this->addRoute('POST', '/attendance/checkin', 'AttendanceController', 'checkIn', true);
        $this->addRoute('POST', '/attendance/checkout', 'AttendanceController', 'checkOut', true);
        $this->addRoute('GET', '/attendance/my-records', 'AttendanceController', 'myRecords', true);
        $this->addRoute('GET', '/attendance/reports', 'AttendanceController', 'reports', true, 'hr');
        
        // Extended Attendance routes
        $this->addRoute('POST', '/attendance/clock-in/qr', 'ExtendedAttendanceController', 'clockInQR', true);
        $this->addRoute('POST', '/attendance/clock-in/rfid', 'ExtendedAttendanceController', 'clockInRFID', true);
        $this->addRoute('POST', '/attendance/clock-in/biometric', 'ExtendedAttendanceController', 'clockInBiometric', true);
        $this->addRoute('POST', '/attendance/clock-in/manual', 'ExtendedAttendanceController', 'clockInManual', true, 'hr');
        $this->addRoute('POST', '/attendance/clock-out', 'ExtendedAttendanceController', 'clockOut', true);
        $this->addRoute('GET', '/attendance/summary', 'ExtendedAttendanceController', 'getAttendanceSummary', true);
        $this->addRoute('GET', '/attendance/monthly-report', 'ExtendedAttendanceController', 'generateMonthlyReport', true);
        
        // Leave routes
        $this->addRoute('GET', '/leaves', 'LeaveController', 'index', true);
        $this->addRoute('GET', '/leaves/{id}', 'LeaveController', 'show', true);
        $this->addRoute('POST', '/leaves', 'LeaveController', 'store', true);
        $this->addRoute('PUT', '/leaves/{id}', 'LeaveController', 'update', true);
        $this->addRoute('POST', '/leaves/{id}/approve', 'LeaveController', 'approve', true, 'manager');
        $this->addRoute('POST', '/leaves/{id}/reject', 'LeaveController', 'reject', true, 'manager');
        $this->addRoute('GET', '/leave-types', 'LeaveController', 'getLeaveTypes', true);
        $this->addRoute('GET', '/leaves/my-requests', 'LeaveController', 'myRequests', true);
        
        // Payroll routes
        $this->addRoute('GET', '/payroll', 'PayrollController', 'index', true, 'hr');
        $this->addRoute('GET', '/payroll/{id}', 'PayrollController', 'show', true);
        $this->addRoute('POST', '/payroll/generate', 'PayrollController', 'generate', true, 'hr');
        $this->addRoute('GET', '/payroll/my-payslips', 'PayrollController', 'myPayslips', true);
        $this->addRoute('GET', '/payroll/{id}/download', 'PayrollController', 'downloadPayslip', true);
        
        // Performance routes
        $this->addRoute('GET', '/performance', 'PerformanceController', 'index', true);
        $this->addRoute('GET', '/performance/{id}', 'PerformanceController', 'show', true);
        $this->addRoute('POST', '/performance', 'PerformanceController', 'store', true, 'manager');
        $this->addRoute('PUT', '/performance/{id}', 'PerformanceController', 'update', true, 'manager');
        $this->addRoute('GET', '/performance/my-reviews', 'PerformanceController', 'myReviews', true);
        
        // Recruitment routes
        $this->addRoute('GET', '/jobs', 'RecruitmentController', 'index', false);
        $this->addRoute('GET', '/jobs/{id}', 'RecruitmentController', 'show', false);
        $this->addRoute('POST', '/jobs', 'RecruitmentController', 'store', true, 'hr');
        $this->addRoute('PUT', '/jobs/{id}', 'RecruitmentController', 'update', true, 'hr');
        $this->addRoute('DELETE', '/jobs/{id}', 'RecruitmentController', 'delete', true, 'hr');
        $this->addRoute('POST', '/jobs/{id}/apply', 'RecruitmentController', 'apply', false);
        $this->addRoute('GET', '/applications', 'RecruitmentController', 'getApplications', true, 'hr');
        $this->addRoute('PUT', '/applications/{id}/status', 'RecruitmentController', 'updateApplicationStatus', true, 'hr');
        
        // Training routes
        $this->addRoute('GET', '/training', 'TrainingController', 'index', true);
        $this->addRoute('GET', '/training/{id}', 'TrainingController', 'show', true);
        $this->addRoute('POST', '/training', 'TrainingController', 'store', true, 'hr');
        $this->addRoute('PUT', '/training/{id}', 'TrainingController', 'update', true, 'hr');
        $this->addRoute('POST', '/training/{id}/enroll', 'TrainingController', 'enroll', true);
        $this->addRoute('POST', '/training/{id}/complete', 'TrainingController', 'complete', true);
        $this->addRoute('GET', '/training/my-trainings', 'TrainingController', 'myTrainings', true);
        
        // Reports routes
        $this->addRoute('GET', '/reports/dashboard', 'ReportsController', 'getDashboard', true, 'hr');
        $this->addRoute('GET', '/reports/attendance', 'ReportsController', 'attendanceReport', true, 'hr');
        $this->addRoute('GET', '/reports/leave', 'ReportsController', 'leaveReport', true, 'hr');
        $this->addRoute('GET', '/reports/payroll', 'ReportsController', 'payrollReport', true, 'hr');
        $this->addRoute('GET', '/reports/performance', 'ReportsController', 'performanceReport', true, 'hr');
        $this->addRoute('GET', '/reports/headcount', 'ReportsController', 'headcountReport', true, 'hr');
        $this->addRoute('GET', '/reports/attendance/daily', 'ReportsController', 'getDailyAttendanceReport', true, 'hr');
        $this->addRoute('GET', '/reports/attendance/weekly', 'ReportsController', 'getWeeklyAttendanceReport', true, 'hr');
        $this->addRoute('GET', '/reports/attendance/monthly', 'ReportsController', 'getMonthlyAttendanceReport', true, 'hr');
        $this->addRoute('GET', '/reports/attendance/export', 'ReportsController', 'exportAttendanceData', true, 'hr');
        
        // ID Card routes
        $this->addRoute('GET', '/idcards', 'IdCardController', 'index', true, 'hr');
        $this->addRoute('GET', '/idcards/{id}', 'IdCardController', 'show', true, 'hr');
        $this->addRoute('POST', '/idcards/generate', 'IdCardController', 'generate', true, 'hr');
        $this->addRoute('POST', '/idcards/bulk-generate', 'IdCardController', 'bulkGenerateIdCards', true, 'hr');
        $this->addRoute('GET', '/idcards/{id}/download', 'IdCardController', 'download', true, 'hr');
        $this->addRoute('GET', '/idcards/bulk-download', 'IdCardController', 'bulkDownload', true, 'hr');
        $this->addRoute('GET', '/idcards/{id}/preview', 'IdCardController', 'preview', true, 'hr');
        $this->addRoute('GET', '/idcards/templates', 'IdCardController', 'getTemplates', true, 'hr');
        $this->addRoute('GET', '/idcards/templates/layout-types', 'IdCardController', 'getLayoutTypes', true, 'hr');
        $this->addRoute('GET', '/idcards/templates/{id}', 'IdCardController', 'getTemplate', true, 'hr');
        $this->addRoute('POST', '/idcards/templates', 'IdCardController', 'createTemplate', true, 'hr');
        $this->addRoute('POST', '/idcards/templates/variations', 'IdCardController', 'createTemplateVariations', true, 'hr');
        $this->addRoute('GET', '/idcards/templates/stats', 'IdCardController', 'getTemplateStats', true, 'hr');
        $this->addRoute('PUT', '/idcards/templates/{id}', 'IdCardController', 'updateTemplate', true, 'hr');
        $this->addRoute('DELETE', '/idcards/templates/{id}', 'IdCardController', 'deleteTemplate', true, 'hr');
        $this->addRoute('POST', '/idcards/templates/{id}/duplicate', 'IdCardController', 'duplicateTemplate', true, 'hr');
        $this->addRoute('PUT', '/idcards/templates/{id}/set-default', 'IdCardController', 'setDefaultTemplate', true, 'hr');
        $this->addRoute('GET', '/idcards/qr-code/{employeeId}', 'IdCardController', 'getQRCode', true, 'hr');
        $this->addRoute('PUT', '/employees/{id}/qr-rfid', 'IdCardController', 'updateEmployeeQRRFID', true, 'hr');
        
        // Log routes
        $this->addRoute('POST', '/logs/write', 'LogController', 'writeLog', false);
        $this->addRoute('GET', '/logs', 'LogController', 'getLogs', true, 'admin');
        
        // Settings routes
        $this->addRoute('GET', '/settings', 'SettingsController', 'index', true, 'admin');
        $this->addRoute('PUT', '/settings', 'SettingsController', 'update', true, 'admin');
        $this->addRoute('GET', '/settings/{key}', 'SettingsController', 'getSetting', true, 'admin');
        $this->addRoute('PUT', '/settings/{key}', 'SettingsController', 'updateSetting', true, 'admin');
        
        // Deduction Rules routes
        $this->addRoute('GET', '/settings/deduction-rules', 'SettingsController', 'getDeductionRules', true, 'hr');
        $this->addRoute('GET', '/settings/deduction-rules/{id}', 'SettingsController', 'getDeductionRule', true, 'hr');
        $this->addRoute('POST', '/settings/deduction-rules', 'SettingsController', 'createDeductionRule', true, 'hr');
        $this->addRoute('PUT', '/settings/deduction-rules/{id}', 'SettingsController', 'updateDeductionRule', true, 'hr');
        $this->addRoute('DELETE', '/settings/deduction-rules/{id}', 'SettingsController', 'deleteDeductionRule', true, 'hr');
        $this->addRoute('PUT', '/settings/deduction-rules/{id}/set-default', 'SettingsController', 'setDefaultDeductionRule', true, 'hr');
        
        // Overtime Rules routes
        $this->addRoute('GET', '/settings/overtime-rules', 'SettingsController', 'getOvertimeRules', true, 'hr');
        $this->addRoute('GET', '/settings/overtime-rules/{id}', 'SettingsController', 'getOvertimeRule', true, 'hr');
        $this->addRoute('POST', '/settings/overtime-rules', 'SettingsController', 'createOvertimeRule', true, 'hr');
        $this->addRoute('PUT', '/settings/overtime-rules/{id}', 'SettingsController', 'updateOvertimeRule', true, 'hr');
        $this->addRoute('DELETE', '/settings/overtime-rules/{id}', 'SettingsController', 'deleteOvertimeRule', true, 'hr');
        $this->addRoute('PUT', '/settings/overtime-rules/{id}/set-default', 'SettingsController', 'setDefaultOvertimeRule', true, 'hr');
        
        // Attendance Devices routes
        $this->addRoute('GET', '/settings/attendance-devices', 'SettingsController', 'getAttendanceDevices', true, 'hr');
        $this->addRoute('GET', '/settings/attendance-devices/{id}', 'SettingsController', 'getAttendanceDevice', true, 'hr');
        $this->addRoute('POST', '/settings/attendance-devices', 'SettingsController', 'createAttendanceDevice', true, 'hr');
        $this->addRoute('PUT', '/settings/attendance-devices/{id}', 'SettingsController', 'updateAttendanceDevice', true, 'hr');
        $this->addRoute('DELETE', '/settings/attendance-devices/{id}', 'SettingsController', 'deleteAttendanceDevice', true, 'hr');
        $this->addRoute('POST', '/settings/attendance-devices/{id}/test', 'SettingsController', 'testAttendanceDevice', true, 'hr');
        $this->addRoute('POST', '/settings/attendance-devices/{id}/sync', 'SettingsController', 'syncAttendanceDevice', true, 'hr');
        
        // Work Schedules routes
        $this->addRoute('GET', '/settings/work-schedules', 'SettingsController', 'getWorkSchedules', true, 'hr');
        $this->addRoute('GET', '/settings/work-schedules/{id}', 'SettingsController', 'getWorkSchedule', true, 'hr');
        $this->addRoute('POST', '/settings/work-schedules', 'SettingsController', 'createWorkSchedule', true, 'hr');
        $this->addRoute('PUT', '/settings/work-schedules/{id}', 'SettingsController', 'updateWorkSchedule', true, 'hr');
        $this->addRoute('DELETE', '/settings/work-schedules/{id}', 'SettingsController', 'deleteWorkSchedule', true, 'hr');
        $this->addRoute('PUT', '/settings/work-schedules/{id}/set-default', 'SettingsController', 'setDefaultWorkSchedule', true, 'hr');
        
        // File upload routes
        $this->addRoute('POST', '/upload', 'FileController', 'upload', true);
        $this->addRoute('GET', '/files/{id}', 'FileController', 'download', true);
        $this->addRoute('DELETE', '/files/{id}', 'FileController', 'delete', true);
    }
    
    /**
     * Add a route
     */
    private function addRoute($method, $path, $controller, $action, $requireAuth = true, $requiredRole = null) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'requireAuth' => $requireAuth,
            'requiredRole' => $requiredRole
        ];
    }
    
    /**
     * Handle incoming request
     */
    public function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = $this->getPath();
            
            // Find matching route
            $route = $this->findRoute($method, $path);
            
            if (!$route) {
                Response::notFound('Endpoint not found');
                return;
            }
            
            // Handle authentication
            $user = null;
            if ($route['requireAuth']) {
                $user = $this->authenticate();
                
                if (!$user) {
                    Response::unauthorized('Authentication required');
                    return;
                }
                
                // Check role permissions
                if ($route['requiredRole'] && !$this->auth->hasPermission($user['role'], $route['requiredRole'])) {
                    Response::forbidden('Insufficient permissions');
                    return;
                }
            }
            
            // Load controller
            $controllerFile = __DIR__ . '/../controllers/' . $route['controller'] . '.php';
            
            if (!file_exists($controllerFile)) {
                Response::serverError('Controller not found');
                return;
            }
            
            require_once $controllerFile;
            
            // Instantiate controller
            $controllerClass = $route['controller'];
            if (!class_exists($controllerClass)) {
                Response::serverError('Controller class not found');
                return;
            }
            
            $controller = new $controllerClass();
            
            // Check if method exists
            if (!method_exists($controller, $route['action'])) {
                Response::serverError('Controller method not found');
                return;
            }
            
            // Set user context
            if ($user && method_exists($controller, 'setUser')) {
                $controller->setUser($user);
            }
            
            // Extract path parameters
            $params = $this->extractParams($route['path'], $path);
            
            // Call controller method
            call_user_func_array([$controller, $route['action']], $params);
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Get request path
     */
    private function getPath() {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        
        // Remove base path if API is in subdirectory
        // Check for different possible base paths
        $basePaths = [
            '/hrms/backend/api',  // Local development
            '/backend/api',       // Some deployments
            '/api'                // Direct API folder in public_html
        ];
        
        foreach ($basePaths as $basePath) {
            if (strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath));
                break;
            }
        }
        
        return $path ?: '/';
    }
    
    /**
     * Find matching route
     */
    private function findRoute($method, $path) {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path)) {
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Check if path matches route pattern
     */
    private function matchPath($pattern, $path) {
        // Convert pattern to regex
        $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        return preg_match($regex, $path);
    }
    
    /**
     * Extract parameters from path
     */
    private function extractParams($pattern, $path) {
        $params = [];
        
        // Get parameter names from pattern
        preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
        
        // Get parameter values from path
        $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $path, $matches)) {
            array_shift($matches); // Remove full match
            
            // Combine parameter names with values
            for ($i = 0; $i < count($paramNames[1]); $i++) {
                if (isset($matches[$i])) {
                    $params[$paramNames[1][$i]] = $matches[$i];
                }
            }
        }
        
        return array_values($params);
    }
    
    /**
     * Authenticate user
     */
    private function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$authHeader || !preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return null;
        }
        
        $token = $matches[1];
        
        try {
            $payload = $this->auth->verifyToken($token);
            return [
                'id' => $payload['user_id'],
                'role' => $payload['role']
            ];
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get request data
     */
    public static function getRequestData() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            return json_decode($input, true) ?: [];
        }
        
        return $_POST;
    }
    
    /**
     * Get query parameters
     */
    public static function getQueryParams() {
        return $_GET;
    }
}

// Initialize and handle request
try {
    $router = new Router();
    $router->handleRequest();
} catch (Exception $e) {
    Response::handleException($e);
}

?>