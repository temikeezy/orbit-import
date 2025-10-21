<?php
/**
 * File Processor Class
 * 
 * Handles CSV/Excel file uploads, parsing, and batch processing
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OGMI_File_Processor {
    
    /**
     * Maximum file size (10MB)
     */
    const MAX_FILE_SIZE = 10485760;
    
    /**
     * Allowed file types
     */
    const ALLOWED_TYPES = array( 'csv', 'xlsx' );
    
    /**
     * Process file upload
     */
    public function process_upload( $file, $group_id ) {
        // Validate file
        $validation = $this->validate_file( $file );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }
        
        // Create upload directory
        $upload_dir = $this->get_upload_directory();
        if ( is_wp_error( $upload_dir ) ) {
            return $upload_dir;
        }
        
        // Generate unique filename
        $file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        $unique_filename = wp_generate_uuid4() . '.' . $file_extension;
        $target_path = $upload_dir . $unique_filename;
        
        // Move uploaded file
        if ( ! move_uploaded_file( $file['tmp_name'], $target_path ) ) {
            return new WP_Error( 'upload_failed', __( 'Failed to move uploaded file', OGMI_TEXT_DOMAIN ) );
        }
        
        // Parse file and extract headers
        $headers = $this->extract_headers( $target_path, $file_extension );
        if ( is_wp_error( $headers ) ) {
            unlink( $target_path ); // Clean up file
            return $headers;
        }
        
        // Get preview rows
        $preview_rows = $this->get_preview_rows( $target_path, $file_extension, 5 );
        
        // Store file info in transient
        $file_id = wp_generate_uuid4();
        $file_data = array(
            'file_path' => $target_path,
            'file_extension' => $file_extension,
            'group_id' => $group_id,
            'user_id' => get_current_user_id(),
            'created' => time(),
            'headers' => $headers,
            'total_rows' => $this->count_total_rows( $target_path, $file_extension )
        );
        
        set_transient( 'ogmi_file_' . $file_id, $file_data, HOUR_IN_SECONDS );
        
        return array(
            'file_id' => $file_id,
            'headers' => $headers,
            'preview_rows' => $preview_rows,
            'total_rows' => $file_data['total_rows']
        );
    }
    
    /**
     * Validate uploaded file
     */
    private function validate_file( $file ) {
        // Check if file was uploaded
        if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'no_file', __( 'No file uploaded', OGMI_TEXT_DOMAIN ) );
        }
        
        // Check file size
        if ( $file['size'] > self::MAX_FILE_SIZE ) {
            return new WP_Error( 'file_too_large', __( 'File too large. Maximum size is 10MB.', OGMI_TEXT_DOMAIN ) );
        }
        
        // Check file type
        $file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $file_extension, self::ALLOWED_TYPES, true ) ) {
            return new WP_Error( 'invalid_file_type', __( 'Invalid file type. Only CSV and Excel files are allowed.', OGMI_TEXT_DOMAIN ) );
        }
        
        // Check for upload errors
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            return new WP_Error( 'upload_error', __( 'File upload error', OGMI_TEXT_DOMAIN ) );
        }
        
        return true;
    }
    
    /**
     * Get upload directory
     */
    private function get_upload_directory() {
        $upload_dir = wp_upload_dir();
        $import_dir = trailingslashit( $upload_dir['basedir'] ) . 'orbit-group-import/';
        
        if ( ! wp_mkdir_p( $import_dir ) ) {
            return new WP_Error( 'directory_creation_failed', __( 'Failed to create upload directory', OGMI_TEXT_DOMAIN ) );
        }
        
        return $import_dir;
    }
    
    /**
     * Extract headers from file
     */
    private function extract_headers( $file_path, $file_extension ) {
        try {
            if ( $file_extension === 'csv' ) {
                return $this->extract_csv_headers( $file_path );
            } elseif ( $file_extension === 'xlsx' ) {
                return $this->extract_xlsx_headers( $file_path );
            }
        } catch ( Exception $e ) {
            return new WP_Error( 'header_extraction_failed', __( 'Failed to extract file headers', OGMI_TEXT_DOMAIN ) );
        }
        
        return new WP_Error( 'unsupported_file_type', __( 'Unsupported file type', OGMI_TEXT_DOMAIN ) );
    }
    
    /**
     * Extract headers from CSV file
     */
    private function extract_csv_headers( $file_path ) {
        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            return new WP_Error( 'file_read_failed', __( 'Failed to read file', OGMI_TEXT_DOMAIN ) );
        }
        
        // Detect delimiter
        $delimiter = $this->detect_csv_delimiter( $file_path );
        
        $headers = fgetcsv( $handle, 0, $delimiter );
        fclose( $handle );
        
        if ( ! $headers || ! is_array( $headers ) ) {
            return new WP_Error( 'invalid_csv', __( 'Invalid CSV file format', OGMI_TEXT_DOMAIN ) );
        }
        
        // Clean headers
        $headers = array_map( 'trim', $headers );
        $headers = array_filter( $headers );
        
        return array_values( $headers );
    }
    
    /**
     * Extract headers from Excel file
     */
    private function extract_xlsx_headers( $file_path ) {
        // For Excel files, we'll use a simple approach
        // In a production environment, you might want to use a library like PhpSpreadsheet
        
        // For now, we'll return an error and suggest using CSV
        return new WP_Error( 'excel_not_supported', __( 'Excel files are not yet supported. Please use CSV format.', OGMI_TEXT_DOMAIN ) );
    }
    
    /**
     * Detect CSV delimiter
     */
    private function detect_csv_delimiter( $file_path ) {
        $handle = fopen( $file_path, 'r' );
        $first_line = fgets( $handle );
        fclose( $handle );
        
        $delimiters = array( ',', ';', '\t' );
        $delimiter_counts = array();
        
        foreach ( $delimiters as $delimiter ) {
            $delimiter_counts[ $delimiter ] = substr_count( $first_line, $delimiter );
        }
        
        return array_search( max( $delimiter_counts ), $delimiter_counts, true );
    }
    
    /**
     * Get preview rows from file
     */
    private function get_preview_rows( $file_path, $file_extension, $limit = 5 ) {
        $rows = array();
        
        if ( $file_extension === 'csv' ) {
            $handle = fopen( $file_path, 'r' );
            if ( ! $handle ) {
                return $rows;
            }
            
            $delimiter = $this->detect_csv_delimiter( $file_path );
            
            // Skip header row
            fgetcsv( $handle, 0, $delimiter );
            
            // Get preview rows
            $count = 0;
            while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false && $count < $limit ) {
                $rows[] = array_map( 'trim', $row );
                $count++;
            }
            
            fclose( $handle );
        }
        
        return $rows;
    }
    
    /**
     * Count total rows in file
     */
    private function count_total_rows( $file_path, $file_extension ) {
        if ( $file_extension === 'csv' ) {
            $handle = fopen( $file_path, 'r' );
            if ( ! $handle ) {
                return 0;
            }
            
            $count = 0;
            while ( fgetcsv( $handle ) !== false ) {
                $count++;
            }
            
            fclose( $handle );
            
            // Subtract 1 for header row
            return max( 0, $count - 1 );
        }
        
        return 0;
    }
    
    /**
     * Process batch of rows
     */
    public function process_batch( $file_id, $mapping, $batch_size, $offset, $group_id ) {
        // Get file data
        $file_data = get_transient( 'ogmi_file_' . $file_id );
        if ( ! $file_data ) {
            return new WP_Error( 'file_not_found', __( 'File data not found or expired', OGMI_TEXT_DOMAIN ) );
        }
        
        // Validate mapping
        if ( empty( $mapping['email'] ) ) {
            return new WP_Error( 'email_mapping_required', __( 'Email mapping is required', OGMI_TEXT_DOMAIN ) );
        }
        
        // Process rows
        $user_manager = new OGMI_User_Manager();
        $results = array(
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'processed' => 0
        );
        
        $rows = $this->get_batch_rows( $file_data['file_path'], $file_data['file_extension'], $batch_size, $offset );
        
        foreach ( $rows as $row ) {
            $results['processed']++;
            
            // Extract data based on mapping
            $email = $this->get_mapped_value( $row, $mapping, 'email' );
            $first_name = $this->get_mapped_value( $row, $mapping, 'first_name' );
            $last_name = $this->get_mapped_value( $row, $mapping, 'last_name' );
            $role = $this->get_mapped_value( $row, $mapping, 'role' );
            
            // Validate email
            if ( empty( $email ) || ! is_email( $email ) ) {
                $results['skipped']++;
                $results['errors']++;
                continue;
            }
            
            // Validate role
            if ( ! in_array( $role, array( 'member', 'mod', 'admin' ), true ) ) {
                $role = 'member';
            }
            
            // Add member to group
            $result = $user_manager->add_member_to_group( $email, $first_name, $last_name, $group_id, $role );
            
            if ( is_wp_error( $result ) ) {
                $results['skipped']++;
                $results['errors']++;
            } else {
                if ( $result['is_new'] ) {
                    $results['created']++;
                } else {
                    $results['updated']++;
                }
            }
        }
        
        $results['offset'] = $offset + $results['processed'];
        $results['has_more'] = $results['processed'] >= $batch_size;
        
        return $results;
    }
    
    /**
     * Get batch of rows from file
     */
    private function get_batch_rows( $file_path, $file_extension, $batch_size, $offset ) {
        $rows = array();
        
        if ( $file_extension === 'csv' ) {
            $handle = fopen( $file_path, 'r' );
            if ( ! $handle ) {
                return $rows;
            }
            
            $delimiter = $this->detect_csv_delimiter( $file_path );
            
            // Skip header row
            fgetcsv( $handle, 0, $delimiter );
            
            // Skip to offset
            for ( $i = 0; $i < $offset; $i++ ) {
                if ( fgetcsv( $handle, 0, $delimiter ) === false ) {
                    break;
                }
            }
            
            // Get batch rows
            $count = 0;
            while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false && $count < $batch_size ) {
                $rows[] = array_map( 'trim', $row );
                $count++;
            }
            
            fclose( $handle );
        }
        
        return $rows;
    }
    
    /**
     * Get mapped value from row
     */
    private function get_mapped_value( $row, $mapping, $field ) {
        if ( ! isset( $mapping[ $field ] ) || empty( $mapping[ $field ] ) ) {
            return '';
        }
        
        $column_index = $mapping[ $field ];
        if ( isset( $row[ $column_index ] ) ) {
            return trim( $row[ $column_index ] );
        }
        
        return '';
    }
    
    /**
     * Get file preview
     */
    public function get_file_preview( $file_id ) {
        $file_data = get_transient( 'ogmi_file_' . $file_id );
        if ( ! $file_data ) {
            return new WP_Error( 'file_not_found', __( 'File data not found or expired', OGMI_TEXT_DOMAIN ) );
        }
        
        return array(
            'headers' => $file_data['headers'],
            'file_extension' => $file_data['file_extension'],
            'total_rows' => $file_data['total_rows']
        );
    }
    
    /**
     * Clean up file
     */
    public function cleanup_file( $file_id ) {
        $file_data = get_transient( 'ogmi_file_' . $file_id );
        if ( $file_data && isset( $file_data['file_path'] ) && file_exists( $file_data['file_path'] ) ) {
            unlink( $file_data['file_path'] );
        }
        
        delete_transient( 'ogmi_file_' . $file_id );
    }
}
