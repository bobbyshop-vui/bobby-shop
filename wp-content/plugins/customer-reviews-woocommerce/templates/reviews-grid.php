<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cr-reviews-grid" id="<?php echo $id; ?>" style="<?php echo esc_attr( $section_style ); ?>" data-attributes="<?php echo wc_esc_json(wp_json_encode($attributes));?>">
	<?php
		echo $cr_credits_line;
		echo $review_form;
		echo $summary_bar;
		if ( 0 < count( $reviews ) ) :
	?>
		<div class="cr-reviews-grid-inner">
			<div class="cr-reviews-grid-col cr-reviews-grid-col1"></div>
			<div class="cr-reviews-grid-col cr-reviews-grid-col2"></div>
			<div class="cr-reviews-grid-col cr-reviews-grid-col3"></div>
			<?php foreach ( $reviews as $i => $review ):
				$rating = intval( get_comment_meta( $review->comment_ID, 'rating', true ) );
				if( 'yes' === get_option( 'ivole_verified_links', 'no' ) ) {
					$order_id = intval( get_comment_meta( $review->comment_ID, 'ivole_order', true ) );
				} else {
					$order_id = 0;
				}
				$country = get_comment_meta( $review->comment_ID, 'ivole_country', true );
				$country_code = null;
				if( is_array( $country ) && isset( $country['code'] ) ) {
					$country_code = $country['code'];
				}
				$product = wc_get_product( $review->comment_post_ID );
				if( $product ) {
					$card_class = 'cr-review-card cr-card-product';
				} else {
					$card_class = 'cr-review-card cr-card-shop';
				}
				$pics = get_comment_meta( $review->comment_ID, 'ivole_review_image' );
				$pics_local = get_comment_meta( $review->comment_ID, 'ivole_review_image2' );
				$pics_v = get_comment_meta( $review->comment_ID, 'ivole_review_video' );
				$pics_v_local = get_comment_meta( $review->comment_ID, 'ivole_review_video2' );
				$customer_images_html = '';
				$customer_images = array();
				$customer_videos = array();
				foreach( $pics as $pic ) {
					if( $pic['url'] ) {
						$customer_images[] = $pic['url'];
					}
				}
				foreach( $pics_local as $pic ) {
					$attachmentUrl = wp_get_attachment_image_url( $pic, apply_filters( 'cr_reviews_grid_image_size', 'large' ) );
					if( $attachmentUrl ) {
						$customer_images[] = $attachmentUrl;
					}
				}
				foreach( $pics_v as $pic_v ) {
					if( $pic_v['url'] ) {
						$customer_videos[] = $pic_v['url'];
					}
				}
				foreach( $pics_v_local as $pic_v ) {
					$attachmentUrl = wp_get_attachment_url( $pic_v );
					if( $attachmentUrl ) {
						$customer_videos[] = $attachmentUrl;
					}
				}
				$count_customer_images = count( $customer_images );
				$count_customer_videos = count( $customer_videos );
				$count_customer_media = $count_customer_images + $count_customer_videos;
				if( 0 < $count_customer_media ) {
					$pic_idx = 0;
					$vid_idx = 0;
					$customer_images_html = '<div class="image-row">';
					$counter_icons_html = '';
					if ( 0 < $count_customer_videos ) {
						// if there are videos, use the 1st one as a cover
						$customer_images_html .= '<video preload="metadata" class="image-row-vid" src="' . esc_url( $customer_videos[$vid_idx] ) . '" data-crmedia="vid" data-crtitle="' . sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $vid_idx + 1, $review->comment_author ) . '"></video>';
						$customer_images_html .= '<img class="cr-comment-videoicon" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'img/video.svg" ';
						$customer_images_html .= 'alt="' . sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $vid_idx + 1, $review->comment_author ) . '">';
						$vid_idx++;
						// add a video counter icon
						$counter_icons_html .= '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="cr-grid-icon-counter-video"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z" /><path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /></svg>';
						$counter_icons_html .= '<span>' . esc_html( $count_customer_videos ) . '</span>';
					} else {
						// otherwise, use the 1st picture as a cover
						$customer_images_html .= '<img class="image-row-img" src="' . esc_url( $customer_images[$pic_idx] ) . '" alt="' . sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), $pic_idx + 1 ) . $review->comment_author . '" loading="lazy" data-crmedia="pic">';
						$pic_idx++;
					}
					for( $j=$vid_idx; $j < $count_customer_videos; $j++ ) {
						$customer_images_html .= '<video preload="metadata" class="image-row-vid image-row-vid-none" src="' . esc_url( $customer_videos[$j] )  . '" data-crmedia="vid" data-crtitle="' . sprintf( __( 'Video #%1$d from %2$s', 'customer-reviews-woocommerce' ), $vid_idx + 1, $review->comment_author ) . '"></video>';
					}
					for( $j=$pic_idx; $j < $count_customer_images; $j++ ) {
						$customer_images_html .= '<img class="image-row-img image-row-img-none" src="' . esc_url( $customer_images[$j] ) . '" alt="' . sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), $j+1 ) . $review->comment_author . '" data-crmedia="pic">';
					}
					$customer_images_html .= '<div class="media-row-count">';
					$customer_images_html .= $counter_icons_html;
					if ( 0 < $count_customer_images ) {
						$customer_images_html .= '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="27" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="cr-grid-icon-counter-photo"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M15 8h.01" /><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z" /><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" /><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3" /></svg>';
						$customer_images_html .= '<span>' . esc_html( $count_customer_images ) . '</span>';
					}
					$customer_images_html .= '</div></div>';
				}
				$author = get_comment_author( $review );
				?>
				<div class="<?php echo esc_attr( $card_class ); ?>" style="<?php echo esc_attr( $card_style ); ?>" data-reviewid="<?php echo esc_attr( $review->comment_ID ); ?>">
					<div class="cr-review-card-content">
						<?php echo $customer_images_html; ?>
						<div class="top-row">
							<?php
							$avtr = get_avatar( $review, 56, '', esc_attr( $author ) );
							if( $avatars && $avtr ): ?>
								<div class="review-thumbnail">
									<?php echo $avtr; ?>
								</div>
							<?php endif; ?>
							<div class="reviewer">
								<div class="reviewer-name">
									<?php
									echo esc_html( $author );
									if( $country_code ) {
										echo '<img src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'img/flags/' . $country_code . '.svg" class="ivole-grid-country-icon" width="20" height="15" alt="' . $country_code . '">';
									}
									?>
								</div>
								<?php
								if( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) && wc_review_is_from_verified_owner( $review->comment_ID ) ) {
									echo '<div class="reviewer-verified">';
									echo '<img class="cr-reviewer-verified" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'img/verified.svg' . '" alt="' . $verified_text . '" width="22" height="22" loading="lazy" />';
									echo $verified_text;
									echo '</div>';
								} else {
									echo '<div class="reviewer-verified">';
									echo esc_html__( 'Reviewer', 'customer-reviews-woocommerce' );
									echo '</div>';
								}
								?>
							</div>
						</div>
						<div class="rating-row">
							<div class="rating">
								<div class="crstar-rating" style="<?php echo esc_attr( $stars_style ); ?>"><span style="width:<?php echo ($rating / 5) * 100; ?>%;"></span></div>
							</div>
							<div class="rating-label">
								<?php echo $rating . '/5'; ?>
							</div>
						</div>
						<div class="middle-row">
							<div class="review-content">
								<?php echo wpautop( wp_kses_post( $review->comment_content ) ); ?>
							</div>
							<?php if ( $order_id && intval( $review->comment_post_ID ) !== intval( $shop_page_id ) ): ?>
								<div class="verified-review-row">
									<div class="verified-badge"><?php printf( $badge, $review->comment_post_ID, $order_id ); ?></div>
								</div>
							<?php elseif ( $order_id && intval( $review->comment_post_ID ) === intval( $shop_page_id ) ): ?>
								<div class="verified-review-row">
									<div class="verified-badge"><?php printf( $badge_sr, $order_id ); ?></div>
								</div>
							<?php endif; ?>
							<div class="datetime">
								<?php printf( _x( '%s ago', '%s = human-readable time difference', 'customer-reviews-woocommerce' ), human_time_diff( mysql2date( 'U', $review->comment_date, true ), current_time( 'timestamp' ) ) ); ?>
							</div>
						</div>
						<?php
						if ( $incentivized_label ) :
							$coupon_code = get_comment_meta( $review->comment_ID, 'cr_coupon_code', true );
							if ( $coupon_code ) :
						?>
							<div class="cr-incentivized-row">
								<?php
									$incentivized_badge_icon = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="cr-incentivized-svg"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 15l6 -6" /><circle cx="9.5" cy="9.5" r=".5" fill="currentColor" /><circle cx="14.5" cy="14.5" r=".5" fill="currentColor" /><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /></svg>';
									$incentivized_badge_content = '<span class="cr-incentivized-icon">' . $incentivized_badge_icon . '</span>' . esc_html( $incentivized_label );
									echo '<div class="cr-incentivized-badge">' . $incentivized_badge_content . '</div>';
								?>
							</div>
						<?php
							endif;
						endif;
						?>
						<?php if ( $show_products && $product ):
							if( 'publish' === $product->get_status() ):
								?>
								<div class="review-product" style="<?php echo esc_attr( $product_style ); ?>">
									<div class="product-thumbnail">
										<?php echo $product->get_image( 'woocommerce_gallery_thumbnail' ); ?>
									</div>
									<div class="product-title">
										<?php if ( $product_links ): ?>
											<?php echo '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . $product->get_title() . '</a>'; ?>
										<?php else: ?>
											<?php echo '<span>' . $product->get_title() . '</span>'; ?>
										<?php endif; ?>
									</div>
								</div>
								<?php
							endif;
						endif;
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( $show_more ): ?>
			<div class="cr-show-more">
				<button class="cr-show-more-button" type="button"><?php echo __( 'Show more', 'customer-reviews-woocommerce' ); ?></button>
				<span class="cr-show-more-spinner" style="display:none;"></span>
			</div>
		<?php else: ?>
			<div class="cr-show-more">
				<span class="cr-show-more-spinner" style="display:none;"></span>
			</div>
		<?php endif; ?>
	<?php else: ?>
		<div class="cr-reviews-grid-empty">
			<?php echo esc_html__( 'Sorry, no reviews match your current selections', 'customer-reviews-woocommerce' ); ?>
		</div>
	<?php endif; ?>
</div>
