import api from '../../api'

const state = {
  employees: [],
  currentEmployee: null,
  departments: [],
  positions: [],
  loading: false,
  pagination: {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  },
  filters: {
    search: '',
    department_id: null,
    position_id: null,
    status: 'active'
  }
}

const mutations = {
  SET_EMPLOYEES(state, data) {
    state.employees = data.employees || []
    if (data.pagination) {
      state.pagination = data.pagination
    }
  },
  
  SET_CURRENT_EMPLOYEE(state, employee) {
    state.currentEmployee = employee
  },
  
  ADD_EMPLOYEE(state, employee) {
    state.employees.unshift(employee)
  },
  
  UPDATE_EMPLOYEE(state, updatedEmployee) {
    const index = state.employees.findIndex(emp => emp.id === updatedEmployee.id)
    if (index !== -1) {
      state.employees.splice(index, 1, updatedEmployee)
    }
    if (state.currentEmployee && state.currentEmployee.id === updatedEmployee.id) {
      state.currentEmployee = updatedEmployee
    }
  },
  
  REMOVE_EMPLOYEE(state, employeeId) {
    state.employees = state.employees.filter(emp => emp.id !== employeeId)
    if (state.currentEmployee && state.currentEmployee.id === employeeId) {
      state.currentEmployee = null
    }
  },
  
  SET_DEPARTMENTS(state, departments) {
    state.departments = departments
  },
  
  SET_POSITIONS(state, positions) {
    state.positions = positions
  },
  
  ADD_DEPARTMENT(state, department) {
    state.departments.push(department)
  },
  
  UPDATE_DEPARTMENT(state, updatedDepartment) {
    const index = state.departments.findIndex(dept => dept.id === updatedDepartment.id)
    if (index !== -1) {
      state.departments.splice(index, 1, updatedDepartment)
    }
  },
  
  REMOVE_DEPARTMENT(state, departmentId) {
    state.departments = state.departments.filter(dept => dept.id !== departmentId)
  },
  
  ADD_POSITION(state, position) {
    state.positions.push(position)
  },
  
  UPDATE_POSITION(state, updatedPosition) {
    const index = state.positions.findIndex(pos => pos.id === updatedPosition.id)
    if (index !== -1) {
      state.positions.splice(index, 1, updatedPosition)
    }
  },
  
  REMOVE_POSITION(state, positionId) {
    state.positions = state.positions.filter(pos => pos.id !== positionId)
  },
  
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  
  SET_FILTERS(state, filters) {
    state.filters = { ...state.filters, ...filters }
  },
  
  RESET_FILTERS(state) {
    state.filters = {
      search: '',
      department_id: null,
      position_id: null,
      status: 'active'
    }
  }
}

const actions = {
  async fetchEmployees({ commit, state }, params = {}) {
    try {
      commit('SET_LOADING', true)
      
      const queryParams = {
        page: state.pagination.current_page,
        per_page: state.pagination.per_page,
        ...state.filters,
        ...params
      }
      
      const response = await api.get('/employees', { params: queryParams })
      
      if (response.data.success) {
        // Transform API response to match store structure
        const storeData = {
          employees: response.data.data,
          pagination: response.data.pagination
        }
        commit('SET_EMPLOYEES', storeData)
        return { success: true, pagination: response.data.pagination }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch employees error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch employees' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async fetchEmployee({ commit }, employeeId) {
    try {
      commit('SET_LOADING', true)
      
      const response = await api.get(`/employees/${employeeId}`)
      
      if (response.data.success) {
        commit('SET_CURRENT_EMPLOYEE', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch employee error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch employee' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async createEmployee({ commit }, employeeData) {
    try {
      const response = await api.post('/employees', employeeData)
      
      if (response.data.success) {
        commit('ADD_EMPLOYEE', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Create employee error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to create employee' 
      }
    }
  },
  
  async updateEmployee({ commit }, { id, data }) {
    try {
      const response = await api.put(`/employees/${id}`, data)
      
      if (response.data.success) {
        commit('UPDATE_EMPLOYEE', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Update employee error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to update employee' 
      }
    }
  },
  
  async deleteEmployee({ commit }, employeeId) {
    try {
      const response = await api.delete(`/employees/${employeeId}`)
      
      if (response.data.success) {
        commit('REMOVE_EMPLOYEE', employeeId)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Delete employee error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to delete employee' 
      }
    }
  },
  
  async fetchDepartments({ commit }) {
    try {
      const response = await api.get('/departments')
      
      if (response.data.success) {
        commit('SET_DEPARTMENTS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch departments error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch departments' 
      }
    }
  },
  
  async createDepartment({ commit }, departmentData) {
    try {
      const response = await api.post('/departments', departmentData)
      
      if (response.data.success) {
        commit('ADD_DEPARTMENT', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Create department error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to create department' 
      }
    }
  },
  
  async updateDepartment({ commit }, { id, data }) {
    try {
      const response = await api.put(`/departments/${id}`, data)
      
      if (response.data.success) {
        commit('UPDATE_DEPARTMENT', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Update department error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to update department' 
      }
    }
  },
  
  async deleteDepartment({ commit }, departmentId) {
    try {
      const response = await api.delete(`/departments/${departmentId}`)
      
      if (response.data.success) {
        commit('REMOVE_DEPARTMENT', departmentId)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Delete department error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to delete department' 
      }
    }
  },
  
  async fetchPositions({ commit }) {
    try {
      const response = await api.get('/positions')
      
      if (response.data.success) {
        commit('SET_POSITIONS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch positions error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch positions' 
      }
    }
  },
  
  async createPosition({ commit }, positionData) {
    try {
      const response = await api.post('/positions', positionData)
      
      if (response.data.success) {
        commit('ADD_POSITION', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Create position error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to create position' 
      }
    }
  },
  
  async updatePosition({ commit }, { id, data }) {
    try {
      const response = await api.put(`/positions/${id}`, data)
      
      if (response.data.success) {
        commit('UPDATE_POSITION', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Update position error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to update position' 
      }
    }
  },
  
  async deletePosition({ commit }, positionId) {
    try {
      const response = await api.delete(`/positions/${positionId}`)
      
      if (response.data.success) {
        commit('REMOVE_POSITION', positionId)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Delete position error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to delete position' 
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
  employees: state => state.employees,
  currentEmployee: state => state.currentEmployee,
  departments: state => state.departments,
  positions: state => state.positions,
  loading: state => state.loading,
  pagination: state => state.pagination,
  filters: state => state.filters,
  
  employeeById: state => id => {
    return state.employees.find(emp => emp.id === id)
  },
  
  departmentById: state => id => {
    return state.departments.find(dept => dept.id === id)
  },
  
  positionById: state => id => {
    return state.positions.find(pos => pos.id === id)
  },
  
  employeesByDepartment: state => departmentId => {
    return state.employees.filter(emp => emp.department_id === departmentId)
  },
  
  employeesByPosition: state => positionId => {
    return state.employees.filter(emp => emp.position_id === positionId)
  },
  
  activeEmployees: state => {
    return state.employees.filter(emp => emp.status === 'active')
  },
  
  totalEmployees: state => state.pagination.total
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}