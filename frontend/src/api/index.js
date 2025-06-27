import axios from 'axios'
import router from '../router'

// Create axios instance with base configuration
const api = axios.create({
  baseURL: process.env.VUE_APP_API_URL || 'http://localhost/hrms/backend/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('hrms_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('hrms_token')
      localStorage.removeItem('user')
      // Use router navigation instead of window.location to avoid full page reload
      if (router.currentRoute.value.path !== '/login') {
        router.push('/login')
      }
    }
    return Promise.reject(error)
  }
)

export default api

// Export common API utilities
export const handleApiError = (error) => {
  if (error.response) {
    // Server responded with error status
    return error.response.data?.message || 'Server error occurred'
  } else if (error.request) {
    // Request was made but no response received
    return 'Network error - please check your connection'
  } else {
    // Something else happened
    return error.message || 'An unexpected error occurred'
  }
}

export const formatApiResponse = (response) => {
  return {
    success: response.data?.success || false,
    data: response.data?.data || null,
    message: response.data?.message || '',
    errors: response.data?.errors || []
  }
}