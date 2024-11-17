<div class="cr-badge badge_size_wide<?php echo $badgeClass; ?>" style="<?php echo $badgeStyle; ?>">

	<div class="badge__store"><?php echo $storeStats['storeName']; ?></div>

	<div class="badge__info badge__nowrap">
	<div class="badge__logo"></div>

	<div class="badge__reviews"><?php echo $strCountW; ?></div>

	<div class="verified"><?php echo $badgeVerifiedW; ?></div>

		<?php if( $separateRatings ) : ?>

			<div class="badge__stars">
				<?php foreach ($stRating as $ratingStar) : ?>
					<div class="badge__star">
						<div class="badge__star-icon badge__star-icon_type_empty"></div>
						<div class="badge__star-icon badge__star-icon_type_fill" style="width: <?php echo $ratingStar; ?>"></div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="badge__rating rating">
				<?php echo $strStoreRatingW; ?>
			</div>

			<div class="badge__stars">
				<?php foreach ($prRating as $ratingStar) : ?>
					<div class="badge__star">
						<div class="badge__star-icon badge__star-icon_type_empty"></div>
						<div class="badge__star-icon badge__star-icon_type_fill" style="width: <?php echo $ratingStar; ?>"></div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="badge__rating rating">
				<?php echo $strProdRatingW; ?>
			</div>

		<?php else : ?>

			<div class="badge__stars">
				<?php foreach ($avRating as $ratingStar) : ?>
					<div class="badge__star">
						<div class="badge__star-icon badge__star-icon_type_empty"></div>
						<div class="badge__star-icon badge__star-icon_type_fill" style="width: <?php echo $ratingStar; ?>"></div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="badge__rating rating">
				<?php echo $strAvRatingW; ?>
			</div>

		<?php endif; ?>

	</div>

	<?php if( $verifiedPage ) : ?>
		<a href="<?php echo $verifiedPage; ?>" rel="nofollow noopener noreferrer" target="_blank" aria-label="<?php echo esc_attr( $storeStats['storeName'] ); ?>">
			<span class="badge__link"></span>
		</a>
	<?php else : ?>
		<span class="badge__link"></span>
	<?php endif; ?>

</div>
