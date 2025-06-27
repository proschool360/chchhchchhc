<template>
  <div 
    class="shape-element"
    :style="shapeStyle"
  >
    <!-- Content for shape if needed -->
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'ShapeElement',
  props: {
    element: {
      type: Object,
      required: true
    },
    preview: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const shapeStyle = computed(() => {
      const baseStyle = {
        width: '100%',
        height: '100%',
        backgroundColor: props.element.backgroundColor || 'transparent',
        border: props.element.borderWidth ? 
          `${props.element.borderWidth}px solid ${props.element.borderColor || '#000'}` : 'none',
        borderRadius: `${props.element.borderRadius || 0}px`
      }
      
      // Special handling for line elements
      if (props.element.type === 'line') {
        baseStyle.borderRadius = '0'
        if (!props.element.backgroundColor || props.element.backgroundColor === 'transparent') {
          baseStyle.backgroundColor = props.element.borderColor || '#000000'
        }
      }
      
      return baseStyle
    })
    
    return {
      shapeStyle
    }
  }
}
</script>

<style scoped>
.shape-element {
  user-select: none;
  pointer-events: none;
}
</style>