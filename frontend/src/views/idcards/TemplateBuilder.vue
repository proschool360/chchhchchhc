<template>
  <div class="template-builder">
    <!-- Builder Header -->
    <div class="builder-header">
      <div class="header-left">
        <h2 v-if="isEditing">Edit Template: {{ template?.name }}</h2>
        <h2 v-else>Create New Template</h2>
      </div>
      <div class="header-actions">
        <el-button @click="previewTemplate" :disabled="!canPreview">
          <i class="fas fa-eye"></i>
          Preview
        </el-button>
        <el-button type="primary" @click="saveTemplate" :loading="saving">
          <i class="fas fa-save"></i>
          Save Template
        </el-button>
        <el-button @click="$emit('cancel')">
          Cancel
        </el-button>
      </div>
    </div>

    <!-- Builder Content -->
    <div class="builder-content">
      <!-- Left Panel - Properties -->
      <div class="properties-panel">
        <div class="panel-section">
          <h3>Template Settings</h3>
          <el-form :model="templateData" label-width="120px">
            <el-form-item label="Name">
              <el-input v-model="templateData.name" placeholder="Template name" />
            </el-form-item>
            <el-form-item label="Description">
              <el-input
                v-model="templateData.description"
                type="textarea"
                placeholder="Template description"
                :rows="2"
              />
            </el-form-item>
            <el-form-item label="Dimensions">
              <div class="dimension-inputs">
                <el-input-number
                  v-model="templateData.width"
                  :min="200"
                  :max="1000"
                  placeholder="Width"
                />
                <span class="dimension-separator">×</span>
                <el-input-number
                  v-model="templateData.height"
                  :min="100"
                  :max="800"
                  placeholder="Height"
                />
                <span class="dimension-unit">px</span>
              </div>
            </el-form-item>
            <el-form-item label="Orientation">
              <el-radio-group v-model="templateData.orientation">
                <el-radio label="portrait">Portrait</el-radio>
                <el-radio label="landscape">Landscape</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-form>
        </div>

        <div class="panel-section">
          <h3>Background</h3>
          <el-form :model="templateData.background" label-width="120px">
            <el-form-item label="Type">
              <el-radio-group v-model="templateData.background.type">
                <el-radio label="color">Color</el-radio>
                <el-radio label="gradient">Gradient</el-radio>
                <el-radio label="image">Image</el-radio>
              </el-radio-group>
            </el-form-item>
            <el-form-item v-if="templateData.background.type === 'color'" label="Color">
              <el-color-picker v-model="templateData.background.color" />
            </el-form-item>
            <el-form-item v-if="templateData.background.type === 'gradient'" label="Gradient">
              <div class="gradient-controls">
                <el-color-picker v-model="templateData.background.gradientStart" />
                <span>to</span>
                <el-color-picker v-model="templateData.background.gradientEnd" />
                <el-select v-model="templateData.background.gradientDirection">
                  <el-option label="Top to Bottom" value="to bottom" />
                  <el-option label="Left to Right" value="to right" />
                  <el-option label="Diagonal" value="45deg" />
                </el-select>
              </div>
            </el-form-item>
            <el-form-item v-if="templateData.background.type === 'image'" label="Image">
              <el-upload
                class="background-uploader"
                :show-file-list="false"
                :on-success="handleBackgroundUpload"
                :before-upload="beforeBackgroundUpload"
                action="/api/upload/background"
              >
                <img v-if="templateData.background.imageUrl" :src="templateData.background.imageUrl" class="background-image" />
                <i v-else class="el-icon-plus background-uploader-icon"></i>
              </el-upload>
            </el-form-item>
          </el-form>
        </div>

        <div class="panel-section">
          <h3>Elements</h3>
          <div class="element-toolbar">
            <el-button size="small" @click="addElement('text')">
              <i class="fas fa-font"></i>
              Text
            </el-button>
            <el-button size="small" @click="addElement('image')">
              <i class="fas fa-image"></i>
              Image
            </el-button>
            <el-button size="small" @click="addElement('qrcode')">
              <i class="fas fa-qrcode"></i>
              QR Code
            </el-button>
            <el-button size="small" @click="addElement('barcode')">
              <i class="fas fa-barcode"></i>
              Barcode
            </el-button>
            <el-button size="small" @click="addElement('shape')">
              <i class="fas fa-shapes"></i>
              Shape
            </el-button>
          </div>

          <!-- Element Properties -->
          <div v-if="selectedElement" class="element-properties">
            <h4>{{ selectedElement.type.toUpperCase() }} Properties</h4>
            
            <!-- Common Properties -->
            <el-form :model="selectedElement" label-width="80px" size="small">
              <el-form-item label="X">
                <el-input-number v-model="selectedElement.x" :min="0" :max="templateData.width" />
              </el-form-item>
              <el-form-item label="Y">
                <el-input-number v-model="selectedElement.y" :min="0" :max="templateData.height" />
              </el-form-item>
              <el-form-item label="Width">
                <el-input-number v-model="selectedElement.width" :min="10" :max="templateData.width" />
              </el-form-item>
              <el-form-item label="Height">
                <el-input-number v-model="selectedElement.height" :min="10" :max="templateData.height" />
              </el-form-item>
            </el-form>

            <!-- Text Properties -->
            <el-form v-if="selectedElement.type === 'text'" :model="selectedElement" label-width="80px" size="small">
              <el-form-item label="Content">
                <el-select v-model="selectedElement.content" placeholder="Select field">
                  <el-option label="Employee Name" value="{{employee.full_name}}" />
                  <el-option label="Employee ID" value="{{employee.employee_id}}" />
                  <el-option label="Department" value="{{employee.department}}" />
                  <el-option label="Position" value="{{employee.position}}" />
                  <el-option label="Email" value="{{employee.email}}" />
                  <el-option label="Phone" value="{{employee.phone}}" />
                  <el-option label="Join Date" value="{{employee.join_date}}" />
                  <el-option label="Custom Text" value="custom" />
                </el-select>
              </el-form-item>
              <el-form-item v-if="selectedElement.content === 'custom'" label="Text">
                <el-input v-model="selectedElement.customText" />
              </el-form-item>
              <el-form-item label="Font Size">
                <el-input-number v-model="selectedElement.fontSize" :min="8" :max="72" />
              </el-form-item>
              <el-form-item label="Font Weight">
                <el-select v-model="selectedElement.fontWeight">
                  <el-option label="Normal" value="normal" />
                  <el-option label="Bold" value="bold" />
                  <el-option label="Light" value="300" />
                </el-select>
              </el-form-item>
              <el-form-item label="Color">
                <el-color-picker v-model="selectedElement.color" />
              </el-form-item>
              <el-form-item label="Align">
                <el-radio-group v-model="selectedElement.textAlign">
                  <el-radio label="left">Left</el-radio>
                  <el-radio label="center">Center</el-radio>
                  <el-radio label="right">Right</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-form>

            <!-- Image Properties -->
            <el-form v-if="selectedElement.type === 'image'" :model="selectedElement" label-width="80px" size="small">
              <el-form-item label="Source">
                <el-select v-model="selectedElement.source">
                  <el-option label="Employee Photo" value="{{employee.photo}}" />
                  <el-option label="Company Logo" value="{{company.logo}}" />
                  <el-option label="Custom Image" value="custom" />
                </el-select>
              </el-form-item>
              <el-form-item v-if="selectedElement.source === 'custom'" label="Upload">
                <el-upload
                  class="element-uploader"
                  :show-file-list="false"
                  :on-success="(response) => handleElementImageUpload(response, selectedElement)"
                  action="/api/upload/element"
                >
                  <el-button size="small">Upload Image</el-button>
                </el-upload>
              </el-form-item>
              <el-form-item label="Fit">
                <el-select v-model="selectedElement.objectFit">
                  <el-option label="Cover" value="cover" />
                  <el-option label="Contain" value="contain" />
                  <el-option label="Fill" value="fill" />
                </el-select>
              </el-form-item>
              <el-form-item label="Border">
                <el-input-number v-model="selectedElement.borderWidth" :min="0" :max="10" />
              </el-form-item>
              <el-form-item label="Border Color">
                <el-color-picker v-model="selectedElement.borderColor" />
              </el-form-item>
            </el-form>

            <!-- QR Code Properties -->
            <el-form v-if="selectedElement.type === 'qrcode'" :model="selectedElement" label-width="80px" size="small">
              <el-form-item label="Data">
                <el-select v-model="selectedElement.qrData">
                  <el-option label="Employee ID" value="{{employee.employee_id}}" />
                  <el-option label="Employee Code" value="{{employee.code}}" />
                  <el-option label="Custom Data" value="custom" />
                </el-select>
              </el-form-item>
              <el-form-item v-if="selectedElement.qrData === 'custom'" label="Custom">
                <el-input v-model="selectedElement.customQRData" />
              </el-form-item>
              <el-form-item label="Error Level">
                <el-select v-model="selectedElement.errorLevel">
                  <el-option label="Low" value="L" />
                  <el-option label="Medium" value="M" />
                  <el-option label="Quartile" value="Q" />
                  <el-option label="High" value="H" />
                </el-select>
              </el-form-item>
            </el-form>

            <!-- Element Actions -->
            <div class="element-actions">
              <el-button size="small" @click="duplicateElement">
                <i class="fas fa-copy"></i>
                Duplicate
              </el-button>
              <el-button size="small" type="danger" @click="deleteElement">
                <i class="fas fa-trash"></i>
                Delete
              </el-button>
            </div>
          </div>
        </div>
      </div>

      <!-- Center Panel - Canvas -->
      <div class="canvas-panel">
        <div class="canvas-toolbar">
          <el-button-group>
            <el-button size="small" @click="zoomOut" :disabled="zoom <= 0.25">
              <i class="fas fa-search-minus"></i>
            </el-button>
            <el-button size="small">{{ Math.round(zoom * 100) }}%</el-button>
            <el-button size="small" @click="zoomIn" :disabled="zoom >= 2">
              <i class="fas fa-search-plus"></i>
            </el-button>
          </el-button-group>
          <el-button size="small" @click="resetZoom">
            <i class="fas fa-expand-arrows-alt"></i>
            Fit
          </el-button>
        </div>

        <div class="canvas-container" ref="canvasContainer">
          <div
            class="canvas"
            :style="canvasStyle"
            @click="deselectElement"
          >
            <!-- Background -->
            <div class="canvas-background" :style="backgroundStyle"></div>
            
            <!-- Elements -->
            <div
              v-for="element in templateData.elements"
              :key="element.id"
              class="canvas-element"
              :class="{ selected: selectedElement?.id === element.id }"
              :style="getElementStyle(element)"
              @click.stop="selectElement(element)"
              @mousedown="startDrag(element, $event)"
            >
              <!-- Text Element -->
              <div v-if="element.type === 'text'" class="text-element">
                {{ getDisplayText(element) }}
              </div>
              
              <!-- Image Element -->
              <div v-if="element.type === 'image'" class="image-element">
                <img v-if="getImageSrc(element)" :src="getImageSrc(element)" :style="imageElementStyle(element)" />
                <div v-else class="image-placeholder">
                  <i class="fas fa-image"></i>
                  <span>Image</span>
                </div>
              </div>
              
              <!-- QR Code Element -->
              <div v-if="element.type === 'qrcode'" class="qrcode-element">
                <div class="qr-placeholder">
                  <i class="fas fa-qrcode"></i>
                  <span>QR Code</span>
                </div>
              </div>
              
              <!-- Resize Handles -->
              <div v-if="selectedElement?.id === element.id" class="resize-handles">
                <div class="resize-handle nw" @mousedown.stop="startResize(element, 'nw', $event)"></div>
                <div class="resize-handle ne" @mousedown.stop="startResize(element, 'ne', $event)"></div>
                <div class="resize-handle sw" @mousedown.stop="startResize(element, 'sw', $event)"></div>
                <div class="resize-handle se" @mousedown.stop="startResize(element, 'se', $event)"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Panel - Layers -->
      <div class="layers-panel">
        <h3>Layers</h3>
        <div class="layers-list">
          <div
            v-for="(element, index) in templateData.elements"
            :key="element.id"
            class="layer-item"
            :class="{ selected: selectedElement?.id === element.id }"
            @click="selectElement(element)"
          >
            <div class="layer-info">
              <i :class="getElementIcon(element.type)"></i>
              <span>{{ getElementName(element) }}</span>
            </div>
            <div class="layer-actions">
              <el-button size="mini" @click.stop="moveElementUp(index)" :disabled="index === 0">
                <i class="fas fa-arrow-up"></i>
              </el-button>
              <el-button size="mini" @click.stop="moveElementDown(index)" :disabled="index === templateData.elements.length - 1">
                <i class="fas fa-arrow-down"></i>
              </el-button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <el-dialog v-model="showPreview" title="Template Preview" width="800px">
      <div class="preview-container">
        <div class="preview-card" :style="previewStyle">
          <!-- Preview content will be rendered here -->
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import idCardAPI from '@/api/idcard'

// Props
const props = defineProps({
  template: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['save', 'cancel'])

// Reactive data
const saving = ref(false)
const showPreview = ref(false)
const zoom = ref(1)
const selectedElement = ref(null)
const isDragging = ref(false)
const isResizing = ref(false)
const dragStart = ref({ x: 0, y: 0 })
const canvasContainer = ref(null)

// Template data
const templateData = reactive({
  name: '',
  description: '',
  width: 400,
  height: 250,
  orientation: 'landscape',
  background: {
    type: 'color',
    color: '#ffffff',
    gradientStart: '#ffffff',
    gradientEnd: '#f0f0f0',
    gradientDirection: 'to bottom',
    imageUrl: ''
  },
  elements: []
})

// Computed properties
const isEditing = computed(() => !!props.template)
const canPreview = computed(() => templateData.name && templateData.elements.length > 0)

const canvasStyle = computed(() => ({
  width: `${templateData.width * zoom.value}px`,
  height: `${templateData.height * zoom.value}px`,
  transform: `scale(${zoom.value})`,
  transformOrigin: 'top left'
}))

const backgroundStyle = computed(() => {
  const bg = templateData.background
  switch (bg.type) {
    case 'color':
      return { backgroundColor: bg.color }
    case 'gradient':
      return {
        background: `linear-gradient(${bg.gradientDirection}, ${bg.gradientStart}, ${bg.gradientEnd})`
      }
    case 'image':
      return {
        backgroundImage: bg.imageUrl ? `url(${bg.imageUrl})` : 'none',
        backgroundSize: 'cover',
        backgroundPosition: 'center'
      }
    default:
      return { backgroundColor: '#ffffff' }
  }
})

const previewStyle = computed(() => ({
  width: `${templateData.width}px`,
  height: `${templateData.height}px`,
  ...backgroundStyle.value
}))

// Methods
const addElement = (type) => {
  const element = {
    id: Date.now() + Math.random(),
    type,
    x: 50,
    y: 50,
    width: type === 'text' ? 150 : 100,
    height: type === 'text' ? 30 : 100,
    zIndex: templateData.elements.length
  }

  // Type-specific properties
  switch (type) {
    case 'text':
      Object.assign(element, {
        content: '{{employee.full_name}}',
        customText: 'Sample Text',
        fontSize: 16,
        fontWeight: 'normal',
        color: '#000000',
        textAlign: 'left'
      })
      break
    case 'image':
      Object.assign(element, {
        source: '{{employee.photo}}',
        customImageUrl: '',
        objectFit: 'cover',
        borderWidth: 0,
        borderColor: '#000000'
      })
      break
    case 'qrcode':
      Object.assign(element, {
        qrData: '{{employee.employee_id}}',
        customQRData: '',
        errorLevel: 'M'
      })
      break
  }

  templateData.elements.push(element)
  selectedElement.value = element
}

const selectElement = (element) => {
  selectedElement.value = element
}

const deselectElement = () => {
  selectedElement.value = null
}

const deleteElement = () => {
  if (selectedElement.value) {
    const index = templateData.elements.findIndex(el => el.id === selectedElement.value.id)
    if (index > -1) {
      templateData.elements.splice(index, 1)
      selectedElement.value = null
    }
  }
}

const duplicateElement = () => {
  if (selectedElement.value) {
    const newElement = {
      ...selectedElement.value,
      id: Date.now() + Math.random(),
      x: selectedElement.value.x + 10,
      y: selectedElement.value.y + 10
    }
    templateData.elements.push(newElement)
    selectedElement.value = newElement
  }
}

const getElementStyle = (element) => ({
  position: 'absolute',
  left: `${element.x}px`,
  top: `${element.y}px`,
  width: `${element.width}px`,
  height: `${element.height}px`,
  zIndex: element.zIndex,
  fontSize: element.fontSize ? `${element.fontSize}px` : undefined,
  fontWeight: element.fontWeight || undefined,
  color: element.color || undefined,
  textAlign: element.textAlign || undefined,
  border: element.borderWidth ? `${element.borderWidth}px solid ${element.borderColor}` : undefined
})

const getDisplayText = (element) => {
  if (element.content === 'custom') {
    return element.customText || 'Custom Text'
  }
  // Replace template variables with sample data
  return element.content
    .replace('{{employee.full_name}}', 'John Doe')
    .replace('{{employee.employee_id}}', 'EMP001')
    .replace('{{employee.department}}', 'IT Department')
    .replace('{{employee.position}}', 'Software Developer')
    .replace('{{employee.email}}', 'john.doe@company.com')
    .replace('{{employee.phone}}', '+1234567890')
    .replace('{{employee.join_date}}', '2023-01-15')
}

const getImageSrc = (element) => {
  if (element.source === 'custom') {
    return element.customImageUrl
  }
  if (element.source === '{{company.logo}}') {
    return '/images/sample-logo.png'
  }
  if (element.source === '{{employee.photo}}') {
    return '/images/sample-employee.jpg'
  }
  return null
}

const imageElementStyle = (element) => ({
  width: '100%',
  height: '100%',
  objectFit: element.objectFit || 'cover'
})

const getElementIcon = (type) => {
  const icons = {
    text: 'fas fa-font',
    image: 'fas fa-image',
    qrcode: 'fas fa-qrcode',
    barcode: 'fas fa-barcode',
    shape: 'fas fa-shapes'
  }
  return icons[type] || 'fas fa-square'
}

const getElementName = (element) => {
  const names = {
    text: 'Text',
    image: 'Image',
    qrcode: 'QR Code',
    barcode: 'Barcode',
    shape: 'Shape'
  }
  return names[element.type] || 'Element'
}

const moveElementUp = (index) => {
  if (index > 0) {
    const element = templateData.elements.splice(index, 1)[0]
    templateData.elements.splice(index - 1, 0, element)
  }
}

const moveElementDown = (index) => {
  if (index < templateData.elements.length - 1) {
    const element = templateData.elements.splice(index, 1)[0]
    templateData.elements.splice(index + 1, 0, element)
  }
}

const zoomIn = () => {
  zoom.value = Math.min(zoom.value + 0.25, 2)
}

const zoomOut = () => {
  zoom.value = Math.max(zoom.value - 0.25, 0.25)
}

const resetZoom = () => {
  zoom.value = 1
}

const startDrag = (element, event) => {
  if (isResizing.value) return
  
  isDragging.value = true
  selectedElement.value = element
  dragStart.value = {
    x: event.clientX - element.x,
    y: event.clientY - element.y
  }
  
  document.addEventListener('mousemove', handleDrag)
  document.addEventListener('mouseup', stopDrag)
}

const handleDrag = (event) => {
  if (!isDragging.value || !selectedElement.value) return
  
  selectedElement.value.x = Math.max(0, Math.min(
    event.clientX - dragStart.value.x,
    templateData.width - selectedElement.value.width
  ))
  selectedElement.value.y = Math.max(0, Math.min(
    event.clientY - dragStart.value.y,
    templateData.height - selectedElement.value.height
  ))
}

const stopDrag = () => {
  isDragging.value = false
  document.removeEventListener('mousemove', handleDrag)
  document.removeEventListener('mouseup', stopDrag)
}

const startResize = (element, direction, event) => {
  isResizing.value = true
  selectedElement.value = element
  
  const startX = event.clientX
  const startY = event.clientY
  const startWidth = element.width
  const startHeight = element.height
  const startLeft = element.x
  const startTop = element.y
  
  const handleResize = (event) => {
    const deltaX = event.clientX - startX
    const deltaY = event.clientY - startY
    
    switch (direction) {
      case 'se': // Southeast
        element.width = Math.max(10, startWidth + deltaX)
        element.height = Math.max(10, startHeight + deltaY)
        break
      case 'sw': // Southwest
        element.width = Math.max(10, startWidth - deltaX)
        element.height = Math.max(10, startHeight + deltaY)
        element.x = Math.max(0, startLeft + deltaX)
        break
      case 'ne': // Northeast
        element.width = Math.max(10, startWidth + deltaX)
        element.height = Math.max(10, startHeight - deltaY)
        element.y = Math.max(0, startTop + deltaY)
        break
      case 'nw': // Northwest
        element.width = Math.max(10, startWidth - deltaX)
        element.height = Math.max(10, startHeight - deltaY)
        element.x = Math.max(0, startLeft + deltaX)
        element.y = Math.max(0, startTop + deltaY)
        break
    }
  }
  
  const stopResize = () => {
    isResizing.value = false
    document.removeEventListener('mousemove', handleResize)
    document.removeEventListener('mouseup', stopResize)
  }
  
  document.addEventListener('mousemove', handleResize)
  document.addEventListener('mouseup', stopResize)
}

const handleBackgroundUpload = (response) => {
  templateData.background.imageUrl = response.url
}

const beforeBackgroundUpload = (file) => {
  const isImage = file.type.startsWith('image/')
  if (!isImage) {
    ElMessage.error('Please upload an image file')
  }
  return isImage
}

const handleElementImageUpload = (response, element) => {
  element.customImageUrl = response.url
}

const previewTemplate = () => {
  showPreview.value = true
}

const saveTemplate = async () => {
  if (!templateData.name) {
    ElMessage.error('Please enter a template name')
    return
  }
  
  try {
    saving.value = true
    
    const templatePayload = {
      name: templateData.name,
      description: templateData.description,
      width: templateData.width,
      height: templateData.height,
      orientation: templateData.orientation,
      template_data: {
        background: templateData.background,
        elements: templateData.elements
      }
    }
    
    let response
    if (isEditing.value) {
      response = await idCardAPI.updateTemplate(props.template.id, templatePayload)
    } else {
      response = await idCardAPI.createTemplate(templatePayload)
    }
    
    if (response.success) {
      ElMessage.success('Template saved successfully')
      emit('save', response.data)
    }
  } catch (error) {
    ElMessage.error('Failed to save template')
    console.error('Save template error:', error)
  } finally {
    saving.value = false
  }
}

// Initialize template data
const initializeTemplate = () => {
  if (props.template) {
    Object.assign(templateData, {
      name: props.template.name,
      description: props.template.description,
      width: props.template.width,
      height: props.template.height,
      orientation: props.template.orientation,
      background: props.template.template_data?.background || templateData.background,
      elements: props.template.template_data?.elements || []
    })
  }
}

// Lifecycle
onMounted(() => {
  initializeTemplate()
})

onUnmounted(() => {
  document.removeEventListener('mousemove', handleDrag)
  document.removeEventListener('mouseup', stopDrag)
})
</script>

<style scoped>
.template-builder {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f5f5f5;
}

.builder-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: white;
  border-bottom: 1px solid #e0e0e0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-left h2 {
  margin: 0;
  color: #333;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.builder-content {
  flex: 1;
  display: flex;
  overflow: hidden;
}

.properties-panel {
  width: 300px;
  background: white;
  border-right: 1px solid #e0e0e0;
  overflow-y: auto;
}

.panel-section {
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.panel-section h3 {
  margin: 0 0 16px 0;
  color: #333;
  font-size: 14px;
  font-weight: 600;
}

.dimension-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
}

.dimension-separator {
  color: #666;
}

.dimension-unit {
  color: #999;
  font-size: 12px;
}

.gradient-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.background-uploader {
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  width: 100px;
  height: 60px;
}

.background-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.background-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.element-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.element-properties {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
}

.element-properties h4 {
  margin: 0 0 12px 0;
  color: #333;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.element-actions {
  margin-top: 16px;
  display: flex;
  gap: 8px;
}

.canvas-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f8f9fa;
}

.canvas-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: white;
  border-bottom: 1px solid #e0e0e0;
}

.canvas-container {
  flex: 1;
  overflow: auto;
  padding: 40px;
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

.canvas {
  position: relative;
  background: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  border-radius: 4px;
  overflow: hidden;
}

.canvas-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.canvas-element {
  cursor: move;
  border: 2px solid transparent;
  box-sizing: border-box;
}

.canvas-element.selected {
  border-color: #409eff;
}

.canvas-element:hover {
  border-color: #409eff;
  opacity: 0.8;
}

.text-element {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  padding: 4px;
  box-sizing: border-box;
  word-break: break-word;
}

.image-element {
  width: 100%;
  height: 100%;
  position: relative;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  border: 1px dashed #d9d9d9;
  color: #999;
}

.qrcode-element {
  width: 100%;
  height: 100%;
}

.qr-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  border: 1px dashed #d9d9d9;
  color: #999;
}

.resize-handles {
  position: absolute;
  top: -4px;
  left: -4px;
  right: -4px;
  bottom: -4px;
  pointer-events: none;
}

.resize-handle {
  position: absolute;
  width: 8px;
  height: 8px;
  background: #409eff;
  border: 1px solid white;
  border-radius: 50%;
  pointer-events: all;
  cursor: pointer;
}

.resize-handle.nw {
  top: 0;
  left: 0;
  cursor: nw-resize;
}

.resize-handle.ne {
  top: 0;
  right: 0;
  cursor: ne-resize;
}

.resize-handle.sw {
  bottom: 0;
  left: 0;
  cursor: sw-resize;
}

.resize-handle.se {
  bottom: 0;
  right: 0;
  cursor: se-resize;
}

.layers-panel {
  width: 250px;
  background: white;
  border-left: 1px solid #e0e0e0;
  overflow-y: auto;
}

.layers-panel h3 {
  margin: 0;
  padding: 16px;
  color: #333;
  font-size: 14px;
  font-weight: 600;
  border-bottom: 1px solid #f0f0f0;
}

.layers-list {
  padding: 8px;
}

.layer-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  margin-bottom: 4px;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.layer-item:hover {
  background: #f5f5f5;
}

.layer-item.selected {
  background: #e6f7ff;
  border: 1px solid #91d5ff;
}

.layer-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.layer-info i {
  color: #666;
  width: 16px;
}

.layer-actions {
  display: flex;
  gap: 4px;
}

.preview-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  background: #f5f5f5;
}

.preview-card {
  background: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  border-radius: 4px;
}
</style>