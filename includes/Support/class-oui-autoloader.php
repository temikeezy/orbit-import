<?php
namespace OUI\Support;

class Autoloader {
	/**
	 * Register PSR-4 autoloader for a namespace prefix.
	 *
	 * @param string $prefix Namespace prefix.
	 * @param string $base_dir Base directory for the namespace prefix.
	 * @return void
	 */
	public static function register( $prefix, $base_dir ) {
		spl_autoload_register( static function ( $class ) use ( $prefix, $base_dir ) {
			if ( strpos( $class, $prefix ) !== 0 ) {
				return;
			}
			$relative = substr( $class, strlen( $prefix ) );
			$relative = str_replace( '\\', '/', $relative );
			$file = rtrim( $base_dir, '/\\' ) . '/' . 'class-' . 'oui-' . strtolower( str_replace( '/', '-', $relative ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		} );
	}
}
