<?php
/**
 * Single Product Rating
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/rating.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $product;

if ( ! wc_review_ratings_enabled() ) {
	return;
}

$count_answered = 0;
if( class_exists( 'CR_Qna' ) ) {
	$count_answered = CR_Qna::get_count_answered( $product->get_id() );
}

$rating_count = $product->get_rating_count();
$review_count = $product->get_review_count();
$average      = $product->get_average_rating();

if ( $rating_count > 0 || $count_answered > 0 ) : ?>

	<div class="woocommerce-product-rating">
		<?php echo wc_get_rating_html( $average, $rating_count ); // WPCS: XSS ok. ?>
		<?php if ( comments_open() ) :
			if( 0 < $rating_count ) {
				echo '<a href="#reviews" class="woocommerce-review-link" rel="nofollow">';
				printf( _n( '%s review', '%s reviews', $review_count, 'customer-reviews-woocommerce' ), '<span class="count">' . esc_html( $review_count ) . '</span>' );
				echo '</a>';
			}
			if( 0 < $rating_count && 0 < $count_answered ) {
				echo '<span class="cr-qna-separator">|</span>';
			}
			if( 0 < $count_answered ) {
    		echo '<a href="#cr_qna" class="cr-qna-link" rel="nofollow">';
				printf( _n( '%s answered question', '%s answered questions', $count_answered, 'customer-reviews-woocommerce' ), '<span class="count">' . esc_html( $count_answered ) . '</span>' );
				echo '</a>';
			}
		endif; ?>
	</div>

<?php endif; ?>
