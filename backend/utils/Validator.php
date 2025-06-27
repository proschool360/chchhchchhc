<?php
/**
 * Input Validation Utility Class
 * Handles validation of user inputs across the HRMS system
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

class Validator {
    private $data;
    private $rules;
    private $errors;
    private $customMessages;
    
    public function __construct($data = []) {
        $this->data = $data;
        $this->errors = [];
        $this->customMessages = [];
    }
    
    /**
     * Set validation rules
     */
    public function setRules($rules) {
        $this->rules = $rules;
        return $this;
    }
    
    /**
     * Set custom error messages
     */
    public function setMessages($messages) {
        $this->customMessages = $messages;
        return $this;
    }
    
    /**
     * Validate data against rules
     */
    public function validate() {
        foreach ($this->rules as $field => $rules) {
            $this->validateField($field, $rules);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Validate a single field
     */
    private function validateField($field, $rules) {
        $rulesArray = is_string($rules) ? explode('|', $rules) : $rules;
        $value = $this->getValue($field);
        
        foreach ($rulesArray as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }
    
    /**
     * Apply a single validation rule
     */
    private function applyRule($field, $value, $rule) {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $parameter = isset($parts[1]) ? $parts[1] : null;
        
        switch ($ruleName) {
            case 'required':
                $this->validateRequired($field, $value);
                break;
            case 'email':
                $this->validateEmail($field, $value);
                break;
            case 'min':
                $this->validateMin($field, $value, $parameter);
                break;
            case 'max':
                $this->validateMax($field, $value, $parameter);
                break;
            case 'numeric':
                $this->validateNumeric($field, $value);
                break;
            case 'integer':
                $this->validateInteger($field, $value);
                break;
            case 'string':
                $this->validateString($field, $value);
                break;
            case 'array':
                $this->validateArray($field, $value);
                break;
            case 'date':
                $this->validateDate($field, $value);
                break;
            case 'date_format':
                $this->validateDateFormat($field, $value, $parameter);
                break;
            case 'in':
                $this->validateIn($field, $value, $parameter);
                break;
            case 'not_in':
                $this->validateNotIn($field, $value, $parameter);
                break;
            case 'unique':
                $this->validateUnique($field, $value, $parameter);
                break;
            case 'exists':
                $this->validateExists($field, $value, $parameter);
                break;
            case 'confirmed':
                $this->validateConfirmed($field, $value);
                break;
            case 'regex':
                $this->validateRegex($field, $value, $parameter);
                break;
            case 'phone':
                $this->validatePhone($field, $value);
                break;
            case 'url':
                $this->validateUrl($field, $value);
                break;
            case 'file':
                $this->validateFile($field, $value);
                break;
            case 'image':
                $this->validateImage($field, $value);
                break;
            case 'mimes':
                $this->validateMimes($field, $value, $parameter);
                break;
            case 'size':
                $this->validateSize($field, $value, $parameter);
                break;
            case 'password':
                $this->validatePassword($field, $value);
                break;
            case 'employee_id':
                $this->validateEmployeeId($field, $value);
                break;
            case 'salary':
                $this->validateSalary($field, $value);
                break;
        }
    }
    
    /**
     * Get value from data
     */
    private function getValue($field) {
        return isset($this->data[$field]) ? $this->data[$field] : null;
    }
    
    /**
     * Add error message
     */
    private function addError($field, $rule, $parameters = []) {
        $message = $this->getMessage($field, $rule, $parameters);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get error message
     */
    private function getMessage($field, $rule, $parameters = []) {
        $key = "{$field}.{$rule}";
        
        if (isset($this->customMessages[$key])) {
            return $this->customMessages[$key];
        }
        
        $messages = [
            'required' => 'The :field field is required.',
            'email' => 'The :field must be a valid email address.',
            'min' => 'The :field must be at least :min characters.',
            'max' => 'The :field may not be greater than :max characters.',
            'numeric' => 'The :field must be a number.',
            'integer' => 'The :field must be an integer.',
            'string' => 'The :field must be a string.',
            'array' => 'The :field must be an array.',
            'date' => 'The :field is not a valid date.',
            'date_format' => 'The :field does not match the format :format.',
            'in' => 'The selected :field is invalid.',
            'not_in' => 'The selected :field is invalid.',
            'unique' => 'The :field has already been taken.',
            'exists' => 'The selected :field is invalid.',
            'confirmed' => 'The :field confirmation does not match.',
            'regex' => 'The :field format is invalid.',
            'phone' => 'The :field must be a valid phone number.',
            'url' => 'The :field must be a valid URL.',
            'file' => 'The :field must be a file.',
            'image' => 'The :field must be an image.',
            'mimes' => 'The :field must be a file of type: :values.',
            'size' => 'The :field must be :size kilobytes.',
            'password' => 'The :field must meet password requirements.',
            'employee_id' => 'The :field must be a valid employee ID.',
            'salary' => 'The :field must be a valid salary amount.'
        ];
        
        $message = isset($messages[$rule]) ? $messages[$rule] : 'The :field is invalid.';
        
        // Replace placeholders
        $message = str_replace(':field', ucfirst(str_replace('_', ' ', $field)), $message);
        
        foreach ($parameters as $key => $value) {
            $message = str_replace(":$key", $value, $message);
        }
        
        return $message;
    }
    
    // Validation methods
    
    private function validateRequired($field, $value) {
        if (is_null($value) || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, 'required');
        }
    }
    
    private function validateEmail($field, $value) {
        if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'email');
        }
    }
    
    private function validateMin($field, $value, $min) {
        if (!is_null($value)) {
            if (is_string($value) && strlen($value) < $min) {
                $this->addError($field, 'min', ['min' => $min]);
            } elseif (is_numeric($value) && $value < $min) {
                $this->addError($field, 'min', ['min' => $min]);
            }
        }
    }
    
    private function validateMax($field, $value, $max) {
        if (!is_null($value)) {
            if (is_string($value) && strlen($value) > $max) {
                $this->addError($field, 'max', ['max' => $max]);
            } elseif (is_numeric($value) && $value > $max) {
                $this->addError($field, 'max', ['max' => $max]);
            }
        }
    }
    
    private function validateNumeric($field, $value) {
        if (!is_null($value) && !is_numeric($value)) {
            $this->addError($field, 'numeric');
        }
    }
    
    private function validateInteger($field, $value) {
        if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_INT) && !ctype_digit((string)$value)) {
            $this->addError($field, 'integer');
        }
    }
    
    private function validateString($field, $value) {
        if (!is_null($value) && !is_string($value)) {
            $this->addError($field, 'string');
        }
    }
    
    private function validateArray($field, $value) {
        if (!is_null($value) && !is_array($value)) {
            $this->addError($field, 'array');
        }
    }
    
    private function validateDate($field, $value) {
        if (!is_null($value) && !strtotime($value)) {
            $this->addError($field, 'date');
        }
    }
    
    private function validateDateFormat($field, $value, $format) {
        if (!is_null($value)) {
            $date = DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->addError($field, 'date_format', ['format' => $format]);
            }
        }
    }
    
    private function validateIn($field, $value, $list) {
        if (!is_null($value)) {
            $values = explode(',', $list);
            if (!in_array($value, $values)) {
                $this->addError($field, 'in');
            }
        }
    }
    
    private function validateNotIn($field, $value, $list) {
        if (!is_null($value)) {
            $values = explode(',', $list);
            if (in_array($value, $values)) {
                $this->addError($field, 'not_in');
            }
        }
    }
    
    private function validateUnique($field, $value, $table) {
        if (!is_null($value)) {
            // This would require database connection
            // Implementation depends on your database setup
        }
    }
    
    private function validateExists($field, $value, $table) {
        if (!is_null($value)) {
            // This would require database connection
            // Implementation depends on your database setup
        }
    }
    
    private function validateConfirmed($field, $value) {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->getValue($confirmField);
        
        if ($value !== $confirmValue) {
            $this->addError($field, 'confirmed');
        }
    }
    
    private function validateRegex($field, $value, $pattern) {
        if (!is_null($value) && !preg_match($pattern, $value)) {
            $this->addError($field, 'regex');
        }
    }
    
    private function validatePhone($field, $value) {
        if (!is_null($value)) {
            $pattern = '/^[\+]?[1-9]?[0-9]{7,15}$/';
            if (!preg_match($pattern, $value)) {
                $this->addError($field, 'phone');
            }
        }
    }
    
    private function validateUrl($field, $value) {
        if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, 'url');
        }
    }
    
    private function validateFile($field, $value) {
        if (!is_null($value) && !isset($_FILES[$field])) {
            $this->addError($field, 'file');
        }
    }
    
    private function validateImage($field, $value) {
        if (!is_null($value) && isset($_FILES[$field])) {
            $imageInfo = getimagesize($_FILES[$field]['tmp_name']);
            if (!$imageInfo) {
                $this->addError($field, 'image');
            }
        }
    }
    
    private function validateMimes($field, $value, $mimes) {
        if (!is_null($value) && isset($_FILES[$field])) {
            $allowedMimes = explode(',', $mimes);
            $fileMime = $_FILES[$field]['type'];
            
            if (!in_array($fileMime, $allowedMimes)) {
                $this->addError($field, 'mimes', ['values' => $mimes]);
            }
        }
    }
    
    private function validateSize($field, $value, $size) {
        if (!is_null($value) && isset($_FILES[$field])) {
            $fileSize = $_FILES[$field]['size'] / 1024; // Convert to KB
            
            if ($fileSize > $size) {
                $this->addError($field, 'size', ['size' => $size]);
            }
        }
    }
    
    private function validatePassword($field, $value) {
        if (!is_null($value)) {
            $errors = [];
            
            if (strlen($value) < 8) {
                $errors[] = 'at least 8 characters';
            }
            
            if (!preg_match('/[A-Z]/', $value)) {
                $errors[] = 'at least one uppercase letter';
            }
            
            if (!preg_match('/[a-z]/', $value)) {
                $errors[] = 'at least one lowercase letter';
            }
            
            if (!preg_match('/[0-9]/', $value)) {
                $errors[] = 'at least one number';
            }
            
            if (!preg_match('/[^A-Za-z0-9]/', $value)) {
                $errors[] = 'at least one special character';
            }
            
            if (!empty($errors)) {
                $message = 'Password must contain ' . implode(', ', $errors) . '.';
                $this->errors[$field][] = $message;
            }
        }
    }
    
    private function validateEmployeeId($field, $value) {
        if (!is_null($value)) {
            // Employee ID format: EMP followed by 4-6 digits
            $pattern = '/^EMP[0-9]{4,6}$/';
            if (!preg_match($pattern, $value)) {
                $this->addError($field, 'employee_id');
            }
        }
    }
    
    private function validateSalary($field, $value) {
        if (!is_null($value)) {
            if (!is_numeric($value) || $value < 0) {
                $this->addError($field, 'salary');
            }
        }
    }
    
    /**
     * Static method for quick validation
     */
    public static function make($data, $rules, $messages = []) {
        $validator = new self($data);
        $validator->setRules($rules);
        $validator->setMessages($messages);
        $validator->validate();
        
        return $validator;
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Clean and validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = null) {
        $errors = [];
        
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file uploaded or invalid file';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($maxSize && $file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $fileType = $file['type'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileType, $allowedTypes) && !in_array($fileExtension, $allowedTypes)) {
                $errors[] = 'File type not allowed';
            }
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $file['error'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_info' => [
                'name' => $file['name'],
                'type' => $file['type'],
                'size' => $file['size'],
                'tmp_name' => $file['tmp_name']
            ]
        ];
    }
}

?>