<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'epidxshop' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'xW?1Frj,&2K6NtNxd9/fUj)!dJ~2cD`QlZs#-Lnoz1IOL<N}<^>H@`!%==(6FTWS' );
define( 'SECURE_AUTH_KEY',  '|D@w@/U(72f~Z!h{JkE5yM|L#S@8J$K<b:$=|GB)z)DB+@f^k&1f^/ytcm6#o[?m' );
define( 'LOGGED_IN_KEY',    'EBva07H|/*_ENR$%Apr4vQg&LTg:(;v([:OJUobs1Md<*l}3n29 JKW<~c/=@URu' );
define( 'NONCE_KEY',        'nHa;RI64$EPB *]9LNA{2!+Jk@kt0I{!&|Z%L.&Q!Axo6S2ZU> Q|$Tl+KQ8bVc=' );
define( 'AUTH_SALT',        '04N_XYg^Ns _F?2j+V>L%!@3ev`JNCx%0X#,s(R@xh49vhzl8PB(y$bBl@YasXjY' );
define( 'SECURE_AUTH_SALT', ']e dk9q^Z@[cWsr>fREbasR85(/L2,=tYDE ZWBWi`,GG02[5]6I>/&|BUZb0h0C' );
define( 'LOGGED_IN_SALT',   'fPPnG`NRQUc2j&Z[#1bnd-~H3tt}yZNPucH@@. gY*>}RQ/1_pDN>@owN?:=NTG3' );
define( 'NONCE_SALT',       'l&K$qNd[rX~Xua2[ucDYW3hy!F)+Bpz3DDq4{m/0W@Fl`xB)8o9em$OK>BWUdT~t' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'shop_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
