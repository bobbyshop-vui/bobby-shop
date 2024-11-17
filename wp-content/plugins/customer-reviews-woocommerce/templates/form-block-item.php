<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="cr-form-item" data-itemid="<?php echo $cr_form_item_id; ?>">
	<div class="cr-form-item-title">
		<div>
			<?php echo $cr_form_item_name; ?>
		</div>
	</div>
	<?php if( $cr_form_item_image ) : ?>
		<div class="cr-form-item-image-cnt">
			<div class="cr-form-item-image" style="background-image: url(<?php echo esc_url( $cr_form_item_image ); ?>)"></div>
			<?php if( $cr_form_item_price ) : ?>
				<div class="cr-form-item-price">
					<?php echo $cr_form_item_price; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="cr-form-item-question">
		<div class="cr-form-item-subcontainer">
			<div>
				<div class="cr-form-item-subheader">
					<span>
						<?php echo $cr_form_item_rating_name; ?>
					</span>
					<span style="color: #f68609;">*</span>
				</div>
				<div style="overflow: hidden;">
					<div class="cr-form-item-question-row1">
						<div style="height: 48px; line-height: 48px; display: inline-block; text-align: center; vertical-align: top; float: left; width: 15%;"></div>
						<?php for( $i = 1; $i <= 5; $i++ ): ?>
							<div style="height: 48px; line-height: 48px; display: inline-block; text-align: center; vertical-align: top; float: left; width: 14%;">
								<?php echo $i; ?>
							</div>
						<?php endfor; ?>
						<div style="height: 48px; line-height: 48px; display: inline-block; text-align: center; vertical-align: top; float: left; width: 15%;"></div>
					</div>
					<div class="cr-form-item-question-row2">
						<div style="height: 48px; line-height: 48px; display: inline-block; text-align: center; vertical-align: top; float: left; width: 15%;">
							<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512" style="width: 40px; height: 40px; top: 4px; position: relative; display: inline-block;">
								<circle cx="184" cy="232" r="24"/><path d="M256 288c45.42 0 83.62 29.53 95.71 69.83a8 8 0 01-7.87 10.17H168.15a8 8 0 01-7.82-10.17C172.32 317.53 210.53 288 256 288z"/>
								<circle cx="328" cy="232" r="24"/><circle cx="256" cy="256" r="208" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/>
							</svg>
						</div>
						<?php for( $i = 1; $i <= 5; $i++ ): ?>
							<div class="cr-form-item-rating-radio">
								<div class="cr-form-item-outer">
									<div class="cr-form-item-inner<?php if( $i === $cr_form_rating ) echo ' cr-form-active-radio'; ?>" data-rating="<?php echo $i; ?>"></div>
								</div>
							</div>
						<?php endfor; ?>
						<div style="height: 48px; line-height: 48px; display: inline-block; text-align: center; vertical-align: top; float: left; width: 15%;">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 40px; height: 40px; top: 4px; position: relative; display: inline-block;">
								<circle cx="184" cy="232" r="24"/><path d="M256.05 384c-45.42 0-83.62-29.53-95.71-69.83a8 8 0 017.82-10.17h175.69a8 8 0 017.82 10.17c-11.99 40.3-50.2 69.83-95.62 69.83z"/><circle cx="328" cy="232" r="24"/>
								<circle cx="256" cy="256" r="208" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/>
							</svg>
						</div>
					</div>
					<div style="clear: both;"></div>
				</div>
			</div>
		</div>
	</div>
	<div class="cr-form-item-comment cr-form-item-container<?php if( $cr_form_item_comment_req ) { echo ' cr-form-required'; } ?>">
		<div class="cr-form-item-subcontainer">
			<div>
				<div class="cr-form-item-subheader">
					<span>
						<?php echo $cr_form_item_comment_name; ?>
					</span>
					<?php if( $cr_form_item_comment_req ) : ?>
						<span style="color: #f68609;">*</span>
					<?php endif; ?>
				</div>
				<textarea placeholder="<?php echo $cr_form_item_comment_placeholder; ?>" rows="1"><?php esc_html_e( $cr_form_comment ); ?></textarea>
			</div>
		</div>
	</div>
	<?php
		if( $cr_form_media_enabled ) :
			?>
			<div class="cr-form-item-media cr-form-item-container">
				<div class="cr-form-item-subcontainer<?php echo ( 0 < count( $cr_form_item_media_array ) ? ' cr-form-visible' : '' ); ?>">
					<div>
						<div class="cr-form-item-subheader">
							<span>
								<?php echo $cr_form_item_media_name; ?>
							</span>
						</div>
						<div class="cr-form-item-media-error"></div>
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
				</div>
			</div>
			<?php
		endif;
	?>
</div>
