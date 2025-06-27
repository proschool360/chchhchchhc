/**
 * Date utility functions for formatting and manipulation
 */

/**
 * Format a date string or Date object to a readable format
 * @param {string|Date} date - The date to format
 * @param {string} format - The format type ('short', 'long', 'datetime', 'time')
 * @returns {string} Formatted date string
 */
export function formatDate(date, format = 'short') {
  if (!date) return '-'
  
  const dateObj = typeof date === 'string' ? new Date(date) : date
  
  if (isNaN(dateObj.getTime())) {
    return 'Invalid Date'
  }
  
  const options = {
    short: {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    },
    long: {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    },
    datetime: {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    },
    time: {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    }
  }
  
  return dateObj.toLocaleDateString('en-US', options[format] || options.short)
}

/**
 * Format a date to ISO string (YYYY-MM-DD)
 * @param {string|Date} date - The date to format
 * @returns {string} ISO date string
 */
export function formatDateISO(date) {
  if (!date) return ''
  
  const dateObj = typeof date === 'string' ? new Date(date) : date
  
  if (isNaN(dateObj.getTime())) {
    return ''
  }
  
  return dateObj.toISOString().split('T')[0]
}

/**
 * Format a date for display in tables (MM/DD/YYYY)
 * @param {string|Date} date - The date to format
 * @returns {string} Formatted date string
 */
export function formatDateTable(date) {
  if (!date) return '-'
  
  const dateObj = typeof date === 'string' ? new Date(date) : date
  
  if (isNaN(dateObj.getTime())) {
    return 'Invalid Date'
  }
  
  return dateObj.toLocaleDateString('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: 'numeric'
  })
}

/**
 * Get relative time (e.g., "2 hours ago", "3 days ago")
 * @param {string|Date} date - The date to compare
 * @returns {string} Relative time string
 */
export function getRelativeTime(date) {
  if (!date) return '-'
  
  const dateObj = typeof date === 'string' ? new Date(date) : date
  
  if (isNaN(dateObj.getTime())) {
    return 'Invalid Date'
  }
  
  const now = new Date()
  const diffInSeconds = Math.floor((now - dateObj) / 1000)
  
  if (diffInSeconds < 60) {
    return 'Just now'
  }
  
  const diffInMinutes = Math.floor(diffInSeconds / 60)
  if (diffInMinutes < 60) {
    return `${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''} ago`
  }
  
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) {
    return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`
  }
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 30) {
    return `${diffInDays} day${diffInDays > 1 ? 's' : ''} ago`
  }
  
  const diffInMonths = Math.floor(diffInDays / 30)
  if (diffInMonths < 12) {
    return `${diffInMonths} month${diffInMonths > 1 ? 's' : ''} ago`
  }
  
  const diffInYears = Math.floor(diffInMonths / 12)
  return `${diffInYears} year${diffInYears > 1 ? 's' : ''} ago`
}

/**
 * Check if a date is today
 * @param {string|Date} date - The date to check
 * @returns {boolean} True if the date is today
 */
export function isToday(date) {
  if (!date) return false
  
  const dateObj = typeof date === 'string' ? new Date(date) : date
  const today = new Date()
  
  return dateObj.toDateString() === today.toDateString()
}

/**
 * Check if a date is in the current week
 * @param {string|Date} date - The date to check
 * @returns {boolean} True if the date is in the current week
 */
export function isThisWeek(date) {
  if (!date) return false
  
  const dateObj = typeof date === 'string' ? new Date(date) : date
  const today = new Date()
  const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()))
  const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6))
  
  return dateObj >= startOfWeek && dateObj <= endOfWeek
}

/**
 * Get the start and end dates of the current month
 * @returns {Object} Object with start and end dates
 */
export function getCurrentMonthRange() {
  const now = new Date()
  const start = new Date(now.getFullYear(), now.getMonth(), 1)
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  
  return {
    start: formatDateISO(start),
    end: formatDateISO(end)
  }
}

/**
 * Calculate age from birth date
 * @param {string|Date} birthDate - The birth date
 * @returns {number} Age in years
 */
export function calculateAge(birthDate) {
  if (!birthDate) return 0
  
  const birth = typeof birthDate === 'string' ? new Date(birthDate) : birthDate
  const today = new Date()
  
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  
  return age
}

/**
 * Format duration in milliseconds to human readable format
 * @param {number} milliseconds - Duration in milliseconds
 * @returns {string} Formatted duration string
 */
export function formatDuration(milliseconds) {
  if (!milliseconds || milliseconds < 0) return '0 minutes'
  
  const seconds = Math.floor(milliseconds / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)
  
  if (days > 0) {
    return `${days} day${days > 1 ? 's' : ''} ${hours % 24} hour${(hours % 24) > 1 ? 's' : ''}`
  }
  
  if (hours > 0) {
    return `${hours} hour${hours > 1 ? 's' : ''} ${minutes % 60} minute${(minutes % 60) > 1 ? 's' : ''}`
  }
  
  if (minutes > 0) {
    return `${minutes} minute${minutes > 1 ? 's' : ''}`
  }
  
  return `${seconds} second${seconds > 1 ? 's' : ''}`
}

export default {
  formatDate,
  formatDateISO,
  formatDateTable,
  getRelativeTime,
  isToday,
  isThisWeek,
  getCurrentMonthRange,
  calculateAge,
  formatDuration
}