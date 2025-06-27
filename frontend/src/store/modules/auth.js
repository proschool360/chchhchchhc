import axios from 'axios'

const API_BASE_URL = 'https://whatsapp.proschool360.com/api'

const state = {
  user: null,
  token: localStorage.getItem('hrms_token') || null,
  isAuthenticated: false,
  loginLoading: false
}

const mutations = {
  SET_USER(state, user) {
    state.user = user
    state.isAuthenticated = !!user
  },
  
  SET_TOKEN(state, token) {
    state.token = token
    if (token) {
      localStorage.setItem('hrms_token', token)
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    } else {
      localStorage.removeItem('hrms_token')
      delete axios.defaults.headers.common['Authorization']
    }
  },
  
  SET_LOGIN_LOADING(state, loading) {
    state.loginLoading = loading
  },
  
  LOGOUT(state) {
    state.user = null
    state.token = null
    state.isAuthenticated = false
    localStorage.removeItem('hrms_token')
    delete axios.defaults.headers.common['Authorization']
  }
}

const actions = {
  async login({ commit }, credentials) {
    try {
      commit('SET_LOGIN_LOADING', true)
      
      const response = await axios.post(`${API_BASE_URL}/auth/login`, credentials)
      
      if (response.data.success) {
        const { token, user } = response.data.data
        
        commit('SET_TOKEN', token)
        commit('SET_USER', user)
        
        return { success: true, user }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Login error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Login failed. Please try again.' 
      }
    } finally {
      commit('SET_LOGIN_LOADING', false)
    }
  },
  
  async register({ commit }, userData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/auth/register`, userData)
      
      if (response.data.success) {
        return { success: true, message: response.data.message }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Registration error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Registration failed. Please try again.' 
      }
    }
  },
  
  async logout({ commit }) {
    try {
      await axios.post(`${API_BASE_URL}/auth/logout`)
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      commit('LOGOUT')
    }
  },
  
  async fetchUser({ commit, state }) {
    if (!state.token) return
    
    try {
      const response = await axios.get(`${API_BASE_URL}/auth/profile`)
      
      if (response.data.success) {
        commit('SET_USER', response.data.data)
        return response.data.data
      }
    } catch (error) {
      console.error('Fetch user error:', error)
      if (error.response?.status === 401) {
        commit('LOGOUT')
      }
    }
  },
  
  async updateProfile({ commit }, profileData) {
    try {
      const response = await axios.put(`${API_BASE_URL}/auth/profile`, profileData)
      
      if (response.data.success) {
        commit('SET_USER', response.data.data)
        return { success: true, message: 'Profile updated successfully' }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Update profile error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to update profile' 
      }
    }
  },
  
  async changePassword({ commit }, passwordData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/auth/change-password`, passwordData)
      
      if (response.data.success) {
        return { success: true, message: 'Password changed successfully' }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Change password error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to change password' 
      }
    }
  },
  
  async forgotPassword({ commit }, email) {
    try {
      const response = await axios.post(`${API_BASE_URL}/auth/forgot-password`, { email })
      
      if (response.data.success) {
        return { success: true, message: response.data.message }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Forgot password error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to send reset email' 
      }
    }
  },
  
  async resetPassword({ commit }, resetData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/auth/reset-password`, resetData)
      
      if (response.data.success) {
        return { success: true, message: response.data.message }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Reset password error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to reset password' 
      }
    }
  },
  
  async refreshToken({ commit, state }) {
    if (!state.token) return
    
    try {
      const response = await axios.post(`${API_BASE_URL}/auth/refresh`)
      
      if (response.data.success) {
        const { token } = response.data.data
        commit('SET_TOKEN', token)
        return token
      }
    } catch (error) {
      console.error('Refresh token error:', error)
      commit('LOGOUT')
    }
  },
  
  initializeAuth({ commit, dispatch, state }) {
    if (state.token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${state.token}`
      dispatch('fetchUser')
    }
  }
}

const getters = {
  isAuthenticated: state => state.isAuthenticated,
  user: state => state.user,
  token: state => state.token,
  loginLoading: state => state.loginLoading,
  userRole: state => state.user?.role || null,
  userId: state => state.user?.id || null,
  userName: state => state.user?.name || null,
  userEmail: state => state.user?.email || null,
  userAvatar: state => state.user?.avatar || null,
  hasRole: state => role => {
    if (!state.user || !state.user.role) return false
    if (Array.isArray(role)) {
      return role.includes(state.user.role)
    }
    return state.user.role === role
  },
  hasPermission: state => permission => {
    if (!state.user || !state.user.permissions) return false
    return state.user.permissions.includes(permission)
  }
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}