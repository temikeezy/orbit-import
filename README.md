# ORBIT Group Member Importer

A WordPress plugin that enables bulk import of members into BuddyBoss/BuddyPress groups directly from the group management interface.

## Features

### 🎯 **Frontend Integration**
- Seamlessly integrated into BuddyBoss group management "Members" section
- Accessible via: Group → Manage → Members
- No backend admin interface required

### 👥 **User Management**
- **Individual Member Addition**: Add single members with email, first name, and last name
- **Bulk CSV/Excel Import**: Upload CSV or Excel (.xlsx) files to import multiple members at once
- **Automatic User Creation**: Creates new WordPress users if they don't exist
- **Existing User Handling**: Adds existing users to the group without creating duplicates

### 🔐 **Access Control**
- **Role-Based Access**: Only group moderators and administrators can import members
- **Security**: Proper nonce verification and permission checks
- **Group-Specific**: Import functionality is scoped to individual groups

### 📊 **Import Process**
- **Wizard Interface**: Step-by-step import process with visual progress indicators
- **File Upload**: Drag-and-drop and click-to-browse CSV/XLSX upload
- **Column Mapping**: Map file columns to user fields
- **Batch Processing**: Efficiently handles large files (configurable batch size)
- **Background Jobs**: Optional async processing via Action Scheduler (with WP‑Cron fallback)
- **Progress Tracking**: Real-time progress with detailed results
- **Error Handling**: Comprehensive validation and error reporting

### 🎨 **User Experience**
- **Modern UI**: Clean, professional interface with step indicators
- **Responsive Design**: Works on desktop and mobile devices
- **Accessibility**: ARIA live announcements and sensible focus management
- **Visual Feedback**: Clear success/error messages and progress indicators
- **Restart Options**: Easy navigation between import steps

### ⚙️ **Developer/Operations**
- **REST API**: Endpoints to upload, process, add members, and manage jobs
- **Settings Page**: Batch size, email toggles, multisite options, BuddyBoss template slug
- **BuddyBoss Emails**: Uses `bp_send_email` with tokens; core HTML fallback
- **WP‑CLI**: Command to scaffold the BuddyBoss email template
- **Filters/Actions**: Extensive hooks for customization (batch size, roles, MIME, emails)

## Installation

1. Upload the plugin files to `/wp-content/plugins/orbit-group-member-importer/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure BuddyBoss or BuddyPress is installed and active

Optional:
- Install `PhpOffice/PhpSpreadsheet` for Excel (.xlsx) parsing
- Install Action Scheduler for robust background processing

## Usage

### Individual Member Addition
1. Navigate to your group's management page
2. Go to the "Members" section
3. Use the "Quick Add Member" form to add individual members

### Bulk CSV/Excel Import
1. Navigate to your group's management page
2. Go to the "Members" section
3. Click "Bulk Import from CSV File"
4. Follow the wizard steps:
   - **Step 1**: Upload your CSV or Excel (.xlsx) file
   - **Step 2**: Map columns (email, first_name, last_name)
   - **Step 3**: Monitor import progress
   - **Step 4**: Review import results

### File Format
Your CSV/XLSX file should include the following columns:
- `email` (required): Valid email addresses
- `first_name` (optional): User's first name
- `last_name` (optional): User's last name

Example CSV:
```csv
email,first_name,last_name
john.doe@example.com,John,Doe
jane.smith@example.com,Jane,Smith
```

## BuddyBoss Email Template (Recommended)

If you use BuddyBoss/BuddyPress emails, create a template with the slug `orbit-welcome` and include the tokens used by this plugin. Set the template subject and body as follows:

### Subject

```
Welcome to {{site.name}}
```

### Body (HTML)

```
<p>Hi {{recipient.name}},</p>
<p>Welcome to <strong>{{site.name}}</strong>!</p>
<p>Click below to set your password and get started:</p>
<p><a href="{{reset.url}}">{{reset.url}}</a></p>
<p>See you inside — <a href="{{site.url}}">{{site.url}}</a></p>
```

Tokens available:
- `{{recipient.name}}`
- `{{reset.url}}`
- `{{site.name}}`
- `{{site.url}}`

If BuddyBoss is inactive or sending fails, the plugin automatically falls back to a core HTML email with a working password reset link.

## REST API

Base namespace: `ogmi/v1`

- `POST /ogmi/v1/import/upload` (multipart: `file`, `group_id`)
- `POST /ogmi/v1/import/process` (`file_id`, `mapping`, `batch_size`, `offset`, `group_id`)
- `POST /ogmi/v1/member/add` (`email`, `first_name`, `last_name`, `role`, `group_id`)
- Background jobs:
  - `POST /ogmi/v1/job/start` (`file_id`, `mapping`, `group_id`)
  - `GET /ogmi/v1/job/status?job_id=...`

All endpoints require authenticated users who pass the import permission check.

## Background Jobs

Large imports can be scheduled in the background. The plugin prefers Action Scheduler if present, with WP‑Cron fallback. Poll progress via the REST job status endpoint.

Filters:
- `ogmi_scheduler_batch_size`
- `ogmi_scheduler_group`

## CLI

Create BuddyBoss email template:

```
wp ogmi create-buddyboss-template
```

## XLSX Support

If `PhpOffice/PhpSpreadsheet` is available, the plugin can parse `.xlsx` files for headers, previews, and batch processing. Otherwise, the UI falls back to CSV.


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
│   ├── class-file-processor.php             # File handling and parsing (CSV/XLSX)
│   ├── class-user-manager.php               # User creation and management
│   ├── class-permission-handler.php         # Capability checks
│   ├── rest/
│   │   └── class-rest-controller.php        # REST API endpoints
│   ├── scheduler/
│   │   └── class-import-scheduler.php       # Background job processing
│   ├── admin/
│   │   └── class-settings.php               # Settings page and tools
│   └── cli/
│       └── class-ogmi-cli.php               # WP‑CLI commands
├── assets/
│   ├── css/
│   │   └── group-manager.css         # Frontend styles
│   └── js/
│       └── group-manager.js          # Frontend JavaScript
├── templates/
│   └── members-import-interface.php  # Import interface template
├── samples/
│   ├── sample.csv                    # Example CSV file
│   └── sample-xlsx.php               # Dynamic Excel sample generator
├── languages/
│   └── .placeholder                  # Translations scaffold
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
- File type and MIME validation (CSV/XLSX)
- Email validation and sanitization
- CSV delimiter detection and safe parsing
- Temporary upload TTL and cleanup

### Performance Optimizations
- Batch processing for large files
- Optional background jobs (Action Scheduler/WP‑Cron)
- Temporary file cleanup
- Efficient database queries
- Minimal memory usage

## Changelog

### Version 1.1.1 (Current)
- ✅ Complete frontend integration into BuddyBoss group management
- ✅ Wizard-style import interface with step indicators
- ✅ Drag-and-drop file upload functionality
- ✅ Column mapping interface
- ✅ Batch processing for large CSV/XLSX files
- ✅ Comprehensive error handling and validation
- ✅ Real-time progress tracking
- ✅ Automatic user creation and group membership
- ✅ Role-based access control
- ✅ Mobile-responsive design
- ✅ REST API and background job support
- ✅ Hardened welcome emails (BuddyBoss tokens + core HTML fallback)
- ✅ Settings page, privacy policy content, and WP‑CLI helper

### Version 1.0.0
- Initial release with basic functionality

## Support

For support, feature requests, or bug reports, please contact the Ilorin Innovation Hub development team.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Ilorin Innovation Hub for the ORBIT Network platform.