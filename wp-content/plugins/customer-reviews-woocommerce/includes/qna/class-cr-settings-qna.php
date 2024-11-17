<?php

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

if ( ! class_exists( 'CR_Qna_Settings' ) ):

class CR_Qna_Settings {

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
				$this->tab = 'qna';

				add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
				add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
				add_action( 'cr_save_settings_' . $this->tab, array( $this, 'save' ) );
		}

		public function register_tab( $tabs ) {
				$tabs[$this->tab] = __( 'Q & A', 'customer-reviews-woocommerce' );
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
				$this->settings = array(
						array(
								'title' => __( 'Questions and Answers', 'customer-reviews-woocommerce' ),
								'type'  => 'title',
								'desc'  => __( 'Add a tab with questions and answers to product pages on your website. Let your prospective customers ask questions about products and view questions asked by others. Boost sales by answering the questions and making sure that prospective customers have all the information available to make a purchase decision.', 'customer-reviews-woocommerce' ),
								'id'    => 'ivole_options'
						),
						array(
								'title'   => __( 'Questions and Answers', 'customer-reviews-woocommerce' ),
								'desc'    => sprintf( __( 'Enable this option to display a tab with questions and answers on product pages.', 'customer-reviews-woocommerce' ), '<a href="https://www.cusrev.com" target="_blank" rel="noopener noreferrer">', '</a>' ),
								'id'      => 'ivole_questions_answers',
								'default' => 'no',
								'type'    => 'checkbox'
						),
						array(
								'title'   => __( 'reCAPTCHA v3 for Q & A', 'customer-reviews-woocommerce' ),
								'desc'    => __( 'Enable reCAPTCHA v3 to eliminate SPAM questions and answers. You must enter a Site Key and a Secret Key in the fields below, if you want to use reCAPTCHA. You will receive these keys after registration at reCAPTCHA website.', 'customer-reviews-woocommerce' ),
								'id'      => 'ivole_qna_enable_captcha',
								'default' => 'no',
								'type'    => 'checkbox'
						),
						array(
								'title'    => __( 'reCAPTCHA v3 Site Key', 'customer-reviews-woocommerce' ),
								'type'     => 'text',
								'desc'     => __( 'If you would like to use reCAPTCHA v3, insert here the Site Key that you will receive after registration at the reCAPTCHA website.', 'customer-reviews-woocommerce' ),
								'default'  => '',
								'id'       => 'ivole_qna_captcha_site_key',
								'desc_tip' => true
						),
						array(
								'title'    => __( 'reCAPTCHA v3 Secret Key', 'customer-reviews-woocommerce' ),
								'type'     => 'text',
								'desc'     => __( 'If you would like to use reCAPTCHA v3, insert here the Secret Key that you will receive after registration at the reCAPTCHA website.', 'customer-reviews-woocommerce' ),
								'default'  => '',
								'id'       => 'ivole_qna_captcha_secret_key',
								'desc_tip' => true
						),
						array(
								'title'   => __( 'Display Count of Answered Questions', 'customer-reviews-woocommerce' ),
								'desc'    => __( 'Enable this option to display the count of answered questions next to the product rating and under the product name.', 'customer-reviews-woocommerce' ),
								'id'      => 'ivole_qna_count',
								'default' => 'no',
								'type'    => 'checkbox'
						),
						array(
								'title'   => __( 'Reply Notifications', 'customer-reviews-woocommerce' ),
								'desc'    => sprintf( __( 'Enable this option to send notifications when somebody replies to a question. The template of notifications can be configured on the <a href="%s">Emails</a> tab.', 'customer-reviews-woocommerce' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=emails' ) ),
								'id'      => 'ivole_qna_email_reply',
								'default' => 'no',
								'type'    => 'checkbox',
								'autoload' => false
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
