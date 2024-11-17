<?php
/*
Plugin Name: Customer Reviews for WooCommerce
Description: Customer Reviews for WooCommerce plugin helps you get more customer reviews for your shop by sending automated reminders and coupons.
Plugin URI: https://wordpress.org/plugins/customer-reviews-woocommerce/
Version: 5.61.2
Author: CusRev
Author URI: https://www.cusrev.com/business/
Text Domain: customer-reviews-woocommerce
Domain Path: /languages
Requires at least: 4.5
WC requires at least: 3.6
WC tested up to: 9.3
License: GPLv3

Customer Reviews for WooCommerce is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

Customer Reviews for WooCommerce is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Customer Reviews for WooCommerce. If not, see https://www.gnu.org/licenses/gpl.html.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'IVOLE_CONTENT_DIR' ) ) {
	$cr_upload_dir = wp_upload_dir();
	if( $cr_upload_dir && isset( $cr_upload_dir['basedir'] ) ) {
		define( 'IVOLE_CONTENT_DIR', $cr_upload_dir['basedir'] . '/cr' );
	} elseif ( defined( 'UPLOADS' ) ) {
		$uploads = untrailingslashit( UPLOADS );
		define( 'IVOLE_CONTENT_DIR', $uploads . '/cr' );
	} else {
		define( 'IVOLE_CONTENT_DIR', WP_CONTENT_DIR . '/uploads/cr' );
	}
}

require_once( 'class-ivole.php' );
require_once( __DIR__ . '/includes/misc/class-cr-qtranslate.php' );
require_once( __DIR__ . '/includes/misc/class-cr-wpml.php' );
require_once( __DIR__ . '/includes/trust-badge/class-cr-verified-reviews.php' );
require_once( __DIR__ . '/includes/misc/class-cr-checkout.php' );

/**
 * Check if WooCommerce is active
**/
$cr_activated_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
if (
	in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ||
	( is_multisite() && isset( $cr_activated_plugins['woocommerce/woocommerce.php'] ) )
) {
	add_action( 'init', 'cusrev_init', 9 );
	add_action( 'plugins_loaded', 'cr_plugins_loaded' );
	add_action( 'woocommerce_init', 'cr_woocommerce_init' );
	add_action( 'after_setup_theme', 'cr_setup_theme', 2 );

	function cusrev_init() {
		load_plugin_textdomain( 'customer-reviews-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/languages' );

		if ( "" == ivole_get_site_url() ) {
			ivole_set_duplicate_site_url_lock();
		}

		$cusrev = new Ivole();
	}

	function cr_plugins_loaded() {
		$cr_qtranslate = new CR_QTranslate();
		if( is_admin() || wp_doing_cron() ) {
			CR_Reviews_Importer::init_background_importer();
			CR_Reviews_Exporter::init_background_exporter();
		}
	}

	function cr_woocommerce_init() {
		$cr_checkout = new CR_Checkout();
	}

	function cr_setup_theme() {
		if (
			1 === preg_match( '~' . CR_Local_Forms::FORMS_SLUG . '/(?P<form>[\w]{13})/?\?.*r=(?P<email>[\w]{16})|' . CR_Local_Forms::FORMS_SLUG . '/(?P<form>' . CR_Local_Forms::TEST_FORM . ')/?\?.*r=(?P<email>[\w]{16})~iJ', $_SERVER['REQUEST_URI'], $matches ) ||
			1 === preg_match( '~' . CR_Local_Forms::FORMS_SLUG . '/(?P<form>[\w]{13})/?|' . CR_Local_Forms::FORMS_SLUG . '/(?P<form>' . CR_Local_Forms::TEST_FORM . ')/?~iJ', $_SERVER['REQUEST_URI'], $matches )
		) {
			if ( isset( $matches['form'] ) ) {
				$ext_id = isset( $matches['email'] ) ? $matches['email'] : '';
				$cr_local_forms = new CR_Local_Forms( $matches['form'], $ext_id );
				$cr_local_forms->output();
				exit();
			}
		} elseif (
			1 === preg_match( '~' . CR_Local_Forms::PIXEL_SLUG . '/(?P<pixel>[\w]{16}).png|' . CR_Local_Forms::PIXEL_SLUG . '/(?P<pixel>' . CR_Local_Forms::TEST_FORM . ').png~iJ', $_SERVER['REQUEST_URI'], $matches )
		) {
			if ( isset( $matches['pixel'] ) ) {
				CR_Local_Forms::render_pixel( $matches['pixel'] );
				exit();
			}
		}
	}

	add_action( 'before_woocommerce_init', function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	} );

}

add_shortcode( 'cusrev_reviews', 'ivole_reviews_shortcode' );

function ivole_reviews_shortcode( $atts, $content )
{
	$shortcode_enabled = get_option( 'ivole_reviews_shortcode', 'no' );
	if( $shortcode_enabled === 'no' ) {
		return;
	} else {
		extract( shortcode_atts( array( 'comment_file' => '/comments.php' ), $atts ) );
		$content = ivole_return_comment_form( $comment_file );
	}
	return $content;
}

function ivole_return_comment_form( $comment_file )
{
	if ( 0 !== validate_file( wp_normalize_path( $comment_file ) ) ) {
		return '';
	}
	ob_start();
	comments_template( $comment_file );
	$form = ob_get_contents();
	ob_end_clean();
	return $form;
}

register_activation_hook( __FILE__, 'ivole_activation_hook' );
function ivole_activation_hook() {
	update_option( 'ivole_activation_notice', 1 );
}

//---------Admin notice for staging----------
function ivole_general_admin_notice()
{
	if ( ivole_is_duplicate_site() && current_user_can( 'manage_options' ) ) {

		if ( ! empty( $_REQUEST['_wcsnonce'] ) && wp_verify_nonce( $_REQUEST['_wcsnonce'], 'ivole_duplicate_site' ) && isset( $_GET['ivole_duplicate_site'] ) ) {

			if ( 'update' === $_GET['ivole_duplicate_site'] ) {
				ivole_set_duplicate_site_url_lock();
			} elseif ( 'ignore' === $_GET['ivole_duplicate_site'] ) {
				update_option( 'ivole_ignore_duplicate_siteurl_notice', ivole_get_current_sites_duplicate_lock() );
				update_option( 'ivole_enable', 'no' );
			}
			wp_safe_redirect( remove_query_arg( array( 'ivole_duplicate_site', '_wcsnonce' ) ) );

		} elseif ( ivole_get_current_sites_duplicate_lock() !== get_option( 'ivole_ignore_duplicate_siteurl_notice' ) ) { ?>

			<div id="message" class="error">
				<p><?php
					printf( esc_html__( 'It looks like this site has moved or is a duplicate site. %1$sCustomer Reviews for WooCommerce%2$s has disabled sending automatic review reminder emails on this site to prevent duplicate reminders from a staging or test environment.', 'customer-reviews-woocommerce' ), '<strong>', '</strong>' ); ?></p>
				<div style="margin: 5px 0;">
					<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ivole_duplicate_site', 'ignore' ), 'ivole_duplicate_site', '_wcsnonce' ) ); ?>"><?php esc_html_e( 'Hide this message (but don\'t enable automatic review reminders)', 'customer-reviews-woocommerce' ); ?></a>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ivole_duplicate_site', 'update' ), 'ivole_duplicate_site', '_wcsnonce' ) ); ?>"><?php esc_html_e( 'Enable automatic review reminders', 'customer-reviews-woocommerce' ); ?></a>
				</div>
			</div>
		<?php
		}
	}
}

add_action('admin_notices', 'ivole_general_admin_notice');

/**
 * Returns CR record of the site URL for this site
 */
function ivole_get_site_url( $blog_id = null, $path = '', $scheme = null ) {
	if ( empty( $blog_id ) || ! is_multisite() ) {
		$url = get_option( 'ivole_siteurl' );
	} else {
		switch_to_blog( $blog_id );
		$url = get_option( 'ivole_siteurl' );
		restore_current_blog();
	}

	// Remove the prefix used to prevent the site URL being updated on WP Engine
	$url = str_replace( '_[ivole_siteurl]_', '', $url );

	$url = set_url_scheme( $url, $scheme );

	if ( ! empty( $path ) && is_string( $path ) && strpos( $path, '..' ) === false ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	return $url;
}

/**
 * Checks if the WordPress site URL is the same as the URL for the site CR normally
 * runs on. Useful for checking if automatic reminders should be disabled.
 */
function ivole_is_duplicate_site() {

	// WPML compatibility for situation when different languages are assigned to different domains but share the same database
	if( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
		return false;
	}

	if ( defined( 'WP_SITEURL' ) ) {
		$site_url = WP_SITEURL;
	} else {
		$site_url = get_site_url();
	}

	$wp_site_url_parts  = wp_parse_url( $site_url );
	$ivole_site_url_parts = wp_parse_url( ivole_get_site_url() );

	if ( ! isset( $wp_site_url_parts['path'] ) && ! isset( $ivole_site_url_parts['path'] ) ) {
		$paths_match = true;
	} elseif ( isset( $wp_site_url_parts['path'] ) && isset( $ivole_site_url_parts['path'] ) && $wp_site_url_parts['path'] == $ivole_site_url_parts['path'] ) {
		$paths_match = true;
	} else {
		$paths_match = false;
	}

	if ( isset( $wp_site_url_parts['host'] ) && isset( $ivole_site_url_parts['host'] ) && $wp_site_url_parts['host'] == $ivole_site_url_parts['host'] ) {
		$hosts_match = true;
	} else {
		$hosts_match = false;
	}

	// Check the host and path, do not check the protocol/scheme to avoid issues with WP Engine and other occasions where the WP_SITEURL constant may be set, but being overridden (e.g. by FORCE_SSL_ADMIN)
	if ( $paths_match && $hosts_match ) {
		$is_duplicate = false;
	} else {
		$is_duplicate = true;
	}

	return $is_duplicate;
}

/**
 * Creates a URL based on the current site's URL that can be used to prevent duplicate payments from staging sites.
 *
 * The URL can not simply be the site URL, e.g. http://example.com, because WP Engine replaces all instances of the site URL in the database
 * when creating a staging site. As a result, we obfuscate the URL by inserting '_[ivole_siteurl]_' into the middle of it.
 *
 * Why not just use a hash? Because keeping the URL in the value allows for viewing and editing the URL directly in the database.
 */
function ivole_get_current_sites_duplicate_lock() {

	if ( defined( 'WP_SITEURL' ) ) {
		$site_url = WP_SITEURL;
	} else {
		$site_url = get_site_url();
	}

	return substr_replace( $site_url, '_[ivole_siteurl]_', intval( strlen( $site_url ) / 2 ), 0 );
}

function ivole_set_duplicate_site_url_lock() {
	update_option( 'ivole_siteurl', ivole_get_current_sites_duplicate_lock() );
	update_option( 'ivole_ignore_duplicate_siteurl_notice', '' );
}

// deactivation clean-up
register_deactivation_hook( __FILE__, 'cr_deactivate_the_plugin' );
function cr_deactivate_the_plugin() {
	wp_unschedule_hook( 'ivole_send_reminder' );
	// deactivate products XML feed
	$feed = new CR_Google_Shopping_Prod_Feed();
	$feed->deactivate();
	update_option( 'ivole_product_feed', 'no' );
	// deactivate product reviews XML feed
	$feed_reviews = new CR_Google_Shopping_Feed();
	$feed_reviews->deactivate();
	update_option( 'ivole_google_generate_xml_feed', 'no' );
	// deactivate the live mode for verified reviews
	update_option( 'ivole_verified_live_mode', 'no' );
}
