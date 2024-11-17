<?php
/**
 * Review Form Template
 *
 * This template can be overridden by copying it to yourtheme/customer-reviews-woocommerce/cr-review-form.php
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$cr_current_user = wp_get_current_user();
$cr_user_name = '';
$cr_user_email = '';
if( $cr_current_user instanceof WP_User ) {
	$cr_user_email = $cr_current_user->user_email;
	$cr_user_name = $cr_current_user->user_firstname . ' ' . $cr_current_user->user_lastname;
	if ( empty( trim( $cr_user_name ) ) ) $cr_user_name = '';
}
?>

<div class="cr-review-form-wrap">

	<div class="cr-review-form-nav">
		<div class="cr-nav-left">
			<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M16.9607 19.2506L11.0396 13.3295L16.9607 7.40833" stroke="#0E252C" stroke-miterlimit="10"/>
			</svg>
			<span>
				<?php _e( 'Add a review', 'customer-reviews-woocommerce' ); ?>
			</span>
		</div>
		<div class="cr-nav-right">
			<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M8.61914 8.62009L19.381 19.3799M8.61914 19.3799L19.381 8.62009" stroke="#0E252C" stroke-miterlimit="10" stroke-linejoin="round"/>
			</svg>
		</div>
	</div>

	<?php if (
		'verified' === $cr_form_permissions &&
		0 < $cr_item_id &&
		! wc_customer_bought_product( '', get_current_user_id(), $cr_item_id ) &&
		is_user_logged_in()
	) : ?>

		<div class="cr-review-form-not-logged-in">
			<span>
			<?php _e( 'Only customers who have purchased this product may leave a review', 'customer-reviews-woocommerce' ); ?>
			</span>
		</div>

	<?php elseif (
		( 'registered' === $cr_form_permissions && ! is_user_logged_in() ) ||
		( 'verified' === $cr_form_permissions && 0 > $cr_item_id && ! is_user_logged_in() )
	) : ?>

		<div class="cr-review-form-not-logged-in">
			<span>
			<?php _e( 'You must be logged in to post a review', 'customer-reviews-woocommerce' ); ?>
			</span>
			<?php
				$cr_login_url = get_option( 'ivole_review_login_url' );
				if ( $cr_login_url ) {
					$cr_login_url = add_query_arg( 'redirect_to', urlencode( apply_filters( 'the_permalink', get_the_permalink(), $cr_item_id ) ), $cr_login_url );
				} else {
					$cr_login_url = wp_login_url( apply_filters( 'the_permalink', get_the_permalink(), $cr_item_id ) );
				}
			?>
			<a class="cr-review-form-continue" href="<?php echo esc_url( $cr_login_url ); ?>" rel="nofollow"><?php _e( 'Log In', 'customer-reviews-woocommerce' ); ?></a>
		</div>

	<?php elseif ( 'nobody' === $cr_form_permissions ) : ?>

		<div class="cr-review-form-not-logged-in">
			<span>
			<?php _e( 'Currently, we are not accepting new reviews', 'customer-reviews-woocommerce' ); ?>
			</span>
		</div>

	<?php else : ?>

		<div class="cr-review-form-item">
			<img src="<?php echo esc_url( $cr_item_pic ); ?>" alt="<?php echo esc_attr( $cr_item_name ); ?>"/>
			<span><?php echo esc_html( $cr_item_name ); ?></span>
			<input type="hidden" value="<?php echo esc_attr( $cr_item_id ); ?>" class="cr-review-form-item-id" />
		</div>

		<?php do_action( 'cr_review_form_rating', $cr_item_id ); ?>

		<div class="cr-review-form-comment">
			<div class="cr-review-form-lbl">
				<?php _e( 'Your review', 'customer-reviews-woocommerce' ); ?>
			</div>
			<textarea rows="5" name="cr_review_form_comment_txt" class="cr-review-form-comment-txt"></textarea>
			<div class="cr-review-form-field-error">
				<?php _e( '* Review is required', 'customer-reviews-woocommerce' ); ?>
			</div>
		</div>

		<div class="cr-review-form-ne">
			<div class="cr-review-form-name">
				<div class="cr-review-form-lbl">
					<?php _e( 'Name', 'customer-reviews-woocommerce' ); ?>
				</div>
				<input type="text" name="cr_review_form_name" class="cr-review-form-txt" autocomplete="name" value="<?php echo $cr_user_name;?>"></input>
				<div class="cr-review-form-field-error">
					<?php _e( '* Name is required', 'customer-reviews-woocommerce' ); ?>
				</div>
			</div>
			<div class="cr-review-form-email">
				<div class="cr-review-form-lbl">
					<?php _e( 'Email', 'customer-reviews-woocommerce' ); ?>
				</div>
				<input type="email" name="cr_review_form_email" class="cr-review-form-txt" autocomplete="email" value="<?php echo $cr_user_email;?>"></input>
				<div class="cr-review-form-field-error">
					<?php _e( '* Email is required', 'customer-reviews-woocommerce' ); ?>
				</div>
			</div>
		</div>

		<?php
			if( $cr_form_media_enabled ) :
		?>
			<div class="cr-form-item-media cr-form-item-container">
				<div class="cr-form-item-subcontainer<?php echo ( 0 < count( $cr_form_item_media_array ) ? ' cr-form-visible' : '' ); ?>">
					<div>
						<div class="cr-form-item-media-none">
							<svg class="cr-form-item-media-icon" viewBox="0 0 576 512">
								<path d="M480 416v16c0 26.51-21.49 48-48 48H48c-26.51 0-48-21.49-48-48V176c0-26.51 21.49-48 48-48h16v208c0 44.112 35.888 80 80 80h336zm96-80V80c0-26.51-21.49-48-48-48H144c-26.51 0-48 21.49-48 48v256c0 26.51 21.49 48 48 48h384c26.51 0 48-21.49 48-48zM256 128c0 26.51-21.49 48-48 48s-48-21.49-48-48 21.49-48 48-48 48 21.49 48 48zm-96 144l55.515-55.515c4.686-4.686 12.284-4.686 16.971 0L272 256l135.515-135.515c4.686-4.686 12.284-4.686 16.971 0L512 208v112H160v-48z"></path>
							</svg>
							<svg class="cr-form-item-media-icon" viewBox="0 0 576 512">
								<path d="M336.2 64H47.8C21.4 64 0 85.4 0 111.8v288.4C0 426.6 21.4 448 47.8 448h288.4c26.4 0 47.8-21.4 47.8-47.8V111.8c0-26.4-21.4-47.8-47.8-47.8zm189.4 37.7L416 177.3v157.4l109.6 75.5c21.2 14.6 50.4-.3 50.4-25.8V127.5c0-25.4-29.1-40.4-50.4-25.8z"></path>
							</svg>
							<span>
								<?php echo $cr_form_item_media_desc; ?>
							</span>
						</div>
						<div class="cr-form-item-media-preview">
							<?php
								$counter = 1;
								foreach( $cr_form_item_media_array as $item ) {
									echo '<div class="cr-upload-images-containers cr-upload-images-container-' . $counter . ' cr-upload-ok">';
									if( wp_attachment_is( 'image', $item['id'] ) ) {
										echo '<img class="cr-upload-images-thumbnail" src="' . $item['url'] . '">';
									} else {
										echo '<svg class="cr-upload-video-thumbnail" viewBox="0 0 576 512"><path d="M336.2 64H47.8C21.4 64 0 85.4 0 111.8v288.4C0 426.6 21.4 448 47.8 448h288.4c26.4 0 47.8-21.4 47.8-47.8V111.8c0-26.4-21.4-47.8-47.8-47.8zm189.4 37.7L416 177.3v157.4l109.6 75.5c21.2 14.6 50.4-.3 50.4-25.8V127.5c0-25.4-29.1-40.4-50.4-25.8z"></path></svg>';
									}
									echo '<div class="cr-upload-images-pbar"><div class="cr-upload-images-pbarin"></div></div>';
									echo '<button class="cr-upload-images-delete">';
									echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path class="cr-no-icon" d="M12.12 10l3.53 3.53-2.12 2.12L10 12.12l-3.54 3.54-2.12-2.12L7.88 10 4.34 6.46l2.12-2.12L10 7.88l3.54-3.53 2.12 2.12z"/></g></svg>';
									echo '</button>';
									echo '<input name="cr-upload-images-ids[]" type="hidden" value="' . esc_attr( json_encode( array( 'id' => $item['id'], 'key' => $item['key'] ) ) ) . '">';
									echo '<span class="cr-upload-images-delete-spinner"></span>';
									echo '</div>';
									$counter++;
								}
								if( 1 < $counter && $cr_form_media_upload_limit >= $counter ) {
									echo '<div class="cr-form-item-media-add">+</div>';
								}
							?>
						</div>
						<input type="file" accept="image/jpeg,image/png,video/*" class="cr-form-item-media-file"<?php echo ( 1 < $counter ? ' data-lastindex="' . $counter . '"' : '' ); ?>>
					</div>
					<div class="cr-review-form-field-error"></div>
				</div>
			</div>
		<?php
			endif;
		?>

		<?php if ( $cr_form_checkbox ) : ?>
			<div class="cr-review-form-terms">
				<label>
					<input type="checkbox" class="cr-review-form-checkbox" name="cr_review_form_checkbox" />
					<span><?php echo $cr_form_checkbox_text; ?></span>
				</label>
				<div class="cr-review-form-field-error">
					<?php _e( '* Please tick the checkbox to proceed', 'customer-reviews-woocommerce' ); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php
			do_action( 'cr_review_form_before_btns' );
		?>

		<div class="cr-review-form-buttons">
			<button type="button" class="cr-review-form-submit">
				<span><?php _e( 'Submit', 'customer-reviews-woocommerce' ); ?></span>
				<img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'img/spinner-dots.svg'; ?>" alt="Loading" />
			</button>
			<button type="button" class="cr-review-form-cancel">
				<?php _e( 'Cancel', 'customer-reviews-woocommerce' ); ?>
			</button>
		</div>

		<div class="cr-review-form-result">
			<span></span>
			<button type="button" class="cr-review-form-continue"></button>
		</div>

	<?php endif; ?>

</div>
