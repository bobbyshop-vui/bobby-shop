<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="cr-form-customer">
	<div class="cr-form-customer-title-ctr">
		<div class="cr-form-customer-title">
			<?php echo $cr_form_cust_title; ?>
		</div>
	</div>
	<div class="cr-form-item-container">
		<div class="cr-form-customer-container">
			<div class="cr-form-customer-name">
				<div class="cr-form-customer-name-preview">
					<div class="cr-form-customer-name-preview-name">
						<?php echo $cr_form_cust_preview_name; ?>
					</div>
				</div>
				<div class="cr-form-customer-name-options">
					<?php if ( $cr_form_cust_name ) : ?>
					<div class="cr-form-customer-name-option<?php if( $cr_form_cust_preview_name === $cr_form_cust_name ) echo ' cr-form-active-name' ?>">
						<span><?php echo $cr_form_cust_name; ?></span>
					</div>
					<?php endif; ?>
					<?php if ( $cr_form_cust_name_w_dot ) : ?>
						<div class="cr-form-customer-name-option<?php if( $cr_form_cust_preview_name === $cr_form_cust_name_w_dot ) echo ' cr-form-active-name' ?>">
							<span><?php echo $cr_form_cust_name_w_dot; ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $cr_form_cust_f_name ) : ?>
						<div class="cr-form-customer-name-option<?php if( $cr_form_cust_preview_name === $cr_form_cust_f_name ) echo ' cr-form-active-name' ?>">
							<span><?php echo $cr_form_cust_f_name; ?></span>
						</div>
					<?php endif; ?>
					<div class="cr-form-customer-name-option<?php if( $cr_form_cust_preview_name === $cr_form_cust_anonymous ) echo ' cr-form-active-name' ?>">
						<span><?php echo $cr_form_cust_anonymous; ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="cr-form-terms">
	<?php echo $cr_form_terms; ?>
</div>
<div class="cr-form-submit">
	<span class="cr-form-submit-label"><?php echo $cr_form_submit; ?></span>
	<span class="cr-form-submit-loader"></span>
</div>
