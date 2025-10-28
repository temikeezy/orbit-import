<?php
/**
 * User Manager Class
 * 
 * Handles user creation and group membership management
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OGMI_User_Manager {
    
    /**
     * Add member to group
     */
    public function add_member_to_group( $email, $first_name, $last_name, $group_id, $role = 'member' ) {
        // Validate inputs
        if ( empty( $email ) || ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', __( 'Invalid email address', OGMI_TEXT_DOMAIN ) );
        }
        
        if ( ! $group_id || ! $this->group_exists( $group_id ) ) {
            return new WP_Error( 'invalid_group', __( 'Invalid group', OGMI_TEXT_DOMAIN ) );
        }
        
        if ( ! in_array( $role, array( 'member', 'mod', 'admin' ), true ) ) {
            $role = 'member';
        }
        
        // Check if user already exists
        $user = get_user_by( 'email', $email );
        $is_new_user = false;
        
        if ( ! $user ) {
            // Create new user
            $user_result = $this->create_user( $email, $first_name, $last_name );
            if ( is_wp_error( $user_result ) ) {
                return $user_result;
            }
            
            $user = $user_result;
            $is_new_user = true;
        }
        
        // Check if user is already a member of the group
        if ( $this->is_user_member_of_group( $user->ID, $group_id ) ) {
            return new WP_Error( 'already_member', __( 'User is already a member of this group', OGMI_TEXT_DOMAIN ) );
        }
        
        // Add user to group
        $group_result = $this->add_user_to_group( $user->ID, $group_id );
        if ( is_wp_error( $group_result ) ) {
            return $group_result;
        }
        
        // Set user role in group
        if ( $role !== 'member' ) {
            $this->set_user_group_role( $user->ID, $group_id, $role );
        }
        
        return array(
            'user_id' => $user->ID,
            'is_new' => $is_new_user,
            'message' => $is_new_user ? 
                __( 'New user created and added to group', OGMI_TEXT_DOMAIN ) : 
                __( 'Existing user added to group', OGMI_TEXT_DOMAIN )
        );
    }
    
    /**
     * Create new WordPress user
     */
    private function create_user( $email, $first_name, $last_name ) {
        // Generate username from email
        $username = $this->generate_username( $email );
        
        // Generate secure password
        $password = wp_generate_password( 20, true, true );
        
        // Prepare user data
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim( $first_name . ' ' . $last_name ),
            'role' => 'subscriber'
        );
        
        // Create user
        $user_id = wp_insert_user( $user_data );
        
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        // Send welcome email (optional)
        $this->send_welcome_email( $user_id, $password );
        
        return get_user_by( 'id', $user_id );
    }
    
    /**
     * Generate unique username from email
     */
    private function generate_username( $email ) {
        $base_username = sanitize_user( strstr( $email, '@', true ) );
        
        // If base username is empty or too short, use a fallback
        if ( empty( $base_username ) || strlen( $base_username ) < 3 ) {
            $base_username = 'user' . time();
        }
        
        // Ensure username is unique
        $username = $base_username;
        $counter = 1;
        
        while ( username_exists( $username ) ) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Check if group exists
     */
    private function group_exists( $group_id ) {
        if ( ! function_exists( 'groups_get_group' ) ) {
            return false;
        }
        
        $group = groups_get_group( $group_id );
        return ! empty( $group->id );
    }
    
    /**
     * Check if user is already a member of the group
     */
    private function is_user_member_of_group( $user_id, $group_id ) {
        if ( ! function_exists( 'groups_is_user_member' ) ) {
            return false;
        }
        
        return groups_is_user_member( $user_id, $group_id );
    }
    
    /**
     * Add user to group
     */
    private function add_user_to_group( $user_id, $group_id ) {
        if ( ! function_exists( 'groups_join_group' ) ) {
            return new WP_Error( 'function_not_available', __( 'BuddyBoss function not available', OGMI_TEXT_DOMAIN ) );
        }
        
        $result = groups_join_group( $group_id, $user_id );
        
        if ( ! $result ) {
            return new WP_Error( 'join_failed', __( 'Failed to add user to group', OGMI_TEXT_DOMAIN ) );
        }
        
        return true;
    }
    
    /**
     * Set user role in group
     */
    private function set_user_group_role( $user_id, $group_id, $role ) {
        if ( $role === 'member' ) {
            return true; // Default role, no action needed
        }
        
        if ( ! function_exists( 'groups_promote_member' ) ) {
            return new WP_Error( 'function_not_available', __( 'BuddyBoss function not available', OGMI_TEXT_DOMAIN ) );
        }
        
        $result = groups_promote_member( $user_id, $group_id, $role );
        
        if ( ! $result ) {
            return new WP_Error( 'promote_failed', __( 'Failed to set user role in group', OGMI_TEXT_DOMAIN ) );
        }
        
        return true;
    }
    
    /**
     * Send welcome email to new user
     */
	private function send_welcome_email( $user_id, $password ) {
		// Preserve existing toggle
		$send_welcome = apply_filters( 'ogmi_send_welcome_email', true, $user_id );
		if ( ! $send_welcome ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		// Build a real reset URL now (so onboarding works even if later emails fail)
		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			// If we can’t generate a key, don’t attempt to send a broken email
			return;
		}

		$reset_url = add_query_arg(
			array(
				'action' => 'rp',
				'key'    => $key,
				'login'  => rawurlencode( $user->user_login ),
			),
			wp_login_url()
		);

		$sent = false;

		// Prefer BuddyBoss email framework if present
		if ( function_exists( 'bp_send_email' ) ) {
			$tokens = array(
				'recipient.name' => $user->display_name ?: $user->user_login,
				'reset.url'      => $reset_url,
				'site.name'      => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				'site.url'       => home_url( '/' ),
			);

			// Use a custom template slug "orbit-welcome".
			// Create this in BuddyBoss > Emails and include {{reset.url}} in the content.
			$email_type = 'orbit-welcome';

			try {
				$sent = (bool) bp_send_email( $email_type, $user_id, array( 'tokens' => $tokens ) );
			} catch ( Exception $e ) {
				if ( function_exists( 'error_log' ) ) {
					error_log( '[OGMI] bp_send_email exception: ' . $e->getMessage() );
				}
				$sent = false;
			}
		}

		// Fallback to core mail if BuddyBoss is missing or sending failed
		if ( ! $sent ) {
			$this->send_core_welcome_fallback( $user, $reset_url );
		}

		// Fire existing hook for any custom listeners (unchanged behavior)
		do_action( 'ogmi_user_created', $user_id, $password );
	}

	private function send_core_welcome_fallback( WP_User $user, $reset_url ) {
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$subject  = sprintf( 'Welcome to %s', $blogname );

		$body = sprintf(
			'<p>Hi %s,</p>
			 <p>Welcome to <strong>%s</strong>! Your account has been created.</p>
			 <p>Please set your password using the secure link below:</p>
			 <p><a href="%s">%s</a></p>
			 <p>If you didn’t request this, you can ignore this email.</p>
			 <p>— %s</p>',
			esc_html( $user->display_name ?: $user->user_login ),
			esc_html( $blogname ),
			esc_url( $reset_url ),
			esc_html( $reset_url ),
			esc_html( parse_url( home_url(), PHP_URL_HOST ) )
		);

		// Ensure HTML + AltBody. Add ephemeral filters and remove immediately after send.
		$set_html = function() { return 'text/html'; };
		add_filter( 'wp_mail_content_type', $set_html );

		$set_alt = function( $phpmailer ) use ( $body ) {
			if ( empty( $phpmailer->AltBody ) ) {
				$phpmailer->AltBody = wp_strip_all_tags( $body );
			}
		};
		add_action( 'phpmailer_init', $set_alt );

		// Optional: tiny guard rail to avoid "Message body empty"
		if ( trim( wp_strip_all_tags( $body ) ) === '' ) {
			if ( function_exists( 'error_log' ) ) {
				error_log( '[OGMI] Core welcome fallback: computed empty body; injecting placeholder.' );
			}
			$body = '<p>(Email content missing. Please contact support.)</p>';
		}

		wp_mail( $user->user_email, $subject, $body );

		// Clean up
		remove_filter( 'wp_mail_content_type', $set_html );
		remove_action( 'phpmailer_init', $set_alt );
	}
    
    /**
     * Get user group roles
     */
    public function get_user_group_roles( $user_id, $group_id ) {
        if ( ! function_exists( 'groups_is_user_admin' ) || ! function_exists( 'groups_is_user_mod' ) ) {
            return array();
        }
        
        $roles = array();
        
        if ( groups_is_user_admin( $user_id, $group_id ) ) {
            $roles[] = 'admin';
        } elseif ( groups_is_user_mod( $user_id, $group_id ) ) {
            $roles[] = 'mod';
        } else {
            $roles[] = 'member';
        }
        
        return $roles;
    }
    
    /**
     * Remove user from group
     */
    public function remove_user_from_group( $user_id, $group_id ) {
        if ( ! function_exists( 'groups_leave_group' ) ) {
            return new WP_Error( 'function_not_available', __( 'BuddyBoss function not available', OGMI_TEXT_DOMAIN ) );
        }
        
        $result = groups_leave_group( $group_id, $user_id );
        
        if ( ! $result ) {
            return new WP_Error( 'leave_failed', __( 'Failed to remove user from group', OGMI_TEXT_DOMAIN ) );
        }
        
        return true;
    }
    
    /**
     * Update user group role
     */
    public function update_user_group_role( $user_id, $group_id, $new_role ) {
        // Validate role
        if ( ! in_array( $new_role, array( 'member', 'mod', 'admin' ), true ) ) {
            return new WP_Error( 'invalid_role', __( 'Invalid role', OGMI_TEXT_DOMAIN ) );
        }
        
        // Set the new role
        return $this->set_user_group_role( $user_id, $group_id, $new_role );
    }
    
    /**
     * Get group member count
     */
    public function get_group_member_count( $group_id ) {
        if ( ! function_exists( 'groups_get_total_member_count' ) ) {
            return 0;
        }
        
        return groups_get_total_member_count( $group_id );
    }
    
    /**
     * Get group members
     */
    public function get_group_members( $group_id, $per_page = 20, $page = 1 ) {
        if ( ! function_exists( 'groups_get_group_members' ) ) {
            return array();
        }
        
        $args = array(
            'group_id' => $group_id,
            'per_page' => $per_page,
            'page' => $page,
            'exclude_admins_mods' => false
        );
        
        $members = groups_get_group_members( $args );
        
        return isset( $members['members'] ) ? $members['members'] : array();
    }
}
