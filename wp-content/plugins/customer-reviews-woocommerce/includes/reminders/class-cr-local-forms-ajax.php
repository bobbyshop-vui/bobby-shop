<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Local_Forms_Ajax' ) ) :

	class CR_Local_Forms_Ajax {
		private $form_id;
		private $items;
		private $customer_email;
		private $customer_name;
		private $form_header;
		private $form_body;

		public function __construct() {
			add_action( 'wp_ajax_cr_local_forms_submit', array( $this, 'submit_form' ) );
			add_action( 'wp_ajax_nopriv_cr_local_forms_submit', array( $this, 'submit_form' ) );
			add_action( 'wp_ajax_cr_local_forms_upload_media', array( $this, 'upload_media' ) );
			add_action( 'wp_ajax_nopriv_cr_local_forms_upload_media', array( $this, 'upload_media' ) );
			add_action( 'wp_ajax_cr_local_forms_delete_media', array( $this, 'delete_media' ) );
			add_action( 'wp_ajax_nopriv_cr_local_forms_delete_media', array( $this, 'delete_media' ) );
		}

		public function submit_form() {
			if( isset( $_POST['formId'] ) ) {
				if( CR_Local_Forms::TEST_FORM === $_POST['formId'] ) {
					// submission of a test form
					return;
				} else {
					global $wpdb;
					$table_name = $wpdb->prefix . CR_Local_Forms::FORMS_TABLE;
					$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_name` WHERE `formId` = %s", $_POST['formId'] ) );
					if( null !== $record ) {
						$db_items = json_decode( $record->items, true );

						foreach( $_POST['items'] as $review_item ) {
							foreach( $db_items as $key => $item ) {
								if( intval( $review_item['id'] ) === intval( $item['id'] ) ) {
									$db_items[$key]['rating'] = $review_item['rating'];
									$db_items[$key]['comment'] = $review_item['comment'];
									if( isset( $review_item['media'] ) ) {
										$db_items[$key]['media'] = array_values( $review_item['media'] );
									} else {
										$db_items[$key]['media'] = array();
									}
									break;
								}
							}
						}

						$req = new stdClass();
						$req->order = new stdClass();
						$req->order->id = $record->orderId;
						$req->order->display_name = $_POST['displayName'];
						$req->order->items = array();
						foreach( $db_items as $item ) {
							if( -1 === intval( $item['id'] ) ) {
								$req->order->shop_rating = $item['rating'];
								$req->order->shop_comment = $item['comment'];
							} else {
								$product = new stdClass();
								$product->id = $item['id'];
								$product->name = $item['name'];
								$product->price = $item['price'];
								$product->rating = $item['rating'];
								$product->comment = $item['comment'];
								$product->media = $item['media'];
								$req->order->items[] = $product;
							}
						}

						$db_items = json_encode( $db_items );
						$update_result = $wpdb->update( $table_name, array(
							'displayName' => $_POST['displayName'],
							'items' => $db_items
						), array( 'formId' => $_POST['formId'] ) );
						if( false !== $update_result ) {
							CR_Endpoint::create_review( $req, true );
						};
					}
				}
			}
		}

		public function upload_media() {
			$return = array(
				'code' => 100,
				'message' => ''
			);
			if( isset( $_POST['cr_form'] ) && isset( $_POST['cr_item'] ) ) {
				if( isset( $_FILES ) && is_array( $_FILES ) && 0 < count( $_FILES ) ) {
					// check the file size
					$attach_image_size = get_option( 'ivole_attach_image_size', 25 );
					$max_size = 1024 * 1024 * $attach_image_size;
					if ( $max_size < $_FILES['cr_file']['size'] ) {
						$return['code'] = 501;
						$return['message'] = sprintf( __( 'The file cannot be uploaded because its size exceeds the limit of %d MB', 'customer-reviews-woocommerce' ), $attach_image_size );
						wp_send_json( $return );
						return;
					}
					// check the file type
					$file_name_parts = explode( '.', $_FILES['cr_file']['name'] );
					$file_ext = $file_name_parts[ count( $file_name_parts ) - 1 ];
					if( ! CR_Reviews::is_valid_file_type( $file_ext ) ) {
						$return['code'] = 502;
						$return['message'] = __( 'Error: accepted file types are PNG, JPG, JPEG, GIF, MP4, MPEG, OGG, WEBM, MOV, AVI', 'customer-reviews-woocommerce' );
						wp_send_json( $return );
						return;
					}
					// upload the file
					$attachmentId = media_handle_upload( 'cr_file', 0 );
					if( !is_wp_error( $attachmentId ) ) {
						$upload_key = bin2hex( openssl_random_pseudo_bytes( 10 ) );
						if( false !== update_post_meta( $attachmentId, 'cr-upload-temp-key', $upload_key ) ) {
							// save the attachment id in the database
							if( false !== self::update_db_item( $_POST['cr_form'], $_POST['cr_item'], $attachmentId, false ) ) {
								// return to js
								$return['attachment'] = array(
									'id' => $attachmentId,
									'key' => $upload_key
								);
							} else {
								$return['code'] = 504;
								$return['message'] = 'Error: could not update media in the database.';
							}
						} else {
							$return['code'] = 503;
							$return['message'] = $_FILES['cr_file']['name'] . ': could not update the upload key.';
						}
					} else {
						$return['code'] = $attachmentId->get_error_code();
						$return['message'] = $attachmentId->get_error_message();
					}
					$return['code'] = 200;
					$return['message'] = 'OK';
				}
			}
			wp_send_json( $return );
		}

		public function delete_media() {
			$return = array(
				'code' => 100,
				'message' => ''
			);
			if( isset( $_POST['image'] ) && $_POST['image'] ) {
				$image_decoded = json_decode( stripslashes( $_POST['image'] ), true );
				if( $image_decoded && is_array( $image_decoded ) ) {
					if( isset( $image_decoded["id"] ) && $image_decoded["id"] ) {
						if( isset( $image_decoded["key"] ) && $image_decoded["key"] ) {
							$attachmentId = intval( $image_decoded["id"] );
							if( 'attachment' === get_post_type( $attachmentId ) ) {
								if( $image_decoded["key"] === get_post_meta( $attachmentId, 'cr-upload-temp-key', true ) ) {
									if( wp_delete_attachment( $attachmentId, true ) ) {
										if( false !== self::update_db_item( $_POST['cr_form'], $_POST['cr_item'], $attachmentId, true ) ) {
											$return['code'] = 200;
											$return['message'] = 'OK';
										} else {
											$return['code'] = 508;
											$return['message'] = 'Error: could not delete a media ID in the database.';
										}
									} else {
										$return['code'] = 507;
										$return['message'] = 'Error: could not delete the image.';
									}
								} else {
									$return['code'] = 506;
									$return['message'] = 'Error: meta key does not match.';
								}
							} else {
								$return['code'] = 505;
								$return['message'] = 'Error: id does not belong to an attachment.';
							}
						} else {
							$return['code'] = 504;
							$return['message'] = 'Error: image key is not set.';
						}
					} else {
						$return['code'] = 503;
						$return['message'] = 'Error: image id is not set.';
					}
				} else {
					$return['code'] = 502;
					$return['message'] = 'Error: JSON decoding problem.';
				}
			} else {
				$return['code'] = 501;
				$return['message'] = 'Error: no image to delete.';
			}
			wp_send_json( $return );
		}

		public static function update_db_item( $form_id, $item_id, $attachmentId, $delete ) {
			global $wpdb;
			$table_name = $wpdb->prefix . CR_Local_Forms::FORMS_TABLE;
			$update_result = false;
			$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_name` WHERE `formId` = %s", $form_id ) );
			if( null !== $record ) {
				$db_items = json_decode( $record->items, true );
				foreach( $db_items as $key => $value ) {
					if( isset( $value['id'] ) && $item_id == $value['id'] ) {
						if( $delete ) {
							if( isset( $value['media'] ) && is_array( $value['media'] ) ) {
								$k = array_search( $attachmentId, $value['media'] );
								if( false !== $k ) {
									unset( $db_items[$key]['media'][$k] );
									$db_items[$key]['media'] = array_values( $db_items[$key]['media'] );
								}
							}
						} else {
							$db_items[$key]['media'][] = $attachmentId;
						}
						$db_items = json_encode( $db_items );
						$update_result = $wpdb->update( $wpdb->prefix . CR_Local_Forms::FORMS_TABLE, array(
							'items' => $db_items
						), array( 'formId' => $form_id ) );
						break;
					}
				}
			}
			return $update_result;
		}

	}

endif;
