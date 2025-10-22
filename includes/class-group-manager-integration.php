<?php
/**
 * Group Manager Integration Class
 * 
 * Handles integration with BuddyBoss group management interface
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OGMI_Group_Manager_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }
    
    /**
     * Initialize the integration
     */
    public function init() {
        // Hook into BuddyBoss group management - try multiple hooks for better compatibility
        add_action( 'bp_after_group_admin_content', array( $this, 'add_import_interface' ) );
        add_action( 'bp_after_group_members_list', array( $this, 'add_import_interface' ) );
        add_action( 'bp_groups_members_template', array( $this, 'add_import_interface' ) );
        add_action( 'bp_template_content', array( $this, 'add_import_interface' ) );
        
        // Try alternative approach
        add_action( 'bp_before_group_members_list', array( $this, 'add_import_interface_alternative' ) );
        add_action( 'bp_after_group_members_list', array( $this, 'add_import_interface_alternative' ) );
        
        // Add debug hook to see what's happening (disabled for production)
        add_action( 'wp_footer', array( $this, 'debug_info' ) );
        
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_ogmi_add_member', array( $this, 'handle_add_member' ) );
        add_action( 'wp_ajax_ogmi_upload_file', array( $this, 'handle_file_upload' ) );
        add_action( 'wp_ajax_ogmi_process_batch', array( $this, 'handle_batch_process' ) );
        add_action( 'wp_ajax_ogmi_get_file_preview', array( $this, 'handle_get_preview' ) );
    }
    
    /**
     * Add import interface to group management
     */
    public function add_import_interface() {
        // Only show on members management page
        if ( ! $this->is_members_management_page() ) {
            return;
        }
        
        // Check permissions
        if ( ! $this->user_can_import() ) {
            return;
        }
        
        // Include the template
        $template_path = OGMI_PLUGIN_DIR . 'templates/members-import-interface.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    }
    
    /**
     * Alternative method to add import interface - try different approach
     */
    public function add_import_interface_alternative() {
        // Check if we're on a group page and user can import
        if ( ! bp_is_group() || ! $this->user_can_import() ) {
            return;
        }
        
        // Check if we're in the members section - BuddyBoss uses 'manage-members'
        $current_action = bp_action_variable( 0 );
        if ( $current_action !== 'members' && $current_action !== 'manage-members' && ! empty( $current_action ) ) {
            return;
        }
        
        // Add the interface
        $template_path = OGMI_PLUGIN_DIR . 'templates/members-import-interface.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    }
    
    /**
     * Check if we're on the members management page
     */
    private function is_members_management_page() {
        // Check if we're in group admin and on members page
        if ( ! function_exists( 'bp_is_group_admin_page' ) || ! bp_is_group_admin_page() ) {
            return false;
        }
        
        // Check the current action - BuddyBoss uses 'manage-members' for the members management page
        $current_action = bp_action_variable( 0 );
        return $current_action === 'members' || $current_action === 'manage-members' || empty( $current_action );
    }
    
    /**
     * Debug information
     */
    public function debug_info() {
        // Only show debug info to administrators and only on group pages
        if ( ! current_user_can( 'administrator' ) || ! bp_is_group() ) {
            return;
        }
        
        echo '<div style="position: fixed; bottom: 10px; right: 10px; background: #000; color: #fff; padding: 10px; font-size: 12px; z-index: 9999; max-width: 300px;">';
        echo '<strong>OGMI Debug Info:</strong><br>';
        echo 'Is Group: ' . ( bp_is_group() ? 'Yes' : 'No' ) . '<br>';
        echo 'Is Group Admin: ' . ( function_exists( 'bp_is_group_admin_page' ) && bp_is_group_admin_page() ? 'Yes' : 'No' ) . '<br>';
        echo 'Current Action: ' . ( bp_action_variable( 0 ) ?: 'None' ) . '<br>';
        echo 'Group ID: ' . ( bp_get_current_group_id() ?: 'None' ) . '<br>';
        echo 'User Can Import: ' . ( $this->user_can_import() ? 'Yes' : 'No' ) . '<br>';
        echo 'Is Members Page: ' . ( $this->is_members_management_page() ? 'Yes' : 'No' ) . '<br>';
        echo '</div>';
    }
    
    /**
     * Check if current user can import members
     */
    private function user_can_import() {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $group_id = bp_get_current_group_id();
        
        if ( ! $group_id ) {
            return false;
        }
        
        // Check if user is site administrator
        if ( user_can( $user_id, 'administrator' ) ) {
            return true;
        }
        
        // Check if user is group administrator
        if ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id ) ) {
            return true;
        }
        
        // Check if user is group moderator
        if ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $user_id, $group_id ) ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on group management pages
        if ( ! $this->is_members_management_page() ) {
            error_log('OGMI: Not on members management page, skipping script enqueue');
            return;
        }
        
        error_log('OGMI: Enqueuing scripts for members management page');
        error_log('OGMI: User can import: ' . ($this->user_can_import() ? 'YES' : 'NO'));
        
        // Enqueue styles
        wp_enqueue_style(
            'ogmi-group-manager',
            OGMI_PLUGIN_URL . 'assets/css/group-manager.css',
            array(),
            OGMI_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'ogmi-group-manager',
            OGMI_PLUGIN_URL . 'assets/js/group-manager.js',
            array( 'jquery' ),
            OGMI_VERSION,
            true
        );
        
        $group_id = bp_get_current_group_id();
        $nonce = wp_create_nonce( 'ogmi_import' );
        
        error_log('OGMI: Group ID: ' . $group_id);
        error_log('OGMI: Nonce: ' . $nonce);
        
        // Localize script
        wp_localize_script( 'ogmi-group-manager', 'OGMI', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => $nonce,
            'groupId' => $group_id,
            'strings' => array(
                'uploading' => __( 'Uploading...', OGMI_TEXT_DOMAIN ),
                'processing' => __( 'Processing...', OGMI_TEXT_DOMAIN ),
                'success' => __( 'Success!', OGMI_TEXT_DOMAIN ),
                'error' => __( 'Error occurred', OGMI_TEXT_DOMAIN ),
                'selectFile' => __( 'Please select a file', OGMI_TEXT_DOMAIN ),
                'invalidFile' => __( 'Invalid file type. Please upload CSV or Excel file.', OGMI_TEXT_DOMAIN ),
                'fileTooLarge' => __( 'File too large', OGMI_TEXT_DOMAIN ),
                'userAdded' => __( 'User added successfully', OGMI_TEXT_DOMAIN ),
                'userExists' => __( 'User already exists and has been added to group', OGMI_TEXT_DOMAIN ),
                'userCreated' => __( 'New user created and added to group', OGMI_TEXT_DOMAIN ),
                'emailRequired' => __( 'Email address is required', OGMI_TEXT_DOMAIN ),
                'invalidEmail' => __( 'Please enter a valid email address', OGMI_TEXT_DOMAIN ),
                'importComplete' => __( 'Import completed successfully!', OGMI_TEXT_DOMAIN ),
            )
        ) );
    }
    
    /**
     * Handle individual member addition
     */
    public function handle_add_member() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ogmi_import' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', OGMI_TEXT_DOMAIN ) ) );
        }
        
        // Check permissions
        if ( ! $this->user_can_import() ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', OGMI_TEXT_DOMAIN ) ) );
        }
        
        // Get and validate data
        $email = sanitize_email( $_POST['email'] );
        $first_name = sanitize_text_field( $_POST['first_name'] );
        $last_name = sanitize_text_field( $_POST['last_name'] );
        $role = sanitize_key( $_POST['role'] );
        $group_id = (int) $_POST['group_id'];
        
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email address', OGMI_TEXT_DOMAIN ) ) );
        }
        
        if ( ! in_array( $role, array( 'member', 'mod', 'admin' ), true ) ) {
            $role = 'member';
        }
        
        // Use user manager to add member
        $user_manager = new OGMI_User_Manager();
        $result = $user_manager->add_member_to_group( $email, $first_name, $last_name, $group_id, $role );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        
        wp_send_json_success( $result );
    }
    
    /**
     * Handle file upload
     */
    public function handle_file_upload() {
        error_log('OGMI: File upload handler called');
        error_log('OGMI: POST data: ' . print_r($_POST, true));
        error_log('OGMI: FILES data: ' . print_r($_FILES, true));
        
        // Check if nonce exists
        if ( ! isset( $_POST['nonce'] ) ) {
            error_log('OGMI: No nonce provided');
            wp_send_json_error( array( 'message' => __( 'No security token provided', OGMI_TEXT_DOMAIN ) ) );
        }
        
        error_log('OGMI: Nonce received: ' . $_POST['nonce']);
        error_log('OGMI: Nonce verification result: ' . (wp_verify_nonce( $_POST['nonce'], 'ogmi_import' ) ? 'PASS' : 'FAIL'));
        
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ogmi_import' ) ) {
            error_log('OGMI: Nonce verification failed');
            wp_send_json_error( array( 'message' => __( 'Security check failed', OGMI_TEXT_DOMAIN ) ) );
        }
        
        // Check permissions
        if ( ! $this->user_can_import() ) {
            error_log('OGMI: User cannot import');
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', OGMI_TEXT_DOMAIN ) ) );
        }
        
        // Check if file was uploaded
        if ( empty( $_FILES['file'] ) || ! isset( $_FILES['file']['tmp_name'] ) ) {
            error_log('OGMI: No file uploaded');
            wp_send_json_error( array( 'message' => __( 'No file uploaded', OGMI_TEXT_DOMAIN ) ) );
        }
        
        $file = $_FILES['file'];
        $group_id = (int) $_POST['group_id'];
        
        error_log('OGMI: Processing file upload for group ' . $group_id);
        error_log('OGMI: File details: ' . print_r($file, true));
        
        // Use file processor to handle upload
        $file_processor = new OGMI_File_Processor();
        $result = $file_processor->process_upload( $file, $group_id );
        
        if ( is_wp_error( $result ) ) {
            error_log('OGMI: File processing error: ' . $result->get_error_message());
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        
        error_log('OGMI: File processing successful: ' . print_r($result, true));
        wp_send_json_success( $result );
    }
    
    /**
     * Handle batch processing
     */
    public function handle_batch_process() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ogmi_import' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', OGMI_TEXT_DOMAIN ) ) );
        }
        
        // Check permissions
        if ( ! $this->user_can_import() ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', OGMI_TEXT_DOMAIN ) ) );
        }
        
        $file_id = sanitize_text_field( $_POST['file_id'] );
        $mapping = (array) $_POST['mapping'];
        $batch_size = (int) $_POST['batch_size'];
        $offset = (int) $_POST['offset'];
        $group_id = (int) $_POST['group_id'];
        
        // Use file processor to process batch
        $file_processor = new OGMI_File_Processor();
        $result = $file_processor->process_batch( $file_id, $mapping, $batch_size, $offset, $group_id );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        
        // If import is complete, clean up the file
        if ( isset( $result['has_more'] ) && ! $result['has_more'] ) {
            $file_processor->cleanup_file( $file_id );
        }
        
        wp_send_json_success( $result );
    }
    
    /**
     * Handle get file preview
     */
    public function handle_get_preview() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ogmi_import' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', OGMI_TEXT_DOMAIN ) ) );
        }
        
        // Check permissions
        if ( ! $this->user_can_import() ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', OGMI_TEXT_DOMAIN ) ) );
        }
        
        $file_id = sanitize_text_field( $_POST['file_id'] );
        
        // Use file processor to get preview
        $file_processor = new OGMI_File_Processor();
        $result = $file_processor->get_file_preview( $file_id );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        
        wp_send_json_success( $result );
    }
}
