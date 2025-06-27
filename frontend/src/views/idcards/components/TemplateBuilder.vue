<template>
  <div class="template-builder">
    <!-- Builder Header -->
    <div class="builder-header">
      <div class="header-left">
        <h2 v-if="!template">Create New Template</h2>
        <h2 v-else>Edit Template: {{ template.name }}</h2>
        <p>Drag and drop elements to design your ID card template</p>
      </div>
      <div class="header-right">
        <el-button @click="previewTemplate">Preview</el-button>
        <el-button @click="resetTemplate" type="warning">Reset</el-button>
        <el-button @click="saveTemplate" type="primary">Save Template</el-button>
        <el-button @click="$emit('cancel')">Cancel</el-button>
      </div>
    </div>

    <!-- Builder Content -->
    <div class="builder-content">
      <!-- Elements Palette -->
      <div class="elements-palette">
        <h3>Elements</h3>
        <div class="element-categories">
          <!-- Basic Elements -->
          <div class="category">
            <h4>Basic Elements</h4>
            <div class="elements-grid">
              <div
                v-for="element in basicElements"
                :key="element.type"
                class="element-item"
                draggable="true"
                @dragstart="handleDragStart($event, element)"
              >
                <i :class="element.icon"></i>
                <span>{{ element.label }}</span>
              </div>
            </div>
          </div>

          <!-- Employee Data -->
          <div class="category">
            <h4>Employee Data</h4>
            <div class="elements-grid">
              <div
                v-for="element in employeeElements"
                :key="element.type"
                class="element-item"
                draggable="true"
                @dragstart="handleDragStart($event, element)"
              >
                <i :class="element.icon"></i>
                <span>{{ element.label }}</span>
              </div>
            </div>
          </div>

          <!-- Company Elements -->
          <div class="category">
            <h4>Company Elements</h4>
            <div class="elements-grid">
              <div
                v-for="element in companyElements"
                :key="element.type"
                class="element-item"
                draggable="true"
                @dragstart="handleDragStart($event, element)"
              >
                <i :class="element.icon"></i>
                <span>{{ element.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Canvas Area -->
      <div class="canvas-area">
        <div class="canvas-header">
          <div class="canvas-controls">
            <label>Template Name:</label>
            <el-input
              v-model="templateData.name"
              placeholder="Enter template name"
              style="width: 200px; margin-right: 16px;"
            />
            <label>Size:</label>
            <el-select v-model="templateData.size" @change="handleSizeChange">
              <el-option label="Standard (350x220)" value="standard" />
              <el-option label="Credit Card (340x214)" value="credit" />
              <el-option label="Custom" value="custom" />
            </el-select>
            <template v-if="templateData.size === 'custom'">
              <el-input
                v-model.number="templateData.width"
                placeholder="Width"
                type="number"
                style="width: 80px; margin: 0 8px;"
              />
              <span>x</span>
              <el-input
                v-model.number="templateData.height"
                placeholder="Height"
                type="number"
                style="width: 80px; margin: 0 8px;"
              />
            </template>
          </div>
          <div class="zoom-controls">
            <el-button-group>
              <el-button @click="zoomOut" :disabled="zoomLevel <= 0.5">
                <i class="fas fa-search-minus"></i>
              </el-button>
              <el-button disabled>{{ Math.round(zoomLevel * 100) }}%</el-button>
              <el-button @click="zoomIn" :disabled="zoomLevel >= 2">
                <i class="fas fa-search-plus"></i>
              </el-button>
            </el-button-group>
          </div>
        </div>

        <!-- Canvas -->
        <div class="canvas-container">
          <div
            class="canvas"
            :style="canvasStyle"
            @drop="handleDrop"
            @dragover="handleDragOver"
            @click="selectElement(null)"
          >
            <!-- Grid Background -->
            <div class="grid-background" v-if="showGrid"></div>
            
            <!-- Template Elements -->
            <div
              v-for="element in templateData.elements"
              :key="element.id"
              class="template-element"
              :class="{ selected: selectedElement?.id === element.id }"
              :style="getElementStyle(element)"
              @click.stop="selectElement(element)"
              @mousedown="startDrag($event, element)"
            >
              <component
                :is="getElementComponent(element.type)"
                :element="element"
                :preview="true"
              />
              
              <!-- Resize Handles -->
              <div v-if="selectedElement?.id === element.id" class="resize-handles">
                <div class="resize-handle nw" @mousedown.stop="startResize($event, element, 'nw')"></div>
                <div class="resize-handle ne" @mousedown.stop="startResize($event, element, 'ne')"></div>
                <div class="resize-handle sw" @mousedown.stop="startResize($event, element, 'sw')"></div>
                <div class="resize-handle se" @mousedown.stop="startResize($event, element, 'se')"></div>
              </div>
              
              <!-- Delete Button -->
              <div v-if="selectedElement?.id === element.id" class="element-delete">
                <el-button
                  size="small"
                  type="danger"
                  circle
                  @click.stop="deleteElement(element)"
                >
                  <i class="fas fa-times"></i>
                </el-button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Properties Panel -->
      <div class="properties-panel">
        <h3>Properties</h3>
        <div v-if="selectedElement" class="element-properties">
          <h4>{{ getElementLabel(selectedElement.type) }}</h4>
          
          <!-- Common Properties -->
          <div class="property-group">
            <label>Position</label>
            <div class="position-inputs">
              <el-input
                v-model.number="selectedElement.x"
                placeholder="X"
                type="number"
                size="small"
              />
              <el-input
                v-model.number="selectedElement.y"
                placeholder="Y"
                type="number"
                size="small"
              />
            </div>
          </div>
          
          <div class="property-group">
            <label>Size</label>
            <div class="size-inputs">
              <el-input
                v-model.number="selectedElement.width"
                placeholder="Width"
                type="number"
                size="small"
              />
              <el-input
                v-model.number="selectedElement.height"
                placeholder="Height"
                type="number"
                size="small"
              />
            </div>
          </div>
          
          <!-- Text Properties -->
          <template v-if="isTextElement(selectedElement.type)">
            <div class="property-group">
              <label>Text Content</label>
              <el-input
                v-model="selectedElement.content"
                placeholder="Enter text"
                size="small"
              />
            </div>
            
            <div class="property-group">
              <label>Font Size</label>
              <el-input
                v-model.number="selectedElement.fontSize"
                placeholder="Font size"
                type="number"
                size="small"
              />
            </div>
            
            <div class="property-group">
              <label>Font Weight</label>
              <el-select v-model="selectedElement.fontWeight" size="small">
                <el-option label="Normal" value="normal" />
                <el-option label="Bold" value="bold" />
                <el-option label="Light" value="300" />
                <el-option label="Medium" value="500" />
                <el-option label="Semi Bold" value="600" />
              </el-select>
            </div>
            
            <div class="property-group">
              <label>Text Color</label>
              <el-color-picker v-model="selectedElement.color" size="small" />
            </div>
            
            <div class="property-group">
              <label>Text Align</label>
              <el-select v-model="selectedElement.textAlign" size="small">
                <el-option label="Left" value="left" />
                <el-option label="Center" value="center" />
                <el-option label="Right" value="right" />
              </el-select>
            </div>
          </template>
          
          <!-- Image Properties -->
          <template v-if="selectedElement.type === 'image' || selectedElement.type === 'logo'">
            <div class="property-group">
              <label>Image URL</label>
              <el-input
                v-model="selectedElement.src"
                placeholder="Image URL"
                size="small"
              />
            </div>
            
            <div class="property-group">
              <label>Border Radius</label>
              <el-input
                v-model.number="selectedElement.borderRadius"
                placeholder="Border radius"
                type="number"
                size="small"
              />
            </div>
          </template>
          
          <!-- QR Code Properties -->
          <template v-if="selectedElement.type === 'qr_code'">
            <div class="property-group">
              <label>QR Data</label>
              <el-input
                v-model="selectedElement.data"
                placeholder="QR code data"
                size="small"
              />
            </div>
          </template>
          
          <!-- Background Properties -->
          <div class="property-group">
            <label>Background Color</label>
            <el-color-picker v-model="selectedElement.backgroundColor" size="small" show-alpha />
          </div>
          
          <div class="property-group">
            <label>Border</label>
            <div class="border-inputs">
              <el-input
                v-model.number="selectedElement.borderWidth"
                placeholder="Width"
                type="number"
                size="small"
              />
              <el-color-picker v-model="selectedElement.borderColor" size="small" />
            </div>
          </div>
        </div>
        
        <div v-else class="no-selection">
          <i class="fas fa-mouse-pointer"></i>
          <p>Select an element to edit its properties</p>
        </div>
        
        <!-- Template Settings -->
        <div class="template-settings">
          <h4>Template Settings</h4>
          
          <div class="property-group">
            <label>Background Color</label>
            <el-color-picker v-model="templateData.backgroundColor" size="small" />
          </div>
          
          <div class="property-group">
            <label>Show Grid</label>
            <el-switch v-model="showGrid" />
          </div>
          
          <div class="property-group">
            <label>Description</label>
            <el-input
              v-model="templateData.description"
              type="textarea"
              placeholder="Template description"
              :rows="3"
              size="small"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <el-dialog
      v-model="showPreview"
      title="Template Preview"
      width="600px"
    >
      <div class="preview-container">
        <div class="preview-card" :style="previewStyle">
          <div
            v-for="element in templateData.elements"
            :key="element.id"
            :style="getElementStyle(element, false)"
          >
            <component
              :is="getElementComponent(element.type)"
              :element="element"
              :preview="true"
            />
          </div>
        </div>
      </div>
      <template #footer>
        <el-button @click="showPreview = false">Close</el-button>
        <el-button type="primary" @click="exportPreview">Export Preview</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import TextElement from './elements/TextElement.vue'
import ImageElement from './elements/ImageElement.vue'
import QRCodeElement from './elements/QRCodeElement.vue'
import ShapeElement from './elements/ShapeElement.vue'

export default {
  name: 'TemplateBuilder',
  components: {
    TextElement,
    ImageElement,
    QRCodeElement,
    ShapeElement
  },
  props: {
    template: {
      type: Object,
      default: null
    }
  },
  emits: ['save', 'cancel'],
  setup(props, { emit }) {
    // Reactive data
    const zoomLevel = ref(1)
    const showGrid = ref(true)
    const showPreview = ref(false)
    const selectedElement = ref(null)
    const draggedElement = ref(null)
    const isDragging = ref(false)
    const isResizing = ref(false)
    const dragOffset = reactive({ x: 0, y: 0 })
    
    const templateData = reactive({
      name: '',
      description: '',
      width: 350,
      height: 220,
      size: 'standard',
      backgroundColor: '#ffffff',
      elements: []
    })
    
    // Element definitions
    const basicElements = [
      { type: 'text', label: 'Text', icon: 'fas fa-font' },
      { type: 'image', label: 'Image', icon: 'fas fa-image' },
      { type: 'shape', label: 'Shape', icon: 'fas fa-square' },
      { type: 'line', label: 'Line', icon: 'fas fa-minus' }
    ]
    
    const employeeElements = [
      { type: 'employee_name', label: 'Employee Name', icon: 'fas fa-user' },
      { type: 'employee_id', label: 'Employee ID', icon: 'fas fa-id-badge' },
      { type: 'department', label: 'Department', icon: 'fas fa-building' },
      { type: 'position', label: 'Position', icon: 'fas fa-briefcase' },
      { type: 'photo', label: 'Photo', icon: 'fas fa-camera' },
      { type: 'qr_code', label: 'QR Code', icon: 'fas fa-qrcode' }
    ]
    
    const companyElements = [
      { type: 'company_name', label: 'Company Name', icon: 'fas fa-building' },
      { type: 'logo', label: 'Company Logo', icon: 'fas fa-image' },
      { type: 'address', label: 'Address', icon: 'fas fa-map-marker-alt' }
    ]
    
    // Computed properties
    const canvasStyle = computed(() => ({
      width: `${templateData.width * zoomLevel.value}px`,
      height: `${templateData.height * zoomLevel.value}px`,
      backgroundColor: templateData.backgroundColor,
      transform: `scale(${zoomLevel.value})`,
      transformOrigin: 'top left'
    }))
    
    const previewStyle = computed(() => ({
      width: `${templateData.width}px`,
      height: `${templateData.height}px`,
      backgroundColor: templateData.backgroundColor,
      position: 'relative',
      margin: '0 auto',
      border: '1px solid #ddd',
      borderRadius: '8px',
      overflow: 'hidden'
    }))
    
    // Methods
    const initializeTemplate = () => {
      if (props.template) {
        Object.assign(templateData, {
          name: props.template.name,
          description: props.template.description || '',
          width: props.template.width,
          height: props.template.height,
          backgroundColor: props.template.background_color || '#ffffff',
          elements: JSON.parse(props.template.elements || '[]')
        })
        
        // Determine size preset
        if (templateData.width === 350 && templateData.height === 220) {
          templateData.size = 'standard'
        } else if (templateData.width === 340 && templateData.height === 214) {
          templateData.size = 'credit'
        } else {
          templateData.size = 'custom'
        }
      }
    }
    
    const handleSizeChange = () => {
      switch (templateData.size) {
        case 'standard':
          templateData.width = 350
          templateData.height = 220
          break
        case 'credit':
          templateData.width = 340
          templateData.height = 214
          break
        // custom size is handled by manual input
      }
    }
    
    const zoomIn = () => {
      if (zoomLevel.value < 2) {
        zoomLevel.value = Math.min(2, zoomLevel.value + 0.25)
      }
    }
    
    const zoomOut = () => {
      if (zoomLevel.value > 0.5) {
        zoomLevel.value = Math.max(0.5, zoomLevel.value - 0.25)
      }
    }
    
    const handleDragStart = (event, element) => {
      draggedElement.value = element
      event.dataTransfer.effectAllowed = 'copy'
    }
    
    const handleDragOver = (event) => {
      event.preventDefault()
      event.dataTransfer.dropEffect = 'copy'
    }
    
    const handleDrop = (event) => {
      event.preventDefault()
      
      if (!draggedElement.value) return
      
      const canvas = event.currentTarget
      const rect = canvas.getBoundingClientRect()
      const x = (event.clientX - rect.left) / zoomLevel.value
      const y = (event.clientY - rect.top) / zoomLevel.value
      
      const newElement = createElementFromType(draggedElement.value.type, x, y)
      templateData.elements.push(newElement)
      
      draggedElement.value = null
      selectElement(newElement)
    }
    
    const createElementFromType = (type, x, y) => {
      const baseElement = {
        id: Date.now() + Math.random(),
        type,
        x: Math.max(0, x),
        y: Math.max(0, y),
        width: 100,
        height: 30,
        backgroundColor: 'transparent',
        borderWidth: 0,
        borderColor: '#000000'
      }
      
      // Type-specific properties
      switch (type) {
        case 'text':
        case 'employee_name':
        case 'employee_id':
        case 'department':
        case 'position':
        case 'company_name':
        case 'address':
          return {
            ...baseElement,
            content: getDefaultContent(type),
            fontSize: 14,
            fontWeight: 'normal',
            color: '#000000',
            textAlign: 'left'
          }
          
        case 'image':
        case 'logo':
        case 'photo':
          return {
            ...baseElement,
            width: 80,
            height: 80,
            src: '',
            borderRadius: type === 'photo' ? 50 : 0
          }
          
        case 'qr_code':
          return {
            ...baseElement,
            width: 60,
            height: 60,
            data: '{employee_id}'
          }
          
        case 'shape':
          return {
            ...baseElement,
            backgroundColor: '#f0f0f0',
            borderWidth: 1,
            borderColor: '#cccccc'
          }
          
        case 'line':
          return {
            ...baseElement,
            height: 2,
            backgroundColor: '#000000'
          }
          
        default:
          return baseElement
      }
    }
    
    const getDefaultContent = (type) => {
      const contentMap = {
        text: 'Sample Text',
        employee_name: '{employee_name}',
        employee_id: '{employee_id}',
        department: '{department}',
        position: '{position}',
        company_name: 'Company Name',
        address: 'Company Address'
      }
      return contentMap[type] || 'Text'
    }
    
    const selectElement = (element) => {
      selectedElement.value = element
    }
    
    const deleteElement = (element) => {
      const index = templateData.elements.findIndex(el => el.id === element.id)
      if (index > -1) {
        templateData.elements.splice(index, 1)
        if (selectedElement.value?.id === element.id) {
          selectedElement.value = null
        }
      }
    }
    
    const startDrag = (event, element) => {
      if (isResizing.value) return
      
      isDragging.value = true
      selectElement(element)
      
      const rect = event.currentTarget.getBoundingClientRect()
      dragOffset.x = event.clientX - rect.left
      dragOffset.y = event.clientY - rect.top
      
      document.addEventListener('mousemove', handleMouseMove)
      document.addEventListener('mouseup', handleMouseUp)
    }
    
    const handleMouseMove = (event) => {
      if (!isDragging.value || !selectedElement.value) return
      
      const canvas = document.querySelector('.canvas')
      const canvasRect = canvas.getBoundingClientRect()
      
      const newX = (event.clientX - canvasRect.left - dragOffset.x) / zoomLevel.value
      const newY = (event.clientY - canvasRect.top - dragOffset.y) / zoomLevel.value
      
      selectedElement.value.x = Math.max(0, Math.min(templateData.width - selectedElement.value.width, newX))
      selectedElement.value.y = Math.max(0, Math.min(templateData.height - selectedElement.value.height, newY))
    }
    
    const handleMouseUp = () => {
      isDragging.value = false
      isResizing.value = false
      document.removeEventListener('mousemove', handleMouseMove)
      document.removeEventListener('mouseup', handleMouseUp)
    }
    
    const startResize = (event, element, direction) => {
      isResizing.value = true
      selectElement(element)
      
      const startX = event.clientX
      const startY = event.clientY
      const startWidth = element.width
      const startHeight = element.height
      const startPosX = element.x
      const startPosY = element.y
      
      const handleResizeMove = (moveEvent) => {
        const deltaX = (moveEvent.clientX - startX) / zoomLevel.value
        const deltaY = (moveEvent.clientY - startY) / zoomLevel.value
        
        switch (direction) {
          case 'se': // Southeast
            element.width = Math.max(10, startWidth + deltaX)
            element.height = Math.max(10, startHeight + deltaY)
            break
          case 'sw': // Southwest
            element.width = Math.max(10, startWidth - deltaX)
            element.height = Math.max(10, startHeight + deltaY)
            element.x = Math.max(0, startPosX + deltaX)
            break
          case 'ne': // Northeast
            element.width = Math.max(10, startWidth + deltaX)
            element.height = Math.max(10, startHeight - deltaY)
            element.y = Math.max(0, startPosY + deltaY)
            break
          case 'nw': // Northwest
            element.width = Math.max(10, startWidth - deltaX)
            element.height = Math.max(10, startHeight - deltaY)
            element.x = Math.max(0, startPosX + deltaX)
            element.y = Math.max(0, startPosY + deltaY)
            break
        }
      }
      
      const handleResizeUp = () => {
        isResizing.value = false
        document.removeEventListener('mousemove', handleResizeMove)
        document.removeEventListener('mouseup', handleResizeUp)
      }
      
      document.addEventListener('mousemove', handleResizeMove)
      document.addEventListener('mouseup', handleResizeUp)
    }
    
    const getElementStyle = (element, includeZoom = true) => {
      const scale = includeZoom ? zoomLevel.value : 1
      return {
        position: 'absolute',
        left: `${element.x * scale}px`,
        top: `${element.y * scale}px`,
        width: `${element.width * scale}px`,
        height: `${element.height * scale}px`,
        backgroundColor: element.backgroundColor || 'transparent',
        border: element.borderWidth ? `${element.borderWidth}px solid ${element.borderColor}` : 'none',
        borderRadius: element.borderRadius ? `${element.borderRadius}px` : '0',
        cursor: isDragging.value ? 'grabbing' : 'grab'
      }
    }
    
    const getElementComponent = (type) => {
      if (isTextElement(type)) return 'TextElement'
      if (['image', 'logo', 'photo'].includes(type)) return 'ImageElement'
      if (type === 'qr_code') return 'QRCodeElement'
      return 'ShapeElement'
    }
    
    const isTextElement = (type) => {
      return ['text', 'employee_name', 'employee_id', 'department', 'position', 'company_name', 'address'].includes(type)
    }
    
    const getElementLabel = (type) => {
      const allElements = [...basicElements, ...employeeElements, ...companyElements]
      const element = allElements.find(el => el.type === type)
      return element ? element.label : type
    }
    
    const previewTemplate = () => {
      showPreview.value = true
    }
    
    const resetTemplate = () => {
      templateData.elements = []
      selectedElement.value = null
      ElMessage.success('Template reset')
    }
    
    const saveTemplate = () => {
      if (!templateData.name.trim()) {
        ElMessage.error('Please enter a template name')
        return
      }
      
      const templateToSave = {
        name: templateData.name,
        description: templateData.description,
        width: templateData.width,
        height: templateData.height,
        background_color: templateData.backgroundColor,
        elements: JSON.stringify(templateData.elements)
      }
      
      emit('save', templateToSave)
    }
    
    const exportPreview = () => {
      // Implementation for exporting preview as image
      ElMessage.info('Export functionality will be implemented')
    }
    
    // Lifecycle
    onMounted(() => {
      initializeTemplate()
    })
    
    return {
      // Reactive data
      zoomLevel,
      showGrid,
      showPreview,
      selectedElement,
      templateData,
      basicElements,
      employeeElements,
      companyElements,
      
      // Computed
      canvasStyle,
      previewStyle,
      
      // Methods
      handleSizeChange,
      zoomIn,
      zoomOut,
      handleDragStart,
      handleDragOver,
      handleDrop,
      selectElement,
      deleteElement,
      startDrag,
      startResize,
      getElementStyle,
      getElementComponent,
      isTextElement,
      getElementLabel,
      previewTemplate,
      resetTemplate,
      saveTemplate,
      exportPreview
    }
  }
}
</script>

<style scoped>
.template-builder {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f5f7fa;
}

.builder-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: white;
  border-bottom: 1px solid #e1e8ed;
}

.header-left h2 {
  margin: 0;
  color: #2c3e50;
  font-size: 20px;
  font-weight: 600;
}

.header-left p {
  margin: 4px 0 0 0;
  color: #7f8c8d;
  font-size: 14px;
}

.header-right {
  display: flex;
  gap: 12px;
}

.builder-content {
  flex: 1;
  display: flex;
  overflow: hidden;
}

.elements-palette {
  width: 280px;
  background: white;
  border-right: 1px solid #e1e8ed;
  overflow-y: auto;
  padding: 20px;
}

.elements-palette h3 {
  margin: 0 0 20px 0;
  color: #2c3e50;
  font-size: 16px;
  font-weight: 600;
}

.category {
  margin-bottom: 24px;
}

.category h4 {
  margin: 0 0 12px 0;
  color: #34495e;
  font-size: 14px;
  font-weight: 500;
}

.elements-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.element-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 8px;
  border: 1px solid #e1e8ed;
  border-radius: 6px;
  cursor: grab;
  transition: all 0.2s ease;
  background: #fafbfc;
}

.element-item:hover {
  border-color: #9b59b6;
  background: #f8f4fd;
  transform: translateY(-1px);
}

.element-item:active {
  cursor: grabbing;
}

.element-item i {
  font-size: 18px;
  color: #9b59b6;
  margin-bottom: 6px;
}

.element-item span {
  font-size: 11px;
  color: #2c3e50;
  text-align: center;
  line-height: 1.2;
}

.canvas-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f8f9fa;
}

.canvas-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: white;
  border-bottom: 1px solid #e1e8ed;
}

.canvas-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.canvas-controls label {
  font-size: 14px;
  color: #2c3e50;
  font-weight: 500;
}

.zoom-controls {
  display: flex;
  align-items: center;
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
  border: 2px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.grid-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: 
    linear-gradient(rgba(0,0,0,0.1) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0,0,0,0.1) 1px, transparent 1px);
  background-size: 20px 20px;
  pointer-events: none;
}

.template-element {
  cursor: grab;
  border: 2px solid transparent;
  transition: border-color 0.2s ease;
}

.template-element:hover {
  border-color: #9b59b6;
}

.template-element.selected {
  border-color: #e74c3c;
  border-style: dashed;
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
  background: #e74c3c;
  border: 1px solid white;
  border-radius: 50%;
  cursor: pointer;
  pointer-events: all;
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

.element-delete {
  position: absolute;
  top: -12px;
  right: -12px;
}

.properties-panel {
  width: 300px;
  background: white;
  border-left: 1px solid #e1e8ed;
  overflow-y: auto;
  padding: 20px;
}

.properties-panel h3 {
  margin: 0 0 20px 0;
  color: #2c3e50;
  font-size: 16px;
  font-weight: 600;
}

.element-properties h4 {
  margin: 0 0 16px 0;
  color: #34495e;
  font-size: 14px;
  font-weight: 500;
  padding-bottom: 8px;
  border-bottom: 1px solid #e1e8ed;
}

.property-group {
  margin-bottom: 16px;
}

.property-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  color: #2c3e50;
  font-weight: 500;
}

.position-inputs,
.size-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.border-inputs {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 8px;
  align-items: center;
}

.no-selection {
  text-align: center;
  padding: 40px 20px;
  color: #7f8c8d;
}

.no-selection i {
  font-size: 48px;
  margin-bottom: 16px;
  display: block;
}

.template-settings {
  margin-top: 32px;
  padding-top: 20px;
  border-top: 1px solid #e1e8ed;
}

.template-settings h4 {
  margin: 0 0 16px 0;
  color: #34495e;
  font-size: 14px;
  font-weight: 500;
}

.preview-container {
  text-align: center;
  padding: 20px;
}

.preview-card {
  display: inline-block;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

:deep(.el-input__inner) {
  font-size: 13px;
}

:deep(.el-select) {
  width: 100%;
}

:deep(.el-color-picker) {
  width: 100%;
}

:deep(.el-textarea__inner) {
  font-size: 13px;
}
</style>