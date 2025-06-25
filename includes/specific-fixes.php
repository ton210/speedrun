<?php
/**
 * MunchMakers Specific Performance Fixes
 * File: includes/specific-fixes.php
 * 
 * This file contains fixes for the specific performance issues identified in your report:
 * - scrollBar.js consuming 3.3s CPU time
 * - Elementor image carousel blocking for 1.2s
 * - Swiper.js causing 500ms+ blocking
 * - Layout shifts from carousel images (CLS 0.96)
 * - Heavy payment scripts loading unnecessarily
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle specific performance fixes for MunchMakers
 */
class MMSO_Specific_Fixes {
    
    private $delayed_scripts = array();
    private $problem_scripts = array(
        'scrollBar.js',
        'image-carousel',
        'swiper.js'
    );
    
    public function __construct() {
        // Priority 1: Fix critical render-blocking issues
        add_action('init', array($this, 'disable_problematic_features'), 1);
        add_action('wp_enqueue_scripts', array($this, 'fix_script_issues'), 5);
        
        // Priority 2: Fix layout shifts
        add_action('wp_head', array($this, 'inject_cls_fixes'), 2);
        add_filter('the_content', array($this, 'fix_content_images'), 99);
        add_filter('post_thumbnail_html', array($this, 'fix_thumbnail_dimensions'), 10, 5);
        
        // Priority 3: Optimize third-party scripts
        add_action('wp_enqueue_scripts', array($this, 'optimize_third_party'), 999);
        add_action('wp_footer', array($this, 'lazy_load_scripts'), 998);
        
        // Priority 4: Elementor-specific fixes
        add_action('elementor/frontend/before_enqueue_scripts', array($this, 'fix_elementor_scripts'), 1);
        add_filter('elementor/frontend/the_content', array($this, 'optimize_elementor_content'));
        
        // Priority 5: WooCommerce optimizations
        add_action('wp', array($this, 'conditional_woo_features'));
        add_filter('woocommerce_enqueue_styles', array($this, 'conditional_woo_styles'));
        
        // AJAX handlers
        add_action('wp_ajax_mmso_get_optimized_carousel', array($this, 'ajax_get_optimized_carousel'));
        add_action('wp_ajax_nopriv_mmso_get_optimized_carousel', array($this, 'ajax_get_optimized_carousel'));
    }
    
    /**
     * Disable features causing major performance issues
     */
    public function disable_problematic_features() {
        // Completely remove Woodmart's scrollbar functionality
        add_filter('woodmart_enqueue_scripts', function($scripts) {
            unset($scripts['scrollbar']);
            unset($scripts['scrollBar']);
            return $scripts;
        });
        
        // Remove theme features we don't need
        add_filter('woodmart_register_scripts', function($scripts) {
            $remove = array('wd-scrollbar', 'wd-scroll-bar', 'woodmart-scrollbar');
            foreach ($remove as $handle) {
                if (isset($scripts[$handle])) {
                    unset($scripts[$handle]);
                }
            }
            return $scripts;
        });
    }
    
    /**
     * Fix specific script issues
     */
    public function fix_script_issues() {
        global $wp_scripts;
        
        // Remove all variations of scrollBar.js
        $scrollbar_handles = array(
            'wd-scrollbar',
            'wd-scroll-bar',
            'woodmart-scrollbar',
            'woodmart-theme-scrollbar'
        );
        
        foreach ($scrollbar_handles as $handle) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
        
        // Replace with optimized version if scrollbar is needed
        if ($this->is_scrollbar_needed()) {
            wp_add_inline_script('jquery', $this->get_optimized_scrollbar());
        }
        
        // Fix Swiper initialization
        if (wp_script_is('swiper', 'registered')) {
            wp_deregister_script('swiper');
            wp_register_script(
                'swiper',
                'https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js',
                array(),
                '8.4.5',
                true
            );
            
            // Add optimized initialization
            wp_add_inline_script('swiper', $this->get_optimized_swiper_init(), 'after');
        }
    }
    
    /**
     * Check if scrollbar functionality is actually needed
     */
    private function is_scrollbar_needed() {
        // Only on pages that actually use custom scrollbars
        return is_shop() || is_product_category() || is_product();
    }
    
    /**
     * Get optimized scrollbar replacement
     */
    private function get_optimized_scrollbar() {
        return "
        // Lightweight scrollbar replacement
        (function($) {
            'use strict';
            
            // Only run if needed
            if (!$('.scrollbar-inner, .wd-scroll, .woodmart-scroll').length) return;
            
            // Use CSS for styling, minimal JS
            const style = document.createElement('style');
            style.textContent = `
                .scrollbar-inner, .wd-scroll, .woodmart-scroll {
                    overflow-y: auto !important;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: thin;
                    scrollbar-color: #888 #f1f1f1;
                }
                .scrollbar-inner::-webkit-scrollbar,
                .wd-scroll::-webkit-scrollbar,
                .woodmart-scroll::-webkit-scrollbar {
                    width: 8px;
                    height: 8px;
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
            `;
            document.head.appendChild(style);
            
            // Simple height adjustment if needed
            $('.scrollbar-inner, .wd-scroll, .woodmart-scroll').each(function() {
                const maxHeight = $(this).data('max-height') || $(this).css('max-height');
                if (maxHeight && maxHeight !== 'none') {
                    $(this).css({
                        'max-height': maxHeight,
                        'overflow-y': 'auto'
                    });
                }
            });
        })(jQuery);
        ";
    }
    
    /**
     * Get optimized Swiper initialization
     */
    private function get_optimized_swiper_init() {
        return "
        // Optimized Swiper initialization
        (function() {
            'use strict';
            
            // Wait for DOM ready
            document.addEventListener('DOMContentLoaded', function() {
                // Find all Swiper containers
                const swipers = document.querySelectorAll('.swiper-container, .elementor-image-carousel');
                
                if (swipers.length === 0) return;
                
                // Use Intersection Observer for lazy initialization
                const swiperObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting && !entry.target.swiper) {
                            initSwiper(entry.target);
                            swiperObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    rootMargin: '50px'
                });
                
                // Observe all Swiper containers
                swipers.forEach(swiper => swiperObserver.observe(swiper));
                
                // Optimized Swiper initialization
                function initSwiper(container) {
                    const config = {
                        // Performance optimizations
                        preloadImages: false,
                        lazy: {
                            loadPrevNext: true,
                            loadOnTransitionStart: true
                        },
                        watchSlidesProgress: true,
                        watchSlidesVisibility: true,
                        // Reduce CPU usage
                        speed: 300,
                        cssMode: true,
                        mousewheel: {
                            forceToAxis: true
                        },
                        // Responsive
                        breakpoints: {
                            320: {
                                slidesPerView: 2,
                                spaceBetween: 10
                            },
                            768: {
                                slidesPerView: 3,
                                spaceBetween: 20
                            },
                            1024: {
                                slidesPerView: 4,
                                spaceBetween: 30
                            }
                        }
                    };
                    
                    // Initialize
                    new Swiper(container, config);
                }
            });
        })();
        ";
    }
    
    /**
     * Inject critical CSS to prevent layout shifts
     */
    public function inject_cls_fixes() {
        ?>
        <style id="mmso-cls-fixes">
        /* Critical fixes for your specific CLS issues */
        
        /* Fix carousel button shifts (0.96 CLS) */
        .elementor-widget-container {
            position: relative;
            min-height: 150px; /* Prevent collapse */
        }
        
        .elementor-swiper-button {
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 10;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .elementor-widget-container:hover .elementor-swiper-button {
            opacity: 1;
        }
        
        .elementor-swiper-button svg.e-font-icon-svg {
            width: 20px !important;
            height: 20px !important;
            display: block !important;
            pointer-events: none;
        }
        
        /* Fix image dimensions for your specific images */
        img[alt*="Biotray"],
        img[alt*="BioTray"],
        img[alt*="Bamboo"],
        img[alt*="Rt"],
        img[alt*="rt"],
        img[alt*="Bt"] {
            width: 150px !important;
            height: 150px !important;
            aspect-ratio: 1 !important;
            object-fit: cover !important;
        }
        
        /* Reserve space for carousel images */
        .elementor-image-carousel img {
            display: block;
            width: 100%;
            height: auto;
            aspect-ratio: 1;
            background: #f5f5f5;
        }
        
        /* Fix flag images */
        img[src*="flagcdn.com"] {
            width: 32px !important;
            height: 21px !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }
        
        /* Prevent late CSS loading shifts */
        link[href*="vss-global.css"] {
            display: none !important;
        }
        
        /* Product grid stability */
        ul.products {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            min-height: 600px;
        }
        
        .product-wrapper {
            aspect-ratio: 3/4;
            position: relative;
            overflow: hidden;
        }
        
        /* Font loading optimization */
        @font-face {
            font-family: 'Eina01';
            src: url('/wp-content/uploads/2025/01/Eina01-Bold.ttf') format('truetype');
            font-weight: 700;
            font-display: swap;
            font-style: normal;
        }
        
        @font-face {
            font-family: 'Eina01';
            src: url('/wp-content/uploads/2025/01/Eina01-SemiBold.ttf') format('truetype');
            font-weight: 600;
            font-display: swap;
            font-style: normal;
        }
        
        /* Skeleton screens for loading state */
        .mmso-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: mmso-loading 1.5s infinite;
        }
        
        @keyframes mmso-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Mobile-specific fixes */
        @media (max-width: 768px) {
            .elementor-image-carousel img {
                width: 100px !important;
                height: 100px !important;
            }
            
            ul.products {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
        </style>
        
        <script>
        // Fix layout shifts immediately on page load
        (function() {
            'use strict';
            
            // Fix images before they load
            function fixImageDimensions() {
                // Carousel images
                document.querySelectorAll('.elementor-image-carousel img').forEach(function(img) {
                    if (!img.width && !img.height) {
                        // Check filename for dimensions
                        if (img.src.includes('150x150')) {
                            img.width = 150;
                            img.height = 150;
                        } else if (img.src.includes('300x300')) {
                            img.width = 300;
                            img.height = 300;
                        } else {
                            // Default aspect ratio
                            img.style.aspectRatio = '1';
                        }
                    }
                });
                
                // Product images
                document.querySelectorAll('.products img').forEach(function(img) {
                    if (!img.getAttribute('width')) {
                        img.setAttribute('loading', 'lazy');
                        img.style.aspectRatio = '1';
                    }
                });
            }
            
            // Run immediately
            fixImageDimensions();
            
            // Run again when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fixImageDimensions);
            }
            
            // Prevent FOUT (Flash of Unstyled Text)
            document.documentElement.style.opacity = '0';
            document.addEventListener('DOMContentLoaded', function() {
                document.documentElement.style.transition = 'opacity 0.3s';
                document.documentElement.style.opacity = '1';
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Fix content images to prevent shifts
     */
    public function fix_content_images($content) {
        // Add dimensions to images without them
        $content = preg_replace_callback('/<img([^>]+)>/i', function($matches) {
            $img_tag = $matches[0];
            
            // Skip if already has width and height
            if (strpos($img_tag, 'width=') !== false && strpos($img_tag, 'height=') !== false) {
                return $img_tag;
            }
            
            // Extract src
            preg_match('/src=["\']([^"\']+)["\']/', $img_tag, $src_match);
            if (!empty($src_match[1])) {
                $src = $src_match[1];
                
                // Determine dimensions from filename
                if (strpos($src, '150x150') !== false) {
                    $img_tag = str_replace('<img', '<img width="150" height="150"', $img_tag);
                } elseif (strpos($src, '300x300') !== false) {
                    $img_tag = str_replace('<img', '<img width="300" height="300"', $img_tag);
                } elseif (strpos($src, '768x768') !== false) {
                    $img_tag = str_replace('<img', '<img width="768" height="768"', $img_tag);
                }
                
                // Add loading lazy if not present
                if (strpos($img_tag, 'loading=') === false) {
                    $img_tag = str_replace('<img', '<img loading="lazy"', $img_tag);
                }
                
                // Add aspect ratio
                if (strpos($img_tag, 'style=') === false) {
                    $img_tag = str_replace('<img', '<img style="aspect-ratio:1"', $img_tag);
                }
            }
            
            return $img_tag;
        }, $content);
        
        return $content;
    }
    
    /**
     * Fix thumbnail dimensions
     */
    public function fix_thumbnail_dimensions($html, $post_id, $post_thumbnail_id, $size, $attr) {
        // Ensure dimensions are set
        if (!isset($attr['width']) || !isset($attr['height'])) {
            $image_meta = wp_get_attachment_metadata($post_thumbnail_id);
            if ($image_meta) {
                $attr['width'] = $image_meta['width'];
                $attr['height'] = $image_meta['height'];
                
                // Rebuild the img tag
                $html = wp_get_attachment_image($post_thumbnail_id, $size, false, $attr);
            }
        }
        
        return $html;
    }
    
    /**
     * Optimize third-party scripts
     */
    public function optimize_third_party() {
        global $wp_scripts;
        
        if (!is_admin()) {
            // Scripts to delay until user interaction
            $delay_scripts = array(
                'klaviyo' => 'klaviyo.com',
                'facebook' => 'connect.facebook.net',
                'google-analytics' => 'google-analytics.com',
                'gtag' => 'googletagmanager.com',
                'hotjar' => 'hotjar.com',
                'tiktok' => 'tiktok.com',
                'pinterest' => 'pinterest.com'
            );
            
            foreach ($wp_scripts->registered as $handle => $script) {
                foreach ($delay_scripts as $key => $domain) {
                    if (strpos($script->src, $domain) !== false) {
                        wp_dequeue_script($handle);
                        $this->delayed_scripts[$handle] = $script->src;
                    }
                }
            }
            
            // Payment scripts - only on checkout/cart
            if (!is_checkout() && !is_cart() && !is_product()) {
                $payment_scripts = array('stripe', 'paypal', 'square', 'google-pay');
                foreach ($payment_scripts as $script) {
                    if (wp_script_is($script, 'enqueued')) {
                        wp_dequeue_script($script);
                        if (is_product()) {
                            $this->delayed_scripts[$script] = $wp_scripts->registered[$script]->src;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Lazy load delayed scripts
     */
    public function lazy_load_scripts() {
        if (empty($this->delayed_scripts)) {
            return;
        }
        ?>
        <script id="mmso-lazy-scripts">
        (function() {
            'use strict';
            
            const delayedScripts = <?php echo json_encode($this->delayed_scripts); ?>;
            let scriptsLoaded = false;
            let interactionTimer = null;
            
            function loadDelayedScripts() {
                if (scriptsLoaded) return;
                scriptsLoaded = true;
                
                console.log('[MMSO] Loading delayed scripts...');
                
                // Clear any pending timers
                if (interactionTimer) {
                    clearTimeout(interactionTimer);
                }
                
                // Load scripts with priority
                const priority = {
                    'stripe': 1,
                    'paypal': 2,
                    'klaviyo': 3,
                    'facebook': 4,
                    'google': 5
                };
                
                // Sort by priority
                const sortedScripts = Object.entries(delayedScripts).sort((a, b) => {
                    const aPriority = Object.keys(priority).find(key => a[0].includes(key)) || 999;
                    const bPriority = Object.keys(priority).find(key => b[0].includes(key)) || 999;
                    return priority[aPriority] - priority[bPriority];
                });
                
                // Load scripts with delay between each
                sortedScripts.forEach(([handle, src], index) => {
                    setTimeout(() => {
                        const script = document.createElement('script');
                        script.src = src;
                        script.async = true;
                        script.dataset.handle = handle;
                        document.body.appendChild(script);
                        
                        console.log('[MMSO] Loaded:', handle);
                    }, index * 200); // 200ms delay between scripts
                });
            }
            
            // Strategy 1: Load on user interaction
            const interactions = ['mousedown', 'touchstart', 'keydown'];
            
            interactions.forEach(event => {
                document.addEventListener(event, function() {
                    // Small delay to ensure smooth interaction
                    interactionTimer = setTimeout(loadDelayedScripts, 100);
                }, { once: true, passive: true });
            });
            
            // Strategy 2: Load when idle
            if ('requestIdleCallback' in window) {
                requestIdleCallback(function() {
                    interactionTimer = setTimeout(loadDelayedScripts, 1000);
                }, { timeout: 3000 });
            }
            
            // Strategy 3: Fallback timer
            setTimeout(loadDelayedScripts, 5000);
            
            // Special handling for product pages
            <?php if (is_product()): ?>
            document.addEventListener('DOMContentLoaded', function() {
                // Load payment scripts when user shows purchase intent
                const purchaseElements = document.querySelectorAll(
                    '.single_add_to_cart_button, .quantity input, .variations select'
                );
                
                purchaseElements.forEach(element => {
                    element.addEventListener('click', loadDelayedScripts, { once: true });
                    element.addEventListener('focus', loadDelayedScripts, { once: true });
                });
            });
            <?php endif; ?>
        })();
        </script>
        <?php
    }
    
    /**
     * Fix Elementor-specific issues
     */
    public function fix_elementor_scripts() {
        // Remove heavy Elementor features
        add_action('wp_enqueue_scripts', function() {
            // Remove motion effects on mobile
            if (wp_is_mobile()) {
                wp_dequeue_script('elementor-pro-motion-fx');
                wp_dequeue_style('elementor-pro-motion-fx');
            }
            
            // Remove unnecessary widgets
            if (!is_singular()) {
                wp_dequeue_script('elementor-pro-form');
                wp_dequeue_script('elementor-pro-popup');
            }
        }, 20);
    }
    
    /**
     * Optimize Elementor content
     */
    public function optimize_elementor_content($content) {
        // Add lazy loading to background images
        $content = preg_replace_callback(
            '/class="elementor-section[^"]*"[^>]*style="[^"]*background-image:\s*url\(([^)]+)\)/',
            function($matches) {
                $original = $matches[0];
                $image_url = $matches[1];
                
                // Add data attribute for lazy loading
                return $original . '" data-bg="' . $image_url;
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Conditional WooCommerce features
     */
    public function conditional_woo_features() {
        // Disable features on non-WooCommerce pages
        if (!is_woocommerce() && !is_cart() && !is_checkout()) {
            // Remove WooCommerce widgets
            add_filter('sidebars_widgets', function($sidebars_widgets) {
                foreach ($sidebars_widgets as $sidebar => $widgets) {
                    if (is_array($widgets)) {
                        $sidebars_widgets[$sidebar] = array_filter($widgets, function($widget) {
                            return strpos($widget, 'woocommerce') === false;
                        });
                    }
                }
                return $sidebars_widgets;
            });
            
            // Remove WooCommerce actions
            remove_action('wp_footer', 'woocommerce_demo_store');
            remove_action('wp_head', 'wc_generator_tag');
        }
    }
    
    /**
     * Conditional WooCommerce styles
     */
    public function conditional_woo_styles($styles) {
        // Only load on WooCommerce pages
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            return array();
        }
        
        return $styles;
    }
    
    /**
     * AJAX handler for optimized carousel
     */
    public function ajax_get_optimized_carousel() {
        // Return optimized carousel HTML
        wp_send_json_success(array(
            'html' => '<div class="optimized-carousel">Optimized carousel content</div>'
        ));
    }
}

// Initialize specific fixes
new MMSO_Specific_Fixes();

/**
 * Additional helper functions
 */

// Check if we're on a page that needs heavy scripts
function mmso_needs_heavy_scripts() {
    return is_checkout() || is_cart() || (is_product() && !wp_is_mobile());
}

// Get optimized image URL
function mmso_get_optimized_image_url($url, $size = 'full') {
    // Add WebP support
    $webp_url = str_replace(array('.jpg', '.jpeg', '.png'), '.webp', $url);
    
    // Check if WebP exists
    $upload_dir = wp_upload_dir();
    $webp_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $webp_url);
    
    if (file_exists($webp_path)) {
        return $webp_url;
    }
    
    return $url;
}

// Add performance marks for debugging
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_footer', function() {
        ?>
        <script>
        // Performance debugging
        if (window.performance && performance.mark) {
            performance.mark('mmso_specific_fixes_loaded');
            
            // Log problematic scripts
            const problemScripts = [
                'scrollBar.js',
                'image-carousel',
                'swiper.js'
            ];
            
            performance.getEntriesByType('resource').forEach(entry => {
                problemScripts.forEach(script => {
                    if (entry.name.includes(script)) {
                        console.warn('[MMSO] Problem script detected:', script, 'Duration:', entry.duration + 'ms');
                    }
                });
            });
        }
        </script>
        <?php
    }, 999);
}