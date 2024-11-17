<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Reviews_Media_Meta_Box' ) ) :

	class CR_Reviews_Media_Meta_Box {

		public function __construct() {
			add_action( 'add_meta_boxes', array( $this, 'add_media_meta_box' ), 11, 2 );
			add_action( 'wp_ajax_cr_upload_local_images_admin', array( $this, 'new_upload' ) );
			add_action( 'wp_ajax_nopriv_cr_upload_local_images_admin', array( $this, 'new_upload' ) );
			add_action( 'wp_ajax_cr_detach_images_admin', array( $this, 'detach' ) );
			add_action( 'wp_ajax_nopriv_cr_detach_images_admin', array( $this, 'detach' ) );
		}

		public function add_media_meta_box( $post_type, $comment ) {
			if ( 'comment' === $post_type ) {
				$rating = get_comment_meta( $comment->comment_ID, 'rating', true );
				if($rating) {
					add_meta_box(
						'cr_reviews_media_meta_box',
						__( 'Uploaded Media', 'customer-reviews-woocommerce' ),
						array($this, 'render_meta_box'),
						$post_type,
						'normal',
						'default'
					);
				}
			}
		}

		public function render_meta_box( $comment ) {
			$pics = get_comment_meta( $comment->comment_ID, CR_Reviews::REVIEWS_META_IMG );
			$pics_local = get_comment_meta( $comment->comment_ID, CR_Reviews::REVIEWS_META_LCL_IMG );
			$pics_v = get_comment_meta( $comment->comment_ID, CR_Reviews::REVIEWS_META_VID );
			$pics_v_local = get_comment_meta( $comment->comment_ID, CR_Reviews::REVIEWS_META_LCL_VID );
			$pics_n = count( $pics );
			$pics_local_n = count( $pics_local );
			$pics_v_n = count( $pics_v );
			$pics_v_local_n = count( $pics_v_local );
			$k_image = 1;
			$k_video = 1;
			$cr_query = '?crsrc=wp';
			echo '<div class="cr-comment-images">';

			if ( $pics_n > 0 ) {
				for ( $i = 0; $i < $pics_n; $i++ ) {
					if ( isset( $pics[$i]['url'] ) ) {
						echo '<div class="cr-comment-image">';
						echo '<img src="' .
						$pics[$i]['url'] . $cr_query . '" alt="' . sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), $k_image ) .
						$comment->comment_author . '">';
						echo '</div>';
						$k_image++;
					}
				}
			}

			if ( $pics_v_n > 0 ) {
				for ( $i = 0; $i < $pics_v_n; $i++ ) {
					echo '<div class="cr-comment-video cr-comment-video-' . $k_video . '">';
					echo '<div class="cr-video-cont">';
					echo '<video preload="metadata" class="cr-video-a" ';
					echo 'src="' . $pics_v[$i]['url'] . $cr_query;
					echo '"></video>';
					echo '<img class="cr-comment-videoicon" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/video.svg" ';
					echo 'alt="' . sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $k_video, $comment->comment_author ) . '">';
					echo '<button class="cr-comment-video-close">' . CR_Reviews::get_close_button_svg() . '</button>';
					echo '</div></div>';
					$k_video++;
				}
			}

			$button_remove =  '';
			if( current_user_can( 'upload_files' ) ) {
				$button_remove = '<button class="cr-comment-image-close"><span class="dashicons dashicons-no"></span></button>';
			}

			if ( $pics_local_n > 0 ) {
				$temp_comment_content_flag = false;
				$temp_comment_content = '';
				for ( $i = 0; $i < $pics_local_n; $i++ ) {
					$attachmentUrl = wp_get_attachment_url( $pics_local[$i] );

					if ( $attachmentUrl ) {
						$temp_comment_content_flag = true;
						// any changes here must be also cascaded to JS for new picture uploads
						$temp_comment_content .= '<div class="cr-comment-image cr-comment-image-' . $pics_local[$i] . '">';
						$temp_comment_content .= '<div class="cr-comment-image-detach"><div class="cr-comment-image-detach-controls">';
						$temp_comment_content .= '<p>' . __( 'Detach?', 'customer-reviews-woocommerce' ) . '</p>';
						$temp_comment_content .= '<p><span class="cr-comment-image-detach-no">' . __( 'No', 'customer-reviews-woocommerce' ) . '</span>';
						$temp_comment_content .= '<span class="cr-comment-image-detach-yes" data-nonce="' . wp_create_nonce( 'cr-upload-images-detach' ) . '" data-attachment="' . $pics_local[$i] . '">' . __( 'Yes', 'customer-reviews-woocommerce' ) . '</span>';
						$temp_comment_content .= '</p><span class="cr-comment-image-detach-spinner"></span></div><img src="' .
						$attachmentUrl . '" alt="' . sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), $k_image ) .
						$comment->comment_author . '" /></div>';
						$temp_comment_content .= $button_remove;
						$temp_comment_content .= '</div>';
						$k_image++;
					}
				}

				if ( $temp_comment_content_flag ) {
					echo $temp_comment_content;
				}
			}

			if ( 0 < $pics_v_local_n ) {
				$temp_comment_content_flag = false;
				$temp_comment_content = '';
				for ( $i = 0; $i < $pics_v_local_n; $i++ ) {
					$attachmentUrl = wp_get_attachment_url( $pics_v_local[$i] );
					if ( $attachmentUrl ) {
						$temp_comment_content_flag = true;
						// any changes here must be also cascaded to JS for new video uploads
						$temp_comment_content .= '<div class="cr-comment-video cr-comment-video-' . $pics_v_local[$i] . '">';
						$temp_comment_content .= '<div class="cr-comment-image-detach"><div class="cr-comment-image-detach-controls">';
						$temp_comment_content .= '<p>' . __( 'Detach?', 'customer-reviews-woocommerce' ) . '</p>';
						$temp_comment_content .= '<p><span class="cr-comment-image-detach-no">' . __( 'No', 'customer-reviews-woocommerce' ) . '</span>';
						$temp_comment_content .= '<span class="cr-comment-image-detach-yes" data-nonce="' . wp_create_nonce( 'cr-upload-images-detach' ) . '" data-attachment="' . $pics_v_local[$i] . '">' . __( 'Yes', 'customer-reviews-woocommerce' ) . '</span>';
						$temp_comment_content .= '</p><span class="cr-comment-image-detach-spinner"></span></div>';
						$temp_comment_content .= '<div class="cr-video-cont">';
						$temp_comment_content .= '<video preload="metadata" class="cr-video-a" ';
						$temp_comment_content .= 'src="' . $attachmentUrl;
						$temp_comment_content .= '"></video>';
						$temp_comment_content .= '<img class="cr-comment-videoicon" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/video.svg" ';
						$temp_comment_content .= 'alt="' . sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $k_video, $comment->comment_author ) . '">';
						$temp_comment_content .= '<button class="cr-comment-video-close">' . CR_Reviews::get_close_button_svg() . '</button>';
						$temp_comment_content .= '</div></div>';
						$temp_comment_content .= $button_remove;
						$temp_comment_content .= '</div>';
						$k_video++;
					}
				}
				if ( $temp_comment_content_flag ) {
					echo $temp_comment_content;
				}
			}

			echo '<div class="cr-comment-images-clear"></div></div>';

			$uploadMedia = '<div class="cr-upload-local-images">';
			$uploadMedia .= '<label for="review_image" class="cr-upload-local-images-status">';
			$uploadMedia .= __( 'Upload images or videos', 'customer-reviews-woocommerce' );
			$uploadMedia .= '</label><input type="file" accept="image/*, video/*" multiple="multiple" name="review_image_' . $comment->comment_ID . '[]" id="review_image" />';
			$uploadMedia .= '<input type="button" class="cr-upload-local-images-btn button button-secondary" value="' .
			__( 'Upload', 'customer-reviews-woocommerce' ) . '" data-postid="' . $comment->comment_post_ID .
			'" data-commentid="' . $comment->comment_ID . '" data-nonce="' . wp_create_nonce( 'cr-upload-images' ) . '"/>';
			$uploadMedia .= '</div>';
			echo $uploadMedia;
		}

		public function new_upload() {
			$return = array(
				'code' => 100,
				'message' => array()
			);
			if( check_ajax_referer( 'cr-upload-images', 'cr_nonce', false ) ) {
				if( current_user_can( 'upload_files' ) ) {
					if( isset( $_FILES ) && is_array( $_FILES ) ) {
						$uploadSuccess = array();
						$uploadError = array();
						$comment = get_comment( $_POST['comment_id'] );
						$commentAuthor = '';
						if( $comment ) {
							$commentAuthor = $comment->comment_author;
						}
						$k = intval( $_POST['count_files'] ) + 1;
						foreach( $_FILES as $file_id => $file ) {
							$attachmentId = media_handle_upload( $file_id, $_POST['post_id'] );
							if( !is_wp_error( $attachmentId ) ) {
								$attachmentUrl = wp_get_attachment_url( $attachmentId );
								if( $attachmentUrl ) {
									$attachmentType = '';
									if( wp_attachment_is( 'image', $attachmentId ) ) {
										add_comment_meta( $_POST['comment_id'], CR_Reviews::REVIEWS_META_LCL_IMG, $attachmentId );
										$attachmentType = 'image';
									} else if( wp_attachment_is( 'video', $attachmentId ) ) {
										add_comment_meta( $_POST['comment_id'], CR_Reviews::REVIEWS_META_LCL_VID, $attachmentId );
										$attachmentType = 'video';
									}
									$uploadSuccess[] = array(
										'id' => $attachmentId,
										'url' => $attachmentUrl,
										'author' => sprintf( __( 'File #%1$d from ', 'customer-reviews-woocommerce' ), $k ) . $commentAuthor,
										'nonce' => wp_create_nonce( 'cr-upload-images-detach' ),
										'type' => $attachmentType
									);
									$k++;
								} else {
									$uploadError[] = array(
										'code' => 501,
										'message' => $file['name'] . ': could not obtain URL of the attachment.'
									);
								}
							} else {
								$uploadError[] = array(
									'code' => $attachmentId->get_error_code(),
									'message' => $attachmentId->get_error_message()
								);
							}
						}
						$countFiles = count( $_FILES );
						$countSuccess = count( $uploadSuccess );
						$countError = count( $uploadError );
						if( $countSuccess === $countFiles ) {
							$return['code'] = 200;
						} elseif ( 0 < $countSuccess ) {
							$return['code'] = 201;
						} else {
							$return['code'] = 202;
						}
						$return['message'] = array( sprintf( '%1d of %2d files have been successfully uploaded.', $countSuccess, $countFiles ) );
						$return['files'] = $uploadSuccess;
						foreach( $uploadError as $error ) {
							$return['message'][] = 'Error' . ': ' . $error['message'];
						}
						// update the meta field with the count of media files
						$media_count = CR_Ajax_Reviews::get_media_count( $_POST['comment_id'] );
						update_comment_meta( $_POST['comment_id'], 'ivole_media_count', $media_count );
					}
				} else {
					$return['code'] = 501;
					$return['message'] = array( 'Error: no permission to upload files.' );
				}
			} else {
				$return['code'] = 500;
				$return['message'] = array( 'Error: nonce validation failed. Please refresh the page and try again.' );
			}
			wp_send_json( $return );
		}

		public function detach() {
			$attachment_id = isset( $_POST['attachment_id'] ) ? $_POST['attachment_id'] : 0;
			$return = array( 'code' => 0, 'attachment' => $attachment_id );
			if( check_ajax_referer( 'cr-upload-images-detach', 'cr_nonce', false ) ) {
				if ( current_user_can( 'upload_files' ) ) {
					if( isset( $_POST['comment_id'] ) && 0 < $_POST['comment_id'] ) {
						if( isset( $_POST['attachment_id'] ) && 0 < $_POST['attachment_id'] ) {
							$meta_name = CR_Reviews::REVIEWS_META_LCL_IMG;
							if( 2 == $_POST['media_type'] ) {
								$meta_name = CR_Reviews::REVIEWS_META_LCL_VID;
							}
							if( delete_comment_meta( $_POST['comment_id'], $meta_name, $_POST['attachment_id'] ) ) {
								$return = array( 'code' => 1, 'attachment' => $_POST['attachment_id'] );
								// update the meta field with the count of media files
								$media_count = CR_Ajax_Reviews::get_media_count( $_POST['comment_id'] );
								update_comment_meta( $_POST['comment_id'], 'ivole_media_count', $media_count );
							}
						}
					}
				}
			}
			wp_send_json( $return );
		}

	}

endif;
