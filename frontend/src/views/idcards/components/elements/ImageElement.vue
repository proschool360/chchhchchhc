<template>
  <div class="image-element" :style="containerStyle">
    <img 
      v-if="imageSrc" 
      :src="imageSrc" 
      :alt="element.alt || 'Image'"
      :style="imageStyle"
      @error="handleImageError"
    />
    <div v-else class="image-placeholder" :style="placeholderStyle">
      <i :class="placeholderIcon"></i>
      <span>{{ placeholderText }}</span>
    </div>
  </div>
</template>

<script>
import { computed, ref } from 'vue'

export default {
  name: 'ImageElement',
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
    const imageError = ref(false)
    
    const containerStyle = computed(() => ({
      width: '100%',
      height: '100%',
      overflow: 'hidden',
      borderRadius: `${props.element.borderRadius || 0}px`,
      border: props.element.borderWidth ? 
        `${props.element.borderWidth}px solid ${props.element.borderColor || '#000'}` : 'none'
    }))
    
    const imageStyle = computed(() => ({
      width: '100%',
      height: '100%',
      objectFit: 'cover',
      display: 'block'
    }))
    
    const placeholderStyle = computed(() => ({
      width: '100%',
      height: '100%',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: '#f8f9fa',
      color: '#6c757d',
      fontSize: '12px',
      textAlign: 'center'
    }))
    
    const imageSrc = computed(() => {
      if (imageError.value) return null
      
      // Handle different image types
      switch (props.element.type) {
        case 'photo':
          return props.employeeData?.photo || props.element.src || getDefaultPhoto()
        case 'logo':
          return props.element.src || getDefaultLogo()
        case 'image':
        default:
          return props.element.src
      }
    })
    
    const placeholderIcon = computed(() => {
      switch (props.element.type) {
        case 'photo':
          return 'fas fa-user'
        case 'logo':
          return 'fas fa-building'
        case 'image':
        default:
          return 'fas fa-image'
      }
    })
    
    const placeholderText = computed(() => {
      switch (props.element.type) {
        case 'photo':
          return 'Employee Photo'
        case 'logo':
          return 'Company Logo'
        case 'image':
        default:
          return 'Image'
      }
    })
    
    const getDefaultPhoto = () => {
      // Return a default avatar image URL or null
      return null
    }
    
    const getDefaultLogo = () => {
      // Return a default company logo URL or null
      return null
    }
    
    const handleImageError = () => {
      imageError.value = true
    }
    
    return {
      containerStyle,
      imageStyle,
      placeholderStyle,
      imageSrc,
      placeholderIcon,
      placeholderText,
      handleImageError
    }
  }
}
</script>

<style scoped>
.image-element {
  user-select: none;
  pointer-events: none;
}

.image-placeholder {
  user-select: none;
}

.image-placeholder i {
  font-size: 24px;
  margin-bottom: 4px;
}

.image-placeholder span {
  font-size: 10px;
  font-weight: 500;
}
</style>