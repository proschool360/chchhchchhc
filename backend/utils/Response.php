<?php
/**
 * API Response Utility Class
 * Standardizes API responses across the HRMS system
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

class Response {
    
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], $statusCode);
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'An error occurred', $statusCode = 400, $errors = null) {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], $statusCode);
    }
    
    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], 422);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 401);
    }
    
    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Access forbidden') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 403);
    }
    
    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 404);
    }
    
    /**
     * Send server error response
     */
    public static function serverError($message = 'Internal server error') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 500);
    }
    
    /**
     * Send paginated response
     */
    public static function paginated($data, $pagination, $message = 'Data retrieved successfully') {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'current_page' => (int)$pagination['current_page'],
                'per_page' => (int)$pagination['per_page'],
                'total_records' => (int)$pagination['total_records'],
                'total_pages' => (int)ceil($pagination['total_records'] / $pagination['per_page']),
                'has_next' => $pagination['current_page'] < ceil($pagination['total_records'] / $pagination['per_page']),
                'has_prev' => $pagination['current_page'] > 1
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], 200);
    }
    
    /**
     * Send created response
     */
    public static function created($data = null, $message = 'Resource created successfully') {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], 201);
    }
    
    /**
     * Send updated response
     */
    public static function updated($data = null, $message = 'Resource updated successfully') {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], 200);
    }
    
    /**
     * Send deleted response
     */
    public static function deleted($message = 'Resource deleted successfully') {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 200);
    }
    
    /**
     * Send no content response
     */
    public static function noContent() {
        http_response_code(204);
        exit;
    }
    
    /**
     * Send custom response
     */
    public static function custom($data, $statusCode = 200) {
        self::sendResponse($data, $statusCode);
    }
    
    /**
     * Send file download response
     */
    public static function download($filePath, $fileName = null, $mimeType = null) {
        if (!file_exists($filePath)) {
            self::notFound('File not found');
            return;
        }
        
        $fileName = $fileName ?: basename($filePath);
        $mimeType = $mimeType ?: self::getMimeType($filePath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Send file view response
     */
    public static function view($filePath, $mimeType = null) {
        if (!file_exists($filePath)) {
            self::notFound('File not found');
            return;
        }
        
        $mimeType = $mimeType ?: self::getMimeType($filePath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=3600');
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Send response with proper headers
     */
    private static function sendResponse($data, $statusCode = 200) {
        // Set HTTP response code
        http_response_code($statusCode);
        
        // Set headers
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }
        
        // Send JSON response
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Get MIME type of file
     */
    private static function getMimeType($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    /**
     * Format validation errors
     */
    public static function formatValidationErrors($errors) {
        $formatted = [];
        
        foreach ($errors as $field => $messages) {
            if (is_array($messages)) {
                $formatted[$field] = $messages;
            } else {
                $formatted[$field] = [$messages];
            }
        }
        
        return $formatted;
    }
    
    /**
     * Log API response for debugging
     */
    public static function log($data, $statusCode, $endpoint = null) {
        if (defined('API_LOGGING') && API_LOGGING) {
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'endpoint' => $endpoint ?: $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'status_code' => $statusCode,
                'response_size' => strlen(json_encode($data)),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            
            // Log to file or database
            error_log('API Response: ' . json_encode($logData), 3, __DIR__ . '/../logs/api.log');
        }
    }
    
    /**
     * Handle exceptions and send appropriate response
     */
    public static function handleException($exception) {
        // Log the exception
        error_log('Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
        
        // Send appropriate response based on exception type
        if ($exception instanceof InvalidArgumentException) {
            self::validationError([], $exception->getMessage());
        } elseif ($exception instanceof UnauthorizedException) {
            self::unauthorized($exception->getMessage());
        } elseif ($exception instanceof ForbiddenException) {
            self::forbidden($exception->getMessage());
        } elseif ($exception instanceof NotFoundException) {
            self::notFound($exception->getMessage());
        } else {
            // Generic server error
            $message = defined('DEBUG') && DEBUG ? $exception->getMessage() : 'Internal server error';
            self::serverError($message);
        }
    }
}

// Custom exception classes
class UnauthorizedException extends Exception {}
class ForbiddenException extends Exception {}
class NotFoundException extends Exception {}

?>