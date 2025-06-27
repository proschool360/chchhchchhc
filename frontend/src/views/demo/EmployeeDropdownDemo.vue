<template>
  <div class="employee-dropdown-demo">
    <el-card class="demo-card">
      <template #header>
        <div class="card-header">
          <span>Employee Dropdown Component Demo</span>
        </div>
      </template>
      
      <div class="demo-section">
        <h3>Basic Usage</h3>
        <p>Simple employee dropdown with search functionality:</p>
        <div class="demo-item">
          <label>Select Employee:</label>
          <EmployeeDropdown 
            v-model="selectedEmployee1" 
            @change="handleEmployeeChange"
          />
          <p v-if="selectedEmployee1" class="selected-info">
            Selected Employee ID: {{ selectedEmployee1 }}
          </p>
        </div>
      </div>

      <el-divider />

      <div class="demo-section">
        <h3>Custom Placeholder and Width</h3>
        <p>Employee dropdown with custom placeholder and width:</p>
        <div class="demo-item">
          <label>Choose Team Member:</label>
          <EmployeeDropdown 
            v-model="selectedEmployee2" 
            placeholder="Choose team member..."
            width="300px"
            @change="handleEmployeeChange"
          />
          <p v-if="selectedEmployee2" class="selected-info">
            Selected Employee ID: {{ selectedEmployee2 }}
          </p>
        </div>
      </div>

      <el-divider />

      <div class="demo-section">
        <h3>Disabled State</h3>
        <p>Employee dropdown in disabled state:</p>
        <div class="demo-item">
          <label>Disabled Dropdown:</label>
          <EmployeeDropdown 
            v-model="selectedEmployee3" 
            :disabled="true"
            placeholder="This dropdown is disabled"
          />
        </div>
      </div>

      <el-divider />

      <div class="demo-section">
        <h3>Non-clearable</h3>
        <p>Employee dropdown without clear button:</p>
        <div class="demo-item">
          <label>Select Employee (No Clear):</label>
          <EmployeeDropdown 
            v-model="selectedEmployee4" 
            :clearable="false"
            placeholder="Cannot be cleared once selected"
            @change="handleEmployeeChange"
          />
          <p v-if="selectedEmployee4" class="selected-info">
            Selected Employee ID: {{ selectedEmployee4 }}
          </p>
        </div>
      </div>

      <el-divider />

      <div class="demo-section">
        <h3>Form Integration Example</h3>
        <p>Using EmployeeDropdown in a form:</p>
        <el-form :model="form" label-width="120px" class="demo-form">
          <el-form-item label="Project Name:">
            <el-input v-model="form.projectName" placeholder="Enter project name" />
          </el-form-item>
          <el-form-item label="Assign To:">
            <EmployeeDropdown 
              v-model="form.assignedTo" 
              placeholder="Select employee to assign"
              @change="handleFormEmployeeChange"
            />
          </el-form-item>
          <el-form-item label="Priority:">
            <el-select v-model="form.priority" placeholder="Select priority">
              <el-option label="High" value="high" />
              <el-option label="Medium" value="medium" />
              <el-option label="Low" value="low" />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="submitForm">Submit</el-button>
            <el-button @click="resetForm">Reset</el-button>
          </el-form-item>
        </el-form>
      </div>

      <el-divider />

      <div class="demo-section">
        <h3>Event Handling</h3>
        <p>Demonstrating change events:</p>
        <div class="demo-item">
          <label>Select Employee:</label>
          <EmployeeDropdown 
            v-model="selectedEmployee5" 
            @change="handleDetailedEmployeeChange"
          />
          <div v-if="selectedEmployeeDetails" class="employee-details">
            <h4>Selected Employee Details:</h4>
            <p><strong>ID:</strong> {{ selectedEmployeeDetails.id }}</p>
            <p><strong>Name:</strong> {{ selectedEmployeeDetails.name }}</p>
            <p><strong>Employee ID:</strong> {{ selectedEmployeeDetails.employee_id }}</p>
          </div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import EmployeeDropdown from '@/components/EmployeeDropdown.vue'

export default {
  name: 'EmployeeDropdownDemo',
  components: {
    EmployeeDropdown
  },
  setup() {
    const selectedEmployee1 = ref(null)
    const selectedEmployee2 = ref(null)
    const selectedEmployee3 = ref(null)
    const selectedEmployee4 = ref(null)
    const selectedEmployee5 = ref(null)
    const selectedEmployeeDetails = ref(null)

    const form = reactive({
      projectName: '',
      assignedTo: null,
      priority: ''
    })

    const handleEmployeeChange = (employeeId, employeeData) => {
      console.log('Employee changed:', employeeId, employeeData)
      ElMessage.success(`Selected employee: ${employeeData?.name || employeeId}`)
    }

    const handleFormEmployeeChange = (employeeId, employeeData) => {
      console.log('Form employee changed:', employeeId, employeeData)
      if (employeeData) {
        ElMessage.info(`Assigned to: ${employeeData.name}`)
      }
    }

    const handleDetailedEmployeeChange = (employeeId, employeeData) => {
      selectedEmployeeDetails.value = employeeData
      console.log('Detailed employee change:', employeeId, employeeData)
    }

    const submitForm = () => {
      console.log('Form submitted:', form)
      ElMessage.success('Form submitted successfully!')
    }

    const resetForm = () => {
      form.projectName = ''
      form.assignedTo = null
      form.priority = ''
      ElMessage.info('Form reset')
    }

    return {
      selectedEmployee1,
      selectedEmployee2,
      selectedEmployee3,
      selectedEmployee4,
      selectedEmployee5,
      selectedEmployeeDetails,
      form,
      handleEmployeeChange,
      handleFormEmployeeChange,
      handleDetailedEmployeeChange,
      submitForm,
      resetForm
    }
  }
}
</script>

<style scoped>
.employee-dropdown-demo {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
}

.demo-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: bold;
  font-size: 18px;
}

.demo-section {
  margin-bottom: 30px;
}

.demo-section h3 {
  color: #409eff;
  margin-bottom: 10px;
}

.demo-section p {
  color: #606266;
  margin-bottom: 15px;
}

.demo-item {
  margin-bottom: 20px;
}

.demo-item label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #303133;
}

.selected-info {
  margin-top: 10px;
  padding: 8px 12px;
  background-color: #f0f9ff;
  border: 1px solid #409eff;
  border-radius: 4px;
  color: #409eff;
  font-size: 14px;
}

.demo-form {
  max-width: 500px;
}

.employee-details {
  margin-top: 15px;
  padding: 15px;
  background-color: #f5f7fa;
  border-radius: 6px;
  border-left: 4px solid #409eff;
}

.employee-details h4 {
  margin: 0 0 10px 0;
  color: #303133;
}

.employee-details p {
  margin: 5px 0;
  color: #606266;
}

.employee-details strong {
  color: #303133;
}
</style>