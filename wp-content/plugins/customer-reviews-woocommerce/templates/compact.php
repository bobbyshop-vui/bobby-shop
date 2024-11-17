<div class="cr-badge badge_size_compact<?php echo $badgeClass; ?>">

	<div class="badge__verified"><?php echo $badgeVerified; ?></div>
	<div class="badge__middle">
		<div class="badge__stars">
			<?php foreach ($avRating as $ratingStar) : ?>
				<div class="badge__star">
					<div class="badge__star-fill-container" style="width: <?php echo $ratingStar; ?>">
						<svg class="badge__star-fill" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill="currentColor" d="M6.7792 10.5333L10.9687 13L9.85696 8.35037L13.5584 5.22202L8.68416 4.81842L6.7792 0.433411L4.87425 4.81842L0 5.22202L3.70144 8.35037L2.58966 13L6.7792 10.5333Z" />
						</svg>
					</div>
					<svg class="badge__star-empty" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path stroke="currentColor" d="M7.4746 10.1024L7.22091 9.95304L6.96723 10.1024L3.79117 11.9724L4.62945 8.46665L4.70105 8.16722L4.46591 7.96849L1.68784 5.62055L5.35722 5.31671L5.65536 5.29202L5.77456 5.01764L7.22091 1.68827L8.66727 5.01764L8.78647 5.29202L9.08461 5.31671L12.754 5.62055L9.97592 7.96849L9.74078 8.16722L9.81238 8.46665L10.6507 11.9724L7.4746 10.1024Z"/>
					</svg>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="badge__reviews"><?php echo $strCount; ?></div>

</div>
