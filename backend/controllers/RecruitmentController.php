<?php
/**
 * Recruitment Controller
 * Handles recruitment operations including job postings, applications, and interviews
 */

if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/database.php';

class RecruitmentController {
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
     * Get job postings with pagination
     */
    public function getJobPostings() {
        try {
            $params = Router::getQueryParams();
            
            // Pagination
            $page = max(1, (int)($params['page'] ?? 1));
            $perPage = min(100, max(10, (int)($params['per_page'] ?? 20)));
            $offset = ($page - 1) * $perPage;
            
            // Filters
            $filters = [];
            $filterValues = [];
            
            if (!empty($params['status'])) {
                $filters[] = "jp.status = ?";
                $filterValues[] = $params['status'];
            }
            
            if (!empty($params['department_id'])) {
                $filters[] = "jp.department_id = ?";
                $filterValues[] = $params['department_id'];
            }
            
            if (!empty($params['position_id'])) {
                $filters[] = "jp.position_id = ?";
                $filterValues[] = $params['position_id'];
            }
            
            if (!empty($params['search'])) {
                $filters[] = "(jp.title LIKE ? OR jp.description LIKE ?)";
                $searchTerm = '%' . $params['search'] . '%';
                $filterValues[] = $searchTerm;
                $filterValues[] = $searchTerm;
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM job_postings jp $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get job postings
            $query = "SELECT jp.*, 
                            d.name as department_name,
                            p.title as position_title,
                            COUNT(ja.id) as application_count
                     FROM job_postings jp
                     LEFT JOIN departments d ON jp.department_id = d.id
                     LEFT JOIN positions p ON jp.position_id = p.id
                     LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
                     $whereClause
                     GROUP BY jp.id
                     ORDER BY jp.created_at DESC
                     LIMIT ? OFFSET ?";
            
            $jobPostings = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            // Format data
            foreach ($jobPostings as &$posting) {
                $posting['application_count'] = (int)$posting['application_count'];
                $posting['salary_min'] = $posting['salary_min'] ? (float)$posting['salary_min'] : null;
                $posting['salary_max'] = $posting['salary_max'] ? (float)$posting['salary_max'] : null;
            }
            
            Response::paginated($jobPostings, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch job postings: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single job posting
     */
    public function showJobPosting($id) {
        try {
            $query = "SELECT jp.*, 
                            d.name as department_name,
                            p.title as position_title,
                            COUNT(ja.id) as application_count
                     FROM job_postings jp
                     LEFT JOIN departments d ON jp.department_id = d.id
                     LEFT JOIN positions p ON jp.position_id = p.id
                     LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
                     WHERE jp.id = ?
                     GROUP BY jp.id";
            
            $jobPosting = $this->db->selectOne($query, [$id]);
            
            if (!$jobPosting) {
                Response::notFound('Job posting not found');
                return;
            }
            
            // Format data
            $jobPosting['application_count'] = (int)$jobPosting['application_count'];
            $jobPosting['salary_min'] = $jobPosting['salary_min'] ? (float)$jobPosting['salary_min'] : null;
            $jobPosting['salary_max'] = $jobPosting['salary_max'] ? (float)$jobPosting['salary_max'] : null;
            
            // Get recent applications if user has access
            if (in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                $applications = $this->db->select(
                    "SELECT ja.id, ja.applicant_name, ja.email, ja.phone, ja.status, ja.applied_at
                     FROM job_applications ja
                     WHERE ja.job_posting_id = ?
                     ORDER BY ja.applied_at DESC
                     LIMIT 10",
                    [$id]
                );
                $jobPosting['recent_applications'] = $applications;
            }
            
            Response::success($jobPosting);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch job posting: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new job posting
     */
    public function storeJobPosting() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'title' => 'required|string|max:200',
                'description' => 'required|string',
                'requirements' => 'required|string',
                'department_id' => 'required|integer',
                'position_id' => 'required|integer',
                'employment_type' => 'required|in:full_time,part_time,contract,internship',
                'experience_level' => 'required|in:entry,junior,mid,senior,executive',
                'salary_min' => 'numeric|min:0',
                'salary_max' => 'numeric|min:0',
                'location' => 'string|max:100',
                'application_deadline' => 'date',
                'status' => 'in:draft,published,closed'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Validate department and position
            $department = $this->db->selectOne(
                "SELECT id FROM departments WHERE id = ? AND status = 'active'",
                [$data['department_id']]
            );
            
            if (!$department) {
                Response::error('Invalid department selected', 400);
                return;
            }
            
            $position = $this->db->selectOne(
                "SELECT id FROM positions WHERE id = ? AND department_id = ? AND status = 'active'",
                [$data['position_id'], $data['department_id']]
            );
            
            if (!$position) {
                Response::error('Invalid position selected', 400);
                return;
            }
            
            // Validate salary range
            if (isset($data['salary_min']) && isset($data['salary_max']) && 
                $data['salary_min'] > $data['salary_max']) {
                Response::error('Minimum salary cannot be greater than maximum salary', 400);
                return;
            }
            
            // Create job posting
            $query = "INSERT INTO job_postings 
                     (title, description, requirements, department_id, position_id, employment_type, 
                      experience_level, salary_min, salary_max, location, application_deadline, 
                      status, posted_by, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $jobPostingId = $this->db->insert($query, [
                $data['title'],
                $data['description'],
                $data['requirements'],
                $data['department_id'],
                $data['position_id'],
                $data['employment_type'],
                $data['experience_level'],
                $data['salary_min'] ?? null,
                $data['salary_max'] ?? null,
                $data['location'] ?? null,
                $data['application_deadline'] ?? null,
                $data['status'] ?? 'draft',
                $this->user['employee_id']
            ]);
            
            if ($jobPostingId) {
                $jobPosting = $this->db->selectOne(
                    "SELECT jp.*, d.name as department_name, p.title as position_title
                     FROM job_postings jp
                     LEFT JOIN departments d ON jp.department_id = d.id
                     LEFT JOIN positions p ON jp.position_id = p.id
                     WHERE jp.id = ?",
                    [$jobPostingId]
                );
                
                Response::created($jobPosting, 'Job posting created successfully');
            } else {
                Response::serverError('Failed to create job posting');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to create job posting: ' . $e->getMessage());
        }
    }
    
    /**
     * Update job posting
     */
    public function updateJobPosting($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Check if job posting exists
            $jobPosting = $this->db->selectOne(
                "SELECT * FROM job_postings WHERE id = ?",
                [$id]
            );
            
            if (!$jobPosting) {
                Response::notFound('Job posting not found');
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'title' => 'string|max:200',
                'description' => 'string',
                'requirements' => 'string',
                'department_id' => 'integer',
                'position_id' => 'integer',
                'employment_type' => 'in:full_time,part_time,contract,internship',
                'experience_level' => 'in:entry,junior,mid,senior,executive',
                'salary_min' => 'numeric|min:0',
                'salary_max' => 'numeric|min:0',
                'location' => 'string|max:100',
                'application_deadline' => 'date',
                'status' => 'in:draft,published,closed'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Validate department and position if provided
            if (isset($data['department_id'])) {
                $department = $this->db->selectOne(
                    "SELECT id FROM departments WHERE id = ? AND status = 'active'",
                    [$data['department_id']]
                );
                
                if (!$department) {
                    Response::error('Invalid department selected', 400);
                    return;
                }
            }
            
            if (isset($data['position_id'])) {
                $departmentId = $data['department_id'] ?? $jobPosting['department_id'];
                $position = $this->db->selectOne(
                    "SELECT id FROM positions WHERE id = ? AND department_id = ? AND status = 'active'",
                    [$data['position_id'], $departmentId]
                );
                
                if (!$position) {
                    Response::error('Invalid position selected', 400);
                    return;
                }
            }
            
            // Validate salary range
            $salaryMin = $data['salary_min'] ?? $jobPosting['salary_min'];
            $salaryMax = $data['salary_max'] ?? $jobPosting['salary_max'];
            
            if ($salaryMin && $salaryMax && $salaryMin > $salaryMax) {
                Response::error('Minimum salary cannot be greater than maximum salary', 400);
                return;
            }
            
            // Prepare update data
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = [
                'title', 'description', 'requirements', 'department_id', 'position_id',
                'employment_type', 'experience_level', 'salary_min', 'salary_max',
                'location', 'application_deadline', 'status'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                Response::error('No valid fields to update', 400);
                return;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $updateValues[] = $id;
            
            // Update job posting
            $query = "UPDATE job_postings SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $result = $this->db->update($query, $updateValues);
            
            if ($result) {
                $updatedJobPosting = $this->db->selectOne(
                    "SELECT jp.*, d.name as department_name, p.title as position_title
                     FROM job_postings jp
                     LEFT JOIN departments d ON jp.department_id = d.id
                     LEFT JOIN positions p ON jp.position_id = p.id
                     WHERE jp.id = ?",
                    [$id]
                );
                
                Response::updated($updatedJobPosting, 'Job posting updated successfully');
            } else {
                Response::serverError('Failed to update job posting');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update job posting: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete job posting
     */
    public function deleteJobPosting($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            // Check if job posting exists
            $jobPosting = $this->db->selectOne(
                "SELECT * FROM job_postings WHERE id = ?",
                [$id]
            );
            
            if (!$jobPosting) {
                Response::notFound('Job posting not found');
                return;
            }
            
            // Check if there are applications
            $applicationCount = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM job_applications WHERE job_posting_id = ?",
                [$id]
            );
            
            if ($applicationCount['count'] > 0) {
                Response::error('Cannot delete job posting with existing applications', 400);
                return;
            }
            
            // Delete job posting
            $result = $this->db->delete("DELETE FROM job_postings WHERE id = ?", [$id]);
            
            if ($result) {
                Response::deleted('Job posting deleted successfully');
            } else {
                Response::serverError('Failed to delete job posting');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to delete job posting: ' . $e->getMessage());
        }
    }
    
    /**
     * Get job applications
     */
    public function getApplications() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $params = Router::getQueryParams();
            
            // Pagination
            $page = max(1, (int)($params['page'] ?? 1));
            $perPage = min(100, max(10, (int)($params['per_page'] ?? 20)));
            $offset = ($page - 1) * $perPage;
            
            // Filters
            $filters = [];
            $filterValues = [];
            
            if (!empty($params['job_posting_id'])) {
                $filters[] = "ja.job_posting_id = ?";
                $filterValues[] = $params['job_posting_id'];
            }
            
            if (!empty($params['status'])) {
                $filters[] = "ja.status = ?";
                $filterValues[] = $params['status'];
            }
            
            if (!empty($params['search'])) {
                $filters[] = "(ja.applicant_name LIKE ? OR ja.email LIKE ?)";
                $searchTerm = '%' . $params['search'] . '%';
                $filterValues[] = $searchTerm;
                $filterValues[] = $searchTerm;
            }
            
            $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM job_applications ja $whereClause";
            $totalResult = $this->db->selectOne($countQuery, $filterValues);
            $totalRecords = $totalResult['total'];
            
            // Get applications
            $query = "SELECT ja.*, 
                            jp.title as job_title,
                            d.name as department_name,
                            p.title as position_title
                     FROM job_applications ja
                     JOIN job_postings jp ON ja.job_posting_id = jp.id
                     LEFT JOIN departments d ON jp.department_id = d.id
                     LEFT JOIN positions p ON jp.position_id = p.id
                     $whereClause
                     ORDER BY ja.applied_at DESC
                     LIMIT ? OFFSET ?";
            
            $applications = $this->db->select($query, array_merge($filterValues, [$perPage, $offset]));
            
            Response::paginated($applications, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch applications: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single application
     */
    public function showApplication($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $query = "SELECT ja.*, 
                            jp.title as job_title, jp.description as job_description,
                            d.name as department_name,
                            p.title as position_title
                     FROM job_applications ja
                     JOIN job_postings jp ON ja.job_posting_id = jp.id
                     LEFT JOIN departments d ON jp.department_id = d.id
                     LEFT JOIN positions p ON jp.position_id = p.id
                     WHERE ja.id = ?";
            
            $application = $this->db->selectOne($query, [$id]);
            
            if (!$application) {
                Response::notFound('Application not found');
                return;
            }
            
            // Get interview history
            $interviews = $this->getApplicationInterviews($id);
            $application['interviews'] = $interviews;
            
            Response::success($application);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch application: ' . $e->getMessage());
        }
    }
    
    /**
     * Submit job application (public endpoint)
     */
    public function submitApplication() {
        try {
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'job_posting_id' => 'required|integer',
                'applicant_name' => 'required|string|max:100',
                'email' => 'required|email|max:100',
                'phone' => 'required|string|max:20',
                'cover_letter' => 'string',
                'experience_years' => 'integer|min:0',
                'current_salary' => 'numeric|min:0',
                'expected_salary' => 'numeric|min:0',
                'notice_period' => 'string|max:50',
                'linkedin_profile' => 'url',
                'portfolio_url' => 'url'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Validate job posting
            $jobPosting = $this->db->selectOne(
                "SELECT * FROM job_postings WHERE id = ? AND status = 'published'",
                [$data['job_posting_id']]
            );
            
            if (!$jobPosting) {
                Response::error('Job posting not found or not accepting applications', 400);
                return;
            }
            
            // Check application deadline
            if ($jobPosting['application_deadline'] && 
                strtotime($jobPosting['application_deadline']) < time()) {
                Response::error('Application deadline has passed', 400);
                return;
            }
            
            // Check for duplicate application
            $existing = $this->db->selectOne(
                "SELECT id FROM job_applications WHERE job_posting_id = ? AND email = ?",
                [$data['job_posting_id'], $data['email']]
            );
            
            if ($existing) {
                Response::error('You have already applied for this position', 400);
                return;
            }
            
            // Create application
            $query = "INSERT INTO job_applications 
                     (job_posting_id, applicant_name, email, phone, cover_letter, 
                      experience_years, current_salary, expected_salary, notice_period, 
                      linkedin_profile, portfolio_url, status, applied_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'applied', NOW())";
            
            $applicationId = $this->db->insert($query, [
                $data['job_posting_id'],
                $data['applicant_name'],
                $data['email'],
                $data['phone'],
                $data['cover_letter'] ?? null,
                $data['experience_years'] ?? null,
                $data['current_salary'] ?? null,
                $data['expected_salary'] ?? null,
                $data['notice_period'] ?? null,
                $data['linkedin_profile'] ?? null,
                $data['portfolio_url'] ?? null
            ]);
            
            if ($applicationId) {
                $application = $this->db->selectOne(
                    "SELECT ja.*, jp.title as job_title
                     FROM job_applications ja
                     JOIN job_postings jp ON ja.job_posting_id = jp.id
                     WHERE ja.id = ?",
                    [$applicationId]
                );
                
                Response::created($application, 'Application submitted successfully');
            } else {
                Response::serverError('Failed to submit application');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to submit application: ' . $e->getMessage());
        }
    }
    
    /**
     * Update application status
     */
    public function updateApplicationStatus($id) {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Check if application exists
            $application = $this->db->selectOne(
                "SELECT * FROM job_applications WHERE id = ?",
                [$id]
            );
            
            if (!$application) {
                Response::notFound('Application not found');
                return;
            }
            
            // Validate input
            $validator = Validator::make($data, [
                'status' => 'required|in:applied,screening,interview,selected,rejected,hired',
                'notes' => 'string'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Update application
            $query = "UPDATE job_applications SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?";
            $result = $this->db->update($query, [
                $data['status'],
                $data['notes'] ?? null,
                $id
            ]);
            
            if ($result) {
                $updatedApplication = $this->db->selectOne(
                    "SELECT ja.*, jp.title as job_title
                     FROM job_applications ja
                     JOIN job_postings jp ON ja.job_posting_id = jp.id
                     WHERE ja.id = ?",
                    [$id]
                );
                
                Response::updated($updatedApplication, 'Application status updated successfully');
            } else {
                Response::serverError('Failed to update application status');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to update application status: ' . $e->getMessage());
        }
    }
    
    /**
     * Schedule interview
     */
    public function scheduleInterview() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $data = Router::getRequestData();
            
            // Validate input
            $validator = Validator::make($data, [
                'application_id' => 'required|integer',
                'interview_type' => 'required|in:phone,video,in_person,technical,hr,final',
                'scheduled_at' => 'required|datetime',
                'duration_minutes' => 'integer|min:15|max:480',
                'location' => 'string|max:200',
                'meeting_link' => 'url',
                'interviewer_ids' => 'array',
                'notes' => 'string'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->getErrors());
                return;
            }
            
            // Validate application
            $application = $this->db->selectOne(
                "SELECT * FROM job_applications WHERE id = ?",
                [$data['application_id']]
            );
            
            if (!$application) {
                Response::error('Application not found', 400);
                return;
            }
            
            // Create interviews table if not exists
            $this->createInterviewsTable();
            
            // Create interview
            $query = "INSERT INTO interviews 
                     (application_id, interview_type, scheduled_at, duration_minutes, 
                      location, meeting_link, notes, status, created_by, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', ?, NOW())";
            
            $interviewId = $this->db->insert($query, [
                $data['application_id'],
                $data['interview_type'],
                $data['scheduled_at'],
                $data['duration_minutes'] ?? 60,
                $data['location'] ?? null,
                $data['meeting_link'] ?? null,
                $data['notes'] ?? null,
                $this->user['employee_id']
            ]);
            
            if ($interviewId) {
                // Add interviewers if provided
                if (!empty($data['interviewer_ids'])) {
                    $this->addInterviewers($interviewId, $data['interviewer_ids']);
                }
                
                // Update application status to interview
                $this->db->update(
                    "UPDATE job_applications SET status = 'interview' WHERE id = ?",
                    [$data['application_id']]
                );
                
                $interview = $this->db->selectOne(
                    "SELECT i.*, ja.applicant_name, ja.email, jp.title as job_title
                     FROM interviews i
                     JOIN job_applications ja ON i.application_id = ja.id
                     JOIN job_postings jp ON ja.job_posting_id = jp.id
                     WHERE i.id = ?",
                    [$interviewId]
                );
                
                Response::created($interview, 'Interview scheduled successfully');
            } else {
                Response::serverError('Failed to schedule interview');
            }
            
        } catch (Exception $e) {
            Response::serverError('Failed to schedule interview: ' . $e->getMessage());
        }
    }
    
    /**
     * Get recruitment analytics
     */
    public function getAnalytics() {
        try {
            if (!in_array($this->user['role'], ['admin', 'hr', 'manager'])) {
                Response::forbidden('Access denied');
                return;
            }
            
            $params = Router::getQueryParams();
            $year = $params['year'] ?? date('Y');
            
            // Overall recruitment statistics
            $overallStats = $this->db->selectOne(
                "SELECT 
                    COUNT(DISTINCT jp.id) as total_job_postings,
                    COUNT(DISTINCT ja.id) as total_applications,
                    COUNT(CASE WHEN ja.status = 'hired' THEN 1 END) as total_hired,
                    COUNT(CASE WHEN jp.status = 'published' THEN 1 END) as active_postings
                 FROM job_postings jp
                 LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
                 WHERE YEAR(jp.created_at) = ?",
                [$year]
            );
            
            // Application status distribution
            $statusDistribution = $this->db->select(
                "SELECT status, COUNT(*) as count
                 FROM job_applications ja
                 JOIN job_postings jp ON ja.job_posting_id = jp.id
                 WHERE YEAR(jp.created_at) = ?
                 GROUP BY status
                 ORDER BY count DESC",
                [$year]
            );
            
            // Department-wise hiring
            $departmentStats = $this->db->select(
                "SELECT d.name as department_name,
                        COUNT(DISTINCT jp.id) as job_postings,
                        COUNT(ja.id) as applications,
                        COUNT(CASE WHEN ja.status = 'hired' THEN 1 END) as hired
                 FROM departments d
                 LEFT JOIN job_postings jp ON d.id = jp.department_id AND YEAR(jp.created_at) = ?
                 LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
                 GROUP BY d.id, d.name
                 ORDER BY hired DESC",
                [$year]
            );
            
            // Monthly trends
            $monthlyTrends = $this->db->select(
                "SELECT 
                    MONTH(jp.created_at) as month,
                    COUNT(DISTINCT jp.id) as job_postings,
                    COUNT(ja.id) as applications,
                    COUNT(CASE WHEN ja.status = 'hired' THEN 1 END) as hired
                 FROM job_postings jp
                 LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
                 WHERE YEAR(jp.created_at) = ?
                 GROUP BY MONTH(jp.created_at)
                 ORDER BY month",
                [$year]
            );
            
            $analytics = [
                'overall_statistics' => [
                    'total_job_postings' => (int)$overallStats['total_job_postings'],
                    'total_applications' => (int)$overallStats['total_applications'],
                    'total_hired' => (int)$overallStats['total_hired'],
                    'active_postings' => (int)$overallStats['active_postings'],
                    'conversion_rate' => $overallStats['total_applications'] > 0 ? 
                        round(($overallStats['total_hired'] / $overallStats['total_applications']) * 100, 2) : 0
                ],
                'status_distribution' => $statusDistribution,
                'department_statistics' => $departmentStats,
                'monthly_trends' => $monthlyTrends,
                'year' => $year
            ];
            
            Response::success($analytics);
            
        } catch (Exception $e) {
            Response::serverError('Failed to fetch recruitment analytics: ' . $e->getMessage());
        }
    }
    
    // Helper methods
    
    private function getApplicationInterviews($applicationId) {
        try {
            $this->createInterviewsTable();
            
            $query = "SELECT i.*, e.first_name, e.last_name
                     FROM interviews i
                     LEFT JOIN employees e ON i.created_by = e.id
                     WHERE i.application_id = ?
                     ORDER BY i.scheduled_at";
            
            return $this->db->select($query, [$applicationId]);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function addInterviewers($interviewId, $interviewerIds) {
        try {
            $this->createInterviewersTable();
            
            foreach ($interviewerIds as $interviewerId) {
                $this->db->insert(
                    "INSERT INTO interview_interviewers (interview_id, interviewer_id) VALUES (?, ?)",
                    [$interviewId, $interviewerId]
                );
            }
        } catch (Exception $e) {
            // Log error but don't fail the interview creation
        }
    }
    
    private function createInterviewsTable() {
        $query = "CREATE TABLE IF NOT EXISTS interviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            interview_type ENUM('phone', 'video', 'in_person', 'technical', 'hr', 'final') NOT NULL,
            scheduled_at DATETIME NOT NULL,
            duration_minutes INT DEFAULT 60,
            location VARCHAR(200),
            meeting_link VARCHAR(500),
            notes TEXT,
            feedback TEXT,
            rating DECIMAL(3,2),
            status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES employees(id) ON DELETE SET NULL
        )";
        
        $this->db->execute($query);
    }
    
    private function createInterviewersTable() {
        $query = "CREATE TABLE IF NOT EXISTS interview_interviewers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            interview_id INT NOT NULL,
            interviewer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE,
            FOREIGN KEY (interviewer_id) REFERENCES employees(id) ON DELETE CASCADE,
            UNIQUE KEY unique_interview_interviewer (interview_id, interviewer_id)
        )";
        
        $this->db->execute($query);
    }
}

?>