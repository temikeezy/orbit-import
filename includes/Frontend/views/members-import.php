<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$group_id = bp_get_current_group_id();
$group = groups_get_group( $group_id );
?>

<div class="oui-members-import-section">
    <div class="oui-import-header">
        <h3><?php echo esc_html__( 'Import Members', 'orbit-import' ); ?></h3>
        <p><?php echo esc_html__( 'Add new members to this group individually or in bulk from a CSV/Excel file.', 'orbit-import' ); ?></p>
    </div>

    <div class="oui-import-actions">
        <!-- Quick Add Individual Member -->
        <div class="oui-quick-add">
            <h4><?php echo esc_html__( 'Quick Add Member', 'orbit-import' ); ?></h4>
            <form id="oui-quick-add-form" class="oui-inline-form">
                <div class="oui-form-row-inline">
                    <input type="email" id="quick-email" name="email" placeholder="<?php echo esc_attr__( 'Email address', 'orbit-import' ); ?>" required>
                    <input type="text" id="quick-first-name" name="first_name" placeholder="<?php echo esc_attr__( 'First name (optional)', 'orbit-import' ); ?>">
                    <input type="text" id="quick-last-name" name="last_name" placeholder="<?php echo esc_attr__( 'Last name (optional)', 'orbit-import' ); ?>">
                    <select id="quick-role" name="role">
                        <option value="member"><?php echo esc_html__( 'Member', 'orbit-import' ); ?></option>
                        <option value="mod"><?php echo esc_html__( 'Moderator', 'orbit-import' ); ?></option>
                        <option value="admin"><?php echo esc_html__( 'Administrator', 'orbit-import' ); ?></option>
                    </select>
                    <button type="submit" class="oui-button oui-button-primary">
                        <?php echo esc_html__( 'Add Member', 'orbit-import' ); ?>
                    </button>
                </div>
            </form>
            <div id="oui-quick-result" class="oui-result" style="display: none;"></div>
        </div>

        <!-- Bulk Import Toggle -->
        <div class="oui-bulk-import-toggle">
            <button type="button" id="oui-toggle-bulk-import" class="oui-button oui-button-secondary">
                <?php echo esc_html__( 'Bulk Import from File', 'orbit-import' ); ?>
            </button>
        </div>
    </div>

    <!-- Bulk Import Section (Initially Hidden) -->
    <div class="oui-bulk-import-section" id="oui-bulk-import-section" style="display: none;">
        <div class="oui-bulk-header">
            <h4><?php echo esc_html__( 'Bulk Import from File', 'orbit-import' ); ?></h4>
            <p><?php echo esc_html__( 'Upload a CSV or Excel file to add multiple members at once.', 'orbit-import' ); ?></p>
        </div>

        <!-- Download Sample Files -->
        <div class="oui-download-samples">
            <p><?php echo esc_html__( 'Download sample files to understand the format:', 'orbit-import' ); ?></p>
            <a href="<?php echo esc_url( OUI_PLUGIN_URL . 'assets/sample.csv' ); ?>" class="oui-button oui-button-small" download>
                <?php echo esc_html__( 'Sample CSV', 'orbit-import' ); ?>
            </a>
            <a href="<?php echo esc_url( OUI_PLUGIN_URL . 'assets/sample.xlsx' ); ?>" class="oui-button oui-button-small" download>
                <?php echo esc_html__( 'Sample Excel', 'orbit-import' ); ?>
            </a>
        </div>

        <!-- File Upload -->
        <div class="oui-upload-section">
            <div id="oui-dropzone" class="oui-dropzone">
                <div class="oui-dropzone-content">
                    <span class="oui-dropzone-icon">📁</span>
                    <p><?php echo esc_html__( 'Drag & drop your file here, or click to browse', 'orbit-import' ); ?></p>
                    <p class="oui-dropzone-hint"><?php echo esc_html__( 'Supports CSV and Excel (.xlsx) files up to 10MB', 'orbit-import' ); ?></p>
                </div>
                <input type="file" id="oui-file-input" accept=".csv,.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" style="display: none;">
            </div>
            
            <div id="oui-upload-progress" class="oui-progress" style="display: none;">
                <div class="oui-progress-bar">
                    <div class="oui-progress-fill"></div>
                </div>
                <span class="oui-progress-text"><?php echo esc_html__( 'Uploading...', 'orbit-import' ); ?></span>
            </div>
        </div>

        <!-- Column Mapping (Initially Hidden) -->
        <div class="oui-mapping-section" id="oui-mapping-section" style="display: none;">
            <h5><?php echo esc_html__( 'Map Your File Columns', 'orbit-import' ); ?></h5>
            <p><?php echo esc_html__( 'Tell us which columns contain the required information.', 'orbit-import' ); ?></p>
            
            <div class="oui-mapping-grid">
                <div class="oui-mapping-item">
                    <label for="map-email"><?php echo esc_html__( 'Email Address *', 'orbit-import' ); ?></label>
                    <select id="map-email" name="mapping[email]" required>
                        <option value=""><?php echo esc_html__( 'Select column...', 'orbit-import' ); ?></option>
                    </select>
                </div>
                
                <div class="oui-mapping-item">
                    <label for="map-first-name"><?php echo esc_html__( 'First Name', 'orbit-import' ); ?></label>
                    <select id="map-first-name" name="mapping[first_name]">
                        <option value=""><?php echo esc_html__( 'Select column...', 'orbit-import' ); ?></option>
                    </select>
                </div>
                
                <div class="oui-mapping-item">
                    <label for="map-last-name"><?php echo esc_html__( 'Last Name', 'orbit-import' ); ?></label>
                    <select id="map-last-name" name="mapping[last_name]">
                        <option value=""><?php echo esc_html__( 'Select column...', 'orbit-import' ); ?></option>
                    </select>
                </div>
                
                <div class="oui-mapping-item">
                    <label for="map-role"><?php echo esc_html__( 'Group Role', 'orbit-import' ); ?></label>
                    <select id="map-role" name="mapping[role]">
                        <option value=""><?php echo esc_html__( 'Select column...', 'orbit-import' ); ?></option>
                    </select>
                    <small><?php echo esc_html__( 'Leave empty to default to "member"', 'orbit-import' ); ?></small>
                </div>
            </div>
            
            <div class="oui-preview-section">
                <h6><?php echo esc_html__( 'File Preview', 'orbit-import' ); ?></h6>
                <div id="oui-file-preview" class="oui-preview-table"></div>
            </div>
            
            <div class="oui-form-actions">
                <button type="button" id="oui-start-import" class="oui-button oui-button-primary">
                    <?php echo esc_html__( 'Start Import', 'orbit-import' ); ?>
                </button>
                <button type="button" id="oui-cancel-import" class="oui-button oui-button-secondary">
                    <?php echo esc_html__( 'Cancel', 'orbit-import' ); ?>
                </button>
            </div>
        </div>

        <!-- Import Progress (Initially Hidden) -->
        <div class="oui-progress-section" id="oui-progress-section" style="display: none;">
            <h5><?php echo esc_html__( 'Importing Members...', 'orbit-import' ); ?></h5>
            
            <div class="oui-progress-container">
                <div class="oui-progress-bar">
                    <div id="oui-import-progress" class="oui-progress-fill" style="width: 0%;"></div>
                </div>
                <span id="oui-import-progress-text" class="oui-progress-text">0%</span>
            </div>
            
            <div class="oui-import-stats">
                <div class="oui-stat">
                    <span class="oui-stat-label"><?php echo esc_html__( 'Created:', 'orbit-import' ); ?></span>
                    <span id="oui-stat-created" class="oui-stat-value">0</span>
                </div>
                <div class="oui-stat">
                    <span class="oui-stat-label"><?php echo esc_html__( 'Updated:', 'orbit-import' ); ?></span>
                    <span id="oui-stat-updated" class="oui-stat-value">0</span>
                </div>
                <div class="oui-stat">
                    <span class="oui-stat-label"><?php echo esc_html__( 'Skipped:', 'orbit-import' ); ?></span>
                    <span id="oui-stat-skipped" class="oui-stat-value">0</span>
                </div>
                <div class="oui-stat">
                    <span class="oui-stat-label"><?php echo esc_html__( 'Errors:', 'orbit-import' ); ?></span>
                    <span id="oui-stat-errors" class="oui-stat-value">0</span>
                </div>
            </div>
            
            <div id="oui-import-log" class="oui-import-log"></div>
        </div>

        <!-- Import Results (Initially Hidden) -->
        <div class="oui-results-section" id="oui-results-section" style="display: none;">
            <h5><?php echo esc_html__( 'Import Complete', 'orbit-import' ); ?></h5>
            
            <div class="oui-results-summary">
                <div class="oui-result-item oui-result-success">
                    <span class="oui-result-icon">✓</span>
                    <span class="oui-result-text">
                        <strong id="oui-final-created">0</strong> <?php echo esc_html__( 'new users created', 'orbit-import' ); ?>
                    </span>
                </div>
                <div class="oui-result-item oui-result-info">
                    <span class="oui-result-icon">↻</span>
                    <span class="oui-result-text">
                        <strong id="oui-final-updated">0</strong> <?php echo esc_html__( 'existing users added to group', 'orbit-import' ); ?>
                    </span>
                </div>
                <div class="oui-result-item oui-result-warning" id="oui-skipped-result" style="display: none;">
                    <span class="oui-result-icon">⚠</span>
                    <span class="oui-result-text">
                        <strong id="oui-final-skipped">0</strong> <?php echo esc_html__( 'rows skipped', 'orbit-import' ); ?>
                    </span>
                </div>
                <div class="oui-result-item oui-result-error" id="oui-errors-result" style="display: none;">
                    <span class="oui-result-icon">✗</span>
                    <span class="oui-result-text">
                        <strong id="oui-final-errors">0</strong> <?php echo esc_html__( 'errors occurred', 'orbit-import' ); ?>
                    </span>
                </div>
            </div>
            
            <div class="oui-form-actions">
                <button type="button" id="oui-start-new-import" class="oui-button oui-button-primary">
                    <?php echo esc_html__( 'Import More Members', 'orbit-import' ); ?>
                </button>
                <button type="button" id="oui-close-import" class="oui-button oui-button-secondary">
                    <?php echo esc_html__( 'Close', 'orbit-import' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Members tab specific styles */
.oui-members-import-section {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.oui-import-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e1e5e9;
}

.oui-import-header h3 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 18px;
}

.oui-import-header p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.oui-import-actions {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.oui-quick-add h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.oui-form-row-inline {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.oui-form-row-inline input,
.oui-form-row-inline select {
    flex: 1;
    min-width: 150px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.oui-form-row-inline button {
    white-space: nowrap;
    padding: 8px 16px;
}

.oui-bulk-import-toggle {
    text-align: center;
    padding: 15px 0;
    border-top: 1px solid #e1e5e9;
}

.oui-bulk-import-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

.oui-bulk-header h4 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 16px;
}

.oui-bulk-header p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 14px;
}

.oui-download-samples {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
}

.oui-download-samples p {
    margin: 0 0 10px 0;
    color: #495057;
    font-size: 14px;
}

.oui-button-small {
    padding: 6px 12px;
    font-size: 12px;
    margin-right: 8px;
}

.oui-inline-form {
    margin: 0;
}

.oui-result {
    margin-top: 10px;
    padding: 10px;
    border-radius: 4px;
    font-size: 14px;
}

.oui-result.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.oui-result.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .oui-form-row-inline {
        flex-direction: column;
        align-items: stretch;
    }
    
    .oui-form-row-inline input,
    .oui-form-row-inline select,
    .oui-form-row-inline button {
        min-width: auto;
        width: 100%;
    }
}
</style>
