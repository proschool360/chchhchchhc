<?php
/**
 * Authentication Controller
 * Handles user authentication, registration, and account management
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class AuthController {
    private $auth;
    private $db;
    private $user;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->db = Database::getInstance();
    }
    
    /**
     * Set current user context
     */
    public function setUser($user) {
        $this->user = $user;
    }
    
    /**
     * User login
     */
    public function login() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'username' => 'required|string',
                'password' => 'required|string',
                'remember_me' => 'boolean'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            $username = $data['username'];
            $password = $data['password'];
            $rememberMe = isset($data['remember_me']) ? (bool)$data['remember_me'] : false;
            
            // Attempt authentication
            $result = $this->auth->authenticate($username, $password, $rememberMe);
            
            if ($result['success']) {
                // Log successful login
                $this->logActivity($result['user']['id'], 'login', 'User logged in successfully');
                
                Response::success([
                    'token' => $result['token'],
                    'user' => $result['user']
                ], 'Login successful');
            } else {
                // Log failed login attempt
                $this->logActivity(null, 'login_failed', 'Failed login attempt for: ' . $username, [
                    'username' => $username,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                Response::error($result['message'], 401);
            }
            
        } catch (Exception $e) {
            Response::serverError('Login failed: ' . $e->getMessage());
        }
    }
    
    /**
     * User registration
     */
    public function register() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'username' => 'required|string|min:3|max:50',
                'email' => 'required|email|max:100',
                'password' => 'required|password',
                'password_confirmation' => 'required|string',
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'role' => 'string|in:employee,manager,hr,admin'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Check password confirmation
            if ($data['password'] !== $data['password_confirmation']) {
                Response::validationError(['password_confirmation' => ['Password confirmation does not match']]);
                return;
            }
            
            // Check if username exists
            if ($this->usernameExists($data['username'])) {
                Response::validationError(['username' => ['Username already exists']]);
                return;
            }
            
            // Check if email exists
            if ($this->emailExists($data['email'])) {
                Response::validationError(['email' => ['Email already exists']]);
                return;
            }
            
            // Register user
            $result = $this->auth->register($data);
            
            if ($result['success']) {
                // Create employee record if role is employee
                if (($data['role'] ?? 'employee') === 'employee') {
                    $this->createEmployeeRecord($result['user_id'], $data);
                }
                
                // Log registration
                $this->logActivity($result['user_id'], 'register', 'User registered successfully');
                
                Response::created([
                    'user_id' => $result['user_id']
                ], 'Registration successful');
            } else {
                Response::error($result['message']);
            }
            
        } catch (Exception $e) {
            Response::serverError('Registration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Forgot password
     */
    public function forgotPassword() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'email' => 'required|email'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            $result = $this->auth->resetPassword($data['email']);
            
            if ($result['success']) {
                Response::success(null, 'Password reset link sent to your email');
            } else {
                Response::error($result['message']);
            }
            
        } catch (Exception $e) {
            Response::serverError('Password reset failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'token' => 'required|string',
                'password' => 'required|password',
                'password_confirmation' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Check password confirmation
            if ($data['password'] !== $data['password_confirmation']) {
                Response::validationError(['password_confirmation' => ['Password confirmation does not match']]);
                return;
            }
            
            // Verify reset token
            $user = $this->getUserByResetToken($data['token']);
            
            if (!$user) {
                Response::error('Invalid or expired reset token', 400);
                return;
            }
            
            // Update password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL, updated_at = NOW() WHERE id = ?";
            $result = $this->db->update($query, [$hashedPassword, $user['id']]);
            
            if ($result) {
                // Log password reset
                $this->logActivity($user['id'], 'password_reset', 'Password reset successfully');
                
                Response::success(null, 'Password reset successful');
            } else {
                Response::serverError('Failed to reset password');
            }
            
        } catch (Exception $e) {
            Response::serverError('Password reset failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Refresh token
     */
    public function refresh() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'refresh_token' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Verify refresh token
            $payload = $this->auth->verifyToken($data['refresh_token']);
            
            if ($payload['type'] !== 'refresh') {
                Response::error('Invalid refresh token', 400);
                return;
            }
            
            // Generate new access token
            $newToken = $this->auth->generateToken($payload['user_id'], $payload['role']);
            
            Response::success([
                'token' => $newToken
            ], 'Token refreshed successfully');
            
        } catch (Exception $e) {
            Response::error('Token refresh failed: ' . $e->getMessage(), 401);
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        try {
            // Log logout activity
            $this->logActivity($this->user['id'], 'logout', 'User logged out');
            
            // In a real implementation, you might want to blacklist the token
            // For now, we'll just return success
            Response::success(null, 'Logout successful');
            
        } catch (Exception $e) {
            Response::serverError('Logout failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get current user info
     */
    public function me() {
        try {
            // Get user details
            $query = "SELECT u.id, u.username, u.email, u.role, u.status, u.created_at, u.updated_at,
                            e.employee_id, e.first_name, e.last_name, e.phone, e.date_of_birth, e.hire_date,
                            d.name as department_name, p.title as position_title
                     FROM users u
                     LEFT JOIN employees e ON u.id = e.user_id
                     LEFT JOIN departments d ON e.department_id = d.id
                     LEFT JOIN positions p ON e.position_id = p.id
                     WHERE u.id = ?";
            
            $user = $this->db->selectOne($query, [$this->user['id']]);
            
            if (!$user) {
                Response::notFound('User not found');
                return;
            }
            
            // Remove sensitive data
            unset($user['password']);
            
            Response::success($user, 'User information retrieved successfully');
            
        } catch (Exception $e) {
            Response::serverError('Failed to get user info: ' . $e->getMessage());
        }
    }
    
    /**
     * Change password
     */
    public function changePassword() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'current_password' => 'required|string',
                'new_password' => 'required|password',
                'new_password_confirmation' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Check password confirmation
            if ($data['new_password'] !== $data['new_password_confirmation']) {
                Response::validationError(['new_password_confirmation' => ['Password confirmation does not match']]);
                return;
            }
            
            $result = $this->auth->changePassword(
                $this->user['id'],
                $data['current_password'],
                $data['new_password']
            );
            
            if ($result['success']) {
                // Log password change
                $this->logActivity($this->user['id'], 'password_change', 'Password changed successfully');
                
                Response::success(null, 'Password changed successfully');
            } else {
                Response::error($result['message'], 400);
            }
            
        } catch (Exception $e) {
            Response::serverError('Password change failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Update profile
     */
    public function updateProfile() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'first_name' => 'string|max:50',
                'last_name' => 'string|max:50',
                'phone' => 'string|phone',
                'email' => 'email|max:100'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            $this->db->beginTransaction();
            
            // Update user table
            if (isset($data['email'])) {
                // Check if email is already taken by another user
                $existingUser = $this->db->selectOne(
                    "SELECT id FROM users WHERE email = ? AND id != ?",
                    [$data['email'], $this->user['id']]
                );
                
                if ($existingUser) {
                    Response::validationError(['email' => ['Email already exists']]);
                    return;
                }
                
                $this->db->update(
                    "UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?",
                    [$data['email'], $this->user['id']]
                );
            }
            
            // Update employee table
            $employeeFields = ['first_name', 'last_name', 'phone'];
            $updateFields = [];
            $updateValues = [];
            
            foreach ($employeeFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $data[$field];
                }
            }
            
            if (!empty($updateFields)) {
                $updateFields[] = "updated_at = NOW()";
                $updateValues[] = $this->user['id'];
                
                $query = "UPDATE employees SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
                $this->db->update($query, $updateValues);
            }
            
            $this->db->commit();
            
            // Log profile update
            $this->logActivity($this->user['id'], 'profile_update', 'Profile updated successfully');
            
            Response::success(null, 'Profile updated successfully');
            
        } catch (Exception $e) {
            $this->db->rollback();
            Response::serverError('Profile update failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if username exists
     */
    private function usernameExists($username) {
        $query = "SELECT COUNT(*) as count FROM users WHERE username = ?";
        $result = $this->db->selectOne($query, [$username]);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Check if email exists
     */
    private function emailExists($email) {
        $query = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $result = $this->db->selectOne($query, [$email]);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Get user by reset token
     */
    private function getUserByResetToken($token) {
        $query = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
        return $this->db->selectOne($query, [$token]);
    }
    
    /**
     * Create employee record
     */
    private function createEmployeeRecord($userId, $data) {
        $employeeId = $this->generateEmployeeId();
        
        $query = "INSERT INTO employees (user_id, employee_id, first_name, last_name, email, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, 'active', NOW())";
        
        $this->db->insert($query, [
            $userId,
            $employeeId,
            $data['first_name'],
            $data['last_name'],
            $data['email']
        ]);
    }
    
    /**
     * Generate unique employee ID
     */
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
    
    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $description, $metadata = null) {
        try {
            $query = "INSERT INTO user_activities (user_id, action, description, metadata, ip_address, user_agent, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $this->db->insert($query, [
                $userId,
                $action,
                $description,
                $metadata ? json_encode($metadata) : null,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
}

?>