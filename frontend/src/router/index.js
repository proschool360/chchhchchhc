import { createRouter, createWebHistory } from 'vue-router'
import store from '../store'

// Auth Components
const Login = () => import('../views/auth/Login.vue')

// Dashboard
const Dashboard = () => import('../views/Dashboard.vue')

// Demo
const EmployeeDropdownDemo = () => import('../views/demo/EmployeeDropdownDemo.vue')

// Employee Management
const EmployeeList = () => import('../views/employees/EmployeeList.vue')
const EmployeeDetail = () => import('../views/employees/EmployeeDetail.vue')
const EmployeeForm = () => import('../views/employees/EmployeeForm.vue')
const QuickSelect = () => import('../views/employees/QuickSelect.vue')

// Attendance
const AttendanceList = () => import('../views/attendance/AttendanceList.vue')
const AttendanceManagement = () => import('../views/attendance/AttendanceManagement.vue')
const BiometricAttendance = () => import('../views/attendance/components/BiometricAttendance.vue')
const RFIDAttendance = () => import('../views/attendance/components/RFIDAttendance.vue')
const ManualAttendance = () => import('../views/attendance/components/ManualAttendance.vue')
const QRAttendance = () => import('../views/attendance/components/QRAttendance.vue')
const AttendanceReports = () => import('../views/attendance/components/AttendanceReports.vue')

// ID Cards
const IdCardManagement = () => import('../views/idcards/IdCardManagement.vue')
const TemplateBuilder = () => import('../views/idcards/TemplateBuilder.vue')

// Departments
const DepartmentManagement = () => import('../views/departments/DepartmentManagement.vue')

// Positions  
const PositionManagement = () => import('../views/positions/PositionManagement.vue')

// Demo
// const EmployeeDropdownDemo = () => import('../views/demo/EmployeeDropdownDemo.vue')

// Leave Management
const LeaveList = () => import('../views/leave/LeaveList.vue')

// Payroll
const PayrollList = () => import('../views/payroll/PayrollList.vue')

// Recruitment
const RecruitmentList = () => import('../views/recruitment/RecruitmentList.vue')

// Performance
const PerformanceList = () => import('../views/performance/PerformanceList.vue')

// Training
const TrainingList = () => import('../views/training/TrainingList.vue')

// Reports
const ReportsList = () => import('../views/reports/ReportsList.vue')

// Settings
const SettingsList = () => import('../views/settings/SettingsList.vue')

const routes = [
  {
    path: '/',
    redirect: to => {
      // Check if user is authenticated
      const isAuthenticated = store.getters['auth/isAuthenticated']
      return isAuthenticated ? '/dashboard' : '/login'
    }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: {
      title: 'Login',
      requiresAuth: false
    }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: {
      title: 'Dashboard',
      requiresAuth: true
    }
  },
  // {
  //   path: '/demo/employee-dropdown',
  //   name: 'EmployeeDropdownDemo',
  //   component: EmployeeDropdownDemo,
  //   meta: {
  //     title: 'Employee Dropdown Demo',
  //     requiresAuth: true
  //   }
  // },
  
  // Employee Management Routes
  {
    path: '/employees',
    name: 'EmployeeList',
    component: EmployeeList,
    meta: {
      title: 'Employees',
      requiresAuth: true
    }
  },
  {
    path: '/departments',
    name: 'DepartmentManagement',
    component: DepartmentManagement,
    meta: {
      title: 'Department Management',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/positions',
    name: 'PositionManagement',
    component: PositionManagement,
    meta: {
      title: 'Position Management',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/employees/add',
    name: 'EmployeeForm',
    component: EmployeeForm,
    meta: {
      title: 'Add Employee',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/employees/:id/edit',
    name: 'EmployeeEdit',
    component: EmployeeForm,
    meta: {
      title: 'Edit Employee',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/employees/:id',
    name: 'EmployeeDetail',
    component: EmployeeDetail,
    meta: {
      title: 'Employee Details',
      requiresAuth: true
    }
  },
  {
    path: '/employees/quick-select',
    name: 'QuickEmployeeSelect',
    component: QuickSelect,
    meta: {
      title: 'Quick Employee Select',
      requiresAuth: true
    }
  },
  
  // Attendance Routes
  {
    path: '/attendance',
    name: 'AttendanceList',
    component: AttendanceList,
    meta: {
      title: 'Attendance Management',
      requiresAuth: true
    }
  },
  {
    path: '/attendance/today',
    name: 'AttendanceToday',
    component: AttendanceList,
    meta: {
      title: "Today's Attendance",
      requiresAuth: true
    }
  },
  {
    path: '/attendance/records',
    name: 'AttendanceRecords',
    component: AttendanceList,
    meta: {
      title: 'Attendance Records',
      requiresAuth: true
    }
  },
  {
    path: '/attendance/management',
    name: 'AttendanceManagement',
    component: AttendanceManagement,
    meta: {
      title: 'Extended Attendance Management',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/attendance/qr-checkin',
    name: 'QRCheckin',
    component: AttendanceManagement,
    meta: {
      title: 'QR Code Check-in',
      requiresAuth: true
    }
  },
  {
    path: '/attendance/rfid',
    name: 'RFIDAttendance',
    component: RFIDAttendance,
    meta: {
      title: 'RFID Attendance',
      requiresAuth: true
    }
  },
  {
    path: '/attendance/biometric',
    name: 'BiometricAttendance',
    component: BiometricAttendance,
    meta: {
      title: 'Biometric Attendance',
      requiresAuth: true
    }
  },
  {
    path: '/attendance/manual',
    name: 'ManualAttendance',
    component: ManualAttendance,
    meta: {
      title: 'Manual Attendance',
      requiresAuth: true
    }
  },
  {
    path: '/attendance/qr',
    name: 'QRAttendance',
    component: QRAttendance,
    meta: {
      title: 'QR Attendance',
      requiresAuth: true
    }
  },
  {
    path: '/attendance/reports-detailed',
    name: 'AttendanceReportsDetailed',
    component: AttendanceReports,
    meta: {
      title: 'Detailed Attendance Reports',
      requiresAuth: true
    }
  },
  {
    path: '/attendance/devices',
    name: 'AttendanceDevices',
    component: AttendanceManagement,
    meta: {
      title: 'Attendance Devices',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/attendance/reports',
    name: 'AttendanceReports',
    component: ReportsList,
    meta: {
      title: 'Attendance Reports',
      requiresAuth: true
    }
  },
  
  // Leave Management Routes
  {
    path: '/leave',
    name: 'LeaveList',
    component: LeaveList,
    meta: {
      title: 'Leave Management',
      requiresAuth: true
    }
  },
  {
    path: '/leave/requests',
    name: 'LeaveRequests',
    component: LeaveList,
    meta: {
      title: 'Leave Requests',
      requiresAuth: true
    }
  },
  {
    path: '/leave/apply',
    name: 'LeaveApply',
    component: LeaveList,
    meta: {
      title: 'Apply Leave',
      requiresAuth: true
    }
  },
  {
    path: '/leave/balance',
    name: 'LeaveBalance',
    component: LeaveList,
    meta: {
      title: 'Leave Balance',
      requiresAuth: true
    }
  },
  {
    path: '/leave/calendar',
    name: 'LeaveCalendar',
    component: LeaveList,
    meta: {
      title: 'Leave Calendar',
      requiresAuth: true
    }
  },
  
  // Payroll Routes
  {
    path: '/payroll',
    name: 'PayrollList',
    component: PayrollList,
    meta: {
      title: 'Payroll Management',
      requiresAuth: true,
      roles: ['admin', 'hr', 'manager']
    }
  },
  {
    path: '/payroll/records',
    name: 'PayrollRecords',
    component: PayrollList,
    meta: {
      title: 'Payroll Records',
      requiresAuth: true,
      roles: ['admin', 'hr', 'manager']
    }
  },
  {
    path: '/payroll/generate',
    name: 'PayrollGenerate',
    component: PayrollList,
    meta: {
      title: 'Generate Payroll',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/payroll/reports',
    name: 'PayrollReports',
    component: ReportsList,
    meta: {
      title: 'Payroll Reports',
      requiresAuth: true,
      roles: ['admin', 'hr', 'manager']
    }
  },
  
  // Recruitment Routes
  {
    path: '/recruitment',
    name: 'RecruitmentList',
    component: RecruitmentList,
    meta: {
      title: 'Recruitment Management',
      requiresAuth: true,
      roles: ['admin', 'hr', 'manager']
    }
  },
  {
    path: '/recruitment/jobs',
    name: 'RecruitmentJobs',
    component: RecruitmentList,
    meta: {
      title: 'Job Postings',
      requiresAuth: true,
      roles: ['admin', 'hr', 'manager']
    }
  },
  {
    path: '/recruitment/applications',
    name: 'RecruitmentApplications',
    component: RecruitmentList,
    meta: {
      title: 'Applications',
      requiresAuth: true,
      roles: ['admin', 'hr', 'manager']
    }
  },
  {
    path: '/recruitment/interviews',
    name: 'RecruitmentInterviews',
    component: RecruitmentList,
    meta: {
      title: 'Interviews',
      requiresAuth: true,
      roles: ['admin', 'hr', 'manager']
    }
  },
  
  // Performance Routes
  {
    path: '/performance',
    name: 'PerformanceList',
    component: PerformanceList,
    meta: {
      title: 'Performance Management',
      requiresAuth: true
    }
  },
  {
    path: '/performance/reviews',
    name: 'PerformanceReviews',
    component: PerformanceList,
    meta: {
      title: 'Performance Reviews',
      requiresAuth: true
    }
  },
  {
    path: '/performance/goals',
    name: 'PerformanceGoals',
    component: PerformanceList,
    meta: {
      title: 'Goals & KPIs',
      requiresAuth: true
    }
  },
  {
    path: '/performance/feedback',
    name: 'PerformanceFeedback',
    component: PerformanceList,
    meta: {
      title: '360° Feedback',
      requiresAuth: true
    }
  },
  
  // Training Routes
  {
    path: '/training',
    name: 'TrainingList',
    component: TrainingList,
    meta: {
      title: 'Training Management',
      requiresAuth: true
    }
  },
  {
    path: '/training/programs',
    name: 'TrainingPrograms',
    component: TrainingList,
    meta: {
      title: 'Training Programs',
      requiresAuth: true
    }
  },
  {
    path: '/training/enrollments',
    name: 'TrainingEnrollments',
    component: TrainingList,
    meta: {
      title: 'My Trainings',
      requiresAuth: true
    }
  },
  {
    path: '/training/calendar',
    name: 'TrainingCalendar',
    component: TrainingList,
    meta: {
      title: 'Training Calendar',
      requiresAuth: true
    }
  },
  {
    path: '/training/skills',
    name: 'TrainingSkills',
    component: TrainingList,
    meta: {
      title: 'Skill Assessment',
      requiresAuth: true
    }
  },
  
  // Reports Routes
  {
    path: '/reports',
    name: 'ReportsList',
    component: ReportsList,
    meta: {
      title: 'Reports & Analytics',
      requiresAuth: true,
      roles: ['admin', 'hr', 'manager']
    }
  },
  
  // ID Cards Routes
  {
    path: '/idcards/management',
    name: 'IdCardManagement',
    component: IdCardManagement,
    meta: {
      title: 'ID Card Management',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/idcards/template-builder',
    name: 'TemplateBuilder',
    component: TemplateBuilder,
    meta: {
      title: 'ID Card Template Builder',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/demo/employee-dropdown',
    name: 'EmployeeDropdownDemo',
    component: EmployeeDropdownDemo,
    meta: {
      title: 'Employee Dropdown Demo',
      requiresAuth: true
    }
  },
  {
    path: '/idcards/templates',
    name: 'IdCardTemplates',
    component: TemplateBuilder,
    meta: {
      title: 'ID Card Templates',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/idcards/print',
    name: 'IdCardPrint',
    component: IdCardManagement,
    meta: {
      title: 'Print ID Cards',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },

  // Settings Routes
  {
    path: '/settings',
    name: 'SettingsList',
    component: SettingsList,
    meta: {
      title: 'System Settings',
      requiresAuth: true,
      roles: ['admin']
    }
  },
  {
    path: '/settings/company',
    name: 'CompanySettings',
    component: SettingsList,
    meta: {
      title: 'Company Settings',
      requiresAuth: true,
      roles: ['admin']
    }
  },
  {
    path: '/settings/attendance',
    name: 'AttendanceSettings',
    component: SettingsList,
    meta: {
      title: 'Attendance Settings',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/settings/work-schedules',
    name: 'WorkSchedules',
    component: SettingsList,
    meta: {
      title: 'Work Schedules',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/settings/salary-deduction',
    name: 'SalaryDeductionRules',
    component: SettingsList,
    meta: {
      title: 'Salary Deduction Rules',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/settings/overtime',
    name: 'OvertimeRules',
    component: SettingsList,
    meta: {
      title: 'Overtime Rules',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/settings/leave-types',
    name: 'LeaveTypes',
    component: SettingsList,
    meta: {
      title: 'Leave Types',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/settings/holidays',
    name: 'Holidays',
    component: SettingsList,
    meta: {
      title: 'Holidays',
      requiresAuth: true,
      roles: ['admin', 'hr']
    }
  },
  {
    path: '/settings/users',
    name: 'UserManagement',
    component: SettingsList,
    meta: {
      title: 'User Management',
      requiresAuth: true,
      roles: ['admin']
    }
  },
  
  // Catch all route
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard'
  }
]

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const isAuthenticated = store.getters['auth/isAuthenticated']
  const userRole = store.getters['auth/userRole']
  
  // Check if route requires authentication
  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login')
    return
  }
  
  // Check if user has required role
  if (to.meta.roles && !to.meta.roles.includes(userRole)) {
    next('/dashboard')
    return
  }
  
  // Redirect authenticated users away from login
  if (to.path === '/login' && isAuthenticated) {
    next('/dashboard')
    return
  }
  
  next()
})

export default router