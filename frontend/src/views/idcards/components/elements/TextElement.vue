<template>
  <div 
    class="text-element"
    :style="textStyle"
  >
    {{ displayText }}
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'TextElement',
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
    const textStyle = computed(() => ({
      fontSize: `${props.element.fontSize || 14}px`,
      fontWeight: props.element.fontWeight || 'normal',
      color: props.element.color || '#000000',
      textAlign: props.element.textAlign || 'left',
      lineHeight: '1.2',
      width: '100%',
      height: '100%',
      display: 'flex',
      alignItems: 'center',
      justifyContent: getJustifyContent(props.element.textAlign),
      wordWrap: 'break-word',
      overflow: 'hidden'
    }))
    
    const displayText = computed(() => {
      let text = props.element.content || ''
      
      // Replace placeholders with actual data if available
      if (props.employeeData && Object.keys(props.employeeData).length > 0) {
        text = text.replace(/\{employee_name\}/g, props.employeeData.full_name || 'John Doe')
        text = text.replace(/\{employee_id\}/g, props.employeeData.employee_id || 'EMP001')
        text = text.replace(/\{department\}/g, props.employeeData.department || 'IT Department')
        text = text.replace(/\{position\}/g, props.employeeData.position || 'Software Developer')
        text = text.replace(/\{company_name\}/g, 'Your Company Name')
        text = text.replace(/\{address\}/g, 'Company Address')
      } else {
        // Show placeholder text for preview
        text = text.replace(/\{employee_name\}/g, 'John Doe')
        text = text.replace(/\{employee_id\}/g, 'EMP001')
        text = text.replace(/\{department\}/g, 'IT Department')
        text = text.replace(/\{position\}/g, 'Software Developer')
        text = text.replace(/\{company_name\}/g, 'Your Company Name')
        text = text.replace(/\{address\}/g, 'Company Address')
      }
      
      return text
    })
    
    const getJustifyContent = (textAlign) => {
      switch (textAlign) {
        case 'center':
          return 'center'
        case 'right':
          return 'flex-end'
        default:
          return 'flex-start'
      }
    }
    
    return {
      textStyle,
      displayText
    }
  }
}
</script>

<style scoped>
.text-element {
  user-select: none;
  pointer-events: none;
}
</style>