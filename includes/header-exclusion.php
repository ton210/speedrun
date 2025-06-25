<?php
/**
 * Header Exclusion Module for MunchMakers Speed Optimizer
 * File: includes/header-exclusion.php
 * 
 * Completely excludes header from all optimizations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MMSO_Header_Exclusion {
    
    public function __construct() {
        // Remove ALL header-related CSS from the plugin
        add_action('wp_head', array($this, 'remove_header_styles'), 0);
        add_action('wp_enqueue_scripts', array($this, 'protect_header_scripts'), 1);
        
        // Prevent any modifications to header elements
        add_filter('mmso_should_optimize_element', array($this, 'exclude_header_elements'), 10, 2);
        
        // Remove header fixes from all modules
        add_action('init', array($this, 'disable_header_modifications'), 1);
    }
    
    /**
     * Remove all header-related styles from the plugin
     */
    public function remove_header_styles() {
        ?>
        <style id="mmso-header-protection">
        /* Reset any header modifications from MMSO plugin */
        .whb-header,
        .whb-row,
        .whb-top-bar,
        .whb-general-header,
        .whb-header-bottom,
        #menu-secondary-menu,
        .wd-nav-secondary,
        .wd-header-nav,
        .woodmart-navigation {
            /* Remove all forced styles */
            all: revert !important;
        }
        
        /* Allow theme styles to work normally */
        .whb-top-bar * {
            all: unset;
            all: revert;
        }
        </style>
        <?php
    }
    
    /**
     * Protect header scripts from optimization
     */
    public function protect_header_scripts() {
        // List of Woodmart header-related scripts to protect
        $protected_scripts = array(
            'woodmart-theme',
            'woodmart-header-builder',
            'woodmart-navigation',
            'wd-header-base',
            'wd-nav-menu'
        );
        
        foreach ($protected_scripts as $handle) {
            // Remove from dequeue list if it was queued for removal
            add_filter('mmso_scripts_to_defer', function($scripts) use ($handle) {
                return array_diff($scripts, array($handle));
            });
            
            add_filter('mmso_scripts_to_delay', function($scripts) use ($handle) {
                return array_diff($scripts, array($handle));
            });
        }
    }
    
    /**
     * Exclude header elements from any optimization
     */
    public function exclude_header_elements($should_optimize, $element) {
        // List of header-related selectors to exclude
        $header_selectors = array(
            '.whb-header',
            '.whb-row',
            '.whb-top-bar',
            '.whb-general-header',
            '.whb-header-bottom',
            '#menu-secondary-menu',
            '.wd-nav-secondary',
            '.wd-header-nav',
            '.woodmart-navigation',
            'header',
            '.site-header',
            '[class*="header"]',
            '[id*="header"]'
        );
        
        foreach ($header_selectors as $selector) {
            if (strpos($element, $selector) !== false) {
                return false; // Don't optimize
            }
        }
        
        return $should_optimize;
    }
    
    /**
     * Disable all header modifications from other modules
     */
    public function disable_header_modifications() {
        // Remove header fixes from main plugin
        remove_action('wp_head', 'add_critical_optimizations', 1);
        
        // Remove header fixes from specific fixes module
        if (class_exists('MMSO_Specific_Fixes')) {
            $specific_fixes = new MMSO_Specific_Fixes();
            remove_action('wp_head', array($specific_fixes, 'fix_header_styles'), 1);
            remove_action('wp_head', array($specific_fixes, 'fix_header_critical'), 1);
        }
        
        // Remove header fixes from enhanced fixes
        if (class_exists('MMSO_Enhanced_Fixes')) {
            $enhanced_fixes = new MMSO_Enhanced_Fixes();
            remove_action('wp_head', array($enhanced_fixes, 'fix_header_critical'), 1);
        }
    }
}

// Initialize header exclusion
new MMSO_Header_Exclusion();

// Add filter to prevent header optimization in main plugin
add_filter('mmso_optimize_element', function($element, $type) {
    // Skip any header-related elements
    if ($type === 'header' || strpos($element, 'header') !== false || 
        strpos($element, 'whb-') !== false || strpos($element, 'menu-secondary') !== false) {
        return false;
    }
    return $element;
}, 10, 2);