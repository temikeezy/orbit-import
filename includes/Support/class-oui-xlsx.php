<?php
namespace OUI\Support;

use Generator;

defined( 'ABSPATH' ) || exit;

class XLSX {
	public static function read_headers( $file ) {
		if ( ! class_exists( '\\ZipArchive' ) ) { return array(); }
		$zip = new \ZipArchive();
		if ( $zip->open( $file ) !== true ) { return array(); }
		$headers = array();
		$sstIndex = $zip->locateName( 'xl/sharedStrings.xml' );
		$shared = array();
		if ( $sstIndex !== false ) {
			$xml = simplexml_load_string( $zip->getFromIndex( $sstIndex ) );
			if ( $xml && isset( $xml->si ) ) {
				foreach ( $xml->si as $i => $si ) { $shared[ (int) $i ] = isset( $si->t ) ? (string) $si->t : (string) $si->asXML(); }
			}
		}
		$sheetIndex = $zip->locateName( 'xl/worksheets/sheet1.xml' );
		if ( $sheetIndex === false ) {
			for ( $i = 0; $i < $zip->numFiles; $i++ ) { $name = $zip->getNameIndex( $i ); if ( strpos( $name, 'xl/worksheets/sheet' ) === 0 && substr( $name, -4 ) === '.xml' ) { $sheetIndex = $i; break; } }
		}
		if ( $sheetIndex === false ) { $zip->close(); return array(); }
		$sheetXml = simplexml_load_string( $zip->getFromIndex( $sheetIndex ) );
		$zip->close();
		if ( ! $sheetXml || ! isset( $sheetXml->sheetData->row ) ) { return array(); }
		$firstRow = $sheetXml->sheetData->row[0];
		if ( ! $firstRow ) { return array(); }
		$line = array();
		foreach ( $firstRow->c as $c ) {
			$val = isset( $c->v ) ? (string) $c->v : '';
			$type = isset( $c['t'] ) ? (string) $c['t'] : '';
			if ( $type === 's' ) { $idx = (int) $val; $val = isset( $shared[ $idx ] ) ? $shared[ $idx ] : ''; }
			$line[] = (string) $val;
		}
		return array_map( 'strval', $line );
	}

	public static function iterate( $file ) {
		if ( ! class_exists( '\\ZipArchive' ) ) {
			return self::empty_generator();
		}
		$zip = new \ZipArchive();
		if ( $zip->open( $file ) !== true ) {
			return self::empty_generator();
		}
		$sharedStrings = array();
		$sstIndex = $zip->locateName( 'xl/sharedStrings.xml' );
		if ( $sstIndex !== false ) {
			$xml = simplexml_load_string( $zip->getFromIndex( $sstIndex ) );
			if ( $xml && isset( $xml->si ) ) {
				foreach ( $xml->si as $i => $si ) {
					$sharedStrings[ (int) $i ] = isset( $si->t ) ? (string) $si->t : (string) $si->asXML();
				}
			}
		}
		// naive: read first worksheet
		$sheetPath = 'xl/worksheets/sheet1.xml';
		$sheetIndex = $zip->locateName( $sheetPath );
		if ( $sheetIndex === false ) {
			// try any worksheet
			for ( $i = 0; $i < $zip->numFiles; $i++ ) {
				$name = $zip->getNameIndex( $i );
				if ( strpos( $name, 'xl/worksheets/sheet' ) === 0 && substr( $name, -4 ) === '.xml' ) { $sheetIndex = $i; break; }
			}
		}
		if ( $sheetIndex === false ) {
			$zip->close();
			return self::empty_generator();
		}
		$sheetXml = simplexml_load_string( $zip->getFromIndex( $sheetIndex ) );
		$zip->close();
		if ( ! $sheetXml ) {
			return self::empty_generator();
		}
		$ns = $sheetXml->getNamespaces(true);
		$rows = isset( $sheetXml->sheetData->row ) ? $sheetXml->sheetData->row : array();
		$maxColIndex = 0;
		foreach ( $rows as $row ) {
			$cells = array();
			$cols = $row->c;
			$line = array();
			$lastCol = 0;
			foreach ( $cols as $c ) {
				$ref = (string) $c['r'];
				$colLetters = preg_replace('/\d+/', '', $ref);
				$colIndex = self::lettersToIndex( $colLetters );
				while ( $lastCol + 1 < $colIndex ) { $line[] = ''; $lastCol++; }
				$val = isset( $c->v ) ? (string) $c->v : '';
				$type = isset( $c['t'] ) ? (string) $c['t'] : '';
				if ( $type === 's' ) {
					$idx = (int) $val; $val = isset( $sharedStrings[ $idx ] ) ? $sharedStrings[ $idx ] : '';
				}
				$line[] = $val;
				$lastCol = $colIndex;
			}
			yield array_map( 'strval', $line );
		}
	}

	private static function lettersToIndex( $letters ) {
		$letters = strtoupper( $letters );
		$len = strlen( $letters );
		$index = 0;
		for ( $i = 0; $i < $len; $i++ ) { $index = $index * 26 + ( ord( $letters[$i] ) - 64 ); }
		return $index;
	}

	private static function empty_generator() {
		if ( false ) { yield []; }
	}
}
