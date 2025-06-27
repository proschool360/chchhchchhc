import api from './index'

// Get all departments
export const getDepartments = async () => {
  const response = await api.get('/departments')
  return response.data
}

// Get department by ID
export const getDepartment = async (id) => {
  const response = await api.get(`/departments/${id}`)
  return response.data
}

// Create new department
export const createDepartment = async (departmentData) => {
  const response = await api.post('/departments', departmentData)
  return response.data
}

// Update department
export const updateDepartment = async (id, departmentData) => {
  const response = await api.put(`/departments/${id}`, departmentData)
  return response.data
}

// Delete department
export const deleteDepartment = async (id) => {
  const response = await api.delete(`/departments/${id}`)
  return response.data
}

// Get department statistics
export const getDepartmentStats = async (id) => {
  const response = await api.get(`/departments/${id}/stats`)
  return response.data
}

// Get employees in department
export const getDepartmentEmployees = async (id) => {
  const response = await api.get(`/departments/${id}/employees`)
  return response.data
}

export default {
  getDepartments,
  getDepartment,
  createDepartment,
  updateDepartment,
  deleteDepartment,
  getDepartmentStats,
  getDepartmentEmployees
}