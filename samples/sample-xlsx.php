<?php
// Generates a minimal XLSX sample on the fly to avoid shipping binaries in repo.
// Output: email, first_name, last_name with two rows.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Try to load PhpSpreadsheet and stream a simple workbook; if missing, fall back to CSV download.
if ( class_exists( '\\PhpOffice\\PhpSpreadsheet\\Spreadsheet' ) && class_exists( '\\PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx' ) ) {
	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setCellValue('A1', 'email');
	$sheet->setCellValue('B1', 'first_name');
	$sheet->setCellValue('C1', 'last_name');
	$sheet->setCellValue('A2', 'john.doe@example.com');
	$sheet->setCellValue('B2', 'John');
	$sheet->setCellValue('C2', 'Doe');
	$sheet->setCellValue('A3', 'jane.smith@example.com');
	$sheet->setCellValue('B3', 'Jane');
	$sheet->setCellValue('C3', 'Smith');

	$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
	$filename = 'ogmi-sample.xlsx';
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Cache-Control: max-age=0');
	$writer->save('php://output');
	exit;
}

// Fallback: serve CSV if PhpSpreadsheet not available
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="ogmi-sample.csv"');
echo "email,first_name,last_name\n";
echo "john.doe@example.com,John,Doe\n";
echo "jane.smith@example.com,Jane,Smith\n";
exit;


