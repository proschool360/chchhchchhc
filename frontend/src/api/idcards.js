import api from './index'

const idCardAPI = {
  // Get all ID cards with pagination and filters
  async getAll(params = {}) {
    try {
      const response = await api.get('/idcards', { params })
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to fetch ID cards'
      }
    }
  },

  // Get specific ID card by ID
  async getById(id) {
    try {
      const response = await api.get(`/idcards/${id}`)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to fetch ID card'
      }
    }
  },

  // Generate ID card for employee
  async generate(employeeId, templateData = {}) {
    try {
      const response = await api.post(`/idcards/generate`, { employee_id: employeeId, ...templateData })
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to generate ID card'
      }
    }
  },

  // Bulk generate ID cards (enhanced)
  async bulkGenerate(data) {
    try {
      const response = await api.post('/idcards/bulk-generate', data)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to bulk generate ID cards'
      }
    }
  },

  // Download ID card
  download(employeeId) {
    return api.get(`/idcards/download/${employeeId}`, {
      responseType: 'blob'
    }).then(response => {
      // Handle blob response for file download
      const url = window.URL.createObjectURL(new Blob([response.data]))
      return {
        success: true,
        data: {
          download_url: url,
          blob: response.data
        }
      }
    })
  },

  // Preview ID card
  preview(employeeId) {
    return api.get(`/idcards/preview/${employeeId}`)
  },

  // Get all templates
  getTemplates() {
    return api.get('/idcards/templates')
  },

  // Get template by ID
  getTemplate(templateId) {
    return api.get(`/idcards/templates/${templateId}`)
  },

  // Create new template (simplified with layout types)
  async createTemplate(templateData) {
    try {
      const response = await api.post('/idcards/templates', templateData)
      return {
        success: true,
        data: response.data,
        message: 'Template created successfully'
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to create template'
      }
    }
  },

  // Create template variations (predefined templates)
  async createTemplateVariations() {
    try {
      const response = await api.post('/idcards/templates/variations')
      return {
        success: true,
        data: response.data,
        message: 'Template variations created successfully'
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to create template variations'
      }
    }
  },

  // Get template statistics
  async getTemplateStats() {
    try {
      const response = await api.get('/idcards/templates/stats')
      return {
        success: true,
        data: response.data,
        message: 'Template stats retrieved successfully'
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to get template stats'
      }
    }
  },

  // Update template
  updateTemplate(templateId, data) {
    return api.put(`/idcards/templates/${templateId}`, data)
  },

  // Delete template
  deleteTemplate(templateId) {
    return api.delete(`/idcards/templates/${templateId}`)
  },

  // Duplicate template
  duplicateTemplate(templateId) {
    return api.post(`/idcards/templates/${templateId}/duplicate`)
  },

  // Set default template
  setDefaultTemplate(templateId) {
    return api.put(`/idcards/templates/${templateId}/set-default`)
  },

  // Preview template
  previewTemplate(templateId, employeeData = null) {
    return api.post(`/idcards/templates/${templateId}/preview`, {
      employee_data: employeeData
    })
  },

  // Generate QR code for employee
  async generateQRCode(employeeId) {
    try {
      const response = await api.post(`/idcards/qr-code/${employeeId}`)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to generate QR code'
      }
    }
  },

  // Get QR code for employee
  async getQRCode(employeeId) {
    try {
      const response = await api.get(`/idcards/qr-code/${employeeId}`)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to fetch QR code'
      }
    }
  },

  // Get available layout types
  async getLayoutTypes() {
    try {
      const response = await api.get('/idcards/templates/layout-types')
      return {
        success: true,
        data: response.data,
        message: 'Layout types retrieved successfully'
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to get layout types'
      }
    }
  },

  // Download QR code
  downloadQRCode(employeeId) {
    return api.get(`/idcards/qr-code/${employeeId}/download`, {
      responseType: 'blob'
    }).then(response => {
      const url = window.URL.createObjectURL(new Blob([response.data]))
      return {
        success: true,
        data: {
          download_url: url,
          blob: response.data
        }
      }
    })
  },

  // Get ID card statistics
  getStats() {
    return api.get('/idcards/stats')
  },

  // Update employee QR code
  updateEmployeeQR(employeeId, qrData) {
    return api.put(`/employees/${employeeId}/qr-code`, { qr_data: qrData })
  },

  // Update employee RFID
  updateEmployeeRFID(employeeId, rfidData) {
    return api.put(`/employees/${employeeId}/rfid`, { rfid_card_id: rfidData })
  },

  // Update employee biometric ID
  updateEmployeeBiometric(employeeId, biometricData) {
    return api.put(`/employees/${employeeId}/biometric`, { biometric_id: biometricData })
  },

  // Export ID cards data
  exportCards(params = {}) {
    return api.get('/idcards/export', {
      params,
      responseType: 'blob'
    }).then(response => {
      const url = window.URL.createObjectURL(new Blob([response.data]))
      return {
        success: true,
        data: {
          download_url: url,
          blob: response.data
        }
      }
    })
  },

  // Upload template file
  uploadTemplate(file, templateData = {}) {
    const formData = new FormData()
    formData.append('template_file', file)
    
    // Add template metadata
    Object.keys(templateData).forEach(key => {
      formData.append(key, templateData[key])
    })
    
    return api.post('/idcards/templates/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Upload employee photo
  uploadEmployeePhoto(employeeId, file) {
    const formData = new FormData()
    formData.append('photo', file)
    
    return api.post(`/employees/${employeeId}/photo`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Get template preview with sample data
  getTemplatePreviewWithSample(templateId) {
    return api.get(`/idcards/templates/${templateId}/preview-sample`)
  },

  // Validate template design
  validateTemplate(templateData) {
    return api.post('/idcards/templates/validate', templateData)
  },

  // Get available fonts for templates
  getAvailableFonts() {
    return api.get('/idcards/fonts')
  },

  // Get template usage statistics
  getTemplateUsage(templateId) {
    return api.get(`/idcards/templates/${templateId}/usage`)
  },

  // Batch update employee cards
  batchUpdateCards(updates) {
    return api.put('/idcards/batch-update', { updates })
  },

  // Get card generation history
  getGenerationHistory(params = {}) {
    return api.get('/idcards/history', { params })
  },

  // Regenerate expired cards
  regenerateExpiredCards() {
    return api.post('/idcards/regenerate-expired')
  },

  // Get card print queue
  getPrintQueue() {
    return api.get('/idcards/print-queue')
  },

  // Add cards to print queue
  addToPrintQueue(cardIds) {
    return api.post('/idcards/print-queue', { card_ids: cardIds })
  },

  // Remove cards from print queue
  removeFromPrintQueue(queueIds) {
    return api.delete('/idcards/print-queue', { data: { queue_ids: queueIds } })
  },

  // Mark cards as printed
  markAsPrinted(cardIds) {
    return api.put('/idcards/mark-printed', { card_ids: cardIds })
  }
}

// Export both APIs for backward compatibility
export default idCardAPI
export { idCardAPI }