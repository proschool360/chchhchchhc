import axios from 'axios'

const API_BASE_URL = 'https://whatsapp.proschool360.com/api'

const state = {
  performanceReviews: [],
  goals: [],
  feedback360: [],
  currentReview: null,
  loading: false,
  pagination: {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  }
}

const mutations = {
  SET_PERFORMANCE_REVIEWS(state, data) {
    state.performanceReviews = data.reviews || []
    if (data.pagination) {
      state.pagination = data.pagination
    }
  },
  
  SET_GOALS(state, goals) {
    state.goals = goals
  },
  
  SET_FEEDBACK_360(state, feedback) {
    state.feedback360 = feedback
  },
  
  SET_CURRENT_REVIEW(state, review) {
    state.currentReview = review
  },
  
  ADD_PERFORMANCE_REVIEW(state, review) {
    state.performanceReviews.unshift(review)
  },
  
  UPDATE_PERFORMANCE_REVIEW(state, updatedReview) {
    const index = state.performanceReviews.findIndex(review => review.id === updatedReview.id)
    if (index !== -1) {
      state.performanceReviews.splice(index, 1, updatedReview)
    }
  },
  
  ADD_GOAL(state, goal) {
    state.goals.unshift(goal)
  },
  
  UPDATE_GOAL(state, updatedGoal) {
    const index = state.goals.findIndex(goal => goal.id === updatedGoal.id)
    if (index !== -1) {
      state.goals.splice(index, 1, updatedGoal)
    }
  },
  
  SET_LOADING(state, loading) {
    state.loading = loading
  }
}

const actions = {
  async fetchPerformanceReviews({ commit }, params = {}) {
    try {
      commit('SET_LOADING', true)
      const response = await axios.get(`${API_BASE_URL}/performance/reviews`, { params })
      
      if (response.data.success) {
        commit('SET_PERFORMANCE_REVIEWS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch performance reviews error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch performance reviews' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async fetchGoals({ commit }, params = {}) {
    try {
      const response = await axios.get(`${API_BASE_URL}/performance/goals`, { params })
      
      if (response.data.success) {
        commit('SET_GOALS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch goals error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch goals' 
      }
    }
  },
  
  async createGoal({ commit }, goalData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/performance/goals`, goalData)
      
      if (response.data.success) {
        commit('ADD_GOAL', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Create goal error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to create goal' 
      }
    }
  }
}

const getters = {
  performanceReviews: state => state.performanceReviews,
  goals: state => state.goals,
  feedback360: state => state.feedback360,
  currentReview: state => state.currentReview,
  loading: state => state.loading,
  pagination: state => state.pagination
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}