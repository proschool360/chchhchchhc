<template>
  <div class="simple-template-builder">
    <!-- Builder Header -->
    <div class="builder-header">
      <div class="header-left">
        <h2 v-if="isEditing">Edit Template: {{ template?.template_name }}</h2>
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
            <el-form-item label="Name" required>
              <el-input 
                v-model="templateData.template_name" 
                placeholder="Template name"
                :disabled="isEditing"
              />
            </el-form-item>
            
            <el-form-item label="Layout Type" required>
              <el-select 
                v-model="templateData.layout_type" 
                placeholder="Select layout type"
                :disabled="isEditing"
                @change="handleLayoutChange"
              >
                <el-option
                  v-for="layout in layoutTypes"
                  :key="layout.value"
                  :label="layout.label"
                  :value="layout.value"
                >
                  <span>{{ layout.label }}</span>
                  <span style="color: #8492a6; font-size: 13px; margin-left: 8px;">{{ layout.description }}</span>
                </el-option>
              </el-select>
            </el-form-item>

            <el-form-item label="Status">
              <el-switch
                v-model="templateData.status"
                active-text="Active"
                inactive-text="Inactive"
                :active-value="1"
                :inactive-value="0"
              />
            </el-form-item>
          </el-form>
        </div>

        <div class="panel-section" v-if="templateData.layout_type">
          <h3>Layout Customization</h3>
          <div class="layout-preview">
            <div class="preview-card" :class="templateData.layout_type">
              <div class="card-header">
                <div class="company-logo">
                  <i class="fas fa-building"></i>
                </div>
                <div class="company-name">{{ companyName }}</div>
              </div>
              <div class="card-body">
                <div class="employee-photo">
                  <i class="fas fa-user"></i>
                </div>
                <div class="employee-info">
                  <div class="employee-name">John Doe</div>
                  <div class="employee-id">EMP001</div>
                  <div class="employee-dept">IT Department</div>
                  <div class="employee-position">Software Engineer</div>
                </div>
              </div>
              <div class="card-footer">
                <div class="qr-code">
                  <i class="fas fa-qrcode"></i>
                </div>
              </div>
            </div>
          </div>
          
          <div class="layout-info">
            <el-alert
              :title="getLayoutDescription()"
              type="info"
              :closable="false"
              show-icon
            />
          </div>
        </div>

        <div class="panel-section">
          <h3>Template Information</h3>
          <div class="template-stats">
            <div class="stat-item">
              <span class="stat-label">Dimensions:</span>
              <span class="stat-value">{{ getDimensions() }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Orientation:</span>
              <span class="stat-value">{{ getOrientation() }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Elements:</span>
              <span class="stat-value">{{ getElementCount() }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Panel - Preview -->
      <div class="preview-panel">
        <div class="panel-header">
          <h3>Live Preview</h3>
          <div class="preview-actions">
            <el-button size="small" @click="refreshPreview">
              <i class="fas fa-sync-alt"></i>
              Refresh
            </el-button>
          </div>
        </div>
        
        <div class="preview-container">
          <div v-if="previewLoading" class="preview-loading">
            <el-icon class="is-loading"><Loading /></el-icon>
            <p>Generating preview...</p>
          </div>
          
          <div v-else-if="previewUrl" class="preview-image">
            <img :src="previewUrl" alt="Template Preview" />
          </div>
          
          <div v-else class="preview-placeholder">
            <i class="fas fa-id-card"></i>
            <p>Preview will appear here</p>
            <el-button type="primary" @click="generatePreview">
              Generate Preview
            </el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Loading } from '@element-plus/icons-vue'
import idCardAPI from '@/api/idcards'

export default {
  name: 'SimpleTemplateBuilder',
  components: {
    Loading
  },
  props: {
    template: {
      type: Object,
      default: null
    }
  },
  emits: ['save', 'cancel'],
  setup(props, { emit }) {
    const saving = ref(false)
    const previewLoading = ref(false)
    const previewUrl = ref('')
    const layoutTypes = ref([])
    const companyName = ref('Your Company')
    
    const templateData = reactive({
      template_name: '',
      layout_type: 'modern',
      status: 1
    })
    
    const isEditing = computed(() => !!props.template)
    const canPreview = computed(() => templateData.template_name && templateData.layout_type)
    
    // Initialize template data
    const initializeTemplate = () => {
      if (props.template) {
        Object.assign(templateData, {
          template_name: props.template.template_name || props.template.name,
          layout_type: props.template.layout_type || 'modern',
          status: props.template.status || 1
        })
      } else {
        Object.assign(templateData, {
          template_name: '',
          layout_type: 'modern',
          status: 1
        })
      }
    }
    
    // Load layout types
    const loadLayoutTypes = async () => {
      try {
        const response = await idCardAPI.getLayoutTypes()
        if (response.success) {
          layoutTypes.value = response.data
        }
      } catch (error) {
        console.error('Failed to load layout types:', error)
      }
    }
    
    // Handle layout change
    const handleLayoutChange = () => {
      previewUrl.value = ''
      generatePreview()
    }
    
    // Generate preview
    const generatePreview = async () => {
      if (!canPreview.value) return
      
      try {
        previewLoading.value = true
        const response = await idCardAPI.previewTemplate({
          template_name: templateData.template_name,
          layout_type: templateData.layout_type
        })
        
        if (response.success) {
          previewUrl.value = response.data.preview_url
        }
      } catch (error) {
        console.error('Preview generation failed:', error)
      } finally {
        previewLoading.value = false
      }
    }
    
    // Refresh preview
    const refreshPreview = () => {
      generatePreview()
    }
    
    // Preview template
    const previewTemplate = () => {
      generatePreview()
    }
    
    // Save template
    const saveTemplate = async () => {
      if (!templateData.template_name.trim()) {
        ElMessage.error('Template name is required')
        return
      }
      
      if (!templateData.layout_type) {
        ElMessage.error('Layout type is required')
        return
      }
      
      try {
        saving.value = true
        emit('save', {
          template_name: templateData.template_name.trim(),
          layout_type: templateData.layout_type,
          status: templateData.status
        })
      } finally {
        saving.value = false
      }
    }
    
    // Get layout description
    const getLayoutDescription = () => {
      const layout = layoutTypes.value.find(l => l.value === templateData.layout_type)
      return layout ? layout.description : 'No description available'
    }
    
    // Get dimensions
    const getDimensions = () => {
      const dimensions = {
        modern: '400x250px',
        classic: '380x240px',
        minimal: '420x260px'
      }
      return dimensions[templateData.layout_type] || '400x250px'
    }
    
    // Get orientation
    const getOrientation = () => {
      return 'Landscape'
    }
    
    // Get element count
    const getElementCount = () => {
      const counts = {
        modern: '6 elements',
        classic: '5 elements',
        minimal: '4 elements'
      }
      return counts[templateData.layout_type] || '5 elements'
    }
    
    // Watch for template changes
    watch(() => props.template, () => {
      initializeTemplate()
    }, { immediate: true })
    
    // Watch for layout type changes
    watch(() => templateData.layout_type, () => {
      if (templateData.layout_type) {
        generatePreview()
      }
    })
    
    onMounted(() => {
      loadLayoutTypes()
      initializeTemplate()
    })
    
    return {
      templateData,
      saving,
      previewLoading,
      previewUrl,
      layoutTypes,
      companyName,
      isEditing,
      canPreview,
      handleLayoutChange,
      generatePreview,
      refreshPreview,
      previewTemplate,
      saveTemplate,
      getLayoutDescription,
      getDimensions,
      getOrientation,
      getElementCount
    }
  }
}
</script>

<style scoped>
.simple-template-builder {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.builder-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e4e7ed;
  background: #f8f9fa;
}

.header-left h2 {
  margin: 0;
  color: #303133;
  font-size: 18px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.builder-content {
  flex: 1;
  display: flex;
  height: calc(100vh - 200px);
}

.properties-panel {
  width: 350px;
  border-right: 1px solid #e4e7ed;
  overflow-y: auto;
  background: #fff;
}

.preview-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f8f9fa;
}

.panel-section {
  padding: 20px;
  border-bottom: 1px solid #e4e7ed;
}

.panel-section h3 {
  margin: 0 0 16px 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e4e7ed;
  background: #fff;
}

.panel-header h3 {
  margin: 0;
  color: #303133;
  font-size: 16px;
}

.preview-actions {
  display: flex;
  gap: 8px;
}

.preview-container {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.preview-loading,
.preview-placeholder {
  text-align: center;
  color: #909399;
}

.preview-loading .el-icon {
  font-size: 32px;
  margin-bottom: 12px;
}

.preview-placeholder i {
  font-size: 48px;
  margin-bottom: 16px;
  color: #c0c4cc;
}

.preview-image img {
  max-width: 100%;
  max-height: 100%;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.layout-preview {
  margin: 16px 0;
}

.preview-card {
  width: 280px;
  height: 175px;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  font-size: 10px;
  margin: 0 auto;
}

.preview-card.modern {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.preview-card.classic {
  background: #ffffff;
  border: 2px solid #2c3e50;
  color: #2c3e50;
}

.preview-card.minimal {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  color: #495057;
}

.card-header {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  gap: 8px;
}

.company-logo i {
  font-size: 16px;
}

.company-name {
  font-weight: bold;
  font-size: 11px;
}

.card-body {
  flex: 1;
  display: flex;
  padding: 8px 12px;
  gap: 12px;
}

.employee-photo {
  width: 40px;
  height: 40px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.employee-photo i {
  font-size: 20px;
}

.employee-info {
  flex: 1;
}

.employee-name {
  font-weight: bold;
  font-size: 12px;
  margin-bottom: 2px;
}

.employee-id,
.employee-dept,
.employee-position {
  font-size: 9px;
  margin-bottom: 1px;
  opacity: 0.9;
}

.card-footer {
  display: flex;
  justify-content: flex-end;
  padding: 8px 12px;
}

.qr-code {
  width: 24px;
  height: 24px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qr-code i {
  font-size: 12px;
}

.layout-info {
  margin-top: 16px;
}

.template-stats {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-label {
  color: #606266;
  font-size: 14px;
}

.stat-value {
  color: #303133;
  font-weight: 500;
  font-size: 14px;
}
</style>