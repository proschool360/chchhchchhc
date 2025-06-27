<template>
  <div class="recruitment-list">
    <div class="page-header">
      <div class="header-content">
        <h1>Recruitment Management</h1>
        <p>Manage job postings and track recruitment processes</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" :icon="Plus" @click="showJobDialog = true">
          Post New Job
        </el-button>
      </div>
    </div>
    
    <!-- Recruitment Summary -->
    <el-row :gutter="20" class="summary-cards">
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon active">
              <el-icon><Briefcase /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ recruitmentStats.active_jobs }}</h3>
              <p>Active Jobs</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon applications">
              <el-icon><Document /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ recruitmentStats.total_applications }}</h3>
              <p>Applications</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon interviews">
              <el-icon><ChatDotRound /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ recruitmentStats.scheduled_interviews }}</h3>
              <p>Interviews</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon hired">
              <el-icon><Check /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ recruitmentStats.hired_this_month }}</h3>
              <p>Hired This Month</p>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    
    <!-- Filters and Search -->
    <el-card class="filter-card" shadow="never">
      <el-row :gutter="20">
        <el-col :xs="24" :sm="8" :md="6">
          <el-input
            v-model="filters.search"
            placeholder="Search jobs..."
            :prefix-icon="Search"
            clearable
            @input="handleSearch"
          />
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.department"
            placeholder="Department"
            clearable
            @change="handleFilter"
          >
            <el-option
              v-for="dept in departments"
              :key="dept.id"
              :label="dept.name"
              :value="dept.id"
            />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.status"
            placeholder="Status"
            clearable
            @change="handleFilter"
          >
            <el-option label="Active" value="active" />
            <el-option label="Closed" value="closed" />
            <el-option label="Draft" value="draft" />
            <el-option label="On Hold" value="on_hold" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.job_type"
            placeholder="Job Type"
            clearable
            @change="handleFilter"
          >
            <el-option label="Full-time" value="full_time" />
            <el-option label="Part-time" value="part_time" />
            <el-option label="Contract" value="contract" />
            <el-option label="Internship" value="internship" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="6">
          <div class="filter-actions">
            <el-button :icon="Refresh" @click="resetFilters">Reset</el-button>
            <el-button type="success" :icon="Download" @click="exportJobs">
              Export
            </el-button>
          </div>
        </el-col>
      </el-row>
    </el-card>
    
    <!-- Jobs Table -->
    <el-card class="table-card" shadow="never">
      <el-table
        v-loading="loading"
        :data="jobPostings"
        stripe
        style="width: 100%"
        @sort-change="handleSort"
      >
        <el-table-column type="selection" width="55" />
        
        <el-table-column label="Job Title" min-width="200" sortable="custom" prop="title">
          <template #default="{ row }">
            <div class="job-info">
              <div class="job-title">{{ row.title }}</div>
              <div class="job-code">{{ row.job_code }}</div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Department" prop="department" width="120" sortable="custom" />
        
        <el-table-column label="Job Type" prop="job_type" width="120" sortable="custom">
          <template #default="{ row }">
            <el-tag size="small" effect="plain">{{ formatJobType(row.job_type) }}</el-tag>
          </template>
        </el-table-column>
        
        <el-table-column label="Experience" prop="experience_level" width="120" sortable="custom">
          <template #default="{ row }">
            {{ row.experience_level || '-' }}
          </template>
        </el-table-column>
        
        <el-table-column label="Salary Range" width="150">
          <template #default="{ row }">
            <div v-if="row.salary_min && row.salary_max" class="salary-range">
              ${{ formatCurrency(row.salary_min) }} - ${{ formatCurrency(row.salary_max) }}
            </div>
            <div v-else class="salary-range">Negotiable</div>
          </template>
        </el-table-column>
        
        <el-table-column label="Applications" prop="applications_count" width="120" sortable="custom">
          <template #default="{ row }">
            <el-link type="primary" @click="viewApplications(row)">
              {{ row.applications_count || 0 }}
            </el-link>
          </template>
        </el-table-column>
        
        <el-table-column label="Posted Date" prop="created_at" width="120" sortable="custom">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        
        <el-table-column label="Deadline" prop="application_deadline" width="120" sortable="custom">
          <template #default="{ row }">
            <div :class="{ 'deadline-warning': isDeadlineNear(row.application_deadline) }">
              {{ formatDate(row.application_deadline) }}
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Status" prop="status" width="120" sortable="custom">
          <template #default="{ row }">
            <el-tag
              :type="getStatusType(row.status)"
              size="small"
            >
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column label="Actions" width="200" fixed="right">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-tooltip content="View Details" placement="top">
                <el-button
                  type="primary"
                  :icon="View"
                  size="small"
                  circle
                  @click="viewJobDetails(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Edit" placement="top">
                <el-button
                  type="warning"
                  :icon="Edit"
                  size="small"
                  circle
                  @click="editJob(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Applications" placement="top">
                <el-button
                  type="info"
                  :icon="Document"
                  size="small"
                  circle
                  @click="viewApplications(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Clone" placement="top">
                <el-button
                  type="success"
                  :icon="CopyDocument"
                  size="small"
                  circle
                  @click="cloneJob(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Delete" placement="top">
                <el-button
                  type="danger"
                  :icon="Delete"
                  size="small"
                  circle
                  @click="deleteJob(row)"
                />
              </el-tooltip>
            </div>
          </template>
        </el-table-column>
      </el-table>
      
      <!-- Pagination -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="pagination.currentPage"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>
    
    <!-- New Job Dialog -->
    <el-dialog
      v-model="showJobDialog"
      title="Post New Job"
      width="800px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="jobFormRef"
        :model="jobForm"
        :rules="jobRules"
        label-width="140px"
      >
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Job Title" prop="title">
              <el-input v-model="jobForm.title" placeholder="Enter job title" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Job Code" prop="job_code">
              <el-input v-model="jobForm.job_code" placeholder="Auto-generated" readonly />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Department" prop="department_id">
              <el-select
                v-model="jobForm.department_id"
                placeholder="Select department"
                style="width: 100%"
              >
                <el-option
                  v-for="dept in departments"
                  :key="dept.id"
                  :label="dept.name"
                  :value="dept.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Job Type" prop="job_type">
              <el-select
                v-model="jobForm.job_type"
                placeholder="Select job type"
                style="width: 100%"
              >
                <el-option label="Full-time" value="full_time" />
                <el-option label="Part-time" value="part_time" />
                <el-option label="Contract" value="contract" />
                <el-option label="Internship" value="internship" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Experience Level" prop="experience_level">
              <el-select
                v-model="jobForm.experience_level"
                placeholder="Select experience level"
                style="width: 100%"
              >
                <el-option label="Entry Level" value="entry" />
                <el-option label="Mid Level" value="mid" />
                <el-option label="Senior Level" value="senior" />
                <el-option label="Executive" value="executive" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Location" prop="location">
              <el-input v-model="jobForm.location" placeholder="Job location" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Salary Min" prop="salary_min">
              <el-input-number
                v-model="jobForm.salary_min"
                :min="0"
                :step="1000"
                style="width: 100%"
                placeholder="Minimum salary"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Salary Max" prop="salary_max">
              <el-input-number
                v-model="jobForm.salary_max"
                :min="0"
                :step="1000"
                style="width: 100%"
                placeholder="Maximum salary"
              />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="Application Deadline" prop="application_deadline">
          <el-date-picker
            v-model="jobForm.application_deadline"
            type="date"
            placeholder="Select deadline"
            style="width: 100%"
            :disabled-date="disabledDate"
          />
        </el-form-item>
        
        <el-form-item label="Job Description" prop="description">
          <el-input
            v-model="jobForm.description"
            type="textarea"
            :rows="4"
            placeholder="Enter job description"
          />
        </el-form-item>
        
        <el-form-item label="Requirements" prop="requirements">
          <el-input
            v-model="jobForm.requirements"
            type="textarea"
            :rows="3"
            placeholder="Enter job requirements"
          />
        </el-form-item>
        
        <el-form-item label="Benefits" prop="benefits">
          <el-input
            v-model="jobForm.benefits"
            type="textarea"
            :rows="3"
            placeholder="Enter job benefits"
          />
        </el-form-item>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Remote Work" prop="remote_work">
              <el-switch v-model="jobForm.remote_work" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Urgent Hiring" prop="urgent">
              <el-switch v-model="jobForm.urgent" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showJobDialog = false">Cancel</el-button>
          <el-button @click="saveAsDraft" :loading="submitting">
            Save as Draft
          </el-button>
          <el-button type="primary" @click="publishJob" :loading="submitting">
            Publish Job
          </el-button>
        </span>
      </template>
    </el-dialog>
    
    <!-- Job Details Dialog -->
    <el-dialog
      v-model="showDetailsDialog"
      title="Job Details"
      width="800px"
    >
      <div v-if="selectedJob" class="job-details">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="Job Title" :span="2">
            <div class="job-title-detail">
              {{ selectedJob.title }}
              <el-tag v-if="selectedJob.urgent" type="danger" size="small" class="urgent-tag">
                Urgent
              </el-tag>
            </div>
          </el-descriptions-item>
          <el-descriptions-item label="Job Code">
            {{ selectedJob.job_code }}
          </el-descriptions-item>
          <el-descriptions-item label="Department">
            {{ selectedJob.department }}
          </el-descriptions-item>
          <el-descriptions-item label="Job Type">
            {{ formatJobType(selectedJob.job_type) }}
          </el-descriptions-item>
          <el-descriptions-item label="Experience Level">
            {{ selectedJob.experience_level || 'Not specified' }}
          </el-descriptions-item>
          <el-descriptions-item label="Location">
            {{ selectedJob.location }}
            <el-tag v-if="selectedJob.remote_work" type="success" size="small" class="remote-tag">
              Remote
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Salary Range">
            <div v-if="selectedJob.salary_min && selectedJob.salary_max">
              ${{ formatCurrency(selectedJob.salary_min) }} - ${{ formatCurrency(selectedJob.salary_max) }}
            </div>
            <div v-else>Negotiable</div>
          </el-descriptions-item>
          <el-descriptions-item label="Application Deadline">
            {{ formatDate(selectedJob.application_deadline) }}
          </el-descriptions-item>
          <el-descriptions-item label="Status">
            <el-tag :type="getStatusType(selectedJob.status)">
              {{ getStatusText(selectedJob.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Posted Date">
            {{ formatDate(selectedJob.created_at) }}
          </el-descriptions-item>
          <el-descriptions-item label="Applications">
            <el-link type="primary" @click="viewApplications(selectedJob)">
              {{ selectedJob.applications_count || 0 }} applications
            </el-link>
          </el-descriptions-item>
          <el-descriptions-item label="Posted By">
            {{ selectedJob.posted_by || 'HR Department' }}
          </el-descriptions-item>
        </el-descriptions>
        
        <div class="job-content">
          <div class="content-section">
            <h4>Job Description</h4>
            <p>{{ selectedJob.description || 'No description provided' }}</p>
          </div>
          
          <div class="content-section">
            <h4>Requirements</h4>
            <p>{{ selectedJob.requirements || 'No requirements specified' }}</p>
          </div>
          
          <div class="content-section">
            <h4>Benefits</h4>
            <p>{{ selectedJob.benefits || 'No benefits specified' }}</p>
          </div>
        </div>
      </div>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showDetailsDialog = false">Close</el-button>
          <el-button type="primary" @click="viewApplications(selectedJob)">
            View Applications
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Plus,
  Briefcase,
  Document,
  ChatDotRound,
  Check,
  Search,
  Refresh,
  Download,
  View,
  Edit,
  CopyDocument,
  Delete
} from '@element-plus/icons-vue'

export default {
  name: 'RecruitmentList',
  components: {
    Plus,
    Briefcase,
    Document,
    ChatDotRound,
    Check,
    Search,
    Refresh,
    Download,
    View,
    Edit,
    CopyDocument,
    Delete
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const jobFormRef = ref()
    const showJobDialog = ref(false)
    const showDetailsDialog = ref(false)
    const submitting = ref(false)
    const selectedJob = ref(null)
    
    const filters = reactive({
      search: '',
      department: '',
      status: '',
      job_type: ''
    })
    
    const pagination = reactive({
      currentPage: 1,
      pageSize: 20,
      total: 0
    })
    
    const sortConfig = ref({
      prop: '',
      order: ''
    })
    
    const jobForm = reactive({
      title: '',
      job_code: '',
      department_id: '',
      job_type: '',
      experience_level: '',
      location: '',
      salary_min: null,
      salary_max: null,
      application_deadline: '',
      description: '',
      requirements: '',
      benefits: '',
      remote_work: false,
      urgent: false
    })
    
    const jobRules = {
      title: [
        { required: true, message: 'Please enter job title', trigger: 'blur' }
      ],
      department_id: [
        { required: true, message: 'Please select department', trigger: 'change' }
      ],
      job_type: [
        { required: true, message: 'Please select job type', trigger: 'change' }
      ],
      location: [
        { required: true, message: 'Please enter location', trigger: 'blur' }
      ],
      application_deadline: [
        { required: true, message: 'Please select application deadline', trigger: 'change' }
      ],
      description: [
        { required: true, message: 'Please enter job description', trigger: 'blur' }
      ]
    }
    
    const jobPostings = computed(() => store.getters['recruitment/jobPostings'])
    const recruitmentStats = computed(() => store.getters['recruitment/recruitmentStats'])
    const departments = computed(() => store.getters['employees/departments'])
    const loading = computed(() => store.getters['recruitment/loading'])
    
    const formatCurrency = (amount) => {
      if (!amount) return '0'
      return parseFloat(amount).toLocaleString('en-US')
    }
    
    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const formatJobType = (type) => {
      const typeMap = {
        full_time: 'Full-time',
        part_time: 'Part-time',
        contract: 'Contract',
        internship: 'Internship'
      }
      return typeMap[type] || type
    }
    
    const isDeadlineNear = (deadline) => {
      if (!deadline) return false
      const deadlineDate = new Date(deadline)
      const today = new Date()
      const diffTime = deadlineDate - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      return diffDays <= 7 && diffDays >= 0
    }
    
    const getStatusType = (status) => {
      const statusMap = {
        active: 'success',
        closed: 'info',
        draft: 'warning',
        on_hold: 'danger'
      }
      return statusMap[status] || 'info'
    }
    
    const getStatusText = (status) => {
      const statusMap = {
        active: 'Active',
        closed: 'Closed',
        draft: 'Draft',
        on_hold: 'On Hold'
      }
      return statusMap[status] || status
    }
    
    const disabledDate = (time) => {
      return time.getTime() < Date.now() - 8.64e7 // Disable past dates
    }
    
    const generateJobCode = () => {
      const prefix = 'JOB'
      const timestamp = Date.now().toString().slice(-6)
      return `${prefix}${timestamp}`
    }
    
    const fetchJobPostings = async () => {
      const params = {
        page: pagination.currentPage,
        limit: pagination.pageSize,
        search: filters.search,
        department: filters.department,
        status: filters.status,
        job_type: filters.job_type,
        sort_by: sortConfig.value.prop,
        sort_order: sortConfig.value.order
      }
      
      const result = await store.dispatch('recruitment/fetchJobPostings', params)
      if (result && result.pagination) {
        pagination.total = result.pagination.total
      }
    }
    
    const handleSearch = () => {
      pagination.currentPage = 1
      fetchJobPostings()
    }
    
    const handleFilter = () => {
      pagination.currentPage = 1
      fetchJobPostings()
    }
    
    const handleSort = ({ prop, order }) => {
      sortConfig.value.prop = prop
      sortConfig.value.order = order === 'ascending' ? 'asc' : 'desc'
      fetchJobPostings()
    }
    
    const handleSizeChange = (size) => {
      pagination.pageSize = size
      pagination.currentPage = 1
      fetchJobPostings()
    }
    
    const handleCurrentChange = (page) => {
      pagination.currentPage = page
      fetchJobPostings()
    }
    
    const resetFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      pagination.currentPage = 1
      sortConfig.value = { prop: '', order: '' }
      fetchJobPostings()
    }
    
    const exportJobs = async () => {
      try {
        const params = {
          search: filters.search,
          department: filters.department,
          status: filters.status,
          job_type: filters.job_type,
          format: 'csv'
        }
        
        await store.dispatch('recruitment/exportJobs', params)
        ElMessage.success('Jobs exported successfully')
      } catch (error) {
        console.error('Export error:', error)
        ElMessage.error('Failed to export jobs')
      }
    }
    
    const resetJobForm = () => {
      Object.keys(jobForm).forEach(key => {
        if (key === 'remote_work' || key === 'urgent') {
          jobForm[key] = false
        } else if (key === 'salary_min' || key === 'salary_max') {
          jobForm[key] = null
        } else {
          jobForm[key] = ''
        }
      })
      jobForm.job_code = generateJobCode()
    }
    
    const saveAsDraft = async () => {
      try {
        submitting.value = true
        
        const jobData = {
          ...jobForm,
          status: 'draft'
        }
        
        const result = await store.dispatch('recruitment/createJob', jobData)
        
        if (result.success) {
          ElMessage.success('Job saved as draft successfully')
          showJobDialog.value = false
          resetJobForm()
          fetchJobPostings()
          store.dispatch('recruitment/fetchRecruitmentStats')
        } else {
          ElMessage.error(result.message || 'Failed to save job')
        }
      } catch (error) {
        console.error('Save job error:', error)
        ElMessage.error('An error occurred while saving job')
      } finally {
        submitting.value = false
      }
    }
    
    const publishJob = async () => {
      try {
        const valid = await jobFormRef.value.validate()
        if (!valid) return
        
        submitting.value = true
        
        const jobData = {
          ...jobForm,
          status: 'active'
        }
        
        const result = await store.dispatch('recruitment/createJob', jobData)
        
        if (result.success) {
          ElMessage.success('Job published successfully')
          showJobDialog.value = false
          resetJobForm()
          fetchJobPostings()
          store.dispatch('recruitment/fetchRecruitmentStats')
        } else {
          ElMessage.error(result.message || 'Failed to publish job')
        }
      } catch (error) {
        console.error('Publish job error:', error)
        ElMessage.error('An error occurred while publishing job')
      } finally {
        submitting.value = false
      }
    }
    
    const viewJobDetails = async (job) => {
      try {
        const result = await store.dispatch('recruitment/fetchJobDetails', job.id)
        if (result.success) {
          selectedJob.value = result.data
          showDetailsDialog.value = true
        } else {
          ElMessage.error('Failed to load job details')
        }
      } catch (error) {
        console.error('View job details error:', error)
        ElMessage.error('An error occurred while loading job details')
      }
    }
    
    const editJob = (job) => {
      // Navigate to edit job page or populate form
      router.push(`/recruitment/jobs/${job.id}/edit`)
    }
    
    const viewApplications = (job) => {
      router.push(`/recruitment/jobs/${job.id}/applications`)
    }
    
    const cloneJob = async (job) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to clone the job "${job.title}"?`,
          'Confirm Clone',
          {
            confirmButtonText: 'Clone',
            cancelButtonText: 'Cancel',
            type: 'info'
          }
        )
        
        const result = await store.dispatch('recruitment/cloneJob', job.id)
        if (result.success) {
          ElMessage.success('Job cloned successfully')
          fetchJobPostings()
          store.dispatch('recruitment/fetchRecruitmentStats')
        } else {
          ElMessage.error(result.message || 'Failed to clone job')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Clone job error:', error)
          ElMessage.error('An error occurred while cloning job')
        }
      }
    }
    
    const deleteJob = async (job) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to delete the job "${job.title}"? This action cannot be undone.`,
          'Confirm Deletion',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        
        const result = await store.dispatch('recruitment/deleteJob', job.id)
        if (result.success) {
          ElMessage.success('Job deleted successfully')
          fetchJobPostings()
          store.dispatch('recruitment/fetchRecruitmentStats')
        } else {
          ElMessage.error(result.message || 'Failed to delete job')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Delete job error:', error)
          ElMessage.error('An error occurred while deleting job')
        }
      }
    }
    
    // Watch for job title changes to generate job code
    watch(() => jobForm.title, (newTitle) => {
      if (newTitle && !jobForm.job_code) {
        jobForm.job_code = generateJobCode()
      }
    })
    
    onMounted(async () => {
      resetJobForm()
      await Promise.all([
        store.dispatch('employees/fetchDepartments'),
        store.dispatch('recruitment/fetchRecruitmentStats'),
        fetchJobPostings()
      ])
    })
    
    return {
      filters,
      pagination,
      jobPostings,
      recruitmentStats,
      departments,
      loading,
      showJobDialog,
      showDetailsDialog,
      jobFormRef,
      jobForm,
      jobRules,
      selectedJob,
      submitting,
      formatCurrency,
      formatDate,
      formatJobType,
      isDeadlineNear,
      getStatusType,
      getStatusText,
      disabledDate,
      handleSearch,
      handleFilter,
      handleSort,
      handleSizeChange,
      handleCurrentChange,
      resetFilters,
      exportJobs,
      saveAsDraft,
      publishJob,
      viewJobDetails,
      editJob,
      viewApplications,
      cloneJob,
      deleteJob
    }
  }
}
</script>

<style scoped>
.recruitment-list {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.header-content h1 {
  margin: 0 0 8px 0;
  color: #303133;
  font-size: 24px;
  font-weight: 600;
}

.header-content p {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.summary-cards {
  margin-bottom: 24px;
}

.summary-card {
  border: 1px solid #EBEEF5;
}

.summary-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.summary-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.summary-icon.active {
  background-color: #67C23A;
}

.summary-icon.applications {
  background-color: #409EFF;
}

.summary-icon.interviews {
  background-color: #E6A23C;
}

.summary-icon.hired {
  background-color: #F56C6C;
}

.summary-info h3 {
  margin: 0 0 4px 0;
  color: #303133;
  font-size: 24px;
  font-weight: 600;
}

.summary-info p {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.filter-card {
  margin-bottom: 20px;
  border: 1px solid #EBEEF5;
}

.filter-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.table-card {
  border: 1px solid #EBEEF5;
}

.job-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.job-title {
  font-weight: 500;
  color: #303133;
}

.job-code {
  font-size: 12px;
  color: #909399;
}

.salary-range {
  font-size: 13px;
  color: #606266;
}

.deadline-warning {
  color: #E6A23C;
  font-weight: 500;
}

.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #EBEEF5;
}

.job-details {
  margin-bottom: 20px;
}

.job-title-detail {
  display: flex;
  align-items: center;
  gap: 8px;
}

.urgent-tag {
  margin-left: 8px;
}

.remote-tag {
  margin-left: 8px;
}

.job-content {
  margin-top: 24px;
}

.content-section {
  margin-bottom: 20px;
}

.content-section h4 {
  margin: 0 0 8px 0;
  color: #303133;
  font-size: 14px;
  font-weight: 600;
}

.content-section p {
  margin: 0;
  color: #606266;
  line-height: 1.6;
  white-space: pre-wrap;
}

:deep(.el-table) {
  font-size: 14px;
}

:deep(.el-table th) {
  background-color: #FAFAFA;
  color: #606266;
  font-weight: 600;
}

:deep(.el-table td) {
  padding: 12px 0;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
  color: #606266;
}

:deep(.el-descriptions__content) {
  color: #303133;
}

@media (max-width: 768px) {
  .recruitment-list {
    padding: 15px;
  }
  
  .page-header {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }
  
  .header-actions {
    align-self: flex-start;
  }
  
  .filter-actions {
    justify-content: flex-start;
    margin-top: 12px;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 2px;
  }
  
  :deep(.el-table) {
    font-size: 12px;
  }
  
  .job-info {
    text-align: center;
  }
}
</style>