# ORBIT Group Member Importer

A WordPress plugin that integrates user import functionality directly into the BuddyBoss/BuddyPress group management interface, specifically within the "Members" section of the group admin area.

## Features

### 🎯 **Seamless Integration**
- Integrates directly into BuddyBoss group management interface
- No separate tabs or backend admin interface required
- Appears naturally in Group → Manage → Members section

### 👥 **Individual Member Addition**
- Quick add form with email, first name, last name, and group role
- Real-time validation and feedback
- Auto-refresh members list after successful addition

### 📁 **Bulk Import from CSV**
- Support for CSV file uploads (up to 10MB)
- Drag-and-drop file upload interface
- Column mapping for flexible file formats
- Batch processing with progress indicators
- Real-time statistics (created, updated, skipped, errors)

### 🔐 **Role-Based Access Control**
- Only group administrators and moderators can access import functionality
- Respects existing BuddyBoss/BuddyPress permission structure
- Proper security with nonce verification

### 🚀 **Smart User Management**
- Creates new WordPress users only when necessary
- Adds existing users to groups without creating duplicates
- Supports member, moderator, and administrator roles
- Automatic username generation and secure password creation

## Installation

1. Upload the plugin files to `/wp-content/plugins/orbit-group-member-importer/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure BuddyBoss Platform or BuddyPress is installed and active

## Usage

### Accessing Import Functionality

1. Navigate to any BuddyBoss/BuddyPress group
2. Click the three dots menu (⋯) in the group header
3. Select "Manage" from the dropdown
4. Click "Members" in the left sidebar
5. You'll see the import interface at the top of the page

### Individual Member Addition

1. Fill in the "Quick Add Member" form:
   - **Email Address** (required)
   - **First Name** (optional)
   - **Last Name** (optional)
   - **Group Role** (member, moderator, or administrator)
2. Click "Add Member"
3. The member will be added and the page will refresh to show them

### Bulk Import from CSV

1. Click "Bulk Import from CSV" to expand the bulk import section
2. Download the sample CSV file to understand the format
3. Upload your CSV file using drag-and-drop or file browser
4. Map your file columns to the required fields (email is required)
5. Review the file preview
6. Click "Start Import" to begin processing
7. Monitor progress and view results

### CSV File Format

Your CSV file should have the following columns:

```csv
email,first_name,last_name,role
john.doe@example.com,John,Doe,member
jane.smith@example.com,Jane,Smith,mod
admin.user@example.com,Admin,User,admin
```

**Required Fields:**
- `email` - User's email address (required for all imports)

**Optional Fields:**
- `first_name` - User's first name
- `last_name` - User's last name
- `role` - Group role (member, mod, admin) - defaults to "member"

## Technical Details

### File Structure
```
orbit-group-member-importer/
├── orbit-group-member-importer.php (main plugin file)
├── includes/
│   ├── class-group-manager-integration.php
│   ├── class-file-processor.php
│   ├── class-user-manager.php
│   └── class-permission-handler.php
├── assets/
│   ├── css/
│   │   └── group-manager.css
│   └── js/
│       └── group-manager.js
├── templates/
│   └── members-import-interface.php
└── samples/
    └── sample.csv
```

### Integration Points
- Hooks into BuddyBoss group management interface
- Uses `bp_after_group_admin_content` action
- Integrates with existing group management styling
- Maintains compatibility with BuddyBoss theme and plugins

### Security Features
- WordPress nonce verification for all AJAX requests
- Role-based access control (group admins and moderators only)
- File type and size validation
- Data sanitization and validation
- Secure file upload handling

### Performance Features
- Batch processing for large files (10 rows per batch)
- AJAX-based operations to prevent page timeouts
- Memory-efficient file processing
- Temporary file cleanup
- Progress tracking for user feedback

## Compatibility

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **BuddyBoss**: Compatible with BuddyBoss Platform
- **BuddyPress**: Compatible with BuddyPress plugin

## Permissions

The import functionality is only available to users with the following roles:

- **Site Administrators** - Can import to any group
- **Group Administrators** - Can import to groups they administer
- **Group Moderators** - Can import to groups they moderate

## Error Handling

The plugin provides comprehensive error handling for:

- Invalid file types (only CSV supported)
- File size limits (10MB maximum)
- Invalid email addresses
- Duplicate users
- Permission violations
- Network and server errors

## File Processing

- **Maximum File Size**: 10MB
- **Batch Size**: 10 rows per batch
- **Supported Formats**: CSV only (Excel support planned for future versions)
- **Temporary Storage**: Files are stored temporarily during processing and automatically cleaned up

## User Creation Logic

1. **New Users**: Creates WordPress user accounts with provided information
2. **Existing Users**: Adds existing users to the group without creating duplicates
3. **Role Assignment**: Supports member, moderator, and administrator roles
4. **Email Validation**: Ensures valid email addresses before processing
5. **Duplicate Prevention**: Checks for existing group membership

## Troubleshooting

### Import functionality not appearing
- Ensure you're a group administrator or moderator
- Check that BuddyBoss/BuddyPress is active
- Verify you're in the correct group management section

### File upload issues
- Check file size (must be under 10MB)
- Ensure file is in CSV format
- Verify file has proper headers

### Permission errors
- Confirm you have the right group role
- Check if you're logged in
- Verify group exists and is accessible

## Support

For support and feature requests, please contact the Ilorin Innovation Hub development team.

## Changelog

### v1.0.0
- Initial release
- Frontend group management integration
- Individual member addition
- Bulk CSV import functionality
- Role-based access control
- Comprehensive error handling
- Responsive design
- Security features

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Ilorin Innovation Hub for the ORBIT Network community platform.
