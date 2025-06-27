# EmployeeDropdown Component

A reusable Vue component for selecting employees with search functionality.

## Features

- **Remote Search**: Search employees by typing their name or employee ID
- **Customizable**: Configurable placeholder, width, and behavior
- **Form Integration**: Works seamlessly with Vue forms and v-model
- **Event Handling**: Emits change events with employee data
- **Accessibility**: Supports disabled and clearable states

## Basic Usage

```vue
<template>
  <EmployeeDropdown 
    v-model="selectedEmployeeId" 
    @change="handleEmployeeChange"
  />
</template>

<script>
import EmployeeDropdown from '@/components/EmployeeDropdown.vue'

export default {
  components: {
    EmployeeDropdown
  },
  data() {
    return {
      selectedEmployeeId: null
    }
  },
  methods: {
    handleEmployeeChange(employeeId, employeeData) {
      console.log('Selected employee:', employeeData)
    }
  }
}
</script>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `modelValue` | String/Number | `null` | The selected employee ID (v-model) |
| `placeholder` | String | `'Select employee'` | Placeholder text |
| `width` | String | `'100%'` | Component width |
| `clearable` | Boolean | `true` | Whether the selection can be cleared |
| `disabled` | Boolean | `false` | Whether the component is disabled |
| `departmentId` | String/Number | `null` | Filter employees by department (future feature) |
| `status` | String | `'active'` | Filter employees by status (future feature) |

## Events

| Event | Parameters | Description |
|-------|------------|-------------|
| `update:modelValue` | `employeeId` | Emitted when selection changes (v-model) |
| `change` | `employeeId, employeeData` | Emitted when selection changes with full employee data |

## Examples

### Custom Placeholder and Width

```vue
<EmployeeDropdown 
  v-model="assignedTo" 
  placeholder="Choose team member..."
  width="300px"
/>
```

### Form Integration

```vue
<el-form :model="form">
  <el-form-item label="Assign To:" prop="assignedTo">
    <EmployeeDropdown 
      v-model="form.assignedTo" 
      placeholder="Select employee to assign"
    />
  </el-form-item>
</el-form>
```

### Disabled State

```vue
<EmployeeDropdown 
  v-model="selectedEmployee" 
  :disabled="true"
  placeholder="This dropdown is disabled"
/>
```

### Non-clearable

```vue
<EmployeeDropdown 
  v-model="selectedEmployee" 
  :clearable="false"
  placeholder="Cannot be cleared once selected"
/>
```

### Event Handling

```vue
<template>
  <EmployeeDropdown 
    v-model="selectedEmployee" 
    @change="handleEmployeeSelection"
  />
</template>

<script>
export default {
  methods: {
    handleEmployeeSelection(employeeId, employeeData) {
      if (employeeData) {
        console.log(`Selected: ${employeeData.name} (${employeeData.employee_id})`)
        // Access other employee properties:
        // employeeData.id, employeeData.department, etc.
      }
    }
  }
}
</script>
```

## API Dependencies

This component requires the following API endpoint:

- `GET /api/employees/search?q={query}` - Search employees by name or employee ID
- `GET /api/employees/{id}` - Get employee details by ID (for initial loading)

## Styling

The component uses Element Plus styling by default. You can customize the appearance by:

1. Passing custom width via the `width` prop
2. Using CSS classes to override Element Plus styles
3. Wrapping the component in a container with custom styles

## Demo

To see the component in action, visit the demo page at `/demo/employee-dropdown` in your application.

## Integration Tips

1. **Form Validation**: Use with Element Plus form validation by adding appropriate rules
2. **Loading States**: The component handles its own loading states during search
3. **Error Handling**: Displays error messages automatically if API calls fail
4. **Performance**: Uses remote search to avoid loading all employees at once
5. **Accessibility**: Supports keyboard navigation and screen readers

## Common Use Cases

- **Task Assignment**: Assign tasks or projects to employees
- **Attendance Management**: Select employees for manual attendance entry
- **Leave Requests**: Choose employees for leave management
- **Performance Reviews**: Select employees for performance evaluations
- **Report Generation**: Filter reports by specific employees
- **Team Management**: Assign team members to projects or departments