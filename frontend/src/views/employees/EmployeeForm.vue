<template>
  <div class="employee-form">
    <div class="page-header">
      <div class="header-content">
        <h1>{{ isEdit ? 'Edit Employee' : 'Add New Employee' }}</h1>
        <p>{{ isEdit ? 'Update employee information' : 'Create a new employee record' }}</p>
      </div>
      <div class="header-actions">
        <el-button @click="$router.go(-1)">Cancel</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="loading">
          {{ isEdit ? 'Update' : 'Create' }} Employee
        </el-button>
      </div>
    </div>

    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="140px"
      class="employee-form-content"
    >
      <el-row :gutter="24">
        <!-- Personal Information -->
        <el-col :span="24">
          <el-card class="form-section" shadow="never">
            <template #header>
              <h3>Personal Information</h3>
            </template>
            
            <el-row :gutter="20">
              <el-col :xs="24" :sm="12">
                <el-form-item label="First Name" prop="first_name">
                  <el-input v-model="form.first_name" placeholder="Enter first name" />
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12">
                <el-form-item label="Last Name" prop="last_name">
                  <el-input v-model="form.last_name" placeholder="Enter last name" />
                </el-form-item>
              </el-col>
            </el-row>
            
            <el-row :gutter="20">
              <el-col :xs="24" :sm="12">
                <el-form-item label="Email" prop="email">
                  <el-input v-model="form.email" type="email" placeholder="Enter email address" />
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12">
                <el-form-item label="Phone" prop="phone">
                  <el-input v-model="form.phone" placeholder="Enter phone number" />
                </el-form-item>
              </el-col>
            </el-row>
            
            <el-row :gutter="20">
              <el-col :xs="24" :sm="12">
                <el-form-item label="Date of Birth" prop="date_of_birth">
                  <el-date-picker
                    v-model="form.date_of_birth"
                    type="date"
                    placeholder="Select date of birth"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12">
                <el-form-item label="Gender" prop="gender">
                  <el-select v-model="form.gender" placeholder="Select gender" style="width: 100%">
                    <el-option label="Male" value="male" />
                    <el-option label="Female" value="female" />
                    <el-option label="Other" value="other" />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            
            <el-form-item label="Address" prop="address">
              <el-input
                v-model="form.address"
                type="textarea"
                :rows="3"
                placeholder="Enter full address"
              />
            </el-form-item>
          </el-card>
        </el-col>
        
        <!-- Employment Information -->
        <el-col :span="24">
          <el-card class="form-section" shadow="never">
            <template #header>
              <h3>Employment Information</h3>
            </template>
            
            <el-row :gutter="20">
              <el-col :xs="24" :sm="12">
                <el-form-item label="Employee ID" prop="employee_id">
                  <el-input v-model="form.employee_id" placeholder="Enter employee ID" />
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12">
                <el-form-item label="Hire Date" prop="hire_date">
                  <el-date-picker
                    v-model="form.hire_date"
                    type="date"
                    placeholder="Select join date"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            
            <el-row :gutter="20">
              <el-col :xs="24" :sm="12">
                <el-form-item label="Department" prop="department_id">
                  <el-select v-model="form.department_id" placeholder="Select department" style="width: 100%">
                    <el-option
                      v-for="dept in departments"
                      :key="dept.id"
                      :label="dept.name"
                      :value="dept.id"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12">
                <el-form-item label="Position" prop="position_id">
                  <el-select v-model="form.position_id" placeholder="Select position" style="width: 100%">
                    <el-option
                      v-for="pos in positions"
                      :key="pos.id"
                      :label="pos.title"
                      :value="pos.id"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            
            <el-row :gutter="20">
              <el-col :xs="24" :sm="12">
                <el-form-item label="Employment Type" prop="employment_type">
                  <el-select v-model="form.employment_type" placeholder="Select employment type" style="width: 100%">
                    <el-option label="Full-time" value="full_time" />
                    <el-option label="Part-time" value="part_time" />
                    <el-option label="Contract" value="contract" />
                    <el-option label="Intern" value="intern" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12">
                <el-form-item label="Status" prop="status">
                  <el-select v-model="form.status" placeholder="Select status" style="width: 100%">
                    <el-option label="Active" value="active" />
                    <el-option label="Inactive" value="inactive" />
                    <el-option label="On Leave" value="on_leave" />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            
            <el-row :gutter="20">
              <el-col :xs="24" :sm="12">
                <el-form-item label="Salary" prop="salary">
                  <el-input-number
                    v-model="form.salary"
                    :min="0"
                    :precision="2"
                    placeholder="Enter salary"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>
        </el-col>
        
        <!-- Emergency Contact -->
        <el-col :span="24">
          <el-card class="form-section" shadow="never">
            <template #header>
              <h3>Emergency Contact</h3>
            </template>
            
            <el-row :gutter="20">
              <el-col :xs="24" :sm="12">
                <el-form-item label="Contact Name" prop="emergency_contact_name">
                  <el-input v-model="form.emergency_contact_name" placeholder="Enter contact name" />
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12">
                <el-form-item label="Relationship" prop="emergency_contact_relationship">
                  <el-input v-model="form.emergency_contact_relationship" placeholder="Enter relationship" />
                </el-form-item>
              </el-col>
            </el-row>
            
            <el-row :gutter="20">
              <el-col :xs="24" :sm="12">
                <el-form-item label="Contact Phone" prop="emergency_contact_phone">
                  <el-input v-model="form.emergency_contact_phone" placeholder="Enter contact phone" />
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>
        </el-col>
      </el-row>
    </el-form>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'

export default {
  name: 'EmployeeForm',
  setup() {
    const store = useStore()
    const router = useRouter()
    const route = useRoute()
    const formRef = ref()
    
    const isEdit = computed(() => !!route.params.id)
    const loading = computed(() => store.getters['employees/loading'])
    const departments = computed(() => store.getters['employees/departments'])
    const positions = computed(() => store.getters['employees/positions'])

    
    const form = reactive({
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      date_of_birth: '',
      gender: '',
      address: '',
      employee_id: '',
      hire_date: '',
      department_id: '',
      position_id: '',
      employment_type: '',
      status: 'active',
      salary: 0,
      emergency_contact_name: '',
      emergency_contact_relationship: '',
      emergency_contact_phone: ''
    })
    
    const rules = {
      first_name: [
        { required: true, message: 'First name is required', trigger: 'blur' }
      ],
      last_name: [
        { required: true, message: 'Last name is required', trigger: 'blur' }
      ],
      email: [
        { required: true, message: 'Email is required', trigger: 'blur' },
        { type: 'email', message: 'Please enter a valid email', trigger: 'blur' }
      ],
      phone: [
        { required: true, message: 'Phone number is required', trigger: 'blur' }
      ],
      employee_id: [
        { required: true, message: 'Employee ID is required', trigger: 'blur' }
      ],
      hire_date: [
        { required: true, message: 'Hire date is required', trigger: 'change' }
      ],
      department_id: [
        { required: true, message: 'Department is required', trigger: 'change' }
      ],
      position_id: [
        { required: true, message: 'Position is required', trigger: 'change' }
      ],
      employment_type: [
        { required: true, message: 'Employment type is required', trigger: 'change' }
      ],
      status: [
        { required: true, message: 'Status is required', trigger: 'change' }
      ]
    }
    
    const loadEmployee = async () => {
      if (isEdit.value) {
        const employee = await store.dispatch('employees/fetchEmployee', route.params.id)
        if (employee) {
          Object.keys(form).forEach(key => {
            if (employee[key] !== undefined) {
              form[key] = employee[key]
            }
          })
        }
      }
    }
    
    const handleSubmit = async () => {
      try {
        const valid = await formRef.value.validate()
        if (!valid) return
        
        const action = isEdit.value ? 'employees/updateEmployee' : 'employees/createEmployee'
        const payload = isEdit.value ? { id: route.params.id, ...form } : form
        
        const result = await store.dispatch(action, payload)
        if (result.success) {
          ElMessage.success(`Employee ${isEdit.value ? 'updated' : 'created'} successfully`)
          router.push('/employees')
        } else {
          ElMessage.error(result.message || `Failed to ${isEdit.value ? 'update' : 'create'} employee`)
        }
      } catch (error) {
        console.error('Form submission error:', error)
        ElMessage.error('An error occurred while saving employee')
      }
    }
    
    onMounted(async () => {
      await Promise.all([
        store.dispatch('employees/fetchDepartments'),
        store.dispatch('employees/fetchPositions'),
        loadEmployee()
      ])
    })
    
    return {
      formRef,
      form,
      rules,
      isEdit,
      loading,
      departments,
      positions,
      handleSubmit
    }
  }
}
</script>

<style scoped>
.employee-form {
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

.employee-form-content {
  max-width: 1200px;
}

.form-section {
  margin-bottom: 24px;
  border: 1px solid #EBEEF5;
}

.form-section :deep(.el-card__header) {
  background-color: #FAFAFA;
  border-bottom: 1px solid #EBEEF5;
}

.form-section h3 {
  margin: 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

:deep(.el-form-item) {
  margin-bottom: 20px;
}

:deep(.el-form-item__label) {
  color: #606266;
  font-weight: 500;
}

@media (max-width: 768px) {
  .employee-form {
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
  
  :deep(.el-form-item__label) {
    width: 100px !important;
  }
}
</style>