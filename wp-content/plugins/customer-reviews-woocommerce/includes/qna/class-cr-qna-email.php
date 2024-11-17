<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Qna_Email' ) ) :

	/**
	 * Class for Q & A emails
	 */
	class CR_Qna_Email
	{
		private $name = '';
		private $subject = '';
		private $heading = '';
		private $body = '';
		private $message = '';
		private $headers = array();
		private $template_name = '';
		private $find = array();
		private $replace = array();

		public function __construct( $name, $language = NULL ) {
			$this->name = $name;
			$this->headers[] = 'Content-Type: text/html; charset=UTF-8';

			$this->find['site-title'] = '{site_title}';
			$this->find['customer-name'] = '{customer_name}';
			$this->find['user-name'] = '{user_name}';
			$this->find['product-name'] = '{product_name}';
			$this->find['product-link'] = '{product_link}';
			$this->find['answer'] = '{answer}';
			$this->find['product-button'] = '{product_button}';
			$this->replace['site-title'] = Ivole_Email::get_blogname();
			$this->replace['customer-name'] = '';
			$this->replace['user-name'] = '';
			$this->replace['product-name'] = '';
			$this->replace['product-link'] = '';
			$this->replace['answer'] = '';
			$this->replace['product-button'] = apply_filters(
				'cr_qna_email_product_button',
				'<table border="0" cellspacing="0" cellpadding="0" style="margin-top: 30px; margin-bottom: 30px;">' .
				'<tr>' .
				'<td align="center" style="border-radius: 5px; background-color: #0f9d58;">' .
				'<a rel="noopener" target="_blank" href="{product_link}" target="_blank" style="float: left; font-size: 14px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 5px; padding: 12px 18px; border: 1px solid #0f9d58; background-color: #0f9d58;">{product_name}</a>' .
				'</td>' .
				'</tr>' .
				'</table>'
			);

			$from = '';
			$from_name = '';

			switch( $this->name ) {
				case 'qna_reply':
					$this->subject = get_option( 'ivole_email_subject_' . $this->name, 'New Response to Your Question about {product_name}' );
					$this->heading = get_option( 'ivole_email_heading_' . $this->name, 'New Response to Your Question' );
					$this->body = get_option( 'ivole_email_body_' . $this->name, "Hi {customer_name},\n\n{user_name} responded to your question about <b>{product_name}</b>. Here is a copy of their response:\n\n<i>{answer}</i>\n\nYou can view <b>{product_name}</b> here:\n\n{product_button}\n\nBest wishes,\n{site_title} Team" );
					$this->template_name = 'qna-email-reply.php';
					$from = trim( get_option( 'ivole_email_from_' . $this->name, '' ) );
					$from_name = trim( get_option( 'ivole_email_from_name_' . $this->name, '' ) );
					break;
				default:
					break;
			}

			// WPML integration
			if ( $language && has_filter( 'wpml_translate_single_string' ) ) {
				$this->subject = apply_filters( 'wpml_translate_single_string', $this->subject, 'ivole', 'ivole_email_subject_' . $this->name, $language );
				$this->heading = apply_filters( 'wpml_translate_single_string', $this->heading, 'ivole', 'ivole_email_heading_' . $this->name, $language );
				$this->body = apply_filters( 'wpml_translate_single_string', $this->body, 'ivole', 'ivole_email_body_' . $this->name, $language );
				$from = apply_filters( 'wpml_translate_single_string', $from, 'ivole', 'ivole_email_from_' . $this->name, $language );
				$from_name = apply_filters( 'wpml_translate_single_string', $from_name, 'ivole', 'ivole_email_from_name_' . $this->name, $language );
			}

			if( $from ) {
				if( $from_name ) {
					$this->headers[] = 'From: ' . $from_name . ' <' . $from . '>';
				} else {
					$this->headers[] = 'From: ' . $from;
				}
			}
		}

		public function send_test( $to ) {
			$this->replace['customer-name'] = 'Ann';
			$this->replace['user-name'] = 'John';

			$random_products = wc_get_products( array( 'limit' => 1, 'return' => 'objects' ) );
			if( $random_products && is_array( $random_products ) && 0 < count( $random_products ) ) {
				$this->replace['product-name'] = $random_products[0]->get_name();
				$this->replace['product-link'] = $random_products[0]->get_permalink();
			} else {
				$this->replace['product-name'] = 'T-Shirt';
			}

			$this->replace['answer'] = 'This is a test email with a test answer. \'Ann\' is a name of a fictional person who posted a question. \'John\' is a name of a fictional person who posted an answer. If somebody replies to a real question, these fictional names will be replaced with actual names.';
			$this->replace['product-button'] = $this->replace_variables( $this->replace['product-button'] );

			$this->subject = $this->replace_variables( $this->subject );
			$this->message = $this->get_email_template();
			$result = $this->send_email( $to );
			if( $result ) {
				return 0;
			} else {
				return -1;
			}
		}

		public function send_email( $to ) {
			return wp_mail( $to, $this->subject, $this->message, $this->headers );
		}

		private function get_email_template() {
			$template = wc_locate_template(
				$this->template_name,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$email_template = '';
			ob_start();
			$cr_email_heading = $this->replace_variables( $this->heading );
			$cr_email_body = $this->replace_variables( $this->body );
			$cr_email_footer = Ivole_Email::get_blogname();
			include( $template );
			$email_template = ob_get_clean();
			return $email_template;
		}

		private function replace_variables( $input ) {
			return str_replace( $this->find, $this->replace, $input );
		}

		public function trigger_email( $question_id, $user_name, $product_id, $answer, $answer_id ) {
			if( 'yes' === get_option( 'ivole_qna_email_reply', 'no' ) ) {
				if( ! get_comment_meta( $answer_id, 'cr_answer_notification', true ) ) {
					$to = get_comment_author_email( $question_id );
					if ( filter_var( $to, FILTER_VALIDATE_EMAIL ) ) {
						$this->replace['customer-name'] = get_comment_author( $question_id );
						$this->replace['user-name'] = $user_name;
						$this->replace['product-name'] = get_the_title( $product_id );
						$this->replace['product-link'] = get_permalink( $product_id );
						$this->replace['answer'] = $answer;
						$this->replace['product-button'] = $this->replace_variables( $this->replace['product-button'] );

						$this->subject = $this->replace_variables( $this->subject );
						$this->message = $this->get_email_template();

						$bcc = trim( get_option( 'ivole_email_bcc_' . $this->name, '' ) );
						if( $bcc ) {
							$this->headers[] = 'Bcc: ' . $bcc;
						}

						$result = $this->send_email( $to );

						if( $result ) {
							update_comment_meta( $answer_id, 'cr_answer_notification', 1 );
						}
					}
				}
			}
		}

	}

endif;
