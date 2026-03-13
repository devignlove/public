<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header id="site-header" class="site-header" role="banner">
  <div class="site-header__inner">
    <div class="site-branding">
      <?php if ( has_custom_logo() ) {
        the_custom_logo();
      } else { ?>
        <a class="brand brand--text" href="<?php echo esc_url(home_url('/')); ?>">
          <span class="brand__text"><?php bloginfo('name'); ?></span>
        </a>
      <?php } ?>
    </div>

    <button id="menu-toggle" class="burger" aria-expanded="false" aria-controls="site-nav"
            type="button"
            aria-label="<?php esc_attr_e('Ouvrir le menu','ozitheme'); ?>"
            data-open-text="<?php esc_attr_e('Ouvrir le menu','ozitheme'); ?>"
            data-close-text="<?php esc_attr_e('Fermer le menu','ozitheme'); ?>">
      <span class="screen-reader-text"><?php esc_html_e('Ouvrir le menu','ozitheme'); ?></span>
      <span class="burger__line burger__line--top"></span>
      <span class="burger__line burger__line--middle"></span>
      <span class="burger__line burger__line--bottom"></span>
    </button>

    <nav id="site-nav" class="site-nav" role="navigation" aria-label="<?php esc_attr_e('Menu principal','ozitheme'); ?>" aria-hidden="true">
      <?php if ( has_nav_menu( 'primary' ) ) : ?>
        <?php
        wp_nav_menu([
          'theme_location' => 'primary',
          'container'      => false,
          'menu_id'        => 'primary-menu',
          'menu_class'     => 'menu',
          'fallback_cb'    => '__return_empty_string',
        ]);
        ?>
      <?php else : ?>
        <ul id="primary-menu" class="menu">
          <?php
          wp_list_pages([
            'title_li' => '',
            'depth'    => 1,
          ]);
          ?>
        </ul>
      <?php endif; ?>
    </nav>
  </div>
  <div id="nav-backdrop" class="site-nav-backdrop" tabindex="-1" aria-hidden="true"></div>
</header>
