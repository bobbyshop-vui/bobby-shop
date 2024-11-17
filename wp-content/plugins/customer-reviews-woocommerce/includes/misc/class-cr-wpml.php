<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_WPML' ) ) :

	class CR_WPML {

		public static function translate_admin( $fields ) {
			$fields['ivole_review_button'] = __( 'Review', 'customer-reviews-woocommerce' );
			if ( has_action( 'wpml_register_single_string' ) ) {
				//tab = Review Reminder
				$fields_to_translate = array(
					'ivole_email_subject',
					'ivole_email_heading',
					'ivole_email_body',
					'ivole_form_header',
					'ivole_form_body',
					'ivole_email_from',
					'ivole_email_from_name',
					'ivole_email_replyto',
					'ivole_email_footer',
					'ivole_email_subject_coupon',
					'ivole_email_heading_coupon',
					'ivole_email_body_coupon',
					'ivole_customer_consent_text',
					'ivole_verified_owner',
					'ivole_review_button',
					'ivole_email_from_name_qna_reply',
					'ivole_email_from_qna_reply',
					'ivole_email_subject_qna_reply',
					'ivole_email_heading_qna_reply',
					'ivole_email_body_qna_reply',
					'ivole_review_login_url'
				);
				foreach ( $fields_to_translate as $field_to_translate ) {
					if ( isset( $fields[$field_to_translate] ) ) {
						$fields[$field_to_translate] = wp_unslash( $fields[$field_to_translate] );
						switch ($field_to_translate) {
							case 'ivole_email_body':
							case 'ivole_email_body_coupon':
							case 'ivole_email_body_qna_reply':
								$fields[$field_to_translate] = wp_kses_post( $fields[$field_to_translate] );
								break;
							default:
								$fields[$field_to_translate] = wc_clean( $fields[$field_to_translate] );
								break;
						}
						do_action( 'wpml_register_single_string', 'ivole', $field_to_translate, $fields[$field_to_translate] );
					}
				}
			}
		}
	}

endif;
