<template>
  <div class="qr-code-element" :style="containerStyle">
    <div v-if="qrCodeData" class="qr-code-container">
      <canvas 
        ref="qrCanvas" 
        :width="canvasSize" 
        :height="canvasSize"
        :style="canvasStyle"
      ></canvas>
    </div>
    <div v-else class="qr-placeholder" :style="placeholderStyle">
      <i class="fas fa-qrcode"></i>
      <span>QR Code</span>
    </div>
  </div>
</template>

<script>
import { computed, ref, onMounted, watch } from 'vue'
import QRCode from 'qrcode'

export default {
  name: 'QRCodeElement',
  props: {
    element: {
      type: Object,
      required: true
    },
    preview: {
      type: Boolean,
      default: false
    },
    employeeData: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    const qrCanvas = ref(null)
    
    const containerStyle = computed(() => ({
      width: '100%',
      height: '100%',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }))
    
    const canvasSize = computed(() => {
      // Use the smaller dimension to ensure QR code fits
      const size = Math.min(props.element.width || 60, props.element.height || 60)
      return Math.max(60, size) // Minimum size of 60px
    })
    
    const canvasStyle = computed(() => ({
      width: '100%',
      height: '100%',
      maxWidth: '100%',
      maxHeight: '100%'
    }))
    
    const placeholderStyle = computed(() => ({
      width: '100%',
      height: '100%',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: '#f8f9fa',
      color: '#6c757d',
      fontSize: '10px',
      textAlign: 'center',
      border: '1px dashed #dee2e6'
    }))
    
    const qrCodeData = computed(() => {
      let data = props.element.data || ''
      
      // Replace placeholders with actual employee data
      if (props.employeeData && Object.keys(props.employeeData).length > 0) {
        data = data.replace(/\{employee_id\}/g, props.employeeData.employee_id || 'EMP001')
        data = data.replace(/\{employee_name\}/g, props.employeeData.full_name || 'John Doe')
        data = data.replace(/\{department\}/g, props.employeeData.department || 'IT Department')
        data = data.replace(/\{position\}/g, props.employeeData.position || 'Software Developer')
      } else {
        // Use sample data for preview
        data = data.replace(/\{employee_id\}/g, 'EMP001')
        data = data.replace(/\{employee_name\}/g, 'John Doe')
        data = data.replace(/\{department\}/g, 'IT Department')
        data = data.replace(/\{position\}/g, 'Software Developer')
      }
      
      return data || 'EMP001' // Default QR code data
    })
    
    const generateQRCode = async () => {
      if (!qrCanvas.value || !qrCodeData.value) return
      
      try {
        const canvas = qrCanvas.value
        const ctx = canvas.getContext('2d')
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height)
        
        // Generate QR code
        await QRCode.toCanvas(canvas, qrCodeData.value, {
          width: canvasSize.value,
          height: canvasSize.value,
          margin: 1,
          color: {
            dark: '#000000',
            light: '#FFFFFF'
          },
          errorCorrectionLevel: 'M'
        })
      } catch (error) {
        console.error('Error generating QR code:', error)
        
        // Draw error placeholder
        const canvas = qrCanvas.value
        const ctx = canvas.getContext('2d')
        ctx.clearRect(0, 0, canvas.width, canvas.height)
        
        // Draw error state
        ctx.fillStyle = '#f8f9fa'
        ctx.fillRect(0, 0, canvas.width, canvas.height)
        
        ctx.fillStyle = '#6c757d'
        ctx.font = '12px Arial'
        ctx.textAlign = 'center'
        ctx.fillText('QR Error', canvas.width / 2, canvas.height / 2)
      }
    }
    
    // Watch for changes in QR code data
    watch([qrCodeData, canvasSize], () => {
      if (qrCanvas.value) {
        generateQRCode()
      }
    })
    
    onMounted(() => {
      if (qrCodeData.value) {
        generateQRCode()
      }
    })
    
    return {
      qrCanvas,
      containerStyle,
      canvasSize,
      canvasStyle,
      placeholderStyle,
      qrCodeData
    }
  }
}
</script>

<style scoped>
.qr-code-element {
  user-select: none;
  pointer-events: none;
}

.qr-code-container {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qr-placeholder {
  user-select: none;
}

.qr-placeholder i {
  font-size: 24px;
  margin-bottom: 4px;
}

.qr-placeholder span {
  font-size: 10px;
  font-weight: 500;
}
</style>