<?php
/**
 * Authentication and Authorization Class
 * Handles JWT tokens, user authentication, and role-based access control
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $secretKey;
    private $algorithm;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->secretKey = JWT_SECRET;
        $this->algorithm = JWT_ALGORITHM;
    }
    
    /**
     * Generate JWT token
     */
    public function generateToken($userId, $userRole, $rememberMe = false) {
        $issuedAt = time();
        $expiry = $rememberMe ? $issuedAt + JWT_REFRESH_EXPIRY : $issuedAt + JWT_EXPIRY;
        
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'hrms.local',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'hrms.local',
            'iat' => $issuedAt,
            'exp' => $expiry,
            'user_id' => $userId,
            'role' => $userRole,
            'type' => $rememberMe ? 'refresh' : 'access'
        ];
        
        return $this->encode($payload);
    }
    
    /**
     * Verify and decode JWT token
     */
    public function verifyToken($token) {
        try {
            $payload = $this->decode($token);
            
            // Check if token is expired
            if ($payload['exp'] < time()) {
                throw new Exception('Token has expired');
            }
            
            // Verify user still exists and is active
            $user = $this->getUserById($payload['user_id']);
            if (!$user || $user['status'] !== 'active') {
                throw new Exception('User not found or inactive');
            }
            
            return $payload;
        } catch (Exception $e) {
            throw new Exception('Invalid token: ' . $e->getMessage());
        }
    }
    
    /**
     * Authenticate user with username/email and password
     */
    public function authenticate($username, $password, $rememberMe = false) {
        try {
            // Check login attempts
            if ($this->isAccountLocked($username)) {
                throw new Exception('Account is temporarily locked due to multiple failed login attempts');
            }
            
            // Find user by username or email
            $user = $this->getUserByUsernameOrEmail($username);
            
            if (!$user) {
                $this->recordFailedLogin($username);
                throw new Exception('Invalid credentials');
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->recordFailedLogin($username);
                throw new Exception('Invalid credentials');
            }
            
            // Check if user is active
            if ($user['status'] !== 'active') {
                throw new Exception('Account is not active');
            }
            
            // Clear failed login attempts
            $this->clearFailedLogins($username);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Generate token
            $token = $this->generateToken($user['id'], $user['role'], $rememberMe);
            
            // Get employee details
            $employee = $this->getEmployeeByUserId($user['id']);
            
            return [
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'employee' => $employee
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Register new user
     */
    public function register($userData) {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Validate password strength
            if (!$this->validatePassword($userData['password'])) {
                throw new Exception('Password does not meet security requirements');
            }
            
            // Check if username already exists
            if ($this->getUserByUsername($userData['username'])) {
                throw new Exception('Username already exists');
            }
            
            // Check if email already exists
            if ($this->getUserByEmail($userData['email'])) {
                throw new Exception('Email already exists');
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $query = "INSERT INTO users (username, email, password, role, status, created_at) 
                     VALUES (?, ?, ?, ?, 'active', NOW())";
            
            $userId = $this->db->insert($query, [
                $userData['username'],
                $userData['email'],
                $hashedPassword,
                $userData['role'] ?? 'employee'
            ]);
            
            if ($userId) {
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'message' => 'User registered successfully'
                ];
            } else {
                throw new Exception('Failed to register user');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get user
            $user = $this->getUserById($userId);
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Validate new password
            if (!$this->validatePassword($newPassword)) {
                throw new Exception('New password does not meet security requirements');
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $result = $this->db->update($query, [$hashedPassword, $userId]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            } else {
                throw new Exception('Failed to change password');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reset password
     */
    public function resetPassword($email) {
        try {
            $user = $this->getUserByEmail($email);
            if (!$user) {
                throw new Exception('Email not found');
            }
            
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token (you might want to create a password_resets table)
            $query = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
            $this->db->update($query, [$resetToken, $expiry, $user['id']]);
            
            // Send reset email (implement email sending)
            // $this->sendPasswordResetEmail($user['email'], $resetToken);
            
            return [
                'success' => true,
                'message' => 'Password reset link sent to your email',
                'reset_token' => $resetToken // Remove this in production
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if user has permission
     */
    public function hasPermission($userRole, $requiredRole) {
        $roleHierarchy = [
            'employee' => 1,
            'manager' => 2,
            'hr' => 3,
            'admin' => 4
        ];
        
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Check if user can access resource
     */
    public function canAccess($userId, $resourceOwnerId, $userRole) {
        // Admin and HR can access everything
        if (in_array($userRole, ['admin', 'hr'])) {
            return true;
        }
        
        // Users can access their own resources
        if ($userId == $resourceOwnerId) {
            return true;
        }
        
        // Managers can access their subordinates' resources
        if ($userRole === 'manager') {
            return $this->isSubordinate($userId, $resourceOwnerId);
        }
        
        return false;
    }
    
    /**
     * Validate password strength
     */
    private function validatePassword($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return false;
        }
        
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        if (PASSWORD_REQUIRE_SYMBOLS && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get user by ID
     */
    private function getUserById($id) {
        $query = "SELECT * FROM users WHERE id = ?";
        return $this->db->selectOne($query, [$id]);
    }
    
    /**
     * Get user by username
     */
    private function getUserByUsername($username) {
        $query = "SELECT * FROM users WHERE username = ?";
        return $this->db->selectOne($query, [$username]);
    }
    
    /**
     * Get user by email
     */
    private function getUserByEmail($email) {
        $query = "SELECT * FROM users WHERE email = ?";
        return $this->db->selectOne($query, [$email]);
    }
    
    /**
     * Get user by username or email
     */
    private function getUserByUsernameOrEmail($username) {
        $query = "SELECT * FROM users WHERE username = ? OR email = ?";
        return $this->db->selectOne($query, [$username, $username]);
    }
    
    /**
     * Get employee by user ID
     */
    private function getEmployeeByUserId($userId) {
        $query = "SELECT e.*, d.name as department_name, p.title as position_title 
                 FROM employees e 
                 LEFT JOIN departments d ON e.department_id = d.id 
                 LEFT JOIN positions p ON e.position_id = p.id 
                 WHERE e.user_id = ?";
        return $this->db->selectOne($query, [$userId]);
    }
    
    /**
     * Check if account is locked
     */
    private function isAccountLocked($username) {
        // Implement login attempt tracking
        return false; // Simplified for now
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedLogin($username) {
        // Implement failed login tracking
    }
    
    /**
     * Clear failed login attempts
     */
    private function clearFailedLogins($username) {
        // Implement clearing failed login attempts
    }
    
    /**
     * Update last login time
     */
    private function updateLastLogin($userId) {
        $query = "UPDATE users SET updated_at = NOW() WHERE id = ?";
        $this->db->update($query, [$userId]);
    }
    
    /**
     * Check if user is subordinate
     */
    private function isSubordinate($managerId, $employeeId) {
        $query = "SELECT COUNT(*) as count FROM employees WHERE manager_id = ? AND user_id = ?";
        $result = $this->db->selectOne($query, [$managerId, $employeeId]);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Simple JWT encode (replace with proper JWT library in production)
     */
    private function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secretKey, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Simple JWT decode (replace with proper JWT library in production)
     */
    private function decode($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Verify signature
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secretKey, true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if (!hash_equals($base64Signature, $expectedSignature)) {
            throw new Exception('Invalid token signature');
        }
        
        // Decode payload
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload));
        return json_decode($payload, true);
    }
}

?>