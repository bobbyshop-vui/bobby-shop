<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Review_Extensions_Settings' ) ):

	class CR_Review_Extensions_Settings {

		/**
		* @var CR_Settings_Admin_Menu The instance of the settings admin menu
		*/
		protected $settings_menu;

		/**
		* @var string The slug of this tab
		*/
		protected $tab;

		/**
		* @var array The fields for this tab
		*/
		protected $settings;

		public function __construct( $settings_menu ) {
			$this->settings_menu = $settings_menu;

			$this->tab = 'review_extensions';

			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_settings_' . $this->tab, array( $this, 'save' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Review Extensions', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();

			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();

			//removing spaces
			if( !empty( $_POST ) && isset( $_POST['ivole_verified_owner'] ) ) {
				$_POST['ivole_verified_owner'] = trim($_POST['ivole_verified_owner']);
			}

			WC_Admin_Settings::save_fields( $this->settings );
		}

		protected function init_settings() {
			$display_setting_description = '<p>' .
				__( 'The plugin uses the standard WooCommerce functionality to display reviews on products pages. By default, visual appearance of reviews is controlled by WooCommerce and your active WordPress theme. There is also an option to enable an enhanced visual style for display of reviews on product pages.', 'customer-reviews-woocommerce' ) .
				'</p><p>' .
				sprintf( __( 'If you would like to display reviews in locations other than the standard \'Reviews\' tab on product pages, consider using <a href="%s">shortcodes</a> or Gutenberg blocks provided by the plugin.', 'customer-reviews-woocommerce' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=shortcodes' ) ) .
				'</p>';
			$ivole_ajax_reviews = get_option( 'ivole_ajax_reviews', 'no' );
			$this->settings = array(
				array(
					'title' => __( 'Display of Customer Reviews', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => $display_setting_description,
					'id'    => 'cr_display_section'
				),
				array(
					'title'    => __( 'Visual Style', 'customer-reviews-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( '', 'customer-reviews-woocommerce' ),
					'default'  => 'recent',
					'id'       => 'ivole_ajax_reviews',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'options'  => array(
						'no'  => __( 'WooCommerce (basic user interface)', 'customer-reviews-woocommerce' ),
						'yes' => __( 'CusRev (enhanced user interface)', 'customer-reviews-woocommerce' )
					)
				)
			);
			if( 'yes' === $ivole_ajax_reviews ) {
				$this->settings[] = array(
					'title'    => __( 'Default Quantity of Reviews', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Specify the default number of reviews that will be shown during the initial product page load.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_ajax_reviews_per_page',
					'default'  => 5,
					'type'     => 'number',
					'desc_tip' => true
				);
				$sorting_options = array(
					'recent'  => __( 'Recent reviews first', 'customer-reviews-woocommerce' ),
					'ratinghigh' => __( 'Reviews with the highest rating first', 'customer-reviews-woocommerce' ),
					'ratinglow' => __( 'Reviews with the lowest rating first', 'customer-reviews-woocommerce' )
				);
				// if voting for reviews is enabled, add an option to sort reviews by the helpful rating
				if ( 'yes' === get_option( 'ivole_reviews_voting', 'no' ) ) {
					$sorting_options['helpful'] = __( 'Most helpful reviews first', 'customer-reviews-woocommerce' );
				}
				$this->settings[] = array(
					'title'    => __( 'Default Sorting Order', 'customer-reviews-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( 'Define how reviews are sorted by default. The option to vote for reviews must be enabled to show the most helpful reviews first.', 'customer-reviews-woocommerce' ),
					'default'  => 'recent',
					'id'       => 'ivole_ajax_reviews_sort',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'options'  => $sorting_options
				);
			}
			$this->settings[] = array(
				'type' => 'sectionend',
				'id'   => 'cr_display_section'
			);

			$this->settings[] = array(
				'title' => __( 'Extensions for Customer Reviews', 'customer-reviews-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'The plugin is based on the standard WooCommerce reviews functionality. Here, you can configure various extensions for standard WooCommerce reviews.', 'customer-reviews-woocommerce' ),
				'id'    => 'cr_ext_options'
			);
			$this->settings[] = array(
				'title'         => __( 'Disable Lightbox', 'customer-reviews-woocommerce' ),
				'desc'          => __( 'Disable lightboxes for images attached to reviews (not recommended). Use this option only if your theme generates lightboxes for any picture on the website and this leads to two lightboxes shown after clicking on an image attached to a review.', 'customer-reviews-woocommerce' ),
				'id'            => 'ivole_disable_lightbox',
				'default'       => 'no',
				'type'          => 'checkbox'
			);
			$this->settings[] = array(
				'title'   => __( 'Reviews Summary Bar', 'customer-reviews-woocommerce' ),
				'desc'    => __( 'Enable display of a histogram table with a summary of reviews on a product page.', 'customer-reviews-woocommerce' ),
				'id'      => 'ivole_reviews_histogram',
				'default' => 'no',
				'type'    => 'checkbox'
			);
			$this->settings[] = array(
				'title'    => __( 'Vote for Reviews', 'customer-reviews-woocommerce' ),
				'desc'     => __( 'Enable people to upvote or downvote reviews. The plugin allows one vote per review per person. If the person is a guest, the plugin uses cookies and IP addresses to identify this visitor.', 'customer-reviews-woocommerce' ),
				'id'       => 'ivole_reviews_voting',
				'default'  => 'no',
				'type'     => 'checkbox'
			);
			$this->settings[] = array(
				'title'   => __( 'Remove Plugin\'s Branding', 'customer-reviews-woocommerce' ),
				'desc'    => __( 'Enable this option to remove plugin\'s branding ("Powered by Customer Reviews plugin") from the reviews summary bar. If you like our plugin and would like to support us, please disable this checkbox.', 'customer-reviews-woocommerce' ),
				'id'      => 'ivole_reviews_nobranding',
				'default' => 'yes',
				'type'    => 'checkbox'
			);
			$this->settings[] = array(
				'title'    => __( 'Verified Owner', 'customer-reviews-woocommerce' ),
				'type'     => 'text',
				'desc'     => __( 'Replace the standard ‘verified owner’ label that WooCommerce adds to customer reviews with a custom one. If this field is blank, the standard WooCommerce label will be used.', 'customer-reviews-woocommerce' ),
				'default'  => '',
				'id'       => 'ivole_verified_owner',
				'desc_tip' => true
			);
			$avatars_display_options = array(
				'standard'  => __( 'Standard', 'customer-reviews-woocommerce' ),
				'initials' => __( 'Initials', 'customer-reviews-woocommerce' )
			);
			if( 'yes' === $ivole_ajax_reviews ) {
				$avatars_display_options['hidden'] = __( 'Hidden', 'customer-reviews-woocommerce' );
			}
			$this->settings[] = array(
				'title'    => __( 'Customer Avatars', 'customer-reviews-woocommerce' ),
				'type'     => 'select',
				'desc'     => __( 'Choose how customer avatars are displayed on WooCommerce single product pages. You can use either the standard WordPress avatars or avatars created based on initial letters of customer names.', 'customer-reviews-woocommerce' ),
				'default'  => 'standard',
				'id'       => 'ivole_avatars',
				'desc_tip' => true,
				'class'    => 'wc-enhanced-select',
				'options'  => $avatars_display_options
			);
			$this->settings[] = array(
				'type' => 'sectionend',
				'id'   => 'cr_ext_options'
			);
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

	}

endif;
