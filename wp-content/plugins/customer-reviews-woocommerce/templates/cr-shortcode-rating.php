<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! wc_review_ratings_enabled() ) {
	return;
}

$count_answered = 0;
if( class_exists( 'CR_Qna' ) ) {
	$count_answered = CR_Qna::get_count_answered( $cr_product->get_id() );
}

$rating_count = $cr_product->get_rating_count();
$review_count = $cr_product->get_review_count();
$average      = $cr_product->get_average_rating();

if ( $rating_count > 0 || $count_answered > 0 ) : ?>

	<div class="cr-reviews-rating">
		<?php
			/* translators: %s: rating */
			$label = sprintf( __( 'Rated %s out of 5', 'customer-reviews-woocommerce' ), $average );
			$html_star_rating = '<div class="crstar-rating" style="' . esc_attr( $cr_stars_style ) . '" aria-label="' . esc_attr( $label ) . '">' . CR_Reviews::get_star_rating_html( $average, 0 ) . '</div>';
			echo $html_star_rating;
		?>
		<?php
			if( 0 < $rating_count ) {
				echo '<a href="#reviews" class="cr-review-link" rel="nofollow">';
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
	?>
	</div>

<?php endif; ?>
