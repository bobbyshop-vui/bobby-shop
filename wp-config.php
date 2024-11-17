<?php
define( 'WP_CACHE', true );

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
define( 'DB_NAME', 'u126376622_H99JQ' );

/** Database username */
define( 'DB_USER', 'u126376622_xfKmm' );

/** Database password */
define( 'DB_PASSWORD', 'QVKaAmNaTQ' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

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
define( 'AUTH_KEY',          '[$>|h4n1AB2m}wg-/qPq#r82y1*xx_XiSm=`zSKt[.{SFT0>WIZQ_ Fys3p5P,>t' );
define( 'SECURE_AUTH_KEY',   'N{(~1BW$$XDh:*<9`uG)1/4KVLqhZT>6Z@Ig|/ZGO(K+LKcz F{ac>jwdV/X=1DJ' );
define( 'LOGGED_IN_KEY',     'SoDbmJCUwha<t$)Rc|Oyzz@L~xT+%&jC_aSHdK5m6-WC>NQEAxpe26*2_*VZ^5a9' );
define( 'NONCE_KEY',         'c0=7csK#X!pXF%vMNWuykV<}[6$}U]j|[Z6kPenRDngM%GuJYaI__6/8J#rc]AR9' );
define( 'AUTH_SALT',         '2:51%OGhNe>}fd0I9s?D-9,!J*eVDf8]F_wV#Q8j~~g eJ03K<oD?`l~B:9V!U@j' );
define( 'SECURE_AUTH_SALT',  '_$AQ[8^d#NZZC_)]]+%m0E)m[ELNq|O#AUT4/>:vl$n[G J^tf<sJJ:RwA[1s7Q:' );
define( 'LOGGED_IN_SALT',    '$):d,34D2=y:zls/WdD*aI^bERbO@559jsucMuA=(XO^}h:6kY n>Mb;{(wov8;d' );
define( 'NONCE_SALT',        'JL50/EnV}?L +i!b zPO?DWzAqqI9sX+)^`m`vCVD].FA2B-(vVEDm[a(`li<<pI' );
define( 'WP_CACHE_KEY_SALT', 'R8R*uOl(2o8>vV=k(?IW`b*t)h&N~*+nA%Da?k6k7$oRY}-g?Pj`vAa1+5Y)k$=)' );


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
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );


define( 'FS_METHOD', 'direct' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
