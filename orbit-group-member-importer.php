<?php
/**
 * Plugin Name: ORBIT Group Member Importer
 * Description: Import members directly into BuddyBoss/BuddyPress groups from the group management interface. Add individual members or bulk import from CSV/Excel files.
 * Version: 1.1.1
 * Author: Ilorin Innovation Hub
 * Text Domain: orbit-group-importer
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'OGMI_PLUGIN_FILE', __FILE__ );
define( 'OGMI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OGMI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OGMI_VERSION', '1.1.1' );
define( 'OGMI_TEXT_DOMAIN', 'orbit-group-importer' );

/**
 * Main plugin class
 */
class ORBIT_Group_Member_Importer {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain( OGMI_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        
        // Check if BuddyBoss or BuddyPress is active
        if ( ! $this->is_buddyboss_active() ) {
            add_action( 'admin_notices', array( $this, 'buddyboss_required_notice' ) );
            return;
        }
        
        // Load plugin classes
        $this->load_classes();
        
        // Initialize the group manager integration
        if ( class_exists( 'OGMI_Group_Manager_Integration' ) ) {
            new OGMI_Group_Manager_Integration();
        }
        
        // Schedule cleanup of expired files
        add_action( 'wp_loaded', array( $this, 'schedule_cleanup' ) );

        // Initialize REST API controller
        if ( class_exists( 'OGMI_REST_Controller' ) ) {
            add_action( 'rest_api_init', function() { ( new OGMI_REST_Controller() )->register_routes(); } );
        }

        // Initialize scheduler
        if ( class_exists( 'OGMI_Import_Scheduler' ) ) {
            $GLOBALS['ogmi_import_scheduler'] = new OGMI_Import_Scheduler();
        }

        // Initialize settings
        if ( is_admin() && class_exists( 'OGMI_Settings' ) ) {
            new OGMI_Settings();
        }
    }
    
    /**
     * Load plugin classes
     */
    private function load_classes() {
        require_once OGMI_PLUGIN_DIR . 'includes/class-group-manager-integration.php';
        require_once OGMI_PLUGIN_DIR . 'includes/class-file-processor.php';
        require_once OGMI_PLUGIN_DIR . 'includes/class-user-manager.php';
        require_once OGMI_PLUGIN_DIR . 'includes/class-permission-handler.php';
        require_once OGMI_PLUGIN_DIR . 'includes/rest/class-rest-controller.php';
        require_once OGMI_PLUGIN_DIR . 'includes/scheduler/class-import-scheduler.php';
        if ( is_admin() ) {
            require_once OGMI_PLUGIN_DIR . 'includes/admin/class-settings.php';
        }
        // Load WP-CLI commands if available
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            $cli_file = OGMI_PLUGIN_DIR . 'includes/cli/class-ogmi-cli.php';
            if ( file_exists( $cli_file ) ) {
                require_once $cli_file;
            }
        }
    }
    
    /**
     * Check if BuddyBoss or BuddyPress is active
     */
    private function is_buddyboss_active() {
        return function_exists( 'bp_is_group' ) && 
               function_exists( 'bp_get_current_group_id' ) && 
               function_exists( 'groups_get_groups' );
    }
    
    /**
     * Show admin notice if BuddyBoss is not active
     */
    public function buddyboss_required_notice() {
        if ( current_user_can( 'administrator' ) ) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . esc_html__( 'ORBIT Group Member Importer', OGMI_TEXT_DOMAIN ) . ':</strong> ';
            echo esc_html__( 'This plugin requires BuddyBoss Platform or BuddyPress to be installed and activated.', OGMI_TEXT_DOMAIN );
            echo '</p></div>';
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $import_dir = trailingslashit( $upload_dir['basedir'] ) . 'orbit-group-import/';
        wp_mkdir_p( $import_dir );
        
        // Add .htaccess for security
        $htaccess_content = "Options -Indexes\n";
        $htaccess_content .= "deny from all\n";
        file_put_contents( $import_dir . '.htaccess', $htaccess_content );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Schedule cleanup of expired files
     */
    public function schedule_cleanup() {
        if ( ! wp_next_scheduled( 'ogmi_cleanup_expired_files' ) ) {
            wp_schedule_event( time(), 'hourly', 'ogmi_cleanup_expired_files' );
        }
        
        add_action( 'ogmi_cleanup_expired_files', array( $this, 'cleanup_expired_files' ) );
    }
    
    /**
     * Clean up expired files
     */
    public function cleanup_expired_files() {
        if ( class_exists( 'OGMI_File_Processor' ) ) {
            $file_processor = new OGMI_File_Processor();
            $file_processor->cleanup_expired_files();
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up temporary files
        $upload_dir = wp_upload_dir();
        $import_dir = trailingslashit( $upload_dir['basedir'] ) . 'orbit-group-import/';
        
        if ( is_dir( $import_dir ) ) {
            $files = glob( $import_dir . '*' );
            foreach ( $files as $file ) {
                if ( is_file( $file ) ) {
                    unlink( $file );
                }
            }
        }
        
        // Clear scheduled cleanup
        wp_clear_scheduled_hook( 'ogmi_cleanup_expired_files' );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
ORBIT_Group_Member_Importer::get_instance();
