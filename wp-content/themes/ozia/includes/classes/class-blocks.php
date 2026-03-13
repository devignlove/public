<?php
declare(strict_types=1);

/**
 * OZI_Blocks class
 *
 * Handles block registration and rendering for showcase block.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OZI_Blocks {

    const TEXT_DOMAIN = 'ozitheme';

    public function __construct() {
        add_action( 'init', [ $this, 'register_blocks' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
    }

    /**
     * Register custom blocks.
     */
    public function register_blocks(): void {
        // showcase slider
        $dir = get_stylesheet_directory() . '/blocks/showcase';
        if ( file_exists( $dir . '/block.json' ) ) {
            register_block_type_from_metadata( $dir, [
                'render_callback' => [ $this, 'render_showcase_block' ],
            ] );
        }


    }

    /**
     * Enqueue block editor assets.
     */
    public function enqueue_editor_assets(): void {
        $uri = get_stylesheet_directory_uri();
        $dir = get_stylesheet_directory();

        if ( file_exists( $dir . '/blocks/showcase/bridge.js' ) ) {
            wp_enqueue_script(
                'ozi-editor-bridge',
                $uri . '/blocks/showcase/bridge.js',
                [ 'wp-blocks', 'wp-element' ],
                filemtime( $dir . '/blocks/showcase/bridge.js' ),
                true
            );
        }

        if ( file_exists( $dir . '/blocks/showcase/showcase-editor.css' ) ) {
            wp_enqueue_style(
                'ozi-showcase-editor',
                $uri . '/blocks/showcase/showcase-editor.css',
                [],
                filemtime( $dir . '/blocks/showcase/showcase-editor.css' )
            );
        }
    }

    /**
     * Normalize a repeater-like meta value into a clean list of rows.
     *
     * @param mixed $value Raw meta value from get_post_meta().
     * @param array $allowed_keys Keys expected in each row.
     * @return array<int, array<string, mixed>>
     */
    private function normalize_rows_meta( $value, array $allowed_keys ): array {
        if ( ! is_array( $value ) ) {
            return [];
        }

        $rows = [];

        foreach ( $value as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }

            $normalized = [];
            foreach ( $allowed_keys as $key ) {
                $normalized[ $key ] = $row[ $key ] ?? '';
            }

            $has_content = false;
            foreach ( $normalized as $cell ) {
                if ( is_numeric( $cell ) && (int) $cell > 0 ) {
                    $has_content = true;
                    break;
                }

                if ( is_string( $cell ) && trim( $cell ) !== '' ) {
                    $has_content = true;
                    break;
                }
            }

            if ( $has_content ) {
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    /**
     * Normalize quick features whether stored as CSV or array.
     *
     * @param mixed $value Raw features meta.
     * @return array<int, string>
     */
    private function normalize_features( $value ): array {
        if ( is_array( $value ) ) {
            return array_values(
                array_filter(
                    array_map( 'trim', $value ),
                    static fn( $item ): bool => $item !== ''
                )
            );
        }

        if ( ! is_string( $value ) || trim( $value ) === '' ) {
            return [];
        }

        return array_values(
            array_filter(
                array_map( 'trim', explode( ',', $value ) ),
                static fn( string $item ): bool => $item !== ''
            )
        );
    }

    /**
     * Parse a numeric value from mixed meta input.
     *
     * @param mixed $value Raw meta value.
     * @return float
     */
    private function parse_numeric_meta_value( $value ): float {
        if ( is_numeric( $value ) ) {
            return (float) $value;
        }

        if ( ! is_string( $value ) || trim( $value ) === '' ) {
            return 0.0;
        }

        $normalized = str_replace( ',', '.', preg_replace( '/[^0-9,.\-]/', '', $value ) );
        return is_numeric( $normalized ) ? (float) $normalized : 0.0;
    }

    /**
     * Resolve a media panel for home cards and story blocks.
     *
     * @param int    $post_id Post ID.
     * @param string $fallback_image Fallback image URL.
     * @return string
     */
    private function get_home_media_panel( int $post_id, string $fallback_image = '' ): string {
        $bg_tech_full_id = (int) get_post_meta( $post_id, '_ozi_bg_tech_full_id', true );
        $bg_tech_full    = $bg_tech_full_id ? wp_get_attachment_image_url( $bg_tech_full_id, 'large' ) : '';

        if ( $bg_tech_full ) {
            return '<div class="ozi-home__image-panel"><img src="' . esc_url( $bg_tech_full ) . '" alt=""></div>';
        }

        $hero_html = OZI_Utils::render_featured_media_hero( $post_id );
        if ( $hero_html ) {
            return $hero_html;
        }

        if ( $fallback_image ) {
            return '<div class="ozi-home__image-panel"><img src="' . esc_url( $fallback_image ) . '" alt=""></div>';
        }

        return '<div class="ozi-home__image-panel ozi-home__image-panel--empty" aria-hidden="true"></div>';
    }

    /**
     * Resolve media data for the masked hero wordmark.
     *
     * @param int    $post_id Post ID.
     * @param string $fallback_image Fallback image URL.
     * @return array<string, mixed>
     */
    private function get_home_mask_media_data( int $post_id, string $fallback_image = '' ): array {
        $thumbnail_id = (int) get_post_thumbnail_id( $post_id );

        if ( $thumbnail_id ) {
            $mime = get_post_mime_type( $thumbnail_id ) ?: '';
            $url  = wp_get_attachment_url( $thumbnail_id ) ?: '';

            if ( $url && strpos( $mime, 'video/' ) === 0 ) {
                return [
                    'type'    => 'video',
                    'poster'  => '',
                    'sources' => [
                        [
                            'src'  => $url,
                            'type' => $mime ?: 'video/mp4',
                        ],
                    ],
                ];
            }

            $image = wp_get_attachment_image_url( $thumbnail_id, 'full' );
            if ( $image ) {
                return [
                    'type'  => 'image',
                    'image' => $image,
                ];
            }
        }

        if ( get_post_meta( $post_id, '_ozi_video_use', true ) === '1' ) {
            $uploaded = OZI_Utils::get_uploaded_video_sources( $post_id );
            if ( $uploaded ) {
                return [
                    'type'    => 'video',
                    'poster'  => get_the_post_thumbnail_url( $post_id, 'full' ) ?: '',
                    'sources' => $uploaded,
                ];
            }

            $video_url = trim( (string) get_post_meta( $post_id, '_ozi_video_url', true ) );
            $parsed    = OZI_Utils::parse_video_url( $video_url );
            if ( $parsed['type'] === 'self' && ! empty( $parsed['src'] ) ) {
                $source_type = 'video/mp4';
                if ( preg_match( '~\.webm(\?.*)?$~i', $parsed['src'] ) ) {
                    $source_type = 'video/webm';
                } elseif ( preg_match( '~\.(ogv|ogg)(\?.*)?$~i', $parsed['src'] ) ) {
                    $source_type = 'video/ogg';
                }

                return [
                    'type'    => 'video',
                    'poster'  => get_the_post_thumbnail_url( $post_id, 'full' ) ?: '',
                    'sources' => [
                        [
                            'src'  => $parsed['src'],
                            'type' => $source_type,
                        ],
                    ],
                ];
            }
        }

        $bg_tech_full_id = (int) get_post_meta( $post_id, '_ozi_bg_tech_full_id', true );
        $bg_tech_full    = $bg_tech_full_id ? wp_get_attachment_image_url( $bg_tech_full_id, 'full' ) : '';
        if ( $bg_tech_full ) {
            return [
                'type'  => 'image',
                'image' => $bg_tech_full,
            ];
        }

        if ( $fallback_image ) {
            return [
                'type'  => 'image',
                'image' => $fallback_image,
            ];
        }

        return [
            'type' => 'empty',
        ];
    }

    /**
     * Get selected accessories for a remorque.
     *
     * @param int $post_id Remorque post ID.
     * @return array<int, array<string, string>>
     */
    private function get_selected_accessories( int $post_id ): array {
        $ids = array_values( array_filter( array_map( 'absint', (array) get_post_meta( $post_id, '_ozi_accessoires', true ) ) ) );
        if ( ! $ids ) {
            return [];
        }

        $query = new WP_Query( [
            'post_type'      => 'accessoires',
            'post__in'       => $ids,
            'posts_per_page' => count( $ids ),
            'orderby'        => 'post__in',
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        ] );

        if ( ! $query->have_posts() ) {
            return [];
        }

        $accessories = [];
        while ( $query->have_posts() ) {
            $query->the_post();
            $accessories[] = [
                'title'   => get_the_title(),
                'excerpt' => get_the_excerpt(),
                'url'     => get_permalink() ?: '',
                'image'   => get_the_post_thumbnail_url( get_the_ID(), 'large' ) ?: '',
            ];
        }
        wp_reset_postdata();

        return $accessories;
    }

    /**
     * Render the custom front-page experience.
     *
     * @return string
     */
    public function render_homepage(): string {
        $uri = get_stylesheet_directory_uri();
        $dir = get_stylesheet_directory();

        $this->enqueue_home_assets( $uri, $dir );

        $query = new WP_Query(
            [
                'post_type'      => 'remorques',
                'posts_per_page' => 4,
                'post_status'    => 'publish',
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
                'no_found_rows'  => true,
            ]
        );

        $trailers = [];

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();

                $post_id    = get_the_ID();
                $slug       = get_post_field( 'post_name', $post_id );
                $title      = get_the_title( $post_id );
                $excerpt    = get_the_excerpt( $post_id );
                $weight     = (string) get_post_meta( $post_id, '_ozi_weight', true );
                $capacity   = (string) get_post_meta( $post_id, '_ozi_capacity', true );
                $dimensions = (string) get_post_meta( $post_id, '_ozi_dimensions', true );
                $features   = $this->normalize_features( get_post_meta( $post_id, '_ozi_features', true ) );
                $intros     = trim( (string) get_post_meta( $post_id, '_ozi_intros', true ) );
                $thumb      = get_the_post_thumbnail_url( $post_id, 'large' ) ?: '';

                $trailers[] = [
                    'post_id'     => $post_id,
                    'title'      => $title,
                    'slug'       => $slug,
                    'excerpt'    => $excerpt,
                    'weight'     => $weight,
                    'capacity'   => $capacity,
                    'dimensions' => $dimensions,
                    'features'   => array_slice( $features, 0, 4 ),
                    'intro'      => $intros,
                    'thumb'      => $thumb,
                    'media_html' => $this->get_home_media_panel( $post_id, $thumb ),
                    'url'        => trailingslashit( home_url( '/remorque/' . $slug ) ),
                ];
            }
            wp_reset_postdata();
        }

        $featured_trailer   = $trailers[0] ?? null;
        $secondary_trailer  = $trailers[1] ?? $featured_trailer;
        $collection_trailers = array_slice( $trailers, 0, 4 );
        $models_count       = count( $trailers );
        $total_capacity     = array_sum(
            array_map(
                fn( array $trailer ): float => $this->parse_numeric_meta_value( $trailer['capacity'] ?? '' ),
                $trailers
            )
        );
        $top_capacity       = 0;
        foreach ( $trailers as $trailer ) {
            $top_capacity = max( $top_capacity, (int) round( $this->parse_numeric_meta_value( $trailer['capacity'] ?? '' ) ) );
        }
        $average_load     = $models_count > 0 ? (int) round( $total_capacity / $models_count ) : 0;
        $hero_title       = __( 'Une gamme de remorques nette, fiable et prete a partir.', self::TEXT_DOMAIN );
        $hero_text        = __( 'Une landing page qui installe le ton, montre la gamme et ouvre le projet en une seule lecture.', self::TEXT_DOMAIN );
        $hero_mask_media  = $featured_trailer ? $this->get_home_mask_media_data( (int) $featured_trailer['post_id'], (string) $featured_trailer['thumb'] ) : [ 'type' => 'empty' ];
        $mask_clip_id     = wp_unique_id( 'ozi-home-clip-' );
        $contact_page     = get_page_by_path( 'contact' );
        $contact_url      = $contact_page instanceof WP_Post ? get_permalink( $contact_page ) : home_url( '/contact/' );
        $featured_intro   = $featured_trailer['intro'] ?? '';
        $featured_excerpt = $featured_trailer['excerpt'] ?? '';
        $featured_text    = $featured_intro ?: $featured_excerpt;
        $featured_text    = $featured_text ?: __( 'Des remorques pensees pour garder une vraie presence visuelle tout en restant simples a comprendre et a choisir.', self::TEXT_DOMAIN );
        $story_title      = $secondary_trailer['title'] ?? __( 'Le detail compte.', self::TEXT_DOMAIN );
        $story_text       = $secondary_trailer['intro'] ?? '';
        $story_text       = $story_text ?: ( $secondary_trailer['excerpt'] ?? '' );
        $story_text       = $story_text ?: __( 'Une base claire, des usages concrets et une finition qui met la technique au service du quotidien.', self::TEXT_DOMAIN );
        $story_features   = $featured_trailer ? array_filter( [ $featured_trailer['capacity'], $featured_trailer['weight'], $featured_trailer['dimensions'] ] ) : [];

        ob_start();
        ?>
        <main id="site-main" class="ozi-home" data-ozi-home>
            <section class="ozi-home__hero" data-home-hero>
                <div class="ozi-home__hero-stage" data-home-stage>
                    <div class="ozi-home__media-shell" data-home-visual>
                        <div class="ozi-home__mesh" aria-hidden="true"></div>
                        <div class="ozi-home__grain" aria-hidden="true"></div>
                        <div class="ozi-home__wordmark ozi-home__wordmark--mask" data-home-wordmark aria-hidden="true">
                            <div class="ozi-home__mask-stage">
                                <div class="ozi-home__mask-frame" data-home-mask-frame>
                                    <svg class="ozi-home__mask-svg" viewBox="0 0 2000 520" preserveAspectRatio="xMidYMid meet" role="presentation">
                                        <defs>
                                            <clipPath id="<?php echo esc_attr( $mask_clip_id ); ?>" clipPathUnits="userSpaceOnUse">
                                                <text class="ozi-home__mask-clip-text" x="1000" y="274">OZI CONCEPT</text>
                                            </clipPath>
                                        </defs>

                                        <foreignObject x="0" y="0" width="2000" height="520" clip-path="url(#<?php echo esc_attr( $mask_clip_id ); ?>)">
                                            <div xmlns="http://www.w3.org/1999/xhtml" class="ozi-home__mask-fill" data-home-mask-fill>
                                                <canvas class="ozi-home__mask-canvas" data-home-mask-canvas></canvas>

                                                <?php if ( $hero_mask_media['type'] === 'video' && ! empty( $hero_mask_media['sources'] ) ) : ?>
                                                    <video class="ozi-home__mask-video" data-home-mask-video autoplay muted playsinline webkit-playsinline loop preload="metadata" <?php echo ! empty( $hero_mask_media['poster'] ) ? 'poster="' . esc_url( (string) $hero_mask_media['poster'] ) . '"' : ''; ?>>
                                                        <?php foreach ( (array) $hero_mask_media['sources'] as $source ) : ?>
                                                            <source src="<?php echo esc_url( (string) $source['src'] ); ?>" type="<?php echo esc_attr( (string) $source['type'] ); ?>">
                                                        <?php endforeach; ?>
                                                    </video>
                                                <?php elseif ( $hero_mask_media['type'] === 'image' && ! empty( $hero_mask_media['image'] ) ) : ?>
                                                    <img class="ozi-home__mask-image" data-home-mask-image src="<?php echo esc_url( (string) $hero_mask_media['image'] ); ?>" alt="">
                                                <?php else : ?>
                                                    <div class="ozi-home__mask-fallback"></div>
                                                <?php endif; ?>

                                                <div class="ozi-home__mask-shade"></div>
                                            </div>
                                        </foreignObject>

                                        <text class="ozi-home__mask-stroke" x="1000" y="274">OZI CONCEPT</text>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ozi-home__hero-inner">
                        <div class="ozi-home__copy" data-home-copy>
                            <p class="ozi-home__eyebrow"><?php esc_html_e( 'Landing Page', self::TEXT_DOMAIN ); ?></p>
                            <h1><?php echo esc_html( $hero_title ); ?></h1>
                            <p class="ozi-home__lede"><?php echo esc_html( $hero_text ); ?></p>

                            <div class="ozi-home__actions">
                                <a class="ozi-home__button ozi-home__button--primary" href="#ozi-home-models"><?php esc_html_e( 'Voir la gamme', self::TEXT_DOMAIN ); ?></a>
                                <a class="ozi-home__button ozi-home__button--ghost" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Lancer un projet', self::TEXT_DOMAIN ); ?></a>
                            </div>
                        </div>

                        <ul class="ozi-home__stats" aria-label="<?php esc_attr_e( 'Chiffres cles', self::TEXT_DOMAIN ); ?>">
                            <li><strong><?php echo esc_html( (string) $models_count ); ?></strong><span><?php esc_html_e( 'modeles', self::TEXT_DOMAIN ); ?></span></li>
                            <li><strong><?php echo esc_html( (string) $top_capacity ); ?> kg</strong><span><?php esc_html_e( 'charge utile max', self::TEXT_DOMAIN ); ?></span></li>
                            <li><strong><?php echo esc_html( (string) $average_load ); ?> kg</strong><span><?php esc_html_e( 'charge moyenne', self::TEXT_DOMAIN ); ?></span></li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="ozi-home__intro" data-reveal>
                <div class="ozi-home__section-head">
                    <p class="ozi-home__eyebrow"><?php esc_html_e( 'Pourquoi OZI', self::TEXT_DOMAIN ); ?></p>
                    <h2><?php esc_html_e( 'Une one-page pour installer la confiance avant meme d ouvrir une fiche produit.', self::TEXT_DOMAIN ); ?></h2>
                </div>

                <div class="ozi-home__pillars">
                    <article class="ozi-home__pillar" data-reveal>
                        <span class="ozi-home__pillar-index">01</span>
                        <h3><?php esc_html_e( 'Lecture claire', self::TEXT_DOMAIN ); ?></h3>
                        <p><?php esc_html_e( 'Un hero fort, une gamme lisible et un CTA present sans surcharger l ecran.', self::TEXT_DOMAIN ); ?></p>
                    </article>
                    <article class="ozi-home__pillar" data-reveal>
                        <span class="ozi-home__pillar-index">02</span>
                        <h3><?php esc_html_e( 'Gamme concrete', self::TEXT_DOMAIN ); ?></h3>
                        <p><?php esc_html_e( 'Chaque remorque conserve son identite, son usage et son acces direct.', self::TEXT_DOMAIN ); ?></p>
                    </article>
                    <article class="ozi-home__pillar" data-reveal>
                        <span class="ozi-home__pillar-index">03</span>
                        <h3><?php esc_html_e( 'Projet ouvert', self::TEXT_DOMAIN ); ?></h3>
                        <p><?php esc_html_e( 'La landing pousse a la prise de contact sans casser la lecture premium.', self::TEXT_DOMAIN ); ?></p>
                    </article>
                </div>
            </section>

            <?php if ( $featured_trailer ) : ?>
                <section class="ozi-home__feature" data-reveal>
                    <div class="ozi-home__feature-media">
                        <?php echo $featured_trailer['media_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                    <div class="ozi-home__feature-copy">
                        <p class="ozi-home__eyebrow"><?php esc_html_e( 'Modele en avant', self::TEXT_DOMAIN ); ?></p>
                        <h2><?php echo esc_html( $featured_trailer['title'] ); ?></h2>
                        <p><?php echo esc_html( $featured_text ); ?></p>

                        <?php if ( $story_features ) : ?>
                            <ul class="ozi-home__feature-list">
                                <?php foreach ( $story_features as $feature ) : ?>
                                    <li><?php echo esc_html( $feature ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <div class="ozi-home__actions">
                            <a class="ozi-home__button ozi-home__button--primary" href="<?php echo esc_url( $featured_trailer['url'] ); ?>"><?php esc_html_e( 'Voir cette remorque', self::TEXT_DOMAIN ); ?></a>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section id="ozi-home-models" class="ozi-home__models" data-reveal>
                <div class="ozi-home__section-head">
                    <p class="ozi-home__eyebrow"><?php echo esc_html( sprintf( _n( '%s remorque', '%s remorques', $models_count, self::TEXT_DOMAIN ), (string) $models_count ) ); ?></p>
                    <h2><?php esc_html_e( 'Choisir un modele puis entrer dans le detail.', self::TEXT_DOMAIN ); ?></h2>
                </div>

                <div class="ozi-home__model-grid">
                    <?php if ( $collection_trailers ) : ?>
                        <?php foreach ( $collection_trailers as $index => $trailer ) : ?>
                            <article class="ozi-home__model-card" data-reveal>
                                <a class="ozi-home__model-media" href="<?php echo esc_url( $trailer['url'] ); ?>">
                                    <?php echo $trailer['media_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </a>

                                <div class="ozi-home__model-copy">
                                    <span class="ozi-home__collection-index"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
                                    <h3><?php echo esc_html( $trailer['title'] ); ?></h3>

                                    <?php if ( $trailer['excerpt'] ) : ?>
                                        <p><?php echo esc_html( $trailer['excerpt'] ); ?></p>
                                    <?php endif; ?>

                                    <a class="ozi-home__text-link" href="<?php echo esc_url( $trailer['url'] ); ?>"><?php esc_html_e( 'Ouvrir la fiche', self::TEXT_DOMAIN ); ?></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="ozi-home__collection-empty">
                            <p class="ozi-home__collection-index">00</p>
                            <h3><?php esc_html_e( 'La gamme arrive bientot.', self::TEXT_DOMAIN ); ?></h3>
                            <p><?php esc_html_e( 'Ajoute au moins une remorque publiee pour alimenter automatiquement cette home page.', self::TEXT_DOMAIN ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="ozi-home__story" data-reveal>
                <div class="ozi-home__story-copy">
                    <p class="ozi-home__eyebrow"><?php esc_html_e( 'Esprit de gamme', self::TEXT_DOMAIN ); ?></p>
                    <h2><?php echo esc_html( $story_title ); ?></h2>
                    <p><?php echo esc_html( $story_text ); ?></p>
                </div>
                <div class="ozi-home__story-panel">
                    <div class="ozi-home__story-card">
                        <strong><?php esc_html_e( 'Parcours', self::TEXT_DOMAIN ); ?></strong>
                        <p><?php esc_html_e( 'Home, modele, detail, contact: une lecture simple qui pousse a agir.', self::TEXT_DOMAIN ); ?></p>
                    </div>
                    <div class="ozi-home__story-card">
                        <strong><?php esc_html_e( 'Rendu', self::TEXT_DOMAIN ); ?></strong>
                        <p><?php esc_html_e( 'Moins catalogue brut, plus landing premium qui raconte la marque et la gamme.', self::TEXT_DOMAIN ); ?></p>
                    </div>
                </div>
            </section>

            <section class="ozi-home__cta" data-reveal>
                <p class="ozi-home__eyebrow"><?php esc_html_e( 'Projet', self::TEXT_DOMAIN ); ?></p>
                <h2><?php esc_html_e( 'Une page unique pour donner envie, rassurer et faire avancer le contact.', self::TEXT_DOMAIN ); ?></h2>
                <p><?php esc_html_e( 'Le hero marque les esprits, la gamme devient lisible, et chaque section rapproche du projet.', self::TEXT_DOMAIN ); ?></p>
                <div class="ozi-home__actions ozi-home__actions--center">
                    <a class="ozi-home__button ozi-home__button--primary" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contacter OZI', self::TEXT_DOMAIN ); ?></a>
                </div>
            </section>
        </main>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * Render showcase block (Server-Side Rendering).
     *
     * @param array  $attributes Block attributes.
     * @param string $content Block inner content.
     * @return string Rendered block HTML.
     */
    public function render_showcase_block( array $attributes = [], string $content = '' ): string {
        // avoid rendering twice on the same page (template + editor content etc.)
        static $instance = 0;
        $instance++;
        if ( $instance > 1 ) {
            // already output by the first invocation; skip duplicates
            return '';
        }

        $uri = get_stylesheet_directory_uri();
        $dir = get_stylesheet_directory();

        // Enqueue front-end showcase CSS and JS
        $this->enqueue_showcase_assets( $uri, $dir );

        // Get deep-link parameters
        $page_id      = get_the_ID() ?: get_queried_object_id();
        $request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
        $request_path = $request_uri ? (string) wp_parse_url( $request_uri, PHP_URL_PATH ) : '';

        if ( preg_match( '#/remorque(?:/|$)#', $request_path ) || is_singular( 'remorques' ) ) {
            $base_url = trailingslashit( home_url( '/remorque/' ) );
        } else {
            $base_url = $page_id ? trailingslashit( get_permalink( $page_id ) ) : trailingslashit( home_url( '/showcase/' ) );
        }
        $start_slug = get_query_var( 'ozi_slide' );

        // Query products
        $q = new WP_Query( [
            'post_type'      => 'remorques',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ] );

        $products = [];

        if ( ! $q->have_posts() ) {
            return '<section class="content-wrap"><article class="card"><p style="padding:24px">' .
                esc_html__( 'Aucune remorque publiee pour le moment.', self::TEXT_DOMAIN ) .
                '</p></article></section>';
        }

        ob_start();
        ?>
        <header class="slider" id="slider"
                data-base="<?php echo esc_url( $base_url ); ?>"
                data-start="<?php echo esc_attr( $start_slug ); ?>">
            <div class="slides" id="slides">
                <?php
                if ( $q->have_posts() ) :
                    while ( $q->have_posts() ) :
                        $q->the_post();
                        $id   = get_the_ID();
                        $slug = get_post_field( 'post_name', $id );

                        // Render featured media
                        $hero_html = OZI_Utils::render_featured_media_hero( $id );
                        if ( ! $hero_html ) {
                            $thumb = get_the_post_thumbnail_url( $id, 'full' ) ?: $uri . '/assets/img/placeholder.jpg';
                            $hero_html = '<div class="hero" style="background-image:url(\'' . esc_url( $thumb ) . '\')"></div>';
                        }

                        $features_list = $this->normalize_features( get_post_meta( $id, '_ozi_features', true ) );
                        $weight     = get_post_meta( $id, '_ozi_weight', true );
                        $capacity   = get_post_meta( $id, '_ozi_capacity', true );
                        $dimensions = get_post_meta( $id, '_ozi_dimensions', true );
                        $intros     = (string) get_post_meta( $id, '_ozi_intros', true );
                        $intros_label = (string) get_post_meta( $id, '_ozi_intros_label', true );
                        $infos   = $this->normalize_rows_meta( get_post_meta( $id, '_ozi_infos', true ), [ 'title', 'text', 'image_id' ] );
                        $bg_tech_full_id = (int) get_post_meta( $id, '_ozi_bg_tech_full_id', true );
                        $bg_tech_full    = $bg_tech_full_id ? wp_get_attachment_url( $bg_tech_full_id ) : '';
                        $tech_car   = $this->normalize_rows_meta( get_post_meta( $id, '_ozi_tech_carac', true ), [ 'label', 'value' ] );
                        $tech_eqp   = $this->normalize_rows_meta( get_post_meta( $id, '_ozi_tech_equip', true ), [ 'label', 'value' ] );
                        $reviews    = $this->normalize_rows_meta( get_post_meta( $id, '_ozi_reviews', true ), [ 'author', 'text' ] );
                        $faq        = $this->normalize_rows_meta( get_post_meta( $id, '_ozi_faq', true ), [ 'q', 'a' ] );
                        $buy_link   = (string) get_post_meta( $id, '_ozi_buy_link', true );
                        $buy_label  = (string) get_post_meta( $id, '_ozi_buy_label', true ) ?: __( 'Acheter maintenant', self::TEXT_DOMAIN );
                        $accessories = $this->get_selected_accessories( $id );
                        ?>
                        <section class="slide" data-slug="<?php echo esc_attr( $slug ); ?>">
                            <?php echo $hero_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <div class="top-fade"></div><div class="bottom-fade"></div>

                            <div class="hero-info">
                                <h2><?php the_title(); ?></h2>

                                <ul class="metrics">
                                    <?php if ( $weight !== '' ) : ?>
                                        <li class="metric">
                                            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M7 3h10l3 6v9a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V9l3-6Zm1.7 2-2 4H17.3l-2-4H8.7ZM12 9.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/></svg>
                                            <div><div class="m-label"><?php _e( 'Poids', self::TEXT_DOMAIN ); ?></div><div class="m-val"><?php echo esc_html( $weight ); ?> kg</div></div>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ( $capacity !== '' ) : ?>
                                        <li class="metric">
                                            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3 8h18v7a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8Zm3-5h12v3H6V3Z"/></svg>
                                            <div><div class="m-label"><?php _e( 'Charge utile', self::TEXT_DOMAIN ); ?></div><div class="m-val"><?php echo esc_html( $capacity ); ?> kg</div></div>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ( $dimensions !== '' ) : ?>
                                        <li class="metric">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M15 3H3v18h12V3zm-1 4h-4V4h4v3zm0 3h-4V7h4v3zm-5 0H5V7h4v3zm0 4h-4v-3h4v3zm5-4h4v3h-4v-3z"/></svg>
                                            <div><div class="m-label"><?php _e( 'Dimensions', self::TEXT_DOMAIN ); ?></div><div class="m-val"><?php echo esc_html( $dimensions ); ?> cm²</div></div>
                                        </li>
                                    <?php endif; ?>
                                </ul>

                                <?php if ( $features_list ) : ?>
                                    <ul class="features">
                                        <?php foreach ( $features_list as $f ) : ?>
                                            <li><?php echo esc_html( $f ); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </section>
                        <?php
                        // Build payload for frontend card
                        $products[] = [
                            'title'    => get_the_title(),
                            'slug'     => $slug,
                            'url'      => get_permalink( $id ),
                            'tag'      => get_the_title(),
                            'subtitle' => get_the_excerpt(),
                            'hero'     => get_the_post_thumbnail_url( $id, 'full' ) ?: $uri . '/assets/img/placeholder.jpg',
                            'price'    => get_post_meta( $id, '_ozi_price', true ),
                            'features' => $features_list,
                            'specs'    => [
                                'Poids'        => $weight ?: '',
                                'Charge utile' => $capacity ?: '',
                                'Dimensions'   => $dimensions ?: '',
                            ],
                            'intros'   => $intros,
                            'intros_label' => $intros_label,
                            'infos'    => array_map( function( array $s ) {
                                $u = ! empty( $s['image_id'] ) ? wp_get_attachment_url( (int) $s['image_id'] ) : '';
                                return [ 'title' => (string) ( $s['title'] ?? '' ), 'text' => (string) ( $s['text'] ?? '' ), 'image' => $u ];
                            }, $infos ),
                            'bgTechFull' => $bg_tech_full,
                            'tech'      => [
                                'caracteristiques' => $tech_car,
                                'equipements'      => $tech_eqp,
                            ],
                            'reviews'  => $reviews,
                            'faq'      => $faq,
                            'buyLink'  => $buy_link,
                            'buyLabel' => $buy_label,
                            'accessories' => $accessories,
                        ];
                    endwhile;
                endif;
                wp_reset_postdata();
                ?>
            </div>

            <div class="nav" id="nav-arrows"><button id="prev">❮</button><button id="next">❯</button></div>
            <div class="caption">
                <button class="discover-btn" id="discover"><span><svg width="64px" height="64px" viewBox="-7.58 -7.58 90.96 90.96" xmlns="http://www.w3.org/2000/svg" fill="#ffffff" stroke="#ffffff" stroke-width="3.7902"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><g id="Group_67" data-name="Group 67" transform="translate(-798.203 -587.815)"><path id="Path_59" data-name="Path 59" d="M798.2,589.314a1.5,1.5,0,0,1,2.561-1.06l33.56,33.556a2.528,2.528,0,0,0,3.564,0l33.558-33.556a1.5,1.5,0,1,1,2.121,2.121l-33.558,33.557a5.53,5.53,0,0,1-7.807,0l-33.56-33.557A1.5,1.5,0,0,1,798.2,589.314Z" fill="#fff"></path></g></g></svg></span></button>
            </div>
        </header>

        <section class="content-wrap" id="content">
            <article class="card" id="details-card"></article>
        </section>
        <?php
        $html = ob_get_clean();

        // Inject OZI_DATA once
        static $printed = false;
        if ( ! $printed ) {
            $printed = true;
            $payload = [
                'products' => $products,
                'i18n'     => [
                    'reviews' => __( 'avis', self::TEXT_DOMAIN ),
                    'faq'     => __( 'FAQ', self::TEXT_DOMAIN ),
                    'buy'     => __( 'Acheter maintenant', self::TEXT_DOMAIN ),
                    'accessories' => __( 'Accessoires compatibles', self::TEXT_DOMAIN ),
                    'discoverAccessory' => __( 'Voir l accessoire', self::TEXT_DOMAIN ),
                ],
            ];
            $html .= '<script>window.OZI_DATA=' . wp_json_encode( $payload ) . ';</script>';
        }

        return $html;
    }

    /**
     * Enqueue showcase block assets on front-end.
     *
     * @param string $uri Stylesheet directory URI.
     * @param string $dir Stylesheet directory path.
     */
    private function enqueue_showcase_assets( string $uri, string $dir ): void {
        $css = $dir . '/assets/css/showcase.css';
        if ( file_exists( $css ) ) {
            wp_enqueue_style(
                'ozi-front',
                $uri . '/assets/css/showcase.css',
                [],
                filemtime( $css )
            );
        }

        $js = $dir . '/assets/js/showcase.js';
        if ( file_exists( $js ) ) {
            wp_enqueue_script(
                'ozi-front',
                $uri . '/assets/js/showcase.js',
                [],
                filemtime( $js ),
                true
            );
        }
    }

    /**
     * Enqueue front-page assets.
     *
     * @param string $uri Stylesheet directory URI.
     * @param string $dir Stylesheet directory path.
     */
    private function enqueue_home_assets( string $uri, string $dir ): void {
        $css = $dir . '/assets/css/threejshome.css';
        if ( file_exists( $css ) ) {
            wp_enqueue_style(
                'ozi-home',
                $uri . '/assets/css/threejshome.css',
                [ 'ozi-theme-style' ],
                filemtime( $css )
            );
        }

        $js = $dir . '/assets/js/threejshome.js';
        if ( file_exists( $js ) ) {
            wp_register_script(
                'ozi-home-gsap',
                'https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/gsap.min.js',
                [],
                '3.12.7',
                true
            );

            wp_register_script(
                'ozi-home-scrolltrigger',
                'https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/ScrollTrigger.min.js',
                [ 'ozi-home-gsap' ],
                '3.12.7',
                true
            );

            wp_register_script(
                'ozi-home-three',
                'https://cdn.jsdelivr.net/npm/three@0.161.0/build/three.min.js',
                [],
                '0.161.0',
                true
            );

            wp_enqueue_script(
                'ozi-home',
                $uri . '/assets/js/threejshome.js',
                [ 'ozi-home-gsap', 'ozi-home-scrolltrigger', 'ozi-home-three' ],
                filemtime( $js ),
                true
            );
        }
    }

    /**
     * Render remorque details in front‑end (and preview in editor).
     *
     * @param array  $attributes Block attributes (pulled from meta).
     * @param string $content Not used.
     * @return string HTML markup.
     */
    public function render_remorque_block( array $attributes = [], string $content = '' ): string {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return ''; // outside a post context
        }

        $weight   = get_post_meta( $post_id, '_ozi_weight', true );
        $capacity = get_post_meta( $post_id, '_ozi_capacity', true );

        ob_start();
        ?>
        <div class="remorque-details-block">
            <?php if ( $weight !== '' ) : ?>
                <p><strong><?php _e( 'Poids', self::TEXT_DOMAIN ); ?>:</strong> <?php echo esc_html( $weight ); ?> kg</p>
            <?php endif; ?>
            <?php if ( $capacity !== '' ) : ?>
                <p><strong><?php _e( 'Charge utile', self::TEXT_DOMAIN ); ?>:</strong> <?php echo esc_html( $capacity ); ?> kg</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

new OZI_Blocks();
