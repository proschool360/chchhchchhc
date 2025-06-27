<template>
  <div class="leave-list">
    <div class="page-header">
      <div class="header-content">
        <h1>Leave Management</h1>
        <p>Track and manage employee leave requests</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" :icon="Plus" @click="showLeaveRequestDialog = true">
          New Leave Request
        </el-button>
      </div>
    </div>
    
    <!-- Leave Summary -->
    <el-row :gutter="20" class="summary-cards">
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon pending">
              <el-icon><Clock /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ leaveStats.pending }}</h3>
              <p>Pending Requests</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon approved">
              <el-icon><Check /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ leaveStats.approved }}</h3>
              <p>Approved Leaves</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon rejected">
              <el-icon><Close /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ leaveStats.rejected }}</h3>
              <p>Rejected Requests</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon today">
              <el-icon><Calendar /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ leaveStats.on_leave_today }}</h3>
              <p>On Leave Today</p>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    
    <!-- Filters and Search -->
    <el-card class="filter-card" shadow="never">
      <el-row :gutter="20">
        <el-col :xs="24" :sm="8" :md="6">
          <el-input
            v-model="filters.search"
            placeholder="Search employees..."
            :prefix-icon="Search"
            clearable
            @input="handleSearch"
          />
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.department"
            placeholder="Department"
            clearable
            @change="handleFilter"
          >
            <el-option
              v-for="dept in departments"
              :key="dept.id"
              :label="dept.name"
              :value="dept.id"
            />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.leave_type"
            placeholder="Leave Type"
            clearable
            @change="handleFilter"
          >
            <el-option
              v-for="type in leaveTypes"
              :key="type.id"
              :label="type.name"
              :value="type.id"
            />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-select
            v-model="filters.status"
            placeholder="Status"
            clearable
            @change="handleFilter"
          >
            <el-option label="Pending" value="pending" />
            <el-option label="Approved" value="approved" />
            <el-option label="Rejected" value="rejected" />
            <el-option label="Cancelled" value="cancelled" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="6">
          <div class="filter-actions">
            <el-button :icon="Refresh" @click="resetFilters">Reset</el-button>
            <el-button type="success" :icon="Download" @click="exportLeaveRequests">
              Export
            </el-button>
          </div>
        </el-col>
      </el-row>
    </el-card>
    
    <!-- Leave Requests Table -->
    <el-card class="table-card" shadow="never">
      <el-table
        v-loading="loading"
        :data="leaveRequests"
        stripe
        style="width: 100%"
        @sort-change="handleSort"
      >
        <el-table-column type="selection" width="55" />
        
        <el-table-column label="Employee" min-width="200" sortable="custom" prop="employee_name">
          <template #default="{ row }">
            <div class="employee-info">
              <el-avatar
                :size="32"
                :src="row.employee_avatar"
                :alt="row.employee_name"
                class="employee-avatar"
              >
                {{ row.employee_name?.charAt(0).toUpperCase() }}
              </el-avatar>
              <div class="employee-details">
                <div class="employee-name">{{ row.employee_name }}</div>
                <div class="employee-id">{{ row.employee_id }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Leave Type" prop="leave_type" width="120" sortable="custom">
          <template #default="{ row }">
            <el-tag size="small" effect="plain">{{ row.leave_type }}</el-tag>
          </template>
        </el-table-column>
        
        <el-table-column label="Duration" width="180" sortable="custom">
          <template #default="{ row }">
            <div class="leave-duration">
              <div>{{ formatDate(row.start_date) }} - {{ formatDate(row.end_date) }}</div>
              <div class="leave-days">{{ row.days }} day(s)</div>
            </div>
          </template>
        </el-table-column>
        
        <el-table-column label="Applied On" prop="created_at" width="120" sortable="custom">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        
        <el-table-column label="Status" prop="status" width="120" sortable="custom">
          <template #default="{ row }">
            <el-tag
              :type="getStatusType(row.status)"
              size="small"
            >
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column label="Reason" prop="reason" min-width="150">
          <template #default="{ row }">
            <el-tooltip
              :content="row.reason"
              placement="top"
              :disabled="!row.reason || row.reason.length < 30"
            >
              <span class="reason-text">{{ truncateText(row.reason, 30) || '-' }}</span>
            </el-tooltip>
          </template>
        </el-table-column>
        
        <el-table-column label="Actions" width="180" fixed="right">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-tooltip content="View Details" placement="top">
                <el-button
                  type="primary"
                  :icon="View"
                  size="small"
                  circle
                  @click="viewLeaveRequest(row)"
                />
              </el-tooltip>
              
              <template v-if="row.status === 'pending'">
                <el-tooltip content="Approve" placement="top">
                  <el-button
                    type="success"
                    :icon="Check"
                    size="small"
                    circle
                    @click="approveLeaveRequest(row)"
                  />
                </el-tooltip>
                <el-tooltip content="Reject" placement="top">
                  <el-button
                    type="danger"
                    :icon="Close"
                    size="small"
                    circle
                    @click="rejectLeaveRequest(row)"
                  />
                </el-tooltip>
              </template>
              
              <template v-if="row.status === 'pending' || row.status === 'approved'">
                <el-tooltip content="Cancel" placement="top">
                  <el-button
                    type="warning"
                    :icon="Delete"
                    size="small"
                    circle
                    @click="cancelLeaveRequest(row)"
                  />
                </el-tooltip>
              </template>
            </div>
          </template>
        </el-table-column>
      </el-table>
      
      <!-- Pagination -->
      <div class="pagination-container">
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
    </el-card>
    
    <!-- New Leave Request Dialog -->
    <el-dialog
      v-model="showLeaveRequestDialog"
      title="New Leave Request"
      width="500px"
    >
      <el-form
        ref="leaveFormRef"
        :model="leaveForm"
        :rules="leaveRules"
        label-width="120px"
      >
        <el-form-item label="Employee" prop="employee_id">
          <el-select
            v-model="leaveForm.employee_id"
            placeholder="Select employee"
            style="width: 100%"
            filterable
          >
            <el-option
              v-for="emp in employees"
              :key="emp.id"
              :label="emp.name"
              :value="emp.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Leave Type" prop="leave_type_id">
          <el-select
            v-model="leaveForm.leave_type_id"
            placeholder="Select leave type"
            style="width: 100%"
          >
            <el-option
              v-for="type in leaveTypes"
              :key="type.id"
              :label="type.name"
              :value="type.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Date Range" prop="date_range">
          <el-date-picker
            v-model="leaveForm.date_range"
            type="daterange"
            range-separator="To"
            start-placeholder="Start date"
            end-placeholder="End date"
            style="width: 100%"
            @change="calculateLeaveDays"
          />
        </el-form-item>
        <el-form-item label="Half Day" prop="half_day">
          <el-switch v-model="leaveForm.half_day" />
        </el-form-item>
        <el-form-item label="Reason" prop="reason">
          <el-input
            v-model="leaveForm.reason"
            type="textarea"
            :rows="3"
            placeholder="Enter reason for leave"
          />
        </el-form-item>
        <el-form-item label="Attachment">
          <el-upload
            action="#"
            :auto-upload="false"
            :on-change="handleFileChange"
            :limit="1"
          >
            <el-button type="primary">Select File</el-button>
            <template #tip>
              <div class="el-upload__tip">
                Optional: Upload supporting documents (PDF, JPG, PNG)
              </div>
            </template>
          </el-upload>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showLeaveRequestDialog = false">Cancel</el-button>
          <el-button type="primary" @click="submitLeaveRequest" :loading="submitting">
            Submit Request
          </el-button>
        </span>
      </template>
    </el-dialog>
    
    <!-- Leave Details Dialog -->
    <el-dialog
      v-model="showLeaveDetailsDialog"
      title="Leave Request Details"
      width="600px"
    >
      <div v-if="selectedLeave" class="leave-details">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="Employee" :span="2">
            {{ selectedLeave.employee_name }}
          </el-descriptions-item>
          <el-descriptions-item label="Leave Type">
            {{ selectedLeave.leave_type }}
          </el-descriptions-item>
          <el-descriptions-item label="Status">
            <el-tag :type="getStatusType(selectedLeave.status)">
              {{ getStatusText(selectedLeave.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Start Date">
            {{ formatDate(selectedLeave.start_date) }}
          </el-descriptions-item>
          <el-descriptions-item label="End Date">
            {{ formatDate(selectedLeave.end_date) }}
          </el-descriptions-item>
          <el-descriptions-item label="Duration">
            {{ selectedLeave.days }} day(s)
          </el-descriptions-item>
          <el-descriptions-item label="Half Day">
            {{ selectedLeave.half_day ? 'Yes' : 'No' }}
          </el-descriptions-item>
          <el-descriptions-item label="Applied On">
            {{ formatDateTime(selectedLeave.created_at) }}
          </el-descriptions-item>
          <el-descriptions-item label="Last Updated">
            {{ formatDateTime(selectedLeave.updated_at) }}
          </el-descriptions-item>
          <el-descriptions-item label="Reason" :span="2">
            {{ selectedLeave.reason || 'No reason provided' }}
          </el-descriptions-item>
          <el-descriptions-item v-if="selectedLeave.status !== 'pending'" label="Reviewed By" :span="2">
            {{ selectedLeave.reviewed_by || 'Not available' }}
          </el-descriptions-item>
          <el-descriptions-item v-if="selectedLeave.status === 'rejected'" label="Rejection Reason" :span="2">
            {{ selectedLeave.rejection_reason || 'No reason provided' }}
          </el-descriptions-item>
          <el-descriptions-item v-if="selectedLeave.attachment" label="Attachment" :span="2">
            <el-link type="primary" :href="selectedLeave.attachment" target="_blank">
              View Attachment
            </el-link>
          </el-descriptions-item>
        </el-descriptions>
        
        <div v-if="selectedLeave.status === 'pending'" class="leave-actions">
          <el-button type="success" @click="approveLeaveRequest(selectedLeave)">
            Approve
          </el-button>
          <el-button type="danger" @click="rejectLeaveRequest(selectedLeave)">
            Reject
          </el-button>
        </div>
      </div>
    </el-dialog>
    
    <!-- Rejection Reason Dialog -->
    <el-dialog
      v-model="showRejectionDialog"
      title="Reject Leave Request"
      width="500px"
    >
      <el-form
        ref="rejectionFormRef"
        :model="rejectionForm"
        :rules="rejectionRules"
        label-width="120px"
      >
        <el-form-item label="Reason" prop="reason">
          <el-input
            v-model="rejectionForm.reason"
            type="textarea"
            :rows="3"
            placeholder="Enter reason for rejection"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showRejectionDialog = false">Cancel</el-button>
          <el-button type="danger" @click="confirmRejectLeaveRequest" :loading="submitting">
            Reject Request
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Plus,
  Clock,
  Check,
  Close,
  Calendar,
  Search,
  Refresh,
  Download,
  View,
  Delete
} from '@element-plus/icons-vue'

export default {
  name: 'LeaveList',
  components: {
    Plus,
    Clock,
    Check,
    Close,
    Calendar,
    Search,
    Refresh,
    Download,
    View,
    Delete
  },
  setup() {
    const store = useStore()
    const leaveFormRef = ref()
    const rejectionFormRef = ref()
    const showLeaveRequestDialog = ref(false)
    const showLeaveDetailsDialog = ref(false)
    const showRejectionDialog = ref(false)
    const submitting = ref(false)
    const selectedLeave = ref(null)
    const selectedFile = ref(null)
    
    const filters = reactive({
      search: '',
      department: '',
      leave_type: '',
      status: ''
    })
    
    const pagination = reactive({
      currentPage: 1,
      pageSize: 20,
      total: 0
    })
    
    const sortConfig = ref({
      prop: '',
      order: ''
    })
    
    const leaveForm = reactive({
      employee_id: '',
      leave_type_id: '',
      date_range: [],
      half_day: false,
      reason: '',
      attachment: null
    })
    
    const leaveRules = {
      employee_id: [
        { required: true, message: 'Please select an employee', trigger: 'change' }
      ],
      leave_type_id: [
        { required: true, message: 'Please select leave type', trigger: 'change' }
      ],
      date_range: [
        { required: true, message: 'Please select date range', trigger: 'change' }
      ],
      reason: [
        { required: true, message: 'Please enter reason for leave', trigger: 'blur' }
      ]
    }
    
    const rejectionForm = reactive({
      reason: ''
    })
    
    const rejectionRules = {
      reason: [
        { required: true, message: 'Please enter reason for rejection', trigger: 'blur' }
      ]
    }
    
    const leaveRequests = computed(() => store.getters['leave/leaveRequests'])
    const leaveStats = computed(() => store.getters['leave/leaveStats'])
    const leaveTypes = computed(() => store.getters['leave/leaveTypes'])
    const departments = computed(() => store.getters['employees/departments'])
    const employees = computed(() => store.getters['employees/employees'])
    const loading = computed(() => store.getters['leave/loading'])
    
    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const formatDateTime = (datetime) => {
      if (!datetime) return '-'
      return new Date(datetime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
    
    const truncateText = (text, length) => {
      if (!text) return ''
      return text.length > length ? `${text.substring(0, length)}...` : text
    }
    
    const getStatusType = (status) => {
      const statusMap = {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        cancelled: 'info'
      }
      return statusMap[status] || 'info'
    }
    
    const getStatusText = (status) => {
      const statusMap = {
        pending: 'Pending',
        approved: 'Approved',
        rejected: 'Rejected',
        cancelled: 'Cancelled'
      }
      return statusMap[status] || status
    }
    
    const fetchLeaveRequests = async () => {
      const params = {
        page: pagination.currentPage,
        limit: pagination.pageSize,
        search: filters.search,
        department: filters.department,
        leave_type: filters.leave_type,
        status: filters.status,
        sort_by: sortConfig.value.prop,
        sort_order: sortConfig.value.order
      }
      
      const result = await store.dispatch('leave/fetchLeaveRequests', params)
      if (result && result.pagination) {
        pagination.total = result.pagination.total
      }
    }
    
    const handleSearch = () => {
      pagination.currentPage = 1
      fetchLeaveRequests()
    }
    
    const handleFilter = () => {
      pagination.currentPage = 1
      fetchLeaveRequests()
    }
    
    const handleSort = ({ prop, order }) => {
      sortConfig.value.prop = prop
      sortConfig.value.order = order === 'ascending' ? 'asc' : 'desc'
      fetchLeaveRequests()
    }
    
    const handleSizeChange = (size) => {
      pagination.pageSize = size
      pagination.currentPage = 1
      fetchLeaveRequests()
    }
    
    const handleCurrentChange = (page) => {
      pagination.currentPage = page
      fetchLeaveRequests()
    }
    
    const resetFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      pagination.currentPage = 1
      sortConfig.value = { prop: '', order: '' }
      fetchLeaveRequests()
    }
    
    const exportLeaveRequests = async () => {
      try {
        const params = {
          search: filters.search,
          department: filters.department,
          leave_type: filters.leave_type,
          status: filters.status,
          format: 'csv'
        }
        
        await store.dispatch('leave/exportLeaveRequests', params)
        ElMessage.success('Leave requests exported successfully')
      } catch (error) {
        console.error('Export error:', error)
        ElMessage.error('Failed to export leave requests')
      }
    }
    
    const calculateLeaveDays = () => {
      // This would typically calculate the number of working days between the dates
      // For simplicity, we're not implementing the full calculation here
      console.log('Calculating leave days')
    }
    
    const handleFileChange = (file) => {
      selectedFile.value = file.raw
    }
    
    const submitLeaveRequest = async () => {
      try {
        const valid = await leaveFormRef.value.validate()
        if (!valid) return
        
        submitting.value = true
        
        const formData = new FormData()
        formData.append('employee_id', leaveForm.employee_id)
        formData.append('leave_type_id', leaveForm.leave_type_id)
        formData.append('start_date', leaveForm.date_range[0])
        formData.append('end_date', leaveForm.date_range[1])
        formData.append('half_day', leaveForm.half_day)
        formData.append('reason', leaveForm.reason)
        
        if (selectedFile.value) {
          formData.append('attachment', selectedFile.value)
        }
        
        const result = await store.dispatch('leave/createLeaveRequest', formData)
        
        if (result.success) {
          ElMessage.success('Leave request submitted successfully')
          showLeaveRequestDialog.value = false
          resetLeaveForm()
          fetchLeaveRequests()
        } else {
          ElMessage.error(result.message || 'Failed to submit leave request')
        }
      } catch (error) {
        console.error('Submit leave request error:', error)
        ElMessage.error('An error occurred while submitting leave request')
      } finally {
        submitting.value = false
      }
    }
    
    const resetLeaveForm = () => {
      Object.keys(leaveForm).forEach(key => {
        if (key === 'half_day') {
          leaveForm[key] = false
        } else if (key === 'date_range') {
          leaveForm[key] = []
        } else {
          leaveForm[key] = ''
        }
      })
      selectedFile.value = null
    }
    
    const viewLeaveRequest = (leave) => {
      selectedLeave.value = leave
      showLeaveDetailsDialog.value = true
    }
    
    const approveLeaveRequest = async (leave) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to approve this leave request?`,
          'Confirm Approval',
          {
            confirmButtonText: 'Approve',
            cancelButtonText: 'Cancel',
            type: 'info'
          }
        )
        
        const result = await store.dispatch('leave/approveLeaveRequest', leave.id)
        if (result.success) {
          ElMessage.success('Leave request approved successfully')
          showLeaveDetailsDialog.value = false
          fetchLeaveRequests()
        } else {
          ElMessage.error(result.message || 'Failed to approve leave request')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Approve error:', error)
          ElMessage.error('An error occurred while approving leave request')
        }
      }
    }
    
    const rejectLeaveRequest = (leave) => {
      selectedLeave.value = leave
      showRejectionDialog.value = true
    }
    
    const confirmRejectLeaveRequest = async () => {
      try {
        const valid = await rejectionFormRef.value.validate()
        if (!valid) return
        
        submitting.value = true
        
        const result = await store.dispatch('leave/rejectLeaveRequest', {
          id: selectedLeave.value.id,
          reason: rejectionForm.reason
        })
        
        if (result.success) {
          ElMessage.success('Leave request rejected successfully')
          showRejectionDialog.value = false
          showLeaveDetailsDialog.value = false
          rejectionForm.reason = ''
          fetchLeaveRequests()
        } else {
          ElMessage.error(result.message || 'Failed to reject leave request')
        }
      } catch (error) {
        console.error('Reject error:', error)
        ElMessage.error('An error occurred while rejecting leave request')
      } finally {
        submitting.value = false
      }
    }
    
    const cancelLeaveRequest = async (leave) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to cancel this leave request?`,
          'Confirm Cancellation',
          {
            confirmButtonText: 'Cancel Leave',
            cancelButtonText: 'Keep',
            type: 'warning'
          }
        )
        
        const result = await store.dispatch('leave/cancelLeaveRequest', leave.id)
        if (result.success) {
          ElMessage.success('Leave request cancelled successfully')
          showLeaveDetailsDialog.value = false
          fetchLeaveRequests()
        } else {
          ElMessage.error(result.message || 'Failed to cancel leave request')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Cancel error:', error)
          ElMessage.error('An error occurred while cancelling leave request')
        }
      }
    }
    
    onMounted(async () => {
      await Promise.all([
        store.dispatch('employees/fetchDepartments'),
        store.dispatch('employees/fetchEmployees'),
        store.dispatch('leave/fetchLeaveTypes'),
        store.dispatch('leave/fetchLeaveStats'),
        fetchLeaveRequests()
      ])
    })
    
    return {
      filters,
      pagination,
      leaveRequests,
      leaveStats,
      leaveTypes,
      departments,
      employees,
      loading,
      showLeaveRequestDialog,
      showLeaveDetailsDialog,
      showRejectionDialog,
      leaveFormRef,
      rejectionFormRef,
      leaveForm,
      leaveRules,
      rejectionForm,
      rejectionRules,
      selectedLeave,
      submitting,
      formatDate,
      formatDateTime,
      truncateText,
      getStatusType,
      getStatusText,
      handleSearch,
      handleFilter,
      handleSort,
      handleSizeChange,
      handleCurrentChange,
      resetFilters,
      exportLeaveRequests,
      calculateLeaveDays,
      handleFileChange,
      submitLeaveRequest,
      viewLeaveRequest,
      approveLeaveRequest,
      rejectLeaveRequest,
      confirmRejectLeaveRequest,
      cancelLeaveRequest
    }
  }
}
</script>

<style scoped>
.leave-list {
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

.summary-cards {
  margin-bottom: 24px;
}

.summary-card {
  border: 1px solid #EBEEF5;
}

.summary-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.summary-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.summary-icon.pending {
  background-color: #E6A23C;
}

.summary-icon.approved {
  background-color: #67C23A;
}

.summary-icon.rejected {
  background-color: #F56C6C;
}

.summary-icon.today {
  background-color: #409EFF;
}

.summary-info h3 {
  margin: 0 0 4px 0;
  color: #303133;
  font-size: 24px;
  font-weight: 600;
}

.summary-info p {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.filter-card {
  margin-bottom: 20px;
  border: 1px solid #EBEEF5;
}

.filter-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.table-card {
  border: 1px solid #EBEEF5;
}

.employee-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.employee-avatar {
  flex-shrink: 0;
}

.employee-details {
  min-width: 0;
}

.employee-name {
  font-weight: 500;
  color: #303133;
  margin-bottom: 2px;
}

.employee-id {
  font-size: 12px;
  color: #909399;
}

.leave-duration {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.leave-days {
  font-size: 12px;
  color: #909399;
}

.reason-text {
  color: #606266;
  font-size: 13px;
}

.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #EBEEF5;
}

.leave-details {
  margin-bottom: 20px;
}

.leave-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
}

:deep(.el-table) {
  font-size: 14px;
}

:deep(.el-table th) {
  background-color: #FAFAFA;
  color: #606266;
  font-weight: 600;
}

:deep(.el-table td) {
  padding: 12px 0;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
  color: #606266;
}

:deep(.el-descriptions__content) {
  color: #303133;
}

@media (max-width: 768px) {
  .leave-list {
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
  
  .filter-actions {
    justify-content: flex-start;
    margin-top: 12px;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 2px;
  }
  
  :deep(.el-table) {
    font-size: 12px;
  }
  
  .employee-info {
    flex-direction: column;
    gap: 8px;
    text-align: center;
  }
}
</style>