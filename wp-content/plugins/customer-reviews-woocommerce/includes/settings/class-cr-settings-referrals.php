<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Referrals_Settings' ) ):

	class CR_Referrals_Settings {

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
			$this->tab = 'referrals';

			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_settings_' . $this->tab, array( $this, 'save' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Referral Program', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();
			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();
			WC_Admin_Settings::save_fields( $this->settings );
		}

		protected function init_settings() {
			$referral_link_1 = '<a href="https://www.cusrev.com" target="_blank" rel="noopener noreferrer">';
			$referral_link_2 = '<a href="https://www.cusrev.com" target="_blank" rel="noopener noreferrer">';
			$this->settings = array(
				array(
					'title' => __( 'Referral Program', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					/* translators: please keep %1$s and %2$s in the translated version */
					'desc'  => '<p>' . sprintf( __( 'Referral marketing is one of the most cost-effective ways to acquire new customers. It is based on the idea that your current customers will spread the word about your store and bring in (or refer) new customers. The problem is that it is not easy for them to do so. We help your customers to spread the word by showing their public reviews to other customers at %1$scusrev.com%2$s.', 'customer-reviews-woocommerce' ), $referral_link_1, '</a>' ) . '</p><p>' . sprintf( __( 'Tracking of referrals requires: (1) %1$sTrust Badges%2$s option has to be enabled, (2) a valid %3$slicense key%4$s (Free or Pro) has to be provided.', 'customer-reviews-woocommerce' ), '<a href="' . admin_url( 'admin.php?page=cr-reviews-settings&tab=trust_badges' ) . '">', '</a>', '<a href="' . admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ) . '">', '</a>' ) . '</p>',
					'id'    => 'ivole_options'
				),
				array(
					'title'   => __( 'Track Customer Referrals', 'customer-reviews-woocommerce' ),
					/* translators: please keep %1$s and %2$s in the translated version */
					'desc'    => sprintf( __( 'Enable this option to track orders placed by customers who were referred to your store from %1$scusrev.com%2$s. Tracking is implemented via a 30-day cookie created by the plugin for customers who are referred to your store.', 'customer-reviews-woocommerce' ), $referral_link_2, '</a>' ),
					'id'      => 'ivole_referrals_tracking',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'type' => 'sectionend',
					'id'   => 'ivole_options'
				)
			);
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

	}

endif;
