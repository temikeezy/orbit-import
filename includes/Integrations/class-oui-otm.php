<?php
namespace OUI\Integrations;

defined( 'ABSPATH' ) || exit;

class OTM {
	public static function apply_role( $user_id, $otm_role ) {
		$user_id = (int) $user_id;
		$otm_role = sanitize_key( (string) $otm_role );
		if ( $user_id <= 0 || '' === $otm_role ) {
			return false;
		}
		update_user_meta( $user_id, 'otm_role', $otm_role );
		return true;
	}
}
