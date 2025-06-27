<template>
  <div class="attendance-management">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="fas fa-clock"></i>
          Attendance Management
        </h1>
        <p class="page-description">Manage employee attendance with multiple tracking methods</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" @click="showClockInModal = true">
          <i class="fas fa-sign-in-alt"></i>
          Clock In/Out
        </el-button>
        <el-button type="success" @click="showReportsModal = true">
          <i class="fas fa-chart-bar"></i>
          Reports
        </el-button>
      </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon present">
          <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
          <h3>{{ todayStats.present }}</h3>
          <p>Present Today</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon late">
          <i class="fas fa-user-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ todayStats.late }}</h3>
          <p>Late Arrivals</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon absent">
          <i class="fas fa-user-times"></i>
        </div>
        <div class="stat-content">
          <h3>{{ todayStats.absent }}</h3>
          <p>Absent Today</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon overtime">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ todayStats.overtime_hours }}h</h3>
          <p>Overtime Hours</p>
        </div>
      </div>
    </div>

    <!-- Attendance Methods Tabs -->
    <div class="attendance-methods">
      <el-tabs v-model="activeTab" @tab-click="handleTabClick">
        <el-tab-pane label="Manual Entry" name="manual">
          <ManualAttendance @attendance-recorded="refreshData" />
        </el-tab-pane>
        <el-tab-pane label="QR Code" name="qr">
          <QRAttendance @attendance-recorded="refreshData" />
        </el-tab-pane>
        <el-tab-pane label="RFID" name="rfid">
          <RFIDAttendance @attendance-recorded="refreshData" />
        </el-tab-pane>
        <el-tab-pane label="Biometric" name="biometric">
          <BiometricAttendance @attendance-recorded="refreshData" />
        </el-tab-pane>
      </el-tabs>
    </div>

    <!-- Today's Attendance Table -->
    <div class="attendance-table-section">
      <div class="section-header">
        <h2>Today's Attendance</h2>
        <div class="table-actions">
          <el-date-picker
            v-model="selectedDate"
            type="date"
            placeholder="Select date"
            @change="loadAttendanceData"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
          />
          <el-select v-model="selectedDepartment" placeholder="All Departments" @change="loadAttendanceData">
            <el-option label="All Departments" value="" />
            <el-option
              v-for="dept in departments"
              :key="dept.id"
              :label="dept.name"
              :value="dept.id"
            />
          </el-select>
          <el-button @click="exportData">
            <i class="fas fa-download"></i>
            Export
          </el-button>
        </div>
      </div>

      <el-table
        :data="attendanceData"
        v-loading="loading"
        stripe
        style="width: 100%"
      >
        <el-table-column prop="emp_code" label="Employee ID" width="120" />
        <el-table-column label="Employee Name" width="200">
          <template #default="scope">
            <div class="employee-info">
              <strong>{{ scope.row.first_name }} {{ scope.row.last_name }}</strong>
              <small>{{ scope.row.department }}</small>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="clock_in" label="Clock In" width="120">
          <template #default="scope">
            <span v-if="scope.row.clock_in">{{ formatTime(scope.row.clock_in) }}</span>
            <span v-else class="text-muted">--</span>
          </template>
        </el-table-column>
        <el-table-column prop="clock_out" label="Clock Out" width="120">
          <template #default="scope">
            <span v-if="scope.row.clock_out">{{ formatTime(scope.row.clock_out) }}</span>
            <span v-else class="text-muted">--</span>
          </template>
        </el-table-column>
        <el-table-column prop="hours_worked" label="Hours" width="80">
          <template #default="scope">
            <span v-if="scope.row.hours_worked">{{ scope.row.hours_worked }}h</span>
            <span v-else class="text-muted">--</span>
          </template>
        </el-table-column>
        <el-table-column label="Status" width="120">
          <template #default="scope">
            <el-tag
              :type="getStatusType(scope.row.final_status)"
              size="small"
            >
              {{ scope.row.final_status }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Late" width="80">
          <template #default="scope">
            <span v-if="scope.row.late_minutes > 0" class="text-warning">
              {{ scope.row.late_minutes }}m
            </span>
            <span v-else class="text-muted">--</span>
          </template>
        </el-table-column>
        <el-table-column label="Overtime" width="80">
          <template #default="scope">
            <span v-if="scope.row.overtime_minutes > 0" class="text-success">
              {{ Math.round(scope.row.overtime_minutes / 60 * 100) / 100 }}h
            </span>
            <span v-else class="text-muted">--</span>
          </template>
        </el-table-column>
        <el-table-column label="Salary Impact" width="120">
          <template #default="scope">
            <span 
              :class="{
                'text-success': scope.row.net_salary_impact > 0,
                'text-danger': scope.row.net_salary_impact < 0,
                'text-muted': scope.row.net_salary_impact === 0
              }"
            >
              {{ scope.row.net_salary_impact > 0 ? '+' : '' }}${{ scope.row.net_salary_impact || 0 }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="Type" width="100">
          <template #default="scope">
            <el-tag v-if="scope.row.attendance_type" size="small" type="info">
              {{ scope.row.attendance_type }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="120">
          <template #default="scope">
            <el-button
              size="small"
              @click="editAttendance(scope.row)"
              :disabled="!canEdit(scope.row)"
            >
              <i class="fas fa-edit"></i>
            </el-button>
            <el-button
              size="small"
              type="danger"
              @click="deleteAttendance(scope.row)"
              :disabled="!canDelete(scope.row)"
            >
              <i class="fas fa-trash"></i>
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Clock In/Out Modal -->
    <el-dialog
      v-model="showClockInModal"
      title="Quick Clock In/Out"
      width="500px"
    >
      <QuickClockInOut @attendance-recorded="handleClockInOut" />
    </el-dialog>

    <!-- Reports Modal -->
    <el-dialog
      v-model="showReportsModal"
      title="Attendance Reports"
      width="800px"
    >
      <AttendanceReports />
    </el-dialog>

    <!-- Edit Attendance Modal -->
    <el-dialog
      v-model="showEditModal"
      title="Edit Attendance"
      width="600px"
    >
      <EditAttendanceForm
        v-if="selectedAttendance"
        :attendance="selectedAttendance"
        @updated="handleAttendanceUpdate"
        @cancel="showEditModal = false"
      />
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useStore } from 'vuex'
import ManualAttendance from './components/ManualAttendance.vue'
import QRAttendance from './components/QRAttendance.vue'
import RFIDAttendance from './components/RFIDAttendance.vue'
import BiometricAttendance from './components/BiometricAttendance.vue'
import QuickClockInOut from './components/QuickClockInOut.vue'
import AttendanceReports from './components/AttendanceReports.vue'
import EditAttendanceForm from './components/EditAttendanceForm.vue'
import attendanceAPI from '@/api/attendance'
import departmentAPI from '@/api/departments'

export default {
  name: 'AttendanceManagement',
  components: {
    ManualAttendance,
    QRAttendance,
    RFIDAttendance,
    BiometricAttendance,
    QuickClockInOut,
    AttendanceReports,
    EditAttendanceForm
  },
  setup() {
    const store = useStore()
    
    // Reactive data
    const activeTab = ref('manual')
    const loading = ref(false)
    const selectedDate = ref(new Date().toISOString().split('T')[0])
    const selectedDepartment = ref('')
    const attendanceData = ref([])
    const departments = ref([])
    const showClockInModal = ref(false)
    const showReportsModal = ref(false)
    const showEditModal = ref(false)
    const selectedAttendance = ref(null)
    
    const todayStats = reactive({
      present: 0,
      late: 0,
      absent: 0,
      overtime_hours: 0
    })
    
    // Computed properties
    const currentUser = computed(() => store.getters.currentUser)
    const userRole = computed(() => store.getters.userRole)
    
    // Methods
    const loadAttendanceData = async () => {
      try {
        loading.value = true
        const params = {
          date: selectedDate.value,
          department_id: selectedDepartment.value || undefined
        }
        
        const response = await attendanceAPI.getDailyReport(params)
        if (response.success) {
          attendanceData.value = response.data.attendance_data
          updateTodayStats(response.data.summary)
        }
      } catch (error) {
        ElMessage.error('Failed to load attendance data')
        console.error('Load attendance error:', error)
      } finally {
        loading.value = false
      }
    }
    
    const loadDepartments = async () => {
      try {
        const response = await departmentAPI.getAll()
        if (response.success) {
          departments.value = response.data
        }
      } catch (error) {
        console.error('Load departments error:', error)
      }
    }
    
    const updateTodayStats = (summary) => {
      todayStats.present = summary.present || 0
      todayStats.late = summary.late || 0
      todayStats.absent = summary.absent || 0
      todayStats.overtime_hours = Math.round((summary.total_overtime_minutes || 0) / 60 * 100) / 100
    }
    
    const refreshData = () => {
      loadAttendanceData()
    }
    
    const handleTabClick = (tab) => {
      // Handle tab switching logic if needed
      console.log('Active tab:', tab.name)
    }
    
    const handleClockInOut = () => {
      showClockInModal.value = false
      refreshData()
      ElMessage.success('Attendance recorded successfully')
    }
    
    const editAttendance = (attendance) => {
      selectedAttendance.value = attendance
      showEditModal.value = true
    }
    
    const handleAttendanceUpdate = () => {
      showEditModal.value = false
      selectedAttendance.value = null
      refreshData()
      ElMessage.success('Attendance updated successfully')
    }
    
    const deleteAttendance = async (attendance) => {
      try {
        await ElMessageBox.confirm(
          'Are you sure you want to delete this attendance record?',
          'Confirm Delete',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        
        const response = await attendanceAPI.delete(attendance.id)
        if (response.success) {
          ElMessage.success('Attendance record deleted')
          refreshData()
        }
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error('Failed to delete attendance record')
        }
      }
    }
    
    const exportData = async () => {
      try {
        const params = {
          start_date: selectedDate.value,
          end_date: selectedDate.value,
          department_id: selectedDepartment.value || undefined,
          format: 'csv'
        }
        
        const response = await attendanceAPI.exportData(params)
        if (response.success) {
          // Handle file download
          window.open(response.data.export_url, '_blank')
          ElMessage.success('Export started successfully')
        }
      } catch (error) {
        ElMessage.error('Failed to export data')
      }
    }
    
    const canEdit = (attendance) => {
      return ['admin', 'hr'].includes(userRole.value) || 
             (userRole.value === 'manager' && attendance.department === currentUser.value.department)
    }
    
    const canDelete = (attendance) => {
      return ['admin', 'hr'].includes(userRole.value)
    }
    
    const getStatusType = (status) => {
      const statusTypes = {
        present: 'success',
        late: 'warning',
        absent: 'danger',
        'half-day': 'info'
      }
      return statusTypes[status] || 'info'
    }
    
    const formatTime = (time) => {
      if (!time) return '--'
      return new Date(`2000-01-01 ${time}`).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      })
    }
    
    // Lifecycle
    onMounted(() => {
      loadDepartments()
      loadAttendanceData()
    })
    
    return {
      // Reactive data
      activeTab,
      loading,
      selectedDate,
      selectedDepartment,
      attendanceData,
      departments,
      showClockInModal,
      showReportsModal,
      showEditModal,
      selectedAttendance,
      todayStats,
      
      // Computed
      currentUser,
      userRole,
      
      // Methods
      loadAttendanceData,
      refreshData,
      handleTabClick,
      handleClockInOut,
      editAttendance,
      handleAttendanceUpdate,
      deleteAttendance,
      exportData,
      canEdit,
      canDelete,
      getStatusType,
      formatTime
    }
  }
}
</script>

<style scoped>
.attendance-management {
  padding: 24px;
  background-color: #f5f7fa;
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  background: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-content h1 {
  margin: 0;
  color: #2c3e50;
  font-size: 28px;
  font-weight: 600;
}

.header-content h1 i {
  margin-right: 12px;
  color: #3498db;
}

.page-description {
  margin: 8px 0 0 0;
  color: #7f8c8d;
  font-size: 14px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.stat-icon.present {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
}

.stat-icon.late {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.stat-icon.absent {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.stat-icon.overtime {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.stat-content h3 {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  color: #2c3e50;
}

.stat-content p {
  margin: 4px 0 0 0;
  color: #7f8c8d;
  font-size: 14px;
}

.attendance-methods {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 24px;
  padding: 24px;
}

.attendance-table-section {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 24px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h2 {
  margin: 0;
  color: #2c3e50;
  font-size: 20px;
  font-weight: 600;
}

.table-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.employee-info {
  display: flex;
  flex-direction: column;
}

.employee-info small {
  color: #7f8c8d;
  font-size: 12px;
}

.text-muted {
  color: #bdc3c7;
}

.text-warning {
  color: #f39c12;
  font-weight: 600;
}

.text-success {
  color: #27ae60;
  font-weight: 600;
}

.text-danger {
  color: #e74c3c;
  font-weight: 600;
}

:deep(.el-tabs__header) {
  margin-bottom: 20px;
}

:deep(.el-tabs__nav-wrap::after) {
  background-color: #ecf0f1;
}

:deep(.el-tabs__active-bar) {
  background-color: #3498db;
}

:deep(.el-tabs__item.is-active) {
  color: #3498db;
}

:deep(.el-table th) {
  background-color: #f8f9fa;
  color: #2c3e50;
  font-weight: 600;
}

:deep(.el-table--striped .el-table__body tr.el-table__row--striped td) {
  background-color: #fafbfc;
}

:deep(.el-button--small) {
  padding: 5px 8px;
}
</style>