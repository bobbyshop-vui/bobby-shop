<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_WA_Template' ) ):

	class CR_WA_Template {

		private $name = '';
		private $template_base = '';
		private $fields;

		public function __construct( $template_name ) {
			$this->name = $template_name;
			$this->template_base = dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/';
		}

		public function get_title() {
			$title = '';
			switch( $this->name ) {
				case 'wa_review_reminder':
					$title = __( 'Review Reminder (Click to Chat)', 'customer-reviews-woocommerce' );
					break;
				default:
					break;
			}
			return apply_filters(
				'cr_settings_wa_template_title',
				$title,
				$this->name
			);
		}

		public function get_description() {
			$description = '';
			switch( $this->name ) {
				case 'wa_review_reminder':
					$description = __( 'Review reminders include an invitation to write a review. They are sent to customers who recently purchased something from your store.', 'customer-reviews-woocommerce' );
					break;
				default:
					break;
			}
			return apply_filters(
				'cr_settings_wa_template_description',
				$description,
				$this->name
			);
		}

		public function is_enabled() {
			$enabled = false;
			switch( $this->name ) {
				case 'wa_review_reminder':
					$enabled = 'yes' === get_option( 'ivole_enable_manual', 'yes' ) ? true : false;
					break;
				default:
					break;
			}
			return apply_filters(
				'cr_settings_wa_template_enabled',
				$enabled,
				$this->name
			);
		}

		public function get_message() {
			$message = '';
			switch( $this->name ) {
				case 'wa_review_reminder':
					$message = get_option( 'ivole_wa_message', CR_Wtsap::$default_body );
					break;
				default:
					break;
			}
			return apply_filters(
				'cr_settings_wa_template_message',
				$message,
				$this->name
			);
		}

		private function init_fields() {
			$this->fields = array();

			// Message
			switch( $this->name ) {
				case 'wa_review_reminder':
					$id = 'ivole_wa_message';
					$default = CR_Wtsap::$default_body;
					$variables = array(
						'<code>{site_title}</code> - ' . __( 'The title of your WordPress website.', 'customer-reviews-woocommerce' ),
						'<code>{customer_first_name}</code> - ' . __( 'The first name of the customer who purchased from your store.', 'customer-reviews-woocommerce' ),
						'<code>{customer_last_name}</code> - ' . __( 'The last name of the customer who purchased from your store.', 'customer-reviews-woocommerce' ),
						'<code>{customer_name}</code> - ' . __( 'The full name of the customer who purchased from your store.', 'customer-reviews-woocommerce' ),
						'<code>{order_id}</code> - ' . __( 'The order number for the purchase.', 'customer-reviews-woocommerce' ),
						'<code>{order_date}</code> - ' . __( 'The date that the order was made.', 'customer-reviews-woocommerce' ),
						'<code>{list_products}</code> - ' . __( 'A name and price list of the products purchased.', 'customer-reviews-woocommerce' ),
						'<code>{review_form}</code> - ' . __( 'A link to an aggregated review form.', 'customer-reviews-woocommerce' )
					);
					break;
				default:
					break;
			}
			$this->fields[45] = array(
				'title'    => __( 'Message', 'customer-reviews-woocommerce' ),
				'type'     => 'textvars',
				'desc'     => __( 'Message that will be sent to customers via WhatsApp Click to Chat.', 'customer-reviews-woocommerce' ),
				'id'       => $id,
				'desc_tip' => true,
				'default' => $default,
				'autoload' => false,
				'variables' => $variables
			);

			// Send Test
			$this->fields[70] = array(
				'title' => __( 'Send Test', 'customer-reviews-woocommerce' ),
				'type' => 'watest',
				'desc' => __( 'Send a test message to this phone number by WhatsApp. You must save changes before sending a test message.', 'customer-reviews-woocommerce' ),
				'default' => '',
				'placeholder' => 'Phone number',
				'css' => 'min-width:300px;',
				'desc_tip' => true,
				'class' => 'cr-test-wa-input'
			);

			$this->fields = apply_filters( 'cr_settings_wa_template', $this->fields, $this->name );
			ksort( $this->fields );
		}

		public function output_fields() {
			echo '<h2>' . esc_html( $this->get_title() );
			wc_back_link( __( 'Return to messages', 'customer-reviews-woocommerce' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=messages' ) );
			echo '</h2>';
			echo wpautop( wp_kses_post( $this->get_description() ) );
			$this->init_fields();
			echo '<table class="form-table">';
			WC_Admin_Settings::output_fields( $this->fields );
			echo '</table>';
		}

		public function save_fields() {
			$this->init_fields();
			WC_Admin_Settings::save_fields( $this->fields );
		}

	}

endif;
