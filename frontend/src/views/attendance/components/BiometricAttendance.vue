<template>
  <div class="biometric-attendance">
    <div class="biometric-scanner">
      <el-card>
        <template #header>
          <div class="card-header">
            <h3>Biometric Attendance Scanner</h3>
            <p>Use fingerprint or face recognition for attendance</p>
          </div>
        </template>
        
        <div class="scanner-tabs">
          <el-tabs v-model="activeScanner" @tab-change="handleScannerChange">
            <el-tab-pane label="Fingerprint" name="fingerprint">
              <div class="fingerprint-scanner">
                <div class="scanner-display" :class="fingerprintStatus">
                  <div class="scanner-animation">
                    <div class="fingerprint-icon">
                      <i class="fas fa-fingerprint"></i>
                    </div>
                    <div class="scan-rings" v-if="fingerprintStatus === 'scanning'">
                      <div class="ring ring-1"></div>
                      <div class="ring ring-2"></div>
                      <div class="ring ring-3"></div>
                    </div>
                  </div>
                  <div class="scanner-text">
                    <h4>{{ fingerprintStatusText }}</h4>
                    <p>{{ fingerprintDescription }}</p>
                  </div>
                </div>
                
                <div class="scanner-controls">
                  <el-button 
                    type="primary" 
                    size="large" 
                    @click="startFingerprintScan"
                    :loading="fingerprintScanning"
                    :disabled="fingerprintStatus === 'scanning'"
                  >
                    <i class="fas fa-fingerprint"></i>
                    {{ fingerprintScanning ? 'Scanning...' : 'Start Fingerprint Scan' }}
                  </el-button>
                </div>
              </div>
            </el-tab-pane>
            
            <el-tab-pane label="Face Recognition" name="face">
              <div class="face-scanner">
                <div class="camera-container">
                  <video 
                    ref="videoRef" 
                    :class="{ active: faceScanning }"
                    autoplay 
                    muted
                  ></video>
                  <canvas ref="canvasRef" style="display: none;"></canvas>
                  
                  <div class="face-overlay" v-if="faceScanning">
                    <div class="face-frame">
                      <div class="corner top-left"></div>
                      <div class="corner top-right"></div>
                      <div class="corner bottom-left"></div>
                      <div class="corner bottom-right"></div>
                    </div>
                    <div class="scan-progress">
                      <el-progress 
                        :percentage="scanProgress" 
                        :show-text="false" 
                        :stroke-width="4"
                        color="#27ae60"
                      />
                    </div>
                  </div>
                  
                  <div class="camera-status" v-if="!faceScanning">
                    <i class="fas fa-camera"></i>
                    <p>{{ cameraStatusText }}</p>
                  </div>
                </div>
                
                <div class="scanner-controls">
                  <el-button 
                    type="primary" 
                    size="large" 
                    @click="toggleFaceScan"
                    :loading="initializingCamera"
                  >
                    <i :class="faceScanning ? 'fas fa-stop' : 'fas fa-camera'"></i>
                    {{ faceScanning ? 'Stop Scan' : 'Start Face Scan' }}
                  </el-button>
                  <el-button 
                    v-if="faceScanning" 
                    @click="capturePhoto"
                    :disabled="scanProgress < 100"
                  >
                    <i class="fas fa-camera-retro"></i>
                    Capture
                  </el-button>
                </div>
              </div>
            </el-tab-pane>
          </el-tabs>
        </div>
        
        <!-- Recent Scans -->
        <div class="recent-scans" v-if="recentScans.length > 0">
          <h4>Recent Biometric Scans</h4>
          <div class="scan-list">
            <div 
              v-for="scan in recentScans" 
              :key="scan.id" 
              class="scan-item"
              :class="scan.status"
            >
              <div class="scan-info">
                <div class="scan-method">
                  <i :class="scan.method === 'fingerprint' ? 'fas fa-fingerprint' : 'fas fa-camera'"></i>
                  {{ scan.method === 'fingerprint' ? 'Fingerprint' : 'Face Recognition' }}
                </div>
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
    
    <!-- Biometric Settings -->
    <div class="biometric-settings">
      <el-card>
        <template #header>
          <h4>Scanner Settings</h4>
        </template>
        
        <el-form label-width="140px">
          <el-form-item label="Scanner Type">
            <el-select v-model="settings.preferredScanner" style="width: 100%">
              <el-option label="Fingerprint" value="fingerprint" />
              <el-option label="Face Recognition" value="face" />
              <el-option label="Both" value="both" />
            </el-select>
          </el-form-item>
          
          <el-form-item label="Scan Timeout">
            <el-input-number 
              v-model="settings.scanTimeout" 
              :min="5" 
              :max="60" 
              :step="5"
            />
            <span class="setting-description">Seconds</span>
          </el-form-item>
          
          <el-form-item label="Quality Threshold">
            <el-slider 
              v-model="settings.qualityThreshold" 
              :min="50" 
              :max="100" 
              :step="5"
              show-stops
            />
          </el-form-item>
          
          <el-form-item label="Auto Retry">
            <el-switch v-model="settings.autoRetry" />
            <span class="setting-description">Retry failed scans automatically</span>
          </el-form-item>
          
          <el-form-item label="Sound Feedback">
            <el-switch v-model="settings.soundFeedback" />
            <span class="setting-description">Play sounds for scan results</span>
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
  name: 'BiometricAttendance',
  setup() {
    const videoRef = ref()
    const canvasRef = ref()
    const activeScanner = ref('fingerprint')
    const recentScans = ref([])
    
    // Fingerprint scanner state
    const fingerprintScanning = ref(false)
    const fingerprintStatus = ref('ready')
    const fingerprintStatusText = ref('Ready to Scan')
    const fingerprintDescription = ref('Place your finger on the scanner')
    
    // Face scanner state
    const faceScanning = ref(false)
    const initializingCamera = ref(false)
    const scanProgress = ref(0)
    const cameraStatusText = ref('Click to start camera')
    const mediaStream = ref(null)
    
    const settings = reactive({
      preferredScanner: 'fingerprint',
      scanTimeout: 30,
      qualityThreshold: 80,
      autoRetry: true,
      soundFeedback: true
    })
    
    let scanInterval = null
    let progressInterval = null
    
    const handleScannerChange = (tab) => {
      if (tab === 'face' && !faceScanning.value) {
        cameraStatusText.value = 'Click to start camera'
      }
    }
    
    const startFingerprintScan = async () => {
      fingerprintScanning.value = true
      fingerprintStatus.value = 'scanning'
      fingerprintStatusText.value = 'Scanning...'
      fingerprintDescription.value = 'Keep your finger steady on the scanner'
      
      try {
        // Simulate fingerprint scanning process
        await new Promise(resolve => setTimeout(resolve, 2000))
        
        // Simulate API call for fingerprint verification
        const response = await attendanceAPI.processBiometricAttendance({
          biometric_type: 'fingerprint',
          biometric_data: 'simulated_fingerprint_data'
        })
        
        const { employee, action, timestamp } = response.data
        
        addRecentScan({
          method: 'fingerprint',
          employeeName: employee.name,
          action: action,
          status: 'success',
          timestamp: new Date(timestamp)
        })
        
        fingerprintStatus.value = 'success'
        fingerprintStatusText.value = 'Scan Successful'
        fingerprintDescription.value = `${employee.name} - ${action}`
        
        if (settings.soundFeedback) {
          playSuccessSound()
        }
        
        ElMessage.success(`${action} recorded for ${employee.name}`)
        
        setTimeout(() => {
          resetFingerprintScanner()
        }, 3000)
        
      } catch (error) {
        console.error('Fingerprint scan error:', error)
        
        addRecentScan({
          method: 'fingerprint',
          employeeName: 'Unknown',
          action: 'Error',
          status: 'error',
          timestamp: new Date()
        })
        
        fingerprintStatus.value = 'error'
        fingerprintStatusText.value = 'Scan Failed'
        fingerprintDescription.value = 'Fingerprint not recognized'
        
        ElMessage.error('Fingerprint scan failed')
        
        setTimeout(() => {
          resetFingerprintScanner()
        }, 3000)
      } finally {
        fingerprintScanning.value = false
      }
    }
    
    const resetFingerprintScanner = () => {
      fingerprintStatus.value = 'ready'
      fingerprintStatusText.value = 'Ready to Scan'
      fingerprintDescription.value = 'Place your finger on the scanner'
    }
    
    const toggleFaceScan = async () => {
      if (faceScanning.value) {
        stopFaceScan()
      } else {
        await startFaceScan()
      }
    }
    
    const startFaceScan = async () => {
      try {
        initializingCamera.value = true
        cameraStatusText.value = 'Initializing camera...'
        
        const stream = await navigator.mediaDevices.getUserMedia({ 
          video: { 
            width: 640, 
            height: 480,
            facingMode: 'user'
          } 
        })
        
        mediaStream.value = stream
        videoRef.value.srcObject = stream
        
        await nextTick()
        await videoRef.value.play()
        
        faceScanning.value = true
        scanProgress.value = 0
        
        // Start progress simulation
        progressInterval = setInterval(() => {
          if (scanProgress.value < 100) {
            scanProgress.value += 2
          }
        }, 100)
        
      } catch (error) {
        console.error('Camera access error:', error)
        ElMessage.error('Failed to access camera')
        cameraStatusText.value = 'Camera access denied'
      } finally {
        initializingCamera.value = false
      }
    }
    
    const stopFaceScan = () => {
      if (mediaStream.value) {
        mediaStream.value.getTracks().forEach(track => track.stop())
        mediaStream.value = null
      }
      
      if (progressInterval) {
        clearInterval(progressInterval)
        progressInterval = null
      }
      
      faceScanning.value = false
      scanProgress.value = 0
      cameraStatusText.value = 'Click to start camera'
    }
    
    const capturePhoto = async () => {
      try {
        const canvas = canvasRef.value
        const video = videoRef.value
        const context = canvas.getContext('2d')
        
        canvas.width = video.videoWidth
        canvas.height = video.videoHeight
        context.drawImage(video, 0, 0)
        
        const imageData = canvas.toDataURL('image/jpeg', 0.8)
        
        // Simulate API call for face recognition
        const response = await attendanceAPI.processBiometricAttendance({
          biometric_type: 'face',
          biometric_data: imageData
        })
        
        const { employee, action, timestamp } = response.data
        
        addRecentScan({
          method: 'face',
          employeeName: employee.name,
          action: action,
          status: 'success',
          timestamp: new Date(timestamp)
        })
        
        if (settings.soundFeedback) {
          playSuccessSound()
        }
        
        ElMessage.success(`${action} recorded for ${employee.name}`)
        stopFaceScan()
        
      } catch (error) {
        console.error('Face recognition error:', error)
        
        addRecentScan({
          method: 'face',
          employeeName: 'Unknown',
          action: 'Error',
          status: 'error',
          timestamp: new Date()
        })
        
        ElMessage.error('Face recognition failed')
      }
    }
    
    const addRecentScan = (scan) => {
      recentScans.value.unshift({
        id: Date.now(),
        ...scan
      })
      
      // Keep only last 10 scans
      if (recentScans.value.length > 10) {
        recentScans.value = recentScans.value.slice(0, 10)
      }
    }
    
    const playSuccessSound = () => {
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
      // Set preferred scanner as active
      activeScanner.value = settings.preferredScanner === 'both' ? 'fingerprint' : settings.preferredScanner
    })
    
    onUnmounted(() => {
      stopFaceScan()
      if (scanInterval) {
        clearInterval(scanInterval)
      }
    })
    
    return {
      videoRef,
      canvasRef,
      activeScanner,
      recentScans,
      fingerprintScanning,
      fingerprintStatus,
      fingerprintStatusText,
      fingerprintDescription,
      faceScanning,
      initializingCamera,
      scanProgress,
      cameraStatusText,
      settings,
      handleScannerChange,
      startFingerprintScan,
      toggleFaceScan,
      capturePhoto,
      formatTime,
      getTagType
    }
  }
}
</script>

<style scoped>
.biometric-attendance {
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

/* Fingerprint Scanner Styles */
.fingerprint-scanner {
  text-align: center;
}

.scanner-display {
  position: relative;
  padding: 40px;
  margin-bottom: 30px;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.scanner-display.ready {
  background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
  border: 2px solid #27ae60;
}

.scanner-display.scanning {
  background: linear-gradient(135deg, #fff3cd, #fef9e7);
  border: 2px solid #f39c12;
}

.scanner-display.success {
  background: linear-gradient(135deg, #d4edda, #e8f5e8);
  border: 2px solid #27ae60;
}

.scanner-display.error {
  background: linear-gradient(135deg, #f8d7da, #fdeaea);
  border: 2px solid #e74c3c;
}

.scanner-animation {
  position: relative;
  display: inline-block;
  margin-bottom: 20px;
}

.fingerprint-icon {
  font-size: 80px;
  color: #27ae60;
}

.scan-rings {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.ring {
  position: absolute;
  border: 2px solid #f39c12;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.ring-1 {
  width: 100px;
  height: 100px;
  margin: -50px 0 0 -50px;
  animation-delay: 0s;
}

.ring-2 {
  width: 120px;
  height: 120px;
  margin: -60px 0 0 -60px;
  animation-delay: 0.5s;
}

.ring-3 {
  width: 140px;
  height: 140px;
  margin: -70px 0 0 -70px;
  animation-delay: 1s;
}

@keyframes pulse {
  0% {
    opacity: 1;
    transform: scale(0.8);
  }
  100% {
    opacity: 0;
    transform: scale(1.2);
  }
}

.scanner-text h4 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
  color: #2c3e50;
}

.scanner-text p {
  margin: 0;
  font-size: 16px;
  color: #7f8c8d;
}

/* Face Scanner Styles */
.face-scanner {
  text-align: center;
}

.camera-container {
  position: relative;
  display: inline-block;
  margin-bottom: 20px;
  border-radius: 12px;
  overflow: hidden;
  background: #000;
}

video {
  width: 400px;
  height: 300px;
  object-fit: cover;
  display: block;
}

video.active {
  border: 3px solid #27ae60;
}

.face-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.face-frame {
  position: relative;
  width: 200px;
  height: 200px;
  margin-bottom: 20px;
}

.corner {
  position: absolute;
  width: 30px;
  height: 30px;
  border: 3px solid #27ae60;
}

.corner.top-left {
  top: 0;
  left: 0;
  border-right: none;
  border-bottom: none;
}

.corner.top-right {
  top: 0;
  right: 0;
  border-left: none;
  border-bottom: none;
}

.corner.bottom-left {
  bottom: 0;
  left: 0;
  border-right: none;
  border-top: none;
}

.corner.bottom-right {
  bottom: 0;
  right: 0;
  border-left: none;
  border-top: none;
}

.scan-progress {
  width: 200px;
}

.camera-status {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: #fff;
  text-align: center;
}

.camera-status i {
  font-size: 48px;
  margin-bottom: 10px;
  display: block;
}

.scanner-controls {
  display: flex;
  gap: 12px;
  justify-content: center;
}

/* Recent Scans */
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

.scan-method {
  font-size: 12px;
  color: #7f8c8d;
  margin-bottom: 4px;
}

.scan-method i {
  margin-right: 4px;
}

.employee-name {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 2px;
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

:deep(.el-card__header) {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

:deep(.el-tabs__header) {
  margin-bottom: 20px;
}

@media (max-width: 768px) {
  .biometric-attendance {
    grid-template-columns: 1fr;
  }
  
  video {
    width: 300px;
    height: 225px;
  }
  
  .face-frame {
    width: 150px;
    height: 150px;
  }
  
  .scan-progress {
    width: 150px;
  }
}
</style>