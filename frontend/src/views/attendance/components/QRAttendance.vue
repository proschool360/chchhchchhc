<template>
  <div class="qr-attendance">
    <div class="qr-section">
      <el-row :gutter="24">
        <el-col :span="12">
          <el-card title="QR Code Scanner">
            <div class="qr-scanner-container">
              <div v-if="!isScanning" class="qr-placeholder">
                <el-icon size="64"><QrCode /></el-icon>
                <p>Click "Start Scanner" to begin QR code scanning</p>
                <el-button type="primary" @click="startScanner">
                  <el-icon><VideoCamera /></el-icon>
                  Start Scanner
                </el-button>
              </div>
              <div v-else class="qr-scanner">
                <video ref="videoElement" autoplay playsinline></video>
                <canvas ref="canvasElement" style="display: none;"></canvas>
                <div class="scanner-overlay">
                  <div class="scanner-frame">
                    <div class="corner top-left"></div>
                    <div class="corner top-right"></div>
                    <div class="corner bottom-left"></div>
                    <div class="corner bottom-right"></div>
                  </div>
                  <p class="scanner-text">Position QR code within the frame</p>
                </div>
                <div class="scanner-controls">
                  <el-button @click="stopScanner" type="danger">
                    <el-icon><VideoCameraFilled /></el-icon>
                    Stop Scanner
                  </el-button>
                </div>
              </div>
            </div>
            
            <!-- Manual QR Code Entry -->
            <div class="manual-entry">
              <el-divider>Or Enter QR Code Manually</el-divider>
              <el-input
                v-model="manualQRCode"
                placeholder="Enter employee QR code"
                @keyup.enter="processQRCode(manualQRCode)"
              >
                <template #append>
                  <el-button @click="processQRCode(manualQRCode)" :loading="processing">
                    Check In/Out
                  </el-button>
                </template>
              </el-input>
            </div>
          </el-card>
        </el-col>
        
        <el-col :span="12">
          <el-card title="Recent QR Check-ins">
            <div class="recent-checkins">
              <div v-if="recentCheckins.length === 0" class="no-data">
                <el-icon size="48"><DocumentRemove /></el-icon>
                <p>No recent check-ins</p>
              </div>
              <div v-else>
                <div 
                  v-for="checkin in recentCheckins" 
                  :key="checkin.id" 
                  class="checkin-item"
                >
                  <div class="checkin-avatar">
                    <el-avatar :size="40">
                      {{ getInitials(checkin.employee_name) }}
                    </el-avatar>
                  </div>
                  <div class="checkin-info">
                    <h4>{{ checkin.employee_name }}</h4>
                    <p class="checkin-time">{{ formatDateTime(checkin.clock_in || checkin.clock_out) }}</p>
                    <p class="checkin-type">
                      <el-tag :type="checkin.clock_out ? 'warning' : 'success'" size="small">
                        {{ checkin.clock_out ? 'Clock Out' : 'Clock In' }}
                      </el-tag>
                      <el-tag v-if="checkin.late_minutes > 0" type="danger" size="small">
                        Late: {{ checkin.late_minutes }}m
                      </el-tag>
                    </p>
                  </div>
                  <div class="checkin-status">
                    <el-icon v-if="checkin.clock_out" color="#f56c6c"><Clock /></el-icon>
                    <el-icon v-else color="#67c23a"><Check /></el-icon>
                  </div>
                </div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
    
    <!-- QR Code Generation Section -->
    <div class="qr-generation-section">
      <el-card title="Generate Employee QR Codes">
        <el-row :gutter="16">
          <el-col :span="8">
            <el-select 
              v-model="selectedEmployee" 
              placeholder="Select Employee"
              filterable
              style="width: 100%"
            >
              <el-option
                v-for="employee in employees"
                :key="employee.id"
                :label="`${employee.first_name} ${employee.last_name} (${employee.emp_code})`"
                :value="employee.id"
              />
            </el-select>
          </el-col>
          <el-col :span="4">
            <el-button type="primary" @click="generateQRCode" :loading="generating">
              Generate QR
            </el-button>
          </el-col>
          <el-col :span="4">
            <el-button @click="generateAllQRCodes" :loading="generatingAll">
              Generate All
            </el-button>
          </el-col>
          <el-col :span="4">
            <el-button @click="downloadQRCodes" :disabled="!hasGeneratedQRs">
              Download All
            </el-button>
          </el-col>
          <el-col :span="4">
            <el-button @click="printQRCodes" :disabled="!hasGeneratedQRs">
              Print QRs
            </el-button>
          </el-col>
        </el-row>
        
        <!-- Generated QR Codes Display -->
        <div v-if="generatedQRs.length > 0" class="qr-codes-grid">
          <div v-for="qr in generatedQRs" :key="qr.employee_id" class="qr-code-item">
            <div class="qr-code-image">
              <img :src="qr.qr_code_url" :alt="`QR Code for ${qr.employee_name}`" />
            </div>
            <div class="qr-code-info">
              <h4>{{ qr.employee_name }}</h4>
              <p>{{ qr.emp_code }}</p>
              <el-button size="small" @click="downloadSingleQR(qr)">
                <el-icon><Download /></el-icon>
                Download
              </el-button>
            </div>
          </div>
        </div>
      </el-card>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  QrCode, 
  VideoCamera, 
  VideoCameraFilled, 
  DocumentRemove, 
  Clock, 
  Check, 
  Download 
} from '@element-plus/icons-vue'
import attendanceAPI from '@/api/attendance'
import employeeAPI from '@/api/employees'

export default {
  name: 'QRAttendance',
  components: {
    QrCode,
    VideoCamera,
    VideoCameraFilled,
    DocumentRemove,
    Clock,
    Check,
    Download
  },
  emits: ['attendance-recorded'],
  setup(props, { emit }) {
    const videoElement = ref(null)
    const canvasElement = ref(null)
    const isScanning = ref(false)
    const processing = ref(false)
    const generating = ref(false)
    const generatingAll = ref(false)
    const manualQRCode = ref('')
    const selectedEmployee = ref('')
    const recentCheckins = ref([])
    const employees = ref([])
    const generatedQRs = ref([])
    const hasGeneratedQRs = ref(false)
    
    let stream = null
    let scanInterval = null
    
    const startScanner = async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({ 
          video: { 
            facingMode: 'environment',
            width: { ideal: 640 },
            height: { ideal: 480 }
          } 
        })
        
        videoElement.value.srcObject = stream
        isScanning.value = true
        
        // Start QR code detection
        scanInterval = setInterval(scanForQRCode, 500)
        
      } catch (error) {
        ElMessage.error('Failed to access camera. Please check permissions.')
        console.error('Camera access error:', error)
      }
    }
    
    const stopScanner = () => {
      if (stream) {
        stream.getTracks().forEach(track => track.stop())
        stream = null
      }
      
      if (scanInterval) {
        clearInterval(scanInterval)
        scanInterval = null
      }
      
      isScanning.value = false
    }
    
    const scanForQRCode = () => {
      if (!videoElement.value || !canvasElement.value) return
      
      const video = videoElement.value
      const canvas = canvasElement.value
      const context = canvas.getContext('2d')
      
      canvas.width = video.videoWidth
      canvas.height = video.videoHeight
      
      context.drawImage(video, 0, 0, canvas.width, canvas.height)
      
      // Here you would integrate with a QR code library like jsQR
      // For now, we'll simulate QR detection
      // const imageData = context.getImageData(0, 0, canvas.width, canvas.height)
      // const code = jsQR(imageData.data, imageData.width, imageData.height)
      
      // if (code) {
      //   processQRCode(code.data)
      // }
    }
    
    const processQRCode = async (qrCode) => {
      if (!qrCode || !qrCode.trim()) {
        ElMessage.warning('Please provide a valid QR code')
        return
      }
      
      try {
        processing.value = true
        
        const response = await attendanceAPI.qrCheckin({ qr_code: qrCode.trim() })
        
        if (response.success) {
          ElMessage.success(response.message || 'Attendance recorded successfully!')
          manualQRCode.value = ''
          loadRecentCheckins()
          emit('attendance-recorded')
          
          // Stop scanner after successful scan
          if (isScanning.value) {
            stopScanner()
          }
        }
      } catch (error) {
        ElMessage.error(error.response?.data?.message || 'Failed to process QR code')
      } finally {
        processing.value = false
      }
    }
    
    const loadRecentCheckins = async () => {
      try {
        const response = await attendanceAPI.getRecentQRCheckins()
        if (response.success) {
          recentCheckins.value = response.data
        }
      } catch (error) {
        console.error('Failed to load recent check-ins:', error)
      }
    }
    
    const loadEmployees = async () => {
      try {
        const response = await employeeAPI.getAll()
        if (response.success) {
          employees.value = response.data
        }
      } catch (error) {
        console.error('Failed to load employees:', error)
      }
    }
    
    const generateQRCode = async () => {
      if (!selectedEmployee.value) {
        ElMessage.warning('Please select an employee')
        return
      }
      
      try {
        generating.value = true
        
        const response = await attendanceAPI.generateQRCode(selectedEmployee.value)
        if (response.success) {
          const existingIndex = generatedQRs.value.findIndex(
            qr => qr.employee_id === selectedEmployee.value
          )
          
          if (existingIndex >= 0) {
            generatedQRs.value[existingIndex] = response.data
          } else {
            generatedQRs.value.push(response.data)
          }
          
          hasGeneratedQRs.value = true
          ElMessage.success('QR code generated successfully')
        }
      } catch (error) {
        ElMessage.error('Failed to generate QR code')
      } finally {
        generating.value = false
      }
    }
    
    const generateAllQRCodes = async () => {
      try {
        generatingAll.value = true
        
        const response = await attendanceAPI.generateAllQRCodes()
        if (response.success) {
          generatedQRs.value = response.data
          hasGeneratedQRs.value = true
          ElMessage.success(`Generated ${response.data.length} QR codes`)
        }
      } catch (error) {
        ElMessage.error('Failed to generate QR codes')
      } finally {
        generatingAll.value = false
      }
    }
    
    const downloadQRCodes = async () => {
      try {
        const response = await attendanceAPI.downloadQRCodes()
        if (response.success) {
          window.open(response.data.download_url, '_blank')
          ElMessage.success('Download started')
        }
      } catch (error) {
        ElMessage.error('Failed to download QR codes')
      }
    }
    
    const downloadSingleQR = (qr) => {
      const link = document.createElement('a')
      link.href = qr.qr_code_url
      link.download = `qr-${qr.emp_code}.png`
      link.click()
    }
    
    const printQRCodes = () => {
      const printWindow = window.open('', '_blank')
      let printContent = '<html><head><title>Employee QR Codes</title></head><body>'
      
      generatedQRs.value.forEach(qr => {
        printContent += `
          <div style="page-break-inside: avoid; margin: 20px; text-align: center;">
            <h3>${qr.employee_name}</h3>
            <p>${qr.emp_code}</p>
            <img src="${qr.qr_code_url}" style="width: 200px; height: 200px;" />
          </div>
        `
      })
      
      printContent += '</body></html>'
      printWindow.document.write(printContent)
      printWindow.document.close()
      printWindow.print()
    }
    
    const getInitials = (name) => {
      return name.split(' ').map(n => n[0]).join('').toUpperCase()
    }
    
    const formatDateTime = (datetime) => {
      if (!datetime) return '--'
      return new Date(datetime).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      })
    }
    
    onMounted(() => {
      loadRecentCheckins()
      loadEmployees()
    })
    
    onUnmounted(() => {
      stopScanner()
    })
    
    return {
      videoElement,
      canvasElement,
      isScanning,
      processing,
      generating,
      generatingAll,
      manualQRCode,
      selectedEmployee,
      recentCheckins,
      employees,
      generatedQRs,
      hasGeneratedQRs,
      startScanner,
      stopScanner,
      processQRCode,
      generateQRCode,
      generateAllQRCodes,
      downloadQRCodes,
      downloadSingleQR,
      printQRCodes,
      getInitials,
      formatDateTime
    }
  }
}
</script>

<style scoped lang="scss">
.qr-attendance {
  .qr-section {
    margin-bottom: 24px;
  }
}

.qr-scanner-container {
  height: 400px;
  border: 2px dashed #dcdfe6;
  border-radius: 8px;
  position: relative;
  overflow: hidden;
  
  .qr-placeholder {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #909399;
    
    .el-icon {
      margin-bottom: 16px;
      color: #c0c4cc;
    }
    
    p {
      margin: 16px 0;
      font-size: 14px;
    }
  }
  
  .qr-scanner {
    position: relative;
    width: 100%;
    height: 100%;
    
    video {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .scanner-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: rgba(0, 0, 0, 0.3);
      
      .scanner-frame {
        position: relative;
        width: 250px;
        height: 250px;
        
        .corner {
          position: absolute;
          width: 30px;
          height: 30px;
          border: 3px solid #409eff;
          
          &.top-left {
            top: 0;
            left: 0;
            border-right: none;
            border-bottom: none;
          }
          
          &.top-right {
            top: 0;
            right: 0;
            border-left: none;
            border-bottom: none;
          }
          
          &.bottom-left {
            bottom: 0;
            left: 0;
            border-right: none;
            border-top: none;
          }
          
          &.bottom-right {
            bottom: 0;
            right: 0;
            border-left: none;
            border-top: none;
          }
        }
      }
      
      .scanner-text {
        color: white;
        margin-top: 20px;
        font-size: 14px;
        text-align: center;
      }
    }
    
    .scanner-controls {
      position: absolute;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
    }
  }
}

.manual-entry {
  margin-top: 20px;
}

.recent-checkins {
  max-height: 400px;
  overflow-y: auto;
  
  .no-data {
    text-align: center;
    padding: 40px;
    color: #909399;
    
    .el-icon {
      margin-bottom: 16px;
      color: #c0c4cc;
    }
  }
  
  .checkin-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
    
    &:last-child {
      border-bottom: none;
    }
    
    .checkin-avatar {
      margin-right: 12px;
    }
    
    .checkin-info {
      flex: 1;
      
      h4 {
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 600;
        color: #303133;
      }
      
      .checkin-time {
        margin: 0 0 4px 0;
        font-size: 12px;
        color: #909399;
      }
      
      .checkin-type {
        margin: 0;
        
        .el-tag {
          margin-right: 4px;
        }
      }
    }
    
    .checkin-status {
      font-size: 20px;
    }
  }
}

.qr-generation-section {
  .qr-codes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
    
    .qr-code-item {
      text-align: center;
      padding: 16px;
      border: 1px solid #ebeef5;
      border-radius: 8px;
      
      .qr-code-image {
        margin-bottom: 12px;
        
        img {
          width: 150px;
          height: 150px;
          border: 1px solid #ebeef5;
        }
      }
      
      .qr-code-info {
        h4 {
          margin: 0 0 4px 0;
          font-size: 14px;
          color: #303133;
        }
        
        p {
          margin: 0 0 12px 0;
          font-size: 12px;
          color: #909399;
        }
      }
    }
  }
}
</style>