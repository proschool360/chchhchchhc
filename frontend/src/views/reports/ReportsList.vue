<template>
  <div class="reports-list">
    <div class="page-header">
      <div class="header-content">
        <h1>Reports & Analytics</h1>
        <p>Generate and view comprehensive HR reports and analytics</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" :icon="Plus" @click="showReportDialog = true">
          Generate Report
        </el-button>
      </div>
    </div>
    
    <!-- Quick Stats -->
    <el-row :gutter="20" class="summary-cards">
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon reports">
              <el-icon><Document /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ reportStats.total_reports }}</h3>
              <p>Total Reports</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon scheduled">
              <el-icon><Clock /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ reportStats.scheduled_reports }}</h3>
              <p>Scheduled</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon generated">
              <el-icon><Check /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ reportStats.generated_this_month }}</h3>
              <p>This Month</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon downloads">
              <el-icon><Download /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ reportStats.total_downloads }}</h3>
              <p>Downloads</p>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    
    <!-- Report Categories -->
    <el-row :gutter="20" class="report-categories">
      <el-col :xs="24" :sm="12" :md="8">
        <el-card class="category-card" shadow="hover" @click="filterByCategory('employee')">
          <div class="category-content">
            <div class="category-icon employee">
              <el-icon><User /></el-icon>
            </div>
            <div class="category-info">
              <h3>Employee Reports</h3>
              <p>Employee data, demographics, and organizational charts</p>
              <div class="category-count">{{ getCategoryCount('employee') }} reports</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="8">
        <el-card class="category-card" shadow="hover" @click="filterByCategory('attendance')">
          <div class="category-content">
            <div class="category-icon attendance">
              <el-icon><Calendar /></el-icon>
            </div>
            <div class="category-info">
              <h3>Attendance Reports</h3>
              <p>Time tracking, attendance patterns, and work hours</p>
              <div class="category-count">{{ getCategoryCount('attendance') }} reports</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="8">
        <el-card class="category-card" shadow="hover" @click="filterByCategory('payroll')">
          <div class="category-content">
            <div class="category-icon payroll">
              <el-icon><Money /></el-icon>
            </div>
            <div class="category-info">
              <h3>Payroll Reports</h3>
              <p>Salary summaries, tax reports, and compensation analysis</p>
              <div class="category-count">{{ getCategoryCount('payroll') }} reports</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="8">
        <el-card class="category-card" shadow="hover" @click="filterByCategory('leave')">
          <div class="category-content">
            <div class="category-icon leave">
              <el-icon><Suitcase /></el-icon>
            </div>
            <div class="category-info">
              <h3>Leave Reports</h3>
              <p>Leave balances, usage patterns, and approval statistics</p>
              <div class="category-count">{{ getCategoryCount('leave') }} reports</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="8">
        <el-card class="category-card" shadow="hover" @click="filterByCategory('performance')">
          <div class="category-content">
            <div class="category-icon performance">
              <el-icon><TrendCharts /></el-icon>
            </div>
            <div class="category-info">
              <h3>Performance Reports</h3>
              <p>Evaluation results, goal tracking, and performance trends</p>
              <div class="category-count">{{ getCategoryCount('performance') }} reports</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="8">
        <el-card class="category-card" shadow="hover" @click="filterByCategory('training')">
          <div class="category-content">
            <div class="category-icon training">
              <el-icon><Reading /></el-icon>
            </div>
            <div class="category-info">
              <h3>Training Reports</h3>
              <p>Training completion, skill development, and program effectiveness</p>
              <div class="category-count">{{ getCategoryCount('training') }} reports</div>
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
            placeholder="Search reports..."
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
            <el-option label="Employee" value="employee" />
            <el-option label="Attendance" value="attendance" />
            <el-option label="Payroll" value="payroll" />
            <el-option label="Leave" value="leave" />
            <el-option label="Performance" value="performance" />
            <el-option label="Training" value="training" />
            <el-option label="Recruitment" value="recruitment" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.status"
            placeholder="Status"
            clearable
            @change="handleFilter"
          >
            <el-option label="Generated" value="generated" />
            <el-option label="Scheduled" value="scheduled" />
            <el-option label="Processing" value="processing" />
            <el-option label="Failed" value="failed" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.format"
            placeholder="Format"
            clearable
            @change="handleFilter"
          >
            <el-option label="PDF" value="pdf" />
            <el-option label="Excel" value="excel" />
            <el-option label="CSV" value="csv" />
            <el-option label="Word" value="word" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="6">
          <div class="filter-actions">
            <el-button :icon="Refresh" @click="resetFilters">Reset</el-button>
            <el-button type="success" :icon="Download" @click="bulkDownload">
              Bulk Download
            </el-button>
          </div>
        </el-col>
      </el-row>
    </el-card>
    
    <!-- Reports Table -->
    <el-card class="table-card" shadow="never">
      <el-table
        v-loading="loading"
        :data="reports"
        stripe
        style="width: 100%"
        @sort-change="handleSort"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        
        <el-table-column label="Report" min-width="300" sortable="custom" prop="name">
          <template #default="{ row }">
            <div class="report-info">
              <div class="report-icon">
                <el-icon><component :is="getReportIcon(row.category)" /></el-icon>
              </div>
              <div class="report-details">
                <div class="report-name">{{ row.name }}</div>
                <div class="report-description">{{ row.description }}</div>
                <div class="report-meta">
                  <el-tag size="small" :type="getCategoryType(row.category)">
                    {{ getCategoryText(row.category) }}
                  </el-tag>
                  <span class="report-id">#{{ row.id }}</span>
                </div>
              </div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Generated By" prop="generated_by" width="150" sortable="custom">
          <template #default="{ row }">
            <div class="user-info">
              <div class="user-name">{{ row.generated_by_name }}</div>
              <div class="user-role">{{ row.generated_by_role }}</div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Date Range" prop="date_range" width="180" sortable="custom">
          <template #default="{ row }">
            <div class="date-range">
              <div class="dates">
                {{ formatDate(row.start_date) }} - {{ formatDate(row.end_date) }}
              </div>
              <div class="period">{{ row.period_type }}</div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Format" prop="format" width="100" sortable="custom">
          <template #default="{ row }">
            <el-tag size="small" :type="getFormatType(row.format)">
              {{ row.format.toUpperCase() }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column label="Size" prop="file_size" width="100" sortable="custom">
          <template #default="{ row }">
            <span v-if="row.file_size">{{ formatFileSize(row.file_size) }}</span>
            <span v-else class="no-size">-</span>
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
        
        <el-table-column label="Generated" prop="generated_at" width="140" sortable="custom">
          <template #default="{ row }">
            <div class="generated-info">
              <div class="date">{{ formatDate(row.generated_at) }}</div>
              <div class="time">{{ formatTime(row.generated_at) }}</div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Downloads" prop="download_count" width="100" sortable="custom">
          <template #default="{ row }">
            <div class="download-count">
              <el-icon><Download /></el-icon>
              <span>{{ row.download_count || 0 }}</span>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Actions" width="180" fixed="right">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-tooltip content="View Report" placement="top">
                <el-button
                  type="primary"
                  :icon="View"
                  size="small"
                  circle
                  :disabled="row.status !== 'generated'"
                  @click="viewReport(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Download" placement="top">
                <el-button
                  type="success"
                  :icon="Download"
                  size="small"
                  circle
                  :disabled="row.status !== 'generated'"
                  @click="downloadReport(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Share" placement="top">
                <el-button
                  type="info"
                  :icon="Share"
                  size="small"
                  circle
                  :disabled="row.status !== 'generated'"
                  @click="shareReport(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Regenerate" placement="top">
                <el-button
                  type="warning"
                  :icon="Refresh"
                  size="small"
                  circle
                  @click="regenerateReport(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Delete" placement="top">
                <el-button
                  type="danger"
                  :icon="Delete"
                  size="small"
                  circle
                  @click="deleteReport(row)"
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
    
    <!-- Generate Report Dialog -->
    <el-dialog
      v-model="showReportDialog"
      title="Generate New Report"
      width="700px"
    >
      <el-form
        ref="reportFormRef"
        :model="reportForm"
        :rules="reportRules"
        label-width="140px"
      >
        <el-form-item label="Report Type" prop="category">
          <el-select
            v-model="reportForm.category"
            placeholder="Select report category"
            style="width: 100%"
            @change="onCategoryChange"
          >
            <el-option label="Employee Reports" value="employee" />
            <el-option label="Attendance Reports" value="attendance" />
            <el-option label="Payroll Reports" value="payroll" />
            <el-option label="Leave Reports" value="leave" />
            <el-option label="Performance Reports" value="performance" />
            <el-option label="Training Reports" value="training" />
            <el-option label="Recruitment Reports" value="recruitment" />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Report Template" prop="template_id">
          <el-select
            v-model="reportForm.template_id"
            placeholder="Select report template"
            style="width: 100%"
            :disabled="!reportForm.category"
          >
            <el-option
              v-for="template in availableTemplates"
              :key="template.id"
              :label="template.name"
              :value="template.id"
            >
              <div class="template-option">
                <div class="template-name">{{ template.name }}</div>
                <div class="template-description">{{ template.description }}</div>
              </div>
            </el-option>
          </el-select>
        </el-form-item>
        
        <el-form-item label="Report Name" prop="name">
          <el-input
            v-model="reportForm.name"
            placeholder="Enter report name"
          />
        </el-form-item>
        
        <el-form-item label="Description" prop="description">
          <el-input
            v-model="reportForm.description"
            type="textarea"
            :rows="2"
            placeholder="Enter report description"
          />
        </el-form-item>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Start Date" prop="start_date">
              <el-date-picker
                v-model="reportForm.start_date"
                type="date"
                placeholder="Select start date"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="End Date" prop="end_date">
              <el-date-picker
                v-model="reportForm.end_date"
                type="date"
                placeholder="Select end date"
                style="width: 100%"
                :disabled-date="disabledEndDate"
              />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="Departments" prop="departments">
          <el-select
            v-model="reportForm.departments"
            placeholder="Select departments (optional)"
            style="width: 100%"
            multiple
            collapse-tags
            collapse-tags-tooltip
          >
            <el-option
              v-for="dept in departments"
              :key="dept.id"
              :label="dept.name"
              :value="dept.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Output Format" prop="format">
          <el-radio-group v-model="reportForm.format">
            <el-radio label="pdf">PDF</el-radio>
            <el-radio label="excel">Excel</el-radio>
            <el-radio label="csv">CSV</el-radio>
            <el-radio label="word">Word</el-radio>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item label="Schedule" prop="schedule_type">
          <el-radio-group v-model="reportForm.schedule_type">
            <el-radio label="now">Generate Now</el-radio>
            <el-radio label="scheduled">Schedule for Later</el-radio>
            <el-radio label="recurring">Recurring Report</el-radio>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item
          v-if="reportForm.schedule_type === 'scheduled'"
          label="Schedule Date"
          prop="scheduled_date"
        >
          <el-date-picker
            v-model="reportForm.scheduled_date"
            type="datetime"
            placeholder="Select schedule date and time"
            style="width: 100%"
            :disabled-date="disabledScheduleDate"
          />
        </el-form-item>
        
        <el-form-item
          v-if="reportForm.schedule_type === 'recurring'"
          label="Frequency"
          prop="frequency"
        >
          <el-select
            v-model="reportForm.frequency"
            placeholder="Select frequency"
            style="width: 100%"
          >
            <el-option label="Daily" value="daily" />
            <el-option label="Weekly" value="weekly" />
            <el-option label="Monthly" value="monthly" />
            <el-option label="Quarterly" value="quarterly" />
            <el-option label="Yearly" value="yearly" />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Email Recipients" prop="email_recipients">
          <el-input
            v-model="reportForm.email_recipients"
            placeholder="Enter email addresses separated by commas"
          />
          <div class="form-help-text">
            Optional: Report will be emailed to these addresses when ready
          </div>
        </el-form-item>
        
        <el-form-item label="Include Charts" prop="include_charts">
          <el-switch v-model="reportForm.include_charts" />
          <div class="form-help-text">
            Include visual charts and graphs in the report
          </div>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showReportDialog = false">Cancel</el-button>
          <el-button type="primary" @click="generateReport" :loading="generating">
            {{ reportForm.schedule_type === 'now' ? 'Generate Report' : 'Schedule Report' }}
          </el-button>
        </span>
      </template>
    </el-dialog>
    
    <!-- Share Report Dialog -->
    <el-dialog
      v-model="showShareDialog"
      title="Share Report"
      width="500px"
    >
      <div v-if="selectedReport" class="share-content">
        <div class="share-info">
          <h3>{{ selectedReport.name }}</h3>
          <p>{{ selectedReport.description }}</p>
        </div>
        
        <el-form label-width="100px">
          <el-form-item label="Share Link">
            <el-input
              v-model="shareLink"
              readonly
              class="share-link-input"
            >
              <template #append>
                <el-button @click="copyShareLink">
                  <el-icon><CopyDocument /></el-icon>
                </el-button>
              </template>
            </el-input>
          </el-form-item>
          
          <el-form-item label="Email To">
            <el-input
              v-model="shareEmails"
              placeholder="Enter email addresses separated by commas"
            />
          </el-form-item>
          
          <el-form-item label="Message">
            <el-input
              v-model="shareMessage"
              type="textarea"
              :rows="3"
              placeholder="Optional message to include"
            />
          </el-form-item>
          
          <el-form-item label="Access">
            <el-radio-group v-model="shareAccess">
              <el-radio label="view">View Only</el-radio>
              <el-radio label="download">View & Download</el-radio>
            </el-radio-group>
          </el-form-item>
          
          <el-form-item label="Expires">
            <el-select v-model="shareExpiry" style="width: 100%">
              <el-option label="Never" value="never" />
              <el-option label="1 Day" value="1d" />
              <el-option label="1 Week" value="1w" />
              <el-option label="1 Month" value="1m" />
              <el-option label="3 Months" value="3m" />
            </el-select>
          </el-form-item>
        </el-form>
      </div>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showShareDialog = false">Cancel</el-button>
          <el-button type="primary" @click="sendShareEmail" :loading="sharing">
            Share Report
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
  Document,
  Clock,
  Check,
  Download,
  User,
  Calendar,
  Money,
  Suitcase,
  TrendCharts,
  Reading,
  Search,
  Refresh,
  View,
  Share,
  Delete,
  CopyDocument
} from '@element-plus/icons-vue'

export default {
  name: 'ReportsList',
  components: {
    Plus,
    Document,
    Clock,
    Check,
    Download,
    User,
    Calendar,
    Money,
    Suitcase,
    TrendCharts,
    Reading,
    Search,
    Refresh,
    View,
    Share,
    Delete,
    CopyDocument
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const reportFormRef = ref()
    const showReportDialog = ref(false)
    const showShareDialog = ref(false)
    const generating = ref(false)
    const sharing = ref(false)
    const selectedReport = ref(null)
    const selectedReports = ref([])
    const shareLink = ref('')
    const shareEmails = ref('')
    const shareMessage = ref('')
    const shareAccess = ref('view')
    const shareExpiry = ref('1m')
    
    const filters = reactive({
      search: '',
      category: '',
      status: '',
      format: ''
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
    
    const reportForm = reactive({
      category: '',
      template_id: '',
      name: '',
      description: '',
      start_date: '',
      end_date: '',
      departments: [],
      format: 'pdf',
      schedule_type: 'now',
      scheduled_date: '',
      frequency: '',
      email_recipients: '',
      include_charts: true
    })
    
    const reportRules = {
      category: [
        { required: true, message: 'Please select report category', trigger: 'change' }
      ],
      template_id: [
        { required: true, message: 'Please select report template', trigger: 'change' }
      ],
      name: [
        { required: true, message: 'Please enter report name', trigger: 'blur' }
      ],
      start_date: [
        { required: true, message: 'Please select start date', trigger: 'change' }
      ],
      end_date: [
        { required: true, message: 'Please select end date', trigger: 'change' }
      ]
    }
    
    const reports = computed(() => store.getters['reports/reports'])
    const reportStats = computed(() => store.getters['reports/reportStats'])
    const departments = computed(() => store.getters['employees/departments'])
    const reportTemplates = computed(() => store.getters['reports/reportTemplates'])
    const loading = computed(() => store.getters['reports/loading'])
    
    const availableTemplates = computed(() => {
      if (!reportForm.category) return []
      return reportTemplates.value.filter(template => template.category === reportForm.category)
    })
    
    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const formatTime = (datetime) => {
      if (!datetime) return '-'
      return new Date(datetime).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
      })
    }
    
    const formatFileSize = (bytes) => {
      if (!bytes) return '-'
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(1024))
      return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
    }
    
    const getCategoryCount = (category) => {
      return reports.value.filter(report => report.category === category).length
    }
    
    const getReportIcon = (category) => {
      const iconMap = {
        employee: 'User',
        attendance: 'Calendar',
        payroll: 'Money',
        leave: 'Suitcase',
        performance: 'TrendCharts',
        training: 'Reading',
        recruitment: 'User'
      }
      return iconMap[category] || 'Document'
    }
    
    const getCategoryType = (category) => {
      const categoryMap = {
        employee: 'primary',
        attendance: 'success',
        payroll: 'warning',
        leave: 'info',
        performance: 'danger',
        training: '',
        recruitment: 'primary'
      }
      return categoryMap[category] || 'info'
    }
    
    const getCategoryText = (category) => {
      const categoryMap = {
        employee: 'Employee',
        attendance: 'Attendance',
        payroll: 'Payroll',
        leave: 'Leave',
        performance: 'Performance',
        training: 'Training',
        recruitment: 'Recruitment'
      }
      return categoryMap[category] || category
    }
    
    const getFormatType = (format) => {
      const formatMap = {
        pdf: 'danger',
        excel: 'success',
        csv: 'warning',
        word: 'primary'
      }
      return formatMap[format] || 'info'
    }
    
    const getStatusType = (status) => {
      const statusMap = {
        generated: 'success',
        scheduled: 'warning',
        processing: 'primary',
        failed: 'danger'
      }
      return statusMap[status] || 'info'
    }
    
    const getStatusText = (status) => {
      const statusMap = {
        generated: 'Generated',
        scheduled: 'Scheduled',
        processing: 'Processing',
        failed: 'Failed'
      }
      return statusMap[status] || status
    }
    
    const disabledEndDate = (time) => {
      if (!reportForm.start_date) return false
      return time.getTime() < new Date(reportForm.start_date).getTime()
    }
    
    const disabledScheduleDate = (time) => {
      return time.getTime() < Date.now()
    }
    
    const filterByCategory = (category) => {
      filters.category = category
      handleFilter()
    }
    
    const fetchReports = async () => {
      const params = {
        page: pagination.currentPage,
        limit: pagination.pageSize,
        search: filters.search,
        category: filters.category,
        status: filters.status,
        format: filters.format,
        sort_by: sortConfig.value.prop,
        sort_order: sortConfig.value.order
      }
      
      const result = await store.dispatch('reports/fetchReports', params)
      if (result && result.pagination) {
        pagination.total = result.pagination.total
      }
    }
    
    const handleSearch = () => {
      pagination.currentPage = 1
      fetchReports()
    }
    
    const handleFilter = () => {
      pagination.currentPage = 1
      fetchReports()
    }
    
    const handleSort = ({ prop, order }) => {
      sortConfig.value.prop = prop
      sortConfig.value.order = order === 'ascending' ? 'asc' : 'desc'
      fetchReports()
    }
    
    const handleSizeChange = (size) => {
      pagination.pageSize = size
      pagination.currentPage = 1
      fetchReports()
    }
    
    const handleCurrentChange = (page) => {
      pagination.currentPage = page
      fetchReports()
    }
    
    const handleSelectionChange = (selection) => {
      selectedReports.value = selection
    }
    
    const resetFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      pagination.currentPage = 1
      sortConfig.value = { prop: '', order: '' }
      fetchReports()
    }
    
    const onCategoryChange = () => {
      reportForm.template_id = ''
    }
    
    const resetReportForm = () => {
      Object.keys(reportForm).forEach(key => {
        if (key === 'departments') {
          reportForm[key] = []
        } else if (key === 'format') {
          reportForm[key] = 'pdf'
        } else if (key === 'schedule_type') {
          reportForm[key] = 'now'
        } else if (key === 'include_charts') {
          reportForm[key] = true
        } else {
          reportForm[key] = ''
        }
      })
    }
    
    const generateReport = async () => {
      try {
        const valid = await reportFormRef.value.validate()
        if (!valid) return
        
        generating.value = true
        
        const result = await store.dispatch('reports/generateReport', reportForm)
        
        if (result.success) {
          ElMessage.success(
            reportForm.schedule_type === 'now' 
              ? 'Report generation started successfully' 
              : 'Report scheduled successfully'
          )
          showReportDialog.value = false
          resetReportForm()
          fetchReports()
          store.dispatch('reports/fetchReportStats')
        } else {
          ElMessage.error(result.message || 'Failed to generate report')
        }
      } catch (error) {
        console.error('Generate report error:', error)
        ElMessage.error('An error occurred while generating report')
      } finally {
        generating.value = false
      }
    }
    
    const viewReport = (report) => {
      window.open(`/api/reports/${report.id}/view`, '_blank')
    }
    
    const downloadReport = async (report) => {
      try {
        const result = await store.dispatch('reports/downloadReport', report.id)
        if (result.success) {
          ElMessage.success('Report downloaded successfully')
          // Update download count
          report.download_count = (report.download_count || 0) + 1
        } else {
          ElMessage.error('Failed to download report')
        }
      } catch (error) {
        console.error('Download report error:', error)
        ElMessage.error('An error occurred while downloading report')
      }
    }
    
    const shareReport = (report) => {
      selectedReport.value = report
      shareLink.value = `${window.location.origin}/reports/shared/${report.share_token}`
      shareEmails.value = ''
      shareMessage.value = ''
      shareAccess.value = 'view'
      shareExpiry.value = '1m'
      showShareDialog.value = true
    }
    
    const copyShareLink = async () => {
      try {
        await navigator.clipboard.writeText(shareLink.value)
        ElMessage.success('Share link copied to clipboard')
      } catch (error) {
        console.error('Copy error:', error)
        ElMessage.error('Failed to copy share link')
      }
    }
    
    const sendShareEmail = async () => {
      try {
        sharing.value = true
        
        const result = await store.dispatch('reports/shareReport', {
          report_id: selectedReport.value.id,
          emails: shareEmails.value,
          message: shareMessage.value,
          access: shareAccess.value,
          expiry: shareExpiry.value
        })
        
        if (result.success) {
          ElMessage.success('Report shared successfully')
          showShareDialog.value = false
        } else {
          ElMessage.error(result.message || 'Failed to share report')
        }
      } catch (error) {
        console.error('Share report error:', error)
        ElMessage.error('An error occurred while sharing report')
      } finally {
        sharing.value = false
      }
    }
    
    const regenerateReport = async (report) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to regenerate the report "${report.name}"?`,
          'Confirm Regeneration',
          {
            confirmButtonText: 'Regenerate',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        
        const result = await store.dispatch('reports/regenerateReport', report.id)
        if (result.success) {
          ElMessage.success('Report regeneration started')
          fetchReports()
          store.dispatch('reports/fetchReportStats')
        } else {
          ElMessage.error(result.message || 'Failed to regenerate report')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Regenerate report error:', error)
          ElMessage.error('An error occurred while regenerating report')
        }
      }
    }
    
    const deleteReport = async (report) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to delete the report "${report.name}"?`,
          'Confirm Deletion',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        
        const result = await store.dispatch('reports/deleteReport', report.id)
        if (result.success) {
          ElMessage.success('Report deleted successfully')
          fetchReports()
          store.dispatch('reports/fetchReportStats')
        } else {
          ElMessage.error(result.message || 'Failed to delete report')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Delete report error:', error)
          ElMessage.error('An error occurred while deleting report')
        }
      }
    }
    
    const bulkDownload = async () => {
      if (selectedReports.value.length === 0) {
        ElMessage.warning('Please select reports to download')
        return
      }
      
      try {
        const reportIds = selectedReports.value.map(report => report.id)
        const result = await store.dispatch('reports/bulkDownload', reportIds)
        
        if (result.success) {
          ElMessage.success('Bulk download started')
        } else {
          ElMessage.error('Failed to start bulk download')
        }
      } catch (error) {
        console.error('Bulk download error:', error)
        ElMessage.error('An error occurred during bulk download')
      }
    }
    
    onMounted(async () => {
      await Promise.all([
        store.dispatch('employees/fetchDepartments'),
        store.dispatch('reports/fetchReportTemplates'),
        store.dispatch('reports/fetchReportStats'),
        fetchReports()
      ])
    })
    
    return {
      filters,
      pagination,
      reports,
      reportStats,
      departments,
      reportTemplates,
      availableTemplates,
      loading,
      showReportDialog,
      showShareDialog,
      reportFormRef,
      reportForm,
      reportRules,
      selectedReport,
      selectedReports,
      generating,
      sharing,
      shareLink,
      shareEmails,
      shareMessage,
      shareAccess,
      shareExpiry,
      formatDate,
      formatTime,
      formatFileSize,
      getCategoryCount,
      getReportIcon,
      getCategoryType,
      getCategoryText,
      getFormatType,
      getStatusType,
      getStatusText,
      disabledEndDate,
      disabledScheduleDate,
      filterByCategory,
      handleSearch,
      handleFilter,
      handleSort,
      handleSizeChange,
      handleCurrentChange,
      handleSelectionChange,
      resetFilters,
      onCategoryChange,
      generateReport,
      viewReport,
      downloadReport,
      shareReport,
      copyShareLink,
      sendShareEmail,
      regenerateReport,
      deleteReport,
      bulkDownload
    }
  }
}
</script>

<style scoped>
.reports-list {
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

.summary-icon.reports {
  background-color: #409EFF;
}

.summary-icon.scheduled {
  background-color: #E6A23C;
}

.summary-icon.generated {
  background-color: #67C23A;
}

.summary-icon.downloads {
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

.report-categories {
  margin-bottom: 24px;
}

.category-card {
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid #EBEEF5;
}

.category-card:hover {
  border-color: #409EFF;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}

.category-content {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.category-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
  flex-shrink: 0;
}

.category-icon.employee {
  background-color: #409EFF;
}

.category-icon.attendance {
  background-color: #67C23A;
}

.category-icon.payroll {
  background-color: #E6A23C;
}

.category-icon.leave {
  background-color: #909399;
}

.category-icon.performance {
  background-color: #F56C6C;
}

.category-icon.training {
  background-color: #606266;
}

.category-info {
  flex: 1;
  min-width: 0;
}

.category-info h3 {
  margin: 0 0 8px 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.category-info p {
  margin: 0 0 8px 0;
  color: #606266;
  font-size: 14px;
  line-height: 1.4;
}

.category-count {
  color: #909399;
  font-size: 12px;
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

.report-info {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.report-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background-color: #F5F7FA;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #606266;
  flex-shrink: 0;
}

.report-details {
  flex: 1;
  min-width: 0;
}

.report-name {
  font-weight: 500;
  color: #303133;
  margin-bottom: 4px;
  line-height: 1.4;
}

.report-description {
  font-size: 12px;
  color: #909399;
  margin-bottom: 8px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.report-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.report-id {
  font-size: 12px;
  color: #909399;
  background-color: #F5F7FA;
  padding: 2px 6px;
  border-radius: 4px;
}

.user-info {
  min-width: 0;
}

.user-name {
  font-weight: 500;
  color: #303133;
  margin-bottom: 2px;
}

.user-role {
  font-size: 12px;
  color: #909399;
}

.date-range {
  min-width: 0;
}

.dates {
  font-size: 13px;
  color: #606266;
  margin-bottom: 2px;
}

.period {
  font-size: 12px;
  color: #909399;
}

.no-size {
  color: #909399;
  font-style: italic;
}

.generated-info {
  min-width: 0;
}

.date {
  font-size: 13px;
  color: #606266;
  margin-bottom: 2px;
}

.time {
  font-size: 12px;
  color: #909399;
}

.download-count {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #606266;
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

.template-option {
  padding: 4px 0;
}

.template-name {
  font-weight: 500;
  color: #303133;
  margin-bottom: 2px;
}

.template-description {
  font-size: 12px;
  color: #909399;
}

.form-help-text {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.share-content {
  margin-bottom: 20px;
}

.share-info h3 {
  margin: 0 0 8px 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.share-info p {
  margin: 0 0 16px 0;
  color: #606266;
  line-height: 1.4;
}

.share-link-input {
  font-family: monospace;
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

:deep(.el-select-dropdown__item) {
  height: auto;
  padding: 8px 20px;
}

@media (max-width: 768px) {
  .reports-list {
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
  
  .report-categories {
    margin-bottom: 20px;
  }
  
  .category-content {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
  
  .category-icon {
    align-self: center;
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
  
  .report-info {
    flex-direction: column;
    gap: 8px;
    text-align: center;
  }
  
  .report-meta {
    justify-content: center;
  }
}
</style>