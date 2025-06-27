<template>
  <div class="training-list">
    <div class="page-header">
      <div class="header-content">
        <h1>Training Management</h1>
        <p>Manage employee training programs and track progress</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" :icon="Plus" @click="showTrainingDialog = true">
          New Training Program
        </el-button>
      </div>
    </div>
    
    <!-- Training Summary -->
    <el-row :gutter="20" class="summary-cards">
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon active">
              <el-icon><Reading /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ trainingStats.active_programs }}</h3>
              <p>Active Programs</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon enrolled">
              <el-icon><User /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ trainingStats.total_enrollments }}</h3>
              <p>Total Enrollments</p>
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
              <h3>{{ trainingStats.completed_trainings }}</h3>
              <p>Completed</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon completion">
              <el-icon><TrendCharts /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ trainingStats.completion_rate }}%</h3>
              <p>Completion Rate</p>
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
            placeholder="Search training programs..."
            :prefix-icon="Search"
            clearable
            @input="handleSearch"
          />
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.category"
            placeholder="Category"
            clearable
            @change="handleFilter"
          >
            <el-option label="Technical Skills" value="technical" />
            <el-option label="Soft Skills" value="soft_skills" />
            <el-option label="Leadership" value="leadership" />
            <el-option label="Compliance" value="compliance" />
            <el-option label="Safety" value="safety" />
            <el-option label="Professional Development" value="professional" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.status"
            placeholder="Status"
            clearable
            @change="handleFilter"
          >
            <el-option label="Draft" value="draft" />
            <el-option label="Active" value="active" />
            <el-option label="Completed" value="completed" />
            <el-option label="Cancelled" value="cancelled" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.delivery_method"
            placeholder="Delivery Method"
            clearable
            @change="handleFilter"
          >
            <el-option label="Online" value="online" />
            <el-option label="In-Person" value="in_person" />
            <el-option label="Hybrid" value="hybrid" />
            <el-option label="Self-Paced" value="self_paced" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="6">
          <div class="filter-actions">
            <el-button :icon="Refresh" @click="resetFilters">Reset</el-button>
            <el-button type="success" :icon="Download" @click="exportTrainings">
              Export
            </el-button>
          </div>
        </el-col>
      </el-row>
    </el-card>
    
    <!-- Training Table -->
    <el-card class="table-card" shadow="never">
      <el-table
        v-loading="loading"
        :data="trainingPrograms"
        stripe
        style="width: 100%"
        @sort-change="handleSort"
      >
        <el-table-column type="selection" width="55" />
        
        <el-table-column label="Program" min-width="250" sortable="custom" prop="title">
          <template #default="{ row }">
            <div class="program-info">
              <div class="program-title">{{ row.title }}</div>
              <div class="program-description">{{ row.description }}</div>
              <div class="program-meta">
                <el-tag size="small" :type="getCategoryType(row.category)">
                  {{ getCategoryText(row.category) }}
                </el-tag>
                <span class="duration">{{ row.duration }} hours</span>
              </div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Instructor" prop="instructor_name" width="150" sortable="custom">
          <template #default="{ row }">
            <div v-if="row.instructor_name" class="instructor-info">
              <div class="instructor-name">{{ row.instructor_name }}</div>
              <div class="instructor-type">{{ row.instructor_type }}</div>
            </div>
            <span v-else class="no-instructor">Not assigned</span>
          </template>
        </el-table-column>
        
        <el-table-column label="Delivery" prop="delivery_method" width="120" sortable="custom">
          <template #default="{ row }">
            <el-tag size="small" :type="getDeliveryType(row.delivery_method)">
              {{ getDeliveryText(row.delivery_method) }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column label="Schedule" prop="start_date" width="180" sortable="custom">
          <template #default="{ row }">
            <div class="schedule-info">
              <div class="dates">
                {{ formatDate(row.start_date) }} - {{ formatDate(row.end_date) }}
              </div>
              <div v-if="row.schedule_time" class="time">
                {{ row.schedule_time }}
              </div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Enrollments" prop="enrollments_count" width="120" sortable="custom">
          <template #default="{ row }">
            <div class="enrollment-info">
              <div class="count">{{ row.enrollments_count || 0 }}/{{ row.max_participants || '∞' }}</div>
              <el-progress
                v-if="row.max_participants"
                :percentage="calculateEnrollmentPercentage(row.enrollments_count, row.max_participants)"
                :stroke-width="4"
                :show-text="false"
                :color="getEnrollmentColor(row.enrollments_count, row.max_participants)"
              />
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Completion" prop="completion_rate" width="120" sortable="custom">
          <template #default="{ row }">
            <div v-if="row.completion_rate !== null" class="completion-info">
              <div class="rate">{{ row.completion_rate }}%</div>
              <el-progress
                :percentage="row.completion_rate"
                :stroke-width="4"
                :show-text="false"
                :color="getCompletionColor(row.completion_rate)"
              />
            </div>
            <span v-else class="no-data">-</span>
          </template>
        </el-table-column>
        
        <el-table-column label="Cost" prop="cost_per_participant" width="100" sortable="custom">
          <template #default="{ row }">
            <div v-if="row.cost_per_participant" class="cost-info">
              ${{ formatCurrency(row.cost_per_participant) }}
            </div>
            <span v-else class="free">Free</span>
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
        
        <el-table-column label="Created" prop="created_at" width="120" sortable="custom">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
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
                  @click="viewTrainingDetails(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Manage Enrollments" placement="top">
                <el-button
                  type="info"
                  :icon="User"
                  size="small"
                  circle
                  @click="manageEnrollments(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Edit" placement="top">
                <el-button
                  type="warning"
                  :icon="Edit"
                  size="small"
                  circle
                  :disabled="row.status === 'completed'"
                  @click="editTraining(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Clone" placement="top">
                <el-button
                  type="success"
                  :icon="CopyDocument"
                  size="small"
                  circle
                  @click="cloneTraining(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Delete" placement="top">
                <el-button
                  type="danger"
                  :icon="Delete"
                  size="small"
                  circle
                  :disabled="row.status === 'active' && row.enrollments_count > 0"
                  @click="deleteTraining(row)"
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
    
    <!-- New Training Dialog -->
    <el-dialog
      v-model="showTrainingDialog"
      title="New Training Program"
      width="800px"
    >
      <el-form
        ref="trainingFormRef"
        :model="trainingForm"
        :rules="trainingRules"
        label-width="140px"
      >
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Program Title" prop="title">
              <el-input
                v-model="trainingForm.title"
                placeholder="Enter program title"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Category" prop="category">
              <el-select
                v-model="trainingForm.category"
                placeholder="Select category"
                style="width: 100%"
              >
                <el-option label="Technical Skills" value="technical" />
                <el-option label="Soft Skills" value="soft_skills" />
                <el-option label="Leadership" value="leadership" />
                <el-option label="Compliance" value="compliance" />
                <el-option label="Safety" value="safety" />
                <el-option label="Professional Development" value="professional" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="Description" prop="description">
          <el-input
            v-model="trainingForm.description"
            type="textarea"
            :rows="3"
            placeholder="Enter program description"
          />
        </el-form-item>
        
        <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="Duration (hours)" prop="duration">
              <el-input-number
                v-model="trainingForm.duration"
                :min="1"
                :max="1000"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="Max Participants" prop="max_participants">
              <el-input-number
                v-model="trainingForm.max_participants"
                :min="1"
                :max="500"
                style="width: 100%"
                placeholder="Leave empty for unlimited"
              />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="Cost per Person" prop="cost_per_participant">
              <el-input-number
                v-model="trainingForm.cost_per_participant"
                :min="0"
                :precision="2"
                style="width: 100%"
                placeholder="0 for free"
              />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Delivery Method" prop="delivery_method">
              <el-select
                v-model="trainingForm.delivery_method"
                placeholder="Select delivery method"
                style="width: 100%"
              >
                <el-option label="Online" value="online" />
                <el-option label="In-Person" value="in_person" />
                <el-option label="Hybrid" value="hybrid" />
                <el-option label="Self-Paced" value="self_paced" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Instructor" prop="instructor_id">
              <el-select
                v-model="trainingForm.instructor_id"
                placeholder="Select instructor"
                style="width: 100%"
                filterable
                clearable
              >
                <el-option
                  v-for="instructor in instructors"
                  :key="instructor.id"
                  :label="instructor.name"
                  :value="instructor.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Start Date" prop="start_date">
              <el-date-picker
                v-model="trainingForm.start_date"
                type="date"
                placeholder="Select start date"
                style="width: 100%"
                :disabled-date="disabledDate"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="End Date" prop="end_date">
              <el-date-picker
                v-model="trainingForm.end_date"
                type="date"
                placeholder="Select end date"
                style="width: 100%"
                :disabled-date="disabledEndDate"
              />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="Schedule Time" prop="schedule_time">
          <el-input
            v-model="trainingForm.schedule_time"
            placeholder="e.g., Monday-Friday 9:00 AM - 5:00 PM"
          />
        </el-form-item>
        
        <el-form-item label="Location" prop="location">
          <el-input
            v-model="trainingForm.location"
            placeholder="Enter training location or online platform"
          />
        </el-form-item>
        
        <el-form-item label="Prerequisites" prop="prerequisites">
          <el-input
            v-model="trainingForm.prerequisites"
            type="textarea"
            :rows="2"
            placeholder="Enter any prerequisites for this training"
          />
        </el-form-item>
        
        <el-form-item label="Learning Objectives" prop="learning_objectives">
          <el-input
            v-model="trainingForm.learning_objectives"
            type="textarea"
            :rows="3"
            placeholder="Enter learning objectives"
          />
        </el-form-item>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Certification" prop="provides_certification">
              <el-switch v-model="trainingForm.provides_certification" />
              <div class="form-help-text">
                Provides certification upon completion
              </div>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Mandatory" prop="is_mandatory">
              <el-switch v-model="trainingForm.is_mandatory" />
              <div class="form-help-text">
                Required training for all employees
              </div>
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showTrainingDialog = false">Cancel</el-button>
          <el-button type="primary" @click="createTraining" :loading="submitting">
            Create Program
          </el-button>
        </span>
      </template>
    </el-dialog>
    
    <!-- Training Details Dialog -->
    <el-dialog
      v-model="showDetailsDialog"
      title="Training Program Details"
      width="900px"
    >
      <div v-if="selectedTraining" class="training-details">
        <el-row :gutter="20">
          <el-col :span="16">
            <el-descriptions title="Program Information" :column="2" border>
              <el-descriptions-item label="Title" :span="2">
                {{ selectedTraining.title }}
              </el-descriptions-item>
              <el-descriptions-item label="Category">
                <el-tag :type="getCategoryType(selectedTraining.category)">
                  {{ getCategoryText(selectedTraining.category) }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Duration">
                {{ selectedTraining.duration }} hours
              </el-descriptions-item>
              <el-descriptions-item label="Delivery Method">
                <el-tag :type="getDeliveryType(selectedTraining.delivery_method)">
                  {{ getDeliveryText(selectedTraining.delivery_method) }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Status">
                <el-tag :type="getStatusType(selectedTraining.status)">
                  {{ getStatusText(selectedTraining.status) }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Instructor">
                {{ selectedTraining.instructor_name || 'Not assigned' }}
              </el-descriptions-item>
              <el-descriptions-item label="Cost">
                {{ selectedTraining.cost_per_participant ? `$${formatCurrency(selectedTraining.cost_per_participant)}` : 'Free' }}
              </el-descriptions-item>
              <el-descriptions-item label="Schedule" :span="2">
                {{ formatDate(selectedTraining.start_date) }} - {{ formatDate(selectedTraining.end_date) }}
                <div v-if="selectedTraining.schedule_time">{{ selectedTraining.schedule_time }}</div>
              </el-descriptions-item>
              <el-descriptions-item label="Location" :span="2">
                {{ selectedTraining.location || 'Not specified' }}
              </el-descriptions-item>
            </el-descriptions>
          </el-col>
          <el-col :span="8">
            <div class="training-stats">
              <h3>Training Statistics</h3>
              <div class="stat-item">
                <div class="stat-label">Enrollments</div>
                <div class="stat-value">{{ selectedTraining.enrollments_count || 0 }}</div>
              </div>
              <div class="stat-item">
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ selectedTraining.completed_count || 0 }}</div>
              </div>
              <div class="stat-item">
                <div class="stat-label">Completion Rate</div>
                <div class="stat-value">{{ selectedTraining.completion_rate || 0 }}%</div>
              </div>
              <div class="stat-item">
                <div class="stat-label">Average Rating</div>
                <div class="stat-value">
                  <el-rate
                    v-model="selectedTraining.average_rating"
                    disabled
                    show-score
                    text-color="#ff9900"
                    score-template="{value}"
                  />
                </div>
              </div>
            </div>
          </el-col>
        </el-row>
        
        <div v-if="selectedTraining.description" class="description-section">
          <h3>Description</h3>
          <p>{{ selectedTraining.description }}</p>
        </div>
        
        <div v-if="selectedTraining.learning_objectives" class="objectives-section">
          <h3>Learning Objectives</h3>
          <p>{{ selectedTraining.learning_objectives }}</p>
        </div>
        
        <div v-if="selectedTraining.prerequisites" class="prerequisites-section">
          <h3>Prerequisites</h3>
          <p>{{ selectedTraining.prerequisites }}</p>
        </div>
        
        <div class="features-section">
          <h3>Features</h3>
          <el-row :gutter="20">
            <el-col :span="12">
              <div class="feature-item">
                <el-icon><Medal /></el-icon>
                <span>{{ selectedTraining.provides_certification ? 'Provides Certification' : 'No Certification' }}</span>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="feature-item">
                <el-icon><Warning /></el-icon>
                <span>{{ selectedTraining.is_mandatory ? 'Mandatory Training' : 'Optional Training' }}</span>
              </div>
            </el-col>
          </el-row>
        </div>
      </div>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showDetailsDialog = false">Close</el-button>
          <el-button
            v-if="selectedTraining?.status !== 'completed'"
            type="warning"
            @click="editTraining(selectedTraining)"
          >
            Edit Program
          </el-button>
          <el-button
            type="primary"
            @click="manageEnrollments(selectedTraining)"
          >
            Manage Enrollments
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
  Reading,
  User,
  Check,
  TrendCharts,
  Search,
  Refresh,
  Download,
  View,
  Edit,
  CopyDocument,
  Delete,
  Medal,
  Warning
} from '@element-plus/icons-vue'

export default {
  name: 'TrainingList',
  components: {
    Plus,
    Reading,
    User,
    Check,
    TrendCharts,
    Search,
    Refresh,
    Download,
    View,
    Edit,
    CopyDocument,
    Delete,
    Medal,
    Warning
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const trainingFormRef = ref()
    const showTrainingDialog = ref(false)
    const showDetailsDialog = ref(false)
    const submitting = ref(false)
    const selectedTraining = ref(null)
    
    const filters = reactive({
      search: '',
      category: '',
      status: '',
      delivery_method: ''
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
    
    const trainingForm = reactive({
      title: '',
      description: '',
      category: '',
      duration: 1,
      max_participants: null,
      cost_per_participant: 0,
      delivery_method: '',
      instructor_id: '',
      start_date: '',
      end_date: '',
      schedule_time: '',
      location: '',
      prerequisites: '',
      learning_objectives: '',
      provides_certification: false,
      is_mandatory: false
    })
    
    const trainingRules = {
      title: [
        { required: true, message: 'Please enter program title', trigger: 'blur' }
      ],
      category: [
        { required: true, message: 'Please select category', trigger: 'change' }
      ],
      duration: [
        { required: true, message: 'Please enter duration', trigger: 'blur' }
      ],
      delivery_method: [
        { required: true, message: 'Please select delivery method', trigger: 'change' }
      ],
      start_date: [
        { required: true, message: 'Please select start date', trigger: 'change' }
      ],
      end_date: [
        { required: true, message: 'Please select end date', trigger: 'change' }
      ]
    }
    
    const trainingPrograms = computed(() => store.getters['training/trainingPrograms'])
    const trainingStats = computed(() => store.getters['training/trainingStats'])
    const instructors = computed(() => store.getters['training/instructors'])
    const loading = computed(() => store.getters['training/loading'])
    
    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount)
    }
    
    const calculateEnrollmentPercentage = (enrolled, max) => {
      if (!max || max === 0) return 0
      return Math.round((enrolled / max) * 100)
    }
    
    const getEnrollmentColor = (enrolled, max) => {
      const percentage = calculateEnrollmentPercentage(enrolled, max)
      if (percentage >= 90) return '#F56C6C'
      if (percentage >= 70) return '#E6A23C'
      return '#67C23A'
    }
    
    const getCompletionColor = (rate) => {
      if (rate >= 80) return '#67C23A'
      if (rate >= 60) return '#E6A23C'
      return '#F56C6C'
    }
    
    const getCategoryType = (category) => {
      const categoryMap = {
        technical: 'primary',
        soft_skills: 'success',
        leadership: 'warning',
        compliance: 'danger',
        safety: 'info',
        professional: ''
      }
      return categoryMap[category] || 'info'
    }
    
    const getCategoryText = (category) => {
      const categoryMap = {
        technical: 'Technical Skills',
        soft_skills: 'Soft Skills',
        leadership: 'Leadership',
        compliance: 'Compliance',
        safety: 'Safety',
        professional: 'Professional Development'
      }
      return categoryMap[category] || category
    }
    
    const getDeliveryType = (method) => {
      const methodMap = {
        online: 'primary',
        in_person: 'success',
        hybrid: 'warning',
        self_paced: 'info'
      }
      return methodMap[method] || 'info'
    }
    
    const getDeliveryText = (method) => {
      const methodMap = {
        online: 'Online',
        in_person: 'In-Person',
        hybrid: 'Hybrid',
        self_paced: 'Self-Paced'
      }
      return methodMap[method] || method
    }
    
    const getStatusType = (status) => {
      const statusMap = {
        draft: 'info',
        active: 'success',
        completed: 'primary',
        cancelled: 'danger'
      }
      return statusMap[status] || 'info'
    }
    
    const getStatusText = (status) => {
      const statusMap = {
        draft: 'Draft',
        active: 'Active',
        completed: 'Completed',
        cancelled: 'Cancelled'
      }
      return statusMap[status] || status
    }
    
    const disabledDate = (time) => {
      return time.getTime() < Date.now() - 8.64e7 // Disable past dates
    }
    
    const disabledEndDate = (time) => {
      if (!trainingForm.start_date) return time.getTime() < Date.now() - 8.64e7
      return time.getTime() < new Date(trainingForm.start_date).getTime()
    }
    
    const fetchTrainingPrograms = async () => {
      const params = {
        page: pagination.currentPage,
        limit: pagination.pageSize,
        search: filters.search,
        category: filters.category,
        status: filters.status,
        delivery_method: filters.delivery_method,
        sort_by: sortConfig.value.prop,
        sort_order: sortConfig.value.order
      }
      
      const result = await store.dispatch('training/fetchTrainingPrograms', params)
      if (result && result.pagination) {
        pagination.total = result.pagination.total
      }
    }
    
    const handleSearch = () => {
      pagination.currentPage = 1
      fetchTrainingPrograms()
    }
    
    const handleFilter = () => {
      pagination.currentPage = 1
      fetchTrainingPrograms()
    }
    
    const handleSort = ({ prop, order }) => {
      sortConfig.value.prop = prop
      sortConfig.value.order = order === 'ascending' ? 'asc' : 'desc'
      fetchTrainingPrograms()
    }
    
    const handleSizeChange = (size) => {
      pagination.pageSize = size
      pagination.currentPage = 1
      fetchTrainingPrograms()
    }
    
    const handleCurrentChange = (page) => {
      pagination.currentPage = page
      fetchTrainingPrograms()
    }
    
    const resetFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      pagination.currentPage = 1
      sortConfig.value = { prop: '', order: '' }
      fetchTrainingPrograms()
    }
    
    const exportTrainings = async () => {
      try {
        const params = {
          search: filters.search,
          category: filters.category,
          status: filters.status,
          delivery_method: filters.delivery_method,
          format: 'csv'
        }
        
        await store.dispatch('training/exportTrainings', params)
        ElMessage.success('Training programs exported successfully')
      } catch (error) {
        console.error('Export error:', error)
        ElMessage.error('Failed to export training programs')
      }
    }
    
    const resetTrainingForm = () => {
      Object.keys(trainingForm).forEach(key => {
        if (key === 'provides_certification' || key === 'is_mandatory') {
          trainingForm[key] = false
        } else if (key === 'duration') {
          trainingForm[key] = 1
        } else if (key === 'cost_per_participant') {
          trainingForm[key] = 0
        } else if (key === 'max_participants') {
          trainingForm[key] = null
        } else {
          trainingForm[key] = ''
        }
      })
    }
    
    const createTraining = async () => {
      try {
        const valid = await trainingFormRef.value.validate()
        if (!valid) return
        
        submitting.value = true
        
        const result = await store.dispatch('training/createTraining', trainingForm)
        
        if (result.success) {
          ElMessage.success('Training program created successfully')
          showTrainingDialog.value = false
          resetTrainingForm()
          fetchTrainingPrograms()
          store.dispatch('training/fetchTrainingStats')
        } else {
          ElMessage.error(result.message || 'Failed to create training program')
        }
      } catch (error) {
        console.error('Create training error:', error)
        ElMessage.error('An error occurred while creating training program')
      } finally {
        submitting.value = false
      }
    }
    
    const viewTrainingDetails = async (training) => {
      try {
        const result = await store.dispatch('training/fetchTrainingDetails', training.id)
        if (result.success) {
          selectedTraining.value = result.data
          showDetailsDialog.value = true
        } else {
          ElMessage.error('Failed to load training details')
        }
      } catch (error) {
        console.error('View training details error:', error)
        ElMessage.error('An error occurred while loading training details')
      }
    }
    
    const editTraining = (training) => {
      router.push(`/training/programs/${training.id}/edit`)
    }
    
    const manageEnrollments = (training) => {
      router.push(`/training/programs/${training.id}/enrollments`)
    }
    
    const cloneTraining = async (training) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to clone the training program "${training.title}"?`,
          'Confirm Clone',
          {
            confirmButtonText: 'Clone',
            cancelButtonText: 'Cancel',
            type: 'info'
          }
        )
        
        const result = await store.dispatch('training/cloneTraining', training.id)
        if (result.success) {
          ElMessage.success('Training program cloned successfully')
          fetchTrainingPrograms()
          store.dispatch('training/fetchTrainingStats')
        } else {
          ElMessage.error(result.message || 'Failed to clone training program')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Clone training error:', error)
          ElMessage.error('An error occurred while cloning training program')
        }
      }
    }
    
    const deleteTraining = async (training) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to delete the training program "${training.title}"?`,
          'Confirm Deletion',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        
        const result = await store.dispatch('training/deleteTraining', training.id)
        if (result.success) {
          ElMessage.success('Training program deleted successfully')
          fetchTrainingPrograms()
          store.dispatch('training/fetchTrainingStats')
        } else {
          ElMessage.error(result.message || 'Failed to delete training program')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Delete training error:', error)
          ElMessage.error('An error occurred while deleting training program')
        }
      }
    }
    
    onMounted(async () => {
      await Promise.all([
        store.dispatch('training/fetchInstructors'),
        store.dispatch('training/fetchTrainingStats'),
        fetchTrainingPrograms()
      ])
    })
    
    return {
      filters,
      pagination,
      trainingPrograms,
      trainingStats,
      instructors,
      loading,
      showTrainingDialog,
      showDetailsDialog,
      trainingFormRef,
      trainingForm,
      trainingRules,
      selectedTraining,
      submitting,
      formatDate,
      formatCurrency,
      calculateEnrollmentPercentage,
      getEnrollmentColor,
      getCompletionColor,
      getCategoryType,
      getCategoryText,
      getDeliveryType,
      getDeliveryText,
      getStatusType,
      getStatusText,
      disabledDate,
      disabledEndDate,
      handleSearch,
      handleFilter,
      handleSort,
      handleSizeChange,
      handleCurrentChange,
      resetFilters,
      exportTrainings,
      createTraining,
      viewTrainingDetails,
      editTraining,
      manageEnrollments,
      cloneTraining,
      deleteTraining
    }
  }
}
</script>

<style scoped>
.training-list {
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
  background-color: #409EFF;
}

.summary-icon.enrolled {
  background-color: #67C23A;
}

.summary-icon.completed {
  background-color: #E6A23C;
}

.summary-icon.completion {
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

.program-info {
  min-width: 0;
}

.program-title {
  font-weight: 500;
  color: #303133;
  margin-bottom: 4px;
  line-height: 1.4;
}

.program-description {
  font-size: 12px;
  color: #909399;
  margin-bottom: 8px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.program-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.duration {
  font-size: 12px;
  color: #606266;
  background-color: #F5F7FA;
  padding: 2px 6px;
  border-radius: 4px;
}

.instructor-info {
  min-width: 0;
}

.instructor-name {
  font-weight: 500;
  color: #303133;
  margin-bottom: 2px;
}

.instructor-type {
  font-size: 12px;
  color: #909399;
}

.no-instructor {
  color: #909399;
  font-style: italic;
}

.schedule-info {
  min-width: 0;
}

.dates {
  font-size: 13px;
  color: #606266;
  margin-bottom: 2px;
}

.time {
  font-size: 12px;
  color: #909399;
}

.enrollment-info {
  text-align: center;
}

.count {
  font-size: 13px;
  color: #606266;
  margin-bottom: 4px;
}

.completion-info {
  text-align: center;
}

.rate {
  font-size: 13px;
  color: #606266;
  margin-bottom: 4px;
}

.no-data {
  color: #909399;
  font-style: italic;
}

.cost-info {
  font-weight: 500;
  color: #67C23A;
}

.free {
  color: #909399;
  font-style: italic;
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

.training-details {
  margin-bottom: 20px;
}

.training-stats {
  background-color: #F5F7FA;
  padding: 16px;
  border-radius: 8px;
}

.training-stats h3 {
  margin: 0 0 16px 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.stat-label {
  font-size: 14px;
  color: #606266;
}

.stat-value {
  font-weight: 600;
  color: #303133;
}

.description-section,
.objectives-section,
.prerequisites-section,
.features-section {
  margin-top: 24px;
}

.description-section h3,
.objectives-section h3,
.prerequisites-section h3,
.features-section h3 {
  margin: 0 0 12px 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.description-section p,
.objectives-section p,
.prerequisites-section p {
  margin: 0;
  color: #606266;
  line-height: 1.6;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #606266;
  margin-bottom: 8px;
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

:deep(.el-rate) {
  display: flex;
  align-items: center;
  gap: 4px;
}

@media (max-width: 768px) {
  .training-list {
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
  
  .program-info {
    min-width: 150px;
  }
  
  .program-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  
  .training-stats {
    margin-top: 16px;
  }
  
  .stat-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
}
</style>