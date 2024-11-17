<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Endpoint' ) ) :

	class CR_Endpoint {
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'init_endpoint' ) );
		}

		public function init_endpoint( ) {
			$this->register_routes();
		}

		public function register_routes() {
			$version = '1';
			$namespace = 'ivole/v' . $version;
			register_rest_route( $namespace, '/review', array(
				array(
					'methods'         => WP_REST_Server::CREATABLE,
					'callback'        => array( $this, 'create_review_callback' ),
					'permission_callback' => array( $this, 'create_review_permissions_check' ),
					'args'            => array(),
				),
			) );
		}

		public function create_review_callback( WP_REST_Request $request ) {
			$body = $request->get_body();
			$body2 = json_decode( $body );
			if( json_last_error() === JSON_ERROR_NONE ) {
				return self::create_review( $body2, false );
			} else {
				return new WP_REST_Response( 'Generic error', 500 );
			}
		}

		public static function create_review( $body2, $local ) {
			global $wpdb;
			$ivole_order = 'ivole_order';
			if ( $local ) {
				// reviews from a local review form
				$ivole_order = 'ivole_order_locl';
			} else {
				// reviews from CusRev form
				if ( isset( $body2->liveMode ) ) {
					if ( 0 === intval( $body2->liveMode ) ) {
						$ivole_order = 'ivole_order_priv';
					}
				}
			}
			if( isset( $body2->order ) ) {
				if( isset( $body2->order->id ) && isset( $body2->order->items ) ) {

					$order_id = intval( $body2->order->id );
					$order = new WC_Order( $order_id );
					$customer_name = '';
					$customer_first_name = '';
					$customer_last_name = '';
					$customer_email = '';
					$local_reviews_notif = array();
					$reviews = array();

					//check if registered customers option is used
					$registered_customers = false;
					if( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) {
						$registered_customers = true;
					}

					if( method_exists( $order, 'get_billing_email' ) ) {
						// Woocommerce version 3.0 or later
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
						$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
						$order_date = date_i18n( 'd.m.Y', strtotime( $order->get_date_created() ) );
						$order_currency = $order->get_currency();
					} else {
						return new WP_REST_Response( 'Error: old WooCommerce version, please update WooCommerce to the latest version', 500 );
					}

					//if customer specified preference for display name, take into account their preference
					if( !empty( $body2->order->display_name ) ) {
						$customer_name = strval( $body2->order->display_name );
					}

					//settings for moderation of reviews
					$comment_approved = 1;
					$moderation_enabled = get_option( 'ivole_enable_moderation', 'no' );
					if( $moderation_enabled === 'yes' ) {
						$comment_approved = 0;
					}

					//Find WordPress user ID of the customer (if a customer has an account)
					$customer_user = get_user_by( 'email', $customer_email );
					$customer_user_id = 0;
					if( $customer_user ) {
						$customer_user_id = $customer_user->ID;
					}

					//Country / region of the customer
					$country = null;
					if( isset( $body2->geo_location ) && isset( $body2->geo_location->code )
						&& isset( $body2->geo_location->desc ) ) {
						$country_code = sanitize_text_field( $body2->geo_location->code );
						if( strlen( $country_code ) > 0 ) {
							$country = array( 'code' => $country_code,
								'desc' => sanitize_text_field( $body2->geo_location->desc ) );
						}
					}

					$previous_comments_exist = false;
					$comment_date = current_time( 'mysql' );
					$media_count_total = 0;

					//shop review
					if( isset( $body2->order->shop_rating ) && isset( $body2->order->shop_comment ) ) {
						$shop_page_id = wc_get_page_id( 'shop' );
						if( $shop_page_id > 0 ) {
							$shop_comment_text = strval( $body2->order->shop_comment );

							//check if API provided replies to custom questions
							$shop_custom_questions = new CR_Custom_Questions();
							$shop_custom_questions->parse_shop_questions( $body2->order );

							//WPML integration
							//wc_get_page_id returns shop page ID in the default WPML site language
							//If a review was submitted in a language different from the default one, it is necessary to get shop page ID for the non-default language
							$wpml_current_lang = '';
							if ( has_filter( 'wpml_object_id' ) ) {
								$wpml_order_language = $order->get_meta( 'wpml_language', true );
								$shop_page_id = apply_filters( 'wpml_object_id', $shop_page_id, 'page', true, $wpml_order_language );
								//switch the current WPML site language to the language of the order because
								//call to get_comments (below) returns only comments for shop page in the current WPML language
								$wpml_current_lang = apply_filters( 'wpml_current_language', null );
								do_action( 'wpml_switch_language', $wpml_order_language );
							}
							if ( has_filter( 'wpml_object_id' ) ) {
								if( class_exists( 'WCML_Comments' ) ) {
									global $woocommerce_wpml;
									if( $woocommerce_wpml ) {
										remove_action( 'added_comment_meta', array( $woocommerce_wpml->comments, 'maybe_duplicate_comment_rating' ), 10, 4 );
									}
								}
							}
							// Polylang integration
							if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_get_post_language' ) ) {
								$polylang_order_language = pll_get_post_language( $order_id );
								if( $polylang_order_language ) {
									$shop_page_id = pll_get_post( $shop_page_id, $polylang_order_language  );
								}
							}

							// check if a shop review has already been submitted for this order by this customer
							$args = array(
								'post_id' => $shop_page_id,
								'author_email' => $customer_email,
								'meta_key' => $ivole_order,
								'meta_value' => $order_id,
								'orderby' => 'comment_ID',
								'order' => 'DESC'
							);
							$existing_comments = get_comments( $args );
							$num_existing_comments = count( $existing_comments );

							if( $num_existing_comments > 0 ) {
								$previous_comments_exist = true;
								$review_id = $existing_comments[0]->comment_ID;
								$commentdata = array(
									'comment_ID' => $review_id,
									'comment_content' => $shop_comment_text,
									'comment_approved' => $comment_approved,
									'comment_author' => $customer_name,
									'comment_date' => $comment_date,
									'comment_date_gmt' => get_gmt_from_date( $comment_date ) );
								wp_update_comment( $commentdata );
								update_comment_meta( $review_id, 'rating', intval( $body2->order->shop_rating ) );
								if( $country ) {
									update_comment_meta( $review_id, 'ivole_country', $country );
								} else {
									delete_comment_meta( $review_id, 'ivole_country' );
								}
								if( $shop_custom_questions->has_questions() ) {
									$shop_custom_questions->save_questions( $review_id );
								} else {
									$shop_custom_questions->delete_questions( $review_id );
								}
								wp_update_comment_count_now( $shop_page_id );
							} else {
								$commentdata = array(
									'comment_author' => $customer_name,
									'comment_author_email' => $customer_email,
									'comment_author_url' => '',
									'user_id' => $customer_user_id,
									'comment_content' => $shop_comment_text,
									'comment_post_ID' =>  $shop_page_id,
									'comment_type' => 'review',
									'comment_approved' => $comment_approved,
									'comment_meta' => array( 'rating' => intval( $body2->order->shop_rating ) ) );
								$review_id = wp_insert_comment( $commentdata );
								if( !$review_id ) {
									//adding a new review may fail, if review fields include characters that are not supported by DB
									//for example, collation utf8_general_ci does not support emoticons
									//in these cases, we will remove unsupported characters and try to add the review again
									$tfields = array( 'comment_author', 'comment_author_email', 'comment_content' );
									foreach ( $tfields as $field ) {
										if ( isset( $commentdata[ $field ] ) ) {
												$commentdata[ $field ] = $wpdb->strip_invalid_text_for_column( $wpdb->comments, $field, $commentdata[ $field ] );
										}
									}
									$review_id = wp_insert_comment( $commentdata );
								}
								if( $review_id ) {
									$reviews[] = $review_id;
									add_comment_meta( $review_id, $ivole_order, $order_id, true );
									if( $country ) {
										update_comment_meta( $review_id, 'ivole_country', $country );
									}
									if( $shop_custom_questions->has_questions() ) {
										$shop_custom_questions->save_questions( $review_id );
									}
									wp_update_comment_count_now( $shop_page_id );
									// set current user to emulate submission of review by a real user - it is necessary for compatibility with other plugins
									$current_user = wp_get_current_user();
									if( $customer_user_id ) {
										wp_set_current_user( $customer_user_id );
									}
									// deactivate AutomateWoo hook because otherwise it might trigger a PHP error
									if ( class_exists( '\AutomateWoo\Session_Tracker' ) ) {
										remove_action( 'comment_post', array( '\AutomateWoo\Session_Tracker', 'capture_from_comment' ), 10 );
									}
									//
									do_action( 'comment_post', $review_id, $commentdata['comment_approved'], $commentdata );
									// set the previous current user back
									if( $current_user ) {
										wp_set_current_user( $current_user->ID );
									} else {
										wp_set_current_user( 0 );
									}
									// notifications for local reviews
									if ( $local ) {
										$local_reviews_notif[] = array(
											'item' => $commentdata['comment_post_ID'],
											'rating' => intval( $body2->order->shop_rating ),
											'comment' => $commentdata['comment_content']
										);
									}
								} else {
									return new WP_REST_Response( 'Review creation error 3', 500 );
								}
							}
							//WPML integration
							if ( has_filter( 'wpml_object_id' ) ) {
								do_action( 'wpml_switch_language', $wpml_current_lang );
							}
							//WPML integration
							if ( has_filter( 'wpml_object_id' ) ) {
								if( class_exists( 'WCML_Comments' ) ) {
									global $woocommerce_wpml;
									if( $woocommerce_wpml ) {
										add_action( 'added_comment_meta', array( $woocommerce_wpml->comments, 'maybe_duplicate_comment_rating' ), 10, 4 );
									}
								}
							}
						}
					}

					//product reviews
					$result = true;
					if( is_array( $body2->order->items ) ) {
						$num_items = count( $body2->order->items );
						for( $i = 0; $i < $num_items; $i++ ) {
							if( isset( $body2->order->items[$i]->rating ) && isset( $body2->order->items[$i]->id ) ) {
								// check if replies to custom questions were provided
								$product_custom_questions = new CR_Custom_Questions();
								$product_custom_questions->parse_product_questions( $body2->order->items[$i] );

								// check if review text was provided, if not then we will be adding an empty comment
								$comment_text = '';
								if( isset( $body2->order->items[$i]->comment ) ) {
									$comment_text = strval( $body2->order->items[$i]->comment );
								}

								// check if media files were provided
								$media_meta = array();
								if( $local ) {
									if( isset( $body2->order->items[$i]->media ) && is_array( $body2->order->items[$i]->media ) ) {
										$num_media = count( $body2->order->items[$i]->media );
										for( $m = 0; $m < $num_media; $m++ ) {
											// image
											if( wp_attachment_is( 'image', $body2->order->items[$i]->media[$m] ) ) {
												$media_meta[] = array(
													'meta' => CR_Reviews::REVIEWS_META_LCL_IMG,
													'value' => $body2->order->items[$i]->media[$m]
												);
											}
											// video
											else if( wp_attachment_is( 'video', $body2->order->items[$i]->media[$m] ) ) {
												$media_meta[] = array(
													'meta' => CR_Reviews::REVIEWS_META_LCL_VID,
													'value' => $body2->order->items[$i]->media[$m]
												);
											}
										}
									}
								} else {
									if( isset( $body2->order->items[$i]->media ) && is_array( $body2->order->items[$i]->media ) ) {
										$num_media = count( $body2->order->items[$i]->media );
										for( $m = 0; $m < $num_media; $m++ ) {
											// image
											if( 'image' === $body2->order->items[$i]->media[$m]->type && isset( $body2->order->items[$i]->media[$m]->href ) ) {
												$media_meta[] = array(
													'meta' => 'ivole_review_image',
													'value' => array( 'url' => $body2->order->items[$i]->media[$m]->href )
												);
											}
											// video
											else if( 'video' === $body2->order->items[$i]->media[$m]->type && isset( $body2->order->items[$i]->media[$m]->href) ) {
												$media_meta[] = array(
													'meta' => 'ivole_review_video',
													'value' => array( 'url' => $body2->order->items[$i]->media[$m]->href )
												);
											}
										}
									}
								}
								$media_meta_count = count( $media_meta );
								$media_count_total = $media_count_total + $media_meta_count;

								$order_item_product_id = intval( $body2->order->items[$i]->id );

								//WPML integration
								//The order contains product ID of the product in the default WPML site language
								//If a review was submitted in a language different from the default one, it is necessary to get product ID for the non-default language
								$wpml_current_lang = '';
								if ( has_filter( 'wpml_object_id' ) ) {
									$wpml_order_language = $order->get_meta( 'wpml_language', true );
									$order_item_product_id = apply_filters( 'wpml_object_id', $order_item_product_id, 'product', true, $wpml_order_language );
									//switch the current WPML site language to the language of the order because
									//call to get_comments (below) returns only comments for products in the current WPML language
									$wpml_current_lang = apply_filters( 'wpml_current_language', null );
									do_action( 'wpml_switch_language', $wpml_order_language );
								}
								// Polylang integration
								if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_get_post_language' ) ) {
									$polylang_order_language = pll_get_post_language( $order_id );
									if( $polylang_order_language ) {
										$order_item_product_id = pll_get_post( $order_item_product_id, $polylang_order_language  );
									}
								}

								// check if a review has already been submitted for this product and for this order by this customer
								$args = array(
									'post_id' => $order_item_product_id,
									'author_email' => $customer_email,
									'meta_key' => $ivole_order,
									'meta_value' => $order_id,
									'orderby' => 'comment_ID',
									'order' => 'DESC'
								);
								$existing_comments = get_comments( $args );
								$num_existing_comments = count( $existing_comments );

								if( $num_existing_comments > 0 ) {
									// there are previous comment(s) submitted via external form
									$previous_comments_exist = true;
									$review_id = $existing_comments[0]->comment_ID;
									$commentdata = array(
										'comment_ID' => $review_id,
										'comment_content' => $comment_text,
										'comment_approved' => $comment_approved,
										'comment_author' => $customer_name,
										'comment_date' => $comment_date,
										'comment_date_gmt' => get_gmt_from_date( $comment_date ) );
									wp_update_comment( $commentdata );
									update_comment_meta( $review_id, 'rating', intval( $body2->order->items[$i]->rating ) );
									update_comment_meta( $review_id, 'ivole_country', $country );
									//remove previously added media files
									delete_comment_meta( $review_id, CR_Reviews::REVIEWS_META_IMG );
									delete_comment_meta( $review_id, CR_Reviews::REVIEWS_META_LCL_IMG );
									delete_comment_meta( $review_id, CR_Reviews::REVIEWS_META_VID );
									delete_comment_meta( $review_id, CR_Reviews::REVIEWS_META_LCL_VID );
									// add media files to meta if they exist
									if( $media_meta_count > 0 ) {
										for( $m = 0; $m < $media_meta_count; $m++ ) {
											add_comment_meta( $review_id, $media_meta[$m]['meta'], $media_meta[$m]['value'], false );
											if( $local ) {
												// in case of local review forms, we need to attach media files to products
												wp_update_post( array(
													'ID' => $media_meta[$m]['value'],
													'post_parent' => $order_item_product_id
												) );
											}
										}
									}
									// update the meta field with the count of media files
									$mdia_count = CR_Ajax_Reviews::get_media_count( $review_id );
									update_comment_meta( $review_id, 'ivole_media_count', $mdia_count );
									//
									if( $product_custom_questions->has_questions() ) {
										$product_custom_questions->save_questions( $review_id );
									} else {
										$product_custom_questions->delete_questions( $review_id );
									}
									wp_update_comment_count_now( $order_item_product_id );
								} else {
									// there are no previous comment(s) submitted via external form for this order and product
									$commentdata = array(
										'comment_author' => $customer_name,
										'comment_author_email' => $customer_email,
										'comment_author_url' => '',
										'user_id' => $customer_user_id,
										'comment_content' => $comment_text,
										'comment_post_ID' =>  $order_item_product_id,
										'comment_type' => 'review',
										'comment_approved' => $comment_approved,
										'comment_meta' => array( 'rating' => intval( $body2->order->items[$i]->rating ) ) );
									$review_id = wp_insert_comment( $commentdata );
									if( !$review_id ) {
										//adding a new review may fail, if review fields include characters that are not supported by DB
										//for example, collation utf8_general_ci does not support emoticons
										//in these cases, we will remove unsupported characters and try to add the review again
										$tfields = array( 'comment_author', 'comment_author_email', 'comment_content' );
										foreach ( $tfields as $field ) {
											if ( isset( $commentdata[ $field ] ) ) {
													$commentdata[ $field ] = $wpdb->strip_invalid_text_for_column( $wpdb->comments, $field, $commentdata[ $field ] );
											}
										}
										$review_id = wp_insert_comment( $commentdata );
									}
									if( $review_id ) {
										$reviews[] = $review_id;
										add_comment_meta( $review_id, $ivole_order, $order_id, true );
										if( $country ) {
											update_comment_meta( $review_id, 'ivole_country', $country );
										}
										// add media files to meta if they exist
										if( $media_meta_count > 0 ) {
											for( $m = 0; $m < $media_meta_count; $m++ ) {
												add_comment_meta( $review_id, $media_meta[$m]['meta'], $media_meta[$m]['value'], false );
												if( $local ) {
													// in case of local review forms, we need to attach media files to products
													wp_update_post( array(
														'ID' => $media_meta[$m]['value'],
														'post_parent' => $order_item_product_id
													) );
												}
											}
										}
										// update the meta field with the count of media files
										$mdia_count = CR_Ajax_Reviews::get_media_count( $review_id );
										update_comment_meta( $review_id, 'ivole_media_count', $mdia_count );
										//
										if( $product_custom_questions->has_questions() ) {
											$product_custom_questions->save_questions( $review_id );
										}
										wp_update_comment_count_now( $order_item_product_id );
										// set current user to emulate submission of review by a real user - it is necessary for compatibility with other plugins
										$current_user = wp_get_current_user();
										if( $customer_user_id ) {
											wp_set_current_user( $customer_user_id );
										}
										// deactivate AutomateWoo hook because otherwise it might trigger a PHP error
										if ( class_exists( '\AutomateWoo\Session_Tracker' ) ) {
											remove_action( 'comment_post', array( '\AutomateWoo\Session_Tracker', 'capture_from_comment' ), 10 );
										}
										//
										do_action( 'comment_post', $review_id, $commentdata['comment_approved'], $commentdata );
										// set the previous current user back
										if( $current_user ) {
											wp_set_current_user( $current_user->ID );
										} else {
											wp_set_current_user( 0 );
										}
										// notifications for local reviews
										if ( $local ) {
											$local_reviews_notif[] = array(
												'item' => $commentdata['comment_post_ID'],
												'rating' => intval( $body2->order->items[$i]->rating ),
												'comment' => $commentdata['comment_content']
											);
										}
									} else {
										$result = false;
									}
								}
								// WPML integration
								if ( has_filter( 'wpml_object_id' ) ) {
									do_action( 'wpml_switch_language', $wpml_current_lang );
								}
							}
						}
					}
					// if there was a problem with any product review, return an error
					if ( ! $result ) {
						return new WP_REST_Response( 'Review creation error 1', 500 );
					}
					// check if there are any local reviews to include in a notification
					if ( 0 < count( $local_reviews_notif ) ) {
						$rne = new CR_Review_Notification_Email( 'review_notification' );
						$rne->trigger_email( $customer_name, $local_reviews_notif );
					}
					// an action on posting reviews
					do_action( 'cr_endpoint_review_posted', $order_id );
					// if there are previous comments, it means that the customer has already received a coupon
					// and we don't need to send another one, so return early
					if( $previous_comments_exist ) {
						// send result to the endpoint
						return new WP_REST_Response( '', 200 );
					}
					// send a coupon to the customer
					$coupon = CR_Discount_Tiers::get_coupon( $media_count_total );
					if ( $coupon['is_enabled'] ) {
						//qTranslate integration
						$lang = $order->get_meta( '_user_language', true );
						$old_lang = '';
						if( $lang ) {
							global $q_config;
							$old_lang = $q_config['language'];
							$q_config['language'] = $lang;
						}

						$roles_are_ok = true;
						if( 'roles' === $coupon['cr_coupon_enable_for_role'] && $customer_user ) {
							$roles = $customer_user->roles;
							$enabled_roles = is_array( $coupon['cr_coupon_enabled_roles'] ) ? $coupon['cr_coupon_enabled_roles'] : array();
							$intersection = array_intersect( $enabled_roles, $roles );
							if( count( $intersection ) < 1 ) {
								//the customer does not have roles for which discount coupons are enabled
								$roles_are_ok = false;
							}
						}

						if( $roles_are_ok ) {
							$ec = new CR_Email_Coupon( $order_id );
							$coupon_type = $coupon['cr_coupon_type'];
							if( $coupon_type === 'static' ) {
								$coupon_id = $coupon['cr_existing_coupon'];
							} else {
								$coupon_id = $ec->generate_coupon( $customer_email, $order_id, $coupon );
								// compatibility with W3 Total Cache plugin
								// clear DB cache to read properties of the coupon
								if( function_exists( 'w3tc_dbcache_flush' ) ) {
									w3tc_dbcache_flush();
								}
							}
							if( $coupon_id > 0 && get_post_type( $coupon_id ) === 'shop_coupon' && get_post_status( $coupon_id ) === 'publish' ) {
								$coupon_code = get_post_field( 'post_title', $coupon_id );
								$discount_type = get_post_meta( $coupon_id, 'discount_type', true );
								$discount_amount = get_post_meta( $coupon_id, 'coupon_amount', true );
								$discount_string = "";
								if( $discount_type == "percent" && $discount_amount > 0 ) {
									$discount_string = $discount_amount . "%";
								} elseif( $discount_amount > 0 ) {
									$discount_string = trim( strip_tags( CR_Email_Func::cr_price( $discount_amount, array( 'currency' => get_option( 'woocommerce_currency' ) ) ) ) );
								}

								if ( 'wa' === $coupon['channel'] ) {
									$wa = new CR_Wtsap( $order_id );
									$coupon_res = $wa->send_coupon(
										$customer_first_name,
										$customer_last_name,
										$customer_name,
										$coupon_code,
										$discount_string,
										$customer_email,
										$order_id,
										$order_date,
										$order_currency,
										$order,
										$discount_type,
										$discount_amount
									);
								} else {
									$coupon_res = $ec->trigger_coupon(
										$customer_first_name,
										$customer_last_name,
										$customer_name,
										$coupon_code,
										$discount_string,
										$customer_email,
										$order_id,
										$order_date,
										$order_currency,
										$order,
										$discount_type,
										$discount_amount
									);
								}

								if ( 0 === $coupon_res[0] ) {
									foreach( $reviews as $rvw_id ) {
										update_comment_meta( $rvw_id, 'cr_coupon_code', $coupon_code );
									}
								}
								$order->add_order_note( 'CR: ' . $coupon_res[1] );
							}
						}

						//qTranslate integration
						if( $lang ) {
							$q_config['language'] = $old_lang;
						}
					}

					// send result to the endpoint
					if( $result ) {
						return new WP_REST_Response( '', 200 );
					} else {
						return new WP_REST_Response( 'Review creation error 2', 500 );
					}
				}
			} else if( isset( $body2->test ) ) {
				return new WP_REST_Response( 'CR Test OK', 200 );
			}
			return new WP_REST_Response( 'Generic error', 500 );
		}

		public function create_review_permissions_check( WP_REST_Request $request ) {
			$body = $request->get_body();
			$body2 = json_decode( $body );
			if( json_last_error() === JSON_ERROR_NONE ) {
				if( isset( $body2->key ) && isset( $body2->order ) ) {
					if( isset( $body2->order->id ) ) {
						$order = wc_get_order( $body2->order->id );
						if ( $order ) {
							$saved_key = $order->get_meta( 'ivole_secret_key', true );
							if( $body2->key === $saved_key ) {
								return true;
							} else {
								return new WP_Error(
									'cr_authentication_failed',
									'No permission to post reviews',
									array( 'status' => 401 )
								);
							}
						} else {
							return new WP_Error(
								'cr_authentication_failed',
								'Order ID does not exist',
								array( 'status' => 401 )
							);
						}
					}
				} else if( isset( $body2->test ) ) {
					if( false != get_option( 'ivole_test_secret_key' ) && $body2->test === get_option( 'ivole_test_secret_key' ) ){
						 return true;
					}
				}
			}
			return false;
		}
	}

endif;
