<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Email_Template' ) ):

	class CR_Email_Template {

		private $name = '';
		private $template_base = '';
		private $fields;
		private $mailer;

		public function __construct( $template_name ) {
			$this->name = $template_name;
			$this->template_base = dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/';
		}

		public function get_title() {
			$title = '';
			switch( $this->name ) {
				case 'review_reminder':
					$title = __( 'Review Reminder', 'customer-reviews-woocommerce' );
					break;
				case 'review_discount':
					$title = __( 'Review for Discount', 'customer-reviews-woocommerce' );
					break;
				case 'qna_reply':
					$title = __( 'Q & A Reply Notification', 'customer-reviews-woocommerce' );
					break;
				default:
					break;
			}
			return $title;
		}

		public function get_description() {
			$description = '';
			switch( $this->name ) {
				case 'review_reminder':
					$description = __( 'Review reminders include an invitation to write a review. They are sent to customers who recently purchased something from your store.', 'customer-reviews-woocommerce' );
					break;
				case 'review_discount':
					$description = __( 'Review for Discount emails provide discount codes to customers who wrote reviews.', 'customer-reviews-woocommerce' );
					break;
				case 'qna_reply':
					$description = __( 'Reply notification are sent to customers when somebody posts an answer to their question.', 'customer-reviews-woocommerce' );
					break;
				default:
					break;
			}
			return $description;
		}

		public function is_enabled() {
			$enabled = false;
			switch( $this->name ) {
				case 'review_reminder':
					$enabled = 'yes' === get_option( 'ivole_enable', 'no' ) ? true : false;
					break;
				case 'review_discount':
					$coupon_enable_option = CR_Review_Discount_Settings::get_review_discounts();
					foreach( $coupon_enable_option as $coupon_enable ) {
						if (
							'email' === $coupon_enable['channel'] &&
							$coupon_enable['enabled']
						) {
							$enabled = true;
							break;
						}
					}
					break;
				case 'qna_reply':
					$enabled = 'yes' === get_option( 'ivole_qna_email_reply', 'no' ) ? true : false;
					break;
				default:
					break;
			}
			return $enabled;
		}

		public function get_subject() {
			$subject = '';
			switch( $this->name ) {
				case 'review_reminder':
					$subject = get_option( 'ivole_email_subject', '[{site_title}] ' . __( 'Review Your Experience with Us', 'customer-reviews-woocommerce' ) );
					break;
				case 'review_discount':
					$subject = get_option( 'ivole_email_subject_coupon', '[{site_title}] ' . __( 'Discount Coupon for You', 'customer-reviews-woocommerce' ) );
					break;
				case 'qna_reply':
					$subject = get_option( 'ivole_email_subject_' . $this->name, 'New Response to Your Question about {product_name}' );
					break;

				default:
					break;
			}
			return $subject;
		}

		public function get_from() {
			$from = '';
			$from_name = '';
			switch( $this->name ) {
				case 'review_reminder':
				case 'review_discount':
					$from = get_option( 'ivole_email_from', '' );
					$from_name = get_option( 'ivole_email_from_name', Ivole_Email::get_blogname() );
					if( 0 < strlen( $from_name ) && 0 < strlen( $from ) ) {
						$from = $from_name . ' <' . $from . '>';
					}
					if( 0 === strlen( $from ) ) {
						if( 'cr' === get_option( 'ivole_mailer_review_reminder', 'wp' ) ) {
							$from = Ivole_Email::get_blogname() . ' via CR <feedback@cusrev.com>';
						} else {
							$from = Ivole_Email::get_blogname() . ' <' . apply_filters( 'wp_mail_from', get_option( 'admin_email' ) ) . '>';
						}
					}
					break;
				case 'qna_reply':
					$from = get_option( 'ivole_email_from_' . $this->name, apply_filters( 'wp_mail_from', get_option( 'admin_email' ) ) );
					$from_name = get_option( 'ivole_email_from_name_' . $this->name );
					if( 0 < strlen( $from_name ) && 0 < strlen( $from ) ) {
						$from = $from_name . ' <' . $from . '>';
					} elseif( 0 < strlen( $from ) ) {
						$from = Ivole_Email::get_blogname() . ' <' . $from . '>';
					}
					break;

				default:
					break;
			}
			return $from;
		}

		public function get_theme_template_file() {
			$template = $this->get_template_file_name();
			return get_stylesheet_directory() . '/' . apply_filters( 'cr_template_directory', 'customer-reviews-woocommerce', $template ) . '/' . $template;
		}

		public function get_template_file_name() {
			$template = '';
			switch( $this->name ) {
				case 'review_reminder':
					$template = CR_Email_Func::TEMPLATE_REVIEW_REMINDER;
					break;
				case 'review_discount':
					$template = CR_Email_Func::TEMPLATE_REVIEW_DISCOUNT;
					break;
				case 'qna_reply':
					$template = 'qna-email-reply.php';
					break;
				default:
					break;
			}
			return $template;
		}

		private function init_fields() {
			if( 'review_reminder' === $this->name || 'review_discount' === $this->name ) {
				$this->mailer = get_option( 'ivole_mailer_review_reminder', 'wp' );
			} else {
				$this->mailer = get_option( 'ivole_mailer_' . $this->name, 'wp' );
			}
			$verified = ( 'yes' === get_option( 'ivole_verified_reviews', 'no' ) ) ? true : false;

			$this->fields = array();

			// Mailer
			switch( $this->name ) {
				case 'review_reminder':
				case 'review_discount':
					if( $verified ) {
						$options = array(
							'cr' => __( 'CusRev (AWS SES)', 'customer-reviews-woocommerce' )
						);
					} else {
						$options = array(
							'wp' => __( 'WordPress Default', 'customer-reviews-woocommerce' )
						);
					}
					break;
				case 'qna_reply':
					$options = array(
						'wp' => __( 'WordPress Default', 'customer-reviews-woocommerce' )
					);
					break;
				default:
					break;
			}
			$this->fields[5] = array(
				'title'    => __( 'Mailer', 'customer-reviews-woocommerce' ),
				'type'     => 'select',
				'desc'     => __( 'Software that will be used for sending email messages.', 'customer-reviews-woocommerce' ),
				'default'  => 'wp',
				'id'       => 'ivole_mailer_' . $this->name,
				'desc_tip' => true,
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width:300px;',
				'autoload' => false,
				'options'  => $options
			);

			// From Name
			switch( $this->name ) {
				case 'review_reminder':
				case 'review_discount':
					$id = 'ivole_email_from_name';
					$type = $verified ? 'email_from_name' : 'text';
					break;
				case 'qna_reply':
					$id = 'ivole_email_from_name_' . $this->name;
					$type = 'text';
					break;
				default:
					break;
			}
			$this->fields[10] = array(
				'title'    => __( '"From" name', 'customer-reviews-woocommerce' ),
				'desc'     => __( 'Name that will be used together with the "From" Address to send emails.', 'customer-reviews-woocommerce' ),
				'id' => $id,
				'type' => $type,
				'default'  => esc_attr( Ivole_Email::get_blogname() ),
				'autoload' => false,
				'desc_tip' => true,
				'class' => 'cr-admin-settings-wide-text',
			);

			// From Email
			switch( $this->name ) {
				case 'review_reminder':
				case 'review_discount':
					$id = 'ivole_email_from';
					$type = $verified ? 'email_from' : 'email';
					break;
				case 'qna_reply':
					$id = 'ivole_email_from_' . $this->name;
					$type = 'email';
					break;
				default:
					break;
			}
			$this->fields[15] = array(
				'title' => __( '"From" address', 'customer-reviews-woocommerce' ),
				'desc' => __( 'Emails will be sent from the email address specified in this field.', 'customer-reviews-woocommerce' ),
				'id' => $id,
				'type' => $type,
				'class' => 'cr-admin-settings-wide-text',
				'default' => apply_filters( 'wp_mail_from', get_option( 'admin_email' ) ),
				'autoload' => false,
				'desc_tip' => true
			);

			// BCC
			if( 'qna_reply' === $this->name || 'review_discount' === $this->name ) {
				switch( $this->name ) {
					case 'review_discount':
						$id = 'ivole_coupon_email_bcc';
						break;
					case 'qna_reply':
						$id = 'ivole_email_bcc_' . $this->name;
						break;
					default:
						break;
				}
				$this->fields[20] = array(
					'title' => __( '"BCC" address', 'customer-reviews-woocommerce' ),
					'desc' => __( 'Add a BCC recipient for emails. It can be useful to verify that emails are being sent correctly.', 'customer-reviews-woocommerce' ),
					'id' => $id,
					'type' => 'email',
					'class' => 'cr-admin-settings-wide-text',
					'default' => '',
					'autoload' => false,
					'desc_tip' => true
				);
			}

			// Reply-To
			if( 'review_reminder' === $this->name || 'review_discount' === $this->name ) {
				switch( $this->name ) {
					case 'review_reminder':
						$id = 'ivole_email_replyto';
						$desc = __( 'Add a Reply-To address for emails with reminders. If customers decide to reply to automatic emails, their replies will be sent to this address. It is recommended to use an email address associated with the domain of your site. If you use a free email address (e.g., Gmail or Hotmail), it will increase probability of emails being marked as SPAM.', 'customer-reviews-woocommerce' );
						break;
					case 'review_discount':
						$id = 'ivole_coupon_email_replyto';
						$desc = __( 'Add a Reply-To address for emails with discount coupons. If customers decide to reply to automatic emails, their replies will be sent to this address.', 'customer-reviews-woocommerce' );
						break;
					default:
						break;
				}
				$this->fields[25] = array(
					'title'    => __( '"Reply-To" address', 'customer-reviews-woocommerce' ),
					'type'     => 'email',
					'desc'     => $desc,
					'default'  => get_option( 'admin_email' ),
					'id'       => $id,
					'class' => 'cr-admin-settings-wide-text',
					'desc_tip' => true,
					'autoload' => false
				);
			}

			// Email for notifications
			if( 'review_reminder' === $this->name ) {
				$this->fields[30] = array(
					'title'    => __( 'Email for Notifications', 'customer-reviews-woocommerce' ),
					'type'     => 'email',
					'desc'     => __( 'Specify an email to receive notifications about new reviews and errors. It is recommended to provide an email address that you regularly check.', 'customer-reviews-woocommerce' ),
					'default'  => get_option( 'admin_email' ),
					'id'       => 'ivole_email_bcc',
					'desc_tip' => true,
					'class' => 'cr-admin-settings-wide-text',
					'autoload' => false
				);
			}

			// Email Subject
			switch( $this->name ) {
				case 'review_reminder':
					$id = 'ivole_email_subject';
					$default = '[{site_title}] Review Your Experience with Us';
					break;
				case 'review_discount':
					$id = 'ivole_email_subject_coupon';
					$default = '[{site_title}] Discount Coupon for You';
					break;
				case 'qna_reply':
					$id = 'ivole_email_subject_' . $this->name;
					$default = 'New Response to Your Question about {product_name}';
					break;
				default:
					break;
			}
			$this->fields[35] = array(
				'title' => __( 'Email Subject', 'customer-reviews-woocommerce' ),
				'type' => 'text',
				'desc_tip' => true,
				'desc' => __( 'Subject of the email that will be sent to customers.', 'customer-reviews-woocommerce' ),
				'default' => $default,
				'autoload' => false,
				'class' => 'cr-admin-settings-wide-text',
				'id' => $id,
			);

			// Email Heading
			switch( $this->name ) {
				case 'review_reminder':
					$id = 'ivole_email_heading';
					$default = 'How did we do?';
					break;
				case 'review_discount':
					$id = 'ivole_email_heading_coupon';
					$default = 'Thank You for Leaving a Review';
					break;
				case 'qna_reply':
					$id = 'ivole_email_heading_' . $this->name;
					$default = 'New Response to Your Question';
					break;
				default:
					break;
			}
			$this->fields[40] = array(
				'title' => __( 'Email Heading', 'customer-reviews-woocommerce' ),
				'type' => 'text',
				'desc_tip' => true,
				'desc' => __( 'Heading of the email that will be sent to customers.', 'customer-reviews-woocommerce' ),
				'default' => $default,
				'autoload' => false,
				'class' => 'cr-admin-settings-wide-text',
				'id' => $id,
			);

			// Email Body
			switch( $this->name ) {
				case 'review_reminder':
					$id = 'ivole_email_body';
					$default = Ivole_Email::$default_body;
					$variables = array(
						'<code>{site_title}</code> - ' . __( 'The title of your WordPress website.', 'customer-reviews-woocommerce' ),
						'<code>{customer_first_name}</code> - ' . __( 'The first name of the customer who purchased from your store.', 'customer-reviews-woocommerce' ),
						'<code>{customer_last_name}</code> - ' . __( 'The last name of the customer who purchased from your store.', 'customer-reviews-woocommerce' ),
						'<code>{customer_name}</code> - ' . __( 'The full name of the customer who purchased from your store.', 'customer-reviews-woocommerce' ),
						'<code>{order_id}</code> - ' . __( 'The order number for the purchase.', 'customer-reviews-woocommerce' ),
						'<code>{order_date}</code> - ' . __( 'The date that the order was made.', 'customer-reviews-woocommerce' ),
						'<code>{list_products}</code> - ' . __( 'A name and price list of the products purchased.', 'customer-reviews-woocommerce' )
					);
					break;
				case 'review_discount':
					$id = 'ivole_email_body_coupon';
					$default = Ivole_Email::$default_body_coupon;
					$variables = array(
						'<code>{site_title}</code> - ' . __( 'The title of your WordPress website.', 'customer-reviews-woocommerce' ),
						'<code>{customer_first_name}</code> - ' . __( 'The first name of the customer who purchased from your store.', 'customer-reviews-woocommerce' ),
						'<code>{customer_last_name}</code> - ' . __( 'The last name of the customer who purchased from your store.', 'customer-reviews-woocommerce' ),
						'<code>{customer_name}</code> - ' . __( 'The full name of the customer who purchased from your store.', 'customer-reviews-woocommerce' ),
						'<code>{coupon_code}</code> - ' . __( 'The code of coupon for discount.', 'customer-reviews-woocommerce' ),
						'<code>{discount_amount}</code> - ' . __( 'Amount of the coupon (e.g., $10 or 11% depending on type of the coupon).', 'customer-reviews-woocommerce' )
					);
					break;
				case 'qna_reply':
					$id = 'ivole_email_body_' . $this->name;
					$default = "Hi {customer_name},\n\n{user_name} responded to your question about <b>{product_name}</b>. Here is a copy of their response:\n\n<i>{answer}</i>\n\nYou can view <b>{product_name}</b> here:\n\n{product_button}\n\nBest wishes,\n{site_title} Team";
					$variables = array(
						'<code>{site_title}</code> - ' . __( 'The title of your WordPress website.', 'customer-reviews-woocommerce' ),
						'<code>{customer_name}</code> - ' . __( 'The full name of the customer who purchased from your store.', 'customer-reviews-woocommerce' ),
						'<code>{user_name}</code> - ' . __( 'The full name of a person who responded to a question.', 'customer-reviews-woocommerce' ),
						'<code>{product_name}</code> - ' . __( 'The name of the product for which a Q & A reply was posted.', 'customer-reviews-woocommerce' ),
						'<code>{answer}</code> - ' . __( 'A copy of the answer posted to the Q & A.', 'customer-reviews-woocommerce' ),
						'<code>{product_button}</code> - ' . __( 'A button with a link to the page with Q & A.', 'customer-reviews-woocommerce' )
					);
					break;
				default:
					break;
			}
			$this->fields[45] = array(
				'title'    => __( 'Email Body', 'customer-reviews-woocommerce' ),
				'type'     => 'htmltext',
				'desc'     => __( 'Body of the email that will be sent to customers.', 'customer-reviews-woocommerce' ),
				'id'       => $id,
				'desc_tip' => true,
				'default' => $default,
				'autoload' => false,
				'variables' => $variables
			);

			// Email Footer
			if( 'review_reminder' === $this->name ) {
				if( 'wp' === $this->mailer ) {
					$default = 'This email was sent by {site_title}.';
					$type = 'textarea';
				} else {
					$default = 'This email was sent by CusRev on behalf of {site_title}.<br>' . "\n" . 'If you do not want to receive any more emails from CusRev, please <a href="{{unsubscribeLink}}" style="color:#555555; text-decoration: underline; line-height: 12px; font-size: 10px;">unsubscribe</a>.';
					$type = 'footertext';
				}
				$this->fields[50] = array(
					'title'    => __( 'Email Footer', 'customer-reviews-woocommerce' ),
					'type'     => $type,
					'desc'     => __( 'Footer of the email that will be sent to customers. Modification of this field is possible with the professional license.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_email_footer',
					'default'  => $default,
					'class'    => 'cr-admin-settings-wide-text cr-admin-settings-footer',
					'desc_tip' => true,
					'custom_attributes' => array(
						'rows' => '3'
					)
				);
			}

			// Email Colors
			if( 'review_reminder' === $this->name || 'review_discount' === $this->name ) {
				switch( $this->name ) {
					case 'review_reminder':
						$id = 'ivole_email_color_bg';
						$desc = __( 'Background color for heading of the email and review button.', 'customer-reviews-woocommerce' );
						break;
					case 'review_discount':
						$id = 'ivole_email_coupon_color_bg';
						$desc = __( 'Background color for heading of the email.', 'customer-reviews-woocommerce' );
						break;
					default:
						break;
				}
				$this->fields[55] = array(
					'title'    => __( 'Email Color 1', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'id'       => $id,
					'default'  => '#0f9d58',
					'desc'     => $desc,
					'desc_tip' => true
				);
				switch( $this->name ) {
					case 'review_reminder':
						$id = 'ivole_email_color_text';
						$desc = __( 'Text color for heading of the email and review button.', 'customer-reviews-woocommerce' );
						break;
					case 'review_discount':
						$id = 'ivole_email_coupon_color_text';
						$desc = __( 'Text color for heading of the email.', 'customer-reviews-woocommerce' );
						break;
					default:
						break;
				}
				$this->fields[60] = array(
					'title'    => __( 'Email Color 2', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'id'       => $id,
					'default'  => '#ffffff',
					'desc'     => $desc,
					'desc_tip' => true
				);
			}

			if( 'review_discount' === $this->name ) {
				$this->fields[65] = array(
					'title'       => __( 'Photos/videos uploaded', 'customer-reviews-woocommerce' ),
					'type'        => 'select',
					'is_option'		=> false,
					'desc'        => __( 'Simulate sending of different coupons depending on how many photos/videos a customer attached to their review. This field can be changed without saving changes.', 'customer-reviews-woocommerce' ),
					'default'     => '0',
					'is_option' 	=> false,
					'id'          => 'cr_email_test_media_count',
					'css'         => 'width:100px;',
					'desc_tip'    => true,
					'options'			=> array(
						'0' => '0',
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5'
					)
				);
			}

			if( 'review_discount' === $this->name ) {
				$class = $this->name . ' coupon_mail';
			} else {
				$class = $this->name;
			}

			// Send Test
			$this->fields[70] = array(
				'title' => __( 'Send Test', 'customer-reviews-woocommerce' ),
				'type' => 'emailtest',
				'desc' => __( 'Send a test email to this address. You must save changes before sending a test email.', 'customer-reviews-woocommerce' ),
				'default' => '',
				'placeholder' => 'Email address',
				'css' => 'min-width:300px;',
				'desc_tip' => true,
				'class' => $class
			);

			$this->fields = apply_filters( 'cr_settings_email_template', $this->fields, $this->name );
			ksort( $this->fields );
		}

		public function output_fields() {
			$this->admin_actions();
			echo '<h2>' . esc_html( $this->get_title() );
			wc_back_link( __( 'Return to emails', 'customer-reviews-woocommerce' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=emails' ) );
			echo '</h2>';
			echo wpautop( wp_kses_post( $this->get_description() ) );
			$this->init_fields();
			echo '<table class="form-table">';
			WC_Admin_Settings::output_fields( $this->fields );
			echo '</table>';

			if (
				current_user_can( 'edit_themes' ) &&
				'wp' === $this->mailer &&
				apply_filters( 'cr_settings_email_template_php', true, $this->name )
			) {
				?>
				<div id="template">
					<?php
						$template = $this->get_template_file_name();
						$local_file    = $this->get_theme_template_file();
						$core_file     = $this->template_base . $this->get_template_file_name();
						$template_file = $core_file;
						$template_dir  = apply_filters( 'cr_template_directory', 'customer-reviews-woocommerce', $template );
						?>
						<div class="template <?php echo esc_attr( $this->name ); ?>">
							<h4><?php _e( 'Email template', 'customer-reviews-woocommerce' ) ?></h4>

							<?php if ( file_exists( $local_file ) ) : ?>
								<p>
									<a href="#" class="button toggle_editor"></a>

									<?php if ( is_writable( $local_file ) ) : ?>
										<a href="<?php echo esc_url( wp_nonce_url( remove_query_arg( array( 'move_template', 'saved' ), add_query_arg( 'delete_template', $this->name ) ), 'cr_email_template_nonce', '_cr_email_nonce' ) ); ?>" class="delete_template button">
											<?php esc_html_e( 'Delete template file', 'customer-reviews-woocommerce' ); ?>
										</a>
									<?php endif; ?>

									<?php
									/* translators: %s: Path to template file */
									printf( esc_html__( 'This template has been overridden by your theme and can be found in: %s.', 'customer-reviews-woocommerce' ), '<code>' . esc_html( trailingslashit( basename( get_stylesheet_directory() ) ) . $template_dir . '/' . $template ) . '</code>' );
									?>
								</p>

								<div class="editor" style="display:none">
									<textarea class="code" cols="25" rows="20"
									<?php
									if ( ! is_writable( $local_file ) ) :
										?>
										readonly="readonly" disabled="disabled"
									<?php else : ?>
										data-name="<?php echo esc_attr( $this->name ) . '_code'; ?>"<?php endif; ?>><?php echo esc_html( file_get_contents( $local_file ) ); ?></textarea>
								</div>
							<?php elseif ( file_exists( $template_file ) ) : ?>
								<p>
									<a href="#" class="button toggle_editor"></a>

									<?php
									$templates_dir = get_stylesheet_directory() . '/' . $template_dir;
									$theme_dir     = get_stylesheet_directory();

									if ( is_dir( $templates_dir ) ) {
										$target_dir = $templates_dir;
									} else {
										$target_dir = $theme_dir;
									}

									if ( is_writable( $target_dir ) ) :
										?>
										<a href="<?php echo esc_url( wp_nonce_url( remove_query_arg( array( 'delete_template', 'saved' ), add_query_arg( 'move_template', $this->name ) ), 'cr_email_template_nonce', '_cr_email_nonce' ) ); ?>" class="button">
											<?php esc_html_e( 'Copy file to theme', 'customer-reviews-woocommerce' ); ?>
										</a>
									<?php endif; ?>

									<?php
									/* translators: 1: Path to template file 2: Path to theme folder */
									printf( esc_html__( 'To override and edit this email template copy %1$s to your theme folder: %2$s.', 'customer-reviews-woocommerce' ), '<code>' . esc_html( plugin_basename( $template_file ) ) . '</code>', '<code>' . esc_html( trailingslashit( basename( get_stylesheet_directory() ) ) . $template_dir . '/' . $template ) . '</code>' );
									?>
								</p>

								<div class="editor" style="display:none">
									<textarea class="code" readonly="readonly" disabled="disabled" cols="25" rows="20"><?php echo esc_html( file_get_contents( $template_file ) );  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents ?></textarea>
								</div>
							<?php else : ?>
								<p><?php esc_html_e( 'File was not found.', 'customer-reviews-woocommerce' ); ?></p>
							<?php endif; ?>
						</div>
				</div>
				<?php
				wc_enqueue_js(
					"jQuery( 'select.email_type' ).on( 'change', function() {

						var val = jQuery( this ).val();

						jQuery( '.template_plain, .template_html' ).show();

						if ( val != 'multipart' && val != 'html' ) {
							jQuery('.template_html').hide();
						}

						if ( val != 'multipart' && val != 'plain' ) {
							jQuery('.template_plain').hide();
						}

					}).trigger( 'change' );

					var view = '" . esc_js( __( 'View template', 'customer-reviews-woocommerce' ) ) . "';
					var hide = '" . esc_js( __( 'Hide template', 'customer-reviews-woocommerce' ) ) . "';

					jQuery( 'a.toggle_editor' ).text( view ).on( 'click', function() {
						var label = hide;

						if ( jQuery( this ).closest(' .template' ).find( '.editor' ).is(':visible') ) {
							var label = view;
						}

						jQuery( this ).text( label ).closest(' .template' ).find( '.editor' ).slideToggle();
						return false;
					} );

					jQuery( 'a.delete_template' ).on( 'click', function() {
						if ( window.confirm('" . esc_js( __( 'Are you sure you want to delete this template file?', 'customer-reviews-woocommerce' ) ) . "') ) {
							return true;
						}

						return false;
					});

					jQuery( '.editor textarea' ).on( 'change', function() {
						var name = jQuery( this ).attr( 'data-name' );

						if ( name ) {
							jQuery( this ).attr( 'name', name );
						}
					});"
				);
			}
		}

		public function save_fields() {
			if( ! empty( $_POST ) && isset( $_POST['ivole_email_body'] ) ) {
				if( empty( preg_replace( '#\s#isUu', '', html_entity_decode( $_POST['ivole_email_body'] ) ) ) ) {
					WC_Admin_Settings::add_error( __( '\'Email Body\' field cannot be empty', 'customer-reviews-woocommerce' ) );
					$_POST['ivole_email_body'] = get_option( 'ivole_email_body' );
				}
			}
			if( ! empty( $_POST ) && isset( $_POST['ivole_email_body_coupon'] ) ) {
				if( empty( preg_replace( '#\s#isUu', '', html_entity_decode( $_POST['ivole_email_body_coupon'] ) ) ) ) {
					WC_Admin_Settings::add_error( __( '\'Email Body\' field cannot be empty', 'customer-reviews-woocommerce' ) );
					$_POST['ivole_email_body_coupon'] = get_option( 'ivole_email_body_coupon' );
				}
			}
			// validate colors (users sometimes remove # or provide invalid hex color codes)
			if ( ! empty( $_POST ) && isset( $_POST['ivole_email_color_bg'] ) ) {
				if( ! preg_match_all( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['ivole_email_color_bg'] ) ) {
					$_POST['ivole_email_color_bg'] = '#0f9d58';
				}
			}
			if ( ! empty( $_POST ) && isset( $_POST['ivole_email_color_text'] ) ) {
				if( ! preg_match_all( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['ivole_email_color_text'] ) ) {
					$_POST['ivole_email_color_text'] = '#ffffff';
				}
			}
			//
			$this->init_fields();
			WC_Admin_Settings::save_fields( $this->fields );
		}

		private function admin_actions() {
			if (
				( ! empty( $_GET['move_template'] ) || ! empty( $_GET['delete_template'] ) )
				&& 'GET' === $_SERVER['REQUEST_METHOD']
			) {
				if ( empty( $_GET['_cr_email_nonce'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_GET['_cr_email_nonce'] ) ), 'cr_email_template_nonce' ) ) {
					wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'customer-reviews-woocommerce' ) );
				}

				if ( ! current_user_can( 'edit_themes' ) ) {
					wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'customer-reviews-woocommerce' ) );
				}

				if ( ! empty( $_GET['move_template'] ) ) {
					$this->move_template_action();
				}

				if ( ! empty( $_GET['delete_template'] ) ) {
					$this->delete_template_action();
				}
			}
		}

		private function move_template_action() {
			$theme_file = $this->get_theme_template_file();
			if ( wp_mkdir_p( dirname( $theme_file ) ) && ! file_exists( $theme_file ) ) {
				// Locate template file.
				$core_file = $this->template_base . $this->get_template_file_name();;
				// Copy template file.
				copy( $core_file, $theme_file );
				?>
				<div class="updated">
					<p><?php echo esc_html__( 'Template file copied to theme.', 'customer-reviews-woocommerce' ); ?></p>
				</div>
				<?php
			}
		}

		private function delete_template_action() {
			$theme_file = $this->get_theme_template_file();
			if ( file_exists( $theme_file ) ) {
				unlink( $theme_file );
				?>
				<div class="updated">
					<p><?php echo esc_html__( 'Template file deleted from theme.', 'customer-reviews-woocommerce' ); ?></p>
				</div>
				<?php
			}
		}

	}

endif;
