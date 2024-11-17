<?php
/**
 * The template to display the reviewers meta data (name, verified owner, review date)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/review-meta.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $comment;
$verified = wc_review_is_from_verified_owner( $comment->comment_ID );
$shop_manager = false;
if( isset( $comment->user_id ) && 0 < $comment->user_id ) {
	if( user_can( $comment->user_id, 'manage_woocommerce' ) ) {
		$shop_manager = true;
	}
}

if ( '0' === $comment->comment_approved ) { ?>

	<p class="meta">
		<em class="woocommerce-review__awaiting-approval">
			<?php esc_html_e( 'Your review is awaiting approval', 'woocommerce' ); ?>
		</em>
	</p>

<?php } else { ?>

	<p class="meta">
		<strong class="woocommerce-review__author"><?php comment_author(); ?> </strong>
		<?php
		if( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) && $shop_manager ) {
			$store_manager = apply_filters( 'cr_reviews_store_manager', __( 'store manager', 'customer-reviews-woocommerce' ) );
			echo '<em class="woocommerce-review__verified verified">(' . esc_html__( $store_manager, 'customer-reviews-woocommerce' ) . ')</em> ';
		} else {
			if ( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) && $verified ) {
				$cr_verified_label = get_option( 'ivole_verified_owner', '' );
				if( $cr_verified_label ) {
					if ( function_exists( 'pll__' ) ) {
						$cr_verified_label = esc_attr( pll__( $cr_verified_label ) );
					} else {
						$cr_verified_label = esc_attr( $cr_verified_label );
					}
				} else {
					$cr_verified_label = esc_attr__( 'verified owner', 'woocommerce' );
				}
				echo '<em class="woocommerce-review__verified verified">(' . $cr_verified_label . ')</em> ';
			}
		}

		?>
		<span class="woocommerce-review__dash">&ndash;</span> <time class="woocommerce-review__published-date" datetime="<?php echo esc_attr( get_comment_date( 'c' ) ); ?>"><?php echo esc_html( get_comment_date( wc_date_format() ) ); ?></time>
	</p>

<?php
}
