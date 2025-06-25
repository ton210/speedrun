<?php
/**
 * MunchMakers Simplified Specific Performance Fixes
 * File: includes/specific-fixes.php
 * 
 * Focuses on critical fixes without complex features
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MMSO_Specific_Fixes {
    
    public function __construct() {
        // Priority 1: Fix image issues
        add_filter('wp_get_attachment_image_attributes', array($this, 'fix_image_attributes'), 10, 3);
        add_filter('the_content', array($this, 'fix_content_images'), 999);
        
        // Priority 2: Remove problematic scripts
        add_action('wp_enqueue_scripts', array($this, 'remove_problem_scripts'), 999);
        
        // Priority 3: Fix layout shifts (non-header)
        add_action('wp_head', array($this, 'add_cls_fixes'), 2);
        
        // Priority 4: Optimize large images
        add_action('wp_footer', array($this, 'lazy_load_large_images'), 999);
    }
    
    /**
     * Fix header visibility
     */
    public function fix_header_styles() {
        ?>
        <style id="mmso-header-fix">
        /* Critical header fixes */
        .whb-top-bar {
            background-color: #333 !important;
            color: #fff !important;
            min-height: 45px !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 99999 !important;
        }
        
        #menu-secondary-menu {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            align-items: center !important;
            gap: 15px !important;
            margin: 0 !important;
            padding: 0 !important;
            list-style: none !important;
        }
        
        #menu-secondary-menu > li {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin: 0 !important;
        }
        
        #menu-secondary-menu a {
            color: #fff !important;
            font-size: 14px !important;
            padding: 5px 10px !important;
            display: inline-block !important;
            text-decoration: none !important;
            line-height: 45px !important;
            white-space: nowrap !important;
        }
        
        #menu-secondary-menu a:hover {
            color: #a4ca5a !important;
        }
        
        .whb-top-bar .whb-column {
            display: flex !important;
            align-items: center !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Fix phone number */
        .whb-col-right .wd-header-text a {
            color: #fff !important;
            font-size: 14px !important;
        }
        </style>
        <?php
    }
    
    /**
     * Fix image attributes
     */
    public function fix_image_attributes($attr, $attachment, $size) {
        // Add dimensions if missing
        if (empty($attr['width']) || empty($attr['height'])) {
            $image_src = wp_get_attachment_image_src($attachment->ID, $size);
            if ($image_src) {
                $attr['width'] = $image_src[1];
                $attr['height'] = $image_src[2];
            }
        }
        
        // Add lazy loading
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }
        
        // Add aspect ratio for modern browsers
        if (!empty($attr['width']) && !empty($attr['height'])) {
            $attr['style'] = (isset($attr['style']) ? $attr['style'] . '; ' : '') . 
                            'aspect-ratio: ' . $attr['width'] . '/' . $attr['height'];
        }
        
        return $attr;
    }
    
    /**
     * Fix content images
     */
    public function fix_content_images($content) {
        // Add dimensions to images without them
        $content = preg_replace_callback('/<img([^>]+)>/i', function($matches) {
            $img_tag = $matches[0];
            
            // Skip if already has dimensions
            if (strpos($img_tag, 'width=') !== false && strpos($img_tag, 'height=') !== false) {
                return $img_tag;
            }
            
            // Extract src
            preg_match('/src=["\']([^"\']+)["\']/', $img_tag, $src_match);
            if (!empty($src_match[1])) {
                // Extract dimensions from filename
                if (preg_match('/(\d+)x(\d+)/', $src_match[1], $dim_match)) {
                    $width = $dim_match[1];
                    $height = $dim_match[2];
                    $img_tag = str_replace('<img', "<img width=\"$width\" height=\"$height\"", $img_tag);
                }
                
                // Add lazy loading
                if (strpos($img_tag, 'loading=') === false) {
                    $img_tag = str_replace('<img', '<img loading="lazy"', $img_tag);
                }
            }
            
            return $img_tag;
        }, $content);
        
        return $content;
    }
    
    /**
     * Remove problematic scripts (excluding navigation)
     */
    public function remove_problem_scripts() {
        // Only remove scrollbar scripts, not navigation
        $scrollbar_handles = array(
            'wd-scrollbar',
            'wd-scroll-bar', 
            'woodmart-scrollbar',
            'woodmart-theme-scrollbar'
        );
        
        foreach ($scrollbar_handles as $handle) {
            // Check it's not a navigation script
            if (strpos($handle, 'nav') === false && strpos($handle, 'menu') === false) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
            }
        }
        
        // Add CSS replacement for scrollbar only
        wp_add_inline_style('woodmart-style', '
            .scrollbar-inner:not(.wd-dropdown-menu), 
            .wd-scroll:not(.wd-dropdown-menu), 
            .woodmart-scroll:not(.wd-dropdown-menu) {
                overflow-y: auto !important;
                scrollbar-width: thin;
                scrollbar-color: #888 #f1f1f1;
            }
            .scrollbar-inner:not(.wd-dropdown-menu)::-webkit-scrollbar,
            .wd-scroll:not(.wd-dropdown-menu)::-webkit-scrollbar,
            .woodmart-scroll:not(.wd-dropdown-menu)::-webkit-scrollbar {
                width: 8px;
            }
            .scrollbar-inner:not(.wd-dropdown-menu)::-webkit-scrollbar-thumb,
            .wd-scroll:not(.wd-dropdown-menu)::-webkit-scrollbar-thumb,
            .woodmart-scroll:not(.wd-dropdown-menu)::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 4px;
            }
        ');
    }
    
    /**
     * Add CLS fixes
     */
    public function add_cls_fixes() {
        ?>
        <style id="mmso-cls-fixes">
        /* Prevent layout shifts */
        
        /* Reserve space for images */
        img {
            height: auto;
        }
        
        img[width][height] {
            height: auto;
        }
        
        /* Fix carousel shifts */
        .elementor-image-carousel-wrapper {
            min-height: 150px;
            position: relative;
        }
        
        .elementor-swiper-button {
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .elementor-widget-container:hover .elementor-swiper-button {
            opacity: 1;
        }
        
        /* Product grid stability */
        .products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            min-height: 400px;
        }
        
        .product {
            position: relative;
            min-height: 300px;
        }
        
        /* Font loading optimization */
        .wf-loading body {
            visibility: hidden;
        }
        
        .wf-active body,
        .wf-inactive body {
            visibility: visible;
        }
        </style>
        <?php
    }
    
    /**
     * Lazy load large images
     */
    public function lazy_load_large_images() {
        ?>
        <script>
        (function() {
            'use strict';
            
            // Identify large images
            const largeImagePatterns = [
                'Summer-Hero_MunchMakers-2.gif',
                '1024x1024.png',
                'UGC_'
            ];
            
            // Replace GIF with static image until interaction
            document.querySelectorAll('img').forEach(function(img) {
                const src = img.src || img.dataset.src || '';
                
                // Check if it's the 693KB GIF
                if (src.includes('Summer-Hero_MunchMakers-2.gif')) {
                    // Store original src
                    img.dataset.gifSrc = src;
                    
                    // Replace with placeholder
                    img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 600"%3E%3Crect fill="%23f0f0f0"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999"%3ELoading...%3C/text%3E%3C/svg%3E';
                    
                    // Load on interaction
                    let loaded = false;
                    const loadGif = function() {
                        if (!loaded) {
                            loaded = true;
                            img.src = img.dataset.gifSrc;
                        }
                    };
                    
                    // Load on scroll or click
                    window.addEventListener('scroll', loadGif, { once: true });
                    document.addEventListener('click', loadGif, { once: true });
                    
                    // Fallback
                    setTimeout(loadGif, 3000);
                }
                
                // Check for large PNGs
                largeImagePatterns.forEach(function(pattern) {
                    if (src.includes(pattern) && !img.complete) {
                        img.loading = 'lazy';
                    }
                });
            });
            
            // Performance monitoring
            if (window.performance && performance.getEntriesByType) {
                window.addEventListener('load', function() {
                    const resources = performance.getEntriesByType('resource');
                    const images = resources.filter(r => r.initiatorType === 'img' && r.transferSize > 100000);
                    
                    if (images.length > 0) {
                        console.log('[MMSO] Large images detected:', images.map(img => ({
                            url: img.name.split('/').pop(),
                            size: Math.round(img.transferSize / 1024) + 'KB',
                            loadTime: Math.round(img.duration) + 'ms'
                        })));
                    }
                });
            }
        })();
        </script>
        <?php
    }
}

// Initialize
new MMSO_Specific_Fixes();