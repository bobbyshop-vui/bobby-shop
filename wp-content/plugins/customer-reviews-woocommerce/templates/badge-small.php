<div class="cr-badge badge_size_small<?php echo $badgeClass; ?>" style="<?php echo $badgeStyle; ?>">

	<div class="badge__store"><?php echo $storeStats['storeName']; ?></div>

	<div class="badge__nowrap">
		<div class="badge__stars">
			<?php foreach ($avRating as $ratingStar) : ?>
				<div class="badge__star">
					<div class="badge__star-icon badge__star-icon_type_empty"></div>
					<div class="badge__star-icon badge__star-icon_type_fill" style="width: <?php echo $ratingStar; ?>"></div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="badge__verified verified">
			<div class="verified__logo"></div>
			<div class="verified__text"><?php echo $badgeVerified; ?></div>
		</div>
	</div>

	<div class="badge__rating rating">
		<?php if( $separateRatings ) : ?>
			<span class="rating__store"><?php echo $strStoreRating; ?></span> <span class="rating__reviews">(<?php echo $strCount; ?>)</span> | <span class="rating__product"><?php echo $strProdRating; ?></span>
		<?php else : ?>
			<span class="rating__store"><?php echo $strAvRating; ?></span> <span class="rating__reviews">(<?php echo $strCount; ?>)</span>
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
