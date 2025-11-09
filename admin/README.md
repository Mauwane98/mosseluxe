# Admin Panel Bootstrap System

## Overview
The admin panel now uses an automatic bootstrap system that simplifies development and ensures consistency across all admin pages.

## 🆕 New Arrivals Management

Control what appears in your homepage "New Arrivals" section:

### Features:
- **Manual Product Selection**: Choose specific products to feature as new arrivals
- **Display Order Control**: Set custom ordering for featured products
- **Release Date Scheduling**: Schedule products to appear at specific times
- **Custom Messages**: Set personalized messages when no products are available
- **Display Count**: Control how many products to show (1-12)

### How to Use:
1. Go to **Marketing → New Arrivals** in the admin panel
2. **Configure Settings**: Set display count and custom message
3. **Add Products**: Select products and set their display order
4. **Schedule Releases**: Set release dates for future product launches
5. **Manage Display**: Remove products or reorder as needed

### Database Tables:
- `new_arrivals`: Links products to new arrivals with ordering and scheduling
- Settings: `new_arrivals_message`, `new_arrivals_display_count`

## How to Use

### For New Admin Pages
Instead of manually setting up sessions, authentication, and includes, simply use:

```php
<?php
// Include the admin bootstrap for automatic setup
require_once 'bootstrap.php';

// Your page logic here
$pageTitle = "Your Page Title";

// Display any session messages
displaySuccessMessage();
displayErrorMessage();

// Your page content here

<?php include 'footer.php'; ?>
```

### What the Bootstrap Does Automatically
- ✅ Starts PHP sessions
- ✅ Includes database connection
- ✅ Checks admin authentication (redirects to login if not authenticated)
- ✅ Includes the enhanced header with all features
- ✅ Provides utility functions for messages, formatting, and permissions
- ✅ Sets up automatic cleanup of session messages

### Available Utility Functions

#### Message Display
```php
displaySuccessMessage(); // Shows green success alerts
displayErrorMessage();   // Shows red error alerts
```

#### Formatting Functions
```php
formatCurrency(123.45);     // Returns "R123.45"
formatDate('2025-01-08');   // Returns formatted date
getStatusBadgeClass('Completed'); // Returns appropriate CSS class
```

#### Permission Checking
```php
if (hasPermission('admin')) {
    // Show admin-only content
}
```

#### Current Page Detection
```php
$currentPage = getCurrentPage(); // Returns current filename
```

## Enhanced Features Included

### 🎨 Visual Enhancements
- Shimmer hover effects on navigation
- Animated tooltips with helpful descriptions
- Smooth loading states and transitions
- Enhanced form interactions

### ⌨️ Keyboard Shortcuts
- `Ctrl/Cmd + D` → Dashboard
- `Ctrl/Cmd + P` → Products
- `Ctrl/Cmd + O` → Orders
- `Ctrl/Cmd + U` → Users
- `Ctrl/Cmd + S` → Settings
- `Escape` → Close modals

### 📱 User Experience
- Responsive design with mobile support
- Auto-hiding alerts and notifications
- Enhanced search with debouncing
- Smart form validation and feedback

### 🔧 Developer Experience
- Automatic session management
- Consistent error handling
- Built-in security checks
- Easy-to-use helper functions

## Migration Guide

### Old Way (Manual Setup)
```php
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';

if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$conn = get_db_connection();

$pageTitle = "Page Title";
include 'header.php';

// Manual message handling
if (isset($_SESSION['success_message'])) {
    // Manual HTML output
}
?>
```

### New Way (Bootstrap)
```php
<?php
require_once 'bootstrap.php';

$pageTitle = "Page Title";

// Automatic message display
displaySuccessMessage();
displayErrorMessage();
?>
```

## Benefits

1. **Consistency**: All admin pages have the same setup and features
2. **Security**: Automatic authentication checks on every page
3. **Maintainability**: Changes to core functionality only need to be made in one place
4. **Developer Experience**: Less boilerplate code, more focus on page-specific logic
5. **User Experience**: Enhanced features automatically available on all pages

## File Structure
```
admin/
├── bootstrap.php          # Main bootstrap file
├── header.php            # Enhanced header with all features
├── footer.php            # Footer include
├── dashboard.php         # Example using bootstrap
├── products.php          # Example using bootstrap
└── [other admin pages]
```

## Future Enhancements
- Role-based permissions system
- Audit logging
- Performance monitoring
- Dark mode support
- Multi-language support
