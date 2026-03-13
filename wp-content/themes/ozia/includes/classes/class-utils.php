<?php
declare(strict_types=1);

/**
 * OZI_Utils class
 *
 * Utility functions for video processing, media handling, and common helpers.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OZI_Utils {

    const TEXT_DOMAIN = 'ozitheme';

    /**
     * Get video sources (mp4 and webm) for a post.
     *
     * @param int $post_id Post ID.
     * @return array Array of video sources with 'src' and 'type'.
     */
    public static function get_uploaded_video_sources( int $post_id ): array {
        $sources = [];
        $webm_id = (int) get_post_meta( $post_id, '_ozi_video_webm_id', true );
        $mp4_id  = (int) get_post_meta( $post_id, '_ozi_video_id', true );

        if ( $webm_id ) {
            $url = wp_get_attachment_url( $webm_id );
            if ( $url ) {
                $sources[] = [ 'src' => $url, 'type' => 'video/webm' ];
            }
        }

        if ( $mp4_id ) {
            $url = wp_get_attachment_url( $mp4_id );
            if ( $url ) {
                $sources[] = [ 'src' => $url, 'type' => 'video/mp4' ];
            }
        }

        return $sources;
    }

    /**
     * Parse video URL and detect type (YouTube, Vimeo, or self-hosted).
     *
     * @param string $url Video URL.
     * @return array Array with 'type', 'id', and 'src'.
     */
    public static function parse_video_url( string $url ): array {
        $output = [ 'type' => '', 'id' => '', 'src' => '' ];

        if ( ! $url ) {
            return $output;
        }

        // Self-hosted file
        if ( preg_match( '~\.(mp4|webm|ogv)(\?.*)?$~i', $url ) ) {
            $output['type'] = 'self';
            $output['src']  = esc_url( $url );
            return $output;
        }

        // YouTube
        if ( preg_match( '~(youtube\.com|youtu\.be)~i', $url ) ) {
            if ( preg_match( '~(?:v=|/embed/|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $matches ) ) {
                $id               = $matches[1];
                $output['type']   = 'youtube';
                $output['id']     = $id;
                $output['src']    = 'https://www.youtube.com/embed/' . $id . '?autoplay=1&mute=1&controls=0&playsinline=1&loop=1&playlist=' . $id . '&modestbranding=1&rel=0';
                return $output;
            }
        }

        // Vimeo
        if ( preg_match( '~vimeo\.com~i', $url ) ) {
            if ( preg_match( '~vimeo\.com/(?:video/)?([0-9]+)~', $url, $matches ) ) {
                $id            = $matches[1];
                $output['type'] = 'vimeo';
                $output['id']   = $id;
                $output['src']  = 'https://player.vimeo.com/video/' . $id . '?background=1&autoplay=1&muted=1&loop=1&autopause=0&playsinline=1';
                return $output;
            }
        }

        return $output;
    }

    /**
     * Render featured media hero (video or image).
     *
     * Priority:
     * 1) Featured image is a video => render <video>
     * 2) Fallback video (uploads or URL) if enabled => render video or iframe
     * 3) Featured image is an image => render as background
     * 4) Empty => return empty string
     *
     * @param int $post_id Post ID.
     * @return string HTML for hero media section.
     */
    public static function render_featured_media_hero( int $post_id ): string {
        $tid = (int) get_post_thumbnail_id( $post_id );

        if ( $tid ) {
            $mime = get_post_mime_type( $tid ) ?: '';
            $url  = wp_get_attachment_url( $tid ) ?: '';

            // Featured image is a video
            if ( $url && strpos( $mime, 'video/' ) === 0 ) {
                return self::render_self_hosted_video_hero( $url, self::get_attachment_poster_url( $tid ) );
            }

            // Featured image is an image
            if ( $img = wp_get_attachment_image_url( $tid, 'full' ) ) {
                return '<div class="hero" style="background-image:url(\'' . esc_url( $img ) . '\')"></div>';
            }
        }

        // Fallback video (if enabled)
        $use = get_post_meta( $post_id, '_ozi_video_use', true ) === '1';
        if ( $use ) {
            $uploaded = self::get_uploaded_video_sources( $post_id );
            $url      = trim( (string) get_post_meta( $post_id, '_ozi_video_url', true ) );

            // Render uploaded videos
            if ( ! empty( $uploaded ) ) {
                $poster = get_the_post_thumbnail_url( $post_id, 'full' ) ?: '';
                ob_start();
                ?>
                <div class="hero hero--video">
                    <video class="hero-media" autoplay muted playsinline webkit-playsinline loop preload="auto" <?php echo $poster ? 'poster="' . esc_url( $poster ) . '"' : ''; ?>>
                        <?php foreach ( $uploaded as $source ) : ?>
                            <source src="<?php echo esc_url( $source['src'] ); ?>" type="<?php echo esc_attr( $source['type'] ); ?>">
                        <?php endforeach; ?>
                    </video>
                </div>
                <?php
                return ob_get_clean();
            }

            // Parse and render URL video
            $vi = self::parse_video_url( $url );
            if ( $vi['type'] === 'self' ) {
                $poster = get_the_post_thumbnail_url( $post_id, 'full' ) ?: '';
                return self::render_self_hosted_video_hero( $vi['src'], $poster );
            }

            if ( $vi['type'] === 'youtube' || $vi['type'] === 'vimeo' ) {
                return '<div class="hero hero--video"><iframe class="hero-media hero-media--embed" title="video" src="" data-src="' .
                       esc_url( $vi['src'] ) . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
            }
        }

        return '';
    }

    /**
     * Shared opening tag for hero video markup.
     *
     * @return string
     */
    private static function get_hero_video_tag_open(): string {
        return '<video class="hero-media" autoplay muted playsinline webkit-playsinline loop preload="metadata" disablepictureinpicture>';
    }

    /**
     * Render a self-hosted video hero with robust, browser-friendly markup.
     *
     * @param string $url Video URL.
     * @param string $poster Optional poster image URL.
     * @return string
     */
    private static function render_self_hosted_video_hero( string $url, string $poster = '' ): string {
        $type = 'video/mp4';
        if ( preg_match( '~\.webm(\?.*)?$~i', $url ) ) {
            $type = 'video/webm';
        } elseif ( preg_match( '~\.(ogv|ogg)(\?.*)?$~i', $url ) ) {
            $type = 'video/ogg';
        }

        $poster_attr = $poster ? ' poster="' . esc_url( $poster ) . '"' : '';

        return '<div class="hero hero--video">' .
            '<video class="hero-media" autoplay muted playsinline webkit-playsinline loop preload="metadata" disablepictureinpicture src="' . esc_url( $url ) . '"' . $poster_attr . '>' .
            '<source src="' . esc_url( $url ) . '" type="' . esc_attr( $type ) . '">' .
            '</video></div>';
    }

    /**
     * Try to retrieve the generated poster image for a video attachment.
     *
     * @param int $attachment_id Attachment ID.
     * @return string
     */
    private static function get_attachment_poster_url( int $attachment_id ): string {
        $poster = wp_get_attachment_image_url( $attachment_id, 'full' );
        return $poster ?: '';
    }

    /**
     * Get theme directory.
     *
     * @return string Stylesheet directory path.
     */
    public static function get_theme_dir(): string {
        return get_stylesheet_directory();
    }

    /**
     * Get theme URI.
     *
     * @return string Stylesheet directory URI.
     */
    public static function get_theme_uri(): string {
        return get_stylesheet_directory_uri();
    }
}
