<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-content">
        <h1>HR Dashboard</h1>
        <p>Welcome back! Here's what's happening in your organization today.</p>
      </div>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Loading dashboard data...</p>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="error-icon">⚠️</div>
      <h3>Error Loading Dashboard</h3>
      <p>{{ error }}</p>
    </div>
    
    <!-- Key Metrics Cards -->
    <div v-else class="metrics-grid">
      <div class="metric-card">
        <div class="metric-icon employees">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
          </svg>
        </div>
        <div class="metric-info">
          <h3>{{ dashboardData?.employees?.total_employees || 0 }}</h3>
          <p>Total Employees</p>
          <span class="metric-change positive">+{{ dashboardData?.employees?.active_employees || 0 }} active</span>
        </div>
      </div>
      
      <div class="metric-card">
        <div class="metric-icon attendance">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M16.2,16.2L11,13V7H12.5V12.2L17,14.9L16.2,16.2Z"/>
          </svg>
        </div>
        <div class="metric-info">
          <h3>{{ dashboardData?.attendance?.attendance_rate || 0 }}%</h3>
          <p>Attendance Rate</p>
          <span class="metric-change" :class="attendanceChangeClass">
            {{ dashboardData?.attendance?.present_count || 0 }} present today
          </span>
        </div>
      </div>
      
      <div class="metric-card">
        <div class="metric-icon leaves">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3M19,19H5V8H19V19Z"/>
          </svg>
        </div>
        <div class="metric-info">
          <h3>{{ dashboardData?.leaves?.pending_requests || 0 }}</h3>
          <p>Pending Leaves</p>
          <span class="metric-change neutral">{{ dashboardData?.leaves?.total_requests || 0 }} total requests</span>
        </div>
      </div>
      
      <div class="metric-card">
        <div class="metric-icon recruitment">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M16,4C16.88,4 17.67,4.38 18.18,5H20C21.11,5 22,5.89 22,7V19C22,20.11 21.11,21 20,21H4C2.89,21 2,20.11 2,19V7C2,5.89 2.89,5 4,5H5.82C6.33,4.38 7.12,4 8,4H16M16,6H8A1,1 0 0,0 7,7V8H17V7A1,1 0 0,0 16,6Z"/>
          </svg>
        </div>
        <div class="metric-info">
          <h3>{{ dashboardData?.employees?.total_departments || 0 }}</h3>
          <p>Total Departments</p>
          <span class="metric-change neutral">{{ dashboardData?.employees?.inactive_employees || 0 }} inactive employees</span>
        </div>
      </div>
    </div>
    
    <!-- Charts Section -->
    <div class="charts-grid">
      <div class="chart-card">
        <div class="card-header">
          <h3>Attendance Trends</h3>
          <select v-model="attendancePeriod" class="form-select">
            <option value="7d">Last 7 days</option>
            <option value="30d">Last 30 days</option>
            <option value="3m">Last 3 months</option>
          </select>
        </div>
        <div ref="attendanceChartRef" class="chart-container"></div>
      </div>
      
      <div class="chart-card">
        <div class="card-header">
          <h3>Department Headcount</h3>
        </div>
        <div ref="departmentChartRef" class="chart-container"></div>
      </div>
    </div>
    
    <!-- Recent Activities and Quick Actions -->
    <div class="bottom-grid">
      <div class="activity-card">
        <div class="card-header">
          <h3>Recent Activities</h3>
          <button class="btn btn-link" @click="$router.push('/reports')">View All</button>
        </div>
        <div class="activity-list">
          <div v-if="recentActivities.length === 0" class="no-activities">
            <p>No recent activities found</p>
          </div>
          <div v-else v-for="activity in recentActivities" :key="activity.id" class="activity-item">
            <div class="activity-icon" :style="{ color: activity.color }">
              <component :is="activity.icon" />
            </div>
            <div class="activity-content">
              <p class="activity-text">{{ activity.text }}</p>
              <span class="activity-time">{{ formatTime(activity.time) }}</span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="quick-actions-card">
        <div class="card-header">
          <h3>Quick Actions</h3>
        </div>
        <div class="quick-actions">
          <button
            v-for="action in quickActions"
            :key="action.name"
            :class="`btn btn-${action.type} action-button`"
            @click="handleQuickAction(action.route)"
          >
            <component :is="action.icon" class="btn-icon" />
            {{ action.name }}
          </button>
        </div>
      </div>
    </div>
    
    <!-- Debug Panel for troubleshooting -->
    <!-- Debug Panel removed to fix component resolution issues -->
  </div>
</template>

<script>
import { ref, computed, onMounted, nextTick, markRaw } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import * as echarts from 'echarts'
import errorLogger from '@/utils/errorLogger'
// import DebugPanel from '@/components/DebugPanel.vue' // Temporarily disabled
import {
  User,
  Clock,
  Calendar,
  UserFilled,
  Plus,
  Document,
  Setting,
  DataAnalysis,
  Bell,
  Check,
  Warning
} from '@element-plus/icons-vue'

export default {
  name: 'Dashboard',
  components: {
    User,
    Clock,
    Calendar,
    UserFilled,
    Plus,
    Document,
    Setting,
    DataAnalysis,
    Bell,
    Check,
    Warning
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    
    const attendanceChartRef = ref(null)
    const departmentChartRef = ref(null)
    const attendancePeriod = ref('30d')
    const loading = ref(false)
    const error = ref(null)
    
    // Debug logging
    errorLogger.logComponentLifecycle('Dashboard', 'setup', {
      store: !!store,
      router: !!router,
      echarts: !!echarts
    })
    
    const dashboardData = computed(() => {
      try {
        const data = store.getters['reports/dashboardData']
        errorLogger.log('info', 'Dashboard data computed', { data })
        return data
      } catch (err) {
        errorLogger.log('error', 'Error computing dashboard data', { error: err.message })
        return null
      }
    })
    
    const attendanceChangeClass = computed(() => {
      const change = dashboardData.value?.attendance?.attendance_rate || 0
      return change > 85 ? 'positive' : change < 70 ? 'negative' : 'neutral'
    })
    
    const recentActivities = computed(() => {
      if (!dashboardData.value?.recent_activities) {
        return []
      }
      
      return dashboardData.value.recent_activities.map((activity, index) => {
        const activityConfig = getActivityConfig(activity.type)
        return {
          id: index + 1,
          icon: markRaw(activityConfig.icon),
          color: activityConfig.color,
          text: activity.description,
          time: new Date(activity.date)
        }
      })
    })
    
    const getActivityConfig = (type) => {
      const configs = {
        'hire': { icon: Plus, color: '#409EFF' },
        'leave': { icon: Check, color: '#67C23A' },
        'attendance': { icon: Bell, color: '#E6A23C' },
        'payroll': { icon: Warning, color: '#F56C6C' },
        'default': { icon: Bell, color: '#909399' }
      }
      return configs[type] || configs.default
    }
    
    const quickActions = ref([
      { name: 'Add Employee', type: 'primary', icon: markRaw(Plus), route: '/employees/add' },
      { name: 'Generate Payroll', type: 'success', icon: markRaw(Document), route: '/payroll/generate' },
      { name: 'View Reports', type: 'info', icon: markRaw(DataAnalysis), route: '/reports' },
      { name: 'Settings', type: 'warning', icon: markRaw(Setting), route: '/settings' }
    ])
    
    const formatTime = (time) => {
      const now = new Date()
      const diff = now - time
      const minutes = Math.floor(diff / (1000 * 60))
      const hours = Math.floor(diff / (1000 * 60 * 60))
      const days = Math.floor(diff / (1000 * 60 * 60 * 24))
      
      if (minutes < 60) {
        return `${minutes} minutes ago`
      } else if (hours < 24) {
        return `${hours} hours ago`
      } else {
        return `${days} days ago`
      }
    }
    
    const handleQuickAction = (route) => {
      try {
        errorLogger.log('info', 'Quick action clicked', { route })
        router.push(route)
      } catch (err) {
        errorLogger.log('error', 'Error handling quick action', {
          route,
          error: err.message
        })
      }
    }
    
    const initAttendanceChart = () => {
      try {
        errorLogger.log('info', 'Initializing attendance chart')
        if (!attendanceChartRef.value) {
          errorLogger.log('error', 'Attendance chart DOM element not found')
          return
        }
        
        const chart = echarts.init(attendanceChartRef.value)
        errorLogger.log('info', 'Attendance chart DOM found and initialized')
      const option = {
        tooltip: {
          trigger: 'axis'
        },
        xAxis: {
          type: 'category',
          data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
        },
        yAxis: {
          type: 'value',
          max: 100
        },
        series: [{
          data: [95, 92, 88, 94, 90, 85, 87],
          type: 'line',
          smooth: true,
          itemStyle: {
            color: '#409EFF'
          },
          areaStyle: {
            color: {
              type: 'linear',
              x: 0,
              y: 0,
              x2: 0,
              y2: 1,
              colorStops: [{
                offset: 0, color: 'rgba(64, 158, 255, 0.3)'
              }, {
                offset: 1, color: 'rgba(64, 158, 255, 0.1)'
              }]
            }
          }
        }]
      }
      chart.setOption(option)
      } catch (err) {
        errorLogger.log('error', 'Error initializing attendance chart', {
          message: err.message,
          stack: err.stack
        })
      }
    }
    
    const initDepartmentChart = () => {
      try {
        errorLogger.log('info', 'Initializing department chart')
        if (!departmentChartRef.value) {
          errorLogger.log('error', 'Department chart DOM element not found')
          return
        }
        
        const chart = echarts.init(departmentChartRef.value)
        errorLogger.log('info', 'Department chart DOM found and initialized')
      const option = {
        tooltip: {
          trigger: 'item'
        },
        series: [{
          type: 'pie',
          radius: '70%',
          data: [
            { value: 35, name: 'Engineering' },
            { value: 25, name: 'Sales' },
            { value: 20, name: 'Marketing' },
            { value: 15, name: 'HR' },
            { value: 5, name: 'Finance' }
          ],
          emphasis: {
            itemStyle: {
              shadowBlur: 10,
              shadowOffsetX: 0,
              shadowColor: 'rgba(0, 0, 0, 0.5)'
            }
          }
        }]
      }
      chart.setOption(option)
      } catch (err) {
        errorLogger.log('error', 'Error initializing department chart', {
          message: err.message,
          stack: err.stack
        })
      }
    }
    
    onMounted(async () => {
      errorLogger.logComponentLifecycle('Dashboard', 'onMounted', { timestamp: new Date().toISOString() })
      
      try {
        loading.value = true
        error.value = null
        
        errorLogger.log('info', 'Starting dashboard data fetch')
        
        // Check if store modules exist
        if (!store.hasModule || !store.hasModule('reports')) {
          errorLogger.log('warning', 'Reports store module not found', {
            availableModules: Object.keys(store._modules.root._children || {})
          })
        }
        
        const result = await store.dispatch('reports/fetchDashboardData')
        
        if (!result.success) {
          error.value = result.message || 'Failed to load dashboard data'
          errorLogger.log('error', 'Dashboard data fetch failed', { result })
          return
        }
        
        errorLogger.log('info', 'Dashboard data fetched successfully')
        
        // Debug: Log the dashboard data to console
        console.log('Dashboard data loaded:', dashboardData.value)
        console.log('Employee statistics:', dashboardData.value?.employees)
        console.log('Attendance statistics:', dashboardData.value?.attendance)
        console.log('Leave statistics:', dashboardData.value?.leaves)
        console.log('Payroll statistics:', dashboardData.value?.payroll)
        
        errorLogger.log('info', 'Initializing charts')
        nextTick(() => {
          initAttendanceChart()
          initDepartmentChart()
        })
        errorLogger.log('info', 'Charts initialized successfully')
        
      } catch (err) {
        console.error('Dashboard loading error:', err)
        error.value = 'Failed to load dashboard data'
        errorLogger.log('error', 'Dashboard mount error', {
          message: err.message,
          stack: err.stack,
          name: err.name
        })
      } finally {
        loading.value = false
        errorLogger.log('info', 'Dashboard loading completed', { hasError: !!error.value })
      }
    })
    
    return {
      attendanceChartRef,
      departmentChartRef,
      attendancePeriod,
      dashboardData,
      attendanceChangeClass,
      recentActivities,
      quickActions,
      loading,
      error,
      formatTime,
      handleQuickAction
    }
  }
}
</script>

<style scoped>
@import '../assets/styles/modern-hrms.css';

/* Dashboard Container */
.page-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 1.5rem;
}

/* Enhanced Page Header */
.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2.5rem 2rem;
  margin-bottom: 2rem;
  border-radius: 1.5rem;
  box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
  position: relative;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
  pointer-events: none;
}

.header-content {
  position: relative;
  z-index: 1;
}

.page-header h1 {
  font-size: 3rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
  text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  background: linear-gradient(45deg, #ffffff, #e2e8f0);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.page-header p {
  font-size: 1.2rem;
  opacity: 0.95;
  font-weight: 400;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Enhanced Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

/* Enhanced Metric Cards */
.metric-card {
  background: white;
  border-radius: 1.5rem;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(10px);
}

.metric-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c);
  background-size: 300% 100%;
  animation: gradientShift 3s ease infinite;
}

@keyframes gradientShift {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

.metric-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.metric-content {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.metric-icon {
  width: 70px;
  height: 70px;
  border-radius: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.75rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  position: relative;
  overflow: hidden;
}

.metric-icon::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transform: rotate(45deg);
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
  100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

.metric-info {
  flex: 1;
}

.metric-info h3 {
  font-size: 2.5rem;
  font-weight: 800;
  color: #1a202c;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #2d3748, #4a5568);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.metric-info p {
  color: #718096;
  font-weight: 600;
  font-size: 1rem;
  margin-bottom: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.metric-change {
  font-size: 0.875rem;
  font-weight: 700;
  padding: 0.5rem 1rem;
  border-radius: 2rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Enhanced Chart Cards */
.charts-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
  margin-bottom: 3rem;
}

@media (min-width: 1024px) {
  .charts-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.chart-card {
  background: white;
  border-radius: 1.5rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.3s ease;
}

.chart-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
}

.card-header {
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
}

/* Enhanced Bottom Grid */
.bottom-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
}

@media (min-width: 1024px) {
  .bottom-grid {
    grid-template-columns: 2fr 1fr;
  }
}

.activity-card,
.quick-actions-card {
  background: white;
  border-radius: 1.5rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.3s ease;
}

.activity-card:hover,
.quick-actions-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
}

/* Enhanced Activity List */
.activity-list {
  max-height: 400px;
  overflow-y: auto;
  padding: 1.5rem;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid #f7fafc;
  transition: all 0.2s ease;
}

.activity-item:hover {
  background: #f8fafc;
  border-radius: 0.75rem;
  padding-left: 1rem;
  padding-right: 1rem;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1.1rem;
  color: white;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Enhanced Quick Actions */
.quick-actions {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  padding: 1.5rem;
}

@media (min-width: 640px) {
  .quick-actions {
    grid-template-columns: 1fr 1fr;
  }
}

.action-button {
  height: 5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 1rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.action-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.action-button:hover::before {
  left: 100%;
}

.btn-icon {
  width: 1.5rem;
  height: 1.5rem;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.5rem;
  margin: 1rem 0;
}

.error-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.error-container h3 {
  color: #dc2626;
  margin-bottom: 0.5rem;
}

.error-container p {
  color: #7f1d1d;
}

.charts-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

@media (min-width: 1024px) {
  .charts-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.chart-container {
  height: 300px;
  width: 100%;
}

.bottom-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

@media (min-width: 1024px) {
  .bottom-grid {
    grid-template-columns: 2fr 1fr;
  }
}

.activity-list {
  max-height: 400px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f3f4f6;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1rem;
}

.activity-content {
  flex: 1;
}

.activity-text {
  margin: 0 0 0.25rem 0;
  color: #1f2937;
  font-size: 0.875rem;
  line-height: 1.4;
}

.activity-time {
  color: #6b7280;
  font-size: 0.75rem;
}

.quick-actions {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
}

@media (min-width: 640px) {
  .quick-actions {
    grid-template-columns: 1fr 1fr;
  }
}

.action-button {
  height: 4rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  font-size: 0.75rem;
}

.btn-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.metric-icon.employees {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.metric-icon.attendance {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.metric-icon.leaves {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.metric-icon.recruitment {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.metric-change.positive {
  color: #10b981;
}

.metric-change.negative {
  color: #ef4444;
}

.metric-change.neutral {
  color: #6b7280;
}
</style>