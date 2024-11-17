<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Email' ) ) :

/**
 * Reminder email for product reviews
 */
class Ivole_Email {

	public $id;
	public $to;
	public $heading;
	public $subject;
	public $form_header;
	public $form_body;
	public $template_html;
	public $from;
	public $from_name;
	public $bcc;
	public $replyto;
	public $language;
	public $footer;
	public $review_button;
	public $find = array();
	public $replace = array();
	public static $default_body = "Hi {customer_first_name},\n\nThank you for shopping with us!\n\nWe would love if you could help us and other customers by reviewing products that you recently purchased in order #{order_id}. It only takes a minute and it would really help others. Click the button below and leave your review!\n\nBest wishes,\n{site_title}";
	public static $default_body_coupon = "Hi {customer_first_name},\n\nThank you for reviewing your order!\n\nAs a token of appreciation, we’d like to offer you a discount coupon for your next purchases in our shop. Please apply the following coupon code during checkout to receive {discount_amount} discount.\n\n<strong>{coupon_code}</strong>\n\nBest wishes,\n{site_title}";
	/**
	 * Constructor.
	 */
	public function __construct( $order_id = 0 ) {
		$this->id               = 'ivole_reminder';
		$this->heading          = strval( get_option( 'ivole_email_heading', __( 'How did we do?', 'customer-reviews-woocommerce' ) ) );
		$this->subject          = strval( get_option( 'ivole_email_subject', '[{site_title}] ' . __( 'Review Your Experience with Us', 'customer-reviews-woocommerce' ) ) );
		$this->form_header      = strval( get_option( 'ivole_form_header', __( 'How did we do?', 'customer-reviews-woocommerce' ) ) );
		$this->form_body        = strval( get_option( 'ivole_form_body', __( 'Please review your experience with products and services that you purchased at {site_title}.', 'customer-reviews-woocommerce' ) ) );
		$this->template_html    = self::plugin_path() . '/templates/email.php';
		$this->from							= get_option( 'ivole_email_from', '' );
		$this->from_name				= get_option( 'ivole_email_from_name', self::get_blogname() );
		$this->replyto					= get_option( 'ivole_email_replyto', get_option( 'admin_email' ) );
		$this->footer						= get_option( 'ivole_email_footer', '' );
		$this->review_button		= __( 'Review', 'customer-reviews-woocommerce' );

		// fetch language - either from the plugin's option or from WordPress standard locale
		$this->language = self::fetch_language();

		$this->find['site-title'] = '{site_title}';
		$this->replace['site-title'] = self::get_blogname();

		//qTranslate integration
		if( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			$this->heading = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->heading );
			$this->subject = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->subject );
			$this->form_header = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->form_header );
			$this->form_body = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->form_body );
			$this->from_name = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->from_name );
			$this->footer = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->footer );
			$this->review_button = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->review_button );
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
			$this->heading = apply_filters( 'wpml_translate_single_string', $this->heading, 'ivole', 'ivole_email_heading', $wpml_current_language );
			$this->subject = apply_filters( 'wpml_translate_single_string', $this->subject, 'ivole', 'ivole_email_subject', $wpml_current_language );
			$this->form_header = apply_filters( 'wpml_translate_single_string', $this->form_header, 'ivole', 'ivole_form_header', $wpml_current_language );
			$this->form_body = apply_filters( 'wpml_translate_single_string', $this->form_body, 'ivole', 'ivole_form_body', $wpml_current_language );
			$this->from = apply_filters( 'wpml_translate_single_string', $this->from, 'ivole', 'ivole_email_from', $wpml_current_language );
			$this->from_name = apply_filters( 'wpml_translate_single_string', $this->from_name, 'ivole', 'ivole_email_from_name', $wpml_current_language );
			$this->replyto = apply_filters( 'wpml_translate_single_string', $this->replyto, 'ivole', 'ivole_email_replyto', $wpml_current_language );
			$this->footer = apply_filters( 'wpml_translate_single_string', $this->footer, 'ivole', 'ivole_email_footer', $wpml_current_language );
			$this->review_button = apply_filters( 'wpml_translate_single_string', $this->review_button, 'ivole', 'ivole_review_button', $wpml_current_language );
			if ( empty( $this->from_name ) ) {
				$this->from_name = self::get_blogname();
			}
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
			$this->heading = pll_translate_string( $this->heading, $polylang_current_language );
			$this->subject = pll_translate_string( $this->subject, $polylang_current_language );
			$this->form_header = pll_translate_string( $this->form_header, $polylang_current_language );
			$this->form_body = pll_translate_string( $this->form_body, $polylang_current_language );
			$this->from = pll_translate_string( $this->from, $polylang_current_language );
			$this->from_name = pll_translate_string( $this->from_name, $polylang_current_language );
			$this->replyto = pll_translate_string( $this->replyto, $polylang_current_language );
			$this->footer = pll_translate_string( $this->footer, $polylang_current_language );
			$this->review_button = pll_translate_string( $this->review_button, $polylang_current_language );
			if ( empty( $this->from_name ) ) {
				$this->from_name = self::get_blogname();
			}
			$this->language = strtoupper( $polylang_current_language );
		}

		// TranslatePress integration
		if( function_exists( 'trp_translate' ) ) {
			$trp_order_language = '';
			if ( $order ) {
				$trp_order_language = $order->get_meta( 'trp_language', true );
			}
			if( $trp_order_language ) {
				$this->heading = trp_translate( $this->heading, $trp_order_language, false );
				$this->subject = trp_translate( $this->subject, $trp_order_language, false );
				$this->form_header = trp_translate( $this->form_header, $trp_order_language, false );
				$this->form_body = trp_translate( $this->form_body, $trp_order_language, false );
				$this->from = trp_translate( $this->from, $trp_order_language, false );
				$this->from_name = trp_translate( $this->from_name, $trp_order_language, false );
				$this->replyto = trp_translate( $this->replyto, $trp_order_language, false );
				$this->footer = trp_translate( $this->footer, $trp_order_language, false );
				$this->review_button = trp_translate( $this->review_button, $trp_order_language, false );
				if ( empty( $this->from_name ) ) {
					$this->from_name = self::get_blogname();
				}
				$this->language = strtoupper( substr( $trp_order_language, 0, 2 ) );
			}
		}

		//a safety check if some translation plugin removed language
		if ( empty( $this->language ) || 'WPML' === $this->language ) {
			$this->language = 'EN';
		}

		// map language codes returned by translation plugins that include '-' like PT-PT
		$this->language = CR_Email_Func::cr_map_language( $this->language );

		$this->footer = strval( $this->footer );
	}

	/**
	 * Trigger version 2.
	 */
	public function trigger2( $order_id, $to, $schedule ) {
		if ( ! Ivole::is_curl_installed() ) {
			return array(
				100,
				__( 'Error: cURL library is missing on the server.', 'customer-reviews-woocommerce' ),
				array()
			);
		}
		$this->find['customer-first-name']  = '{customer_first_name}';
		$this->find['customer-last-name']  = '{customer_last_name}';
		$this->find['customer-name'] = '{customer_name}';
		$this->find['order-id'] = '{order_id}';
		$this->find['order-date'] = '{order_date}';
		$this->find['list-products'] = '{list_products}';

		// check if Reply-To address needs to be added to email
		if( filter_var( $this->replyto, FILTER_VALIDATE_EMAIL ) ) {
			$this->replyto = $this->replyto;
		} else {
			$this->replyto = get_option( 'admin_email' );
		}

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
				// if no order exists with the provided $order_id, then we cannot send an email
				return array(
					15,
					sprintf( __( 'Error: the order %s does not exist anymore.', 'customer-reviews-woocommerce' ), $order_id ),
					array()
				);
			}

			//check if Limit Number of Reviews option is used
			if( 'yes' === get_option( 'ivole_limit_reminders', 'yes' ) ) {
				//check how many reminders have already been sent for this order (if any)
				$reviews = $order->get_meta( '_ivole_review_reminder', true );
				if( $reviews >= 1 ) {
					//if more than one, then we cannot send an email
					return array(
						3,
						__( 'Error: maximum number of reminders per order is limited to one.', 'customer-reviews-woocommerce' ),
						array()
					);
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
			$shipping_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );
			$temp_shipping_country = '';
			if( method_exists( $order, 'get_billing_email' ) ) {
				// Woocommerce version 3.0 or later
				$user = $order->get_user();
				$this->to = self::get_customer_email( $order );
				$customer_first_name = $order->get_billing_first_name();
				$customer_last_name = $order->get_billing_last_name();
				$this->replace['customer-first-name'] = $customer_first_name;
				$this->replace['customer-last-name'] = $customer_last_name;
				$this->replace['customer-name'] = $customer_first_name . ' ' . $customer_last_name;
				$this->replace['order-id'] = $order->get_order_number();
				$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $order->get_date_created() ) );
				$order_date = date_i18n( 'd.m.Y', strtotime( $order->get_date_created() ) );
				$order_currency = $order->get_currency();
				$temp_shipping_country = $order->get_shipping_country();
				if( strlen( $temp_shipping_country ) > 0 ) {
					$shipping_country = $temp_shipping_country;
				}

				$price_args = array( 'currency' => $order_currency );
				$list_products = '';
				foreach ( $order->get_items() as $order_item ) {
					if( $excl_free && 0 >= $order->get_line_total( $order_item ) ) {
						continue;
					}
					if ( method_exists( $order_item, 'get_product_id' ) ) {
						$product = wc_get_product( $order_item->get_product_id() );
						if ( $product ) {
							$product_price = wc_get_price_to_display( $product );
							$list_products .= $order_item->get_name() . ' / ' . CR_Email_Func::cr_price( $product_price, $price_args ) . '<br/>';
						}
					}
				}
				$this->replace['list-products'] = $list_products;
			} else {
				return array(
					17,
					'Error: old WooCommerce version, please update WooCommerce to the latest version',
					array()
				);
			}
			if( isset( $user ) && !empty( $user ) ) {
				// check customer roles if there is a restriction to which roles reminders should be sent
				if( 'roles' === $for_role ) {
					$roles = $user->roles;
					$intersection = array_intersect( $enabled_roles, $roles );
					if( count( $intersection ) < 1 ){
							//customer has no allowed roles
							return array(
								5,
								__( 'Error: the order was placed by a customer who doesn\'t have a role for which review reminders are enabled.', 'customer-reviews-woocommerce' ),
								array()
							);
					}
				}
			} else {
				// check if sending of review reminders is enabled for guests
				if( ! $for_guests ) {
					return array(
						11,
						__( 'Error: reminders are disabled for guests.', 'customer-reviews-woocommerce' ),
						array()
					);
				}
			}
			// check if BCC address needs to be added to email
			$bcc_address = get_option( 'ivole_email_bcc', '' );
			if( filter_var( $bcc_address, FILTER_VALIDATE_EMAIL ) ) {
				$this->bcc = $bcc_address;
			} else {
				$this->bcc = '';
			}
			// check if customer email is valid
			if( !filter_var( $this->to, FILTER_VALIDATE_EMAIL ) ) {
				return array(
					10,
					__( 'Error: the customer\'s email is invalid.', 'customer-reviews-woocommerce' ),
					array()
				);
			}
			// check if email subject is not empty
			$email_subject = $this->replace_variables( $this->subject );
			if( 0 >= strlen( $email_subject ) ) {
				return array(
					13,
					__( 'Error: "Email Subject" is empty. Please enter a string for the subject line of emails.', 'customer-reviews-woocommerce' ),
					array()
				);
			}

			$message = $this->get_content();
			$message = $this->replace_variables( $message );

			$secret_key = $order->get_meta( 'ivole_secret_key', true );
			if ( ! $secret_key ) {
				// generate and save a secret key for callback to DB
				$secret_key = bin2hex( openssl_random_pseudo_bytes( 16 ) );
				$order->update_meta_data( 'ivole_secret_key', $secret_key );
				if ( ! $order->save() ) {
					// could not save the secret key to DB, so a customer will not be able to submit the review form
					return array(
						6,
						__( 'Error: could not save the secret key to DB. Please try again.', 'customer-reviews-woocommerce' ),
						array()
					);
				}
			}

			if ( has_filter( 'wpml_default_language' ) ) {
				$site_default_lang = apply_filters('wpml_default_language', NULL );
				$site_current_lang = apply_filters( 'wpml_current_language', NULL );
				do_action( 'wpml_switch_language', $site_default_lang );
				Ivole::remove_class_filter( 'rest_url', 'WPML_URL_Converter_Domain_Strategy', 'convertRestUrlToCurrentDomain', 10 );
				Ivole::remove_class_filter( 'rest_url', 'WPML_URL_Converter_Subdir_Strategy', 'convertRestUrl', 10 );
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
				'token' => '164592f60fbf658711d47b2f55a1bbba',
				'shop' => array( "name" => self::get_blogname(),
			 		'domain' => self::get_blogurl(),
				 	'country' => apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) ) ),
				'email' => array( 'to' => $this->to,
					'from' => strval( $this->from ),
					'fromText' => $this->from_name,
					'bcc' => $this->bcc,
					'replyTo' => $this->replyto,
			 		'subject' => $email_subject,
					'header' => $this->replace_variables( $this->heading ),
					'body' => $message,
				 	'footer' => $this->replace_variables( $this->footer )
				),
				'customer' => array( 'firstname' => $customer_first_name,
					'lastname' => $customer_last_name ),
				'order' => array( 'id' => strval( $order_id ),
			 		'date' => $order_date,
					'currency' => $order_currency,
					'country' => $shipping_country,
				 	'items' => CR_Email_Func::get_order_items2( $order, $order_currency ) ),
				'callback' => array(
					'url' => $callback_url,
					'key' => $secret_key ),
				'form' => array('header' => $this->replace_variables( $this->form_header ),
					'description' => $this->replace_variables( $this->form_body ),
				 	'commentRequired' => $comment_required,
				 	'allowMedia' => $allowMedia,
				 	'shopRating' => $shop_rating,
				 	'ratingBar' => $ratingBar,
				 	'geoLocation' => $geolocation ),
				'colors' => array(
					'form' => array(
						'bg' => get_option( 'ivole_form_color_bg', '#0f9d58' ),
						'text' => get_option( 'ivole_form_color_text', '#ffffff' ),
						'el' => get_option( 'ivole_form_color_el', '#1AB394' )
					),
					'email' => array(
						'bg' => get_option( 'ivole_email_color_bg', '#0f9d58' ),
						'text' => get_option( 'ivole_email_color_text', '#ffffff' )
					)
				),
				'language' => $this->language,
				'schedule' => $schedule,
				'liveMode' => $liveMode
			);
			//check that array of items is not empty
			if( 1 > count( $data['order']['items'] ) ) {
				$order->add_order_note(
					__( 'CR: A review invitation cannot be sent because the order does not contain any products for which review reminders are enabled in the settings.', 'customer-reviews-woocommerce' )
				);
				return array(
					4,
					__( 'Error: the order does not contain any products for which review reminders are enabled in the settings.', 'customer-reviews-woocommerce' ),
					array()
				);
			}
			$is_test = false;
		} else {
			// no order number means this is a test and we should provide some dummy information
			$this->replace['customer-first-name'] = __( 'Jane', 'customer-reviews-woocommerce' );
			$this->replace['customer-last-name'] = __( 'Doe', 'customer-reviews-woocommerce' );
			$this->replace['customer-name'] = __( 'Jane Doe', 'customer-reviews-woocommerce' );
			$this->replace['order-id'] = 12345;
			$this->replace['order-date'] = date_i18n( wc_date_format(), time() );
			if( 0 >= strlen( $this->replace['order-date'] ) ) {
				$this->replace['order-date'] = date_i18n( 'F j, Y', time() );
			}
			$this->replace['list-products'] = sprintf(
				'%s / %s<br/>%s / %s<br/>',
				__( 'Item 1 Test', 'customer-reviews-woocommerce' ),
				CR_Email_Func::cr_price( 15 ),
				__( 'Item 2 Test', 'customer-reviews-woocommerce' ),
				CR_Email_Func::cr_price( 150 )
			);

			// check if email subject is not empty
			$email_subject = $this->replace_variables( $this->subject );
			if( 0 >= strlen( $email_subject ) ) {
				return array(
					13,
					__( 'Error: "Email Subject" is empty. Please enter a string for the subject line of emails.', 'customer-reviews-woocommerce' ),
					array()
				);
			}

			$message = $this->get_content();
			$message = $this->replace_variables( $message );

			$data = array(
				'shop' => array( "name" => self::get_blogname(),
			 	'domain' => self::get_blogurl() ),
				'email' => array( 'to' => $to,
					'from' => strval( $this->from ),
					'fromText' => $this->from_name,
					'replyTo' => $this->replyto,
			 		'subject' => $email_subject,
					'header' => $this->replace_variables( $this->heading ),
					'body' => $message,
					'footer' => $this->replace_variables( $this->footer )
				),
				'customer' => array( 'firstname' => __( 'Jane', 'customer-reviews-woocommerce' ),
					'lastname' => __( 'Doe', 'customer-reviews-woocommerce' ) ),
				'order' => array( 'id' => '12345',
					'date' => date_i18n( 'd.m.Y', time() ),
					'currency' => get_woocommerce_currency(),
					'items' => CR_Email_Func::get_test_items()
				),
				'form' => array( 'header' => $this->replace_variables( $this->form_header ),
					'description' => $this->replace_variables( $this->form_body ),
					'commentRequired' => $comment_required,
					'allowMedia' => $allowMedia,
				 	'shopRating' => $shop_rating,
				 	'ratingBar' => $ratingBar,
				 	'geoLocation' => $geolocation ),
				'colors' => array(
					'form' => array(
						'bg' => get_option( 'ivole_form_color_bg', '#0f9d58' ),
						'text' => get_option( 'ivole_form_color_text', '#ffffff' ),
						'el' => get_option( 'ivole_form_color_el', '#1AB394' )
					),
					'email' => array(
						'bg' => get_option( 'ivole_email_color_bg', '#0f9d58' ),
						'text' => get_option( 'ivole_email_color_text', '#ffffff' )
					)
				),
				'language' => $this->language
			);
			$is_test = true;
		}
		$data_extra = array(
			'reviewBtn' => $this->review_button
		);
		$license = get_option( 'ivole_license_key', '' );
		if( strlen( $license ) > 0 ) {
			$data['licenseKey'] = $license;
		}
		$result = CR_Email_Func::send_email( $data, $is_test, $data_extra );

		if( isset( $result['status'] ) && $result['status'] === 'OK' ) {
			self::update_reminders_meta( $order_id, $schedule );
			return array(
				0,
				$result['status'],
				$result
			);
		} elseif( isset( $result['status'] ) && $result['status'] === 'Error' ) {
			if ( isset( $result['details'] ) ) {
				$result['details'] = $this->map_error_desc( $result['details'] );
			} else {
				$result['details'] = '';
			}
			return array(
				16,
				$result['details'],
				$result
			);
		} else {
			return array(
				1,
				'',
				$result
			);
		}
	}

	public function get_content() {
		ob_start();
		$def_body = self::$default_body;
		$lang = $this->language;
		include( $this->template_html );
		return ob_get_clean();
	}

	public static function plugin_path() {
		return untrailingslashit( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) );
	}

	public function replace_variables( $input ) {
		return str_replace( $this->find, $this->replace, $input );
	}

	public static function get_blogname() {
		$blog_name = get_option( 'ivole_shop_name', get_option( 'blogname' ) );
		if( !$blog_name ) {
			$blog_name = get_option( 'blogname' );
			if( !$blog_name ) {
				$blog_name = self::get_blogurl();
			}
		}
		return wp_specialchars_decode( $blog_name, ENT_QUOTES );
	}

	public static function get_blogurl() {
		$temp = get_option( 'home' );
		$disallowed = array('http://', 'https://');
		foreach($disallowed as $d) {
			if(strpos($temp, $d) === 0) {
				return str_replace($d, '', $temp);
			}
		}
		return $temp;
	}

	public static function get_blogdomain() {
		$temp = get_option( 'home' );
		$temp = parse_url( $temp, PHP_URL_HOST );
		if( !$temp ) {
			$temp = '';
		}
		return $temp;
	}

	public static function update_reminders_meta( $order_id, $schedule ) {
		//update count of review reminders sent in order meta
		if( $order_id ) {
			$order = wc_get_order( $order_id );
			if( $schedule ) {
				// CR Cron
				$order->update_meta_data( '_ivole_cr_cron', 1 );
				$order->save();
			} else {
				// WP Cron
				$count = $order->get_meta( '_ivole_review_reminder', true );
				$new_count = 0;
				if( '' === $count ) {
					$new_count = 1;
				} else {
					$count = intval( $count );
					$new_count = $count + 1;
				}
				$order->update_meta_data( '_ivole_review_reminder', $new_count );
				$order->save();
			}
		}
	}

	private function map_error_desc( $desc ) {
		if ( 0 === strcmp( 'Too many review invitations for a single order', $desc ) ) {
			return __( 'Error: only one review invitation per order is allowed.', 'customer-reviews-woocommerce' ) . ' <a href="https://cusrev.freshdesk.com/support/solutions/articles/43000511299-error-only-one-review-invitation-per-order-is-allowed" target="_blank" rel="noopener noreferrer">' . __( 'View additional information', 'customer-reviews-woocommerce' ) . '</a>.';
		} elseif ( 0 === strcmp( 'All products were reviewed by this customer', $desc ) ) {
			return __( 'Error: the customer has already reviewed all products from this order.', 'customer-reviews-woocommerce' );
		} elseif (
			0 === strcmp( 'Unable to send reminder to specified email', $desc ) ||
			0 === strcmp( 'Customer has unsubscribed from emails', $desc )
		) {
			return __( 'Error: the customer has unsubscribed from emails.', 'customer-reviews-woocommerce' );
		} elseif ( 0 === strcmp( 'The customer already reviewed shop or products in the past', $desc ) ) {
			return __( 'Error: the customer has already left a review for a different order in the past.', 'customer-reviews-woocommerce' );
		} else {
			return $desc;
		}
	}

	public static function get_customer_email( $order ) {
		$email = '';
		//check if registered customers option is used
		$registered_customers = false;
		if( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) {
			$registered_customers = true;
		}
		//
		if( $registered_customers ) {
			$user = $order->get_user();
			if( $user ) {
				$email = $user->user_email;
			} else {
				$email = $order->get_billing_email();
			}
		} else {
			$email = $order->get_billing_email();
		}
		return $email;
	}

	public static function fetch_language() {
		$language = 'EN';
		if ( 'yes' !== get_option( 'ivole_verified_reviews', 'no' ) ) {
			$wp_locale = get_locale();
			$wp_lang = explode( '_', $wp_locale );
			if( is_array( $wp_lang ) && 0 < count( $wp_lang ) ) {
				$language = strtoupper( $wp_lang[0] );
			} else {
				$language = 'EN';
			}
		} else {
			$language = get_option( 'ivole_language', 'EN' );
		}
		return $language;
	}

	public static function fetch_language_trnsl( $order_id, $order ) {
		$lang = self::fetch_language();

		// qTranslate integration
		if( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			if( 'QQ' === $lang ) {
				global $q_config;
				$lang = strtoupper( $q_config['language'] );
			}
		}

		// WPML integration
		if ( has_filter( 'wpml_translate_single_string' ) && defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
			$wpml_current_language = apply_filters( 'wpml_current_language', NULL );
			if ( $order ) {
				$wpml_current_language = $order->get_meta( 'wpml_language', true );
			}
			if ( $wpml_current_language ) {
				$lang = strtoupper( $wpml_current_language );
			}
		}

		// Polylang integration
		if( function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post_language' ) && function_exists( 'pll_translate_string' ) ) {
			$polylang_current_language = pll_current_language();
			if( $order_id ) {
				$polylang_current_language = pll_get_post_language( $order_id );
			}
			$lang = strtoupper( $polylang_current_language );
		}

		// TranslatePress integration
		if( function_exists( 'trp_translate' ) ) {
			$trp_order_language = '';
			if ( $order ) {
				$trp_order_language = $order->get_meta( 'trp_language', true );
			}
			if( $trp_order_language ) {
				$lang = strtoupper( substr( $trp_order_language, 0, 2 ) );
			}
		}

		// a safety check if some translation plugin removed language
		if ( empty( $lang ) || 'WPML' === $lang ) {
			$lang = 'EN';
		}

		// map language codes returned by translation plugins that include '-' like PT-PT
		$lang = CR_Email_Func::cr_map_language( $lang );

		return $lang;
	}

}

endif;
