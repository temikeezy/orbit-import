<?php
/**
 * Members Import Interface Template
 * 
 * This template is displayed in the group management Members section
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$group_id = bp_get_current_group_id();
$group = groups_get_group( $group_id );
?>

<div class="ogmi-import-interface">
    <div class="ogmi-import-header">
        <h3><?php echo esc_html__( 'Import Members', OGMI_TEXT_DOMAIN ); ?></h3>
        <p><?php echo esc_html__( 'Add new members to this group individually or in bulk from a CSV file.', OGMI_TEXT_DOMAIN ); ?></p>
    </div>

    <div class="ogmi-import-actions">
        <!-- Quick Add Individual Member -->
        <div class="ogmi-quick-add">
            <h4><?php echo esc_html__( 'Quick Add Member', OGMI_TEXT_DOMAIN ); ?></h4>
            <form id="ogmi-quick-add-form" class="ogmi-inline-form">
                <div class="ogmi-form-row-inline">
                    <div class="ogmi-form-field">
                        <label for="quick-email"><?php echo esc_html__( 'Email Address *', OGMI_TEXT_DOMAIN ); ?></label>
                        <input type="email" id="quick-email" name="email" placeholder="<?php echo esc_attr__( 'user@example.com', OGMI_TEXT_DOMAIN ); ?>" required>
                    </div>
                    
                    <div class="ogmi-form-field">
                        <label for="quick-first-name"><?php echo esc_html__( 'First Name', OGMI_TEXT_DOMAIN ); ?></label>
                        <input type="text" id="quick-first-name" name="first_name" placeholder="<?php echo esc_attr__( 'John', OGMI_TEXT_DOMAIN ); ?>">
                    </div>
                    
                    <div class="ogmi-form-field">
                        <label for="quick-last-name"><?php echo esc_html__( 'Last Name', OGMI_TEXT_DOMAIN ); ?></label>
                        <input type="text" id="quick-last-name" name="last_name" placeholder="<?php echo esc_attr__( 'Doe', OGMI_TEXT_DOMAIN ); ?>">
                    </div>
                    
                    <div class="ogmi-form-field">
                        <label for="quick-role"><?php echo esc_html__( 'Group Role', OGMI_TEXT_DOMAIN ); ?></label>
                        <select id="quick-role" name="role">
                            <option value="member"><?php echo esc_html__( 'Member', OGMI_TEXT_DOMAIN ); ?></option>
                            <option value="mod"><?php echo esc_html__( 'Moderator', OGMI_TEXT_DOMAIN ); ?></option>
                            <option value="admin"><?php echo esc_html__( 'Administrator', OGMI_TEXT_DOMAIN ); ?></option>
                        </select>
                    </div>
                    
                    <div class="ogmi-form-field ogmi-form-field-button">
                        <button type="submit" class="ogmi-button ogmi-button-primary">
                            <?php echo esc_html__( 'Add Member', OGMI_TEXT_DOMAIN ); ?>
                        </button>
                    </div>
                </div>
            </form>
            <div id="ogmi-quick-result" class="ogmi-result" style="display: none;"></div>
        </div>

        <!-- Bulk Import Toggle -->
        <div class="ogmi-bulk-import-toggle">
            <button type="button" id="ogmi-toggle-bulk-import" class="ogmi-button ogmi-button-secondary">
                <?php echo esc_html__( 'Bulk Import from CSV', OGMI_TEXT_DOMAIN ); ?>
            </button>
        </div>
    </div>

    <!-- Bulk Import Section (Initially Hidden) -->
    <div class="ogmi-bulk-import-section" id="ogmi-bulk-import-section" style="display: none;">
        <div class="ogmi-bulk-header">
            <h4><?php echo esc_html__( 'Bulk Import from CSV File', OGMI_TEXT_DOMAIN ); ?></h4>
            <p><?php echo esc_html__( 'Upload a CSV file to add multiple members at once.', OGMI_TEXT_DOMAIN ); ?></p>
        </div>

        <!-- Download Sample File -->
        <div class="ogmi-download-sample">
            <p><?php echo esc_html__( 'Download sample CSV file to understand the format:', OGMI_TEXT_DOMAIN ); ?></p>
            <a href="<?php echo esc_url( OGMI_PLUGIN_URL . 'samples/sample.csv' ); ?>" class="ogmi-button ogmi-button-small" download>
                <?php echo esc_html__( 'Download Sample CSV', OGMI_TEXT_DOMAIN ); ?>
            </a>
        </div>

        <!-- File Upload -->
        <div class="ogmi-upload-section">
            <div id="ogmi-dropzone" class="ogmi-dropzone">
                <div class="ogmi-dropzone-content">
                    <span class="ogmi-dropzone-icon">📁</span>
                    <p><?php echo esc_html__( 'Drag & drop your CSV file here, or click to browse', OGMI_TEXT_DOMAIN ); ?></p>
                    <p class="ogmi-dropzone-hint"><?php echo esc_html__( 'Supports CSV files up to 10MB', OGMI_TEXT_DOMAIN ); ?></p>
                </div>
                <input type="file" id="ogmi-file-input" accept=".csv" style="display: none;">
            </div>
            
            <div id="ogmi-upload-progress" class="ogmi-progress" style="display: none;">
                <div class="ogmi-progress-bar">
                    <div class="ogmi-progress-fill"></div>
                </div>
                <span class="ogmi-progress-text"><?php echo esc_html__( 'Uploading...', OGMI_TEXT_DOMAIN ); ?></span>
            </div>
        </div>

        <!-- Column Mapping (Initially Hidden) -->
        <div class="ogmi-mapping-section" id="ogmi-mapping-section" style="display: none;">
            <h5><?php echo esc_html__( 'Map Your File Columns', OGMI_TEXT_DOMAIN ); ?></h5>
            <p><?php echo esc_html__( 'Tell us which columns contain the required information.', OGMI_TEXT_DOMAIN ); ?></p>
            
            <div class="ogmi-mapping-grid">
                <div class="ogmi-mapping-item">
                    <label for="map-email"><?php echo esc_html__( 'Email Address *', OGMI_TEXT_DOMAIN ); ?></label>
                    <select id="map-email" name="mapping[email]" required>
                        <option value=""><?php echo esc_html__( 'Select column...', OGMI_TEXT_DOMAIN ); ?></option>
                    </select>
                </div>
                
                <div class="ogmi-mapping-item">
                    <label for="map-first-name"><?php echo esc_html__( 'First Name', OGMI_TEXT_DOMAIN ); ?></label>
                    <select id="map-first-name" name="mapping[first_name]">
                        <option value=""><?php echo esc_html__( 'Select column...', OGMI_TEXT_DOMAIN ); ?></option>
                    </select>
                </div>
                
                <div class="ogmi-mapping-item">
                    <label for="map-last-name"><?php echo esc_html__( 'Last Name', OGMI_TEXT_DOMAIN ); ?></label>
                    <select id="map-last-name" name="mapping[last_name]">
                        <option value=""><?php echo esc_html__( 'Select column...', OGMI_TEXT_DOMAIN ); ?></option>
                    </select>
                </div>
                
                <div class="ogmi-mapping-item">
                    <label for="map-role"><?php echo esc_html__( 'Group Role', OGMI_TEXT_DOMAIN ); ?></label>
                    <select id="map-role" name="mapping[role]">
                        <option value=""><?php echo esc_html__( 'Select column...', OGMI_TEXT_DOMAIN ); ?></option>
                    </select>
                    <small><?php echo esc_html__( 'Leave empty to default to "member"', OGMI_TEXT_DOMAIN ); ?></small>
                </div>
            </div>
            
            <div class="ogmi-preview-section">
                <h6><?php echo esc_html__( 'File Preview', OGMI_TEXT_DOMAIN ); ?></h6>
                <div id="ogmi-file-preview" class="ogmi-preview-table"></div>
            </div>
            
            <div class="ogmi-form-actions">
                <button type="button" id="ogmi-start-import" class="ogmi-button ogmi-button-primary">
                    <?php echo esc_html__( 'Start Import', OGMI_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" id="ogmi-cancel-import" class="ogmi-button ogmi-button-secondary">
                    <?php echo esc_html__( 'Cancel', OGMI_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </div>

        <!-- Import Progress (Initially Hidden) -->
        <div class="ogmi-progress-section" id="ogmi-progress-section" style="display: none;">
            <h5><?php echo esc_html__( 'Importing Members...', OGMI_TEXT_DOMAIN ); ?></h5>
            
            <div class="ogmi-progress-container">
                <div class="ogmi-progress-bar">
                    <div id="ogmi-import-progress" class="ogmi-progress-fill" style="width: 0%;"></div>
                </div>
                <span id="ogmi-import-progress-text" class="ogmi-progress-text">0%</span>
            </div>
            
            <div class="ogmi-import-stats">
                <div class="ogmi-stat">
                    <span class="ogmi-stat-label"><?php echo esc_html__( 'Created:', OGMI_TEXT_DOMAIN ); ?></span>
                    <span id="ogmi-stat-created" class="ogmi-stat-value">0</span>
                </div>
                <div class="ogmi-stat">
                    <span class="ogmi-stat-label"><?php echo esc_html__( 'Updated:', OGMI_TEXT_DOMAIN ); ?></span>
                    <span id="ogmi-stat-updated" class="ogmi-stat-value">0</span>
                </div>
                <div class="ogmi-stat">
                    <span class="ogmi-stat-label"><?php echo esc_html__( 'Skipped:', OGMI_TEXT_DOMAIN ); ?></span>
                    <span id="ogmi-stat-skipped" class="ogmi-stat-value">0</span>
                </div>
                <div class="ogmi-stat">
                    <span class="ogmi-stat-label"><?php echo esc_html__( 'Errors:', OGMI_TEXT_DOMAIN ); ?></span>
                    <span id="ogmi-stat-errors" class="ogmi-stat-value">0</span>
                </div>
            </div>
            
            <div id="ogmi-import-log" class="ogmi-import-log"></div>
        </div>

        <!-- Import Results (Initially Hidden) -->
        <div class="ogmi-results-section" id="ogmi-results-section" style="display: none;">
            <h5><?php echo esc_html__( 'Import Complete', OGMI_TEXT_DOMAIN ); ?></h5>
            
            <div class="ogmi-results-summary">
                <div class="ogmi-result-item ogmi-result-success">
                    <span class="ogmi-result-icon">✓</span>
                    <span class="ogmi-result-text">
                        <strong id="ogmi-final-created">0</strong> <?php echo esc_html__( 'new users created', OGMI_TEXT_DOMAIN ); ?>
                    </span>
                </div>
                <div class="ogmi-result-item ogmi-result-info">
                    <span class="ogmi-result-icon">↻</span>
                    <span class="ogmi-result-text">
                        <strong id="ogmi-final-updated">0</strong> <?php echo esc_html__( 'existing users added to group', OGMI_TEXT_DOMAIN ); ?>
                    </span>
                </div>
                <div class="ogmi-result-item ogmi-result-warning" id="ogmi-skipped-result" style="display: none;">
                    <span class="ogmi-result-icon">⚠</span>
                    <span class="ogmi-result-text">
                        <strong id="ogmi-final-skipped">0</strong> <?php echo esc_html__( 'rows skipped', OGMI_TEXT_DOMAIN ); ?>
                    </span>
                </div>
                <div class="ogmi-result-item ogmi-result-error" id="ogmi-errors-result" style="display: none;">
                    <span class="ogmi-result-icon">✗</span>
                    <span class="ogmi-result-text">
                        <strong id="ogmi-final-errors">0</strong> <?php echo esc_html__( 'errors occurred', OGMI_TEXT_DOMAIN ); ?>
                    </span>
                </div>
            </div>
            
            <div class="ogmi-form-actions">
                <button type="button" id="ogmi-start-new-import" class="ogmi-button ogmi-button-primary">
                    <?php echo esc_html__( 'Import More Members', OGMI_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" id="ogmi-close-import" class="ogmi-button ogmi-button-secondary">
                    <?php echo esc_html__( 'Close', OGMI_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </div>
    </div>
</div>
