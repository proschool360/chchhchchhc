<template>
  <aside class="sidebar" :class="{ collapsed: isCollapsed }">
    <div class="sidebar-header">
      <div class="logo">
        <el-icon><OfficeBuilding /></el-icon>
        <span v-if="!isCollapsed" class="logo-text">HRMS</span>
      </div>
      <el-button link @click="toggleCollapse" class="collapse-btn">
        <el-icon><Fold v-if="!isCollapsed" /><Expand v-else /></el-icon>
      </el-button>
    </div>
    <nav class="sidebar-nav">
      <el-menu
        :default-active="$route.path"
        :collapse="isCollapsed"
        :unique-opened="true"
        router
        class="sidebar-menu"
      >
        <el-menu-item index="/dashboard">
          <el-icon><Odometer /></el-icon>
          <span>Dashboard</span>
        </el-menu-item>

        <el-sub-menu index="quick-access">
          <template #title>
            <el-icon><Star /></el-icon>
            <span>Quick Access</span>
          </template>
          <el-menu-item index="/attendance/qr-checkin">QR Attendance</el-menu-item>
          <el-menu-item index="/attendance/rfid">RFID Attendance</el-menu-item>
          <el-menu-item index="/attendance/biometric">Biometric Attendance</el-menu-item>
          <el-menu-item index="/attendance/manual">Manual Attendance</el-menu-item>
          <el-menu-item index="/idcards/management">Generate ID Cards</el-menu-item>
          <el-menu-item index="/employees/quick-select">Quick Employee Select</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="employees">
          <template #title>
            <el-icon><User /></el-icon>
            <span>Employees</span>
          </template>
          <el-menu-item index="/employees">All Employees</el-menu-item>
          <el-menu-item index="/employees/add">Add Employee</el-menu-item>
          <el-menu-item index="/departments">Departments</el-menu-item>
          <el-menu-item index="/positions">Positions</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="attendance">
          <template #title>
            <el-icon><Clock /></el-icon>
            <span>Attendance</span>
          </template>
          <el-menu-item index="/attendance/today">Today's Attendance</el-menu-item>
          <el-menu-item index="/attendance/records">Attendance Records</el-menu-item>
          <el-menu-item index="/attendance/management">Attendance Management</el-menu-item>
          <el-menu-item index="/attendance/qr-checkin">QR Check-in</el-menu-item>
          <el-menu-item index="/attendance/devices" v-if="canAccessSettings">Attendance Devices</el-menu-item>
          <el-menu-item index="/attendance/reports">Reports</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="leave">
          <template #title>
            <el-icon><Calendar /></el-icon>
            <span>Leave</span>
          </template>
          <el-menu-item index="/leave/requests">Leave Requests</el-menu-item>
          <el-menu-item index="/leave/apply">Apply Leave</el-menu-item>
          <el-menu-item index="/leave/balance">Leave Balance</el-menu-item>
          <el-menu-item index="/leave/calendar">Leave Calendar</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="payroll" v-if="canAccessPayroll">
          <template #title>
            <el-icon><Money /></el-icon>
            <span>Payroll</span>
          </template>
          <el-menu-item index="/payroll/records">Payroll Records</el-menu-item>
          <el-menu-item index="/payroll/generate">Generate Payroll</el-menu-item>
          <el-menu-item index="/payroll/reports">Payroll Reports</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="recruitment" v-if="canAccessRecruitment">
          <template #title>
            <el-icon><UserFilled /></el-icon>
            <span>Recruitment</span>
          </template>
          <el-menu-item index="/recruitment/jobs">Job Postings</el-menu-item>
          <el-menu-item index="/recruitment/applications">Applications</el-menu-item>
          <el-menu-item index="/recruitment/interviews">Interviews</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="performance">
          <template #title>
            <el-icon><TrendCharts /></el-icon>
            <span>Performance</span>
          </template>
          <el-menu-item index="/performance/reviews">Performance Reviews</el-menu-item>
          <el-menu-item index="/performance/goals">Goals & KPIs</el-menu-item>
          <el-menu-item index="/performance/feedback">360° Feedback</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="training">
          <template #title>
            <el-icon><Reading /></el-icon>
            <span>Training</span>
          </template>
          <el-menu-item index="/training/programs">Training Programs</el-menu-item>
          <el-menu-item index="/training/enrollments">My Trainings</el-menu-item>
          <el-menu-item index="/training/calendar">Training Calendar</el-menu-item>
          <el-menu-item index="/training/skills">Skill Assessment</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="reports" v-if="canAccessReports">
          <template #title>
            <el-icon><DataAnalysis /></el-icon>
            <span>Reports</span>
          </template>
          <el-menu-item index="/reports/dashboard">HR Dashboard</el-menu-item>
          
          <!-- Attendance Reports -->
          <el-sub-menu index="attendance-reports">
            <template #title>
              <span>Attendance Reports</span>
            </template>
            <el-menu-item index="/reports/attendance/daily">Daily Attendance</el-menu-item>
            <el-menu-item index="/reports/attendance/weekly">Weekly Attendance</el-menu-item>
            <el-menu-item index="/reports/attendance/monthly">Monthly Attendance</el-menu-item>
            <el-menu-item index="/reports/attendance/export">Export Attendance</el-menu-item>
          </el-sub-menu>
          
          <!-- Analytics Reports -->
          <el-sub-menu index="analytics-reports">
            <template #title>
              <span>Analytics</span>
            </template>
            <el-menu-item index="/reports/headcount">Headcount Report</el-menu-item>
            <el-menu-item index="/reports/attrition">Attrition Analysis</el-menu-item>
            <el-menu-item index="/reports/leave-trends">Leave Trends</el-menu-item>
          </el-sub-menu>
          
          <!-- MIS Reports -->
          <el-sub-menu index="mis-reports">
            <template #title>
              <span>MIS Reports</span>
            </template>
            <el-menu-item index="/reports/mis/employee">Employee MIS</el-menu-item>
            <el-menu-item index="/reports/mis/attendance">Attendance MIS</el-menu-item>
            <el-menu-item index="/reports/mis/payroll">Payroll MIS</el-menu-item>
            <el-menu-item index="/reports/mis/leave">Leave MIS</el-menu-item>
            <el-menu-item index="/reports/mis/performance">Performance MIS</el-menu-item>
            <el-menu-item index="/reports/mis/recruitment">Recruitment MIS</el-menu-item>
            <el-menu-item index="/reports/mis/training">Training MIS</el-menu-item>
          </el-sub-menu>
          
          <el-menu-item index="/reports/custom">Custom Reports</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="idcards" v-if="canAccessSettings">
          <template #title>
            <el-icon><CreditCard /></el-icon>
            <span>ID Cards</span>
          </template>
          <el-menu-item index="/idcards/management">ID Card Management</el-menu-item>
          <el-menu-item index="/idcards/templates">Template Builder</el-menu-item>
          <el-menu-item index="/idcards/print">Print Cards</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="settings" v-if="canAccessSettings">
          <template #title>
            <el-icon><Setting /></el-icon>
            <span>Settings</span>
          </template>
          <el-menu-item index="/settings/company">Company Settings</el-menu-item>
          <el-menu-item index="/settings/attendance">Attendance Settings</el-menu-item>
          <el-menu-item index="/settings/work-schedules">Work Schedules</el-menu-item>
          <el-menu-item index="/settings/salary-deduction">Salary Deduction Rules</el-menu-item>
          <el-menu-item index="/settings/overtime">Overtime Rules</el-menu-item>
          <el-menu-item index="/settings/leave-types">Leave Types</el-menu-item>
          <el-menu-item index="/settings/holidays">Holidays</el-menu-item>
          <el-menu-item index="/settings/users">User Management</el-menu-item>
        </el-sub-menu>
      </el-menu>
    </nav>
  </aside>
</template>

<script>
import {
  OfficeBuilding,
  Fold,
  Expand,
  Odometer,
  User,
  Clock,
  Calendar,
  Money,
  UserFilled,
  TrendCharts,
  Reading,
  DataAnalysis,
  Setting,
  CreditCard,
  Star,
  Camera,
  Monitor,
  View,
  Edit
} from '@element-plus/icons-vue'
import { computed, ref } from 'vue'
import { useStore } from 'vuex'
import { useRoute } from 'vue-router'

export default {
  name: 'Sidebar',
  props: {
    isCollapsed: {
      type: Boolean,
      default: false
    }
  },
  emits: ['toggle-collapse'],
  components: {
    OfficeBuilding,
    Fold,
    Expand,
    Odometer,
    User,
    Clock,
    Calendar,
    Money,
    UserFilled,
    TrendCharts,
    Reading,
    DataAnalysis,
    Setting,
    CreditCard,
    Star,
    Camera,
    Monitor,
    View,
    Edit
  },
  setup(props, { emit }) {
    const store = useStore()
    const route = useRoute()

    const userRole = computed(() => store.getters['auth/userRole'])

    const canAccessPayroll = computed(() => {
      return ['admin', 'hr', 'manager'].includes(userRole.value)
    })

    const canAccessRecruitment = computed(() => {
      return ['admin', 'hr', 'manager'].includes(userRole.value)
    })

    const canAccessReports = computed(() => {
      return ['admin', 'hr', 'manager'].includes(userRole.value)
    })

    const canAccessSettings = computed(() => {
      return ['admin', 'hr'].includes(userRole.value)
    })

    const toggleCollapse = () => {
      emit('toggle-collapse')
    }

    return {
      canAccessPayroll,
      canAccessRecruitment,
      canAccessReports,
      canAccessSettings,
      toggleCollapse
    }
  }
}
</script>

<style scoped lang="scss">
.sidebar {
  width: 250px;
  background: #001529;
  color: white;
  transition: width 0.3s ease;
  overflow: hidden;

  &.collapsed {
    width: 64px;
  }

  .sidebar-header {
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    border-bottom: 1px solid #1f2937;

    .logo {
      display: flex;
      align-items: center;
      gap: 8px;

      .el-icon {
        font-size: 24px;
        color: #1890ff;
      }

      .logo-text {
        font-size: 20px;
        font-weight: bold;
        color: white;
      }
    }

    .collapse-btn {
      color: white;

      &:hover {
        background: rgba(255, 255, 255, 0.1);
      }
    }
  }

  .sidebar-nav {
    height: calc(100vh - 64px);
    overflow-y: auto;

    .sidebar-menu {
      border: none;
      background: transparent;

      .el-menu-item,
      .el-sub-menu__title {
        color: rgba(0, 0, 0, 0.8);

        &:hover {
          background: rgba(255, 255, 255, 0.1);
          
        }

        &.is-active {
          background: #1890ff;
          color: rgba(255, 255, 255, 0.044);
        }
      }

      .el-sub-menu .el-menu-item {
        background: rgba(177, 172, 172, 0.2);

        &:hover {
          background: rgba(255, 255, 255, 0.1);
        }

        &.is-active {
          background: #1890ff;
        }
      }

      .menu-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        color: #1890ff;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 16px;
        border-bottom: 1px solid rgba(24, 144, 255, 0.2);

        .el-icon {
          font-size: 16px;
        }

        span {
          font-size: 12px;
        }
      }

      .menu-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 16px 20px;
      }
    }
  }
}
</style>