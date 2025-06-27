<template>
  <div class="department-management">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="fas fa-building"></i>
          Department Management
        </h1>
        <p class="page-description">Manage organizational departments and their hierarchies</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" @click="showCreateModal = true">
          <i class="fas fa-plus"></i>
          Add Department
        </el-button>
      </div>
    </div>

    <!-- Department Statistics -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon primary">
          <i class="fas fa-building"></i>
        </div>
        <div class="stat-content">
          <h3>{{ departments.length }}</h3>
          <p>Total Departments</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon success">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ activeDepartments.length }}</h3>
          <p>Active Departments</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon warning">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
          <h3>{{ totalEmployees }}</h3>
          <p>Total Employees</p>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
      <div class="search-box">
        <el-input
          v-model="searchQuery"
          placeholder="Search departments..."
          prefix-icon="el-icon-search"
          clearable
        />
      </div>
      <div class="filter-controls">
        <el-select v-model="statusFilter" placeholder="All Status" clearable>
          <el-option label="All Status" value="" />
          <el-option label="Active" value="active" />
          <el-option label="Inactive" value="inactive" />
        </el-select>
      </div>
    </div>

    <!-- Departments Table -->
    <div class="table-container">
      <el-table
        :data="filteredDepartments"
        v-loading="loading"
        stripe
        style="width: 100%"
      >
        <el-table-column prop="name" label="Department Name" sortable>
          <template #default="scope">
            <div class="department-info">
              <strong>{{ scope.row.name }}</strong>
              <small v-if="scope.row.code">{{ scope.row.code }}</small>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column prop="description" label="Description" show-overflow-tooltip />
        
        <el-table-column prop="manager_name" label="Manager" width="150">
          <template #default="scope">
            <span v-if="scope.row.manager_name">{{ scope.row.manager_name }}</span>
            <span v-else class="text-muted">No Manager</span>
          </template>
        </el-table-column>
        
        <el-table-column prop="employee_count" label="Employees" width="100" align="center">
          <template #default="scope">
            <el-tag size="small">{{ scope.row.employee_count || 0 }}</el-tag>
          </template>
        </el-table-column>
        
        <el-table-column prop="status" label="Status" width="100" align="center">
          <template #default="scope">
            <el-tag
              :type="scope.row.status === 'active' ? 'success' : 'danger'"
              size="small"
            >
              {{ scope.row.status }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column prop="created_at" label="Created" width="120" sortable>
          <template #default="scope">
            {{ formatDate(scope.row.created_at) }}
          </template>
        </el-table-column>
        
        <el-table-column label="Actions" width="150" align="center">
          <template #default="scope">
            <el-button
              type="primary"
              size="small"
              @click="editDepartment(scope.row)"
            >
              <i class="fas fa-edit"></i>
            </el-button>
            <el-button
              type="danger"
              size="small"
              @click="deleteDepartment(scope.row)"
              :disabled="scope.row.employee_count > 0"
            >
              <i class="fas fa-trash"></i>
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Create/Edit Department Modal -->
    <el-dialog
      :title="editingDepartment ? 'Edit Department' : 'Create Department'"
      v-model="showCreateModal"
      width="500px"
      @close="resetForm"
    >
      <el-form
        ref="departmentFormRef"
        :model="departmentForm"
        :rules="formRules"
        label-width="120px"
      >
        <el-form-item label="Name" prop="name">
          <el-input v-model="departmentForm.name" placeholder="Enter department name" />
        </el-form-item>
        
        <el-form-item label="Code" prop="code">
          <el-input v-model="departmentForm.code" placeholder="Enter department code (optional)" />
        </el-form-item>
        
        <el-form-item label="Description" prop="description">
          <el-input
            v-model="departmentForm.description"
            type="textarea"
            :rows="3"
            placeholder="Enter department description"
          />
        </el-form-item>
        
        <el-form-item label="Manager" prop="manager_id">
          <el-select
            v-model="departmentForm.manager_id"
            placeholder="Select department manager (optional)"
            clearable
            filterable
            style="width: 100%"
          >
            <el-option
              v-for="employee in employees"
              :key="employee.id"
              :label="employee.full_name"
              :value="employee.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Status" prop="status">
          <el-select v-model="departmentForm.status" style="width: 100%">
            <el-option label="Active" value="active" />
            <el-option label="Inactive" value="inactive" />
          </el-select>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showCreateModal = false">Cancel</el-button>
          <el-button type="primary" @click="saveDepartment" :loading="saving">
            {{ editingDepartment ? 'Update' : 'Create' }}
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { ElMessage, ElMessageBox } from 'element-plus'
import { formatDate } from '@/utils/dateUtils'

const store = useStore()

// Reactive data
const loading = ref(false)
const saving = ref(false)
const showCreateModal = ref(false)
const editingDepartment = ref(null)
const searchQuery = ref('')
const statusFilter = ref('')
const departmentFormRef = ref(null)
const managers = ref([])

// Form data
const departmentForm = ref({
  name: '',
  code: '',
  description: '',
  manager_id: '',
  status: 'active'
})

// Form validation rules
const formRules = {
  name: [
    { required: true, message: 'Department name is required', trigger: 'blur' },
    { min: 2, max: 100, message: 'Name should be 2-100 characters', trigger: 'blur' }
  ],
  code: [
    { max: 20, message: 'Code should not exceed 20 characters', trigger: 'blur' }
  ],
  status: [
    { required: true, message: 'Status is required', trigger: 'change' }
  ]
}

// Computed properties
const departments = computed(() => store.getters['departments/allDepartments'])
const employees = computed(() => managers.value)

const activeDepartments = computed(() => 
  departments.value.filter(dept => dept.status === 'active')
)

const totalEmployees = computed(() => 
  departments.value.reduce((total, dept) => total + (dept.employee_count || 0), 0)
)

const filteredDepartments = computed(() => {
  let filtered = departments.value
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(dept => 
      dept.name.toLowerCase().includes(query) ||
      dept.code?.toLowerCase().includes(query) ||
      dept.description?.toLowerCase().includes(query)
    )
  }
  
  if (statusFilter.value) {
    filtered = filtered.filter(dept => dept.status === statusFilter.value)
  }
  
  return filtered
})

// Methods
const loadDepartments = async () => {
  try {
    loading.value = true
    await store.dispatch('departments/fetchDepartments')
  } catch (error) {
    ElMessage.error('Failed to load departments')
  } finally {
    loading.value = false
  }
}

const loadManagers = async () => {
  try {
    const managersData = await store.dispatch('departments/fetchManagers')
    managers.value = managersData
  } catch (error) {
    console.error('Failed to load managers:', error)
    ElMessage.error('Failed to load managers')
  }
}

const editDepartment = (department) => {
  editingDepartment.value = department
  departmentForm.value = {
    name: department.name,
    code: department.code || '',
    description: department.description || '',
    manager_id: department.manager_id || '',
    status: department.status
  }
  showCreateModal.value = true
}

const saveDepartment = async () => {
  try {
    const valid = await departmentFormRef.value.validate()
    if (!valid) return
    
    saving.value = true
    
    if (editingDepartment.value) {
      await store.dispatch('departments/updateDepartment', {
        id: editingDepartment.value.id,
        data: departmentForm.value
      })
      ElMessage.success('Department updated successfully')
    } else {
      await store.dispatch('departments/createDepartment', departmentForm.value)
      ElMessage.success('Department created successfully')
    }
    
    showCreateModal.value = false
    resetForm()
    loadDepartments()
  } catch (error) {
    ElMessage.error(error.message || 'Failed to save department')
  } finally {
    saving.value = false
  }
}

const deleteDepartment = async (department) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete "${department.name}"?`,
      'Confirm Delete',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await store.dispatch('departments/deleteDepartment', department.id)
    ElMessage.success('Department deleted successfully')
    loadDepartments()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to delete department')
    }
  }
}

const resetForm = () => {
  editingDepartment.value = null
  departmentForm.value = {
    name: '',
    code: '',
    description: '',
    manager_id: '',
    status: 'active'
  }
  departmentFormRef.value?.clearValidate()
}

// Lifecycle
onMounted(() => {
  loadDepartments()
  loadManagers()
})
</script>

<style scoped>
.department-management {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e4e7ed;
}

.header-content h1 {
  margin: 0;
  color: #303133;
  font-size: 24px;
  font-weight: 600;
}

.header-content p {
  margin: 4px 0 0 0;
  color: #606266;
  font-size: 14px;
}

.page-title i {
  margin-right: 8px;
  color: #409eff;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: white;
}

.stat-icon.primary { background: #409eff; }
.stat-icon.success { background: #67c23a; }
.stat-icon.warning { background: #e6a23c; }

.stat-content h3 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  color: #303133;
}

.stat-content p {
  margin: 4px 0 0 0;
  color: #606266;
  font-size: 14px;
}

.filters-section {
  background: white;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  gap: 16px;
  align-items: center;
}

.search-box {
  flex: 1;
  max-width: 300px;
}

.filter-controls {
  display: flex;
  gap: 12px;
}

.table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.department-info strong {
  display: block;
  color: #303133;
}

.department-info small {
  color: #909399;
  font-size: 12px;
}

.text-muted {
  color: #909399;
  font-style: italic;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>