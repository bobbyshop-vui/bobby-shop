<?php
/**
* Display single product reviews (comments)
*
* This template can be overridden by copying it to yourtheme/woocommerce/single-product-reviews.php.
*
* HOWEVER, on occasion WooCommerce will need to update template files and you
* (the theme developer) will need to copy the new files to your theme to
* maintain compatibility. We try to do this as little as possible, but it does
* happen. When this occurs the version of the template file will be bumped and
* the readme will list any important changes.
*
* @see     https://docs.woocommerce.com/document/template-structure/
* @package WooCommerce/Templates
* @version 4.3.0
*/

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! comments_open() ) {
	return;
}

$no_comments_yet = true;

//check for old WooCommerce versions
if( method_exists( $product, 'get_id' ) ) {
	$cr_product_id  = $product->get_id();
} else {
	$cr_product_id  = $product->id;
}

$nonce = wp_create_nonce( "cr_product_reviews_" . $cr_product_id );

?>
<div id="reviews" class="cr-reviews-ajax-reviews">
	<div id="comments" class="cr-reviews-ajax-comments" data-nonce="<?php echo $nonce; ?>" data-page="1">
		<h2 class="woocommerce-Reviews-title">
			<?php
			$count = $product->get_review_count();
			if ( $count && wc_review_ratings_enabled() ) {
				/* translators: 1: reviews count 2: product name */
				$reviews_title = sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $count, 'woocommerce' ) ), esc_html( $count ), '<span>' . get_the_title() . '</span>' );
				echo apply_filters( 'woocommerce_reviews_title', $reviews_title, $count, $product ); // WPCS: XSS ok.
			} else {
				esc_html_e( 'Reviews', 'woocommerce' );
			}
			?>
		</h2>

		<?php
		$cr_form_permissions = CR_Forms_Settings::get_default_review_permissions();
		$form_settings = CR_Forms_Settings::get_default_form_settings();
		$cr_form_checkbox = ( 'yes' === CR_Forms_Settings::get_onsite_form_checkbox( $form_settings ) ) ? true : false;
		$cr_form_checkbox_text = CR_Forms_Settings::get_onsite_form_checkbox_text( $form_settings );
		if ( false === $cr_form_checkbox_text ) {
			$cr_form_checkbox_text = CR_Forms_Settings::get_default_form_onsite_checkbox_text();
		}
		$new_reviews_allowed = in_array( $cr_form_permissions, array( 'registered', 'verified', 'anybody' ) ) ? true : false;
		$cr_per_page = CR_Ajax_Reviews::get_per_page();
		if ( have_comments() ) : ?>
			<?php
			$no_comments_yet = false;
			$cr_get_reviews = CR_Ajax_Reviews::get_reviews( $cr_product_id );
			do_action( 'cr_reviews_summary', $cr_product_id, true, $new_reviews_allowed );
			do_action( 'cr_reviews_customer_images', $cr_get_reviews['reviews'] );
			if ( $new_reviews_allowed ) {
				do_action( 'cr_reviews_nosummary', $cr_product_id );
			}
			do_action( 'cr_reviews_search', $cr_get_reviews['reviews'] );
			do_action( 'cr_reviews_count_row', $cr_get_reviews['reviews_count'], 1, $cr_per_page );
			// WPML switch to show reviews in all or some languages
			if ( has_filter( 'wpml_object_id' ) ) {
				if( class_exists( 'WCML_Comments' ) ) {
					global $woocommerce_wpml;
					if( $woocommerce_wpml ) :
						?>
							<div class="cr-ajax-reviews-wpml-switch">
								<?php
								$woocommerce_wpml->comments->comments_link();
								?>
							</div>
						<?php
						// remove the default WPML switch from above the review form
						remove_action( 'comment_form_before', array( $woocommerce_wpml->comments, 'comments_link' ) );
					endif;
				}
			}
			?>
			<ol class="commentlist cr-ajax-reviews-list" data-product="<?php echo $cr_product_id; ?>">
				<?php
				$hide_avatars = 'hidden' === get_option( 'ivole_avatars', 'standard' ) ? true : false;
				wp_list_comments(
					apply_filters(
						'woocommerce_product_review_list_args',
						array(
							'callback' => array( 'CR_Reviews', 'callback_comments' ),
							'reverse_top_level' => false,
							'per_page' => $cr_per_page,
							'page' => 1,
							'cr_hide_avatars' => $hide_avatars
						)
					),
					$cr_get_reviews['reviews'][0]
				);
				?>
			</ol>

			<?php
			if ( $cr_get_reviews['reviews_count'] > $cr_per_page ) {
				?>
					<div class="cr-show-more-review-spinner-cnt">
						<button class="cr-show-more-reviews-prd" type="button">
							<?php
								echo sprintf(
									__( 'Show more reviews (%d)', 'customer-reviews-woocommerce' ),
									$cr_get_reviews['reviews_count'] - $cr_per_page
								);
							?>
						</button>
						<span class="cr-show-more-review-spinner" style="display:none"></span>
					</div>
				<?php
			} else {
				?>
				<span class="cr-show-more-review-spinner" style="display:none"></span>
				<?php
			}
			?>
			<p class="cr-search-no-reviews" style="display:none"><?php esc_html_e("Sorry, no reviews match your current selections", "customer-reviews-woocommerce" );?></p>
		<?php else : ?>
			<p class="woocommerce-noreviews"><?php esc_html_e( 'There are no reviews yet', 'customer-reviews-woocommerce' ); ?></p>
		<?php endif; ?>
	</div>

	<?php
		$cr_ajax_review_form_class = 'cr-ajax-reviews-review-form';
		if( $no_comments_yet && $new_reviews_allowed ) {
			$cr_ajax_review_form_class .= ' cr-ajax-reviews-review-form-nc';
		}
	?>
	<div class="<?php echo $cr_ajax_review_form_class; ?>">
		<div id="review_form_wrapper">
			<div id="review_form" class="cr-single-product-review">
				<?php
				$item_id = $cr_product_id;
				$item_name = $product->get_name();
				$item_pic = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail', false );
				$media_upload = ( 'yes' === get_option( 'ivole_attach_image', 'no' ) ? true : false );
				$cr_form_item_media_array = array();
				$cr_form_item_media_desc = __( 'Add photos or video to your review', 'customer-reviews-woocommerce' );
				wc_get_template(
					'cr-review-form.php',
					array(
						'cr_item_id' => $item_id,
						'cr_item_name' => $item_name,
						'cr_item_pic' => $item_pic,
						'cr_form_media_enabled' => $media_upload,
						'cr_form_item_media_array' => $cr_form_item_media_array,
						'cr_form_item_media_desc' => $cr_form_item_media_desc,
						'cr_form_permissions' => $cr_form_permissions,
						'cr_form_checkbox' => $cr_form_checkbox,
						'cr_form_checkbox_text' => wp_specialchars_decode( $cr_form_checkbox_text, ENT_QUOTES )
					),
					'customer-reviews-woocommerce',
					dirname( dirname( __FILE__ ) ) . '/templates/'
				);
				?>
			</div>
		</div>
	</div>

	<div class="clear"></div>
</div>
