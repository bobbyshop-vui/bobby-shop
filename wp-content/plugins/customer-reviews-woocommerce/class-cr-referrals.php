<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Referrals' ) ) :

	class CR_Referrals {
	  public function __construct() {
			// Tracking of referrals only when referrals tracking is enabled
			if ( 'yes' === get_option( 'ivole_referrals_tracking', 'no' ) ) {
				// Track referrals
				add_filter( 'query_vars', array( $this, 'referral_session' ) );
				add_action( 'parse_query', array( $this, 'check_referral' ) );
				// Trigger for new order
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ), 10, 2 );
				// Triggers for paid orders
				add_action( 'woocommerce_order_status_processing', array( $this, 'referral_trigger' ) );
				add_action( 'woocommerce_order_status_completed', array( $this, 'referral_trigger' ) );
			}
	  }

		public function referral_trigger( $order_id ) {
			if( $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$referral_session = $order->get_meta( '_cr_referral_session', true );
					if( $referral_session ) {
						$license_key = get_option( 'ivole_license_key', '' );
						if( $license_key ) {
							$order_status = $order->get_status();
							if( 'processing' === $order_status || 'completed' === $order_status ) {
								$customer_email = '';
								$customer_first_name = '';
								$customer_last_name = '';
								$order_date = '';
								$order_currency = '';
								$user = NULL;
								$shipping_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );
								$temp_shipping_country = '';

								//check if registered customers option is used
								$registered_customers = false;
								if( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) {
									$registered_customers = true;
								}

								// get information about the order
								if( method_exists( $order, 'get_billing_email' ) ) {
									// get order details
									if( $registered_customers ) {
										$user = $order->get_user();
										if( $user ) {
											$customer_email = $user->user_email;
										} else {
											$customer_email = $order->get_billing_email();
										}
									} else {
										$customer_email = $order->get_billing_email();
									}
									$customer_first_name = $order->get_billing_first_name();
									$customer_last_name = $order->get_billing_last_name();
									$order_date = date_i18n( 'd.m.Y', strtotime( $order->get_date_created() ) );
									$order_currency = $order->get_currency();
									$temp_shipping_country = $order->get_shipping_country();
									if( strlen( $temp_shipping_country ) > 0 ) {
										$shipping_country = $temp_shipping_country;
									}
									//
									$data = array(
										'licenseKey' => $license_key,
										'shopDomain' => Ivole_Email::get_blogurl(),
										'referralSession' => $referral_session,
										'order' => array(
											'id' => strval( $order_id ),
											'customer' => array(
												'firstname' => $customer_first_name,
												'lastname' => $customer_last_name
											),
											'date' => $order_date,
											'currency' => $order_currency,
										 	'country' => $shipping_country,
										 	'total' => $order->get_total()
										)
									);
									$api_url = 'https://api.cusrev.com/v1/production/referral';
									$data_string = json_encode( $data );
									$ch = curl_init();
									curl_setopt( $ch, CURLOPT_URL, $api_url );
									curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
									curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
									curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
									curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
										'Content-Type: application/json',
										'Content-Length: ' . strlen( $data_string ) )
									);
									$result = curl_exec( $ch );
								}
							}
						}
					}
				}
			}
		}

		public function referral_session( $qvars ) {
			$qvars[] = 'referral_session';
    	return $qvars;
		}

		public function check_referral( $wp_query ) {
			$referral_session = get_query_var( 'referral_session', '' );
			$expires = 30 * DAY_IN_SECONDS; // 30 days
			$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : parse_url( get_option( 'siteurl' ), PHP_URL_HOST );

			if( $referral_session ) {
				//error_log( print_r( $referral_session, true ) );
				setcookie( 'cr_referral_session', strval( $referral_session ), array(
					'expires' => time() + $expires,
					'path' => '/',
					'domain' => $domain,
					'samesite' => 'Lax' )
				);
			}
		}

		public function update_order_meta( $order_id, $data ) {
			if( $order_id ) {
				$order = wc_get_order( $order_id );
				if( $order && isset( $_COOKIE['cr_referral_session'] ) ) {
					// If the referral cookie is present, save it as a meta field in the order
					$order->update_meta_data( '_cr_referral_session', sanitize_text_field( $_COOKIE['cr_referral_session'] ) );
					$order->save();
				}
			}
		}
	}

endif;
