<template>
  <el-dialog
    v-model="visible"
    :title="isEditing ? 'Edit Attendance Record' : 'Add Attendance Record'"
    width="600px"
    :before-close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="120px"
      v-loading="loading"
    >
      <el-form-item label="Employee" prop="employeeId">
        <el-select
          v-model="form.employeeId"
          filterable
          remote
          :remote-method="searchEmployees"
          :loading="searchLoading"
          placeholder="Search and select employee"
          style="width: 100%"
          :disabled="isEditing"
        >
          <el-option
            v-for="employee in employees"
            :key="employee.id"
            :label="`${employee.name} (${employee.employee_id})`"
            :value="employee.id"
          >
            <div class="employee-option">
              <span class="employee-name">{{ employee.name }}</span>
              <span class="employee-id">{{ employee.employee_id }}</span>
              <span class="employee-dept">{{ employee.department }}</span>
            </div>
          </el-option>
        </el-select>
      </el-form-item>
      
      <el-form-item label="Date" prop="date">
        <el-date-picker
          v-model="form.date"
          type="date"
          placeholder="Select date"
          style="width: 100%"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          :disabled-date="disabledDate"
        />
      </el-form-item>
      
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Clock In" prop="clockIn">
            <el-time-picker
              v-model="form.clockIn"
              placeholder="Select clock in time"
              style="width: 100%"
              format="HH:mm"
              value-format="HH:mm"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Clock Out" prop="clockOut">
            <el-time-picker
              v-model="form.clockOut"
              placeholder="Select clock out time"
              style="width: 100%"
              format="HH:mm"
              value-format="HH:mm"
            />
          </el-form-item>
        </el-col>
      </el-row>
      
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Break Start">
            <el-time-picker
              v-model="form.breakStart"
              placeholder="Break start time"
              style="width: 100%"
              format="HH:mm"
              value-format="HH:mm"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Break End">
            <el-time-picker
              v-model="form.breakEnd"
              placeholder="Break end time"
              style="width: 100%"
              format="HH:mm"
              value-format="HH:mm"
            />
          </el-form-item>
        </el-col>
      </el-row>
      
      <el-form-item label="Status" prop="status">
        <el-select v-model="form.status" placeholder="Select status" style="width: 100%">
          <el-option label="Present" value="present">
            <el-tag type="success" size="small">Present</el-tag>
          </el-option>
          <el-option label="Absent" value="absent">
            <el-tag type="danger" size="small">Absent</el-tag>
          </el-option>
          <el-option label="Late" value="late">
            <el-tag type="warning" size="small">Late</el-tag>
          </el-option>
          <el-option label="Half Day" value="half_day">
            <el-tag type="info" size="small">Half Day</el-tag>
          </el-option>
          <el-option label="Work From Home" value="wfh">
            <el-tag type="" size="small">Work From Home</el-tag>
          </el-option>
          <el-option label="On Leave" value="leave">
            <el-tag type="warning" size="small">On Leave</el-tag>
          </el-option>
        </el-select>
      </el-form-item>
      
      <el-form-item label="Leave Type" v-if="form.status === 'leave'">
        <el-select v-model="form.leaveType" placeholder="Select leave type" style="width: 100%">
          <el-option label="Sick Leave" value="sick" />
          <el-option label="Annual Leave" value="annual" />
          <el-option label="Personal Leave" value="personal" />
          <el-option label="Emergency Leave" value="emergency" />
          <el-option label="Maternity Leave" value="maternity" />
          <el-option label="Paternity Leave" value="paternity" />
          <el-option label="Unpaid Leave" value="unpaid" />
        </el-select>
      </el-form-item>
      
      <el-form-item label="Overtime Hours">
        <el-input-number
          v-model="form.overtimeHours"
          :min="0"
          :max="12"
          :step="0.5"
          placeholder="Overtime hours"
          style="width: 100%"
        />
      </el-form-item>
      
      <el-form-item label="Location">
        <el-input
          v-model="form.location"
          placeholder="Work location (optional)"
          maxlength="100"
          show-word-limit
        />
      </el-form-item>
      
      <el-form-item label="Notes">
        <el-input
          v-model="form.notes"
          type="textarea"
          :rows="3"
          placeholder="Additional notes (optional)"
          maxlength="500"
          show-word-limit
        />
      </el-form-item>
      
      <!-- Calculated Fields Display -->
      <el-divider content-position="left">Calculated Information</el-divider>
      
      <el-row :gutter="20">
        <el-col :span="8">
          <el-form-item label="Work Hours">
            <el-input :value="calculatedWorkHours" readonly>
              <template #prefix>
                <i class="fas fa-clock"></i>
              </template>
            </el-input>
          </el-form-item>
        </el-col>
        <el-col :span="8">
          <el-form-item label="Break Time">
            <el-input :value="calculatedBreakTime" readonly>
              <template #prefix>
                <i class="fas fa-coffee"></i>
              </template>
            </el-input>
          </el-form-item>
        </el-col>
        <el-col :span="8">
          <el-form-item label="Total Hours">
            <el-input :value="calculatedTotalHours" readonly>
              <template #prefix>
                <i class="fas fa-calculator"></i>
              </template>
            </el-input>
          </el-form-item>
        </el-col>
      </el-row>
      
      <el-alert
        v-if="validationWarnings.length > 0"
        type="warning"
        :closable="false"
        show-icon
      >
        <template #title>
          <div>Validation Warnings:</div>
          <ul style="margin: 5px 0 0 20px; padding: 0;">
            <li v-for="warning in validationWarnings" :key="warning">{{ warning }}</li>
          </ul>
        </template>
      </el-alert>
    </el-form>
    
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">Cancel</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">
          {{ isEditing ? 'Update' : 'Create' }} Record
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import attendanceAPI from '@/api/attendance'
import employeeAPI from '@/api/employees'

export default {
  name: 'EditAttendanceForm',
  props: {
    modelValue: {
      type: Boolean,
      default: false
    },
    attendanceRecord: {
      type: Object,
      default: null
    }
  },
  emits: ['update:modelValue', 'success'],
  setup(props, { emit }) {
    const formRef = ref()
    const employees = ref([])
    const loading = ref(false)
    const submitting = ref(false)
    const searchLoading = ref(false)
    
    const form = reactive({
      employeeId: '',
      date: '',
      clockIn: '',
      clockOut: '',
      breakStart: '',
      breakEnd: '',
      status: 'present',
      leaveType: '',
      overtimeHours: 0,
      location: '',
      notes: ''
    })
    
    const rules = {
      employeeId: [
        { required: true, message: 'Please select an employee', trigger: 'change' }
      ],
      date: [
        { required: true, message: 'Please select a date', trigger: 'change' }
      ],
      status: [
        { required: true, message: 'Please select a status', trigger: 'change' }
      ],
      clockIn: [
        {
          validator: (rule, value, callback) => {
            if (form.status === 'present' || form.status === 'late' || form.status === 'wfh') {
              if (!value) {
                callback(new Error('Clock in time is required for this status'))
              }
            }
            callback()
          },
          trigger: 'change'
        }
      ],
      clockOut: [
        {
          validator: (rule, value, callback) => {
            if (form.clockIn && value) {
              if (value <= form.clockIn) {
                callback(new Error('Clock out time must be after clock in time'))
              }
            }
            callback()
          },
          trigger: 'change'
        }
      ]
    }
    
    const visible = computed({
      get: () => props.modelValue,
      set: (value) => emit('update:modelValue', value)
    })
    
    const isEditing = computed(() => {
      return props.attendanceRecord && props.attendanceRecord.id
    })
    
    const calculatedWorkHours = computed(() => {
      if (!form.clockIn || !form.clockOut) return '0h 0m'
      
      const clockIn = new Date(`2000-01-01 ${form.clockIn}`)
      const clockOut = new Date(`2000-01-01 ${form.clockOut}`)
      
      if (clockOut <= clockIn) return '0h 0m'
      
      const diffMs = clockOut - clockIn
      const hours = Math.floor(diffMs / (1000 * 60 * 60))
      const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60))
      
      return `${hours}h ${minutes}m`
    })
    
    const calculatedBreakTime = computed(() => {
      if (!form.breakStart || !form.breakEnd) return '0h 0m'
      
      const breakStart = new Date(`2000-01-01 ${form.breakStart}`)
      const breakEnd = new Date(`2000-01-01 ${form.breakEnd}`)
      
      if (breakEnd <= breakStart) return '0h 0m'
      
      const diffMs = breakEnd - breakStart
      const hours = Math.floor(diffMs / (1000 * 60 * 60))
      const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60))
      
      return `${hours}h ${minutes}m`
    })
    
    const calculatedTotalHours = computed(() => {
      if (!form.clockIn || !form.clockOut) return '0h 0m'
      
      const clockIn = new Date(`2000-01-01 ${form.clockIn}`)
      const clockOut = new Date(`2000-01-01 ${form.clockOut}`)
      
      if (clockOut <= clockIn) return '0h 0m'
      
      let totalMs = clockOut - clockIn
      
      // Subtract break time
      if (form.breakStart && form.breakEnd) {
        const breakStart = new Date(`2000-01-01 ${form.breakStart}`)
        const breakEnd = new Date(`2000-01-01 ${form.breakEnd}`)
        
        if (breakEnd > breakStart) {
          totalMs -= (breakEnd - breakStart)
        }
      }
      
      // Add overtime
      if (form.overtimeHours > 0) {
        totalMs += (form.overtimeHours * 60 * 60 * 1000)
      }
      
      const hours = Math.floor(totalMs / (1000 * 60 * 60))
      const minutes = Math.floor((totalMs % (1000 * 60 * 60)) / (1000 * 60))
      
      return `${hours}h ${minutes}m`
    })
    
    const validationWarnings = computed(() => {
      const warnings = []
      
      // Check for late arrival
      if (form.clockIn && form.status === 'present') {
        const clockInTime = new Date(`2000-01-01 ${form.clockIn}`)
        const standardStart = new Date('2000-01-01 09:00') // Assuming 9 AM start
        
        if (clockInTime > standardStart) {
          warnings.push('Employee clocked in late - consider changing status to "Late"')
        }
      }
      
      // Check for long break
      if (form.breakStart && form.breakEnd) {
        const breakStart = new Date(`2000-01-01 ${form.breakStart}`)
        const breakEnd = new Date(`2000-01-01 ${form.breakEnd}`)
        const breakDuration = (breakEnd - breakStart) / (1000 * 60) // minutes
        
        if (breakDuration > 60) {
          warnings.push('Break time exceeds 1 hour')
        }
      }
      
      // Check for excessive overtime
      if (form.overtimeHours > 4) {
        warnings.push('Overtime exceeds 4 hours - please verify')
      }
      
      return warnings
    })
    
    const searchEmployees = async (query) => {
      if (query) {
        searchLoading.value = true
        try {
          const response = await employeeAPI.searchEmployees(query)
          employees.value = response.data
        } catch (error) {
          console.error('Error searching employees:', error)
        } finally {
          searchLoading.value = false
        }
      }
    }
    
    const disabledDate = (date) => {
      // Disable future dates
      return date > new Date()
    }
    
    const resetForm = () => {
      Object.assign(form, {
        employeeId: '',
        date: '',
        clockIn: '',
        clockOut: '',
        breakStart: '',
        breakEnd: '',
        status: 'present',
        leaveType: '',
        overtimeHours: 0,
        location: '',
        notes: ''
      })
      
      nextTick(() => {
        formRef.value?.clearValidate()
      })
    }
    
    const loadFormData = () => {
      if (props.attendanceRecord) {
        Object.assign(form, {
          employeeId: props.attendanceRecord.employee_id,
          date: props.attendanceRecord.date,
          clockIn: props.attendanceRecord.clock_in,
          clockOut: props.attendanceRecord.clock_out,
          breakStart: props.attendanceRecord.break_start,
          breakEnd: props.attendanceRecord.break_end,
          status: props.attendanceRecord.status,
          leaveType: props.attendanceRecord.leave_type || '',
          overtimeHours: props.attendanceRecord.overtime_hours || 0,
          location: props.attendanceRecord.location || '',
          notes: props.attendanceRecord.notes || ''
        })
        
        // Load employee data if editing
        if (props.attendanceRecord.employee) {
          employees.value = [props.attendanceRecord.employee]
        }
      } else {
        resetForm()
        form.date = new Date().toISOString().split('T')[0]
      }
    }
    
    const handleSubmit = async () => {
      try {
        const valid = await formRef.value.validate()
        if (!valid) return
        
        submitting.value = true
        
        const data = {
          employee_id: form.employeeId,
          date: form.date,
          clock_in: form.clockIn,
          clock_out: form.clockOut,
          break_start: form.breakStart,
          break_end: form.breakEnd,
          status: form.status,
          leave_type: form.leaveType,
          overtime_hours: form.overtimeHours,
          location: form.location,
          notes: form.notes
        }
        
        if (isEditing.value) {
          await attendanceAPI.updateAttendance(props.attendanceRecord.id, data)
          ElMessage.success('Attendance record updated successfully')
        } else {
          await attendanceAPI.createAttendance(data)
          ElMessage.success('Attendance record created successfully')
        }
        
        emit('success')
        handleClose()
      } catch (error) {
        console.error('Error saving attendance record:', error)
        ElMessage.error('Failed to save attendance record')
      } finally {
        submitting.value = false
      }
    }
    
    const handleClose = () => {
      if (submitting.value) return
      
      visible.value = false
      setTimeout(() => {
        resetForm()
      }, 300)
    }
    
    // Watch for dialog visibility changes
    watch(
      () => props.modelValue,
      (newValue) => {
        if (newValue) {
          loadFormData()
        }
      },
      { immediate: true }
    )
    
    // Watch for status changes to clear leave type
    watch(
      () => form.status,
      (newStatus) => {
        if (newStatus !== 'leave') {
          form.leaveType = ''
        }
      }
    )
    
    return {
      formRef,
      employees,
      loading,
      submitting,
      searchLoading,
      form,
      rules,
      visible,
      isEditing,
      calculatedWorkHours,
      calculatedBreakTime,
      calculatedTotalHours,
      validationWarnings,
      searchEmployees,
      disabledDate,
      handleSubmit,
      handleClose
    }
  }
}
</script>

<style scoped>
.employee-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.employee-name {
  font-weight: 600;
  color: #2c3e50;
}

.employee-id {
  color: #7f8c8d;
  font-size: 12px;
}

.employee-dept {
  color: #95a5a6;
  font-size: 11px;
}

.dialog-footer {
  text-align: right;
}

:deep(.el-form-item__label) {
  font-weight: 600;
  color: #2c3e50;
}

:deep(.el-input.is-disabled .el-input__inner) {
  background-color: #f5f7fa;
  border-color: #e4e7ed;
  color: #606266;
}

:deep(.el-divider__text) {
  background-color: #fff;
  color: #2c3e50;
  font-weight: 600;
}

:deep(.el-alert__title) {
  font-size: 14px;
}

:deep(.el-alert__title ul) {
  list-style-type: disc;
}

@media (max-width: 768px) {
  :deep(.el-dialog) {
    width: 95% !important;
    margin: 5vh auto;
  }
  
  :deep(.el-form-item__label) {
    width: 100px !important;
  }
}
</style>