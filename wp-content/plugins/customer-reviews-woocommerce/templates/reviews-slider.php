<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cr-reviews-slider" id="<?php echo $id; ?>" data-slick='<?php echo wp_json_encode( $slider_settings ); ?>' style="<?php echo esc_attr( $section_style ); ?>">
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
		$author = get_comment_author( $review );
	?>
		<div class="cr-review-card">
			<div class="cr-review-card-inner" style="<?php echo esc_attr( $card_style ); ?>">
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
				<?php
					do_action( 'cr_slider_before_review_text', $review );
				?>
				<div class="middle-row">
					<div class="review-content">
						<div class="review-text">
						<?php
						$clear_content = wp_strip_all_tags( $review->comment_content );
						if( $max_chars && mb_strlen( $clear_content ) > $max_chars ) {
							$less_content = wp_kses_post( mb_substr( $clear_content, 0, $max_chars ) );
							$more_content = wp_kses_post( mb_substr( $clear_content, $max_chars ) );
							$read_more = '<span class="cr-slider-read-more">...<br><a href="#">' . esc_html__( 'Show More', 'customer-reviews-woocommerce' ) . '</a></span>';
							$more_content = '<div class="cr-slider-details" style="display:none;">' . $more_content . '<br><span class="cr-slider-read-less"><a href="#">' . esc_html__( 'Show Less', 'customer-reviews-woocommerce' ) . '</a></span></div>';
							$comment_content = $less_content . $read_more . $more_content;
							echo $comment_content;
						} else {
							echo wpautop( wp_kses_post( $review->comment_content ) );
						}
						?>
						</div>
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
				<?php if ( $show_products && $product = wc_get_product( $review->comment_post_ID ) ):
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
