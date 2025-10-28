<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * OGMI WP-CLI commands.
	 */
	class OGMI_CLI_Command {
		/**
		 * Create BuddyBoss email template slug `orbit-welcome` if missing.
		 *
		 * ## EXAMPLES
		 *
		 *     wp ogmi create-buddyboss-template
		 */
		public function create_buddyboss_template( $args, $assoc_args ) {
			if ( ! function_exists( 'bp_send_email' ) ) {
				WP_CLI::warning( 'BuddyBoss/BuddyPress emails are not available. Skipping.' );
				return;
			}

			$template_type = apply_filters( 'ogmi_welcome_email_template', 'orbit-welcome', null );

			// If the template exists, we consider it done.
			if ( function_exists( 'bp_email_get_post_by_type' ) ) {
				$existing = bp_email_get_post_by_type( $template_type );
				if ( $existing ) {
					WP_CLI::success( "Email template '{$template_type}' already exists (ID: {$existing})." );
					return;
				}
			}

			$subject = 'Welcome to {{site.name}}';
			$content = '<p>Hi {{recipient.name}},</p>
			<p>Welcome to <strong>{{site.name}}</strong>!</p>
			<p>Click below to set your password and get started:</p>
			<p><a href="{{reset.url}}">{{reset.url}}</a></p>
			<p>See you inside — <a href="{{site.url}}">{{site.url}}</a></p>';

			$postarr = array(
				'post_type'   => bp_get_email_post_type(),
				'post_status' => 'publish',
				'post_title'  => $template_type,
			);
			$post_id = wp_insert_post( $postarr, true );
			if ( is_wp_error( $post_id ) ) {
				WP_CLI::error( 'Failed to create email post: ' . $post_id->get_error_message() );
				return;
			}

			bp_update_email( array(
				'id'      => $post_id,
				'args'    => array(
					'post_content' => $content,
					'post_excerpt' => $subject,
				),
				'tax_input' => array(
					bp_get_email_tax_type() => array( $template_type ),
				),
			) );

			WP_CLI::success( "Created BuddyBoss email template '{$template_type}' (ID: {$post_id})." );
		}
	}

	WP_CLI::add_command( 'ogmi', 'OGMI_CLI_Command' );
}


