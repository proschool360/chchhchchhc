<template>
  <div class="payroll-list">
    <div class="page-header">
      <div class="header-content">
        <h1>Payroll Management</h1>
        <p>Manage employee salaries and payroll processing</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" :icon="Plus" @click="showPayrollDialog = true">
          Process Payroll
        </el-button>
        <el-button type="success" :icon="Download" @click="exportPayroll">
          Export Payroll
        </el-button>
      </div>
    </div>
    
    <!-- Payroll Summary -->
    <el-row :gutter="20" class="summary-cards">
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon total">
              <el-icon><Money /></el-icon>
            </div>
            <div class="summary-info">
              <h3>${{ formatCurrency(payrollStats.total_payroll) }}</h3>
              <p>Total Payroll</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon processed">
              <el-icon><Check /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ payrollStats.processed_count }}</h3>
              <p>Processed</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon pending">
              <el-icon><Clock /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ payrollStats.pending_count }}</h3>
              <p>Pending</p>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-card class="summary-card" shadow="hover">
          <div class="summary-content">
            <div class="summary-icon employees">
              <el-icon><User /></el-icon>
            </div>
            <div class="summary-info">
              <h3>{{ payrollStats.employee_count }}</h3>
              <p>Employees</p>
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
            v-model="filters.status"
            placeholder="Status"
            clearable
            @change="handleFilter"
          >
            <el-option label="Processed" value="processed" />
            <el-option label="Pending" value="pending" />
            <el-option label="Draft" value="draft" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8" :md="4">
          <el-date-picker
            v-model="filters.pay_period"
            type="month"
            placeholder="Pay Period"
            format="YYYY-MM"
            value-format="YYYY-MM"
            @change="handleFilter"
          />
        </el-col>
        <el-col :xs="24" :sm="8" :md="6">
          <div class="filter-actions">
            <el-button :icon="Refresh" @click="resetFilters">Reset</el-button>
            <el-button type="warning" :icon="Calculator" @click="calculatePayroll">
              Calculate
            </el-button>
          </div>
        </el-col>
      </el-row>
    </el-card>
    
    <!-- Payroll Table -->
    <el-card class="table-card" shadow="never">
      <el-table
        v-loading="loading"
        :data="payrollRecords"
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
        
        <el-table-column label="Department" prop="department" width="120" sortable="custom" />
        
        <el-table-column label="Pay Period" prop="pay_period" width="120" sortable="custom">
          <template #default="{ row }">
            {{ formatPayPeriod(row.pay_period) }}
          </template>
        </el-table-column>
        
        <el-table-column label="Basic Salary" prop="basic_salary" width="120" sortable="custom">
          <template #default="{ row }">
            ${{ formatCurrency(row.basic_salary) }}
          </template>
        </el-table-column>
        
        <el-table-column label="Allowances" prop="allowances" width="120" sortable="custom">
          <template #default="{ row }">
            ${{ formatCurrency(row.allowances) }}
          </template>
        </el-table-column>
        
        <el-table-column label="Deductions" prop="deductions" width="120" sortable="custom">
          <template #default="{ row }">
            ${{ formatCurrency(row.deductions) }}
          </template>
        </el-table-column>
        
        <el-table-column label="Net Salary" prop="net_salary" width="140" sortable="custom">
          <template #default="{ row }">
            <div class="net-salary">
              ${{ formatCurrency(row.net_salary) }}
            </div>
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
        
        <el-table-column label="Processed Date" prop="processed_date" width="140" sortable="custom">
          <template #default="{ row }">
            {{ formatDate(row.processed_date) }}
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
                  @click="viewPayrollDetails(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Edit" placement="top">
                <el-button
                  type="warning"
                  :icon="Edit"
                  size="small"
                  circle
                  :disabled="row.status === 'processed'"
                  @click="editPayroll(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Generate Payslip" placement="top">
                <el-button
                  type="success"
                  :icon="Document"
                  size="small"
                  circle
                  :disabled="row.status !== 'processed'"
                  @click="generatePayslip(row)"
                />
              </el-tooltip>
              
              <el-tooltip content="Delete" placement="top">
                <el-button
                  type="danger"
                  :icon="Delete"
                  size="small"
                  circle
                  :disabled="row.status === 'processed'"
                  @click="deletePayroll(row)"
                />
              </el-tooltip>
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
    
    <!-- Process Payroll Dialog -->
    <el-dialog
      v-model="showPayrollDialog"
      title="Process Payroll"
      width="600px"
    >
      <el-form
        ref="payrollFormRef"
        :model="payrollForm"
        :rules="payrollRules"
        label-width="140px"
      >
        <el-form-item label="Pay Period" prop="pay_period">
          <el-date-picker
            v-model="payrollForm.pay_period"
            type="month"
            placeholder="Select pay period"
            format="YYYY-MM"
            value-format="YYYY-MM"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Department" prop="department_id">
          <el-select
            v-model="payrollForm.department_id"
            placeholder="Select department (optional)"
            clearable
            style="width: 100%"
          >
            <el-option label="All Departments" value="" />
            <el-option
              v-for="dept in departments"
              :key="dept.id"
              :label="dept.name"
              :value="dept.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Include Bonus" prop="include_bonus">
          <el-switch v-model="payrollForm.include_bonus" />
        </el-form-item>
        <el-form-item label="Include Overtime" prop="include_overtime">
          <el-switch v-model="payrollForm.include_overtime" />
        </el-form-item>
        <el-form-item label="Auto Process" prop="auto_process">
          <el-switch v-model="payrollForm.auto_process" />
          <div class="form-help-text">
            If enabled, payroll will be automatically processed and marked as completed
          </div>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showPayrollDialog = false">Cancel</el-button>
          <el-button type="primary" @click="processPayroll" :loading="processing">
            Process Payroll
          </el-button>
        </span>
      </template>
    </el-dialog>
    
    <!-- Payroll Details Dialog -->
    <el-dialog
      v-model="showDetailsDialog"
      title="Payroll Details"
      width="700px"
    >
      <div v-if="selectedPayroll" class="payroll-details">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-descriptions title="Employee Information" :column="1" border>
              <el-descriptions-item label="Name">
                {{ selectedPayroll.employee_name }}
              </el-descriptions-item>
              <el-descriptions-item label="Employee ID">
                {{ selectedPayroll.employee_id }}
              </el-descriptions-item>
              <el-descriptions-item label="Department">
                {{ selectedPayroll.department }}
              </el-descriptions-item>
              <el-descriptions-item label="Position">
                {{ selectedPayroll.position }}
              </el-descriptions-item>
            </el-descriptions>
          </el-col>
          <el-col :span="12">
            <el-descriptions title="Payroll Information" :column="1" border>
              <el-descriptions-item label="Pay Period">
                {{ formatPayPeriod(selectedPayroll.pay_period) }}
              </el-descriptions-item>
              <el-descriptions-item label="Status">
                <el-tag :type="getStatusType(selectedPayroll.status)">
                  {{ getStatusText(selectedPayroll.status) }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Processed Date">
                {{ formatDate(selectedPayroll.processed_date) }}
              </el-descriptions-item>
              <el-descriptions-item label="Processed By">
                {{ selectedPayroll.processed_by || 'System' }}
              </el-descriptions-item>
            </el-descriptions>
          </el-col>
        </el-row>
        
        <div class="salary-breakdown">
          <h3>Salary Breakdown</h3>
          <el-table :data="salaryBreakdown" border>
            <el-table-column label="Component" prop="component" />
            <el-table-column label="Amount" prop="amount">
              <template #default="{ row }">
                <span :class="{ 'negative-amount': row.type === 'deduction' }">
                  {{ row.type === 'deduction' ? '-' : '' }}${{ formatCurrency(Math.abs(row.amount)) }}
                </span>
              </template>
            </el-table-column>
            <el-table-column label="Type" prop="type">
              <template #default="{ row }">
                <el-tag
                  :type="row.type === 'earning' ? 'success' : 'danger'"
                  size="small"
                >
                  {{ row.type === 'earning' ? 'Earning' : 'Deduction' }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
          
          <div class="salary-summary">
            <el-row :gutter="20">
              <el-col :span="8">
                <div class="summary-item">
                  <span class="label">Total Earnings:</span>
                  <span class="value positive">${{ formatCurrency(selectedPayroll.total_earnings) }}</span>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="summary-item">
                  <span class="label">Total Deductions:</span>
                  <span class="value negative">${{ formatCurrency(selectedPayroll.total_deductions) }}</span>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="summary-item">
                  <span class="label">Net Salary:</span>
                  <span class="value net">${{ formatCurrency(selectedPayroll.net_salary) }}</span>
                </div>
              </el-col>
            </el-row>
          </div>
        </div>
      </div>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showDetailsDialog = false">Close</el-button>
          <el-button
            v-if="selectedPayroll?.status === 'processed'"
            type="success"
            @click="generatePayslip(selectedPayroll)"
          >
            Generate Payslip
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
  Money,
  Check,
  Clock,
  User,
  Search,
  Refresh,
  Download,
  Calculator,
  View,
  Edit,
  Document,
  Delete
} from '@element-plus/icons-vue'

export default {
  name: 'PayrollList',
  components: {
    Plus,
    Money,
    Check,
    Clock,
    User,
    Search,
    Refresh,
    Download,
    Calculator,
    View,
    Edit,
    Document,
    Delete
  },
  setup() {
    const store = useStore()
    const payrollFormRef = ref()
    const showPayrollDialog = ref(false)
    const showDetailsDialog = ref(false)
    const processing = ref(false)
    const selectedPayroll = ref(null)
    
    const filters = reactive({
      search: '',
      department: '',
      status: '',
      pay_period: ''
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
    
    const payrollForm = reactive({
      pay_period: '',
      department_id: '',
      include_bonus: true,
      include_overtime: true,
      auto_process: false
    })
    
    const payrollRules = {
      pay_period: [
        { required: true, message: 'Please select pay period', trigger: 'change' }
      ]
    }
    
    const payrollRecords = computed(() => store.getters['payroll/payrollRecords'])
    const payrollStats = computed(() => store.getters['payroll/payrollStats'])
    const departments = computed(() => store.getters['employees/departments'])
    const loading = computed(() => store.getters['payroll/loading'])
    
    const salaryBreakdown = computed(() => {
      if (!selectedPayroll.value) return []
      
      const breakdown = [
        { component: 'Basic Salary', amount: selectedPayroll.value.basic_salary, type: 'earning' },
        { component: 'House Rent Allowance', amount: selectedPayroll.value.hra, type: 'earning' },
        { component: 'Transport Allowance', amount: selectedPayroll.value.transport_allowance, type: 'earning' },
        { component: 'Medical Allowance', amount: selectedPayroll.value.medical_allowance, type: 'earning' },
        { component: 'Overtime', amount: selectedPayroll.value.overtime_amount, type: 'earning' },
        { component: 'Bonus', amount: selectedPayroll.value.bonus, type: 'earning' },
        { component: 'Tax Deduction', amount: selectedPayroll.value.tax_deduction, type: 'deduction' },
        { component: 'Provident Fund', amount: selectedPayroll.value.pf_deduction, type: 'deduction' },
        { component: 'Insurance', amount: selectedPayroll.value.insurance_deduction, type: 'deduction' },
        { component: 'Other Deductions', amount: selectedPayroll.value.other_deductions, type: 'deduction' }
      ]
      
      return breakdown.filter(item => item.amount > 0)
    })
    
    const formatCurrency = (amount) => {
      if (!amount) return '0.00'
      return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
    }
    
    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const formatPayPeriod = (period) => {
      if (!period) return '-'
      const [year, month] = period.split('-')
      const date = new Date(year, month - 1)
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long'
      })
    }
    
    const getStatusType = (status) => {
      const statusMap = {
        processed: 'success',
        pending: 'warning',
        draft: 'info'
      }
      return statusMap[status] || 'info'
    }
    
    const getStatusText = (status) => {
      const statusMap = {
        processed: 'Processed',
        pending: 'Pending',
        draft: 'Draft'
      }
      return statusMap[status] || status
    }
    
    const fetchPayrollRecords = async () => {
      const params = {
        page: pagination.currentPage,
        limit: pagination.pageSize,
        search: filters.search,
        department: filters.department,
        status: filters.status,
        pay_period: filters.pay_period,
        sort_by: sortConfig.value.prop,
        sort_order: sortConfig.value.order
      }
      
      const result = await store.dispatch('payroll/fetchPayrollRecords', params)
      if (result && result.pagination) {
        pagination.total = result.pagination.total
      }
    }
    
    const handleSearch = () => {
      pagination.currentPage = 1
      fetchPayrollRecords()
    }
    
    const handleFilter = () => {
      pagination.currentPage = 1
      fetchPayrollRecords()
    }
    
    const handleSort = ({ prop, order }) => {
      sortConfig.value.prop = prop
      sortConfig.value.order = order === 'ascending' ? 'asc' : 'desc'
      fetchPayrollRecords()
    }
    
    const handleSizeChange = (size) => {
      pagination.pageSize = size
      pagination.currentPage = 1
      fetchPayrollRecords()
    }
    
    const handleCurrentChange = (page) => {
      pagination.currentPage = page
      fetchPayrollRecords()
    }
    
    const resetFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      pagination.currentPage = 1
      sortConfig.value = { prop: '', order: '' }
      fetchPayrollRecords()
    }
    
    const calculatePayroll = async () => {
      try {
        await ElMessageBox.confirm(
          'This will recalculate payroll for all employees. Continue?',
          'Confirm Calculation',
          {
            confirmButtonText: 'Calculate',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        
        const result = await store.dispatch('payroll/calculatePayroll', {
          pay_period: filters.pay_period || new Date().toISOString().slice(0, 7)
        })
        
        if (result.success) {
          ElMessage.success('Payroll calculated successfully')
          fetchPayrollRecords()
        } else {
          ElMessage.error(result.message || 'Failed to calculate payroll')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Calculate payroll error:', error)
          ElMessage.error('An error occurred while calculating payroll')
        }
      }
    }
    
    const exportPayroll = async () => {
      try {
        const params = {
          search: filters.search,
          department: filters.department,
          status: filters.status,
          pay_period: filters.pay_period,
          format: 'csv'
        }
        
        await store.dispatch('payroll/exportPayroll', params)
        ElMessage.success('Payroll exported successfully')
      } catch (error) {
        console.error('Export error:', error)
        ElMessage.error('Failed to export payroll')
      }
    }
    
    const processPayroll = async () => {
      try {
        const valid = await payrollFormRef.value.validate()
        if (!valid) return
        
        processing.value = true
        
        const result = await store.dispatch('payroll/processPayroll', payrollForm)
        
        if (result.success) {
          ElMessage.success('Payroll processed successfully')
          showPayrollDialog.value = false
          resetPayrollForm()
          fetchPayrollRecords()
          store.dispatch('payroll/fetchPayrollStats')
        } else {
          ElMessage.error(result.message || 'Failed to process payroll')
        }
      } catch (error) {
        console.error('Process payroll error:', error)
        ElMessage.error('An error occurred while processing payroll')
      } finally {
        processing.value = false
      }
    }
    
    const resetPayrollForm = () => {
      Object.keys(payrollForm).forEach(key => {
        if (key === 'include_bonus' || key === 'include_overtime') {
          payrollForm[key] = true
        } else if (key === 'auto_process') {
          payrollForm[key] = false
        } else {
          payrollForm[key] = ''
        }
      })
    }
    
    const viewPayrollDetails = async (payroll) => {
      try {
        const result = await store.dispatch('payroll/fetchPayrollDetails', payroll.id)
        if (result.success) {
          selectedPayroll.value = result.data
          showDetailsDialog.value = true
        } else {
          ElMessage.error('Failed to load payroll details')
        }
      } catch (error) {
        console.error('View payroll details error:', error)
        ElMessage.error('An error occurred while loading payroll details')
      }
    }
    
    const editPayroll = (payroll) => {
      // Navigate to edit payroll page or open edit dialog
      console.log('Edit payroll:', payroll)
      ElMessage.info('Edit payroll functionality to be implemented')
    }
    
    const generatePayslip = async (payroll) => {
      try {
        const result = await store.dispatch('payroll/generatePayslip', payroll.id)
        if (result.success) {
          ElMessage.success('Payslip generated successfully')
          // Open or download the payslip
          if (result.url) {
            window.open(result.url, '_blank')
          }
        } else {
          ElMessage.error(result.message || 'Failed to generate payslip')
        }
      } catch (error) {
        console.error('Generate payslip error:', error)
        ElMessage.error('An error occurred while generating payslip')
      }
    }
    
    const deletePayroll = async (payroll) => {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to delete payroll record for ${payroll.employee_name}?`,
          'Confirm Deletion',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        
        const result = await store.dispatch('payroll/deletePayroll', payroll.id)
        if (result.success) {
          ElMessage.success('Payroll record deleted successfully')
          fetchPayrollRecords()
          store.dispatch('payroll/fetchPayrollStats')
        } else {
          ElMessage.error(result.message || 'Failed to delete payroll record')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Delete payroll error:', error)
          ElMessage.error('An error occurred while deleting payroll record')
        }
      }
    }
    
    onMounted(async () => {
      await Promise.all([
        store.dispatch('employees/fetchDepartments'),
        store.dispatch('payroll/fetchPayrollStats'),
        fetchPayrollRecords()
      ])
    })
    
    return {
      filters,
      pagination,
      payrollRecords,
      payrollStats,
      departments,
      loading,
      showPayrollDialog,
      showDetailsDialog,
      payrollFormRef,
      payrollForm,
      payrollRules,
      selectedPayroll,
      salaryBreakdown,
      processing,
      formatCurrency,
      formatDate,
      formatPayPeriod,
      getStatusType,
      getStatusText,
      handleSearch,
      handleFilter,
      handleSort,
      handleSizeChange,
      handleCurrentChange,
      resetFilters,
      calculatePayroll,
      exportPayroll,
      processPayroll,
      viewPayrollDetails,
      editPayroll,
      generatePayslip,
      deletePayroll
    }
  }
}
</script>

<style scoped>
.payroll-list {
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

.header-actions {
  display: flex;
  gap: 12px;
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

.summary-icon.total {
  background-color: #409EFF;
}

.summary-icon.processed {
  background-color: #67C23A;
}

.summary-icon.pending {
  background-color: #E6A23C;
}

.summary-icon.employees {
  background-color: #909399;
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

.net-salary {
  font-weight: 600;
  color: #67C23A;
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

.payroll-details {
  margin-bottom: 20px;
}

.salary-breakdown {
  margin-top: 24px;
}

.salary-breakdown h3 {
  margin: 0 0 16px 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.negative-amount {
  color: #F56C6C;
}

.salary-summary {
  margin-top: 16px;
  padding: 16px;
  background-color: #F5F7FA;
  border-radius: 4px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.summary-item .label {
  font-weight: 500;
  color: #606266;
}

.summary-item .value {
  font-weight: 600;
  font-size: 16px;
}

.summary-item .value.positive {
  color: #67C23A;
}

.summary-item .value.negative {
  color: #F56C6C;
}

.summary-item .value.net {
  color: #409EFF;
  font-size: 18px;
}

.form-help-text {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
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
  .payroll-list {
    padding: 15px;
  }
  
  .page-header {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }
  
  .header-actions {
    align-self: flex-start;
    flex-wrap: wrap;
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
  
  .salary-summary {
    padding: 12px;
  }
  
  .summary-item {
    flex-direction: column;
    gap: 4px;
    text-align: center;
  }
}
</style>