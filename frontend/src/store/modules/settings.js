import axios from 'axios'

const API_BASE_URL = 'https://whatsapp.proschool360.com/api'

const state = {
  companySettings: null,
  leaveTypes: [],
  holidays: [],
  users: [],
  systemSettings: null,
  loading: false,
  pagination: {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  }
}

const mutations = {
  SET_COMPANY_SETTINGS(state, settings) {
    state.companySettings = settings
  },
  
  SET_LEAVE_TYPES(state, leaveTypes) {
    state.leaveTypes = leaveTypes
  },
  
  SET_HOLIDAYS(state, holidays) {
    state.holidays = holidays
  },
  
  SET_USERS(state, data) {
    state.users = data.users || []
    if (data.pagination) {
      state.pagination = data.pagination
    }
  },
  
  SET_SYSTEM_SETTINGS(state, settings) {
    state.systemSettings = settings
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
  
  ADD_HOLIDAY(state, holiday) {
    state.holidays.push(holiday)
  },
  
  UPDATE_HOLIDAY(state, updatedHoliday) {
    const index = state.holidays.findIndex(holiday => holiday.id === updatedHoliday.id)
    if (index !== -1) {
      state.holidays.splice(index, 1, updatedHoliday)
    }
  },
  
  REMOVE_HOLIDAY(state, holidayId) {
    state.holidays = state.holidays.filter(holiday => holiday.id !== holidayId)
  },
  
  ADD_USER(state, user) {
    state.users.unshift(user)
  },
  
  UPDATE_USER(state, updatedUser) {
    const index = state.users.findIndex(user => user.id === updatedUser.id)
    if (index !== -1) {
      state.users.splice(index, 1, updatedUser)
    }
  },
  
  REMOVE_USER(state, userId) {
    state.users = state.users.filter(user => user.id !== userId)
  },
  
  SET_LOADING(state, loading) {
    state.loading = loading
  }
}

const actions = {
  async fetchCompanySettings({ commit }) {
    try {
      commit('SET_LOADING', true)
      const response = await axios.get(`${API_BASE_URL}/settings/company`)
      
      if (response.data.success) {
        commit('SET_COMPANY_SETTINGS', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch company settings error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch company settings' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async updateCompanySettings({ commit }, settingsData) {
    try {
      const response = await axios.put(`${API_BASE_URL}/settings/company`, settingsData)
      
      if (response.data.success) {
        commit('SET_COMPANY_SETTINGS', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Update company settings error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to update company settings' 
      }
    }
  },
  
  async fetchLeaveTypes({ commit }) {
    try {
      const response = await axios.get(`${API_BASE_URL}/settings/leave-types`)
      
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
  
  async createLeaveType({ commit }, typeData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/settings/leave-types`, typeData)
      
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
      const response = await axios.put(`${API_BASE_URL}/settings/leave-types/${id}`, data)
      
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
      const response = await axios.delete(`${API_BASE_URL}/settings/leave-types/${typeId}`)
      
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
  
  async fetchHolidays({ commit }) {
    try {
      const response = await axios.get(`${API_BASE_URL}/settings/holidays`)
      
      if (response.data.success) {
        commit('SET_HOLIDAYS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch holidays error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch holidays' 
      }
    }
  },
  
  async createHoliday({ commit }, holidayData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/settings/holidays`, holidayData)
      
      if (response.data.success) {
        commit('ADD_HOLIDAY', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Create holiday error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to create holiday' 
      }
    }
  },
  
  async updateHoliday({ commit }, { id, data }) {
    try {
      const response = await axios.put(`${API_BASE_URL}/settings/holidays/${id}`, data)
      
      if (response.data.success) {
        commit('UPDATE_HOLIDAY', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Update holiday error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to update holiday' 
      }
    }
  },
  
  async deleteHoliday({ commit }, holidayId) {
    try {
      const response = await axios.delete(`${API_BASE_URL}/settings/holidays/${holidayId}`)
      
      if (response.data.success) {
        commit('REMOVE_HOLIDAY', holidayId)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Delete holiday error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to delete holiday' 
      }
    }
  },
  
  async fetchUsers({ commit }, params = {}) {
    try {
      commit('SET_LOADING', true)
      const response = await axios.get(`${API_BASE_URL}/settings/users`, { params })
      
      if (response.data.success) {
        commit('SET_USERS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch users error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch users' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async createUser({ commit }, userData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/settings/users`, userData)
      
      if (response.data.success) {
        commit('ADD_USER', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Create user error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to create user' 
      }
    }
  },
  
  async updateUser({ commit }, { id, data }) {
    try {
      const response = await axios.put(`${API_BASE_URL}/settings/users/${id}`, data)
      
      if (response.data.success) {
        commit('UPDATE_USER', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Update user error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to update user' 
      }
    }
  },
  
  async deleteUser({ commit }, userId) {
    try {
      const response = await axios.delete(`${API_BASE_URL}/settings/users/${userId}`)
      
      if (response.data.success) {
        commit('REMOVE_USER', userId)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Delete user error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to delete user' 
      }
    }
  }
}

const getters = {
  companySettings: state => state.companySettings,
  leaveTypes: state => state.leaveTypes,
  holidays: state => state.holidays,
  users: state => state.users,
  systemSettings: state => state.systemSettings,
  loading: state => state.loading,
  pagination: state => state.pagination,
  
  activeLeaveTypes: state => {
    return state.leaveTypes.filter(type => type.is_active)
  },
  
  upcomingHolidays: state => {
    const today = new Date()
    return state.holidays.filter(holiday => new Date(holiday.date) >= today)
      .sort((a, b) => new Date(a.date) - new Date(b.date))
  },
  
  activeUsers: state => {
    return state.users.filter(user => user.status === 'active')
  }
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}