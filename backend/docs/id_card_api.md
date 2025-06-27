# ID Card Template System API Documentation

## Overview
The refactored ID Card Template System provides a simplified and efficient way to create, manage, and generate ID cards with predefined layouts.

## New Features

### 1. Simplified Template Creation
Instead of complex template data structures, you can now create templates using predefined layout types.

#### Endpoint: `POST /api/id-cards/templates`
```json
{
    "template_name": "My Custom Template",
    "layout_type": "modern",
    "colors": {
        "primary": "#2563eb",
        "secondary": "#64748b",
        "text": "#1e293b"
    }
}
```

#### Available Layout Types:
- **modern**: Clean, contemporary design with rounded corners
- **classic**: Traditional corporate layout with header/footer
- **minimal**: Simple, clean design with circular photo

### 2. Template Variations
Quickly create multiple template variations for different use cases.

#### Endpoint: `POST /api/id-cards/templates/variations`
Creates 5 predefined template variations:
- Corporate Modern (Blue theme)
- Classic Professional (Traditional layout)
- Minimal Clean (Simple design)
- Executive Premium (Black theme)
- Visitor Pass (Orange theme)

### 3. Bulk Card Generation
Generate ID cards for multiple employees at once.

#### Endpoint: `POST /api/id-cards/bulk-generate`
```json
{
    "employee_ids": [1, 2, 3, 4, 5],
    "template_id": 1
}
```

#### Response:
```json
{
    "success": true,
    "message": "Bulk card generation completed",
    "data": {
        "generated_cards": [
            {
                "card_id": 123,
                "employee_id": 1,
                "employee_name": "John Doe",
                "card_number": "12024"
            }
        ],
        "total_generated": 5,
        "errors": [],
        "total_errors": 0
    }
}
```

### 4. Template Statistics
Get usage statistics for all templates.

#### Endpoint: `GET /api/id-cards/templates/stats`

#### Response:
```json
{
    "success": true,
    "data": {
        "templates": [
            {
                "id": 1,
                "template_name": "Corporate Modern",
                "status": "active",
                "created_at": "2024-01-15 10:30:00",
                "cards_generated": 25,
                "active_cards": 23
            }
        ],
        "statistics": {
            "total_templates": 5,
            "total_cards": 150,
            "active_cards": 142
        }
    }
}
```

## Enhanced Preview System

The new preview system generates HTML previews based on the layout type and supports:
- Dynamic dimensions and colors
- Layout-specific rendering
- Employee photo integration
- QR code placeholders
- Company branding

### Preview Response Format:
```json
{
    "html": "<div class='id-card-preview'>...</div>",
    "layout_type": "modern",
    "dimensions": {"width": 400, "height": 250},
    "preview_data": {
        "employee": {...},
        "template_config": {...}
    }
}
```

## Card Data Structure

The new card data format is more structured and includes:

```json
{
    "template_id": 1,
    "layout_type": "modern",
    "employee": {
        "id": 123,
        "employee_id": "EMP001",
        "name": "John Doe",
        "department": "IT Department",
        "position": "Software Developer",
        "photo_url": "/uploads/photos/john_doe.jpg",
        "company_name": "Your Company"
    },
    "template_config": {
        "layout_type": "modern",
        "dimensions": {"width": 400, "height": 250},
        "colors": {
            "primary": "#2563eb",
            "secondary": "#64748b",
            "text": "#1e293b"
        },
        "layout": {
            "photo": {"x": 20, "y": 20, "width": 80, "height": 100},
            "name": {"x": 120, "y": 25, "font_size": 18}
        }
    },
    "metadata": {
        "created_at": "2024-01-15T10:30:00Z",
        "version": "2.0"
    }
}
```

## Migration Notes

### Database Schema Updates
The system now uses these column names:
- `qr_code_data` instead of `card_data`
- `card_number` instead of `qr_code`
- `status` instead of `is_active`
- `created_by` instead of `generated_by`
- `template_name` instead of `name`

### Backward Compatibility
The system supports both old and new data formats during the transition period.

## Error Handling

All endpoints return consistent error responses:
```json
{
    "success": false,
    "message": "Error description",
    "error_code": 400
}
```

## Security

- All endpoints require proper authentication
- Permission checks are enforced for each operation
- Input validation prevents SQL injection and XSS
- Activity logging tracks all template operations

## Performance Improvements

1. **Simplified Template Structure**: Reduces storage and processing overhead
2. **Bulk Operations**: Efficient batch processing for multiple cards
3. **Optimized Queries**: Better database performance with proper indexing
4. **Caching Support**: Template data can be cached for faster access
5. **Lazy Loading**: Preview generation only when needed

## Usage Examples

### Creating a Custom Template
```php
// POST /api/id-cards/templates
$data = [
    'template_name' => 'Department Specific',
    'layout_type' => 'classic',
    'colors' => [
        'primary' => '#dc2626',
        'secondary' => '#991b1b',
        'text' => '#7f1d1d'
    ]
];
```

### Generating Cards for New Employees
```php
// POST /api/id-cards/bulk-generate
$data = [
    'employee_ids' => [101, 102, 103],
    'template_id' => 2
];
```

### Getting Template Performance
```php
// GET /api/id-cards/templates/stats
// Returns usage statistics for all templates
```

This refactored system provides a much simpler and more maintainable approach to ID card template management while offering enhanced functionality and better performance.