<template>
  <div class="quick-employee-select">
    <el-page-header @back="$router.go(-1)" content="Quick Employee Select" />
    
    <div class="content-wrapper">
      <el-row :gutter="20">
        <!-- Employee Search & Selection -->
        <el-col :span="12">
          <el-card class="search-card">
            <template #header>
              <div class="card-header">
                <el-icon><Search /></el-icon>
                <span>Find Employee</span>
              </div>
            </template>
            
            <div class="search-section">
              <EmployeeDropdown 
                v-model="selectedEmployeeId" 
                placeholder="Search by name or employee ID..."
                @change="handleEmployeeSelect"
              />
              
              <div v-if="selectedEmployee" class="employee-info">
                <el-divider content-position="left">Employee Details</el-divider>
                <div class="employee-details">
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="Name">
                      {{ selectedEmployee.name }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Employee ID">
                      {{ selectedEmployee.employee_id }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Department">
                      {{ selectedEmployee.department || 'N/A' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Position">
                      {{ selectedEmployee.position || 'N/A' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Status">
                      <el-tag :type="getStatusType(selectedEmployee.status)">
                        {{ selectedEmployee.status || 'Active' }}
                      </el-tag>
                    </el-descriptions-item>
                  </el-descriptions>
                </div>
              </div>
            </div>
          </el-card>
        </el-col>
        
        <!-- Quick Actions -->
        <el-col :span="12">
          <el-card class="actions-card">
            <template #header>
              <div class="card-header">
                <el-icon><Operation /></el-icon>
                <span>Quick Actions</span>
              </div>
            </template>
            
            <div class="actions-section">
              <div v-if="!selectedEmployee" class="no-selection">
                <el-empty description="Select an employee to see available actions" />
              </div>
              
              <div v-else class="action-buttons">
                <el-button 
                  type="primary" 
                  :icon="View" 
                  @click="viewEmployeeDetails"
                  class="action-btn"
                >
                  View Details
                </el-button>
                
                <el-button 
                  type="success" 
                  :icon="Edit" 
                  @click="editEmployee"
                  class="action-btn"
                >
                  Edit Employee
                </el-button>
                
                <el-button 
                  type="info" 
                  :icon="Clock" 
                  @click="viewAttendance"
                  class="action-btn"
                >
                  View Attendance
                </el-button>
                
                <el-button 
                  type="warning" 
                  :icon="Document" 
                  @click="manageLeave"
                  class="action-btn"
                >
                  Manage Leave
                </el-button>
                
                <el-button 
                  type="primary" 
                  :icon="CreditCard" 
                  @click="generateIdCard"
                  class="action-btn"
                >
                  Generate ID Card
                </el-button>
                
                <el-button 
                  type="info" 
                  :icon="Money" 
                  @click="viewPayroll"
                  class="action-btn"
                >
                  View Payroll
                </el-button>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
      
      <!-- Recent Employees -->
      <el-row class="recent-section">
        <el-col :span="24">
          <el-card>
            <template #header>
              <div class="card-header">
                <el-icon><Clock /></el-icon>
                <span>Recently Viewed Employees</span>
                <el-button 
                  type="text" 
                  size="small" 
                  @click="clearRecentEmployees"
                  v-if="recentEmployees.length > 0"
                >
                  Clear All
                </el-button>
              </div>
            </template>
            
            <div v-if="recentEmployees.length === 0" class="no-recent">
              <el-empty description="No recently viewed employees" />
            </div>
            
            <div v-else class="recent-employees">
              <el-row :gutter="16">
                <el-col 
                  :span="6" 
                  v-for="employee in recentEmployees" 
                  :key="employee.id"
                  class="recent-employee-item"
                >
                  <el-card 
                    shadow="hover" 
                    class="employee-card"
                    @click="selectRecentEmployee(employee)"
                  >
                    <div class="employee-avatar">
                      <el-avatar :size="40" :icon="UserFilled" />
                    </div>
                    <div class="employee-info-compact">
                      <div class="employee-name">{{ employee.name }}</div>
                      <div class="employee-id">{{ employee.employee_id }}</div>
                      <div class="employee-dept">{{ employee.department || 'N/A' }}</div>
                    </div>
                  </el-card>
                </el-col>
              </el-row>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  Search, 
  Operation, 
  View, 
  Edit, 
  Clock, 
  Document, 
  CreditCard, 
  Money, 
  UserFilled 
} from '@element-plus/icons-vue'
import EmployeeDropdown from '@/components/EmployeeDropdown.vue'

export default {
  name: 'QuickEmployeeSelect',
  components: {
    EmployeeDropdown
  },
  setup() {
    const router = useRouter()
    const selectedEmployeeId = ref(null)
    const selectedEmployee = ref(null)
    const recentEmployees = ref([])
    
    // Load recent employees from localStorage
    const loadRecentEmployees = () => {
      const stored = localStorage.getItem('recentEmployees')
      if (stored) {
        recentEmployees.value = JSON.parse(stored)
      }
    }
    
    // Save recent employees to localStorage
    const saveRecentEmployees = () => {
      localStorage.setItem('recentEmployees', JSON.stringify(recentEmployees.value))
    }
    
    // Add employee to recent list
    const addToRecentEmployees = (employee) => {
      // Remove if already exists
      const existingIndex = recentEmployees.value.findIndex(emp => emp.id === employee.id)
      if (existingIndex > -1) {
        recentEmployees.value.splice(existingIndex, 1)
      }
      
      // Add to beginning
      recentEmployees.value.unshift(employee)
      
      // Keep only last 8 employees
      if (recentEmployees.value.length > 8) {
        recentEmployees.value = recentEmployees.value.slice(0, 8)
      }
      
      saveRecentEmployees()
    }
    
    const handleEmployeeSelect = (employeeId, employeeData) => {
      selectedEmployee.value = employeeData
      if (employeeData) {
        addToRecentEmployees(employeeData)
        ElMessage.success(`Selected: ${employeeData.name}`)
      }
    }
    
    const selectRecentEmployee = (employee) => {
      selectedEmployeeId.value = employee.id
      selectedEmployee.value = employee
      ElMessage.info(`Selected: ${employee.name}`)
    }
    
    const clearRecentEmployees = async () => {
      try {
        await ElMessageBox.confirm(
          'This will clear all recently viewed employees. Continue?',
          'Clear Recent Employees',
          {
            confirmButtonText: 'Clear',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        recentEmployees.value = []
        saveRecentEmployees()
        ElMessage.success('Recent employees cleared')
      } catch {
        // User cancelled
      }
    }
    
    const getStatusType = (status) => {
      switch (status?.toLowerCase()) {
        case 'active': return 'success'
        case 'inactive': return 'danger'
        case 'on_leave': return 'warning'
        default: return 'success'
      }
    }
    
    // Quick action methods
    const viewEmployeeDetails = () => {
      if (selectedEmployee.value) {
        router.push(`/employees/${selectedEmployee.value.id}`)
      }
    }
    
    const editEmployee = () => {
      if (selectedEmployee.value) {
        router.push(`/employees/${selectedEmployee.value.id}/edit`)
      }
    }
    
    const viewAttendance = () => {
      if (selectedEmployee.value) {
        router.push(`/attendance?employee_id=${selectedEmployee.value.id}`)
      }
    }
    
    const manageLeave = () => {
      if (selectedEmployee.value) {
        router.push(`/leave?employee_id=${selectedEmployee.value.id}`)
      }
    }
    
    const generateIdCard = () => {
      if (selectedEmployee.value) {
        router.push(`/idcards/management?employee_id=${selectedEmployee.value.id}`)
      }
    }
    
    const viewPayroll = () => {
      if (selectedEmployee.value) {
        router.push(`/payroll?employee_id=${selectedEmployee.value.id}`)
      }
    }
    
    onMounted(() => {
      loadRecentEmployees()
    })
    
    return {
      selectedEmployeeId,
      selectedEmployee,
      recentEmployees,
      handleEmployeeSelect,
      selectRecentEmployee,
      clearRecentEmployees,
      getStatusType,
      viewEmployeeDetails,
      editEmployee,
      viewAttendance,
      manageLeave,
      generateIdCard,
      viewPayroll,
      // Icons
      Search,
      Operation,
      View,
      Edit,
      Clock,
      Document,
      CreditCard,
      Money,
      UserFilled
    }
  }
}
</script>

<style scoped>
.quick-employee-select {
  padding: 20px;
}

.content-wrapper {
  margin-top: 20px;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.search-card,
.actions-card {
  height: 100%;
  min-height: 400px;
}

.search-section {
  padding: 10px 0;
}

.employee-info {
  margin-top: 20px;
}

.employee-details {
  margin-top: 15px;
}

.actions-section {
  padding: 10px 0;
}

.no-selection {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 200px;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.action-btn {
  width: 100%;
  height: 45px;
  font-size: 14px;
  justify-content: flex-start;
}

.recent-section {
  margin-top: 20px;
}

.no-recent {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 150px;
}

.recent-employees {
  padding: 10px 0;
}

.recent-employee-item {
  margin-bottom: 16px;
}

.employee-card {
  cursor: pointer;
  transition: all 0.3s ease;
  height: 120px;
}

.employee-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.employee-card .el-card__body {
  padding: 15px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  height: 100%;
}

.employee-avatar {
  margin-bottom: 10px;
}

.employee-info-compact {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.employee-name {
  font-weight: 600;
  font-size: 14px;
  color: #303133;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.employee-id {
  font-size: 12px;
  color: #909399;
  margin-bottom: 2px;
}

.employee-dept {
  font-size: 11px;
  color: #C0C4CC;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 768px) {
  .recent-employee-item {
    margin-bottom: 12px;
  }
  
  .employee-card {
    height: 100px;
  }
  
  .action-btn {
    height: 40px;
    font-size: 13px;
  }
}
</style>