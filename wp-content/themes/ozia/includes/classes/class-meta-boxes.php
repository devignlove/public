<?php
declare(strict_types=1);

/**
 * OZI_Meta_Boxes class
 *
 * Handles all metabox registration, rendering, and saving for remorques CPT.
 * Includes: featured media, basic info, and advanced info metaboxes.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OZI_Meta_Boxes {

    const TEXT_DOMAIN = 'ozitheme';

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'save_post', [ $this, 'save_featured_media' ] );
        add_action( 'save_post', [ $this, 'save_basic_info' ] );
        add_action( 'save_post', [ $this, 'save_advanced_info' ] );
    }

    /**
     * Enqueue admin assets for the remorque edit screen only.
     *
     * @param string $hook_suffix Current admin hook.
     */
    public function enqueue_admin_assets( string $hook_suffix ): void {
        if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'remorques' ) {
            return;
        }

        $css = get_stylesheet_directory() . '/assets/css/admin-remorque.css';
        if ( file_exists( $css ) ) {
            wp_enqueue_style(
                'ozi-remorque-admin',
                get_stylesheet_directory_uri() . '/assets/css/admin-remorque.css',
                [],
                filemtime( $css )
            );
        }

    }

    /**
     * Register all metaboxes.
     */
    public function register_metaboxes(): void {
        // remove the default featured image box; we handle media ourselves
        remove_meta_box( 'postimagediv', 'remorques', 'side' );


        add_meta_box(
            'ozi_featured_media',
            __( 'Média mis en avant (image ou vidéo)', self::TEXT_DOMAIN ),
            [ $this, 'render_featured_media' ],
            'remorques',
            'side',
            'low'
        );

        add_meta_box(
            'ozi_meta_basic',
            __( 'Infos Remorque', self::TEXT_DOMAIN ),
            [ $this, 'render_basic_info' ],
            'remorques',
            'normal',
            'high'
        );

        add_meta_box(
            'ozi_meta_adv',
            __( 'Infos avancées', self::TEXT_DOMAIN ),
            [ $this, 'render_advanced_info' ],
            'remorques',
            'normal',
            'default'
        );

    }

    /**
     * Render featured media metabox.
     *
     * @param WP_Post $post Current post object.
     */
    public function render_featured_media( $post ): void {
        wp_enqueue_media();
        $thumb_id = (int) get_post_thumbnail_id( $post->ID );
        $url      = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';
        $mime     = $thumb_id ? get_post_mime_type( $thumb_id ) : '';
        $is_video = ( $mime && strpos( $mime, 'video/' ) === 0 );
        $accessoires = [];
        $accessoire_ids = [];

        wp_nonce_field( 'ozi_featured_media', 'ozi_featured_media_nonce' );
        ?>
        <div id="ozi-fm-wrap" style="display:grid;gap:8px">
            <div id="ozi-fm-preview" style="background:#111;border:1px solid #ddd;border-radius:6px;overflow:hidden;aspect-ratio:16/9;display:grid;place-items:center;color:#fff">
                <?php if ( $url ) : ?>
                    <?php if ( $is_video ) : ?>
                        <video src="<?php echo esc_url( $url ); ?>" muted playsinline controls style="width:100%;height:100%;object-fit:contain"></video>
                    <?php else : ?>
                        <img src="<?php echo esc_url( $url ); ?>" alt="" style="width:100%;height:100%;object-fit:contain"/>
                    <?php endif; ?>
                <?php else : ?>
                    <em><?php _e( 'Aucun média sélectionné', self::TEXT_DOMAIN ); ?></em>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="button" class="button" id="ozi-fm-choose"><?php _e( 'Choisir/Remplacer', self::TEXT_DOMAIN ); ?></button>
                <button type="button" class="button-link-delete" id="ozi-fm-clear"><?php _e( 'Supprimer', self::TEXT_DOMAIN ); ?></button>
            </div>
            <input type="hidden" id="_thumbnail_id" name="_thumbnail_id" value="<?php echo esc_attr( $thumb_id ); ?>">
        </div>
        <!-- Accessoires -->
        <h3 class="ozi-title"><?php _e( 'Accessoires compatibles', self::TEXT_DOMAIN ); ?></h3>
        <div class="ozi-box">
            <?php if ( $accessoires ) : ?>
                <div class="ozi-accessories-grid">
                    <?php foreach ( $accessoires as $accessoire ) : ?>
                        <?php
                        $is_checked = in_array( (int) $accessoire->ID, $accessoire_ids, true );
                        $thumb_url  = get_the_post_thumbnail_url( $accessoire->ID, 'medium' ) ?: '';
                        ?>
                        <div class="ozi-accessory-option">
                            <label>
                                <input type="checkbox" name="ozi_accessoires[]" value="<?php echo esc_attr( (string) $accessoire->ID ); ?>" <?php checked( $is_checked ); ?>>
                                <span>
                                    <strong><?php echo esc_html( get_the_title( $accessoire ) ); ?></strong>
                                    <?php if ( $thumb_url ) : ?>
                                        <span class="ozi-thumb" style="margin:8px 0 10px;max-width:220px;"><img src="<?php echo esc_url( $thumb_url ); ?>" alt=""></span>
                                    <?php endif; ?>
                                    <span class="ozi-muted"><?php echo esc_html( wp_trim_words( get_the_excerpt( $accessoire ), 18 ) ); ?></span>
                                </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="ozi-muted"><?php _e( 'Aucun accessoire publiÃ© pour le moment.', self::TEXT_DOMAIN ); ?></p>
            <?php endif; ?>
        </div>

        <?php
        wp_enqueue_script( 'ozi-metabox', get_stylesheet_directory_uri() . '/assets/js/metaboxes.js', [ 'wp-i18n' ], filemtime( get_stylesheet_directory() . '/assets/js/metaboxes.js' ), true );
    }

    /**
     * Save featured media (with nonce verification and permission check).
     *
     * @param int $post_id Post ID being saved.
     */
    public function save_featured_media( int $post_id ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( get_post_type( $post_id ) !== 'remorques' ) {
            return;
        }
        if ( ! isset( $_POST['ozi_featured_media_nonce'] ) || ! wp_verify_nonce( $_POST['ozi_featured_media_nonce'], 'ozi_featured_media' ) ) {
            return;
        }

        if ( isset( $_POST['_thumbnail_id'] ) ) {
            $tid = (int) $_POST['_thumbnail_id'];
            if ( $tid > 0 ) {
                update_post_meta( $post_id, '_thumbnail_id', $tid );
            } else {
                delete_post_meta( $post_id, '_thumbnail_id' );
            }
        }
    }

    /**
     * Render basic info metabox (price, weight, capacity, dimensions, features, video).
     *
     * @param WP_Post $post Current post object.
     */
    public function render_basic_info( $post ): void {
        wp_enqueue_media();

        $price      = (string) get_post_meta( $post->ID, '_ozi_price', true );
        $weight     = (string) get_post_meta( $post->ID, '_ozi_weight', true );
        $capacity   = (string) get_post_meta( $post->ID, '_ozi_capacity', true );
        $dimensions = (string) get_post_meta( $post->ID, '_ozi_dimensions', true );
        $features   = (string) get_post_meta( $post->ID, '_ozi_features', true );

        $video_use = get_post_meta( $post->ID, '_ozi_video_use', true ) === '1';
        $mp4_id    = (int) get_post_meta( $post->ID, '_ozi_video_id', true );
        $webm_id   = (int) get_post_meta( $post->ID, '_ozi_video_webm_id', true );
        $video_url = (string) get_post_meta( $post->ID, '_ozi_video_url', true );
        $mp4_url   = $mp4_id ? wp_get_attachment_url( $mp4_id ) : '';
        $webm_url  = $webm_id ? wp_get_attachment_url( $webm_id ) : '';

        wp_nonce_field( 'ozi_basic_info', 'ozi_basic_info_nonce' );
        ?>
        <div style="padding:10px 0;border-bottom:1px solid #e5e7eb;margin-bottom:10px">
            <label style="display:inline-flex;gap:8px;align-items:center">
                <input type="checkbox" name="ozi_video_use" value="1" <?php checked( $video_use, true ); ?>>
                <strong><?php _e( 'Activer une VIDÉO en hero si le média mis en avant n\'est pas une vidéo', self::TEXT_DOMAIN ); ?></strong>
            </label>
            <div style="display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center;margin-top:8px">
                <div><strong>Vidéo MP4</strong><br><small><?php echo $mp4_url ? esc_html( $mp4_url ) : '—'; ?></small></div>
                <button type="button" class="button ozi-pick-video" data-target="ozi_video_id"><?php esc_html_e( 'Choisir/Remplacer', self::TEXT_DOMAIN ); ?></button>
                <button type="button" class="button-link-delete ozi-clear-video" data-target="ozi_video_id"><?php esc_html_e( 'Supprimer', self::TEXT_DOMAIN ); ?></button>
                <input type="hidden" id="ozi_video_id" name="ozi_video_id" value="<?php echo esc_attr( $mp4_id ); ?>">
            </div>
            <div style="display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center;margin-top:8px">
                <div><strong>Vidéo WebM (optionnel)</strong><br><small><?php echo $webm_url ? esc_html( $webm_url ) : '—'; ?></small></div>
                <button type="button" class="button ozi-pick-video" data-target="ozi_video_webm_id"><?php esc_html_e( 'Choisir/Remplacer', self::TEXT_DOMAIN ); ?></button>
                <button type="button" class="button-link-delete ozi-clear-video" data-target="ozi_video_webm_id"><?php esc_html_e( 'Supprimer', self::TEXT_DOMAIN ); ?></button>
                <input type="hidden" id="ozi_video_webm_id" name="ozi_video_webm_id" value="<?php echo esc_attr( $webm_id ); ?>">
            </div>
            <p style="margin-top:8px"><label><?php _e( 'URL vidéo (YouTube / Vimeo / MP4)', self::TEXT_DOMAIN ); ?><br>
                <input type="url" name="ozi_video_url" value="<?php echo esc_attr( $video_url ); ?>" style="width:100%"></label></p>
        </div>

        <p><label><?php _e( 'Prix (€ TTC):', self::TEXT_DOMAIN ); ?><br>
                <input type="text" name="ozi_price" value="<?php echo esc_attr( $price ); ?>" style="width:50%"></label></p>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
            <p><label><?php _e( 'Poids (kg):', self::TEXT_DOMAIN ); ?><br>
                    <input type="text" name="ozi_weight" value="<?php echo esc_attr( $weight ); ?>" style="width:100%"></label></p>
            <p><label><?php _e( 'Charge utile (kg):', self::TEXT_DOMAIN ); ?><br>
                    <input type="text" name="ozi_capacity" value="<?php echo esc_attr( $capacity ); ?>" style="width:100%"></label></p>
            <p><label><?php _e( 'Dimensions (cm²)', self::TEXT_DOMAIN ); ?><br>
                    <input type="text" name="ozi_dimensions" value="<?php echo esc_attr( $dimensions ); ?>" style="width:100%"></label></p>
        </div>

        <p><label><?php _e( 'Caractéristiques (liste rapide, séparées par virgules — affichées sur le slide)', self::TEXT_DOMAIN ); ?><br>
                <textarea name="ozi_features" rows="2" style="width:100%"><?php echo esc_textarea( $features ); ?></textarea></label></p>

        <?php
        wp_enqueue_script( 'ozi-metabox', get_stylesheet_directory_uri() . '/assets/js/metaboxes.js', [ 'wp-i18n' ], filemtime( get_stylesheet_directory() . '/assets/js/metaboxes.js' ), true );
    }

    /**
     * Save basic info metabox data.
     *
     * @param int $post_id Post ID being saved.
     */
    public function save_basic_info( int $post_id ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( get_post_type( $post_id ) !== 'remorques' ) {
            return;
        }
        if ( ! isset( $_POST['ozi_basic_info_nonce'] ) || ! wp_verify_nonce( $_POST['ozi_basic_info_nonce'], 'ozi_basic_info' ) ) {
            return;
        }

        $map = [
            'ozi_price'      => '_ozi_price',
            'ozi_weight'     => '_ozi_weight',
            'ozi_capacity'   => '_ozi_capacity',
            'ozi_dimensions' => '_ozi_dimensions',
            'ozi_features'   => '_ozi_features',
        ];

        foreach ( $map as $field => $meta ) {
            if ( ! isset( $_POST[ $field ] ) ) {
                continue;
            }
            $val = ( $field === 'ozi_features' )
                ? sanitize_textarea_field( $_POST[ $field ] )
                : sanitize_text_field( $_POST[ $field ] );
            update_post_meta( $post_id, $meta, $val );
        }

        update_post_meta( $post_id, '_ozi_video_use', isset( $_POST['ozi_video_use'] ) ? '1' : '0' );

        if ( isset( $_POST['ozi_video_id'] ) ) {
            update_post_meta( $post_id, '_ozi_video_id', (int) $_POST['ozi_video_id'] );
        }
        if ( isset( $_POST['ozi_video_webm_id'] ) ) {
            update_post_meta( $post_id, '_ozi_video_webm_id', (int) $_POST['ozi_video_webm_id'] );
        }
        if ( isset( $_POST['ozi_video_url'] ) ) {
            update_post_meta( $post_id, '_ozi_video_url', esc_url_raw( $_POST['ozi_video_url'] ) );
        }
    }

    /**
     * Render advanced info metabox (intros, infos sections, tech specs, reviews, FAQ).
     *
     * @param WP_Post $post Current post object.
     */
    public function render_advanced_info( $post ): void {
        wp_enqueue_media();

        $intros           = get_post_meta( $post->ID, '_ozi_intros', true );
        $intros_label     = get_post_meta( $post->ID, '_ozi_intros_label', true );
        $infos            = (array) get_post_meta( $post->ID, '_ozi_infos', true );
        $bg_tech_full_id  = (int) get_post_meta( $post->ID, '_ozi_bg_tech_full_id', true );
        $bg_tech_full_url = $bg_tech_full_id ? wp_get_attachment_url( $bg_tech_full_id ) : '';
        $tech_car         = (array) get_post_meta( $post->ID, '_ozi_tech_carac', true );
        $tech_eqp         = (array) get_post_meta( $post->ID, '_ozi_tech_equip', true );
        $reviews          = (array) get_post_meta( $post->ID, '_ozi_reviews', true );
        $faq              = (array) get_post_meta( $post->ID, '_ozi_faq', true );
        $buy_link         = (string) get_post_meta( $post->ID, '_ozi_buy_link', true );
        $buy_lbl          = (string) get_post_meta( $post->ID, '_ozi_buy_label', true );
        $accessoire_ids   = array_values( array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_ozi_accessoires', true ) ) ) );
        $accessoires      = get_posts( [
            'post_type'      => 'accessoires',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        // Populate with defaults if empty
        $tech_car = $this->populate_defaults( $tech_car, $this->get_default_characteristics() );
        $tech_eqp = $this->populate_defaults( $tech_eqp, $this->get_default_equipment() );
        $reviews  = $this->populate_defaults( $reviews, $this->get_default_reviews() );
        $faq      = $this->populate_defaults( $faq, $this->get_default_faq() );

        wp_nonce_field( 'ozi_meta_adv', 'ozi_meta_adv_nonce' );
        ?>
        <style>
            .ozi-box { border:1px solid #e5e7eb; border-radius:8px; padding:12px; margin:10px 0; }
            .ozi-title { margin:12px 0 6px; font-weight:600; }
            .ozi-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
            #ozi-infos .item { display:grid; grid-template-columns:minmax(0,2fr) minmax(260px,1fr); gap:12px; }
            #ozi-infos .item .ozi-column p { margin:0 0 8px; }
            .ozi-list>.item { border-top:1px dashed #e5e7eb; padding-top:10px; margin-top:10px; position:relative; }
            .ozi-list>.item:first-child { border-top:0; margin-top:0; padding-top:0; }
            .ozi-actions { display:flex; gap:8px; margin-top:8px; flex-wrap:wrap; }
            .ozi-thumb { width:100%; aspect-ratio:16/9; background:#0b0b0b; border-radius:6px; overflow:hidden; }
            .ozi-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
            .ozi-del { position:absolute; right:0; top:0; }
            .ozi-muted { color:#64748b; font-size:12px; }
            .ozi-accessories-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; }
            .ozi-accessory-option { border:1px solid #e5e7eb; border-radius:12px; padding:12px; background:#fff; }
            .ozi-accessory-option label { display:flex; gap:10px; align-items:flex-start; }
            .ozi-accessory-option input { margin-top:3px; }
            .ozi-accessory-option strong { display:block; margin-bottom:4px; }
        </style>

        <!-- Intros Section -->
        <h3 class="ozi-title"><?php _e( 'Intros', self::TEXT_DOMAIN ); ?></h3>
        <div class="ozi-box">
            <div class="ozi-row">
                <p>
                    <label><?php _e( 'Texte de la section', self::TEXT_DOMAIN ); ?><br>
                        <textarea name="ozi_intros" rows="3" style="width:100%"><?php echo esc_textarea( $intros ); ?></textarea>
                    </label>
                </p>
                <p>
                    <label><?php _e( 'Libellé du bouton', self::TEXT_DOMAIN ); ?><br>
                        <input type="text" name="ozi_intros_label" value="<?php echo esc_attr( $intros_label ); ?>" style="width:100%">
                    </label>
                    <small class="ozi-muted"><?php _e( 'Ce bouton ouvre les infos en modal sur le site.', self::TEXT_DOMAIN ); ?></small>
                </p>
            </div>
        </div>

        <!-- Infos Section -->
        <h3 class="ozi-title"><?php _e( 'Infos', self::TEXT_DOMAIN ); ?></h3>
        <div class="ozi-box">
            <div id="ozi-infos" class="ozi-list">
                <?php foreach ( $infos as $i => $s ) :
                    $img_id   = (int) ( $s['image_id'] ?? 0 );
                    $img_url  = $img_id ? wp_get_attachment_url( $img_id ) : '';
                    ?>
                    <div class="item" data-i="<?php echo $i; ?>">
                        <button type="button" class="button-link-delete ozi-del" data-group="infos"><?php _e( 'Supprimer', self::TEXT_DOMAIN ); ?></button>
                        <div class="ozi-column">
                            <p><label><?php _e( 'Titre', self::TEXT_DOMAIN ); ?><br>
                                <input type="text" name="ozi_infos[<?php echo $i; ?>][title]" value="<?php echo esc_attr( $s['title'] ?? '' ); ?>" style="width:100%"></label></p>
                            <p><label><?php _e( 'Texte', self::TEXT_DOMAIN ); ?><br>
                                <textarea name="ozi_infos[<?php echo $i; ?>][text]" rows="2" style="width:100%"><?php echo esc_textarea( $s['text'] ?? '' ); ?></textarea></label></p>
                        </div>
                        <div class="ozi-column">
                            <div>
                                <div class="ozi-thumb" id="ozi-sec-prev-<?php echo $i; ?>"><?php if ( $img_url ) : ?><img src="<?php echo esc_url( $img_url ); ?>" alt=""><?php endif; ?></div>
                                <div class="ozi-muted"><?php echo $img_url ? esc_html( $img_url ) : '—'; ?></div>
                            </div>
                            <div class="ozi-actions">
                                <button type="button" class="button ozi-pick" data-target="ozi-sec-id-<?php echo $i; ?>" data-prev="ozi-sec-prev-<?php echo $i; ?>"><?php _e( 'Choisir image', self::TEXT_DOMAIN ); ?></button>
                                <button type="button" class="button-link-delete ozi-clear" data-target="ozi-sec-id-<?php echo $i; ?>" data-prev="ozi-sec-prev-<?php echo $i; ?>"><?php _e( 'Supprimer', self::TEXT_DOMAIN ); ?></button>
                                <input type="hidden" id="ozi-sec-id-<?php echo $i; ?>" name="ozi_infos[<?php echo $i; ?>][image_id]" value="<?php echo $img_id; ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button button-primary" id="ozi-add-infos"><?php _e( 'Ajouter une info', self::TEXT_DOMAIN ); ?></button></p>
        </div>

        <!-- Background Tech Full -->
        <h3 class="ozi-title"><?php _e( 'Background Tech Full', self::TEXT_DOMAIN ); ?></h3>
        <div class="ozi-box">
            <div>
                <div class="ozi-thumb" id="ozi-bg-techfull-prev"><?php if ( $bg_tech_full_url ) : ?><img src="<?php echo esc_url( $bg_tech_full_url ); ?>" alt=""><?php endif; ?></div>
                <div class="ozi-muted" id="ozi-bg-techfull-url"><?php echo $bg_tech_full_url ? esc_html( $bg_tech_full_url ) : '—'; ?></div>
                <div class="ozi-actions">
                    <button type="button" class="button ozi-pick-bg" data-target="ozi_bg_tech_full_id" data-prev="ozi-bg-techfull-prev" data-url="ozi-bg-techfull-url"><?php _e( 'Choisir image', self::TEXT_DOMAIN ); ?></button>
                    <button type="button" class="button-link-delete ozi-clear-bg" data-target="ozi_bg_tech_full_id" data-prev="ozi-bg-techfull-prev" data-url="ozi-bg-techfull-url"><?php _e( 'Supprimer', self::TEXT_DOMAIN ); ?></button>
                    <input type="hidden" id="ozi_bg_tech_full_id" name="ozi_bg_tech_full_id" value="<?php echo esc_attr( $bg_tech_full_id ); ?>">
                </div>
            </div>
        </div>

        <!-- Tech Characteristics -->
        <h3 class="ozi-title"><?php _e( 'Caractéristiques', self::TEXT_DOMAIN ); ?></h3>
        <div class="ozi-box">
            <div id="ozi-tech-car" class="ozi-list">
                <?php foreach ( $tech_car as $i => $r ) : ?>
                    <div class="item" data-i="<?php echo $i; ?>">
                        <button type="button" class="button-link-delete ozi-del" data-group="tech-car"><?php _e( 'Supprimer', self::TEXT_DOMAIN ); ?></button>
                        <div class="ozi-row">
                            <p><input type="text" name="ozi_tech_carac[<?php echo $i; ?>][label]" value="<?php echo esc_attr( $r['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Nom', self::TEXT_DOMAIN ); ?>" style="width:100%"></p>
                            <p><input type="text" name="ozi_tech_carac[<?php echo $i; ?>][value]" value="<?php echo esc_attr( $r['value'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Valeur', self::TEXT_DOMAIN ); ?>" style="width:100%"></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" id="ozi-add-tech-car"><?php _e( 'Ajouter une caractéristique', self::TEXT_DOMAIN ); ?></button></p>
        </div>

        <!-- Tech Equipment -->
        <h3 class="ozi-title"><?php _e( 'Équipement', self::TEXT_DOMAIN ); ?></h3>
        <div class="ozi-box">
            <div id="ozi-tech-eqp" class="ozi-list">
                <?php foreach ( $tech_eqp as $i => $r ) : ?>
                    <div class="item" data-i="<?php echo $i; ?>">
                        <button type="button" class="button-link-delete ozi-del" data-group="tech-eqp"><?php _e( 'Supprimer', self::TEXT_DOMAIN ); ?></button>
                        <div class="ozi-row">
                            <p><input type="text" name="ozi_tech_equip[<?php echo $i; ?>][label]" value="<?php echo esc_attr( $r['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Nom', self::TEXT_DOMAIN ); ?>" style="width:100%"></p>
                            <p><input type="text" name="ozi_tech_equip[<?php echo $i; ?>][value]" value="<?php echo esc_attr( $r['value'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Valeur', self::TEXT_DOMAIN ); ?>" style="width:100%"></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" id="ozi-add-tech-eqp"><?php _e( 'Ajouter un équipement', self::TEXT_DOMAIN ); ?></button></p>
        </div>

        <!-- Reviews -->
        <h3 class="ozi-title"><?php _e( 'Avis', self::TEXT_DOMAIN ); ?></h3>
        <div class="ozi-box">
            <div id="ozi-reviews" class="ozi-list">
                <?php foreach ( $reviews as $i => $r ) : ?>
                    <div class="item" data-i="<?php echo $i; ?>">
                        <button type="button" class="button-link-delete ozi-del" data-group="reviews"><?php _e( 'Supprimer', self::TEXT_DOMAIN ); ?></button>
                        <div class="ozi-row">
                            <p><input type="text" name="ozi_reviews[<?php echo $i; ?>][author]" value="<?php echo esc_attr( $r['author'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Auteur', self::TEXT_DOMAIN ); ?>" style="width:100%"></p>
                            <p><input type="text" name="ozi_reviews[<?php echo $i; ?>][text]" value="<?php echo esc_attr( $r['text'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Commentaire', self::TEXT_DOMAIN ); ?>" style="width:100%"></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" id="ozi-add-review"><?php _e( 'Ajouter un avis', self::TEXT_DOMAIN ); ?></button></p>
        </div>

        <!-- FAQ -->
        <h3 class="ozi-title"><?php _e( 'FAQ', self::TEXT_DOMAIN ); ?></h3>
        <div class="ozi-box">
            <div id="ozi-faq" class="ozi-list">
                <?php foreach ( $faq as $i => $q ) : ?>
                    <div class="item" data-i="<?php echo $i; ?>">
                        <button type="button" class="button-link-delete ozi-del" data-group="faq"><?php _e( 'Supprimer', self::TEXT_DOMAIN ); ?></button>
                        <div class="ozi-row">
                            <p><input type="text" name="ozi_faq[<?php echo $i; ?>][q]" value="<?php echo esc_attr( $q['q'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Question', self::TEXT_DOMAIN ); ?>" style="width:100%"></p>
                            <p><input type="text" name="ozi_faq[<?php echo $i; ?>][a]" value="<?php echo esc_attr( $q['a'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Réponse', self::TEXT_DOMAIN ); ?>" style="width:100%"></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" id="ozi-add-faq"><?php _e( 'Ajouter une Q/R', self::TEXT_DOMAIN ); ?></button></p>
        </div>

        <!-- CTA -->
        <h3 class="ozi-title"><?php _e( 'Appel à l\'action (CTA)', self::TEXT_DOMAIN ); ?></h3>
        <div class="ozi-box">
            <div class="ozi-row">
                <p><label><?php _e( 'Lien achat', self::TEXT_DOMAIN ); ?><br><input type="url" name="ozi_buy_link" value="<?php echo esc_attr( $buy_link ); ?>" style="width:100%"></label></p>
                <p><label><?php _e( 'Libellé bouton', self::TEXT_DOMAIN ); ?><br><input type="text" name="ozi_buy_label" value="<?php echo esc_attr( $buy_lbl ); ?>" style="width:100%"></label></p>
            </div>
        </div>

        <h3 class="ozi-title"><?php _e( 'Accessoires compatibles', self::TEXT_DOMAIN ); ?></h3>
        <div class="ozi-box">
            <?php if ( $accessoires ) : ?>
                <div class="ozi-accessories-grid">
                    <?php foreach ( $accessoires as $accessoire ) : ?>
                        <?php
                        $is_checked = in_array( (int) $accessoire->ID, $accessoire_ids, true );
                        $thumb_url  = get_the_post_thumbnail_url( $accessoire->ID, 'medium' ) ?: '';
                        ?>
                        <div class="ozi-accessory-option">
                            <label>
                                <input type="checkbox" name="ozi_accessoires[]" value="<?php echo esc_attr( (string) $accessoire->ID ); ?>" <?php checked( $is_checked ); ?>>
                                <span>
                                    <strong><?php echo esc_html( get_the_title( $accessoire ) ); ?></strong>
                                    <?php if ( $thumb_url ) : ?>
                                        <span class="ozi-thumb" style="margin:8px 0 10px;max-width:220px;"><img src="<?php echo esc_url( $thumb_url ); ?>" alt=""></span>
                                    <?php endif; ?>
                                    <span class="ozi-muted"><?php echo esc_html( wp_trim_words( get_the_excerpt( $accessoire ), 18 ) ); ?></span>
                                </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="ozi-muted"><?php _e( 'Aucun accessoire publiÃ© pour le moment.', self::TEXT_DOMAIN ); ?></p>
            <?php endif; ?>
        </div>

        <?php
        wp_enqueue_script( 'ozi-metabox', get_stylesheet_directory_uri() . '/assets/js/metaboxes.js', [ 'wp-i18n' ], filemtime( get_stylesheet_directory() . '/assets/js/metaboxes.js' ), true );
    }

    /**
     * Render accessory selection metabox.
     *
     * @param WP_Post $post Current post object.
     */
    public function render_accessoires_meta( $post ): void {
        $accessoire_ids = array_values( array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_ozi_accessoires', true ) ) ) );
        $accessoires    = get_posts( [
            'post_type'      => 'accessoires',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );
        ?>
        <?php if ( $accessoires ) : ?>
            <div class="ozi-accessories-grid">
                <?php foreach ( $accessoires as $accessoire ) : ?>
                    <?php
                    $is_checked = in_array( (int) $accessoire->ID, $accessoire_ids, true );
                    $thumb_url  = get_the_post_thumbnail_url( $accessoire->ID, 'medium' ) ?: '';
                    ?>
                    <div class="ozi-accessory-option">
                        <label>
                            <input type="checkbox" name="ozi_accessoires[]" value="<?php echo esc_attr( (string) $accessoire->ID ); ?>" <?php checked( $is_checked ); ?>>
                            <span>
                                <strong><?php echo esc_html( get_the_title( $accessoire ) ); ?></strong>
                                <?php if ( $thumb_url ) : ?>
                                    <span class="ozi-thumb" style="margin:8px 0 10px;max-width:220px;"><img src="<?php echo esc_url( $thumb_url ); ?>" alt=""></span>
                                <?php endif; ?>
                                <span class="ozi-muted"><?php echo esc_html( wp_trim_words( get_the_excerpt( $accessoire ), 18 ) ); ?></span>
                            </span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="ozi-muted"><?php _e( 'Aucun accessoire publiÃ© pour le moment.', self::TEXT_DOMAIN ); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Save advanced info metabox data.
     *
     * @param int $post_id Post ID being saved.
     */
    public function save_advanced_info( int $post_id ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( get_post_type( $post_id ) !== 'remorques' ) {
            return;
        }
        if ( ! isset( $_POST['ozi_meta_adv_nonce'] ) || ! wp_verify_nonce( $_POST['ozi_meta_adv_nonce'], 'ozi_meta_adv' ) ) {
            return;
        }

        // Intros
        if ( isset( $_POST['ozi_intros'] ) ) {
            update_post_meta( $post_id, '_ozi_intros', sanitize_textarea_field( $_POST['ozi_intros'] ) );
        }
        if ( isset( $_POST['ozi_intros_label'] ) ) {
            update_post_meta( $post_id, '_ozi_intros_label', sanitize_text_field( $_POST['ozi_intros_label'] ) );
        }

        // Infos section
        $infos = [];
        if ( ! empty( $_POST['ozi_infos'] ) && is_array( $_POST['ozi_infos'] ) ) {
            foreach ( $_POST['ozi_infos'] as $row ) {
                $title = isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '';
                $text  = isset( $row['text'] ) ? sanitize_textarea_field( $row['text'] ) : '';
                $img   = isset( $row['image_id'] ) ? (int) $row['image_id'] : 0;
                if ( $title || $text || $img ) {
                    $infos[] = [
                        'title'    => $title,
                        'text'     => $text,
                        'image_id' => $img,
                    ];
                }
            }
        }
        update_post_meta( $post_id, '_ozi_infos', $infos );

        // Background Tech Full
        if ( isset( $_POST['ozi_bg_tech_full_id'] ) ) {
            $id = (int) $_POST['ozi_bg_tech_full_id'];
            if ( $id > 0 ) {
                update_post_meta( $post_id, '_ozi_bg_tech_full_id', $id );
            } else {
                delete_post_meta( $post_id, '_ozi_bg_tech_full_id' );
            }
        }

        // Technique characteristics
        $car = [];
        if ( ! empty( $_POST['ozi_tech_carac'] ) && is_array( $_POST['ozi_tech_carac'] ) ) {
            foreach ( $_POST['ozi_tech_carac'] as $r ) {
                $label = isset( $r['label'] ) ? sanitize_text_field( $r['label'] ) : '';
                $value = isset( $r['value'] ) ? sanitize_text_field( $r['value'] ) : '';
                if ( $label || $value ) {
                    $car[] = [ 'label' => $label, 'value' => $value ];
                }
            }
        }
        update_post_meta( $post_id, '_ozi_tech_carac', $car );

        // Technique equipment
        $eqp = [];
        if ( ! empty( $_POST['ozi_tech_equip'] ) && is_array( $_POST['ozi_tech_equip'] ) ) {
            foreach ( $_POST['ozi_tech_equip'] as $r ) {
                $label = isset( $r['label'] ) ? sanitize_text_field( $r['label'] ) : '';
                $value = isset( $r['value'] ) ? sanitize_text_field( $r['value'] ) : '';
                if ( $label || $value ) {
                    $eqp[] = [ 'label' => $label, 'value' => $value ];
                }
            }
        }
        update_post_meta( $post_id, '_ozi_tech_equip', $eqp );

        // Reviews
        $reviews = [];
        if ( ! empty( $_POST['ozi_reviews'] ) && is_array( $_POST['ozi_reviews'] ) ) {
            foreach ( $_POST['ozi_reviews'] as $r ) {
                $author = isset( $r['author'] ) ? sanitize_text_field( $r['author'] ) : '';
                $text   = isset( $r['text'] ) ? sanitize_text_field( $r['text'] ) : '';
                if ( $author || $text ) {
                    $reviews[] = [ 'author' => $author, 'text' => $text ];
                }
            }
        }
        update_post_meta( $post_id, '_ozi_reviews', $reviews );

        // FAQ
        $faq = [];
        if ( ! empty( $_POST['ozi_faq'] ) && is_array( $_POST['ozi_faq'] ) ) {
            foreach ( $_POST['ozi_faq'] as $r ) {
                $q = isset( $r['q'] ) ? sanitize_text_field( $r['q'] ) : '';
                $a = isset( $r['a'] ) ? sanitize_text_field( $r['a'] ) : '';
                if ( $q || $a ) {
                    $faq[] = [ 'q' => $q, 'a' => $a ];
                }
            }
        }
        update_post_meta( $post_id, '_ozi_faq', $faq );

        // CTA
        if ( isset( $_POST['ozi_buy_link'] ) ) {
            update_post_meta( $post_id, '_ozi_buy_link', esc_url_raw( $_POST['ozi_buy_link'] ) );
        }
        if ( isset( $_POST['ozi_buy_label'] ) ) {
            update_post_meta( $post_id, '_ozi_buy_label', sanitize_text_field( $_POST['ozi_buy_label'] ) );
        }

        $accessoire_ids = [];
        if ( ! empty( $_POST['ozi_accessoires'] ) && is_array( $_POST['ozi_accessoires'] ) ) {
            $accessoire_ids = array_values( array_filter( array_map( 'absint', wp_unslash( $_POST['ozi_accessoires'] ) ) ) );
        }
        update_post_meta( $post_id, '_ozi_accessoires', $accessoire_ids );
    }

    /**
     * Populate array with defaults if empty.
     *
     * @param array $data Existing data.
     * @param array $defaults Default data.
     * @return array Populated data.
     */
    private function populate_defaults( array $data, array $defaults ): array {
        if ( empty( $data ) || ! $this->has_meaningful_data( $data ) ) {
            return $defaults;
        }
        return $data;
    }

    /**
     * Check if array has meaningful data (not all empty values).
     *
     * @param array $data Data to check.
     * @return bool True if data is meaningful.
     */
    private function has_meaningful_data( array $data ): bool {
        foreach ( $data as $row ) {
            foreach ( $row as $value ) {
                if ( is_string( $value ) && trim( $value ) !== '' ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get default characteristics labels.
     *
     * @return array Default characteristics.
     */
    private function get_default_characteristics(): array {
        return [
            [ 'label' => 'Dimensions ouvertes (cm)', 'value' => '' ],
            [ 'label' => 'Dimensions pliées (cm)', 'value' => '' ],
            [ 'label' => 'Temps de pliage et de dépliage', 'value' => '' ],
            [ 'label' => 'Poids à vide', 'value' => '' ],
            [ 'label' => 'Charge utile', 'value' => '' ],
            [ 'label' => 'Poids total autorisé en charge', 'value' => '' ],
            [ 'label' => 'Hauteur de chargement', 'value' => '' ],
            [ 'label' => 'Châssis tubulaire', 'value' => '' ],
            [ 'label' => 'Protection de suspension', 'value' => '' ],
            [ 'label' => 'Homologation CE', 'value' => '' ],
            [ 'label' => 'Fabrication', 'value' => '' ],
            [ 'label' => 'Garantie structure', 'value' => '' ],
        ];
    }

    /**
     * Get default equipment labels.
     *
     * @return array Default equipment.
     */
    private function get_default_equipment(): array {
        return [
            [ 'label' => 'Rail complet 240 mm', 'value' => '' ],
            [ 'label' => 'Rampe d\'accès 240 mm', 'value' => '' ],
            [ 'label' => 'Sabot de blocage de roue inox', 'value' => '' ],
            [ 'label' => 'Boîtier d\'attache AL-KO', 'value' => '' ],
            [ 'label' => 'Moyeux AL-KO', 'value' => '' ],
            [ 'label' => 'Roue 13″', 'value' => '' ],
            [ 'label' => 'Garde-boue', 'value' => '' ],
            [ 'label' => 'Feux LED', 'value' => '' ],
            [ 'label' => 'Faisceau', 'value' => '' ],
            [ 'label' => 'Prise 7 broches', 'value' => '' ],
            [ 'label' => 'Traverse pour sangler la moto', 'value' => '' ],
            [ 'label' => 'Marche pied', 'value' => '' ],
        ];
    }

    /**
     * Get default reviews.
     *
     * @return array Default reviews.
     */
    private function get_default_reviews(): array {
        return [
            [
                'author' => 'Fabrice',
                'text'   => "Le processus d'achat a été parfait. La livraison se fait très rapidement et efficacement. Quant à la remorque elle-même, les finitions sont parfaites, les soudures vraiment bien faites. Le métal est clairement de qualité. Tout est impeccablement graissé et la remorque se déplie et se replie facilement. Monter la moto dessus est une formalité ! Bref, un produit à la qualité et au packaging irréprochables. Certes, cela peut paraître un peu cher, mais c'est sans commune mesure avec la concurrence !",
            ],
            [
                'author' => 'Pierre',
                'text'   => 'Excellent produit, je suis vraiment ravi de mon achat. Qualité de fabrication remarquable, cinématique de la remorque particulièrement bien étudiée ; en bref, c\'est un produit d\'exception.',
            ],
            [
                'author' => 'Stéphanie',
                'text'   => 'Nous avons acheté cette remorque car nous sommes en appartement et nous n\'avons pas de garage. Cela nous permet de la ranger à l\'intérieur. Nous sommes très satisfaits de notre achat. Livraison rapide et vendeur très sérieux qui répond à toutes les questions. Remorque facile à monter.',
            ],
        ];
    }

    /**
     * Get default FAQ.
     *
     * @return array Default FAQ.
     */
    private function get_default_faq(): array {
        return [
            [ 'q' => 'Faut-il un permis spécial ?', 'a' => 'Non, une voiture classique suffit.' ],
            [ 'q' => 'Convient-elle à ma moto ?', 'a' => 'Oui, jusqu\'à 220 kg. Le sabot est ajustable pour convenir à toutes les dimensions.' ],
            [ 'q' => 'Est-ce facile à plier ?', 'a' => 'Oui, en moins de 3 minutes et sans outil. La remorque se plie et se range facilement. Une vidéo explicative est disponible.' ],
            [ 'q' => 'Est-ce facile à déplacer ?', 'a' => 'Oui, et d\'autant plus avec les accessoires proposés par OZI Concept.' ],
            [ 'q' => 'Une carte grise est-elle nécessaire pour la remorque ?', 'a' => 'Pas pour ce modèle.' ],
        ];
    }
}

new OZI_Meta_Boxes();
