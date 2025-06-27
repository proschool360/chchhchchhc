import { createStore } from 'vuex'
import api from '@/api'

const state = {
  positions: [],
  currentPosition: null,
  loading: false,
  error: null,
  pagination: {
    page: 1,
    limit: 10,
    total: 0
  }
}

const getters = {
  allPositions: state => state.positions,
  currentPosition: state => state.currentPosition,
  isLoading: state => state.loading,
  error: state => state.error,
  pagination: state => state.pagination,
  positionById: state => id => state.positions.find(pos => pos.id === id),
  activePositions: state => state.positions.filter(pos => pos.status === 'active'),
  positionsByDepartment: state => departmentId => state.positions.filter(pos => pos.department_id === departmentId)
}

const mutations = {
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  SET_ERROR(state, error) {
    state.error = error
  },
  SET_POSITIONS(state, positions) {
    state.positions = positions
  },
  SET_CURRENT_POSITION(state, position) {
    state.currentPosition = position
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
  DELETE_POSITION(state, positionId) {
    state.positions = state.positions.filter(pos => pos.id !== positionId)
  },
  SET_PAGINATION(state, pagination) {
    state.pagination = { ...state.pagination, ...pagination }
  }
}

const actions = {
  async fetchPositions({ commit }, params = {}) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const response = await api.get('/positions', { params })
      // Backend returns data directly in response.data, not response.data.positions
      commit('SET_POSITIONS', response.data.data || [])
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

  async fetchPosition({ commit }, id) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const response = await api.get(`/positions/${id}`)
      commit('SET_CURRENT_POSITION', response.data.data || response.data)
      return response.data
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async createPosition({ commit }, positionData) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const response = await api.post('/positions', positionData)
      commit('ADD_POSITION', response.data.data || response.data)
      return response.data
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async updatePosition({ commit }, { id, data }) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const response = await api.put(`/positions/${id}`, data)
      commit('UPDATE_POSITION', response.data.data || response.data)
      return response.data
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async deletePosition({ commit }, id) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      await api.delete(`/positions/${id}`)
      commit('DELETE_POSITION', id)
      return true
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  clearError({ commit }) {
    commit('SET_ERROR', null)
  },

  clearCurrentPosition({ commit }) {
    commit('SET_CURRENT_POSITION', null)
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}