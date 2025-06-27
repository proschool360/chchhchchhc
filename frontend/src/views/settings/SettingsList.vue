<template>
  <div class="settings-list">
    <div class="page-header">
      <div class="header-content">
        <h1>System Settings</h1>
        <p>Configure and manage system-wide settings and preferences</p>
      </div>
    </div>
    
    <!-- Settings Navigation -->
    <el-row :gutter="20">
      <el-col :xs="24" :sm="24" :md="6">
        <el-card class="settings-nav" shadow="never">
          <el-menu
            :default-active="activeSection"
            mode="vertical"
            @select="handleSectionChange"
          >
            <el-menu-item index="general">
              <el-icon><Setting /></el-icon>
              <span>General Settings</span>
            </el-menu-item>
            <el-menu-item index="company">
              <el-icon><OfficeBuilding /></el-icon>
              <span>Company Information</span>
            </el-menu-item>
            <el-menu-item index="attendance">
              <el-icon><Clock /></el-icon>
              <span>Attendance Settings</span>
            </el-menu-item>
            <el-menu-item index="work-schedules">
              <el-icon><Calendar /></el-icon>
              <span>Work Schedules</span>
            </el-menu-item>
            <el-menu-item index="salary-deduction">
              <el-icon><Money /></el-icon>
              <span>Salary Deduction Rules</span>
            </el-menu-item>
            <el-menu-item index="overtime">
              <el-icon><Timer /></el-icon>
              <span>Overtime Rules</span>
            </el-menu-item>
            <el-menu-item index="security">
              <el-icon><Lock /></el-icon>
              <span>Security & Privacy</span>
            </el-menu-item>
            <el-menu-item index="notifications">
              <el-icon><Bell /></el-icon>
              <span>Notifications</span>
            </el-menu-item>
            <el-menu-item index="integrations">
              <el-icon><Connection /></el-icon>
              <span>Integrations</span>
            </el-menu-item>
            <el-menu-item index="backup">
              <el-icon><FolderOpened /></el-icon>
              <span>Backup & Recovery</span>
            </el-menu-item>
            <el-menu-item index="audit">
              <el-icon><Document /></el-icon>
              <span>Audit Logs</span>
            </el-menu-item>
            <el-menu-item index="system">
              <el-icon><Monitor /></el-icon>
              <span>System Information</span>
            </el-menu-item>
          </el-menu>
        </el-card>
      </el-col>
      
      <el-col :xs="24" :sm="24" :md="18">
        <!-- General Settings -->
        <el-card v-if="activeSection === 'general'" class="settings-content" shadow="never">
          <template #header>
            <div class="section-header">
              <h2>General Settings</h2>
              <p>Configure basic system preferences and defaults</p>
            </div>
          </template>
          
          <el-form
            ref="generalFormRef"
            :model="generalSettings"
            :rules="generalRules"
            label-width="180px"
            @submit.prevent="saveGeneralSettings"
          >
            <el-form-item label="System Name" prop="system_name">
              <el-input
                v-model="generalSettings.system_name"
                placeholder="Enter system name"
              />
            </el-form-item>
            
            <el-form-item label="Default Language" prop="default_language">
              <el-select
                v-model="generalSettings.default_language"
                placeholder="Select default language"
                style="width: 100%"
              >
                <el-option label="English" value="en" />
                <el-option label="Spanish" value="es" />
                <el-option label="French" value="fr" />
                <el-option label="German" value="de" />
                <el-option label="Chinese" value="zh" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Default Timezone" prop="default_timezone">
              <el-select
                v-model="generalSettings.default_timezone"
                placeholder="Select default timezone"
                style="width: 100%"
                filterable
              >
                <el-option
                  v-for="tz in timezones"
                  :key="tz.value"
                  :label="tz.label"
                  :value="tz.value"
                />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Date Format" prop="date_format">
              <el-select
                v-model="generalSettings.date_format"
                placeholder="Select date format"
                style="width: 100%"
              >
                <el-option label="MM/DD/YYYY" value="MM/DD/YYYY" />
                <el-option label="DD/MM/YYYY" value="DD/MM/YYYY" />
                <el-option label="YYYY-MM-DD" value="YYYY-MM-DD" />
                <el-option label="DD-MM-YYYY" value="DD-MM-YYYY" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Time Format" prop="time_format">
              <el-radio-group v-model="generalSettings.time_format">
                <el-radio label="12">12 Hour (AM/PM)</el-radio>
                <el-radio label="24">24 Hour</el-radio>
              </el-radio-group>
            </el-form-item>
            
            <el-form-item label="Currency" prop="default_currency">
              <el-select
                v-model="generalSettings.default_currency"
                placeholder="Select default currency"
                style="width: 100%"
                filterable
              >
                <el-option label="USD - US Dollar" value="USD" />
                <el-option label="EUR - Euro" value="EUR" />
                <el-option label="GBP - British Pound" value="GBP" />
                <el-option label="JPY - Japanese Yen" value="JPY" />
                <el-option label="CAD - Canadian Dollar" value="CAD" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Working Days" prop="working_days">
              <el-checkbox-group v-model="generalSettings.working_days">
                <el-checkbox label="monday">Monday</el-checkbox>
                <el-checkbox label="tuesday">Tuesday</el-checkbox>
                <el-checkbox label="wednesday">Wednesday</el-checkbox>
                <el-checkbox label="thursday">Thursday</el-checkbox>
                <el-checkbox label="friday">Friday</el-checkbox>
                <el-checkbox label="saturday">Saturday</el-checkbox>
                <el-checkbox label="sunday">Sunday</el-checkbox>
              </el-checkbox-group>
            </el-form-item>
            
            <el-form-item label="Working Hours" prop="working_hours">
              <el-row :gutter="10">
                <el-col :span="12">
                  <el-time-picker
                    v-model="generalSettings.work_start_time"
                    placeholder="Start time"
                    format="HH:mm"
                    value-format="HH:mm"
                    style="width: 100%"
                  />
                </el-col>
                <el-col :span="12">
                  <el-time-picker
                    v-model="generalSettings.work_end_time"
                    placeholder="End time"
                    format="HH:mm"
                    value-format="HH:mm"
                    style="width: 100%"
                  />
                </el-col>
              </el-row>
            </el-form-item>
            
            <el-form-item label="Auto Logout" prop="auto_logout">
              <el-switch v-model="generalSettings.auto_logout" />
              <div class="form-help-text">
                Automatically log out users after a period of inactivity
              </div>
            </el-form-item>
            
            <el-form-item
              v-if="generalSettings.auto_logout"
              label="Logout Timeout"
              prop="logout_timeout"
            >
              <el-select
                v-model="generalSettings.logout_timeout"
                placeholder="Select timeout duration"
                style="width: 100%"
              >
                <el-option label="15 minutes" :value="15" />
                <el-option label="30 minutes" :value="30" />
                <el-option label="1 hour" :value="60" />
                <el-option label="2 hours" :value="120" />
                <el-option label="4 hours" :value="240" />
              </el-select>
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="saveGeneralSettings" :loading="saving">
                Save Changes
              </el-button>
              <el-button @click="resetGeneralSettings">Reset</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        
        <!-- Company Information -->
        <el-card v-if="activeSection === 'company'" class="settings-content" shadow="never">
          <template #header>
            <div class="section-header">
              <h2>Company Information</h2>
              <p>Manage your organization's details and branding</p>
            </div>
          </template>
          
          <el-form
            ref="companyFormRef"
            :model="companySettings"
            :rules="companyRules"
            label-width="180px"
            @submit.prevent="saveCompanySettings"
          >
            <el-form-item label="Company Logo" prop="logo">
              <div class="logo-upload">
                <el-upload
                  class="logo-uploader"
                  action="/api/upload/logo"
                  :show-file-list="false"
                  :on-success="handleLogoSuccess"
                  :before-upload="beforeLogoUpload"
                >
                  <img v-if="companySettings.logo" :src="companySettings.logo" class="logo" />
                  <el-icon v-else class="logo-uploader-icon"><Plus /></el-icon>
                </el-upload>
                <div class="logo-help">
                  <p>Upload company logo (PNG, JPG, max 2MB)</p>
                  <p>Recommended size: 200x80 pixels</p>
                </div>
              </div>
            </el-form-item>
            
            <el-form-item label="Company Name" prop="name">
              <el-input
                v-model="companySettings.name"
                placeholder="Enter company name"
              />
            </el-form-item>
            
            <el-form-item label="Legal Name" prop="legal_name">
              <el-input
                v-model="companySettings.legal_name"
                placeholder="Enter legal company name"
              />
            </el-form-item>
            
            <el-form-item label="Registration Number" prop="registration_number">
              <el-input
                v-model="companySettings.registration_number"
                placeholder="Enter company registration number"
              />
            </el-form-item>
            
            <el-form-item label="Tax ID" prop="tax_id">
              <el-input
                v-model="companySettings.tax_id"
                placeholder="Enter tax identification number"
              />
            </el-form-item>
            
            <el-form-item label="Industry" prop="industry">
              <el-select
                v-model="companySettings.industry"
                placeholder="Select industry"
                style="width: 100%"
                filterable
              >
                <el-option label="Technology" value="technology" />
                <el-option label="Healthcare" value="healthcare" />
                <el-option label="Finance" value="finance" />
                <el-option label="Education" value="education" />
                <el-option label="Manufacturing" value="manufacturing" />
                <el-option label="Retail" value="retail" />
                <el-option label="Consulting" value="consulting" />
                <el-option label="Other" value="other" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Company Size" prop="size">
              <el-select
                v-model="companySettings.size"
                placeholder="Select company size"
                style="width: 100%"
              >
                <el-option label="1-10 employees" value="1-10" />
                <el-option label="11-50 employees" value="11-50" />
                <el-option label="51-200 employees" value="51-200" />
                <el-option label="201-500 employees" value="201-500" />
                <el-option label="501-1000 employees" value="501-1000" />
                <el-option label="1000+ employees" value="1000+" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Address" prop="address">
              <el-input
                v-model="companySettings.address"
                type="textarea"
                :rows="3"
                placeholder="Enter company address"
              />
            </el-form-item>
            
            <el-row :gutter="20">
              <el-col :span="12">
                <el-form-item label="City" prop="city">
                  <el-input
                    v-model="companySettings.city"
                    placeholder="Enter city"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="State/Province" prop="state">
                  <el-input
                    v-model="companySettings.state"
                    placeholder="Enter state or province"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            
            <el-row :gutter="20">
              <el-col :span="12">
                <el-form-item label="Postal Code" prop="postal_code">
                  <el-input
                    v-model="companySettings.postal_code"
                    placeholder="Enter postal code"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Country" prop="country">
                  <el-select
                    v-model="companySettings.country"
                    placeholder="Select country"
                    style="width: 100%"
                    filterable
                  >
                    <el-option label="United States" value="US" />
                    <el-option label="Canada" value="CA" />
                    <el-option label="United Kingdom" value="GB" />
                    <el-option label="Germany" value="DE" />
                    <el-option label="France" value="FR" />
                    <el-option label="Australia" value="AU" />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            
            <el-row :gutter="20">
              <el-col :span="12">
                <el-form-item label="Phone" prop="phone">
                  <el-input
                    v-model="companySettings.phone"
                    placeholder="Enter phone number"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Email" prop="email">
                  <el-input
                    v-model="companySettings.email"
                    placeholder="Enter email address"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            
            <el-form-item label="Website" prop="website">
              <el-input
                v-model="companySettings.website"
                placeholder="Enter website URL"
              />
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="saveCompanySettings" :loading="saving">
                Save Changes
              </el-button>
              <el-button @click="resetCompanySettings">Reset</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        
        <!-- Attendance Settings -->
        <el-card v-if="activeSection === 'attendance'" class="settings-content" shadow="never">
          <template #header>
            <div class="section-header">
              <h2>Attendance Settings</h2>
              <p>Configure attendance tracking and validation rules</p>
            </div>
          </template>
          
          <el-form
            ref="attendanceFormRef"
            :model="attendanceSettings"
            label-width="220px"
            @submit.prevent="saveAttendanceSettings"
          >
            <el-divider content-position="left">Clock-in/Clock-out Rules</el-divider>
            
            <el-form-item label="Allow Early Clock-in">
              <el-switch v-model="attendanceSettings.allow_early_clockin" />
              <div class="form-help-text">
                Allow employees to clock in before their scheduled start time
              </div>
            </el-form-item>
            
            <el-form-item label="Early Clock-in Buffer (minutes)" v-if="attendanceSettings.allow_early_clockin">
              <el-input-number
                v-model="attendanceSettings.early_clockin_buffer"
                :min="0"
                :max="120"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Late Arrival Grace Period (minutes)">
              <el-input-number
                v-model="attendanceSettings.late_grace_period"
                :min="0"
                :max="60"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Auto Clock-out">
              <el-switch v-model="attendanceSettings.auto_clockout" />
              <div class="form-help-text">
                Automatically clock out employees at end of shift
              </div>
            </el-form-item>
            
            <el-form-item label="Auto Clock-out Time" v-if="attendanceSettings.auto_clockout">
              <el-time-picker
                v-model="attendanceSettings.auto_clockout_time"
                format="HH:mm"
                value-format="HH:mm"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-divider content-position="left">Break Time Settings</el-divider>
            
            <el-form-item label="Track Break Time">
              <el-switch v-model="attendanceSettings.track_break_time" />
            </el-form-item>
            
            <el-form-item label="Default Break Duration (minutes)" v-if="attendanceSettings.track_break_time">
              <el-input-number
                v-model="attendanceSettings.default_break_duration"
                :min="15"
                :max="120"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Maximum Break Duration (minutes)" v-if="attendanceSettings.track_break_time">
              <el-input-number
                v-model="attendanceSettings.max_break_duration"
                :min="30"
                :max="240"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-divider content-position="left">Location & Device Settings</el-divider>
            
            <el-form-item label="Require Location Verification">
              <el-switch v-model="attendanceSettings.require_location" />
              <div class="form-help-text">
                Require employees to be within specified location to clock in/out
              </div>
            </el-form-item>
            
            <el-form-item label="Location Radius (meters)" v-if="attendanceSettings.require_location">
              <el-input-number
                v-model="attendanceSettings.location_radius"
                :min="10"
                :max="1000"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Allow Multiple Device Login">
              <el-switch v-model="attendanceSettings.allow_multiple_devices" />
            </el-form-item>
            
            <el-form-item label="Require Photo Verification">
              <el-switch v-model="attendanceSettings.require_photo" />
              <div class="form-help-text">
                Require employees to take a photo when clocking in/out
              </div>
            </el-form-item>
            
            <el-divider content-position="left">Notification Settings</el-divider>
            
            <el-form-item label="Late Arrival Notifications">
              <el-switch v-model="attendanceSettings.notify_late_arrival" />
            </el-form-item>
            
            <el-form-item label="Early Departure Notifications">
              <el-switch v-model="attendanceSettings.notify_early_departure" />
            </el-form-item>
            
            <el-form-item label="Missed Clock-out Notifications">
              <el-switch v-model="attendanceSettings.notify_missed_clockout" />
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="saveAttendanceSettings" :loading="saving">
                Save Changes
              </el-button>
              <el-button @click="resetAttendanceSettings">Reset</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        
        <!-- Work Schedules -->
        <el-card v-if="activeSection === 'work-schedules'" class="settings-content" shadow="never">
          <template #header>
            <div class="section-header">
              <h2>Work Schedules</h2>
              <p>Manage work schedules and shift patterns</p>
            </div>
          </template>
          
          <div class="work-schedules-section">
            <div class="section-toolbar">
              <el-button type="primary" @click="showCreateScheduleDialog = true">
                <el-icon><Plus /></el-icon>
                Create Schedule
              </el-button>
            </div>
            
            <el-table :data="workSchedules" v-loading="loadingSchedules">
              <el-table-column prop="name" label="Schedule Name" />
              <el-table-column prop="type" label="Type">
                <template #default="scope">
                  <el-tag :type="getScheduleTypeColor(scope.row.type)">{{ scope.row.type }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="working_days" label="Working Days">
                <template #default="scope">
                  {{ scope.row.working_days?.join(', ') }}
                </template>
              </el-table-column>
              <el-table-column prop="start_time" label="Start Time" />
              <el-table-column prop="end_time" label="End Time" />
              <el-table-column prop="is_default" label="Default">
                <template #default="scope">
                  <el-tag v-if="scope.row.is_default" type="success">Default</el-tag>
                </template>
              </el-table-column>
              <el-table-column label="Actions" width="200">
                <template #default="scope">
                  <el-button size="small" @click="editSchedule(scope.row)">
                    <el-icon><Edit /></el-icon>
                    Edit
                  </el-button>
                  <el-button 
                    size="small" 
                    type="danger" 
                    @click="deleteSchedule(scope.row)"
                    :disabled="scope.row.is_default"
                  >
                    <el-icon><Delete /></el-icon>
                    Delete
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </el-card>
        
        <!-- Salary Deduction Rules -->
        <el-card v-if="activeSection === 'salary-deduction'" class="settings-content" shadow="never">
          <template #header>
            <div class="section-header">
              <h2>Salary Deduction Rules</h2>
              <p>Configure automatic salary deductions for attendance violations</p>
            </div>
          </template>
          
          <el-form
            ref="deductionFormRef"
            :model="deductionSettings"
            label-width="250px"
            @submit.prevent="saveDeductionSettings"
          >
            <el-divider content-position="left">Late Arrival Deductions</el-divider>
            
            <el-form-item label="Enable Late Arrival Deductions">
              <el-switch v-model="deductionSettings.enable_late_deductions" />
            </el-form-item>
            
            <el-form-item label="Deduction Type" v-if="deductionSettings.enable_late_deductions">
              <el-radio-group v-model="deductionSettings.late_deduction_type">
                <el-radio label="fixed">Fixed Amount</el-radio>
                <el-radio label="hourly">Hourly Rate</el-radio>
                <el-radio label="percentage">Percentage of Daily Salary</el-radio>
              </el-radio-group>
            </el-form-item>
            
            <el-form-item 
              label="Deduction Amount" 
              v-if="deductionSettings.enable_late_deductions && deductionSettings.late_deduction_type === 'fixed'"
            >
              <el-input-number
                v-model="deductionSettings.late_fixed_amount"
                :min="0"
                :precision="2"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item 
              label="Deduction Percentage (%)" 
              v-if="deductionSettings.enable_late_deductions && deductionSettings.late_deduction_type === 'percentage'"
            >
              <el-input-number
                v-model="deductionSettings.late_percentage"
                :min="0"
                :max="100"
                :precision="2"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-divider content-position="left">Absence Deductions</el-divider>
            
            <el-form-item label="Enable Absence Deductions">
              <el-switch v-model="deductionSettings.enable_absence_deductions" />
            </el-form-item>
            
            <el-form-item label="Full Day Absence Deduction" v-if="deductionSettings.enable_absence_deductions">
              <el-radio-group v-model="deductionSettings.absence_deduction_type">
                <el-radio label="daily_salary">Full Daily Salary</el-radio>
                <el-radio label="fixed">Fixed Amount</el-radio>
              </el-radio-group>
            </el-form-item>
            
            <el-form-item 
              label="Fixed Absence Amount" 
              v-if="deductionSettings.enable_absence_deductions && deductionSettings.absence_deduction_type === 'fixed'"
            >
              <el-input-number
                v-model="deductionSettings.absence_fixed_amount"
                :min="0"
                :precision="2"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-divider content-position="left">Early Departure Deductions</el-divider>
            
            <el-form-item label="Enable Early Departure Deductions">
              <el-switch v-model="deductionSettings.enable_early_departure_deductions" />
            </el-form-item>
            
            <el-form-item label="Minimum Early Departure (minutes)" v-if="deductionSettings.enable_early_departure_deductions">
              <el-input-number
                v-model="deductionSettings.early_departure_threshold"
                :min="1"
                :max="480"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Early Departure Deduction Type" v-if="deductionSettings.enable_early_departure_deductions">
              <el-radio-group v-model="deductionSettings.early_departure_type">
                <el-radio label="hourly">Hourly Rate</el-radio>
                <el-radio label="fixed">Fixed Amount</el-radio>
              </el-radio-group>
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="saveDeductionSettings" :loading="saving">
                Save Changes
              </el-button>
              <el-button @click="resetDeductionSettings">Reset</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        
        <!-- Overtime Rules -->
        <el-card v-if="activeSection === 'overtime'" class="settings-content" shadow="never">
          <template #header>
            <div class="section-header">
              <h2>Overtime Rules</h2>
              <p>Configure overtime calculation and approval rules</p>
            </div>
          </template>
          
          <el-form
            ref="overtimeFormRef"
            :model="overtimeSettings"
            label-width="250px"
            @submit.prevent="saveOvertimeSettings"
          >
            <el-divider content-position="left">Overtime Calculation</el-divider>
            
            <el-form-item label="Enable Overtime Tracking">
              <el-switch v-model="overtimeSettings.enable_overtime" />
            </el-form-item>
            
            <el-form-item label="Daily Overtime Threshold (hours)" v-if="overtimeSettings.enable_overtime">
              <el-input-number
                v-model="overtimeSettings.daily_overtime_threshold"
                :min="6"
                :max="12"
                :precision="1"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Weekly Overtime Threshold (hours)" v-if="overtimeSettings.enable_overtime">
              <el-input-number
                v-model="overtimeSettings.weekly_overtime_threshold"
                :min="35"
                :max="60"
                :precision="1"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Overtime Rate Multiplier" v-if="overtimeSettings.enable_overtime">
              <el-input-number
                v-model="overtimeSettings.overtime_rate_multiplier"
                :min="1"
                :max="3"
                :precision="2"
                style="width: 200px"
              />
              <div class="form-help-text">
                Multiplier for overtime pay (e.g., 1.5 for time and a half)
              </div>
            </el-form-item>
            
            <el-divider content-position="left">Overtime Approval</el-divider>
            
            <el-form-item label="Require Overtime Pre-approval">
              <el-switch v-model="overtimeSettings.require_preapproval" />
            </el-form-item>
            
            <el-form-item label="Auto-approve Overtime Under (hours)" v-if="!overtimeSettings.require_preapproval">
              <el-input-number
                v-model="overtimeSettings.auto_approve_threshold"
                :min="0"
                :max="4"
                :precision="1"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Maximum Daily Overtime (hours)">
              <el-input-number
                v-model="overtimeSettings.max_daily_overtime"
                :min="1"
                :max="8"
                :precision="1"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Maximum Weekly Overtime (hours)">
              <el-input-number
                v-model="overtimeSettings.max_weekly_overtime"
                :min="5"
                :max="20"
                :precision="1"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-divider content-position="left">Weekend & Holiday Overtime</el-divider>
            
            <el-form-item label="Weekend Overtime Rate Multiplier">
              <el-input-number
                v-model="overtimeSettings.weekend_rate_multiplier"
                :min="1"
                :max="3"
                :precision="2"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Holiday Overtime Rate Multiplier">
              <el-input-number
                v-model="overtimeSettings.holiday_rate_multiplier"
                :min="1"
                :max="3"
                :precision="2"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="saveOvertimeSettings" :loading="saving">
                Save Changes
              </el-button>
              <el-button @click="resetOvertimeSettings">Reset</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        
        <!-- Security Settings -->
        <el-card v-if="activeSection === 'security'" class="settings-content" shadow="never">
          <template #header>
            <div class="section-header">
              <h2>Security & Privacy</h2>
              <p>Configure security policies and privacy settings</p>
            </div>
          </template>
          
          <el-form
            ref="securityFormRef"
            :model="securitySettings"
            label-width="200px"
            @submit.prevent="saveSecuritySettings"
          >
            <el-divider content-position="left">Password Policy</el-divider>
            
            <el-form-item label="Minimum Password Length">
              <el-input-number
                v-model="securitySettings.min_password_length"
                :min="6"
                :max="20"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Require Uppercase">
              <el-switch v-model="securitySettings.require_uppercase" />
            </el-form-item>
            
            <el-form-item label="Require Lowercase">
              <el-switch v-model="securitySettings.require_lowercase" />
            </el-form-item>
            
            <el-form-item label="Require Numbers">
              <el-switch v-model="securitySettings.require_numbers" />
            </el-form-item>
            
            <el-form-item label="Require Special Characters">
              <el-switch v-model="securitySettings.require_special_chars" />
            </el-form-item>
            
            <el-form-item label="Password Expiry (days)">
              <el-input-number
                v-model="securitySettings.password_expiry_days"
                :min="0"
                :max="365"
                style="width: 200px"
              />
              <div class="form-help-text">
                Set to 0 to disable password expiry
              </div>
            </el-form-item>
            
            <el-divider content-position="left">Login Security</el-divider>
            
            <el-form-item label="Two-Factor Authentication">
              <el-switch v-model="securitySettings.enable_2fa" />
              <div class="form-help-text">
                Require users to enable 2FA for enhanced security
              </div>
            </el-form-item>
            
            <el-form-item label="Max Login Attempts">
              <el-input-number
                v-model="securitySettings.max_login_attempts"
                :min="3"
                :max="10"
                style="width: 200px"
              />
            </el-form-item>
            
            <el-form-item label="Account Lockout Duration">
              <el-select
                v-model="securitySettings.lockout_duration"
                style="width: 200px"
              >
                <el-option label="15 minutes" :value="15" />
                <el-option label="30 minutes" :value="30" />
                <el-option label="1 hour" :value="60" />
                <el-option label="2 hours" :value="120" />
                <el-option label="24 hours" :value="1440" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="IP Whitelist">
              <el-switch v-model="securitySettings.enable_ip_whitelist" />
              <div class="form-help-text">
                Restrict access to specific IP addresses
              </div>
            </el-form-item>
            
            <el-form-item
              v-if="securitySettings.enable_ip_whitelist"
              label="Allowed IP Addresses"
            >
              <el-input
                v-model="securitySettings.allowed_ips"
                type="textarea"
                :rows="3"
                placeholder="Enter IP addresses, one per line"
              />
            </el-form-item>
            
            <el-divider content-position="left">Data Privacy</el-divider>
            
            <el-form-item label="Data Retention Period">
              <el-select
                v-model="securitySettings.data_retention_period"
                style="width: 200px"
              >
                <el-option label="1 year" value="1y" />
                <el-option label="2 years" value="2y" />
                <el-option label="3 years" value="3y" />
                <el-option label="5 years" value="5y" />
                <el-option label="7 years" value="7y" />
                <el-option label="Indefinite" value="indefinite" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Audit Logging">
              <el-switch v-model="securitySettings.enable_audit_logging" />
              <div class="form-help-text">
                Log all user actions for security auditing
              </div>
            </el-form-item>
            
            <el-form-item label="Data Encryption">
              <el-switch v-model="securitySettings.enable_encryption" />
              <div class="form-help-text">
                Encrypt sensitive data at rest
              </div>
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="saveSecuritySettings" :loading="saving">
                Save Changes
              </el-button>
              <el-button @click="resetSecuritySettings">Reset</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        
        <!-- System Information -->
        <el-card v-if="activeSection === 'system'" class="settings-content" shadow="never">
          <template #header>
            <div class="section-header">
              <h2>System Information</h2>
              <p>View system status and performance metrics</p>
            </div>
          </template>
          
          <div class="system-info">
            <el-row :gutter="20">
              <el-col :xs="24" :sm="12" :md="8">
                <div class="info-card">
                  <div class="info-icon system">
                    <el-icon><Monitor /></el-icon>
                  </div>
                  <div class="info-content">
                    <h3>System Version</h3>
                    <p>{{ systemInfo.version }}</p>
                  </div>
                </div>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8">
                <div class="info-card">
                  <div class="info-icon database">
                    <el-icon><Coin /></el-icon>
                  </div>
                  <div class="info-content">
                    <h3>Database</h3>
                    <p>{{ systemInfo.database_type }} {{ systemInfo.database_version }}</p>
                  </div>
                </div>
              </el-col>
              <el-col :xs="24" :sm="12" :md="8">
                <div class="info-card">
                  <div class="info-icon uptime">
                    <el-icon><Clock /></el-icon>
                  </div>
                  <div class="info-content">
                    <h3>Uptime</h3>
                    <p>{{ systemInfo.uptime }}</p>
                  </div>
                </div>
              </el-col>
            </el-row>
            
            <el-row :gutter="20" style="margin-top: 20px">
              <el-col :xs="24" :sm="12">
                <el-card class="metric-card" shadow="hover">
                  <div class="metric-header">
                    <h3>Server Resources</h3>
                  </div>
                  <div class="metric-content">
                    <div class="metric-item">
                      <span class="metric-label">CPU Usage</span>
                      <el-progress
                        :percentage="systemInfo.cpu_usage"
                        :color="getProgressColor(systemInfo.cpu_usage)"
                      />
                    </div>
                    <div class="metric-item">
                      <span class="metric-label">Memory Usage</span>
                      <el-progress
                        :percentage="systemInfo.memory_usage"
                        :color="getProgressColor(systemInfo.memory_usage)"
                      />
                    </div>
                    <div class="metric-item">
                      <span class="metric-label">Disk Usage</span>
                      <el-progress
                        :percentage="systemInfo.disk_usage"
                        :color="getProgressColor(systemInfo.disk_usage)"
                      />
                    </div>
                  </div>
                </el-card>
              </el-col>
              <el-col :xs="24" :sm="12">
                <el-card class="metric-card" shadow="hover">
                  <div class="metric-header">
                    <h3>System Health</h3>
                  </div>
                  <div class="metric-content">
                    <div class="health-item">
                      <span class="health-label">Database Connection</span>
                      <el-tag :type="systemInfo.database_status === 'connected' ? 'success' : 'danger'">
                        {{ systemInfo.database_status }}
                      </el-tag>
                    </div>
                    <div class="health-item">
                      <span class="health-label">Cache Service</span>
                      <el-tag :type="systemInfo.cache_status === 'running' ? 'success' : 'danger'">
                        {{ systemInfo.cache_status }}
                      </el-tag>
                    </div>
                    <div class="health-item">
                      <span class="health-label">Email Service</span>
                      <el-tag :type="systemInfo.email_status === 'running' ? 'success' : 'danger'">
                        {{ systemInfo.email_status }}
                      </el-tag>
                    </div>
                    <div class="health-item">
                      <span class="health-label">Background Jobs</span>
                      <el-tag :type="systemInfo.queue_status === 'running' ? 'success' : 'danger'">
                        {{ systemInfo.queue_status }}
                      </el-tag>
                    </div>
                  </div>
                </el-card>
              </el-col>
            </el-row>
            
            <el-row :gutter="20" style="margin-top: 20px">
              <el-col :span="24">
                <el-card class="metric-card" shadow="hover">
                  <div class="metric-header">
                    <h3>Recent Activity</h3>
                    <el-button size="small" @click="refreshSystemInfo">
                      <el-icon><Refresh /></el-icon>
                      Refresh
                    </el-button>
                  </div>
                  <div class="activity-content">
                    <el-table :data="systemInfo.recent_activities" style="width: 100%">
                      <el-table-column prop="timestamp" label="Time" width="180">
                        <template #default="{ row }">
                          {{ formatDateTime(row.timestamp) }}
                        </template>
                      </el-table-column>
                      <el-table-column prop="type" label="Type" width="120">
                        <template #default="{ row }">
                          <el-tag size="small" :type="getActivityType(row.type)">
                            {{ row.type }}
                          </el-tag>
                        </template>
                      </el-table-column>
                      <el-table-column prop="description" label="Description" />
                      <el-table-column prop="user" label="User" width="150" />
                    </el-table>
                  </div>
                </el-card>
              </el-col>
            </el-row>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { ElMessage } from 'element-plus'
import {
  Setting,
  OfficeBuilding,
  Lock,
  Bell,
  Connection,
  FolderOpened,
  Document,
  Monitor,
  Plus,
  Clock,
  Coin,
  Refresh,
  Edit,
  Delete
} from '@element-plus/icons-vue'

export default {
  name: 'SettingsList',
  components: {
    Setting,
    OfficeBuilding,
    Lock,
    Bell,
    Connection,
    FolderOpened,
    Document,
    Monitor,
    Plus,
    Clock,
    Coin,
    Refresh,
    Edit,
    Delete
  },
  setup() {
    const store = useStore()
    const activeSection = ref('general')
    const saving = ref(false)
    const generalFormRef = ref()
    const companyFormRef = ref()
    const securityFormRef = ref()
    
    const generalSettings = reactive({
      system_name: '',
      default_language: 'en',
      default_timezone: '',
      date_format: 'MM/DD/YYYY',
      time_format: '12',
      default_currency: 'USD',
      working_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
      work_start_time: '09:00',
      work_end_time: '17:00',
      auto_logout: true,
      logout_timeout: 30
    })
    
    const companySettings = reactive({
      logo: '',
      name: '',
      legal_name: '',
      registration_number: '',
      tax_id: '',
      industry: '',
      size: '',
      address: '',
      city: '',
      state: '',
      postal_code: '',
      country: '',
      phone: '',
      email: '',
      website: ''
    })
    
    const securitySettings = reactive({
      min_password_length: 8,
      require_uppercase: true,
      require_lowercase: true,
      require_numbers: true,
      require_special_chars: false,
      password_expiry_days: 90,
      enable_2fa: false,
      max_login_attempts: 5,
      lockout_duration: 30,
      enable_ip_whitelist: false,
      allowed_ips: '',
      data_retention_period: '3y',
      enable_audit_logging: true,
      enable_encryption: true
    })
    
    const attendanceSettings = reactive({
      allow_early_clockin: false,
      early_clockin_buffer: 15,
      late_grace_period: 5,
      auto_clockout: false,
      auto_clockout_time: '18:00',
      track_break_time: false,
      default_break_duration: 60,
      max_break_duration: 120,
      require_location: false,
      location_radius: 100,
      allow_multiple_devices: false,
      require_photo: false,
      notify_late_arrival: true,
      notify_early_departure: true,
      notify_missed_clockout: true
    })
    
    const deductionSettings = reactive({
      enable_late_deductions: false,
      late_deduction_type: 'fixed',
      late_fixed_amount: 0,
      late_percentage: 0,
      enable_absence_deductions: false,
      absence_deduction_type: 'daily_salary',
      absence_fixed_amount: 0,
      enable_early_departure_deductions: false,
      early_departure_threshold: 30,
      early_departure_type: 'hourly'
    })
    
    const overtimeSettings = reactive({
      enable_overtime: false,
      daily_overtime_threshold: 8,
      weekly_overtime_threshold: 40,
      overtime_rate_multiplier: 1.5,
      require_preapproval: false,
      auto_approve_threshold: 2,
      max_daily_overtime: 4,
      max_weekly_overtime: 12,
      weekend_rate_multiplier: 2.0,
      holiday_rate_multiplier: 2.5
    })
    
    const workSchedules = ref([])
    const loadingSchedules = ref(false)
    const showCreateScheduleDialog = ref(false)
    
    const attendanceFormRef = ref()
    const deductionFormRef = ref()
    const overtimeFormRef = ref()
    
    const generalRules = {
      system_name: [
        { required: true, message: 'Please enter system name', trigger: 'blur' }
      ],
      default_language: [
        { required: true, message: 'Please select default language', trigger: 'change' }
      ],
      default_timezone: [
        { required: true, message: 'Please select default timezone', trigger: 'change' }
      ]
    }
    
    const companyRules = {
      name: [
        { required: true, message: 'Please enter company name', trigger: 'blur' }
      ],
      email: [
        { type: 'email', message: 'Please enter valid email address', trigger: 'blur' }
      ]
    }
    
    const timezones = [
      { label: 'UTC', value: 'UTC' },
      { label: 'Eastern Time (ET)', value: 'America/New_York' },
      { label: 'Central Time (CT)', value: 'America/Chicago' },
      { label: 'Mountain Time (MT)', value: 'America/Denver' },
      { label: 'Pacific Time (PT)', value: 'America/Los_Angeles' },
      { label: 'Greenwich Mean Time (GMT)', value: 'Europe/London' },
      { label: 'Central European Time (CET)', value: 'Europe/Paris' },
      { label: 'Japan Standard Time (JST)', value: 'Asia/Tokyo' },
      { label: 'Australian Eastern Time (AET)', value: 'Australia/Sydney' }
    ]
    
    const systemInfo = computed(() => store.getters['settings/systemInfo'])
    const settings = computed(() => store.getters['settings/settings'])
    
    const formatDateTime = (datetime) => {
      if (!datetime) return '-'
      return new Date(datetime).toLocaleString()
    }
    
    const getProgressColor = (percentage) => {
      if (percentage < 50) return '#67C23A'
      if (percentage < 80) return '#E6A23C'
      return '#F56C6C'
    }
    
    const getActivityType = (type) => {
      const typeMap = {
        login: 'success',
        logout: 'info',
        error: 'danger',
        warning: 'warning',
        info: 'primary'
      }
      return typeMap[type] || 'info'
    }
    
    const handleSectionChange = (section) => {
      activeSection.value = section
    }
    
    const handleLogoSuccess = (response, file) => {
      companySettings.logo = URL.createObjectURL(file.raw)
    }
    
    const beforeLogoUpload = (file) => {
      const isImage = file.type === 'image/jpeg' || file.type === 'image/png'
      const isLt2M = file.size / 1024 / 1024 < 2
      
      if (!isImage) {
        ElMessage.error('Logo must be JPG or PNG format!')
      }
      if (!isLt2M) {
        ElMessage.error('Logo size must be smaller than 2MB!')
      }
      return isImage && isLt2M
    }
    
    const saveGeneralSettings = async () => {
      try {
        const valid = await generalFormRef.value.validate()
        if (!valid) return
        
        saving.value = true
        
        const result = await store.dispatch('settings/updateSettings', {
          section: 'general',
          data: generalSettings
        })
        
        if (result.success) {
          ElMessage.success('General settings saved successfully')
        } else {
          ElMessage.error(result.message || 'Failed to save settings')
        }
      } catch (error) {
        console.error('Save general settings error:', error)
        ElMessage.error('An error occurred while saving settings')
      } finally {
        saving.value = false
      }
    }
    
    const saveCompanySettings = async () => {
      try {
        const valid = await companyFormRef.value.validate()
        if (!valid) return
        
        saving.value = true
        
        const result = await store.dispatch('settings/updateSettings', {
          section: 'company',
          data: companySettings
        })
        
        if (result.success) {
          ElMessage.success('Company settings saved successfully')
        } else {
          ElMessage.error(result.message || 'Failed to save settings')
        }
      } catch (error) {
        console.error('Save company settings error:', error)
        ElMessage.error('An error occurred while saving settings')
      } finally {
        saving.value = false
      }
    }
    
    const saveSecuritySettings = async () => {
      try {
        saving.value = true
        
        const result = await store.dispatch('settings/updateSettings', {
          section: 'security',
          data: securitySettings
        })
        
        if (result.success) {
          ElMessage.success('Security settings saved successfully')
        } else {
          ElMessage.error(result.message || 'Failed to save settings')
        }
      } catch (error) {
        console.error('Save security settings error:', error)
        ElMessage.error('An error occurred while saving settings')
      } finally {
        saving.value = false
      }
    }
    
    const resetGeneralSettings = () => {
      if (settings.value.general) {
        Object.assign(generalSettings, settings.value.general)
      }
    }
    
    const resetCompanySettings = () => {
      if (settings.value.company) {
        Object.assign(companySettings, settings.value.company)
      }
    }
    
    const resetSecuritySettings = () => {
      if (settings.value.security) {
        Object.assign(securitySettings, settings.value.security)
      }
    }
    
    const saveAttendanceSettings = async () => {
      try {
        const valid = await attendanceFormRef.value.validate()
        if (!valid) return
        
        saving.value = true
        
        const result = await store.dispatch('settings/updateSettings', {
          section: 'attendance',
          data: attendanceSettings
        })
        
        if (result.success) {
          ElMessage.success('Attendance settings saved successfully')
        } else {
          ElMessage.error(result.message || 'Failed to save settings')
        }
      } catch (error) {
        console.error('Save attendance settings error:', error)
        ElMessage.error('An error occurred while saving settings')
      } finally {
        saving.value = false
      }
    }
    
    const saveDeductionSettings = async () => {
      try {
        const valid = await deductionFormRef.value.validate()
        if (!valid) return
        
        saving.value = true
        
        const result = await store.dispatch('settings/updateSettings', {
          section: 'salary_deduction',
          data: deductionSettings
        })
        
        if (result.success) {
          ElMessage.success('Salary deduction settings saved successfully')
        } else {
          ElMessage.error(result.message || 'Failed to save settings')
        }
      } catch (error) {
        console.error('Save deduction settings error:', error)
        ElMessage.error('An error occurred while saving settings')
      } finally {
        saving.value = false
      }
    }
    
    const saveOvertimeSettings = async () => {
      try {
        const valid = await overtimeFormRef.value.validate()
        if (!valid) return
        
        saving.value = true
        
        const result = await store.dispatch('settings/updateSettings', {
          section: 'overtime',
          data: overtimeSettings
        })
        
        if (result.success) {
          ElMessage.success('Overtime settings saved successfully')
        } else {
          ElMessage.error(result.message || 'Failed to save settings')
        }
      } catch (error) {
        console.error('Save overtime settings error:', error)
        ElMessage.error('An error occurred while saving settings')
      } finally {
        saving.value = false
      }
    }
    
    const resetAttendanceSettings = () => {
      if (settings.value.attendance) {
        Object.assign(attendanceSettings, settings.value.attendance)
      }
    }
    
    const resetDeductionSettings = () => {
      if (settings.value.salary_deduction) {
        Object.assign(deductionSettings, settings.value.salary_deduction)
      }
    }
    
    const resetOvertimeSettings = () => {
      if (settings.value.overtime) {
        Object.assign(overtimeSettings, settings.value.overtime)
      }
    }
    
    const getScheduleTypeColor = (type) => {
      const typeMap = {
        'fixed': 'primary',
        'flexible': 'success',
        'shift': 'warning',
        'remote': 'info'
      }
      return typeMap[type] || 'info'
    }
    
    const editSchedule = (schedule) => {
      // TODO: Implement schedule editing
      console.log('Edit schedule:', schedule)
    }
    
    const deleteSchedule = (schedule) => {
      // TODO: Implement schedule deletion
      console.log('Delete schedule:', schedule)
    }
    
    const loadWorkSchedules = async () => {
      try {
        loadingSchedules.value = true
        // TODO: Load work schedules from API
        workSchedules.value = [
          {
            id: 1,
            name: 'Standard Business Hours',
            type: 'fixed',
            working_days: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            start_time: '09:00',
            end_time: '17:00',
            is_default: true
          },
          {
            id: 2,
            name: 'Flexible Hours',
            type: 'flexible',
            working_days: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            start_time: '08:00',
            end_time: '18:00',
            is_default: false
          }
        ]
      } catch (error) {
        console.error('Load work schedules error:', error)
        ElMessage.error('Failed to load work schedules')
      } finally {
        loadingSchedules.value = false
      }
    }
    
    const refreshSystemInfo = async () => {
      await store.dispatch('settings/fetchSystemInfo')
    }
    
    const loadSettings = () => {
      if (settings.value.general) {
        Object.assign(generalSettings, settings.value.general)
      }
      if (settings.value.company) {
        Object.assign(companySettings, settings.value.company)
      }
      if (settings.value.security) {
        Object.assign(securitySettings, settings.value.security)
      }
      if (settings.value.attendance) {
        Object.assign(attendanceSettings, settings.value.attendance)
      }
      if (settings.value.salary_deduction) {
        Object.assign(deductionSettings, settings.value.salary_deduction)
      }
      if (settings.value.overtime) {
        Object.assign(overtimeSettings, settings.value.overtime)
      }
    }
    
    onMounted(async () => {
      await Promise.all([
        store.dispatch('settings/fetchSettings'),
        store.dispatch('settings/fetchSystemInfo')
      ])
      loadSettings()
      await loadWorkSchedules()
    })
    
    return {
      activeSection,
      saving,
      generalFormRef,
      companyFormRef,
      securityFormRef,
      attendanceFormRef,
      deductionFormRef,
      overtimeFormRef,
      generalSettings,
      companySettings,
      securitySettings,
      attendanceSettings,
      deductionSettings,
      overtimeSettings,
      workSchedules,
      loadingSchedules,
      showCreateScheduleDialog,
      generalRules,
      companyRules,
      timezones,
      systemInfo,
      formatDateTime,
      getProgressColor,
      getActivityType,
      getScheduleTypeColor,
      handleSectionChange,
      handleLogoSuccess,
      beforeLogoUpload,
      saveGeneralSettings,
      saveCompanySettings,
      saveSecuritySettings,
      saveAttendanceSettings,
      saveDeductionSettings,
      saveOvertimeSettings,
      resetGeneralSettings,
      resetCompanySettings,
      resetSecuritySettings,
      resetAttendanceSettings,
      resetDeductionSettings,
      resetOvertimeSettings,
      editSchedule,
      deleteSchedule,
      loadWorkSchedules,
      refreshSystemInfo
    }
  }
}
</script>

<style scoped>
.settings-list {
  padding: 20px;
}

.page-header {
  margin-bottom: 24px;
}

.header-content h1 {
  margin: 0 0 8px 0;
  color: #303133;
  font-size: 24px;
  font-weight: 600;
}

.header-content p {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.settings-nav {
  border: 1px solid #EBEEF5;
}

.settings-content {
  border: 1px solid #EBEEF5;
}

.section-header {
  margin-bottom: 20px;
}

.section-header h2 {
  margin: 0 0 8px 0;
  color: #303133;
  font-size: 18px;
  font-weight: 600;
}

.section-header p {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.form-help-text {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.logo-upload {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.logo-uploader {
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: border-color 0.3s;
}

.logo-uploader:hover {
  border-color: #409EFF;
}

.logo-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 120px;
  height: 80px;
  line-height: 80px;
  text-align: center;
}

.logo {
  width: 120px;
  height: 80px;
  display: block;
  object-fit: contain;
}

.logo-help {
  flex: 1;
}

.logo-help p {
  margin: 0 0 4px 0;
  font-size: 12px;
  color: #909399;
}

.system-info {
  padding: 20px 0;
}

.info-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background-color: #F5F7FA;
  border-radius: 8px;
  height: 100%;
}

.info-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
  flex-shrink: 0;
}

.info-icon.system {
  background-color: #409EFF;
}

.info-icon.database {
  background-color: #67C23A;
}

.info-icon.uptime {
  background-color: #E6A23C;
}

.info-content h3 {
  margin: 0 0 4px 0;
  color: #303133;
  font-size: 14px;
  font-weight: 600;
}

.info-content p {
  margin: 0;
  color: #606266;
  font-size: 16px;
  font-weight: 500;
}

.metric-card {
  border: 1px solid #EBEEF5;
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.metric-header h3 {
  margin: 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.metric-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.metric-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.metric-label {
  font-size: 14px;
  color: #606266;
  font-weight: 500;
}

.health-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #F5F7FA;
}

.health-item:last-child {
  border-bottom: none;
}

.health-label {
  font-size: 14px;
  color: #606266;
}

.activity-content {
  margin-top: 16px;
}

.work-schedules-section {
  margin-top: 20px;
}

.section-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-toolbar h3 {
  margin: 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

:deep(.el-menu) {
  border-right: none;
}

:deep(.el-menu-item) {
  height: 48px;
  line-height: 48px;
  margin-bottom: 4px;
  border-radius: 6px;
}

:deep(.el-menu-item.is-active) {
  background-color: #ECF5FF;
  color: #409EFF;
}

:deep(.el-menu-item:hover) {
  background-color: #F5F7FA;
}

:deep(.el-card__header) {
  padding: 20px 20px 0 20px;
  border-bottom: none;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.el-divider__text) {
  font-weight: 600;
  color: #303133;
}

:deep(.el-progress-bar__outer) {
  background-color: #F5F7FA;
}

@media (max-width: 768px) {
  .settings-list {
    padding: 15px;
  }
  
  .logo-upload {
    flex-direction: column;
    gap: 12px;
  }
  
  .info-card {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
  
  .metric-header {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
  
  .health-item {
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
  }
}
</style>