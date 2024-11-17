<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Wtsap' ) ) :

class CR_Wtsap {

	public $to;
	public $phone;
	public $phone_country;
	public $language;
	public $find = array();
	public $replace = array();
	public static $wame = 'https://wa.me/';
	public static $default_body = "Hi {customer_first_name}, thank you for shopping at {site_title}! Could you help us and other customers by reviewing products that you recently purchased? It only takes a minute and it would really help others. Many thanks! Click here to leave a review: {review_form}";
	/**
	 * Constructor.
	 */
	public function __construct( $order_id = 0 ) {
		$this->form_header      = strval( get_option( 'ivole_form_header', __( 'How did we do?', 'customer-reviews-woocommerce' ) ) );
		$this->form_body        = strval( get_option( 'ivole_form_body', __( 'Please review your experience with products and services that you purchased at {site_title}.', 'customer-reviews-woocommerce' ) ) );
		$this->find['site-title'] = '{site_title}';
		$this->replace['site-title'] = Ivole_Email::get_blogname();
		$this->find['customer-first-name']  = '{customer_first_name}';
		$this->find['customer-last-name'] = '{customer_last_name}';
		$this->find['customer-name'] = '{customer_name}';
		$this->find['order-id'] = '{order_id}';
		$this->find['order-date'] = '{order_date}';
		$this->find['list-products'] = '{list_products}';
		$this->find['review-form'] = '{review_form}';
		$this->find['coupon-code'] = '{coupon_code}';
		$this->find['discount-amount'] = '{discount_amount}';

		// fetch language - either from the plugin's option or from WordPress standard locale
		if ( 'yes' !== get_option( 'ivole_verified_reviews', 'no' ) ) {
			$wp_locale = get_locale();
			$wp_lang = explode( '_', $wp_locale );
			if( is_array( $wp_lang ) && 0 < count( $wp_lang ) ) {
				$this->language = strtoupper( $wp_lang[0] );
			} else {
				$this->language = 'EN';
			}
		} else {
			$this->language = get_option( 'ivole_language', 'EN' );
		}

		//qTranslate integration
		if( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			$this->form_header = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->form_header );
			$this->form_body = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->form_body );
			if( 'QQ' === $this->language ) {
				global $q_config;
				$this->language = strtoupper( $q_config['language'] );
			}
		}

		$order = false;
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
		}

		//WPML integration
		if ( has_filter( 'wpml_translate_single_string' ) && defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
			$wpml_current_language = apply_filters( 'wpml_current_language', NULL );
			if ( $order ) {
				$wpml_current_language = $order->get_meta( 'wpml_language', true );
			}
			$this->form_header = apply_filters( 'wpml_translate_single_string', $this->form_header, 'ivole', 'ivole_form_header', $wpml_current_language );
			$this->form_body = apply_filters( 'wpml_translate_single_string', $this->form_body, 'ivole', 'ivole_form_body', $wpml_current_language );
			if ( $wpml_current_language ) {
				$this->language = strtoupper( $wpml_current_language );
			}
		}

		//Polylang integration
		if( function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post_language' ) && function_exists( 'pll_translate_string' ) ) {
			$polylang_current_language = pll_current_language();
			if( $order_id ) {
				$polylang_current_language = pll_get_post_language( $order_id );
			}
			$this->form_header = pll_translate_string( $this->form_header, $polylang_current_language );
			$this->form_body = pll_translate_string( $this->form_body, $polylang_current_language );
			$this->language = strtoupper( $polylang_current_language );
		}

		// TranslatePress integration
		if( function_exists( 'trp_translate' ) ) {
			$trp_order_language = '';
			if ( $order ) {
				$trp_order_language = $order->get_meta( 'trp_language', true );
			}
			if( $trp_order_language ) {
				$this->form_header = trp_translate( $this->form_header, $trp_order_language, false );
				$this->form_body = trp_translate( $this->form_body, $trp_order_language, false );
				$this->language = strtoupper( substr( $trp_order_language, 0, 2 ) );
			}
		}

		//a safety check if some translation plugin removed language
		if ( empty( $this->language ) || 'WPML' === $this->language ) {
			$this->language = 'EN';
		}

		// map language codes returned by translation plugins that include '-' like PT-PT
		$this->language = CR_Email_Func::cr_map_language( $this->language );
	}

	public function get_review_form( $order_id, $schedule ) {
		$data_for_sending = $this->get_data_for_sending( $order_id, $schedule );

		if ( 0 === $data_for_sending[0] ) {
			$data = $data_for_sending[1];
			$form_result = CR_Local_Forms::save_form(
				$data['order']['id'],
				array(
					'firstname' => $data['customer']['firstname'],
					'lastname' => $data['customer']['lastname'],
					'email' => $data['email']['to'],
				),
				$data['form']['header'],
				$data['form']['description'],
				$data['order']['items'],
				false,
				$data['language'],
				null
			);

			if ( 0 !== $form_result['code'] ) {
				return array( 9, 'Error: ' . $form_result['text'] );
			}

			// create a message
			$this->replace['review-form'] = esc_url( $form_result['text'] );
			$message = $this->get_content();
			$message = $this->replace_variables( $message );

			// create a link
			$link = self::$wame . $this->phone . '?text=' . urlencode( $message );

			return array(
				0,
				$link,
				$this->phone
			);
		} else {
			return $data_for_sending;
		}
	}

	public function get_data_for_sending( $order_id, $schedule ) {
		$comment_required = get_option( 'ivole_form_comment_required', 'no' );
		if( 'no' === $comment_required ) {
			$comment_required = 0;
		} else {
			$comment_required = 1;
		}

		$shop_rating = 'yes' === get_option( 'ivole_form_shop_rating', 'no' ) ? true : false;
		$allowMedia = 'yes' === get_option( 'ivole_form_attach_media', 'no' ) ? true : false;
		$ratingBar = 'star' === get_option( 'ivole_form_rating_bar', 'smiley' ) ? 'star' : 'smiley';
		$geolocation = 'yes' === get_option( 'ivole_form_geolocation', 'no' ) ? true : false;

		if ( $order_id ) {

			$order = wc_get_order( $order_id );
			if ( ! $order  ) {
				// if no order exists with the provided $order_id, then we cannot create a review form
				return array( 1, sprintf( __( 'Error: order %s does not exist', 'customer-reviews-woocommerce' ), $order_id ) );
			}

			//check if Limit Number of Reviews option is used
			if( 'yes' === get_option( 'ivole_limit_reminders', 'yes' ) ) {
				//check how many reminders have already been sent for this order (if any)
				$reviews = $order->get_meta( '_ivole_review_reminder', true );
				if( $reviews >= 1 ) {
					//if more than one, then we should not create a review form
					return array( 2, __( 'Error: an option to limit review reminders is enabled in the settings', 'customer-reviews-woocommerce' ) );
				}
			}

			//check if registered customers option is used
			$registered_customers = false;
			if( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) {
				$registered_customers = true;
			}

			//check customer roles
			$for_role = get_option( 'ivole_enable_for_role', 'all' );
			$enabled_roles = get_option( 'ivole_enabled_roles', array() );
			$for_guests = 'no' === get_option( 'ivole_enable_for_guests', 'yes' ) ? false : true;

			// check if taxes should be included in list_products variable
			$tax_displ = get_option( 'woocommerce_tax_display_cart' );
			$incl_tax = false;
			if ( 'excl' === $tax_displ ) {
				$incl_tax = false;
			} else {
				$incl_tax = true;
			}

			//check if free products should be excluded from list_products variable
			$excl_free = false;
			if( 'yes' == get_option( 'ivole_exclude_free_products', 'no' ) ) {
				$excl_free = true;
			}

			// check if we are dealing with an old WooCommerce version
			$customer_first_name = '';
			$customer_last_name = '';
			$order_date = '';
			$order_currency = '';
			$order_items = array();
			$user = NULL;
			$billing_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );
			$shipping_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );
			$temp_country = '';
			if( method_exists( $order, 'get_billing_email' ) ) {
				// Woocommerce version 3.0 or later
				$user = $order->get_user();
				if( $registered_customers ) {
					if( $user ) {
						$this->to = $user->user_email;
					} else {
						$this->to = $order->get_billing_email();
					}
				} else {
					$this->to = $order->get_billing_email();
				}
				$customer_first_name = $order->get_billing_first_name();
				$customer_last_name = $order->get_billing_last_name();
				$this->replace['customer-first-name'] = $customer_first_name;
				$this->replace['customer-last-name'] = $customer_last_name;
				$this->replace['customer-name'] = $customer_first_name . ' ' . $customer_last_name;
				$this->replace['order-id'] = $order->get_order_number();
				$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $order->get_date_created() ) );
				$order_date = date_i18n( 'd.m.Y', strtotime( $order->get_date_created() ) );
				$order_currency = $order->get_currency();
				$temp_country = $order->get_billing_country();
				if( strlen( $temp_country ) > 0 ) {
					$billing_country = $temp_country;
				}
				$temp_country = $order->get_shipping_country();
				if( strlen( $temp_country ) > 0 ) {
					$shipping_country = $temp_country;
				}
				$this->phone = $order->get_billing_phone();
				$this->phone_country = $billing_country;
				if ( ! $this->phone && method_exists( $order, 'get_shipping_phone' ) ) {
					$this->phone = $order->get_shipping_phone();
					$this->phone_country = $shipping_country;
				}

				$price_args = array( 'currency' => $order_currency );
				$list_products = '';
				$first_product = true;
				foreach ( $order->get_items() as $order_item ) {
					if( $excl_free && 0 >= $order->get_line_total( $order_item, $incl_tax ) ) {
						continue;
					}
					if ( $first_product ) {
						$first_product = false;
					} else {
						$list_products .= ', ';
					}
					$list_products .= $order_item->get_name();
				}
				$this->replace['list-products'] = $list_products;
			} else {
				return array( 3, 'Error: old WooCommerce version, please update WooCommerce to the latest version' );
			}
			if( isset( $user ) && !empty( $user ) ) {
				// check customer roles if there is a restriction to which roles reminders should be sent
				if( 'roles' === $for_role ) {
					$roles = $user->roles;
					$intersection = array_intersect( $enabled_roles, $roles );
					if( count( $intersection ) < 1 ){
						//customer has no allowed roles
						return array( 4, 'Error: customer does not have roles for which review reminders are enabled' );
					}
				}
			} else {
				// check if sending of review reminders is enabled for guests
				if( ! $for_guests ) {
					return array( 5, 'Error: review reminders are disabled for guests' );
				}
			}

			// check if customer email is valid
			if ( ! filter_var( $this->to, FILTER_VALIDATE_EMAIL ) ) {
				$this->to = '';
			}

			// check if customer phone number is valid
			$vldtr = new CR_Phone_Vldtr();
			$this->phone = $vldtr->parse_phone_number( $this->phone, $this->phone_country );
			if ( ! $this->phone ) {
				return array( 6, 'Error: no valid phone numbers found in the order' );
			}

			$secret_key = $order->get_meta( 'ivole_secret_key', true );
			if ( ! $secret_key ) {
				// generate and save a secret key for callback to DB
				$secret_key = bin2hex( openssl_random_pseudo_bytes( 16 ) );
				$order->update_meta_data( 'ivole_secret_key', $secret_key );
				if ( ! $order->save() ) {
					// could not save the secret key to DB, so a customer will not be able to submit the review form
					return array( 7, 'Error: could not update the order' );
				}
			}

			// WPML integration
			$ivole_language = get_option( 'ivole_language' );
			if ( has_filter( 'wpml_default_language' ) ) {
				$site_default_lang = apply_filters('wpml_default_language', NULL );
				$site_current_lang = apply_filters( 'wpml_current_language', NULL );
				do_action( 'wpml_switch_language', $site_default_lang );
				$callback_url = get_rest_url( null, '/ivole/v1/review' );
				do_action( 'wpml_switch_language', $site_current_lang );
			} else {
				$callback_url = get_rest_url( null, '/ivole/v1/review' );
			}

			$liveMode = get_option( 'ivole_verified_live_mode', false );
			if ( false === $liveMode ) {
				// compatibility with the previous versions of the plugin
				// that had ivole_reviews_verified option instead of ivole_verified_live_mode option
				$liveMode = 0;
				$ivole_reviews_verified = get_option( 'ivole_reviews_verified', false );
				if ( false !== $ivole_reviews_verified ) {
					if ( 'yes' === $ivole_reviews_verified ) {
						update_option( 'ivole_verified_live_mode', 'yes', false );
						$liveMode = 1;
					} else {
						update_option( 'ivole_verified_live_mode', 'no', false );
					}
					delete_option( 'ivole_reviews_verified' );
				}
			} else {
				if ( 'yes' === $liveMode ) {
					$liveMode = 1;
				} else {
					$liveMode = 0;
				}
			}

			$data = array(
				'shop' => array(
					'name' => Ivole_Email::get_blogname(),
			 		'domain' => Ivole_Email::get_blogurl(),
				 	'country' => apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) ) ),
				'email' => array(
					'to' => $this->to,
					'subject' => 'WA',
					'header' => 'WA',
					'body' => 'WA'
				),
				'customer' => array(
					'firstname' => $customer_first_name,
					'lastname' => $customer_last_name
				),
				'order' => array(
					'id' => strval( $order_id ),
			 		'date' => $order_date,
					'currency' => $order_currency,
					'country' => $shipping_country,
				 	'items' => CR_Email_Func::get_order_items2( $order, $order_currency )
				),
				'callback' => array(
					'url' => $callback_url,
					'key' => $secret_key
				),
				'form' => array(
					'header' => $this->replace_variables( $this->form_header ),
					'description' => $this->replace_variables( $this->form_body ),
				 	'commentRequired' => $comment_required,
				 	'allowMedia' => $allowMedia,
				 	'shopRating' => $shop_rating,
				 	'ratingBar' => $ratingBar,
				 	'geoLocation' => $geolocation
				),
				'colors' => array(
					'form' => array(
						'bg' => get_option( 'ivole_form_color_bg', '#0f9d58' ),
						'text' => get_option( 'ivole_form_color_text', '#ffffff' ),
						'el' => get_option( 'ivole_form_color_el', '#1AB394' )
					)
				),
				'language' => $this->language,
				'schedule' => $schedule,
				'liveMode' => $liveMode,
				'channel' => 'whatsapp',
				'phone' => $this->phone,
				'licenseKey' => strval( get_option( 'ivole_license_key', '' ) )
			);
			//check that array of items is not empty
			if( 1 > count( $data['order']['items'] ) ) {
				$order->add_order_note(
					__( 'CR: A review invitation cannot be sent because the order does not contain any products for which review reminders are enabled in the settings.', 'customer-reviews-woocommerce' ),
				);
				return array( 7, __( 'Error: the order does not contain any products for which review reminders are enabled in the settings.', 'customer-reviews-woocommerce' ) );
			}
		} else {
			return array( 8, __( 'Error: invalid order ID', 'customer-reviews-woocommerce' ) );
		}

		return array( 0, $data );
	}

	public function get_test_form( $phone ) {
		$this->replace['customer-first-name'] = __( 'Jane', 'customer-reviews-woocommerce' );
		$this->replace['customer-last-name'] = __( 'Doe', 'customer-reviews-woocommerce' );
		$this->replace['customer-name'] = $this->replace['customer-first-name'] . ' ' . $this->replace['customer-last-name'];
		$this->replace['order-id'] = '12345';
		$this->replace['order-date'] = date_i18n( wc_date_format(), strtotime( time() ) );
		$this->replace['list-products'] = sprintf(
			'%s, %s',
			__( 'Item 1 Test', 'customer-reviews-woocommerce' ),
			__( 'Item 2 Test', 'customer-reviews-woocommerce' )
		);

		$form_result = CR_Local_Forms::save_form(
			$this->replace['order-id'],
			array(
				'firstname' => $this->replace['customer-first-name'],
				'lastname' => $this->replace['customer-last-name'],
				'email' => '',
			),
			$this->replace_variables( $this->form_header ),
			$this->replace_variables( $this->form_body ),
			array(
				array( 'id' => 1,
					'name' => __( 'Item 1 Test', 'customer-reviews-woocommerce' ),
					'price' => 15,
					'image' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/test-product-1.jpeg'
				),
				array( 'id' => 2,
					'name' => __( 'Item 2 Test', 'customer-reviews-woocommerce' ),
					'price' => 150,
					'image' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/test-product-2.jpeg'
				)
			), // items,
			true, // is_test
			$this->language,
			null
		);

		if ( 0 !== $form_result['code'] ) {
			return array(
				9,
				'Error: ' . $form_result['text']
			);
		}

		// create a message
		$this->replace['review-form'] = esc_url( $form_result['text'] );
		$message = $this->get_content();
		$message = $this->replace_variables( $message );

		// create a link
		$link = self::$wame . $phone . '?text=' . urlencode( $message );

		return array(
			0,
			$link
		);
	}

	public function get_content() {
		$content = get_option( 'ivole_wa_message', self::$default_body );
		return $content;
	}

	public function replace_variables( $input ) {
		return str_replace( $this->find, $this->replace, $input );
	}

	public function get_phone_number( $order_id ) {
		if ( $order_id ) {

			$order = wc_get_order( $order_id );
			if ( ! $order  ) {
				// if no order exists with the provided $order_id, then we cannot get the phone number
				return array( 1, sprintf( __( 'Error: order %s does not exist', 'customer-reviews-woocommerce' ), $order_id ) );
			}

			$billing_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );
			$shipping_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );

			$temp_country = '';
			if( method_exists( $order, 'get_billing_email' ) ) {
				// Woocommerce version 3.0 or later
				$temp_country = $order->get_billing_country();
				if( strlen( $temp_country ) > 0 ) {
					$billing_country = $temp_country;
				}
				$temp_country = $order->get_shipping_country();
				if( strlen( $temp_country ) > 0 ) {
					$shipping_country = $temp_country;
				}
				$this->phone = $order->get_billing_phone();
				$this->phone_country = $billing_country;
				if ( ! $this->phone && method_exists( $order, 'get_shipping_phone' ) ) {
					$this->phone = $order->get_shipping_phone();
					$this->phone_country = $shipping_country;
				}
			} else {
				return array( 3, 'Error: old WooCommerce version, please update WooCommerce to the latest version' );
			}

			// check if customer phone number is valid
			$vldtr = new CR_Phone_Vldtr();
			$this->phone = $vldtr->parse_phone_number( $this->phone, $this->phone_country );
			if ( ! $this->phone ) {
				return array( 6, 'Error: no valid phone numbers found in the order' );
			}

			return array( 0, $this->phone );
		}
		return array( 7, 'Error: no order number provided' );
	}

	public function send_message( $order_id, $schedule ) {
		$data_for_sending = $this->get_data_for_sending( $order_id, $schedule );

		if ( 0 === $data_for_sending[0] ) {
			$api_url = 'https://api.cusrev.com/v1/production/review-reminder';

			$data_string = json_encode( $data_for_sending[1] );
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $api_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen( $data_string ) )
			);
			$result = curl_exec( $ch );
			$result = json_decode( $result );
			// error_log( print_r($result, true) );
			if ( isset( $result->status ) && $result->status === 'OK' ) {
				Ivole_Email::update_reminders_meta( $order_id, $schedule );
				return array( 0, $result->status );
			} else {
				if ( isset( $result->details ) ) {
					return array( 1, $result->details );
				} else {
					return array( 2, 'Unknown error' );
				}
			}
		} else {
			return $data_for_sending;
		}
	}

	public function send_coupon( $customer_first_name, $customer_last_name, $customer_name, $coupon_code, $discount_string, $customer_email, $order_id, $order_date, $order_currency, $order, $discount_type, $discount_amount ) {
		$billing_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );
		$shipping_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );

		if( method_exists( $order, 'get_billing_phone' ) ) {
			$temp_country = $order->get_billing_country();
			if( strlen( $temp_country ) > 0 ) {
				$billing_country = $temp_country;
			}
			$temp_country = $order->get_shipping_country();
			if( strlen( $temp_country ) > 0 ) {
				$shipping_country = $temp_country;
			}
			$this->phone = $order->get_billing_phone();
			$this->phone_country = $billing_country;
			if ( ! $this->phone && method_exists( $order, 'get_shipping_phone' ) ) {
				$this->phone = $order->get_shipping_phone();
				$this->phone_country = $shipping_country;
			}
			// check if customer phone number is valid
			$vldtr = new CR_Phone_Vldtr();
			$this->phone = $vldtr->parse_phone_number( $this->phone, $this->phone_country );
			if ( ! $this->phone ) {
				return array(
					6,
					'Error: no valid phone numbers found in the order'
				);
			}
		} else {
			return;
		}

		$data = array(
			'token' => '164592f60fbf658711d47b2f55a1bbba',
			'shop' => array(
				"name" => Ivole_Email::get_blogname(),
				'domain' => Ivole_Email::get_blogurl(),
				'country' => apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) )
			),
			'email' => array(
				'to' => $customer_email,
				'subject' => 'WA',
				'header' => 'WA',
				'body' => 'WA'
			),
			'customer' => array(
				'firstname' => $customer_first_name,
				'lastname' => $customer_last_name
			),
			'order' => array(
				'id' => strval( $order_id ),
				'date' => $order_date,
				'currency' => $order_currency,
				'country' => $shipping_country,
				'items' => CR_Email_Func::get_order_items2( $order, $order_currency )
			),
			'discount' => array(
				'type' => $discount_type,
				'amount' => $discount_amount,
				'code' => $coupon_code
			),
			'language' => $this->language,
			'channel' => 'whatsapp',
			'phone' => $this->phone,
			'licenseKey' => strval( get_option( 'ivole_license_key', '' ) )
		);

		$api_url = 'https://api.cusrev.com/v1/production/review-discount';

		$data_string = json_encode( $data );
		// error_log( print_r($data_string, true) );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $api_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen( $data_string ) )
		);
		$result = curl_exec( $ch );
		$result = json_decode( $result );
		// error_log( print_r($result, true) );
		if( isset( $result->status ) && $result->status === 'OK' ) {
			return array(
				0,
				sprintf(
					__( 'Discount coupon %s has been successfully sent to the customer by WhatsApp.', 'customer-reviews-woocommerce' ),
					$coupon_code
				)
			);
		} else {
			if ( isset( $result->details ) ) {
				return array(
					1,
					sprintf(
						__( 'An error occurred when sending the discount coupon %1$s to the customer by WhatsApp. Error: %2$s.', 'customer-reviews-woocommerce' ),
						$coupon_code,
						$result->details
					)
				);
			} else {
				return array(
					2,
					sprintf(
						__( 'An error occurred when sending the discount coupon %s to the customer by WhatsApp.', 'customer-reviews-woocommerce' ),
						$coupon_code
					)
				);
			}
		}
	}

	public function send_test( $phone, $test_type, $media_count, $country ) {
		// some dummy information for tests
		$this->replace['customer-first-name'] = __( 'Jane', 'customer-reviews-woocommerce' );
		$this->replace['customer-last-name'] = __( 'Doe', 'customer-reviews-woocommerce' );
		$this->replace['customer-name'] = __( 'Jane Doe', 'customer-reviews-woocommerce' );
		//
		$data = array(
			'token' => '164592f60fbf658711d47b2f55a1bbba',
			'shop' => array(
				"name" => Ivole_Email::get_blogname(),
				'domain' => Ivole_Email::get_blogurl()
			),
			'email' => array(
				'to' => 'watest@cusrev.com',
				'subject' => 'WA',
				'header' => 'WA',
				'body' => 'WA'
			),
			'customer' => array(
				'firstname' => __( 'Jane', 'customer-reviews-woocommerce' ),
				'lastname' => __( 'Doe', 'customer-reviews-woocommerce' )
			),
			'order' => array(
				'id' => '12345',
				'date' => date_i18n( 'd.m.Y', time() ),
				'currency' => get_woocommerce_currency(),
				'country' => $country,
				'items' => array(
					array( 'id' => 1,
						'name' => __( 'Item 1 Test', 'customer-reviews-woocommerce' ),
						'price' => 15,
						'image' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/test-product-1.jpeg'
					),
					array( 'id' => 2,
						'name' => __( 'Item 2 Test', 'customer-reviews-woocommerce' ),
						'price' => 150,
						'image' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/test-product-2.jpeg'
					)
				)
			),
			'language' => $this->language,
			'channel' => 'whatsapp',
			'phone' => $phone,
			'licenseKey' => strval( get_option( 'ivole_license_key', '' ) )
		);

		if ( 1 === $test_type ) {
			// review for discount testing
			$cpn = CR_Review_Discount_Settings::get_coupon_for_testing( $media_count, 'wa' );
			if ( 0 !== $cpn['code'] ) {
				return $cpn;
			}
			$this->replace['coupon-code'] = $cpn['coupon_code'];
			$this->replace['discount-amount'] = ( $cpn['discount_string'] == "" ) ? '10' : $cpn['discount_string'];
			//
			$data['discount'] = array(
				'type' => $cpn['discount_type'],
				'amount' => $this->replace['discount-amount'],
				'code' => $this->replace['coupon-code']
			);
		} else {
			// review reminder testing
			$comment_required = get_option( 'ivole_form_comment_required', 'no' );
			if( 'no' === $comment_required ) {
				$comment_required = 0;
			} else {
				$comment_required = 1;
			}
			$shop_rating = 'yes' === get_option( 'ivole_form_shop_rating', 'no' ) ? true : false;
			$allowMedia = 'yes' === get_option( 'ivole_form_attach_media', 'no' ) ? true : false;
			$ratingBar = 'star' === get_option( 'ivole_form_rating_bar', 'smiley' ) ? 'star' : 'smiley';
			$geolocation = 'yes' === get_option( 'ivole_form_geolocation', 'no' ) ? true : false;
			//
			$data['form'] = array(
				'header' => $this->replace_variables( $this->form_header ),
				'description' => $this->replace_variables( $this->form_body ),
				'commentRequired' => $comment_required,
				'allowMedia' => $allowMedia,
			 	'shopRating' => $shop_rating,
			 	'ratingBar' => $ratingBar,
			 	'geoLocation' => $geolocation
			);
		}

		$api_url = 'https://api.cusrev.com/v1/production/test-email';

		$data_string = json_encode( $data );
		// error_log( print_r($data_string, true) );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $api_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen( $data_string ) )
		);
		$result = curl_exec( $ch );
		$result = json_decode( $result );
		// error_log( print_r($result, true) );
		if ( isset( $result->status ) && $result->status === 'OK' ) {
			return array(
				0,
				sprintf(
					__( 'A test message has been successfully sent by WhatsApp to %s.', 'customer-reviews-woocommerce' ),
					$phone
				)
			);
		} else {
			if ( isset( $result->error ) && $result->error ) {
				if ( isset( $result->details ) ) {
					$error_msg = sprintf(
						__( 'An error occurred when sending a test message by WhatsApp: %1$s; %2$s', 'customer-reviews-woocommerce' ),
						$result->error,
						$result->details
					);
				} else {
					$error_msg = sprintf(
						__( 'An error occurred when sending a test message by WhatsApp: %s', 'customer-reviews-woocommerce' ),
						$result->error
					);
				}
				return array(
					1,
					$error_msg
				);
			}
			return array(
				2,
				__( 'An error occurred when sending a test message by WhatsApp.', 'customer-reviews-woocommerce' )
			);
		}
	}

}

endif;
