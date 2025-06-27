<template>
  <div class="rfid-attendance">
    <div class="rfid-scanner">
      <el-card>
        <template #header>
          <div class="card-header">
            <h3>RFID Attendance Scanner</h3>
            <p>Scan RFID cards for quick attendance tracking</p>
          </div>
        </template>
        
        <div class="scanner-section">
          <div class="scanner-status" :class="scannerStatus">
            <div class="status-icon">
              <i v-if="scannerStatus === 'ready'" class="fas fa-wifi"></i>
              <i v-else-if="scannerStatus === 'scanning'" class="fas fa-spinner fa-spin"></i>
              <i v-else-if="scannerStatus === 'success'" class="fas fa-check-circle"></i>
              <i v-else class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="status-text">
              <h4>{{ statusText }}</h4>
              <p>{{ statusDescription }}</p>
            </div>
          </div>
          
          <div class="scanner-input">
            <el-input
              v-model="rfidInput"
              placeholder="Scan RFID card or enter card ID"
              size="large"
              @keyup.enter="processRFID"
              :disabled="scanning"
              ref="rfidInputRef"
            >
              <template #prepend>
                <i class="fas fa-id-card"></i>
              </template>
              <template #append>
                <el-button @click="processRFID" :loading="scanning" type="primary">
                  Process
                </el-button>
              </template>
            </el-input>
          </div>
          
          <div class="scanner-actions">
            <el-button @click="toggleScanner" :type="scannerActive ? 'danger' : 'success'">
              <i :class="scannerActive ? 'fas fa-stop' : 'fas fa-play'"></i>
              {{ scannerActive ? 'Stop Scanner' : 'Start Scanner' }}
            </el-button>
            <el-button @click="clearInput">
              <i class="fas fa-eraser"></i>
              Clear
            </el-button>
          </div>
        </div>
        
        <!-- Recent Scans -->
        <div class="recent-scans" v-if="recentScans.length > 0">
          <h4>Recent Scans</h4>
          <div class="scan-list">
            <div 
              v-for="scan in recentScans" 
              :key="scan.id" 
              class="scan-item"
              :class="scan.status"
            >
              <div class="scan-info">
                <div class="employee-name">{{ scan.employeeName }}</div>
                <div class="scan-time">{{ formatTime(scan.timestamp) }}</div>
              </div>
              <div class="scan-status">
                <el-tag :type="getTagType(scan.status)">{{ scan.action }}</el-tag>
              </div>
            </div>
          </div>
        </div>
      </el-card>
    </div>
    
    <!-- RFID Settings -->
    <div class="rfid-settings">
      <el-card>
        <template #header>
          <h4>Scanner Settings</h4>
        </template>
        
        <el-form label-width="120px">
          <el-form-item label="Auto Clear">
            <el-switch v-model="settings.autoClear" />
            <span class="setting-description">Automatically clear input after scan</span>
          </el-form-item>
          
          <el-form-item label="Sound Alert">
            <el-switch v-model="settings.soundAlert" />
            <span class="setting-description">Play sound on successful scan</span>
          </el-form-item>
          
          <el-form-item label="Scan Timeout">
            <el-input-number 
              v-model="settings.scanTimeout" 
              :min="1" 
              :max="30" 
              :step="1"
            />
            <span class="setting-description">Seconds to wait for scan</span>
          </el-form-item>
        </el-form>
      </el-card>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, onUnmounted, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import attendanceAPI from '@/api/attendance'

export default {
  name: 'RFIDAttendance',
  setup() {
    const rfidInputRef = ref()
    const rfidInput = ref('')
    const scanning = ref(false)
    const scannerActive = ref(false)
    const scannerStatus = ref('ready')
    const recentScans = ref([])
    
    const settings = reactive({
      autoClear: true,
      soundAlert: true,
      scanTimeout: 5
    })
    
    const statusText = ref('Scanner Ready')
    const statusDescription = ref('Ready to scan RFID cards')
    
    let scanTimeout = null
    
    const updateStatus = (status, text, description) => {
      scannerStatus.value = status
      statusText.value = text
      statusDescription.value = description
    }
    
    const processRFID = async () => {
      if (!rfidInput.value.trim()) {
        ElMessage.warning('Please scan or enter RFID card ID')
        return
      }
      
      scanning.value = true
      updateStatus('scanning', 'Processing...', 'Validating RFID card')
      
      try {
        const response = await attendanceAPI.processRFIDAttendance({
          rfid_card_id: rfidInput.value.trim()
        })
        
        const { employee, action, timestamp } = response.data
        
        // Add to recent scans
        recentScans.value.unshift({
          id: Date.now(),
          employeeName: employee.name,
          action: action,
          status: 'success',
          timestamp: new Date(timestamp)
        })
        
        // Keep only last 10 scans
        if (recentScans.value.length > 10) {
          recentScans.value = recentScans.value.slice(0, 10)
        }
        
        updateStatus('success', 'Scan Successful', `${employee.name} - ${action}`)
        
        if (settings.soundAlert) {
          playSuccessSound()
        }
        
        ElMessage.success(`${action} recorded for ${employee.name}`)
        
        if (settings.autoClear) {
          setTimeout(() => {
            rfidInput.value = ''
            updateStatus('ready', 'Scanner Ready', 'Ready to scan RFID cards')
          }, 2000)
        }
        
      } catch (error) {
        console.error('RFID processing error:', error)
        
        recentScans.value.unshift({
          id: Date.now(),
          employeeName: 'Unknown',
          action: 'Error',
          status: 'error',
          timestamp: new Date()
        })
        
        updateStatus('error', 'Scan Failed', error.response?.data?.message || 'Invalid RFID card')
        ElMessage.error('Failed to process RFID card')
      } finally {
        scanning.value = false
      }
    }
    
    const toggleScanner = () => {
      scannerActive.value = !scannerActive.value
      
      if (scannerActive.value) {
        updateStatus('ready', 'Scanner Active', 'Waiting for RFID scan...')
        focusInput()
      } else {
        updateStatus('ready', 'Scanner Stopped', 'Click start to begin scanning')
      }
    }
    
    const clearInput = () => {
      rfidInput.value = ''
      updateStatus('ready', 'Scanner Ready', 'Ready to scan RFID cards')
      focusInput()
    }
    
    const focusInput = async () => {
      await nextTick()
      rfidInputRef.value?.focus()
    }
    
    const playSuccessSound = () => {
      // Create a simple beep sound
      const audioContext = new (window.AudioContext || window.webkitAudioContext)()
      const oscillator = audioContext.createOscillator()
      const gainNode = audioContext.createGain()
      
      oscillator.connect(gainNode)
      gainNode.connect(audioContext.destination)
      
      oscillator.frequency.value = 800
      oscillator.type = 'sine'
      
      gainNode.gain.setValueAtTime(0.3, audioContext.currentTime)
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3)
      
      oscillator.start(audioContext.currentTime)
      oscillator.stop(audioContext.currentTime + 0.3)
    }
    
    const formatTime = (date) => {
      return new Intl.DateTimeFormat('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      }).format(date)
    }
    
    const getTagType = (status) => {
      switch (status) {
        case 'success': return 'success'
        case 'error': return 'danger'
        default: return 'info'
      }
    }
    
    onMounted(() => {
      focusInput()
    })
    
    onUnmounted(() => {
      if (scanTimeout) {
        clearTimeout(scanTimeout)
      }
    })
    
    return {
      rfidInputRef,
      rfidInput,
      scanning,
      scannerActive,
      scannerStatus,
      statusText,
      statusDescription,
      recentScans,
      settings,
      processRFID,
      toggleScanner,
      clearInput,
      formatTime,
      getTagType
    }
  }
}
</script>

<style scoped>
.rfid-attendance {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
  padding: 20px;
}

.card-header {
  text-align: center;
}

.card-header h3 {
  margin: 0 0 8px 0;
  color: #2c3e50;
  font-size: 20px;
  font-weight: 600;
}

.card-header p {
  margin: 0;
  color: #7f8c8d;
  font-size: 14px;
}

.scanner-section {
  text-align: center;
}

.scanner-status {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 30px;
  margin-bottom: 30px;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.scanner-status.ready {
  background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
  border: 2px solid #27ae60;
}

.scanner-status.scanning {
  background: linear-gradient(135deg, #fff3cd, #fef9e7);
  border: 2px solid #f39c12;
}

.scanner-status.success {
  background: linear-gradient(135deg, #d4edda, #e8f5e8);
  border: 2px solid #27ae60;
}

.scanner-status.error {
  background: linear-gradient(135deg, #f8d7da, #fdeaea);
  border: 2px solid #e74c3c;
}

.status-icon {
  font-size: 48px;
  margin-right: 20px;
}

.scanner-status.ready .status-icon { color: #27ae60; }
.scanner-status.scanning .status-icon { color: #f39c12; }
.scanner-status.success .status-icon { color: #27ae60; }
.scanner-status.error .status-icon { color: #e74c3c; }

.status-text h4 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
}

.status-text p {
  margin: 0;
  font-size: 16px;
  opacity: 0.8;
}

.scanner-input {
  margin-bottom: 20px;
}

.scanner-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.recent-scans {
  margin-top: 30px;
  text-align: left;
}

.recent-scans h4 {
  margin: 0 0 15px 0;
  color: #2c3e50;
  font-size: 16px;
  font-weight: 600;
}

.scan-list {
  max-height: 300px;
  overflow-y: auto;
}

.scan-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  margin-bottom: 8px;
  border-radius: 8px;
  border-left: 4px solid #bdc3c7;
}

.scan-item.success {
  background-color: #f8f9fa;
  border-left-color: #27ae60;
}

.scan-item.error {
  background-color: #fdf2f2;
  border-left-color: #e74c3c;
}

.employee-name {
  font-weight: 600;
  color: #2c3e50;
}

.scan-time {
  font-size: 12px;
  color: #7f8c8d;
}

.setting-description {
  margin-left: 10px;
  font-size: 12px;
  color: #7f8c8d;
}

:deep(.el-input-group__prepend) {
  background-color: #f8f9fa;
}

:deep(.el-card__header) {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

@media (max-width: 768px) {
  .rfid-attendance {
    grid-template-columns: 1fr;
  }
}
</style>