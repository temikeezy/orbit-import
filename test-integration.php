<?php
/**
 * Simple integration test for ORBIT Bulk User Importer v2.0.0
 * 
 * This file can be used to test basic functionality of the plugin
 * Run this file in a WordPress environment with BuddyBoss/BuddyPress active
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Test if the plugin is properly loaded
function test_plugin_loading() {
    echo "<h2>Plugin Loading Test</h2>";
    
    // Check if constants are defined
    $constants = [
        'OUI_PLUGIN_FILE',
        'OUI_PLUGIN_DIR', 
        'OUI_PLUGIN_URL',
        'OUI_VERSION'
    ];
    
    foreach ( $constants as $constant ) {
        if ( defined( $constant ) ) {
            echo "✓ {$constant}: " . constant( $constant ) . "<br>";
        } else {
            echo "✗ {$constant}: Not defined<br>";
        }
    }
    
    // Check if classes exist
    $classes = [
        'OUI\\Frontend\\Group_Integration',
        'OUI\\Integrations\\BuddyBoss',
        'OUI\\Support\\CSV',
        'OUI\\Support\\XLSX'
    ];
    
    foreach ( $classes as $class ) {
        if ( class_exists( $class ) ) {
            echo "✓ Class {$class}: Loaded<br>";
        } else {
            echo "✗ Class {$class}: Not found<br>";
        }
    }
}

// Test BuddyBoss integration
function test_buddyboss_integration() {
    echo "<h2>BuddyBoss Integration Test</h2>";
    
    if ( class_exists( 'OUI\\Integrations\\BuddyBoss' ) ) {
        $is_active = OUI\Integrations\BuddyBoss::is_active();
        echo "BuddyBoss Active: " . ( $is_active ? "✓ Yes" : "✗ No" ) . "<br>";
        
        if ( $is_active ) {
            $groups = OUI\Integrations\BuddyBoss::name_to_id_map();
            echo "Available Groups: " . count( $groups ) . "<br>";
            
            if ( ! empty( $groups ) ) {
                echo "Sample Groups:<br>";
                $count = 0;
                foreach ( $groups as $name => $id ) {
                    if ( $count >= 3 ) break;
                    echo "- {$name} (ID: {$id})<br>";
                    $count++;
                }
            }
        }
    } else {
        echo "✗ BuddyBoss integration class not found<br>";
    }
}

// Test file processing capabilities
function test_file_processing() {
    echo "<h2>File Processing Test</h2>";
    
    // Test CSV support
    if ( class_exists( 'OUI\\Support\\CSV' ) ) {
        echo "✓ CSV Support: Available<br>";
    } else {
        echo "✗ CSV Support: Not available<br>";
    }
    
    // Test XLSX support
    if ( class_exists( 'OUI\\Support\\XLSX' ) ) {
        echo "✓ XLSX Support: Available<br>";
    } else {
        echo "✗ XLSX Support: Not available<br>";
    }
    
    // Test upload directory
    $uploads = wp_upload_dir();
    $import_dir = trailingslashit( $uploads['basedir'] ) . 'orbit-import/';
    
    if ( wp_mkdir_p( $import_dir ) ) {
        echo "✓ Upload Directory: Created/accessible at {$import_dir}<br>";
    } else {
        echo "✗ Upload Directory: Cannot create/access<br>";
    }
}

// Test user permissions
function test_user_permissions() {
    echo "<h2>User Permissions Test</h2>";
    
    $current_user = wp_get_current_user();
    echo "Current User: {$current_user->user_login} (ID: {$current_user->ID})<br>";
    
    // Check if user is administrator
    if ( user_can( $current_user, 'administrator' ) ) {
        echo "✓ User Role: Administrator<br>";
    } else {
        echo "User Role: " . implode( ', ', $current_user->roles ) . "<br>";
    }
    
    // Check BuddyBoss group permissions (if in a group context)
    if ( function_exists( 'bp_get_current_group_id' ) ) {
        $group_id = bp_get_current_group_id();
        if ( $group_id ) {
            echo "Current Group ID: {$group_id}<br>";
            
            if ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $current_user->ID, $group_id ) ) {
                echo "✓ Group Role: Moderator<br>";
            } elseif ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $current_user->ID, $group_id ) ) {
                echo "✓ Group Role: Administrator<br>";
            } else {
                echo "Group Role: Member<br>";
            }
        } else {
            echo "Not in a group context<br>";
        }
    }
}

// Test AJAX endpoints
function test_ajax_endpoints() {
    echo "<h2>AJAX Endpoints Test</h2>";
    
    $endpoints = [
        'oui_frontend_upload',
        'oui_frontend_add_user', 
        'oui_frontend_process_batch',
        'oui_frontend_get_preview'
    ];
    
    foreach ( $endpoints as $endpoint ) {
        if ( has_action( "wp_ajax_{$endpoint}" ) ) {
            echo "✓ AJAX Endpoint {$endpoint}: Registered<br>";
        } else {
            echo "✗ AJAX Endpoint {$endpoint}: Not registered<br>";
        }
    }
}

// Run all tests
function run_integration_tests() {
    echo "<h1>ORBIT Bulk User Importer v2.0.0 - Integration Tests</h1>";
    echo "<p>Running tests on " . date( 'Y-m-d H:i:s' ) . "</p>";
    
    test_plugin_loading();
    echo "<hr>";
    
    test_buddyboss_integration();
    echo "<hr>";
    
    test_file_processing();
    echo "<hr>";
    
    test_user_permissions();
    echo "<hr>";
    
    test_ajax_endpoints();
    echo "<hr>";
    
    echo "<h2>Test Complete</h2>";
    echo "<p>If all tests show ✓ marks, the plugin should be working correctly.</p>";
    echo "<p>To use the plugin:</p>";
    echo "<ol>";
    echo "<li>Navigate to a BuddyBoss/BuddyPress group</li>";
    echo "<li>Look for the 'Import Members' tab (visible to moderators and administrators)</li>";
    echo "<li>Use either individual user addition or bulk import functionality</li>";
    echo "</ol>";
}

// Run tests if this file is accessed directly
if ( isset( $_GET['run_tests'] ) && current_user_can( 'administrator' ) ) {
    run_integration_tests();
} else {
    echo "<h1>ORBIT Bulk User Importer v2.0.0 - Integration Test</h1>";
    echo "<p>To run integration tests, add <code>?run_tests=1</code> to the URL and ensure you're logged in as an administrator.</p>";
    echo "<p><a href='?run_tests=1'>Run Integration Tests</a></p>";
}
?>
