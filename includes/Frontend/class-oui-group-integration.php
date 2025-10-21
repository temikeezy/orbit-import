<?php
namespace OUI\Frontend;

use OUI\Import\Runner;
use OUI\Integrations\BuddyBoss;
use OUI\Support\CSV;
use OUI\Support\XLSX;

defined( 'ABSPATH' ) || exit;

class Group_Integration {
    
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }
    
    public function init() {
        // Hook into BuddyBoss/BuddyPress group navigation
        if ( BuddyBoss::is_active() ) {
            // Add import functionality to Members tab instead of separate tab
            add_action( 'bp_groups_members_template', array( $this, 'add_import_to_members_tab' ) );
            add_action( 'bp_after_group_members_list', array( $this, 'add_import_to_members_tab' ) );
            add_action( 'bp_group_members_list_item_action', array( $this, 'add_import_to_members_tab' ) );
            
            // Also try the template action for BuddyBoss
            add_action( 'bp_template_content', array( $this, 'maybe_add_import_to_members' ) );
            
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
            
            // AJAX handlers for frontend
            add_action( 'wp_ajax_oui_frontend_upload', array( $this, 'handle_file_upload' ) );
            add_action( 'wp_ajax_oui_frontend_add_user', array( $this, 'handle_add_user' ) );
            add_action( 'wp_ajax_oui_frontend_process_batch', array( $this, 'handle_batch_process' ) );
            add_action( 'wp_ajax_oui_frontend_get_preview', array( $this, 'handle_get_preview' ) );
        } else {
            // Debug: Add admin notice if BuddyBoss is not active
            add_action( 'admin_notices', array( $this, 'buddyboss_not_active_notice' ) );
        }
    }
    
    public function add_import_to_members_tab() {
        // Check if we're in the right context
        if ( ! $this->is_members_tab_context() ) {
            return;
        }
        
        $group_id = bp_get_current_group_id();
        if ( ! $group_id || ! $this->user_can_import( $group_id ) ) {
            return;
        }
        
        // Add import interface to members tab
        $this->render_import_interface();
    }
    
    public function maybe_add_import_to_members() {
        // Check if we're in the members tab context
        if ( ! $this->is_members_tab_context() ) {
            return;
        }
        
        $group_id = bp_get_current_group_id();
        if ( ! $group_id || ! $this->user_can_import( $group_id ) ) {
            return;
        }
        
        // Add import interface
        $this->render_import_interface();
    }
    
    private function is_members_tab_context() {
        // Check if we're in a group and on the members tab
        if ( ! function_exists( 'bp_is_group' ) || ! bp_is_group() ) {
            return false;
        }
        
        // Check if we're on the members tab
        $current_action = bp_current_action();
        $is_members_tab = ( $current_action === 'members' || $current_action === 'all-members' || empty( $current_action ) );
        
        // Also check the URL to be sure
        $is_members_url = strpos( $_SERVER['REQUEST_URI'], '/members/' ) !== false;
        
        return $is_members_tab || $is_members_url;
    }
    
    private function render_import_interface() {
        // Only render once per page load
        static $rendered = false;
        if ( $rendered ) {
            return;
        }
        $rendered = true;
        
        // Include the import interface
        $template_path = plugin_dir_path( dirname( __DIR__ ) ) . 'includes/Frontend/views/members-import.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // Fallback to the main template
            $template_path = plugin_dir_path( dirname( __DIR__ ) ) . 'includes/Frontend/views/group-import.php';
            if ( file_exists( $template_path ) ) {
                include $template_path;
            }
        }
    }
    
    public function import_screen() {
        add_action( 'bp_template_content', array( $this, 'import_content' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/plugins' ) );
    }
    
    public function import_content() {
        $group_id = bp_get_current_group_id();
        if ( ! $group_id || ! $this->user_can_import() ) {
            echo '<div class="bp-feedback error"><p>' . esc_html__( 'You do not have permission to access this page.', 'orbit-import' ) . '</p></div>';
            return;
        }
        
        // Include the frontend template
        $template_path = plugin_dir_path( dirname( __DIR__ ) ) . 'includes/Frontend/views/group-import.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="bp-feedback error"><p>' . esc_html__( 'Import template not found.', 'orbit-import' ) . '</p></div>';
        }
    }
    
    public function enqueue_frontend_scripts() {
        if ( ! bp_is_group() || ! $this->user_can_import() ) {
            return;
        }
        
        wp_enqueue_style( 
            'oui-frontend', 
            OUI_PLUGIN_URL . 'assets/css/frontend.css', 
            array(), 
            OUI_VERSION 
        );
        
        wp_enqueue_script( 
            'oui-frontend', 
            OUI_PLUGIN_URL . 'assets/js/frontend.js', 
            array( 'jquery' ), 
            OUI_VERSION, 
            true 
        );
        
        wp_localize_script( 'oui-frontend', 'OUI_Frontend', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'oui_frontend' ),
            'groupId' => bp_get_current_group_id(),
            'strings' => array(
                'uploading' => __( 'Uploading...', 'orbit-import' ),
                'processing' => __( 'Processing...', 'orbit-import' ),
                'success' => __( 'Success!', 'orbit-import' ),
                'error' => __( 'Error occurred', 'orbit-import' ),
                'selectFile' => __( 'Please select a file', 'orbit-import' ),
                'invalidFile' => __( 'Invalid file type. Please upload CSV or Excel file.', 'orbit-import' ),
                'fileTooLarge' => __( 'File too large', 'orbit-import' ),
                'userAdded' => __( 'User added successfully', 'orbit-import' ),
                'userExists' => __( 'User already exists and has been added to group', 'orbit-import' ),
                'userCreated' => __( 'New user created and added to group', 'orbit-import' ),
            )
        ) );
    }
    
    public function handle_file_upload() {
        if ( ! $this->verify_ajax_request() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'orbit-import' ) ), 403 );
        }
        
        $group_id = isset( $_POST['group_id'] ) ? (int) $_POST['group_id'] : 0;
        if ( ! $group_id || ! $this->user_can_import( $group_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid group or insufficient permissions', 'orbit-import' ) ), 403 );
        }
        
        if ( empty( $_FILES['file'] ) || ! isset( $_FILES['file']['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded', 'orbit-import' ) ), 400 );
        }
        
        $file = $_FILES['file'];
        $size = isset( $file['size'] ) ? (int) $file['size'] : 0;
        $max = apply_filters( 'oui_max_csv_size', 10 * 1024 * 1024 ); // 10MB
        
        if ( $size <= 0 || $size > $max ) {
            wp_send_json_error( array( 'message' => __( 'File too large', 'orbit-import' ) ), 400 );
        }
        
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, array( 'csv', 'xlsx' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid file type', 'orbit-import' ) ), 400 );
        }
        
        // Store file temporarily
        $uploads = wp_upload_dir();
        $dir = trailingslashit( $uploads['basedir'] ) . 'orbit-import/temp/';
        wp_mkdir_p( $dir );
        
        $target = $dir . wp_unique_filename( $dir, sanitize_file_name( $file['name'] ) );
        if ( ! move_uploaded_file( $file['tmp_name'], $target ) ) {
            wp_send_json_error( array( 'message' => __( 'Could not save file', 'orbit-import' ) ), 500 );
        }
        
        // Extract headers and first few rows for preview
        $headers = array();
        $preview_rows = array();
        
        if ( 'csv' === $ext ) {
            $dialect = CSV::detect_dialect( $target );
            $gen = CSV::iterate( $target, $dialect['delimiter'], $dialect['enclosure'], $dialect['escape'] );
            $count = 0;
            foreach ( $gen as $row ) {
                if ( $count === 0 ) {
                    $headers = is_array( $row ) ? $row : array();
                } elseif ( $count < 6 ) { // First 5 data rows for preview
                    $preview_rows[] = is_array( $row ) ? $row : array();
                } else {
                    break;
                }
                $count++;
            }
        } else {
            $gen = XLSX::iterate( $target );
            $count = 0;
            foreach ( $gen as $row ) {
                if ( $count === 0 ) {
                    $headers = is_array( $row ) ? $row : array();
                } elseif ( $count < 6 ) { // First 5 data rows for preview
                    $preview_rows[] = is_array( $row ) ? $row : array();
                } else {
                    break;
                }
                $count++;
            }
        }
        
        $headers = array_filter( array_map( 'strval', $headers ) );
        
        // Store file info in transient for processing
        $file_id = wp_generate_uuid4();
        set_transient( 'oui_file_' . $file_id, array(
            'file_path' => $target,
            'file_ext' => $ext,
            'group_id' => $group_id,
            'headers' => $headers,
            'user_id' => get_current_user_id(),
            'created' => time()
        ), HOUR_IN_SECONDS );
        
        wp_send_json_success( array(
            'file_id' => $file_id,
            'headers' => $headers,
            'preview_rows' => $preview_rows,
            'total_columns' => count( $headers )
        ) );
    }
    
    public function handle_add_user() {
        if ( ! $this->verify_ajax_request() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'orbit-import' ) ), 403 );
        }
        
        $group_id = isset( $_POST['group_id'] ) ? (int) $_POST['group_id'] : 0;
        if ( ! $group_id || ! $this->user_can_import( $group_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid group or insufficient permissions', 'orbit-import' ) ), 403 );
        }
        
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
        $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
        $role = isset( $_POST['role'] ) ? sanitize_key( $_POST['role'] ) : 'member';
        
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email address', 'orbit-import' ) ), 400 );
        }
        
        $result = $this->add_user_to_group( $email, $first_name, $last_name, $group_id, $role );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
        }
        
        wp_send_json_success( $result );
    }
    
    public function handle_batch_process() {
        if ( ! $this->verify_ajax_request() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'orbit-import' ) ), 403 );
        }
        
        $file_id = isset( $_POST['file_id'] ) ? sanitize_text_field( $_POST['file_id'] ) : '';
        $mapping = isset( $_POST['mapping'] ) ? (array) $_POST['mapping'] : array();
        $batch_size = isset( $_POST['batch_size'] ) ? (int) $_POST['batch_size'] : 10;
        $offset = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;
        
        if ( empty( $file_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid file ID', 'orbit-import' ) ), 400 );
        }
        
        $file_data = get_transient( 'oui_file_' . $file_id );
        if ( ! $file_data ) {
            wp_send_json_error( array( 'message' => __( 'File data not found or expired', 'orbit-import' ) ), 400 );
        }
        
        $group_id = $file_data['group_id'];
        if ( ! $this->user_can_import( $group_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'orbit-import' ) ), 403 );
        }
        
        $result = $this->process_batch( $file_data, $mapping, $batch_size, $offset );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
        }
        
        wp_send_json_success( $result );
    }
    
    public function handle_get_preview() {
        if ( ! $this->verify_ajax_request() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'orbit-import' ) ), 403 );
        }
        
        $file_id = isset( $_POST['file_id'] ) ? sanitize_text_field( $_POST['file_id'] ) : '';
        
        if ( empty( $file_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid file ID', 'orbit-import' ) ), 400 );
        }
        
        $file_data = get_transient( 'oui_file_' . $file_id );
        if ( ! $file_data ) {
            wp_send_json_error( array( 'message' => __( 'File data not found or expired', 'orbit-import' ) ), 400 );
        }
        
        $group_id = $file_data['group_id'];
        if ( ! $this->user_can_import( $group_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'orbit-import' ) ), 403 );
        }
        
        // Return file info for preview
        wp_send_json_success( array(
            'headers' => $file_data['headers'],
            'file_ext' => $file_data['file_ext']
        ) );
    }
    
    public function buddyboss_not_active_notice() {
        if ( current_user_can( 'administrator' ) ) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>ORBIT Import:</strong> BuddyBoss or BuddyPress is not active. The import functionality requires one of these plugins to be installed and activated.';
            echo '</p></div>';
        }
    }
    
    private function user_can_import( $group_id = null ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        
        $user_id = get_current_user_id();
        
        // Check if user is administrator
        if ( user_can( $user_id, 'administrator' ) ) {
            return true;
        }
        
        // Check if user is group moderator or admin
        if ( $group_id && BuddyBoss::is_active() ) {
            if ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $user_id, $group_id ) ) {
                return true;
            }
            if ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id ) ) {
                return true;
            }
        }
        
        return false;
    }
    
    private function verify_ajax_request() {
        return check_ajax_referer( 'oui_frontend', 'nonce', false ) && is_user_logged_in();
    }
    
    private function add_user_to_group( $email, $first_name, $last_name, $group_id, $role = 'member' ) {
        $user = get_user_by( 'email', $email );
        $is_new = false;
        
        if ( ! $user ) {
            // Create new user
            $username = sanitize_user( strstr( $email, '@', true ) );
            if ( username_exists( $username ) ) {
                $username = $username . '_' . time();
            }
            
            $password = wp_generate_password( 20, true, true );
            
            $user_data = array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass' => $password,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => trim( $first_name . ' ' . $last_name ),
                'role' => 'subscriber'
            );
            
            $user_id = wp_insert_user( $user_data );
            
            if ( is_wp_error( $user_id ) ) {
                return $user_id;
            }
            
            $is_new = true;
            $user = get_user_by( 'id', $user_id );
        }
        
        // Add user to group
        if ( BuddyBoss::is_active() ) {
            BuddyBoss::join_group( $group_id, $user->ID );
            BuddyBoss::promote( $group_id, $user->ID, $role );
        }
        
        return array(
            'user_id' => $user->ID,
            'is_new' => $is_new,
            'message' => $is_new ? __( 'New user created and added to group', 'orbit-import' ) : __( 'User already exists and has been added to group', 'orbit-import' )
        );
    }
    
    private function process_batch( $file_data, $mapping, $batch_size, $offset ) {
        $file_path = $file_data['file_path'];
        $file_ext = $file_data['file_ext'];
        $group_id = $file_data['group_id'];
        
        $headers = $file_data['headers'];
        $header_map = array();
        foreach ( $headers as $i => $h ) {
            $header_map[ strtolower( trim( (string) $h ) ) ] = (int) $i;
        }
        
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $count = 0;
        
        // Get iterator
        if ( 'csv' === $file_ext ) {
            $dialect = CSV::detect_dialect( $file_path );
            $gen = CSV::iterate( $file_path, $dialect['delimiter'], $dialect['enclosure'], $dialect['escape'] );
        } else {
            $gen = XLSX::iterate( $file_path );
        }
        
        $index = -1;
        foreach ( $gen as $row ) {
            $index++;
            if ( $index === 0 ) continue; // Skip header
            if ( $index <= $offset ) continue; // Skip to offset
            
            $get_val = function( $key ) use ( $mapping, $header_map, $row ) {
                $col = isset( $mapping[ $key ] ) && $mapping[ $key ] !== '' ? strtolower( $mapping[ $key ] ) : $key;
                if ( isset( $header_map[ $col ] ) && isset( $row[ $header_map[ $col ] ] ) ) {
                    return trim( (string) $row[ $header_map[ $col ] ] );
                }
                return '';
            };
            
            $email = sanitize_email( $get_val( 'email' ) );
            if ( empty( $email ) || ! is_email( $email ) ) {
                $skipped++;
                $errors++;
                $count++;
                if ( $count >= $batch_size ) break;
                continue;
            }
            
            $first_name = $get_val( 'first_name' );
            $last_name = $get_val( 'last_name' );
            $role = $get_val( 'role' );
            if ( ! in_array( $role, array( 'member', 'mod', 'admin' ), true ) ) {
                $role = 'member';
            }
            
            $result = $this->add_user_to_group( $email, $first_name, $last_name, $group_id, $role );
            
            if ( is_wp_error( $result ) ) {
                $skipped++;
                $errors++;
            } else {
                if ( $result['is_new'] ) {
                    $created++;
                } else {
                    $updated++;
                }
            }
            
            $count++;
            if ( $count >= $batch_size ) break;
        }
        
        return array(
            'processed' => $count,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'offset' => $offset + $count,
            'has_more' => $count >= $batch_size
        );
    }
}
