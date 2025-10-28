<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OGMI_REST_Controller extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'ogmi/v1';
		$this->rest_base = 'import';
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/import/upload', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'upload' ),
			'permission_callback' => array( $this, 'can_import' ),
			'args' => array(),
		) );

		register_rest_route( $this->namespace, '/import/process', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'process' ),
			'permission_callback' => array( $this, 'can_import' ),
		) );

		register_rest_route( $this->namespace, '/member/add', array(
			'path'     => '/member/add',
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'add_member' ),
			'permission_callback' => array( $this, 'can_import' ),
		) );
	}

	public function can_import( WP_REST_Request $request ) {
		if ( ! function_exists( 'bp_get_current_group_id' ) ) {
			return current_user_can( 'manage_options' );
		}
		$group_id = (int) $request->get_param( 'group_id' );
		$user_id  = get_current_user_id();
		$allowed = false;
		if ( user_can( $user_id, 'administrator' ) ) {
			$allowed = true;
		} elseif ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id ) ) {
			$allowed = true;
		} elseif ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $user_id, $group_id ) ) {
			$allowed = true;
		}
		return (bool) apply_filters( 'ogmi_user_can_import', $allowed, $user_id, $group_id );
	}

	public function upload( WP_REST_Request $request ) {
		if ( empty( $_FILES['file'] ) ) {
			return new WP_Error( 'no_file', __( 'No file uploaded', OGMI_TEXT_DOMAIN ), array( 'status' => 400 ) );
		}
		$group_id = (int) $request->get_param( 'group_id' );
		$file_processor = new OGMI_File_Processor();
		$result = $file_processor->process_upload( $_FILES['file'], $group_id );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'message' => $result->get_error_message() ), 400 );
		}
		return new WP_REST_Response( $result, 200 );
	}

	public function process( WP_REST_Request $request ) {
		$params = $request->get_params();
		$file_id = sanitize_text_field( $params['file_id'] ?? '' );
		$mapping = (array) ( $params['mapping'] ?? array() );
		$batch_size = (int) ( $params['batch_size'] ?? 50 );
		$offset = (int) ( $params['offset'] ?? 0 );
		$group_id = (int) ( $params['group_id'] ?? 0 );

		$file_processor = new OGMI_File_Processor();
		$result = $file_processor->process_batch( $file_id, $mapping, $batch_size, $offset, $group_id );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'message' => $result->get_error_message() ), 400 );
		}
		return new WP_REST_Response( $result, 200 );
	}

	public function add_member( WP_REST_Request $request ) {
		$params = $request->get_params();
		$email = sanitize_email( $params['email'] ?? '' );
		$first_name = sanitize_text_field( $params['first_name'] ?? '' );
		$last_name = sanitize_text_field( $params['last_name'] ?? '' );
		$role = sanitize_key( $params['role'] ?? 'member' );
		$group_id = (int) ( $params['group_id'] ?? 0 );

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Invalid email address', OGMI_TEXT_DOMAIN ) ), 400 );
		}
		if ( ! in_array( $role, array( 'member', 'mod', 'admin' ), true ) ) {
			$role = 'member';
		}

		$user_manager = new OGMI_User_Manager();
		$result = $user_manager->add_member_to_group( $email, $first_name, $last_name, $group_id, $role );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'message' => $result->get_error_message() ), 400 );
		}
		return new WP_REST_Response( $result, 200 );
	}
}


