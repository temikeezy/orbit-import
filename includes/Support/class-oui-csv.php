<?php
namespace OUI\Support;

use Generator;
use SplFileObject;

defined( 'ABSPATH' ) || exit;

class CSV {
	public static function detect_dialect( $file ) {
		$delims = array( ',', ';', "\t" );
		$best   = ',';
		$max    = 0;
		$handle = fopen( $file, 'rb' );
		if ( ! $handle ) {
			return array( 'delimiter' => ',', 'enclosure' => '"', 'escape' => '\\' );
		}
		$line = fgets( $handle );
		fclose( $handle );
		$line = self::strip_bom( (string) $line );
		foreach ( $delims as $d ) {
			$c = substr_count( $line, $d );
			if ( $c > $max ) {
				$max  = $c;
				$best = $d;
			}
		}
		return array( 'delimiter' => $best, 'enclosure' => '"', 'escape' => '\\' );
	}

	public static function iterate( $file, $delimiter = ',', $enclosure = '"', $escape = '\\' ) {
		$csv = new SplFileObject( $file, 'r' );
		$csv->setFlags( SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE );
		$csv->setCsvControl( $delimiter, $enclosure, $escape );
		$first = true;
		foreach ( $csv as $row ) {
			if ( $first ) {
				$first = false;
				if ( is_array( $row ) && isset( $row[0] ) ) {
					$row[0] = self::strip_bom( (string) $row[0] );
				}
			}
			yield is_array( $row ) ? array_map( 'strval', $row ) : array();
		}
	}

	private static function strip_bom( $s ) {
		if ( substr( $s, 0, 3 ) === "\xEF\xBB\xBF" ) {
			return substr( $s, 3 );
		}
		return $s;
	}
}
