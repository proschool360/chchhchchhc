import api from './index'

const idCardAPI = {
  // Get all ID cards with pagination and filters
  getAll(params = {}) {
    return api.get('/idcards', { params })
  },

  // Get specific ID card by employee ID
  getById(employeeId) {
    return api.get(`/idcards/employee/${employeeId}`)
  },

  // Generate ID card for employee
  generate(employeeId, data = {}) {
    return api.post(`/idcards/generate/${employeeId}`, data)
  },

  // Bulk generate ID cards
  bulkGenerate(data = {}) {
    return api.post('/idcards/bulk-generate', data)
  },

  // Download ID card
  download(employeeId, format = 'png') {
    return api.get(`/idcards/download/${employeeId}`, {
      params: { format },
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

  // Preview ID card
  preview(employeeId) {
    return api.get(`/idcards/preview/${employeeId}`)
  },

  // Print ID card
  print(employeeId, printerSettings = {}) {
    return api.post(`/idcards/print/${employeeId}`, printerSettings)
  },

  // Bulk print ID cards
  bulkPrint(employeeIds, printerSettings = {}) {
    return api.post('/idcards/bulk-print', {
      employee_ids: employeeIds,
      ...printerSettings
    })
  },

  // Template management
  getTemplates() {
    return api.get('/idcards/templates')
  },

  getTemplate(templateId) {
    return api.get(`/idcards/templates/${templateId}`)
  },

  createTemplate(data) {
    return api.post('/idcards/templates', data)
  },

  updateTemplate(templateId, data) {
    return api.put(`/idcards/templates/${templateId}`, data)
  },

  deleteTemplate(templateId) {
    return api.delete(`/idcards/templates/${templateId}`)
  },

  duplicateTemplate(templateId) {
    return api.post(`/idcards/templates/${templateId}/duplicate`)
  },

  setDefaultTemplate(templateId) {
    return api.put(`/idcards/templates/${templateId}/set-default`)
  },

  previewTemplate(templateId, sampleData = {}) {
    return api.post(`/idcards/templates/${templateId}/preview`, sampleData)
  },

  // QR Code management
  generateQRCode(employeeId) {
    return api.post(`/idcards/qr-code/generate/${employeeId}`)
  },

  getQRCode(employeeId) {
    return api.get(`/idcards/qr-code/${employeeId}`)
  },

  downloadQRCode(employeeId, format = 'png') {
    return api.get(`/idcards/qr-code/download/${employeeId}`, {
      params: { format },
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

  // Batch operations
  batchDownload(employeeIds, format = 'zip') {
    return api.post('/idcards/batch-download', {
      employee_ids: employeeIds,
      format
    }, {
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

  // Statistics and reports
  getStats() {
    return api.get('/idcards/stats')
  },

  getGenerationReport(params = {}) {
    return api.get('/idcards/reports/generation', { params })
  },

  // Card status management
  updateCardStatus(employeeId, status) {
    return api.put(`/idcards/status/${employeeId}`, { status })
  },

  // Card validation
  validateCard(cardData) {
    return api.post('/idcards/validate', cardData)
  },

  // Export functionality
  exportCards(params = {}, format = 'excel') {
    return api.get('/idcards/export', {
      params: { ...params, format },
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

  // Card history and audit
  getCardHistory(employeeId) {
    return api.get(`/idcards/history/${employeeId}`)
  },

  // Printer management
  getPrinters() {
    return api.get('/idcards/printers')
  },

  getPrinterStatus(printerId) {
    return api.get(`/idcards/printers/${printerId}/status`)
  },

  configurePrinter(printerId, settings) {
    return api.put(`/idcards/printers/${printerId}/configure`, settings)
  }
}

export default idCardAPI