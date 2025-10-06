<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
use OUI\Integrations\BuddyBoss;
$bb_active = BuddyBoss::is_active();
$groups_options = array();
if ( $bb_active ) {
	$map = BuddyBoss::name_to_id_map();
	ksort( $map );
	$groups_options = $map;
}
?>
<div class="oui-grid">
	<div class="oui-col">
		<h2><?php echo esc_html__( 'Map Columns', 'orbit-import' ); ?></h2>
		<p><?php echo esc_html__( 'Leave blank to auto-detect by matching header names.', 'orbit-import' ); ?></p>
		<table class="form-table">
			<tr><th><?php echo esc_html__( 'Email (required)', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-email" placeholder="email" /></td></tr>
			<tr><th><?php echo esc_html__( 'Username', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-username" placeholder="username" /></td></tr>
			<tr><th><?php echo esc_html__( 'First name', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-first_name" placeholder="first_name" /></td></tr>
			<tr><th><?php echo esc_html__( 'Last name', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-last_name" placeholder="last_name" /></td></tr>
			<tr><th><?php echo esc_html__( 'Display name', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-display_name" placeholder="display_name" /></td></tr>
			<tr><th><?php echo esc_html__( 'Password', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-password" placeholder="password" /></td></tr>
			<tr><th><?php echo esc_html__( 'WP Role', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-wp_role" placeholder="wp_role" /></td></tr>
			<tr><th><?php echo esc_html__( 'OTM Role', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-otm_role" placeholder="otm_role" /></td></tr>
			<tr><th><?php echo esc_html__( 'User Status', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-user_status" placeholder="user_status" /></td></tr>
			<tr><th><?php echo esc_html__( 'Streams', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-streams" placeholder="streams" /></td></tr>
			<tr><th><?php echo esc_html__( 'Stream Roles', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-stream_roles" placeholder="stream_roles" /></td></tr>
			<tr><th><?php echo esc_html__( 'Meta prefix', 'orbit-import' ); ?></th><td><input type="text" id="oui-map-meta_prefix" placeholder="meta:" /></td></tr>
		</table>
		<p>
			<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'orbit-import', 'step' => 'upload' ), admin_url( 'users.php' ) ) ); ?>"><?php echo esc_html__( 'Back', 'orbit-import' ); ?></a>
			<button class="button button-primary" id="oui-save-mapping"><?php echo esc_html__( 'Save & Continue', 'orbit-import' ); ?></button>
		</p>
	</div>
	<?php if ( $bb_active ) : ?>
	<div class="oui-col">
		<h2><?php echo esc_html__( 'Bulk Stream Assignment (optional)', 'orbit-import' ); ?></h2>
		<p><?php echo esc_html__( 'Select streams and role; choose how to merge with CSV streams.', 'orbit-import' ); ?></p>
		<label><?php echo esc_html__( 'Streams', 'orbit-import' ); ?></label>
		<select multiple size="8" id="oui-bulk-streams" style="width:100%">
			<?php foreach ( $groups_options as $name => $id ) : ?>
				<option value="<?php echo esc_attr( (int) $id ); ?>"><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
		<p>
			<label><?php echo esc_html__( 'Role', 'orbit-import' ); ?></label>
			<select id="oui-bulk-role">
				<option value="member">member</option>
				<option value="mod">mod</option>
				<option value="admin">admin</option>
			</select>
		</p>
		<p>
			<label><input type="radio" name="oui-mode" value="csv_only" checked> <?php echo esc_html__( 'CSV only', 'orbit-import' ); ?></label><br>
			<label><input type="radio" name="oui-mode" value="append_bulk"> <?php echo esc_html__( 'Append bulk', 'orbit-import' ); ?></label><br>
			<label><input type="radio" name="oui-mode" value="replace_with_bulk"> <?php echo esc_html__( 'Replace with bulk', 'orbit-import' ); ?></label>
		</p>
		<p>
			<label><input type="checkbox" id="oui-bulk-existing-only"> <?php echo esc_html__( 'Apply to existing users only', 'orbit-import' ); ?></label>
		</p>
	</div>
	<?php endif; ?>
</div>
