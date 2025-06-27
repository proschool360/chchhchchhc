// Error Logger Utility for HRMS Dashboard Debugging

class ErrorLogger {
  constructor() {
    this.errors = []
    this.maxErrors = 100
    this.init()
  }

  init() {
    // Capture console errors
    this.captureConsoleErrors()
    
    // Capture unhandled promise rejections
    this.captureUnhandledRejections()
    
    // Capture Vue errors if available
    this.captureVueErrors()
    
    // Log initial state
    this.log('info', 'ErrorLogger initialized', {
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent,
      url: window.location.href
    })
  }

  captureConsoleErrors() {
    const originalError = console.error
    const originalWarn = console.warn
    
    console.error = (...args) => {
      this.log('error', 'Console Error', {
        message: args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : String(arg)).join(' '),
        timestamp: new Date().toISOString()
      })
      originalError.apply(console, args)
    }
    
    console.warn = (...args) => {
      this.log('warning', 'Console Warning', {
        message: args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : String(arg)).join(' '),
        timestamp: new Date().toISOString()
      })
      originalWarn.apply(console, args)
    }
  }

  captureUnhandledRejections() {
    window.addEventListener('unhandledrejection', (event) => {
      this.log('error', 'Unhandled Promise Rejection', {
        reason: event.reason,
        promise: event.promise,
        timestamp: new Date().toISOString()
      })
    })
    
    window.addEventListener('error', (event) => {
      this.log('error', 'JavaScript Error', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        error: event.error,
        timestamp: new Date().toISOString()
      })
    })
  }

  captureVueErrors() {
    // This will be set up in main.js
    window.vueErrorHandler = (err, instance, info) => {
      this.log('error', 'Vue Error', {
        message: err.message,
        stack: err.stack,
        componentInfo: info,
        componentName: instance?.$options?.name || 'Unknown',
        timestamp: new Date().toISOString()
      })
    }
  }

  log(level, message, data = {}) {
    const logEntry = {
      id: Date.now() + Math.random(),
      level,
      message,
      data,
      timestamp: new Date().toISOString(),
      url: window.location.href,
      userAgent: navigator.userAgent
    }
    
    this.errors.unshift(logEntry)
    
    // Keep only the latest errors
    if (this.errors.length > this.maxErrors) {
      this.errors = this.errors.slice(0, this.maxErrors)
    }
    
    // Store in localStorage for persistence
    this.saveToStorage()
    
    // Console output with styling
    this.consoleOutput(logEntry)
  }

  consoleOutput(logEntry) {
    const styles = {
      error: 'color: #ff4757; font-weight: bold;',
      warning: 'color: #ffa502; font-weight: bold;',
      info: 'color: #3742fa; font-weight: bold;',
      success: 'color: #2ed573; font-weight: bold;'
    }
    
    console.group(`%c[${logEntry.level.toUpperCase()}] ${logEntry.message}`, styles[logEntry.level] || '')
    console.log('Timestamp:', logEntry.timestamp)
    console.log('URL:', logEntry.url)
    if (Object.keys(logEntry.data).length > 0) {
      console.log('Data:', logEntry.data)
    }
    console.groupEnd()
    
    // Also write to file via backend API
    this.writeToFile(logEntry)
  }

  async writeToFile(logEntry) {
    try {
      const response = await fetch('https://whatsapp.proschool360.com/api/logs/write', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          level: logEntry.level,
          message: logEntry.message,
          data: logEntry.data,
          timestamp: logEntry.timestamp,
          url: logEntry.url,
          userAgent: logEntry.userAgent
        })
      })
      
      if (!response.ok) {
        console.warn('Failed to write log to file:', response.statusText)
      }
    } catch (error) {
      console.warn('Error writing log to file:', error)
    }
  }

  saveToStorage() {
    try {
      localStorage.setItem('hrms_error_logs', JSON.stringify(this.errors.slice(0, 50)))
    } catch (e) {
      console.warn('Could not save error logs to localStorage:', e)
    }
  }

  loadFromStorage() {
    try {
      const stored = localStorage.getItem('hrms_error_logs')
      if (stored) {
        const parsed = JSON.parse(stored)
        this.errors = [...parsed, ...this.errors]
      }
    } catch (e) {
      console.warn('Could not load error logs from localStorage:', e)
    }
  }

  // API Error Logger
  logApiError(url, method, status, response, requestData = null) {
    this.log('error', 'API Error', {
      url,
      method,
      status,
      response,
      requestData,
      timestamp: new Date().toISOString()
    })
  }

  // Component Lifecycle Logger
  logComponentLifecycle(componentName, lifecycle, data = {}) {
    this.log('info', `Component ${lifecycle}`, {
      componentName,
      lifecycle,
      ...data,
      timestamp: new Date().toISOString()
    })
  }

  // Route Change Logger
  logRouteChange(from, to) {
    this.log('info', 'Route Change', {
      from: from.path,
      to: to.path,
      fromName: from.name,
      toName: to.name,
      timestamp: new Date().toISOString()
    })
  }

  // Store State Logger
  logStoreAction(action, payload, result) {
    this.log('info', 'Store Action', {
      action,
      payload,
      result,
      timestamp: new Date().toISOString()
    })
  }

  // Get all errors
  getAllErrors() {
    return this.errors
  }

  // Get errors by level
  getErrorsByLevel(level) {
    return this.errors.filter(error => error.level === level)
  }

  // Get recent errors
  getRecentErrors(minutes = 10) {
    const cutoff = new Date(Date.now() - minutes * 60 * 1000)
    return this.errors.filter(error => new Date(error.timestamp) > cutoff)
  }

  // Clear all errors
  clearErrors() {
    this.errors = []
    localStorage.removeItem('hrms_error_logs')
    this.log('info', 'Error logs cleared')
  }

  // Export errors as JSON
  exportErrors() {
    const dataStr = JSON.stringify(this.errors, null, 2)
    const dataBlob = new Blob([dataStr], { type: 'application/json' })
    const url = URL.createObjectURL(dataBlob)
    const link = document.createElement('a')
    link.href = url
    link.download = `hrms-error-logs-${new Date().toISOString().split('T')[0]}.json`
    link.click()
    URL.revokeObjectURL(url)
  }

  // Generate error report
  generateReport() {
    const errorCounts = this.errors.reduce((acc, error) => {
      acc[error.level] = (acc[error.level] || 0) + 1
      return acc
    }, {})

    const recentErrors = this.getRecentErrors(60) // Last hour
    const apiErrors = this.errors.filter(e => e.message.includes('API Error'))
    const vueErrors = this.errors.filter(e => e.message.includes('Vue Error'))

    return {
      summary: {
        totalErrors: this.errors.length,
        errorCounts,
        recentErrorsCount: recentErrors.length,
        apiErrorsCount: apiErrors.length,
        vueErrorsCount: vueErrors.length
      },
      recentErrors: recentErrors.slice(0, 10),
      apiErrors: apiErrors.slice(0, 10),
      vueErrors: vueErrors.slice(0, 10)
    }
  }
}

// Create singleton instance
const errorLogger = new ErrorLogger()

// Load existing errors from storage
errorLogger.loadFromStorage()

// Make it globally available
window.errorLogger = errorLogger

export default errorLogger