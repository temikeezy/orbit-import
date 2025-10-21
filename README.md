# ORBIT Bulk User Importer v2.0.0

## Overview

The ORBIT Bulk User Importer has been completely rewritten to work on the frontend under BuddyBoss/BuddyPress group tabs. This version removes all backend admin functionality and provides a streamlined, role-based import experience directly within groups.

## Key Features

### Frontend Group Integration
- **Group Tab Integration**: Import functionality is now available as a tab within each BuddyBoss/BuddyPress group
- **Role-Based Access**: Only users with moderator or administrator roles can access the import functionality
- **No Backend Required**: All import operations happen on the frontend

### Import Options

#### 1. Individual User Addition
- Add single users to groups with a simple form
- Supports email, first name, last name, and group role assignment
- Automatically creates new WordPress users if they don't exist
- Adds existing users to the group if they already exist

#### 2. Bulk Import from Files
- **Supported Formats**: CSV and Excel (.xlsx) files
- **Drag & Drop Interface**: Modern file upload with drag-and-drop support
- **Column Mapping**: Flexible column mapping for different file formats
- **Batch Processing**: Processes large files in small batches to prevent timeouts
- **Real-time Progress**: Live progress tracking with detailed statistics

### User Management
- **Smart User Creation**: Creates new WordPress users only when necessary
- **Existing User Handling**: Adds existing users to groups without creating duplicates
- **Role Assignment**: Supports member, moderator, and administrator roles
- **Group Integration**: Seamlessly integrates with BuddyBoss/BuddyPress group functionality

## Installation

1. Upload the plugin files to `/wp-content/plugins/orbit-bulk-user-importer/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure BuddyBoss or BuddyPress is installed and active

## Usage

### Accessing Import Functionality

1. Navigate to any BuddyBoss/BuddyPress group
2. Look for the "Import Members" tab (only visible to moderators and administrators)
3. Choose between individual user addition or bulk import

### Individual User Addition

1. Go to the "Add Individual Member" tab
2. Fill in the user's email address (required)
3. Optionally add first name, last name, and group role
4. Click "Add Member"

### Bulk Import

1. Go to the "Bulk Import from File" tab
2. Download sample files to understand the expected format
3. Upload your CSV or Excel file using drag-and-drop or file browser
4. Map your file columns to the required fields (email is required)
5. Review the file preview
6. Click "Start Import" to begin processing

### File Format Requirements

#### Required Fields
- **Email**: User's email address (required for all imports)

#### Optional Fields
- **First Name**: User's first name
- **Last Name**: User's last name
- **Role**: Group role (member, mod, admin) - defaults to "member"

#### Sample File Structure
```csv
email,first_name,last_name,role
john@example.com,John,Doe,member
jane@example.com,Jane,Smith,mod
admin@example.com,Admin,User,admin
```

## Technical Details

### File Processing
- **Maximum File Size**: 10MB
- **Batch Size**: 10 rows per batch (configurable)
- **Supported Formats**: CSV, Excel (.xlsx)
- **Temporary Storage**: Files are stored temporarily during processing and automatically cleaned up

### Security
- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **Role-Based Access**: Only moderators and administrators can access import functionality
- **File Validation**: Strict file type and size validation
- **Data Sanitization**: All user input is properly sanitized

### Performance
- **Batch Processing**: Large files are processed in small batches to prevent server timeouts
- **Progress Tracking**: Real-time progress updates during import
- **Memory Efficient**: Uses generators for large file processing
- **Automatic Cleanup**: Temporary files are automatically removed

## Compatibility

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **BuddyBoss**: Compatible with BuddyBoss platform
- **BuddyPress**: Compatible with BuddyPress plugin

## Migration from v1.x

This is a major version update that completely changes how the plugin works:

- **No Migration Required**: This is a complete rewrite
- **Backend Interface Removed**: All admin interface functionality has been removed
- **Frontend Only**: All functionality is now available through group tabs
- **New File Structure**: Plugin structure has been updated for better organization

## Support

For support and feature requests, please contact the Ilorin Innovation Hub development team.

## Changelog

### v2.0.0
- Complete rewrite for frontend group integration
- Removed all backend admin functionality
- Added role-based access control
- Implemented drag-and-drop file upload
- Added individual user addition functionality
- Improved batch processing with real-time progress
- Enhanced security with proper nonce verification
- Updated file structure and organization
