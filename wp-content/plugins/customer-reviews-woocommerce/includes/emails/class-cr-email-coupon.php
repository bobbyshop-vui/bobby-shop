<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Email_Coupon' ) ) :

/**
 * Reminder email for product reviews
 */
class CR_Email_Coupon {

	public $id;
	public $to;
	public $heading;
	public $subject;
	public $template_html;
	public $from;
	public $from_name;
	public $bcc;
	public $replyto;
	public $language;
	public $footer;
	public $find = array();
	public $replace = array();
	/**
	 * Constructor.
	 */
	public function __construct( $order_id = 0 ) {
		$this->id               = 'ivole_review_coupon';
		$this->heading          = strval( get_option( 'ivole_email_heading_coupon', __( 'Thank You for Leaving a Review', 'customer-reviews-woocommerce' ) ) );
		$this->subject          = strval( get_option( 'ivole_email_subject_coupon', '[{site_title}] ' . __( 'Discount Coupon for You', 'customer-reviews-woocommerce' ) ) );
		$this->template_html    = Ivole_Email::plugin_path() . '/templates/email_coupon.php';
		$this->from							= get_option( 'ivole_email_from', '' );
		$this->from_name				= get_option( 'ivole_email_from_name', Ivole_Email::get_blogname() );
		$this->replyto					= get_option( 'ivole_coupon_email_replyto', get_option( 'admin_email' ) );
		$this->footer						= get_option( 'ivole_email_footer', '' );

		// fetch language - either from the plugin's option or from WordPress standard locale
		$this->language = Ivole_Email::fetch_language();

		$this->find['site-title'] = '{site_title}';
		$this->find['customer-first-name']  = '{customer_first_name}';
		$this->find['customer-last-name']  = '{customer_last_name}';
		$this->find['customer-name'] = '{customer_name}';
		$this->find['coupon-code'] = '{coupon_code}';
		$this->find['discount-amount'] = '{discount_amount}';
		$this->replace['site-title'] = Ivole_Email::get_blogname();

		//qTranslate integration
		if( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			$this->heading = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->heading );
			$this->subject = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->subject );
			$this->from_name = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->from_name );
			$this->footer = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->footer );
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
			$this->heading = apply_filters( 'wpml_translate_single_string', $this->heading, 'ivole', 'ivole_email_heading_coupon', $wpml_current_language );
			$this->subject = apply_filters( 'wpml_translate_single_string', $this->subject, 'ivole', 'ivole_email_subject_coupon', $wpml_current_language );
			$this->from = apply_filters( 'wpml_translate_single_string', $this->from, 'ivole', 'ivole_email_from', $wpml_current_language );
			$this->from_name = apply_filters( 'wpml_translate_single_string', $this->from_name, 'ivole', 'ivole_email_from_name', $wpml_current_language );
			$this->replyto = apply_filters( 'wpml_translate_single_string', $this->replyto, 'ivole', 'ivole_email_replyto', $wpml_current_language );
			$this->footer = apply_filters( 'wpml_translate_single_string', $this->footer, 'ivole', 'ivole_email_footer', $wpml_current_language );
			if ( empty( $this->from_name ) ) {
				$this->from_name = Ivole_Email::get_blogname();
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
			$this->from = pll_translate_string( $this->from, $polylang_current_language );
			$this->from_name = pll_translate_string( $this->from_name, $polylang_current_language );
			$this->replyto = pll_translate_string( $this->replyto, $polylang_current_language );
			$this->footer = pll_translate_string( $this->footer, $polylang_current_language );
			if ( empty( $this->from_name ) ) {
				$this->from_name = Ivole_Email::get_blogname();
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
				$this->from = trp_translate( $this->from, $trp_order_language, false );
				$this->from_name = trp_translate( $this->from_name, $trp_order_language, false );
				$this->replyto = trp_translate( $this->replyto, $trp_order_language, false );
				$this->footer = trp_translate( $this->footer, $trp_order_language, false );
				if ( empty( $this->from_name ) ) {
					$this->from_name = Ivole_Email::get_blogname();
				}
				$this->language = strtoupper( substr( $trp_order_language, 0, 2 ) );
			}
		}

		//a safety check if some translation plugin removed language
		if ( empty( $this->language ) || 'WPML' === $this->language ) {
			$this->language = 'EN';
		}

		$this->footer = strval( $this->footer );
	}

	/**
	 * Trigger 2 - used for sending test emails only
	 */
	public function triggerTest( $order_id, $to = null, $coupon_code = '', $discount_string = '', $discount_type = '' ) {
		if ( ! Ivole::is_curl_installed() ) {
			return array( 100, __( 'Error: cURL library is missing on the server.', 'customer-reviews-woocommerce' ) );
		}

		$mailer = get_option( 'ivole_mailer_review_reminder', 'cr' );

		// check if Reply-To address needs to be added to email
		if( filter_var( $this->replyto, FILTER_VALIDATE_EMAIL ) ) {
			$this->replyto = $this->replyto;
		} else {
			$this->replyto = get_option( 'admin_email' );
		}

		// some dummy information for tests
		$this->to = $to;
		$this->replace['customer-first-name'] = __( 'Jane', 'customer-reviews-woocommerce' );
		$this->replace['customer-last-name'] = __( 'Doe', 'customer-reviews-woocommerce' );
		$this->replace['customer-name'] = __( 'Jane Doe', 'customer-reviews-woocommerce' );
		$this->replace['coupon-code'] = $coupon_code;
		$this->replace['discount-amount'] = ( $discount_string == "" ) ? '10%' : $discount_string;

		// check if email subject is not empty
		$email_subject = $this->replace_variables( $this->subject );
		if( 0 >= strlen( $email_subject ) ) {
			return 13;
		}

		$message = $this->get_content();
		$message = $this->replace_variables( $message );

		$data = array(
			'shop' => array( "name" => Ivole_Email::get_blogname(),
				'domain' => Ivole_Email::get_blogurl() ),
			'email' => array( 'to' => $to,
				'from' => strval( $this->from ),
				'fromText' => $this->from_name,
				'replyTo' => $this->replyto,
				'subject' => $email_subject,
				'header' => $this->replace_variables( $this->heading ),
				'body' => $message,
				'footer' => $this->replace_variables( $this->footer ) ),
			'customer' => array( 'firstname' => __( 'Jane', 'customer-reviews-woocommerce' ),
				'lastname' => __( 'Doe', 'customer-reviews-woocommerce' ) ),
			'order' => array( 'id' => '12345',
				'date' => date_i18n( 'd.m.Y', time() ),
				'currency' => get_woocommerce_currency(),
				'items' => CR_Email_Func::get_test_items()
				),
				'colors' => array(
					'email' => array(
						"bg" => get_option( 'ivole_email_coupon_color_bg', '#0f9d58' ),
						'text' => get_option( 'ivole_email_coupon_color_text', '#ffffff' )
					)
				),
			'language' => $this->language
		);
		if( 'wp' === $mailer ) {
			$data['discount'] = array(
				'type' => $discount_type,
				'amount' => $this->replace['discount-amount'],
				'code' => $coupon_code
			);
		}
		$license = get_option( 'ivole_license_key', '' );
		if( strlen( $license ) > 0 ) {
			$data['licenseKey'] = $license;
		}
		$result = CR_Email_Func::send_email_coupon( $data, true );
		if( false === $result ) {
			return array( 2, 'cURL error' );
		}
		//error_log( $result );
		$result = json_decode( $result );
		if( isset( $result->status ) && $result->status === 'OK' ) {
			return 0;
		} else {
			return 1;
		}
	}

	public function generate_coupon( $to, $review_id, $coupon ) {
		$prefix = $coupon['cr_coupon_prefix'];
		$unique_code = ( !empty( $to ) ) ? strtoupper( uniqid( substr( preg_replace( '/[^a-z0-9]/i', '', sanitize_title( $to ) ), 0, 5 ) ) ) : strtoupper( uniqid() );
		$unique_code = apply_filters( 'cr_generate_coupon', strtoupper( $prefix ) . $unique_code, $review_id );
		$coupon_args = array(
			'post_title' 	=> $unique_code,
			'post_content' 	=> '',
			'post_status' 	=> 'publish',
			'post_author' 	=> 1,
			'post_type'     => 'shop_coupon'
		);
		$coupon_id = wp_insert_post( $coupon_args );
		if( $coupon_id > 0 ){
			$type = $coupon['cr_coupon__discount_type'];
			update_post_meta( $coupon_id, 'discount_type', $type );
			$amount = floatval( $coupon['cr_coupon__coupon_amount'] );
			update_post_meta( $coupon_id, 'coupon_amount', $amount );
			$individual_use = 'yes' === $coupon['cr_coupon__individual_use'] ? 'yes' : 'no';
			update_post_meta( $coupon_id, 'individual_use', $individual_use );
			$product_ids = is_array( $coupon['cr_coupon__product_ids'] ) ? $coupon['cr_coupon__product_ids'] : array();
			$product_ids = implode( ",", $product_ids );
			update_post_meta( $coupon_id, 'product_ids', $product_ids );
			$exclude_product_ids = is_array( $coupon['cr_coupon__exclude_product_ids'] ) ? $coupon['cr_coupon__exclude_product_ids'] : array();
			$exclude_product_ids = implode( ",", $exclude_product_ids );
			update_post_meta( $coupon_id, 'exclude_product_ids', $exclude_product_ids );
			$usage_limit = 0 < intval( $coupon['cr_coupon__usage_limit'] ) ? intval( $coupon['cr_coupon__usage_limit'] ) : 0;
			update_post_meta( $coupon_id, 'usage_limit', $usage_limit );
			update_post_meta( $coupon_id, 'usage_limit_per_user', $usage_limit );
			$days = 0 < intval( $coupon['cr_coupon__expires_days'] ) ? intval( $coupon['cr_coupon__expires_days'] ) : 0;
			if( $days > 0 ) {
				$today = time();
				$expiry_date = date( 'Y-m-d', $today + DAY_IN_SECONDS * $days );
				update_post_meta( $coupon_id, 'expiry_date', $expiry_date );
				$date_expires = strtotime( $expiry_date );
				update_post_meta( $coupon_id, 'date_expires', $date_expires );
			} else {
				update_post_meta( $coupon_id, 'expiry_date', NULL );
				update_post_meta( $coupon_id, 'date_expires', '' );
			}
			$coupon_sharing = 'yes' === $coupon['cr_coupon__sharing'] ? 'yes' : 'no';
			if( 'no' === $coupon_sharing ) {
				update_post_meta( $coupon_id, 'customer_email', array( $to ) );
			}
			$free_shipping = 'yes' === $coupon['cr_coupon__free_shipping'] ? 'yes' : 'no';
			update_post_meta( $coupon_id, 'free_shipping', $free_shipping );

			$exclude_sale_items = 'yes' === $coupon['cr_coupon__exclude_sale_items'] ? 'yes' : 'no';
			update_post_meta( $coupon_id, 'exclude_sale_items', $exclude_sale_items );

			$product_categories = is_array( $coupon['cr_coupon__product_categories'] ) ? $coupon['cr_coupon__product_categories'] : array();
			update_post_meta( $coupon_id, 'product_categories', $product_categories );

			$exclude_product_categories = is_array( $coupon['cr_coupon__excluded_product_categories'] ) ? $coupon['cr_coupon__excluded_product_categories'] : array();
			update_post_meta( $coupon_id, 'exclude_product_categories', $exclude_product_categories );

			$minimum_amount =  0 < floatval( $coupon['cr_coupon__minimum_amount'] ) ? floatval( $coupon['cr_coupon__minimum_amount'] ) : 0;
			if( $minimum_amount > 0 ) {
				update_post_meta( $coupon_id, 'minimum_amount', $minimum_amount );
			} else {
				update_post_meta( $coupon_id, 'minimum_amount', '' );
			}

			$maximum_amount = 0 < floatval( $coupon['cr_coupon__maximum_amount'] ) ? floatval( $coupon['cr_coupon__maximum_amount'] ) : 0;
			if( $maximum_amount > 0 ) {
				update_post_meta( $coupon_id, 'maximum_amount', $maximum_amount );
			} else {
				update_post_meta( $coupon_id, 'maximum_amount', '' );
			}
			update_post_meta( $coupon_id, 'usage_count', 0 );
			update_post_meta( $coupon_id, 'generated_from_review_id', $review_id );
			update_post_meta( $coupon_id, '_ivole_auto_generated', 1 );
		}
		return $coupon_id;
	}

	public function get_content() {
		ob_start();
		//$email_heading = $this->heading;
		$def_body = Ivole_Email::$default_body_coupon;
		$lang = $this->language;
		include( $this->template_html );
		return ob_get_clean();
	}

	public function replace_variables( $input ) {
		return str_replace( $this->find, $this->replace, __( $input ) );
	}

	public function trigger_coupon( $customer_first_name, $customer_last_name, $customer_name, $coupon_code, $discount_string, $customer_email, $order_id, $order_date, $order_currency, $order, $discount_type, $discount_amount ) {
		$this->replace['customer-first-name'] = $customer_first_name;
		$this->replace['customer-last-name'] = $customer_last_name;
		$this->replace['customer-name'] = $customer_name;
		$this->replace['coupon-code'] = $coupon_code;
		$this->replace['discount-amount'] = $discount_string;

		$from_address = get_option( 'ivole_email_from', '' );
		$from_name = get_option( 'ivole_email_from_name', Ivole_Email::get_blogname() );
		$footer = get_option( 'ivole_email_footer', '' );

		// check if Reply-To address needs to be added to email
		$replyto = get_option( 'ivole_coupon_email_replyto', get_option( 'admin_email' ) );
		if ( filter_var( $replyto, FILTER_VALIDATE_EMAIL ) ) {
			$replyto = $replyto;
		} else {
			$replyto = get_option( 'admin_email' );
		}

		$bcc_address = get_option( 'ivole_coupon_email_bcc', '' );
		if ( ! filter_var( $bcc_address, FILTER_VALIDATE_EMAIL ) ) {
			$bcc_address = '';
		}

		$message = $this->get_content();
		$message = $this->replace_variables( $message );

		// in case of local email templates, discount amount is expected with '%' or currency sign
		$mailer = get_option( 'ivole_mailer_review_reminder', 'cr' );
		if ( 'wp' === $mailer ) {
			$discount_amount = $discount_string;
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
				'from' => $from_address,
				'fromText' => $from_name,
				'replyTo' => $replyto,
				'bcc' => $bcc_address,
				'subject' => $this->replace_variables( $this->subject ),
				'header' => $this->replace_variables( $this->heading ),
				'body' => $message,
				'footer' => $this->replace_variables( $this->footer )
			),
			'customer' => array(
				'firstname' => $customer_first_name,
				'lastname' => $customer_last_name
			),
			'order' => array(
				'id' => strval( $order_id ),
				'date' => $order_date,
				'currency' => $order_currency,
				'items' => CR_Email_Func::get_order_items2( $order, $order_currency )
			),
			'discount' => array('type' => $discount_type,
				'amount' => $discount_amount,
				'code' => $coupon_code ),
			'colors' => array(
				'email' => array(
					"bg" => get_option( 'ivole_email_coupon_color_bg', '#0f9d58' ),
					'text' => get_option( 'ivole_email_coupon_color_text', '#ffffff' )
				)
			),
			'language' => $this->language,
		);

		$license = get_option( 'ivole_license_key', '' );
		if ( strlen( $license ) > 0 ) {
			$data['licenseKey'] = $license;
		}

		$result = CR_Email_Func::send_email_coupon( $data, false );
		$result = json_decode( $result );
		if( isset( $result->status ) && $result->status === 'OK' ) {
			return array(
				0,
				sprintf(
					__( 'Discount coupon %s has been successfully sent to the customer by email.', 'customer-reviews-woocommerce' ),
					$coupon_code
				)
			);
		} else {
			return array(
				1,
				sprintf(
					__( 'An error occurred when sending the discount coupon %s to the customer by email.', 'customer-reviews-woocommerce' ),
					$coupon_code
				)
			);
		}
	}

}

endif;
