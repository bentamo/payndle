# Service Manager Pro

A modern WordPress plugin for managing and displaying services with categories using an intuitive AJAX interface.

## Features

### Service Management
- Create, edit, and delete services
- Add featured images and descriptions
- Organize services into hierarchical categories
- Responsive and mobile-friendly interface

### User-Facing Features
- Display services in a clean, filterable grid
- Category-based filtering
- Smooth AJAX interactions
- Modern, clean design

### Admin Features
- Intuitive service management dashboard
- Category management
- Drag-and-drop reordering (if implemented)
- Bulk actions

## Installation

1. Upload the `my-services-plugin.php` file to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the shortcodes to display services on your pages

## Shortcodes

### Display Services
```
[user_services]
```

**Parameters:**
- `category` - (optional) Show services from a specific category (slug or ID)
- `limit` - (optional) Number of services to display
- `columns` - (optional) Number of columns in grid (2-4)

### Service Management Panel
```
[manager_add_service]
```
(For administrators only)

## Hooks and Filters

### Actions
- `mvp_before_services_loop` - Before services grid
- `mvp_after_services_loop` - After services grid
- `mvp_service_item_before` - Before individual service item
- `mvp_service_item_after` - After individual service item

### Filters
- `mvp_service_query_args` - Modify the services query
- `mvp_service_categories_args` - Modify category query arguments
- `mvp_service_item_classes` - Add custom classes to service items

## Styling

The plugin includes basic styling that can be overridden in your theme. Use the following CSS variables for theming:

```css
:root {
  --primary: #64C493;
  --primary-dark: #4a9e7a;
  --text: #0C1930;
  --text-light: #6B7280;
  --border: #E5E7EB;
  --background: #F9FAFB;
  --white: #FFFFFF;
}
```

## Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

## Changelog

### 2.0
- Complete rewrite with modern JavaScript and CSS
- Improved user interface and experience
- Better performance with optimized queries
- Enhanced security with nonce verification
- Added support for WordPress REST API

### 1.0
- Initial release

## License

GPL v2 or later

## Author

[Your Name]
