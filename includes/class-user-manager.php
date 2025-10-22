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
        error_log('OGMI: add_member_to_group called with email: ' . $email . ', first_name: ' . $first_name . ', last_name: ' . $last_name . ', group_id: ' . $group_id . ', role: ' . $role);
        
        // Validate inputs
        if ( empty( $email ) || ! is_email( $email ) ) {
            error_log('OGMI: Invalid email address: ' . $email);
            return new WP_Error( 'invalid_email', __( 'Invalid email address', OGMI_TEXT_DOMAIN ) );
        }
        
        if ( ! $group_id || ! $this->group_exists( $group_id ) ) {
            error_log('OGMI: Invalid group ID: ' . $group_id);
            return new WP_Error( 'invalid_group', __( 'Invalid group', OGMI_TEXT_DOMAIN ) );
        }
        
        if ( ! in_array( $role, array( 'member', 'mod', 'admin' ), true ) ) {
            $role = 'member';
        }
        
        // Check if user already exists
        $user = get_user_by( 'email', $email );
        $is_new_user = false;
        
        if ( ! $user ) {
            error_log('OGMI: User does not exist, creating new user for email: ' . $email);
            // Create new user
            $user_result = $this->create_user( $email, $first_name, $last_name );
            if ( is_wp_error( $user_result ) ) {
                error_log('OGMI: User creation failed: ' . $user_result->get_error_message());
                return $user_result;
            }
            
            $user = $user_result;
            $is_new_user = true;
            error_log('OGMI: New user created successfully with ID: ' . $user->ID);
        } else {
            error_log('OGMI: User already exists with ID: ' . $user->ID);
        }
        
        // Check if user is already a member of the group
        if ( $this->is_user_member_of_group( $user->ID, $group_id ) ) {
            error_log('OGMI: User is already a member of group: ' . $group_id);
            return new WP_Error( 'already_member', __( 'User is already a member of this group', OGMI_TEXT_DOMAIN ) );
        }
        
        // Add user to group
        error_log('OGMI: Adding user ' . $user->ID . ' to group ' . $group_id);
        $group_result = $this->add_user_to_group( $user->ID, $group_id );
        if ( is_wp_error( $group_result ) ) {
            error_log('OGMI: Failed to add user to group: ' . $group_result->get_error_message());
            return $group_result;
        }
        error_log('OGMI: Successfully added user to group');
        
        // Set user role in group
        if ( $role !== 'member' ) {
            error_log('OGMI: Setting user role to: ' . $role);
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
        // Check if welcome emails are enabled
        $send_welcome = apply_filters( 'ogmi_send_welcome_email', true, $user_id );
        
        if ( ! $send_welcome ) {
            return;
        }
        
        // Send WordPress new user notification
        wp_new_user_notification( $user_id, null, 'user' );
        
        // You can also send a custom welcome email here
        do_action( 'ogmi_user_created', $user_id, $password );
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
