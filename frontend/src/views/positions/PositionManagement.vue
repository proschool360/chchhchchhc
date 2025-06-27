<template>
  <div class="position-management">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="fas fa-briefcase"></i>
          Position Management
        </h1>
        <p class="page-description">Manage job positions and their requirements across departments</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" @click="showCreateModal = true">
          <i class="fas fa-plus"></i>
          Add Position
        </el-button>
      </div>
    </div>

    <!-- Position Statistics -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon primary">
          <i class="fas fa-briefcase"></i>
        </div>
        <div class="stat-content">
          <h3>{{ positions.length }}</h3>
          <p>Total Positions</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon success">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ activePositions.length }}</h3>
          <p>Active Positions</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon warning">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
          <h3>{{ totalEmployees }}</h3>
          <p>Filled Positions</p>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
      <div class="search-box">
        <el-input
          v-model="searchQuery"
          placeholder="Search positions..."
          prefix-icon="el-icon-search"
          clearable
        />
      </div>
      <div class="filter-controls">
        <el-select v-model="departmentFilter" placeholder="All Departments" clearable>
          <el-option label="All Departments" value="" />
          <el-option
            v-for="dept in departments"
            :key="dept.id"
            :label="dept.name"
            :value="dept.id"
          />
        </el-select>
        <el-select v-model="statusFilter" placeholder="All Status" clearable>
          <el-option label="All Status" value="" />
          <el-option label="Active" value="active" />
          <el-option label="Inactive" value="inactive" />
        </el-select>
      </div>
    </div>

    <!-- Positions Table -->
    <div class="table-container">
      <el-table
        :data="filteredPositions"
        v-loading="loading"
        stripe
        style="width: 100%"
      >
        <el-table-column prop="title" label="Position Title" sortable>
          <template #default="scope">
            <div class="position-info">
              <strong>{{ scope.row.title }}</strong>
              <small v-if="scope.row.code">{{ scope.row.code }}</small>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column prop="department_name" label="Department" width="150" sortable>
          <template #default="scope">
            <el-tag size="small" type="info">{{ scope.row.department_name }}</el-tag>
          </template>
        </el-table-column>
        
        <el-table-column prop="level" label="Level" width="100" align="center">
          <template #default="scope">
            <el-tag
              size="small"
              :type="getLevelType(scope.row.level)"
            >
              {{ scope.row.level }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column prop="salary_range" label="Salary Range" width="150">
          <template #default="scope">
            <span v-if="scope.row.min_salary && scope.row.max_salary">
              ${{ formatNumber(scope.row.min_salary) }} - ${{ formatNumber(scope.row.max_salary) }}
            </span>
            <span v-else class="text-muted">Not specified</span>
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
              @click="editPosition(scope.row)"
            >
              <i class="fas fa-edit"></i>
            </el-button>
            <el-button
              type="danger"
              size="small"
              @click="deletePosition(scope.row)"
              :disabled="scope.row.employee_count > 0"
            >
              <i class="fas fa-trash"></i>
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Create/Edit Position Modal -->
    <el-dialog
      :title="editingPosition ? 'Edit Position' : 'Create Position'"
      v-model="showCreateModal"
      width="600px"
      @close="resetForm"
    >
      <el-form
        ref="positionFormRef"
        :model="positionForm"
        :rules="formRules"
        label-width="140px"
      >
        <el-form-item label="Position Title" prop="title">
          <el-input v-model="positionForm.title" placeholder="Enter position title" />
        </el-form-item>
        
        <el-form-item label="Position Code" prop="code">
          <el-input v-model="positionForm.code" placeholder="Enter position code (optional)" />
        </el-form-item>
        
        <el-form-item label="Department" prop="department_id">
          <el-select v-model="positionForm.department_id" placeholder="Select department" style="width: 100%">
            <el-option
              v-for="dept in departments"
              :key="dept.id"
              :label="dept.name"
              :value="dept.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Level" prop="level">
          <el-select v-model="positionForm.level" placeholder="Select level" style="width: 100%">
            <el-option label="Entry" value="entry" />
            <el-option label="Junior" value="junior" />
            <el-option label="Mid" value="mid" />
            <el-option label="Senior" value="senior" />
            <el-option label="Lead" value="lead" />
            <el-option label="Manager" value="manager" />
            <el-option label="Director" value="director" />
            <el-option label="Executive" value="executive" />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Description" prop="description">
          <el-input
            v-model="positionForm.description"
            type="textarea"
            :rows="3"
            placeholder="Enter position description"
          />
        </el-form-item>
        
        <el-form-item label="Requirements" prop="requirements">
          <el-input
            v-model="positionForm.requirements"
            type="textarea"
            :rows="3"
            placeholder="Enter position requirements"
          />
        </el-form-item>
        
        <div class="form-row">
          <el-form-item label="Min Salary" prop="min_salary" style="width: 48%; margin-right: 4%">
            <el-input-number
              v-model="positionForm.min_salary"
              :min="0"
              :step="1000"
              placeholder="Minimum salary"
              style="width: 100%"
            />
          </el-form-item>
          
          <el-form-item label="Max Salary" prop="max_salary" style="width: 48%">
            <el-input-number
              v-model="positionForm.max_salary"
              :min="0"
              :step="1000"
              placeholder="Maximum salary"
              style="width: 100%"
            />
          </el-form-item>
        </div>
        
        <el-form-item label="Status" prop="status">
          <el-select v-model="positionForm.status" style="width: 100%">
            <el-option label="Active" value="active" />
            <el-option label="Inactive" value="inactive" />
          </el-select>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showCreateModal = false">Cancel</el-button>
          <el-button type="primary" @click="savePosition" :loading="saving">
            {{ editingPosition ? 'Update' : 'Create' }}
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
const editingPosition = ref(null)
const searchQuery = ref('')
const departmentFilter = ref('')
const statusFilter = ref('')
const positionFormRef = ref(null)

// Form data
const positionForm = ref({
  title: '',
  code: '',
  department_id: '',
  level: '',
  description: '',
  requirements: '',
  min_salary: null,
  max_salary: null,
  status: 'active'
})

// Form validation rules
const formRules = {
  title: [
    { required: true, message: 'Position title is required', trigger: 'blur' },
    { min: 2, max: 100, message: 'Title should be 2-100 characters', trigger: 'blur' }
  ],
  department_id: [
    { required: true, message: 'Department is required', trigger: 'change' }
  ],
  level: [
    { required: true, message: 'Level is required', trigger: 'change' }
  ],
  status: [
    { required: true, message: 'Status is required', trigger: 'change' }
  ]
}

// Computed properties
const positions = computed(() => store.getters['positions/allPositions'])
const departments = computed(() => store.getters['departments/allDepartments'])

const activePositions = computed(() => 
  positions.value.filter(pos => pos.status === 'active')
)

const totalEmployees = computed(() => 
  positions.value.reduce((total, pos) => total + (pos.employee_count || 0), 0)
)

const filteredPositions = computed(() => {
  let filtered = positions.value
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(pos => 
      pos.title.toLowerCase().includes(query) ||
      pos.code?.toLowerCase().includes(query) ||
      pos.description?.toLowerCase().includes(query) ||
      pos.department_name?.toLowerCase().includes(query)
    )
  }
  
  if (departmentFilter.value) {
    filtered = filtered.filter(pos => pos.department_id === departmentFilter.value)
  }
  
  if (statusFilter.value) {
    filtered = filtered.filter(pos => pos.status === statusFilter.value)
  }
  
  return filtered
})

// Methods
const loadPositions = async () => {
  try {
    loading.value = true
    await store.dispatch('positions/fetchPositions')
  } catch (error) {
    ElMessage.error('Failed to load positions')
  } finally {
    loading.value = false
  }
}

const loadDepartments = async () => {
  try {
    await store.dispatch('departments/fetchDepartments')
  } catch (error) {
    console.error('Failed to load departments:', error)
  }
}

const editPosition = (position) => {
  editingPosition.value = position
  positionForm.value = {
    title: position.title,
    code: position.code || '',
    department_id: position.department_id,
    level: position.level,
    description: position.description || '',
    requirements: position.requirements || '',
    min_salary: position.min_salary || null,
    max_salary: position.max_salary || null,
    status: position.status
  }
  showCreateModal.value = true
}

const savePosition = async () => {
  try {
    const valid = await positionFormRef.value.validate()
    if (!valid) return
    
    // Validate salary range
    if (positionForm.value.min_salary && positionForm.value.max_salary) {
      if (positionForm.value.min_salary >= positionForm.value.max_salary) {
        ElMessage.error('Maximum salary must be greater than minimum salary')
        return
      }
    }
    
    saving.value = true
    
    if (editingPosition.value) {
      await store.dispatch('positions/updatePosition', {
        id: editingPosition.value.id,
        data: positionForm.value
      })
      ElMessage.success('Position updated successfully')
    } else {
      await store.dispatch('positions/createPosition', positionForm.value)
      ElMessage.success('Position created successfully')
    }
    
    showCreateModal.value = false
    resetForm()
    loadPositions()
  } catch (error) {
    ElMessage.error(error.message || 'Failed to save position')
  } finally {
    saving.value = false
  }
}

const deletePosition = async (position) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete "${position.title}"?`,
      'Confirm Delete',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await store.dispatch('positions/deletePosition', position.id)
    ElMessage.success('Position deleted successfully')
    loadPositions()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to delete position')
    }
  }
}

const resetForm = () => {
  editingPosition.value = null
  positionForm.value = {
    title: '',
    code: '',
    department_id: '',
    level: '',
    description: '',
    requirements: '',
    min_salary: null,
    max_salary: null,
    status: 'active'
  }
  positionFormRef.value?.clearValidate()
}

const getLevelType = (level) => {
  const levelTypes = {
    'entry': '',
    'junior': 'info',
    'mid': 'warning',
    'senior': 'success',
    'lead': 'success',
    'manager': 'danger',
    'director': 'danger',
    'executive': 'danger'
  }
  return levelTypes[level] || ''
}

const formatNumber = (num) => {
  return new Intl.NumberFormat().format(num)
}

// Lifecycle
onMounted(() => {
  loadPositions()
  loadDepartments()
})
</script>

<style scoped>
.position-management {
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

.position-info strong {
  display: block;
  color: #303133;
}

.position-info small {
  color: #909399;
  font-size: 12px;
}

.text-muted {
  color: #909399;
  font-style: italic;
}

.form-row {
  display: flex;
  gap: 16px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>