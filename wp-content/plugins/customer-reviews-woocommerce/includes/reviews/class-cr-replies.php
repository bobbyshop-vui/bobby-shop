<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( dirname( dirname( dirname( __FILE__ ) ) ) . '/firebase/src/JWT.php' );
use \ivole\Firebase\JWT\JWT;

if ( ! class_exists( 'CR_Replies' ) ) :

	class CR_Replies {
		private $api_url = 'https://api.cusrev.com/v1/production/reviews/review-reply/seller';

		public function __construct( $comment_id ) {
			$comment = get_comment( $comment_id );
			if ( $comment ) {
				// get parent comment (orignal review) and find order number related to this review
				//it is possible that we have a reply to reply
				//in this case, we will have to loop through previous replies to find the original review
				$max_iterations = 200;
				$parent_id = $comment->comment_parent;
				$i = 0;
				while( $parent_id ) {
					$i++;
					//just a safety measure to avoid infinite loop
					if( $i > $max_iterations ) {
						break;
					}
					$parent = get_comment( $parent_id );
					if( $parent ) {
						if( $parent->comment_parent ) {
							$parent_id = $parent->comment_parent;
							continue;
						} else {
							$ivole_order = get_comment_meta( $parent->comment_ID, 'ivole_order', true );
							$rating = get_comment_meta( $parent->comment_ID, 'rating', true );
							$order = wc_get_order( $ivole_order );
							if( $ivole_order && $rating && $order ) {
								$current_user = wp_get_current_user();

								if( $current_user->ID ) {
									$key = strtolower( get_option( 'ivole_license_key' ) );
									$payload = array(
										'iss' => Ivole_Email::get_blogurl(),
										'aud' => 'www.cusrev.com',
										'iat' => time()
									);
									$jwt = JWT::encode( $payload, $key, 'HS256' );
									//support for shop pages (product ID = -1)
									$product_id = $parent->comment_post_ID;
									$shop_page_id = wc_get_page_id( 'shop' );
									if( intval( $shop_page_id ) === intval( $product_id ) ) {
										$product_id = '-1';
									}
									$ivole_language = get_option( 'ivole_language' );

									// WPML and Polylang integration
									$shop_pages = array();
									if( function_exists( 'pll_get_post_translations' ) ) {
										// Polylang integration
										$ivole_language = pll_get_post_language( $ivole_order );
										// if $product_id is not -1 already
										if( 0 < intval( $product_id ) ) {
											// get IDs of the shop page in all languages
											$translated_shop_page_ids = pll_get_post_translations( $shop_page_id );
											if( $translated_shop_page_ids && is_array( $translated_shop_page_ids ) && count( $translated_shop_page_ids ) > 0 ) {
												$shop_pages = array_map( 'intval', $translated_shop_page_ids );
											}
											// if $product_id is in the array, it is a shop page, otherwise translate it like a product id
											if( in_array( intval( $product_id ), $shop_pages ) ) {
												$product_id = '-1';
											} else {
												$polylang_default_language = pll_default_language();
												$product_id = pll_get_post( $product_id, $polylang_default_language );
											}
										}
									} elseif ( has_filter( 'wpml_object_id' ) ) {
										// WPML integration
										$ivole_language = $order->get_meta( 'wpml_language', true );
										// if $product_id is not -1 already
										if( 0 < intval( $product_id ) ) {
											// get IDs of the shop page in all languages
											$trid = apply_filters( 'wpml_element_trid', NULL, $shop_page_id, 'post_page' );
											if( $trid ) {
												$translations = apply_filters( 'wpml_get_element_translations', NULL, $trid, 'post_page' );
												if( $translations && is_array( $translations ) && 0 < count( $translations ) ) {
													$translated_shop_page_ids = array();
													foreach ($translations as $translation) {
														if( isset( $translation->element_id ) ) {
															$translated_shop_page_ids[] = intval( $translation->element_id );
														}
													}
													if( 0 < count( $translated_shop_page_ids ) ) {
														$shop_pages = $translated_shop_page_ids;
													}
												}
											}
											// if $product_id is in the array, it is a shop page, otherwise translate it like a product id
											if( in_array( intval( $product_id ), $shop_pages ) ) {
												$product_id = '-1';
											} else {
												$default_language = apply_filters( 'wpml_default_language', NULL );
												$product_id = apply_filters( 'translate_object_id', $product_id, 'product', true, $default_language );
											}
										}
									}

									if ( empty( $ivole_language ) ) {
										$ivole_language = 'EN';
									}
									$data = array(
										'shopDomain' => Ivole_Email::get_blogurl(),
										'orderId' => $ivole_order,
										'productId' => $product_id,
										'replyId' => strval( $comment_id ),
										'email' => $current_user->user_email,
										'language' => $ivole_language,
										'text' => $comment->comment_content,
										'token' => $jwt
									);
									$data_string = json_encode( $data );
									//error_log( print_r( $data_string, true ) );
									$ch = curl_init();
									curl_setopt( $ch, CURLOPT_URL, $this->api_url );
									curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
									curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
									curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
									curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
										'Content-Type: application/json',
										'Content-Length: ' . strlen( $data_string ) )
									);
									$result = curl_exec( $ch );
									//error_log( print_r( $result, true ) );
									$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
									if( $httpcode ) {
										$meta = array();
										if( 201 === $httpcode ) {
											//success
											$meta[] = 201;
											$meta[] = __( 'A copy of the reply was published on CusRev portal', 'customer-reviews-woocommerce' );
										} else {
											//some error
											$resultd = json_decode( $result );
											if( $resultd && isset( $resultd->details ) ) {
												$meta[] = 995;
												$meta[] = $resultd->details;
											} else {
												$meta[] = 999;
												$meta[] = __( 'Unknown error', 'customer-reviews-woocommerce' );
											}
										}
										update_comment_meta( $comment_id, 'ivole_reply', $meta );
									} else {
										//error_log( print_r( $result, true ) );
										if( false === $result ) {
											$meta = array();
											$meta[] = 997;
											$meta[] = curl_error( $ch );
										} else {
											$meta = array();
											$meta[] = 998;
											$meta[] = __( 'Unknown error', 'customer-reviews-woocommerce' );
										}
										update_comment_meta( $comment_id, 'ivole_reply', $meta );
									}
								} else {
									$meta = array();
									$meta[] = 996;
									$meta[] = __( 'ID of the current user is not set', 'customer-reviews-woocommerce' );
									update_comment_meta( $comment_id, 'ivole_reply', $meta );
								}
							}
							break;
						}
					} else {
						break;
					}
				}
			};
		}

		public static function isReplyForCRReview( $comment ) {
			if( $comment && $comment->comment_parent ) {
				$parent_id = $comment->comment_parent;
				$max_iterations = 200;
				$i = 0;
				while( $parent_id ) {
					$i++;
					//just a safety measure to avoid infinite loop
					if( $i > $max_iterations ) {
						break;
					}
					$parent = get_comment( $parent_id );
					if( $parent ) {
						if( $parent->comment_parent ) {
							$parent_id = $parent->comment_parent;
							continue;
						} else {
							$ivole_order = get_comment_meta( $parent->comment_ID, 'ivole_order', true );
							$rating = get_comment_meta( $parent->comment_ID, 'rating', true );
							if( $ivole_order && $rating ) {
								$product_id = $parent->comment_post_ID;
								//WPML integration
								if ( has_filter( 'wpml_object_id' ) ) {
									$default_language = apply_filters( 'wpml_default_language', NULL );
									if( 'product' === get_post_type( $product_id ) ) {
										// it is a product
										$product_id = apply_filters( 'translate_object_id', $product_id, 'product', true, $default_language );
									} else {
										// it is shop page (shop review)
										$product_id = apply_filters( 'translate_object_id', $product_id, 'page', true, $default_language );
									}
								}
								return array( $ivole_order, $product_id );
							} else {
								return false;
							}
						}
					} else {
						return false;
					}
				}
			}
			return false;
		}

		public static function isReplyForReview( $comment ) {
			if( $comment && $comment->comment_parent ) {
				$parent_id = $comment->comment_parent;
				$max_iterations = 200;
				$i = 0;
				while( $parent_id ) {
					$i++;
					//just a safety measure to avoid infinite loop
					if( $i > $max_iterations ) {
						break;
					}
					$parent = get_comment( $parent_id );
					if( $parent ) {
						if( $parent->comment_parent ) {
							$parent_id = $parent->comment_parent;
							continue;
						} else {
							$rating = get_comment_meta( $parent->comment_ID, 'rating', true );
							if( $rating ) {
								$product_id = $parent->comment_post_ID;
								//WPML integration
								if ( has_filter( 'wpml_object_id' ) ) {
									$default_language = apply_filters( 'wpml_default_language', NULL );
									if( 'product' === get_post_type( $product_id ) ) {
										// it is a product
										$product_id = apply_filters( 'translate_object_id', $product_id, 'product', true, $default_language );
									} else {
										// it is shop page (shop review)
										$product_id = apply_filters( 'translate_object_id', $product_id, 'page', true, $default_language );
									}
								}
								return true;
							} else {
								return false;
							}
						}
					} else {
						return false;
					}
				}
			}
			return false;
		}

	}

endif;
