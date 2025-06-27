import api from './index'

// Get all employees
export const getEmployees = async (params = {}) => {
  const response = await api.get('/employees', { params })
  return response.data
}

// Get employee by ID
export const getEmployee = async (id) => {
  const response = await api.get(`/employees/${id}`)
  return response.data
}

// Get employee by employee ID (badge number)
export const getEmployeeByEmployeeId = async (employeeId) => {
  const response = await api.get(`/employees/by-employee-id/${employeeId}`)
  return response.data
}

// Create new employee
export const createEmployee = async (employeeData) => {
  const response = await api.post('/employees', employeeData)
  return response.data
}

// Update employee
export const updateEmployee = async (id, employeeData) => {
  const response = await api.put(`/employees/${id}`, employeeData)
  return response.data
}

// Delete employee
export const deleteEmployee = async (id) => {
  const response = await api.delete(`/employees/${id}`)
  return response.data
}

// Upload employee photo
export const uploadEmployeePhoto = async (id, photoFile) => {
  const formData = new FormData()
  formData.append('photo', photoFile)
  const response = await api.post(`/employees/${id}/photo`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
  return response.data
}

// Get employee attendance summary
export const getEmployeeAttendance = async (id, params = {}) => {
  const response = await api.get(`/employees/${id}/attendance`, { params })
  return response.data
}

// Get employee leave balance
export const getEmployeeLeaveBalance = async (id) => {
  const response = await api.get(`/employees/${id}/leave-balance`)
  return response.data
}

// Search employees
export const searchEmployees = async (query) => {
  const response = await api.get(`/employees/search?q=${encodeURIComponent(query)}`)
  return response.data
}

export default {
  getEmployees,
  getEmployee,
  getEmployeeByEmployeeId,
  createEmployee,
  updateEmployee,
  deleteEmployee,
  uploadEmployeePhoto,
  getEmployeeAttendance,
  getEmployeeLeaveBalance,
  searchEmployees
}