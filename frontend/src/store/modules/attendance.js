import axios from 'axios'

const API_BASE_URL = 'https://whatsapp.proschool360.com/api'

const state = {
  attendanceRecords: [],
  todayAttendance: null,
  attendanceStats: null,
  loading: false,
  pagination: {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  },
  filters: {
    employee_id: null,
    date_from: null,
    date_to: null,
    status: null
  }
}

const mutations = {
  SET_ATTENDANCE_RECORDS(state, data) {
    state.attendanceRecords = data.records || []
    if (data.pagination) {
      state.pagination = data.pagination
    }
  },
  
  SET_TODAY_ATTENDANCE(state, attendance) {
    state.todayAttendance = attendance
  },
  
  SET_ATTENDANCE_STATS(state, stats) {
    state.attendanceStats = stats
  },
  
  ADD_ATTENDANCE_RECORD(state, record) {
    state.attendanceRecords.unshift(record)
  },
  
  UPDATE_ATTENDANCE_RECORD(state, updatedRecord) {
    const index = state.attendanceRecords.findIndex(record => record.id === updatedRecord.id)
    if (index !== -1) {
      state.attendanceRecords.splice(index, 1, updatedRecord)
    }
  },
  
  UPDATE_TODAY_ATTENDANCE(state, attendance) {
    state.todayAttendance = attendance
  },
  
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  
  SET_FILTERS(state, filters) {
    state.filters = { ...state.filters, ...filters }
  },
  
  RESET_FILTERS(state) {
    state.filters = {
      employee_id: null,
      date_from: null,
      date_to: null,
      status: null
    }
  }
}

const actions = {
  async fetchAttendanceRecords({ commit, state }, params = {}) {
    try {
      commit('SET_LOADING', true)
      
      const queryParams = {
        page: state.pagination.current_page,
        per_page: state.pagination.per_page,
        ...state.filters,
        ...params
      }
      
      const response = await axios.get(`${API_BASE_URL}/attendance`, { params: queryParams })
      
      if (response.data.success) {
        commit('SET_ATTENDANCE_RECORDS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch attendance records error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch attendance records' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async fetchTodayAttendance({ commit }, employeeId = null) {
    try {
      const params = employeeId ? { employee_id: employeeId } : {}
      const response = await axios.get(`${API_BASE_URL}/attendance/today`, { params })
      
      if (response.data.success) {
        commit('SET_TODAY_ATTENDANCE', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch today attendance error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch today attendance' 
      }
    }
  },
  
  async checkIn({ commit }, data = {}) {
    try {
      const response = await axios.post(`${API_BASE_URL}/attendance/checkin`, data)
      
      if (response.data.success) {
        commit('UPDATE_TODAY_ATTENDANCE', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Check in error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to check in' 
      }
    }
  },
  
  async checkOut({ commit }, data = {}) {
    try {
      const response = await axios.post(`${API_BASE_URL}/attendance/checkout`, data)
      
      if (response.data.success) {
        commit('UPDATE_TODAY_ATTENDANCE', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Check out error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to check out' 
      }
    }
  },
  
  async updateAttendance({ commit }, { id, data }) {
    try {
      const response = await axios.put(`${API_BASE_URL}/attendance/${id}`, data)
      
      if (response.data.success) {
        commit('UPDATE_ATTENDANCE_RECORD', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Update attendance error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to update attendance' 
      }
    }
  },
  
  async fetchAttendanceStats({ commit }, params = {}) {
    try {
      const response = await axios.get(`${API_BASE_URL}/attendance/stats`, { params })
      
      if (response.data.success) {
        commit('SET_ATTENDANCE_STATS', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch attendance stats error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch attendance statistics' 
      }
    }
  },
  
  async fetchTodayStats({ commit }) {
    try {
      const response = await axios.get(`${API_BASE_URL}/attendance/today-stats`)
      
      if (response.data.success) {
        commit('SET_ATTENDANCE_STATS', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch today stats error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch today statistics' 
      }
    }
  },
  
  async generateAttendanceReport({ commit }, params) {
    try {
      const response = await axios.get(`${API_BASE_URL}/attendance/reports/summary`, { 
        params,
        responseType: 'blob'
      })
      
      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      
      // Get filename from response headers
      const contentDisposition = response.headers['content-disposition']
      let filename = 'attendance_report.csv'
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
      console.error('Generate attendance report error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to generate attendance report' 
      }
    }
  },
  
  async generateDailyReport({ commit }, params) {
    try {
      const response = await axios.get(`${API_BASE_URL}/attendance/reports/daily`, { 
        params,
        responseType: 'blob'
      })
      
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      
      const contentDisposition = response.headers['content-disposition']
      let filename = 'daily_attendance_report.csv'
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
      console.error('Generate daily report error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to generate daily report' 
      }
    }
  },
  
  async generateMonthlyReport({ commit }, params) {
    try {
      const response = await axios.get(`${API_BASE_URL}/attendance/reports/monthly`, { 
        params,
        responseType: 'blob'
      })
      
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      
      const contentDisposition = response.headers['content-disposition']
      let filename = 'monthly_attendance_report.csv'
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
      console.error('Generate monthly report error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to generate monthly report' 
      }
    }
  },
  
  setFilters({ commit }, filters) {
    commit('SET_FILTERS', filters)
  },
  
  resetFilters({ commit }) {
    commit('RESET_FILTERS')
  }
}

const getters = {
  attendanceRecords: state => state.attendanceRecords,
  todayAttendance: state => state.todayAttendance,
  attendanceStats: state => state.attendanceStats,
  todayStats: state => state.attendanceStats || {
    present: 0,
    absent: 0,
    late: 0,
    early_departure: 0
  },
  loading: state => state.loading,
  pagination: state => state.pagination,
  filters: state => state.filters,
  
  isCheckedIn: state => {
    return state.todayAttendance && state.todayAttendance.check_in_time && !state.todayAttendance.check_out_time
  },
  
  isCheckedOut: state => {
    return state.todayAttendance && state.todayAttendance.check_in_time && state.todayAttendance.check_out_time
  },
  
  canCheckIn: state => {
    return !state.todayAttendance || (!state.todayAttendance.check_in_time)
  },
  
  canCheckOut: state => {
    return state.todayAttendance && state.todayAttendance.check_in_time && !state.todayAttendance.check_out_time
  },
  
  workingHours: state => {
    if (!state.todayAttendance || !state.todayAttendance.check_in_time) {
      return '00:00'
    }
    
    const checkIn = new Date(`${state.todayAttendance.date} ${state.todayAttendance.check_in_time}`)
    const checkOut = state.todayAttendance.check_out_time 
      ? new Date(`${state.todayAttendance.date} ${state.todayAttendance.check_out_time}`)
      : new Date()
    
    const diff = checkOut - checkIn
    const hours = Math.floor(diff / (1000 * 60 * 60))
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
    
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`
  },
  
  attendanceByEmployee: state => employeeId => {
    return state.attendanceRecords.filter(record => record.employee_id === employeeId)
  },
  
  attendanceByDate: state => date => {
    return state.attendanceRecords.filter(record => record.date === date)
  }
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}