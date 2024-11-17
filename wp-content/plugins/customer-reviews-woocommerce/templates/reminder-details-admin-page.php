<div class="wrap">
	<a href="<?php echo esc_url( $backlink ); ?>" class="cr-reminder-details-nav-back-a">
		<div class="cr-reminder-details-nav-back">
			<span class="dashicons dashicons-arrow-left-alt"></span>
			<span><?php echo esc_html__( 'Reminders', 'customer-reviews-woocommerce' ); ?></span>
		</div>
	</a>
	<h1 class="wp-heading-inline">
		<?php
			echo sprintf( __( 'Reminder #%d', 'customer-reviews-woocommerce' ), $reminder_id );
		?>
	</h1>
	<hr class="wp-header-end">
	<br class="clear" />
	<div class="cr-reminder-details-cont">
		<div class="cr-reminder-details-cont-a">
			<div class="cr-reminder-details-subcont">
				<div class="cr-reminder-details-subcont-h">
					<?php echo esc_html__( 'Order details', 'customer-reviews-woocommerce' ); ?>
				</div>
				<div class="cr-reminder-details-subcont-b">
					<table class="cr-reminder-details-table">
						<tbody>
							<tr>
								<td>
									<?php echo esc_html__( 'Order', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'Customer order related to the review reminder', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $order_id ) ); ?>"><?php echo esc_html( $order_id ); ?></a>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo esc_html__( 'Order created date', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'Date when the order was created', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<?php echo esc_html( $order_created ); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo esc_html__( 'Order paid date', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'Date when the order was paid for', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<?php echo esc_html( $order_paid ); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo esc_html__( 'Order completed date', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'Date when the order status was marked as completed', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<?php echo esc_html( $order_completed ); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo esc_html__( 'Customer name', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'Customer\'s name for receiving a review invitation', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<?php echo esc_html( $customer_name ); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo esc_html__( 'Customer email', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'Customer\'s email address for receiving a review invitation', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<?php echo $customer_email; ?></a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="cr-reminder-details-subcont">
				<div class="cr-reminder-details-subcont-h">
					<?php echo esc_html__( 'Reminder details', 'customer-reviews-woocommerce' ); ?>
				</div>
				<div class="cr-reminder-details-subcont-b">
					<table class="cr-reminder-details-table">
						<tbody>
							<tr>
								<td>
									<?php echo esc_html__( 'Status', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'Current status of this review reminder', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<?php echo esc_html( $reminder_status ); ?>
								</td>
							</tr>
							<?php if ( $reminder_error ) : ?>
								<tr>
									<td>
										<?php echo esc_html__( 'Error description', 'customer-reviews-woocommerce' ); ?>
									</td>
									<td class="cr-reminder-details-help">
										<?php echo CR_Admin::cr_help_tip( __( 'Details about the error that occurred while sending this review reminder', 'customer-reviews-woocommerce' ) ); ?>
									</td>
									<td>
										<?php echo esc_html( $reminder_error ); ?>
									</td>
								</tr>
							<?php endif; ?>
							<?php if ( $reminder_cancelation ) : ?>
								<tr>
									<td>
										<?php echo esc_html__( 'Cancelation reason', 'customer-reviews-woocommerce' ); ?>
									</td>
									<td class="cr-reminder-details-help">
										<?php echo CR_Admin::cr_help_tip( __( 'Details about the reason why this review reminder was canceled', 'customer-reviews-woocommerce' ) ); ?>
									</td>
									<td>
										<?php echo esc_html( $reminder_cancelation ); ?>
									</td>
								</tr>
							<?php endif; ?>
							<tr>
								<td>
									<?php echo esc_html__( 'Verification', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'An indicator showing whether this review reminder was sent with the independently verified or no verification setting', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<?php echo esc_html( $reminder_verification ); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo esc_html__( 'Channel', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'A channel for sending this review reminder', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<?php echo esc_html( $reminder_channel ); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo esc_html__( 'Type', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'An indicator showing whether this review reminder was sent manually or automatically', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<?php echo esc_html( $reminder_type ); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo esc_html__( 'Language', 'customer-reviews-woocommerce' ); ?>
								</td>
								<td class="cr-reminder-details-help">
									<?php echo CR_Admin::cr_help_tip( __( 'Language of this review reminder', 'customer-reviews-woocommerce' ) ); ?>
								</td>
								<td>
									<?php echo esc_html( $reminder_language ); ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="cr-reminder-details-cont-b">
			<div class="cr-reminder-details-subcont">
				<div class="cr-reminder-details-subcont-h">
					<?php echo esc_html__( 'Technical information', 'customer-reviews-woocommerce' ); ?>
				</div>
				<div class="cr-reminder-details-subcont-b">
					<div class="cr-reminder-details-line">
						<div class="cr-reminder-details-line-h">
							<?php
								echo esc_html__( 'ID', 'customer-reviews-woocommerce' ) .
									CR_Admin::cr_help_tip( __( 'Internal ID number for this review reminder', 'customer-reviews-woocommerce' ) );
							?>
						</div>
						<span><?php echo esc_html( $reminder_id ); ?></span>
					</div>
					<div class="cr-reminder-details-line">
						<div class="cr-reminder-details-line-h">
							<?php
								echo esc_html__( 'Tracking ID', 'customer-reviews-woocommerce' ) .
									CR_Admin::cr_help_tip( __( 'External ID number for this review reminder', 'customer-reviews-woocommerce' ) );
							?>
						</div>
						<span><?php echo esc_html( $ext_id ); ?></span>
					</div>
				</div>
			</div>
			<div class="cr-reminder-details-subcont">
				<div class="cr-reminder-details-subcont-h">
					<?php echo esc_html__( 'Tracking details', 'customer-reviews-woocommerce' ); ?>
				</div>
				<div class="cr-reminder-details-subcont-b">
					<div class="cr-reminder-details-line">
						<div class="cr-reminder-details-line-h">
							<?php
								echo esc_html__( 'Reminder created date', 'customer-reviews-woocommerce' ) .
									CR_Admin::cr_help_tip( __( 'Date when this review reminder was created', 'customer-reviews-woocommerce' ) );
							?>
						</div>
						<span><?php echo esc_html( $reminder_created ); ?></span>
					</div>
					<div class="cr-reminder-details-line">
						<div class="cr-reminder-details-line-h">
							<?php
								if ( 'canceled' === $reminder_status_code ) {
									echo esc_html__( 'Reminder canceled date', 'customer-reviews-woocommerce' ) .
										CR_Admin::cr_help_tip( __( 'Date when this review reminder was canceled', 'customer-reviews-woocommerce' ) );
								} else {
									echo esc_html__( 'Message sent date', 'customer-reviews-woocommerce' ) .
										CR_Admin::cr_help_tip( __( 'Date when this review reminder was sent to a customer', 'customer-reviews-woocommerce' ) );
								}
							?>
						</div>
						<span><?php echo esc_html( $reminder_sent ); ?></span>
					</div>
					<div class="cr-reminder-details-line">
						<div class="cr-reminder-details-line-h">
							<?php
								echo esc_html__( 'Message opened date', 'customer-reviews-woocommerce' ) .
									CR_Admin::cr_help_tip( __( 'Date when a customer viewed this review reminder', 'customer-reviews-woocommerce' ) );
							?>
						</div>
						<span><?php echo esc_html( $reminder_opened ); ?></span>
					</div>
					<div class="cr-reminder-details-line">
						<div class="cr-reminder-details-line-h">
							<?php
								echo esc_html__( 'Review form opened date', 'customer-reviews-woocommerce' ) .
									CR_Admin::cr_help_tip( __( 'Date when a customer opened an aggregated review form from this review reminder', 'customer-reviews-woocommerce' ) );
							?>
						</div>
						<span><?php echo esc_html( $form_opened ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>
