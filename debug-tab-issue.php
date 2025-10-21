<?php
/**
 * Debug script to troubleshoot why the import tab isn't appearing
 * 
 * Add this to your theme's functions.php or run it as a standalone script
 * to diagnose the issue.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    // If running standalone, define ABSPATH
    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', dirname( dirname( dirname( __FILE__ ) ) ) . '/' );
    }
}

function debug_orbit_import_tab() {
    echo "<h2>ORBIT Import Tab Debug Information</h2>";
    
    // Check if plugin is loaded
    echo "<h3>1. Plugin Status</h3>";
    if ( defined( 'OUI_VERSION' ) ) {
        echo "✓ Plugin loaded - Version: " . OUI_VERSION . "<br>";
    } else {
        echo "✗ Plugin not loaded<br>";
        return;
    }
    
    // Check BuddyBoss/BuddyPress status
    echo "<h3>2. BuddyBoss/BuddyPress Status</h3>";
    
    // Check for BuddyPress
    if ( class_exists( 'BuddyPress' ) ) {
        echo "✓ BuddyPress class exists<br>";
    } elseif ( function_exists( 'buddypress' ) ) {
        echo "✓ BuddyPress function exists<br>";
    } else {
        echo "✗ BuddyPress not detected<br>";
    }
    
    // Check for BuddyBoss
    if ( class_exists( 'BuddyBoss' ) ) {
        echo "✓ BuddyBoss class exists<br>";
    } elseif ( function_exists( 'buddyboss' ) ) {
        echo "✓ BuddyBoss function exists<br>";
    } else {
        echo "✗ BuddyBoss not detected<br>";
    }
    
    // Check key functions
    $functions_to_check = [
        'bp_is_group',
        'bp_get_current_group_id',
        'bp_get_current_group_slug',
        'bp_get_group_permalink',
        'bp_core_new_subnav_item',
        'groups_get_groups',
        'groups_join_group'
    ];
    
    echo "<h3>3. Required Functions</h3>";
    foreach ( $functions_to_check as $function ) {
        if ( function_exists( $function ) ) {
            echo "✓ {$function}<br>";
        } else {
            echo "✗ {$function}<br>";
        }
    }
    
    // Check if we're in a group context
    echo "<h3>4. Group Context</h3>";
    if ( function_exists( 'bp_is_group' ) ) {
        if ( bp_is_group() ) {
            echo "✓ Currently in a group context<br>";
            
            if ( function_exists( 'bp_get_current_group_id' ) ) {
                $group_id = bp_get_current_group_id();
                echo "✓ Group ID: {$group_id}<br>";
                
                if ( function_exists( 'bp_get_current_group_slug' ) ) {
                    $group_slug = bp_get_current_group_slug();
                    echo "✓ Group Slug: {$group_slug}<br>";
                }
            }
        } else {
            echo "✗ Not in a group context<br>";
        }
    } else {
        echo "✗ bp_is_group function not available<br>";
    }
    
    // Check user permissions
    echo "<h3>5. User Permissions</h3>";
    $current_user = wp_get_current_user();
    echo "Current User: {$current_user->user_login} (ID: {$current_user->ID})<br>";
    
    if ( user_can( $current_user, 'administrator' ) ) {
        echo "✓ User is Administrator<br>";
    } else {
        echo "User Roles: " . implode( ', ', $current_user->roles ) . "<br>";
        
        if ( function_exists( 'bp_get_current_group_id' ) ) {
            $group_id = bp_get_current_group_id();
            if ( $group_id ) {
                if ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $current_user->ID, $group_id ) ) {
                    echo "✓ User is Group Moderator<br>";
                } elseif ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $current_user->ID, $group_id ) ) {
                    echo "✓ User is Group Administrator<br>";
                } else {
                    echo "✗ User is not a group moderator or administrator<br>";
                }
            }
        }
    }
    
    // Check if our integration class exists
    echo "<h3>6. Integration Class</h3>";
    if ( class_exists( 'OUI\\Frontend\\Group_Integration' ) ) {
        echo "✓ Group_Integration class exists<br>";
    } else {
        echo "✗ Group_Integration class not found<br>";
    }
    
    // Check if BuddyBoss integration is active
    if ( class_exists( 'OUI\\Integrations\\BuddyBoss' ) ) {
        $is_active = OUI\Integrations\BuddyBoss::is_active();
        echo "BuddyBoss Integration Active: " . ( $is_active ? "✓ Yes" : "✗ No" ) . "<br>";
    } else {
        echo "✗ BuddyBoss integration class not found<br>";
    }
    
    // Check if hooks are registered
    echo "<h3>7. Hook Registration</h3>";
    global $wp_filter;
    
    if ( isset( $wp_filter['bp_setup_nav'] ) ) {
        echo "✓ bp_setup_nav hook has callbacks<br>";
        
        // Check if our callback is registered
        $callbacks = $wp_filter['bp_setup_nav']->callbacks;
        $found = false;
        foreach ( $callbacks as $priority => $hooks ) {
            foreach ( $hooks as $hook ) {
                if ( is_array( $hook['function'] ) && 
                     is_object( $hook['function'][0] ) && 
                     get_class( $hook['function'][0] ) === 'OUI\\Frontend\\Group_Integration' ) {
                    echo "✓ Our callback is registered at priority {$priority}<br>";
                    $found = true;
                    break 2;
                }
            }
        }
        if ( ! $found ) {
            echo "✗ Our callback is not registered<br>";
        }
    } else {
        echo "✗ bp_setup_nav hook has no callbacks<br>";
    }
    
    echo "<h3>8. Recommendations</h3>";
    echo "<ul>";
    echo "<li>Make sure you're viewing a BuddyBoss/BuddyPress group page</li>";
    echo "<li>Ensure you're logged in as a group moderator, administrator, or site administrator</li>";
    echo "<li>Check that BuddyBoss or BuddyPress is properly installed and activated</li>";
    echo "<li>Try deactivating and reactivating the ORBIT Import plugin</li>";
    echo "<li>Clear any caching plugins</li>";
    echo "</ul>";
}

// Add debug info to admin bar for administrators
add_action( 'admin_bar_menu', function( $wp_admin_bar ) {
    if ( current_user_can( 'administrator' ) ) {
        $wp_admin_bar->add_node( array(
            'id'    => 'orbit-debug',
            'title' => 'ORBIT Debug',
            'href'  => '#',
            'meta'  => array(
                'onclick' => 'alert("Check the page source for debug info"); return false;'
            )
        ) );
    }
}, 999 );

// Add debug info to group pages
add_action( 'wp_footer', function() {
    if ( current_user_can( 'administrator' ) && function_exists( 'bp_is_group' ) && bp_is_group() ) {
        echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
        debug_orbit_import_tab();
        echo '</div>';
    }
});

// Also add to admin notices
add_action( 'admin_notices', function() {
    if ( current_user_can( 'administrator' ) ) {
        echo '<div class="notice notice-info"><p>';
        echo '<strong>ORBIT Import Debug:</strong> ';
        echo 'If you don\'t see the import tab in groups, check the debug info at the bottom of group pages. ';
        echo 'Make sure BuddyBoss/BuddyPress is active and you have the right permissions.';
        echo '</p></div>';
    }
});
?>
