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
define( 'DB_NAME', 'epidxnewsroom' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'xIJQ>q FI%r.D__Lpwwzn_6WtPWO]ooA 9[s @:cq-FfPn(hcGQ)-.)q]-OEi9y_' );
define( 'SECURE_AUTH_KEY',  ':&q[i^}eL,mM|6{K>VFch+nZdmdDXI_8qwQr|YYNO)8UVwdE?Yk4^/,FEcpzf.8K' );
define( 'LOGGED_IN_KEY',    '2txEWBlo{c^|>{ISD ;N>1Bva<;.F/aML~w-g_|#]&!z9r]pQ9} #eV{nu*HYLTH' );
define( 'NONCE_KEY',        'QS9c^=)D!I.DWyLxU@D_)qz|byes+ U&CdM9Zs[4YS!rjd;OJW]Vhr;FZuDRl-c!' );
define( 'AUTH_SALT',        '@8V `n>^fEbU}=,xK9IP8.goU6zx(7eLmDryj|.1;moYlj+N* -8[-s-6y|t]YWd' );
define( 'SECURE_AUTH_SALT', 'my$*0#~$oor2q8EJHxS[mS6Q}oRw7z+F+%)3~Gt+p~Hj05i[~>xVwvj/D?X4m%dO' );
define( 'LOGGED_IN_SALT',   'gE$YVXv$Nz!Vd=>]k6ypoANu%y5D9{Pqegr2v{{fPSw]5{GC}#xO1[$7AqEq,8bi' );
define( 'NONCE_SALT',       'bzjZqEfc:HJKagM!?)viQE4K{$}-- @|yC6K` QRO$W;juB-&v1j<I7i=U8s6$|s' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'newsroom_';

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

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
