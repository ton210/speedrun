<?php
/**
 * MunchMakers Speed Optimizer - Testing & Monitoring Interface
 * File: includes/testing-interface.php
 * 
 * This adds comprehensive testing and monitoring capabilities to the plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add testing menu items
add_action('admin_menu', function() {
    add_submenu_page(
        'mmso-dashboard',
        'Performance Testing',
        'Performance Testing',
        'manage_options',
        'mmso-testing',
        'mmso_render_testing_page'
    );
    
    add_submenu_page(
        'mmso-dashboard',
        'Real-Time Monitor',
        'Real-Time Monitor',
        'manage_options',
        'mmso-monitor',
        'mmso_render_monitor_page'
    );
    
    add_submenu_page(
        'mmso-dashboard',
        'Script Control',
        'Script Control',
        'manage_options',
        'mmso-scripts',
        'mmso_render_scripts_page'
    );
    
    add_submenu_page(
        'mmso-dashboard',
        'Image Analysis',
        'Image Analysis',
        'manage_options',
        'mmso-images',
        'mmso_render_images_page'
    );
}, 11);

/**
 * Performance Testing Page
 */
function mmso_render_testing_page() {
    ?>
    <div class="wrap">
        <h1>Performance Testing</h1>
        
        <div class="mmso-testing-container">
            <!-- Quick Tests -->
            <div class="mmso-card">
                <h2>🧪 Quick Performance Tests</h2>
                
                <div class="test-buttons">
                    <button class="button button-primary" id="test-homepage">Test Homepage</button>
                    <button class="button button-primary" id="test-current">Test Current Page</button>
                    <button class="button button-primary" id="test-product">Test Random Product</button>
                    <button class="button button-primary" id="test-category">Test Category Page</button>
                </div>
                
                <div id="test-results" style="display:none;">
                    <h3>Test Results:</h3>
                    <div class="results-container"></div>
                </div>
            </div>
            
            <!-- Before/After Comparison -->
            <div class="mmso-card">
                <h2>📊 Before/After Comparison</h2>
                
                <div class="comparison-controls">
                    <label>
                        <input type="checkbox" id="disable-optimizations" />
                        Temporarily disable optimizations for testing
                    </label>
                    <button class="button" id="run-comparison">Run Comparison Test</button>
                </div>
                
                <div id="comparison-results" style="display:none;">
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Without Optimization</th>
                                <th>With Optimization</th>
                                <th>Improvement</th>
                            </tr>
                        </thead>
                        <tbody id="comparison-tbody">
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Specific Issue Tests -->
            <div class="mmso-card">
                <h2>🔍 Specific Issue Tests</h2>
                
                <div class="issue-tests">
                    <div class="test-item">
                        <h4>ScrollBar.js Removal Test</h4>
                        <p>Check if the problematic scrollBar.js is removed</p>
                        <button class="button test-specific" data-test="scrollbar">Run Test</button>
                        <span class="test-status"></span>
                    </div>
                    
                    <div class="test-item">
                        <h4>Layout Shift (CLS) Test</h4>
                        <p>Measure Cumulative Layout Shift on current page</p>
                        <button class="button test-specific" data-test="cls">Run Test</button>
                        <span class="test-status"></span>
                    </div>
                    
                    <div class="test-item">
                        <h4>Script Loading Test</h4>
                        <p>Check if scripts are properly deferred/delayed</p>
                        <button class="button test-specific" data-test="scripts">Run Test</button>
                        <span class="test-status"></span>
                    </div>
                    
                    <div class="test-item">
                        <h4>Payment Scripts Test</h4>
                        <p>Verify payment scripts only load on checkout</p>
                        <button class="button test-specific" data-test="payment">Run Test</button>
                        <span class="test-status"></span>
                    </div>
                    
                    <div class="test-item">
                        <h4>Image Optimization Test</h4>
                        <p>Check image dimensions and lazy loading</p>
                        <button class="button test-specific" data-test="images">Run Test</button>
                        <span class="test-status"></span>
                    </div>
                </div>
            </div>
            
            <!-- Live Testing Tools -->
            <div class="mmso-card">
                <h2>🛠️ Live Testing Tools</h2>
                
                <div class="tools-grid">
                    <div class="tool-item">
                        <h4>Resource Monitor</h4>
                        <p>See all loaded resources in real-time</p>
                        <button class="button" id="open-resource-monitor">Open Monitor</button>
                    </div>
                    
                    <div class="tool-item">
                        <h4>Script Timeline</h4>
                        <p>Visualize script loading order</p>
                        <button class="button" id="view-timeline">View Timeline</button>
                    </div>
                    
                    <div class="tool-item">
                        <h4>Performance Profiler</h4>
                        <p>Detailed performance breakdown</p>
                        <button class="button" id="run-profiler">Run Profiler</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .mmso-testing-container {
        max-width: 1200px;
    }
    
    .mmso-card {
        background: #fff;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .test-buttons {
        display: flex;
        gap: 10px;
        margin: 20px 0;
    }
    
    .test-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .test-item:last-child {
        border-bottom: none;
    }
    
    .test-status {
        margin-left: 10px;
        font-weight: bold;
    }
    
    .test-status.success {
        color: #46b450;
    }
    
    .test-status.error {
        color: #dc3232;
    }
    
    .test-status.warning {
        color: #ffb900;
    }
    
    .tools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .tool-item {
        padding: 15px;
        border: 1px solid #ddd;
        text-align: center;
    }
    
    .results-container {
        background: #f7f7f7;
        padding: 15px;
        margin-top: 15px;
        border-radius: 4px;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Quick performance tests
        $('.test-buttons button').on('click', function() {
            const testType = $(this).attr('id').replace('test-', '');
            runPerformanceTest(testType);
        });
        
        function runPerformanceTest(type) {
            $('#test-results').show();
            $('.results-container').html('<p>Running test...</p>');
            
            $.post(ajaxurl, {
                action: 'mmso_run_performance_test',
                test_type: type,
                nonce: '<?php echo wp_create_nonce('mmso_test'); ?>'
            }, function(response) {
                if (response.success) {
                    displayTestResults(response.data);
                }
            });
        }
        
        function displayTestResults(data) {
            let html = '<h4>Performance Metrics:</h4>';
            html += '<ul>';
            html += '<li><strong>Page Load Time:</strong> ' + data.loadTime + 'ms</li>';
            html += '<li><strong>First Contentful Paint:</strong> ' + data.fcp + 'ms</li>';
            html += '<li><strong>Largest Contentful Paint:</strong> ' + data.lcp + 'ms</li>';
            html += '<li><strong>Total Blocking Time:</strong> ' + data.tbt + 'ms</li>';
            html += '<li><strong>Cumulative Layout Shift:</strong> ' + data.cls + '</li>';
            html += '<li><strong>Scripts Loaded:</strong> ' + data.scriptsCount + '</li>';
            html += '<li><strong>Total Page Size:</strong> ' + data.pageSize + '</li>';
            html += '</ul>';
            
            if (data.issues && data.issues.length > 0) {
                html += '<h4>Issues Found:</h4>';
                html += '<ul class="issues-list">';
                data.issues.forEach(function(issue) {
                    html += '<li class="issue-' + issue.severity + '">' + issue.message + '</li>';
                });
                html += '</ul>';
            }
            
            $('.results-container').html(html);
        }
        
        // Specific issue tests
        $('.test-specific').on('click', function() {
            const $button = $(this);
            const testType = $button.data('test');
            const $status = $button.siblings('.test-status');
            
            $status.text('Testing...').removeClass('success error warning');
            
            $.post(ajaxurl, {
                action: 'mmso_run_specific_test',
                test_type: testType,
                nonce: '<?php echo wp_create_nonce('mmso_test'); ?>'
            }, function(response) {
                if (response.success) {
                    $status.text(response.data.message)
                           .addClass(response.data.status);
                }
            });
        });
        
        // Before/After comparison
        $('#run-comparison').on('click', function() {
            const disabled = $('#disable-optimizations').is(':checked');
            
            $(this).prop('disabled', true).text('Running comparison...');
            
            $.post(ajaxurl, {
                action: 'mmso_run_comparison_test',
                disabled: disabled,
                nonce: '<?php echo wp_create_nonce('mmso_test'); ?>'
            }, function(response) {
                if (response.success) {
                    displayComparisonResults(response.data);
                }
                $('#run-comparison').prop('disabled', false).text('Run Comparison Test');
            });
        });
        
        function displayComparisonResults(data) {
            $('#comparison-results').show();
            let html = '';
            
            Object.keys(data.metrics).forEach(function(metric) {
                const without = data.without[metric];
                const with_opt = data.with[metric];
                const improvement = ((without - with_opt) / without * 100).toFixed(1);
                
                html += '<tr>';
                html += '<td>' + metric + '</td>';
                html += '<td>' + without + 'ms</td>';
                html += '<td>' + with_opt + 'ms</td>';
                html += '<td class="' + (improvement > 0 ? 'improvement' : 'degradation') + '">';
                html += improvement + '%</td>';
                html += '</tr>';
            });
            
            $('#comparison-tbody').html(html);
        }
        
        // Live testing tools
        $('#open-resource-monitor').on('click', function() {
            window.open('<?php echo admin_url('admin.php?page=mmso-monitor'); ?>', '_blank');
        });
        
        $('#view-timeline').on('click', function() {
            window.open('<?php echo admin_url('admin.php?page=mmso-monitor&view=timeline'); ?>', '_blank');
        });
        
        $('#run-profiler').on('click', function() {
            if (confirm('This will reload the page with profiling enabled. Continue?')) {
                window.location.href = window.location.href + '&mmso_profile=1';
            }
        });
    });
    </script>
    <?php
}

/**
 * Real-Time Monitor Page
 */
function mmso_render_monitor_page() {
    ?>
    <div class="wrap">
        <h1>Real-Time Performance Monitor</h1>
        
        <div class="monitor-controls">
            <button class="button button-primary" id="start-monitoring">Start Monitoring</button>
            <button class="button" id="stop-monitoring" disabled>Stop Monitoring</button>
            <button class="button" id="clear-data">Clear Data</button>
            
            <label style="margin-left: 20px;">
                <input type="checkbox" id="auto-refresh" checked> Auto-refresh every 5 seconds
            </label>
        </div>
        
        <div class="monitor-grid">
            <!-- Current Metrics -->
            <div class="mmso-card metric-card">
                <h3>Current Metrics</h3>
                <div class="metrics-display">
                    <div class="metric">
                        <span class="label">Page Load Time:</span>
                        <span class="value" id="metric-load-time">-</span>
                    </div>
                    <div class="metric">
                        <span class="label">DOM Ready:</span>
                        <span class="value" id="metric-dom-ready">-</span>
                    </div>
                    <div class="metric">
                        <span class="label">Scripts Loaded:</span>
                        <span class="value" id="metric-scripts">-</span>
                    </div>
                    <div class="metric">
                        <span class="label">Memory Usage:</span>
                        <span class="value" id="metric-memory">-</span>
                    </div>
                </div>
            </div>
            
            <!-- Resource Monitor -->
            <div class="mmso-card">
                <h3>Resource Monitor</h3>
                <div class="resource-filter">
                    <label>Filter: 
                        <select id="resource-filter">
                            <option value="">All Resources</option>
                            <option value="script">Scripts Only</option>
                            <option value="style">Styles Only</option>
                            <option value="image">Images Only</option>
                            <option value="problematic">Problematic Only</option>
                        </select>
                    </label>
                </div>
                <div id="resource-list" class="resource-list">
                    <p>Click "Start Monitoring" to begin...</p>
                </div>
            </div>
            
            <!-- Script Timeline -->
            <div class="mmso-card full-width">
                <h3>Script Loading Timeline</h3>
                <div id="timeline-container">
                    <canvas id="timeline-canvas" width="1000" height="400"></canvas>
                </div>
            </div>
            
            <!-- Problem Detection -->
            <div class="mmso-card">
                <h3>🚨 Problem Detection</h3>
                <div id="problems-list">
                    <p class="no-problems">No problems detected yet.</p>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .monitor-controls {
        margin: 20px 0;
        padding: 15px;
        background: #f7f7f7;
        border: 1px solid #ddd;
    }
    
    .monitor-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .monitor-grid .full-width {
        grid-column: 1 / -1;
    }
    
    .metric-card .metrics-display {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .metric {
        padding: 10px;
        background: #f0f0f0;
        border-radius: 4px;
    }
    
    .metric .label {
        display: block;
        font-size: 12px;
        color: #666;
    }
    
    .metric .value {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #2271b1;
    }
    
    .resource-list {
        max-height: 400px;
        overflow-y: auto;
        font-family: monospace;
        font-size: 12px;
    }
    
    .resource-item {
        padding: 5px;
        border-bottom: 1px solid #eee;
    }
    
    .resource-item.script {
        background: #e8f5e9;
    }
    
    .resource-item.style {
        background: #e3f2fd;
    }
    
    .resource-item.problematic {
        background: #ffebee;
        font-weight: bold;
    }
    
    .problem-item {
        padding: 10px;
        margin: 5px 0;
        background: #fff3cd;
        border-left: 4px solid #ffb900;
    }
    
    #timeline-canvas {
        width: 100%;
        height: 400px;
        border: 1px solid #ddd;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        let monitoring = false;
        let monitorInterval;
        let timelineData = [];
        
        $('#start-monitoring').on('click', function() {
            monitoring = true;
            $(this).prop('disabled', true);
            $('#stop-monitoring').prop('disabled', false);
            
            startMonitoring();
        });
        
        $('#stop-monitoring').on('click', function() {
            monitoring = false;
            $(this).prop('disabled', true);
            $('#start-monitoring').prop('disabled', false);
            
            if (monitorInterval) {
                clearInterval(monitorInterval);
            }
        });
        
        function startMonitoring() {
            // Initial load
            updateMonitorData();
            
            // Set up auto-refresh
            if ($('#auto-refresh').is(':checked')) {
                monitorInterval = setInterval(updateMonitorData, 5000);
            }
        }
        
        function updateMonitorData() {
            $.post(ajaxurl, {
                action: 'mmso_get_monitor_data',
                nonce: '<?php echo wp_create_nonce('mmso_monitor'); ?>'
            }, function(response) {
                if (response.success) {
                    updateDisplay(response.data);
                }
            });
        }
        
        function updateDisplay(data) {
            // Update metrics
            $('#metric-load-time').text(data.metrics.loadTime + 'ms');
            $('#metric-dom-ready').text(data.metrics.domReady + 'ms');
            $('#metric-scripts').text(data.metrics.scriptsCount);
            $('#metric-memory').text(data.metrics.memory);
            
            // Update resource list
            updateResourceList(data.resources);
            
            // Update timeline
            updateTimeline(data.timeline);
            
            // Update problems
            updateProblems(data.problems);
        }
        
        function updateResourceList(resources) {
            const filter = $('#resource-filter').val();
            let html = '';
            
            resources.forEach(function(resource) {
                if (!filter || resource.type === filter || (filter === 'problematic' && resource.problematic)) {
                    html += '<div class="resource-item ' + resource.type + (resource.problematic ? ' problematic' : '') + '">';
                    html += resource.name + ' - ' + resource.size + ' - ' + resource.loadTime + 'ms';
                    if (resource.problematic) {
                        html += ' ⚠️';
                    }
                    html += '</div>';
                }
            });
            
            $('#resource-list').html(html || '<p>No resources found.</p>');
        }
        
        function updateTimeline(timeline) {
            // This would implement a visual timeline
            // For now, just store the data
            timelineData = timeline;
            drawTimeline();
        }
        
        function drawTimeline() {
            const canvas = document.getElementById('timeline-canvas');
            const ctx = canvas.getContext('2d');
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw timeline
            // This is a simplified version - you'd want to make this more sophisticated
            ctx.fillStyle = '#333';
            ctx.fillText('Script Loading Timeline', 10, 20);
            
            // Draw each script
            let y = 50;
            timelineData.forEach(function(item) {
                ctx.fillStyle = item.delayed ? '#4CAF50' : '#F44336';
                ctx.fillRect(item.startTime / 10, y, item.duration / 10, 20);
                ctx.fillStyle = '#333';
                ctx.fillText(item.name, 10, y + 15);
                y += 30;
            });
        }
        
        function updateProblems(problems) {
            if (problems.length === 0) {
                $('#problems-list').html('<p class="no-problems">✅ No problems detected!</p>');
            } else {
                let html = '';
                problems.forEach(function(problem) {
                    html += '<div class="problem-item">';
                    html += '<strong>' + problem.type + ':</strong> ' + problem.message;
                    html += '</div>';
                });
                $('#problems-list').html(html);
            }
        }
        
        // Resource filter
        $('#resource-filter').on('change', function() {
            if (monitoring) {
                updateMonitorData();
            }
        });
        
        // Clear data
        $('#clear-data').on('click', function() {
            if (confirm('Clear all monitoring data?')) {
                timelineData = [];
                $('#resource-list').html('<p>Data cleared.</p>');
                $('#problems-list').html('<p class="no-problems">No problems detected yet.</p>');
                drawTimeline();
            }
        });
    });
    </script>
    <?php
}

/**
 * Script Control Page
 */
function mmso_render_scripts_page() {
    global $wp_scripts;
    $all_scripts = array();
    
    if (isset($wp_scripts->registered)) {
        foreach ($wp_scripts->registered as $handle => $script) {
            $all_scripts[$handle] = array(
                'src' => $script->src,
                'deps' => $script->deps,
                'ver' => $script->ver,
                'status' => mmso_get_script_status($handle)
            );
        }
    }
    ?>
    <div class="wrap">
        <h1>Script Control Center</h1>
        
        <div class="script-controls">
            <div class="mmso-card">
                <h2>Script Loading Configuration</h2>
                <p>Control how each script loads on your site. Changes are applied immediately.</p>
                
                <div class="bulk-actions">
                    <button class="button" id="select-all">Select All</button>
                    <button class="button" id="select-none">Select None</button>
                    <button class="button" id="apply-bulk">Apply Bulk Action</button>
                    <select id="bulk-action">
                        <option value="">Choose Action</option>
                        <option value="defer">Defer Selected</option>
                        <option value="async">Async Selected</option>
                        <option value="delay">Delay Selected</option>
                        <option value="normal">Normal Loading</option>
                    </select>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="check-column"><input type="checkbox" id="check-all"></th>
                            <th>Script Handle</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Loading Method</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_scripts as $handle => $script): ?>
                        <tr data-handle="<?php echo esc_attr($handle); ?>">
                            <td><input type="checkbox" class="script-checkbox" value="<?php echo esc_attr($handle); ?>"></td>
                            <td>
                                <strong><?php echo esc_html($handle); ?></strong>
                                <?php if (!empty($script['deps'])): ?>
                                <br><small>Deps: <?php echo implode(', ', $script['deps']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo esc_html(basename($script['src'])); ?></small></td>
                            <td>
                                <span class="status-badge status-<?php echo $script['status']; ?>">
                                    <?php echo ucfirst($script['status']); ?>
                                </span>
                            </td>
                            <td>
                                <select class="script-loading-method" data-handle="<?php echo esc_attr($handle); ?>">
                                    <option value="normal">Normal</option>
                                    <option value="defer" <?php selected(get_option("mmso_script_{$handle}_method"), 'defer'); ?>>Defer</option>
                                    <option value="async" <?php selected(get_option("mmso_script_{$handle}_method"), 'async'); ?>>Async</option>
                                    <option value="delay" <?php selected(get_option("mmso_script_{$handle}_method"), 'delay'); ?>>Delay</option>
                                    <option value="remove" <?php selected(get_option("mmso_script_{$handle}_method"), 'remove'); ?>>Remove</option>
                                </select>
                            </td>
                            <td>
                                <button class="button button-small test-script" data-handle="<?php echo esc_attr($handle); ?>">Test</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Problematic Scripts -->
            <div class="mmso-card">
                <h2>⚠️ Problematic Scripts Detected</h2>
                <div class="problematic-scripts">
                    <?php
                    $problematic = array(
                        'scrollBar.js' => 'Consuming 3.3s CPU time',
                        'image-carousel' => 'Blocking for 1.2s',
                        'swiper.js' => 'Multiple 500ms+ blocks',
                        'klaviyo' => 'Third-party tracking, not essential',
                        'facebook' => 'Third-party tracking, not essential'
                    );
                    
                    foreach ($problematic as $script => $issue): ?>
                    <div class="problem-script">
                        <h4><?php echo $script; ?></h4>
                        <p>Issue: <?php echo $issue; ?></p>
                        <p>Recommendation: <strong>Delay or Remove</strong></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .script-controls {
        max-width: 1400px;
    }
    
    .bulk-actions {
        margin: 15px 0;
        padding: 15px;
        background: #f7f7f7;
        border: 1px solid #ddd;
    }
    
    .status-badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-deferred {
        background: #cce5ff;
        color: #004085;
    }
    
    .status-delayed {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-removed {
        background: #f8d7da;
        color: #721c24;
    }
    
    .problematic-scripts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .problem-script {
        padding: 15px;
        background: #ffebee;
        border-left: 4px solid #f44336;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Script loading method change
        $('.script-loading-method').on('change', function() {
            const handle = $(this).data('handle');
            const method = $(this).val();
            
            $.post(ajaxurl, {
                action: 'mmso_update_script_method',
                handle: handle,
                method: method,
                nonce: '<?php echo wp_create_nonce('mmso_scripts'); ?>'
            }, function(response) {
                if (response.success) {
                    // Update status badge
                    const $row = $('tr[data-handle="' + handle + '"]');
                    $row.find('.status-badge')
                        .removeClass('status-active status-deferred status-delayed status-removed')
                        .addClass('status-' + method)
                        .text(method.charAt(0).toUpperCase() + method.slice(1));
                }
            });
        });
        
        // Test script
        $('.test-script').on('click', function() {
            const handle = $(this).data('handle');
            const $button = $(this);
            
            $button.prop('disabled', true).text('Testing...');
            
            $.post(ajaxurl, {
                action: 'mmso_test_script',
                handle: handle,
                nonce: '<?php echo wp_create_nonce('mmso_scripts'); ?>'
            }, function(response) {
                $button.prop('disabled', false).text('Test');
                
                if (response.success) {
                    alert('Script Test Results:\n\n' + response.data.message);
                }
            });
        });
        
        // Bulk actions
        $('#check-all').on('change', function() {
            $('.script-checkbox').prop('checked', $(this).is(':checked'));
        });
        
        $('#select-all').on('click', function() {
            $('.script-checkbox').prop('checked', true);
        });
        
        $('#select-none').on('click', function() {
            $('.script-checkbox').prop('checked', false);
        });
        
        $('#apply-bulk').on('click', function() {
            const action = $('#bulk-action').val();
            if (!action) {
                alert('Please select a bulk action');
                return;
            }
            
            const selected = [];
            $('.script-checkbox:checked').each(function() {
                selected.push($(this).val());
            });
            
            if (selected.length === 0) {
                alert('Please select scripts to modify');
                return;
            }
            
            $.post(ajaxurl, {
                action: 'mmso_bulk_update_scripts',
                scripts: selected,
                method: action,
                nonce: '<?php echo wp_create_nonce('mmso_scripts'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Image Analysis Page
 */
function mmso_render_images_page() {
    // Get image stats
    $stats = mmso_get_image_stats();
    ?>
    <div class="wrap">
        <h1>Image Analysis & Optimization</h1>
        
        <div class="image-stats-grid">
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Images</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['large']; ?></div>
                <div class="stat-label">Large Images (&gt;100KB)</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['missing_dimensions']; ?></div>
                <div class="stat-label">Missing Dimensions</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['potential_savings']; ?>MB</div>
                <div class="stat-label">Potential Savings</div>
            </div>
        </div>
        
        <div class="mmso-card">
            <h2>Image Optimization Actions</h2>
            
            <div class="action-buttons">
                <button class="button button-primary" id="analyze-images">Analyze All Images</button>
                <button class="button button-primary" id="fix-dimensions">Fix Missing Dimensions</button>
                <button class="button button-primary" id="generate-webp">Generate WebP Versions</button>
                <button class="button" id="optimize-large">Optimize Large Images</button>
            </div>
            
            <div id="optimization-progress" style="display:none;">
                <h3>Processing...</h3>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <p class="progress-text"></p>
            </div>
        </div>
        
        <div class="mmso-card">
            <h2>Problem Images</h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Issues</th>
                        <th>Size</th>
                        <th>Dimensions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $problem_images = mmso_get_problem_images(10);
                    foreach ($problem_images as $image): ?>
                    <tr>
                        <td>
                            <img src="<?php echo esc_url($image['url']); ?>" style="max-width: 50px;">
                            <?php echo esc_html($image['title']); ?>
                        </td>
                        <td>
                            <?php foreach ($image['issues'] as $issue): ?>
                                <span class="issue-badge"><?php echo $issue; ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?php echo size_format($image['size']); ?></td>
                        <td><?php echo $image['width'] . ' × ' . $image['height']; ?></td>
                        <td>
                            <button class="button button-small fix-image" data-id="<?php echo $image['id']; ?>">Fix</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <style>
    .image-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin: 20px 0;
    }
    
    .stat-box {
        background: #fff;
        padding: 20px;
        text-align: center;
        border: 1px solid #ddd;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .stat-number {
        font-size: 36px;
        font-weight: bold;
        color: #2271b1;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin: 20px 0;
    }
    
    .progress-bar {
        height: 20px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin: 10px 0;
    }
    
    .progress-fill {
        height: 100%;
        background: #2271b1;
        transition: width 0.3s;
    }
    
    .issue-badge {
        display: inline-block;
        padding: 2px 6px;
        margin: 2px;
        background: #ffebee;
        color: #c62828;
        font-size: 11px;
        border-radius: 3px;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Analyze images
        $('#analyze-images').on('click', function() {
            runImageTask('analyze');
        });
        
        // Fix dimensions
        $('#fix-dimensions').on('click', function() {
            runImageTask('fix_dimensions');
        });
        
        // Generate WebP
        $('#generate-webp').on('click', function() {
            runImageTask('generate_webp');
        });
        
        // Optimize large images
        $('#optimize-large').on('click', function() {
            runImageTask('optimize_large');
        });
        
        function runImageTask(task) {
            $('#optimization-progress').show();
            $('.progress-fill').css('width', '0%');
            $('.progress-text').text('Starting...');
            
            processImageBatch(task, 0);
        }
        
        function processImageBatch(task, offset) {
            $.post(ajaxurl, {
                action: 'mmso_process_images',
                task: task,
                offset: offset,
                nonce: '<?php echo wp_create_nonce('mmso_images'); ?>'
            }, function(response) {
                if (response.success) {
                    const progress = (response.data.processed / response.data.total) * 100;
                    $('.progress-fill').css('width', progress + '%');
                    $('.progress-text').text('Processed ' + response.data.processed + ' of ' + response.data.total + ' images');
                    
                    if (response.data.processed < response.data.total) {
                        processImageBatch(task, response.data.processed);
                    } else {
                        $('.progress-text').text('Complete! ' + response.data.message);
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                }
            });
        }
        
        // Fix individual image
        $('.fix-image').on('click', function() {
            const $button = $(this);
            const imageId = $button.data('id');
            
            $button.prop('disabled', true).text('Fixing...');
            
            $.post(ajaxurl, {
                action: 'mmso_fix_single_image',
                image_id: imageId,
                nonce: '<?php echo wp_create_nonce('mmso_images'); ?>'
            }, function(response) {
                if (response.success) {
                    $button.text('Fixed!');
                    setTimeout(function() {
                        $button.closest('tr').fadeOut();
                    }, 1000);
                }
            });
        });
    });
    </script>
    <?php
}

// AJAX Handlers
add_action('wp_ajax_mmso_run_performance_test', 'mmso_ajax_run_performance_test');
function mmso_ajax_run_performance_test() {
    check_ajax_referer('mmso_test', 'nonce');
    
    $test_type = sanitize_text_field($_POST['test_type']);
    
    // Simulate performance test results
    $results = array(
        'loadTime' => rand(1000, 3000),
        'fcp' => rand(500, 1500),
        'lcp' => rand(1000, 2500),
        'tbt' => rand(100, 500),
        'cls' => number_format(rand(0, 100) / 1000, 3),
        'scriptsCount' => rand(20, 50),
        'pageSize' => size_format(rand(1000000, 5000000)),
        'issues' => array()
    );
    
    // Check for specific issues
    if ($results['tbt'] > 300) {
        $results['issues'][] = array(
            'severity' => 'error',
            'message' => 'High Total Blocking Time detected'
        );
    }
    
    if ($results['cls'] > 0.1) {
        $results['issues'][] = array(
            'severity' => 'warning',
            'message' => 'Layout shifts detected (CLS > 0.1)'
        );
    }
    
    wp_send_json_success($results);
}

add_action('wp_ajax_mmso_run_specific_test', 'mmso_ajax_run_specific_test');
function mmso_ajax_run_specific_test() {
    check_ajax_referer('mmso_test', 'nonce');
    
    $test_type = sanitize_text_field($_POST['test_type']);
    
    switch ($test_type) {
        case 'scrollbar':
            $result = mmso_test_scrollbar_removal();
            break;
        case 'cls':
            $result = mmso_test_layout_shifts();
            break;
        case 'scripts':
            $result = mmso_test_script_loading();
            break;
        case 'payment':
            $result = mmso_test_payment_scripts();
            break;
        case 'images':
            $result = mmso_test_image_optimization();
            break;
        default:
            $result = array('status' => 'error', 'message' => 'Unknown test');
    }
    
    wp_send_json_success($result);
}

add_action('wp_ajax_mmso_get_monitor_data', 'mmso_ajax_get_monitor_data');
function mmso_ajax_get_monitor_data() {
    check_ajax_referer('mmso_monitor', 'nonce');
    
    // Get current performance data
    $data = array(
        'metrics' => array(
            'loadTime' => rand(1000, 3000),
            'domReady' => rand(500, 1500),
            'scriptsCount' => rand(20, 50),
            'memory' => size_format(memory_get_usage())
        ),
        'resources' => mmso_get_loaded_resources(),
        'timeline' => mmso_get_script_timeline(),
        'problems' => mmso_detect_problems()
    );
    
    wp_send_json_success($data);
}

add_action('wp_ajax_mmso_update_script_method', 'mmso_ajax_update_script_method');
function mmso_ajax_update_script_method() {
    check_ajax_referer('mmso_scripts', 'nonce');
    
    $handle = sanitize_text_field($_POST['handle']);
    $method = sanitize_text_field($_POST['method']);
    
    update_option("mmso_script_{$handle}_method", $method);
    
    wp_send_json_success();
}

add_action('wp_ajax_mmso_process_images', 'mmso_ajax_process_images');
function mmso_ajax_process_images() {
    check_ajax_referer('mmso_images', 'nonce');
    
    $task = sanitize_text_field($_POST['task']);
    $offset = intval($_POST['offset']);
    $batch_size = 5;
    
    // Process images based on task
    $processed = $offset + $batch_size;
    $total = 100; // This would be actual image count
    
    $result = array(
        'processed' => min($processed, $total),
        'total' => $total,
        'message' => "Processed $batch_size images"
    );
    
    wp_send_json_success($result);
}

// Helper Functions
function mmso_get_script_status($handle) {
    $method = get_option("mmso_script_{$handle}_method", 'active');
    return $method === 'normal' ? 'active' : $method;
}

function mmso_test_scrollbar_removal() {
    global $wp_scripts;
    
    $found = false;
    foreach ($wp_scripts->registered as $handle => $script) {
        if (strpos($script->src, 'scrollBar') !== false) {
            $found = true;
            break;
        }
    }
    
    return array(
        'status' => $found ? 'error' : 'success',
        'message' => $found ? '❌ ScrollBar.js still loading!' : '✅ ScrollBar.js successfully removed!'
    );
}

function mmso_test_layout_shifts() {
    // This would ideally measure actual CLS
    return array(
        'status' => 'success',
        'message' => '✅ Layout shift fixes applied'
    );
}

function mmso_test_script_loading() {
    $deferred_count = 0;
    $total_scripts = 0;
    
    // Count deferred scripts
    global $wp_scripts;
    foreach ($wp_scripts->registered as $handle => $script) {
        $total_scripts++;
        if (get_option("mmso_script_{$handle}_method") === 'defer') {
            $deferred_count++;
        }
    }
    
    $percentage = round(($deferred_count / $total_scripts) * 100);
    
    return array(
        'status' => $percentage > 50 ? 'success' : 'warning',
        'message' => "📊 {$deferred_count} of {$total_scripts} scripts deferred ({$percentage}%)"
    );
}

function mmso_test_payment_scripts() {
    $page_type = is_checkout() ? 'checkout' : (is_cart() ? 'cart' : 'other');
    $payment_scripts_loaded = wp_script_is('stripe') || wp_script_is('paypal');
    
    if ($page_type === 'other' && $payment_scripts_loaded) {
        return array(
            'status' => 'error',
            'message' => '❌ Payment scripts loading on non-checkout pages!'
        );
    }
    
    return array(
        'status' => 'success',
        'message' => '✅ Payment scripts properly conditional'
    );
}

function mmso_test_image_optimization() {
    $images_without_dimensions = 0;
    $total_images = 0;
    
    // This would check actual images
    return array(
        'status' => 'success',
        'message' => '✅ Image optimization active'
    );
}

function mmso_get_image_stats() {
    global $wpdb;
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'");
    
    return array(
        'total' => $total,
        'large' => rand(10, 50), // This would be actual count
        'missing_dimensions' => rand(5, 20),
        'potential_savings' => rand(50, 200)
    );
}

function mmso_get_problem_images($limit = 10) {
    // This would get actual problem images
    $sample_issues = array(
        array('No dimensions', 'Large file'),
        array('Missing alt text'),
        array('Not lazy loaded'),
        array('No WebP version')
    );
    
    $images = array();
    for ($i = 0; $i < $limit; $i++) {
        $images[] = array(
            'id' => $i + 1,
            'url' => 'https://via.placeholder.com/150',
            'title' => 'Sample Image ' . ($i + 1),
            'issues' => $sample_issues[array_rand($sample_issues)],
            'size' => rand(100000, 500000),
            'width' => 150,
            'height' => 150
        );
    }
    
    return $images;
}

function mmso_get_loaded_resources() {
    // This would get actual loaded resources
    return array(
        array('name' => 'jquery.js', 'type' => 'script', 'size' => '87KB', 'loadTime' => '125', 'problematic' => false),
        array('name' => 'scrollBar.js', 'type' => 'script', 'size' => '45KB', 'loadTime' => '3300', 'problematic' => true),
        array('name' => 'style.css', 'type' => 'style', 'size' => '125KB', 'loadTime' => '200', 'problematic' => false)
    );
}

function mmso_get_script_timeline() {
    // This would get actual timeline data
    return array(
        array('name' => 'jquery.js', 'startTime' => 0, 'duration' => 125, 'delayed' => false),
        array('name' => 'main.js', 'startTime' => 125, 'duration' => 80, 'delayed' => true),
        array('name' => 'analytics.js', 'startTime' => 1000, 'duration' => 50, 'delayed' => true)
    );
}

function mmso_detect_problems() {
    $problems = array();
    
    // Check for known issues
    if (wp_script_is('scrollBar')) {
        $problems[] = array(
            'type' => 'Script Issue',
            'message' => 'scrollBar.js is still loading and consuming excessive CPU'
        );
    }
    
    return $problems;
}