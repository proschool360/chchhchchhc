<template>
  <div class="id-card-management">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="fas fa-id-card"></i>
          ID Card Management
        </h1>
        <p class="page-description">Generate and manage employee ID cards with custom templates</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" @click="showTemplateBuilder = true">
          <i class="fas fa-palette"></i>
          Template Builder
        </el-button>
        <el-button type="success" @click="generateBulkCards">
          <i class="fas fa-layer-group"></i>
          Bulk Generate
        </el-button>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon generated">
          <i class="fas fa-id-card"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.total_cards }}</h3>
          <p>Total ID Cards</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon pending">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.pending_cards }}</h3>
          <p>Pending Generation</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon templates">
          <i class="fas fa-palette"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.total_templates }}</h3>
          <p>Available Templates</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon downloads">
          <i class="fas fa-download"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.total_downloads }}</h3>
          <p>Downloads This Month</p>
        </div>
      </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="main-content">
      <el-tabs v-model="activeTab" @tab-click="handleTabClick">
        <!-- ID Cards Tab -->
        <el-tab-pane label="ID Cards" name="cards">
          <div class="cards-section">
            <div class="section-header">
              <h2>Employee ID Cards</h2>
              <div class="table-actions">
                <el-input
                  v-model="searchQuery"
                  placeholder="Search employees..."
                  prefix-icon="el-icon-search"
                  @input="handleSearch"
                  style="width: 250px; margin-right: 12px;"
                />
                <el-select v-model="selectedDepartment" placeholder="All Departments" @change="loadIdCards">
                  <el-option label="All Departments" value="" />
                  <el-option
                    v-for="dept in departments"
                    :key="dept.id"
                    :label="dept.name"
                    :value="dept.id"
                  />
                </el-select>
                <el-select v-model="selectedTemplate" placeholder="Select Template" @change="loadIdCards">
                  <el-option
                    v-for="template in templates"
                    :key="template.id"
                    :label="template.name"
                    :value="template.id"
                  />
                </el-select>
              </div>
            </div>

            <!-- Bulk Actions Bar -->
            <div v-if="getSelectedEmployees().length > 0" class="bulk-actions-bar">
              <div class="bulk-info">
                <i class="fas fa-check-circle"></i>
                <span>{{ getSelectedEmployees().length }} employee(s) selected</span>
              </div>
              <div class="bulk-buttons">
                <el-button type="primary" @click="generateBulkCards">
                  <i class="fas fa-layer-group"></i>
                  Generate Selected ({{ getSelectedEmployees().length }})
                </el-button>
                <el-button @click="clearSelection">
                  <i class="fas fa-times"></i>
                  Clear Selection
                </el-button>
              </div>
            </div>

            <el-table
              ref="idCardsTable"
              :data="filteredIdCards"
              v-loading="loading"
              stripe
              style="width: 100%"
              @selection-change="handleSelectionChange"
            >
              <el-table-column type="selection" width="55" />
              <el-table-column prop="employee_id" label="Employee ID" width="120" />
              <el-table-column label="Employee" width="200">
                <template #default="scope">
                  <div class="employee-info">
                    <div class="employee-avatar">
                      <img 
                        v-if="scope.row.photo" 
                        :src="scope.row.photo" 
                        :alt="scope.row.full_name"
                      />
                      <i v-else class="fas fa-user"></i>
                    </div>
                    <div class="employee-details">
                      <strong>{{ scope.row.full_name }}</strong>
                      <small>{{ scope.row.department }}</small>
                    </div>
                  </div>
                </template>
              </el-table-column>
              <el-table-column prop="position" label="Position" width="150" />
              <el-table-column label="Template" width="150">
                <template #default="scope">
                  <el-tag v-if="scope.row.template_name" size="small" type="info">
                    {{ scope.row.template_name }}
                  </el-tag>
                  <span v-else class="text-muted">No template</span>
                </template>
              </el-table-column>
              <el-table-column label="Status" width="120">
                <template #default="scope">
                  <el-tag
                    :type="getStatusType(scope.row.status)"
                    size="small"
                  >
                    {{ scope.row.status }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="created_at" label="Generated" width="120">
                <template #default="scope">
                  <span v-if="scope.row.created_at">
                    {{ formatDate(scope.row.created_at) }}
                  </span>
                  <span v-else class="text-muted">Not generated</span>
                </template>
              </el-table-column>
              <el-table-column label="QR Code" width="100">
                <template #default="scope">
                  <el-button
                    size="small"
                    @click="showQRCode(scope.row)"
                    :disabled="!scope.row.qr_code"
                  >
                    <i class="fas fa-qrcode"></i>
                  </el-button>
                </template>
              </el-table-column>
              <el-table-column label="Actions" width="200">
                <template #default="scope">
                  <el-button
                    size="small"
                    type="primary"
                    @click="generateCard(scope.row)"
                    :disabled="scope.row.status === 'generated'"
                  >
                    <i class="fas fa-magic"></i>
                    Generate
                  </el-button>
                  <el-button
                    size="small"
                    type="success"
                    @click="downloadCard(scope.row)"
                    :disabled="scope.row.status !== 'generated'"
                  >
                    <i class="fas fa-download"></i>
                    Download
                  </el-button>
                  <el-button
                    size="small"
                    @click="previewCard(scope.row)"
                    :disabled="scope.row.status !== 'generated'"
                  >
                    <i class="fas fa-eye"></i>
                    Preview
                  </el-button>
                </template>
              </el-table-column>
            </el-table>

            <!-- Pagination -->
            <div class="pagination-wrapper">
              <el-pagination
                v-model:current-page="currentPage"
                v-model:page-size="pageSize"
                :page-sizes="[10, 20, 50, 100]"
                :total="totalCards"
                layout="total, sizes, prev, pager, next, jumper"
                @size-change="handleSizeChange"
                @current-change="handleCurrentChange"
              />
            </div>
          </div>
        </el-tab-pane>

        <!-- Templates Tab -->
        <el-tab-pane label="Templates" name="templates">
          <div class="templates-section">
            <div class="section-header">
              <h2>ID Card Templates</h2>
              <div class="template-actions">
                <el-button type="primary" @click="createNewTemplate">
                  <i class="fas fa-plus"></i>
                  New Template
                </el-button>
                <el-button type="success" @click="createTemplateVariations">
                  <i class="fas fa-magic"></i>
                  Create Variations
                </el-button>
              </div>
            </div>

            <div class="templates-grid">
              <div
                v-for="template in templates"
                :key="template.id"
                class="template-card"
                :class="{ active: template.is_default }"
              >
                <div class="template-preview">
                  <div class="template-thumbnail">
                    <img v-if="template.preview_url" :src="template.preview_url" :alt="template.name" />
                    <div v-else class="template-placeholder">
                      <i class="fas fa-id-card"></i>
                      <span>{{ template.name }}</span>
                    </div>
                  </div>
                  <div class="template-overlay">
                    <el-button size="small" @click="editTemplate(template)">
                      <i class="fas fa-edit"></i>
                    </el-button>
                    <el-button size="small" @click="duplicateTemplate(template)">
                      <i class="fas fa-copy"></i>
                    </el-button>
                    <el-button 
                      size="small" 
                      type="danger" 
                      @click="deleteTemplate(template)"
                      :disabled="template.is_default"
                    >
                      <i class="fas fa-trash"></i>
                    </el-button>
                  </div>
                </div>
                <div class="template-info">
                  <h3>{{ template.template_name || template.name }}</h3>
                  <p>{{ template.description || 'No description available' }}</p>
                  <div class="template-meta">
                    <span class="template-size">{{ template.width || 400 }}x{{ template.height || 250 }}px</span>
                    <el-tag v-if="template.layout_type" size="small" type="info">{{ template.layout_type }}</el-tag>
                    <el-tag v-if="template.is_default" size="small" type="success">Default</el-tag>
                  </div>
                  <div class="template-actions">
                    <el-button 
                      size="small" 
                      @click="setDefaultTemplate(template)"
                      :disabled="template.is_default"
                    >
                      Set as Default
                    </el-button>
                    <el-button size="small" @click="previewTemplate(template)">
                      Preview
                    </el-button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>

    <!-- Template Builder Modal -->
    <el-dialog
      v-model="showTemplateBuilder"
      title="ID Card Template Builder"
      width="90%"
      :close-on-click-modal="false"
    >
      <SimpleTemplateBuilder
        v-if="showTemplateBuilder"
        :template="editingTemplate"
        @save="handleTemplateSave"
        @cancel="closeTemplateBuilder"
      />
    </el-dialog>

    <!-- Card Preview Modal -->
    <el-dialog
      v-model="showPreviewModal"
      title="ID Card Preview"
      width="600px"
    >
      <div class="card-preview">
        <img v-if="previewCardUrl" :src="previewCardUrl" alt="ID Card Preview" />
        <div v-else class="preview-placeholder">
          <i class="fas fa-id-card"></i>
          <p>No preview available</p>
        </div>
      </div>
      <template #footer>
        <el-button @click="showPreviewModal = false">Close</el-button>
        <el-button type="primary" @click="downloadPreviewCard">Download</el-button>
      </template>
    </el-dialog>

    <!-- QR Code Modal -->
    <el-dialog
      v-model="showQRModal"
      title="Employee QR Code"
      width="400px"
    >
      <div class="qr-code-display">
        <div class="qr-code-image">
          <img v-if="currentQRCode" :src="currentQRCode" alt="QR Code" />
        </div>
        <div class="qr-code-info">
          <p><strong>Employee:</strong> {{ selectedEmployee?.full_name }}</p>
          <p><strong>ID:</strong> {{ selectedEmployee?.employee_id }}</p>
          <p><strong>Department:</strong> {{ selectedEmployee?.department }}</p>
        </div>
      </div>
      <template #footer>
        <el-button @click="showQRModal = false">Close</el-button>
        <el-button type="primary" @click="downloadQRCode">Download QR</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useStore } from 'vuex'
import TemplateBuilder from './components/TemplateBuilder.vue'
import SimpleTemplateBuilder from './components/SimpleTemplateBuilder.vue'
import idCardAPI from '@/api/idcards'
import departmentAPI from '@/api/departments'

export default {
  name: 'IdCardManagement',
  components: {
    TemplateBuilder,
    SimpleTemplateBuilder
  },
  setup() {
    const store = useStore()
    
    // Reactive data
    const activeTab = ref('cards')
    const loading = ref(false)
    const searchQuery = ref('')
    const selectedDepartment = ref('')
    const selectedTemplate = ref('')
    const currentPage = ref(1)
    const pageSize = ref(20)
    const totalCards = ref(0)
    
    const idCards = ref([])
    const templates = ref([])
    const departments = ref([])
    
    const showTemplateBuilder = ref(false)
    const showPreviewModal = ref(false)
    const showQRModal = ref(false)
    
    const editingTemplate = ref(null)
    const previewCardUrl = ref('')
    const currentQRCode = ref('')
    const selectedEmployee = ref(null)
    const selectedEmployees = ref([])
    const idCardsTable = ref(null)
    
    const stats = reactive({
      total_cards: 0,
      pending_cards: 0,
      total_templates: 0,
      total_downloads: 0
    })
    
    // Computed properties
    const currentUser = computed(() => store.getters.currentUser)
    const userRole = computed(() => store.getters.userRole)
    
    const filteredIdCards = computed(() => {
      let filtered = idCards.value
      
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(card => 
          card.full_name.toLowerCase().includes(query) ||
          card.employee_id.toLowerCase().includes(query) ||
          card.department.toLowerCase().includes(query)
        )
      }
      
      return filtered
    })
    
    // Methods
    const loadIdCards = async () => {
      try {
        loading.value = true
        const params = {
          page: currentPage.value,
          limit: pageSize.value,
          department_id: selectedDepartment.value || undefined,
          template_id: selectedTemplate.value || undefined
        }
        
        const response = await idCardAPI.getAll(params)
        if (response.success) {
          idCards.value = response.data.cards
          totalCards.value = response.data.total
          updateStats(response.data.stats)
        }
      } catch (error) {
        ElMessage.error('Failed to load ID cards')
        console.error('Load ID cards error:', error)
      } finally {
        loading.value = false
      }
    }
    
    const loadTemplates = async () => {
      try {
        const response = await idCardAPI.getTemplates()
        if (response.success) {
          templates.value = response.data
          stats.total_templates = response.data.length
          
          // Load template statistics
          const statsResponse = await idCardAPI.getTemplateStats()
          if (statsResponse.success) {
            updateStats(statsResponse.data)
          }
        }
      } catch (error) {
        console.error('Load templates error:', error)
      }
    }
    
    const loadDepartments = async () => {
      try {
        const response = await departmentAPI.getDepartments()
        if (response.success) {
          departments.value = response.data
        }
      } catch (error) {
        console.error('Load departments error:', error)
      }
    }
    
    const updateStats = (statsData) => {
      if (statsData) {
        Object.assign(stats, statsData)
      }
    }
    
    const handleTabClick = (tab) => {
      if (tab.name === 'templates') {
        loadTemplates()
      }
    }
    
    const handleSearch = () => {
      // Search is handled by computed property
    }
    
    const handleSizeChange = (newSize) => {
      pageSize.value = newSize
      currentPage.value = 1
      loadIdCards()
    }
    
    const handleCurrentChange = (newPage) => {
      currentPage.value = newPage
      loadIdCards()
    }
    
    const generateCard = async (employee) => {
      try {
        const response = await idCardAPI.generate(employee.id, {
          template_id: selectedTemplate.value
        })
        if (response.success) {
          ElMessage.success('ID card generated successfully')
          loadIdCards()
        }
      } catch (error) {
        ElMessage.error('Failed to generate ID card')
      }
    }
    
    const generateBulkCards = async () => {
      try {
        // Load templates if not already loaded
        if (templates.value.length === 0) {
          await loadTemplates()
        }

        if (templates.value.length === 0) {
          ElMessage.warning('No templates available. Please create a template first.')
          return
        }

        // Show template selection dialog
        const selectedCount = getSelectedEmployees().length
        const dialogTitle = selectedCount > 0 
          ? `Bulk Generate ID Cards (${selectedCount} selected)`
          : 'Bulk Generate ID Cards (All Eligible)'
         
        const { value: templateId } = await ElMessageBox.prompt(
          'Select a template for bulk ID card generation:',
          dialogTitle,
          {
            confirmButtonText: 'Generate',
            cancelButtonText: 'Cancel',
            inputType: 'select',
            inputOptions: templates.value.reduce((options, template) => {
              const templateInfo = template.template_name || template.name
              const layoutType = template.layout_type ? ` (${template.layout_type})` : ''
              const defaultTag = template.is_default ? ' (Default)' : ''
              options[template.id] = templateInfo + layoutType + defaultTag
              return options
            }, {}),
            inputValue: templates.value.find(t => t.is_default)?.id || templates.value[0]?.id,
            inputValidator: (value) => {
              if (!value) {
                return 'Please select a template'
              }
              return true
            }
          }
        )
        
        // Get selected employees or use all employees without cards
        const selectedEmployees = getSelectedEmployees()
        let employeeIds = []
        
        if (selectedEmployees.length > 0) {
          employeeIds = selectedEmployees.map(emp => emp.employee_id)
        } else {
          // Get all employees without ID cards
          const allEmployees = idCards.value.filter(card => card.status !== 'generated')
          employeeIds = allEmployees.map(emp => emp.employee_id)
        }
         
        // Clear selection after processing
        if (selectedEmployees.length > 0) {
          clearSelection()
        }
        
        if (employeeIds.length === 0) {
          ElMessage.warning('No employees found for ID card generation')
          return
        }
        
        const response = await idCardAPI.bulkGenerate({
          template_id: templateId,
          employee_ids: employeeIds,
          department_id: selectedDepartment.value || undefined
        })
        
        if (response.success) {
          const { successful, failed, total_processed } = response.data
          if (failed > 0) {
            ElMessage.warning(`Generated ${successful} ID cards successfully, ${failed} failed out of ${total_processed} total`)
          } else {
            ElMessage.success(`Successfully generated ${successful} ID cards`)
          }
          loadIdCards()
        } else {
          ElMessage.error(response.error || 'Failed to generate bulk ID cards')
        }
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error('Failed to generate bulk ID cards')
          console.error('Bulk generate error:', error)
        }
      }
    }
    
    const downloadCard = async (employee) => {
      try {
        const response = await idCardAPI.download(employee.id)
        if (response.success) {
          // Handle file download
          const link = document.createElement('a')
          link.href = response.data.download_url
          link.download = `${employee.employee_id}_id_card.png`
          link.click()
          ElMessage.success('Download started')
        }
      } catch (error) {
        ElMessage.error('Failed to download ID card')
      }
    }
    
    const previewCard = async (employee) => {
      try {
        const response = await idCardAPI.preview(employee.id)
        if (response.success) {
          previewCardUrl.value = response.data.preview_url
          selectedEmployee.value = employee
          showPreviewModal.value = true
        }
      } catch (error) {
        ElMessage.error('Failed to load card preview')
      }
    }
    
    const downloadPreviewCard = () => {
      if (previewCardUrl.value && selectedEmployee.value) {
        downloadCard(selectedEmployee.value)
      }
    }
    
    const showQRCode = (employee) => {
      currentQRCode.value = employee.qr_code_url
      selectedEmployee.value = employee
      showQRModal.value = true
    }
    
    const downloadQRCode = () => {
      if (currentQRCode.value && selectedEmployee.value) {
        const link = document.createElement('a')
        link.href = currentQRCode.value
        link.download = `${selectedEmployee.value.employee_id}_qr_code.png`
        link.click()
        ElMessage.success('QR code download started')
      }
    }
    
    const handleSelectionChange = (selection) => {
      selectedEmployees.value = selection
    }
    
    const getSelectedEmployees = () => {
      return selectedEmployees.value
    }
    
    const clearSelection = () => {
      if (idCardsTable.value) {
        idCardsTable.value.clearSelection()
      }
      selectedEmployees.value = []
    }
    
    const createNewTemplate = async () => {
      try {
        // Show layout type selection dialog
        const layoutTypes = await idCardAPI.getLayoutTypes()
        if (!layoutTypes.success) {
          ElMessage.error('Failed to load layout types')
          return
        }

        const { value: layoutData } = await ElMessageBox.prompt(
          'Create a new ID card template:',
          'New Template',
          {
            confirmButtonText: 'Create',
            cancelButtonText: 'Cancel',
            inputType: 'select',
            inputOptions: layoutTypes.data.reduce((options, layout) => {
              options[layout.value] = `${layout.label} - ${layout.description}`
              return options
            }, {}),
            inputValue: 'modern',
            inputValidator: (value) => {
              if (!value) {
                return 'Please select a layout type'
              }
              return true
            }
          }
        )

        // Get template name
        const { value: templateName } = await ElMessageBox.prompt(
          'Enter template name:',
          'Template Name',
          {
            confirmButtonText: 'Create',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
              if (!value || value.trim().length === 0) {
                return 'Template name is required'
              }
              if (value.trim().length < 3) {
                return 'Template name must be at least 3 characters'
              }
              return true
            }
          }
        )

        // Create template with simplified data
        const response = await idCardAPI.createTemplate({
          template_name: templateName.trim(),
          layout_type: layoutData
        })

        if (response.success) {
          ElMessage.success('Template created successfully')
          loadTemplates()
        } else {
          ElMessage.error(response.error || 'Failed to create template')
        }
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error('Failed to create template')
          console.error('Create template error:', error)
        }
      }
    }
    
    const editTemplate = (template) => {
      editingTemplate.value = template
      showTemplateBuilder.value = true
    }
    
    const duplicateTemplate = async (template) => {
      try {
        const response = await idCardAPI.duplicateTemplate(template.id)
        if (response.success) {
          ElMessage.success('Template duplicated successfully')
          loadTemplates()
        }
      } catch (error) {
        ElMessage.error('Failed to duplicate template')
      }
    }
    
    const deleteTemplate = async (template) => {
      try {
        await ElMessageBox.confirm(
          `Delete template "${template.name}"?`,
          'Confirm Delete',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )
        
        const response = await idCardAPI.deleteTemplate(template.id)
        if (response.success) {
          ElMessage.success('Template deleted successfully')
          loadTemplates()
        }
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error('Failed to delete template')
        }
      }
    }
    
    const setDefaultTemplate = async (template) => {
      try {
        const response = await idCardAPI.setDefaultTemplate(template.id)
        if (response.success) {
          ElMessage.success('Default template updated')
          loadTemplates()
        }
      } catch (error) {
        ElMessage.error('Failed to set default template')
      }
    }
    
    const previewTemplate = async (template) => {
      try {
        const response = await idCardAPI.previewTemplate(template.id)
        if (response.success) {
          previewCardUrl.value = response.data.preview_url
          showPreviewModal.value = true
        }
      } catch (error) {
        ElMessage.error('Failed to load template preview')
      }
    }
    
    const createTemplateVariations = async () => {
      try {
        await ElMessageBox.confirm(
          'This will create predefined ID card templates (Corporate Modern, Visitor Pass, etc.). Continue?',
          'Create Template Variations',
          {
            confirmButtonText: 'Create',
            cancelButtonText: 'Cancel',
            type: 'info'
          }
        )
        
        const response = await idCardAPI.createTemplateVariations()
        if (response.success) {
          const { created, existing } = response.data
          if (existing > 0) {
            ElMessage.success(`Created ${created} new templates. ${existing} templates already existed.`)
          } else {
            ElMessage.success(`Successfully created ${created} template variations`)
          }
          loadTemplates()
        } else {
          ElMessage.error(response.error || 'Failed to create template variations')
        }
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error('Failed to create template variations')
          console.error('Create variations error:', error)
        }
      }
    }

    const handleTemplateSave = async (templateData) => {
      try {
        let response
        if (editingTemplate.value) {
          response = await idCardAPI.updateTemplate(editingTemplate.value.id, templateData)
        } else {
          response = await idCardAPI.createTemplate(templateData)
        }
        
        if (response.success) {
          ElMessage.success('Template saved successfully')
          loadTemplates()
          closeTemplateBuilder()
        } else {
          ElMessage.error(response.error || 'Failed to save template')
        }
      } catch (error) {
        ElMessage.error('Failed to save template')
        console.error('Save template error:', error)
      }
    }
    
    const closeTemplateBuilder = () => {
      showTemplateBuilder.value = false
      editingTemplate.value = null
    }
    
    const getStatusType = (status) => {
      const statusTypes = {
        generated: 'success',
        pending: 'warning',
        error: 'danger'
      }
      return statusTypes[status] || 'info'
    }
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    // Lifecycle
    onMounted(() => {
      loadDepartments()
      loadIdCards()
      loadTemplates()
    })
    
    return {
      // Reactive data
      activeTab,
      loading,
      searchQuery,
      selectedDepartment,
      selectedTemplate,
      currentPage,
      pageSize,
      totalCards,
      idCards,
      templates,
      departments,
      showTemplateBuilder,
      showPreviewModal,
      showQRModal,
      editingTemplate,
      previewCardUrl,
      currentQRCode,
      selectedEmployee,
      selectedEmployees,
      idCardsTable,
      stats,
      
      // Computed
      currentUser,
      userRole,
      filteredIdCards,
      
      // Methods
      loadIdCards,
      handleTabClick,
      handleSearch,
      handleSizeChange,
      handleCurrentChange,
      handleSelectionChange,
      getSelectedEmployees,
      clearSelection,
      generateCard,
      generateBulkCards,
      downloadCard,
      previewCard,
      downloadPreviewCard,
      showQRCode,
      downloadQRCode,
      createNewTemplate,
      createTemplateVariations,
      editTemplate,
      duplicateTemplate,
      deleteTemplate,
      setDefaultTemplate,
      previewTemplate,
      handleTemplateSave,
      closeTemplateBuilder,
      getStatusType,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Template Actions */
.template-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.template-actions .el-button {
  margin-left: 0;
}

/* Template Grid Enhancements */
.template-card .template-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 8px 0;
}

.template-card .template-meta .el-tag {
  margin-left: 0;
}

/* Layout type styling */
.template-card .template-meta .el-tag--info {
  background-color: #e1f3ff;
  border-color: #b3d8ff;
  color: #409eff;
}

/* Bulk actions improvements */
.bulk-actions-bar {
  background: linear-gradient(90deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 1px solid #0ea5e9;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.bulk-info {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #0369a1;
  font-weight: 500;
}

.bulk-info i {
  color: #0ea5e9;
}

.bulk-buttons {
  display: flex;
  gap: 8px;
}
.id-card-management {
  padding: 24px;
  background: #f5f7fa;
  min-height: 100vh;
}

/* Enhanced page header */
.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 24px;
  border-radius: 12px;
  margin-bottom: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content h1 {
  margin: 0 0 8px 0;
  font-size: 28px;
  font-weight: 600;
}

.header-content p {
  margin: 0;
  opacity: 0.9;
  font-size: 16px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.header-actions .el-button {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.3);
  color: white;
}

.header-actions .el-button:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  background: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-content h1 {
  margin: 0;
  color: #2c3e50;
  font-size: 28px;
  font-weight: 600;
}

.header-content h1 i {
  margin-right: 12px;
  color: #9b59b6;
}

.page-description {
  margin: 8px 0 0 0;
  color: #7f8c8d;
  font-size: 14px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.stat-icon.generated {
  background: linear-gradient(135deg, #9b59b6, #8e44ad);
}

.stat-icon.pending {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.stat-icon.templates {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.stat-icon.downloads {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
}

.stat-content h3 {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  color: #2c3e50;
}

.stat-content p {
  margin: 4px 0 0 0;
  color: #7f8c8d;
  font-size: 14px;
}

.main-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 24px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h2 {
  margin: 0;
  color: #2c3e50;
  font-size: 20px;
  font-weight: 600;
}

.table-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.employee-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.employee-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  background: #ecf0f1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.employee-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.employee-avatar i {
  color: #bdc3c7;
  font-size: 18px;
}

.employee-details {
  display: flex;
  flex-direction: column;
}

.employee-details small {
  color: #7f8c8d;
  font-size: 12px;
}

.bulk-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #e8f4fd;
  border: 1px solid #b3d8ff;
  border-radius: 6px;
  padding: 12px 16px;
  margin-bottom: 16px;
  animation: slideDown 0.3s ease-out;
}

.bulk-info {
  display: flex;
  align-items: center;
  color: #1890ff;
  font-weight: 500;
}

.bulk-info i {
  margin-right: 8px;
  font-size: 16px;
}

.bulk-buttons {
  display: flex;
  gap: 8px;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

.templates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.template-card {
  border: 2px solid #ecf0f1;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.3s ease;
  background: white;
}

.template-card:hover {
  border-color: #9b59b6;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(155, 89, 182, 0.15);
}

.template-card.active {
  border-color: #27ae60;
  box-shadow: 0 4px 12px rgba(39, 174, 96, 0.15);
}

.template-preview {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.template-thumbnail {
  width: 100%;
  height: 100%;
}

.template-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.template-placeholder {
  width: 100%;
  height: 100%;
  background: #f8f9fa;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #7f8c8d;
}

.template-placeholder i {
  font-size: 48px;
  margin-bottom: 12px;
}

.template-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.template-card:hover .template-overlay {
  opacity: 1;
}

.template-info {
  padding: 16px;
}

.template-info h3 {
  margin: 0 0 8px 0;
  color: #2c3e50;
  font-size: 16px;
  font-weight: 600;
}

.template-info p {
  margin: 0 0 12px 0;
  color: #7f8c8d;
  font-size: 14px;
  line-height: 1.4;
}

.template-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.template-size {
  font-size: 12px;
  color: #95a5a6;
}

.template-actions {
  display: flex;
  gap: 8px;
}

.card-preview {
  text-align: center;
  padding: 20px;
}

.card-preview img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.preview-placeholder {
  padding: 60px 20px;
  color: #7f8c8d;
  text-align: center;
}

.preview-placeholder i {
  font-size: 48px;
  margin-bottom: 16px;
  display: block;
}

.qr-code-display {
  text-align: center;
}

.qr-code-image {
  margin-bottom: 20px;
}

.qr-code-image img {
  width: 200px;
  height: 200px;
  border: 1px solid #ecf0f1;
  border-radius: 8px;
}

.qr-code-info {
  text-align: left;
}

.qr-code-info p {
  margin: 8px 0;
  color: #2c3e50;
}

.text-muted {
  color: #bdc3c7;
}

:deep(.el-tabs__header) {
  margin-bottom: 20px;
}

:deep(.el-tabs__nav-wrap::after) {
  background-color: #ecf0f1;
}

:deep(.el-tabs__active-bar) {
  background-color: #9b59b6;
}

:deep(.el-tabs__item.is-active) {
  color: #9b59b6;
}

:deep(.el-table th) {
  background-color: #f8f9fa;
  color: #2c3e50;
  font-weight: 600;
}

:deep(.el-table--striped .el-table__body tr.el-table__row--striped td) {
  background-color: #fafbfc;
}

:deep(.el-button--small) {
  padding: 5px 8px;
}
</style>