<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'Ah!bwkEY3FbA=kHjL+,1V[MLDH(+3cP|z% vLE)a0ELcShEFYz;O&0$r< N^nr$A' );
define( 'SECURE_AUTH_KEY',   'q^b;ZyXyPEyIDH-4KKEv2kS%ik3cyoP0yPSwC(39=`WB+12Q~hbJ7{M&YbPj_Pei' );
define( 'LOGGED_IN_KEY',     'Sx!+u<y|m?U-[~mfEn*>I}Qp#<X29Pz$zh5KGlrb ?5K~}y_DCN#EI2pc,N(3[XI' );
define( 'NONCE_KEY',         'u/%?CWAaJb]i6zBZ$$s5$7)ykV7]G}xd1Zcy4G?}z:xE$?ctj1lx?&?E)$r6%?gG' );
define( 'AUTH_SALT',         'c) iDc#u7KMSB; JE+*C:6%a3k|V%4WE?gx=q s-|33S1 &F?s3H%K|Ht5TNVm-Z' );
define( 'SECURE_AUTH_SALT',  'W|wpT1f&&P~(iv[7laVAcT]~^;i(DDuUB6?-`A!<D})]*bLq3A^;.k):&x)}fWo_' );
define( 'LOGGED_IN_SALT',    '`fc^~g,`bg4Dyx8oB:rAr%H,?D7mzQ6P}1]Nr-RidPu}>rnvxDjL=|P^=X#ud^R%' );
define( 'NONCE_SALT',        '<^NRwn0l)]F>#~Bjn8Qp*0%_=U TS,, 4fU~q669ayR<IU*FVikq|JqdLaS=%tQm' );
define( 'WP_CACHE_KEY_SALT', 'c0PrQXj XUI;6~d(:O!Ht3bFwCp:%Vuk+kX$e>&5eILR32G~{Tv-.o}v_6?Z]OO{' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
