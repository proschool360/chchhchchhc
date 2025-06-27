<template>
  <div class="employee-detail">
    <div class="page-header">
      <div class="header-content">
        <h1>Employee Details</h1>
        <p>View comprehensive employee information</p>
      </div>
      <div class="header-actions">
        <el-button @click="$router.go(-1)">Back</el-button>
        <el-button type="primary" @click="editEmployee">
          Edit Employee
        </el-button>
      </div>
    </div>

    <div v-if="loading" class="loading-container">
      <el-skeleton :rows="10" animated />
    </div>

    <div v-else-if="employee" class="employee-content">
      <el-row :gutter="24">
        <!-- Employee Overview -->
        <el-col :xs="24" :lg="8">
          <el-card class="employee-overview" shadow="never">
            <div class="employee-avatar-section">
              <el-avatar
                :size="120"
                :src="employee.avatar"
                :alt="employee.name"
                class="employee-avatar"
              >
                {{ employee.name?.charAt(0).toUpperCase() }}
              </el-avatar>
              <div class="employee-basic-info">
                <h2>{{ employee.name }}</h2>
                <p class="employee-id">ID: {{ employee.employee_id }}</p>
                <el-tag
                  :type="getStatusType(employee.status)"
                  size="large"
                >
                  {{ getStatusText(employee.status) }}
                </el-tag>
              </div>
            </div>
            
            <div class="quick-info">
              <div class="info-item">
                <span class="label">Department:</span>
                <span class="value">{{ employee.department }}</span>
              </div>
              <div class="info-item">
                <span class="label">Position:</span>
                <span class="value">{{ employee.position }}</span>
              </div>
              <div class="info-item">
                <span class="label">Join Date:</span>
                <span class="value">{{ formatDate(employee.join_date) }}</span>
              </div>
              <div class="info-item">
                <span class="label">Employment Type:</span>
                <span class="value">{{ formatEmploymentType(employee.employment_type) }}</span>
              </div>
            </div>
          </el-card>
        </el-col>
        
        <!-- Detailed Information -->
        <el-col :xs="24" :lg="16">
          <el-tabs v-model="activeTab" class="employee-tabs">
            <!-- Personal Information -->
            <el-tab-pane label="Personal Info" name="personal">
              <el-card shadow="never">
                <el-descriptions :column="2" border>
                  <el-descriptions-item label="Full Name">
                    {{ employee.name }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Email">
                    <el-link :href="`mailto:${employee.email}`" type="primary">
                      {{ employee.email }}
                    </el-link>
                  </el-descriptions-item>
                  <el-descriptions-item label="Phone">
                    <el-link :href="`tel:${employee.phone}`" type="primary">
                      {{ employee.phone }}
                    </el-link>
                  </el-descriptions-item>
                  <el-descriptions-item label="Date of Birth">
                    {{ formatDate(employee.date_of_birth) }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Gender">
                    {{ formatGender(employee.gender) }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Address" :span="2">
                    {{ employee.address || 'Not provided' }}
                  </el-descriptions-item>
                </el-descriptions>
              </el-card>
            </el-tab-pane>
            
            <!-- Employment Information -->
            <el-tab-pane label="Employment" name="employment">
              <el-card shadow="never">
                <el-descriptions :column="2" border>
                  <el-descriptions-item label="Employee ID">
                    {{ employee.employee_id }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Join Date">
                    {{ formatDate(employee.join_date) }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Department">
                    {{ employee.department }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Position">
                    {{ employee.position }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Employment Type">
                    {{ formatEmploymentType(employee.employment_type) }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Status">
                    <el-tag :type="getStatusType(employee.status)">
                      {{ getStatusText(employee.status) }}
                    </el-tag>
                  </el-descriptions-item>
                  <el-descriptions-item label="Salary">
                    {{ formatCurrency(employee.salary) }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Manager">
                    {{ employee.manager_name || 'Not assigned' }}
                  </el-descriptions-item>
                </el-descriptions>
              </el-card>
            </el-tab-pane>
            
            <!-- Emergency Contact -->
            <el-tab-pane label="Emergency Contact" name="emergency">
              <el-card shadow="never">
                <el-descriptions :column="2" border>
                  <el-descriptions-item label="Contact Name">
                    {{ employee.emergency_contact_name || 'Not provided' }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Relationship">
                    {{ employee.emergency_contact_relationship || 'Not provided' }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Phone Number">
                    <el-link 
                      v-if="employee.emergency_contact_phone"
                      :href="`tel:${employee.emergency_contact_phone}`" 
                      type="primary"
                    >
                      {{ employee.emergency_contact_phone }}
                    </el-link>
                    <span v-else>Not provided</span>
                  </el-descriptions-item>
                </el-descriptions>
              </el-card>
            </el-tab-pane>
            
            <!-- Recent Activities -->
            <el-tab-pane label="Activities" name="activities">
              <el-card shadow="never">
                <el-timeline>
                  <el-timeline-item
                    v-for="activity in activities"
                    :key="activity.id"
                    :timestamp="formatDateTime(activity.created_at)"
                    placement="top"
                  >
                    <el-card shadow="never" class="activity-card">
                      <div class="activity-content">
                        <h4>{{ activity.title }}</h4>
                        <p>{{ activity.description }}</p>
                        <el-tag size="small" :type="getActivityType(activity.type)">
                          {{ activity.type }}
                        </el-tag>
                      </div>
                    </el-card>
                  </el-timeline-item>
                </el-timeline>
                
                <div v-if="!activities.length" class="no-activities">
                  <el-empty description="No recent activities" />
                </div>
              </el-card>
            </el-tab-pane>
          </el-tabs>
        </el-col>
      </el-row>
    </div>
    
    <div v-else class="error-container">
      <el-result
        icon="warning"
        title="Employee Not Found"
        sub-title="The requested employee could not be found."
      >
        <template #extra>
          <el-button type="primary" @click="$router.push('/employees')">
            Back to Employee List
          </el-button>
        </template>
      </el-result>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'

export default {
  name: 'EmployeeDetail',
  setup() {
    const store = useStore()
    const router = useRouter()
    const route = useRoute()
    const activeTab = ref('personal')
    
    const employee = computed(() => store.getters['employees/currentEmployee'])
    const loading = computed(() => store.getters['employees/loading'])
    const activities = computed(() => store.getters['employees/employeeActivities'] || [])
    
    const formatDate = (date) => {
      if (!date) return 'Not provided'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
    
    const formatDateTime = (datetime) => {
      if (!datetime) return ''
      return new Date(datetime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
    
    const formatCurrency = (amount) => {
      if (!amount) return 'Not specified'
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(amount)
    }
    
    const formatGender = (gender) => {
      const genderMap = {
        male: 'Male',
        female: 'Female',
        other: 'Other'
      }
      return genderMap[gender] || 'Not specified'
    }
    
    const formatEmploymentType = (type) => {
      const typeMap = {
        full_time: 'Full-time',
        part_time: 'Part-time',
        contract: 'Contract',
        intern: 'Intern'
      }
      return typeMap[type] || type
    }
    
    const getStatusType = (status) => {
      const statusMap = {
        active: 'success',
        inactive: 'danger',
        on_leave: 'warning'
      }
      return statusMap[status] || 'info'
    }
    
    const getStatusText = (status) => {
      const statusMap = {
        active: 'Active',
        inactive: 'Inactive',
        on_leave: 'On Leave'
      }
      return statusMap[status] || status
    }
    
    const getActivityType = (type) => {
      const typeMap = {
        created: 'success',
        updated: 'primary',
        deleted: 'danger',
        promoted: 'warning',
        transferred: 'info'
      }
      return typeMap[type] || 'info'
    }
    
    const editEmployee = () => {
      router.push(`/employees/${route.params.id}/edit`)
    }
    
    const loadEmployee = async () => {
      try {
        const result = await store.dispatch('employees/fetchEmployee', route.params.id)
        if (!result.success) {
          ElMessage.error('Failed to load employee details')
        }
      } catch (error) {
        console.error('Error loading employee:', error)
        ElMessage.error('An error occurred while loading employee details')
      }
    }
    
    onMounted(() => {
      loadEmployee()
    })
    
    return {
      activeTab,
      employee,
      loading,
      activities,
      formatDate,
      formatDateTime,
      formatCurrency,
      formatGender,
      formatEmploymentType,
      getStatusType,
      getStatusText,
      getActivityType,
      editEmployee
    }
  }
}
</script>

<style scoped>
.employee-detail {
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

.loading-container,
.error-container {
  margin-top: 40px;
}

.employee-overview {
  border: 1px solid #EBEEF5;
}

.employee-avatar-section {
  text-align: center;
  padding-bottom: 20px;
  border-bottom: 1px solid #EBEEF5;
  margin-bottom: 20px;
}

.employee-avatar {
  margin-bottom: 16px;
}

.employee-basic-info h2 {
  margin: 0 0 8px 0;
  color: #303133;
  font-size: 20px;
  font-weight: 600;
}

.employee-id {
  margin: 0 0 12px 0;
  color: #909399;
  font-size: 14px;
}

.quick-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-item .label {
  color: #606266;
  font-weight: 500;
}

.info-item .value {
  color: #303133;
  font-weight: 600;
}

.employee-tabs {
  border: 1px solid #EBEEF5;
  border-radius: 4px;
}

.employee-tabs :deep(.el-tabs__header) {
  margin: 0;
  background-color: #FAFAFA;
  border-bottom: 1px solid #EBEEF5;
}

.employee-tabs :deep(.el-tabs__content) {
  padding: 20px;
}

.activity-card {
  border: 1px solid #EBEEF5;
  margin-bottom: 8px;
}

.activity-content h4 {
  margin: 0 0 8px 0;
  color: #303133;
  font-size: 14px;
  font-weight: 600;
}

.activity-content p {
  margin: 0 0 8px 0;
  color: #606266;
  font-size: 13px;
}

.no-activities {
  text-align: center;
  padding: 40px 0;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
  color: #606266;
}

:deep(.el-descriptions__content) {
  color: #303133;
}

@media (max-width: 768px) {
  .employee-detail {
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
  
  .employee-avatar {
    width: 80px;
    height: 80px;
  }
  
  .employee-basic-info h2 {
    font-size: 18px;
  }
  
  :deep(.el-descriptions) {
    --el-descriptions-item-bordered-label-background: #FAFAFA;
  }
}
</style>