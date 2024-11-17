<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Manual' ) ) :

	class CR_Manual {
		private $auto_reminders;
		private $order_status;
		private $order_status_name;

		public function __construct() {
			$automatic_reminders_enabled = 'yes' === get_option( 'ivole_enable', 'no' ) ? true : false;
			$this->auto_reminders = $automatic_reminders_enabled;
			$manual_reminders_enabled = 'yes' === get_option( 'ivole_enable_manual', 'yes' ) ? true : false;

			if( ! $automatic_reminders_enabled && ! $manual_reminders_enabled ) {
				return;
			}

			// new 'Review Reminder' column
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'custom_shop_order_column' ), 20 );
			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'custom_shop_order_column' ), 20 );
			add_action( 'manage_shop_order_posts_custom_column' , array( $this, 'custom_orders_list_column_content' ), 10, 2 );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column' , array( $this, 'custom_orders_list_column_content' ), 10, 2 );
			add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ), 20, 2 );

			$wc_status_names = wc_get_order_statuses();
			$this->order_status = get_option( 'ivole_order_status', 'completed' );
			$this->order_status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;
			$this->order_status_name = $this->order_status;
			if( isset( $wc_status_names['wc-' . $this->order_status] ) ) {
				$this->order_status_name = $wc_status_names['wc-' . $this->order_status];
			}

			if( ! $manual_reminders_enabled ) {
				return;
			}

			// 'Send now' envelope button
			add_filter( 'woocommerce_admin_order_actions', array( $this, 'manual_sending' ), 20, 2 );
			add_action( 'admin_head', array( $this, 'add_custom_order_status_actions_button_css' ) );
			add_action( 'wp_ajax_ivole_manual_review_reminder', array( $this, 'manual_review_reminder' ) );
			add_action( 'wp_ajax_cr_manual_review_reminder_wa', array( $this, 'manual_wa_review_reminder' ) );
			add_action( 'wp_ajax_cr_manual_review_reminder_wa_api', array( $this, 'manual_wa_review_reminder_api' ) );
			add_action( 'wp_ajax_cr_manual_review_reminder_conf', array( $this, 'manual_review_reminder_conf' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'include_scripts' ) );
		}

		public function manual_sending( $actions, $order ) {
			// Display the button for all orders that have a 'completed' status
			if ( $order->has_status( array( $this->order_status ) ) ) {
				// Get Order ID (compatibility all WC versions)
				$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
				$consent = $order->get_meta( '_ivole_cr_consent', true );
				$verified_reviews = get_option( 'ivole_verified_reviews', 'no' );
				if (
					'yes' === $consent ||
					(
						! CR_Sender::is_eu_customer( $order ) &&
						'no' !== $consent
					) ||
					(
						'yes' !== $verified_reviews &&
						'no' !== $consent
					)
				) {
					// Set the action button
					if (
						$order->get_meta( '_ivole_cr_cron', true ) ||
						( '' === $order->get_meta( '_ivole_review_reminder', true ) && 'cr' === get_option( 'ivole_scheduler_type', 'wp' ) )
					) {
						$actions['ivole'] = array(
							'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=ivole_manual_review_reminder&order_id=' . $order_id ), 'cr-man-rem', 'cr_manual_reminder' ),
							'name'      => __( 'Sync the order with CR Cron', 'customer-reviews-woocommerce' ),
							'action'    => "view ivole-order ivole-order-cr ivole-o-" . $order_id,
						);
					} else {
						$whatsapp_class = 'cr-whatsapp-act';
						if ( 'yes' === $verified_reviews ) {
							$whatsapp_class .= ' cr-whatsapp-api';
						}
						if ( ! $this->is_phone_exists( $order ) ) {
							$whatsapp_class .= ' cr-no-phone';
						}
						$actions['ivole'] = array(
							'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=ivole_manual_review_reminder&order_id=' . $order_id ), 'cr-man-rem', 'cr_manual_reminder' ),
							'name'      => __( 'Send review reminder now', 'customer-reviews-woocommerce' ),
							'action'    => "view ivole-order ivole-o-" . $order_id . ' ' . $whatsapp_class,
						);
					}
				} else {
					// Set the action button to do nothing but display an error message when no consent was received
					$actions['ivole'] = array(
						'url'       => '',
						/* translators: %s will be automatically replaced with the status name */
						'name'      => sprintf( __( 'A review reminder cannot be sent because the customer did not provide their consent', 'customer-reviews-woocommerce' ), '\'' . $this->order_status_name . '\'' ),
						'action'    => "view ivole-order cr-order-dimmed", // keep "view" class for a clean button CSS
					);
				}
			} else {
				// Set the action button to do nothing but display an error message when the order status is wrong
				$actions['ivole'] = array(
					'url'       => '',
					/* translators: %s will be automatically replaced with the status name */
					'name'      => sprintf( __( 'If you would like to send a review reminder manually, please set the order status to %s', 'customer-reviews-woocommerce' ), '\'' . $this->order_status_name . '\'' ),
					'action'    => "view ivole-order cr-order-dimmed", // keep "view" class for a clean button CSS
				);
			}
			return $actions;
		}

		public function add_custom_order_status_actions_button_css() {
			echo '<style>.view.ivole-order.ivole-order-cr::after { font-family: woocommerce !important; content: "\e031" !important; } .widefat .column-ivole-review-reminder {width: 100px;} ' .
			'.view.ivole-order::after { font-family: woocommerce !important; content: "\e02d" !important; } .widefat .column-ivole-review-reminder {width: 100px;} .view.ivole-order.cr-order-dimmed { opacity: 0.6; cursor: help; }</style>';
		}

		public function manual_review_reminder() {
			if ( isset( $_POST['order_id'] ) ) {
				$order_id = intval( $_POST['order_id'] );

				if ( ! wp_verify_nonce( $_POST['nonce'], 'cr-man-rem' ) ) {
					wp_send_json(
						array(
							'code' => 101,
							'message' => __( 'A security token expired, please refresh the page and try again.', 'customer-reviews-woocommerce' ),
							'order_id' => $order_id
						)
					);
				}

				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_send_json(
						array(
							'code' => 102,
							'message' => __( 'Your user account does not have permissions for sending review reminders.', 'customer-reviews-woocommerce' ),
							'order_id' => $order_id
						)
					);
				}

				$order = wc_get_order( $order_id );

				if ( ! $order  ) {
					wp_send_json( array( 'code' => 98, 'message' => __( 'Error: invalid order ID.', 'customer-reviews-woocommerce' ), 'order_id' => $order_id ) );
				}

				//qTranslate integration
				$lang = $order->get_meta( '_user_language', true );
				$old_lang = '';
				if( $lang ) {
					global $q_config;
					$old_lang = $q_config['language'];
					$q_config['language'] = $lang;

					//WPML integration
					if ( has_filter( 'wpml_current_language' ) ) {
						$old_lang = apply_filters( 'wpml_current_language', NULL );
						do_action( 'wpml_switch_language', $lang );
					}
				}

				// Check if a review reminder for this order was sent with a different mailer previously
				$mailer = get_option( 'ivole_mailer_review_reminder', 'wp' );
				if( 'cr' === $mailer ) {
					$existing_wp_reviews_count = get_comments( array(
						'meta_key' => 'ivole_order_locl',
						'meta_value' => $order_id,
						'count' => true
					) );
					if( 0 < $existing_wp_reviews_count ) {
						wp_send_json( array( 'code' => 97, 'message' => __( 'Error: a review reminder could not be sent because reviews(s) have already been collected with a WordPress mailer for this order.', 'customer-reviews-woocommerce' ), 'order_id' => $order_id ) );
					}
				} else {
					$existing_cr_reviews_count = get_comments( array(
						'meta_key' => 'ivole_order',
						'meta_value' => $order_id,
						'count' => true
					) );
					if( 0 < $existing_cr_reviews_count ) {
						wp_send_json( array( 'code' => 97, 'message' => __( 'Error: a review reminder could not be sent because reviews(s) have already been collected with a CusRev mailer for this order.', 'customer-reviews-woocommerce' ), 'order_id' => $order_id ) );
					}
				}

				$schedule = $this->get_schedule( $order );

				$l_msg = '';
				$e = new Ivole_Email( $order_id );
				$result = $e->trigger2( $order_id, null, $schedule );
				// logging
				$log = new CR_Reminders_Log();
				$l_result = $log->add(
					$order_id,
					'm',
					'email',
					$result
				);
				if (
					is_array( $l_result ) &&
					isset( $l_result['code'] ) &&
					0 !== $l_result['code'] &&
					isset( $l_result['text'] )
				) {
					$l_msg = ';<br>' . esc_html( $l_result['text'] );
				}
				// end of logging

				//qTranslate integration
				if( $lang ) {
					$q_config['language'] = $old_lang;

					//WPML integration
					if ( has_filter( 'wpml_current_language' ) ) {
						do_action( 'wpml_switch_language', $old_lang );
					}
				}

				$msg = '';
				if ( ! $schedule ) {
					// necessary for reminders sent via WP Cron
					$order->read_meta_data( true );
					$status = $order->get_meta( '_ivole_review_reminder', true );
					if( '' === $status ) {
						$msg = __( 'No reminders sent', 'customer-reviews-woocommerce' );
					} else {
						$status = intval( $status );
						if( $status > 0) {
							$msg = $status . __( ' reminder(s) sent', 'customer-reviews-woocommerce' );
						} else {
							$msg = __( 'No reminders sent yet', 'customer-reviews-woocommerce' );
						}
					}
				}

				if ( is_array( $result ) && count( $result ) > 1 && 0 !== $result[0] ) {
					wp_send_json( array( 'code' => $result[0], 'message' => $result[1] . $l_msg, 'order_id' => $order_id ) );
				} elseif (
					0 === $result ||
					( is_array( $result ) && count( $result ) > 1 && 0 === $result[0] )
				) {
					// unschedule automatic review reminder if manual sending was successful (for reminders sent via WP Cron)
					if ( ! $schedule ) {
						$timestamp = wp_next_scheduled( 'ivole_send_reminder', array( $order_id ) );
						if ( $timestamp ) {
							wp_unschedule_event( $timestamp, 'ivole_send_reminder', array( $order_id ) );
						}
						do_action( 'cr_manual_unschedule', $order_id );
						$order->add_order_note( __( 'CR: a review reminder was triggered manually via email.', 'customer-reviews-woocommerce' ) );
					} else {
						$msg = __( 'Successfully synced with CR Cron', 'customer-reviews-woocommerce' );
					}
					wp_send_json( array( 'code' => 0, 'message' => $msg . $l_msg, 'order_id' => $order_id ) );
				} else {
					wp_send_json( array( 'code' => 98, 'message' => 'Error code 98' . $l_msg, 'order_id' => $order_id ) );
				}
			}
		}

		public function custom_shop_order_column( $columns ) {
			$columns['ivole-review-reminder'] = __( 'Review Reminder', 'customer-reviews-woocommerce' );
			return $columns;
		}

		public function custom_orders_list_column_content( $column, $post_id ) {
			if( 'ivole-review-reminder' === $column ) {
				$order = wc_get_order( $post_id );
				if ( $order ) {
					// Check customer consent
					$no_cr_consent = 'no' === $order->get_meta( '_ivole_cr_consent', true ) ? true : false;
					if( $no_cr_consent ) {
						echo __( 'No customer consent received', 'customer-reviews-woocommerce' );
						return;
					}
					//count reviews that an order has received
					$args = array(
						'count' => true,
						'meta_key' => array( 'ivole_order', 'ivole_order_locl' ),
						'meta_value' => $order->get_id()
					);
					$reviews_count = get_comments( $args );
					$reviews_text = '';
					if( $reviews_count > 0 ) {
						/* translators: %d will be automatically replaced with the count of reviews */
						$reviews_text = ';<br> ' . sprintf( _n( '%d review received', '%d reviews received', $reviews_count, 'customer-reviews-woocommerce' ), $reviews_count );
					}
					//
					$cr_cron = $order->get_meta( '_ivole_cr_cron', true );
					//check if a review reminder was sent via CR Cron
					if( $cr_cron ) {
						echo __( 'A review reminder was scheduled via CR Cron', 'customer-reviews-woocommerce' ) . $reviews_text;
					} else {
						//a review has not been sent via CR Cron
						$reminder = $order->get_meta( '_ivole_review_reminder', true );
						if( '' === $reminder && 'cr' === get_option( 'ivole_scheduler_type', 'wp' ) ) {
							//no review reminder has been scheduled via WP Cron and CR Cron is the current setting
							if( $this->auto_reminders ) {
								if ( $order->has_status( array( $this->order_status ) ) ) {
									echo __( 'No reminders sent yet', 'customer-reviews-woocommerce' );
								} else {
									/* translators: %s will be automatically replaced with the status name */
									echo sprintf( __( 'A review reminder will be scheduled after the status is set to %s', 'customer-reviews-woocommerce' ), '\'' . $this->order_status_name . '\'' );
								}
							} else {
								echo __( 'Automatic review reminders are disabled', 'customer-reviews-woocommerce' );
							}
						} else {
							if( !$reminder ) {
								//no review reminder has been sent via WP Cron and WP Cron is the current setting
								if( $this->auto_reminders ) {
									if ( $order->has_status( array( $this->order_status ) ) ) {
										echo __( 'No reminders sent yet', 'customer-reviews-woocommerce' );
									} else {
										/* translators: %s will be automatically replaced with the status name */
										echo sprintf( __( 'A review reminder will be scheduled after the status is set to %s', 'customer-reviews-woocommerce' ), '\'' . $this->order_status_name . '\'' );
									}
								} else {
									echo __( 'Automatic review reminders are disabled', 'customer-reviews-woocommerce' );
								}
							} else {
								//a review reminder has been sent via WP Cron
								$reminder = intval( $reminder );
								if( $reminder > 0) {
									/* translators: %d will be automatically replaced with the count of review reminders */
									echo sprintf( _n( '%d reminder sent', '%d reminders sent', $reminder, 'customer-reviews-woocommerce' ), $reminder ) . $reviews_text;
								} else {
									echo __( 'No reminders sent', 'customer-reviews-woocommerce' ) . $reviews_text;
								}
							}
							$timestamp = wp_next_scheduled( 'ivole_send_reminder', array( $order->get_id() ) );
							if( $timestamp ) {
								echo ';<br> ';
								if( $timestamp >= 0 ) {
									$local_timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', $timestamp ), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) . ' (T)' );
									echo esc_html( __( 'A reminder is scheduled for ', 'customer-reviews-woocommerce' ) . $local_timestamp );
								} else {
									echo esc_html__( 'WP Cron error', 'customer-reviews-woocommerce' );
								}
							} else {
								do_action( 'cr_manual_next_scheduled', $order );
							}
						}
					}
				}
			}
		}

		public function default_hidden_columns( $hidden, $screen ) {
			if (
				isset( $screen->id ) &&
				(
					'edit-shop_order' === $screen->id ||
					(
						function_exists( 'wc_get_page_screen_id' ) &&
						wc_get_page_screen_id( 'shop_order' ) === $screen->id
					)
				)
			) {
				array_splice( $hidden, array_search( 'wc_actions', $hidden ), 1 );
			}
			return $hidden;
		}

		public function include_scripts( $hook ) {
			if ( 'edit.php' == $hook || 'woocommerce_page_wc-orders' === $hook ) {
				wp_register_script( 'cr-manual-review-reminder', plugins_url( 'js/admin-manual.js', dirname( dirname( __FILE__ ) ) ), array( 'jquery' ), Ivole::CR_VERSION, false );

				$send_button = '<ul class="cr-send-menu">';
				$send_button .= '<li class="cr-send-email">' . __( 'Email', 'customer-reviews-woocommerce' );
				$send_button .= '<span class="dashicons dashicons-email"></span></li>';
				//
				$send_button .= '<li class="cr-send-whatsapp" data-tip="' . esc_attr__( 'A review invitation cannot be sent by WhatsApp because no phone number is found in the order.', 'customer-reviews-woocommerce' ) . '">';
				$send_button .= __( 'WhatsApp', 'customer-reviews-woocommerce' );
				$send_button .= '<span class="dashicons dashicons-whatsapp"></span></li>';
				//
				$send_button .= '<li class="cr-send-wa-cons"><span class="cr-send-wa-cons-msg">';
				$send_button .= __( 'Has the customer provided a consent to receive a review invitation?', 'customer-reviews-woocommerce' );
				$send_button .= '</span><span class="cr-send-wa-cons-btn">';
				$send_button .= '<a href="" class="cr-send-wa-cons-yes">' . __( 'Yes', 'customer-reviews-woocommerce' ) . '</a>';
				$send_button .= '<a href="" class="cr-send-wa-cons-no">' . __( 'No', 'customer-reviews-woocommerce' ) . '</a>';
				$send_button .= '<span class="cr-send-wa-spinner"></span>';
				$send_button .= '</span></li>';
				//
				$send_button .= '<li class="cr-send-wa-link"><span class="cr-send-wa-link-msg">';
				$send_button .= '</span><span class="cr-send-wa-link-btn">';
				$send_button .= '<a href="" target="_blank" class="cr-send-wa-link-yes">' .
					__( 'Send', 'customer-reviews-woocommerce' ) .
					'<span class="dashicons dashicons-external cr-send-wa-link-ext"></span>' .
					'</a>';
				$send_button .= '<a href="" class="cr-send-wa-link-no">' . __( 'Cancel', 'customer-reviews-woocommerce' ) . '</a>';
				$send_button .= '<span class="cr-send-wa-spinner"></span>';
				$send_button .= '</span></li>';
				//
				$send_button .= '<li class="cr-send-wa-error"><span class="cr-send-wa-error-msg">';
				$send_button .= '</span><span class="cr-send-wa-error-btn">';
				$send_button .= '<a href="" class="cr-send-wa-error-ok">' . __( 'OK', 'customer-reviews-woocommerce' ) . '</a>';
				$send_button .= '</span></li>';
				//
				$send_button .= '<li class="cr-send-wa-fbck"><span class="cr-send-wa-fbck-msg">';
				$send_button .= __( 'Have you sent the review invitation?', 'customer-reviews-woocommerce' );
				$send_button .= '</span><span class="cr-send-wa-fbck-btn">';
				$send_button .= '<a href="" class="cr-send-wa-fbck-yes">' . __( 'Yes', 'customer-reviews-woocommerce' ) . '</a>';
				$send_button .= '<a href="" class="cr-send-wa-fbck-no">' . __( 'No', 'customer-reviews-woocommerce' ) . '</a>';
				$send_button .= '<span class="cr-send-wa-spinner"></span>';
				$send_button .= '</span></li></ul>';

				wp_localize_script('cr-manual-review-reminder', 'CrManualStrings', array(
					'sending' => __( 'Sending...', 'customer-reviews-woocommerce' ),
					'syncing' => __( 'Syncing...', 'customer-reviews-woocommerce' ),
					'error_code_1' => __( 'Error code 1', 'customer-reviews-woocommerce' ),
					'error_code_2' => __( 'Error code 2 (%s).', 'customer-reviews-woocommerce' ),
					'send_button' => $send_button
				));

				wp_enqueue_script( 'cr-manual-review-reminder' );

				wp_enqueue_style( 'cr-admin-css', plugins_url('css/admin.css', dirname( dirname( __FILE__ ) ) ), array(), Ivole::CR_VERSION );
			}
		}

		public function manual_wa_review_reminder() {
			if ( isset( $_POST['order_id'] ) ) {
				$order_id = intval( $_POST['order_id'] );

				if ( ! wp_verify_nonce( $_POST['nonce'], 'cr-man-rem' ) ) {
					wp_send_json(
						array(
							'code' => 101,
							'message' => __( 'A security token expired, please refresh the page and try again.', 'customer-reviews-woocommerce' ),
							'order_id' => $order_id
						)
					);
				}

				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_send_json(
						array(
							'code' => 102,
							'message' => __( 'Your user account does not have permissions for sending review reminders.', 'customer-reviews-woocommerce' ),
							'order_id' => $order_id
						)
					);
				}

				$order = wc_get_order( $order_id );

				if ( ! $order  ) {
					wp_send_json( array( 'code' => 98, 'message' => __( 'Error: invalid order ID.', 'customer-reviews-woocommerce' ), 'order_id' => $order_id ) );
				}

				if ( isset( $_POST['type'] ) && 'api' === $_POST['type'] ) {
					// sending via an api
					// Check if there are any reviews for this order collected via local forms previously
					$existing_cr_reviews_count = get_comments(
						array(
							'meta_key' => 'ivole_order_locl',
							'meta_value' => $order_id,
							'count' => true
						)
					);
					if( 0 < $existing_cr_reviews_count ) {
						wp_send_json(
							array(
								'code' => 2,
								'message' => __( 'Error: a review reminder could not be sent because reviews(s) have already been collected via a local review form for this order.', 'customer-reviews-woocommerce' )
							)
						);
					}

					$wa = new CR_Wtsap( $order_id );
					$result = $wa->get_phone_number( $order_id );

					if ( is_array( $result ) && count( $result)  > 1 ) {
						if ( 0 !== $result[0] ) {
							wp_send_json( array( 'code' => 1, 'message' => $result[1] ) );
						} else {
							$phone_msg = sprintf( __( 'Check that the customer\'s phone number %s is formatted correctly.', 'customer-reviews-woocommerce' ), '<code>' . $result[1] . '</code>' ) . '<br>';
							$phone_msg .= '<span class="cr-send-wa-link-msg-examples">' . __( 'Examples', 'customer-reviews-woocommerce' ) . '</span>' . '<br>';
							$phone_msg .= sprintf( __( 'Correct: %s', 'customer-reviews-woocommerce' ), '<code>1XXXXXXXXXX</code>' ) . '<br>';
							$phone_msg .= sprintf( __( 'Incorrect: %s', 'customer-reviews-woocommerce' ), '<code>+001-(XXX)XXXXXXX</code>' );
							wp_send_json(
								array(
									'code' => 100,
									'phone' => $phone_msg
								)
							);
						}
					} else {
						wp_send_json( array( 'code' => 2, 'message' => 'Error: could not get a phone number' ) );
					}
				} else {
					// sending via an app
					// Check if there are any reviews for this order collected via CusRev previously
					$existing_cr_reviews_count = get_comments(
						array(
							'meta_key' => 'ivole_order',
							'meta_value' => $order_id,
							'count' => true
						)
					);
					if( 0 < $existing_cr_reviews_count ) {
						wp_send_json(
							array(
								'code' => 3,
								'message' => __( 'Error: a review reminder could not be sent because reviews(s) have already been collected with a CusRev mailer for this order.', 'customer-reviews-woocommerce' )
							)
						);
					}

					$wa = new CR_Wtsap( $order_id );
					$result = $wa->get_review_form( $order_id, false );

					if ( is_array( $result ) && count( $result)  > 1 ) {
						if ( 0 !== $result[0] ) {
							wp_send_json( array( 'code' => 1, 'message' => $result[1] ) );
						} else {
							$phone_msg = sprintf( __( 'Check that the customer\'s phone number %s is formatted correctly.', 'customer-reviews-woocommerce' ), '<code>' . $result[2] . '</code>' ) . '<br>';
							$phone_msg .= '<span class="cr-send-wa-link-msg-examples">' . __( 'Examples', 'customer-reviews-woocommerce' ) . '</span>' . '<br>';
							$phone_msg .= sprintf( __( 'Correct: %s', 'customer-reviews-woocommerce' ), '<code>1XXXXXXXXXX</code>' ) . '<br>';
							$phone_msg .= sprintf( __( 'Incorrect: %s', 'customer-reviews-woocommerce' ), '<code>+001-(XXX)XXXXXXX</code>' );
							wp_send_json(
								array(
									'code' => 0,
									'link' => $result[1],
									'phone' => $phone_msg
								)
							);
						}
					} else {
						wp_send_json( array( 'code' => 2, 'message' => 'Error: could not create a review form' ) );
					}
				}
			}
		}

		public function manual_wa_review_reminder_api() {
			if ( isset( $_POST['order_id'] ) ) {
				$order_id = intval( $_POST['order_id'] );

				if ( ! wp_verify_nonce( $_POST['nonce'], 'cr-man-rem' ) ) {
					wp_send_json(
						array(
							'code' => 101,
							'message' => __( 'A security token expired, please refresh the page and try again.', 'customer-reviews-woocommerce' )
						)
					);
				}

				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_send_json(
						array(
							'code' => 102,
							'message' => __( 'Your user account does not have permissions for sending review reminders.', 'customer-reviews-woocommerce' )
						)
					);
				}

				$order = wc_get_order( $order_id );

				if ( ! $order  ) {
					wp_send_json(
						array(
							'code' => 98,
							'message' => __( 'Error: invalid order ID.', 'customer-reviews-woocommerce' )
						)
					);
				}

				// Check if there are any reviews for this order collected via local forms previously
				$existing_cr_reviews_count = get_comments(
					array(
						'meta_key' => 'ivole_order_locl',
						'meta_value' => $order_id,
						'count' => true
					)
				);
				if( 0 < $existing_cr_reviews_count ) {
					wp_send_json(
						array(
							'code' => 2,
							'message' => __( 'Error: a review reminder could not be sent because reviews(s) have already been collected via a local review form for this order.', 'customer-reviews-woocommerce' )
						)
					);
				}

				$schedule = $this->get_schedule( $order );
				$wa = new CR_Wtsap( $order_id );
				$result = $wa->send_message( $order_id, $schedule );

				if ( is_array( $result ) && count( $result)  > 1 ) {
					if ( 0 !== $result[0] ) {
						wp_send_json(
							array(
								'code' => 1,
								'message' => 'Error: ' . $result[1]
							)
						);
					} else {
						// unschedule automatic review reminder if manual sending was successful (for reminders sent via WP Cron)
						if( !$schedule ) {
							$timestamp = wp_next_scheduled( 'ivole_send_reminder', array( $order_id ) );
							if( $timestamp ) {
								wp_unschedule_event( $timestamp, 'ivole_send_reminder', array( $order_id ) );
							}
							$order->add_order_note( __( 'CR: a review reminder was triggered manually via WhatsApp.', 'customer-reviews-woocommerce' ) );
						}
						// get an updated count of reminders for the review reminder column
						$order->read_meta_data( true );
						$rmndr_msg = __( 'No reminders sent', 'customer-reviews-woocommerce' );
						$rmndr_count = $order->get_meta( '_ivole_review_reminder', true );
						if ( $rmndr_count ) {
							$rmndr_count = intval( $rmndr_count );
							if ( 0 < $rmndr_count ) {
								/* translators: %d will be automatically replaced with the count of review reminders */
								$rmndr_msg = sprintf( _n( '%d reminder sent', '%d reminders sent', $rmndr_count, 'customer-reviews-woocommerce' ), $rmndr_count );
							}
						}
						//
						wp_send_json(
							array(
								'code' => 0,
								'message' => __( 'A review reminder has been successfully sent via WhatsApp.', 'customer-reviews-woocommerce' ),
								'order_id' => $order_id,
								'reminders' => $rmndr_msg
							)
						);
					}
				} else {
					wp_send_json( array( 'code' => 2, 'message' => 'Error: could not send a WhatsApp message' ) );
				}
			}
		}

		public function manual_review_reminder_conf() {
			if ( isset( $_POST['order_id'] ) ) {
				$order_id = intval( $_POST['order_id'] );

				if ( ! wp_verify_nonce( $_POST['nonce'], 'cr-man-rem' ) ) {
					wp_send_json(
						array(
							'code' => 101,
							'message' => __( 'A security token expired, please refresh the page and try again.', 'customer-reviews-woocommerce' ),
							'order_id' => $order_id
						)
					);
				}

				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_send_json(
						array(
							'code' => 102,
							'message' => __( 'Your user account does not have permissions for sending review reminders.', 'customer-reviews-woocommerce' ),
							'order_id' => $order_id
						)
					);
				}

				$order = wc_get_order( $order_id );

				if ( ! $order  ) {
					wp_send_json(
						array(
							'code' => 98,
							'message' => __( 'Error: invalid order ID.', 'customer-reviews-woocommerce' )
						)
					);
				}

				// update the count of review reminders sent for an order based on the feedback of a user
				// after sending a reminder via WhatsApp manually
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

				// add an order note
				$order->add_order_note(
					__( 'CR: a review reminder was sent via WhatsApp manually.', 'customer-reviews-woocommerce' )
				);

				// return information about the count of reminders to UI
				$msg = '';
				if( 0 < $new_count ) {
					$msg = $new_count . __( ' reminder(s) sent', 'customer-reviews-woocommerce' );
				} else {
					$msg = __( 'No reminders sent yet', 'customer-reviews-woocommerce' );
				}
				wp_send_json(
					array(
						'code' => 0,
						'order_id' => $order_id,
						'message' => $msg
					)
				);
			}
		}

		public function is_phone_exists( $order ) {
			$shipping_phone = '';
			$billing_phone = $order->get_billing_phone();
			if ( method_exists( $order, 'get_shipping_phone' ) ) {
				$shipping_phone = $order->get_shipping_phone();
			}
			$billing_phone = preg_replace( "/[^0-9]/", '', $billing_phone );
			$shipping_phone = preg_replace( "/[^0-9]/", '', $shipping_phone );
			if ( $billing_phone || $shipping_phone ) {
				return true;
			} else {
				return false;
			}
		}

		public function get_schedule( $order ) {
			$schedule = false;
			if (
				$order->get_meta( '_ivole_cr_cron', true ) ||
				( '' === $order->get_meta( '_ivole_review_reminder', true ) && 'cr' === get_option( 'ivole_scheduler_type', 'wp' ) )
			) {
				//reminder should be sent via CR Cron
				$schedule = true;
			}
			return $schedule;
		}

	}

endif;
