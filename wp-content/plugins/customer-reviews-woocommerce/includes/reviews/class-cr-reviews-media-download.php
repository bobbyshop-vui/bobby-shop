<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Reviews_Media_Download' ) ) :

	class CR_Reviews_Media_Download {

		public function __construct() {
			add_action( 'wp_ajax_cr_auto_download_media', array( $this, 'download_media_progress' ) );
			add_action( 'wp_ajax_cr_auto_download_okay', array( $this, 'download_media_okay' ) );
			add_action( 'wp_ajax_cr_auto_download_media_frontend', array( $this, 'download_media_frontend' ) );
			add_action( 'wp_ajax_nopriv_cr_auto_download_media_frontend', array( $this, 'download_media_frontend' ) );
		}

		public static function maybe_auto_download_media() {
			self::auto_download_media();
		}

		private static function auto_download_media() {
			$transientExists = get_transient( 'cr_download_media' );
			if( !$transientExists ) {
				global $wpdb;
				$mediaCount = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->commentmeta WHERE meta_key = 'ivole_review_image' OR meta_key = 'ivole_review_video'" );
				$mediaCount = intval( $mediaCount );
				if( 0 < $mediaCount ) {
					echo '<div class="cr-notice-auto-download notice notice-info">';
					echo '<p>' . sprintf( _n( 'There is %d media file attached by customers to reviews but not downloaded into WordPress Media Library yet.', 'There are %d media files attached by customers to reviews but not downloaded into WordPress Media Library yet.', $mediaCount, 'customer-reviews-woocommerce' ), $mediaCount ) . '</p>' ;
					echo '<button class="cr-button-auto-download button cr-button-auto-init" type="button" data-nonce="' . wp_create_nonce( 'cr-auto-download-media' ) . '">' . __( 'Download', 'customer-reviews-woocommerce' ) . '</button>';
					echo '</div>';
				}
			}
		}

		public function download_media_progress() {
			$return = array( 'code' => 0 );

			if ( isset( $_REQUEST['cr_nonce'] ) && wp_verify_nonce( $_REQUEST['cr_nonce'], 'cr-auto-download-media' ) ) {
				global $wpdb;
				$mediaFiles = $wpdb->get_results( "SELECT comment_id, meta_key, meta_value FROM $wpdb->commentmeta WHERE meta_key = 'ivole_review_image' OR meta_key = 'ivole_review_video'", OBJECT );

				$mediaFileToDownload = null;
				$downloadURL = '';
				$commentMeta = array( 'newCache' => 'cr_media_cache' );
				if( $mediaFiles ) {
					foreach( $mediaFiles as $mediaFile ) {
						$downloadURL = unserialize( $mediaFile->meta_value );
						$transientExists = get_transient( $downloadURL['url'] );
						if( !$transientExists  ) {
							$mediaFileToDownload = $mediaFile;
							if( 'ivole_review_video' === $mediaFile->meta_key ) {
								$commentMeta['newLocal'] = 'ivole_review_video2';
								$commentMeta['oldCloud'] = 'ivole_review_video';
							} else {
								$commentMeta['newLocal'] = 'ivole_review_image2';
								$commentMeta['oldCloud'] = 'ivole_review_image';
							}
							break;
						}
					}
				}

				if( $mediaFileToDownload ) {
					$return = $this->download_media_file( $mediaFileToDownload, $downloadURL, $commentMeta );
				} else {
					$return['code'] = 406;
					$return['msg'] = __( 'All media files have been downloaded.', 'customer-reviews-woocommerce' );
				}
			} else {
				$return['code'] = 300;
				$return['msg'] = __( 'Nonce verification failed.' );
			}

			wp_send_json( $return );
		}

		public function download_media_okay() {
			if ( isset( $_REQUEST['cr_nonce'] ) && wp_verify_nonce( $_REQUEST['cr_nonce'], 'cr-auto-download-media' ) ) {
				// do not display a notification for at least 15 mins
				set_transient( 'cr_download_media', 1, 900 );
			}
			wp_send_json( 0 );
		}

		public function download_media_frontend() {
			$transientFrontendExists = get_transient( 'cr_download_media_frontend' );
			if( !$transientFrontendExists && isset( $_POST['reviewID'] ) && 0 < intval( $_POST['reviewID'] ) ) {
				global $wpdb;
				$reviewID = intval( $_POST['reviewID'] );
				$mediaFiles = $wpdb->get_results( "SELECT comment_id, meta_key, meta_value FROM $wpdb->commentmeta WHERE comment_id = '" . $reviewID . "' AND ( meta_key = 'ivole_review_image' OR meta_key = 'ivole_review_video' )", OBJECT );

				$mediaFileToDownload = null;
				$downloadURL = '';
				$commentMeta = array( 'newCache' => 'cr_media_cache' );
				if( $mediaFiles ) {
					foreach( $mediaFiles as $mediaFile ) {
						$downloadURL = unserialize( $mediaFile->meta_value );
						$transientExists = get_transient( $downloadURL['url'] );
						if( !$transientExists  ) {
							$mediaFileToDownload = $mediaFile;
							if( 'ivole_review_video' === $mediaFile->meta_key ) {
								$commentMeta['newLocal'] = 'ivole_review_video2';
								$commentMeta['oldCloud'] = 'ivole_review_video';
							} else {
								$commentMeta['newLocal'] = 'ivole_review_image2';
								$commentMeta['oldCloud'] = 'ivole_review_image';
							}
							break;
						}
					}

					if( $mediaFileToDownload ) {
						$this->download_media_file( $mediaFileToDownload, $downloadURL, $commentMeta );
					}
				}
				// do not download next file for at least 5 mins
				set_transient( 'cr_download_media_frontend', 1, 300 );
			}
			wp_send_json( 0 );
		}

		private function download_media_file( $mediaFileToDownload, $downloadURL, $commentMeta ) {
			$return = array(
				'code' => 100,
				'msg' => ''
			);
			// set a transient to prevent parallel downloads of this file for 10 mins
			set_transient( $downloadURL['url'], 1, 600 );
			//
			$tmpFile = download_url( $downloadURL['url'] . '?crsrc=do' );
			$file_array = array(
				'name' => basename( $downloadURL['url'] ),
				'tmp_name' => $tmpFile
			);
			if ( is_wp_error( $tmpFile ) ) {
				$return['code'] = 400;
				$return['msg'] = sprintf( __( 'An error occurred while downloading a media file. Error code: %1$s (%2$s). File name: %3$s', 'customer-reviews-woocommerce' ), $return['code'], $tmpFile->get_error_code() . ' - ' . $tmpFile->get_error_message(), esc_url( $downloadURL['url'] ) );
				// check if the file was deleted by customer and the plugin should stop trying to download it
				if( false !== strpos( $tmpFile->get_error_code(), '404' ) && false !== strpos( $tmpFile->get_error_message(), 'Not Found' ) ) {
					delete_comment_meta( $mediaFileToDownload->comment_id, $commentMeta['oldCloud'], array( 'url' => $downloadURL['url'] ) );
					add_comment_meta( $mediaFileToDownload->comment_id, $commentMeta['newCache'], array( 'external' => $downloadURL['url'], 'local' => -1 ) );
				}
			} else {
				$review = get_comment( $mediaFileToDownload->comment_id );
				if( $review && $review->comment_post_ID ) {
					$customerName = get_comment_author( $mediaFileToDownload->comment_id );
					$product = wc_get_product( $review->comment_post_ID );
					if( $product ) {
						$reviewedItem = $product->get_name();
					} else {
						$reviewedItem = get_the_title( $review->comment_post_ID );
					}
					$fileDesc = sprintf( __( 'Review of %s by %s', 'customer-reviews-woocommerce' ), $reviewedItem, $customerName );
					$customerUserId = $review->user_id ? $review->user_id : 0;
					$reviewId = sprintf( __( 'Review ID: %s', 'customer-reviews-woocommerce' ), $review->comment_ID );
					$mediaId = media_handle_sideload( $file_array, $review->comment_post_ID, $fileDesc, array( 'post_author' => $customerUserId, 'post_date' => $review->comment_date, 'post_content' => $reviewId ) );
					if( is_wp_error( $mediaId ) ) {
						$return['code'] = 401;
						$return['msg'] = sprintf( __( 'An error occurred while downloading a media file. Error code: %s.', 'customer-reviews-woocommerce' ), $return['code'] );
					} else {
						$metaId = add_comment_meta( $review->comment_ID, $commentMeta['newLocal'], $mediaId );
						if( $metaId ) {
							if( add_comment_meta( $review->comment_ID, $commentMeta['newCache'], array( 'external' => $downloadURL['url'], 'local' => $metaId ) ) ) {
								if( delete_comment_meta( $review->comment_ID, $commentMeta['oldCloud'], array( 'url' => $downloadURL['url'] ) ) ) {
									$return['code'] = 200;
									$return['msg'] = sprintf( __( 'Downloaded the file <b>%1$s</b> attached by <b>%2$s</b> to their review of <b>%3$s</b>. Downloading the next file...', 'customer-reviews-woocommerce' ), $file_array[ 'name' ], $customerName, $reviewedItem );
								} else {
									delete_comment_meta( $review->comment_ID, $commentMeta['newLocal'], $mediaId );
									delete_comment_meta( $review->comment_ID, $commentMeta['newCache'], array( 'url' => $downloadURL['url'] ) );
									wp_delete_attachment( $mediaId );
									$return['code'] = 402;
									$return['msg'] = sprintf( __( 'An error occurred while downloading a media file. Error code: %s.', 'customer-reviews-woocommerce' ), $return['code'] );
								}
							} else {
								delete_comment_meta( $review->comment_ID, $commentMeta['newLocal'], $mediaId );
								wp_delete_attachment( $mediaId );
								$return['code'] = 403;
								$return['msg'] = sprintf( __( 'An error occurred while downloading a media file. Error code: %s.', 'customer-reviews-woocommerce' ), $return['code'] );
							}
						} else {
							wp_delete_attachment( $mediaId );
							$return['code'] = 404;
							$return['msg'] = sprintf( __( 'An error occurred while downloading a media file. Error code: %s.', 'customer-reviews-woocommerce' ), $return['code'] );
						}
					}
				} else {
					delete_comment_meta(
						$mediaFileToDownload->comment_id,
						$mediaFileToDownload->meta_key,
						$downloadURL
					);
					$return['code'] = 405;
					$return['msg'] = sprintf( __( 'An error occurred while downloading a media file. Error code: %s.', 'customer-reviews-woocommerce' ), $return['code'] );
				}
				@unlink( $file_array[ 'tmp_name' ] );
			}
			return $return;
		}

	}

endif;
