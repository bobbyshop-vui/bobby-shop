<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( ABSPATH . 'wp-admin/includes/media.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/image.php' );

if ( ! class_exists( 'CR_Reviews' ) ) :

	class CR_Reviews {

		private $limit_file_size = 5000000;
		private $limit_file_count = 3;
		private $ivrating = 'ivrating';
		private $ivole_reviews_histogram = 'no';
		private $ivole_ajax_reviews = 'no';
		private $disable_lightbox = false;
		private $reviews_voting = false;
		protected $lang;
		public static $onsite_q_types;
		private $incentivized_badge = false;

		const REVIEWS_META_IMG = 'ivole_review_image';
		const REVIEWS_META_LCL_IMG = 'ivole_review_image2';
		const REVIEWS_META_VID = 'ivole_review_video';
		const REVIEWS_META_LCL_VID = 'ivole_review_video2';

		public function __construct() {
			$this->limit_file_count = get_option( 'ivole_attach_image_quantity', 5 );
			$this->limit_file_size = 1024 * 1024 * get_option( 'ivole_attach_image_size', 25 );
			$this->lang = CR_Trust_Badge::get_badge_language();
			$this->disable_lightbox = 'yes' === get_option( 'ivole_disable_lightbox', 'no' ) ? true : false;
			$this->ivole_reviews_histogram = get_option( 'ivole_reviews_histogram', 'no' );
			$this->ivole_ajax_reviews = get_option( 'ivole_ajax_reviews', 'no' );
			$this->reviews_voting = 'yes' === get_option( 'ivole_reviews_voting', 'no' ) ? true : false;
			self::$onsite_q_types = array(
				'text' => __( 'Text', 'customer-reviews-woocommerce' ),
				'number' => __( 'Number', 'customer-reviews-woocommerce' )
			);
			$onsite_questions = CR_Forms_Settings::get_default_form_settings();
			if (
				$onsite_questions &&
				is_array( $onsite_questions ) &&
				isset( $onsite_questions['cus_atts'] ) &&
				is_array( $onsite_questions['cus_atts'] )
			) {
				$onsite_questions = true;
			} else {
				$onsite_questions = false;
			}

			add_action( 'wp_enqueue_scripts', array( $this, 'cr_style_1' ) );
			if( 'yes' === get_option( 'ivole_attach_image', 'no' ) ) {
				add_action( 'woocommerce_product_review_comment_form_args', array( $this, 'custom_fields_attachment' ) );
				add_action( 'wp_insert_comment', array( $this, 'save_review_image' ) );
				add_action( 'wp_ajax_cr_upload_local_images_frontend', array( $this, 'new_ajax_upload' ) );
				add_action( 'wp_ajax_nopriv_cr_upload_local_images_frontend', array( $this, 'new_ajax_upload' ) );
				add_action( 'wp_ajax_cr_delete_local_images_frontend', array( $this, 'new_ajax_delete' ) );
				add_action( 'wp_ajax_nopriv_cr_delete_local_images_frontend', array( $this, 'new_ajax_delete' ) );
			}
			if( 'yes' === get_option( 'ivole_form_attach_media', 'no' ) || 'yes' == get_option( 'ivole_attach_image', 'no' ) ) {
				if( 'yes' === $this->ivole_ajax_reviews ) {
					add_action( 'cr_reviews_customer_images', array( $this, 'display_review_media_top_prd' ) );
				}
				// standard WooCommerce review template
				add_action( 'woocommerce_review_after_comment_text', array( $this, 'display_review_image' ), 10 );
				// enhanced CusRev review template
				add_action( 'cr_review_after_comment_text', array( $this, 'display_review_image' ), 10 );
			}
			add_action( 'comment_form_after_fields', array( $this, 'custom_fields_terms' ) );
			if( self::is_captcha_enabled() ) {
				if( ! is_user_logged_in() ) {
					add_action( 'comment_form_after_fields', array( $this, 'custom_fields_captcha2' ) );
					add_action( 'cr_review_form_before_btns', array( $this, 'display_captcha_cr' ) );
					add_filter( 'preprocess_comment', array( $this, 'validate_captcha' ) );
					add_action( 'wp_enqueue_scripts', array( $this, 'cr_style_2' ), 11 );
				}
			}
			if( 'yes' === $this->ivole_reviews_histogram || 'yes' === get_option( 'ivole_reviews_shortcode', 'no' ) ) {
				add_action( 'init', array( $this, 'add_query_var' ), 20 );
			}
			if (
				'yes' === $this->ivole_reviews_histogram ||
				'yes' === $this->ivole_ajax_reviews ||
				$onsite_questions
			) {
				add_filter( 'comments_template', array( $this, 'load_custom_comments_template' ), 100 );
			}
			if( 'yes' === $this->ivole_reviews_histogram ) {
				add_action( 'cr_reviews_summary', array( $this, 'show_summary_table' ), 10, 3 );
				add_filter( 'comments_template_query_args', array( $this, 'filter_comments2' ), 20);
				add_filter( 'comments_array', array( $this, 'include_review_replies' ), 11, 2 );
			} else {
				add_action( 'cr_reviews_nosummary', array( $this, 'show_nosummary' ), 10, 1 );
			}
			if( $this->reviews_voting ) {
				add_action( 'wp_ajax_cr_vote_review', array( $this, 'vote_review_registered' ) );
				add_action( 'wp_ajax_nopriv_cr_vote_review', array( $this, 'vote_review_unregistered' ) );
				// standard WooCommerce review template
				add_action( 'woocommerce_review_after_comment_text', array( $this, 'display_voting_buttons' ), 11 );
				// enhanced CusRev review template
				add_action( 'cr_review_after_comment_text', array( $this, 'display_voting_buttons' ), 11 );
			}
			add_action( 'cr_reviews_count_row', array( $this, 'show_count_row' ), 10, 3 );
			add_action( 'woocommerce_review_before_comment_text', array( $this, 'display_verified_badge' ), 10 );
			add_action( 'cr_review_before_comment_text', array( $this, 'display_verified_badge_only' ), 10 );
			if( 'yes' === get_option( 'ivole_trust_badge_floating', 'no' ) && ! is_admin() ) {
				new CR_Floating_Trust_Badge();
			}
			add_action( 'woocommerce_review_before_comment_text', array( $this, 'display_custom_questions' ), 11 );
			add_action( 'cr_review_before_comment_text', array( $this, 'display_custom_questions' ), 11 );
			add_action( 'woocommerce_review_meta', array( $this, 'cusrev_review_meta' ), 9, 1 );
			add_action( 'wp_footer', array( $this, 'cr_photoswipe' ) );
			add_action( 'woocommerce_review_before_comment_text', array( $this, 'display_featured' ), 9 );
			add_action( 'woocommerce_before_single_product', array( $this, 'custom_avatars' ) );
			add_filter( 'cr_review_form_before_comment', array( 'CR_Custom_Questions', 'review_form_questions' ) );
			add_action( 'wp_insert_comment', array( 'CR_Custom_Questions', 'submit_onsite_questions' ) );
			add_action( 'comment_post', array( $this, 'clear_trustbadge_cache' ), 10, 3 );
			add_action( 'cr_review_form_rating', array( 'CR_Custom_Questions', 'review_form_rating' ) );
			// standard WooCommerce review template
			add_action( 'woocommerce_review_after_comment_text', array( $this, 'display_incentivized_badge' ), 8 );
			// enhanced CusRev review template
			add_action( 'cr_review_after_comment_text', array( $this, 'display_incentivized_badge' ), 8 );
			// a filter for voting buttons on customer uploaded media pop-up
			add_filter( 'cr_reviews_media_voting', array( $this, 'display_media_voting' ), 10, 2 );
		}
		public function custom_fields_attachment( $comment_form ) {
			$post_id = get_the_ID();
			$html_field_attachment = '<div class="cr-upload-local-images"><div class="cr-upload-images-preview"></div>';
			$html_field_attachment .= '<label for="cr_review_image" class="cr-upload-images-status">';
			$html_field_attachment .= sprintf( __( 'Upload up to %d images or videos', 'customer-reviews-woocommerce' ), $this->limit_file_count );
			$html_field_attachment .= '</label><input type="file" accept="image/*, video/*" multiple="multiple" name="review_image_';
			$html_field_attachment .= $post_id . '[]" id="cr_review_image" data-nonce="' . wp_create_nonce( 'cr-upload-images-frontend' );
			$html_field_attachment .= '" data-postid="' . $post_id . '" />';
			$html_field_attachment .= '</div>';
			$comment_form['comment_field'] .= apply_filters( 'ivole_custom_fields_attachment2', $html_field_attachment );
			$comment_form = apply_filters( 'ivole_custom_fields_attachment', $comment_form );
			return $comment_form;
		}
		// public function custom_fields_captcha( $comment_form ) {
		// 	$site_key = self::captcha_site_key();
		// 	$comment_form['comment_field'] .= '<div style="clear:both;"></div><div class="cr-recaptcha'
		// 		. (CR_Qna::is_captcha_enabled() ? '' : ' g-recaptcha')
		// 		. '" data-sitekey="' . $site_key . '"></div>';
		// 	return $comment_form;
		// }
		public function custom_fields_captcha2() {
			$site_key = self::captcha_site_key();
			echo '<div style="clear:both;"></div>';
			echo '<div class="cr-recaptcha' . (CR_Qna::is_captcha_enabled() ? '' : ' g-recaptcha') .
				'" data-sitekey="' . $site_key . '"></div>';
		}
		public function display_captcha_cr() {
			wp_enqueue_script( 'cr-recaptcha' );
			$site_key = self::captcha_site_key();
			echo '<div class="cr-review-form-captcha">';
			echo '<div class="cr-recaptcha' . (CR_Qna::is_captcha_enabled() ? '' : ' g-recaptcha') .
				'" data-sitekey="' . $site_key . '" data-crcaptchaid="' . substr( str_shuffle( md5( microtime() ) ), 0, 5 ) . '"></div>';
			echo '<div class="cr-review-form-field-error">' . __( '* Please confirm that you are not a robot', 'customer-reviews-woocommerce' ) . '</div>';
			echo '</div>';
		}
		public function custom_fields_terms() {
			$form_settings = CR_Forms_Settings::get_default_form_settings();
			$cr_form_checkbox = ( 'yes' === CR_Forms_Settings::get_onsite_form_checkbox( $form_settings ) ) ? true : false;
			$cr_form_checkbox_text = CR_Forms_Settings::get_onsite_form_checkbox_text( $form_settings );
			if ( false === $cr_form_checkbox_text ) {
				$cr_form_checkbox_text = CR_Forms_Settings::get_default_form_onsite_checkbox_text();
			}
			$cr_form_checkbox_text = wp_specialchars_decode( $cr_form_checkbox_text, ENT_QUOTES );
			if ( $cr_form_checkbox ) :
				?>
					<div class="cr-review-form-terms">
						<label>
							<input type="checkbox" class="cr-review-form-checkbox" name="cr_review_form_checkbox" />
							<span><?php echo $cr_form_checkbox_text; ?></span>
						</label>
					</div>
				<?php
			endif;
		}
		public function save_review_image( $comment_id ) {
			if( isset( $_POST['cr-upload-images-ids'] ) && is_array( $_POST['cr-upload-images-ids'] ) ) {
				$nFiles = count( $_POST['cr-upload-images-ids'] );
				// check count of files
				if( $nFiles > $this->limit_file_count ) {
					echo sprintf( __( 'Error: You tried to upload too many files. The maximum number of files that you can upload is %d.', 'customer-reviews-woocommerce' ), $this->limit_file_count );
					echo '<br/>' . sprintf( __( 'Go back to: %s', 'customer-reviews-woocommerce' ), '<a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a>' );
					die;
				}
				$images_count = 0;
				foreach ($_POST['cr-upload-images-ids'] as $image) {
					$image_decoded = json_decode( stripslashes( $image ), true );
					if( $image_decoded && is_array( $image_decoded ) ) {
						if( isset( $image_decoded["id"] ) && $image_decoded["id"] ) {
							if( isset( $image_decoded["key"] ) && $image_decoded["key"] ) {
								$attachmentId = intval( $image_decoded["id"] );
								if( 'attachment' === get_post_type( $attachmentId ) ) {
									if( $image_decoded["key"] === get_post_meta( $attachmentId, 'cr-upload-temp-key', true ) ) {
										if( wp_attachment_is( 'image', $attachmentId ) ) {
											add_comment_meta( $comment_id, self::REVIEWS_META_LCL_IMG, $attachmentId );
										} else if( wp_attachment_is( 'video', $attachmentId ) ) {
											add_comment_meta( $comment_id, self::REVIEWS_META_LCL_VID, $attachmentId );
										}
										delete_post_meta( $attachmentId, 'cr-upload-temp-key' );
										$images_count++;
									}
								}
							}
						}
					}
				}
				// create a meta field with the count of media files
				update_comment_meta( $comment_id, 'ivole_media_count', $images_count );
			}
		}

		public static function is_valid_file_type( $type ) {
			$type = strtolower( trim ( $type ) );
			return in_array( $type, ['png', 'gif', 'jpg', 'jpeg', 'mp4', 'mpeg', 'ogg', 'webm', 'mov', 'avi'] );
		}

		public function display_review_image( $comment ) {
			$output = '';
			$pics = get_comment_meta( $comment->comment_ID, self::REVIEWS_META_IMG );
			$pics_local = get_comment_meta( $comment->comment_ID, self::REVIEWS_META_LCL_IMG );
			$pics_v = get_comment_meta( $comment->comment_ID, self::REVIEWS_META_VID );
			$pics_v_local = get_comment_meta( $comment->comment_ID, self::REVIEWS_META_LCL_VID );
			$pics_n = count( $pics );
			$pics_local_n = count( $pics_local );
			$pics_v_n = count( $pics_v );
			$pics_v_local_n = count( $pics_v_local );
			$cr_query = '?crsrc=wp';
			if( 0 < $pics_n || 0 < $pics_local_n || 0 < $pics_v_n || 0 < $pics_v_local_n ) {
				$output .= '<div class="cr-comment-images cr-comment-videos">';
				$k = 1;
				if( 0 < $pics_n ) {
					for( $i = 0; $i < $pics_n; $i++ ) {
						if ( isset( $pics[$i]['url'] ) ) {
							$output .= '<div class="iv-comment-image cr-comment-image-ext" data-reviewid="' . $comment->comment_ID . '">';
							$output .= '<a href="' . $pics[$i]['url'] . $cr_query . '" class="cr-comment-a" rel="nofollow"><img src="' .
							$pics[$i]['url'] . $cr_query . '" alt="' . sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), $k ) .
							$comment->comment_author . '" loading="lazy"></a>';
							$output .= '</div>';
							$k++;
						}
					}
				}
				if( 0 < $pics_local_n ) {
					$temp_comment_content_flag = false;
					$temp_comment_content = '';
					for( $i = 0; $i < $pics_local_n; $i++ ) {
						$attachmentSrc = wp_get_attachment_image_src( $pics_local[$i], apply_filters( 'cr_reviews_image_size', 'large' ) );
						if( $attachmentSrc ) {
							$temp_comment_content_flag = true;
							$temp_comment_content .= '<div class="iv-comment-image">';
							$temp_comment_content .= '<a href="' . $attachmentSrc[0] . '" class="cr-comment-a"><img src="' .
							$attachmentSrc[0] . '" width="' . $attachmentSrc[1] . '" height="' . $attachmentSrc[2] .
							'" alt="' . sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), $k ) .
							$comment->comment_author . '" loading="lazy"></a>';
							$temp_comment_content .= '</div>';
							$k++;
						}
					}
					if( $temp_comment_content_flag ) {
						$output .= $temp_comment_content;
					}
				}
				$k = 1;
				if( 0 < $pics_v_n ) {
					for( $i = 0; $i < $pics_v_n; $i ++) {
						$output .= '<div class="cr-comment-video cr-comment-video-ext cr-comment-video-' . $k . '" data-reviewid="' . $comment->comment_ID . '">';
						$output .= '<div class="cr-video-cont">';
						$output .= '<video preload="metadata" class="cr-video-a" ';
						$output .= 'src="' . $pics_v[$i]['url'] . $cr_query;
						$output .= '"></video>';
						$output .= '<img class="cr-comment-videoicon" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/video.svg" ';
						$output .= 'alt="' . sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $k, $comment->comment_author ) . '">';
						$output .= '<button class="cr-comment-video-close">' . self::get_close_button_svg() . '</button>';
						$output .= '</div></div>';
						$k++;
					}
				}
				if( 0 < $pics_v_local_n ) {
					$temp_comment_content_flag = false;
					$temp_comment_content = '';
					for( $i = 0; $i < $pics_v_local_n; $i++ ) {
						$attachmentUrl = wp_get_attachment_url( $pics_v_local[$i] );
						if( $attachmentUrl ) {
							$temp_comment_content_flag = true;
							$temp_comment_content .= '<div class="cr-comment-video cr-comment-video-' . $k . '">';
							$temp_comment_content .= '<div class="cr-video-cont">';
							$temp_comment_content .= '<video preload="metadata" class="cr-video-a" ';
							$temp_comment_content .= 'src="' . $attachmentUrl;
							$temp_comment_content .= '"></video>';
							$temp_comment_content .= '<img class="cr-comment-videoicon" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/video.svg" ';
							$temp_comment_content .= 'alt="' . sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $k, $comment->comment_author ) . '">';
							$temp_comment_content .= '<button class="cr-comment-video-close">' . self::get_close_button_svg() . '</button>';
							$temp_comment_content .= '</div></div>';
							$k++;
						}
					}
					if( $temp_comment_content_flag ) {
						$output .= $temp_comment_content;
					}
				}
				$output .= '<div style="clear:both;"></div></div>';
			}
			echo $output;
		}
		// include replies to reviews when filtering by number of stars
		public function include_review_replies( $comments, $post_id ){
			$comments_flat = array();
			foreach ( $comments as $comment ) {
				$comments_flat[]  = $comment;
				$args = array(
					'parent' => $comment->comment_ID,
					'format' => 'flat',
					'status' => 'approve',
					'orderby' => 'comment_date'
				);
				$comment_children = get_comments( $args );
				foreach ( $comment_children as $comment_child ) {
					$reply_already_exist = false;
					foreach( $comments as $comment_flat ) {
						if( $comment_flat->comment_ID === $comment_child->comment_ID ) {
							$reply_already_exist = true;
						}
					}
					if( !$reply_already_exist ) {
						$comments_flat[] = $comment_child;
					}
				}
			}
			return $comments_flat;
		}
		public function display_voting_buttons( $comment ) {
			if( 0 === intval( $comment->comment_parent ) ) {
				$votes = $this->get_votes( $comment->comment_ID );
				if( is_array( $votes ) ) {
					?>
					<div class="cr-voting-cont cr-voting-cont-uni">
						<span class="cr-voting-upvote cr-voting-a<?php echo ( $votes['current'] > 0 ? ' cr-voting-active' : '' ); ?>" data-vote="<?php echo $comment->comment_ID; ?>" data-upvote="1">
							<svg width="1000" height="1227" viewBox="0 0 1000 1227" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path class="cr-voting-svg-int" d="M644.626 317.445C649.154 317.445 652.363 317.445 655.572 317.445C723.597 317.449 791.624 317.158 859.648 317.572C898.609 317.808 933.112 330.638 960.638 358.82C995.241 394.246 1006.17 436.789 996.788 485.136C990.243 518.839 984.39 552.677 978.124 586.435C972.353 617.536 966.435 648.611 960.597 679.7C953.013 720.085 946.573 760.728 937.577 800.796C926.489 850.175 895.987 884.112 848.079 900.497C832.798 905.724 815.765 907.905 799.527 907.935C549.65 908.388 299.771 908.259 49.8947 908.247C25.2463 908.245 10.0803 898.71 2.61154 877.687C0.677947 872.241 0.300995 866.015 0.297088 860.148C0.175995 710.546 0.422088 560.945 0.000213738 411.345C-0.075958 384.09 20.215 362.994 48.6134 363.302C113.65 364.009 178.699 363.433 243.742 363.648C250.986 363.672 256.344 361.898 261.676 356.627C300.166 318.564 338.904 280.75 377.791 243.088C390.217 231.053 394.06 215.312 397.885 199.588C410.045 149.59 413.808 98.6035 414.676 47.3575C414.918 33.1016 417.97 19.961 429.484 11.1564C436.297 5.94738 445.088 0.58606 453.191 0.257936C503.865 -1.7948 551.841 8.18175 593.892 38.2071C628.316 62.7872 644.705 96.9199 644.634 139.162C644.541 194.99 644.621 250.818 644.625 306.646C644.626 309.849 644.626 313.051 644.626 317.445Z" fill="#00A382" fill-opacity="0.4"/>
								<path class="cr-voting-svg-ext" d="M644.626 317.445C649.154 317.445 652.363 317.445 655.572 317.445C723.597 317.449 791.624 317.158 859.648 317.572C898.609 317.808 933.112 330.638 960.638 358.82C995.241 394.246 1006.17 436.789 996.788 485.136C990.243 518.839 984.39 552.677 978.124 586.435C972.353 617.536 966.435 648.611 960.597 679.7C953.013 720.085 946.573 760.728 937.577 800.796C926.489 850.175 895.987 884.112 848.079 900.497C832.798 905.724 815.765 907.905 799.527 907.935C549.65 908.388 299.771 908.259 49.8947 908.247C25.2463 908.245 10.0803 898.71 2.61154 877.687C0.677947 872.241 0.300995 866.015 0.297088 860.147C0.175995 710.546 0.422088 560.945 0.000213738 411.345C-0.075958 384.09 20.215 362.994 48.6134 363.302C113.65 364.009 178.699 363.433 243.742 363.648C250.986 363.672 256.344 361.898 261.676 356.627C300.166 318.564 338.904 280.75 377.791 243.088C390.217 231.053 394.06 215.312 397.884 199.588C410.045 149.59 413.808 98.6035 414.675 47.3575C414.918 33.1016 417.97 19.961 429.484 11.1564C436.297 5.94738 445.088 0.58606 453.191 0.257936C503.865 -1.7948 551.841 8.18175 593.892 38.2071C628.316 62.7872 644.705 96.9199 644.634 139.162C644.54 194.99 644.621 250.818 644.624 306.646C644.626 309.849 644.626 313.051 644.626 317.445ZM565.625 819.015C565.625 819.036 565.625 819.058 565.625 819.081C643.392 819.081 721.159 819.091 798.925 819.075C828.847 819.069 847.042 803.902 852.509 774.366C861.169 727.589 869.743 680.798 878.411 634.023C888.853 577.675 899.495 521.365 909.747 464.984C913.148 446.285 908.323 430.019 892.739 417.99C882.896 410.392 871.601 407.894 859.249 407.918C774.708 408.082 690.167 407.929 605.626 408.064C588.71 408.091 574.158 403.558 563.621 389.513C556.435 379.935 554.595 368.881 554.597 357.283C554.609 285.207 554.316 213.127 554.812 141.055C554.927 124.215 547.863 113.125 533.511 106.08C526.277 102.527 518.486 100.119 511.005 97.0488C504.636 94.4355 502.461 96.4629 502.093 103.281C499.685 147.967 493.855 192.172 480.816 235.115C473.15 260.361 463.355 284.873 444.131 303.847C404.035 343.418 363.549 382.591 323.033 421.73C318.933 425.691 317.385 429.689 317.389 435.23C317.48 559.603 317.431 683.976 317.433 808.349C317.433 818.991 317.513 819.013 328.258 819.013C407.381 819.017 486.502 819.015 565.625 819.015ZM226.81 818.503C226.81 696.718 226.81 575.511 226.81 454.082C181.205 454.082 136.127 454.082 90.797 454.082C90.797 575.755 90.797 696.941 90.797 818.503C136.418 818.503 181.504 818.503 226.81 818.503Z" fill="#00A382"/>
							</svg>
						</span>
						<span class="cr-voting-upvote-count">(<?php
							if( isset( $votes['upvotes'] ) ) {
								echo intval( $votes['upvotes'] );
							} else {
								echo '0';
							} ?>)</span>
						<span class="cr-voting-downvote cr-voting-a<?php echo ( $votes['current'] < 0 ? ' cr-voting-active' : '' ); ?>" data-vote="<?php echo $comment->comment_ID; ?>" data-upvote="0">
							<svg width="1000" height="1227" viewBox="0 0 1000 1227" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path class="cr-voting-svg-int" d="M355.375 909.828C350.847 909.828 347.638 909.828 344.429 909.828C276.404 909.824 208.377 910.115 140.353 909.701C101.392 909.465 66.8886 896.635 39.3632 868.453C4.75973 833.028 -6.17383 790.485 3.21288 742.137C9.7578 708.434 15.6113 674.596 21.8769 640.838C27.6484 609.737 33.5664 578.663 39.4042 547.573C46.9882 507.188 53.4277 466.546 62.4238 426.477C73.5117 377.099 104.014 343.161 151.922 326.776C167.203 321.55 184.236 319.368 200.474 319.339C450.351 318.886 700.23 319.015 950.106 319.026C974.755 319.028 989.921 328.564 997.39 349.587C999.323 355.032 999.7 361.259 999.704 367.126C999.825 516.727 999.579 666.329 1000 815.928C1000.08 843.184 979.786 864.28 951.388 863.971C886.351 863.264 821.302 863.84 756.259 863.625C749.015 863.602 743.657 865.375 738.325 870.647C699.835 908.709 661.097 946.524 622.21 984.186C609.784 996.221 605.941 1011.96 602.116 1027.69C589.956 1077.68 586.193 1128.67 585.325 1179.92C585.083 1194.17 582.031 1207.31 570.517 1216.12C563.704 1221.33 554.913 1226.69 546.81 1227.02C496.136 1229.07 448.16 1219.09 406.109 1189.07C371.685 1164.49 355.296 1130.35 355.367 1088.11C355.46 1032.28 355.38 976.455 355.376 920.627C355.375 917.424 355.375 914.223 355.375 909.828Z" fill="#CA2430" fill-opacity="0.4"/>
								<path class="cr-voting-svg-ext" d="M355.374 909.828C350.847 909.828 347.638 909.828 344.429 909.828C276.403 909.824 208.376 910.115 140.353 909.701C101.392 909.464 66.8882 896.634 39.3628 868.453C4.75934 833.027 -6.17424 790.484 3.21247 742.137C9.75739 708.433 15.6109 674.596 21.8765 640.838C27.648 609.736 33.566 578.662 39.4038 547.572C46.9878 507.188 53.4272 466.545 62.4233 426.477C73.5112 377.098 104.013 343.161 151.921 326.776C167.202 321.549 184.236 319.368 200.474 319.338C450.351 318.885 700.229 319.014 950.106 319.026C974.754 319.028 989.92 328.563 997.389 349.586C999.323 355.032 999.7 361.258 999.703 367.125C999.825 516.727 999.578 666.328 1000 815.928C1000.08 843.183 979.785 864.279 951.387 863.97C886.35 863.263 821.301 863.84 756.258 863.625C749.014 863.601 743.657 865.375 738.325 870.646C699.835 908.709 661.096 946.523 622.21 984.185C609.784 996.22 605.94 1011.96 602.116 1027.69C589.956 1077.68 586.192 1128.67 585.325 1179.92C585.083 1194.17 582.03 1207.31 570.516 1216.12C563.704 1221.33 554.913 1226.69 546.809 1227.01C496.136 1229.07 448.159 1219.09 406.108 1189.07C371.685 1164.49 355.296 1130.35 355.366 1088.11C355.46 1032.28 355.38 976.455 355.376 920.627C355.374 917.423 355.374 914.222 355.374 909.828ZM434.376 408.258C434.376 408.237 434.376 408.215 434.376 408.192C356.609 408.192 278.841 408.182 201.076 408.198C171.154 408.203 152.958 423.371 147.492 452.906C138.831 499.684 130.257 546.475 121.589 593.25C111.148 649.598 100.505 705.908 90.2534 762.289C86.853 780.988 91.6772 797.254 107.261 809.283C117.105 816.881 128.4 819.379 140.751 819.355C225.292 819.191 309.833 819.344 394.374 819.209C411.29 819.181 425.843 823.715 436.38 837.76C443.565 847.338 445.405 858.392 445.403 869.99C445.392 942.066 445.685 1014.15 445.188 1086.22C445.073 1103.06 452.138 1114.15 466.489 1121.19C473.724 1124.75 481.515 1127.15 488.995 1130.22C495.364 1132.84 497.54 1130.81 497.907 1123.99C500.315 1079.31 506.145 1035.1 519.184 992.158C526.851 966.912 536.645 942.4 555.87 923.425C595.966 883.855 636.452 844.681 676.967 805.543C681.067 801.582 682.616 797.584 682.612 792.043C682.52 667.67 682.569 543.297 682.567 418.924C682.567 408.282 682.487 408.26 671.743 408.26C592.62 408.256 513.499 408.258 434.376 408.258ZM773.19 408.77C773.19 530.555 773.19 651.762 773.19 773.191C818.795 773.191 863.874 773.191 909.204 773.191C909.204 651.518 909.204 530.332 909.204 408.77C863.583 408.77 818.497 408.77 773.19 408.77Z" fill="#CA2430"/>
							</svg>
						</span>
						<span class="cr-voting-downvote-count">(<?php
							if( isset( $votes['downvotes'] ) ) {
								echo intval( $votes['downvotes'] );
							} else {
								echo '0';
							} ?>)</span>
					</div>
					<?php
				}
			}
		}
		public function cr_style_1() {
			if( is_product() ) {
				$assets_version = Ivole::CR_VERSION;
				if( ! $this->disable_lightbox ) {
					wp_enqueue_script( 'photoswipe-ui-default' );
					wp_enqueue_style( 'photoswipe-default-skin' );
				}
				wp_register_style( 'cr-frontend-css', plugins_url( '/css/frontend.css', dirname( dirname( __FILE__ ) ) ), array(), $assets_version, 'all' );
				wp_register_script( 'cr-frontend-js', plugins_url( '/js/frontend.js', dirname( dirname( __FILE__ ) ) ), array( 'jquery' ), $assets_version, true );
				wp_enqueue_style( 'cr-frontend-css' );
				wp_localize_script(
					'cr-frontend-js',
					'cr_ajax_object',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'ivole_recaptcha' => self::is_captcha_enabled() ? 1 : 0,
						'disable_lightbox' => ( $this->disable_lightbox ? 1 : 0 ),
						'cr_upload_initial' => sprintf( __( 'Upload up to %d images or videos', 'customer-reviews-woocommerce' ), $this->limit_file_count ),
						'cr_upload_error_file_type' => __( 'Error: accepted file types are PNG, JPG, JPEG, GIF, MP4, MPEG, OGG, WEBM, MOV, AVI', 'customer-reviews-woocommerce' ),
						'cr_upload_error_too_many' => sprintf( __( 'Error: You tried to upload too many files. The maximum number of files that can be uploaded is %d.', 'customer-reviews-woocommerce' ), $this->limit_file_count ),
						'cr_upload_error_file_size' => sprintf( __( 'The file cannot be uploaded because its size exceeds the limit of %d MB', 'customer-reviews-woocommerce' ), intval( $this->limit_file_size / 1024 / 1024 ) ),
						'cr_images_upload_limit' => $this->limit_file_count,
						'cr_images_upload_max_size' => $this->limit_file_size
					)
			);
			wp_enqueue_script( 'cr-frontend-js' );
		}
	}
	public function cr_style_2() {
		if ( is_product() ) {
			if ( CR_Qna::is_captcha_enabled() ) {
				$script_file_basename = 'reviews-qa-captcha';
				$script_id = 'cr-' . $script_file_basename;
				wp_register_script(
					$script_id,
					plugins_url( 'js/' . $script_file_basename . '.js', dirname( dirname( __FILE__ ) ) ),
					array( 'jquery' ),
					'4.9',
					true
				);
				wp_localize_script( $script_id, 'crReviewsQaCaptchaConfig', array(
					'v2Sitekey' => self::captcha_site_key(),
				) );
				wp_enqueue_script( $script_id );
			} else {
				wp_register_script( 'cr-recaptcha', 'https://www.google.com/recaptcha/api.js?hl=' . $this->lang, array(), null, true );
				wp_enqueue_script( 'cr-recaptcha' );
			}
		} else {
			wp_register_script( 'cr-recaptcha', 'https://www.google.com/recaptcha/api.js?hl=' . $this->lang, array(), null, true );
		}
	}
	public function validate_captcha( $commentdata ) {
		if( is_admin() && current_user_can( 'edit_posts' ) ) {
			return $commentdata;
		}
		if( 'cr_qna' !== $commentdata['comment_type'] ) {
			if( get_post_type( $commentdata['comment_post_ID'] ) === 'product' ) {
				if( !$this->ping_captcha() ) {
					wp_die( __( 'reCAPTCHA vertification failed and your review cannot be saved.', 'customer-reviews-woocommerce' ), __( 'Add Review Error', 'customer-reviews-woocommerce' ), array( 'back_link' => true ) );
				}
			}
		}
		return $commentdata;
	}
	private function ping_captcha( $recaptcha = null ) {
		if( !$recaptcha && isset( $_POST['g-recaptcha-response'] ) ) {
			$recaptcha = $_POST['g-recaptcha-response'];
		}
		if( $recaptcha ) {
			$secret_key = get_option( 'ivole_captcha_secret_key', '' );
			$response = json_decode( wp_remote_retrieve_body( wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array( 'body' => array( 'secret' => $secret_key, 'response' => $recaptcha ) ) ) ), true );
			if( $response["success"] )
			{
				return true;
			}
		}
		return false;
	}
	public function load_custom_comments_template( $template ) {
		if ( get_post_type() !== 'product' ) {
			return $template;
		}
		$plugin_folder = 'customer-reviews-woocommerce';
		$check_dirs = array(
			trailingslashit( get_stylesheet_directory() ) . $plugin_folder,
			trailingslashit( get_template_directory() ) . $plugin_folder
		);
		$template_file_name = 'cr-single-product-reviews.php';
		if( 'yes' === $this->ivole_ajax_reviews ) {
			$template_file_name = 'cr-ajax-product-reviews.php';
		}
		foreach ( $check_dirs as $dir ) {
			if ( file_exists( trailingslashit( $dir ) . $template_file_name ) ) {
				return trailingslashit( $dir ) . $template_file_name;
			}
		}
		return wc_locate_template( $template_file_name, '', plugin_dir_path ( dirname( dirname( __FILE__ ) ) ) . '/templates/' );
	}
	public function show_summary_table( $product_id, $is_ajax = false, $new_reviews_allowed = false ) {
		$tab_reviews = apply_filters( 'cr_productpage_reviews_tab', '#tab-reviews' );
		$all = $this->count_ratings( $product_id, 0 );
		if( $all > 0 ) {
			$five = (float)$this->count_ratings( $product_id, 5 );
			$five_percent = floor( $five / $all * 100 );
			$five_rounding = $five / $all * 100 - $five_percent;
			$four = (float)$this->count_ratings( $product_id, 4 );
			$four_percent = floor( $four / $all * 100 );
			$four_rounding = $four / $all * 100 - $four_percent;
			$three = (float)$this->count_ratings( $product_id, 3 );
			$three_percent = floor( $three / $all * 100 );
			$three_rounding = $three / $all * 100 - $three_percent;
			$two = (float)$this->count_ratings( $product_id, 2 );
			$two_percent = floor( $two / $all * 100 );
			$two_rounding = $two / $all * 100 - $two_percent;
			$one = (float)$this->count_ratings( $product_id, 1 );
			$one_percent = floor( $one / $all * 100 );
			$one_rounding = $one / $all * 100 - $one_percent;
			// $hundred = $five_percent + $four_percent + $three_percent + $two_percent + $one_percent;
			// if( $hundred < 100 ) {
			// 	$to_distribute = 100 - $hundred;
			// 	$roundings = array( '5' => $five_rounding, '4' => $four_rounding, '3' => $three_rounding, '2' => $two_rounding, '1' => $one_rounding );
			// 	arsort($roundings);
			// 	error_log( print_r( $roundings, true ) );
			// }
			$average = 0;
			$product = wc_get_product( $product_id );
			if( $product ) {
				$average = $product->get_average_rating();
				// Polylang integration
				if( function_exists( 'pll_current_language' ) && function_exists( 'PLL' ) && apply_filters( 'cr_reviews_polylang_merge', true ) ) {
					global $polylang;
					$translationIds = PLL()->model->post->get_translations( $product_id );
					if( 0 < count( $translationIds ) ) {
						$average = ( 5 * $five + 4 * $four + 3 * $three + 2 * $two + 1 * $one ) / $all;
					}
				} elseif (
					has_filter( 'wpml_object_id' ) &&
					has_filter( 'wpml_is_comment_query_filtered' ) &&
					has_filter( 'wpml_element_trid' ) &&
					has_filter( 'wpml_get_element_translations' )
				) {
					// WPML integration
					$is_filtered = apply_filters( 'wpml_is_comment_query_filtered', true, $product_id );
					if( false === $is_filtered ) {
						$average = ( 5 * $five + 4 * $four + 3 * $three + 2 * $two + 1 * $one ) / $all;
					}
				}
			}
			$output = '';
			if ('yes' !== get_option('ivole_reviews_nobranding', 'yes')) {
				$output .= '<div class="cr-credits-div">';
				$output .= '<span>Powered by</span><a href="https://wordpress.org/plugins/customer-reviews-woocommerce/" target="_blank" alt="Customer Reviews for WooCommerce" title="Customer Reviews for WooCommerce"><img src="' . plugins_url( '/img/logo-vs.svg', dirname( dirname( __FILE__ ) ) ) . '" alt="CusRev"></a>';
				$output .= '</div>';
			}
			$output .= '<div class="cr-summaryBox-wrap">';
			$output .= '<div class="cr-overall-rating-wrap">';
			$output .= '<div class="cr-average-rating"><span>' . number_format_i18n( $average, 1 ) . '</span></div>';
			$output .= '<div class="cr-average-rating-stars"><div class="crstar-rating"><span style="width:' . ( $average / 5 * 100 ) . '%;"></span></div></div>';
			$output .= '<div class="cr-total-rating-count">' . sprintf( _n( 'Based on %s review', 'Based on %s reviews', $all, 'customer-reviews-woocommerce' ), number_format_i18n( $all ) ) . '</div>';
			if ( $new_reviews_allowed ) {
				$output .= '<button class="cr-ajax-reviews-add-review" type="button">' . __( 'Add a review', 'woocommerce' ) .'</button>';
			}
			$output .= '</div>';
			if( $is_ajax ) {
				$nonce = wp_create_nonce( "cr_product_reviews_filter_" . $product_id );
				$output .= '<div class="ivole-summaryBox cr-summaryBox-ajax" data-nonce="' . $nonce . '">';
			} else {
				$output .= '<div class="ivole-summaryBox">';
			}
			$output .= '<table id="ivole-histogramTable">';
			$output .= '<tbody>';
			$output .= '<tr class="ivole-histogramRow">';
			if( $five > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="ivole-histogram-a" data-rating="5" href="' . esc_url( add_query_arg( $this->ivrating, 5, get_permalink( $product_id ) ) ) . $tab_reviews . '" title="' . __( '5 star', 'customer-reviews-woocommerce' ) . '">' . __( '5 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="ivole-histogram-a" data-rating="5" href="' . esc_url( add_query_arg( $this->ivrating, 5, get_permalink( $product_id ) ) ) . $tab_reviews . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $five_percent . '%">' . $five_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="ivole-histogram-a" data-rating="5" href="' . esc_url( add_query_arg( $this->ivrating, 5, get_permalink( $product_id ) ) ) . $tab_reviews . '">' . (string)$five_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __( '5 star', 'customer-reviews-woocommerce' ) . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $five_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$five_percent . '%</td>';
			}
			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			if( $four > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="ivole-histogram-a" data-rating="4" href="' . esc_url( add_query_arg( $this->ivrating, 4, get_permalink( $product_id ) ) ) . $tab_reviews . '" title="' . __( '4 star', 'customer-reviews-woocommerce' ) . '">' . __( '4 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="ivole-histogram-a" data-rating="4" href="' . esc_url( add_query_arg( $this->ivrating, 4, get_permalink( $product_id ) ) ) . $tab_reviews . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $four_percent . '%">' . $four_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="ivole-histogram-a" data-rating="4" href="' . esc_url( add_query_arg( $this->ivrating, 4, get_permalink( $product_id ) ) ) . $tab_reviews . '">' . (string)$four_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __( '4 star', 'customer-reviews-woocommerce' ) . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $four_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$four_percent . '%</td>';
			}
			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			if( $three > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="ivole-histogram-a" data-rating="3" href="' . esc_url( add_query_arg( $this->ivrating, 3, get_permalink( $product_id ) ) ) . $tab_reviews . '" title="' . __( '3 star', 'customer-reviews-woocommerce' ) . '">' . __( '3 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="ivole-histogram-a" data-rating="3" href="' . esc_url( add_query_arg( $this->ivrating, 3, get_permalink( $product_id ) ) ) . $tab_reviews . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $three_percent . '%">' . $three_percent .'</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="ivole-histogram-a" data-rating="3" href="' . esc_url( add_query_arg( $this->ivrating, 3, get_permalink( $product_id ) ) ) . $tab_reviews . '">' . (string)$three_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __( '3 star', 'customer-reviews-woocommerce' ) . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $three_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$three_percent . '%</td>';
			}
			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			if( $two > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="ivole-histogram-a" data-rating="2" href="' . esc_url( add_query_arg( $this->ivrating, 2, get_permalink( $product_id ) ) ) . $tab_reviews . '" title="' . __( '2 star', 'customer-reviews-woocommerce' ) . '">' . __( '2 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="ivole-histogram-a" data-rating="2" href="' . esc_url( add_query_arg( $this->ivrating, 2, get_permalink( $product_id ) ) ) . $tab_reviews . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $two_percent . '%">' . $two_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="ivole-histogram-a" data-rating="2" href="' . esc_url( add_query_arg( $this->ivrating, 2, get_permalink( $product_id ) ) ) . $tab_reviews . '">' . (string)$two_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __( '2 star', 'customer-reviews-woocommerce' ) . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $two_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$two_percent . '%</td>';
			}
			$output .= '</tr>';
			$output .= '<tr class="ivole-histogramRow">';
			if( $one > 0 ) {
				$output .= '<td class="ivole-histogramCell1"><a class="ivole-histogram-a" data-rating="1" href="' . esc_url( add_query_arg( $this->ivrating, 1, get_permalink( $product_id ) ) ) . $tab_reviews . '" title="' . __( '1 star', 'customer-reviews-woocommerce' ) . '">' . __( '1 star', 'customer-reviews-woocommerce' ) . '</a></td>';
				$output .= '<td class="ivole-histogramCell2"><a class="ivole-histogram-a" data-rating="1" href="' . esc_url( add_query_arg( $this->ivrating, 1, get_permalink( $product_id ) ) ) . $tab_reviews . '"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $one_percent . '%">' . $one_percent . '</div></div></a></td>';
				$output .= '<td class="ivole-histogramCell3"><a class="ivole-histogram-a" data-rating="1" href="' . esc_url( add_query_arg( $this->ivrating, 1, get_permalink( $product_id ) ) ) . $tab_reviews . '">' . (string)$one_percent . '%</a></td>';
			} else {
				$output .= '<td class="ivole-histogramCell1">' . __( '1 star', 'customer-reviews-woocommerce' ) . '</td>';
				$output .= '<td class="ivole-histogramCell2"><div class="ivole-meter"><div class="ivole-meter-bar" style="width: ' . $one_percent . '%"></div></div></td>';
				$output .= '<td class="ivole-histogramCell3">' . (string)$one_percent . '%</td>';
			}
			$output .= '</tr>';
			$output .= '</tbody>';
			$output .= '</table>';
			$output .= '</div>';
			if( get_query_var( $this->ivrating ) ) {
				$rating = intval( get_query_var( $this->ivrating ) );
				if( $rating > 0 && $rating <= 5 ) {
					$filtered_comments = sprintf( esc_html( _n( 'Showing %1$d of %2$d review (%3$d star). ', 'Showing %1$d of %2$d reviews (%3$d star). ', $all, 'customer-reviews-woocommerce'  ) ), $this->count_ratings( $product_id, $rating ), $all, $rating );
					$all_comments = sprintf( esc_html( _n( 'See all %d review', 'See all %d reviews', $all, 'customer-reviews-woocommerce'  ) ), $all );
					$output .= '<div class="cr-count-filtered-reviews">' . $filtered_comments . '<a class="cr-seeAllReviews" href="' . esc_url( get_permalink( $product_id ) ) . $tab_reviews . '">' . $all_comments . '</a></div>';
				}
			}
			$output .= '</div>';
			echo $output;
		}
	}
	private function count_ratings( $product_id, $rating ) {
		$post_in = array();
		if( function_exists( 'pll_current_language' ) && function_exists( 'PLL' ) && apply_filters( 'cr_reviews_polylang_merge', true ) ) {
			// Polylang integration
			global $polylang;
			$translationIds = PLL()->model->post->get_translations( $product_id );
			foreach ( $translationIds as $key => $translationID ) {
				$post_in[] = $translationID;
			}
		} elseif (
			has_filter( 'wpml_object_id' ) &&
			has_filter( 'wpml_is_comment_query_filtered' ) &&
			has_filter( 'wpml_element_trid' ) &&
			has_filter( 'wpml_get_element_translations' )
		) {
			// WPML integration
			$is_filtered = false;
			if( wp_doing_ajax() ) {
				if( isset( $_COOKIE[CR_Ajax_Reviews::WPML_COOKIE] ) && 'no' === $_COOKIE[CR_Ajax_Reviews::WPML_COOKIE] ) {
					$is_filtered = false;
				} else {
					$is_filtered = true;
				}
			} else {
				$is_filtered = apply_filters( 'wpml_is_comment_query_filtered', true, $product_id );
			}
			if( false === $is_filtered ) {
				$trid = apply_filters( 'wpml_element_trid', NULL, $product_id, 'post_product' );
				if( $trid ) {
					$translations = apply_filters( 'wpml_get_element_translations', NULL, $trid, 'post_product' );
					if( $translations && is_array( $translations ) ) {
						foreach ($translations as $translation) {
							if( isset( $translation->element_id ) ) {
								$post_in[] = intval( $translation->element_id );
							}
						}
						global $sitepress;
						if ( $sitepress ) {
							remove_filter( 'comments_clauses', [ $sitepress, 'comments_clauses' ], 10 );
						}
					}
				}
			} else {
				$post_in = array( $product_id );
			}
		} else {
			$post_in = array( $product_id );
		}
		$args = array(
			'post__in' => $post_in,
			'post_status' => 'publish',
			'status' => 'approve',
			'parent' => 0,
			'count' => true,
			'type__not_in' => 'cr_qna'
		);
		if( 0 === $rating ) {
			$args['meta_query'][] = array(
				'key' => 'rating',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'numeric'
			);
		} else if( $rating > 0 ){
			$args['meta_query'][] = array(
				'key' => 'rating',
				'value'   => $rating,
				'compare' => '=',
				'type'    => 'numeric'
			);
		}
		return get_comments( $args );
	}
	public function add_query_var() {
		global $wp;
		$wp->add_query_var( $this->ivrating );
		$wp->add_query_var( 'crsearch' );
	}
	public function filter_comments2( $comment_args ) {
		global $post;
		if( get_post_type() === 'product' ) {
			if( get_query_var( $this->ivrating ) ) {
				$rating = intval( get_query_var( $this->ivrating ) );
				if( $rating > 0 && $rating <= 5 ) {
					$comment_args['meta_query'][] = array(
						'key' => 'rating',
						'value'   => $rating,
						'compare' => '=',
						'type'    => 'numeric'
					);
					$page = (int) get_query_var( 'cpage' );
					if ( $page ) {
						$comment_args['offset'] = ( $page - 1 ) * $comment_args['number'];
					} elseif ( 'oldest' === get_option( 'default_comments_page' ) ) {
						$comment_args['offset'] = 0;
					} else {
						// If fetching the first page of 'newest', we need a top-level comment count.
						$top_level_query = new WP_Comment_Query();
						$top_level_args  = array(
							'count'   => true,
							'orderby' => false,
							'post_id' => $post->ID,
							'status'  => 'approve',
							'meta_query' => $comment_args['meta_query']
						);

						if ( $comment_args['hierarchical'] ) {
							$top_level_args['parent'] = 0;
						}

						if ( isset( $comment_args['include_unapproved'] ) ) {
							$top_level_args['include_unapproved'] = $comment_args['include_unapproved'];
						}

						$top_level_count = $top_level_query->query( $top_level_args );
						if( isset( $comment_args['number'] ) && $comment_args['number'] > 0 ) {
							$comment_args['offset'] = ( ceil( $top_level_count / $comment_args['number'] ) - 1 ) * $comment_args['number'];
						} else {
							$comment_args['offset'] = 0;
						}
					}
				}
			}
		}
		return $comment_args;
	}
	public function vote_review_registered() {
		$comment_id = intval( $_POST['reviewID'] );
		$upvote = intval( $_POST['upvote'] );
		$registered_upvoters = get_comment_meta( $comment_id, 'ivole_review_reg_upvoters', true );
		$registered_downvoters = get_comment_meta( $comment_id, 'ivole_review_reg_downvoters', true );
		$current_user = get_current_user_id();
		// check if this registered user has already upvoted this review
		if( !empty( $registered_upvoters ) ) {
			$registered_upvoters = maybe_unserialize( $registered_upvoters );
			if( is_array( $registered_upvoters ) ) {
				$registered_upvoters_count = count( $registered_upvoters );
				$index_upvoters = -1;
				for($i = 0; $i < $registered_upvoters_count; $i++ ) {
					if( $current_user === $registered_upvoters[$i] ) {
						if( 0 < $upvote ) {
							// upvote request, exit because this user has already upvoted this review earlier
							$votes = $this->get_votes( $comment_id );
							wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
							return;
						} else {
							// downvote request, remove the upvote
							$index_upvoters = $i;
							break;
						}
					}
				}
				if( 0 <= $index_upvoters ) {
					array_splice( $registered_upvoters, $index_upvoters, 1 );
				}
			} else {
				$registered_upvoters = array();
			}
		} else {
			$registered_upvoters = array();
		}
		// check if this registered user has already downvoted this review
		if( !empty( $registered_downvoters ) ) {
			$registered_downvoters = maybe_unserialize( $registered_downvoters );
			if( is_array( $registered_downvoters ) ) {
				$registered_downvoters_count = count( $registered_downvoters );
				$index_downvoters = -1;
				for($i = 0; $i < $registered_downvoters_count; $i++ ) {
					if( $current_user === $registered_downvoters[$i] ) {
						if( 0 < $upvote ) {
							// upvote request, remove the downvote
							$index_downvoters = $i;
							break;
						} else {
							// downvote request, exit because this user has already downvoted this review earlier
							$votes = $this->get_votes( $comment_id );
							wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
							return;
						}
					}
				}
				if( 0 <= $index_downvoters ) {
					array_splice( $registered_downvoters, $index_downvoters, 1 );
				}
			} else {
				$registered_downvoters = array();
			}
		} else {
			$registered_downvoters = array();
		}

		//update arrays of registered upvoters and downvoters
		if( 0 < $upvote ) {
			$registered_upvoters[] = $current_user;
		} else {
			$registered_downvoters[] = $current_user;
		}

		update_comment_meta( $comment_id, 'ivole_review_reg_upvoters', $registered_upvoters );
		update_comment_meta( $comment_id, 'ivole_review_reg_downvoters', $registered_downvoters );
		$votes = $this->send_votes( $comment_id );
		// compatibility with W3 Total Cache plugin
		// clear DB cache to make sure that count of upvotes is immediately updated
		if( function_exists( 'w3tc_dbcache_flush' ) ) {
			w3tc_dbcache_flush();
		}
		wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
	}

	public function vote_review_unregistered() {
		$ip = $_SERVER['REMOTE_ADDR'];
		$comment_id = intval( $_POST['reviewID'] );
		$upvote = intval( $_POST['upvote'] );

		// check (via cookie) if this unregistered user has already upvoted this review
		if( isset( $_COOKIE['ivole_review_upvote'] ) ) {
			$upcomment_ids = json_decode( $_COOKIE['ivole_review_upvote'], true );
			if( is_array( $upcomment_ids ) ) {
				$upcomment_ids_count = count( $upcomment_ids );
				$index_upvoters = -1;
				for( $i = 0; $i < $upcomment_ids_count; $i++ ) {
					if( $comment_id === $upcomment_ids[$i] ) {
						if( 0 < $upvote ) {
							// upvote request, exit because this user has already upvoted this review earlier
							$votes = $this->get_votes( $comment_id );
							wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
						} else {
							// downvote request, remove the upvote
							$index_upvoters = $i;
							break;
						}
					}
				}
				if( 0 <= $index_upvoters ) {
					array_splice( $upcomment_ids, $index_upvoters, 1 );
				}
			} else {
				$upcomment_ids = array();
			}
		} else {
			$upcomment_ids = array();
		}

		// check (via cookie) if this unregistered user has already downvoted this review
		if( isset( $_COOKIE['ivole_review_downvote'] ) ) {
			$downcomment_ids = json_decode( $_COOKIE['ivole_review_downvote'], true );
			if( is_array( $downcomment_ids ) ) {
				$downcomment_ids_count = count( $downcomment_ids );
				$index_downvoters = -1;
				for( $i = 0; $i < $downcomment_ids_count; $i++ ) {
					if( $comment_id === $downcomment_ids[$i] ) {
						if( 0 < $upvote ) {
							// upvote request, remove the downvote
							$index_downvoters = $i;
							break;
						} else {
							// downvote request, exit because this user has already downvoted this review earlier
							$votes = $this->get_votes( $comment_id );
							wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
						}
					}
				}
				if( 0 <= $index_downvoters ) {
					array_splice( $downcomment_ids, $index_downvoters, 1 );
				}
			} else {
				$downcomment_ids = array();
			}
		} else {
			$downcomment_ids = array();
		}

		$unregistered_upvoters = get_comment_meta( $comment_id, 'ivole_review_unreg_upvoters', true );
		$unregistered_downvoters = get_comment_meta( $comment_id, 'ivole_review_unreg_downvoters', true );

		// check if this unregistered user has already upvoted this review
		if( !empty( $unregistered_upvoters ) ) {
			$unregistered_upvoters = maybe_unserialize( $unregistered_upvoters );
			if( is_array( $unregistered_upvoters ) ) {
				$unregistered_upvoters_count = count( $unregistered_upvoters );
				$index_upvoters = -1;
				for($i = 0; $i < $unregistered_upvoters_count; $i++ ) {
					if( $ip === $unregistered_upvoters[$i] ) {
						if( 0 < $upvote ) {
							// upvote request, exit because this user has already upvoted this review earlier
							$votes = $this->get_votes( $comment_id );
							wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
						} else {
							// downvote request, remove the upvote
							$index_upvoters = $i;
							break;
						}
					}
				}
				if( 0 <= $index_upvoters ) {
					array_splice( $unregistered_upvoters, $index_upvoters, 1 );
				}
			} else {
				$unregistered_upvoters = array();
			}
		} else {
			$unregistered_upvoters = array();
		}

		// check if this unregistered user has already downvoted this review
		if( !empty( $unregistered_downvoters ) ) {
			$unregistered_downvoters = maybe_unserialize( $unregistered_downvoters );
			if( is_array( $unregistered_downvoters ) ) {
				$unregistered_downvoters_count = count( $unregistered_downvoters );
				$index_downvoters = -1;
				for($i = 0; $i < $unregistered_downvoters_count; $i++ ) {
					if( $ip === $unregistered_downvoters[$i] ) {
						if( 0 < $upvote ) {
							// upvote request, remove the downvote
							$index_downvoters = $i;
							break;
						} else {
							// downvote request, exit because this user has already downvoted this review earlier
							$votes = $this->get_votes( $comment_id );
							wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
						}
					}
				}
				if( 0 <= $index_downvoters ) {
					array_splice( $unregistered_downvoters, $index_downvoters, 1 );
				}
			} else {
				$unregistered_downvoters = array();
			}
		} else {
			$unregistered_downvoters = array();
		}

		//update cookie arrays of unregistered upvoters and downvoters
		if( 0 < $upvote ) {
			$upcomment_ids[] = $comment_id;
			$unregistered_upvoters[] = $ip;
		} else {
			$downcomment_ids[] = $comment_id;
			$unregistered_downvoters[] = $ip;
		}
		setcookie( 'ivole_review_upvote', json_encode( $upcomment_ids ), time() + 365*24*60*60, COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'ivole_review_downvote', json_encode( $downcomment_ids ), time() + 365*24*60*60, COOKIEPATH, COOKIE_DOMAIN );
		update_comment_meta( $comment_id, 'ivole_review_unreg_upvoters', $unregistered_upvoters );
		update_comment_meta( $comment_id, 'ivole_review_unreg_downvoters', $unregistered_downvoters );
		$votes = $this->send_votes( $comment_id );
		// compatibility with W3 Total Cache plugin
		// clear DB cache to make sure that count of upvotes is immediately updated
		if( function_exists( 'w3tc_dbcache_flush' ) ) {
			w3tc_dbcache_flush();
		}
		wp_send_json( array( 'code' => 0, 'votes' => $votes ) );
	}
	public function get_votes( $comment_id ) {
		$r_upvotes = 0;
		$r_downvotes = 0;
		$u_upvotes = 0;
		$u_downvotes = 0;
		$current = 0;
		$registered_upvoters = get_comment_meta( $comment_id, 'ivole_review_reg_upvoters', true );
		$registered_downvoters = get_comment_meta( $comment_id, 'ivole_review_reg_downvoters', true );
		$unregistered_upvoters = get_comment_meta( $comment_id, 'ivole_review_unreg_upvoters', true );
		$unregistered_downvoters = get_comment_meta( $comment_id, 'ivole_review_unreg_downvoters', true );

		if( !empty( $registered_upvoters ) ) {
			$registered_upvoters = maybe_unserialize( $registered_upvoters );
			if( is_array( $registered_upvoters ) ) {
				$r_upvotes = count( $registered_upvoters );
			}
		}

		if( !empty( $registered_downvoters ) ) {
			$registered_downvoters = maybe_unserialize( $registered_downvoters );
			if( is_array( $registered_downvoters ) ) {
				$r_downvotes = count( $registered_downvoters );
			}
		}

		if( !empty( $unregistered_upvoters ) ) {
			$unregistered_upvoters = maybe_unserialize( $unregistered_upvoters );
			if( is_array( $unregistered_upvoters ) ) {
				$u_upvotes = count( $unregistered_upvoters );
			}
		}

		if( !empty( $unregistered_downvoters ) ) {
			$unregistered_downvoters = maybe_unserialize( $unregistered_downvoters );
			if( is_array( $unregistered_downvoters ) ) {
				$u_downvotes = count( $unregistered_downvoters );
			}
		}

		$current_user = get_current_user_id();
		if( $current_user ) {
			if( is_array( $registered_upvoters ) ) {
				$r_upvoters_flip = array_flip( $registered_upvoters );
				if( isset( $r_upvoters_flip[$current_user] ) ) {
					$current = 1;
				}
			}
			if( 0 === $current && is_array( $registered_downvoters ) ) {
				$r_downvoters_flip = array_flip( $registered_downvoters );
				if( isset( $r_downvoters_flip[$current_user] ) ) {
					$current = -1;
				}
			}
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
			if( is_array( $unregistered_upvoters ) ) {
				$u_upvoters_flip = array_flip( $unregistered_upvoters );
				if( isset( $u_upvoters_flip[$ip] ) ) {
					$current = 1;
				}
			}
			if( 0 === $current && is_array( $unregistered_downvoters ) ) {
				$u_downvoters_flip = array_flip( $unregistered_downvoters );
				if( isset( $u_downvoters_flip[$ip] ) ) {
					$current = -1;
				}
			}
			if( 0 === $current ) {
				if( isset( $_COOKIE['ivole_review_upvote'] ) ) {
					$upcomment_ids = json_decode( $_COOKIE['ivole_review_upvote'], true );
					if( is_array( $upcomment_ids ) ) {
						$upcomment_ids_flip = array_flip( $upcomment_ids );
						if( isset( $upcomment_ids_flip[$comment_id] ) ) {
							$current = 1;
						}
					}
					if( 0 === $current ) {
						$downcomment_ids = json_decode( $_COOKIE['ivole_review_downvote'], true );
						if( is_array( $downcomment_ids ) ) {
							$downcomment_ids_flip = array_flip( $downcomment_ids );
							if( isset( $downcomment_ids_flip[$comment_id] ) ) {
								$current = -1;
							}
						}
					}
				}
			}
		}

		$votes = array(
			'upvotes' => $r_upvotes + $u_upvotes,
			'downvotes' => $r_downvotes + $u_downvotes,
			'total' => $r_upvotes + $r_downvotes + $u_upvotes + $u_downvotes,
			'current' => $current
		);
		return $votes;
	}
	public function send_votes( $comment_id ) {
		$comment = get_comment( $comment_id );
		if( $comment ) {
			$votes = $this->get_votes( $comment_id );
			update_comment_meta( $comment_id, 'ivole_review_votes', $votes['upvotes'] - $votes['downvotes'] );
			$product_id = $comment->comment_post_ID;
			//clear WP Super Cache after voting
			if( function_exists( 'wpsc_delete_post_cache' ) ) {
				wpsc_delete_post_cache( $product_id );
			}
			//clear W3TC after voting
			if( function_exists( 'w3tc_flush_post' ) ) {
				w3tc_flush_post( $product_id );
			}
			if( Ivole::is_curl_installed() ) {
				$order_id = get_comment_meta( $comment_id, 'ivole_order', true );
				$order = wc_get_order( $order_id );
				if ( $order_id && $order ) {
					$secret_key = $order->get_meta( 'ivole_secret_key', true );
					if( '' !== $secret_key ) {
						$data = array(
							'token' => '164592f60fbf658711d47b2f55a1bbba',
							'secretKey' => $secret_key,
							'shop' => array( 'domain' => Ivole_Email::get_blogurl(),
							'orderId' => $order_id,
							'productId' => $product_id ),
							'upvotes' => $votes['upvotes'],
							'downvotes' => $votes['total'] - $votes['upvotes']
						);
						$api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/review-vote';
						$data_string = json_encode( $data );
						$ch = curl_init();
						curl_setopt( $ch, CURLOPT_URL, $api_url );
						curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
						curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
						curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
						curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
							'Content-Type: application/json',
							'Content-Length: ' . strlen( $data_string ) )
						);
						$result = curl_exec( $ch );
					}
				}
			}
			return $votes;
		}
		return 0;
	}

	public function compatibility_reviews( $located, $template_name, $args, $template_path, $default_path ) {
		if( 'single-product/review.php' === $template_name ) {
			$replacement_path = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'templates/review-compat.php';
			if( is_file( $replacement_path ) ) {
				$located = $replacement_path;
				//error_log( print_r( $replacement_path, true ) );
			}
		}
		return $located;
	}

	public function display_verified_badge( $comment ) {
		if( 0 === intval( $comment->comment_parent ) ) {
			$output = '';
			// check if a badge should be shown for the review
			$product_id = $comment->comment_post_ID;
			$order_id = get_comment_meta( $comment->comment_ID, 'ivole_order', true );
			// WPML integration
			if ( has_filter( 'wpml_object_id' ) ) {
				$wpml_def_language = apply_filters( 'wpml_default_language', null );
				$original_product_id = apply_filters( 'wpml_object_id', $product_id, 'product', true, $wpml_def_language );
				$product_id = $original_product_id;
			}
			if( '' !== $order_id && 'yes' === get_option( 'ivole_verified_links', 'no' ) ) {
				// prepare language suffix to insert into cusrev.com link
				$l_suffix = '';
				$site_lang = '';
				if( 'en' !== $this->lang ) {
					$l_suffix = '-' . $this->lang;
					$site_lang = $this->lang . '/';
				}
				//
				$output = '<img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) );
				$output .= '/img/shield-20.png" alt="' . __( 'Verified review', 'customer-reviews-woocommerce' ) . '" class="ivole-verified-badge-icon">';
				$output .= '<span class="ivole-verified-badge-text">';
				$output .= __( 'Verified review', 'customer-reviews-woocommerce' );
				// URL is different for product reviews and shop reviews. Need to check if this is a shop review.
				$shop_page_id = wc_get_page_id( 'shop' );
				if( intval( $shop_page_id ) === intval( $product_id ) ) {
					$output .= ' - <a href="https://www.cusrev.com/' . $site_lang . 'reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '/s/r-' . $order_id;
				} else {
					$output .= ' - <a href="https://www.cusrev.com/' . $site_lang . 'reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '/p/p-' . $product_id . '/r-' . $order_id;
				}
				$output .= '" title="" target="_blank" rel="nofollow noopener">';
				$output .= __( 'view original', 'customer-reviews-woocommerce' ) . '</a>';
				$output .= '<img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" alt="' . __( 'External link', 'customer-reviews-woocommerce' ) . '" class="ivole-verified-badge-ext-icon"></span>';
			}

			// check if country/region should be shown for the review
			$country = get_comment_meta( $comment->comment_ID, 'ivole_country', true );
			if( is_array( $country ) && 2 === count( $country ) ) {
				$country_string = '';
				if( isset( $country['code'] ) ) {
					if( strlen( $output ) > 0 ) {
						$output .= '<span class="ivole-review-country-space">&emsp;|&emsp;</span>';
					}
					$output .= '<img src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/flags/' . $country['code'] . '.svg" class="ivole-review-country-icon" alt="' . $country['code'] . '">';
					if( isset( $country['desc'] ) ) {
						$output .= '<span class="ivole-review-country-text">' . $country['desc'] . '</span>';
					}
				}
			}
			// if there is something to print, print it
			if( strlen( $output ) > 0 ) {
				echo '<p class="ivole-verified-badge">' . $output . '</p>';
			}
		}
	}

	public function display_verified_badge_only( $comment ) {
		if( 0 === intval( $comment->comment_parent ) ) {
			$output = '';
			// check if a badge should be shown for the review
			$product_id = $comment->comment_post_ID;
			$order_id = get_comment_meta( $comment->comment_ID, 'ivole_order', true );
			// WPML integration
			if ( has_filter( 'wpml_object_id' ) ) {
				$wpml_def_language = apply_filters( 'wpml_default_language', null );
				$original_product_id = apply_filters( 'wpml_object_id', $product_id, 'product', true, $wpml_def_language );
				$product_id = $original_product_id;
			}
			if( '' !== $order_id && 'yes' === get_option( 'ivole_verified_links', 'no' ) ) {
				// prepare language suffix to insert into cusrev.com link
				$l_suffix = '';
				$site_lang = '';
				if( 'en' !== $this->lang ) {
					$l_suffix = '-' . $this->lang;
					$site_lang = $this->lang . '/';
				}
				//
				$output = '<img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) );
				$output .= '/img/shield-20.png" alt="' . __( 'Verified review', 'customer-reviews-woocommerce' ) . '" class="ivole-verified-badge-icon">';
				$output .= '<span class="ivole-verified-badge-text">';
				$output .= __( 'Verified review', 'customer-reviews-woocommerce' );
				// URL is different for product reviews and shop reviews. Need to check if this is a shop review.
				$shop_page_id = wc_get_page_id( 'shop' );
				if( intval( $shop_page_id ) === intval( $product_id ) ) {
					$output .= ' - <a href="https://www.cusrev.com/' . $site_lang . 'reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '/s/r-' . $order_id;
				} else {
					$output .= ' - <a href="https://www.cusrev.com/' . $site_lang . 'reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '/p/p-' . $product_id . '/r-' . $order_id;
				}
				$output .= '" title="" target="_blank" rel="nofollow noopener">';
				$output .= __( 'view original', 'customer-reviews-woocommerce' ) . '</a>';
				$output .= '<img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" alt="' . __( 'External link', 'customer-reviews-woocommerce' ) . '" class="ivole-verified-badge-ext-icon"></span>';
			}
			// if there is something to print, print it
			if( strlen( $output ) > 0 ) {
				echo '<p class="ivole-verified-badge">' . $output . '</p>';
			}
		}
	}

	public function display_featured( $comment ) {
		if( 0 === intval( $comment->comment_parent ) ) {
			if( 0 < $comment->comment_karma ) {
				// display 'featured' badge
				$output = __( 'Featured Review', 'customer-reviews-woocommerce' );
				echo '<p class="cr-featured-badge"><span>' . $output . '</span></p>';
			}
		}
	}

	public function display_custom_questions( $comment ) {
		if( 0 === intval( $comment->comment_parent ) ) {
			$custom_questions = new CR_Custom_Questions();
			$custom_questions->read_questions( $comment->comment_ID );
			$custom_questions->output_questions( true );
		}
	}

	public function cusrev_review_meta( $comment ) {
		$template = wc_locate_template(
			'review-meta.php',
			'customer-reviews-woocommerce',
			__DIR__ . '/../../templates/'
		);
		include( $template );
		remove_action( 'woocommerce_review_meta', 'woocommerce_review_display_meta', 10 );
	}

	public function display_review_media_top_prd( $reviews ) {
		echo self::display_review_images_top( $reviews[0] );
	}

	public static function display_review_images_top( $comments ) {
		$pics_prepared = array();
		$cr_query = '?crsrc=wp';
		$images_top = '';
		$max_count_top = self::get_max_top_images();

		foreach( $comments as $comment ) {
			$pics = get_comment_meta( $comment->comment_ID, self::REVIEWS_META_IMG );
			$pics_local = get_comment_meta( $comment->comment_ID, self::REVIEWS_META_LCL_IMG );
			$pics_v = get_comment_meta( $comment->comment_ID, self::REVIEWS_META_VID );
			$pics_v_local = get_comment_meta( $comment->comment_ID, self::REVIEWS_META_LCL_VID );
			$pics_n = count( $pics );
			$pics_local_n = count( $pics_local );
			$pics_v_n = count( $pics_v );
			$pics_v_local_n = count( $pics_v_local );
			$img_label_counter = 1;
			for( $i = 0; $i < $pics_n; $i ++) {
				if ( isset( $pics[$i]['url'] ) ) {
					$pics_prepared[] = array(
						$pics[$i]['url'] . $cr_query,
						$comment,
						0, // width
						0, // height
						0, // a flag for images
						sprintf( __( 'Image #%1$d from %2$s', 'customer-reviews-woocommerce' ), $img_label_counter, $comment->comment_author )
					);
					$img_label_counter++;
				}
			}
			for( $i = 0; $i < $pics_local_n; $i ++) {
				$attachmentSrc = wp_get_attachment_image_src( $pics_local[$i], apply_filters( 'cr_topreviews_image_size', 'large' ) );
				if ( $attachmentSrc ) {
					$pics_prepared[] = array(
						$attachmentSrc[0],
						$comment,
						$attachmentSrc[1], // width
						$attachmentSrc[2], // height
						0,                 // a flag for images
						sprintf( __( 'Image #%1$d from %2$s', 'customer-reviews-woocommerce' ), $img_label_counter, $comment->comment_author )
					);
					$img_label_counter++;
				}
			}
			$vid_label_counter = 1;
			for( $i = 0; $i < $pics_v_n; $i ++) {
				if ( isset( $pics_v[$i]['url'] ) ) {
					$pics_prepared[] = array(
						$pics_v[$i]['url'] . $cr_query,
						$comment,
						0, // width
						0, // height
						1, // a flag for videos
						sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $vid_label_counter, $comment->comment_author )
					);
					$vid_label_counter++;
				}
			}
			for( $i = 0; $i < $pics_v_local_n; $i ++) {
				$attachmentUrl = wp_get_attachment_url( $pics_v_local[$i] );
				if ( $attachmentUrl ) {
					$pics_prepared[] = array(
						$attachmentUrl,
						$comment,
						0, // width
						0, // height
						1, // a flag for images
						sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $vid_label_counter, $comment->comment_author )
					);
					$vid_label_counter++;
				}
			}
			if ( $max_count_top <= count( $pics_prepared ) ) {
				// there are sufficient media files already, stop the loop
				break;
			}
		}
		$count = count( $pics_prepared );
		if ( 0 < $count ) :
			wp_enqueue_script( 'cr-reviews-slider' );
			$images_top .= '<div class="cr-ajax-reviews-cus-images-div">';
			$images_top .= '<p class="cr-ajax-reviews-cus-images-title">' . esc_html__( 'Customer Images', 'customer-reviews-woocommerce' ) . '</p>';
			$images_top .= '<div class="cr-ajax-reviews-cus-images-div2">';
			// show the first ten or less pictures only
			$count_top = $count > $max_count_top - 1 ? $max_count_top : $count;
			for( $i = 0; $i < $count_top; $i ++) {
				$output = '';
				$output_w = ( $pics_prepared[$i][2] > 0 ) ? ' width="' . $pics_prepared[$i][2] . '"' : '';
				$output_h = ( $pics_prepared[$i][3] > 0 ) ? ' height="' . $pics_prepared[$i][3] . '"' : '';
				$output_wh = ( $output_w && $output_h ) ? $output_w . $output_h : '';
				$output .= '<div class="cr-comment-image-top">';
				if ( 1 === $pics_prepared[$i][4] ) {
					// video
					$output .= '<video class="cr-comment-image-top-item" preload="metadata" data-slide="' . $i . '" src="' . $pics_prepared[$i][0] . '"></video>';
					$output .= '<img class="cr-comment-videoicon" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/video.svg" ';
					$output .= 'alt="' . esc_attr( $pics_prepared[$i][5] ) . '">';
				} else {
					// images
					$output .= '<img class="cr-comment-image-top-item" data-slide="' . $i . '" src="' . $pics_prepared[$i][0] . '"' . $output_wh . ' alt="' . esc_attr( $pics_prepared[$i][5] ) . '" loading="lazy">';
				}
				$output .= '</div>';
				$images_top .= $output;
			}
			$nav_slides_to_show = $count > 2 ? 3 : $count;
			$main_slider_settings = array(
				'slidesToShow' => 1,
				'slidesToScroll' => 1,
				'arrows' => false,
				'fade' => true,
				'asNavFor' => '.cr-ajax-reviews-cus-images-slider-nav'
			);
			$dots = ( 15 < $count ) ? false : true;
			$nav_slider_settings = array(
				'slidesToShow' => $nav_slides_to_show,
				'slidesToScroll' => 1,
				'centerMode' => true,
				'dots' => $dots,
				'focusOnSelect' => true,
				'asNavFor' => '.cr-ajax-reviews-cus-images-slider-main',
				'respondTo' => 'min',
				'responsive' => array(
					array(
						'breakpoint' => 600,
						'settings' => array(
							'centerMode' => true,
							'centerPadding' => '30px',
							'slidesToShow' => $nav_slides_to_show
						)
					),
					array(
						'breakpoint' => 415,
						'settings' => array(
							'centerMode' => true,
							'centerPadding' => '35px',
							'slidesToShow' => $nav_slides_to_show
						)
					),
					array(
						'breakpoint' => 320,
						'settings' => array(
							'centerMode' => true,
							'centerPadding' => '40px',
							'slidesToShow' => $nav_slides_to_show
						)
					)
				)
			);
			if ( is_rtl() ) {
				$main_slider_settings['rtl'] = true;
				$nav_slider_settings['rtl'] = true;
			}
			$images_top .= '</div>';
			$images_top .= '</div>';
			$images_top .= '<div class="cr-ajax-reviews-cus-images-modal-cont">';
			$images_top .= '<div class="cr-ajax-reviews-cus-images-modal">';
			$images_top .= '<div class="cr-ajax-reviews-cus-images-hdr">';
			$images_top .= '<button class="cr-ajax-reviews-cus-images-close">';
			$images_top .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path class="cr-no-icon" d="M12.12 10l3.53 3.53-2.12 2.12L10 12.12l-3.54 3.54-2.12-2.12L7.88 10 4.34 6.46l2.12-2.12L10 7.88l3.54-3.53 2.12 2.12z"/></g></svg>';
			$images_top .= '</button>';
			$images_top .= '</div>';
			$images_top .= '<div class="cr-ajax-reviews-cus-images-slider-main cr-reviews-slider" data-slick=' . wp_json_encode( $main_slider_settings ) . '>';
			for( $i = 0; $i < $count; $i ++) {
				$ratingr = intval( get_comment_meta( $pics_prepared[$i][1]->comment_ID, 'rating', true ) );
				$output = '<div class="cr-ajax-reviews-slide-main"><div class="cr-ajax-reviews-slide-main-flex">';
				if ( 1 === $pics_prepared[$i][4] ) {
					// video
					$output .= '<div class="cr-ajax-reviews-video">';
					$output .= '<video preload="metadata" data-slide="' . $i . '" src="' . $pics_prepared[$i][0] . '"></video>';
					$output .= '<img class="cr-comment-videoicon" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/video.svg" ';
					$output .= 'alt="' . esc_attr( $pics_prepared[$i][5] ) . '">';
					$output .= '</div>';
				} else {
					// images
					$output .= '<img src="' . $pics_prepared[$i][0] . '" alt="' . esc_attr( $pics_prepared[$i][5] ) . '" loading="lazy">';
				}
				$output .= '<div class="cr-ajax-reviews-slide-main-comment">';
				$output .= wc_get_rating_html( $ratingr );
				$output .= '<p><strong class="woocommerce-review__author">' . esc_html( $pics_prepared[$i][1]->comment_author ) .'</strong></p>';
				$output .= '<time class="woocommerce-review__published-date" datetime="' . esc_attr( mysql2date( 'c', $pics_prepared[$i][1]->comment_date ) ) . '">' . esc_html( mysql2date( wc_date_format(), $pics_prepared[$i][1]->comment_date ) ) . '</time>';
				// WPML integration for translation of reviews
				if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
					ob_start();
					do_action( 'woocommerce_review_before', $pics_prepared[$i][1] );
					ob_end_clean();
				}
				$output .= '<p class="cr-ajax-reviews-slide-main-comment-body">' . $pics_prepared[$i][1]->comment_content . '</p>';
				$output .= apply_filters( 'cr_reviews_media_voting', '', $pics_prepared[$i][1] );
				$output .= '</div></div></div>';
				$images_top .= $output;
			}
			$images_top .= '</div>';
			$images_top .= '<div class="cr-ajax-reviews-cus-images-slider-nav cr-reviews-slider" data-slick=' . wp_json_encode( $nav_slider_settings ) . '>';
			for( $i = 0; $i < $count; $i ++) {
				$output = '<div class="cr-ajax-reviews-slide-nav">';
				if ( 1 === $pics_prepared[$i][4] ) {
					// video
					$output .= '<video preload="metadata" data-slide="' . $i . '" src="' . $pics_prepared[$i][0] . '"></video>';
					$output .= '<img class="cr-comment-videoicon" src="' . plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/video.svg" ';
					$output .= 'alt="' . esc_attr( $pics_prepared[$i][5] ) . '">';
				} else {
					// images
					$output .= '<img src="' . $pics_prepared[$i][0] . '" alt="' . esc_attr( $pics_prepared[$i][5] ) . '" loading="lazy">';
				}
				$output .= '</div>';
				$images_top .= $output;
			}
			$images_top .= '</div>';
			$images_top .= '</div>';
			$images_top .= '</div>';
		endif;
		return $images_top;
	}

	public function display_media_voting( $output, $comment ) {
		if ( $this->reviews_voting ) {
			ob_start();
			$this->display_voting_buttons( $comment );
			$vote_output = ob_get_contents();
			ob_end_clean();
			$output = "<div class='cr-vote'>" . $vote_output . "</div>";
		}
		return $output;
	}

	public function cr_photoswipe() {
		if( is_product() ) {
			if ( ! $this->disable_lightbox && ! current_theme_supports( 'wc-product-gallery-lightbox' ) ) {
				wc_get_template(
					'cr-photoswipe.php',
					array(),
					'customer-reviews-woocommerce',
					dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/'
				);
			}
		}
	}

	private static function is_captcha_enabled() {
		return 'yes' === get_option( 'ivole_enable_captcha', 'no' );
	}

	private static function captcha_site_key() {
		return get_option( 'ivole_captcha_site_key', '' );
	}

	public function new_ajax_upload() {
		$return = array(
			'code' => 100,
			'message' => ''
		);
		if( check_ajax_referer( 'cr-upload-images-frontend', 'cr_nonce', false ) ) {
			// check captcha
			if( self::is_captcha_enabled() ) {
				$captcha_is_wrong = true;
				if( isset( $_POST['cr_captcha'] ) && $_POST['cr_captcha'] ) {
					if( $this->ping_captcha( strval( $_POST['cr_captcha'] ) ) ) {
						$captcha_is_wrong = false;
					}
				}
				if( $captcha_is_wrong ) {
					$return['code'] = 504;
					$return['message'] = __( 'Error: please solve the CAPTCHA before uploading files', 'customer-reviews-woocommerce' );
					wp_send_json( $return );
					return;
				}
			}
			if( isset( $_FILES ) && is_array( $_FILES ) && 0 < count( $_FILES ) ) {
				// check the file size
				if ( $this->limit_file_size < $_FILES['cr_file']['size'] ) {
					$return['code'] = 501;
					$return['message'] = __( 'Error: the file(s) is too large', 'customer-reviews-woocommerce' );
					wp_send_json( $return );
					return;
				}
				// check the file type
				$file_name_parts = explode( '.', $_FILES['cr_file']['name'] );
				$file_ext = $file_name_parts[ count( $file_name_parts ) - 1 ];
				if( ! self::is_valid_file_type( $file_ext ) ) {
					$return['code'] = 502;
					$return['message'] = __( 'Error: accepted file types are PNG, JPG, JPEG, GIF, MP4, MPEG, OGG, WEBM, MOV, AVI', 'customer-reviews-woocommerce' );
					wp_send_json( $return );
					return;
				}
				// upload the file
				$post_id = $_POST['cr_postid'] ? $_POST['cr_postid'] : 0;
				$attachmentId = media_handle_upload( 'cr_file', $post_id );
				if( !is_wp_error( $attachmentId ) ) {
					$upload_key = bin2hex( openssl_random_pseudo_bytes( 10 ) );
					if( false !== update_post_meta( $attachmentId, 'cr-upload-temp-key', $upload_key ) ) {
						$return['attachment'] = array(
							'id' => $attachmentId,
							'key' => $upload_key,
							'nonce' => wp_create_nonce( 'cr-upload-images-delete' )
						);
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
		} else {
			$return['code'] = 500;
			$return['message'] = 'Error: nonce validation failed. Please refresh the page and try again.';
		}
		wp_send_json( $return );
	}

	public function new_ajax_delete() {
		$return = array(
			'code' => 100,
			'message' => '',
			'class' => ''
		);
		if( check_ajax_referer( 'cr-upload-images-delete', 'cr_nonce', false ) ) {
			if( isset( $_POST['image'] ) && $_POST['image'] ) {
				$image_decoded = json_decode( stripslashes( $_POST['image'] ), true );
				if( $image_decoded && is_array( $image_decoded ) ) {
					if( isset( $image_decoded["id"] ) && $image_decoded["id"] ) {
						if( isset( $image_decoded["key"] ) && $image_decoded["key"] ) {
							$attachmentId = intval( $image_decoded["id"] );
							if( 'attachment' === get_post_type( $attachmentId ) ) {
								if( $image_decoded["key"] === get_post_meta( $attachmentId, 'cr-upload-temp-key', true ) ) {
									if( wp_delete_attachment( $attachmentId, true ) ) {
										$return['code'] = 200;
										$return['message'] = 'OK';
										$return['class'] = $_POST['class'];
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
		} else {
			$return['code'] = 500;
			$return['message'] = 'Error: nonce validation failed.';
		}
		wp_send_json( $return );
	}

	public function custom_avatars() {
		if ( 'initials' === get_option( 'ivole_avatars', 'standard' ) ) {
			add_filter( 'get_avatar', array( $this, 'get_avatar' ), 10, 5 );
		}
	}

	public static function callback_comments( $comment, $args, $depth ) {
		$GLOBALS['comment'] = $comment;
		wc_get_template(
			'cr-review.php',
			array(
				'comment' => $comment,
				'args'    => $args,
				'depth'   => $depth,
			),
			'customer-reviews-woocommerce',
			dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/'
		);
	}

	public function get_avatar( $avatar, $id_or_email, $size = 96, $default = '', $alt = '' ) {
		return CR_Reviews_Grid::cr_get_avatar( $avatar, $id_or_email, $size, $default, $alt );
	}

	public function show_count_row( $count, $page, $per_page ) {
		$count_wording = CR_All_Reviews::get_count_wording( $count, $page, $per_page, false, 0, 0 );
		?>
			<div class="cr-count-row">
				<div class="cr-count-row-count">
					<?php echo esc_html( $count_wording ); ?>
				</div>
				<div class="cr-ajax-reviews-sort-div">
					<select name="cr_ajax_reviews_sort" class="cr-ajax-reviews-sort" aria-label="' . esc_attr__( 'Sort reviews', 'customer-reviews-woocommerce' ) . '">
						<option value="recent"<?php if( 'recent' === CR_Ajax_Reviews::get_sort() ) { echo ' selected="selected"'; } ?>>
							<?php echo __( 'Most Recent', 'customer-reviews-woocommerce' ); ?>
						</option>
						<?php if ( $this->reviews_voting ) : ?>
							<option value="helpful"<?php if( 'helpful' === CR_Ajax_Reviews::get_sort() ) { echo ' selected="selected"'; } ?>>
								<?php echo __( 'Most Helpful', 'customer-reviews-woocommerce' ); ?>
							</option>
						<?php endif; ?>
						<option value="ratinghigh"<?php if( 'ratinghigh' === CR_Ajax_Reviews::get_sort() ) { echo ' selected="selected"'; } ?>>
							<?php echo __( 'Highest Rating', 'customer-reviews-woocommerce' ); ?>
						</option>
						<option value="ratinglow"<?php if( 'ratinglow' === CR_Ajax_Reviews::get_sort() ) { echo ' selected="selected"'; } ?>>
							<?php echo __( 'Lowest Rating', 'customer-reviews-woocommerce' ); ?>
						</option>
					</select>
				</div>
			</div>
		<?php
	}

	public function clear_trustbadge_cache( $comment_id, $comment_approved, $commentdata ) {
		if (
			$commentdata &&
			is_array( $commentdata ) &&
			isset( $commentdata['comment_type'] ) &&
			'review' === $commentdata['comment_type']
		) {
			// clear store stats for Trust Badges
			delete_option( 'ivole_store_stats' );
		}
	}

	public static function get_star_rating_html( $rating, $count = 0 ) {
		$html = '<span style="width:' . ( ( $rating / 5 ) * 100 ) . '%">';

		if ( 0 < $count ) {
			/* translators: 1: rating 2: rating count */
			$html .= sprintf( _n( 'Rated %1$s out of 5 based on %2$s customer rating', 'Rated %1$s out of 5 based on %2$s customer ratings', $count, 'woocommerce' ), '<strong class="rating">' . esc_html( $rating ) . '</strong>', '<span class="rating">' . esc_html( $count ) . '</span>' );
		} else {
			/* translators: %s: rating */
			$html .= sprintf( esc_html__( 'Rated %s out of 5', 'woocommerce' ), '<strong class="rating">' . esc_html( $rating ) . '</strong>' );
		}

		$html .= '</span>';

		return apply_filters( 'cr_get_star_rating_html', $html, $rating, $count );
	}

	public function show_nosummary( $product_id ) {
		$average = 0;
		$product = wc_get_product( $product_id );
		if( $product ) {
			$average = $product->get_average_rating();
		}
		?>
		<div class="cr-ajax-reviews-nosummary">
			<div class="cr-nosummary-rating-cnt">
				<svg width="44" height="40" viewBox="0 0 44 40" fill="none" xmlns="http://www.w3.org/2000/svg">
					<mask id="path-1-inside-1_101_2" fill="white">
						<path d="M20.7076 0.577256C21.0991 -0.192419 22.234 -0.192419 22.6255 0.577256L28.4924 12.085C28.6488 12.3909 28.9499 12.6028 29.2983 12.6515L42.4162 14.4975C43.2937 14.6211 43.6437 15.6656 43.0096 16.264L33.5161 25.2219C33.2645 25.4595 33.1489 25.8028 33.2092 26.1383L35.4494 38.7869C35.6 39.6331 34.6826 40.2785 33.897 39.8785L22.1648 33.9076C21.853 33.749 21.4801 33.749 21.1683 33.9076L9.43531 39.8785C8.65055 40.2785 7.73311 39.6331 7.8837 38.7869L10.124 26.1383C10.1834 25.8028 10.0686 25.4595 9.81628 25.2219L0.32434 16.264C-0.310626 15.6656 0.0394195 14.6211 0.916977 14.4975L14.0356 12.6515C14.3832 12.6028 14.6851 12.3909 14.8407 12.085L20.7076 0.577256Z"/>
					</mask>
					<path d="M20.7076 0.577256C21.0991 -0.192419 22.234 -0.192419 22.6255 0.577256L28.4924 12.085C28.6488 12.3909 28.9499 12.6028 29.2983 12.6515L42.4162 14.4975C43.2937 14.6211 43.6437 15.6656 43.0096 16.264L33.5161 25.2219C33.2645 25.4595 33.1489 25.8028 33.2092 26.1383L35.4494 38.7869C35.6 39.6331 34.6826 40.2785 33.897 39.8785L22.1648 33.9076C21.853 33.749 21.4801 33.749 21.1683 33.9076L9.43531 39.8785C8.65055 40.2785 7.73311 39.6331 7.8837 38.7869L10.124 26.1383C10.1834 25.8028 10.0686 25.4595 9.81628 25.2219L0.32434 16.264C-0.310626 15.6656 0.0394195 14.6211 0.916977 14.4975L14.0356 12.6515C14.3832 12.6028 14.6851 12.3909 14.8407 12.085L20.7076 0.577256Z" fill="#F4DB6B" stroke="#F5CD5B" stroke-width="2" mask="url(#path-1-inside-1_101_2)"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M15.5958 36.7433L24.2822 3.82672L28.4924 12.0849C28.6487 12.3909 28.9499 12.6028 29.2983 12.6514L42.4162 14.4975C43.2937 14.621 43.6437 15.6656 43.0096 16.2639L33.516 25.2219C33.2645 25.4594 33.1489 25.8028 33.2091 26.1382L35.4494 38.7869C35.5999 39.633 34.6826 40.2784 33.8969 39.8784L22.1648 33.9075C21.853 33.7489 21.4801 33.7489 21.1683 33.9075L15.5958 36.7433Z" fill="#F5CD5B"/>
				</svg>
				<span class="cr-nosummary-rating-val">
					<?php echo esc_html( number_format_i18n( $average, 1 ) ); ?>
				</span>
				<span class="cr-nosummary-rating-lbl">
					<?php _e( 'Rating', 'customer-reviews-woocommerce' ); ?>
				</span>
			</div>
			<button class="cr-nosummary-add">
				<?php _e( 'Add a review', 'customer-reviews-woocommerce' ); ?>
			</button>
		</div>
		<?php
	}

	public static function get_close_button_svg() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="cr-close-button-svg"><rect x="0" fill="none" width="18" height="18"/><g><path class="cr-close-button-svg-p" d="M12.12 10l3.53 3.53-2.12 2.12L10 12.12l-3.54 3.54-2.12-2.12L7.88 10 4.34 6.46l2.12-2.12L10 7.88l3.54-3.53 2.12 2.12z"/></g></svg>';
	}

	public function display_incentivized_badge( $comment ) {
		$display_badge = false;
		// avoid checking the option in the database if it is not the first review on a page
		if ( $this->incentivized_badge ) {
			$display_badge = true;
		} elseif ( false === $this->incentivized_badge ) {
			$incentivized_setting = CR_Review_Discount_Settings::get_incentivized_badge_setting();
			if (
				$incentivized_setting &&
				is_array( $incentivized_setting ) &&
				isset( $incentivized_setting['bdg'] ) &&
				isset( $incentivized_setting['lbl'] ) &&
				'yes' === $incentivized_setting['bdg']
			) {
				$this->incentivized_badge = $incentivized_setting['lbl'];
				$display_badge = true;
			} else {
				$this->incentivized_badge = '';
			}
		}
		if ( $display_badge ) {
			$coupon_code = get_comment_meta( $comment->comment_ID, 'cr_coupon_code', true );
			if ( $coupon_code ) {
				$incentivized_badge_icon = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="cr-incentivized-svg"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 15l6 -6" /><circle cx="9.5" cy="9.5" r=".5" fill="currentColor" /><circle cx="14.5" cy="14.5" r=".5" fill="currentColor" /><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /></svg>';
				$incentivized_badge_content = '<span class="cr-incentivized-icon">' . $incentivized_badge_icon . '</span>' . esc_html( $this->incentivized_badge );
				echo '<div class="cr-incentivized-badge">' . $incentivized_badge_content . '</div>';
			}
		}
	}

	public static function get_max_top_images() {
		return apply_filters( 'cr_topreviews_max_count', 10 );
	}
}

endif;
