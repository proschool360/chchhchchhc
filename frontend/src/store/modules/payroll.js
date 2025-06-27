import axios from 'axios'

const API_BASE_URL = 'https://whatsapp.proschool360.com/api'

const state = {
  payrollRecords: [],
  currentPayroll: null,
  loading: false,
  pagination: {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  },
  filters: {
    employee_id: null,
    month: null,
    year: null,
    status: null
  }
}

const mutations = {
  SET_PAYROLL_RECORDS(state, data) {
    state.payrollRecords = data.records || []
    if (data.pagination) {
      state.pagination = data.pagination
    }
  },
  
  SET_CURRENT_PAYROLL(state, payroll) {
    state.currentPayroll = payroll
  },
  
  ADD_PAYROLL_RECORD(state, record) {
    state.payrollRecords.unshift(record)
  },
  
  UPDATE_PAYROLL_RECORD(state, updatedRecord) {
    const index = state.payrollRecords.findIndex(record => record.id === updatedRecord.id)
    if (index !== -1) {
      state.payrollRecords.splice(index, 1, updatedRecord)
    }
  },
  
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  
  SET_FILTERS(state, filters) {
    state.filters = { ...state.filters, ...filters }
  }
}

const actions = {
  async fetchPayrollRecords({ commit, state }, params = {}) {
    try {
      commit('SET_LOADING', true)
      
      const queryParams = {
        page: state.pagination.current_page,
        per_page: state.pagination.per_page,
        ...state.filters,
        ...params
      }
      
      const response = await axios.get(`${API_BASE_URL}/payroll`, { params: queryParams })
      
      if (response.data.success) {
        commit('SET_PAYROLL_RECORDS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch payroll records error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch payroll records' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async generatePayroll({ commit }, data) {
    try {
      const response = await axios.post(`${API_BASE_URL}/payroll/generate`, data)
      
      if (response.data.success) {
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Generate payroll error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to generate payroll' 
      }
    }
  },
  
  setFilters({ commit }, filters) {
    commit('SET_FILTERS', filters)
  }
}

const getters = {
  payrollRecords: state => state.payrollRecords,
  currentPayroll: state => state.currentPayroll,
  loading: state => state.loading,
  pagination: state => state.pagination,
  filters: state => state.filters
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}