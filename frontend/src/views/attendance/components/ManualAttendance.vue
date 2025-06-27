<template>
  <div class="manual-attendance">
    <div class="attendance-form">
      <el-card>
        <template #header>
          <div class="card-header">
            <h3>Manual Attendance Entry</h3>
            <p>Manually record employee attendance</p>
          </div>
        </template>
        
        <el-form :model="attendanceForm" :rules="rules" ref="formRef" label-width="120px">
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Employee" prop="employeeId">
                <el-select
                  v-model="attendanceForm.employeeId"
                  placeholder="Select employee"
                  filterable
                  remote
                  :remote-method="searchEmployees"
                  :loading="searchLoading"
                  style="width: 100%"
                >
                  <el-option
                    v-for="employee in employees"
                    :key="employee.id"
                    :label="`${employee.name} (${employee.employee_id})`"
                    :value="employee.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Date" prop="date">
                <el-date-picker
                  v-model="attendanceForm.date"
                  type="date"
                  placeholder="Select date"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
          </el-row>
          
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Clock In" prop="clockIn">
                <el-time-picker
                  v-model="attendanceForm.clockIn"
                  placeholder="Select clock in time"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Clock Out" prop="clockOut">
                <el-time-picker
                  v-model="attendanceForm.clockOut"
                  placeholder="Select clock out time"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
          </el-row>
          
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Break Start">
                <el-time-picker
                  v-model="attendanceForm.breakStart"
                  placeholder="Select break start time"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Break End">
                <el-time-picker
                  v-model="attendanceForm.breakEnd"
                  placeholder="Select break end time"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
          </el-row>
          
          <el-form-item label="Status" prop="status">
            <el-select v-model="attendanceForm.status" placeholder="Select status" style="width: 100%">
              <el-option label="Present" value="present" />
              <el-option label="Late" value="late" />
              <el-option label="Absent" value="absent" />
              <el-option label="Half Day" value="half_day" />
            </el-select>
          </el-form-item>
          
          <el-form-item label="Notes">
            <el-input
              v-model="attendanceForm.notes"
              type="textarea"
              :rows="3"
              placeholder="Add any notes or comments"
            />
          </el-form-item>
          
          <el-form-item>
            <el-button type="primary" @click="submitAttendance" :loading="submitting">
              Record Attendance
            </el-button>
            <el-button @click="resetForm">Reset</el-button>
          </el-form-item>
        </el-form>
      </el-card>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import attendanceAPI from '@/api/attendance'
import employeeAPI from '@/api/employees'

export default {
  name: 'ManualAttendance',
  setup() {
    const formRef = ref()
    const employees = ref([])
    const searchLoading = ref(false)
    const submitting = ref(false)
    
    const attendanceForm = reactive({
      employeeId: '',
      date: new Date(),
      clockIn: null,
      clockOut: null,
      breakStart: null,
      breakEnd: null,
      status: 'present',
      notes: ''
    })
    
    const rules = {
      employeeId: [
        { required: true, message: 'Please select an employee', trigger: 'change' }
      ],
      date: [
        { required: true, message: 'Please select a date', trigger: 'change' }
      ],
      clockIn: [
        { required: true, message: 'Please select clock in time', trigger: 'change' }
      ],
      status: [
        { required: true, message: 'Please select status', trigger: 'change' }
      ]
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
    
    const submitAttendance = async () => {
      try {
        await formRef.value.validate()
        submitting.value = true
        
        const attendanceData = {
          employee_id: attendanceForm.employeeId,
          date: attendanceForm.date,
          clock_in: attendanceForm.clockIn,
          clock_out: attendanceForm.clockOut,
          break_start: attendanceForm.breakStart,
          break_end: attendanceForm.breakEnd,
          status: attendanceForm.status,
          notes: attendanceForm.notes,
          entry_type: 'manual'
        }
        
        await attendanceAPI.createAttendance(attendanceData)
        ElMessage.success('Attendance recorded successfully')
        resetForm()
      } catch (error) {
        console.error('Error recording attendance:', error)
        ElMessage.error('Failed to record attendance')
      } finally {
        submitting.value = false
      }
    }
    
    const resetForm = () => {
      formRef.value?.resetFields()
      Object.assign(attendanceForm, {
        employeeId: '',
        date: new Date(),
        clockIn: null,
        clockOut: null,
        breakStart: null,
        breakEnd: null,
        status: 'present',
        notes: ''
      })
    }
    
    onMounted(() => {
      // Load initial employees
      searchEmployees('')
    })
    
    return {
      formRef,
      employees,
      searchLoading,
      submitting,
      attendanceForm,
      rules,
      searchEmployees,
      submitAttendance,
      resetForm
    }
  }
}
</script>

<style scoped>
.manual-attendance {
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

:deep(.el-form-item__label) {
  font-weight: 500;
  color: #2c3e50;
}

:deep(.el-card__header) {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}
</style>