<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Checkout' ) ) :

	class CR_Checkout {
		public static $def_consent_text;
		public $consent_text;

		public function __construct() {
			if (
				'yes' === get_option( 'ivole_customer_consent', 'yes' ) ||
				(
					(
						'yes' === get_option( 'ivole_verified_reviews', 'no' ) ||
						'cr' === get_option( 'ivole_mailer_review_reminder', 'cr' )
					) &&
					CR_Review_Reminder_Settings::get_auto_show_consent()
				)
			) {
				self::$def_consent_text = __( 'Would you like to be invited to review your order? Check here to receive a message from CusRev (an independent reviews service) with a review form.', 'customer-reviews-woocommerce' );
				$this->consent_text = get_option( 'ivole_customer_consent_text', self::$def_consent_text );
				// WPML integration
				if ( has_filter( 'wpml_translate_single_string' ) && has_filter( 'wpml_current_language' ) ) {
					$wpml_current_language = apply_filters( 'wpml_current_language', NULL );
					$this->consent_text = apply_filters( 'wpml_translate_single_string', $this->consent_text, 'ivole', 'ivole_customer_consent_text', $wpml_current_language );
				}
				//
				if ( class_exists( 'WC_Blocks_Utils' ) ) {
					if ( WC_Blocks_Utils::has_block_in_page( wc_get_page_id( 'checkout' ), 'woocommerce/checkout' ) ) {
						if ( function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
							woocommerce_register_additional_checkout_field(
								array(
									'id'            => 'cusrev/checkout-consent',
									'label'         => $this->consent_text,
									'optionalLabel' => $this->consent_text,
									'location'      => 'order',
									'type'          => 'checkbox',
								)
							);
						}
					}
				}
				//
				add_action( 'woocommerce_checkout_terms_and_conditions', array( $this, 'display_cr_checkbox' ), 40 );
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'cr_checkbox_meta' ) );
				add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'cr_api_checkbox_meta' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'cr_checkout_style' ) );
				add_filter( 'kco_additional_checkboxes', array( $this, 'klarna_cr_checkbox' ) );
				add_action( 'kco_wc_payment_complete', array( $this, 'klarna_cr_payment_complete' ) );
			}
		}

		public function display_cr_checkbox() {
			$output = '<p class="cr-customer-consent">';
			$output .= '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">';
			$output .= '<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox cr-customer-consent-checkbox" name="cr_customer_consent" id="cr_customer_consent" />';
			$output .= '<span class="woocommerce-terms-and-conditions-checkbox-text">' . $this->consent_text . '</span>';
			$output .= '</label>';
			$output .= '<input type="hidden" name="cr_customer_consent_field" value="1" />';
			$output .= '</p>';
			echo apply_filters( 'cr_consent_checkbox', $output );
		}

		public function cr_checkbox_meta( $order_id ) {
			if( isset( $_POST['cr_customer_consent_field'] ) && $_POST['cr_customer_consent_field'] ) {
				$order = wc_get_order( $order_id );
				if( isset( $_POST['cr_customer_consent'] ) && $_POST['cr_customer_consent'] ) {
					$order->update_meta_data( '_ivole_cr_consent', 'yes' );
				} else {
					$order->update_meta_data( '_ivole_cr_consent', 'no' );
				}
				$order->save();
			}
		}

		public function cr_checkout_style() {
			if( is_checkout() ) {
				$assets_version = Ivole::CR_VERSION;
				wp_register_style( 'cr-frontend-css', plugins_url( '/css/frontend.css', dirname( dirname( __FILE__ ) ) ), array(), $assets_version, 'all' );
				wp_enqueue_style( 'cr-frontend-css' );
			}
		}

		public function klarna_cr_checkbox( $additional_checkboxes ) {
			$additional_checkboxes[] = array(
				'id'       => 'cr_customer_consent',
				'text'     => $this->consent_text,
				'checked'  => false,
				'required' => false
			);
			return $additional_checkboxes;
		}

		public function klarna_cr_payment_complete( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$klarna_order_id = $order->get_transaction_id();
				$klarna_order = KCO_WC()->api->get_klarna_order( $klarna_order_id );

				// V2 of Klarna Checkout API
				if ( isset( $klarna_order['merchant_requested'] ) ) {
					if ( isset( $klarna_order['merchant_requested']['additional_checkboxes'] ) ) {
						foreach ( $klarna_order['merchant_requested']['additional_checkboxes'] as $checkbox ) {
							if ( 'cr_customer_consent' === $checkbox['id'] ) {
								if ( $checkbox['checked'] ) {
									$order->update_meta_data( '_ivole_cr_consent', 'yes' );
								} else {
									$order->update_meta_data( '_ivole_cr_consent', 'no' );
								}
							}
						}
					}
				}

				// V3 of Klarna Checkout API
				if ( isset( $klarna_order['options'] ) ) {
					if ( isset( $klarna_order['options']['additional_checkboxes'] ) ) {
						foreach ( $klarna_order['options']['additional_checkboxes'] as $checkbox ) {
							if ( 'cr_customer_consent' === $checkbox['id'] ) {
								if ( $checkbox['checked'] ) {
									$order->update_meta_data( '_ivole_cr_consent', 'yes' );
								} else {
									$order->update_meta_data( '_ivole_cr_consent', 'no' );
								}
							}
						}
					}
				}
			}
		}

		public function cr_api_checkbox_meta( $order ) {
			if ( $order instanceof \WC_Order ) {
				$consent = 'no';

				if (
					class_exists( 'Automattic\WooCommerce\Blocks\Package' ) &&
					class_exists( 'Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields' )
				) {
					$checkout_fields = Automattic\WooCommerce\Blocks\Package::container()->get( Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );
					$cr_consent_field = $checkout_fields->get_field_from_object( 'cusrev/checkout-consent', $order );
					if ( $cr_consent_field ) {
						$consent = 'yes';
					}
				}

				$order->update_meta_data( '_ivole_cr_consent', $consent );
				$order->save();
			}
		}
	}

endif;
