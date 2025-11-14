<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
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
define( 'AUTH_KEY',         'yEGw 4=aibFtyk@dkM$nq|v,8oj^xq_~=,BJ9l).t$R,p^<jHJOq:9Dt|# M2q[x' );
define( 'SECURE_AUTH_KEY',  '(OkUZnjlo2Qigk:S]+&r&jlTw)JFr$>-i/ZgshQ.}cGv}+-3D^p@mfGf#-,IIO6a' );
define( 'LOGGED_IN_KEY',    'Uk3|)C;[F|N^2aBb5Y|<1sH^GY,Yg:gdQnA62V|Rmy#D !0d0{TDwm*Lpg))8TL~' );
define( 'NONCE_KEY',        '*lW)caNotLQ3KP._Kp<_t4$^`;ZcJx;WT|/<rr7yr5NcdbQ`vfaKN+6%S^#;I/(f' );
define( 'AUTH_SALT',        'H4V4 3wkVrhfi&IjSBzM7~|le:tm|P$+UH6tcUxxQ)no5mrAX4l{cOxWL%Ajf S/' );
define( 'SECURE_AUTH_SALT', 'u~0Be=^C{Jn| ss*TwA`ho(tk]#tCe|mu~WksmFx.{,~z*)#@!zw+|_~g- #&az.' );
define( 'LOGGED_IN_SALT',   'tDKE?b].-KG6d~cyCP/Z9}G45B22c$YGa5,@DWpie_fw)^}&Izi!]uMAHhi.h;Jr' );
define( 'NONCE_SALT',       '?93:pCTR<L+uj `PTJ2T2?XvNq 6b@o0wTJ #v#In#Cj*9C!ZQ?~eg.+Ew[NrEC~' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', true );

/* Add any custom values between this line and the "stop editing" line. */

define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

