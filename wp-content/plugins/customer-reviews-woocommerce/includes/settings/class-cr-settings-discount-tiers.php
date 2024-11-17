<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Discount_Tiers' ) ):

	class CR_Discount_Tiers {

		private static $option_not_available = '';
		public static $tiers = array( 1, 2, 3 );
		public static $tiers_settings = array(
			'cr_media_count' => 'cr_media_count',
			'cr_coupon_type' => 'cr_coupon_type',
			'cr_coupon_enable_for_role' => 'cr_coupon_enable_for_role',
			'cr_coupon_enabled_roles' => 'cr_coupon_enabled_roles',
			'cr_existing_coupon' => 'cr_existing_coupon',
			'cr_coupon__discount_type' => 'cr_coupon__discount_type',
			'cr_coupon__coupon_amount' => 'cr_coupon__coupon_amount',
			'cr_coupon__free_shipping' => 'cr_coupon__free_shipping',
			'cr_coupon__expires_days' => 'cr_coupon__expires_days',
			'cr_coupon__minimum_amount' => 'cr_coupon__minimum_amount',
			'cr_coupon__maximum_amount' => 'cr_coupon__maximum_amount',
			'cr_coupon__individual_use' => 'cr_coupon__individual_use',
			'cr_coupon__exclude_sale_items' => 'cr_coupon__exclude_sale_items',
			'cr_coupon__product_ids' => 'cr_coupon__product_ids',
			'cr_coupon__exclude_product_ids' => 'cr_coupon__exclude_product_ids',
			'cr_coupon__product_categories' => 'cr_coupon__product_categories',
			'cr_coupon__excluded_product_categories' => 'cr_coupon__excluded_product_categories',
			'cr_coupon__usage_limit' => 'cr_coupon__usage_limit',
			'cr_coupon_prefix' => 'cr_coupon_prefix',
			'cr_coupon__sharing' => 'cr_coupon__sharing'
		);

		public static function show_coupon_tiers_table( $value ) {
			self::$option_not_available =  esc_html__( 'The option is not available with the coupon type selected for this discount tier.', 'customer-reviews-woocommerce' );
			$table_tiers = self::read_coupon_tiers_table();

			?>
			<tr valign="top">
				<td colspan="2" style="padding-left:0px;padding-right:0px;">
					<table class="cr-coupon-tiers-table widefat">
						<thead>
							<tr class="cr-alternate">
								<th class="cr-coupon-tiers-table-th">
								</th>
								<th class="cr-coupon-tiers-table-th">
									<?php
									esc_html_e( 'Discount Tier 1', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Use discount tiers to offer customers different coupons depending on the number of photos or videos uploaded with reviews.', 'customer-reviews-woocommerce' ) );
									?>
								</th>
								<th class="cr-coupon-tiers-table-th">
									<?php
									esc_html_e( 'Discount Tier 2', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Use discount tiers to offer customers different coupons depending on the number of photos or videos uploaded with reviews.', 'customer-reviews-woocommerce' ) );
									?>
								</th>
								<th class="cr-coupon-tiers-table-th">
									<?php
									esc_html_e( 'Discount Tier 3', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Use discount tiers to offer customers different coupons depending on the number of photos or videos uploaded with reviews.', 'customer-reviews-woocommerce' ) );
									?>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Photos/videos uploaded', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Set how many media files (photos or videos) a customer should upload with their review to qualify for each of the discount tiers.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										?>
										<td class="cr-coupon-tiers-table-td cr-coupon-tiers-table-td-<?php echo $tier; ?>">
											<select class="cr-coupon-tiers-table-select" name="<?php echo self::$tiers_settings['cr_media_count'] . $tier; ?>">
												<?php
												$svalue = intval( $table_tiers[self::$tiers_settings['cr_media_count']][$tier] );
												echo '<option value="0"' . ( 0 >= $svalue ? ' selected' : '' ) . '>' . esc_html__( '0 or more', 'customer-reviews-woocommerce' ) . '</option>';
												echo '<option value="1"' . ( 1 === $svalue ? ' selected' : '' ) . '>' . esc_html__( '1 or more', 'customer-reviews-woocommerce' ) . '</option>';
												echo '<option value="2"' . ( 2 === $svalue ? ' selected' : '' ) . '>' . esc_html__( '2 or more', 'customer-reviews-woocommerce' ) . '</option>';
												echo '<option value="3"' . ( 3 === $svalue ? ' selected' : '' ) . '>' . esc_html__( '3 or more', 'customer-reviews-woocommerce' ) . '</option>';
												echo '<option value="4"' . ( 4 <= $svalue ? ' selected' : '' ) . '>' . esc_html__( '4 or more', 'customer-reviews-woocommerce' ) . '</option>';
												?>
											</select>
										</td>
										<?php
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Coupon type', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Choose if a new unique coupon will be created, an existing coupon will be used or no coupon will be offered to customers at each of the discount tiers.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										?>
										<td class="cr-coupon-tiers-table-td cr-coupon-tiers-table-td-<?php echo $tier; ?>">
											<select class="cr-coupon-tiers-table-select cr-coupon-to-use" name="<?php echo self::$tiers_settings['cr_coupon_type'] . $tier; ?>">
												<?php
												$svalue = strval( $table_tiers[self::$tiers_settings['cr_coupon_type']][$tier] );
												echo '<option value="none"' . ( 'static' !== $svalue && 'dynamic' !== $svalue ? ' selected' : '' ) . '>' . esc_html__( 'No coupon', 'customer-reviews-woocommerce' ) . '</option>';
												echo '<option value="static"' . ( 'static' === $svalue ? ' selected' : '' ) . '>' . esc_html__( 'Existing coupon', 'customer-reviews-woocommerce' ) . '</option>';
												echo '<option value="dynamic"' . ( 'dynamic' === $svalue ? ' selected' : '' ) . '>' . esc_html__( 'New coupon', 'customer-reviews-woocommerce' ) . '</option>';
												?>
											</select>
										</td>
										<?php
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Enable for roles', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Define if discount coupons will be sent to all customers or only to customers with specific roles.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										?>
										<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-<?php echo $tier; ?>">
											<select class="cr-coupon-tiers-table-select cr-any-coupon" name="<?php echo self::$tiers_settings['cr_coupon_enable_for_role'] . $tier; ?>">
												<?php
												$svalue = strval( $table_tiers[self::$tiers_settings['cr_coupon_enable_for_role']][$tier] );
												echo '<option value="all"' . ( 'roles' !== $svalue ? ' selected' : '' ) . '>' . esc_html__( 'All roles', 'customer-reviews-woocommerce' ) . '</option>';
												echo '<option value="roles"' . ( 'roles' === $svalue ? ' selected' : '' ) . '>' . esc_html__( 'Specific roles', 'customer-reviews-woocommerce' ) . '</option>';
												?>
											</select>
											<?php echo '<span class="cr-option-not-available cr-any-coupon">' . self::$option_not_available . '</span>'; ?>
										</td>
										<?php
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Roles', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Define if discount coupons will be sent to all customers or only to customers with specific roles.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = $table_tiers[self::$tiers_settings['cr_coupon_enabled_roles']][$tier];
										$svalue = is_array( $svalue ) ? array_filter( $svalue ) : array();
										self::show_roles( array( 'tier' => $tier, 'name' => self::$tiers_settings['cr_coupon_enabled_roles'], 'settings' => $svalue ) );
									}
								?>
							</tr>
							<tr class="cr-coupon-section cr-alternate">
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Existing Coupon to Use', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Choose one of the existing coupons for sending to customers who provided reviews.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<td class="cr-coupon-tiers-table-td" colspan="3">
								</td>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Existing coupon', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'This coupon code will be sent to customers who provide reviews.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = strval( $table_tiers[self::$tiers_settings['cr_existing_coupon']][$tier] );
										self::show_existing_coupons( array( 'tier' => $tier, 'name' => self::$tiers_settings['cr_existing_coupon'], 'coupon' => $svalue ) );
									}
								?>
							</tr>
							<tr class="cr-coupon-section cr-alternate">
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'New Coupon to Create', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Settings for automatic creation of unique coupons for each customer and order. When a customer submits a review, a new, unique discount code will be created according to these settings and sent to the customer.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<td class="cr-coupon-tiers-table-td" colspan="3">
								</td>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Discount type', 'woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Choose one the discount types for new coupons.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
								$coupon_types = wc_get_coupon_types();
								foreach( self::$tiers as $tier ) {
									$svalue = strval( $table_tiers[self::$tiers_settings['cr_coupon__discount_type']][$tier] );
									?>
									<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-<?php echo $tier; ?>">
										<select class="cr-coupon-tiers-table-select cr-new-coupon" name="<?php echo self::$tiers_settings['cr_coupon__discount_type'] . $tier; ?>">
											<?php
											foreach( $coupon_types as $key => $value ) {
												echo '<option value="' . esc_attr( $key ) . '"' . ( $key === $svalue ? ' selected' : '' ) . '>' . esc_html( $value ) . '</option>';
											}
											?>
										</select>
										<?php echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>'; ?>
									</td>
									<?php
								}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Coupon amount', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Value of the coupon.', 'woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = floatval( $table_tiers[self::$tiers_settings['cr_coupon__coupon_amount']][$tier] );
										$svalue = 0 < $svalue ? wc_format_localized_price( $svalue ) : '';
										echo '<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-' . $tier . '">';
										echo '<input type="text" name="' . self::$tiers_settings['cr_coupon__coupon_amount'] . $tier . '" placeholder="' . wc_format_localized_price( 0 ) . '" class="cr-new-coupon" value="' . $svalue . '">';
										echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>';
										echo '</td>';
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Allow free shipping', 'woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( sprintf(
										__( 'Check this box if the coupon grants free shipping. A <a href="%s" target="_blank">free shipping method</a> must be enabled in your shipping zone and be set to require "a valid free shipping coupon" (see the "Free Shipping Requires" setting).', 'woocommerce' ),
										'https://docs.woocommerce.com/document/free-shipping/'
									) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = strval( $table_tiers[self::$tiers_settings['cr_coupon__free_shipping']][$tier] );
										echo '<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-' . $tier . '">';
										echo '<input type="checkbox" name="' . self::$tiers_settings['cr_coupon__free_shipping'] . $tier . '" value="yes" class="cr-new-coupon"' . ( 'yes' === $svalue ? ' checked' : '' ) . '>';
										echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>';
										echo '</td>';
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Validity', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Number of days during which the coupon will be valid from the moment of submission of a review or set to 0 for unlimited validity.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = intval( $table_tiers[self::$tiers_settings['cr_coupon__expires_days']][$tier] );
										echo '<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-' . $tier . '">';
										echo '<input type="number" name="' . self::$tiers_settings['cr_coupon__expires_days'] . $tier . '" min="0" step="1" placeholder="0" class="cr-new-coupon"' . ( 0 < $svalue ? ' value="' . $svalue . '"' : '' ) . '>';
										echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>';
										echo '</td>';
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Minimum spend', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'This field allows you to set the minimum spend (subtotal, including taxes) allowed to use the coupon.', 'woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = floatval( $table_tiers[self::$tiers_settings['cr_coupon__minimum_amount']][$tier] );
										$svalue = 0 < $svalue ? wc_format_localized_price( $svalue ) : '';
										echo '<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-' . $tier . '">';
										echo '<input type="text" name="' . self::$tiers_settings['cr_coupon__minimum_amount'] . $tier . '" placeholder="' . __( 'No minimum', 'woocommerce' ) . '" class="cr-new-coupon" value="' . $svalue . '">';
										echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>';
										echo '</td>';
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Maximum spend', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'This field allows you to set the maximum spend (subtotal, including taxes) allowed when using the coupon.', 'woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = floatval( $table_tiers[self::$tiers_settings['cr_coupon__maximum_amount']][$tier] );
										$svalue = 0 < $svalue ? wc_format_localized_price( $svalue ) : '';
										echo '<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-' . $tier . '">';
										echo '<input type="text" name="' . self::$tiers_settings['cr_coupon__maximum_amount'] . $tier . '" placeholder="' . __( 'No maximum', 'woocommerce' ) . '" class="cr-new-coupon" value="' . $svalue . '">';
										echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>';
										echo '</td>';
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Individual use only', 'woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = strval( $table_tiers[self::$tiers_settings['cr_coupon__individual_use']][$tier] );
										echo '<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-' . $tier . '">';
										echo '<input type="checkbox" name="' . self::$tiers_settings['cr_coupon__individual_use'] . $tier . '" value="yes" class="cr-new-coupon"' . ( 'yes' === $svalue ? ' checked' : '' ) . '>';
										echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>';
										echo '</td>';
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Exclude sale items', 'woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Check this box if the coupon should not apply to items on sale. Per-item coupons will only work if the item is not on sale. Per-cart coupons will only work if there are items in the cart that are not on sale.', 'woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = strval( $table_tiers[self::$tiers_settings['cr_coupon__exclude_sale_items']][$tier] );
										echo '<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-' . $tier . '">';
										echo '<input type="checkbox" name="' . self::$tiers_settings['cr_coupon__exclude_sale_items'] . $tier . '" value="yes" class="cr-new-coupon"' . ( 'yes' === $svalue ? ' checked' : '' ) . '>';
										echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>';
										echo '</td>';
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Products', 'woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Products which need to be in the cart to use this coupon or, for "Product Discounts", which products are discounted.', 'woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = $table_tiers[self::$tiers_settings['cr_coupon__product_ids']][$tier];
										$svalue = is_array( $svalue ) ? array_filter( $svalue ) : array();
										self::show_product_search( array( 'tier' => $tier, 'name' => self::$tiers_settings['cr_coupon__product_ids'], 'products' => $svalue ) );
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Exclude products', 'woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Products which must not be in the cart to use this coupon or, for "Product Discounts", which products are not discounted.', 'woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = $table_tiers[self::$tiers_settings['cr_coupon__exclude_product_ids']][$tier];
										$svalue = is_array( $svalue ) ? array_filter( $svalue ) : array();
										self::show_product_search( array( 'tier' => $tier, 'name' => self::$tiers_settings['cr_coupon__exclude_product_ids'], 'products' => $svalue ) );
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Product categories', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'A product must be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will be discounted.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = $table_tiers[self::$tiers_settings['cr_coupon__product_categories']][$tier];
										$svalue = is_array( $svalue ) ? array_filter( $svalue ) : array();
										self::show_categories( array( 'tier' => $tier, 'name' => self::$tiers_settings['cr_coupon__product_categories'], 'settings' => $svalue ) );
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Exclude categories', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'Product must not be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will not be discounted.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = $table_tiers[self::$tiers_settings['cr_coupon__excluded_product_categories']][$tier];
										$svalue = is_array( $svalue ) ? array_filter( $svalue ) : array();
										self::show_categories( array( 'tier' => $tier, 'name' => self::$tiers_settings['cr_coupon__excluded_product_categories'], 'settings' => $svalue ) );
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Usage limit', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'How many times this coupon can be used before it is void. Set it to 0 for unlimited usage.', 'woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = intval( $table_tiers[self::$tiers_settings['cr_coupon__usage_limit']][$tier] );
										echo '<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-' . $tier . '">';
										echo '<input type="number" name="' . self::$tiers_settings['cr_coupon__usage_limit'] . $tier . '" min="0" step="1" placeholder="0" class="cr-new-coupon"' . ( 0 < $svalue ? ' value="' . $svalue . '"' : '' ) . '>';
										echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>';
										echo '</td>';
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Coupon prefix', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'A prefix that will be added to coupon codes generated by the plugin.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = strval( $table_tiers[self::$tiers_settings['cr_coupon_prefix']][$tier] );
										echo '<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-' . $tier . '">';
										echo '<input type="text" name="' . self::$tiers_settings['cr_coupon_prefix'] . $tier . '" placeholder="" class="cr-new-coupon" value="' . $svalue . '">';
										echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>';
										echo '</td>';
									}
								?>
							</tr>
							<tr>
								<td class="cr-coupon-tiers-table-td">
									<?php
									esc_html_e( 'Allow sharing', 'customer-reviews-woocommerce' );
									echo CR_Admin::ivole_wc_help_tip( __( 'If sharing is allowed, customers will be able to share their coupons with other people. Otherwise, coupons will be valid only for customers who received them.', 'customer-reviews-woocommerce' ) );
									?>
								</td>
								<?php
									foreach( self::$tiers as $tier ) {
										$svalue = strval( $table_tiers[self::$tiers_settings['cr_coupon__sharing']][$tier] );
										echo '<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-' . $tier . '">';
										echo '<input type="checkbox" name="' . self::$tiers_settings['cr_coupon__sharing'] . $tier . '" value="yes" class="cr-new-coupon"' . ( 'yes' === $svalue ? ' checked' : '' ) . '>';
										echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>';
										echo '</td>';
									}
								?>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<?php
		}

		private static function show_roles( $settings ) {
			global $wp_roles;
			$all_options = $wp_roles->get_names();
			?>
			<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-coupon-tiers-table-td-<?php echo $settings['tier']; ?> cr-no-coupon">
				<div class="cr-any-coupon">
					<select multiple="multiple" name="<?php echo esc_attr( $settings['name'] . $settings['tier'] ); ?>[]" style="width:250px;"  data-placeholder="<?php esc_attr_e( 'Choose roles', 'customer-reviews-woocommerce' ); ?>&hellip;" aria-label="<?php esc_attr_e( 'Role', 'customer-reviews-woocommerce' ); ?>" class="wc-enhanced-select">
						<?php
						if ( ! empty( $all_options ) ) {
							foreach ( $all_options as $key => $val ) {
								echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $settings['settings'] ), true, false ) . '>' . $val . '</option>';
							}
						}
						?>
					</select>
					<br />
					<a class="select_all button" href="#"><?php _e( 'Select all', 'customer-reviews-woocommerce' ); ?></a>
					<a class="select_none button" href="#"><?php _e( 'Select none', 'customer-reviews-woocommerce' ); ?></a>
				</div>
				<?php echo '<span class="cr-option-not-available cr-any-coupon">' . self::$option_not_available . '</span>'; ?>
			</td>
			<?php
		}

		private static function show_existing_coupons( $settings ) {
			$coupons = self::get_existing_coupons();
			?>
			<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-coupon-tiers-table-td-<?php echo $settings['tier']; ?> cr-no-coupon">
				<div class="cr-existing-coupon cr-existing-coupon-field">
					<select class="wc-coupon-search"  style="width:250px;" name="<?php echo esc_attr( $settings['name'] . $settings['tier'] ); ?>" data-placeholder="<?php esc_html_e( 'Search for a coupon', 'customer-reviews-woocommerce' ); ?>&hellip;"
						data-action="woocommerce_json_search_coupons" >
						<?php
						foreach ( $coupons as $key => $val ) {
							if ( $settings['coupon'] == $key ) {
								echo "<option value='". esc_attr( $key ) ."'>" . $val. "</option>";
							}
						}
						?>
					</select>
				</div>
				<?php echo '<span class="cr-option-not-available cr-existing-coupon">' . self::$option_not_available . '</span>'; ?>
			</td>
			<?php
		}

		private static function show_product_search( $settings ) {
			?>
			<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-<?php echo $settings['tier']; ?>">
				<div class="cr-new-coupon">
					<select class="wc-product-search" multiple="multiple"  style="width:250px;" name="<?php echo esc_attr( $settings['name'] . $settings['tier'] ); ?>[]" data-placeholder="<?php esc_html_e( 'Search for a product', 'customer-reviews-woocommerce' ); ?>&hellip;"
						data-action="woocommerce_json_search_products_and_variations" data-allow_clear="true" >
						<?php
						foreach ( $settings['products'] as $product_id ) {
							$product = wc_get_product( $product_id );
							if ( is_object( $product ) ) {
								echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
							}
						}
						?>
					</select>
				</div>
				<?php echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>'; ?>
			</td>
			<?php
		}

		private static function show_categories( $settings ) {
			$args = array(
				'number'     => 0,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false,
				'fields'     => 'id=>name'
			);
			$all_options = get_terms( 'product_cat', $args );
			// WPML filters product categories by the current language, the code below unfilters them
			if ( has_filter( 'wpml_current_language' ) && defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
				$languages = apply_filters( 'wpml_active_languages', NULL );
				if ( !empty( $languages ) ) {
					global $sitepress;
					if ( $sitepress ) {
						remove_filter( 'get_terms_args', array( $sitepress, 'get_terms_args_filter' ) );
						remove_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ) );
						remove_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
						$all_options = get_terms( 'product_cat', $args );
						add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );
						add_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
						add_filter( 'get_terms_args', array( $sitepress, 'get_terms_args_filter' ), 10, 2 );
					}
				}
			}
			?>
			<td class="cr-coupon-tiers-table-td cr-coupon-settings cr-no-coupon cr-coupon-tiers-table-td-<?php echo $settings['tier']; ?>">
				<div class="cr-new-coupon">
					<select multiple="multiple" name="<?php echo esc_attr( $settings['name'] . $settings['tier'] ); ?>[]" style="width:250px;"  data-placeholder="<?php esc_attr_e( 'Choose categories', 'customer-reviews-woocommerce' ); ?>&hellip;" aria-label="<?php esc_attr_e( 'Category', 'customer-reviews-woocommerce' ) ?>" class="wc-enhanced-select">
						<?php
						if ( ! empty( $all_options ) ) {
							foreach ( $all_options as $key => $val ) {
								echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $settings['settings'] ), true, false ) . '>' . $val . '</option>';
							}
						}
						?>
					</select>
					<br />
					<a class="select_all button" href="#"><?php _e( 'Select all', 'customer-reviews-woocommerce' ); ?></a>
					<a class="select_none button" href="#"><?php _e( 'Select none', 'customer-reviews-woocommerce' ); ?></a>
				</div>
				<?php echo '<span class="cr-option-not-available cr-new-coupon">' . self::$option_not_available . '</span>'; ?>
			</td>
			<?php
		}

		private static function get_existing_coupons() {
			global $wpdb;

			$all = $wpdb->get_results(
				"SELECT * FROM {$wpdb->posts}
				WHERE post_type = 'shop_coupon' AND post_status = 'publish'
				ORDER BY post_date DESC;",
				ARRAY_A
			);

			$coupons = array();
			$today = time();
			foreach ( $all as $coupon ) {
				$expires = get_post_meta( $coupon['ID'], 'date_expires', true );
				$email_array = get_post_meta( $coupon['ID'], 'customer_email', true );
				if ( ( intval( $expires ) > $today || intval( $expires ) == 0 ) &&
				( ! is_array( $email_array ) || count( $email_array ) == 0 ) ) {
					$coupons[ $coupon['ID'] ] = rawurldecode( stripslashes( $coupon['post_title'] ) );
				}
			}

			return $coupons;
		}

		public static function save_coupon_tiers_table( $value, $option, $raw_value ) {
			$value = array();
			foreach( self::$tiers_settings as $tiers_setting ) {
				foreach( self::$tiers as $tier ) {
					if( isset( $_POST[$tiers_setting . $tier] ) )  {
						$value[$tiers_setting][$tier] = $_POST[$tiers_setting . $tier];
					} else {
						$value[$tiers_setting][$tier] = '';
					}
				}
			}
			return $value;
		}

		public static function read_coupon_tiers_table() {
			$db_settings = get_option( 'ivole_coupon_tiers', false );
			$table = array();
			foreach( self::$tiers_settings as $tiers_setting ) {
				foreach( self::$tiers as $tier ) {
					if( is_array( $db_settings ) && isset( $db_settings[$tiers_setting] )
						&& is_array( $db_settings[$tiers_setting] ) && isset( $db_settings[$tiers_setting][$tier] ) )  {
						$table[$tiers_setting][$tier] = $db_settings[$tiers_setting][$tier];
					} elseif ( 1 === $tier && false !== ( $old_setting = get_option( 'ivole' . substr( $tiers_setting, 2 ) ) ) ) {
						$table[$tiers_setting][$tier] = $old_setting;
					} else {
						$table[$tiers_setting][$tier] = '';
					}
				}
			}
			return $table;
		}

		public static function get_coupon( $media_count ) {
			$coupon_settings = CR_Review_Discount_Settings::get_review_discounts();

			$coupon = array(
				'is_enabled' => false
			);

			if ( 0 < count( $coupon_settings ) && $coupon_settings[0]['enabled'] ) {
				$coupon['channel'] = $coupon_settings[0]['channel'];
				$s = self::read_coupon_tiers_table();
				if( $s and is_array( $s ) ) {
					$tier_w_coupon = 0;
					$compare_count = -1;
					foreach( self::$tiers as $tier ) {
						if( in_array( $s[self::$tiers_settings['cr_coupon_type']][$tier], array( 'dynamic', 'static' ) ) &&
					 		$media_count >= $s[self::$tiers_settings['cr_media_count']][$tier] &&
							$compare_count < $s[self::$tiers_settings['cr_media_count']][$tier] ) {
								$compare_count = $s[self::$tiers_settings['cr_media_count']][$tier];
								$tier_w_coupon = $tier;
						}
					}
					if( 0 < $tier_w_coupon ) {
						$coupon['is_enabled'] = true;
						foreach( self::$tiers_settings as $tiers_setting ) {
							$coupon[$tiers_setting] = $s[$tiers_setting][$tier_w_coupon];
						}
					}
				}
			}

			return $coupon;
		}

	}

endif;
