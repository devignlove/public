<?php
declare(strict_types=1);

/**
 * Theme: Ozi Showcase — functions.php
 *
 * Simplified entry point that loads theme classes and utilities.
 * All functionality is organized into modular, class-based components:
 *   - OZI_Theme_Setup: Theme configuration and CPT registration
 *   - OZI_Meta_Boxes: Metabox management and rendering
 *   - OZI_Blocks: Block registration and rendering
 *   - OZI_Utils: Utility functions and helpers
 *
 * This refactoring follows WordPress coding standards with strict typing,
 * security-first approach (nonces, escaping, sanitization), and modern PHP practices.
 *
 * @version 2.0.0 (Refactored)
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Define theme constants
 */
if ( ! defined( 'OZI_THEME_DIR' ) ) {
    define( 'OZI_THEME_DIR', get_stylesheet_directory() );
}
if ( ! defined( 'OZI_THEME_URI' ) ) {
    define( 'OZI_THEME_URI', get_stylesheet_directory_uri() );
}

/**
 * Load theme classes (class-based architecture)
 */
require_once OZI_THEME_DIR . '/includes/classes/class-utils.php';
require_once OZI_THEME_DIR . '/includes/classes/class-theme-setup.php';
require_once OZI_THEME_DIR . '/includes/classes/class-meta-boxes.php';
require_once OZI_THEME_DIR . '/includes/classes/class-blocks.php';

/**
 * Add showcase shortcode fallback for classic editor.
 * If needed in WordPress posts with classic editor, use: [ozi_showcase]
 */
add_shortcode( 'ozi_showcase', function() {
    // Render showcase block with no attributes
    $callback = new OZI_Blocks();
    return $callback->render_showcase_block();
} );

add_shortcode( 'ozi_home', function() {
    $callback = new OZI_Blocks();
    return $callback->render_homepage();
} );
