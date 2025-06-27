/**
 * ResizeObserver Error Fix
 * 
 * This utility fixes the common "ResizeObserver loop completed with undelivered notifications" error
 * that occurs when using charting libraries like ECharts or other components that observe element sizes.
 * 
 * The error is harmless but can clutter the console. This fix suppresses the specific error
 * while preserving other error handling.
 */

// Store the original error handler
let originalErrorHandler = null

/**
 * Initialize the ResizeObserver error fix
 */
export function initResizeObserverFix() {
  // Only initialize once
  if (originalErrorHandler !== null) {
    return
  }

  // Store the original error handler
  originalErrorHandler = window.onerror

  // Set up our custom error handler
  window.onerror = (message, source, lineno, colno, error) => {
    // Suppress ResizeObserver errors
    if (typeof message === 'string' && message.includes('ResizeObserver loop completed with undelivered notifications')) {
      console.debug('ResizeObserver error suppressed:', message)
      return true // Suppress this specific error
    }

    // Call the original error handler for other errors
    if (originalErrorHandler) {
      return originalErrorHandler(message, source, lineno, colno, error)
    }

    return false // Let other errors bubble up
  }

  // Also handle unhandled promise rejections that might contain ResizeObserver errors
  const originalUnhandledRejection = window.onunhandledrejection
  window.onunhandledrejection = (event) => {
    if (event.reason && typeof event.reason.message === 'string' && 
        event.reason.message.includes('ResizeObserver loop completed with undelivered notifications')) {
      console.debug('ResizeObserver promise rejection suppressed:', event.reason.message)
      event.preventDefault()
      return
    }

    // Call the original handler for other rejections
    if (originalUnhandledRejection) {
      originalUnhandledRejection(event)
    }
  }

  console.debug('ResizeObserver error fix initialized')
}

/**
 * Remove the ResizeObserver error fix and restore original handlers
 */
export function removeResizeObserverFix() {
  if (originalErrorHandler !== null) {
    window.onerror = originalErrorHandler
    originalErrorHandler = null
  }
  console.debug('ResizeObserver error fix removed')
}

/**
 * Debounced ResizeObserver wrapper for better performance
 */
export function createDebouncedResizeObserver(callback, delay = 100) {
  let timeoutId = null

  return new ResizeObserver((entries) => {
    if (timeoutId) {
      clearTimeout(timeoutId)
    }

    timeoutId = setTimeout(() => {
      try {
        callback(entries)
      } catch (error) {
        if (!error.message.includes('ResizeObserver loop completed with undelivered notifications')) {
          console.error('ResizeObserver callback error:', error)
        }
      }
    }, delay)
  })
}

export default {
  initResizeObserverFix,
  removeResizeObserverFix,
  createDebouncedResizeObserver
}