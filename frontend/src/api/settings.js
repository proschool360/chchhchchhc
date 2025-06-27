import api from './index'

const settingsAPI = {
  // General system settings
  getSettings() {
    return api.get('/settings')
  },

  updateSettings(data) {
    return api.put('/settings', data)
  },

  getSetting(key) {
    return api.get(`/settings/${key}`)
  },

  updateSetting(key, value) {
    return api.put(`/settings/${key}`, { value })
  },

  // Salary deduction rules
  getDeductionRules() {
    return api.get('/settings/deduction-rules')
  },

  getDeductionRule(ruleId) {
    return api.get(`/settings/deduction-rules/${ruleId}`)
  },

  createDeductionRule(data) {
    return api.post('/settings/deduction-rules', data)
  },

  updateDeductionRule(ruleId, data) {
    return api.put(`/settings/deduction-rules/${ruleId}`, data)
  },

  deleteDeductionRule(ruleId) {
    return api.delete(`/settings/deduction-rules/${ruleId}`)
  },

  setDefaultDeductionRule(ruleId) {
    return api.put(`/settings/deduction-rules/${ruleId}/set-default`)
  },

  // Overtime rules
  getOvertimeRules() {
    return api.get('/settings/overtime-rules')
  },

  getOvertimeRule(ruleId) {
    return api.get(`/settings/overtime-rules/${ruleId}`)
  },

  createOvertimeRule(data) {
    return api.post('/settings/overtime-rules', data)
  },

  updateOvertimeRule(ruleId, data) {
    return api.put(`/settings/overtime-rules/${ruleId}`, data)
  },

  deleteOvertimeRule(ruleId) {
    return api.delete(`/settings/overtime-rules/${ruleId}`)
  },

  setDefaultOvertimeRule(ruleId) {
    return api.put(`/settings/overtime-rules/${ruleId}/set-default`)
  },

  // Attendance devices
  getAttendanceDevices() {
    return api.get('/settings/attendance-devices')
  },

  getAttendanceDevice(deviceId) {
    return api.get(`/settings/attendance-devices/${deviceId}`)
  },

  createAttendanceDevice(data) {
    return api.post('/settings/attendance-devices', data)
  },

  updateAttendanceDevice(deviceId, data) {
    return api.put(`/settings/attendance-devices/${deviceId}`, data)
  },

  deleteAttendanceDevice(deviceId) {
    return api.delete(`/settings/attendance-devices/${deviceId}`)
  },

  testAttendanceDevice(deviceId) {
    return api.post(`/settings/attendance-devices/${deviceId}/test`)
  },

  syncAttendanceDevice(deviceId) {
    return api.post(`/settings/attendance-devices/${deviceId}/sync`)
  },

  // Work schedules
  getWorkSchedules() {
    return api.get('/settings/work-schedules')
  },

  getWorkSchedule(scheduleId) {
    return api.get(`/settings/work-schedules/${scheduleId}`)
  },

  createWorkSchedule(data) {
    return api.post('/settings/work-schedules', data)
  },

  updateWorkSchedule(scheduleId, data) {
    return api.put(`/settings/work-schedules/${scheduleId}`, data)
  },

  deleteWorkSchedule(scheduleId) {
    return api.delete(`/settings/work-schedules/${scheduleId}`)
  },

  setDefaultWorkSchedule(scheduleId) {
    return api.put(`/settings/work-schedules/${scheduleId}/set-default`)
  },

  // Company settings
  getCompanySettings() {
    return api.get('/settings/company')
  },

  updateCompanySettings(data) {
    return api.put('/settings/company', data)
  },

  uploadCompanyLogo(file) {
    const formData = new FormData()
    formData.append('logo', file)
    
    return api.post('/settings/company/logo', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Attendance settings
  getAttendanceSettings() {
    return api.get('/settings/attendance')
  },

  updateAttendanceSettings(data) {
    return api.put('/settings/attendance', data)
  },

  // Payroll settings
  getPayrollSettings() {
    return api.get('/settings/payroll')
  },

  updatePayrollSettings(data) {
    return api.put('/settings/payroll', data)
  },

  // Leave settings
  getLeaveSettings() {
    return api.get('/settings/leave')
  },

  updateLeaveSettings(data) {
    return api.put('/settings/leave', data)
  },

  // Notification settings
  getNotificationSettings() {
    return api.get('/settings/notifications')
  },

  updateNotificationSettings(data) {
    return api.put('/settings/notifications', data)
  },

  // Email settings
  getEmailSettings() {
    return api.get('/settings/email')
  },

  updateEmailSettings(data) {
    return api.put('/settings/email', data)
  },

  testEmailSettings() {
    return api.post('/settings/email/test')
  },

  // SMS settings
  getSMSSettings() {
    return api.get('/settings/sms')
  },

  updateSMSSettings(data) {
    return api.put('/settings/sms', data)
  },

  testSMSSettings() {
    return api.post('/settings/sms/test')
  },

  // Security settings
  getSecuritySettings() {
    return api.get('/settings/security')
  },

  updateSecuritySettings(data) {
    return api.put('/settings/security', data)
  },

  // Backup settings
  getBackupSettings() {
    return api.get('/settings/backup')
  },

  updateBackupSettings(data) {
    return api.put('/settings/backup', data)
  },

  createBackup() {
    return api.post('/settings/backup/create')
  },

  getBackupHistory() {
    return api.get('/settings/backup/history')
  },

  downloadBackup(backupId) {
    return api.get(`/settings/backup/download/${backupId}`, {
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

  restoreBackup(backupId) {
    return api.post(`/settings/backup/restore/${backupId}`)
  },

  deleteBackup(backupId) {
    return api.delete(`/settings/backup/${backupId}`)
  },

  // System maintenance
  getMaintenanceSettings() {
    return api.get('/settings/maintenance')
  },

  updateMaintenanceSettings(data) {
    return api.put('/settings/maintenance', data)
  },

  enableMaintenanceMode() {
    return api.post('/settings/maintenance/enable')
  },

  disableMaintenanceMode() {
    return api.post('/settings/maintenance/disable')
  },

  // System logs
  getSystemLogs(params = {}) {
    return api.get('/settings/logs', { params })
  },

  clearSystemLogs() {
    return api.delete('/settings/logs')
  },

  downloadSystemLogs(params = {}) {
    return api.get('/settings/logs/download', {
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

  // System information
  getSystemInfo() {
    return api.get('/settings/system-info')
  },

  // Database settings
  getDatabaseSettings() {
    return api.get('/settings/database')
  },

  optimizeDatabase() {
    return api.post('/settings/database/optimize')
  },

  getDatabaseStats() {
    return api.get('/settings/database/stats')
  },

  // Cache settings
  getCacheSettings() {
    return api.get('/settings/cache')
  },

  clearCache() {
    return api.post('/settings/cache/clear')
  },

  // API settings
  getAPISettings() {
    return api.get('/settings/api')
  },

  updateAPISettings(data) {
    return api.put('/settings/api', data)
  },

  generateAPIKey() {
    return api.post('/settings/api/generate-key')
  },

  revokeAPIKey(keyId) {
    return api.delete(`/settings/api/keys/${keyId}`)
  },

  getAPIKeys() {
    return api.get('/settings/api/keys')
  },

  // Integration settings
  getIntegrationSettings() {
    return api.get('/settings/integrations')
  },

  updateIntegrationSettings(data) {
    return api.put('/settings/integrations', data)
  },

  testIntegration(integrationType) {
    return api.post(`/settings/integrations/${integrationType}/test`)
  },

  // Theme and UI settings
  getThemeSettings() {
    return api.get('/settings/theme')
  },

  updateThemeSettings(data) {
    return api.put('/settings/theme', data)
  },

  // Language settings
  getLanguageSettings() {
    return api.get('/settings/language')
  },

  updateLanguageSettings(data) {
    return api.put('/settings/language', data)
  },

  getAvailableLanguages() {
    return api.get('/settings/language/available')
  },

  // Currency settings
  getCurrencySettings() {
    return api.get('/settings/currency')
  },

  updateCurrencySettings(data) {
    return api.put('/settings/currency', data)
  },

  getAvailableCurrencies() {
    return api.get('/settings/currency/available')
  },

  // Timezone settings
  getTimezoneSettings() {
    return api.get('/settings/timezone')
  },

  updateTimezoneSettings(data) {
    return api.put('/settings/timezone', data)
  },

  getAvailableTimezones() {
    return api.get('/settings/timezone/available')
  },

  // Import/Export settings
  exportSettings() {
    return api.get('/settings/export', {
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

  importSettings(file) {
    const formData = new FormData()
    formData.append('settings_file', file)
    
    return api.post('/settings/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Reset settings
  resetSettings(category = null) {
    const endpoint = category ? `/settings/reset/${category}` : '/settings/reset'
    return api.post(endpoint)
  },

  // Validation
  validateSettings(data) {
    return api.post('/settings/validate', data)
  }
}

export default settingsAPI