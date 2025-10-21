<?php
/**
 * Permission Handler Class
 * 
 * Handles role-based access control and security
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OGMI_Permission_Handler {
    
    /**
     * Check if current user can import members
     */
    public static function can_import_members( $group_id = null ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        
        $user_id = get_current_user_id();
        
        // Site administrators can always import
        if ( user_can( $user_id, 'administrator' ) ) {
            return true;
        }
        
        // If no group ID provided, get current group
        if ( ! $group_id ) {
            $group_id = bp_get_current_group_id();
        }
        
        if ( ! $group_id ) {
            return false;
        }
        
        // Check group-specific permissions
        return self::can_manage_group_members( $user_id, $group_id );
    }
    
    /**
     * Check if user can manage group members
     */
    public static function can_manage_group_members( $user_id, $group_id ) {
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
     * Check if user can add members to group
     */
    public static function can_add_members( $user_id, $group_id ) {
        return self::can_manage_group_members( $user_id, $group_id );
    }
    
    /**
     * Check if user can remove members from group
     */
    public static function can_remove_members( $user_id, $group_id ) {
        return self::can_manage_group_members( $user_id, $group_id );
    }
    
    /**
     * Check if user can change member roles
     */
    public static function can_change_member_roles( $user_id, $group_id ) {
        // Only group administrators can change roles
        if ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id ) ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user can promote members to moderator
     */
    public static function can_promote_to_moderator( $user_id, $group_id ) {
        return self::can_change_member_roles( $user_id, $group_id );
    }
    
    /**
     * Check if user can promote members to administrator
     */
    public static function can_promote_to_admin( $user_id, $group_id ) {
        return self::can_change_member_roles( $user_id, $group_id );
    }
    
    /**
     * Check if user can demote members
     */
    public static function can_demote_members( $user_id, $group_id ) {
        return self::can_change_member_roles( $user_id, $group_id );
    }
    
    /**
     * Verify AJAX request security
     */
    public static function verify_ajax_request( $nonce_action = 'ogmi_import' ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'not_logged_in', __( 'You must be logged in to perform this action', OGMI_TEXT_DOMAIN ) );
        }
        
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], $nonce_action ) ) {
            return new WP_Error( 'invalid_nonce', __( 'Security check failed', OGMI_TEXT_DOMAIN ) );
        }
        
        // Check if user can import members
        if ( ! self::can_import_members() ) {
            return new WP_Error( 'insufficient_permissions', __( 'You do not have permission to perform this action', OGMI_TEXT_DOMAIN ) );
        }
        
        return true;
    }
    
    /**
     * Get user's role in group
     */
    public static function get_user_group_role( $user_id, $group_id ) {
        if ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id ) ) {
            return 'admin';
        }
        
        if ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $user_id, $group_id ) ) {
            return 'mod';
        }
        
        if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( $user_id, $group_id ) ) {
            return 'member';
        }
        
        return 'none';
    }
    
    /**
     * Check if user is group member
     */
    public static function is_group_member( $user_id, $group_id ) {
        if ( function_exists( 'groups_is_user_member' ) ) {
            return groups_is_user_member( $user_id, $group_id );
        }
        
        return false;
    }
    
    /**
     * Check if user is group administrator
     */
    public static function is_group_admin( $user_id, $group_id ) {
        if ( function_exists( 'groups_is_user_admin' ) ) {
            return groups_is_user_admin( $user_id, $group_id );
        }
        
        return false;
    }
    
    /**
     * Check if user is group moderator
     */
    public static function is_group_moderator( $user_id, $group_id ) {
        if ( function_exists( 'groups_is_user_mod' ) ) {
            return groups_is_user_mod( $user_id, $group_id );
        }
        
        return false;
    }
    
    /**
     * Get all users who can manage group members
     */
    public static function get_group_managers( $group_id ) {
        $managers = array();
        
        if ( ! function_exists( 'groups_get_group_members' ) ) {
            return $managers;
        }
        
        // Get all group members
        $members = groups_get_group_members( array(
            'group_id' => $group_id,
            'per_page' => 999,
            'exclude_admins_mods' => false
        ) );
        
        if ( ! isset( $members['members'] ) ) {
            return $managers;
        }
        
        // Filter to only admins and moderators
        foreach ( $members['members'] as $member ) {
            $role = self::get_user_group_role( $member->ID, $group_id );
            if ( in_array( $role, array( 'admin', 'mod' ), true ) ) {
                $managers[] = $member;
            }
        }
        
        return $managers;
    }
    
    /**
     * Check if current user can access group management
     */
    public static function can_access_group_management( $group_id = null ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        
        $user_id = get_current_user_id();
        
        // Site administrators can always access
        if ( user_can( $user_id, 'administrator' ) ) {
            return true;
        }
        
        // If no group ID provided, get current group
        if ( ! $group_id ) {
            $group_id = bp_get_current_group_id();
        }
        
        if ( ! $group_id ) {
            return false;
        }
        
        // Check if user can manage group members
        return self::can_manage_group_members( $user_id, $group_id );
    }
    
    /**
     * Validate group ID
     */
    public static function validate_group_id( $group_id ) {
        if ( ! $group_id || ! is_numeric( $group_id ) ) {
            return new WP_Error( 'invalid_group_id', __( 'Invalid group ID', OGMI_TEXT_DOMAIN ) );
        }
        
        $group_id = (int) $group_id;
        
        if ( ! function_exists( 'groups_get_group' ) ) {
            return new WP_Error( 'function_not_available', __( 'BuddyBoss function not available', OGMI_TEXT_DOMAIN ) );
        }
        
        $group = groups_get_group( $group_id );
        
        if ( ! $group || ! $group->id ) {
            return new WP_Error( 'group_not_found', __( 'Group not found', OGMI_TEXT_DOMAIN ) );
        }
        
        return $group_id;
    }
    
    /**
     * Validate user ID
     */
    public static function validate_user_id( $user_id ) {
        if ( ! $user_id || ! is_numeric( $user_id ) ) {
            return new WP_Error( 'invalid_user_id', __( 'Invalid user ID', OGMI_TEXT_DOMAIN ) );
        }
        
        $user_id = (int) $user_id;
        $user = get_user_by( 'id', $user_id );
        
        if ( ! $user ) {
            return new WP_Error( 'user_not_found', __( 'User not found', OGMI_TEXT_DOMAIN ) );
        }
        
        return $user_id;
    }
    
    /**
     * Check if user can perform action on another user
     */
    public static function can_perform_action_on_user( $current_user_id, $target_user_id, $group_id, $action ) {
        // Users cannot perform actions on themselves
        if ( $current_user_id === $target_user_id ) {
            return false;
        }
        
        // Get current user's role
        $current_user_role = self::get_user_group_role( $current_user_id, $group_id );
        
        // Get target user's role
        $target_user_role = self::get_user_group_role( $target_user_id, $group_id );
        
        // Site administrators can do anything
        if ( user_can( $current_user_id, 'administrator' ) ) {
            return true;
        }
        
        // Group administrators can manage moderators and members
        if ( $current_user_role === 'admin' ) {
            return in_array( $target_user_role, array( 'mod', 'member' ), true );
        }
        
        // Group moderators can only manage members
        if ( $current_user_role === 'mod' ) {
            return $target_user_role === 'member';
        }
        
        return false;
    }
    
    /**
     * Log security events
     */
    public static function log_security_event( $event, $user_id, $group_id, $details = array() ) {
        $log_entry = array(
            'timestamp' => current_time( 'mysql' ),
            'event' => $event,
            'user_id' => $user_id,
            'group_id' => $group_id,
            'ip_address' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'details' => $details
        );
        
        // Store in WordPress options (you might want to use a proper logging system)
        $logs = get_option( 'ogmi_security_logs', array() );
        $logs[] = $log_entry;
        
        // Keep only last 100 entries
        if ( count( $logs ) > 100 ) {
            $logs = array_slice( $logs, -100 );
        }
        
        update_option( 'ogmi_security_logs', $logs );
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
        
        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) === true ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
