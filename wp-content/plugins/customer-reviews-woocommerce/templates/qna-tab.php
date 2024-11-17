<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nonce = wp_create_nonce( "cr_qna_" . $cr_post_id );
$nonce_ans = wp_create_nonce( "cr_qna_a_" . $cr_post_id );
$nonce_showmore = wp_create_nonce( "cr_qna_sm_" . $cr_post_id );

$current_user = wp_get_current_user();
$user_name = '';
$user_email = '';
if( $current_user instanceof WP_User ) {
	$user_email = $current_user->user_email;
	$user_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
	if ( empty( trim( $user_name ) ) ) $user_name = '';
}
if( $attributes ) {
	$json_attributes = wc_esc_json( wp_json_encode( $attributes ) );
} else {
	$json_attributes = '';
}
?>
<div id="cr_qna" class="cr-qna-block" data-attributes="<?php echo $json_attributes; ?>">
	<h2><?php _e( 'Q & A', 'customer-reviews-woocommerce' ); ?></h2>
	<div class="cr-qna-search-block">
		<div class="cr-ajax-qna-search">
			<svg width='1em' height='1em' viewBox='0 0 16 16' class='cr-qna-search-icon' fill='#18B394' xmlns='http://www.w3.org/2000/svg'>
				<path fill-rule='evenodd' d='M10.442 10.442a1 1 0 0 1 1.415 0l3.85 3.85a1 1 0 0 1-1.414 1.415l-3.85-3.85a1 1 0 0 1 0-1.415z'/><path fill-rule='evenodd' d='M6.5 12a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11zM13 6.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0z'/>
			</svg>
			<input name="cr_qna_input_text_search" class="cr-input-text" type="text" placeholder="<?php echo __( 'Search answers', 'customer-reviews-woocommerce' ); ?>">
			<span class="cr-clear-input">
				<svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-x-circle-fill" fill="#18B394" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
				</svg>
			</span>
		</div>
		<button type="button" class="cr-qna-ask-button"><?php _e( 'Ask a question', 'customer-reviews-woocommerce' ); ?></button>
	</div>
	<div class="cr-qna-list-block">
		<?php
		if( isset( $qna ) && is_array( $qna ) && 0 < count( $qna ) ) :
			?><div class="cr-qna-list-block-inner"><?php
			echo CR_Qna::display_qna_list( $qna );
			?></div><?php
			?>
			<button id="cr-show-more-q-id" type="button" data-nonce="<?php echo $nonce_showmore; ?>" data-product="<?php echo $cr_post_id; ?>" data-page="0"<?php if( count( $qna ) >= $total_qna ) echo ' style="display:none"'; ?>><?php echo __( 'Show more', 'customer-reviews-woocommerce' ); ?></button>
			<span id="cr-show-more-q-spinner" style="display:none;"></span>
			<p class="cr-search-no-qna" style="display:none"><?php esc_html_e( 'Sorry, no questions were found', 'customer-reviews-woocommerce' );?></p>
			<?php
		else:
		?>
		<div class="cr-qna-list-empty"><?php _e( 'There are no questions yet', 'customer-reviews-woocommerce' ); ?></div>
		<?php
		endif;
		?>
	</div>
	<div class="cr-qna-new-q-overlay">
		<div class="cr-qna-new-q-form">
			<button class="cr-qna-new-q-form-close">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path class="cr-no-icon" d="M12.12 10l3.53 3.53-2.12 2.12L10 12.12l-3.54 3.54-2.12-2.12L7.88 10 4.34 6.46l2.12-2.12L10 7.88l3.54-3.53 2.12 2.12z"/></g></svg>
			</button>
			<div class="cr-qna-new-q-form-input">
				<p class="cr-qna-new-q-form-title"><?php _e( 'Ask a question', 'customer-reviews-woocommerce' ); ?></p>
				<p class="cr-qna-new-q-form-text"><?php _e( 'Your question will be answered by a store representative or other customers.', 'customer-reviews-woocommerce' ); ?></p>
				<textarea name="question" class="cr-qna-new-q-form-q" rows="3" placeholder="<?php _e( 'Start your question with \'What\', \'How\', \'Why\', etc.', 'customer-reviews-woocommerce' ); ?>"></textarea>
				<input type="text" name="name" class="cr-qna-new-q-form-name" placeholder="<?php _e( 'Your name', 'customer-reviews-woocommerce' ); ?>" value="<?php echo $user_name;?>" autocomplete="name">
				<input type="email" name="email" class="cr-qna-new-q-form-email" placeholder="<?php _e( 'Your email', 'customer-reviews-woocommerce' ); ?>" value="<?php echo $user_email;?>" autocomplete="email">
				<div class="cr-qna-new-q-form-s">
					<?php
					if( 0 < strlen( $cr_recaptcha ) ) {
						echo '<p>' . sprintf( __( 'This site is protected by reCAPTCHA and the Google %1$sPrivacy Policy%2$s and %3$sTerms of Service%4$s apply.', 'customer-reviews-woocommerce' ), '<a href="https://policies.google.com/privacy" rel="noopener noreferrer nofollow" target="_blank">', '</a>', '<a href="https://policies.google.com/terms" rel="noopener noreferrer nofollow" target="_blank">', '</a>' ) . '</p>';
					}
					?>
					<button type="button" data-nonce="<?php echo $nonce; ?>" data-product="<?php echo $cr_post_id; ?>" data-crcptcha="<?php echo $cr_recaptcha; ?>" class="cr-qna-new-q-form-s-b"><?php _e( 'Submit', 'customer-reviews-woocommerce' ); ?></button>
					<button type="button" class="cr-qna-new-q-form-s-b cr-qna-new-q-form-s-p"><img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'img/spinner-dots.svg'; ?>" alt="Loading" /></button>
				</div>
			</div>
			<div class="cr-qna-new-q-form-ok">
				<p class="cr-qna-new-q-form-title"><?php _e( 'Thank you for the question!', 'customer-reviews-woocommerce' ); ?></p>
				<svg class="cr-qna-new-q-form-mail" width="46" height="32" viewBox="0 0 46 32" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g class="cr-qna-new-q-form-mail-svg">
						<path d="M45.0364 3.55812C45.0364 2.70569 44.1884 2.05668 43.274 2.07433H8.4438C6.87799 2.07433 6.09079 3.67762 7.20545 4.60789L20.0137 15.3003L7.19273 26.2178C6.09629 27.1516 6.88452 28.741 8.4438 28.741H43.2771C44.2403 28.741 45.0336 28.0792 45.036 27.2636C45.036 27.2621 45.0364 27.261 45.0364 27.2595V3.55812ZM41.5179 23.6545L31.7073 15.3003L41.5179 7.1105V23.6545ZM12.7176 5.03729H39.0034L25.8605 16.0089L12.7176 5.03729ZM22.5124 17.3862L24.6218 19.1472C25.3076 19.7196 26.413 19.7196 27.0988 19.1472L29.2082 17.3862L39.0632 25.778H12.6578L22.5124 17.3862Z" fill="#18B394"/>
						<path d="M3.9868 11.2593H8.4437C9.41542 11.2593 10.203 10.5962 10.203 9.77787C10.203 8.95958 9.41542 8.29639 8.4437 8.29639H3.9868C3.01508 8.29639 2.22754 8.95958 2.22754 9.77787C2.22754 10.5962 3.01543 11.2593 3.9868 11.2593Z" fill="#18B394"/>
						<path d="M7.97542 15.4075C7.97542 14.5892 7.18788 13.926 6.21616 13.926H1.75926C0.787543 13.926 0 14.5892 0 15.4075C0 16.2258 0.787543 16.889 1.75926 16.889H6.21616C7.18754 16.889 7.97542 16.2258 7.97542 15.4075Z" fill="#18B394"/>
						<path d="M10.203 21.0371C10.203 20.2189 9.41542 19.5557 8.4437 19.5557H3.9868C3.01508 19.5557 2.22754 20.2189 2.22754 21.0371C2.22754 21.8554 3.01508 22.5186 3.9868 22.5186H8.4437C9.41542 22.5186 10.203 21.8554 10.203 21.0371Z" fill="#18B394"/>
					</g>
				</svg>
				<p class="cr-qna-new-q-form-text"><?php _e( 'Your question has been received and will be answered soon. Please do not submit the same question again.', 'customer-reviews-woocommerce' ); ?></p>
				<div class="cr-qna-new-q-form-s">
					<button type="button" class="cr-qna-new-q-form-s-b"><?php _e( 'OK', 'customer-reviews-woocommerce' ); ?></button>
				</div>
			</div>
			<div class="cr-qna-new-q-form-error">
				<p class="cr-qna-new-q-form-title"><?php _e( 'Error', 'customer-reviews-woocommerce' ); ?></p>
				<img class="cr-qna-new-q-form-mail" src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'img/warning.svg'; ?>" alt="Warning" height="32" />
				<p class="cr-qna-new-q-form-text">
					<?php _e( 'An error occurred when saving your question. Please report it to the website administrator. Additional information:', 'customer-reviews-woocommerce' ); ?>
					<span class="cr-qna-new-q-form-text-additional"></span>
				</p>
			</div>
		</div>
		<div class="cr-qna-new-q-form cr-qna-new-a-form">
			<button class="cr-qna-new-q-form-close">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path class="cr-no-icon" d="M12.12 10l3.53 3.53-2.12 2.12L10 12.12l-3.54 3.54-2.12-2.12L7.88 10 4.34 6.46l2.12-2.12L10 7.88l3.54-3.53 2.12 2.12z"/></g></svg>
			</button>
			<div class="cr-qna-new-q-form-input">
				<p class="cr-qna-new-q-form-title"><?php _e( 'Add an answer', 'customer-reviews-woocommerce' ); ?></p>
				<p class="cr-qna-new-q-form-text"></p>
				<textarea name="question" class="cr-qna-new-q-form-q" rows="3" placeholder="<?php _e( 'Write your answer', 'customer-reviews-woocommerce' ); ?>"></textarea>
				<input type="text" name="name" class="cr-qna-new-q-form-name" placeholder="<?php _e( 'Your name', 'customer-reviews-woocommerce' ); ?>" value="<?php echo $user_name;?>" autocomplete="name">
				<input type="email" name="email" class="cr-qna-new-q-form-email" placeholder="<?php _e( 'Your email', 'customer-reviews-woocommerce' ); ?>" value="<?php echo $user_email;?>" autocomplete="email">
				<div class="cr-qna-new-q-form-s">
					<?php
					if( 0 < strlen( $cr_recaptcha ) ) {
						echo '<p>' . sprintf( __( 'This site is protected by reCAPTCHA and the Google %1$sPrivacy Policy%2$s and %3$sTerms of Service%4$s apply.', 'customer-reviews-woocommerce' ), '<a href="https://policies.google.com/privacy" rel="noopener noreferrer nofollow" target="_blank">', '</a>', '<a href="https://policies.google.com/terms" rel="noopener noreferrer nofollow" target="_blank">', '</a>' ) . '</p>';
					}
					?>
					<button type="button" data-nonce="<?php echo $nonce_ans; ?>" data-product="<?php echo $cr_post_id; ?>" data-crcptcha="<?php echo $cr_recaptcha; ?>" class="cr-qna-new-q-form-s-b"><?php _e( 'Submit', 'customer-reviews-woocommerce' ); ?></button>
					<button type="button" class="cr-qna-new-q-form-s-b cr-qna-new-q-form-s-p"><img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'img/spinner-dots.svg'; ?>" alt="Loading" /></button>
				</div>
			</div>
			<div class="cr-qna-new-q-form-ok">
				<p class="cr-qna-new-q-form-title"><?php _e( 'Thank you for the answer!', 'customer-reviews-woocommerce' ); ?></p>
				<svg class="cr-qna-new-q-form-mail" width="46" height="32" viewBox="0 0 46 32" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g class="cr-qna-new-q-form-mail-svg">
						<path d="M45.0364 3.55812C45.0364 2.70569 44.1884 2.05668 43.274 2.07433H8.4438C6.87799 2.07433 6.09079 3.67762 7.20545 4.60789L20.0137 15.3003L7.19273 26.2178C6.09629 27.1516 6.88452 28.741 8.4438 28.741H43.2771C44.2403 28.741 45.0336 28.0792 45.036 27.2636C45.036 27.2621 45.0364 27.261 45.0364 27.2595V3.55812ZM41.5179 23.6545L31.7073 15.3003L41.5179 7.1105V23.6545ZM12.7176 5.03729H39.0034L25.8605 16.0089L12.7176 5.03729ZM22.5124 17.3862L24.6218 19.1472C25.3076 19.7196 26.413 19.7196 27.0988 19.1472L29.2082 17.3862L39.0632 25.778H12.6578L22.5124 17.3862Z" fill="#18B394"/>
						<path d="M3.9868 11.2593H8.4437C9.41542 11.2593 10.203 10.5962 10.203 9.77787C10.203 8.95958 9.41542 8.29639 8.4437 8.29639H3.9868C3.01508 8.29639 2.22754 8.95958 2.22754 9.77787C2.22754 10.5962 3.01543 11.2593 3.9868 11.2593Z" fill="#18B394"/>
						<path d="M7.97542 15.4075C7.97542 14.5892 7.18788 13.926 6.21616 13.926H1.75926C0.787543 13.926 0 14.5892 0 15.4075C0 16.2258 0.787543 16.889 1.75926 16.889H6.21616C7.18754 16.889 7.97542 16.2258 7.97542 15.4075Z" fill="#18B394"/>
						<path d="M10.203 21.0371C10.203 20.2189 9.41542 19.5557 8.4437 19.5557H3.9868C3.01508 19.5557 2.22754 20.2189 2.22754 21.0371C2.22754 21.8554 3.01508 22.5186 3.9868 22.5186H8.4437C9.41542 22.5186 10.203 21.8554 10.203 21.0371Z" fill="#18B394"/>
					</g>
				</svg>
				<p class="cr-qna-new-q-form-text"><?php _e( 'Your answer has been received and will be published soon. Please do not submit the same answer again.', 'customer-reviews-woocommerce' ); ?></p>
				<div class="cr-qna-new-q-form-s">
					<button type="button" class="cr-qna-new-q-form-s-b"><?php _e( 'OK', 'customer-reviews-woocommerce' ); ?></button>
				</div>
			</div>
			<div class="cr-qna-new-q-form-error">
				<p class="cr-qna-new-q-form-title"><?php _e( 'Error', 'customer-reviews-woocommerce' ); ?></p>
				<img class="cr-qna-new-q-form-mail" src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'img/warning.svg'; ?>" alt="Warning" height="32" />
				<p class="cr-qna-new-q-form-text">
					<?php _e( 'An error occurred when saving your answer. Please report it to the website administrator. Additional information:', 'customer-reviews-woocommerce' ); ?>
					<span class="cr-qna-new-q-form-text-additional"></span>
				</p>
			</div>
		</div>
	</div>
</div>
