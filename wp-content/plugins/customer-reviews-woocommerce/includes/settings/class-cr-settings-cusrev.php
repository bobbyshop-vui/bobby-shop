<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_CusRev_Settings' ) ):

	class CR_CusRev_Settings {

		protected $settings_menu;
		protected $tab;
		protected $settings;

		public function __construct( $settings_menu ) {
			$this->settings_menu = $settings_menu;
			$this->tab = 'cusrev';

			// display CusRev.com tab only when review verification is enabled on the Review Reminder tab
			if( 'yes' === get_option( 'ivole_verified_reviews', 'no' ) ) {
				add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			}
			add_action( 'woocommerce_admin_field_checkbox_age_restriction', array( $this, 'show_checkbox_age_restriction' ) );
			add_action( 'woocommerce_admin_field_verified_page', array( $this, 'show_verified_page' ) );
			add_action( 'woocommerce_admin_field_twocolsmode', array( $this, 'show_twocolsmode' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_settings_' . $this->tab, array( $this, 'save' ) );
			add_action( 'admin_footer', array( $this, 'output_page_javascript' ) );
			add_action( 'wp_ajax_cr_check_age_restriction_ajax', array( $this, 'check_verified_reviews_ajax' ) );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_verified_live_mode', array( $this, 'save_live_mode' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_age_restriction', array( $this, 'save_age_restriction_checkbox' ), 10, 3 );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'CusRev.com', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();
			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();
			WC_Admin_Settings::save_fields( $this->settings );
		}

		protected function init_settings() {
			// compatibility with the previous versions of the plugin
			// that had ivole_reviews_verified option instead of ivole_verified_live_mode option
			if ( false === get_option( 'ivole_verified_live_mode', false ) ) {
				$ivole_reviews_verified = get_option( 'ivole_reviews_verified', false );
				if ( false !== $ivole_reviews_verified ) {
					if ( 'yes' === $ivole_reviews_verified ) {
						update_option( 'ivole_verified_live_mode', 'yes', false );
						update_option( 'ivole_verified_links', 'yes', false );
					} else {
						update_option( 'ivole_verified_live_mode', 'no', false );
						update_option( 'ivole_verified_links', 'no', false );
					}
					delete_option( 'ivole_reviews_verified' );
				}
			}
			//
			$this->settings = array(
				array(
					'title' => __( 'CusRev.com', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Display a public page with verified copies of reviews on CusRev.com', 'customer-reviews-woocommerce' ),
					'id'    => 'cr_cusrev_options'
				),
				array(
					'title' => __( 'Mode', 'customer-reviews-woocommerce' ),
					'type' => 'twocolsmode',
					'id' => 'ivole_verified_live_mode',
					'default' => 'no',
					'autoload' => false
				),
				array(
					'title'    => __( 'Page URL', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Specify name of the page with verified reviews. This will be a base URL for reviews related to your shop. You can use alphanumeric symbols and \'.\' in the name of the page.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_reviews_verified_page',
					'default'  => Ivole_Email::get_blogdomain(),
					'type'     => 'verified_page',
					'css'      => 'width:250px;vertical-align:middle;',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Age Restriction', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Enable this option if your store sells age-restricted products (e.g., adult content, alcohol, etc.)', 'customer-reviews-woocommerce' ),
					'default'  => 'no',
					'type'     => 'checkbox_age_restriction',
					'css'      => 'width:250px;vertical-align:middle;',
					'desc_tip' => false,
					'id' => 'ivole_age_restriction',
					'autoload' => false,
					'class' => 'cr-setting-disabled'
				),
				array(
					'title'   => __( 'Links to Reviews', 'customer-reviews-woocommerce' ),
					'desc'    => sprintf(
						__( 'Display a special %s icon next to reviews received in the Live mode together with a nofollow link to the verified copy of the review published on CusRev.com', 'customer-reviews-woocommerce' ),
						'<img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ )  ) ) ) . '/img/shield-20.png" style="width:15px;">'
					),
					'id'      => 'ivole_verified_links',
					'default' => 'no',
					'type'    => 'checkbox',
					'autoload' => true
				),
				array(
					'type' => 'sectionend',
					'id'   => 'cr_cusrev_options'
				)
			);
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

		/**
		* Custom field type for age restriction checkbox
		*/
		public function show_checkbox_age_restriction( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$description = $tmp['description'];
			$option_value = get_option( $value['id'], $value['default'] );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<?php echo esc_html( $value['title'] ); ?>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ) ?></span></legend>
						<label for="<?php echo $value['id'] ?>">
							<input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>"
							type="checkbox" class="cr-disabled-checkbox<?php echo ( isset( $value['class'] ) && 0 < strlen( $value['class'] ) ) ? ' ' . esc_attr( $value['class'] ) : ''; ?>"
							value="1" disabled="disabled" /><?php echo $description ?></label>
						<p class="cr-verified-badge-status"></p>
					</fieldset>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for verified page
		*/
		public function show_verified_page( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$disabled = 'yes' === get_option( 'ivole_verified_live_mode', 'no' ) ? '' : 'disabled';
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					https://www.cusrev.com/reviews/
					<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					value="<?php echo get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ); ?>"
					<?php echo $disabled; ?> />
					<?php echo $description; ?>
				</td>
			</tr>
			<?php
		}

		public function save_live_mode( $value, $option, $raw_value ) {
			$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';

			$ageRestriction = false;
			if( isset( $_POST['ivole_age_restriction'] ) && ( 1 == $_POST['ivole_age_restriction'] || 'yes' == $_POST['ivole_age_restriction'] ) ) {
				$ageRestriction = true;
			}

			$verified_reviews = new CR_Verified_Reviews();
			if( 'yes' === $value ) {
				if( 0 != $verified_reviews->enable( $_POST['ivole_reviews_verified_page'], $ageRestriction ) ) {
					// if activation failed, disable the option
					$value = 'no';
				}
			} else {
				$verified_reviews->disable( $ageRestriction );
			}

			return $value;
		}

		public function save_age_restriction_checkbox( $value, $option, $raw_value ) {
			$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
			return $value;
		}

		/**
		* Function to check if age restriction is enabled
		*/
		public function check_verified_reviews_ajax() {
			$vrevs = new CR_Verified_Reviews();
			$rval = $vrevs->check_status();

			if ( 0 === $rval ) {
				wp_send_json( array( 'status' => 0 ) );
			} else {
				wp_send_json( array( 'status' => 1 ) );
			}
		}

		public function output_page_javascript() {
			if ( $this->is_this_tab() ) {
				?>
				<script type="text/javascript">
				jQuery(function($) {
					// check age restriction setting
					if ( jQuery('#ivole_age_restriction').length > 0 ) {
						let data = {
							'action': 'cr_check_age_restriction_ajax'
						};
						jQuery('.cr-verified-badge-status').text('Checking settings...');
						jQuery('.cr-verified-badge-status').css('visibility', 'visible');
						jQuery.post(ajaxurl, data, function(response) {
							jQuery('.cr-verified-badge-status').css( 'visibility', 'hidden' );
							jQuery('.cr-disabled-checkbox').prop( 'disabled', false );
							jQuery('#ivole_age_restriction').prop( 'checked', <?php echo 'yes' === get_option( 'ivole_age_restriction', 'no' ) ? 'true' : 'false'; ?> );
						});
					}
				});
				</script>
				<?php
			}
		}

		public function show_twocolsmode( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$option_value = get_option( $value['id'], $value['default'] );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-checkbox">
					<div class="cr-twocols-cont cr-cusrev-mode">
						<input type="hidden" name="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>">
						<div class="cr-twocols-left cr-twocols-cols<?php if( 'yes' !== $option_value ) echo ' cr-twocols-sel'; ?>">
							<svg width="70" height="70" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
								<circle cx="50" cy="50" r="49" fill="#DA8ECC"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M55.6152 98.6818L29.4864 72.553L51.1851 50.8543L42.907 42.5762C38.0793 37.7484 38.0793 29.9212 42.907 25.0934C47.7347 20.2657 55.562 20.2657 60.3897 25.0934C66.7268 31.4305 73.0635 37.7678 79.4002 44.1051C85.4248 50.1302 91.4494 56.1554 97.4742 62.1802C92.5089 81.591 75.9524 96.3616 55.6152 98.6818Z" fill="#7B79E1"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M50 21C42.3674 21 36 27.1803 36 34.8907V41H35.3595C31.1664 41 27.6366 44.1375 27.145 48.3017C26.3843 54.7449 26.3843 61.2551 27.145 67.6983C27.6366 71.8625 31.1664 75 35.3595 75H64.5562C68.8296 75 72.4231 71.7939 72.9085 67.5481C73.6338 61.2034 73.6338 54.7966 72.9085 48.4519C72.4231 44.2061 68.8296 41 64.5562 41H64V34.8907C64 27.1803 57.6325 21 50 21ZM58 41V34.8907C58 30.5716 54.397 27 50 27C45.603 27 42 30.5716 42 34.8907V41H58Z" fill="white"/>
								<rect x="47" y="51" width="6" height="14" rx="3" fill="#7B79E1"/>
							</svg>
							<div class="cr-twocols-title">
								<?php esc_html_e( 'Private mode' ) ?>
							</div>
							<div class="cr-twocols-main">
								<ul>
									<li>
										<?php echo 'Reviews received in the Private mode will not be published on <a href="https://www.cusrev.com" target="_blank" rel="noopener noreferrer">CusRev.com</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon">' . wc_help_tip( 'A review is considered to be received in the Private mode if it was left in response to a review invitation (reminder) sent while the Private mode was enabled.' ); ?>
									</li>
									<li>
										<?php echo 'The Private mode should be used for setting up and testing integration of the plugin with CusRev' . wc_help_tip( 'The Private mode is enabled by default. Use it to test the review submission process and make sure that reviews are successfully posted from aggregated review forms to your website.' ); ?>
									</li>
									<li>
										<?php echo 'You can create any dummy reviews in the Private mode, and they will never be published on <a href="https://www.cusrev.com" target="_blank" rel="noopener noreferrer">CusRev.com</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon">' . wc_help_tip( 'Reviews received in the Private mode will still be published on your website, so make sure to delete any dummy reviews after testing.' ); ?>
									</li>
								</ul>
							</div>
							<div class="cr-twocols-footer">
								<div class="cr-twocols-chkbox">
									<div class="cr-twocols-chkbox-inner">
									</div>
									<svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M13 25.5C6.09625 25.5 0.5 19.9038 0.5 13C0.5 6.09625 6.09625 0.5 13 0.5C19.9038 0.5 25.5 6.09625 25.5 13C25.5 19.9038 19.9038 25.5 13 25.5ZM11.7538 18L20.5913 9.16125L18.8238 7.39375L11.7538 14.465L8.2175 10.9288L6.45 12.6963L11.7538 18Z" fill="#A46497"/>
									</svg>
								</div>
							</div>
						</div>
						<div class="cr-twocols-right cr-twocols-cols<?php if( 'yes' === $option_value ) echo ' cr-twocols-sel'; ?>">
							<svg width="70" height="70" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
								<circle cx="50" cy="50" r="49" fill="#DA8ECC"/>
								<circle cx="50" cy="50" r="39" fill="#B077D3"/>
								<circle cx="50" cy="50" r="29" fill="#7B79E1"/>
								<path d="M17 59.0004V39.0004H23V54H32V59.0004H17Z" fill="white"/>
								<path d="M40 39V59L34 59.0004V39.0004L40 39Z" fill="white"/>
								<path d="M48.6063 39.0004L53.0485 53.102H53.2178L57.66 39.0004H64.3023L57.1523 59.0004H49.1139L41.964 39.0004H48.6063Z" fill="white"/>
								<path d="M67 59.0004V39.0004H82V43.0004H72V47.0004H81V51.0004H72V55.0004H82V59.0004H67Z" fill="white"/>
							</svg>
							<div class="cr-twocols-title">
								<?php esc_html_e( 'Live mode' ) ?>
							</div>
							<div class="cr-twocols-main">
								<ul>
									<li>
										<?php echo 'Reviews received in the Live mode will be published on <a href="https://www.cusrev.com" target="_blank" rel="noopener noreferrer">CusRev.com</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon">' . wc_help_tip( 'A review is considered to be received in the Live mode if it was left in response to a review invitation (reminder) sent while the Live mode was enabled.' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'It is not possible to remove, moderate or hide reviews received in the Live mode' ); echo wc_help_tip( 'Reviews received in the Live mode will never be deleted from CusRev.com even after you stop collecting reviews with CusRev.' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'Turn on the Live mode after you complete testing and are ready to collect reviews from real customers' ); echo wc_help_tip( 'The \'Reminders\' page will include information about review invitations (reminders) that were sent in the Live mode.' ); ?>
									</li>
									<li>
										<?php echo 'You must pick a \'Page URL\' for your store at <a href="https://www.cusrev.com" target="_blank" rel="noopener noreferrer">CusRev.com</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon"> that has not yet been taken by another store to enable the Live mode' . wc_help_tip( 'It is recommended to use your domain or brand name as a \'Page URL\' for CusRev.com (see this setting below).' ); ?>
									</li>
								</ul>
							</div>
							<div class="cr-twocols-footer">
								<div class="cr-twocols-chkbox">
									<div class="cr-twocols-chkbox-inner">
									</div>
									<span data-tip="<?php echo esc_attr__( 'Enabled', 'woocommerce' ); ?>"><svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M13 25.5C6.09625 25.5 0.5 19.9038 0.5 13C0.5 6.09625 6.09625 0.5 13 0.5C19.9038 0.5 25.5 6.09625 25.5 13C25.5 19.9038 19.9038 25.5 13 25.5ZM11.7538 18L20.5913 9.16125L18.8238 7.39375L11.7538 14.465L8.2175 10.9288L6.45 12.6963L11.7538 18Z" fill="#A46497"/>
									</svg></span>
								</div>
							</div>
						</div>
					</div>
				</td>
			</tr>
			<?php
		}

	}

endif;
