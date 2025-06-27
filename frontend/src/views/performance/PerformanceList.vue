<template>
  <div class="performance-list">
    <div class="page-header">
      <div class="header-content">
        <h1>Performance Management</h1>
        <p>Track and manage employee performance evaluations</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" :icon="Plus" @click="showEvaluationDialog = true">
          New Evaluation
        </el-button>
      </div>
    </div>
    
    <!-- Performance Summary -->
    <el-row :gutter="20" class="summary-cards">
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon pending">
              <el-icon><Clock /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ performanceStats.pending_evaluations }}</h3>
              <p>Pending Evaluations</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon completed">
              <el-icon><Check /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ performanceStats.completed_evaluations }}</h3>
              <p>Completed</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon average">
              <el-icon><TrendCharts /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ performanceStats.average_score }}%</h3>
              <p>Average Score</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon overdue">
              <el-icon><Warning /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ performanceStats.overdue_evaluations }}</h3>
              <p>Overdue</p>
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
            placeholder="Search employees..."
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
            <el-option label="Pending" value="pending" />
            <el-option label="In Progress" value="in_progress" />
            <el-option label="Completed" value="completed" />
            <el-option label="Overdue" value="overdue" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.evaluation_period"
            placeholder="Period"
            clearable
            @change="handleFilter"
          >
            <el-option label="Q1 2024" value="2024-Q1" />
            <el-option label="Q2 2024" value="2024-Q2" />
            <el-option label="Q3 2024" value="2024-Q3" />
            <el-option label="Q4 2024" value="2024-Q4" />
            <el-option label="Annual 2024" value="2024-Annual" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="6">
          <div class="filter-actions">
            <el-button :icon="Refresh" @click="resetFilters">Reset</el-button>
            <el-button type="success" :icon="Download" @click="exportEvaluations">
              Export
            </el-button>
          </div>
        </el-col>
      </el-row>
    </el-card>
    
    <!-- Performance Table -->
    <el-card class="table-card" shadow="never">
      <el-table
        v-loading="loading"
        :data="performanceEvaluations"
        stripe
        style="width: 100%"
        @sort-change="handleSort"
      >
        <el-table-column type="selection" width="55" />
        
        <el-table-column label="Employee" min-width="200" sortable="custom" prop="employee_name">
          <template #default="{ row }">
            <div class="employee-info">
              <el-avatar
                :size="32"
                :src="row.employee_avatar"
                :alt="row.employee_name"
                class="employee-avatar"
              >
                {{ row.employee_name?.charAt(0).toUpperCase() }}
              </el-avatar>
              <div class="employee-details">
                <div class="employee-name">{{ row.employee_name }}</div>
                <div class="employee-position">{{ row.position }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Department" prop="department" width="120" sortable="custom" />
        
        <el-table-column label="Evaluation Period" prop="evaluation_period" width="140" sortable="custom">
          <template #default="{ row }">
            {{ formatPeriod(row.evaluation_period) }}
          </template>
        </el-table-column>
        
        <el-table-column label="Evaluator" prop="evaluator_name" width="150" sortable="custom">
          <template #default="{ row }">
            {{ row.evaluator_name || 'Not assigned' }}
          </template>
        </el-table-column>
        
        <el-table-column label="Overall Score" prop="overall_score" width="120" sortable="custom">
          <template #default="{ row }">
            <div v-if="row.overall_score" class="score-display">
              <el-progress
                :percentage="row.overall_score"
                :color="getScoreColor(row.overall_score)"
                :stroke-width="8"
                text-inside
              />
            </div>
            <span v-else class="no-score">-</span>
          </template>
        </el-table-column>
        
        <el-table-column label="Goals Met" prop="goals_met" width="100" sortable="custom">
          <template #default="{ row }">
            <div v-if="row.total_goals" class="goals-progress">
              {{ row.goals_met || 0 }}/{{ row.total_goals }}
            </div>
            <span v-else>-</span>
          </template>
        </el-table-column>
        
        <el-table-column label="Due Date" prop="due_date" width="120" sortable="custom">
          <template #default="{ row }">
            <div :class="{ 'overdue-date': isOverdue(row.due_date), 'due-soon': isDueSoon(row.due_date) }">
              {{ formatDate(row.due_date) }}
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
        
        <el-table-column label="Last Updated" prop="updated_at" width="140" sortable="custom">
          <template #default="{ row }">
            {{ formatDate(row.updated_at) }}
          </template>
        </el-table-column>
        
        <el-table-column label="Actions" width="180" fixed="right">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-tooltip content="View Details" placement="top">
                <el-button
                  type="primary"
                  :icon="View"
                  size="small"
                  circle
                  @click="viewEvaluationDetails(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Edit" placement="top">
                <el-button
                  type="warning"
                  :icon="Edit"
                  size="small"
                  circle
                  :disabled="row.status === 'completed'"
                  @click="editEvaluation(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Complete" placement="top">
                <el-button
                  type="success"
                  :icon="Check"
                  size="small"
                  circle
                  :disabled="row.status === 'completed'"
                  @click="completeEvaluation(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Delete" placement="top">
                <el-button
                  type="danger"
                  :icon="Delete"
                  size="small"
                  circle
                  :disabled="row.status === 'completed'"
                  @click="deleteEvaluation(row)"
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
    
    <!-- New Evaluation Dialog -->
    <el-dialog
      v-model="showEvaluationDialog"
      title="New Performance Evaluation"
      width="600px"
    >
      <el-form
        ref="evaluationFormRef"
        :model="evaluationForm"
        :rules="evaluationRules"
        label-width="140px"
      >
        <el-form-item label="Employee" prop="employee_id">
          <el-select
            v-model="evaluationForm.employee_id"
            placeholder="Select employee"
            style="width: 100%"
            filterable
          >
            <el-option
              v-for="emp in employees"
              :key="emp.id"
              :label="emp.name"
              :value="emp.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Evaluator" prop="evaluator_id">
          <el-select
            v-model="evaluationForm.evaluator_id"
            placeholder="Select evaluator"
            style="width: 100%"
            filterable
          >
            <el-option
              v-for="emp in employees"
              :key="emp.id"
              :label="emp.name"
              :value="emp.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Evaluation Period" prop="evaluation_period">
          <el-select
            v-model="evaluationForm.evaluation_period"
            placeholder="Select period"
            style="width: 100%"
          >
            <el-option label="Q1 2024" value="2024-Q1" />
            <el-option label="Q2 2024" value="2024-Q2" />
            <el-option label="Q3 2024" value="2024-Q3" />
            <el-option label="Q4 2024" value="2024-Q4" />
            <el-option label="Annual 2024" value="2024-Annual" />
          </el-select>
        </el-form-item>
        <el-form-item label="Due Date" prop="due_date">
          <el-date-picker
            v-model="evaluationForm.due_date"
            type="date"
            placeholder="Select due date"
            style="width: 100%"
            :disabled-date="disabledDate"
          />
        </el-form-item>
        <el-form-item label="Template" prop="template_id">
          <el-select
            v-model="evaluationForm.template_id"
            placeholder="Select evaluation template"
            style="width: 100%"
          >
            <el-option
              v-for="template in evaluationTemplates"
              :key="template.id"
              :label="template.name"
              :value="template.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Instructions" prop="instructions">
          <el-input
            v-model="evaluationForm.instructions"
            type="textarea"
            :rows="3"
            placeholder="Enter evaluation instructions"
          />
        </el-form-item>
        <el-form-item label="Self Evaluation" prop="self_evaluation">
          <el-switch v-model="evaluationForm.self_evaluation" />
          <div class="form-help-text">
            Allow employee to complete self-evaluation first
          </div>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showEvaluationDialog = false">Cancel</el-button>
          <el-button type="primary" @click="createEvaluation" :loading="submitting">
            Create Evaluation
          </el-button>
        </span>
      </template>
    </el-dialog>
    
    <!-- Evaluation Details Dialog -->
    <el-dialog
      v-model="showDetailsDialog"
      title="Evaluation Details"
      width="800px"
    >
      <div v-if="selectedEvaluation" class="evaluation-details">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-descriptions title="Employee Information" :column="1" border>
              <el-descriptions-item label="Name">
                {{ selectedEvaluation.employee_name }}
              </el-descriptions-item>
              <el-descriptions-item label="Position">
                {{ selectedEvaluation.position }}
              </el-descriptions-item>
              <el-descriptions-item label="Department">
                {{ selectedEvaluation.department }}
              </el-descriptions-item>
              <el-descriptions-item label="Manager">
                {{ selectedEvaluation.manager_name || 'Not assigned' }}
              </el-descriptions-item>
            </el-descriptions>
          </el-col>
          <el-col :span="12">
            <el-descriptions title="Evaluation Information" :column="1" border>
              <el-descriptions-item label="Period">
                {{ formatPeriod(selectedEvaluation.evaluation_period) }}
              </el-descriptions-item>
              <el-descriptions-item label="Evaluator">
                {{ selectedEvaluation.evaluator_name }}
              </el-descriptions-item>
              <el-descriptions-item label="Status">
                <el-tag :type="getStatusType(selectedEvaluation.status)">
                  {{ getStatusText(selectedEvaluation.status) }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Due Date">
                {{ formatDate(selectedEvaluation.due_date) }}
              </el-descriptions-item>
            </el-descriptions>
          </el-col>
        </el-row>
        
        <div v-if="selectedEvaluation.overall_score" class="performance-summary">
          <h3>Performance Summary</h3>
          <el-row :gutter="20">
            <el-col :span="8">
              <div class="score-card">
                <div class="score-label">Overall Score</div>
                <div class="score-value">{{ selectedEvaluation.overall_score }}%</div>
                <el-progress
                  :percentage="selectedEvaluation.overall_score"
                  :color="getScoreColor(selectedEvaluation.overall_score)"
                  :stroke-width="8"
                />
              </div>
            </el-col>
            <el-col :span="8">
              <div class="score-card">
                <div class="score-label">Goals Achievement</div>
                <div class="score-value">
                  {{ selectedEvaluation.goals_met || 0 }}/{{ selectedEvaluation.total_goals || 0 }}
                </div>
                <el-progress
                  :percentage="calculateGoalsPercentage(selectedEvaluation.goals_met, selectedEvaluation.total_goals)"
                  color="#67C23A"
                  :stroke-width="8"
                />
              </div>
            </el-col>
            <el-col :span="8">
              <div class="score-card">
                <div class="score-label">Competency Score</div>
                <div class="score-value">{{ selectedEvaluation.competency_score || 0 }}%</div>
                <el-progress
                  :percentage="selectedEvaluation.competency_score || 0"
                  color="#409EFF"
                  :stroke-width="8"
                />
              </div>
            </el-col>
          </el-row>
        </div>
        
        <div v-if="selectedEvaluation.feedback" class="feedback-section">
          <h3>Feedback</h3>
          <div class="feedback-content">
            <h4>Strengths</h4>
            <p>{{ selectedEvaluation.feedback.strengths || 'No strengths noted' }}</p>
            
            <h4>Areas for Improvement</h4>
            <p>{{ selectedEvaluation.feedback.improvements || 'No improvements noted' }}</p>
            
            <h4>Development Goals</h4>
            <p>{{ selectedEvaluation.feedback.development_goals || 'No development goals set' }}</p>
            
            <h4>Manager Comments</h4>
            <p>{{ selectedEvaluation.feedback.manager_comments || 'No manager comments' }}</p>
          </div>
        </div>
      </div>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showDetailsDialog = false">Close</el-button>
          <el-button
            v-if="selectedEvaluation?.status !== 'completed'"
            type="primary"
            @click="editEvaluation(selectedEvaluation)"
          >
            Edit Evaluation
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Plus,
  Clock,
  Check,
  TrendCharts,
  Warning,
  Search,
  Refresh,
  Download,
  View,
  Edit,
  Delete
} from '@element-plus/icons-vue'

export default {
  name: 'PerformanceList',
  components: {
    Plus,
    Clock,
    Check,
    TrendCharts,
    Warning,
    Search,
    Refresh,
    Download,
    View,
    Edit,
    Delete
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const evaluationFormRef = ref()
    const showEvaluationDialog = ref(false)
    const showDetailsDialog = ref(false)
    const submitting = ref(false)
    const selectedEvaluation = ref(null)
    
    const filters = reactive({
      search: '',
      department: '',
      status: '',
      evaluation_period: ''
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
    
    const evaluationForm = reactive({
      employee_id: '',
      evaluator_id: '',
      evaluation_period: '',
      due_date: '',
      template_id: '',
      instructions: '',
      self_evaluation: false
    })
    
    const evaluationRules = {
      employee_id: [
        { required: true, message: 'Please select an employee', trigger: 'change' }
      ],
      evaluator_id: [
        { required: true, message: 'Please select an evaluator', trigger: 'change' }
      ],
      evaluation_period: [
        { required: true, message: 'Please select evaluation period', trigger: 'change' }
      ],
      due_date: [
        { required: true, message: 'Please select due date', trigger: 'change' }
      ],
      template_id: [
        { required: true, message: 'Please select evaluation template', trigger: 'change' }
      ]
    }
    
    const performanceEvaluations = computed(() => store.getters['performance/performanceEvaluations'])
    const performanceStats = computed(() => store.getters['performance/performanceStats'])
    const departments = computed(() => store.getters['employees/departments'])
    const employees = computed(() => store.getters['employees/employees'])
    const evaluationTemplates = computed(() => store.getters['performance/evaluationTemplates'])
    const loading = computed(() => store.getters['performance/loading'])
    
    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const formatPeriod = (period) => {
      if (!period) return '-'
      const [year, quarter] = period.split('-')
      return quarter === 'Annual' ? `Annual ${year}` : `${quarter} ${year}`
    }
    
    const isOverdue = (dueDate) => {
      if (!dueDate) return false
      return new Date(dueDate) < new Date()
    }
    
    const isDueSoon = (dueDate) => {
      if (!dueDate) return false
      const due = new Date(dueDate)
      const today = new Date()
      const diffTime = due - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      return diffDays <= 7 && diffDays > 0
    }
    
    const getScoreColor = (score) => {
      if (score >= 90) return '#67C23A'
      if (score >= 80) return '#E6A23C'
      if (score >= 70) return '#F56C6C'
      return '#909399'
    }
    
    const getStatusType = (status) => {
      const statusMap = {
        pending: 'warning',
        in_progress: 'primary',
        completed: 'success',
        overdue: 'danger'
      }
      return statusMap[status] || 'info'
    }
    
    const getStatusText = (status) => {
      const statusMap = {
        pending: 'Pending',
        in_progress: 'In Progress',
        completed: 'Completed',
        overdue: 'Overdue'
      }
      return statusMap[status] || status
    }
    
    const calculateGoalsPercentage = (met, total) => {
      if (!total || total === 0) return 0
      return Math.round((met / total) * 100)
    }
    
    const disabledDate = (time) => {
      return time.getTime() < Date.now() - 8.64e7 // Disable past dates
    }
    
    const fetchPerformanceEvaluations = async () => {
      const params = {
        page: pagination.currentPage,
        limit: pagination.pageSize,
        search: filters.search,
        department: filters.department,
        status: filters.status,
        evaluation_period: filters.evaluation_period,
        sort_by: sortConfig.value.prop,
        sort_order: sortConfig.value.order
      }
      
      const result = await store.dispatch('performance/fetchPerformanceEvaluations', params)
      if (result && result.pagination) {
        pagination.total = result.pagination.total
      }
    }
    
    const handleSearch = () => {
      pagination.currentPage = 1
      fetchPerformanceEvaluations()
    }
    
    const handleFilter = () => {
      pagination.currentPage = 1
      fetchPerformanceEvaluations()
    }
    
    const handleSort = ({ prop, order }) => {
      sortConfig.value.prop = prop
      sortConfig.value.order = order === 'ascending' ? 'asc' : 'desc'
      fetchPerformanceEvaluations()
    }
    
    const handleSizeChange = (size) => {
      pagination.pageSize = size
      pagination.currentPage = 1
      fetchPerformanceEvaluations()
    }
    
    const handleCurrentChange = (page) => {
      pagination.currentPage = page
      fetchPerformanceEvaluations()
    }
    
    const resetFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      pagination.currentPage = 1
      sortConfig.value = { prop: '', order: '' }
      fetchPerformanceEvaluations()
    }
    
    const exportEvaluations = async () => {
      try {
        const params = {
          search: filters.search,
          department: filters.department,
          status: filters.status,
          evaluation_period: filters.evaluation_period,
          format: 'csv'
        }
        
        await store.dispatch('performance/exportEvaluations', params)
        ElMessage.success('Evaluations exported successfully')
      } catch (error) {
        console.error('Export error:', error)
        ElMessage.error('Failed to export evaluations')
      }
    }
    
    const resetEvaluationForm = () => {
      Object.keys(evaluationForm).forEach(key => {
        if (key === 'self_evaluation') {
          evaluationForm[key] = false
        } else {
          evaluationForm[key] = ''
        }
      })
    }
    
    const createEvaluation = async () => {
      try {
        const valid = await evaluationFormRef.value.validate()
        if (!valid) return
        
        submitting.value = true
        
        const result = await store.dispatch('performance/createEvaluation', evaluationForm)
        
        if (result.success) {
          ElMessage.success('Evaluation created successfully')
          showEvaluationDialog.value = false
          resetEvaluationForm()
          fetchPerformanceEvaluations()
          store.dispatch('performance/fetchPerformanceStats')
        } else {
          ElMessage.error(result.message || 'Failed to create evaluation')
        }
      } catch (error) {
        console.error('Create evaluation error:', error)
        ElMessage.error('An error occurred while creating evaluation')
      } finally {
        submitting.value = false
      }
    }
    
    const viewEvaluationDetails = async (evaluation) => {
      try {
        const result = await store.dispatch('performance/fetchEvaluationDetails', evaluation.id)
        if (result.success) {
          selectedEvaluation.value = result.data
          showDetailsDialog.value = true
        } else {
          ElMessage.error('Failed to load evaluation details')
        }
      } catch (error) {
        console.error('View evaluation details error:', error)
        ElMessage.error('An error occurred while loading evaluation details')
      }
    }
    
    const editEvaluation = (evaluation) => {
      router.push(`/performance/evaluations/${evaluation.id}/edit`)
    }
    
    const completeEvaluation = async (evaluation) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to mark this evaluation as completed?`,
          'Confirm Completion',
          {
            confirmButtonText: 'Complete',
            cancelButtonText: 'Cancel',
            type: 'info'
          }
        )
        
        const result = await store.dispatch('performance/completeEvaluation', evaluation.id)
        if (result.success) {
          ElMessage.success('Evaluation completed successfully')
          fetchPerformanceEvaluations()
          store.dispatch('performance/fetchPerformanceStats')
        } else {
          ElMessage.error(result.message || 'Failed to complete evaluation')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Complete evaluation error:', error)
          ElMessage.error('An error occurred while completing evaluation')
        }
      }
    }
    
    const deleteEvaluation = async (evaluation) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to delete the evaluation for ${evaluation.employee_name}?`,
          'Confirm Deletion',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        
        const result = await store.dispatch('performance/deleteEvaluation', evaluation.id)
        if (result.success) {
          ElMessage.success('Evaluation deleted successfully')
          fetchPerformanceEvaluations()
          store.dispatch('performance/fetchPerformanceStats')
        } else {
          ElMessage.error(result.message || 'Failed to delete evaluation')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Delete evaluation error:', error)
          ElMessage.error('An error occurred while deleting evaluation')
        }
      }
    }
    
    onMounted(async () => {
      await Promise.all([
        store.dispatch('employees/fetchDepartments'),
        store.dispatch('employees/fetchEmployees'),
        store.dispatch('performance/fetchEvaluationTemplates'),
        store.dispatch('performance/fetchPerformanceStats'),
        fetchPerformanceEvaluations()
      ])
    })
    
    return {
      filters,
      pagination,
      performanceEvaluations,
      performanceStats,
      departments,
      employees,
      evaluationTemplates,
      loading,
      showEvaluationDialog,
      showDetailsDialog,
      evaluationFormRef,
      evaluationForm,
      evaluationRules,
      selectedEvaluation,
      submitting,
      formatDate,
      formatPeriod,
      isOverdue,
      isDueSoon,
      getScoreColor,
      getStatusType,
      getStatusText,
      calculateGoalsPercentage,
      disabledDate,
      handleSearch,
      handleFilter,
      handleSort,
      handleSizeChange,
      handleCurrentChange,
      resetFilters,
      exportEvaluations,
      createEvaluation,
      viewEvaluationDetails,
      editEvaluation,
      completeEvaluation,
      deleteEvaluation
    }
  }
}
</script>

<style scoped>
.performance-list {
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

.summary-icon.pending {
  background-color: #E6A23C;
}

.summary-icon.completed {
  background-color: #67C23A;
}

.summary-icon.average {
  background-color: #409EFF;
}

.summary-icon.overdue {
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

.employee-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.employee-avatar {
  flex-shrink: 0;
}

.employee-details {
  min-width: 0;
}

.employee-name {
  font-weight: 500;
  color: #303133;
  margin-bottom: 2px;
}

.employee-position {
  font-size: 12px;
  color: #909399;
}

.score-display {
  width: 80px;
}

.no-score {
  color: #909399;
  font-style: italic;
}

.goals-progress {
  font-size: 13px;
  color: #606266;
}

.overdue-date {
  color: #F56C6C;
  font-weight: 500;
}

.due-soon {
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

.evaluation-details {
  margin-bottom: 20px;
}

.performance-summary {
  margin-top: 24px;
}

.performance-summary h3 {
  margin: 0 0 16px 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.score-card {
  text-align: center;
  padding: 16px;
  background-color: #F5F7FA;
  border-radius: 8px;
}

.score-label {
  font-size: 12px;
  color: #909399;
  margin-bottom: 8px;
}

.score-value {
  font-size: 24px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 12px;
}

.feedback-section {
  margin-top: 24px;
}

.feedback-section h3 {
  margin: 0 0 16px 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.feedback-content h4 {
  margin: 16px 0 8px 0;
  color: #606266;
  font-size: 14px;
  font-weight: 600;
}

.feedback-content p {
  margin: 0 0 16px 0;
  color: #606266;
  line-height: 1.6;
}

.form-help-text {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
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

:deep(.el-progress-bar__inner) {
  border-radius: 4px;
}

@media (max-width: 768px) {
  .performance-list {
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
  
  .employee-info {
    flex-direction: column;
    gap: 8px;
    text-align: center;
  }
  
  .score-card {
    padding: 12px;
  }
  
  .score-value {
    font-size: 20px;
  }
}
</style>