<div class="cr-badge badge-vs badge_size_wide<?php echo $badgeClass; ?>" style="<?php echo $badgeStyle; ?>">

	<div class="cr-badge-vs-flex">

		<div class="badge__logo"></div>

		<div class="badge__info badge__nowrap">

			<div class="badge__store"><?php echo $storeStats['storeName']; ?></div>

				<div class="badge__details">

					<div class="badge__rating-container">

						<?php if( 0 < floatval( $storeStats['storeRating'] ) ) : ?>
							<div class="badge__rating-line">
								<div class="badge__rating rating">
									<span><?php echo $strStoreRatingVS; ?></span><span><?php echo $storeStats['storeRating'] . ' / 5'; ?></span>
								</div>
								<div class="badge__stars">
									<?php foreach ($stRating as $ratingStar) : ?>
										<div class="badge__star">
											<div class="badge__star-icon badge__star-icon--empty"></div>
											<div class="badge__star-icon badge__star-icon--fill" style="width: <?php echo $ratingStar; ?>"></div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>

						<div class="badge__rating-line">
							<div class="badge__rating rating">
								<span><?php echo $strProdRatingVS; ?></span><span><?php echo $storeStats['productRating'] . ' / 5'; ?></span>
							</div>
							<div class="badge__stars">
								<?php foreach ($prRating as $ratingStar) : ?>
									<div class="badge__star">
										<div class="badge__star-icon badge__star-icon--empty"></div>
										<div class="badge__star-icon badge__star-icon--fill" style="width: <?php echo $ratingStar; ?>"></div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<div class="badge__reviews">
						<?php echo $strCount; ?>
					</div>
				</div>
		</div>

	</div>

	<?php if( $verifiedPage ) : ?>
		<a href="<?php echo $verifiedPage; ?>" rel="nofollow noopener noreferrer" target="_blank" aria-label="<?php echo esc_attr( $storeStats['storeName'] ); ?>">
			<span class="badge__link"></span>
		</a>
	<?php else : ?>
		<span class="badge__link"></span>
	<?php endif; ?>

</div>
