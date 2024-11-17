<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( __DIR__ . '/includes/emails/class-cr-email-func.php' );
require_once( __DIR__ . '/includes/emails/class-cr-sender.php' );
require_once( __DIR__ . '/includes/emails/class-cr-email.php' );
require_once( __DIR__ . '/includes/emails/class-cr-email-coupon.php' );
require_once( __DIR__ . '/includes/emails/class-cr-phone-vldtr.php' );
require_once( __DIR__ . '/includes/emails/class-cr-wtsap.php' );
require_once( __DIR__ . '/includes/emails/class-cr-email-review-notification.php' );
require_once( __DIR__ . '/includes/emails/class-cr-wpmail-log.php' );
require_once('class-cr-referrals.php');
require_once( __DIR__ . '/includes/reminders/class-cr-manual.php' );
require_once( __DIR__ . '/includes/reminders/class-cr-admin-menu-reminders.php' );
require_once( __DIR__ . '/includes/reminders/class-cr-reminders-list-table.php' );
require_once( __DIR__ . '/includes/reminders/class-cr-reminders-log-table.php' );
require_once( __DIR__ . '/includes/reminders/class-cr-reminders-details.php' );
require_once( __DIR__ . '/includes/reminders/class-cr-local-forms.php' );
require_once( __DIR__ . '/includes/reminders/class-cr-local-forms-ajax.php' );
require_once( __DIR__ . '/includes/blocks/class-cr-all-reviews.php' );
require_once( __DIR__ . '/includes/blocks/class-cr-reviews-grid.php' );
require_once( __DIR__ . '/includes/blocks/class-cr-reviews-slider.php' );
require_once( __DIR__ . '/includes/blocks/class-cr-reviews-rating.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-admin-menu-reviews.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-ajax-reviews.php');
require_once( __DIR__ . '/includes/reviews/class-cr-reviews-list-table.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-reviews-media-download.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-reviews.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-custom-questions.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-endpoint.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-endpoint-replies.php');
require_once( __DIR__ . '/includes/reviews/class-cr-replies.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-reviews-notifications.php' );
require_once( __DIR__ . '/includes/settings/class-cr-admin.php' );
require_once( __DIR__ . '/includes/settings/class-cr-admin-menu-settings.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-review-reminder.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-review-extensions.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-forms.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-discount-tiers.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-review-discount.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-license.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-trust-badges.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-referrals.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-shortcodes.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-email-template.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-wa-template.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-emails.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-messages.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-cusrev.php' );
require_once( __DIR__ . '/includes/google/class-cr-structured-data.php' );
require_once( __DIR__ . '/includes/google/class-cr-xml-feeds.php' );
require_once( __DIR__ . '/includes/google/class-cr-google-shopping-feed.php' );
require_once( __DIR__ . '/includes/google/class-cr-google-shopping-prod-feed.php' );
require_once( __DIR__ . '/includes/google/class-cr-admin-menu-product-feed.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-feed-status.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-feed-categories.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-feed-identifiers.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-feed-attributes.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-feed-reviews.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-fields.php' );
require_once( __DIR__ . '/includes/misc/class-cr-admin-menu-diagnostics.php' );
require_once( __DIR__ . '/includes/import-export/class-cr-reviews-importer.php' );
require_once( __DIR__ . '/includes/import-export/class-cr-admin-menu-import.php' );
require_once( __DIR__ . '/includes/import-export/class-cr-export-reviews.php' );
require_once( __DIR__ . '/includes/import-export/class-cr-reviews-exporter.php' );
require_once( __DIR__ . '/includes/tags/class-cr-admin-menu-tags.php' );
require_once( __DIR__ . '/includes/tags/class-cr-tags.php' );
require_once( __DIR__ . '/includes/trust-badge/class-cr-trust-badge.php' );
require_once( __DIR__ . '/includes/trust-badge/class-cr-floating-trust-badge.php' );
require_once( __DIR__ . '/includes/qna/class-cr-qna.php' );
require_once( __DIR__ . '/includes/qna/class-cr-qna-list-table.php' );
require_once( __DIR__ . '/includes/qna/class-cr-admin-menu-qna.php' );
require_once( __DIR__ . '/includes/qna/class-cr-settings-qna.php' );
require_once( __DIR__ . '/includes/qna/class-cr-qna-shortcode.php' );
require_once( __DIR__ . '/includes/qna/class-cr-qna-email.php' );
require_once( __DIR__ . '/includes/analytics/class-cr-reminders-log.php' );

class Ivole {
	const CR_VERSION = '5.61.2';

	public function __construct() {
		if( function_exists( 'wc' ) ) {
			$cr_admin = new CR_Admin();
			$cr_sender = new CR_Sender();
			$cr_reviews = new CR_Reviews();
			$cr_endpoint = new CR_Endpoint();
			$cr_endpoint_replies = new CR_Endpoint_Replies();
			$cr_referrals = new CR_Referrals();
			$cr_structured_data = new CR_StructuredData();
			$cr_product_fields = new CR_Product_Fields();
			$cr_ajax_reviews = new CR_Ajax_Reviews();
			$cr_tags = new CR_Tags();
			$cr_qna = new CR_Qna();
			$cr_trust_badge = new CR_Trust_Badge();
			new CR_Reviews_Media_Download();
			new CR_XML_Feeds();
			new CR_Local_Forms_Ajax();
			new CR_Reviews_Notifications();
			new CR_WPMail_Log();

			$cr_all_reviews = new CR_All_Reviews();
			$cr_reviews_grid = new CR_Reviews_Grid();
			$cr_reviews_slider = new CR_Reviews_Slider();
			$cr_reviews_rating = new CR_Reviews_Rating();
			$cr_qna_shortcode = new CR_Qna_Shortcode( $cr_qna );

			if ( is_admin() ) {
				$reviews_admin_menu = new Ivole_Reviews_Admin_Menu();
				$reminders_admin_menu = new CR_Reminders_Admin_Menu();
				$tags_admin_menu = new CR_Tags_Admin_Menu();
				$qna_admin_menu = new CR_Qna_Admin_Menu();
				$product_feed_admin_menu = new CR_Product_Feed_Admin_Menu();
				$settings_admin_menu = new CR_Settings_Admin_Menu();
				$diagnostics_admin_menu = new CR_Diagnostics_Admin_Menu();
				$cr_manual = new CR_Manual();
				$reviews_importer = new CR_Reviews_Importer();
				$import_admin_menu = new CR_Import_Admin_Menu();
				$reviews_exporter = new CR_Reviews_Exporter();

				new CR_Review_Reminder_Settings( $settings_admin_menu );
				new CR_Review_Extensions_Settings( $settings_admin_menu );
				new CR_Forms_Settings( $settings_admin_menu );
				new CR_Review_Discount_Settings( $settings_admin_menu );
				new CR_License_Settings( $settings_admin_menu );
				new Ivole_Trust_Badges( $settings_admin_menu );
				new CR_Referrals_Settings( $settings_admin_menu );
				new CR_Qna_Settings( $settings_admin_menu );
				new CR_Shortcodes_Settings( $settings_admin_menu );
				new CR_Emails_Settings( $settings_admin_menu );
				new CR_Messages_Settings( $settings_admin_menu );
				new CR_CusRev_Settings( $settings_admin_menu );
				new CR_Status_Product_Feed( $product_feed_admin_menu );
				new CR_Categories_Product_Feed( $product_feed_admin_menu );
				new CR_Identifiers_Product_Feed( $product_feed_admin_menu );
				new CR_Attributes_Product_Feed( $product_feed_admin_menu );
				new CR_Reviews_Product_Feed( $product_feed_admin_menu );
				new CR_Export_Reviews( $import_admin_menu );

				$this->add_plugin_row_meta();
			}
		}
	}

	/**
	* Check installation cURL php extension
	* @return bool
	*/
	public static function is_curl_installed()
	{
		return in_array  ('curl', get_loaded_extensions() );
	}

	public function add_plugin_row_meta() {
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 4 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	public function plugin_row_meta( $links, $file ) {
		if ( 'customer-reviews-woocommerce/ivole.php' !== $file ) {
			return $links;
		}
		$row_meta = array(
			'docs'    => '<a href="' . esc_url( 'https://help.cusrev.com/' ) . '" aria-label="' . esc_attr__( 'View CusRev documentation', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Docs', 'customer-reviews-woocommerce' ) . '</a>',
			'support' => '<a href="' . esc_url( 'https://wordpress.org/support/plugin/customer-reviews-woocommerce/' ) . '" aria-label="' . esc_attr__( 'Visit community forums', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Community support', 'customer-reviews-woocommerce' ) . '</a>',
		);
		return array_merge( $links, $row_meta );
	}

	public function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		if ( 'customer-reviews-woocommerce/ivole.php' !== $plugin_file ) {
			return $actions;
		}
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=cr-reviews-settings' ) . '" aria-label="' . esc_attr__( 'View Customer Reviews settings', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Settings', 'customer-reviews-woocommerce' ) . '</a>',
		);
		return array_merge( $action_links, $actions );
	}

	public static function remove_class_filter( $hook_name, $class_name = '', $method_name = '', $priority = 10 ) {
		global $wp_filter;

		// Check that filter actually exists first
		if ( ! isset( $wp_filter[ $hook_name ] ) ) return false;

		/**
		 * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
		 * a simple array, rather it is an object that implements the ArrayAccess interface.
		 *
		 * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
		 *
		 * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
		 */
		if ( is_object( $wp_filter[ $hook_name ] ) && isset( $wp_filter[ $hook_name ]->callbacks ) ) {
				// Create $fob object from filter hook, to use below
				$fob = $wp_filter[ $hook_name ];
				$callbacks = &$wp_filter[ $hook_name ]->callbacks;
		} else {
				$callbacks = &$wp_filter[ $hook_name ];
		}

		// Exit if there aren't any callbacks for specified priority
		if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) return FALSE;

		// Loop through each filter for the specified priority, looking for our class & method
		foreach( (array) $callbacks[ $priority ] as $filter_id => $filter ) {

				// Filter should always be an array - array( $this, 'method' ), if not goto next
				if ( ! isset( $filter[ 'function' ] ) || ! is_array( $filter[ 'function' ] ) ) continue;

				// If first value in array is not an object, it can't be a class
				if ( ! is_object( $filter[ 'function' ][ 0 ] ) ) continue;

				// Method doesn't match the one we're looking for, goto next
				if ( $filter[ 'function' ][ 1 ] !== $method_name ) continue;

				// Method matched, now let's check the Class
				if ( get_class( $filter[ 'function' ][ 0 ] ) === $class_name ) {

						// WordPress 4.7+ use core remove_filter() since we found the class object
						if( isset( $fob ) ){
								// Handles removing filter, reseting callback priority keys mid-iteration, etc.
								$fob->remove_filter( $hook_name, $filter['function'], $priority );
						} else {
								// Use legacy removal process (pre 4.7)
								unset( $callbacks[ $priority ][ $filter_id ] );
								// and if it was the only filter in that priority, unset that priority
								if ( empty( $callbacks[ $priority ] ) ) {
										unset( $callbacks[ $priority ] );
								}
								// and if the only filter for that hook, set the hook to an empty array
								if ( empty( $callbacks ) ) {
										$callbacks = array();
								}
								// Remove this filter from merged_filters, which specifies if filters have been sorted
								unset( $GLOBALS['merged_filters'][ $hook_name ] );
						}

						return true;
				}
		}

		return false;
	}
}
