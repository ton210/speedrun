<?php
/**
 * MunchMakers Speed Optimizer - Advanced Modules
 * Add this file to your plugin directory as: includes/advanced-modules.php
 * Then include it in your main plugin file after the basic functionality is loaded
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Advanced Image Optimization Module
 */
class MMSO_Image_Optimizer {
    
    public function __construct() {
        add_filter('wp_handle_upload', array($this, 'optimize_on_upload'), 10, 2);
        add_filter('wp_get_attachment_image_attributes', array($this, 'advanced_image_attributes'), 10, 3);
        add_filter('the_content', array($this, 'optimize_content_images'), 999);
        add_action('wp_ajax_mmso_convert_to_webp', array($this, 'ajax_convert_to_webp'));
        add_action('wp_head', array($this, 'add_image_optimization_styles'), 5);
    }
    
    public function optimize_on_upload($upload, $context) {
        if (!empty($upload['type']) && strpos($upload['type'], 'image') !== false) {
            $file_path = $upload['file'];
            
            // Generate WebP version
            if (function_exists('imagewebp')) {
                $this->create_webp_version($file_path);
            }
            
            // Optimize original
            $this->optimize_image($file_path);
        }
        
        return $upload;
    }
    
    private function create_webp_version($file_path) {
        $info = pathinfo($file_path);
        $dir = $info['dirname'];
        $name = $info['filename'];
        $webp_path = $dir . '/' . $name . '.webp';
        
        // Skip if WebP already exists
        if (file_exists($webp_path)) {
            return;
        }
        
        $image = null;
        switch (strtolower($info['extension'])) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file_path);
                break;
            case 'png':
                $image = imagecreatefrompng($file_path);
                break;
            case 'gif':
                $image = imagecreatefromgif($file_path);
                break;
        }
        
        if ($image) {
            imagewebp($image, $webp_path, 85);
            imagedestroy($image);
        }
    }
    
    private function optimize_image($file_path) {
        // Get image size
        $size = filesize($file_path);
        
        // Only optimize if larger than 100KB
        if ($size > 102400) {
            $image_info = getimagesize($file_path);
            if ($image_info) {
                $this->compress_image($file_path, $image_info);
            }
        }
    }
    
    private function compress_image($file_path, $image_info) {
        $quality = 85;
        $type = $image_info['mime'];
        
        switch ($type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file_path);
                imagejpeg($image, $file_path, $quality);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file_path);
                imagepng($image, $file_path, 9);
                break;
        }
        
        if (isset($image)) {
            imagedestroy($image);
        }
    }
    
    public function advanced_image_attributes($attributes, $attachment, $size) {
        // Add loading lazy
        $attributes['loading'] = 'lazy';
        $attributes['decoding'] = 'async';
        
        // Add dimensions if missing
        if (empty($attributes['width']) || empty($attributes['height'])) {
            $image_src = wp_get_attachment_image_src($attachment->ID, $size);
            if ($image_src) {
                $attributes['width'] = $image_src[1];
                $attributes['height'] = $image_src[2];
            }
        }
        
        // Add WebP source if available
        $image_url = wp_get_attachment_url($attachment->ID);
        $webp_url = $this->get_webp_url($image_url);
        
        if ($this->webp_exists($webp_url)) {
            $attributes['data-webp'] = $webp_url;
            $attributes['onerror'] = "this.onerror=null;this.src=this.dataset.src||this.src;";
        }
        
        return $attributes;
    }
    
    private function get_webp_url($image_url) {
        return preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $image_url);
    }
    
    private function webp_exists($webp_url) {
        $webp_path = str_replace(site_url(), ABSPATH, $webp_url);
        return file_exists($webp_path);
    }
    
    public function optimize_content_images($content) {
        // Add picture elements for WebP support
        $content = preg_replace_callback('/<img([^>]+)>/i', array($this, 'wrap_img_in_picture'), $content);
        
        return $content;
    }
    
    private function wrap_img_in_picture($matches) {
        $img_tag = $matches[0];
        
        // Extract src
        preg_match('/src=["\']([^"\']+)["\']/', $img_tag, $src_match);
        if (empty($src_match[1])) {
            return $img_tag;
        }
        
        $src = $src_match[1];
        $webp_url = $this->get_webp_url($src);
        
        // Only wrap if WebP exists
        if ($this->webp_exists($webp_url)) {
            return sprintf(
                '<picture><source srcset="%s" type="image/webp">%s</picture>',
                esc_url($webp_url),
                $img_tag
            );
        }
        
        return $img_tag;
    }
    
    public function add_image_optimization_styles() {
        ?>
        <style>
        /* Progressive image loading */
        img[loading="lazy"] {
            background: #f0f0f0;
            background-image: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: mmso-loading 1.5s infinite;
        }
        
        img[loading="lazy"].loaded {
            background: none;
            animation: none;
        }
        
        @keyframes mmso-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Blur-up effect */
        .mmso-blur-up {
            filter: blur(5px);
            transition: filter 0.3s;
        }
        
        .mmso-blur-up.loaded {
            filter: blur(0);
        }
        </style>
        <script>
        // Progressive image loading
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img[loading="lazy"]');
            
            const imageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.addEventListener('load', function() {
                            img.classList.add('loaded');
                        });
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
        });
        </script>
        <?php
    }
    
    public function ajax_convert_to_webp() {
        check_ajax_referer('mmso_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        $file_path = get_attached_file($attachment_id);
        
        if ($file_path && file_exists($file_path)) {
            $this->create_webp_version($file_path);
            wp_send_json_success(array(
                'message' => 'WebP version created successfully',
                'webp_url' => $this->get_webp_url(wp_get_attachment_url($attachment_id))
            ));
        }
        
        wp_send_json_error('Failed to create WebP version');
    }
}

/**
 * Advanced JavaScript Optimization Module
 */
class MMSO_JavaScript_Optimizer {
    
    private $delayed_scripts = array();
    private $critical_scripts = array('jquery-core');
    
    public function __construct() {
        add_filter('script_loader_tag', array($this, 'optimize_script_loading'), 10, 3);
        add_action('wp_footer', array($this, 'inject_script_loader'), 999);
        add_action('wp_head', array($this, 'preload_critical_scripts'), 1);
        add_filter('script_loader_src', array($this, 'add_script_versioning'), 10, 2);
    }
    
    public function optimize_script_loading($tag, $handle, $src) {
        // Skip admin scripts
        if (is_admin()) {
            return $tag;
        }
        
        // Critical scripts get preloaded
        if (in_array($handle, $this->critical_scripts)) {
            return $tag;
        }
        
        // Delay third-party scripts
        $delay_patterns = array(
            'klaviyo',
            'facebook',
            'google-analytics',
            'hotjar',
            'tiktok',
            'pinterest'
        );
        
        foreach ($delay_patterns as $pattern) {
            if (strpos($src, $pattern) !== false) {
                $this->delayed_scripts[] = $src;
                return ''; // Remove from initial load
            }
        }
        
        // Add loading strategy based on script
        if ($this->should_defer($handle)) {
            $tag = str_replace(' src', ' defer src', $tag);
        } elseif ($this->should_async($handle)) {
            $tag = str_replace(' src', ' async src', $tag);
        }
        
        return $tag;
    }
    
    private function should_defer($handle) {
        $defer_scripts = array(
            'jquery',
            'woocommerce',
            'wc-cart-fragments',
            'elementor-frontend',
            'swiper'
        );
        
        return in_array($handle, $defer_scripts);
    }
    
    private function should_async($handle) {
        $async_scripts = array(
            'wp-embed',
            'comment-reply'
        );
        
        return in_array($handle, $async_scripts);
    }
    
    public function inject_script_loader() {
        if (empty($this->delayed_scripts)) {
            return;
        }
        ?>
        <script id="mmso-delayed-scripts">
        (function() {
            const delayedScripts = <?php echo json_encode($this->delayed_scripts); ?>;
            let scriptsLoaded = false;
            
            function loadDelayedScripts() {
                if (scriptsLoaded) return;
                scriptsLoaded = true;
                
                console.log('Loading delayed scripts...');
                
                delayedScripts.forEach(function(src, index) {
                    setTimeout(function() {
                        const script = document.createElement('script');
                        script.src = src;
                        script.async = true;
                        document.body.appendChild(script);
                    }, index * 100); // Stagger loading
                });
            }
            
            // Strategy 1: Load on user interaction
            const triggers = ['mousedown', 'keydown', 'touchstart', 'scroll'];
            triggers.forEach(function(event) {
                document.addEventListener(event, loadDelayedScripts, {
                    once: true,
                    passive: true
                });
            });
            
            // Strategy 2: Load after main content
            if (document.readyState === 'complete') {
                setTimeout(loadDelayedScripts, 1000);
            } else {
                window.addEventListener('load', function() {
                    setTimeout(loadDelayedScripts, 1000);
                });
            }
            
            // Strategy 3: Fallback after 5 seconds
            setTimeout(loadDelayedScripts, 5000);
        })();
        </script>
        <?php
    }
    
    public function preload_critical_scripts() {
        global $wp_scripts;
        
        if (!is_admin() && isset($wp_scripts->registered)) {
            foreach ($this->critical_scripts as $handle) {
                if (isset($wp_scripts->registered[$handle])) {
                    $script = $wp_scripts->registered[$handle];
                    if (!empty($script->src)) {
                        echo '<link rel="preload" as="script" href="' . esc_url($script->src) . '">' . "\n";
                    }
                }
            }
        }
    }
    
    public function add_script_versioning($src, $handle) {
        // Add cache busting for development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $src = add_query_arg('mmso_ver', time(), $src);
        }
        
        return $src;
    }
}

/**
 * Advanced CSS Optimization Module
 */
class MMSO_CSS_Optimizer {
    
    private $critical_css = '';
    private $deferred_styles = array();
    
    public function __construct() {
        add_action('wp_head', array($this, 'inject_critical_css'), 1);
        add_filter('style_loader_tag', array($this, 'optimize_css_loading'), 10, 4);
        add_action('wp_footer', array($this, 'load_deferred_styles'));
        add_action('init', array($this, 'generate_critical_css'));
    }
    
    public function inject_critical_css() {
        // Get page-specific critical CSS
        $critical_css = $this->get_page_critical_css();
        
        if (!empty($critical_css)) {
            echo '<style id="mmso-critical-css">' . $critical_css . '</style>' . "\n";
        }
    }
    
    private function get_page_critical_css() {
        $css = '
        /* Universal Critical CSS */
        *{box-sizing:border-box}
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,sans-serif;line-height:1.6}
        img{max-width:100%;height:auto}
        .site-header{position:relative;min-height:80px}
        .main-nav{display:flex;align-items:center}
        ';
        
        // Page-specific CSS
        if (is_front_page()) {
            $css .= '
            .hero-section{min-height:400px;position:relative}
            .featured-products{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px}
            ';
        } elseif (is_product()) {
            $css .= '
            .single-product{display:grid;grid-template-columns:1fr 1fr;gap:30px}
            .product-images{position:relative}
            .summary{padding:20px}
            @media(max-width:768px){.single-product{grid-template-columns:1fr}}
            ';
        } elseif (is_shop() || is_product_category()) {
            $css .= '
            .products{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px}
            .product{position:relative;min-height:300px}
            ';
        }
        
        return $css;
    }
    
    public function optimize_css_loading($tag, $handle, $href, $media) {
        if (is_admin()) {
            return $tag;
        }
        
        // Critical styles load normally
        $critical_styles = array('woodmart-base', 'woocommerce-layout');
        if (in_array($handle, $critical_styles)) {
            return $tag;
        }
        
        // Non-critical styles load asynchronously
        $this->deferred_styles[$handle] = $href;
        
        return '<link rel="preload" href="' . $href . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' .
               '<noscript>' . $tag . '</noscript>';
    }
    
    public function load_deferred_styles() {
        if (empty($this->deferred_styles)) {
            return;
        }
        ?>
        <script>
        // Fallback for browsers that don't support preload
        (function() {
            const stylesheets = <?php echo json_encode(array_values($this->deferred_styles)); ?>;
            
            stylesheets.forEach(function(href) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                document.head.appendChild(link);
            });
        })();
        </script>
        <?php
    }
    
    public function generate_critical_css() {
        // This would normally use a tool like Critical CSS generator
        // For now, we'll use predefined critical CSS
        $this->critical_css = $this->get_page_critical_css();
    }
}

/**
 * Database Optimization Module
 */
class MMSO_Database_Optimizer {
    
    public function __construct() {
        add_action('init', array($this, 'optimize_queries'));
        add_filter('pre_get_posts', array($this, 'optimize_wp_queries'));
        add_action('mmso_daily_cleanup', array($this, 'daily_cleanup'));
        
        // Schedule daily cleanup
        if (!wp_next_scheduled('mmso_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'mmso_daily_cleanup');
        }
    }
    
    public function optimize_queries() {
        // Disable auto-save
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Limit post revisions
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', 3);
        }
        
        // Disable trackbacks
        add_filter('xmlrpc_methods', function($methods) {
            unset($methods['pingback.ping']);
            return $methods;
        });
    }
    
    public function optimize_wp_queries($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Optimize archive queries
        if ($query->is_archive() || $query->is_search()) {
            $query->set('no_found_rows', true);
            $query->set('update_post_meta_cache', false);
            $query->set('update_post_term_cache', false);
        }
        
        // Optimize product queries
        if (function_exists('is_shop') && (is_shop() || is_product_category())) {
            $query->set('posts_per_page', 12);
            $query->set('fields', 'ids'); // Get only IDs first
        }
    }
    
    public function daily_cleanup() {
        global $wpdb;
        
        // Clean expired transients
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_timeout_%' 
            AND option_value < " . time()
        );
        
        // Clean orphaned post meta
        $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.ID IS NULL
        ");
        
        // Optimize tables
        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
        
        // Log cleanup
        update_option('mmso_last_cleanup', current_time('mysql'));
    }
}

/**
 * Advanced Caching Module
 */
class MMSO_Advanced_Cache {
    
    private $cache_dir;
    private $cache_enabled = true;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->cache_dir = $upload_dir['basedir'] . '/mmso-cache/';
        
        // Create cache directory
        if (!file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }
        
        add_action('init', array($this, 'setup_caching'));
        add_action('save_post', array($this, 'clear_post_cache'));
        add_action('wp_ajax_mmso_clear_cache', array($this, 'ajax_clear_cache'));
    }
    
    public function setup_caching() {
        if (!$this->cache_enabled || is_admin() || is_user_logged_in()) {
            return;
        }
        
        // Try to serve from cache
        add_action('template_redirect', array($this, 'serve_cache'), 1);
        
        // Cache output
        add_action('shutdown', array($this, 'cache_output'));
    }
    
    public function serve_cache() {
        $cache_file = $this->get_cache_file();
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file) < 3600)) {
            // Serve cached content
            header('X-MMSO-Cache: HIT');
            readfile($cache_file);
            exit;
        }
        
        // Start output buffering for caching
        ob_start();
    }
    
    public function cache_output() {
        if (!is_404() && !is_search() && ob_get_length()) {
            $content = ob_get_contents();
            
            // Only cache if no errors
            if (!strpos($content, 'Fatal error') && !strpos($content, 'Warning:')) {
                $cache_file = $this->get_cache_file();
                file_put_contents($cache_file, $content);
            }
        }
    }
    
    private function get_cache_file() {
        $url = $_SERVER['REQUEST_URI'];
        $hash = md5($url);
        return $this->cache_dir . $hash . '.html';
    }
    
    public function clear_post_cache($post_id) {
        // Clear related caches when post is updated
        $this->clear_cache_files();
    }
    
    private function clear_cache_files() {
        $files = glob($this->cache_dir . '*.html');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    public function ajax_clear_cache() {
        check_ajax_referer('mmso_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $this->clear_cache_files();
        wp_send_json_success('Cache cleared successfully');
    }
}

/**
 * Performance Monitoring Module
 */
class MMSO_Performance_Monitor {
    
    private $metrics = array();
    
    public function __construct() {
        add_action('wp_footer', array($this, 'inject_monitoring_script'), 999);
        add_action('wp_ajax_mmso_track_performance', array($this, 'ajax_track_performance'));
        add_action('wp_ajax_nopriv_mmso_track_performance', array($this, 'ajax_track_performance'));
        add_action('admin_menu', array($this, 'add_monitoring_page'));
    }
    
    public function inject_monitoring_script() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <script>
        (function() {
            // Collect performance metrics
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const metrics = {
                        loadTime: performance.timing.loadEventEnd - performance.timing.navigationStart,
                        domReady: performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart,
                        firstPaint: 0,
                        firstContentfulPaint: 0,
                        largestContentfulPaint: 0
                    };
                    
                    // Get paint metrics
                    if ('PerformanceObserver' in window) {
                        try {
                            const paintEntries = performance.getEntriesByType('paint');
                            paintEntries.forEach(function(entry) {
                                if (entry.name === 'first-paint') {
                                    metrics.firstPaint = entry.startTime;
                                } else if (entry.name === 'first-contentful-paint') {
                                    metrics.firstContentfulPaint = entry.startTime;
                                }
                            });
                        } catch (e) {}
                    }
                    
                    // Send metrics
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.send('action=mmso_track_performance&metrics=' + JSON.stringify(metrics) + '&url=' + encodeURIComponent(window.location.href));
                }, 1000);
            });
        })();
        </script>
        <?php
    }
    
    public function ajax_track_performance() {
        if (!isset($_POST['metrics'])) {
            wp_die();
        }
        
        $metrics = json_decode(stripslashes($_POST['metrics']), true);
        $url = esc_url_raw($_POST['url']);
        
        // Store metrics
        $stored_metrics = get_option('mmso_performance_metrics', array());
        
        // Keep last 100 entries
        if (count($stored_metrics) > 100) {
            array_shift($stored_metrics);
        }
        
        $stored_metrics[] = array(
            'timestamp' => current_time('mysql'),
            'url' => $url,
            'metrics' => $metrics
        );
        
        update_option('mmso_performance_metrics', $stored_metrics);
        
        wp_send_json_success();
    }
    
    public function add_monitoring_page() {
        add_submenu_page(
            'mmso-dashboard',
            'Performance Monitor',
            'Performance Monitor',
            'manage_options',
            'mmso-monitor',
            array($this, 'render_monitoring_page')
        );
    }
    
    public function render_monitoring_page() {
        $metrics = get_option('mmso_performance_metrics', array());
        $recent_metrics = array_slice($metrics, -20);
        ?>
        <div class="wrap">
            <h1>Performance Monitor</h1>
            
            <div class="card">
                <h2>Recent Performance Metrics</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>URL</th>
                            <th>Load Time</th>
                            <th>DOM Ready</th>
                            <th>FCP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($recent_metrics) as $entry): ?>
                        <tr>
                            <td><?php echo esc_html($entry['timestamp']); ?></td>
                            <td><?php echo esc_html($entry['url']); ?></td>
                            <td><?php echo number_format($entry['metrics']['loadTime'] / 1000, 2); ?>s</td>
                            <td><?php echo number_format($entry['metrics']['domReady'] / 1000, 2); ?>s</td>
                            <td><?php echo number_format($entry['metrics']['firstContentfulPaint'] / 1000, 2); ?>s</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Average Metrics</h2>
                <?php
                if (!empty($metrics)) {
                    $avg_load = array_sum(array_column(array_column($metrics, 'metrics'), 'loadTime')) / count($metrics);
                    $avg_dom = array_sum(array_column(array_column($metrics, 'metrics'), 'domReady')) / count($metrics);
                    ?>
                    <p><strong>Average Load Time:</strong> <?php echo number_format($avg_load / 1000, 2); ?>s</p>
                    <p><strong>Average DOM Ready:</strong> <?php echo number_format($avg_dom / 1000, 2); ?>s</p>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }
}

/**
 * Initialize all advanced modules
 */
function mmso_init_advanced_modules() {
    // Check if basic plugin is active
    if (!class_exists('MunchMakersSpeedOptimizerSafe')) {
        return;
    }
    
    // Initialize modules
    new MMSO_Image_Optimizer();
    new MMSO_JavaScript_Optimizer();
    new MMSO_CSS_Optimizer();
    new MMSO_Database_Optimizer();
    new MMSO_Advanced_Cache();
    new MMSO_Performance_Monitor();
    
    // Add admin notice
    add_action('admin_notices', function() {
        if (get_current_screen()->id === 'toplevel_page_mmso-dashboard') {
            ?>
            <div class="notice notice-success">
                <p><strong>Advanced Modules Active:</strong> Image Optimizer, JavaScript Optimizer, CSS Optimizer, Database Optimizer, Advanced Cache, Performance Monitor</p>
            </div>
            <?php
        }
    });
}

// Initialize on plugins_loaded
add_action('plugins_loaded', 'mmso_init_advanced_modules', 25);

// Add AJAX nonce
add_action('wp_head', function() {
    ?>
    <script>
    window.mmso_ajax = {
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('mmso_nonce'); ?>'
    };
    </script>
    <?php
});