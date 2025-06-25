<?php
/**
 * Plugin Name: MunchMakers Speed Optimizer
 * Plugin URI: https://www.munchmakers.com
 * Description: Complete performance optimization plugin for MunchMakers with advanced features
 * Version: 3.0.1
 * Author: MunchMakers Dev Team
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('MMSO_PLUGIN_DIR')) {
    define('MMSO_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('MMSO_PLUGIN_URL')) {
    define('MMSO_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('MMSO_VERSION')) {
    define('MMSO_VERSION', '3.0.1');
}

/**
 * Main plugin class
 */
class MunchMakersSpeedOptimizer {
    
    private static $instance = null;
    private $modules_loaded = false;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Core hooks
        add_action('init', array($this, 'init'), 10);
        add_action('wp', array($this, 'frontend_optimizations'), 10);
        
        // Load modules
        add_action('plugins_loaded', array($this, 'load_modules'), 20);
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('admin_notices', array($this, 'admin_notices'));
        }
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // AJAX handlers
        add_action('wp_ajax_mmso_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_mmso_get_status', array($this, 'ajax_get_status'));
    }
    
    public function init() {
        // Remove WordPress bloat
        $this->remove_wp_bloat();
        
        // Setup optimizations only if not admin
        if (!is_admin() && !wp_doing_ajax()) {
            add_action('wp_enqueue_scripts', array($this, 'optimize_assets'), 999);
            add_action('wp_head', array($this, 'add_critical_optimizations'), 1);
            add_action('wp_footer', array($this, 'add_footer_optimizations'), 999);
            add_filter('script_loader_tag', array($this, 'modify_script_loading'), 10, 3);
            add_filter('style_loader_tag', array($this, 'modify_style_loading'), 10, 4);
        }
        
        // Initialize features based on settings
        $this->init_features();
    }
    
    public function load_modules() {
        // Prevent double loading
        if ($this->modules_loaded) {
            return;
        }
        
        $this->modules_loaded = true;
        
        // Load modules in order of priority - check if files exist first
        $modules = array(
            'includes/specific-fixes.php',
            'includes/header-exclusion.php',    // Add header exclusion module
            'includes/testing-interface.php',
            'includes/advanced-modules.php',
            'includes/advanced-admin.php'
        );
        
        foreach ($modules as $module) {
            $module_path = MMSO_PLUGIN_DIR . $module;
            if (file_exists($module_path)) {
                require_once $module_path;
            }
        }
        
        // Fire action for extending
        do_action('mmso_modules_loaded');
    }
    
    private function init_features() {
        // Initialize features based on what modules are loaded
        if (class_exists('MMSO_Specific_Fixes')) {
            // Specific fixes are loaded automatically
        }
        
        if (function_exists('mmso_init_advanced_modules')) {
            // Advanced modules initialization
            add_action('init', 'mmso_init_advanced_modules', 20);
        }
    }
    
    public function frontend_optimizations() {
        // Only run on frontend
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        
        // Check if optimizations are temporarily disabled for testing
        if (isset($_GET['mmso_disable']) && current_user_can('manage_options')) {
            return;
        }
        
        // Page-specific optimizations
        if (function_exists('is_product_category') && (is_product_category() || is_shop())) {
            $this->optimize_category_pages();
        }
        
        if (function_exists('is_product') && is_product()) {
            $this->optimize_product_pages();
        }
        
        if (function_exists('is_cart') && (is_cart() || is_checkout())) {
            $this->optimize_checkout_pages();
        }
        
        if (is_front_page()) {
            $this->optimize_homepage();
        }
    }
    
    private function remove_wp_bloat() {
        // Remove emojis
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', array($this, 'disable_emojis_tinymce'));
        add_filter('emoji_svg_url', '__return_false');
        
        // Remove other unnecessary features
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_action('template_redirect', 'rest_output_link_header', 11);
        
        // Remove feed links
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
        
        // Remove recent comments style
        add_action('widgets_init', function() {
            global $wp_widget_factory;
            if (isset($wp_widget_factory->widgets['WP_Widget_Recent_Comments'])) {
                remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
            }
        });
        
        // Disable XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');
        
        // Remove jQuery migrate
        add_action('wp_default_scripts', array($this, 'remove_jquery_migrate'));
        
        // Disable heartbeat except on post edit pages
        add_action('init', function() {
            global $pagenow;
            if ($pagenow !== 'post.php' && $pagenow !== 'post-new.php') {
                wp_deregister_script('heartbeat');
            }
        });
    }
    
    public function disable_emojis_tinymce($plugins) {
        if (is_array($plugins)) {
            return array_diff($plugins, array('wpemoji'));
        }
        return array();
    }
    
    public function remove_jquery_migrate($scripts) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            if ($script->deps) {
                $script->deps = array_diff($script->deps, array('jquery-migrate'));
            }
        }
    }
    
    public function optimize_assets() {
        // Skip header-related scripts completely
        if ($this->is_header_script()) {
            return;
        }
        
        // Fix the problematic scrollBar.js - multiple variations
        $scrollbar_handles = array(
            'wd-scrollbar',
            'wd-scroll-bar',
            'woodmart-scrollbar',
            'woodmart-theme-scrollbar',
            'woodmart-scroll-bar'
        );
        
        foreach ($scrollbar_handles as $handle) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
        
        // Add lightweight scrollbar CSS replacement
        wp_add_inline_style('woodmart-style', '
            .scrollbar-inner, .wd-scroll, .woodmart-scroll {
                overflow-y: auto !important;
                scrollbar-width: thin;
                scrollbar-color: #888 #f1f1f1;
            }
            .scrollbar-inner::-webkit-scrollbar,
            .wd-scroll::-webkit-scrollbar,
            .woodmart-scroll::-webkit-scrollbar {
                width: 8px;
            }
            .scrollbar-inner::-webkit-scrollbar-track,
            .wd-scroll::-webkit-scrollbar-track,
            .woodmart-scroll::-webkit-scrollbar-track {
                background: #f1f1f1;
            }
            .scrollbar-inner::-webkit-scrollbar-thumb,
            .wd-scroll::-webkit-scrollbar-thumb,
            .woodmart-scroll::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 4px;
            }
            .scrollbar-inner::-webkit-scrollbar-thumb:hover,
            .wd-scroll::-webkit-scrollbar-thumb:hover,
            .woodmart-scroll::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
        ');
        
        // Remove payment scripts from non-checkout pages
        if (!is_checkout() && !is_cart()) {
            $payment_scripts = array(
                'stripe',
                'stripe-js',
                'wc-stripe-payment-request',
                'paypal-checkout',
                'ppcp-smart-button',
                'google-pay',
                'square-payments',
                'wc-square-payment-form'
            );
            
            foreach ($payment_scripts as $handle) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
            }
        }
        
        // Remove unnecessary scripts from category pages
        if (function_exists('is_product_category') && (is_product_category() || is_shop())) {
            $remove_scripts = array(
                'flexslider',
                'zoom',
                'photoswipe',
                'photoswipe-ui-default',
                'single-product',
                'wc-single-product'
            );
            
            foreach ($remove_scripts as $handle) {
                wp_dequeue_script($handle);
            }
        }
        
        // Optimize WooCommerce scripts
        if (function_exists('is_woocommerce') && !is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            wp_dequeue_script('woocommerce');
            wp_dequeue_script('wc-cart-fragments');
            wp_dequeue_style('woocommerce-general');
            wp_dequeue_style('woocommerce-layout');
            wp_dequeue_style('woocommerce-smallscreen');
        }
        
        // Optimize third-party scripts
        $this->optimize_third_party_scripts();
    }
    
    /**
     * Check if current script is header-related
     */
    private function is_header_script() {
        $current_filter = current_filter();
        $header_scripts = array(
            'woodmart-theme',
            'woodmart-header',
            'wd-header',
            'navigation',
            'menu'
        );
        
        foreach ($header_scripts as $script) {
            if (strpos($current_filter, $script) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Exclude header elements from optimization
     */
    public function exclude_header_elements($should_optimize, $element) {
        // List of ALL navigation and header patterns to exclude
        $excluded_patterns = array(
            'whb-',              // Woodmart header builder
            'header',            // Generic header
            'menu-',             // Menu items
            'nav-',              // Navigation
            'navigation',        // Navigation
            'wd-header',         // Woodmart header
            'wd-nav',            // Woodmart nav
            'wd-dropdown',       // Woodmart dropdowns
            'mega-menu',         // Mega menu
            'menu-item',         // Menu items
            'woodmart-nav',      // Woodmart navigation
            'dropdown-menu',     // Dropdown menus
            'elementor-widget-loop-grid', // Menu content widgets
            'cms_block'          // CMS blocks used in menus
        );
        
        foreach ($excluded_patterns as $pattern) {
            if (stripos($element, $pattern) !== false) {
                return false;
            }
        }
        
        return $should_optimize;
    }
    
    private function optimize_third_party_scripts() {
        global $wp_scripts;
        
        if (!isset($wp_scripts->registered)) {
            return;
        }
        
        // Scripts to delay
        $delay_patterns = array(
            'klaviyo' => array('klaviyo.com', 'klaviyo.js'),
            'facebook' => array('facebook.com', 'fbevents.js'),
            'google-analytics' => array('google-analytics.com', 'gtag', 'analytics.js'),
            'hotjar' => array('hotjar.com', 'hotjar.js'),
            'tiktok' => array('tiktok.com', 'tiktok.js'),
            'pinterest' => array('pinterest.com', 'pinit.js')
        );
        
        foreach ($wp_scripts->registered as $handle => $script) {
            if (empty($script->src)) continue;
            
            foreach ($delay_patterns as $name => $patterns) {
                foreach ($patterns as $pattern) {
                    if (strpos($script->src, $pattern) !== false) {
                        wp_dequeue_script($handle);
                        add_action('wp_footer', function() use ($script) {
                            echo '<script data-mmso-delay="true" data-src="' . esc_url($script->src) . '"></script>';
                        }, 999);
                        break 2;
                    }
                }
            }
        }
    }
    
    public function modify_script_loading($tag, $handle, $src) {
        // Skip admin scripts
        if (is_admin()) {
            return $tag;
        }
        
        // EXCLUDE all header-related scripts
        $header_scripts = array(
            'woodmart-theme',
            'woodmart-header',
            'wd-header',
            'wd-navigation',
            'menu',
            'nav'
        );
        
        foreach ($header_scripts as $header_script) {
            if (stripos($handle, $header_script) !== false) {
                return $tag; // Return unmodified
            }
        }
        
        // Get loading method from settings or defaults
        $method = get_option("mmso_script_{$handle}_method", '');
        
        // Default methods if not set
        if (empty($method)) {
            $defer_scripts = array(
                'jquery',
                'jquery-core',
                'elementor-frontend',
                'elementor-pro-frontend',
                'woocommerce',
                'wc-add-to-cart',
                'wc-cart-fragments',
                'swiper'
            );
            
            $async_scripts = array(
                'comment-reply',
                'wp-embed'
            );
            
            if (in_array($handle, $defer_scripts)) {
                $method = 'defer';
            } elseif (in_array($handle, $async_scripts)) {
                $method = 'async';
            }
        }
        
        // Apply loading method
        switch ($method) {
            case 'defer':
                if (strpos($tag, 'defer') === false) {
                    $tag = str_replace(' src', ' defer src', $tag);
                }
                break;
            case 'async':
                if (strpos($tag, 'async') === false) {
                    $tag = str_replace(' src', ' async src', $tag);
                }
                break;
            case 'remove':
                return '';
        }
        
        return $tag;
    }
    
    public function modify_style_loading($tag, $handle, $href, $media) {
        // Skip admin styles
        if (is_admin()) {
            return $tag;
        }
        
        // Critical styles that should load normally
        $critical_styles = array(
            'woodmart-style',
            'woodmart-base',
            'woocommerce-layout',
            'elementor-frontend'
        );
        
        if (in_array($handle, $critical_styles)) {
            return $tag;
        }
        
        // Get loading method from settings
        $method = get_option("mmso_style_{$handle}_method", 'async');
        
        if ($method === 'async') {
            // Non-critical styles load asynchronously
            return '<link rel="preload" href="' . esc_url($href) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' .
                   '<noscript>' . $tag . '</noscript>';
        }
        
        return $tag;
    }
    
    public function add_critical_optimizations() {
        ?>
        <!-- MunchMakers Speed Optimizer - Critical Performance Fixes -->
        <style id="mmso-critical">
        /* Critical CSS to prevent layout shifts */
        *{box-sizing:border-box}
        html{font-size:16px;-webkit-text-size-adjust:100%}
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;line-height:1.6}
        img{max-width:100%;height:auto}
        
        /* HEADER EXCLUDED FROM OPTIMIZATION - Using theme defaults */
        
        /* Fix specific issues from your report (non-header) */
        .elementor-image-carousel-wrapper{min-height:150px;position:relative}
        .elementor-swiper-button{position:absolute!important;top:50%!important;transform:translateY(-50%)!important;z-index:1}
        .elementor-swiper-button svg{width:20px!important;height:20px!important;display:block!important}
        img[src*="150x150"]{width:150px!important;height:150px!important;object-fit:cover}
        
        /* Product grid stability */
        .products,.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;min-height:400px}
        .product{position:relative;min-height:300px}
        .product-image-wrapper{position:relative;overflow:hidden;aspect-ratio:1;background:#f5f5f5}
        
        /* Mobile optimizations */
        @media(max-width:768px){
            .products{grid-template-columns:repeat(2,1fr);gap:10px}
            .elementor-widget-image-carousel{min-height:150px}
        }
        </style>
        
        <script>
        // Critical JavaScript - Fix layout shifts early
        (function() {
            'use strict';
            
            // Fix image dimensions immediately
            document.addEventListener('DOMContentLoaded', function() {
                // Fix all images without dimensions
                document.querySelectorAll('img:not([width])').forEach(function(img) {
                    // Extract dimensions from URL if available
                    const src = img.src || img.dataset.src || '';
                    const match = src.match(/(\d+)x(\d+)/);
                    if (match) {
                        img.width = match[1];
                        img.height = match[2];
                    }
                });
            });
        })();
        </script>
        
        <!-- Resource Hints -->
        <link rel="dns-prefetch" href="//fonts.googleapis.com">
        <link rel="dns-prefetch" href="//fonts.gstatic.com">
        <link rel="dns-prefetch" href="//cdn.munchmakers.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        
        <?php
    }
    
    public function add_footer_optimizations() {
        ?>
        <script id="mmso-footer-optimizations">
        (function() {
            'use strict';
            
            // Lazy load delayed scripts
            const delayedScripts = document.querySelectorAll('[data-mmso-delay]');
            let scriptsLoaded = false;
            
            function loadDelayedScripts() {
                if (scriptsLoaded) return;
                scriptsLoaded = true;
                
                console.log('[MMSO] Loading delayed scripts...');
                
                delayedScripts.forEach(function(script, index) {
                    setTimeout(function() {
                        const newScript = document.createElement('script');
                        newScript.src = script.dataset.src;
                        newScript.async = true;
                        document.body.appendChild(newScript);
                    }, index * 100);
                });
            }
            
            // Load on user interaction
            ['click', 'touchstart', 'scroll', 'mousemove', 'keydown'].forEach(function(event) {
                document.addEventListener(event, function() {
                    setTimeout(loadDelayedScripts, 100);
                }, {once: true, passive: true});
            });
            
            // Fallback after 5 seconds
            setTimeout(loadDelayedScripts, 5000);
        })();
        </script>
        <?php
    }
    
    private function optimize_category_pages() {
        // Category page optimizations
        add_action('wp_footer', function() {
            ?>
            <script>
            jQuery(document).ready(function($) {
                // Lazy load product images not in viewport
                if ('IntersectionObserver' in window) {
                    const imageObserver = new IntersectionObserver(function(entries) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                if (img.dataset.src) {
                                    img.src = img.dataset.src;
                                    img.removeAttribute('data-src');
                                }
                                imageObserver.unobserve(img);
                            }
                        });
                    });
                    
                    document.querySelectorAll('.products img').forEach(function(img) {
                        imageObserver.observe(img);
                    });
                }
            });
            </script>
            <?php
        });
    }
    
    private function optimize_product_pages() {
        // Product page specific optimizations
        add_action('wp_footer', function() {
            ?>
            <script>
            jQuery(document).ready(function($) {
                // Defer gallery initialization
                if (typeof $.fn.wc_product_gallery !== 'undefined') {
                    const gallery = $('.woocommerce-product-gallery');
                    if (gallery.length) {
                        setTimeout(function() {
                            gallery.wc_product_gallery();
                        }, 100);
                    }
                }
            });
            </script>
            <?php
        });
    }
    
    private function optimize_checkout_pages() {
        // Ensure payment scripts are loaded on checkout
        add_action('wp_enqueue_scripts', function() {
            // Re-enable payment scripts for checkout
            wp_enqueue_script('stripe');
            wp_enqueue_script('paypal-checkout');
        }, 1000);
    }
    
    private function optimize_homepage() {
        // Homepage specific optimizations
        add_action('wp_head', function() {
            ?>
            <link rel="prefetch" href="<?php echo get_permalink(wc_get_page_id('shop')); ?>">
            <?php
        });
    }
    
    public function add_admin_menu() {
        $icon_svg = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iY3VycmVudENvbG9yIj48cGF0aCBkPSJNMTMgMi4wNVYxMmg0YzQuNDIgMCA4LTMuNTggOC04IDAtMS41Ny0uNDYtMy4wMy0xLjI0LTQuMjZMMTMgMi4wNXptMCA0LjM5bDYuODYgMi4wNmMuMDkuMzcuMTQuNzYuMTQgMS4xNiAwIDMuMzEtMi42OSA2LTYgNmgtMVY2LjQ0em0tMiAwVjEzaC0xYy0zLjMxIDAtNi0yLjY5LTYtNiAwLTIuOTcgMi4xNi01LjQzIDUtNS45MVY2LjQ0em0wLTQuMzlDNS4wNSAyLjU0IDEgNy41OCAxIDEzYzAgNS42NiA0LjM0IDEwIDEwIDEwczEwLTQuMzQgMTAtMTBjMC0xLjAzLS4xNi0yLjAzLS40NS0yLjk1TDExIDIuMDV6Ii8+PC9zdmc+';
        
        add_menu_page(
            'Speed Optimizer',
            'Speed Optimizer',
            'manage_options',
            'mmso-dashboard',
            array($this, 'admin_dashboard'),
            $icon_svg,
            100
        );
    }
    
    public function admin_dashboard() {
        // Get module status
        $modules = array(
            'specific-fixes' => class_exists('MMSO_Specific_Fixes') || class_exists('MMSO_Enhanced_Fixes'),
            'testing-interface' => function_exists('mmso_render_testing_page'),
            'advanced-modules' => function_exists('mmso_init_advanced_modules'),
            'image-optimizer' => class_exists('MMSO_Image_Optimizer'),
            'cache-module' => class_exists('MMSO_Advanced_Cache')
        );
        ?>
        <div class="wrap">
            <h1>MunchMakers Speed Optimizer</h1>
            
            <div class="mmso-dashboard-grid">
                <div class="mmso-card mmso-status-card">
                    <h2>⚡ Plugin Status</h2>
                    <div class="status-indicator <?php echo $this->get_overall_status(); ?>">
                        <span class="status-icon"></span>
                        <span class="status-text"><?php echo $this->get_status_message(); ?></span>
                    </div>
                    
                    <h3>Loaded Modules:</h3>
                    <ul class="module-list">
                        <?php foreach ($modules as $module => $loaded): ?>
                        <li class="<?php echo $loaded ? 'loaded' : 'not-loaded'; ?>">
                            <?php echo $loaded ? '✅' : '❌'; ?> <?php echo ucwords(str_replace('-', ' ', $module)); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="mmso-card">
                    <h2>🚀 Active Optimizations</h2>
                    <ul class="mmso-feature-list">
                        <li>✅ Fixed header menu visibility</li>
                        <li>✅ Removed problematic scrollBar.js script</li>
                        <li>✅ Deferred jQuery and heavy scripts</li>
                        <li>✅ Lazy loading payment scripts</li>
                        <li>✅ Fixed image layout shifts</li>
                        <li>✅ Removed WordPress bloat</li>
                        <li>✅ Resource hints and preloading</li>
                        <li>✅ Third-party script optimization</li>
                    </ul>
                </div>
                
                <div class="mmso-card">
                    <h2>⚠️ Critical Issues to Fix</h2>
                    <ul>
                        <li style="color: red;"><strong>693KB GIF on homepage</strong> - Replace with video or static image</li>
                        <li style="color: orange;"><strong>Multiple 400-600KB PNGs</strong> - Need compression</li>
                        <li style="color: orange;"><strong>3,770 DOM elements</strong> - Exceeds recommended 1,500</li>
                        <li><strong>Missing image dimensions</strong> - Causing layout shifts</li>
                    </ul>
                </div>
                
                <div class="mmso-card">
                    <h2>🔧 Quick Actions</h2>
                    <p>
                        <button class="button button-primary" id="clear-all-cache">Clear All Caches</button>
                        <button class="button" id="check-images">Check Large Images</button>
                        <a href="<?php echo admin_url('upload.php'); ?>" class="button">Media Library</a>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .mmso-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .mmso-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .mmso-card h2 {
            margin-top: 0;
            font-size: 18px;
        }
        
        .mmso-feature-list {
            list-style: none;
            padding-left: 0;
        }
        
        .mmso-feature-list li {
            padding: 5px 0;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .status-indicator.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-indicator.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-indicator.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border-radius: 50%;
        }
        
        .status-indicator.active .status-icon {
            background: #28a745;
        }
        
        .module-list {
            list-style: none;
            padding-left: 0;
        }
        
        .module-list li {
            padding: 5px 0;
        }
        
        .module-list .not-loaded {
            opacity: 0.6;
        }
        
        .button + .button {
            margin-left: 5px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Clear cache
            $('#clear-all-cache').on('click', function() {
                if (!confirm('Clear all caches?')) return;
                
                const $button = $(this);
                $button.prop('disabled', true).text('Clearing...');
                
                $.post(ajaxurl, {
                    action: 'mmso_clear_cache',
                    nonce: '<?php echo wp_create_nonce('mmso_cache'); ?>'
                }, function(response) {
                    $button.prop('disabled', false).text('Clear All Caches');
                    if (response.success) {
                        alert('All caches cleared successfully!');
                    }
                });
            });
            
            // Check images
            $('#check-images').on('click', function() {
                window.location.href = '<?php echo admin_url('upload.php?orderby=size&order=desc'); ?>';
            });
        });
        </script>
        <?php
    }
    
    private function get_overall_status() {
        // Check if core modules are loaded
        if (class_exists('MMSO_Specific_Fixes') || class_exists('MMSO_Enhanced_Fixes')) {
            return 'active';
        } elseif (file_exists(MMSO_PLUGIN_DIR . 'includes/specific-fixes.php')) {
            return 'warning';
        }
        return 'error';
    }
    
    private function get_status_message() {
        $status = $this->get_overall_status();
        switch ($status) {
            case 'active':
                return 'Optimizations active';
            case 'warning':
                return 'Some modules not loaded';
            case 'error':
                return 'Critical modules missing';
        }
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'mmso') !== false) {
            // Add inline styles if CSS file doesn't exist
            add_action('admin_head', function() {
                echo '<style>
                    .mmso-card { margin-bottom: 20px; }
                    .wrap h1 { margin-bottom: 20px; }
                </style>';
            });
        }
    }
    
    public function admin_notices() {
        // Check for the 693KB GIF
        if (get_current_screen()->base === 'toplevel_page_mmso-dashboard') {
            ?>
            <div class="notice notice-error">
                <p><strong>Critical Performance Issue:</strong> Your homepage has a 693KB GIF file (Summer-Hero_MunchMakers-2.gif) that's severely impacting load times. 
                <a href="<?php echo admin_url('upload.php?s=Summer-Hero'); ?>">Replace it now</a> with a compressed image or video.</p>
            </div>
            <?php
        }
    }
    
    public function activate() {
        // Set default options
        update_option('mmso_activated', time());
        update_option('mmso_version', MMSO_VERSION);
        
        // Create necessary directories
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/mmso-cache/';
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        // Clear caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    public function deactivate() {
        // Clean up transients
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_mmso_%'");
    }
    
    public function ajax_clear_cache() {
        check_ajax_referer('mmso_cache', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        // Clear object cache
        wp_cache_flush();
        
        // Clear any file-based cache
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/mmso-cache/';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        wp_send_json_success('All caches cleared successfully');
    }
    
    public function ajax_get_status() {
        check_ajax_referer('mmso_nonce', 'nonce');
        
        $status = array(
            'active' => true,
            'modules' => array(
                'specific-fixes' => class_exists('MMSO_Specific_Fixes') || class_exists('MMSO_Enhanced_Fixes'),
                'image-optimizer' => class_exists('MMSO_Image_Optimizer'),
                'cache' => class_exists('MMSO_Advanced_Cache')
            )
        );
        
        wp_send_json_success($status);
    }
}

// Initialize the plugin
function mmso_init() {
    MunchMakersSpeedOptimizer::get_instance();
}
add_action('plugins_loaded', 'mmso_init', 10);

// AJAX handlers
add_action('wp_ajax_mmso_clear_cache', function() {
    if (!check_ajax_referer('mmso_cache', 'nonce', false)) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Clear WordPress cache
    wp_cache_flush();
    
    // Clear any plugin-specific cache
    do_action('mmso_clear_cache');
    
    wp_send_json_success('Cache cleared successfully');
});