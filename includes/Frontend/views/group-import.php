<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$group_id = bp_get_current_group_id();
$group = groups_get_group( $group_id );
?>

<div class="oui-group-import-container">
    <div class="oui-header">
        <h2><?php echo esc_html__( 'Import Members to', 'orbit-import' ); ?> <?php echo esc_html( $group->name ); ?></h2>
        <p><?php echo esc_html__( 'Add new members to this group individually or in bulk from a CSV/Excel file.', 'orbit-import' ); ?></p>
    </div>

    <div class="oui-tabs">
        <nav class="oui-tab-nav">
            <button class="oui-tab-button active" data-tab="individual"><?php echo esc_html__( 'Add Individual Member', 'orbit-import' ); ?></button>
            <button class="oui-tab-button" data-tab="bulk"><?php echo esc_html__( 'Bulk Import from File', 'orbit-import' ); ?></button>
        </nav>

        <!-- Individual Member Tab -->
        <div class="oui-tab-content active" id="individual-tab">
            <div class="oui-panel">
                <h3><?php echo esc_html__( 'Add a Single Member', 'orbit-import' ); ?></h3>
                <form id="oui-add-user-form" class="oui-form">
                    <div class="oui-form-row">
                        <label for="user-email"><?php echo esc_html__( 'Email Address *', 'orbit-import' ); ?></label>
                        <input type="email" id="user-email" name="email" required>
                    </div>
                    
                    <div class="oui-form-row">
                        <label for="user-first-name"><?php echo esc_html__( 'First Name', 'orbit-import' ); ?></label>
                        <input type="text" id="user-first-name" name="first_name">
                    </div>
                    
                    <div class="oui-form-row">
                        <label for="user-last-name"><?php echo esc_html__( 'Last Name', 'orbit-import' ); ?></label>
                        <input type="text" id="user-last-name" name="last_name">
                    </div>
                    
                    <div class="oui-form-row">
                        <label for="user-role"><?php echo esc_html__( 'Group Role', 'orbit-import' ); ?></label>
                        <select id="user-role" name="role">
                            <option value="member"><?php echo esc_html__( 'Member', 'orbit-import' ); ?></option>
                            <option value="mod"><?php echo esc_html__( 'Moderator', 'orbit-import' ); ?></option>
                            <option value="admin"><?php echo esc_html__( 'Administrator', 'orbit-import' ); ?></option>
                        </select>
                    </div>
                    
                    <div class="oui-form-actions">
                        <button type="submit" class="oui-button oui-button-primary">
                            <?php echo esc_html__( 'Add Member', 'orbit-import' ); ?>
                        </button>
                    </div>
                </form>
                
                <div id="oui-individual-result" class="oui-result" style="display: none;"></div>
            </div>
        </div>

        <!-- Bulk Import Tab -->
        <div class="oui-tab-content" id="bulk-tab">
            <div class="oui-panel">
                <h3><?php echo esc_html__( 'Bulk Import from File', 'orbit-import' ); ?></h3>
                
                <!-- Step 1: Upload -->
                <div class="oui-step" id="upload-step">
                    <h4><?php echo esc_html__( 'Step 1: Upload File', 'orbit-import' ); ?></h4>
                    <p><?php echo esc_html__( 'Upload a CSV or Excel file containing member information.', 'orbit-import' ); ?></p>
                    
                    <div class="oui-download-samples">
                        <p><?php echo esc_html__( 'Download sample files:', 'orbit-import' ); ?></p>
                        <a href="<?php echo esc_url( OUI_PLUGIN_URL . 'assets/sample.csv' ); ?>" class="oui-button oui-button-secondary" download>
                            <?php echo esc_html__( 'Sample CSV', 'orbit-import' ); ?>
                        </a>
                        <a href="<?php echo esc_url( OUI_PLUGIN_URL . 'assets/sample.xlsx' ); ?>" class="oui-button oui-button-secondary" download>
                            <?php echo esc_html__( 'Sample Excel', 'orbit-import' ); ?>
                        </a>
                    </div>
                    
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

                <!-- Step 2: Mapping -->
                <div class="oui-step" id="mapping-step" style="display: none;">
                    <h4><?php echo esc_html__( 'Step 2: Map Columns', 'orbit-import' ); ?></h4>
                    <p><?php echo esc_html__( 'Map your file columns to the required fields.', 'orbit-import' ); ?></p>
                    
                    <div class="oui-mapping-container">
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
                            <h5><?php echo esc_html__( 'File Preview', 'orbit-import' ); ?></h5>
                            <div id="oui-file-preview" class="oui-preview-table"></div>
                        </div>
                    </div>
                    
                    <div class="oui-form-actions">
                        <button type="button" id="oui-start-import" class="oui-button oui-button-primary">
                            <?php echo esc_html__( 'Start Import', 'orbit-import' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Processing -->
                <div class="oui-step" id="processing-step" style="display: none;">
                    <h4><?php echo esc_html__( 'Step 3: Processing Import', 'orbit-import' ); ?></h4>
                    
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

                <!-- Step 4: Results -->
                <div class="oui-step" id="results-step" style="display: none;">
                    <h4><?php echo esc_html__( 'Import Complete', 'orbit-import' ); ?></h4>
                    
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
                            <?php echo esc_html__( 'Start New Import', 'orbit-import' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/template" id="oui-preview-template">
    <table class="oui-preview-table">
        <thead>
            <tr>
                {{#each headers}}
                <th>{{this}}</th>
                {{/each}}
            </tr>
        </thead>
        <tbody>
            {{#each rows}}
            <tr>
                {{#each this}}
                <td>{{this}}</td>
                {{/each}}
            </tr>
            {{/each}}
        </tbody>
    </table>
</script>
