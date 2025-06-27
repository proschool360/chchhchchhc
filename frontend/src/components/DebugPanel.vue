<template>
  <div class="debug-panel" v-if="showDebug">
    <div class="debug-header">
      <h3>🐛 Debug Panel</h3>
      <div class="debug-controls">
        <el-button size="small" @click="refreshLogs">Refresh</el-button>
        <el-button size="small" @click="clearLogs">Clear</el-button>
        <el-button size="small" @click="exportLogs">Export</el-button>
        <el-button size="small" @click="toggleDebug">Hide</el-button>
      </div>
    </div>
    
    <div class="debug-content">
      <el-tabs v-model="activeTab" type="border-card">
        <el-tab-pane label="Recent Errors" name="errors">
          <div class="error-list">
            <div v-if="recentErrors.length === 0" class="no-errors">
              ✅ No recent errors found
            </div>
            <div 
              v-for="error in recentErrors" 
              :key="error.id"
              class="error-item"
              :class="`error-${error.level}`"
            >
              <div class="error-header">
                <span class="error-level">{{ error.level.toUpperCase() }}</span>
                <span class="error-time">{{ formatTime(error.timestamp) }}</span>
              </div>
              <div class="error-message">{{ error.message }}</div>
              <div v-if="error.data && Object.keys(error.data).length > 0" class="error-data">
                <pre>{{ JSON.stringify(error.data, null, 2) }}</pre>
              </div>
            </div>
          </div>
        </el-tab-pane>
        
        <el-tab-pane label="Store State" name="store">
          <div class="store-info">
            <h4>Authentication</h4>
            <pre>{{ JSON.stringify(authState, null, 2) }}</pre>
            
            <h4>Reports Module</h4>
            <pre>{{ JSON.stringify(reportsState, null, 2) }}</pre>
            
            <h4>Available Store Modules</h4>
            <pre>{{ JSON.stringify(storeModules, null, 2) }}</pre>
          </div>
        </el-tab-pane>
        
        <el-tab-pane label="Network" name="network">
          <div class="network-info">
            <h4>API Errors</h4>
            <div v-if="apiErrors.length === 0" class="no-errors">
              ✅ No API errors found
            </div>
            <div 
              v-for="error in apiErrors" 
              :key="error.id"
              class="api-error"
            >
              <div class="api-header">
                <span class="api-method">{{ error.data.method }}</span>
                <span class="api-url">{{ error.data.url }}</span>
                <span class="api-status">{{ error.data.status }}</span>
              </div>
              <div class="api-response">
                <pre>{{ JSON.stringify(error.data.response, null, 2) }}</pre>
              </div>
            </div>
          </div>
        </el-tab-pane>
        
        <el-tab-pane label="Component Lifecycle" name="lifecycle">
          <div class="lifecycle-info">
            <div 
              v-for="log in lifecycleLogs" 
              :key="log.id"
              class="lifecycle-item"
            >
              <div class="lifecycle-header">
                <span class="component-name">{{ log.data.componentName }}</span>
                <span class="lifecycle-event">{{ log.data.lifecycle }}</span>
                <span class="lifecycle-time">{{ formatTime(log.timestamp) }}</span>
              </div>
              <div v-if="log.data && Object.keys(log.data).length > 2" class="lifecycle-data">
                <pre>{{ JSON.stringify(log.data, null, 2) }}</pre>
              </div>
            </div>
          </div>
        </el-tab-pane>
        
        <el-tab-pane label="System Info" name="system">
          <div class="system-info">
            <h4>Browser Information</h4>
            <pre>{{ JSON.stringify(systemInfo, null, 2) }}</pre>
            
            <h4>Error Summary</h4>
            <pre>{{ JSON.stringify(errorSummary, null, 2) }}</pre>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
  
  <!-- Debug Toggle Button -->
  <div class="debug-toggle" v-if="!showDebug" @click="toggleDebug">
    🐛 Debug
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useStore } from 'vuex'
import errorLogger from '@/utils/errorLogger'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)

const store = useStore()
const showDebug = ref(false)
const activeTab = ref('errors')
const logs = ref([])
const refreshInterval = ref(null)

// Computed properties
const recentErrors = computed(() => {
  return logs.value
    .filter(log => ['error', 'warning'].includes(log.level))
    .slice(0, 20)
})

const apiErrors = computed(() => {
  return logs.value
    .filter(log => log.message.includes('API Error'))
    .slice(0, 10)
})

const lifecycleLogs = computed(() => {
  return logs.value
    .filter(log => log.message.includes('Component'))
    .slice(0, 20)
})

const authState = computed(() => {
  try {
    return {
      isAuthenticated: store.getters['auth/isAuthenticated'],
      user: store.getters['auth/user'],
      token: !!store.getters['auth/token']
    }
  } catch (e) {
    return { error: e.message }
  }
})

const reportsState = computed(() => {
  try {
    return {
      dashboardData: store.getters['reports/dashboardData'],
      dashboardStats: store.getters['reports/dashboardStats'],
      loading: store.getters['reports/loading']
    }
  } catch (e) {
    return { error: e.message }
  }
})

const storeModules = computed(() => {
  try {
    return Object.keys(store._modules.root._children || {})
  } catch (e) {
    return { error: e.message }
  }
})

const systemInfo = computed(() => {
  return {
    userAgent: navigator.userAgent,
    url: window.location.href,
    timestamp: new Date().toISOString(),
    viewport: {
      width: window.innerWidth,
      height: window.innerHeight
    },
    localStorage: {
      available: typeof Storage !== 'undefined',
      errorLogs: !!localStorage.getItem('hrms_error_logs')
    }
  }
})

const errorSummary = computed(() => {
  if (!window.errorLogger) return { error: 'Error logger not available' }
  return window.errorLogger.generateReport()
})

// Methods
const refreshLogs = () => {
  if (window.errorLogger) {
    logs.value = window.errorLogger.getAllErrors()
    errorLogger.log('info', 'Debug panel refreshed', { logCount: logs.value.length })
  }
}

const clearLogs = () => {
  if (window.errorLogger) {
    window.errorLogger.clearErrors()
    logs.value = []
  }
}

const exportLogs = () => {
  if (window.errorLogger) {
    window.errorLogger.exportErrors()
  }
}

const toggleDebug = () => {
  showDebug.value = !showDebug.value
  if (showDebug.value) {
    refreshLogs()
    // Auto-refresh every 5 seconds when debug panel is open
    refreshInterval.value = setInterval(refreshLogs, 5000)
  } else {
    if (refreshInterval.value) {
      clearInterval(refreshInterval.value)
      refreshInterval.value = null
    }
  }
}

const formatTime = (timestamp) => {
  return dayjs(timestamp).fromNow()
}

// Lifecycle
onMounted(() => {
  refreshLogs()
  
  // Check for debug mode in URL
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.get('debug') === 'true') {
    showDebug.value = true
    toggleDebug()
  }
  
  // Listen for keyboard shortcut (Ctrl+Shift+D)
  const handleKeydown = (e) => {
    if (e.ctrlKey && e.shiftKey && e.key === 'D') {
      e.preventDefault()
      toggleDebug()
    }
  }
  
  window.addEventListener('keydown', handleKeydown)
  
  // Cleanup
  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown)
    if (refreshInterval.value) {
      clearInterval(refreshInterval.value)
    }
  })
})
</script>

<style lang="scss" scoped>
.debug-panel {
  position: fixed;
  top: 20px;
  right: 20px;
  width: 600px;
  max-height: 80vh;
  background: white;
  border: 2px solid #e74c3c;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  z-index: 9999;
  font-family: 'Courier New', monospace;
  font-size: 12px;
  
  .debug-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: #e74c3c;
    color: white;
    border-radius: 6px 6px 0 0;
    
    h3 {
      margin: 0;
      font-size: 14px;
    }
    
    .debug-controls {
      display: flex;
      gap: 5px;
    }
  }
  
  .debug-content {
    max-height: 60vh;
    overflow-y: auto;
    
    .error-list, .network-info, .lifecycle-info, .store-info, .system-info {
      padding: 10px;
    }
    
    .error-item, .api-error, .lifecycle-item {
      margin-bottom: 10px;
      padding: 8px;
      border-radius: 4px;
      border-left: 4px solid #ccc;
      
      &.error-error {
        border-left-color: #e74c3c;
        background: #fdf2f2;
      }
      
      &.error-warning {
        border-left-color: #f39c12;
        background: #fef9e7;
      }
      
      &.error-info {
        border-left-color: #3498db;
        background: #f4f8fb;
      }
    }
    
    .error-header, .api-header, .lifecycle-header {
      display: flex;
      gap: 10px;
      margin-bottom: 5px;
      font-weight: bold;
      
      .error-level, .api-method {
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
        color: white;
        
        &.ERROR { background: #e74c3c; }
        &.WARNING { background: #f39c12; }
        &.INFO { background: #3498db; }
      }
      
      .error-time, .lifecycle-time {
        color: #7f8c8d;
        font-size: 10px;
      }
      
      .api-status {
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
        color: white;
        background: #e74c3c;
      }
    }
    
    .error-message {
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .error-data, .api-response, .lifecycle-data {
      background: #f8f9fa;
      padding: 5px;
      border-radius: 3px;
      overflow-x: auto;
      
      pre {
        margin: 0;
        font-size: 10px;
        white-space: pre-wrap;
      }
    }
    
    .no-errors {
      text-align: center;
      color: #27ae60;
      padding: 20px;
      font-weight: bold;
    }
    
    h4 {
      margin: 15px 0 5px 0;
      color: #2c3e50;
      border-bottom: 1px solid #ecf0f1;
      padding-bottom: 5px;
    }
    
    pre {
      background: #f8f9fa;
      padding: 10px;
      border-radius: 4px;
      overflow-x: auto;
      font-size: 10px;
      line-height: 1.4;
    }
  }
}

.debug-toggle {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #e74c3c;
  color: white;
  padding: 10px 15px;
  border-radius: 20px;
  cursor: pointer;
  font-weight: bold;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  z-index: 9998;
  transition: all 0.3s ease;
  
  &:hover {
    background: #c0392b;
    transform: scale(1.05);
  }
}
</style>