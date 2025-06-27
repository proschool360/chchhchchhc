<template>
  <div class="quick-clock">
    <el-card>
      <template #header>
        <div class="card-header">
          <h3>Quick Clock In/Out</h3>
          <p>Fast attendance tracking for employees</p>
        </div>
      </template>
      
      <div class="clock-interface">
        <!-- Current Time Display -->
        <div class="time-display">
          <div class="current-time">
            <h2>{{ currentTime }}</h2>
            <p>{{ currentDate }}</p>
          </div>
        </div>
        
        <!-- Employee Selection -->
        <div class="employee-selection">
          <el-form :model="clockForm" ref="clockFormRef">
            <el-form-item>
              <el-select
                v-model="clockForm.employeeId"
                placeholder="Search and select employee"
                filterable
                remote
                :remote-method="searchEmployees"
                :loading="searchLoading"
                size="large"
                style="width: 100%"
                @change="handleEmployeeSelect"
              >
                <el-option
                  v-for="employee in employees"
                  :key="employee.id"
                  :label="`${employee.name} (${employee.employee_id})`"
                  :value="employee.id"
                >
                  <div class="employee-option">
                    <div class="employee-info">
                      <span class="employee-name">{{ employee.name }}</span>
                      <span class="employee-id">{{ employee.employee_id }}</span>
                    </div>
                    <div class="employee-department">{{ employee.department }}</div>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-form>
        </div>
        
        <!-- Selected Employee Info -->
        <div class="selected-employee" v-if="selectedEmployee">
          <div class="employee-card">
            <div class="employee-avatar">
              <img 
                v-if="selectedEmployee.photo" 
                :src="selectedEmployee.photo" 
                :alt="selectedEmployee.name"
              />
              <i v-else class="fas fa-user"></i>
            </div>
            <div class="employee-details">
              <h4>{{ selectedEmployee.name }}</h4>
              <p>{{ selectedEmployee.employee_id }} • {{ selectedEmployee.department }}</p>
              <div class="attendance-status">
                <el-tag 
                  :type="getStatusType(selectedEmployee.todayStatus)"
                  size="small"
                >
                  {{ selectedEmployee.todayStatus || 'Not Clocked In' }}
                </el-tag>
                <span v-if="selectedEmployee.lastAction" class="last-action">
                  Last: {{ selectedEmployee.lastAction }} at {{ selectedEmployee.lastActionTime }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Clock Actions -->
        <div class="clock-actions" v-if="selectedEmployee">
          <div class="action-buttons">
            <el-button
              type="success"
              size="large"
              @click="clockIn"
              :loading="processing"
              :disabled="!canClockIn"
              class="clock-btn clock-in"
            >
              <i class="fas fa-sign-in-alt"></i>
              Clock In
            </el-button>
            
            <el-button
              type="warning"
              size="large"
              @click="clockOut"
              :loading="processing"
              :disabled="!canClockOut"
              class="clock-btn clock-out"
            >
              <i class="fas fa-sign-out-alt"></i>
              Clock Out
            </el-button>
            
            <el-button
              type="info"
              size="large"
              @click="startBreak"
              :loading="processing"
              :disabled="!canStartBreak"
              class="clock-btn break-start"
            >
              <i class="fas fa-coffee"></i>
              Start Break
            </el-button>
            
            <el-button
              type="primary"
              size="large"
              @click="endBreak"
              :loading="processing"
              :disabled="!canEndBreak"
              class="clock-btn break-end"
            >
              <i class="fas fa-play"></i>
              End Break
            </el-button>
          </div>
          
          <div class="quick-notes" v-if="showNotes">
            <el-input
              v-model="clockForm.notes"
              type="textarea"
              :rows="2"
              placeholder="Add notes (optional)"
              maxlength="200"
              show-word-limit
            />
          </div>
          
          <div class="action-options">
            <el-checkbox v-model="showNotes">Add Notes</el-checkbox>
            <el-checkbox v-model="clockForm.useCurrentLocation">Use Current Location</el-checkbox>
          </div>
        </div>
        
        <!-- Today's Summary -->
        <div class="today-summary" v-if="selectedEmployee && selectedEmployee.todaySummary">
          <h4>Today's Summary</h4>
          <div class="summary-grid">
            <div class="summary-item">
              <span class="label">Clock In:</span>
              <span class="value">{{ selectedEmployee.todaySummary.clockIn || '--' }}</span>
            </div>
            <div class="summary-item">
              <span class="label">Clock Out:</span>
              <span class="value">{{ selectedEmployee.todaySummary.clockOut || '--' }}</span>
            </div>
            <div class="summary-item">
              <span class="label">Break Time:</span>
              <span class="value">{{ selectedEmployee.todaySummary.breakTime || '0h 0m' }}</span>
            </div>
            <div class="summary-item">
              <span class="label">Total Hours:</span>
              <span class="value">{{ selectedEmployee.todaySummary.totalHours || '0h 0m' }}</span>
            </div>
          </div>
        </div>
      </div>
    </el-card>
    
    <!-- Recent Activities -->
    <el-card class="recent-activities">
      <template #header>
        <h4>Recent Clock Activities</h4>
      </template>
      
      <div class="activity-list">
        <div 
          v-for="activity in recentActivities" 
          :key="activity.id"
          class="activity-item"
        >
          <div class="activity-icon" :class="activity.action">
            <i :class="getActionIcon(activity.action)"></i>
          </div>
          <div class="activity-details">
            <div class="activity-employee">{{ activity.employeeName }}</div>
            <div class="activity-action">{{ activity.action }}</div>
            <div class="activity-time">{{ formatDateTime(activity.timestamp) }}</div>
          </div>
          <div class="activity-status">
            <el-tag :type="getActivityTagType(activity.status)" size="small">
              {{ activity.status }}
            </el-tag>
          </div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import attendanceAPI from '@/api/attendance'
import employeeAPI from '@/api/employees'

export default {
  name: 'QuickClockInOut',
  setup() {
    const clockFormRef = ref()
    const employees = ref([])
    const selectedEmployee = ref(null)
    const searchLoading = ref(false)
    const processing = ref(false)
    const showNotes = ref(false)
    const currentTime = ref('')
    const currentDate = ref('')
    const recentActivities = ref([])
    
    const clockForm = reactive({
      employeeId: '',
      notes: '',
      useCurrentLocation: false
    })
    
    let timeInterval = null
    
    const canClockIn = computed(() => {
      return selectedEmployee.value && 
             (!selectedEmployee.value.todayStatus || 
              selectedEmployee.value.todayStatus === 'Clocked Out')
    })
    
    const canClockOut = computed(() => {
      return selectedEmployee.value && 
             (selectedEmployee.value.todayStatus === 'Clocked In' || 
              selectedEmployee.value.todayStatus === 'On Break')
    })
    
    const canStartBreak = computed(() => {
      return selectedEmployee.value && 
             selectedEmployee.value.todayStatus === 'Clocked In'
    })
    
    const canEndBreak = computed(() => {
      return selectedEmployee.value && 
             selectedEmployee.value.todayStatus === 'On Break'
    })
    
    const updateTime = () => {
      const now = new Date()
      currentTime.value = now.toLocaleTimeString('en-US', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      })
      currentDate.value = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
    
    const searchEmployees = async (query) => {
      if (query) {
        searchLoading.value = true
        try {
          const response = await employeeAPI.searchEmployees(query)
          employees.value = response.data
        } catch (error) {
          console.error('Error searching employees:', error)
          ElMessage.error('Failed to search employees')
        } finally {
          searchLoading.value = false
        }
      }
    }
    
    const handleEmployeeSelect = async (employeeId) => {
      if (employeeId) {
        try {
          const employee = employees.value.find(emp => emp.id === employeeId)
          if (employee) {
            // Get today's attendance data
            const attendanceResponse = await attendanceAPI.getEmployeeAttendance(employeeId, {
              date: new Date().toISOString().split('T')[0]
            })
            
            selectedEmployee.value = {
              ...employee,
              todayStatus: attendanceResponse.data.status,
              lastAction: attendanceResponse.data.lastAction,
              lastActionTime: attendanceResponse.data.lastActionTime,
              todaySummary: attendanceResponse.data.summary
            }
          }
        } catch (error) {
          console.error('Error loading employee attendance:', error)
          selectedEmployee.value = employees.value.find(emp => emp.id === employeeId)
        }
      } else {
        selectedEmployee.value = null
      }
    }
    
    const clockIn = async () => {
      await processClockAction('clock_in', 'Clock In')
    }
    
    const clockOut = async () => {
      await processClockAction('clock_out', 'Clock Out')
    }
    
    const startBreak = async () => {
      await processClockAction('break_start', 'Start Break')
    }
    
    const endBreak = async () => {
      await processClockAction('break_end', 'End Break')
    }
    
    const processClockAction = async (action, actionName) => {
      try {
        processing.value = true
        
        let location = null
        if (clockForm.useCurrentLocation) {
          location = await getCurrentLocation()
        }
        
        const clockData = {
          employee_id: selectedEmployee.value.id,
          action: action,
          timestamp: new Date().toISOString(),
          notes: clockForm.notes,
          location: location,
          entry_type: 'quick_clock'
        }
        
        const response = await attendanceAPI.processQuickClock(clockData)
        
        // Update selected employee status
        selectedEmployee.value.todayStatus = response.data.newStatus
        selectedEmployee.value.lastAction = actionName
        selectedEmployee.value.lastActionTime = new Date().toLocaleTimeString('en-US', {
          hour: '2-digit',
          minute: '2-digit'
        })
        
        // Add to recent activities
        recentActivities.value.unshift({
          id: Date.now(),
          employeeName: selectedEmployee.value.name,
          action: actionName,
          status: 'Success',
          timestamp: new Date()
        })
        
        // Keep only last 10 activities
        if (recentActivities.value.length > 10) {
          recentActivities.value = recentActivities.value.slice(0, 10)
        }
        
        ElMessage.success(`${actionName} recorded successfully`)
        
        // Clear notes
        clockForm.notes = ''
        showNotes.value = false
        
      } catch (error) {
        console.error(`${actionName} error:`, error)
        
        recentActivities.value.unshift({
          id: Date.now(),
          employeeName: selectedEmployee.value.name,
          action: actionName,
          status: 'Failed',
          timestamp: new Date()
        })
        
        ElMessage.error(`Failed to record ${actionName.toLowerCase()}`)
      } finally {
        processing.value = false
      }
    }
    
    const getCurrentLocation = () => {
      return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
          reject(new Error('Geolocation not supported'))
          return
        }
        
        navigator.geolocation.getCurrentPosition(
          (position) => {
            resolve({
              latitude: position.coords.latitude,
              longitude: position.coords.longitude,
              accuracy: position.coords.accuracy
            })
          },
          (error) => {
            console.warn('Location access denied:', error)
            resolve(null)
          },
          { timeout: 10000, enableHighAccuracy: true }
        )
      })
    }
    
    const getStatusType = (status) => {
      switch (status) {
        case 'Clocked In': return 'success'
        case 'On Break': return 'warning'
        case 'Clocked Out': return 'info'
        default: return ''
      }
    }
    
    const getActionIcon = (action) => {
      switch (action) {
        case 'Clock In': return 'fas fa-sign-in-alt'
        case 'Clock Out': return 'fas fa-sign-out-alt'
        case 'Start Break': return 'fas fa-coffee'
        case 'End Break': return 'fas fa-play'
        default: return 'fas fa-clock'
      }
    }
    
    const getActivityTagType = (status) => {
      return status === 'Success' ? 'success' : 'danger'
    }
    
    const formatDateTime = (date) => {
      return new Intl.DateTimeFormat('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        month: 'short',
        day: 'numeric'
      }).format(date)
    }
    
    onMounted(() => {
      updateTime()
      timeInterval = setInterval(updateTime, 1000)
      
      // Load initial employees
      searchEmployees('')
    })
    
    onUnmounted(() => {
      if (timeInterval) {
        clearInterval(timeInterval)
      }
    })
    
    return {
      clockFormRef,
      employees,
      selectedEmployee,
      searchLoading,
      processing,
      showNotes,
      currentTime,
      currentDate,
      recentActivities,
      clockForm,
      canClockIn,
      canClockOut,
      canStartBreak,
      canEndBreak,
      searchEmployees,
      handleEmployeeSelect,
      clockIn,
      clockOut,
      startBreak,
      endBreak,
      getStatusType,
      getActionIcon,
      getActivityTagType,
      formatDateTime
    }
  }
}
</script>

<style scoped>
.quick-clock {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
  padding: 20px;
}

.card-header {
  text-align: center;
}

.card-header h3 {
  margin: 0 0 8px 0;
  color: #2c3e50;
  font-size: 20px;
  font-weight: 600;
}

.card-header p {
  margin: 0;
  color: #7f8c8d;
  font-size: 14px;
}

.clock-interface {
  text-align: center;
}

.time-display {
  margin-bottom: 30px;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  color: white;
}

.current-time h2 {
  margin: 0 0 8px 0;
  font-size: 48px;
  font-weight: 300;
  letter-spacing: 2px;
}

.current-time p {
  margin: 0;
  font-size: 18px;
  opacity: 0.9;
}

.employee-selection {
  margin-bottom: 30px;
}

.employee-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.employee-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.employee-name {
  font-weight: 600;
  color: #2c3e50;
}

.employee-id {
  font-size: 12px;
  color: #7f8c8d;
}

.employee-department {
  font-size: 12px;
  color: #3498db;
}

.selected-employee {
  margin-bottom: 30px;
}

.employee-card {
  display: flex;
  align-items: center;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 12px;
  border: 2px solid #e9ecef;
}

.employee-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #3498db;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20px;
  overflow: hidden;
}

.employee-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.employee-avatar i {
  font-size: 24px;
  color: white;
}

.employee-details {
  flex: 1;
  text-align: left;
}

.employee-details h4 {
  margin: 0 0 4px 0;
  color: #2c3e50;
  font-size: 18px;
  font-weight: 600;
}

.employee-details p {
  margin: 0 0 8px 0;
  color: #7f8c8d;
  font-size: 14px;
}

.attendance-status {
  display: flex;
  align-items: center;
  gap: 12px;
}

.last-action {
  font-size: 12px;
  color: #7f8c8d;
}

.clock-actions {
  margin-bottom: 30px;
}

.action-buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 20px;
}

.clock-btn {
  height: 60px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.clock-btn i {
  margin-right: 8px;
  font-size: 18px;
}

.clock-in {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
  border: none;
  color: white;
}

.clock-out {
  background: linear-gradient(135deg, #e67e22, #f39c12);
  border: none;
  color: white;
}

.break-start {
  background: linear-gradient(135deg, #8e44ad, #9b59b6);
  border: none;
  color: white;
}

.break-end {
  background: linear-gradient(135deg, #2980b9, #3498db);
  border: none;
  color: white;
}

.clock-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.clock-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quick-notes {
  margin-bottom: 15px;
}

.action-options {
  display: flex;
  gap: 20px;
  justify-content: center;
}

.today-summary {
  text-align: left;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.today-summary h4 {
  margin: 0 0 15px 0;
  color: #2c3e50;
  font-size: 16px;
  font-weight: 600;
}

.summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #e9ecef;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-item .label {
  color: #7f8c8d;
  font-size: 14px;
}

.summary-item .value {
  color: #2c3e50;
  font-weight: 600;
  font-size: 14px;
}

.recent-activities {
  margin-top: 0;
}

.recent-activities h4 {
  margin: 0;
  color: #2c3e50;
  font-size: 16px;
  font-weight: 600;
}

.activity-list {
  max-height: 400px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f1f2f6;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  color: white;
}

.activity-icon.Clock\ In {
  background: #27ae60;
}

.activity-icon.Clock\ Out {
  background: #e67e22;
}

.activity-icon.Start\ Break {
  background: #8e44ad;
}

.activity-icon.End\ Break {
  background: #2980b9;
}

.activity-details {
  flex: 1;
}

.activity-employee {
  font-weight: 600;
  color: #2c3e50;
  font-size: 14px;
}

.activity-action {
  color: #7f8c8d;
  font-size: 12px;
}

.activity-time {
  color: #bdc3c7;
  font-size: 11px;
}

:deep(.el-card__header) {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

:deep(.el-select) {
  width: 100%;
}

@media (max-width: 768px) {
  .quick-clock {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    grid-template-columns: 1fr;
  }
  
  .current-time h2 {
    font-size: 36px;
  }
  
  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>