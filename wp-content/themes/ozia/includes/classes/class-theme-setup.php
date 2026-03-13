<?php
declare(strict_types=1);

/**
 * Theme_Setup class
 *
 * Handles theme configuration, CPT registration, and asset enqueueing.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// guard against double inclusion / redeclaration in case the file
// is accidentally required more than once (e.g. during unit tests).
if ( ! class_exists( 'OZI_Theme_Setup' ) ) {
    /**
     * Main theme setup class.
     *
     * @package ozi_theme
     */
    class OZI_Theme_Setup {

    const TEXT_DOMAIN = 'ozitheme';

    public function __construct() {
        add_action( 'after_setup_theme', [ $this, 'setup_theme' ] );
        add_action( 'init', [ $this, 'register_post_types' ] );
        add_action( 'init', [ $this, 'add_rewrite_rules' ] );
        add_action( 'after_switch_theme', [ $this, 'flush_rewrites' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
        add_filter( 'upload_mimes', [ $this, 'allow_extra_mimes' ] );
        add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
        add_filter( 'request', [ $this, 'map_remorque_request_to_showcase' ] );
        add_filter( 'pre_get_document_title', [ $this, 'filter_remorque_document_title_text' ] );
        add_filter( 'document_title_parts', [ $this, 'filter_remorque_document_title' ] );
        add_filter( 'the_title', [ $this, 'filter_showcase_page_title' ], 10, 2 );
        add_filter( 'post_type_link', [ $this, 'filter_remorque_permalink' ], 10, 2 );
        add_action( 'template_redirect', [ $this, 'maybe_redirect_showcase' ] );
        add_action( 'template_redirect', [ $this, 'maybe_redirect_nested_remorques_url' ] );
    }

    /**
     * Theme setup: logo, menus, title-tag support.
     */
    public function setup_theme(): void {
        add_theme_support( 'title-tag' );

        add_theme_support( 'custom-logo', [
            'height'      => 64,
            'width'       => 180,
            'flex-width'  => true,
            'flex-height' => true,
        ] );

        register_nav_menus( [
            'primary' => __( 'Menu principal', self::TEXT_DOMAIN ),
            'footer'  => __( 'Menu pied de page', self::TEXT_DOMAIN ),
        ] );
    }

    /**
     * Register CPTs: remorques and accessoires.
     */
    public function register_post_types(): void {
        // Remorques CPT
        register_post_type(
            'remorques',
            [
                'labels'       => [
                    'name'          => __( 'Remorques', self::TEXT_DOMAIN ),
                    'singular_name' => __( 'Remorque', self::TEXT_DOMAIN ),
                    'add_new_item'  => __( 'Ajouter une remorque', self::TEXT_DOMAIN ),
                    'edit_item'     => __( 'Modifier la remorque', self::TEXT_DOMAIN ),
                ],
                'public'       => true,
                'show_in_rest' => true,
                'menu_icon'    => 'dashicons-car',
                // legacy behaviour: no content editor (handled by metaboxes)
                'supports'     => [ 'title', 'thumbnail', 'page-attributes' ],
                'has_archive'  => 'remorque',
                'rewrite'      => [ 'slug' => 'remorque', 'with_front' => false ],
            ]
        );
        // editor support re‑added above, no need to remove it
        // remove_post_type_support( 'remorques', 'editor' );

        // register common metadata so blocks can access it through REST
        $meta_keys = [
            '_ozi_weight',
            '_ozi_capacity',
            '_ozi_dimensions',
            '_ozi_features',
            '_ozi_intros',
            '_ozi_intros_label',
            '_ozi_infos',
            '_ozi_bg_tech_full_id',
            '_ozi_tech_carac',
            '_ozi_tech_equip',
            '_ozi_reviews',
            '_ozi_faq',
            '_ozi_buy_link',
            '_ozi_buy_label',
            '_ozi_price',
        ];
        foreach ( $meta_keys as $key ) {
            register_post_meta( 'remorques', $key, [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'string',
            ] );
        }

        // Accessoires CPT
        register_post_type(
            'accessoires',
            [
                'labels'       => [
                    'name'          => __( 'Accessoires', self::TEXT_DOMAIN ),
                    'singular_name' => __( 'Accessoire', self::TEXT_DOMAIN ),
                    'add_new_item'  => __( 'Ajouter un accessoire', self::TEXT_DOMAIN ),
                    'edit_item'     => __( 'Modifier l\'accessoire', self::TEXT_DOMAIN ),
                ],
                'public'       => true,
                'show_in_rest' => true,
                'menu_icon'    => 'dashicons-hammer',
                'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ],
                'has_archive'  => true,
                'rewrite'      => [ 'slug' => 'accessoires' ],
            ]
        );


    }

    /**
     * Add rewrite rule for deep-linking slider (/showcase/{slug-produit}).
     */
    public function add_rewrite_rules(): void {
        add_rewrite_rule(
            '^remorque/([^/]+)/?$',
            'index.php?pagename=remorque&ozi_slide=$matches[1]',
            'top'
        );

        // Legacy redirect from /showcase/ to /remorque/
        add_rewrite_rule(
            '^showcase/(.*)$',
            'index.php?redirect_showcase_to_remorque=1&showcase_slug=$matches[1]',
            'top'
        );
    }

    /**
     * Flush rewrite rules after theme switch.
     */
    public function flush_rewrites(): void {
        flush_rewrite_rules();
    }

    /**
     * Register custom query variables for deep-linking and redirection.
     *
     * @param array $vars Existing query vars.
     * @return array Modified query vars.
     */
    public function register_query_vars( array $vars ): array {
        $vars[] = 'ozi_slide';
        $vars[] = 'redirect_showcase_to_remorque';
        $vars[] = 'showcase_slug';
        return $vars;
    }

    /**
     * Redirect legacy /showcase/* URLs to /remorque/*.
     */
    public function maybe_redirect_showcase(): void {
        if ( get_query_var( 'redirect_showcase_to_remorque' ) !== '1' ) {
            return;
        }

        $slug = get_query_var( 'showcase_slug' );
        if ( ! $slug ) {
            wp_safe_redirect( home_url( '/remorque/' ), 301 );
            exit;
        }

        $target = home_url( '/remorque/' . rawurlencode( $slug ) . '/' );
        wp_safe_redirect( $target, 301 );
        exit;
    }

    /**
     * Force a clean canonical permalink for remorque singles.
     *
     * @param string  $permalink Generated permalink.
     * @param WP_Post $post Post object.
     * @return string
     */
    public function filter_remorque_permalink( string $permalink, WP_Post $post ): string {
        if ( $post->post_type !== 'remorques' ) {
            return $permalink;
        }

        return trailingslashit( home_url( '/remorque/' . $post->post_name ) );
    }

    /**
     * Redirect legacy or malformed remorque URLs to the canonical path.
     *
     * Example:
     * /remorques -> /remorque/
     * /remorques/ozi-duo-light -> /remorque/ozi-duo-light/
     * /remorques/ozi-duo-light/ozi-one -> /remorque/ozi-duo-light/
     * /remorque/ozi-duo-light/ozi-one -> /remorque/ozi-duo-light/
     */
    public function maybe_redirect_nested_remorques_url(): void {
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
        $request_path = $request_uri ? (string) wp_parse_url( $request_uri, PHP_URL_PATH ) : '';
        $request_path = trim( $request_path, '/' );

        if ( $request_path === 'remorques' ) {
            wp_safe_redirect( trailingslashit( home_url( '/remorque/' ) ), 301 );
            exit;
        }

        if ( ! preg_match( '#^(?:remorque|remorques)/([^/]+)(?:/.+)?$#', $request_path, $matches ) ) {
            return;
        }

        $slug = sanitize_title( $matches[1] );
        $post = get_page_by_path( $slug, OBJECT, 'remorques' );

        if ( ! $post instanceof WP_Post ) {
            return;
        }

        $target = trailingslashit( home_url( '/remorque/' . $slug ) );

        if ( untrailingslashit( home_url( '/' . $request_path ) ) === untrailingslashit( $target ) ) {
            return;
        }

        wp_safe_redirect( $target, 301 );
        exit;
    }

    /**
     * Route /remorque/* requests to the existing showcase page slug.
     *
     * This avoids requiring a physical page whose slug is exactly "remorque"
     * while still keeping the public URL structure under /remorque/.
     *
     * @param array $query_vars Parsed request vars.
     * @return array
     */
    public function map_remorque_request_to_showcase( array $query_vars ): array {
        if ( isset( $query_vars['pagename'] ) && $query_vars['pagename'] === 'remorque' ) {
            $query_vars['pagename'] = 'showcase';
            unset( $query_vars['error'] );
            return $query_vars;
        }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
        $request_path = $request_uri ? (string) wp_parse_url( $request_uri, PHP_URL_PATH ) : '';
        $request_path = trim( $request_path, '/' );

        if ( $request_path === 'remorque' ) {
            $query_vars['pagename'] = 'showcase';
            unset( $query_vars['error'] );
        }

        return $query_vars;
    }

    /**
     * Replace the routed showcase page title with the current remorque title.
     *
     * @param array $parts Document title parts.
     * @return array
     */
    public function filter_remorque_document_title( array $parts ): array {
        $post = $this->get_current_remorque_from_request();
        if ( ! $post instanceof WP_Post ) {
            return $parts;
        }

        $parts['title'] = get_the_title( $post );
        return $parts;
    }

    /**
     * Override the document title string for routed remorque pages.
     *
     * @param string $title Current document title.
     * @return string
     */
    public function filter_remorque_document_title_text( string $title ): string {
        $post = $this->get_current_remorque_from_request();
        if ( ! $post instanceof WP_Post ) {
            return $title;
        }

        return get_the_title( $post ) . ' | ' . get_bloginfo( 'name' );
    }

    /**
     * Replace the internal showcase page title when it is rendered on the front-end.
     *
     * @param string $title Current title.
     * @param int    $post_id Post ID.
     * @return string
     */
    public function filter_showcase_page_title( string $title, int $post_id ): string {
        if ( is_admin() || wp_doing_ajax() ) {
            return $title;
        }

        $remorque = $this->get_current_remorque_from_request();
        if ( ! $remorque instanceof WP_Post ) {
            return $title;
        }

        $queried_object = get_queried_object();
        if ( ! $queried_object instanceof WP_Post || (int) $queried_object->ID !== $post_id ) {
            return $title;
        }

        return get_the_title( $remorque );
    }

    /**
     * Resolve the remorque matched by the deep-link request.
     *
     * @return WP_Post|null
     */
    private function get_current_remorque_from_request(): ?WP_Post {
        $slug = (string) get_query_var( 'ozi_slide' );
        if ( $slug === '' ) {
            return null;
        }

        $post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'remorques' );
        return $post instanceof WP_Post ? $post : null;
    }

    /**
     * Enqueue front-end styles.
     */
    public function enqueue_styles(): void {
        wp_enqueue_style(
            'ozi-theme-style',
            get_stylesheet_uri(),
            [],
            null
        );
    }

    /**
     * Enqueue front-end scripts.
     */
    public function enqueue_scripts(): void {
        $this->enqueue_header_script();
    }

    /**
     * Enqueue mobile header hamburger menu script.
     */
    private function enqueue_header_script(): void {
        $js_path = get_stylesheet_directory() . '/assets/js/header.js';
        if ( file_exists( $js_path ) ) {
            wp_enqueue_script(
                'ozi-header',
                get_stylesheet_directory_uri() . '/assets/js/header.js',
                [],
                filemtime( $js_path ),
                true
            );
        }
    }

    /**
     * Allow additional MIME types for uploads.
     *
     * @param array $mimes Existing MIME types.
     * @return array Modified MIME types.
     */
    public function allow_extra_mimes( array $mimes ): array {
        $mimes['webm'] = 'video/webm';
        $mimes['ogv']  = 'video/ogg';
        $mimes['svg']  = 'image/svg+xml';
        return $mimes;
    }
    }
}

// instantiate once the class definition is visible
new OZI_Theme_Setup();
