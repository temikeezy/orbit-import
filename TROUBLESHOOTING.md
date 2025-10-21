# ORBIT Import Tab Troubleshooting Guide

If you don't see the "Import Members" tab in your BuddyBoss/BuddyPress groups, follow these troubleshooting steps:

## Step 1: Check Plugin Status

1. Go to **WordPress Admin > Plugins**
2. Verify that "ORBIT Bulk User Importer" is **activated**
3. Check that BuddyBoss or BuddyPress is also **activated**

## Step 2: Check User Permissions

The import tab is only visible to users with the following roles:
- **Site Administrators** (can see the tab in all groups)
- **Group Administrators** (can see the tab in groups they admin)
- **Group Moderators** (can see the tab in groups they moderate)

**To check your role:**
1. Go to a group page
2. Look for your role in the group member list
3. If you're not a moderator or admin, ask a group admin to promote you

## Step 3: Verify BuddyBoss/BuddyPress Integration

1. Go to **WordPress Admin > Plugins**
2. Make sure one of these is active:
   - BuddyBoss Platform
   - BuddyPress
3. If neither is active, install and activate one of them

## Step 4: Clear Cache

If you're using caching plugins:
1. Clear all caches
2. Try accessing the group page again

## Step 5: Check Group Context

Make sure you're viewing an actual group page:
1. Navigate to **Groups** in your site navigation
2. Click on a specific group
3. You should see group tabs like "Home", "Members", etc.
4. The "Import Members" tab should appear if you have the right permissions

## Step 6: Debug Information

If the tab still doesn't appear, you can run the debug script:

1. Copy the contents of `debug-tab-issue.php` 
2. Add it to your theme's `functions.php` file (temporarily)
3. Visit a group page as an administrator
4. Look for debug information at the bottom of the page
5. Remove the debug code from `functions.php` after checking

## Step 7: Manual Tab Addition (Advanced)

If the automatic tab addition isn't working, you can manually add it by adding this code to your theme's `functions.php`:

```php
// Manual tab addition for ORBIT Import
add_action( 'bp_setup_nav', function() {
    if ( ! bp_is_group() ) return;
    
    $group_id = bp_get_current_group_id();
    if ( ! $group_id ) return;
    
    // Check if user can import (admin, group admin, or group mod)
    $user_id = get_current_user_id();
    $can_import = false;
    
    if ( user_can( $user_id, 'administrator' ) ) {
        $can_import = true;
    } elseif ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id ) ) {
        $can_import = true;
    } elseif ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $user_id, $group_id ) ) {
        $can_import = true;
    }
    
    if ( $can_import ) {
        bp_core_new_subnav_item( array(
            'name'            => 'Import Members',
            'slug'            => 'import-members',
            'parent_slug'     => bp_get_current_group_slug(),
            'parent_url'      => bp_get_group_permalink(),
            'screen_function' => 'orbit_import_screen',
            'position'        => 50,
            'user_has_access' => true,
            'item_css_id'     => 'group-import-members'
        ) );
    }
}, 100 );

function orbit_import_screen() {
    add_action( 'bp_template_content', function() {
        $template_path = WP_PLUGIN_DIR . '/orbit-bulk-user-importer/includes/Frontend/views/group-import.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<p>Import template not found. Please check plugin installation.</p>';
        }
    } );
    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/plugins' ) );
}
```

## Common Issues and Solutions

### Issue: "Import Members" tab doesn't appear
**Solution:** Check user permissions and ensure you're a group moderator or administrator

### Issue: Tab appears but shows "Access Denied"
**Solution:** Your user role doesn't have import permissions. Ask a group admin to promote you.

### Issue: Plugin activated but no functionality
**Solution:** Ensure BuddyBoss or BuddyPress is also activated and properly configured

### Issue: File upload doesn't work
**Solution:** Check file size (max 10MB) and format (CSV or Excel only)

## Still Having Issues?

If none of these steps resolve the issue:

1. Check the WordPress error logs
2. Verify all plugin files are properly uploaded
3. Try deactivating and reactivating the plugin
4. Contact your system administrator

## Plugin Requirements

- WordPress 6.0+
- PHP 7.4+
- BuddyBoss Platform OR BuddyPress
- User with group moderator/administrator role
