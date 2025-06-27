import axios from 'axios'

const API_BASE_URL = 'https://whatsapp.proschool360.com/api'

const state = {
  jobPostings: [],
  jobApplications: [],
  interviews: [],
  currentJob: null,
  loading: false,
  pagination: {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  }
}

const mutations = {
  SET_JOB_POSTINGS(state, data) {
    state.jobPostings = data.jobs || []
    if (data.pagination) {
      state.pagination = data.pagination
    }
  },
  
  SET_JOB_APPLICATIONS(state, applications) {
    state.jobApplications = applications
  },
  
  SET_INTERVIEWS(state, interviews) {
    state.interviews = interviews
  },
  
  SET_CURRENT_JOB(state, job) {
    state.currentJob = job
  },
  
  ADD_JOB_POSTING(state, job) {
    state.jobPostings.unshift(job)
  },
  
  UPDATE_JOB_POSTING(state, updatedJob) {
    const index = state.jobPostings.findIndex(job => job.id === updatedJob.id)
    if (index !== -1) {
      state.jobPostings.splice(index, 1, updatedJob)
    }
  },
  
  SET_LOADING(state, loading) {
    state.loading = loading
  }
}

const actions = {
  async fetchJobPostings({ commit }, params = {}) {
    try {
      commit('SET_LOADING', true)
      const response = await axios.get(`${API_BASE_URL}/recruitment/jobs`, { params })
      
      if (response.data.success) {
        commit('SET_JOB_POSTINGS', response.data.data)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Fetch job postings error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to fetch job postings' 
      }
    } finally {
      commit('SET_LOADING', false)
    }
  },
  
  async createJobPosting({ commit }, jobData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/recruitment/jobs`, jobData)
      
      if (response.data.success) {
        commit('ADD_JOB_POSTING', response.data.data)
        return { success: true, data: response.data.data }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Create job posting error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to create job posting' 
      }
    }
  }
}

const getters = {
  jobPostings: state => state.jobPostings,
  jobApplications: state => state.jobApplications,
  interviews: state => state.interviews,
  currentJob: state => state.currentJob,
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