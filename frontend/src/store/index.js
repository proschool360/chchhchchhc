import { createStore } from 'vuex'
import errorLogger from '@/utils/errorLogger'
import auth from './modules/auth'
import employees from './modules/employees'
import attendance from './modules/attendance'
import leave from './modules/leave'
import payroll from './modules/payroll'
import recruitment from './modules/recruitment'
import performance from './modules/performance'
import training from './modules/training'
import reports from './modules/reports'
import settings from './modules/settings'
import departments from './modules/departments'
import positions from './modules/positions'

const store = createStore({
  modules: {
    auth,
    employees,
    attendance,
    leave,
    payroll,
    recruitment,
    performance,
    training,
    reports,
    settings,
    departments,
    positions
  },
  strict: process.env.NODE_ENV !== 'production',
  plugins: [
    // Store action logger
    (store) => {
      store.subscribe((mutation, state) => {
        errorLogger.log('info', 'Store Mutation', {
          type: mutation.type,
          payload: mutation.payload
        })
      })
      
      store.subscribeAction({
        before: (action, state) => {
          errorLogger.log('info', 'Store Action Started', {
            type: action.type,
            payload: action.payload
          })
        },
        after: (action, state) => {
          errorLogger.log('info', 'Store Action Completed', {
            type: action.type
          })
        },
        error: (action, state, error) => {
          errorLogger.log('error', 'Store Action Error', {
            type: action.type,
            payload: action.payload,
            error: error.message,
            stack: error.stack
          })
        }
      })
    }
  ]
})

// Log store initialization
errorLogger.log('info', 'Vuex Store Initialized', {
  modules: Object.keys(store._modules.root._children || {}),
  hasReports: !!store._modules.root._children?.reports
})

export default store