<template>
  <div class="page-container">
    <!-- Import modern CSS -->
    <!-- CSS import moved to style section -->
    
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1>Attendance Management</h1>
        <p>Track and manage employee attendance records</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary" @click="showCheckInDialog = true">
          <el-icon><Plus /></el-icon>
          Check In/Out
        </button>
      </div>
    </div>
    
    <!-- Today's Summary -->
    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-content">
          <div class="metric-icon success">
            <el-icon><UserFilled /></el-icon>
          </div>
          <div class="metric-info">
            <h3>{{ todayStats.present || 0 }}</h3>
            <p>Present Today</p>
            <span class="metric-change positive">{{ ((todayStats.present / (todayStats.present + todayStats.absent)) * 100).toFixed(1) || 0 }}% attendance</span>
          </div>
        </div>
      </div>
      
      <div class="metric-card">
        <div class="metric-content">
          <div class="metric-icon danger">
            <el-icon><Close /></el-icon>
          </div>
          <div class="metric-info">
            <h3>{{ todayStats.absent || 0 }}</h3>
            <p>Absent Today</p>
            <span class="metric-change negative">{{ ((todayStats.absent / (todayStats.present + todayStats.absent)) * 100).toFixed(1) || 0 }}% absent</span>
          </div>
        </div>
      </div>
      
      <div class="metric-card">
        <div class="metric-content">
          <div class="metric-icon warning">
            <el-icon><Clock /></el-icon>
          </div>
          <div class="metric-info">
            <h3>{{ todayStats.late || 0 }}</h3>
            <p>Late Arrivals</p>
            <span class="metric-change neutral">Today's late count</span>
          </div>
        </div>
      </div>
      
      <div class="metric-card">
        <div class="metric-content">
          <div class="metric-icon primary">
            <el-icon><Timer /></el-icon>
          </div>
          <div class="metric-info">
            <h3>{{ todayStats.early_departure || 0 }}</h3>
            <p>Early Departures</p>
            <span class="metric-change neutral">Today's early departures</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Filters and Search -->
     <div class="filter-bar">
       <div class="filter-grid">
         <div class="form-group">
           <label class="form-label">Search Employees</label>
           <input
             v-model="filters.search"
             type="text"
             class="form-input"
             placeholder="Search by name or employee ID..."
             @input="handleSearch"
           />
         </div>
         
         <div class="form-group">
           <label class="form-label">Department</label>
           <select
             v-model="filters.department"
             class="form-select"
             @change="handleFilter"
           >
             <option value="">All Departments</option>
             <option
               v-for="dept in departments"
               :key="dept.id"
               :value="dept.id"
             >
               {{ dept.name }}
             </option>
           </select>
         </div>
         
         <div class="form-group">
           <label class="form-label">Status</label>
           <select
             v-model="filters.status"
             class="form-select"
             @change="handleFilter"
           >
             <option value="">All Status</option>
             <option value="present">Present</option>
             <option value="absent">Absent</option>
             <option value="late">Late</option>
             <option value="early">Early Departure</option>
           </select>
         </div>
         
         <div class="form-group">
           <label class="form-label">Date</label>
           <input
             v-model="filters.date"
             type="date"
             class="form-input"
             @change="handleFilter"
           />
         </div>
         
         <div class="form-group">
           <label class="form-label">&nbsp;</label>
           <div class="flex gap-2">
             <button class="btn btn-outline" @click="resetFilters">
               <el-icon><Refresh /></el-icon>
               Reset
             </button>
             <button class="btn btn-success" @click="exportAttendance">
               <el-icon><Download /></el-icon>
               Export
             </button>
           </div>
         </div>
       </div>
     </div>
    
    <!-- Attendance Records -->
    <div class="modern-table">
      <div class="table-header">
        <h3 class="table-title">Attendance Records</h3>
        <div class="table-actions">
          <span class="text-sm text-gray-600">{{ pagination.total }} records found</span>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <p>Loading attendance records...</p>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="error-container">
        <div class="error-title">Error Loading Attendance</div>
        <p>{{ error }}</p>
        <button class="btn btn-primary mt-3" @click="fetchAttendance">
          <el-icon><Refresh /></el-icon>
          Retry
        </button>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="!attendanceRecords.length" class="empty-state">
        <div class="empty-state-icon">
          <el-icon><Clock /></el-icon>
        </div>
        <h3 class="empty-state-title">No Attendance Records Found</h3>
        <p class="empty-state-description">
          {{ filters.search || filters.department || filters.status || filters.date 
             ? 'No records match your current filters.' 
             : 'No attendance records available for the selected period.' }}
        </p>
        <button class="btn btn-primary" @click="showCheckInDialog = true">
          <el-icon><Plus /></el-icon>
          Add Attendance Record
        </button>
      </div>
      
      <!-- Attendance Cards -->
      <div v-else class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="record in attendanceRecords"
            :key="record.id"
            class="modern-card fade-in"
          >
            <div class="card-body">
              <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-green-500 to-blue-600 flex items-center justify-center text-white text-xl font-bold">
                  {{ getInitials(record.employee?.first_name, record.employee?.last_name) }}
                </div>
                <div class="flex-1">
                  <h4 class="font-semibold text-lg text-gray-900">
                    {{ record.employee?.first_name }} {{ record.employee?.last_name }}
                  </h4>
                  <p class="text-gray-600 text-sm">{{ record.employee?.department?.name || 'No Department' }}</p>
                  <p class="text-gray-500 text-xs">{{ formatDate(record.date) }}</p>
                </div>
              </div>
              
              <div class="space-y-3 mb-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2 text-sm text-gray-600">
                    <el-icon><Timer /></el-icon>
                    <span>Check In</span>
                  </div>
                  <span :class="getTimeClass(record.check_in_time, 'in')">
                    {{ record.check_in_time ? formatTime(record.check_in_time) : '--' }}
                  </span>
                </div>
                
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2 text-sm text-gray-600">
                    <el-icon><Timer /></el-icon>
                    <span>Check Out</span>
                  </div>
                  <span :class="getTimeClass(record.check_out_time, 'out')">
                    {{ record.check_out_time ? formatTime(record.check_out_time) : '--' }}
                  </span>
                </div>
                
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2 text-sm text-gray-600">
                    <el-icon><Clock /></el-icon>
                    <span>Working Hours</span>
                  </div>
                  <span class="font-medium text-gray-900">
                    {{ record.working_hours ? formatDuration(record.working_hours) : '--' }}
                  </span>
                </div>
              </div>
              
              <div class="flex items-center justify-between">
                <span :class="getStatusClass(record.status)">
                  {{ getStatusText(record.status) }}
                </span>
                
                <div class="flex gap-2">
                  <button
                    class="btn btn-primary btn-sm"
                    @click="editAttendance(record)"
                  >
                    <el-icon><Edit /></el-icon>
                  </button>
                  <button
                    class="btn btn-danger btn-sm"
                    @click="confirmDelete(record)"
                  >
                    <el-icon><Delete /></el-icon>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Pagination -->
        <div class="flex justify-center mt-8">
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
      </div>
    </div>
    
    <!-- Manual Check-in/out Dialog -->
    <el-dialog
      v-model="showCheckInDialog"
      title="Manual Check-in/out"
      width="500px"
    >
      <el-form
        ref="checkInFormRef"
        :model="checkInForm"
        :rules="checkInRules"
        label-width="120px"
      >
        <el-form-item label="Employee" prop="employee_id">
          <el-select
            v-model="checkInForm.employee_id"
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
        <el-form-item label="Date" prop="date">
          <el-date-picker
            v-model="checkInForm.date"
            type="date"
            placeholder="Select date"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Check In" prop="check_in_time">
          <el-time-picker
            v-model="checkInForm.check_in_time"
            placeholder="Select check-in time"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Check Out" prop="check_out_time">
          <el-time-picker
            v-model="checkInForm.check_out_time"
            placeholder="Select check-out time"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Notes">
          <el-input
            v-model="checkInForm.notes"
            type="textarea"
            :rows="3"
            placeholder="Add notes (optional)"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showCheckInDialog = false">Cancel</el-button>
          <el-button type="primary" @click="handleManualCheckIn" :loading="submitting">
            Save
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
  Search,
  Refresh,
  Download,
  Edit,
  Delete,
  UserFilled,
  Close,
  Clock,
  Timer,
  Check
} from '@element-plus/icons-vue'

export default {
  name: 'AttendanceList',
  components: {
    Plus,
    Search,
    Refresh,
    Download,
    Edit,
    Delete,
    UserFilled,
    Close,
    Clock,
    Timer,
    Check
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const checkInFormRef = ref()
    const showCheckInDialog = ref(false)
    const submitting = ref(false)
    
    const filters = reactive({
      search: '',
      department: '',
      status: '',
      date: ''
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
    
    const error = ref(null)
    
    const checkInForm = reactive({
      employee_id: '',
      date: '',
      check_in_time: '',
      check_out_time: '',
      notes: ''
    })
    
    const checkInRules = {
      employee_id: [
        { required: true, message: 'Please select an employee', trigger: 'change' }
      ],
      date: [
        { required: true, message: 'Please select a date', trigger: 'change' }
      ],
      check_in_time: [
        { required: true, message: 'Please select check-in time', trigger: 'change' }
      ]
    }
    
    const attendanceRecords = computed(() => store.getters['attendance/attendanceRecords'] || [])
    const todayStats = computed(() => store.getters['attendance/todayStats'] || {
      present: 0,
      absent: 0,
      late: 0,
      early_departure: 0
    })
    const departments = computed(() => store.getters['employees/departments'] || [])
    const employees = computed(() => store.getters['employees/employees'] || [])
    const loading = computed(() => store.getters['attendance/loading'])
    
    const getInitials = (firstName, lastName) => {
      return `${firstName?.charAt(0) || ''}${lastName?.charAt(0) || ''}`.toUpperCase()
    }
    
    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
      })
    }
    
    const formatTime = (time) => {
      if (!time) return '-'
      return new Date(`2000-01-01 ${time}`).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      })
    }
    
    const formatDuration = (minutes) => {
      if (!minutes) return '-'
      const hours = Math.floor(minutes / 60)
      const mins = minutes % 60
      return `${hours}h ${mins}m`
    }
    
    const getStatusClass = (status) => {
       const statusMap = {
         present: 'status-badge status-active',
         absent: 'status-badge status-inactive',
         late: 'status-badge status-warning',
         early: 'status-badge status-pending'
       }
       return statusMap[status] || 'status-badge status-inactive'
     }
     
     const getStatusText = (status) => {
       const statusMap = {
         present: 'Present',
         absent: 'Absent',
         late: 'Late',
         early: 'Early Departure'
       }
       return statusMap[status] || status
     }
     
     const getTimeClass = (time, type) => {
       if (!time) return 'text-gray-400'
       
       const timeObj = new Date(`2000-01-01 ${time}`)
       const standardStart = new Date('2000-01-01 09:00:00')
       const standardEnd = new Date('2000-01-01 17:00:00')
       
       if (type === 'in') {
         return timeObj > standardStart ? 'text-red-600 font-medium' : 'text-green-600 font-medium'
       } else {
         return timeObj < standardEnd ? 'text-orange-600 font-medium' : 'text-green-600 font-medium'
       }
     }
    
    const fetchAttendance = async () => {
      try {
        error.value = null
        const params = {
          page: pagination.currentPage,
          limit: pagination.pageSize,
          search: filters.search,
          department: filters.department,
          status: filters.status,
          date: filters.date
        }
        
        const result = await store.dispatch('attendance/fetchAttendanceRecords', params)
        if (!result.success) {
          error.value = result.message || 'Failed to load attendance records'
        } else if (result.pagination) {
          pagination.total = result.pagination.total
        }
      } catch (err) {
        error.value = 'An unexpected error occurred'
        console.error('Fetch attendance error:', err)
      }
    }
    
    const handleSearch = () => {
      pagination.currentPage = 1
      fetchAttendance()
    }
    
    const handleFilter = () => {
      pagination.currentPage = 1
      fetchAttendance()
    }
    
    const handleSort = ({ prop, order }) => {
      sortConfig.value.prop = prop
      sortConfig.value.order = order === 'ascending' ? 'asc' : 'desc'
      fetchAttendance()
    }
    
    const handleSizeChange = (size) => {
      pagination.pageSize = size
      pagination.currentPage = 1
      fetchAttendance()
    }
    
    const handleCurrentChange = (page) => {
      pagination.currentPage = page
      fetchAttendance()
    }
    
    const resetFilters = () => {
       Object.keys(filters).forEach(key => {
         filters[key] = ''
       })
       pagination.currentPage = 1
       fetchAttendance()
     }
     
     const exportAttendance = () => {
       ElMessage.info('Export functionality will be implemented soon')
     }
     
     const confirmDelete = async (record) => {
       try {
         await ElMessageBox.confirm(
           `Are you sure you want to delete this attendance record for ${record.employee?.first_name} ${record.employee?.last_name}?`,
           'Confirm Delete',
           {
             confirmButtonText: 'Delete',
             cancelButtonText: 'Cancel',
             type: 'warning'
           }
         )
         
         const result = await store.dispatch('attendance/deleteAttendance', record.id)
         if (result.success) {
           ElMessage.success('Attendance record deleted successfully')
           fetchAttendance()
         } else {
           ElMessage.error(result.message || 'Failed to delete attendance record')
         }
       } catch (err) {
         if (err !== 'cancel') {
           ElMessage.error('An error occurred while deleting attendance record')
         }
       }
     }
    
    const editAttendance = (record) => {
      // Implementation for editing attendance record
      ElMessage.info('Edit functionality will be implemented')
    }
    
    const deleteAttendance = async (record) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to delete this attendance record?`,
          'Confirm Delete',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning',
            confirmButtonClass: 'el-button--danger'
          }
        )
        
        const result = await store.dispatch('attendance/deleteAttendanceRecord', record.id)
        if (result.success) {
          ElMessage.success('Attendance record deleted successfully')
          fetchAttendance()
        } else {
          ElMessage.error(result.message || 'Failed to delete attendance record')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Delete error:', error)
          ElMessage.error('An error occurred while deleting attendance record')
        }
      }
    }
    
    const handleManualCheckIn = async () => {
      try {
        const valid = await checkInFormRef.value.validate()
        if (!valid) return
        
        submitting.value = true
        const result = await store.dispatch('attendance/createAttendanceRecord', checkInForm)
        
        if (result.success) {
          ElMessage.success('Attendance record created successfully')
          showCheckInDialog.value = false
          Object.keys(checkInForm).forEach(key => {
            checkInForm[key] = ''
          })
          fetchAttendance()
        } else {
          ElMessage.error(result.message || 'Failed to create attendance record')
        }
      } catch (error) {
        console.error('Manual check-in error:', error)
        ElMessage.error('An error occurred while creating attendance record')
      } finally {
        submitting.value = false
      }
    }
    
    onMounted(async () => {
      await Promise.all([
        store.dispatch('employees/fetchDepartments'),
        store.dispatch('employees/fetchEmployees'),
        store.dispatch('attendance/fetchTodayStats'),
        fetchAttendance()
      ])
    })
    
    return {
      filters,
      pagination,
      attendanceRecords,
      todayStats,
      departments,
      employees,
      loading,
      error,
      showCheckInDialog,
      checkInFormRef,
      checkInForm,
      checkInRules,
      submitting,
      getInitials,
      formatDate,
      formatTime,
      formatDuration,
      getStatusClass,
      getStatusText,
      getTimeClass,
      handleSearch,
      handleFilter,
      handleSizeChange,
      handleCurrentChange,
      resetFilters,
      exportAttendance,
      editAttendance,
      confirmDelete,
      fetchAttendance,
      handleManualCheckIn
    }
  }
}
</script>

<style scoped>
@import '../../assets/styles/modern-hrms.css';

.btn-sm {
  padding: 0.5rem 0.75rem;
  font-size: 0.75rem;
}

.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
  .md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .lg\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.space-y-3 > * + * {
  margin-top: 0.75rem;
}

.text-sm {
  font-size: 0.875rem;
}

.text-xs {
  font-size: 0.75rem;
}

.text-lg {
  font-size: 1.125rem;
}

.text-gray-900 {
  color: #1a202c;
}

.text-gray-600 {
  color: #718096;
}

.text-gray-500 {
  color: #a0aec0;
}

.text-gray-400 {
  color: #cbd5e0;
}

.text-red-600 {
  color: #e53e3e;
}

.text-green-600 {
  color: #38a169;
}

.text-orange-600 {
  color: #dd6b20;
}

.font-semibold {
  font-weight: 600;
}

.font-bold {
  font-weight: 700;
}

.font-medium {
  font-weight: 500;
}

.bg-gradient-to-br {
  background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

.from-green-500 {
  --tw-gradient-from: #48bb78;
  --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(72, 187, 120, 0));
}

.to-blue-600 {
  --tw-gradient-to: #3182ce;
}
</style>