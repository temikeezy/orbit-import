<?php
namespace OUI\Integrations;

defined( 'ABSPATH' ) || exit;

class BuddyBoss {
	public static function is_active() {
		return function_exists( 'groups_get_groups' ) && function_exists( 'groups_join_group' );
	}
	public static function name_to_id_map() {
		if ( ! self::is_active() ) { return array(); }
		$map = array(); $groups = groups_get_groups( array( 'per_page' => 999, 'show_hidden' => true, 'orderby' => 'name', 'order' => 'ASC' ) );
		$list = isset( $groups['groups'] ) ? $groups['groups'] : array();
		foreach ( $list as $g ) { $map[ sanitize_text_field( $g->name ) ] = (int) $g->id; }
		return $map;
	}
	public static function resolve_ids_from_names( array $names, $autocreate = false ) {
		if ( ! self::is_active() ) { return array(); }
		$map = self::name_to_id_map(); $ids = array();
		foreach ( $names as $name ) {
			$name = trim( (string) $name ); if ( $name === '' ) { continue; }
			if ( isset( $map[ $name ] ) ) { $ids[] = (int) $map[ $name ]; }
			elseif ( $autocreate && function_exists( 'groups_create_group' ) ) { $gid = groups_create_group( array( 'name' => $name, 'status' => 'public' ) ); if ( $gid && ! is_wp_error( $gid ) ) { $ids[] = (int) $gid; } }
		}
		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}
	public static function join_group( $gid, $user_id ) {
		if ( ! self::is_active() ) { return false; }
		$gid = (int) $gid; $user_id = (int) $user_id; if ( $gid <= 0 || $user_id <= 0 ) { return false; }
		if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( $user_id, $gid ) ) { return true; }
		return (bool) groups_join_group( $gid, $user_id );
	}
	public static function promote( $gid, $user_id, $role ) {
		if ( ! self::is_active() ) { return false; }
		$role = in_array( $role, array( 'member', 'mod', 'admin' ), true ) ? $role : 'member'; if ( 'member' === $role ) { return true; }
		return (bool) groups_promote_member( $user_id, $gid, $role );
	}
}
