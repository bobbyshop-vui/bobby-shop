<?php
/**
 * Review Comments Template
 *
 * Closing li is left out on purpose!
 *
 * This template can be overridden by copying it to yourtheme/customer-reviews-woocommerce/cr-review.php
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">

	<?php
		$verified = wc_review_is_from_verified_owner( $comment->comment_ID );
		$cr_comment_container_class = 'comment_container';
		if ( $verified ) {
			$cr_comment_container_class .= ' cr-verified-owner';
		}
		$cr_hide_avatars = false;
		$hide_avatars_class = '';
		$hide_avatars_wcml_class = '';
		if (
			isset( $action_args ) &&
			isset( $action_args['args'] ) &&
			isset( $action_args['args']['args'] ) &&
			isset( $action_args['args']['args']['cr_hide_avatars'] ) &&
			$action_args['args']['args']['cr_hide_avatars']
		) {
			$cr_hide_avatars = true;
			$hide_avatars_class = ' comment-text-no-avatar';
			$hide_avatars_wcml_class = ' cr-comment-no-avatar';
		}
	?>

	<div id="comment-<?php comment_ID(); ?>" class="<?php echo esc_attr( $cr_comment_container_class ) . $hide_avatars_wcml_class; ?>">

		<?php

		if ( class_exists( 'WCML_Comments' ) ) {
			// provide an action for WPML / WCML plugins to translate the review
			do_action( 'woocommerce_review_before', $comment );
		}

		if ( ! $cr_hide_avatars ) {
			$get_avtr = get_avatar( $comment, apply_filters( 'cr_review_gravatar_size', '60' ), '' );
			if ( $get_avtr ) {
				echo $get_avtr;
			} else {
				$hide_avatars_class = ' comment-text-no-avatar';
			}
		}

		?>

		<div class="comment-text<?php echo $hide_avatars_class; ?>">

			<?php

			$shop_manager = false;
			if( isset( $comment->user_id ) && 0 < $comment->user_id ) {
				if( user_can( $comment->user_id, 'manage_woocommerce' ) ) {
					$shop_manager = true;
				}
			}

			if ( '0' === $comment->comment_approved ) { ?>

				<p class="meta">
					<em class="woocommerce-review__awaiting-approval">
						<?php esc_html_e( 'Your review is awaiting approval', 'customer-reviews-woocommerce' ); ?>
					</em>
				</p>

			<?php } else { ?>

				<div class="meta">
					<div class="cr-meta-author-featured-date">
						<div class="cr-meta-author-title">
							<div>
								<span class="woocommerce-review__author"><?php comment_author(); ?></span>
								<?php
								// check if country/region should be shown for the review
								$country = get_comment_meta( $comment->comment_ID, 'ivole_country', true );
								if( is_array( $country ) && 2 === count( $country ) ) {
									$country_desc = '';
									if( isset( $country['code'] ) ) {
										if( isset( $country['desc'] ) ) {
											$country_desc = $country['desc'];
										} else {
											$country_desc = $country['code'];
										}
										echo '<img src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'img/flags/' . $country['code'] . '.svg" class="ivole-review-country-icon" alt="' . $country['code'] . '" title="' . $country_desc . '">';
									}
								}
								?>
							</div>
							<?php
							if ( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) ) {
								if( $shop_manager ) {
									$store_manager = apply_filters( 'cr_reviews_store_manager', __( 'Store manager', 'customer-reviews-woocommerce' ) );
									echo '<span class="woocommerce-review__verified verified">' . esc_html__( $store_manager, 'customer-reviews-woocommerce' ) . '</span> ';
								} else {
									if ( $verified ) {
										$cr_verified_label = get_option( 'ivole_verified_owner', '' );
										if( $cr_verified_label ) {
											if ( function_exists( 'pll__' ) ) {
												$cr_verified_label = esc_html( pll__( $cr_verified_label ) );
											} else {
												$cr_verified_label = esc_html( $cr_verified_label );
											}
										} else {
											$cr_verified_label = esc_html__( 'Verified owner', 'customer-reviews-woocommerce' );
										}
									} else {
										$cr_verified_label = esc_html__( 'Reviewer', 'customer-reviews-woocommerce' );
									}
									echo '<span class="woocommerce-review__verified verified">' . $cr_verified_label . '</span> ';
								}
							}
							?>
						</div>
						<?php
						// Display a featured badge or a reply icon
						if( 0 === intval( $comment->comment_parent ) ) {
							if( 0 < $comment->comment_karma ) {
								// display 'featured' badge
								$output = __( 'Featured Review', 'customer-reviews-woocommerce' );
								echo '<div class="cr-all-featured-badge"><span>' . $output . '</span></div>';
							}
						} else {
							echo '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M20 13.5a6.5 6.5 0 0 1-6.5 6.5H6v-2h7.5c2.5 0 4.5-2 4.5-4.5S16 9 13.5 9H7.83l3.08 3.09L9.5 13.5L4 8l5.5-5.5l1.42 1.41L7.83 7h5.67a6.5 6.5 0 0 1 6.5 6.5Z"/></svg>';
						}
						?>
					</div>
					<time class="woocommerce-review__published-date" datetime="<?php echo esc_attr( get_comment_date( 'c' ) ); ?>"><?php echo esc_html( get_comment_date( wc_date_format() ) ); ?></time>
				</div>

			<?php
			}

			$rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );

			if ( $rating && wc_review_ratings_enabled() ) {
				if ( 0 < $rating ) {
					/* translators: %s: rating */
					$label = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating );
					$html_star_rating = '<div class="crstar-rating" role="img" aria-label="' . esc_attr( $label ) . '">' . CR_Reviews::get_star_rating_html( $rating, 0 ) . '</div>';
					$product_avatar_name = '';

					if(
						isset( $action_args ) &&
						isset( $action_args['args'] ) &&
						isset( $action_args['args']['args'] ) &&
						isset( $action_args['args']['args']['cr_show_products'] ) &&
						$action_args['args']['args']['cr_show_products']
					) {
						$prod_temp = wc_get_product( $comment->comment_post_ID );
						if( $prod_temp ) {
							// Product review
							if( method_exists( $prod_temp, 'get_status' ) && 'publish' == $prod_temp->get_status() ) {
								$q_name = $prod_temp->get_title();
								$q_name = esc_html( strip_tags( $q_name ) );
								$image = wp_get_attachment_image_url( $prod_temp->get_image_id(), apply_filters( 'cr_allreviews_image_size', 'woocommerce_gallery_thumbnail' ), false );
								$permalink = $prod_temp->get_permalink();
								//
								$product_avatar = '';
								if( $image ) {
									$product_avatar = '<img class="iv-comment-product-img" src="' . esc_url( $image ) . '" alt="' . $q_name . '"/>';
									if( $permalink ) {
										$product_avatar = '<a class="iv-comment-product-a" href="' . esc_url( $permalink ) . '" title="' . $q_name . '">' . $product_avatar . '</a>';
									}
								}
								//
								$product_name = '';
								if( $permalink ) {
									$product_name = '<a class="cr-comment-productname-a" href="' . $permalink . '" title="' . $q_name . '">' . $q_name . '</a>';
								} else {
									$product_name = $q_name;
								}
								//
								$product_avatar_name = $product_avatar . $product_name;
								if( $product_avatar_name ) {
									$product_avatar_name = '<div class="cr-product-name-picture">' . $product_avatar_name . '</div>';
									$html_star_rating = '<div class="cr-rating-product-name">' . $html_star_rating . $product_avatar_name . '</div>';
								}
							}
						} else {
							// Store review
							$permalink = '';
							$shop_page_id = wc_get_page_id( 'shop' );
							if( $shop_page_id ) {
								$permalink = get_permalink( $shop_page_id );
							}
							$q_name = Ivole_Email::get_blogname();
							$image = get_site_icon_url( 512, plugins_url( '/img/store.svg', dirname( __FILE__ ) ) );
							//
							$product_avatar = '';
							if( $image ) {
								$product_avatar = '<img class="iv-comment-product-img" src="' . esc_url( $image ) . '" alt="' . $q_name . '"/>';
								if( $permalink ) {
									$product_avatar = '<a class="iv-comment-product-a" href="' . esc_url( $permalink ) . '" title="' . $q_name . '">' . $product_avatar . '</a>';
								}
							}
							//
							$product_name = '';
							if( $permalink ) {
								$product_name = '<a class="cr-comment-productname-a" href="' . $permalink . '" title="' . $q_name . '">' . $q_name . '</a>';
							} else {
								$product_name = $q_name;
							}
							//
							$product_avatar_name = $product_avatar . $product_name;
							if( $product_avatar_name ) {
								$product_avatar_name = '<div class="cr-product-name-picture">' . $product_avatar_name . '</div>';
								$html_star_rating = '<div class="cr-rating-product-name">' . $html_star_rating . $product_avatar_name . '</div>';
							}
						}
					}

					echo $html_star_rating;
				}
			}


			do_action( 'cr_review_before_comment_text', $comment );

			/**
			 * The woocommerce_review_comment_text hook
			 *
			 * @hooked woocommerce_review_display_comment_text - 10
			 */
			do_action( 'woocommerce_review_comment_text', $comment );

			do_action( 'cr_review_after_comment_text', $comment );
			?>

		</div>
	</div>
