import { createStore } from 'vuex'
import api from '@/api'

const state = {
  departments: [],
  currentDepartment: null,
  loading: false,
  error: null,
  pagination: {
    page: 1,
    limit: 10,
    total: 0
  }
}

const getters = {
  allDepartments: state => state.departments,
  currentDepartment: state => state.currentDepartment,
  isLoading: state => state.loading,
  error: state => state.error,
  pagination: state => state.pagination,
  departmentById: state => id => state.departments.find(dept => dept.id === id),
  activeDepartments: state => state.departments.filter(dept => dept.status === 'active')
}

const mutations = {
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  SET_ERROR(state, error) {
    state.error = error
  },
  SET_DEPARTMENTS(state, departments) {
    state.departments = departments
  },
  SET_CURRENT_DEPARTMENT(state, department) {
    state.currentDepartment = department
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
  DELETE_DEPARTMENT(state, departmentId) {
    state.departments = state.departments.filter(dept => dept.id !== departmentId)
  },
  SET_PAGINATION(state, pagination) {
    state.pagination = { ...state.pagination, ...pagination }
  }
}

const actions = {
  async fetchDepartments({ commit }, params = {}) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const response = await api.get('/departments', { params })
      // Backend returns data directly in response.data, not response.data.departments
      commit('SET_DEPARTMENTS', response.data.data || [])
      commit('SET_PAGINATION', {
        page: response.data.pagination?.current_page || 1,
        limit: response.data.pagination?.per_page || 10,
        total: response.data.pagination?.total_records || 0
      })
      return response.data
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async fetchDepartment({ commit }, id) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const response = await api.get(`/departments/${id}`)
      commit('SET_CURRENT_DEPARTMENT', response.data)
      return response.data
    } catch (error) {
      commit('SET_ERROR', error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async createDepartment({ commit }, departmentData) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const response = await api.post('/departments', departmentData)
      commit('ADD_DEPARTMENT', response.data)
      return response.data
    } catch (error) {
      commit('SET_ERROR', error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async updateDepartment({ commit }, { id, data }) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const response = await api.put(`/departments/${id}`, data)
      commit('UPDATE_DEPARTMENT', response.data)
      return response.data
    } catch (error) {
      commit('SET_ERROR', error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async deleteDepartment({ commit }, id) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      await api.delete(`/departments/${id}`)
      commit('DELETE_DEPARTMENT', id)
      return true
    } catch (error) {
      commit('SET_ERROR', error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  clearError({ commit }) {
    commit('SET_ERROR', null)
  },

  clearCurrentDepartment({ commit }) {
    commit('SET_CURRENT_DEPARTMENT', null)
  },

  async fetchManagers({ commit }) {
    try {
      const response = await api.get('/departments/managers')
      return response.data.data || []
    } catch (error) {
      console.error('Failed to fetch managers:', error)
      throw error
    }
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}