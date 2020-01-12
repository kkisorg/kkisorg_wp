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
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'kkisorg_kkisorg_wp' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', '127.0.0.1' );

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
define( 'AUTH_KEY',         'Z(gC@fvFwr }Jt=B:HR`zYpN2}-dT]uuN*/84|t,GE4.1ovSN?.{YnORX$AEcPs]' );
define( 'SECURE_AUTH_KEY',  '#>{J>j;`&O[3MCKTDG6hQQ@%ZJ1CXnPFrxmc@5]>W||p##E81fj2O%Dy)Y_b|5?+' );
define( 'LOGGED_IN_KEY',    'X-_9X1,, R?7x~`6HlNKvzyk8/GRtdZ;`0{WMzuil1^rhl4PI$%bz/ZODL3,;|Y8' );
define( 'NONCE_KEY',        ')_TakgA_lT3j(>.KH%zGY|jG1#Lv91jmjgtHtcR.gj%eoUBdvyhw:hq1K{SU``f$' );
define( 'AUTH_SALT',        'j!3IKGnR1A}Io^NyqApl&k!UO;r-uy?dq!9LC}pGt+eTmh+qbar5H$&/0xZ08M0y' );
define( 'SECURE_AUTH_SALT', '|#w! :yQi0vw?PHZJ@I!$443oQoT_jCAK>%iW/V8yuxvgB&qx4ekqggn3C.w$E$V' );
define( 'LOGGED_IN_SALT',   'nfLjgFi0,Kq;K=2/ENr,o]gk}JU*Q_6 f_>MQD8P_1>}1Udm?~cOJ=h)xM/`LEV]' );
define( 'NONCE_SALT',       '1b`I{eMqOHG{9*yNYCpvQ0_[z<3Fs,.5@iwC3VgN[E COxf#T<oED;>S>S#|>k(Q' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
