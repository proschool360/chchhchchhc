import axios from 'axios'

const API_BASE_URL = 'https://whatsapp.proschool360.com/api'

const state = {
  trainingPrograms: [],
  enrollments: [],
  skillAssessments: [],
  currentProgram: null,
  loading: false,
  pagination: {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  }
}

const mutations = {
  SET_TRAINING_PROGRAMS(state, data) {
    state.trainingPrograms = data.programs || []
    if (data.pagination) {
      state.pagination = data.pagination
    }
  },
  
  SET_ENROLLMENTS(state, enrollments) {
    state.enrollments = enrollments
  },
  
  SET_SKILL_ASSESSMENTS(state, assessments) {
    state.skillAssessments = assessments
  },
  
  SET_CURRENT_PROGRAM(state, program) {
    state.currentProgram = program
  },
  
  ADD_TRAINING_PROGRAM(state, program) {
    state.trainingPrograms.unshift(program)
  },
  
  UPDATE_TRAINING_PROGRAM(state, updatedProgram) {
    const index = state.trainingPrograms.findIndex(program => program.id === updatedProgram.id)
    if (index !== -1) {
      state.trainingPrograms.splice(index, 1, updatedProgram)
    }
  },
  
  ADD_ENROLLMENT(state, enrollment) {
    state.enrollments.unshift(enrollment)
  },
  
  UPDATE_ENROLLMENT(state, updatedEnrollment) {
    const index = state.enrollments.findIndex(enrollment => enrollment.id === updatedEnrollment.id)
    if (index !== -1) {
      state.enrollments.splice(index, 1, updatedEnrollment)
    }
  },
  
  SET_LOADING(state, loading) {
    state.loading = loading
  }
}

const actions = {
  async fetchTrainingPrograms({ commit }, params = {}) {
    try {
      commit('SET_LOADING', true)
      const response = await axios.get(`${API_BASE_URL}/training/programs`, { params })
      
      if (response.data.success) {
        commit('SET_TRAINING_PROGRAMS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch training programs error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch training programs' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async fetchEnrollments({ commit }, params = {}) {
    try {
      const response = await axios.get(`${API_BASE_URL}/training/enrollments`, { params })
      
      if (response.data.success) {
        commit('SET_ENROLLMENTS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch enrollments error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch enrollments' 
      }
    }
  },
  
  async createTrainingProgram({ commit }, programData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/training/programs`, programData)
      
      if (response.data.success) {
        commit('ADD_TRAINING_PROGRAM', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Create training program error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to create training program' 
      }
    }
  },
  
  async enrollInProgram({ commit }, { programId, employeeId }) {
    try {
      const response = await axios.post(`${API_BASE_URL}/training/programs/${programId}/enroll`, {
        employee_id: employeeId
      })
      
      if (response.data.success) {
        commit('ADD_ENROLLMENT', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Enroll in program error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to enroll in program' 
      }
    }
  }
}

const getters = {
  trainingPrograms: state => state.trainingPrograms,
  enrollments: state => state.enrollments,
  skillAssessments: state => state.skillAssessments,
  currentProgram: state => state.currentProgram,
  loading: state => state.loading,
  pagination: state => state.pagination,
  
  myEnrollments: (state, getters, rootState) => {
    const userId = rootState.auth.user?.id
    return state.enrollments.filter(enrollment => enrollment.employee_id === userId)
  }
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}