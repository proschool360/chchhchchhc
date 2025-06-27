<template>
  <div class="page-container">
    <!-- Import modern CSS -->
    <!-- CSS import moved to style section -->
    
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1>Employee Management</h1>
        <p>Manage your organization's employee information and records</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary" @click="$router.push('/employees/add')">
          <el-icon><Plus /></el-icon>
          Add Employee
        </button>
      </div>
    </div>
    
    <!-- Employee Statistics -->
    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-content">
          <div class="metric-icon success">
            <el-icon><User /></el-icon>
          </div>
          <div class="metric-info">
            <h3>{{ employeeStats.total || 0 }}</h3>
            <p>Total Employees</p>
            <span class="metric-change positive">+{{ employeeStats.new_this_month || 0 }} this month</span>
          </div>
        </div>
      </div>
      
      <div class="metric-card">
        <div class="metric-content">
          <div class="metric-icon primary">
            <el-icon><OfficeBuilding /></el-icon>
          </div>
          <div class="metric-info">
            <h3>{{ departments.length || 0 }}</h3>
            <p>Departments</p>
            <span class="metric-change neutral">Active departments</span>
          </div>
        </div>
      </div>
      
      <div class="metric-card">
        <div class="metric-content">
          <div class="metric-icon warning">
            <el-icon><Briefcase /></el-icon>
          </div>
          <div class="metric-info">
            <h3>{{ positions.length || 0 }}</h3>
            <p>Positions</p>
            <span class="metric-change neutral">Available positions</span>
          </div>
        </div>
      </div>
      
      <div class="metric-card">
        <div class="metric-content">
          <div class="metric-icon success">
            <el-icon><Check /></el-icon>
          </div>
          <div class="metric-info">
            <h3>{{ employeeStats.active || 0 }}</h3>
            <p>Active Employees</p>
            <span class="metric-change positive">{{ ((employeeStats.active / employeeStats.total) * 100).toFixed(1) || 0 }}% active rate</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="filter-bar">
      <div class="filter-grid">
        <div class="form-group">
          <label class="form-label">Search Employees</label>
          <input
            v-model="filters.search"
            type="text"
            class="form-input"
            placeholder="Search by name, email, or employee ID..."
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
          <label class="form-label">Position</label>
          <select
            v-model="filters.position"
            class="form-select"
            @change="handleFilter"
          >
            <option value="">All Positions</option>
            <option
              v-for="pos in positions"
              :key="pos.id"
              :value="pos.id"
            >
              {{ pos.title }}
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
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="on_leave">On Leave</option>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">&nbsp;</label>
          <div class="flex gap-2">
            <button class="btn btn-outline" @click="resetFilters">
              <el-icon><Refresh /></el-icon>
              Reset
            </button>
            <button class="btn btn-success" @click="exportEmployees">
              <el-icon><Download /></el-icon>
              Export
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Employee Table -->
    <div class="modern-table">
      <div class="table-header">
        <h3 class="table-title">Employee List</h3>
        <div class="table-actions">
          <span class="text-sm text-gray-600">{{ pagination.total }} employees found</span>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <p>Loading employees...</p>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="error-container">
        <div class="error-title">Error Loading Employees</div>
        <p>{{ error }}</p>
        <button class="btn btn-primary mt-3" @click="fetchEmployees">
          <el-icon><Refresh /></el-icon>
          Retry
        </button>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="!employees.length" class="empty-state">
        <div class="empty-state-icon">
          <el-icon><User /></el-icon>
        </div>
        <h3 class="empty-state-title">No Employees Found</h3>
        <p class="empty-state-description">
          {{ filters.search || filters.department || filters.position || filters.status 
             ? 'No employees match your current filters.' 
             : 'Get started by adding your first employee.' }}
        </p>
        <button class="btn btn-primary" @click="$router.push('/employees/add')">
          <el-icon><Plus /></el-icon>
          Add First Employee
        </button>
      </div>
      
      <!-- Employee Cards -->
      <div v-else class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="employee in employees"
            :key="employee.id"
            class="modern-card fade-in"
          >
            <div class="card-body">
              <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xl font-bold">
                  {{ getInitials(employee.first_name, employee.last_name) }}
                </div>
                <div class="flex-1">
                  <h4 class="font-semibold text-lg text-gray-900">
                    {{ employee.first_name }} {{ employee.last_name }}
                  </h4>
                  <p class="text-gray-600 text-sm">{{ employee.position?.title || 'No Position' }}</p>
                  <p class="text-gray-500 text-xs">{{ employee.department?.name || 'No Department' }}</p>
                </div>
              </div>
              
              <div class="space-y-2 mb-4">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <el-icon><Message /></el-icon>
                  <span>{{ employee.email }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <el-icon><Phone /></el-icon>
                  <span>{{ employee.phone || 'No phone' }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <el-icon><Calendar /></el-icon>
                  <span>Joined {{ formatDate(employee.hire_date) }}</span>
                </div>
              </div>
              
              <div class="flex items-center justify-between">
                <span :class="getStatusClass(employee.status)">
                  {{ getStatusText(employee.status) }}
                </span>
                
                <div class="flex gap-2">
                  <button
                    class="btn btn-outline btn-sm"
                    @click="$router.push(`/employees/${employee.id}`)"
                  >
                    <el-icon><View /></el-icon>
                  </button>
                  <button
                    class="btn btn-primary btn-sm"
                    @click="$router.push(`/employees/${employee.id}/edit`)"
                  >
                    <el-icon><Edit /></el-icon>
                  </button>
                  <button
                    class="btn btn-danger btn-sm"
                    @click="confirmDelete(employee)"
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
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { ElMessageBox, ElMessage } from 'element-plus'
import {
  Plus,
  Search,
  Refresh,
  Download,
  View,
  Edit,
  Delete,
  User,
  OfficeBuilding,
  Briefcase,
  Check,
  Message,
  Phone,
  Calendar
} from '@element-plus/icons-vue'

export default {
  name: 'EmployeeList',
  components: {
    Plus,
    Search,
    Refresh,
    Download,
    View,
    Edit,
    Delete,
    User,
    OfficeBuilding,
    Briefcase,
    Check,
    Message,
    Phone,
    Calendar
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    
    const filters = reactive({
      search: '',
      department: '',
      position: '',
      status: ''
    })
    
    const pagination = reactive({
      currentPage: 1,
      pageSize: 20,
      total: 0
    })
    
    const error = ref(null)
    
    const employees = computed(() => store.getters['employees/employees'] || [])
    const departments = computed(() => store.getters['employees/departments'] || [])
    const positions = computed(() => store.getters['employees/positions'] || [])
    const loading = computed(() => store.getters['employees/loading'])
    
    const employeeStats = computed(() => {
      const total = employees.value.length
      const active = employees.value.filter(emp => emp.status === 'active').length
      return {
        total,
        active,
        new_this_month: employees.value.filter(emp => {
          const hireDate = new Date(emp.hire_date)
          const now = new Date()
          return hireDate.getMonth() === now.getMonth() && hireDate.getFullYear() === now.getFullYear()
        }).length
      }
    })
    
    const getInitials = (firstName, lastName) => {
      return `${firstName?.charAt(0) || ''}${lastName?.charAt(0) || ''}`.toUpperCase()
    }
    
    const formatDate = (date) => {
      if (!date) return 'Unknown'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const getStatusClass = (status) => {
      const statusMap = {
        active: 'status-badge status-active',
        inactive: 'status-badge status-inactive',
        on_leave: 'status-badge status-pending'
      }
      return statusMap[status] || 'status-badge status-inactive'
    }
    
    const getStatusText = (status) => {
      const statusMap = {
        active: 'Active',
        inactive: 'Inactive',
        on_leave: 'On Leave'
      }
      return statusMap[status] || status
    }
    
    const fetchEmployees = async () => {
      try {
        error.value = null
        const params = {
          page: pagination.currentPage,
          limit: pagination.pageSize,
          search: filters.search,
          department: filters.department,
          position: filters.position,
          status: filters.status
        }
        
        const result = await store.dispatch('employees/fetchEmployees', params)
        if (!result.success) {
          error.value = result.message || 'Failed to load employees'
        } else if (result.pagination) {
          pagination.total = result.pagination.total
        }
      } catch (err) {
        error.value = 'An unexpected error occurred'
        console.error('Fetch employees error:', err)
      }
    }
    
    const handleSearch = () => {
      pagination.currentPage = 1
      fetchEmployees()
    }
    
    const handleFilter = () => {
      pagination.currentPage = 1
      fetchEmployees()
    }
    
    const handleSizeChange = (size) => {
      pagination.pageSize = size
      pagination.currentPage = 1
      fetchEmployees()
    }
    
    const handleCurrentChange = (page) => {
      pagination.currentPage = page
      fetchEmployees()
    }
    
    const resetFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      pagination.currentPage = 1
      fetchEmployees()
    }
    
    const exportEmployees = () => {
      ElMessage.info('Export functionality will be implemented soon')
    }
    
    const confirmDelete = async (employee) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to delete ${employee.first_name} ${employee.last_name}?`,
          'Confirm Delete',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        
        const result = await store.dispatch('employees/deleteEmployee', employee.id)
        if (result.success) {
          ElMessage.success('Employee deleted successfully')
          fetchEmployees()
        } else {
          ElMessage.error(result.message || 'Failed to delete employee')
        }
      } catch (err) {
        if (err !== 'cancel') {
          ElMessage.error('An error occurred while deleting employee')
        }
      }
    }
    
    onMounted(async () => {
      await Promise.all([
        store.dispatch('employees/fetchDepartments'),
        store.dispatch('employees/fetchPositions'),
        fetchEmployees()
      ])
    })
    
    return {
      filters,
      pagination,
      employees,
      departments,
      positions,
      employeeStats,
      loading,
      error,
      getInitials,
      formatDate,
      getStatusClass,
      getStatusText,
      handleSearch,
      handleFilter,
      handleSizeChange,
      handleCurrentChange,
      resetFilters,
      exportEmployees,
      confirmDelete,
      fetchEmployees
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

.space-y-2 > * + * {
  margin-top: 0.5rem;
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

.font-semibold {
  font-weight: 600;
}

.font-bold {
  font-weight: 700;
}

.bg-gradient-to-br {
  background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

.from-blue-500 {
  --tw-gradient-from: #4299e1;
  --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(66, 153, 225, 0));
}

.to-purple-600 {
  --tw-gradient-to: #805ad5;
}
</style>