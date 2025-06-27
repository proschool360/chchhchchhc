import axios from 'axios'

const API_BASE_URL = 'https://whatsapp.proschool360.com/api'

const state = {
  leaveRequests: [],
  leaveTypes: [],
  leaveBalances: [],
  currentLeaveRequest: null,
  loading: false,
  pagination: {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  },
  filters: {
    employee_id: null,
    leave_type_id: null,
    status: null,
    date_from: null,
    date_to: null
  }
}

const mutations = {
  SET_LEAVE_REQUESTS(state, data) {
    state.leaveRequests = data.requests || []
    if (data.pagination) {
      state.pagination = data.pagination
    }
  },
  
  SET_LEAVE_TYPES(state, leaveTypes) {
    state.leaveTypes = leaveTypes
  },
  
  SET_LEAVE_BALANCES(state, balances) {
    state.leaveBalances = balances
  },
  
  SET_CURRENT_LEAVE_REQUEST(state, request) {
    state.currentLeaveRequest = request
  },
  
  ADD_LEAVE_REQUEST(state, request) {
    state.leaveRequests.unshift(request)
  },
  
  UPDATE_LEAVE_REQUEST(state, updatedRequest) {
    const index = state.leaveRequests.findIndex(req => req.id === updatedRequest.id)
    if (index !== -1) {
      state.leaveRequests.splice(index, 1, updatedRequest)
    }
    if (state.currentLeaveRequest && state.currentLeaveRequest.id === updatedRequest.id) {
      state.currentLeaveRequest = updatedRequest
    }
  },
  
  REMOVE_LEAVE_REQUEST(state, requestId) {
    state.leaveRequests = state.leaveRequests.filter(req => req.id !== requestId)
    if (state.currentLeaveRequest && state.currentLeaveRequest.id === requestId) {
      state.currentLeaveRequest = null
    }
  },
  
  ADD_LEAVE_TYPE(state, leaveType) {
    state.leaveTypes.push(leaveType)
  },
  
  UPDATE_LEAVE_TYPE(state, updatedType) {
    const index = state.leaveTypes.findIndex(type => type.id === updatedType.id)
    if (index !== -1) {
      state.leaveTypes.splice(index, 1, updatedType)
    }
  },
  
  REMOVE_LEAVE_TYPE(state, typeId) {
    state.leaveTypes = state.leaveTypes.filter(type => type.id !== typeId)
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
      leave_type_id: null,
      status: null,
      date_from: null,
      date_to: null
    }
  }
}

const actions = {
  async fetchLeaveRequests({ commit, state }, params = {}) {
    try {
      commit('SET_LOADING', true)
      
      const queryParams = {
        page: state.pagination.current_page,
        per_page: state.pagination.per_page,
        ...state.filters,
        ...params
      }
      
      const response = await axios.get(`${API_BASE_URL}/leave`, { params: queryParams })
      
      if (response.data.success) {
        commit('SET_LEAVE_REQUESTS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch leave requests error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch leave requests' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async fetchLeaveRequest({ commit }, requestId) {
    try {
      commit('SET_LOADING', true)
      
      const response = await axios.get(`${API_BASE_URL}/leave/${requestId}`)
      
      if (response.data.success) {
        commit('SET_CURRENT_LEAVE_REQUEST', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch leave request error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch leave request' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async createLeaveRequest({ commit }, requestData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/leave`, requestData)
      
      if (response.data.success) {
        commit('ADD_LEAVE_REQUEST', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Create leave request error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to create leave request' 
      }
    }
  },
  
  async updateLeaveRequest({ commit }, { id, data }) {
    try {
      const response = await axios.put(`${API_BASE_URL}/leave/${id}`, data)
      
      if (response.data.success) {
        commit('UPDATE_LEAVE_REQUEST', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Update leave request error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to update leave request' 
      }
    }
  },
  
  async approveLeaveRequest({ commit }, { id, comments = '' }) {
    try {
      const response = await axios.post(`${API_BASE_URL}/leave/${id}/approve`, { comments })
      
      if (response.data.success) {
        commit('UPDATE_LEAVE_REQUEST', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Approve leave request error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to approve leave request' 
      }
    }
  },
  
  async rejectLeaveRequest({ commit }, { id, comments = '' }) {
    try {
      const response = await axios.post(`${API_BASE_URL}/leave/${id}/reject`, { comments })
      
      if (response.data.success) {
        commit('UPDATE_LEAVE_REQUEST', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Reject leave request error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to reject leave request' 
      }
    }
  },
  
  async cancelLeaveRequest({ commit }, requestId) {
    try {
      const response = await axios.post(`${API_BASE_URL}/leave/${requestId}/cancel`)
      
      if (response.data.success) {
        commit('UPDATE_LEAVE_REQUEST', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Cancel leave request error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to cancel leave request' 
      }
    }
  },
  
  async deleteLeaveRequest({ commit }, requestId) {
    try {
      const response = await axios.delete(`${API_BASE_URL}/leave/${requestId}`)
      
      if (response.data.success) {
        commit('REMOVE_LEAVE_REQUEST', requestId)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Delete leave request error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to delete leave request' 
      }
    }
  },
  
  async fetchLeaveTypes({ commit }) {
    try {
      const response = await axios.get(`${API_BASE_URL}/leave/types`)
      
      if (response.data.success) {
        commit('SET_LEAVE_TYPES', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch leave types error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch leave types' 
      }
    }
  },
  
  async fetchLeaveBalances({ commit }, employeeId = null) {
    try {
      const params = employeeId ? { employee_id: employeeId } : {}
      const response = await axios.get(`${API_BASE_URL}/leave/balances`, { params })
      
      if (response.data.success) {
        commit('SET_LEAVE_BALANCES', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch leave balances error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch leave balances' 
      }
    }
  },
  
  async createLeaveType({ commit }, typeData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/leave/types`, typeData)
      
      if (response.data.success) {
        commit('ADD_LEAVE_TYPE', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Create leave type error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to create leave type' 
      }
    }
  },
  
  async updateLeaveType({ commit }, { id, data }) {
    try {
      const response = await axios.put(`${API_BASE_URL}/leave/types/${id}`, data)
      
      if (response.data.success) {
        commit('UPDATE_LEAVE_TYPE', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Update leave type error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to update leave type' 
      }
    }
  },
  
  async deleteLeaveType({ commit }, typeId) {
    try {
      const response = await axios.delete(`${API_BASE_URL}/leave/types/${typeId}`)
      
      if (response.data.success) {
        commit('REMOVE_LEAVE_TYPE', typeId)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Delete leave type error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to delete leave type' 
      }
    }
  },
  
  async generateLeaveReport({ commit }, params) {
    try {
      const response = await axios.get(`${API_BASE_URL}/leave/reports`, { 
        params,
        responseType: 'blob'
      })
      
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      
      const contentDisposition = response.headers['content-disposition']
      let filename = 'leave_report.csv'
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
      console.error('Generate leave report error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to generate leave report' 
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
  leaveRequests: state => state.leaveRequests,
  leaveTypes: state => state.leaveTypes,
  leaveBalances: state => state.leaveBalances,
  currentLeaveRequest: state => state.currentLeaveRequest,
  loading: state => state.loading,
  pagination: state => state.pagination,
  filters: state => state.filters,
  
  leaveRequestById: state => id => {
    return state.leaveRequests.find(req => req.id === id)
  },
  
  leaveTypeById: state => id => {
    return state.leaveTypes.find(type => type.id === id)
  },
  
  leaveBalanceByType: state => typeId => {
    return state.leaveBalances.find(balance => balance.leave_type_id === typeId)
  },
  
  pendingLeaveRequests: state => {
    return state.leaveRequests.filter(req => req.status === 'pending')
  },
  
  approvedLeaveRequests: state => {
    return state.leaveRequests.filter(req => req.status === 'approved')
  },
  
  rejectedLeaveRequests: state => {
    return state.leaveRequests.filter(req => req.status === 'rejected')
  },
  
  myLeaveRequests: (state, getters, rootState) => {
    const userId = rootState.auth.user?.id
    return state.leaveRequests.filter(req => req.employee_id === userId)
  },
  
  totalLeaveBalance: state => {
    return state.leaveBalances.reduce((total, balance) => total + balance.remaining_days, 0)
  },
  
  usedLeaveBalance: state => {
    return state.leaveBalances.reduce((total, balance) => total + balance.used_days, 0)
  }
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}