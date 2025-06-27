<template>
  <div class="attendance-reports">
    <div class="reports-header">
      <el-card>
        <template #header>
          <div class="card-header">
            <h3>Attendance Reports</h3>
            <p>Generate and view comprehensive attendance reports</p>
          </div>
        </template>
        
        <div class="report-filters">
          <el-form :model="filters" label-width="120px" :inline="true">
            <el-form-item label="Report Type">
              <el-select v-model="filters.reportType" @change="handleReportTypeChange">
                <el-option label="Daily Report" value="daily" />
                <el-option label="Weekly Report" value="weekly" />
                <el-option label="Monthly Report" value="monthly" />
                <el-option label="Custom Range" value="custom" />
                <el-option label="Employee Summary" value="employee" />
                <el-option label="Department Summary" value="department" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Date Range" v-if="filters.reportType === 'custom'">
              <el-date-picker
                v-model="filters.dateRange"
                type="daterange"
                range-separator="To"
                start-placeholder="Start date"
                end-placeholder="End date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
              />
            </el-form-item>
            
            <el-form-item label="Date" v-else-if="filters.reportType === 'daily'">
              <el-date-picker
                v-model="filters.date"
                type="date"
                placeholder="Select date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
              />
            </el-form-item>
            
            <el-form-item label="Month" v-else-if="filters.reportType === 'monthly'">
              <el-date-picker
                v-model="filters.month"
                type="month"
                placeholder="Select month"
                format="YYYY-MM"
                value-format="YYYY-MM"
              />
            </el-form-item>
            
            <el-form-item label="Department">
              <el-select v-model="filters.departmentId" clearable placeholder="All Departments">
                <el-option
                  v-for="dept in departments"
                  :key="dept.id"
                  :label="dept.name"
                  :value="dept.id"
                />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Employee" v-if="filters.reportType === 'employee'">
              <el-select
                v-model="filters.employeeId"
                filterable
                remote
                :remote-method="searchEmployees"
                :loading="searchLoading"
                placeholder="Select employee"
                clearable
              >
                <el-option
                  v-for="employee in employees"
                  :key="employee.id"
                  :label="`${employee.name} (${employee.employee_id})`"
                  :value="employee.id"
                />
              </el-select>
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="generateReport" :loading="generating">
                <i class="fas fa-chart-bar"></i>
                Generate Report
              </el-button>
              <el-button @click="exportReport" :disabled="!reportData.length" :loading="exporting">
                <i class="fas fa-download"></i>
                Export
              </el-button>
            </el-form-item>
          </el-form>
        </div>
      </el-card>
    </div>
    
    <!-- Report Summary -->
    <div class="report-summary" v-if="reportSummary">
      <el-row :gutter="20">
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-content">
              <div class="summary-icon present">
                <i class="fas fa-user-check"></i>
              </div>
              <div class="summary-details">
                <h3>{{ reportSummary.totalPresent }}</h3>
                <p>Total Present</p>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-content">
              <div class="summary-icon absent">
                <i class="fas fa-user-times"></i>
              </div>
              <div class="summary-details">
                <h3>{{ reportSummary.totalAbsent }}</h3>
                <p>Total Absent</p>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-content">
              <div class="summary-icon late">
                <i class="fas fa-user-clock"></i>
              </div>
              <div class="summary-details">
                <h3>{{ reportSummary.totalLate }}</h3>
                <p>Late Arrivals</p>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-content">
              <div class="summary-icon hours">
                <i class="fas fa-clock"></i>
              </div>
              <div class="summary-details">
                <h3>{{ reportSummary.totalHours }}</h3>
                <p>Total Hours</p>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
    
    <!-- Report Charts -->
    <div class="report-charts" v-if="reportData.length > 0">
      <el-row :gutter="20">
        <el-col :span="12">
          <el-card>
            <template #header>
              <h4>Attendance Trends</h4>
            </template>
            <div ref="trendChartRef" style="height: 300px;"></div>
          </el-card>
        </el-col>
        <el-col :span="12">
          <el-card>
            <template #header>
              <h4>Status Distribution</h4>
            </template>
            <div ref="pieChartRef" style="height: 300px;"></div>
          </el-card>
        </el-col>
      </el-row>
    </div>
    
    <!-- Report Table -->
    <div class="report-table">
      <el-card>
        <template #header>
          <div class="table-header">
            <h4>Detailed Report</h4>
            <div class="table-actions">
              <el-input
                v-model="searchText"
                placeholder="Search..."
                prefix-icon="el-icon-search"
                style="width: 200px; margin-right: 10px;"
                clearable
              />
              <el-button @click="refreshReport" :loading="generating">
                <i class="fas fa-sync-alt"></i>
                Refresh
              </el-button>
            </div>
          </div>
        </template>
        
        <el-table
          :data="filteredReportData"
          v-loading="generating"
          stripe
          border
          style="width: 100%"
          :default-sort="{prop: 'date', order: 'descending'}"
        >
          <el-table-column prop="date" label="Date" width="120" sortable>
            <template #default="{ row }">
              {{ formatDate(row.date) }}
            </template>
          </el-table-column>
          
          <el-table-column prop="employeeName" label="Employee" min-width="150" sortable>
            <template #default="{ row }">
              <div class="employee-cell">
                <strong>{{ row.employeeName }}</strong>
                <br>
                <small>{{ row.employeeId }}</small>
              </div>
            </template>
          </el-table-column>
          
          <el-table-column prop="department" label="Department" width="120" sortable />
          
          <el-table-column prop="clockIn" label="Clock In" width="100">
            <template #default="{ row }">
              {{ row.clockIn || '--' }}
            </template>
          </el-table-column>
          
          <el-table-column prop="clockOut" label="Clock Out" width="100">
            <template #default="{ row }">
              {{ row.clockOut || '--' }}
            </template>
          </el-table-column>
          
          <el-table-column prop="breakTime" label="Break Time" width="100">
            <template #default="{ row }">
              {{ row.breakTime || '0h 0m' }}
            </template>
          </el-table-column>
          
          <el-table-column prop="totalHours" label="Total Hours" width="100">
            <template #default="{ row }">
              {{ row.totalHours || '0h 0m' }}
            </template>
          </el-table-column>
          
          <el-table-column prop="status" label="Status" width="100">
            <template #default="{ row }">
              <el-tag :type="getStatusType(row.status)" size="small">
                {{ row.status }}
              </el-tag>
            </template>
          </el-table-column>
          
          <el-table-column prop="overtime" label="Overtime" width="100">
            <template #default="{ row }">
              {{ row.overtime || '0h 0m' }}
            </template>
          </el-table-column>
          
          <el-table-column label="Actions" width="120" fixed="right">
            <template #default="{ row }">
              <el-button type="text" size="small" @click="viewDetails(row)">
                <i class="fas fa-eye"></i>
                Details
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        
        <div class="table-pagination">
          <el-pagination
            v-model:current-page="pagination.currentPage"
            v-model:page-size="pagination.pageSize"
            :page-sizes="[10, 20, 50, 100]"
            :total="reportData.length"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </el-card>
    </div>
    
    <!-- Details Modal -->
    <el-dialog v-model="showDetailsModal" title="Attendance Details" width="600px">
      <div v-if="selectedRecord">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="Employee">{{ selectedRecord.employeeName }}</el-descriptions-item>
          <el-descriptions-item label="Employee ID">{{ selectedRecord.employeeId }}</el-descriptions-item>
          <el-descriptions-item label="Date">{{ formatDate(selectedRecord.date) }}</el-descriptions-item>
          <el-descriptions-item label="Department">{{ selectedRecord.department }}</el-descriptions-item>
          <el-descriptions-item label="Clock In">{{ selectedRecord.clockIn || '--' }}</el-descriptions-item>
          <el-descriptions-item label="Clock Out">{{ selectedRecord.clockOut || '--' }}</el-descriptions-item>
          <el-descriptions-item label="Break Time">{{ selectedRecord.breakTime || '0h 0m' }}</el-descriptions-item>
          <el-descriptions-item label="Total Hours">{{ selectedRecord.totalHours || '0h 0m' }}</el-descriptions-item>
          <el-descriptions-item label="Status">
            <el-tag :type="getStatusType(selectedRecord.status)">{{ selectedRecord.status }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Overtime">{{ selectedRecord.overtime || '0h 0m' }}</el-descriptions-item>
        </el-descriptions>
        
        <div v-if="selectedRecord.notes" style="margin-top: 20px;">
          <h4>Notes:</h4>
          <p>{{ selectedRecord.notes }}</p>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import attendanceAPI from '@/api/attendance'
import departmentAPI from '@/api/departments'
import employeeAPI from '@/api/employees'

export default {
  name: 'AttendanceReports',
  setup() {
    const trendChartRef = ref()
    const pieChartRef = ref()
    const departments = ref([])
    const employees = ref([])
    const reportData = ref([])
    const reportSummary = ref(null)
    const searchLoading = ref(false)
    const generating = ref(false)
    const exporting = ref(false)
    const searchText = ref('')
    const showDetailsModal = ref(false)
    const selectedRecord = ref(null)
    
    const filters = reactive({
      reportType: 'daily',
      date: new Date().toISOString().split('T')[0],
      dateRange: [],
      month: new Date().toISOString().slice(0, 7),
      departmentId: '',
      employeeId: ''
    })
    
    const pagination = reactive({
      currentPage: 1,
      pageSize: 20
    })
    
    const filteredReportData = computed(() => {
      let filtered = reportData.value
      
      if (searchText.value) {
        const search = searchText.value.toLowerCase()
        filtered = filtered.filter(item => 
          item.employeeName.toLowerCase().includes(search) ||
          item.employeeId.toLowerCase().includes(search) ||
          item.department.toLowerCase().includes(search)
        )
      }
      
      const start = (pagination.currentPage - 1) * pagination.pageSize
      const end = start + pagination.pageSize
      return filtered.slice(start, end)
    })
    
    const handleReportTypeChange = () => {
      // Reset filters when report type changes
      if (filters.reportType === 'daily') {
        filters.date = new Date().toISOString().split('T')[0]
      } else if (filters.reportType === 'monthly') {
        filters.month = new Date().toISOString().slice(0, 7)
      }
    }
    
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
    
    const generateReport = async () => {
      try {
        generating.value = true
        
        const params = {
          type: filters.reportType,
          department_id: filters.departmentId,
          employee_id: filters.employeeId
        }
        
        if (filters.reportType === 'daily') {
          params.date = filters.date
        } else if (filters.reportType === 'monthly') {
          params.month = filters.month
        } else if (filters.reportType === 'custom') {
          params.start_date = filters.dateRange[0]
          params.end_date = filters.dateRange[1]
        }
        
        const response = await attendanceAPI.generateReport(params)
        reportData.value = response.data.records
        reportSummary.value = response.data.summary
        
        await nextTick()
        renderCharts()
        
        ElMessage.success('Report generated successfully')
      } catch (error) {
        console.error('Error generating report:', error)
        ElMessage.error('Failed to generate report')
      } finally {
        generating.value = false
      }
    }
    
    const exportReport = async () => {
      try {
        exporting.value = true
        
        const params = {
          type: filters.reportType,
          department_id: filters.departmentId,
          employee_id: filters.employeeId,
          format: 'excel'
        }
        
        if (filters.reportType === 'daily') {
          params.date = filters.date
        } else if (filters.reportType === 'monthly') {
          params.month = filters.month
        } else if (filters.reportType === 'custom') {
          params.start_date = filters.dateRange[0]
          params.end_date = filters.dateRange[1]
        }
        
        const response = await attendanceAPI.exportReport(params)
        
        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `attendance_report_${Date.now()}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        ElMessage.success('Report exported successfully')
      } catch (error) {
        console.error('Error exporting report:', error)
        ElMessage.error('Failed to export report')
      } finally {
        exporting.value = false
      }
    }
    
    const refreshReport = () => {
      generateReport()
    }
    
    const viewDetails = (record) => {
      selectedRecord.value = record
      showDetailsModal.value = true
    }
    
    const renderCharts = () => {
      // This would integrate with a charting library like Chart.js or ECharts
      // For now, we'll just log the data
      console.log('Rendering charts with data:', reportData.value)
    }
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const getStatusType = (status) => {
      switch (status) {
        case 'Present': return 'success'
        case 'Late': return 'warning'
        case 'Absent': return 'danger'
        case 'Half Day': return 'info'
        default: return ''
      }
    }
    
    const handleSizeChange = (size) => {
      pagination.pageSize = size
      pagination.currentPage = 1
    }
    
    const handleCurrentChange = (page) => {
      pagination.currentPage = page
    }
    
    const loadDepartments = async () => {
      try {
        const response = await departmentAPI.getDepartments()
        departments.value = response.data
      } catch (error) {
        console.error('Error loading departments:', error)
      }
    }
    
    onMounted(() => {
      loadDepartments()
    })
    
    return {
      trendChartRef,
      pieChartRef,
      departments,
      employees,
      reportData,
      reportSummary,
      searchLoading,
      generating,
      exporting,
      searchText,
      showDetailsModal,
      selectedRecord,
      filters,
      pagination,
      filteredReportData,
      handleReportTypeChange,
      searchEmployees,
      generateReport,
      exportReport,
      refreshReport,
      viewDetails,
      formatDate,
      getStatusType,
      handleSizeChange,
      handleCurrentChange
    }
  }
}
</script>

<style scoped>
.attendance-reports {
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

.reports-header {
  margin-bottom: 20px;
}

.report-filters {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
}

.report-summary {
  margin-bottom: 20px;
}

.summary-card {
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.summary-content {
  display: flex;
  align-items: center;
  padding: 10px;
}

.summary-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  font-size: 20px;
  color: white;
}

.summary-icon.present {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
}

.summary-icon.absent {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.summary-icon.late {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.summary-icon.hours {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.summary-details h3 {
  margin: 0 0 4px 0;
  font-size: 24px;
  font-weight: 600;
  color: #2c3e50;
}

.summary-details p {
  margin: 0;
  color: #7f8c8d;
  font-size: 14px;
}

.report-charts {
  margin-bottom: 20px;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.table-header h4 {
  margin: 0;
  color: #2c3e50;
  font-size: 16px;
  font-weight: 600;
}

.table-actions {
  display: flex;
  align-items: center;
}

.employee-cell {
  line-height: 1.4;
}

.employee-cell small {
  color: #7f8c8d;
}

.table-pagination {
  margin-top: 20px;
  text-align: right;
}

:deep(.el-card__header) {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

:deep(.el-form--inline .el-form-item) {
  margin-right: 20px;
  margin-bottom: 15px;
}

:deep(.el-table th) {
  background-color: #f8f9fa;
  color: #2c3e50;
  font-weight: 600;
}

:deep(.el-table--striped .el-table__body tr.el-table__row--striped td) {
  background-color: #fafbfc;
}

@media (max-width: 768px) {
  .report-filters {
    padding: 15px;
  }
  
  :deep(.el-form--inline .el-form-item) {
    display: block;
    margin-right: 0;
  }
  
  .table-header {
    flex-direction: column;
    gap: 10px;
    align-items: stretch;
  }
  
  .table-actions {
    justify-content: space-between;
  }
}
</style>