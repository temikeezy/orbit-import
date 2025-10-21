<?php
/**
 * Plugin Name: ORBIT Bulk User Importer
 * Description: Frontend bulk import users and assign them to BuddyBoss/BuddyPress Groups. Add individual users or import from CSV/Excel files directly from group tabs. Role-based access for moderators and above.
 * Version: 2.0.0
 * Author: Ilorin Innovation Hub
 * Text Domain: orbit-import
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'OUI_PAGE_SLUG' ) ) {
	define( 'OUI_PAGE_SLUG', 'orbit-import' );
}
if ( ! defined( 'OUI_CAP' ) ) {
	define( 'OUI_CAP', 'orbit_import_users' );
}

if ( ! defined( 'OUI_PLUGIN_FILE' ) ) {
	define( 'OUI_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'OUI_PLUGIN_DIR' ) ) {
	define( 'OUI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'OUI_PLUGIN_URL' ) ) {
	define( 'OUI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'OUI_VERSION' ) ) {
	define( 'OUI_VERSION', '2.0.0' );
}
if ( ! defined( 'OUI_CAP_IMPORT' ) ) {
	define( 'OUI_CAP_IMPORT', OUI_CAP );
}

require_once OUI_PLUGIN_DIR . 'includes/Support/class-oui-autoloader.php';
if ( class_exists( 'OUI\\Support\\Autoloader' ) ) {
	OUI\Support\Autoloader::register( 'OUI\\', OUI_PLUGIN_DIR . 'includes/' );
}

add_action( 'plugins_loaded', static function () {
	load_plugin_textdomain( 'orbit-import', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

register_activation_hook( __FILE__, static function () {
	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( OUI_CAP_IMPORT ) ) {
		$role->add_cap( OUI_CAP_IMPORT );
	}
	if ( class_exists( 'OUI\\Import\\Job' ) ) {
		OUI\Import\Job::register_cpt();
	}
	flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, static function () {
	flush_rewrite_rules();
} );

add_action( 'init', static function () {
	if ( class_exists( 'OUI\\Import\\Job' ) ) {
		OUI\Import\Job::register_cpt();
	}
} );

// Frontend group integration - no more backend admin interface
add_action( 'init', static function () {
	if ( class_exists( 'OUI\\Frontend\\Group_Integration' ) ) {
		new OUI\Frontend\Group_Integration();
	}
} );

// Admin settings removed - frontend only

// Admin scripts removed - frontend only

// Old admin AJAX handlers removed - using frontend handlers now

// Old admin post handlers removed - using frontend handlers now

// Old mapping AJAX handler removed - using frontend handlers now

// Old dry run AJAX handler removed - using frontend handlers now

// Old batch run AJAX handler removed - using frontend handlers now

require_once OUI_PLUGIN_DIR . 'includes/Support/class-oui-security.php';
require_once OUI_PLUGIN_DIR . 'includes/Support/class-oui-utils.php';
require_once OUI_PLUGIN_DIR . 'includes/Support/class-oui-csv.php';
require_once OUI_PLUGIN_DIR . 'includes/Support/class-oui-xlsx.php';
require_once OUI_PLUGIN_DIR . 'includes/Integrations/class-oui-buddyboss.php';
require_once OUI_PLUGIN_DIR . 'includes/Integrations/class-oui-otm.php';
require_once OUI_PLUGIN_DIR . 'includes/Import/class-oui-job.php';
require_once OUI_PLUGIN_DIR . 'includes/Import/class-oui-mapper.php';
require_once OUI_PLUGIN_DIR . 'includes/Import/class-oui-logger.php';
require_once OUI_PLUGIN_DIR . 'includes/Import/class-oui-runner.php';
require_once OUI_PLUGIN_DIR . 'includes/Admin/class-oui-admin.php';
require_once OUI_PLUGIN_DIR . 'includes/Admin/class-oui-jobs-table.php';
require_once OUI_PLUGIN_DIR . 'includes/Admin/class-oui-settings.php';

// Frontend integration
require_once OUI_PLUGIN_DIR . 'includes/Frontend/class-oui-group-integration.php';
