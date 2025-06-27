<template>
  <el-select
    v-model="selectedEmployee"
    :placeholder="placeholder"
    filterable
    remote
    :remote-method="searchEmployees"
    :loading="searchLoading"
    :style="{ width: width }"
    :clearable="clearable"
    :disabled="disabled"
    @change="handleChange"
  >
    <el-option
      v-for="employee in employees"
      :key="employee.id"
      :label="`${employee.name} (${employee.employee_id})`"
      :value="employee.id"
    />
  </el-select>
</template>

<script>
import { ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import employeeAPI from '@/api/employees'

export default {
  name: 'EmployeeDropdown',
  props: {
    modelValue: {
      type: [String, Number],
      default: null
    },
    placeholder: {
      type: String,
      default: 'Select employee'
    },
    width: {
      type: String,
      default: '100%'
    },
    clearable: {
      type: Boolean,
      default: true
    },
    disabled: {
      type: Boolean,
      default: false
    },
    // Filter employees by department
    departmentId: {
      type: [String, Number],
      default: null
    },
    // Filter employees by status
    status: {
      type: String,
      default: 'active'
    }
  },
  emits: ['update:modelValue', 'change'],
  setup(props, { emit }) {
    const employees = ref([])
    const searchLoading = ref(false)
    const selectedEmployee = ref(props.modelValue)

    // Watch for external changes to modelValue
    watch(() => props.modelValue, (newValue) => {
      selectedEmployee.value = newValue
    })

    // Watch for changes in selectedEmployee and emit to parent
    watch(selectedEmployee, (newValue) => {
      emit('update:modelValue', newValue)
    })

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
      } else {
        employees.value = []
      }
    }

    const handleChange = (value) => {
      const selectedEmp = employees.value.find(emp => emp.id === value)
      emit('change', value, selectedEmp)
    }

    // Load initial employees if modelValue is provided
    const loadInitialEmployee = async () => {
      if (props.modelValue) {
        try {
          const response = await employeeAPI.getEmployee(props.modelValue)
          if (response.data) {
            employees.value = [response.data]
          }
        } catch (error) {
          console.error('Error loading initial employee:', error)
        }
      }
    }

    // Load initial employee on mount
    loadInitialEmployee()

    return {
      employees,
      searchLoading,
      selectedEmployee,
      searchEmployees,
      handleChange
    }
  }
}
</script>

<style scoped>
/* Add any custom styles if needed */
</style>