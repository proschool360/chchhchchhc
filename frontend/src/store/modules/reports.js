import axios from 'axios'
import errorLogger from '@/utils/errorLogger'

const API_BASE_URL = 'https://whatsapp.proschool360.com/api'

const state = {
  dashboardData: null,
  reportData: null,
  loading: false,
  availableReports: [
    { id: 'employee', name: 'Employee Report', description: 'Comprehensive employee information' },
    { id: 'attendance', name: 'Attendance Report', description: 'Employee attendance summary' },
    { id: 'payroll', name: 'Payroll Report', description: 'Salary and payroll information' },
    { id: 'leave', name: 'Leave Report', description: 'Leave requests and balances' },
    { id: 'performance', name: 'Performance Report', description: 'Performance reviews and ratings' },
    { id: 'recruitment', name: 'Recruitment Report', description: 'Hiring and recruitment analytics' },
    { id: 'training', name: 'Training Report', description: 'Training programs and completion' }
  ]
}

const mutations = {
  SET_DASHBOARD_DATA(state, data) {
    state.dashboardData = data
  },
  
  SET_REPORT_DATA(state, data) {
    state.reportData = data
  },
  
  SET_LOADING(state, loading) {
    state.loading = loading
  }
}

const actions = {
  async fetchDashboardData({ commit }) {
    try {
      errorLogger.log('info', 'Starting fetchDashboardData action')
      commit('SET_LOADING', true)
      
      errorLogger.log('info', 'Making API request to dashboard endpoint', {
        url: `${API_BASE_URL}/reports/dashboard`
      })
      
      const response = await axios.get(`${API_BASE_URL}/reports/dashboard`)
      
      errorLogger.log('info', 'Dashboard API response received', {
        status: response.status,
        success: response.data?.success,
        dataKeys: response.data?.data ? Object.keys(response.data.data) : []
      })
      
      if (response.data.success) {
        commit('SET_DASHBOARD_DATA', response.data.data)
        errorLogger.log('info', 'Dashboard data committed to store', {
          data: response.data.data
        })
        return { success: true, data: response.data.data }
      } else {
        errorLogger.log('error', 'Dashboard API returned unsuccessful response', {
          message: response.data.message
        })
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      errorLogger.logApiError(
        `${API_BASE_URL}/reports/dashboard`,
        'GET',
        error.response?.status,
        error.response?.data,
        null
      )
      console.error('Fetch dashboard data error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch dashboard data' 
      }
    } finally {
      commit('SET_LOADING', false)
      errorLogger.log('info', 'fetchDashboardData action completed')
    }
  },
  
  async generateReport({ commit }, { reportType, params = {} }) {
    try {
      commit('SET_LOADING', true)
      const response = await axios.get(`${API_BASE_URL}/reports/${reportType}`, { params })
      
      if (response.data.success) {
        commit('SET_REPORT_DATA', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Generate report error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to generate report' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async downloadReport({ commit }, { reportType, params = {}, format = 'csv' }) {
    try {
      const response = await axios.get(`${API_BASE_URL}/reports/${reportType}`, {
        params: { ...params, format },
        responseType: 'blob'
      })
      
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      
      const contentDisposition = response.headers['content-disposition']
      let filename = `${reportType}_report.${format}`
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="(.+)"/)
        if (filenameMatch) {
          filename = filenameMatch[1]
        }
      }
      
      link.setAttribute('download', filename)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      
      return { success: true }
    } catch (error) {
      console.error('Download report error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to download report' 
      }
    }
  },
  
  async fetchHeadcountReport({ commit }, params = {}) {
    try {
      const response = await axios.get(`${API_BASE_URL}/reports/headcount`, { params })
      
      if (response.data.success) {
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch headcount report error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch headcount report' 
      }
    }
  },
  
  async fetchAttritionReport({ commit }, params = {}) {
    try {
      const response = await axios.get(`${API_BASE_URL}/reports/attrition`, { params })
      
      if (response.data.success) {
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch attrition report error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch attrition report' 
      }
    }
  },
  
  async fetchLeaveTrendsReport({ commit }, params = {}) {
    try {
      const response = await axios.get(`${API_BASE_URL}/reports/leave-trends`, { params })
      
      if (response.data.success) {
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch leave trends report error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch leave trends report' 
      }
    }
  }
}

const getters = {
  dashboardData: state => state.dashboardData,
  reportData: state => state.reportData,
  loading: state => state.loading,
  availableReports: state => state.availableReports,
  
  // Dashboard stats getter that matches what Dashboard.vue expects
  dashboardStats: state => {
    if (!state.dashboardData) {
      errorLogger.log('warning', 'Dashboard data is null in dashboardStats getter')
      return {
        totalEmployees: 0,
        attendanceRate: 0,
        pendingLeaves: 0,
        openPositions: 0
      }
    }
    
    const stats = {
      totalEmployees: state.dashboardData.total_employees || 0,
      attendanceRate: state.dashboardData.attendance_rate || 0,
      pendingLeaves: state.dashboardData.pending_leaves || 0,
      openPositions: state.dashboardData.open_positions || 0
    }
    
    errorLogger.log('info', 'Dashboard stats computed', { stats })
    return stats
  },
  
  totalEmployees: state => {
    return state.dashboardData?.total_employees || 0
  },
  
  presentToday: state => {
    return state.dashboardData?.present_today || 0
  },
  
  pendingLeaves: state => {
    return state.dashboardData?.pending_leaves || 0
  },
  
  monthlyPayroll: state => {
    return state.dashboardData?.monthly_payroll || 0
  }
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}