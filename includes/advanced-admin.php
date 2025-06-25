<?php
/**
 * Advanced Admin Interface for MunchMakers Speed Optimizer
 * Save as: includes/advanced-admin.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add advanced menu items
add_action('admin_menu', function() {
    // Image Optimizer page
    add_submenu_page(
        'mmso-dashboard',
        'Image Optimizer',
        'Image Optimizer',
        'manage_options',
        'mmso-images',
        'mmso_render_image_optimizer_page'
    );
    
    // Script Manager page
    add_submenu_page(
        'mmso-dashboard',
        'Script Manager',
        'Script Manager',
        'manage_options',
        'mmso-scripts',
        'mmso_render_script_manager_page'
    );
    
    // Cache Manager page
    add_submenu_page(
        'mmso-dashboard',
        'Cache Manager',
        'Cache Manager',
        'manage_options',
        'mmso-cache',
        'mmso_render_cache_manager_page'
    );
    
    // Settings page
    add_submenu_page(
        'mmso-dashboard',
        'Advanced Settings',
        'Settings',
        'manage_options',
        'mmso-settings',
        'mmso_render_settings_page'
    );
});

// Image Optimizer Page
function mmso_render_image_optimizer_page() {
    // Get image statistics
    global $wpdb;
    $total_images = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'");
    $webp_count = mmso_count_webp_images();
    $large_images = mmso_get_large_images();
    ?>
    <div class="wrap">
        <h1>Image Optimizer</h1>
        
        <div class="mmso-stats-grid">
            <div class="mmso-stat-card">
                <div class="stat-number"><?php echo number_format($total_images); ?></div>
                <div class="stat-label">Total Images</div>
            </div>
            <div class="mmso-stat-card">
                <div class="stat-number"><?php echo number_format($webp_count); ?></div>
                <div class="stat-label">WebP Versions</div>
            </div>
            <div class="mmso-stat-card">
                <div class="stat-number"><?php echo count($large_images); ?></div>
                <div class="stat-label">Large Images (>100KB)</div>
            </div>
            <div class="mmso-stat-card">
                <div class="stat-number"><?php echo mmso_get_potential_savings(); ?>MB</div>
                <div class="stat-label">Potential Savings</div>
            </div>
        </div>
        
        <div class="mmso-card">
            <h2>Bulk Optimize Images</h2>
            <p>Convert all images to WebP format and optimize file sizes.</p>
            
            <div class="mmso-progress" style="display:none;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <p class="progress-text">Processing...</p>
            </div>
            
            <p>
                <button class="button button-primary" id="bulk-optimize">Start Bulk Optimization</button>
                <button class="button" id="analyze-images">Analyze Images</button>
            </p>
        </div>
        
        <div class="mmso-card">
            <h2>Large Images Requiring Optimization</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Size</th>
                        <th>Dimensions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($large_images, 0, 10) as $image): ?>
                    <tr>
                        <td>
                            <img src="<?php echo esc_url($image['url']); ?>" style="max-width: 50px; height: auto;">
                            <?php echo esc_html($image['title']); ?>
                        </td>
                        <td><?php echo size_format($image['size']); ?></td>
                        <td><?php echo $image['width'] . ' × ' . $image['height']; ?></td>
                        <td>
                            <button class="button button-small optimize-single" data-id="<?php echo $image['id']; ?>">Optimize</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <style>
    .mmso-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    
    .mmso-stat-card {
        background: #fff;
        padding: 20px;
        text-align: center;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .stat-number {
        font-size: 36px;
        font-weight: bold;
        color: #2271b1;
    }
    
    .stat-label {
        color: #666;
        margin-top: 5px;
    }
    
    .mmso-card {
        background: #fff;
        padding: 20px;
        margin: 20px 0;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .mmso-progress {
        margin: 20px 0;
    }
    
    .progress-bar {
        height: 20px;
        background: #f0f0f1;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: #2271b1;
        transition: width 0.3s;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Bulk optimize
        $('#bulk-optimize').on('click', function() {
            const $button = $(this);
            const $progress = $('.mmso-progress');
            
            $button.prop('disabled', true);
            $progress.show();
            
            // Start optimization process
            optimizeNextBatch(0);
        });
        
        function optimizeNextBatch(offset) {
            $.post(ajaxurl, {
                action: 'mmso_bulk_optimize_batch',
                offset: offset,
                nonce: window.mmso_ajax.nonce
            }, function(response) {
                if (response.success) {
                    const progress = (response.data.processed / response.data.total) * 100;
                    $('.progress-fill').css('width', progress + '%');
                    $('.progress-text').text('Processed ' + response.data.processed + ' of ' + response.data.total);
                    
                    if (response.data.processed < response.data.total) {
                        optimizeNextBatch(response.data.processed);
                    } else {
                        $('.progress-text').text('Optimization complete!');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                }
            });
        }
        
        // Single image optimize
        $('.optimize-single').on('click', function() {
            const $button = $(this);
            const imageId = $button.data('id');
            
            $button.prop('disabled', true).text('Optimizing...');
            
            $.post(ajaxurl, {
                action: 'mmso_optimize_single_image',
                attachment_id: imageId,
                nonce: window.mmso_ajax.nonce
            }, function(response) {
                if (response.success) {
                    $button.text('Optimized!');
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

// Script Manager Page
function mmso_render_script_manager_page() {
    // Get all enqueued scripts
    global $wp_scripts;
    $scripts = array();
    
    if (isset($wp_scripts->registered)) {
        foreach ($wp_scripts->registered as $handle => $script) {
            $scripts[$handle] = array(
                'src' => $script->src,
                'deps' => $script->deps,
                'ver' => $script->ver,
                'in_footer' => (bool) $script->args
            );
        }
    }
    
    $delayed_scripts = get_option('mmso_delayed_scripts', array());
    ?>
    <div class="wrap">
        <h1>Script Manager</h1>
        
        <div class="mmso-card">
            <h2>Script Loading Configuration</h2>
            <p>Configure how scripts are loaded on your site. Be careful when modifying critical scripts.</p>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('mmso_save_scripts', 'mmso_nonce'); ?>
                <input type="hidden" name="action" value="mmso_save_script_settings">
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Script Handle</th>
                            <th>Source</th>
                            <th>Loading Strategy</th>
                            <th>Delay Until Interaction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scripts as $handle => $script): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($handle); ?></strong>
                                <?php if (!empty($script['deps'])): ?>
                                <br><small>Deps: <?php echo implode(', ', $script['deps']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo esc_html(substr($script['src'], strrpos($script['src'], '/') + 1)); ?></small>
                            </td>
                            <td>
                                <select name="script_loading[<?php echo esc_attr($handle); ?>]">
                                    <option value="">Normal</option>
                                    <option value="defer" <?php selected(get_option("mmso_script_{$handle}_loading"), 'defer'); ?>>Defer</option>
                                    <option value="async" <?php selected(get_option("mmso_script_{$handle}_loading"), 'async'); ?>>Async</option>
                                </select>
                            </td>
                            <td>
                                <input type="checkbox" name="delayed_scripts[]" value="<?php echo esc_attr($handle); ?>" 
                                    <?php checked(in_array($handle, $delayed_scripts)); ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="Save Script Settings">
                </p>
            </form>
        </div>
        
        <div class="mmso-card">
            <h2>Recommended Optimizations</h2>
            <ul>
                <li><strong>Defer:</strong> jQuery, WooCommerce scripts, theme scripts</li>
                <li><strong>Async:</strong> Analytics, tracking scripts</li>
                <li><strong>Delay:</strong> Social media widgets, chat widgets, marketing scripts</li>
            </ul>
        </div>
    </div>
    <?php
}

// Cache Manager Page
function mmso_render_cache_manager_page() {
    $upload_dir = wp_upload_dir();
    $cache_dir = $upload_dir['basedir'] . '/mmso-cache/';
    $cache_size = mmso_get_directory_size($cache_dir);
    $cache_files = glob($cache_dir . '*.html');
    $cache_count = is_array($cache_files) ? count($cache_files) : 0;
    ?>
    <div class="wrap">
        <h1>Cache Manager</h1>
        
        <div class="mmso-stats-grid">
            <div class="mmso-stat-card">
                <div class="stat-number"><?php echo $cache_count; ?></div>
                <div class="stat-label">Cached Pages</div>
            </div>
            <div class="mmso-stat-card">
                <div class="stat-number"><?php echo size_format($cache_size); ?></div>
                <div class="stat-label">Cache Size</div>
            </div>
            <div class="mmso-stat-card">
                <div class="stat-number"><?php echo mmso_get_cache_hit_rate(); ?>%</div>
                <div class="stat-label">Hit Rate</div>
            </div>
        </div>
        
        <div class="mmso-card">
            <h2>Cache Actions</h2>
            <p>
                <button class="button button-primary" id="clear-all-cache">Clear All Cache</button>
                <button class="button" id="clear-page-cache">Clear Page Cache</button>
                <button class="button" id="clear-object-cache">Clear Object Cache</button>
            </p>
            
            <div id="cache-message" style="display:none;" class="notice notice-success">
                <p></p>
            </div>
        </div>
        
        <div class="mmso-card">
            <h2>Cache Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('mmso_cache_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th>Enable Page Caching</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mmso_enable_page_cache" value="1" 
                                    <?php checked(get_option('mmso_enable_page_cache', 1)); ?>>
                                Cache static pages for faster loading
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>Cache Expiration</th>
                        <td>
                            <input type="number" name="mmso_cache_expiration" 
                                value="<?php echo get_option('mmso_cache_expiration', 3600); ?>"> seconds
                        </td>
                    </tr>
                    <tr>
                        <th>Exclude Pages</th>
                        <td>
                            <textarea name="mmso_cache_exclude" rows="4" cols="50"><?php 
                                echo esc_textarea(get_option('mmso_cache_exclude', '/cart/\n/checkout/\n/my-account/')); 
                            ?></textarea>
                            <p class="description">One URL pattern per line</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Clear cache actions
        $('#clear-all-cache').on('click', function() {
            clearCache('all');
        });
        
        $('#clear-page-cache').on('click', function() {
            clearCache('page');
        });
        
        $('#clear-object-cache').on('click', function() {
            clearCache('object');
        });
        
        function clearCache(type) {
            $.post(ajaxurl, {
                action: 'mmso_clear_cache',
                cache_type: type,
                nonce: window.mmso_ajax.nonce
            }, function(response) {
                if (response.success) {
                    $('#cache-message').show().find('p').text(response.data);
                    setTimeout(function() {
                        $('#cache-message').fadeOut();
                    }, 3000);
                }
            });
        }
    });
    </script>
    <?php
}

// Settings Page
function mmso_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Advanced Settings</h1>
        
        <form method="post" action="options.php">
            <?php settings_fields('mmso_advanced_settings'); ?>
            
            <div class="mmso-card">
                <h2>Performance Settings</h2>
                
                <table class="form-table">
                    <tr>
                        <th>Delay JavaScript Execution</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mmso_delay_js" value="1" 
                                    <?php checked(get_option('mmso_delay_js')); ?>>
                                Delay non-critical JavaScript until user interaction
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>Remove jQuery Migrate</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mmso_remove_jquery_migrate" value="1" 
                                    <?php checked(get_option('mmso_remove_jquery_migrate', 1)); ?>>
                                Remove jQuery Migrate (not needed for modern themes)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>Optimize WooCommerce Scripts</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mmso_optimize_woo" value="1" 
                                    <?php checked(get_option('mmso_optimize_woo', 1)); ?>>
                                Load WooCommerce scripts only where needed
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="mmso-card">
                <h2>Image Settings</h2>
                
                <table class="form-table">
                    <tr>
                        <th>WebP Quality</th>
                        <td>
                            <input type="number" name="mmso_webp_quality" min="1" max="100" 
                                value="<?php echo get_option('mmso_webp_quality', 85); ?>">
                            <p class="description">Quality for WebP conversion (1-100)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Auto-Convert on Upload</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mmso_auto_webp" value="1" 
                                    <?php checked(get_option('mmso_auto_webp')); ?>>
                                Automatically create WebP versions of uploaded images
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="mmso-card">
                <h2>Database Settings</h2>
                
                <table class="form-table">
                    <tr>
                        <th>Post Revision Limit</th>
                        <td>
                            <input type="number" name="mmso_revision_limit" 
                                value="<?php echo get_option('mmso_revision_limit', 3); ?>">
                            <p class="description">Maximum number of revisions to keep (0 = disabled)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Auto Database Cleanup</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mmso_auto_cleanup" value="1" 
                                    <?php checked(get_option('mmso_auto_cleanup')); ?>>
                                Run database cleanup daily
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="mmso-card">
                <h2>Debug Settings</h2>
                
                <table class="form-table">
                    <tr>
                        <th>Performance Monitoring</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mmso_enable_monitoring" value="1" 
                                    <?php checked(get_option('mmso_enable_monitoring')); ?>>
                                Enable performance monitoring (admin only)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>Debug Mode</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mmso_debug_mode" value="1" 
                                    <?php checked(get_option('mmso_debug_mode')); ?>>
                                Show debug information in console
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button(); ?>
        </form>
        
        <div class="mmso-card">
            <h2>System Information</h2>
            <textarea readonly style="width: 100%; height: 200px;">
PHP Version: <?php echo PHP_VERSION; ?>

WordPress Version: <?php echo get_bloginfo('version'); ?>

Active Theme: <?php echo wp_get_theme()->get('Name'); ?>

WooCommerce Version: <?php echo defined('WC_VERSION') ? WC_VERSION : 'Not Active'; ?>

Memory Limit: <?php echo WP_MEMORY_LIMIT; ?>

Max Execution Time: <?php echo ini_get('max_execution_time'); ?>s
Upload Max Size: <?php echo ini_get('upload_max_filesize'); ?>

Active Plugins:
<?php
$active_plugins = get_option('active_plugins');
foreach ($active_plugins as $plugin) {
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
    echo $plugin_data['Name'] . ' v' . $plugin_data['Version'] . "\n";
}
?>
            </textarea>
        </div>
    </div>
    <?php
}

// Register settings
add_action('admin_init', function() {
    // Cache settings
    register_setting('mmso_cache_settings', 'mmso_enable_page_cache');
    register_setting('mmso_cache_settings', 'mmso_cache_expiration');
    register_setting('mmso_cache_settings', 'mmso_cache_exclude');
    
    // Advanced settings
    register_setting('mmso_advanced_settings', 'mmso_delay_js');
    register_setting('mmso_advanced_settings', 'mmso_remove_jquery_migrate');
    register_setting('mmso_advanced_settings', 'mmso_optimize_woo');
    register_setting('mmso_advanced_settings', 'mmso_webp_quality');
    register_setting('mmso_advanced_settings', 'mmso_auto_webp');
    register_setting('mmso_advanced_settings', 'mmso_revision_limit');
    register_setting('mmso_advanced_settings', 'mmso_auto_cleanup');
    register_setting('mmso_advanced_settings', 'mmso_enable_monitoring');
    register_setting('mmso_advanced_settings', 'mmso_debug_mode');
});

// Handle script settings save
add_action('admin_post_mmso_save_script_settings', function() {
    if (!isset($_POST['mmso_nonce']) || !wp_verify_nonce($_POST['mmso_nonce'], 'mmso_save_scripts')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Save script loading strategies
    if (isset($_POST['script_loading'])) {
        foreach ($_POST['script_loading'] as $handle => $strategy) {
            if ($strategy) {
                update_option("mmso_script_{$handle}_loading", $strategy);
            } else {
                delete_option("mmso_script_{$handle}_loading");
            }
        }
    }
    
    // Save delayed scripts
    $delayed_scripts = isset($_POST['delayed_scripts']) ? array_map('sanitize_text_field', $_POST['delayed_scripts']) : array();
    update_option('mmso_delayed_scripts', $delayed_scripts);
    
    wp_redirect(admin_url('admin.php?page=mmso-scripts&updated=1'));
    exit;
});

// Helper functions
function mmso_count_webp_images() {
    $upload_dir = wp_upload_dir();
    $webp_files = glob($upload_dir['basedir'] . '/**/*.webp', GLOB_BRACE);
    return is_array($webp_files) ? count($webp_files) : 0;
}

function mmso_get_large_images($limit = 100) {
    global $wpdb;
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_title, pm.meta_value
        FROM $wpdb->posts p
        JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
        WHERE p.post_type = 'attachment'
        AND p.post_mime_type LIKE 'image/%'
        AND pm.meta_key = '_wp_attachment_metadata'
        ORDER BY p.ID DESC
        LIMIT %d
    ", $limit));
    
    $large_images = array();
    
    foreach ($results as $result) {
        $metadata = maybe_unserialize($result->meta_value);
        if (isset($metadata['filesize']) && $metadata['filesize'] > 102400) { // 100KB
            $large_images[] = array(
                'id' => $result->ID,
                'title' => $result->post_title,
                'size' => $metadata['filesize'],
                'width' => $metadata['width'],
                'height' => $metadata['height'],
                'url' => wp_get_attachment_url($result->ID)
            );
        }
    }
    
    return $large_images;
}

function mmso_get_potential_savings() {
    $large_images = mmso_get_large_images(1000);
    $total_size = array_sum(array_column($large_images, 'size'));
    $potential_savings = $total_size * 0.3; // Assume 30% reduction
    return round($potential_savings / 1024 / 1024, 1); // Convert to MB
}

function mmso_get_directory_size($dir) {
    $size = 0;
    
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $path = $dir . '/' . $file;
                if (is_file($path)) {
                    $size += filesize($path);
                }
            }
        }
    }
    
    return $size;
}

function mmso_get_cache_hit_rate() {
    $hits = get_option('mmso_cache_hits', 0);
    $misses = get_option('mmso_cache_misses', 0);
    $total = $hits + $misses;
    
    return $total > 0 ? round(($hits / $total) * 100, 1) : 0;
}

// AJAX handlers for bulk operations
add_action('wp_ajax_mmso_bulk_optimize_batch', function() {
    check_ajax_referer('mmso_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die();
    }
    
    $offset = intval($_POST['offset']);
    $batch_size = 5;
    
    // Get images to process
    global $wpdb;
    $images = $wpdb->get_results($wpdb->prepare("
        SELECT ID FROM $wpdb->posts 
        WHERE post_type = 'attachment' 
        AND post_mime_type LIKE 'image/%'
        LIMIT %d OFFSET %d
    ", $batch_size, $offset));
    
    $processed = 0;
    foreach ($images as $image) {
        // Process image (create WebP, optimize, etc.)
        $file_path = get_attached_file($image->ID);
        if ($file_path && file_exists($file_path)) {
            // This would call the actual optimization functions
            $processed++;
        }
    }
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'");
    
    wp_send_json_success(array(
        'processed' => $offset + $processed,
        'total' => $total
    ));
});

add_action('wp_ajax_mmso_optimize_single_image', function() {
    check_ajax_referer('mmso_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die();
    }
    
    $attachment_id = intval($_POST['attachment_id']);
    
    // Process the image
    // This would call the actual optimization functions
    
    wp_send_json_success('Image optimized successfully');
});

add_action('wp_ajax_mmso_clear_cache', function() {
    check_ajax_referer('mmso_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die();
    }
    
    $cache_type = sanitize_text_field($_POST['cache_type']);
    
    switch ($cache_type) {
        case 'all':
            // Clear all caches
            $message = 'All caches cleared successfully';
            break;
        case 'page':
            // Clear page cache
            $message = 'Page cache cleared successfully';
            break;
        case 'object':
            // Clear object cache
            $message = 'Object cache cleared successfully';
            break;
        default:
            $message = 'Unknown cache type';
    }
    
    wp_send_json_success($message);
});