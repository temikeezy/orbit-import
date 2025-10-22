# ORBIT Group Member Importer

A WordPress plugin that enables bulk import of members into BuddyBoss/BuddyPress groups directly from the group management interface.

## Features

### 🎯 **Frontend Integration**
- Seamlessly integrated into BuddyBoss group management "Members" section
- Accessible via: Group → Manage → Members
- No backend admin interface required

### 👥 **User Management**
- **Individual Member Addition**: Add single members with email, first name, and last name
- **Bulk CSV Import**: Upload CSV files to import multiple members at once
- **Automatic User Creation**: Creates new WordPress users if they don't exist
- **Existing User Handling**: Adds existing users to the group without creating duplicates

### 🔐 **Access Control**
- **Role-Based Access**: Only group moderators and administrators can import members
- **Security**: Proper nonce verification and permission checks
- **Group-Specific**: Import functionality is scoped to individual groups

### 📊 **Import Process**
- **Wizard Interface**: Step-by-step import process with visual progress indicators
- **File Upload**: Drag-and-drop and click-to-browse CSV file upload
- **Column Mapping**: Intuitive interface to map CSV columns to user fields
- **Batch Processing**: Efficiently handles large CSV files
- **Progress Tracking**: Real-time import progress with detailed results
- **Error Handling**: Comprehensive validation and error reporting

### 🎨 **User Experience**
- **Modern UI**: Clean, professional interface with step indicators
- **Responsive Design**: Works on desktop and mobile devices
- **Visual Feedback**: Clear success/error messages and progress indicators
- **Restart Options**: Easy navigation between import steps

## Installation

1. Upload the plugin files to `/wp-content/plugins/orbit-group-member-importer/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure BuddyBoss or BuddyPress is installed and active

## Usage

### Individual Member Addition
1. Navigate to your group's management page
2. Go to the "Members" section
3. Use the "Quick Add Member" form to add individual members

### Bulk CSV Import
1. Navigate to your group's management page
2. Go to the "Members" section
3. Click "Bulk Import from CSV File"
4. Follow the wizard steps:
   - **Step 1**: Upload your CSV file
   - **Step 2**: Map columns (email, first_name, last_name)
   - **Step 3**: Monitor import progress
   - **Step 4**: Review import results

### CSV File Format
Your CSV file should include the following columns:
- `email` (required): Valid email addresses
- `first_name` (optional): User's first name
- `last_name` (optional): User's last name

Example CSV:
```csv
email,first_name,last_name
john.doe@example.com,John,Doe
jane.smith@example.com,Jane,Smith
```

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- BuddyBoss Platform or BuddyPress plugin
- Group moderator or administrator permissions

## File Structure

```
orbit-group-member-importer/
├── orbit-group-member-importer.php    # Main plugin file
├── includes/
│   ├── class-group-manager-integration.php  # Frontend integration
│   ├── class-file-processor.php             # File handling and parsing
│   └── class-user-manager.php               # User creation and management
├── assets/
│   ├── css/
│   │   └── group-manager.css         # Frontend styles
│   └── js/
│       └── group-manager.js          # Frontend JavaScript
├── templates/
│   └── members-import-interface.php  # Import interface template
├── samples/
│   └── sample.csv                    # Example CSV file
└── README.md                         # This file
```

## Technical Details

### AJAX Endpoints
- `ogmi_add_member`: Add individual member
- `ogmi_upload_file`: Handle file upload
- `ogmi_process_batch`: Process batch import
- `ogmi_get_preview`: Get file preview

### Security Features
- Nonce verification for all AJAX requests
- Permission checks for group access
- File type validation (CSV only)
- Email validation and sanitization
- SQL injection prevention

### Performance Optimizations
- Batch processing for large files
- Temporary file cleanup
- Efficient database queries
- Minimal memory usage

## Changelog

### Version 1.1.0 (Current)
- ✅ Complete frontend integration into BuddyBoss group management
- ✅ Wizard-style import interface with step indicators
- ✅ Drag-and-drop file upload functionality
- ✅ Column mapping interface
- ✅ Batch processing for large CSV files
- ✅ Comprehensive error handling and validation
- ✅ Real-time progress tracking
- ✅ Automatic user creation and group membership
- ✅ Role-based access control
- ✅ Mobile-responsive design

### Version 1.0.0
- Initial release with basic functionality

## Support

For support, feature requests, or bug reports, please contact the Ilorin Innovation Hub development team.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Ilorin Innovation Hub for the ORBIT Network platform.