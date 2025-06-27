<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo">
          <el-icon :size="40" color="#409EFF">
            <User />
          </el-icon>
        </div>
        <h1>HRMS Login</h1>
        <p>Welcome back! Please sign in to your account.</p>
      </div>
      
      <el-form
        ref="loginFormRef"
        :model="loginForm"
        :rules="loginRules"
        class="login-form"
        @submit.prevent="handleLogin"
      >
        <el-form-item prop="email">
          <el-input
            v-model="loginForm.email"
            type="email"
            placeholder="Email Address"
            size="large"
            :prefix-icon="Message"
            autocomplete="email"
          />
        </el-form-item>
        
        <el-form-item prop="password">
          <el-input
            v-model="loginForm.password"
            type="password"
            placeholder="Password"
            size="large"
            :prefix-icon="Lock"
            show-password
            autocomplete="current-password"
            @keyup.enter="handleLogin"
          />
        </el-form-item>
        
        <el-form-item>
          <div class="login-options">
            <el-checkbox v-model="loginForm.remember">Remember me</el-checkbox>
            <el-link type="primary" @click="showForgotPassword = true">
              Forgot password?
            </el-link>
          </div>
        </el-form-item>
        
        <el-form-item>
          <el-button
            type="primary"
            size="large"
            class="login-button"
            :loading="loading"
            @click="handleLogin"
          >
            Sign In
          </el-button>
        </el-form-item>
      </el-form>
      
      <div class="login-footer">
        <p>Don't have an account? Contact your HR administrator.</p>
      </div>
    </div>
    
    <!-- Forgot Password Dialog -->
    <el-dialog
      v-model="showForgotPassword"
      title="Reset Password"
      width="400px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="forgotPasswordFormRef"
        :model="forgotPasswordForm"
        :rules="forgotPasswordRules"
      >
        <el-form-item prop="email">
          <el-input
            v-model="forgotPasswordForm.email"
            type="email"
            placeholder="Enter your email address"
            :prefix-icon="Message"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showForgotPassword = false">Cancel</el-button>
          <el-button
            type="primary"
            :loading="forgotPasswordLoading"
            @click="handleForgotPassword"
          >
            Send Reset Link
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { User, Message, Lock } from '@element-plus/icons-vue'

export default {
  name: 'Login',
  components: {
    User,
    Message,
    Lock
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    
    const loginFormRef = ref()
    const forgotPasswordFormRef = ref()
    const showForgotPassword = ref(false)
    const forgotPasswordLoading = ref(false)
    
    const loginForm = reactive({
      email: '',
      password: '',
      remember: false
    })
    
    const forgotPasswordForm = reactive({
      email: ''
    })
    
    const loginRules = {
      email: [
        { required: true, message: 'Please enter your email', trigger: 'blur' },
        { type: 'email', message: 'Please enter a valid email', trigger: 'blur' }
      ],
      password: [
        { required: true, message: 'Please enter your password', trigger: 'blur' },
        { min: 6, message: 'Password must be at least 6 characters', trigger: 'blur' }
      ]
    }
    
    const forgotPasswordRules = {
      email: [
        { required: true, message: 'Please enter your email', trigger: 'blur' },
        { type: 'email', message: 'Please enter a valid email', trigger: 'blur' }
      ]
    }
    
    const loading = computed(() => store.getters['auth/loginLoading'])
    
    const handleLogin = async () => {
      try {
        const valid = await loginFormRef.value.validate()
        if (!valid) return
        
        const result = await store.dispatch('auth/login', {
          email: loginForm.email,
          password: loginForm.password,
          remember: loginForm.remember
        })
        
        if (result.success) {
          ElMessage.success('Login successful!')
          router.push('/dashboard')
        } else {
          ElMessage.error(result.message || 'Login failed')
        }
      } catch (error) {
        console.error('Login error:', error)
        ElMessage.error('An error occurred during login')
      }
    }
    
    const handleForgotPassword = async () => {
      try {
        const valid = await forgotPasswordFormRef.value.validate()
        if (!valid) return
        
        forgotPasswordLoading.value = true
        
        const result = await store.dispatch('auth/forgotPassword', forgotPasswordForm.email)
        
        if (result.success) {
          ElMessage.success('Password reset link sent to your email')
          showForgotPassword.value = false
          forgotPasswordForm.email = ''
        } else {
          ElMessage.error(result.message || 'Failed to send reset link')
        }
      } catch (error) {
        console.error('Forgot password error:', error)
        ElMessage.error('An error occurred while sending reset link')
      } finally {
        forgotPasswordLoading.value = false
      }
    }
    
    return {
      loginFormRef,
      forgotPasswordFormRef,
      showForgotPassword,
      forgotPasswordLoading,
      loginForm,
      forgotPasswordForm,
      loginRules,
      forgotPasswordRules,
      loading,
      handleLogin,
      handleForgotPassword,
      // Icons
      User,
      Message,
      Lock
    }
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  padding: 40px;
  width: 100%;
  max-width: 400px;
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.logo {
  margin-bottom: 20px;
}

.login-header h1 {
  color: #303133;
  margin: 0 0 10px 0;
  font-size: 28px;
  font-weight: 600;
}

.login-header p {
  color: #909399;
  margin: 0;
  font-size: 14px;
}

.login-form {
  margin-bottom: 20px;
}

.login-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.login-button {
  width: 100%;
  height: 44px;
  font-size: 16px;
  font-weight: 500;
}

.login-footer {
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid #EBEEF5;
}

.login-footer p {
  color: #909399;
  margin: 0;
  font-size: 14px;
}

:deep(.el-input__wrapper) {
  border-radius: 8px;
}

:deep(.el-button) {
  border-radius: 8px;
}

:deep(.el-form-item) {
  margin-bottom: 20px;
}

:deep(.el-form-item__error) {
  padding-top: 4px;
}

@media (max-width: 480px) {
  .login-card {
    padding: 30px 20px;
    margin: 10px;
  }
  
  .login-header h1 {
    font-size: 24px;
  }
}
</style>