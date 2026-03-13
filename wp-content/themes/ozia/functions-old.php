<?php
declare(strict_types=1);

/**
 * Theme: Ozi Showcase — functions.php
 *
 * Core theme functionality (2026 standards):
 *   • CPT remorques + accessoires
 *   • Featured media (image OR video)
 *   • Basic & advanced metaboxes with repeaters
 *   • Gutenberg block "ozi/showcase" (SSR with OZI_DATA)
 *   • Deep‑link slider at /showcase/{slug}
 *
 * This file follows the latest WordPress PHP coding standards,
 * uses strict typing and meaningful docblocks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------------------
 * Helpers thème
 * ----------------------------------------------------- */
function ozi_theme_dir(){ return get_stylesheet_directory(); }
function ozi_theme_uri(){ return get_stylesheet_directory_uri(); }

// === Header: logo + menus ===
/**
 * Configure theme supports and navigation menus.
 *
 * Adds title-tag support, custom logo settings and registers
 * the primary and footer menu locations.
 */
function ozi_setup_theme(): void {
    add_theme_support( 'title-tag' );

    // Custom logo, editable via Appearance → Customize → Site Identity
    add_theme_support( 'custom-logo', [
        'height'      => 64,
        'width'       => 180,
        'flex-width'  => true,
        'flex-height' => true,
    ] );

    register_nav_menus( [
        'primary' => __( 'Menu principal', 'ozitheme' ),
        'footer'  => __( 'Menu pied de page', 'ozitheme' ),
    ] );
}
add_action( 'after_setup_theme', 'ozi_setup_theme' );

// JS du burger (mobile)
/**
 * Enqueue mobile header script when file exists.
 */
function ozi_enqueue_header_script(): void {
    $js = ozi_theme_dir() . '/assets/js/header.js';
    if ( file_exists( $js ) ) {
        wp_enqueue_script(
            'ozi-header',
            ozi_theme_uri() . '/assets/js/header.js',
            [],
            filemtime( $js ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'ozi_enqueue_header_script' );


/* -------------------------------------------------------
 * Front assets (CSS/JS du slider)
 * ----------------------------------------------------- */
/**
 * Enqueue public-facing styles and scripts for the slider.
 */
function ozi_enqueue_front_assets(): void {
    wp_enqueue_style( 'ozi-theme-style', get_stylesheet_uri(), [], null );

    $css = ozi_theme_dir() . '/assets/css/showcase.css';
    if ( file_exists( $css ) ) {
        wp_enqueue_style(
            'ozi-front',
            ozi_theme_uri() . '/assets/css/showcase.css',
            [],
            filemtime( $css )
        );
    }

    $js = ozi_theme_dir() . '/assets/js/showcase.js';
    if ( file_exists( $js ) ) {
        wp_enqueue_script(
            'ozi-front',
            ozi_theme_uri() . '/assets/js/showcase.js',
            [],
            filemtime( $js ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'ozi_enqueue_front_assets' );

/* -------------------------------------------------------
 * Upload mimes
 * ----------------------------------------------------- */
/**
 * Allow additional file types for media uploads.
 *
 * @param array $m Existing mime types.
 * @return array Modified mime types.
 */
function ozi_allow_extra_mimes( array $m ): array {
    $m['webm'] = 'video/webm';
    $m['ogv']  = 'video/ogg';
    $m['svg']  = 'image/svg+xml';
    return $m;
}
add_filter( 'upload_mimes', 'ozi_allow_extra_mimes' );

/* -------------------------------------------------------
 * CPT remorques
 * ----------------------------------------------------- */
/**
 * Register the "remorques" custom post type.
 */
function ozi_register_remorques(): void {
    register_post_type(
        'remorques',
        [
            'labels'       => [
                'name'          => __( 'Remorques', 'ozitheme' ),
                'singular_name' => __( 'Remorque', 'ozitheme' ),
                'add_new_item'  => __( 'Ajouter une remorque', 'ozitheme' ),
                'edit_item'     => __( 'Modifier la remorque', 'ozitheme' ),
            ],
            'public'       => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-car',
            'supports'     => [ 'title', 'thumbnail', 'page-attributes' ],
            'has_archive'  => true,
            'rewrite'      => [ 'slug' => 'remorques' ],
        ]
    );

    // editor not needed for this CPT
    remove_post_type_support( 'remorques', 'editor' );
}
add_action( 'init', 'ozi_register_remorques' );

/* -------------------------------------------------------
 * CPT accessoires
 * ----------------------------------------------------- */
/**
 * Register the "accessoires" custom post type.
 */
function ozi_register_accessoires(): void {
    register_post_type(
        'accessoires',
        [
            'labels'       => [
                'name'          => __( 'Accessoires', 'ozitheme' ),
                'singular_name' => __( 'Accessoire', 'ozitheme' ),
                'add_new_item'  => __( 'Ajouter un accessoire', 'ozitheme' ),
                'edit_item'     => __( 'Modifier l’accessoire', 'ozitheme' ),
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
add_action( 'init', 'ozi_register_accessoires' );

/* -------------------------------------------------------
 * Slider deep-link: /showcase/{slug-produit}
 * ----------------------------------------------------- */
/**
 * Allow "ozi_slide" as a public query var.
 *
 * @param array $vars Existing vars.
 * @return array Modified vars.
 */
function ozi_query_vars( array $vars ): array {
    $vars[] = 'ozi_slide';
    return $vars;
}
add_filter( 'query_vars', 'ozi_query_vars' );

/**
 * Add rewrite rule for deep-linking slider.
 *
 * Change "showcase" if the page slug differs.
 */
function ozi_add_showcase_rewrite(): void {
    add_rewrite_rule(
        '^showcase/([^/]+)/?$',
        'index.php?pagename=showcase&ozi_slide=$matches[1]',
        'top'
    );
}
add_action( 'init', 'ozi_add_showcase_rewrite' );

/**
 * Flush rewrite rules when the theme is switched so the custom rule applies.
 */
function ozi_flush_rewrites_after_switch(): void {
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'ozi_flush_rewrites_after_switch' );

/* -------------------------------------------------------
 * “Média mis en avant” (image OU vidéo)
 * ----------------------------------------------------- */
/**
 * Register and configure the featured media metabox (image or video).
 */
function ozi_setup_featured_media_metabox(): void {
    // replace native featured image box for remorques
    remove_meta_box( 'postimagediv', 'remorques', 'side' );

    add_meta_box(
        'ozi_featured_media',
        __( 'Média mis en avant (image ou vidéo)', 'ozitheme' ),
        'ozi_render_featured_media_metabox',
        'remorques',
        'side',
        'low'
    );
}
add_action( 'add_meta_boxes', 'ozi_setup_featured_media_metabox' );

/**
 * Callback: render the featured media metabox HTML/JS.
 *
 * @param WP_Post $post Current post object.
 */
function ozi_render_featured_media_metabox( $post ): void {
    wp_enqueue_media();
    $thumb_id = (int) get_post_thumbnail_id( $post->ID );
    $url      = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';
    $mime     = $thumb_id ? get_post_mime_type( $thumb_id ) : '';
    $is_video = ( $mime && strpos( $mime, 'video/' ) === 0 );
    wp_nonce_field( 'ozi_featured_media', 'ozi_featured_media_nonce' );
    ?>
    <div id="ozi-fm-wrap" style="display:grid;gap:8px">
        <div id="ozi-fm-preview" style="background:#111;border:1px solid #ddd;border-radius:6px;overflow:hidden;aspect-ratio:16/9;display:grid;place-items:center;color:#fff">
            <?php if ( $url ) : ?>
                <?php if ( $is_video ) : ?>
                    <video src="<?php echo esc_url( $url ); ?>" muted playsinline style="width:100%;height:100%;object-fit:cover"></video>
                <?php else : ?>
                    <img src="<?php echo esc_url( $url ); ?>" alt="" style="width:100%;height:100%;object-fit:cover"/>
                <?php endif; ?>
            <?php else : ?>
                <em><?php _e( 'Aucun média sélectionné', 'ozitheme' ); ?></em>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <button type="button" class="button" id="ozi-fm-choose"><?php _e( 'Choisir/Remplacer', 'ozitheme' ); ?></button>
            <button type="button" class="button-link-delete" id="ozi-fm-clear"><?php _e( 'Supprimer', 'ozitheme' ); ?></button>
        </div>
        <input type="hidden" id="_thumbnail_id" name="_thumbnail_id" value="<?php echo esc_attr( $thumb_id ); ?>">
    </div>
    <script>
    (function($){
      $('#ozi-fm-choose').on('click', function(e){
        e.preventDefault();
        const frame = wp.media({ title:'<?php echo esc_js(__('Sélectionner une image ou une vidéo','ozitheme')); ?>', library:{ type:['image','video'] }, multiple:false, button:{ text:'<?php echo esc_js(__('Utiliser ce média','ozitheme')); ?>'} });
        frame.on('select', function(){
          const att = frame.state().get('selection').first().toJSON();
          $('#_thumbnail_id').val(att.id);
          const $p = $('#ozi-fm-preview').empty();
          if (att.type === 'video') $('<video/>',{src:att.url, muted:true, playsinline:true}).css({width:'100%',height:'100%',objectFit:'cover'}).appendTo($p);
          else $('<img/>',{src:att.url, alt:''}).css({width:'100%',height:'100%',objectFit:'cover'}).appendTo($p);
        });
        frame.open();
      });
      $('#ozi-fm-clear').on('click', function(e){
        e.preventDefault();
        $('#_thumbnail_id').val('');
        $('#ozi-fm-preview').html('<em><?php echo esc_js(__('Aucun média sélectionné','ozitheme')); ?></em>');
      });
    })(jQuery);
    </script>
    <?php
}

/* Sauvegarde _thumbnail_id custom */
/**
 * Persist the custom thumbnail id from the "featured media" metabox.
 *
 * @param int $post_id Post being saved.
 */
function ozi_save_featured_media( int $post_id ): void {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( get_post_type( $post_id ) !== 'remorques' ) {
        return;
    }

    if ( isset( $_POST['ozi_featured_media_nonce'] ) && wp_verify_nonce( $_POST['ozi_featured_media_nonce'], 'ozi_featured_media' ) ) {
        if ( isset( $_POST['_thumbnail_id'] ) ) {
            $tid = (int) $_POST['_thumbnail_id'];
            if ( $tid > 0 ) {
                update_post_meta( $post_id, '_thumbnail_id', $tid );
            } else {
                delete_post_meta( $post_id, '_thumbnail_id' );
            }
        }
    }
}
add_action('save_post','ozi_save_featured_media');

/* -------------------------------------------------------
 * Metabox Infos (basiques + fallback vidéo)
 * ----------------------------------------------------- */
/**
 * Register the basic info metabox for remorques.
 */
function ozi_setup_meta_basic(): void {
    add_meta_box(
        'ozi_meta_basic',
        __( 'Infos Remorque', 'ozitheme' ),
        'ozi_render_meta_basic',
        'remorques',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'ozi_setup_meta_basic' );

/**
 * Render the HTML for the basic infos metabox.
 *
 * @param WP_Post $post Current post object.
 */
function ozi_render_meta_basic( $post ): void {
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
    ?>
    <div style="padding:10px 0;border-bottom:1px solid #e5e7eb;margin-bottom:10px">
        <label style="display:inline-flex;gap:8px;align-items:center">
            <input type="checkbox" name="ozi_video_use" value="1" <?php checked( $video_use, true ); ?>>
            <strong><?php _e( 'Activer une VIDÉO en hero si le média mis en avant n’est pas une vidéo', 'ozitheme' ); ?></strong>
        </label>
        <div style="display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center;margin-top:8px">
            <div><strong>Vidéo MP4</strong><br><small><?php echo $mp4_url ? esc_html( $mp4_url ) : '—'; ?></small></div>
            <button type="button" class="button ozi-pick-video" data-target="ozi_video_id"><?php esc_html_e( 'Choisir/Remplacer', 'ozitheme' ); ?></button>
            <button type="button" class="button-link-delete ozi-clear-video" data-target="ozi_video_id"><?php esc_html_e( 'Supprimer', 'ozitheme' ); ?></button>
            <input type="hidden" id="ozi_video_id" name="ozi_video_id" value="<?php echo esc_attr( $mp4_id ); ?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center;margin-top:8px">
            <div><strong>Vidéo WebM (optionnel)</strong><br><small><?php echo $webm_url ? esc_html( $webm_url ) : '—'; ?></small></div>
            <button type="button" class="button ozi-pick-video" data-target="ozi_video_webm_id"><?php esc_html_e( 'Choisir/Remplacer', 'ozitheme' ); ?></button>
            <button type="button" class="button-link-delete ozi-clear-video" data-target="ozi_video_webm_id"><?php esc_html_e( 'Supprimer', 'ozitheme' ); ?></button>
            <input type="hidden" id="ozi_video_webm_id" name="ozi_video_webm_id" value="<?php echo esc_attr( $webm_id ); ?>">
        </div>
        <p style="margin-top:8px"><label><?php _e( 'URL vidéo (YouTube / Vimeo / MP4)', 'ozitheme' ); ?><br>
            <input type="url" name="ozi_video_url" value="<?php echo esc_attr( $video_url ); ?>" style="width:100%"></label></p>
    </div>

    <p><label><?php _e( 'Prix (€ TTC):', 'ozitheme' ); ?><br>
            <input type="text" name="ozi_price" value="<?php echo esc_attr( $price ); ?>" style="width:50%"></label></p>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
        <p><label><?php _e( 'Poids (kg):', 'ozitheme' ); ?><br>
                <input type="text" name="ozi_weight" value="<?php echo esc_attr( $weight ); ?>" style="width:100%"></label></p>
        <p><label><?php _e( 'Charge utile (kg):', 'ozitheme' ); ?><br>
                <input type="text" name="ozi_capacity" value="<?php echo esc_attr( $capacity ); ?>" style="width:100%"></label></p>
        <p><label><?php _e( 'Dimensions (cm²)', 'ozitheme' ); ?><br>
                <input type="text" name="ozi_dimensions" value="<?php echo esc_attr( $dimensions ); ?>" style="width:100%"></label></p>
    </div>

    <p><label><?php _e( 'Caractéristiques (liste rapide, séparées par virgules — affichées sur le slide)', 'ozitheme' ); ?><br>
            <textarea name="ozi_features" rows="2" style="width:100%"><?php echo esc_textarea( $features ); ?></textarea></label></p>

    <script>
    (function($){
      $(document).on('click','.ozi-pick-video',function(e){
        e.preventDefault();
        var target = $(this).data('target');
        var frame = wp.media({ title:'Choisir une vidéo', library:{ type:'video' }, multiple:false, button:{ text:'Utiliser cette vidéo'} });
        frame.on('select', function(){
          var att = frame.state().get('selection').first().toJSON();
          $('#'+target).val(att.id);
          var label = $('button[data-target="'+target+'"]').closest('div').find('small');
          label.text(att.url || '—');
        });
        frame.open();
      });
      $(document).on('click','.ozi-clear-video',function(e){
        e.preventDefault();
        var target = $(this).data('target');
        $('#'+target).val('');
        var label = $('button[data-target="'+target+'"]').closest('div').find('small');
        label.text('—');
      });
      // Background picker (image)
$(document).on('click','.ozi-pick-bg',function(e){
  e.preventDefault();
  var target=$(this).data('target'), prev=$(this).data('prev'), urlEl=$(this).data('url');

  var frame=wp.media({ title:'Choisir une image', library:{ type:'image' }, multiple:false, button:{ text:'Utiliser' } });
  frame.on('select',function(){
    var att=frame.state().get('selection').first().toJSON();
    $('#'+target).val(att.id);
    $('#'+prev).empty().append($('<img/>',{src:att.url,alt:''}));
    $('#'+urlEl).text(att.url || '—');
  });
  frame.open();
});

$(document).on('click','.ozi-clear-bg',function(e){
  e.preventDefault();
  var target=$(this).data('target'), prev=$(this).data('prev'), urlEl=$(this).data('url');
  $('#'+target).val('');
  $('#'+prev).empty();
  $('#'+urlEl).text('—');
});
    })(jQuery);
    </script>
    <?php
}

/* Sauvegarde Infos basiques */
/**
 * Save the basic info metabox data when remorque is saved.
 *
 * @param int $post_id
 */
function ozi_save_meta_basic( $post_id ): void {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( get_post_type( $post_id ) !== 'remorques' ) {
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
add_action( 'save_post', 'ozi_save_meta_basic' );

/* -------------------------------------------------------
 * Metabox Infos avancées — RÉPÉTEURS illimités
 * ----------------------------------------------------- */
/**
 * Register the advanced infos metabox (with repeaters).
 */
function ozi_setup_meta_adv(): void {
    add_meta_box(
        'ozi_meta_adv',
        __( 'Infos avancées', 'ozitheme' ),
        'ozi_render_meta_adv',
        'remorques',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'ozi_setup_meta_adv' );

/**
 * Render the advanced informations metabox interface.
 *
 * @param WP_Post $post Current post object.
 */
function ozi_render_meta_adv( $post ): void {
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
    /* --- PRÉREMPLISSAGE SI VIDE (ou lignes vides) --- */

    // libellés de Caractéristiques
    $__defaults_car_labels = [
        'Dimensions ouvertes (cm)',
        'Dimensions pliées (cm)',
        'Temps de pliage et de dépliage',
        'Poids à vide',
        'Charge utile',
        'Poids total autorisé en charge',
        'Hauteur de chargement',
        'Châssis tubulaire',
        'Protection de suspension',
        'Homologation CE',
        'Fabrication',
        'Garantie structure',
    ];

    // libellés d’Équipement
    $__defaults_eqp_labels = [
        'Rail complet 240 mm',
        'Rampe d’accès 240 mm',
        'Sabot de blocage de roue inox',
        'Boîtier d’attache AL-KO',
        'Moyeux AL-KO',
        'Roue 13″',
        'Garde-boue',
        'Feux LED',
        'Faisceau',
        'Prise 7 broches',
        'Traverse pour sangler la moto',
        'Marche pied',
    ];

    // détecte si un tableau ne contient que des lignes vides
    $__has_meaningful_rows = function( array $rows ) {
        foreach ( $rows as $r ) {
            $l = isset( $r['label'] ) ? trim( $r['label'] ) : '';
            $v = isset( $r['value'] ) ? trim( $r['value'] ) : '';
            if ( $l !== '' || $v !== '' ) {
                return true;
            }
        }
        return false;
    };

    // injecte les défauts si rien d’utile
    if ( empty( $tech_car ) || ! $__has_meaningful_rows( $tech_car ) ) {
        $tech_car = array_map( fn( $l ) => [ 'label' => $l, 'value' => '' ], $__defaults_car_labels );
    }
    if ( empty( $tech_eqp ) || ! $__has_meaningful_rows( $tech_eqp ) ) {
        $tech_eqp = array_map( fn( $l ) => [ 'label' => $l, 'value' => '' ], $__defaults_eqp_labels );
    }

    // ——— Avis par défaut (sans note) ———
    $__defaults_reviews = [
        [ 'author' => 'Fabrice',   'text' => "Le processus d'achat a été parfait. La livraison se fait très rapidement et efficacement. Quant à la remorque elle-même, les finitions sont parfaites, les soudures vraiment bien faites. Le métal est clairement de qualité. Tout est impeccablement graissé et la remorque se déplie et se replie facilement. Monter la moto dessus est une formalité ! Bref, un produit à la qualité et au packaging irréprochables. Certes, cela peut paraître un peu cher, mais c'est sans commune mesure avec la concurrence !" ],
        [ 'author' => 'Pierre',    'text' => "Excellent produit, je suis vraiment ravi de mon achat. Qualité de fabrication remarquable, cinématique de la remorque particulièrement bien étudiée ; en bref, c’est un produit d’exception." ],
        [ 'author' => 'Stéphanie', 'text' => "Nous avons acheté cette remorque car nous sommes en appartement et nous n'avons pas de garage. Cela nous permet de la ranger à l'intérieur. Nous sommes très satisfaits de notre achat. Livraison rapide et vendeur très sérieux qui répond à toutes les questions. Remorque facile à monter." ],
    ];

    $__has_meaningful_reviews = function( array $rows ) {
        foreach ( $rows as $r ) {
            $a = isset( $r['author'] ) ? trim( $r['author'] ) : '';
            $t = isset( $r['text'] ) ? trim( $r['text'] ) : '';
            if ( $a !== '' || $t !== '' ) {
                return true;
            }
        }
        return false;
    };

    if ( empty( $reviews ) || ! $__has_meaningful_reviews( $reviews ) ) {
        $reviews = $__defaults_reviews;
    }

    // ————— FAQ par défaut (si vide) —————
    $__defaults_faq = [
        [
            'q' => 'Faut-il un permis spécial ?',
            'a' => 'Non, une voiture classique suffit.',
        ],
        [
            'q' => 'Convient-elle à ma moto ?',
            'a' => 'Oui, jusqu’à 220 kg. Le sabot est ajustable pour convenir à toutes les dimensions.',
        ],
        [
            'q' => 'Est-ce facile à plier ?',
            'a' => 'Oui, en moins de 3 minutes et sans outil. La remorque se plie et se range facilement. Une vidéo explicative est disponible.',
        ],
        [
            'q' => 'Est-ce facile à déplacer ?',
            'a' => 'Oui, et d’autant plus avec les accessoires proposés par OZI Concept.',
        ],
        [
            'q' => 'Une carte grise est-elle nécessaire pour la remorque ?',
            'a' => 'Pas pour ce modèle.',
        ],
    ];

    // détecte si une FAQ contient au moins une ligne utile
    $__has_meaningful_faq = function( array $rows ) {
        foreach ( $rows as $r ) {
            $q = isset( $r['q'] ) ? trim( $r['q'] ) : '';
            $a = isset( $r['a'] ) ? trim( $r['a'] ) : '';
            if ( $q !== '' || $a !== '' ) {
                return true;
            }
        }
        return false;
    };

    // injecte les Q/R par défaut si rien d’utile en base
    if ( empty( $faq ) || ! $__has_meaningful_faq( $faq ) ) {
        $faq = $__defaults_faq;
    }

    wp_nonce_field( 'ozi_meta_adv', 'ozi_meta_adv_nonce' );
    ?>
    <style>
      .ozi-box{border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin:10px 0}
      .ozi-title{margin:12px 0 6px;font-weight:600}
      .ozi-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
      #ozi-infos .item{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:12px;}
      #ozi-infos .item .ozi-column p{ margin:0 0 8px; }
      .ozi-list>.item{border-top:1px dashed #e5e7eb;padding-top:10px;margin-top:10px;position:relative}
      .ozi-list>.item:first-child{border-top:0;margin-top:0;padding-top:0}
      .ozi-actions{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}
      .ozi-thumb{width:100%;aspect-ratio:16/9;background:#0b0b0b;border-radius:6px;overflow:hidden}
      .ozi-thumb img{width:100%;height:100%;object-fit:cover;display:block}
      .ozi-del{position:absolute;right:0;top:0}
      .ozi-muted{color:#64748b;font-size:12px}
    </style>


    <!-- intros -->
    <h3 class="ozi-title"><?php _e('Intros','ozitheme'); ?></h3>
    <div class="ozi-box">
    <div class="ozi-row">
      <p>
        <label><?php _e('Texte de la section','ozitheme'); ?><br>
          <textarea name="ozi_intros" rows="3" style="width:100%"><?php
            echo esc_textarea($intros);
          ?></textarea>
        </label>
      </p>

      <p>
        <label><?php _e('Libellé du bouton','ozitheme'); ?><br>
          <input type="text" name="ozi_intros_label" value="<?php
            echo esc_attr($intros_label);
          ?>" style="width:100%">
        </label>
        <small class="ozi-muted"><?php _e('Ce bouton ouvre les infos en modal sur le site.','ozitheme'); ?></small>
    </p>
  </div>
</div>        

    <!-- infos -->
    <h3 class="ozi-title"><?php _e('Infos','ozitheme'); ?></h3>
    <div class="ozi-box">
      <div id="ozi-infos" class="ozi-list">
        <?php foreach ($infos as $i=>$s): $img_id=(int)($s['image_id']??0); $img_url=$img_id?wp_get_attachment_url($img_id):''; ?>
          <div class="item" data-i="<?php echo $i; ?>">
            <button type="button" class="button-link-delete ozi-del" data-group="infos"><?php _e('Supprimer','ozitheme'); ?></button>
            <div class="ozi-column">
              <p><label><?php _e('Titre','ozitheme'); ?><br>
                <input type="text" name="ozi_infos[<?php echo $i; ?>][title]" value="<?php echo esc_attr($s['title']??''); ?>" style="width:100%"></label></p>
              <p><label><?php _e('Texte','ozitheme'); ?><br>
                <textarea name="ozi_infos[<?php echo $i; ?>][text]" rows="2" style="width:100%"><?php echo esc_textarea($s['text']??''); ?></textarea></label></p>
            </div>
            <div class="ozi-column">
              <div>
                <div class="ozi-thumb" id="ozi-sec-prev-<?php echo $i; ?>"><?php if($img_url): ?><img src="<?php echo esc_url($img_url); ?>" alt=""><?php endif; ?></div>
                <div class="ozi-muted"><?php echo $img_url? esc_html($img_url): '—'; ?></div>
              </div>
              <div class="ozi-actions">
                <button type="button" class="button ozi-pick" data-target="ozi-sec-id-<?php echo $i; ?>" data-prev="ozi-sec-prev-<?php echo $i; ?>"><?php _e('Choisir image','ozitheme'); ?></button>
                <button type="button" class="button-link-delete ozi-clear" data-target="ozi-sec-id-<?php echo $i; ?>" data-prev="ozi-sec-prev-<?php echo $i; ?>"><?php _e('Supprimer','ozitheme'); ?></button>
                <input type="hidden" id="ozi-sec-id-<?php echo $i; ?>" name="ozi_infos[<?php echo $i; ?>][image_id]" value="<?php echo $img_id; ?>">
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <p><button type="button" class="button button-primary" id="ozi-add-infos"><?php _e('Ajouter une info','ozitheme'); ?></button></p>
    </div>

       
    <!-- Background Tech Full -->
<h3 class="ozi-title"><?php _e('Background Tech Full','ozitheme'); ?></h3>
<div class="ozi-box">
  <div>
    <div class="ozi-thumb" id="ozi-bg-techfull-prev"><?php if($bg_tech_full_url): ?><img src="<?php echo esc_url($bg_tech_full_url); ?>" alt=""><?php endif; ?></div>
    <div class="ozi-muted" id="ozi-bg-techfull-url"><?php echo $bg_tech_full_url ? esc_html($bg_tech_full_url) : '—'; ?></div>
    <div class="ozi-actions">
      <button type="button" class="button ozi-pick-bg" data-target="ozi_bg_tech_full_id" data-prev="ozi-bg-techfull-prev" data-url="ozi-bg-techfull-url"><?php _e('Choisir image','ozitheme'); ?></button>
      <button type="button" class="button-link-delete ozi-clear-bg" data-target="ozi_bg_tech_full_id" data-prev="ozi-bg-techfull-prev" data-url="ozi-bg-techfull-url"><?php _e('Supprimer','ozitheme'); ?></button>
      <input type="hidden" id="ozi_bg_tech_full_id" name="ozi_bg_tech_full_id" value="<?php echo esc_attr($bg_tech_full_id); ?>">
    </div>
  </div>
</div>

    <!-- Tech Caractéristiques -->
    <h3 class="ozi-title"><?php _e('Caractéristiques','ozitheme'); ?></h3>
    <div class="ozi-box">
      <div id="ozi-tech-car" class="ozi-list">
        <?php foreach ($tech_car as $i=>$r): ?>
          <div class="item" data-i="<?php echo $i; ?>">
            <button type="button" class="button-link-delete ozi-del" data-group="tech-car"><?php _e('Supprimer','ozitheme'); ?></button>
            <div class="ozi-row">
              <p><input type="text" name="ozi_tech_carac[<?php echo $i; ?>][label]" value="<?php echo esc_attr($r['label']??''); ?>" placeholder="<?php esc_attr_e('Nom','ozitheme'); ?>" style="width:100%"></p>
              <p><input type="text" name="ozi_tech_carac[<?php echo $i; ?>][value]" value="<?php echo esc_attr($r['value']??''); ?>" placeholder="<?php esc_attr_e('Valeur','ozitheme'); ?>" style="width:100%"></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <p><button type="button" class="button" id="ozi-add-tech-car"><?php _e('Ajouter une caractéristique','ozitheme'); ?></button></p>
    </div>

    <!-- Tech Équipement -->
    <h3 class="ozi-title"><?php _e('Équipement','ozitheme'); ?></h3>
    <div class="ozi-box">
      <div id="ozi-tech-eqp" class="ozi-list">
        <?php foreach ($tech_eqp as $i=>$r): ?>
          <div class="item" data-i="<?php echo $i; ?>">
            <button type="button" class="button-link-delete ozi-del" data-group="tech-eqp"><?php _e('Supprimer','ozitheme'); ?></button>
            <div class="ozi-row">
              <p><input type="text" name="ozi_tech_equip[<?php echo $i; ?>][label]" value="<?php echo esc_attr($r['label']??''); ?>" placeholder="<?php esc_attr_e('Nom','ozitheme'); ?>" style="width:100%"></p>
              <p><input type="text" name="ozi_tech_equip[<?php echo $i; ?>][value]" value="<?php echo esc_attr($r['value']??''); ?>" placeholder="<?php esc_attr_e('Valeur','ozitheme'); ?>" style="width:100%"></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <p><button type="button" class="button" id="ozi-add-tech-eqp"><?php _e('Ajouter un équipement','ozitheme'); ?></button></p>
    </div>

    <!-- Avis -->
    <h3 class="ozi-title"><?php _e('Avis','ozitheme'); ?></h3>
    <div class="ozi-box">
      <div id="ozi-reviews" class="ozi-list">
        <?php foreach ($reviews as $i=>$r): ?>
          <div class="item" data-i="<?php echo $i; ?>">
            <button type="button" class="button-link-delete ozi-del" data-group="reviews"><?php _e('Supprimer','ozitheme'); ?></button>
            <div class="ozi-row">
              <p><input type="text" name="ozi_reviews[<?php echo $i; ?>][author]" value="<?php echo esc_attr($r['author']??''); ?>" placeholder="<?php esc_attr_e('Auteur','ozitheme'); ?>" style="width:100%"></p>
              <p><input type="text" name="ozi_reviews[<?php echo $i; ?>][text]" value="<?php echo esc_attr($r['text']??''); ?>" placeholder="<?php esc_attr_e('Commentaire','ozitheme'); ?>" style="width:100%"></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <p><button type="button" class="button" id="ozi-add-review"><?php _e('Ajouter un avis','ozitheme'); ?></button></p>
    </div>

    <!-- FAQ -->
    <h3 class="ozi-title"><?php _e('FAQ','ozitheme'); ?></h3>
    <div class="ozi-box">
      <div id="ozi-faq" class="ozi-list">
        <?php foreach ($faq as $i=>$q): ?>
          <div class="item" data-i="<?php echo $i; ?>">
            <button type="button" class="button-link-delete ozi-del" data-group="faq"><?php _e('Supprimer','ozitheme'); ?></button>
            <div class="ozi-row">
              <p><input type="text" name="ozi_faq[<?php echo $i; ?>][q]" value="<?php echo esc_attr($q['q']??''); ?>" placeholder="<?php esc_attr_e('Question','ozitheme'); ?>" style="width:100%"></p>
              <p><input type="text" name="ozi_faq[<?php echo $i; ?>][a]" value="<?php echo esc_attr($q['a']??''); ?>" placeholder="<?php esc_attr_e('Réponse','ozitheme'); ?>" style="width:100%"></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <p><button type="button" class="button" id="ozi-add-faq"><?php _e('Ajouter une Q/R','ozitheme'); ?></button></p>
    </div>

    <!-- CTA -->
    <h3 class="ozi-title"><?php _e('Appel à l’action (CTA)','ozitheme'); ?></h3>
    <div class="ozi-box">
      <div class="ozi-row">
        <p><label><?php _e('Lien achat','ozitheme'); ?><br><input type="url" name="ozi_buy_link" value="<?php echo esc_attr($buy_link);?>" style="width:100%"></label></p>
        <p><label><?php _e('Libellé bouton','ozitheme'); ?><br><input type="text" name="ozi_buy_label" value="<?php echo esc_attr($buy_lbl); ?>" style="width:100%"></label></p>
      </div>
    </div>

    <script>
    (function($){
      function nextIndex($list){ var max=-1; $list.children('.item').each(function(){ var i=parseInt($(this).attr('data-i'),10); if(!isNaN(i)&&i>max) max=i; }); return max+1; }

      // Media picker (images pour infos)
      $(document).on('click','.ozi-pick',function(e){
        e.preventDefault();
        var target=$(this).data('target'), prev=$(this).data('prev');
        var frame=wp.media({ title:'<?php echo esc_js(__('Choisir une image','ozitheme')); ?>', library:{ type:'image' }, multiple:false, button:{ text:'<?php echo esc_js(__('Utiliser','ozitheme')); ?>' } });
        frame.on('select',function(){
          var att=frame.state().get('selection').first().toJSON();
          $('#'+target).val(att.id);
          var $p=$('#'+prev).empty();
          $('<img/>',{src:att.url,alt:''}).appendTo($p);
          $p.next('.ozi-muted').text(att.url||'—');
        });
        frame.open();
      });
      $(document).on('click','.ozi-clear',function(e){
        e.preventDefault();
        var target=$(this).data('target'), prev=$(this).data('prev');
        $('#'+target).val('');
        $('#'+prev).empty(); $('#'+prev).next('.ozi-muted').text('—');
      });

      $(document).on('click','.ozi-del',function(e){ e.preventDefault(); $(this).closest('.item').remove(); });

      // Adders
      $('#ozi-add-infos').on('click',function(){
        var $list=$('#ozi-infos'), i=nextIndex($list);
        var html='' +
          '<div class="item" data-i="'+i+'">'+
          '<button type="button" class="button-link-delete ozi-del" data-group="infos"><?php echo esc_js(__('Supprimer','ozitheme')); ?></button>'+MODELED_SNIPPET_TOO_BIG_REST```}

/* Sauvegarde Infos avancées */
/**
 * Persist the advanced informations metabox data.
 *
 * @param int $post_id ID of the post being saved.
 */
function ozi_save_meta_adv( int $post_id ): void {
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

    // Technique carac
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

    // Technique équipement
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
}
add_action('save_post','ozi_save_meta_adv');

/* -------------------------------------------------------
 * Helpers vidéo
 * ----------------------------------------------------- */
function ozi_get_uploaded_video_sources($post_id){
  $srcs = [];
  $webm_id = (int) get_post_meta($post_id, '_ozi_video_webm_id', true);
  $mp4_id  = (int) get_post_meta($post_id, '_ozi_video_id', true);
  if ($webm_id) { $u = wp_get_attachment_url($webm_id); if ($u) $srcs[] = ['src'=>$u,'type'=>'video/webm']; }
  if ($mp4_id)  { $u = wp_get_attachment_url($mp4_id);  if ($u) $srcs[] = ['src'=>$u,'type'=>'video/mp4'];  }
  return $srcs;
}
function ozi_parse_video_url($url){
  $out = ['type'=>'','id'=>'','src'=>''];
  if (!$url) return $out;
  if (preg_match('~\.(mp4|webm|ogv)(\?.*)?$~i', $url)) { $out['type']='self'; $out['src']=esc_url($url); return $out; }
  if (preg_match('~(youtube\.com|youtu\.be)~i', $url)) {
    if (preg_match('~(?:v=|/embed/|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
      $id=$m[1];
      $out=['type'=>'youtube','id'=>$id,'src'=>'https://www.youtube.com/embed/'.$id.'?autoplay=1&mute=1&controls=0&playsinline=1&loop=1&playlist='.$id.'&modestbranding=1&rel=0'];
      return $out;
    }
  }
  if (preg_match('~vimeo\.com~i', $url)) {
    if (preg_match('~vimeo\.com/(?:video/)?([0-9]+)~', $url, $m)) {
      $id=$m[1];
      $out=['type'=>'vimeo','id'=>$id,'src'=>'https://player.vimeo.com/video/'.$id.'?background=1&autoplay=1&muted=1&loop=1&autopause=0&playsinline=1'];
      return $out;
    }
  }
  return $out;
}

/**
 * Render du hero média pour un post: priorité
 * 1) featured = vidéo => <video>
 * 2) sinon fallback vidéo (uploads/url) si coche activée
 * 3) sinon image de fond
 */
function ozi_render_featured_media_hero($post_id){
  $tid = (int) get_post_thumbnail_id($post_id);
  if ($tid) {
    $mime = get_post_mime_type($tid) ?: '';
    $url  = wp_get_attachment_url($tid) ?: '';
    if ($url && strpos($mime, 'video/') === 0) {
      $type = 'video/mp4';
      if (preg_match('~\.webm(\?.*)?$~i', $url)) $type = 'video/webm';
      elseif (preg_match('~\.(ogv|ogg)(\?.*)?$~i', $url)) $type = 'video/ogg';
      return '<div class="hero hero--video">'.
             '<video class="hero-media" autoplay muted playsinline loop preload="metadata">'.
             '<source src="'.esc_url($url).'" type="'.esc_attr($type).'">'.
             '</video></div>';
    }
    if ($img = wp_get_attachment_image_url($tid, 'full')) {
      return '<div class="hero" style="background-image:url(\''.esc_url($img).'\')"></div>';
    }
  }

  // Fallback vidéo activé ?
  $poster = get_the_post_thumbnail_url($post_id, 'full') ?: '';
  $use = get_post_meta($post_id, '_ozi_video_use', true) === '1';
  if ($use) {
    $uploaded = ozi_get_uploaded_video_sources($post_id);
    $url      = trim((string) get_post_meta($post_id, '_ozi_video_url', true));
    if (!empty($uploaded)) {
      ob_start(); ?>
      <div class="hero hero--video">
        <video class="hero-media" autoplay muted playsinline loop preload="metadata" poster="<?php echo esc_url($poster); ?>">
          <?php foreach ($uploaded as $s): ?>
            <source src="<?php echo esc_url($s['src']); ?>" type="<?php echo esc_attr($s['type']); ?>">
          <?php endforeach; ?>
        </video>
      </div>
      <?php return ob_get_clean();
    }
    $vi = ozi_parse_video_url($url);
    if ($vi['type'] === 'self') {
      return '<div class="hero hero--video"><video class="hero-media" autoplay muted playsinline loop preload="metadata" poster="'.
             esc_url($poster).'"><source src="'.esc_url($vi['src']).'" type="video/mp4"></video></div>';
    }
    if ($vi['type'] === 'youtube' || $vi['type'] === 'vimeo') {
      // en lazy → le JS peut déplacer data-src en src uniquement sur la slide active
      return '<div class="hero hero--video"><iframe class="hero-media hero-media--embed" title="video" src="" data-src="'.
             esc_url($vi['src']).'" frameborder="0" allow="autoplay; fullscreen" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
    }
  }
  return '';
}

/* -------------------------------------------------------
 * Bloc Gutenberg: ozi/showcase (SSR)
 * ----------------------------------------------------- */
add_action('init', function () {
  $dir = ozi_theme_dir() . '/blocks/showcase';
  if (file_exists($dir . '/block.json')) {
    register_block_type_from_metadata($dir, [
      'render_callback' => 'ozi_render_showcase_block',
    ]);
  }
});

/* Optionnel: assets éditeur du bloc */
add_action('enqueue_block_editor_assets', function () {
  $uri  = ozi_theme_uri();
  $dir  = ozi_theme_dir();
  if (file_exists($dir . '/blocks/showcase/bridge.js')) {
    wp_enqueue_script('ozi-editor-bridge', $uri . '/blocks/showcase/bridge.js', ['wp-blocks','wp-element'], filemtime($dir . '/blocks/showcase/bridge.js'), true);
  }
  if (file_exists($dir . '/blocks/showcase/showcase-editor.css')) {
    wp_enqueue_style('ozi-showcase-editor', $uri . '/blocks/showcase/showcase-editor.css', [], filemtime($dir . '/blocks/showcase/showcase-editor.css'));
  }
});

/**
 * Rendu SSR du slider + OZI_DATA
 * - Ajoute data-base et data-start pour le deep-link /showcase/{slug}
 * - Chaque slide a data-slug
 */
function ozi_render_showcase_block($attributes = [], $content = '') {
  $uri = ozi_theme_uri();
  $dir = ozi_theme_dir();

  // Enqueue front
  $css = $dir . '/assets/css/showcase.css';
  if (file_exists($css)) wp_enqueue_style('ozi-front',  $uri . '/assets/css/showcase.css', [], filemtime($css));
  $js  = $dir . '/assets/js/showcase.js';
  if (file_exists($js))  wp_enqueue_script('ozi-front', $uri . '/assets/js/showcase.js',   [], filemtime($js), true);

  // Deep-link base & start (page showcase + ?ozi_slide=slug)
  $base_url   = trailingslashit( get_permalink( get_the_ID() ) ); // ex: /showcase/
  $start_slug = get_query_var('ozi_slide');

  // Query produits
  $q = new WP_Query([
    'post_type'      => 'remorques',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
  ]);

  $products = [];
  ob_start(); ?>
  <header class="slider" id="slider"
          data-base="<?php echo esc_url($base_url); ?>"
          data-start="<?php echo esc_attr($start_slug); ?>">
    <div class="slides" id="slides">
      <?php if ($q->have_posts()): while ($q->have_posts()): $q->the_post();
        $id         = get_the_ID();
        $slug       = get_post_field('post_name', $id);
        $thumb      = get_the_post_thumbnail_url($id, 'full');
        if (!$thumb) $thumb = $uri . '/assets/img/placeholder.jpg';

        $features   = get_post_meta($id, '_ozi_features', true);
        $features_list = $features ? array_filter(array_map('trim', explode(',', $features))) : [];

        $weight     = get_post_meta($id, '_ozi_weight', true);
        $capacity   = get_post_meta($id, '_ozi_capacity', true);
        $dimensions = get_post_meta($id, '_ozi_dimensions', true);
        $intros     = (string) get_post_meta($id, '_ozi_intros', true);
        $intros_label = (string) get_post_meta($id, '_ozi_intros_label', true);
        $infos   = (array) get_post_meta($id, '_ozi_infos', true);
        $bg_tech_full_id = (int) get_post_meta($id, '_ozi_bg_tech_full_id', true);
        $bg_tech_full    = $bg_tech_full_id ? wp_get_attachment_url($bg_tech_full_id) : '';
        $tech_car   = (array) get_post_meta($id, '_ozi_tech_carac', true);
        $tech_eqp   = (array) get_post_meta($id, '_ozi_tech_equip', true);
        $reviews    = (array) get_post_meta($id, '_ozi_reviews', true);
        $faq        = (array) get_post_meta($id, '_ozi_faq', true);
        $buy_link   = (string) get_post_meta($id, '_ozi_buy_link', true);
        $buy_label  = (string) get_post_meta($id, '_ozi_buy_label', true) ?: __('Acheter maintenant','ozitheme');
        ?>
        <section class="slide" data-slug="<?php echo esc_attr($slug); ?>">
          <?php echo ozi_render_featured_media_hero($id) ?: '<div class="hero" style="background-image:url(\''.esc_url($thumb).'\')"></div>'; ?>
          <div class="top-fade"></div><div class="bottom-fade"></div>

          <div class="hero-info">
            <h2><?php the_title(); ?></h2> <!-- Titre non cliquable -->

            <ul class="metrics">
              <?php if ($weight !== ''): ?>
                <li class="metric">
                  <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M7 3h10l3 6v9a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V9l3-6Zm1.7 2-2 4H17.3l-2-4H8.7ZM12 9.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/></svg>
                  <div><div class="m-label"><?php _e('Poids','ozitheme'); ?></div><div class="m-val"><?php echo esc_html($weight); ?> kg</div></div>
                </li>
              <?php endif; ?>
              <?php if ($capacity !== ''): ?>
                <li class="metric">
                  <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3 8h18v7a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8Zm3-5h12v3H6V3Z"/></svg>
                  <div><div class="m-label"><?php _e('Charge utile','ozitheme'); ?></div><div class="m-val"><?php echo esc_html($capacity); ?> kg</div></div>
                </li>
              <?php endif; ?>
              <?php if ($dimensions !== ''): ?>
                <li class="metric">
                <svg xmlns="http://www.w3.org/2000/svg" id="uuid-10769ac3-cd9a-4256-9f45-9880950fa79e"  viewBox="0 0 24 24" width="20" height="20"><g id="uuid-79d6f51f-1ae8-4492-b9f3-242cc1711da6"><path d="M61.47,50.14c-1.64,0-2.97-1.33-2.97-2.97V10.83c0-2.69-2.25-4.88-5.03-4.88H16.47c-1.64,0-2.97-1.33-2.97-2.97s1.33-2.97,2.97-2.97h37c6.05,0,10.97,4.86,10.97,10.83v36.34c0,1.64-1.33,2.97-2.97,2.97Z"/><path d="M50.22,63.4c-.55,0-1.09-.15-1.57-.45-.87-.54-1.4-1.5-1.4-2.52v-1.45H15.47c-6.05,0-10.97-4.86-10.97-10.83v-31.16h-1.53c-1.03,0-1.99-.54-2.53-1.42-.54-.88-.58-1.98-.11-2.9L4.81,3.86c.27-.55.72-1.03,1.32-1.33.7-.36,1.48-.41,2.18-.2.2.06.39.14.57.23.47.25.88.63,1.17,1.12.03.06.06.12.09.18l4.49,8.81c.47.92.43,2.02-.11,2.9-.54.88-1.5,1.42-2.53,1.42h-1.53v31.16c0,2.69,2.25,4.88,5.03,4.88h31.78v-1.45c0-1.03.53-1.98,1.4-2.52.87-.54,1.96-.6,2.88-.15l8.97,4.41c.58.28,1.08.75,1.38,1.37.34.68.39,1.43.19,2.11-.07.24-.17.47-.29.69-.26.44-.63.82-1.1,1.09-.06.04-.13.07-.2.1l-8.97,4.4c-.41.2-.86.3-1.31.3Z"/><path d="M15.43,38.62c-.73,0-1.04-.32-1.04-1.04v-18.8c0-.73.32-1.04,1.04-1.04h4.67c.61,0,.99.23,1.33.87l2.61,4.73c.2.35.29.49.52.49h.26c.23,0,.32-.15.52-.49l2.58-4.73c.35-.64.73-.87,1.33-.87h4.7c.73,0,1.04.32,1.04,1.04v18.8c0,.73-.32,1.04-1.04,1.04h-4.47c-.75,0-1.04-.32-1.04-1.04v-10.21l-1.71,3.16c-.35.67-.78.93-1.54.93h-1.16c-.75,0-1.19-.26-1.54-.93l-1.71-3.16v10.21c0,.73-.32,1.04-1.04,1.04h-4.32Z"/><path d="M39.21,38.62c-.73,0-1.04-.32-1.04-1.04v-4.99c0-4.5,1.89-5.54,4.55-6.35l3.89-1.22c.61-.17.78-.41.78-1.04,0-.58-.2-.75-.78-.75h-6.29c-.73,0-1.04-.32-1.04-1.04v-3.39c0-.73.32-1.04,1.04-1.04h8.56c3.31,0,5.11,1.68,5.11,4.81v2.73c0,2.93-1.71,4.26-4.53,5.13l-3.94,1.25c-.58.17-.75.23-.75.87v.61h8.27c.73,0,1.04.32,1.04,1.04v3.39c0,.73-.32,1.04-1.04,1.04h-13.81Z"/></g></svg>
                <div><div class="m-label"><?php _e('Dimensions','ozitheme'); ?></div><div class="m-val"><?php echo esc_html($dimensions); ?> cm²</div></div>
                </li>
              <?php endif; ?>
            </ul>

            <?php if ($features_list): ?>
              <ul class="features">
                <?php foreach ($features_list as $f): ?>
                  <li><?php echo esc_html($f); ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </section>
        <?php
          // Payload pour la card/détails
          $products[] = [
            'title'    => get_the_title(),
            'slug'     => $slug,
            'url'      => get_permalink($id),
            'tag'      => get_the_title(),
            'subtitle' => get_the_excerpt(),
            'hero'     => $thumb,
            'price'    => get_post_meta($id, '_ozi_price', true),
            'features' => $features_list,
            'specs'    => [
              'Poids'        => $weight ?: '',
              'Charge utile' => $capacity ?: '',
              'Dimensions'   => $dimensions ?: '',
            ],
            'intros'   => $intros,
            'intros_label' => $intros_label,
            'infos' => array_map(function($s){
              $u = !empty($s['image_id']) ? wp_get_attachment_url((int)$s['image_id']) : '';
              return ['title'=>$s['title']??'','text'=>$s['text']??'','image'=>$u];
            }, $infos),
            'bgTechFull' => $bg_tech_full,
            'tech' => [
              'caracteristiques' => $tech_car,
              'equipements'      => $tech_eqp,
            ],
            'reviews'  => $reviews,
            'faq'      => $faq,
            'buyLink'  => $buy_link,
            'buyLabel' => $buy_label,
          ];
      endwhile; endif; wp_reset_postdata(); ?>
    </div>

    <div class="nav" id="nav-arrows"><button id="prev">❮</button><button id="next">❯</button></div>
    <div class="caption">
      <button class="discover-btn" id="discover"><span><svg width="64px" height="64px" viewBox="-7.58 -7.58 90.96 90.96" xmlns="http://www.w3.org/2000/svg" fill="#ffffff" stroke="#ffffff" stroke-width="3.7902"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g id="Group_67" data-name="Group 67" transform="translate(-798.203 -587.815)"> <path id="Path_59" data-name="Path 59" d="M798.2,589.314a1.5,1.5,0,0,1,2.561-1.06l33.56,33.556a2.528,2.528,0,0,0,3.564,0l33.558-33.556a1.5,1.5,0,1,1,2.121,2.121l-33.558,33.557a5.53,5.53,0,0,1-7.807,0l-33.56-33.557A1.5,1.5,0,0,1,798.2,589.314Z" fill="#fff"></path> </g> </g></svg></span></button>
    </div>
  </header>

  <section class="content-wrap" id="content">
    <article class="card" id="details-card"></article>
  </section>
  <?php
  $html = ob_get_clean();

  // Inject OZI_DATA (une fois)
  static $printed = false;
  if (!$printed) {
    $printed = true;
    $payload = [
      'products' => $products,
      'i18n' => [

        'reviews' => __('avis','ozitheme'),
        'faq'     => __('FAQ','ozitheme'),
        'buy'     => __('Acheter maintenant','ozitheme'),
      ],
    ];
    $html .= '<script>window.OZI_DATA=' . wp_json_encode($payload) . ';</script>';
  }

  return $html;
}

/* Shortcode fallback si besoin dans l’éditeur classique */
add_shortcode('ozi_showcase', function(){ return ozi_render_showcase_block(); });
