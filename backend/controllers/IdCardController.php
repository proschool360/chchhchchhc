<?php
/**
 * ID Card Controller
 * Handles employee ID card generation, template management, and customization
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class IdCardController {
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
     * Get all employee ID cards (index)
     */
    public function index() {
        return $this->getEmployeeIdCards();
    }
    
    /**
     * Show specific ID card
     */
    public function show() {
        $cardId = $_GET['id'] ?? null;
        if (!$cardId) {
            return Response::error('Card ID is required', null, 400);
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    eic.*, e.first_name, e.last_name, e.employee_id as emp_code,
                    d.name as department_name, p.title as position_title,
                    t.name as template_name, t.template_data
                FROM employee_id_cards eic
                JOIN employees e ON eic.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                LEFT JOIN id_card_templates t ON eic.template_id = t.id
                WHERE eic.id = ? AND eic.is_active = 1
            ");
            $stmt->execute([$cardId]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$card) {
                return Response::error('ID card not found', null, 404);
            }
            
            // Check permissions
            if ($this->user['role'] === 'employee') {
                $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ? AND id = ?");
                $stmt->execute([$this->user['id'], $card['employee_id']]);
                if (!$stmt->fetch()) {
                    return Response::error('Insufficient permissions', null, 403);
                }
            }
            
            $card['card_data'] = json_decode($card['card_data'], true);
            $card['template_data'] = json_decode($card['template_data'], true);
            
            return Response::success($card, 'ID card retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Show ID Card Error: " . $e->getMessage());
            return Response::error('Failed to retrieve ID card', null, 500);
        }
    }
    
    /**
     * Generate ID card (alias for generateIdCard)
     */
    public function generate() {
        return $this->generateIdCard();
    }
    
    /**
     * Download ID card (alias for downloadIdCard)
     */
    public function download() {
        return $this->downloadIdCard();
    }
    
    /**
     * Bulk download ID cards
     */
    public function bulkDownload() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $cardIds = $_GET['card_ids'] ?? [];
            if (empty($cardIds)) {
                return Response::error('Card IDs are required', null, 400);
            }
            
            if (is_string($cardIds)) {
                $cardIds = explode(',', $cardIds);
            }
            
            $results = [];
            foreach ($cardIds as $cardId) {
                try {
                    // Get card data
                    $stmt = $this->db->prepare("
                        SELECT 
                            eic.*, e.first_name, e.last_name, e.employee_id as emp_code
                        FROM employee_id_cards eic
                        JOIN employees e ON eic.employee_id = e.id
                        WHERE eic.id = ? AND eic.is_active = 1
                    ");
                    $stmt->execute([$cardId]);
                    $card = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($card) {
                        $pdfData = $this->generatePDF($card);
                        $results[] = [
                            'card_id' => $cardId,
                            'employee_name' => $card['first_name'] . ' ' . $card['last_name'],
                            'pdf_url' => $pdfData['url'],
                            'filename' => $pdfData['filename'],
                            'status' => 'success'
                        ];
                    } else {
                        $results[] = [
                            'card_id' => $cardId,
                            'status' => 'error',
                            'message' => 'Card not found'
                        ];
                    }
                } catch (Exception $e) {
                    $results[] = [
                        'card_id' => $cardId,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }
            
            // Log activity
            $successCount = count(array_filter($results, function($r) { return $r['status'] === 'success'; }));
            $this->logActivity($this->user['id'], 'bulk_download_id_cards', 
                "Bulk downloaded {$successCount} ID cards");
            
            return Response::success([
                'results' => $results,
                'total_processed' => count($results),
                'successful' => $successCount,
                'failed' => count($results) - $successCount
            ], 'Bulk download completed');
            
        } catch (Exception $e) {
            error_log("Bulk Download Error: " . $e->getMessage());
            return Response::error('Failed to bulk download ID cards', null, 500);
        }
    }
    
    /**
     * Preview ID card
     */
    public function preview() {
        $cardId = $_GET['id'] ?? null;
        if (!$cardId) {
            return Response::error('Card ID is required', null, 400);
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    eic.*, e.first_name, e.last_name, e.employee_id as emp_code,
                    d.name as department_name, p.title as position_title,
                    t.template_data
                FROM employee_id_cards eic
                JOIN employees e ON eic.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                LEFT JOIN id_card_templates t ON eic.template_id = t.id
                WHERE eic.id = ? AND eic.is_active = 1
            ");
            $stmt->execute([$cardId]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$card) {
                return Response::error('ID card not found', null, 404);
            }
            
            // Check permissions
            if ($this->user['role'] === 'employee') {
                $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ? AND id = ?");
                $stmt->execute([$this->user['id'], $card['employee_id']]);
                if (!$stmt->fetch()) {
                    return Response::error('Insufficient permissions', null, 403);
                }
            }
            
            $card['card_data'] = json_decode($card['card_data'], true);
            $card['template_data'] = json_decode($card['template_data'], true);
            
            // Generate preview HTML/image
            $previewData = $this->generatePreview($card);
            
            return Response::success([
                'card_data' => $card,
                'preview_html' => $previewData['html'],
                'preview_image_url' => $previewData['image_url'] ?? null
            ], 'Preview generated successfully');
            
        } catch (Exception $e) {
            error_log("Preview ID Card Error: " . $e->getMessage());
            return Response::error('Failed to generate preview', null, 500);
        }
    }

    /**
     * Get all ID card templates
     */
    public function getTemplates() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id, template_name as name, template_data, 
                    is_default, status as is_active, created_at
                FROM id_card_templates 
                WHERE status = 'active'
                ORDER BY is_default DESC, template_name ASC
            ");
            $stmt->execute();
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse template_data JSON
            foreach ($templates as &$template) {
                $template['template_data'] = json_decode($template['template_data'], true);
            }
            
            return Response::success($templates, 'Templates retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Get Templates Error: " . $e->getMessage());
            return Response::error('Failed to retrieve templates', null, 500);
        }
    }
    
    /**
     * Create new ID card template with simplified structure
     */
    public function createTemplate() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Simplified validation - only require name and layout type
            $validator = new Validator($data);
            $validator->required(['template_name', 'layout_type'])
                     ->string('template_name')
                     ->string('layout_type');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            // Create simplified template structure
            $templateData = $this->createSimpleTemplate($data);
            
            // If setting as default, unset other defaults
            if (!empty($data['is_default'])) {
                $stmt = $this->db->prepare("UPDATE id_card_templates SET is_default = 0");
                $stmt->execute();
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO id_card_templates (
                    template_name, template_data, is_default, 
                    status, created_by
                ) VALUES (?, ?, ?, 'active', ?)
            ");
            
            $stmt->execute([
                $data['template_name'],
                json_encode($templateData),
                !empty($data['is_default']) ? 1 : 0,
                $this->user['id']
            ]);
            
            $templateId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($this->user['id'], 'create_id_template', 
                "Created ID card template: {$data['template_name']}");
            
            return Response::success([
                'template_id' => $templateId,
                'template_name' => $data['template_name'],
                'layout_type' => $data['layout_type'],
                'template_data' => $templateData
            ], 'Template created successfully');
            
        } catch (Exception $e) {
            error_log("Create Template Error: " . $e->getMessage());
            return Response::error('Failed to create template', null, 500);
        }
    }
    
    /**
     * Get specific template
     */
    public function getTemplate() {
        try {
            $templateId = $_GET['id'] ?? null;
            if (!$templateId) {
                return Response::error('Template ID is required', null, 400);
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    id, name, description, template_data, 
                    is_default, is_active, created_at, updated_at
                FROM id_card_templates 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                return Response::error('Template not found', null, 404);
            }
            
            $template['template_data'] = json_decode($template['template_data'], true);
            
            return Response::success($template, 'Template retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Get Template Error: " . $e->getMessage());
            return Response::error('Failed to retrieve template', null, 500);
        }
    }

    /**
     * Update ID card template
     */
    public function updateTemplate() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $templateId = $_GET['id'] ?? null;
            if (!$templateId) {
                return Response::error('Template ID is required', null, 400);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['name', 'template_data'])
                     ->string('name')
                     ->array('template_data');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            // Validate template data structure
            if (!$this->validateTemplateData($data['template_data'])) {
                return Response::error('Invalid template data structure', null, 400);
            }
            
            // Check if template exists
            $stmt = $this->db->prepare("SELECT id FROM id_card_templates WHERE id = ?");
            $stmt->execute([$templateId]);
            if (!$stmt->fetch()) {
                return Response::error('Template not found', null, 404);
            }
            
            // If setting as default, unset other defaults
            if (!empty($data['is_default'])) {
                $stmt = $this->db->prepare("UPDATE id_card_templates SET is_default = 0");
                $stmt->execute();
            }
            
            $stmt = $this->db->prepare("
                UPDATE id_card_templates SET 
                    name = ?, description = ?, template_data = ?, 
                    is_default = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                json_encode($data['template_data']),
                !empty($data['is_default']) ? 1 : 0,
                $templateId
            ]);
            
            // Log activity
            $this->logActivity($this->user['id'], 'update_id_template', 
                "Updated ID card template: {$data['name']}");
            
            return Response::success(null, 'Template updated successfully');
            
        } catch (Exception $e) {
            error_log("Update Template Error: " . $e->getMessage());
            return Response::error('Failed to update template', null, 500);
        }
    }
    
    /**
     * Generate ID card for employee
     */
    public function generateIdCard() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['employee_id'])
                     ->integer('employee_id');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            $employeeId = $data['employee_id'];
            $templateId = $data['template_id'] ?? null;
            
            // Get employee data
            $stmt = $this->db->prepare("
                SELECT 
                    e.*, d.name as department_name, p.title as position_title,
                    u.username, u.email
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                LEFT JOIN users u ON e.user_id = u.id
                WHERE e.id = ? AND e.status = 'active'
            ");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                return Response::error('Employee not found', null, 404);
            }
            
            // Get template
            if ($templateId) {
                $stmt = $this->db->prepare("
                    SELECT * FROM id_card_templates 
                    WHERE id = ? AND is_active = 1
                ");
                $stmt->execute([$templateId]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT * FROM id_card_templates 
                    WHERE is_default = 1 AND is_active = 1
                ");
                $stmt->execute();
            }
            
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$template) {
                return Response::error('Template not found', null, 404);
            }
            
            // Generate QR code if not exists
            if (empty($employee['qr_code'])) {
                $qrCode = $this->generateQRCode($employee['id']);
                $stmt = $this->db->prepare("UPDATE employees SET qr_code = ? WHERE id = ?");
                $stmt->execute([$qrCode, $employee['id']]);
                $employee['qr_code'] = $qrCode;
            }
            
            // Check if ID card already exists
            $stmt = $this->db->prepare("
                SELECT id FROM employee_id_cards 
                WHERE employee_id = ? AND is_active = 1
            ");
            $stmt->execute([$employeeId]);
            $existingCard = $stmt->fetch();
            
            if ($existingCard) {
                // Deactivate existing card
                $stmt = $this->db->prepare("
                    UPDATE employee_id_cards SET status = 'revoked' 
                    WHERE employee_id = ?
                ");
                $stmt->execute([$employeeId]);
            }
            
            // Generate card data
            $cardData = $this->generateCardData($employee, json_decode($template['template_data'], true));
            
            // Save new ID card
            $stmt = $this->db->prepare("
                INSERT INTO employee_id_cards (
                    employee_id, template_id, qr_code_data, card_number,
                    issue_date, status, created_by
                ) VALUES (?, ?, ?, ?, CURDATE(), 'active', ?)
            ");
            
            // Generate unique card number
            $cardNumber = 'EMP' . str_pad($employeeId, 6, '0', STR_PAD_LEFT) . date('Y');
            
            $stmt->execute([
                $employeeId,
                $template['id'],
                json_encode($cardData),
                $cardNumber,
                $this->user['id']
            ]);
            
            $cardId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($this->user['id'], 'generate_id_card', 
                "Generated ID card for {$employee['first_name']} {$employee['last_name']}");
            
            return Response::success([
                'card_id' => $cardId,
                'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                'card_number' => $cardNumber,
                'qr_code_data' => $cardData
            ], 'ID card generated successfully');
            
        } catch (Exception $e) {
            error_log("Generate ID Card Error: " . $e->getMessage());
            return Response::error('Failed to generate ID card', null, 500);
        }
    }
    
    /**
     * Get employee ID cards
     */
    public function getEmployeeIdCards() {
        try {
            $employeeId = $_GET['employee_id'] ?? null;
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            // Build query based on permissions and filters
            $whereClause = "WHERE eic.status = 'active'";
            $params = [];
            
            if ($employeeId) {
                $whereClause .= " AND eic.employee_id = ?";
                $params[] = $employeeId;
            } elseif ($this->user['role'] === 'employee') {
                // Employees can only see their own cards
                $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ?");
                $stmt->execute([$this->user['id']]);
                $emp = $stmt->fetch();
                if ($emp) {
                    $whereClause .= " AND eic.employee_id = ?";
                    $params[] = $emp['id'];
                }
            }
            
            // Get total count
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) as total
                FROM employee_id_cards eic
                JOIN employees e ON eic.employee_id = e.id
                {$whereClause}
            ");
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];
            
            // Get cards with pagination
            $stmt = $this->db->prepare("
                SELECT 
                    eic.id, eic.employee_id, eic.qr_code_data, eic.card_image_path,
                    eic.issue_date, eic.expiry_date,
                    e.first_name, e.last_name, e.employee_id as emp_code,
                    d.name as department_name,
                    t.template_name
                FROM employee_id_cards eic
                JOIN employees e ON eic.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN id_card_templates t ON eic.template_id = t.id
                {$whereClause}
                ORDER BY eic.issue_date DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse qr_code_data JSON if needed
            foreach ($cards as &$card) {
                if ($card['qr_code_data']) {
                    $card['qr_code_data'] = json_decode($card['qr_code_data'], true);
                }
            }
            
            return Response::success([
                'cards' => $cards,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ], 'ID cards retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Get ID Cards Error: " . $e->getMessage());
            return Response::error('Failed to retrieve ID cards', null, 500);
        }
    }
    
    /**
     * Download ID card as PDF
     */
    public function downloadIdCard() {
        try {
            $cardId = $_GET['card_id'] ?? null;
            if (!$cardId) {
                return Response::error('Card ID is required', null, 400);
            }
            
            // Get card data
            $stmt = $this->db->prepare("
                SELECT 
                    eic.*, e.first_name, e.last_name, e.employee_id as emp_code,
                    d.name as department_name, p.title as position_title,
                    t.template_data
                FROM employee_id_cards eic
                JOIN employees e ON eic.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                LEFT JOIN id_card_templates t ON eic.template_id = t.id
                WHERE eic.id = ? AND eic.is_active = 1
            ");
            $stmt->execute([$cardId]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$card) {
                return Response::error('ID card not found', null, 404);
            }
            
            // Check permissions
            if ($this->user['role'] === 'employee') {
                $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ? AND id = ?");
                $stmt->execute([$this->user['id'], $card['employee_id']]);
                if (!$stmt->fetch()) {
                    return Response::error('Insufficient permissions', null, 403);
                }
            }
            
            // Generate PDF (simplified version - you would use a proper PDF library)
            $pdfData = $this->generatePDF($card);
            
            // Log activity
            $this->logActivity($this->user['id'], 'download_id_card', 
                "Downloaded ID card for {$card['first_name']} {$card['last_name']}");
            
            // Return PDF data or URL
            return Response::success([
                'pdf_url' => $pdfData['url'],
                'filename' => $pdfData['filename']
            ], 'ID card ready for download');
            
        } catch (Exception $e) {
            error_log("Download ID Card Error: " . $e->getMessage());
            return Response::error('Failed to download ID card', null, 500);
        }
    }
    
    /**
     * Bulk generate ID cards
     */
    public function bulkGenerateIdCards() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validator = new Validator($data);
            $validator->required(['employee_ids'])
                     ->array('employee_ids');
            
            if (!$validator->isValid()) {
                return Response::error('Validation failed', $validator->getErrors(), 400);
            }
            
            $employeeIds = $data['employee_ids'];
            $templateId = $data['template_id'] ?? null;
            $results = [];
            
            foreach ($employeeIds as $employeeId) {
                try {
                    // Get employee data
                    $stmt = $this->db->prepare("
                        SELECT 
                            e.*, d.name as department_name, p.title as position_title,
                            u.username, u.email
                        FROM employees e
                        LEFT JOIN departments d ON e.department_id = d.id
                        LEFT JOIN positions p ON e.position_id = p.id
                        LEFT JOIN users u ON e.user_id = u.id
                        WHERE e.employee_id = ? AND e.status = 'active'
                    ");
                    $stmt->execute([$employeeId]);
                    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$employee) {
                        $results[] = [
                            'employee_id' => $employeeId,
                            'status' => 'error',
                            'message' => 'Employee not found or inactive'
                        ];
                        continue;
                    }
                    
                    // Get template
                    if ($templateId) {
                        $stmt = $this->db->prepare("
                            SELECT * FROM id_card_templates 
                            WHERE id = ? AND is_active = 1
                        ");
                        $stmt->execute([$templateId]);
                    } else {
                        $stmt = $this->db->prepare("
                            SELECT * FROM id_card_templates 
                            WHERE is_default = 1 AND is_active = 1
                        ");
                        $stmt->execute();
                    }
                    
                    $template = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$template) {
                        $results[] = [
                            'employee_id' => $employeeId,
                            'status' => 'error',
                            'message' => 'Template not found'
                        ];
                        continue;
                    }
                    
                    // Generate QR code if not exists
                    if (empty($employee['qr_code'])) {
                        $qrCode = $this->generateQRCode($employee['id']);
                        $stmt = $this->db->prepare("UPDATE employees SET qr_code = ? WHERE id = ?");
                        $stmt->execute([$qrCode, $employee['id']]);
                        $employee['qr_code'] = $qrCode;
                    }
                    
                    // Check if ID card already exists
                    $stmt = $this->db->prepare("
                        SELECT id FROM employee_id_cards 
                        WHERE employee_id = ? AND is_active = 1
                    ");
                    $stmt->execute([$employee['id']]);
                    $existingCard = $stmt->fetch();
                    
                    if ($existingCard) {
                        // Deactivate existing card
                        $stmt = $this->db->prepare("
                            UPDATE employee_id_cards SET status = 'revoked' 
                            WHERE employee_id = ?
                        ");
                        $stmt->execute([$employee['id']]);
                    }
                    
                    // Generate card data
                    $cardData = $this->generateCardData($employee, json_decode($template['template_data'], true));
                    
                    // Save new ID card
                    $stmt = $this->db->prepare("
                        INSERT INTO employee_id_cards (
                            employee_id, template_id, qr_code_data, card_number,
                            issue_date, status, created_by
                        ) VALUES (?, ?, ?, ?, CURDATE(), 'active', ?)
                    ");
                    
                    // Generate unique card number
                    $cardNumber = 'EMP' . str_pad($employee['id'], 6, '0', STR_PAD_LEFT) . date('Y');
                    
                    $stmt->execute([
                        $employee['id'],
                        $template['id'],
                        json_encode($cardData),
                        $cardNumber,
                        $this->user['id']
                    ]);
                    
                    $cardId = $this->db->lastInsertId();
                    
                    $results[] = [
                        'employee_id' => $employeeId,
                        'card_id' => $cardId,
                        'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                        'status' => 'success',
                        'message' => 'ID card generated successfully'
                    ];
                    
                } catch (Exception $e) {
                    $results[] = [
                        'employee_id' => $employeeId,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }
            
            // Log activity
            $successCount = count(array_filter($results, function($r) { return $r['status'] === 'success'; }));
            $this->logActivity($this->user['id'], 'bulk_generate_id_cards', 
                "Bulk generated {$successCount} ID cards");
            
            return Response::success([
                'results' => $results,
                'total_processed' => count($results),
                'successful' => $successCount,
                'failed' => count($results) - $successCount
            ], 'Bulk ID card generation completed');
            
        } catch (Exception $e) {
            error_log("Bulk Generate ID Cards Error: " . $e->getMessage());
            return Response::error('Failed to bulk generate ID cards', null, 500);
        }
    }
    
    /**
     * Delete ID card template
     */
    public function deleteTemplate() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $templateId = $_GET['id'] ?? null;
            if (!$templateId) {
                return Response::error('Template ID is required', null, 400);
            }
            
            // Check if template exists
            $stmt = $this->db->prepare("SELECT template_name as name, is_default FROM id_card_templates WHERE id = ?");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch();
            
            if (!$template) {
                return Response::error('Template not found', null, 404);
            }
            
            // Prevent deletion of default template
            if ($template['is_default']) {
                return Response::error('Cannot delete default template', null, 400);
            }
            
            // Soft delete
            $stmt = $this->db->prepare("UPDATE id_card_templates SET is_active = 0 WHERE id = ?");
            $stmt->execute([$templateId]);
            
            // Log activity
            $this->logActivity($this->user['id'], 'delete_id_template', 
                "Deleted ID card template: {$template['name']}");
            
            return Response::success(null, 'Template deleted successfully');
            
        } catch (Exception $e) {
            error_log("Delete Template Error: " . $e->getMessage());
            return Response::error('Failed to delete template', null, 500);
        }
    }
    
    /**
     * Duplicate ID card template
     */
    public function duplicateTemplate() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $templateId = $_GET['id'] ?? null;
            if (!$templateId) {
                return Response::error('Template ID is required', null, 400);
            }
            
            // Get original template
            $stmt = $this->db->prepare("
                SELECT name, description, template_data 
                FROM id_card_templates 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                return Response::error('Template not found', null, 404);
            }
            
            // Create duplicate
            $newName = $template['name'] . ' (Copy)';
            $stmt = $this->db->prepare("
                INSERT INTO id_card_templates (
                    name, description, template_data, is_default, 
                    is_active, created_by
                ) VALUES (?, ?, ?, 0, 1, ?)
            ");
            
            $stmt->execute([
                $newName,
                $template['description'],
                $template['template_data'],
                $this->user['id']
            ]);
            
            $newTemplateId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($this->user['id'], 'duplicate_id_template', 
                "Duplicated ID card template: {$template['name']}");
            
            return Response::success([
                'template_id' => $newTemplateId,
                'name' => $newName
            ], 'Template duplicated successfully');
            
        } catch (Exception $e) {
            error_log("Duplicate Template Error: " . $e->getMessage());
            return Response::error('Failed to duplicate template', null, 500);
        }
    }
    
    /**
     * Set default template
     */
    public function setDefaultTemplate() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $templateId = $_GET['id'] ?? null;
            if (!$templateId) {
                return Response::error('Template ID is required', null, 400);
            }
            
            // Check if template exists
            $stmt = $this->db->prepare("SELECT template_name as name FROM id_card_templates WHERE id = ? AND status = 'active'");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch();
            
            if (!$template) {
                return Response::error('Template not found', null, 404);
            }
            
            // Unset all defaults
            $stmt = $this->db->prepare("UPDATE id_card_templates SET is_default = 0");
            $stmt->execute();
            
            // Set new default
            $stmt = $this->db->prepare("UPDATE id_card_templates SET is_default = 1 WHERE id = ?");
            $stmt->execute([$templateId]);
            
            // Log activity
            $this->logActivity($this->user['id'], 'set_default_template', 
                "Set default ID card template: {$template['name']}");
            
            return Response::success(null, 'Default template updated successfully');
            
        } catch (Exception $e) {
            error_log("Set Default Template Error: " . $e->getMessage());
            return Response::error('Failed to set default template', null, 500);
        }
    }
    
    /**
     * Get QR code for employee
     */
    public function getQRCode() {
        try {
            $employeeId = $_GET['employeeId'] ?? null;
            if (!$employeeId) {
                return Response::error('Employee ID is required', null, 400);
            }
            
            // Get employee data
            $stmt = $this->db->prepare("
                SELECT id, employee_id, first_name, last_name, qr_code 
                FROM employees 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                return Response::error('Employee not found', null, 404);
            }
            
            // Check permissions
            if ($this->user['role'] === 'employee') {
                $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ? AND id = ?");
                $stmt->execute([$this->user['id'], $employeeId]);
                if (!$stmt->fetch()) {
                    return Response::error('Insufficient permissions', null, 403);
                }
            }
            
            // Generate QR code if not exists
            if (empty($employee['qr_code'])) {
                $qrCode = $this->generateQRCode($employee['id']);
                $stmt = $this->db->prepare("UPDATE employees SET qr_code = ? WHERE id = ?");
                $stmt->execute([$qrCode, $employee['id']]);
                $employee['qr_code'] = $qrCode;
            }
            
            return Response::success([
                'employee_id' => $employee['id'],
                'employee_code' => $employee['employee_id'],
                'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                'qr_code' => $employee['qr_code']
            ], 'QR code retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Get QR Code Error: " . $e->getMessage());
            return Response::error('Failed to retrieve QR code', null, 500);
        }
    }
    
    /**
     * Update employee QR/RFID data
     */
    public function updateEmployeeQRRFID() {
        try {
            // Check permissions
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                return Response::error('Insufficient permissions', null, 403);
            }
            
            $employeeId = $_GET['id'] ?? null;
            if (!$employeeId) {
                return Response::error('Employee ID is required', null, 400);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if employee exists
            $stmt = $this->db->prepare("SELECT first_name, last_name FROM employees WHERE id = ? AND status = 'active'");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch();
            
            if (!$employee) {
                return Response::error('Employee not found', null, 404);
            }
            
            $updateFields = [];
            $params = [];
            
            if (isset($data['qr_code'])) {
                $updateFields[] = 'qr_code = ?';
                $params[] = $data['qr_code'];
            }
            
            if (isset($data['rfid_tag'])) {
                $updateFields[] = 'rfid_tag = ?';
                $params[] = $data['rfid_tag'];
            }
            
            if (empty($updateFields)) {
                return Response::error('No data to update', null, 400);
            }
            
            $params[] = $employeeId;
            
            $stmt = $this->db->prepare("
                UPDATE employees SET " . implode(', ', $updateFields) . " 
                WHERE id = ?
            ");
            $stmt->execute($params);
            
            // Log activity
            $this->logActivity($this->user['id'], 'update_employee_qr_rfid', 
                "Updated QR/RFID for {$employee['first_name']} {$employee['last_name']}");
            
            return Response::success(null, 'Employee QR/RFID updated successfully');
            
        } catch (Exception $e) {
            error_log("Update Employee QR/RFID Error: " . $e->getMessage());
            return Response::error('Failed to update employee QR/RFID', null, 500);
        }
    }
    
    // Helper Methods
    
    /**
     * Create simplified template with predefined layouts
     */
    private function createSimpleTemplate($data) {
        $layoutType = $data['layout_type'];
        $colors = $data['colors'] ?? ['primary' => '#2563eb', 'secondary' => '#64748b', 'text' => '#1e293b'];
        $companyLogo = $data['company_logo'] ?? null;
        
        $baseTemplate = [
            'version' => '2.0',
            'layout_type' => $layoutType,
            'dimensions' => ['width' => 400, 'height' => 250],
            'colors' => $colors,
            'company_logo' => $companyLogo,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        switch ($layoutType) {
            case 'modern':
                return array_merge($baseTemplate, [
                    'layout' => [
                        'photo' => ['x' => 20, 'y' => 20, 'width' => 80, 'height' => 100, 'style' => 'rounded'],
                        'name' => ['x' => 120, 'y' => 25, 'font_size' => 18, 'font_weight' => 'bold'],
                        'employee_id' => ['x' => 120, 'y' => 50, 'font_size' => 14, 'prefix' => 'ID: '],
                        'department' => ['x' => 120, 'y' => 70, 'font_size' => 12],
                        'position' => ['x' => 120, 'y' => 90, 'font_size' => 12],
                        'qr_code' => ['x' => 320, 'y' => 170, 'width' => 60, 'height' => 60],
                        'company_name' => ['x' => 20, 'y' => 200, 'font_size' => 14, 'font_weight' => 'bold']
                    ]
                ]);
                
            case 'classic':
                return array_merge($baseTemplate, [
                    'layout' => [
                        'header' => ['x' => 0, 'y' => 0, 'width' => 400, 'height' => 40, 'bg_color' => $colors['primary']],
                        'photo' => ['x' => 30, 'y' => 60, 'width' => 70, 'height' => 90, 'style' => 'square'],
                        'name' => ['x' => 120, 'y' => 70, 'font_size' => 16, 'font_weight' => 'bold'],
                        'employee_id' => ['x' => 120, 'y' => 95, 'font_size' => 13, 'prefix' => 'Employee ID: '],
                        'department' => ['x' => 120, 'y' => 115, 'font_size' => 11],
                        'position' => ['x' => 120, 'y' => 135, 'font_size' => 11],
                        'qr_code' => ['x' => 300, 'y' => 60, 'width' => 70, 'height' => 70],
                        'footer' => ['x' => 0, 'y' => 210, 'width' => 400, 'height' => 40, 'bg_color' => $colors['secondary']]
                    ]
                ]);
                
            case 'minimal':
                return array_merge($baseTemplate, [
                    'layout' => [
                        'photo' => ['x' => 40, 'y' => 40, 'width' => 60, 'height' => 80, 'style' => 'circle'],
                        'name' => ['x' => 120, 'y' => 50, 'font_size' => 16, 'font_weight' => 'normal'],
                        'employee_id' => ['x' => 120, 'y' => 75, 'font_size' => 12, 'color' => $colors['secondary']],
                        'department' => ['x' => 120, 'y' => 95, 'font_size' => 11, 'color' => $colors['secondary']],
                        'qr_code' => ['x' => 320, 'y' => 40, 'width' => 50, 'height' => 50]
                    ]
                ]);
                
            default:
                // Default to modern layout
                return $this->createSimpleTemplate(array_merge($data, ['layout_type' => 'modern']));
        }
    }
    
    private function generateQRCode($employeeId) {
        // Generate unique QR code for employee
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return "EMP{$employeeId}_{$timestamp}_{$random}";
    }
    
    /**
     * Generate optimized card data with employee information
     */
    private function generateCardData($employee, $template) {
        // Extract employee data efficiently
        $employeeData = [
            'id' => $employee['id'],
            'name' => trim($employee['first_name'] . ' ' . $employee['last_name']),
            'employee_id' => $employee['employee_id'],
            'department' => $employee['department_name'] ?? 'N/A',
            'position' => $employee['position_title'] ?? 'N/A',
            'email' => $employee['email'] ?? '',
            'phone' => $employee['phone'] ?? '',
            'photo_url' => $this->getEmployeePhotoUrl($employee),
            'qr_code' => $employee['qr_code'],
            'hire_date' => $employee['hire_date'] ?? null,
            'company_name' => $this->getCompanyName()
        ];
        
        // Create optimized card data structure
        return [
            'version' => '2.0',
            'template_id' => $template['id'] ?? null,
            'layout_type' => $template['layout_type'] ?? 'modern',
            'employee' => $employeeData,
            'template_config' => [
                'dimensions' => $template['dimensions'] ?? ['width' => 400, 'height' => 250],
                'colors' => $template['colors'] ?? ['primary' => '#2563eb', 'secondary' => '#64748b'],
                'layout' => $template['layout'] ?? []
            ],
            'metadata' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $this->user['id'],
                'card_version' => '2.0'
            ]
        ];
    }
    
    /**
     * Get employee photo URL with fallback
     */
    private function getEmployeePhotoUrl($employee) {
        if (!empty($employee['profile_picture'])) {
            return '/uploads/profiles/' . $employee['profile_picture'];
        }
        if (!empty($employee['photo_url'])) {
            return $employee['photo_url'];
        }
        return '/assets/images/default-avatar.png';
    }
    
    /**
     * Get company name from settings or default
     */
    private function getCompanyName() {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'company_name'");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ? $result['setting_value'] : 'Company Name';
        } catch (Exception $e) {
            return 'Company Name';
        }
    }
    
    private function generatePDF($card) {
        // This is a simplified version - you would use a proper PDF library like TCPDF or FPDF
        $filename = "id_card_{$card['emp_code']}_" . date('Y-m-d') . ".pdf";
        $url = "/downloads/id_cards/{$filename}";
        
        // In a real implementation, you would:
        // 1. Use a PDF library to create the actual PDF
        // 2. Apply the template design
        // 3. Insert employee data and QR code
        // 4. Save to file system or return as base64
        
        return [
            'url' => $url,
            'filename' => $filename
        ];
    }
    
    /**
     * Generate enhanced preview with new template structure
     */
    private function generatePreview($card) {
        $cardData = json_decode($card['qr_code_data'], true);
        $templateData = json_decode($card['template_data'], true);
        
        // Get layout configuration
        $layoutType = $templateData['layout_type'] ?? 'modern';
        $dimensions = $templateData['dimensions'] ?? ['width' => 400, 'height' => 250];
        $colors = $templateData['colors'] ?? ['primary' => '#2563eb', 'secondary' => '#64748b', 'text' => '#1e293b'];
        $layout = $templateData['layout'] ?? [];
        
        // Employee data (support both old and new formats)
        $employee = $cardData['employee'] ?? $cardData['employee_data'] ?? [];
        
        $html = $this->generatePreviewHTML($layoutType, $dimensions, $colors, $layout, $employee);
        
        return [
            'html' => $html,
            'layout_type' => $layoutType,
            'dimensions' => $dimensions,
            'preview_data' => [
                'employee' => $employee,
                'template_config' => $templateData
            ]
        ];
    }
    
    /**
     * Generate HTML preview based on layout type
     */
    private function generatePreviewHTML($layoutType, $dimensions, $colors, $layout, $employee) {
        $width = $dimensions['width'];
        $height = $dimensions['height'];
        
        $html = "<div class='id-card-preview' style='width: {$width}px; height: {$height}px; border: 2px solid {$colors['primary']}; position: relative; background: white; font-family: Arial, sans-serif; overflow: hidden; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>";
        
        switch ($layoutType) {
            case 'modern':
                $html .= $this->generateModernLayout($layout, $employee, $colors);
                break;
            case 'classic':
                $html .= $this->generateClassicLayout($layout, $employee, $colors);
                break;
            case 'minimal':
                $html .= $this->generateMinimalLayout($layout, $employee, $colors);
                break;
            default:
                $html .= $this->generateModernLayout($layout, $employee, $colors);
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Generate modern layout HTML
     */
    private function generateModernLayout($layout, $employee, $colors) {
        $photoConfig = $layout['photo'] ?? ['x' => 20, 'y' => 20, 'width' => 80, 'height' => 100];
        $nameConfig = $layout['name'] ?? ['x' => 120, 'y' => 25, 'font_size' => 18];
        
        $html = '';
        
        // Photo
        if (!empty($employee['photo_url'])) {
            $html .= "<img src='" . htmlspecialchars($employee['photo_url']) . "' style='position: absolute; top: {$photoConfig['y']}px; left: {$photoConfig['x']}px; width: {$photoConfig['width']}px; height: {$photoConfig['height']}px; object-fit: cover; border-radius: 8px; border: 2px solid {$colors['primary']};' />";
        }
        
        // Name
        $html .= "<div style='position: absolute; top: {$nameConfig['y']}px; left: {$nameConfig['x']}px; font-size: {$nameConfig['font_size']}px; font-weight: bold; color: {$colors['text']};'>" . htmlspecialchars($employee['name'] ?? 'N/A') . '</div>';
        
        // Employee ID
        $html .= "<div style='position: absolute; top: 50px; left: 120px; font-size: 14px; color: {$colors['secondary']};'>ID: " . htmlspecialchars($employee['employee_id'] ?? 'N/A') . '</div>';
        
        // Department & Position
        $html .= "<div style='position: absolute; top: 70px; left: 120px; font-size: 12px; color: {$colors['secondary']};'>" . htmlspecialchars($employee['department'] ?? 'N/A') . '</div>';
        $html .= "<div style='position: absolute; top: 90px; left: 120px; font-size: 12px; color: {$colors['secondary']};'>" . htmlspecialchars($employee['position'] ?? 'N/A') . '</div>';
        
        // QR Code
        $html .= "<div style='position: absolute; bottom: 20px; right: 20px; width: 60px; height: 60px; border: 2px solid {$colors['primary']}; display: flex; align-items: center; justify-content: center; font-size: 10px; text-align: center; background: {$colors['primary']}; color: white; border-radius: 4px;'>QR</div>";
        
        // Company name
        $html .= "<div style='position: absolute; bottom: 10px; left: 20px; font-size: 12px; font-weight: bold; color: {$colors['primary']};'>" . htmlspecialchars($employee['company_name'] ?? 'Company') . '</div>';
        
        return $html;
    }
    
    /**
     * Generate classic layout HTML
     */
    private function generateClassicLayout($layout, $employee, $colors) {
        $html = "<div style='position: absolute; top: 0; left: 0; width: 100%; height: 40px; background: {$colors['primary']}; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;'>" . htmlspecialchars($employee['company_name'] ?? 'Company Name') . '</div>';
        
        // Photo
        if (!empty($employee['photo_url'])) {
            $html .= "<img src='" . htmlspecialchars($employee['photo_url']) . "' style='position: absolute; top: 60px; left: 30px; width: 70px; height: 90px; object-fit: cover; border: 2px solid {$colors['primary']};' />";
        }
        
        // Employee details
        $html .= "<div style='position: absolute; top: 70px; left: 120px; font-size: 16px; font-weight: bold; color: {$colors['text']};'>" . htmlspecialchars($employee['name'] ?? 'N/A') . '</div>';
        $html .= "<div style='position: absolute; top: 95px; left: 120px; font-size: 13px; color: {$colors['secondary']};'>Employee ID: " . htmlspecialchars($employee['employee_id'] ?? 'N/A') . '</div>';
        $html .= "<div style='position: absolute; top: 115px; left: 120px; font-size: 11px; color: {$colors['secondary']};'>" . htmlspecialchars($employee['department'] ?? 'N/A') . '</div>';
        $html .= "<div style='position: absolute; top: 135px; left: 120px; font-size: 11px; color: {$colors['secondary']};'>" . htmlspecialchars($employee['position'] ?? 'N/A') . '</div>';
        
        // QR Code
        $html .= "<div style='position: absolute; top: 60px; right: 30px; width: 70px; height: 70px; border: 2px solid {$colors['primary']}; display: flex; align-items: center; justify-content: center; font-size: 12px; text-align: center; background: {$colors['primary']}; color: white;'>QR</div>";
        
        // Footer
        $html .= "<div style='position: absolute; bottom: 0; left: 0; width: 100%; height: 40px; background: {$colors['secondary']}; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;'>Valid Employee ID Card</div>";
        
        return $html;
    }
    
    /**
     * Generate minimal layout HTML
     */
    private function generateMinimalLayout($layout, $employee, $colors) {
        $html = '';
        
        // Photo (circular)
        if (!empty($employee['photo_url'])) {
            $html .= "<img src='" . htmlspecialchars($employee['photo_url']) . "' style='position: absolute; top: 40px; left: 40px; width: 60px; height: 80px; object-fit: cover; border-radius: 50%; border: 3px solid {$colors['primary']};' />";
        }
        
        // Employee details
        $html .= "<div style='position: absolute; top: 50px; left: 120px; font-size: 16px; color: {$colors['text']};'>" . htmlspecialchars($employee['name'] ?? 'N/A') . '</div>';
        $html .= "<div style='position: absolute; top: 75px; left: 120px; font-size: 12px; color: {$colors['secondary']};'>" . htmlspecialchars($employee['employee_id'] ?? 'N/A') . '</div>';
        $html .= "<div style='position: absolute; top: 95px; left: 120px; font-size: 11px; color: {$colors['secondary']};'>" . htmlspecialchars($employee['department'] ?? 'N/A') . '</div>';
        
        // QR Code
        $html .= "<div style='position: absolute; top: 40px; right: 40px; width: 50px; height: 50px; border: 1px solid {$colors['secondary']}; display: flex; align-items: center; justify-content: center; font-size: 10px; text-align: center; background: {$colors['primary']}; color: white; border-radius: 4px;'>QR</div>";
        
        return $html;
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
     * Create template variations for different use cases
     */
    public function createTemplateVariations() {
        try {
            // Check permissions
            if (!$this->hasPermission('create_id_card_template')) {
                return Response::error('Insufficient permissions', 403);
            }
            
            $variations = [
                [
                    'template_name' => 'Corporate Modern',
                    'layout_type' => 'modern',
                    'description' => 'Modern corporate design with blue theme'
                ],
                [
                    'template_name' => 'Classic Professional',
                    'layout_type' => 'classic',
                    'description' => 'Traditional professional layout'
                ],
                [
                    'template_name' => 'Minimal Clean',
                    'layout_type' => 'minimal',
                    'description' => 'Clean minimal design for modern workplaces'
                ],
                [
                    'template_name' => 'Executive Premium',
                    'layout_type' => 'modern',
                    'description' => 'Premium design for executive staff',
                    'colors' => ['primary' => '#1a1a1a', 'secondary' => '#666666', 'text' => '#000000']
                ],
                [
                    'template_name' => 'Visitor Pass',
                    'layout_type' => 'minimal',
                    'description' => 'Simple visitor identification card',
                    'colors' => ['primary' => '#f59e0b', 'secondary' => '#92400e', 'text' => '#451a03']
                ]
            ];
            
            $createdTemplates = [];
            
            foreach ($variations as $variation) {
                $templateData = $this->createSimpleTemplate(
                    $variation['layout_type'],
                    $variation['colors'] ?? null
                );
                
                $stmt = $this->db->prepare(
                    "INSERT INTO id_card_templates (template_name, template_data, status, created_by, created_at) 
                     VALUES (?, ?, 'active', ?, NOW())"
                );
                
                $stmt->execute([
                    $variation['template_name'],
                    json_encode($templateData),
                    $_SESSION['user_id']
                ]);
                
                $templateId = $this->db->lastInsertId();
                
                $createdTemplates[] = [
                    'template_id' => $templateId,
                    'template_name' => $variation['template_name'],
                    'layout_type' => $variation['layout_type'],
                    'description' => $variation['description']
                ];
                
                // Log activity
                $this->logActivity($_SESSION['user_id'], 'create_template_variation', 'Created template variation: ' . $variation['template_name']);
            }
            
            return Response::success('Template variations created successfully', [
                'templates' => $createdTemplates,
                'total_created' => count($createdTemplates)
            ]);
            
        } catch (Exception $e) {
            error_log('Template variations creation error: ' . $e->getMessage());
            return Response::error('Failed to create template variations', 500);
        }
    }
    
    /**
     * Bulk generate ID cards for multiple employees
     */
    public function bulkGenerateCards() {
        try {
            // Check permissions
            if (!$this->hasPermission('generate_id_card')) {
                return Response::error('Insufficient permissions', 403);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['employee_ids']) || !is_array($input['employee_ids'])) {
                return Response::error('Employee IDs array is required', 400);
            }
            
            if (!isset($input['template_id'])) {
                return Response::error('Template ID is required', 400);
            }
            
            $employeeIds = $input['employee_ids'];
            $templateId = $input['template_id'];
            $generatedCards = [];
            $errors = [];
            
            foreach ($employeeIds as $employeeId) {
                try {
                    // Get employee data
                    $stmt = $this->db->prepare(
                        "SELECT e.*, d.name as department_name 
                         FROM employees e 
                         LEFT JOIN departments d ON e.department_id = d.id 
                         WHERE e.id = ? AND e.status = 'active'"
                    );
                    $stmt->execute([$employeeId]);
                    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$employee) {
                        $errors[] = "Employee ID {$employeeId} not found or inactive";
                        continue;
                    }
                    
                    // Check if card already exists
                    $stmt = $this->db->prepare(
                        "SELECT id FROM employee_id_cards 
                         WHERE employee_id = ? AND template_id = ? AND status = 'active'"
                    );
                    $stmt->execute([$employeeId, $templateId]);
                    
                    if ($stmt->fetch()) {
                        $errors[] = "Card already exists for employee: {$employee['first_name']} {$employee['last_name']}";
                        continue;
                    }
                    
                    // Generate card data
                    $cardData = $this->generateCardData($employee, $templateId);
                    $cardNumber = $employeeId . date('Y');
                    
                    // Insert new card
                    $stmt = $this->db->prepare(
                        "INSERT INTO employee_id_cards (employee_id, template_id, qr_code_data, card_number, status, created_by, created_at) 
                         VALUES (?, ?, ?, ?, 'active', ?, NOW())"
                    );
                    
                    $stmt->execute([
                        $employeeId,
                        $templateId,
                        json_encode($cardData),
                        $cardNumber,
                        $_SESSION['user_id']
                    ]);
                    
                    $cardId = $this->db->lastInsertId();
                    
                    $generatedCards[] = [
                        'card_id' => $cardId,
                        'employee_id' => $employeeId,
                        'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                        'card_number' => $cardNumber
                    ];
                    
                } catch (Exception $e) {
                    $errors[] = "Failed to generate card for employee ID {$employeeId}: " . $e->getMessage();
                }
            }
            
            // Log bulk activity
            $this->logActivity($_SESSION['user_id'], 'bulk_generate_cards', 'Bulk generated ' . count($generatedCards) . ' ID cards');
            
            return Response::success('Bulk card generation completed', [
                'generated_cards' => $generatedCards,
                'total_generated' => count($generatedCards),
                'errors' => $errors,
                'total_errors' => count($errors)
            ]);
            
        } catch (Exception $e) {
            error_log('Bulk card generation error: ' . $e->getMessage());
            return Response::error('Failed to generate cards in bulk', 500);
        }
    }
    
    /**
     * Get template statistics and usage
     */
    public function getTemplateStats() {
        try {
            // Check permissions
            if (!$this->hasPermission('view_id_card_template')) {
                return Response::error('Insufficient permissions', 403);
            }
            
            // Get template usage statistics
            $stmt = $this->db->prepare(
                "SELECT 
                    t.id,
                    t.template_name,
                    t.status,
                    t.created_at,
                    COUNT(eic.id) as cards_generated,
                    COUNT(CASE WHEN eic.status = 'active' THEN 1 END) as active_cards
                 FROM id_card_templates t
                 LEFT JOIN employee_id_cards eic ON t.id = eic.template_id
                 WHERE t.status = 'active'
                 GROUP BY t.id, t.template_name, t.status, t.created_at
                 ORDER BY cards_generated DESC"
            );
            $stmt->execute();
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total statistics
            $stmt = $this->db->prepare(
                "SELECT 
                    COUNT(DISTINCT t.id) as total_templates,
                    COUNT(eic.id) as total_cards,
                    COUNT(CASE WHEN eic.status = 'active' THEN 1 END) as active_cards
                 FROM id_card_templates t
                 LEFT JOIN employee_id_cards eic ON t.id = eic.template_id
                 WHERE t.status = 'active'"
            );
            $stmt->execute();
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return Response::success('Template statistics retrieved', [
                'templates' => $templates,
                'statistics' => $totals
            ]);
            
        } catch (Exception $e) {
            error_log('Template stats error: ' . $e->getMessage());
            return Response::error('Failed to retrieve template statistics', 500);
        }
    }
    
    /**
     * Get available layout types
     */
    public function getLayoutTypes() {
        try {
            $layoutTypes = [
                [
                    'id' => 'modern',
                    'name' => 'Modern',
                    'description' => 'Clean and contemporary design with bold typography',
                    'preview' => '/assets/templates/modern-preview.png'
                ],
                [
                    'id' => 'classic',
                    'name' => 'Classic',
                    'description' => 'Traditional corporate design with professional layout',
                    'preview' => '/assets/templates/classic-preview.png'
                ],
                [
                    'id' => 'minimal',
                    'name' => 'Minimal',
                    'description' => 'Simple and elegant design with focus on essential information',
                    'preview' => '/assets/templates/minimal-preview.png'
                ],
                [
                    'id' => 'creative',
                    'name' => 'Creative',
                    'description' => 'Vibrant and dynamic design with creative elements',
                    'preview' => '/assets/templates/creative-preview.png'
                ]
            ];
            
            return Response::success($layoutTypes, 'Layout types retrieved successfully');
            
        } catch (Exception $e) {
            error_log('Get layout types error: ' . $e->getMessage());
            return Response::error('Failed to retrieve layout types', 500);
        }
    }
}