<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Review_Notification_Email' ) ) :

	class CR_Review_Notification_Email
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

		public function __construct( $name ) {
			$this->name = $name;
			$this->headers[] = 'Content-Type: text/html; charset=UTF-8';

			$this->find['site-title'] = '{site_title}';
			$this->find['customer-name'] = '{customer_name}';
			$this->find['button-label'] = '{button_label}';
			$this->find['reviews-link'] = '{reviews_link}';
			$this->find['reviews-button'] = '{reviews_button}';
			$this->find['reviews-list'] = '{reviews_list}';
			$this->replace['site-title'] = Ivole_Email::get_blogname();
			$this->replace['customer-name'] = '';
			$this->replace['button-label'] = '';
			$this->replace['reviews-link'] = '';
			$this->replace['reviews-button'] = apply_filters(
				'cr_review_notif_email_button',
				'<table border="0" cellspacing="0" cellpadding="0" style="margin-top: 30px; margin-bottom: 30px;">' .
				'<tr>' .
				'<td align="center" style="border-radius: 5px; background-color: #0f9d58;">' .
				'<a rel="noopener" target="_blank" href="{reviews_link}" target="_blank" style="float: left; font-size: 14px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 5px; padding: 12px 18px; border: 1px solid #0f9d58; background-color: #0f9d58;">{button_label}</a>' .
				'</td>' .
				'</tr>' .
				'</table>'
			);
			$this->replace['reviews-list'] = '';

			switch( $this->name ) {
				case 'review_notification':
					$this->subject = __( 'New Review(s) from {customer_name}', 'customer-reviews-woocommerce' );
					$this->heading = __( 'Notification about new reviews', 'customer-reviews-woocommerce' );
					$this->body = sprintf(
						__(
							'%1$s has posted new review(s) about your store and/or products. Here is a copy of their review(s):%2$s',
							'customer-reviews-woocommerce'
						),
						'<b>{customer_name}</b>',
						'{reviews_list}{reviews_button}'
					);
					$this->template_name = 'email-review-notification.php';
					$this->replace['button-label'] = __( 'Manage Reviews', 'customer-reviews-woocommerce' );
					$this->replace['reviews-link'] = admin_url( 'admin.php?page=cr-reviews' );
					break;
				default:
					break;
			}
		}

		public function send_test( $to ) {
			// to be implemented later
			return 0;
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

		public function trigger_email( $customer, $reviews ) {
			$to = get_option( 'ivole_email_bcc', get_option( 'admin_email' ) );
			$to = trim( $to );
			if( $to && filter_var( $to, FILTER_VALIDATE_EMAIL ) ) {
				$this->replace['customer-name'] = $customer;
				$this->replace['reviews-button'] = $this->replace_variables( $this->replace['reviews-button'] );
				$this->replace['reviews-list'] = $this->format_reviews_list( $reviews );

				$this->subject = $this->replace_variables( $this->subject );
				$this->message = $this->get_email_template();

				$result = $this->send_email( $to );
			}
		}

		private function format_reviews_list( $reviews ) {
			$list = '';
			if (
				$reviews &&
				is_array( $reviews ) &&
				0 < count( $reviews )
			) {
				foreach ( $reviews  as $review ) {
					$list .= '<p><b>' . get_the_title( $review['item'] ) . '</b><br>';
					$list .= sprintf( __( 'Rating: %d star(s)', 'customer-reviews-woocommerce' ), $review['rating'] );
					if ( isset( $review['comment'] ) ) {
						$list .= '<br>' . sprintf( __( 'Comment: %s', 'customer-reviews-woocommerce' ), $review['comment'] );
					}
					$list .= '</p>';
				}
				$list = '<br>' . $list . '<br>';
			}
			return $list;
		}

	}

endif;
