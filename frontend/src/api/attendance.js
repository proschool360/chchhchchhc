import api from './index'

const attendanceAPI = {
  // Basic attendance operations
  getAll(params = {}) {
    return api.get('/attendance', { params })
  },

  getById(id) {
    return api.get(`/attendance/${id}`)
  },

  create(data) {
    return api.post('/attendance', data)
  },

  update(id, data) {
    return api.put(`/attendance/${id}`, data)
  },

  delete(id) {
    return api.delete(`/attendance/${id}`)
  },

  // Extended attendance features
  
  // Manual clock in/out
  clockInManual(data) {
    return api.post('/attendance/clock-in/manual', data)
  },

  clockOutManual(data) {
    return api.post('/attendance/clock-out/manual', data)
  },

  // QR Code attendance
  clockInQR(qrData) {
    return api.post('/attendance/clock-in/qr', { qr_data: qrData })
  },

  clockOutQR(qrData) {
    return api.post('/attendance/clock-out/qr', { qr_data: qrData })
  },

  // QR Code generation and management
  generateEmployeeQR(employeeId) {
    return api.get(`/attendance/qr/generate/${employeeId}`)
  },

  getEmployeeQR(employeeId) {
    return api.get(`/attendance/qr/employee/${employeeId}`)
  },

  validateQRCode(qrData) {
    return api.post('/attendance/qr/validate', { qr_data: qrData })
  },

  // Employee lookup for QR scanning
  lookupEmployee(identifier) {
    return api.get(`/attendance/employee/lookup/${identifier}`)
  },

  // RFID attendance
  clockInRFID(rfidData) {
    return api.post('/attendance/clock-in/rfid', { rfid_card_id: rfidData })
  },

  clockOutRFID(rfidData) {
    return api.post('/attendance/clock-out/rfid', { rfid_card_id: rfidData })
  },

  // Biometric attendance
  clockInBiometric(biometricData) {
    return api.post('/attendance/clock-in/biometric', { biometric_id: biometricData })
  },

  clockOutBiometric(biometricData) {
    return api.post('/attendance/clock-out/biometric', { biometric_id: biometricData })
  },

  // General clock out (auto-detect method)
  clockOut(employeeId, data = {}) {
    return api.post(`/attendance/clock-out/${employeeId}`, data)
  },

  // Attendance summary and reports
  getSummary(params = {}) {
    return api.get('/attendance/summary', { params })
  },

  getDailySummary(date, params = {}) {
    return api.get(`/attendance/summary/daily/${date}`, { params })
  },

  getWeeklySummary(startDate, params = {}) {
    return api.get(`/attendance/summary/weekly/${startDate}`, { params })
  },

  getMonthlySummary(month, year, params = {}) {
    return api.get(`/attendance/summary/monthly/${year}/${month}`, { params })
  },

  // Employee specific attendance
  getEmployeeAttendance(employeeId, params = {}) {
    return api.get(`/attendance/employee/${employeeId}`, { params })
  },

  getEmployeeSummary(employeeId, params = {}) {
    return api.get(`/attendance/employee/${employeeId}/summary`, { params })
  },

  // Today's attendance
  getTodayAttendance(params = {}) {
    return api.get('/attendance/today', { params })
  },

  // Live attendance status
  getLiveStatus() {
    return api.get('/attendance/live-status')
  },

  // Attendance statistics
  getStats(params = {}) {
    return api.get('/attendance/stats', { params })
  },

  // Late arrivals and early departures
  getLateArrivals(params = {}) {
    return api.get('/attendance/late-arrivals', { params })
  },

  getEarlyDepartures(params = {}) {
    return api.get('/attendance/early-departures', { params })
  },

  // Overtime tracking
  getOvertime(params = {}) {
    return api.get('/attendance/overtime', { params })
  },

  getEmployeeOvertime(employeeId, params = {}) {
    return api.get(`/attendance/overtime/employee/${employeeId}`, { params })
  },

  // Attendance reports
  getDailyReport(date, params = {}) {
    return api.get(`/attendance/reports/daily/${date}`, { params })
  },

  getWeeklyReport(startDate, params = {}) {
    return api.get(`/attendance/reports/weekly/${startDate}`, { params })
  },

  getMonthlyReport(month, year, params = {}) {
    return api.get(`/attendance/reports/monthly/${year}/${month}`, { params })
  },

  getRegularityReport(params = {}) {
    return api.get('/attendance/reports/regularity', { params })
  },

  // Export attendance data
  exportDaily(date, format = 'excel') {
    return api.get(`/attendance/export/daily/${date}`, {
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

  exportWeekly(startDate, format = 'excel') {
    return api.get(`/attendance/export/weekly/${startDate}`, {
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

  exportMonthly(month, year, format = 'excel') {
    return api.get(`/attendance/export/monthly/${year}/${month}`, {
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

  exportCustom(params = {}, format = 'excel') {
    return api.get('/attendance/export/custom', {
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

  // Attendance devices
  getDevices() {
    return api.get('/attendance/devices')
  },

  getDevice(deviceId) {
    return api.get(`/attendance/devices/${deviceId}`)
  },

  createDevice(data) {
    return api.post('/attendance/devices', data)
  },

  updateDevice(deviceId, data) {
    return api.put(`/attendance/devices/${deviceId}`, data)
  },

  deleteDevice(deviceId) {
    return api.delete(`/attendance/devices/${deviceId}`)
  },

  // Device status and sync
  getDeviceStatus(deviceId) {
    return api.get(`/attendance/devices/${deviceId}/status`)
  },

  syncDevice(deviceId) {
    return api.post(`/attendance/devices/${deviceId}/sync`)
  },

  // Work schedules
  getWorkSchedules() {
    return api.get('/attendance/work-schedules')
  },

  getWorkSchedule(scheduleId) {
    return api.get(`/attendance/work-schedules/${scheduleId}`)
  },

  createWorkSchedule(data) {
    return api.post('/attendance/work-schedules', data)
  },

  updateWorkSchedule(scheduleId, data) {
    return api.put(`/attendance/work-schedules/${scheduleId}`, data)
  },

  deleteWorkSchedule(scheduleId) {
    return api.delete(`/attendance/work-schedules/${scheduleId}`)
  },

  // Employee work schedule assignment
  assignWorkSchedule(employeeId, scheduleId) {
    return api.post(`/attendance/employees/${employeeId}/work-schedule`, {
      work_schedule_id: scheduleId
    })
  },

  getEmployeeWorkSchedule(employeeId) {
    return api.get(`/attendance/employees/${employeeId}/work-schedule`)
  },

  // Bulk operations
  bulkClockIn(employees) {
    return api.post('/attendance/bulk/clock-in', { employees })
  },

  bulkClockOut(employees) {
    return api.post('/attendance/bulk/clock-out', { employees })
  },

  bulkUpdate(updates) {
    return api.put('/attendance/bulk/update', { updates })
  },

  bulkDelete(attendanceIds) {
    return api.delete('/attendance/bulk/delete', { data: { attendance_ids: attendanceIds } })
  },

  // Attendance corrections and approvals
  requestCorrection(attendanceId, data) {
    return api.post(`/attendance/${attendanceId}/correction-request`, data)
  },

  approveCorrection(correctionId, data = {}) {
    return api.put(`/attendance/corrections/${correctionId}/approve`, data)
  },

  rejectCorrection(correctionId, data = {}) {
    return api.put(`/attendance/corrections/${correctionId}/reject`, data)
  },

  getCorrections(params = {}) {
    return api.get('/attendance/corrections', { params })
  },

  // Real-time attendance monitoring
  getRealtimeData() {
    return api.get('/attendance/realtime')
  },

  // Attendance alerts and notifications
  getAlerts(params = {}) {
    return api.get('/attendance/alerts', { params })
  },

  markAlertAsRead(alertId) {
    return api.put(`/attendance/alerts/${alertId}/read`)
  },

  // Attendance patterns and analytics
  getAttendancePatterns(employeeId, params = {}) {
    return api.get(`/attendance/patterns/${employeeId}`, { params })
  },

  getAttendanceTrends(params = {}) {
    return api.get('/attendance/trends', { params })
  },

  // Holiday and leave integration
  getHolidays(year) {
    return api.get(`/attendance/holidays/${year}`)
  },

  checkLeaveStatus(employeeId, date) {
    return api.get(`/attendance/leave-status/${employeeId}/${date}`)
  }
}

export default attendanceAPI