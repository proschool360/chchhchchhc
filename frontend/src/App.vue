<template>
  <div id="app">
    <div v-if="!isLoginPage" class="app-container">
      <!-- Sidebar -->
      <Sidebar 
        :is-collapsed="sidebarCollapsed" 
        @toggle-collapse="toggleSidebar"
      />
      
      <!-- Main Content -->
      <div class="main-container">
        <!-- Header -->
        <header class="app-header">
          <div class="header-left">
            <h1 class="page-title">{{ pageTitle }}</h1>
          </div>
          
          <div class="header-right">
            <!-- Notifications -->
            <el-dropdown trigger="click" class="notification-dropdown">
              <el-badge :value="notificationCount" :hidden="notificationCount === 0">
                <el-button link size="large">
                  <el-icon><Bell /></el-icon>
                </el-button>
              </el-badge>
              <template #dropdown>
                <el-dropdown-menu>
                  <div class="notification-header">
                    <span>Notifications</span>
                    <el-button link size="small" @click="markAllAsRead">Mark all as read</el-button>
                  </div>
                  <div class="notification-list">
                    <div v-for="notification in notifications" :key="notification.id" class="notification-item">
                      <div class="notification-content">
                        <p class="notification-title">{{ notification.title }}</p>
                        <p class="notification-message">{{ notification.message }}</p>
                        <span class="notification-time">{{ formatTime(notification.created_at) }}</span>
                      </div>
                    </div>
                    <div v-if="notifications.length === 0" class="no-notifications">
                      No new notifications
                    </div>
                  </div>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
            
            <!-- User Profile -->
            <el-dropdown trigger="click" class="user-dropdown">
              <div class="user-info">
                <el-avatar :size="32" :src="userAvatar">
                  <el-icon><User /></el-icon>
                </el-avatar>
                <span class="username">{{ currentUser.employee?.first_name }} {{ currentUser.employee?.last_name }}</span>
                <el-icon class="dropdown-icon"><ArrowDown /></el-icon>
              </div>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="goToProfile">
                    <el-icon><User /></el-icon>
                    My Profile
                  </el-dropdown-item>
                  <el-dropdown-item @click="goToSettings">
                    <el-icon><Setting /></el-icon>
                    Settings
                  </el-dropdown-item>
                  <el-dropdown-item divided @click="logout">
                    <el-icon><SwitchButton /></el-icon>
                    Logout
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </header>
        
        <!-- Page Content -->
        <main class="app-main">
          <router-view />
        </main>
      </div>
    </div>
    
    <!-- Login Page -->
    <div v-else class="login-container">
      <router-view />
    </div>
  </div>
</template>

<script>
import { computed, ref, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import Sidebar from '@/components/Sidebar.vue'

dayjs.extend(relativeTime)

export default {
  name: 'App',
  components: {
    Sidebar
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const route = useRoute()
    const toast = useToast()
    
    const sidebarCollapsed = ref(false)
    const notifications = ref([])
    
    const currentUser = computed(() => store.getters['auth/user'])
    const isAuthenticated = computed(() => store.getters['auth/isAuthenticated'])
    const userRole = computed(() => store.getters['auth/userRole'])
    
    const isLoginPage = computed(() => {
      return route.path === '/login' || route.path === '/'
    })
    
    const pageTitle = computed(() => {
      const routeMeta = route.meta
      return routeMeta?.title || 'HRMS'
    })
    
    const notificationCount = computed(() => {
      return notifications.value.filter(n => !n.read).length
    })
    
    const userAvatar = computed(() => {
      return currentUser.value?.avatar || null
    })
    
    // Permission checks
    const canAccessPayroll = computed(() => {
      return ['admin', 'hr', 'manager'].includes(userRole.value)
    })
    
    const canAccessRecruitment = computed(() => {
      return ['admin', 'hr', 'manager'].includes(userRole.value)
    })
    
    const canAccessReports = computed(() => {
      return ['admin', 'hr', 'manager'].includes(userRole.value)
    })
    
    const canAccessSettings = computed(() => {
      return ['admin', 'hr'].includes(userRole.value)
    })
    
    const toggleSidebar = () => {
      sidebarCollapsed.value = !sidebarCollapsed.value
    }
    
    const formatTime = (timestamp) => {
      return dayjs(timestamp).fromNow()
    }
    
    const markAllAsRead = () => {
      notifications.value.forEach(n => n.read = true)
    }
    
    const goToProfile = () => {
      router.push('/profile')
    }
    
    const goToSettings = () => {
      router.push('/settings')
    }
    
    const logout = async () => {
      try {
        await store.dispatch('auth/logout')
        router.push('/login')
        toast.success('Logged out successfully')
      } catch (error) {
        toast.error('Error logging out')
      }
    }
    
    const loadNotifications = async () => {
      try {
        // This would typically fetch from API
        notifications.value = [
          {
            id: 1,
            title: 'Leave Request',
            message: 'John Doe has requested leave for 3 days',
            created_at: new Date(),
            read: false
          },
          {
            id: 2,
            title: 'New Employee',
            message: 'Jane Smith has joined the company',
            created_at: new Date(Date.now() - 86400000),
            read: false
          }
        ]
      } catch (error) {
        console.error('Error loading notifications:', error)
      }
    }
    
    onMounted(() => {
      if (isAuthenticated.value) {
        loadNotifications()
      }
    })
    
    return {
      sidebarCollapsed,
      notifications,
      currentUser,
      isAuthenticated,
      userRole,
      isLoginPage,
      pageTitle,
      notificationCount,
      userAvatar,
      canAccessPayroll,
      canAccessRecruitment,
      canAccessReports,
      canAccessSettings,
      toggleSidebar,
      formatTime,
      markAllAsRead,
      goToProfile,
      goToSettings,
      logout
    }
  }
}
</script>

<style lang="scss">
@import "@/assets/styles/variables.scss";
#app {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  height: 100vh;
  margin: 0;
  padding: 0;
}

.app-container {
  display: flex;
  height: 100vh;
  min-height: 100vh;
  flex-direction: row;
}

.sidebar {
  width: 250px;
  background: #001529;
  color: white;
  transition: width 0.3s ease;
  overflow: hidden;
  flex-shrink: 0;
  z-index: 100;
  
  &.collapsed {
    width: 64px;
  }
  
  .sidebar-header {
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    border-bottom: 1px solid #1f2937;
    
    .logo {
      display: flex;
      align-items: center;
      gap: 8px;
      
      .el-icon {
        font-size: 24px;
        color: #1890ff;
      }
      
      .logo-text {
        font-size: 20px;
        font-weight: bold;
        color: white;
      }
    }
    
    .collapse-btn {
      color: white;
      
      &:hover {
        background: rgba(255, 255, 255, 0.1);
      }
    }
  }
  
  .sidebar-nav {
    height: calc(100vh - 64px);
    overflow-y: auto;
    
    .sidebar-menu {
      border: none;
      background: transparent;
      
      .el-menu-item,
      .el-sub-menu__title {
        color: rgba(255, 255, 255, 0.8);
        
        &:hover {
          background: rgba(255, 255, 255, 0.1);
          color: white;
        }
        
        &.is-active {
          background: #1890ff;
          color: white;
        }
      }
      
      .el-sub-menu .el-menu-item {
        background: rgba(0, 0, 0, 0.2);
        
        &:hover {
          background: rgba(255, 255, 255, 0.1);
        }
        
        &.is-active {
          background: #1890ff;
        }
      }
    }
  }
}

.main-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
  height: 100vh;
  overflow: hidden;
}

.app-header {
  height: 64px;
  background: white;
  border-bottom: 1px solid #e8e8e8;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  
  .header-left {
    .page-title {
      margin: 0;
      font-size: 20px;
      font-weight: 500;
      color: #262626;
    }
  }
  
  .header-right {
    display: flex;
    align-items: center;
    gap: 16px;
    
    .notification-dropdown,
    .user-dropdown {
      .el-button {
        border: none;
        background: none;
        
        &:hover {
          background: #f5f5f5;
        }
      }
    }
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      padding: 8px;
      border-radius: 6px;
      transition: background 0.2s;
      
      &:hover {
        background: #f5f5f5;
      }
      
      .username {
        font-weight: 500;
        color: #262626;
      }
      
      .dropdown-icon {
        color: #8c8c8c;
        font-size: 12px;
      }
    }
  }
}

.app-main {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  background: #f5f5f5;
  padding: 24px;
  position: relative;
  height: calc(100vh - 64px);
  z-index: 1;
  margin-left: 0;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #e8e8e8;
  font-weight: 500;
}

.notification-list {
  max-height: 300px;
  overflow-y: auto;
}

.notification-item {
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
  
  &:last-child {
    border-bottom: none;
  }
  
  .notification-content {
    .notification-title {
      margin: 0 0 4px 0;
      font-weight: 500;
      font-size: 14px;
    }
    
    .notification-message {
      margin: 0 0 4px 0;
      font-size: 12px;
      color: #8c8c8c;
    }
    
    .notification-time {
      font-size: 11px;
      color: #bfbfbf;
    }
  }
}

.no-notifications {
  padding: 24px;
  text-align: center;
  color: #8c8c8c;
  font-size: 14px;
}

.login-container {
  height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>