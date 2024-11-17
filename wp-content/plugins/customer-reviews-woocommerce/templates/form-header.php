<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="noindex">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo esc_html( $cr_form_header . ' - ' . get_option( 'ivole_shop_name', get_bloginfo( 'name', 'display' ) ) ); ?></title>
		<link rel="stylesheet" href="<?php echo $cr_form_css; ?>">
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
		<script defer src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
		<script defer src="<?php echo $cr_form_js; ?>"></script>
		<script>
			var crAjaxURL = "<?php echo esc_url_raw( $cr_form_ajax ); ?>";
			var crMediaUploadLimit = "<?php echo intval( $cr_form_media_upload_limit ); ?>";
			var crMediaUploadMaxSize = "<?php echo intval( $cr_form_media_upload_max_size ); ?>";
			var crErrorMaxFileSize = "<?php echo strval( $cr_form_error_max_file_size ); ?>";
			var crErrorFileType = "<?php echo strval( $cr_form_error_file_type ); ?>";
		</script>
		<style>
			.cr-form-header, .cr-form-top-line {
				background-color: <?php echo esc_attr( $cr_form_color1 ); ?> !important;
			}
			.cr-form-item-title div, .cr-form-customer-title {
				background-color: <?php echo esc_attr( $cr_form_color1 ); ?> !important;
				color: <?php echo esc_attr( $cr_form_color2 ); ?> !important;
			}
			.cr-form-item-rating-radio .cr-form-item-outer {
				border-color: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
			.cr-form-item-rating-radio .cr-form-item-inner {
				background-color: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
			.cr-form-item-price {
				background-color: <?php echo esc_attr( $cr_form_color1 ); ?> !important;
				color: <?php echo esc_attr( $cr_form_color2 ); ?> !important;
			}
			.cr-form-customer-name-option > span {
				border-color: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
			.cr-form-terms a {
				color: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
			.cr-form-customer-name-option.cr-form-active-name > span {
				color: <?php echo esc_attr( $cr_form_color2 ); ?> !important;
				background-color: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
			.cr-form-submit {
				background-color: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
			.cr-form-submit .cr-form-submit-label {
				color: <?php echo esc_attr( $cr_form_color2 ); ?> !important;
			}
			.cr-form-submit .cr-form-submit-loader::after {
				background-color: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
			.cr-form-edit {
				color: <?php echo esc_attr( $cr_form_color2 ); ?> !important;
				background-color: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
			.cr-form-edit svg path {
				fill: <?php echo esc_attr( $cr_form_color2 ); ?> !important;
			}
			.cr-form-item-media-preview .cr-upload-images-containers .cr-upload-images-delete {
				background-color: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
			.cr-form-item-media-preview .cr-upload-images-containers .cr-upload-images-delete .cr-no-icon {
				fill: <?php echo esc_attr( $cr_form_color2 ); ?> !important;
			}
			.cr-form-item-media-preview .cr-upload-images-containers .cr-upload-images-pbar .cr-upload-images-pbarin {
				background-color: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
			.cr-form-item-media-preview .cr-upload-images-containers .cr-upload-video-thumbnail {
				fill: <?php echo esc_attr( $cr_form_color3 ); ?> !important;
			}
		</style>
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="padding:0;">
		<div class="cr-form-wrapper<?php echo is_rtl() ? ' cr-rtl' : ''; ?>" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
			<div class="cr-form-header"></div>
			<div class="cr-form<?php if( $cr_form_edit ) echo ' cr-form-edit-submit'; ?>" data-formid="<?php echo esc_attr( $cr_form_id ); ?>">
				<div class="cr-form-top-line"></div>
				<div class="cr-form-submitted cr-form-body">
					<div class="cr-form-title">
						<?php echo $cr_form_subm_header; ?>
					</div>
					<div class="cr-form-description">
						<?php echo $cr_form_subm_desc; ?>
					</div>
					<div class="cr-form-edit-container">
						<div class="cr-form-edit">
							<svg data-v-60e65a12="" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path data-v-60e65a12="" d="M14 7.875H3.387l4.944 4.891L7.075 14 0 7l7.075-7 1.247 1.234-4.935 4.891H14v1.75z" fill="#fff"></path>
							</svg>
							<span>
								<?php echo $cr_form_edit_label; ?>
							</span>
						</div>
					</div>
				</div>
				<div class="cr-form-content cr-form-body">
					<div class="cr-form-title">
						<?php echo $cr_form_header; ?>
					</div>
					<div class="cr-form-description">
						<div style="max-width: 515px; line-height: 1.6; margin: 0 auto;">
							<?php echo $cr_form_desc; ?>
						</div>
					</div>
					<div class="cr-form-required">
						<?php echo $cr_form_required; ?>
					</div>
