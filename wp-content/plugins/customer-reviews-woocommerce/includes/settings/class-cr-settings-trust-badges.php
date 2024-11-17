<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Trust_Badges' ) ):

	class Ivole_Trust_Badges {

		/**
		* @var Ivole_Trust_Badges The instance of the trust badges admin menu
		*/
		protected $settings_menu;

		/**
		* @var string The slug of this tab
		*/
		protected $tab;

		/**
		* @var array The fields for this tab
		*/
		protected $settings;
		protected $language;

		protected $floating_light;
		protected $floating_dark;

		public function __construct( $settings_menu ) {
			$this->settings_menu = $settings_menu;
			$this->tab = 'trust_badges';
			$this->floating_light = CR_Floating_Trust_Badge::$floating_light;
			$this->floating_dark = CR_Floating_Trust_Badge::$floating_dark;

			add_action( 'woocommerce_admin_field_trust_badge', array( $this, 'show_trustbadge' ) );
			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_settings_' . $this->tab, array( $this, 'save' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_trustbadges_css' ) );
			add_action( 'admin_footer', array( $this, 'output_page_javascript' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Trust Badges', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();
			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();

			$field_id = 'ivole_license_key';
			if( !empty( $_POST ) && isset( $_POST[$field_id] ) ) {
				$license = new CR_License();
				$license->register_license( $_POST[$field_id] );
			}

			WC_Admin_Settings::save_fields( $this->settings );
		}

		protected function init_settings() {
			$this->language = CR_Trust_Badge::get_badge_language();
			$site_lang = '';
			if( 'en' !== $this->language ) {
				$l_suffix = '-' . $this->language;
				$site_lang = $this->language . '/';
			}
			if( 'yes' === get_option( 'ivole_verified_reviews', 'no' ) ) {
				$this->verified_page = 'https://www.cusrev.com/' . $site_lang . 'reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() );
			} else {
				$this->verified_page = '';
			}

			$this->store_stats = CR_Trust_Badge::get_store_stats( Ivole_Email::get_blogurl(), true );
			//
			$this->settings = array(
				array(
					'title' => __( 'Trust Badges', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => '<p>' .
						__( 'Increase your store\'s conversion rate by placing a "trust badge" on the home, checkout or any other page(s). Let customers feel more confident about shopping on your site by featuring a trust badge that shows a summary of customer reviews. Trust badges can be enabled using shortcodes or blocks in the page editor (blocks require WordPress 5.0 or newer).', 'customer-reviews-woocommerce' ) .
						'</p><p>' .
						sprintf( __( 'If you enable <a href="%1$s">verification of reviews</a> and claim a <a href="%2$s">dedicated page</a> at CusRev.com website, trust badges will include a nofollow link to your page there.', 'customer-reviews-woocommerce' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=review_reminder' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=cusrev' ) ) .
						'</p>',
					'id'    => 'ivole_options'
				),
				array(
					'title'    => __( 'Small Light Badge', 'customer-reviews-woocommerce' ),
					'type'     => 'trust_badge',
					'desc'     => __( 'Shortcode and preview of the small light trust badge.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_sl',
					'css'      => 'min-width:400px;',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Small Light Badge (with Store Rating)', 'customer-reviews-woocommerce' ),
					'type'     => 'trust_badge',
					'desc'     => __( 'Shortcode and preview of the small light trust badge with store rating.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_slp',
					'css'      => 'min-width:400px;',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Small Dark Badge', 'customer-reviews-woocommerce' ),
					'type'     => 'trust_badge',
					'desc'     => __( 'Shortcode and preview of the small dark trust badge.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_sd',
					'css'      => 'min-width:400px;',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Small Dark Badge (with Store Rating)', 'customer-reviews-woocommerce' ),
					'type'     => 'trust_badge',
					'desc'     => __( 'Shortcode and preview of the small dark trust badge with store rating.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_sdp',
					'css'      => 'min-width:400px;',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Wide Light Badge', 'customer-reviews-woocommerce' ),
					'type'     => 'trust_badge',
					'desc'     => __( 'Shortcode and preview of the wide light trust badge. The wide badge has a version for small screens that will be automatically shown when a website is viewed from phones.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_wl',
					'css'      => 'min-width:400px;',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Wide Light Badge (with Store Rating)', 'customer-reviews-woocommerce' ),
					'type'     => 'trust_badge',
					'desc'     => __( 'Shortcode and preview of the wide light trust badge with store rating. The wide badge has a version for small screens that will be automatically shown when a website is viewed from phones.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_wlp',
					'css'      => 'min-width:400px;',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Wide Dark Badge', 'customer-reviews-woocommerce' ),
					'type'     => 'trust_badge',
					'desc'     => __( 'Shortcode and preview of the wide dark trust badge. The wide badge has a version for small screens that will be automatically shown when a website is viewed from phones.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_wd',
					'css'      => 'min-width:400px;',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Wide Dark Badge (with Store Rating)', 'customer-reviews-woocommerce' ),
					'type'     => 'trust_badge',
					'desc'     => __( 'Shortcode and preview of the wide dark trust badge with store rating. The wide badge has a version for small screens that will be automatically shown when a website is viewed from phones.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_wdp',
					'css'      => 'min-width:400px;',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Compact Light Badge', 'customer-reviews-woocommerce' ),
					'type'     => 'trust_badge',
					'desc'     => __( 'Shortcode and preview of the compact light trust badge.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_vsl',
					'css'      => 'min-width:400px;',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Compact Dark Badge', 'customer-reviews-woocommerce' ),
					'type'     => 'trust_badge',
					'desc'     => __( 'Shortcode and preview of the compact dark trust badge.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_vsd',
					'css'      => 'min-width:400px;',
					'desc_tip' => true
				),
				array(
					'type' => 'sectionend',
					'id'   => 'ivole_options'
				),
				array(
					'title' => __( 'Floating Trust Badge', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Settings to display a floating badge with a summary of verified reviews.', 'customer-reviews-woocommerce' ),
					'id'    => 'ivole_options_floating'
				),
				array(
					'title'    => __( 'Floating Badge', 'customer-reviews-woocommerce' ),
					'type'     => 'checkbox',
					'desc'     => __( 'Enable this checkbox to display a floating trust badge on public pages of the website.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_floating',
					'desc_tip' => false
				),
				array(
					'title'    => __( 'Floating Badge Style', 'customer-reviews-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( 'Choose one of the styles for the floating trust badge.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_floating_type',
					'desc_tip' => true,
					'options'  => array(
						'light' => __( 'Light', 'customer-reviews-woocommerce' ),
						'dark'  => __( 'Dark', 'customer-reviews-woocommerce' )
					),
					'default'  => 'light'
				),
				array(
					'title'    => __( 'Floating Badge Location', 'customer-reviews-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( 'Choose one of the locations for the floating trust badge.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_trust_badge_floating_location',
					'desc_tip' => true,
					'options'  => array(
						'bottomright' => __( 'Bottom right', 'customer-reviews-woocommerce' ),
						'bottomleft'  => __( 'Bottom left', 'customer-reviews-woocommerce' ),
					),
					'default'  => 'bottomright'
				),
				array(
					'type' => 'sectionend',
					'id'   => 'ivole_options_floating'
				)
			);
			// if local reviews are used, add an option to set a link to a local page with reviews
			if ( ! $this->verified_page ) {
				$this->settings[] = array(
					'title' => __( 'Trust Badge Link', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => sprintf(
						__( 'Make Trust Badges clickable by adding a link leading to a special page of your website. Use that page to showcase reviews with one of the <a href="%s">shortcodes or Gutenberg blocks</a>.', 'customer-reviews-woocommerce' ),
						admin_url( 'admin.php?page=cr-reviews-settings&tab=shortcodes' )
					),
					'id'    => 'cr_options_localpage'
				);
				$this->settings[] = array(
					'title' => __( 'Reviews Page', 'customer-reviews-woocommerce' ),
					'type' => 'single_select_page_with_search',
					'id' => 'ivole_local_reviews_page',
					'desc' => __( 'Set a page where customers will be taken to after clicking on a Trust Badge.', 'customer-reviews-woocommerce' ),
					'default' => '',
					'class'    => 'wc-page-search',
					'css'      => 'min-width:300px;',
					'args'     => array(
						'exclude' =>
							array(
								wc_get_page_id( 'checkout' ),
								wc_get_page_id( 'myaccount' ),
							),
					),
					'desc_tip' => true,
					'autoload' => false
				);
				$this->settings[] = array(
					'type' => 'sectionend',
					'id'   => 'cr_options_localpage'
				);
			}
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

		/**
		* Custom field type for trust badges
		*/
		public function show_trustbadge( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$shortcode = '';
			$suffix = '';
			$l_suffix = '';
			$site_lang = '';
			if( 'en' !== $this->language ) {
				$l_suffix = '-' . $this->language;
				$site_lang = $this->language . '/';
			}

			switch( $value['id']  ) {
				case 'ivole_trust_badge_sl':
				$shortcode = '[cusrev_trustbadge type="SL" border="yes" color="#FFFFFF"]';
				$suffix = 'sl';
				break;
				case 'ivole_trust_badge_slp':
				$shortcode = '[cusrev_trustbadge type="SLP" border="yes" color="#FFFFFF"]';
				$suffix = 'slp';
				break;
				case 'ivole_trust_badge_sd':
				$shortcode = '[cusrev_trustbadge type="SD" border="yes" color="#3D3D3D"]';
				$suffix = 'sd';
				break;
				case 'ivole_trust_badge_sdp':
				$shortcode = '[cusrev_trustbadge type="SDP" border="yes" color="#3D3D3D"]';
				$suffix = 'sdp';
				break;
				case 'ivole_trust_badge_wl':
				$shortcode = '[cusrev_trustbadge type="WL" color="#FFFFFF"]';
				$suffix = 'wl';
				break;
				case 'ivole_trust_badge_wlp':
				$shortcode = '[cusrev_trustbadge type="WLP" color="#FFFFFF"]';
				$suffix = 'wlp';
				break;
				case 'ivole_trust_badge_wd':
				$shortcode = '[cusrev_trustbadge type="WD" color="#003640"]';
				$suffix = 'wd';
				break;
				case 'ivole_trust_badge_wdp':
				$shortcode = '[cusrev_trustbadge type="WDP" color="#003640"]';
				$suffix = 'wdp';
				break;
				case 'ivole_trust_badge_vsl':
				$shortcode = '[cusrev_trustbadge type="VSL" color="#FFFFFF"]';
				$suffix = 'vsl';
				break;
				case 'ivole_trust_badge_vsd':
				$shortcode = '[cusrev_trustbadge type="VSD" color="#373737"]';
				$suffix = 'vsd';
				break;
				default:
				$shortcode = '';
				$suffix = '';
				break;
			}
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<?php
						$shortcode_custom_color = '<a href="https://www.google.com/search?q=color+picker" target="_blank">' . __( 'color', 'customer-reviews-woocommerce' ) . '</a>';
						/* translators: please keep %1$s, %2$s, %3$s, %4$s, %5$s, and %6$s in the translated string */
						$shortcode_explanation = sprintf( 'Use %1$s shortcode to display this badge on your site. If the shortcode includes %2$s argument, you can set it to %3$s or %4$s to display or hide border. If the shortcode includes %5$s argument, you can set it to a custom %6$s (in HEX format).', '<code>' . $shortcode . '</code>', '<code>border</code>', '<code>yes</code>', '<code>no</code>', '<code>color</code>', $shortcode_custom_color );
					?>
					<p class="cr-trustbadge-desc"><?php echo $shortcode_explanation; ?></p>
					<div class="cr-trustbadgea">
						<?php
							echo CR_Trust_Badge::show_html_trust_badge( $suffix, $this->store_stats, '', '', $this->verified_page );
						?>
					</div>
				</td>
			</tr>
			<?php
		}

		public function load_trustbadges_css( $hook ) {
			$reviews_screen_id = sanitize_title( __( 'Reviews', 'customer-reviews-woocommerce' ) . Ivole_Reviews_Admin_Menu::$screen_id_bubble );
			if( $reviews_screen_id . '_page_cr-reviews-settings' === $hook ) {
				wp_enqueue_style( 'cr-admin-css', plugins_url('css/admin.css', dirname( dirname( __FILE__ ) ) ), array(), Ivole::CR_VERSION );
				wp_register_style( 'cr-badges-css', plugins_url( '/css/badges.css', dirname( dirname( __FILE__ ) ) ), array(), Ivole::CR_VERSION, 'all' );
				wp_enqueue_style( 'cr-badges-css' );
			}
		}

		public function output_page_javascript() {
			if ( $this->is_this_tab() ) {
				$this->floating_badge_preview();
				?>
				<script type="text/javascript">
				jQuery(function($) {
					if ( jQuery('#cr_floatingtrustbadge').length > 0 ) {
						jQuery('#cr_floatingtrustbadge').click(function(){
							if( !jQuery(this).hasClass( 'cr-floatingbadge-big' ) ) {
								jQuery(this).find('div.cr-badge.badge_size_compact').hide();
								jQuery(this).find('div.cr-badge.badge--wide-mobile').css( 'display', 'block' );
								jQuery(this).find('div.cr-floatingbadge-close').css( 'display', 'block' );
								jQuery(this).addClass( 'cr-floatingbadge-big' );
								//update colors
								if( 'light' === jQuery('#ivole_trust_badge_floating_type').val() ) {
									jQuery(this).css( 'border-color', '<?php echo $this->floating_light['big']['border']; ?>' );
									jQuery(this).find('div.cr-floatingbadge-background-top').css( 'background-color', '<?php echo $this->floating_light['big']['top']; ?>' );
									jQuery(this).find('div.cr-floatingbadge-background-middle').css( 'background-color', '<?php echo $this->floating_light['big']['middle']; ?>' );
									jQuery(this).find('div.cr-floatingbadge-background-bottom').css( 'background-color', '<?php echo $this->floating_light['big']['bottom']; ?>' );
									jQuery(this).find('div.cr-floatingbadge-background-bottom').css( 'border-color', '<?php echo $this->floating_light['big']['border']; ?>' );
								} else {
									jQuery(this).css( 'border-color', '<?php echo $this->floating_dark['big']['border']; ?>' );
									jQuery(this).find('div.cr-floatingbadge-background-top').css( 'background-color', '<?php echo $this->floating_dark['big']['top']; ?>' );
									jQuery(this).find('div.cr-floatingbadge-background-middle').css( 'background-color', '<?php echo $this->floating_dark['big']['middle']; ?>' );
									jQuery(this).find('div.cr-floatingbadge-background-bottom').css( 'background-color', '<?php echo $this->floating_dark['big']['bottom']; ?>' );
									jQuery(this).find('div.cr-floatingbadge-background-bottom').css( 'border-color', '<?php echo $this->floating_dark['big']['border']; ?>' );
								}
							}
						});
						jQuery('#cr_floatingtrustbadge .cr-floatingbadge-close').click(function(event){
							if( jQuery('#cr_floatingtrustbadge').hasClass( 'cr-floatingbadge-big' ) ) {
								jQuery(this).closest('#cr_floatingtrustbadge').find('div.cr-badge.badge--wide-mobile').hide();
								jQuery(this).closest('#cr_floatingtrustbadge').find('div.cr-badge.badge_size_compact').css( 'display', 'block' );
								jQuery(this).closest('#cr_floatingtrustbadge').removeClass( 'cr-floatingbadge-big' );
								//update colors
								if( 'light' === jQuery('#ivole_trust_badge_floating_type').val() ) {
									jQuery(this).closest('#cr_floatingtrustbadge').css( 'border-color', '<?php echo $this->floating_light['small']['border']; ?>' );
									jQuery(this).closest('#cr_floatingtrustbadge').find('div.cr-floatingbadge-background-top').css( 'background-color', '<?php echo $this->floating_light['small']['top']; ?>' );
									jQuery(this).closest('#cr_floatingtrustbadge').find('div.cr-floatingbadge-background-middle').css( 'background-color', '<?php echo $this->floating_light['small']['middle']; ?>' );
									jQuery(this).closest('#cr_floatingtrustbadge').find('div.cr-floatingbadge-background-bottom').css( 'background-color', '<?php echo $this->floating_light['small']['bottom']; ?>' );
									jQuery(this).closest('#cr_floatingtrustbadge').find('div.cr-floatingbadge-background-bottom').css( 'border-color', '<?php echo $this->floating_light['small']['border']; ?>' );
								} else {
									jQuery(this).closest('#cr_floatingtrustbadge').css( 'border-color', '<?php echo $this->floating_dark['small']['border']; ?>' );
									jQuery(this).closest('#cr_floatingtrustbadge').find('div.cr-floatingbadge-background-top').css( 'background-color', '<?php echo $this->floating_dark['small']['top']; ?>' );
									jQuery(this).closest('#cr_floatingtrustbadge').find('div.cr-floatingbadge-background-middle').css( 'background-color', '<?php echo $this->floating_dark['small']['middle']; ?>' );
									jQuery(this).closest('#cr_floatingtrustbadge').find('div.cr-floatingbadge-background-bottom').css( 'background-color', '<?php echo $this->floating_dark['small']['bottom']; ?>' );
									jQuery(this).closest('#cr_floatingtrustbadge').find('div.cr-floatingbadge-background-bottom').css( 'border-color', '<?php echo $this->floating_dark['small']['border']; ?>' );
								}
							} else {
								jQuery('#cr_floatingtrustbadge').hide();
							}
							event.stopPropagation();
						});
						jQuery('#ivole_trust_badge_floating_type').change(function(){
							if( 'light' === jQuery(this).val()) {
								if( jQuery('#cr_floatingtrustbadge').hasClass( 'cr-floatingbadge-big' ) ) {
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-top').css( 'background-color', '<?php echo $this->floating_light['big']['top']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-middle').css( 'background-color', '<?php echo $this->floating_light['big']['middle']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-bottom').css( 'background-color', '<?php echo $this->floating_light['big']['bottom']; ?>' );
									jQuery('#cr_floatingtrustbadge').css( 'border-color', '<?php echo $this->floating_light['big']['border']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-bottom').css( 'border-color', '<?php echo $this->floating_light['big']['border']; ?>' );
								} else {
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-top').css( 'background-color', '<?php echo $this->floating_light['small']['top']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-middle').css( 'background-color', '<?php echo $this->floating_light['small']['middle']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-bottom').css( 'background-color', '<?php echo $this->floating_light['small']['bottom']; ?>' );
									jQuery('#cr_floatingtrustbadge').css( 'border-color', '<?php echo $this->floating_light['small']['border']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-bottom').css( 'border-color', '<?php echo $this->floating_light['small']['border']; ?>' );
								}
								jQuery('#cr_floatingtrustbadge div.cr-badge.badge--wide-mobile').removeClass( 'badge_color_dark' );
								jQuery('#cr_floatingtrustbadge div.cr-badge.badge_size_compact').removeClass( 'badge_color_dark' );
							} else {
								if( jQuery(this).hasClass( 'cr-floatingbadge-big' ) ) {
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-top').css( 'background-color', '<?php echo $this->floating_dark['big']['top']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-middle').css( 'background-color', '<?php echo $this->floating_dark['big']['middle']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-bottom').css( 'background-color', '<?php echo $this->floating_dark['big']['bottom']; ?>' );
									jQuery('#cr_floatingtrustbadge').css( 'border-color', '<?php echo $this->floating_dark['big']['border']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-bottom').css( 'border-color', '<?php echo $this->floating_dark['big']['border']; ?>' );
								} else {
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-top').css( 'background-color', '<?php echo $this->floating_dark['small']['top']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-middle').css( 'background-color', '<?php echo $this->floating_dark['small']['middle']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-bottom').css( 'background-color', '<?php echo $this->floating_dark['small']['bottom']; ?>' );
									jQuery('#cr_floatingtrustbadge').css( 'border-color', '<?php echo $this->floating_dark['small']['border']; ?>' );
									jQuery('#cr_floatingtrustbadge div.cr-floatingbadge-background-bottom').css( 'border-color', '<?php echo $this->floating_dark['small']['border']; ?>' );
								}
								jQuery('#cr_floatingtrustbadge div.cr-badge.badge--wide-mobile').addClass( 'badge_color_dark' );
								jQuery('#cr_floatingtrustbadge div.cr-badge.badge_size_compact').addClass( 'badge_color_dark' );
							}
						});
						jQuery('#ivole_trust_badge_floating_location').change(function(){
							if( 'bottomleft' === jQuery(this).val()) {
								jQuery('#cr_floatingtrustbadge').css( 'right', 'auto' );
								jQuery('#cr_floatingtrustbadge').css( 'left', '0px' );
							} else {
								jQuery('#cr_floatingtrustbadge').css( 'left', 'auto' );
								jQuery('#cr_floatingtrustbadge').css( 'right', '0px' );
							}
						});
					}
				});
				</script>
				<?php
			}
		}

		public function floating_badge_preview() {
			$float_style = get_option( 'ivole_trust_badge_floating_type', 'light' );
			if( 'light' === $float_style ) {
				$float_colors = $this->floating_light['small'];
				$suffix = 'cl';
				$suffix_big = 'cwl';
			} else {
				$float_colors = $this->floating_dark['small'];
				$suffix = 'cd';
				$suffix_big = 'cwd';
			}
			$float_location = get_option( 'ivole_trust_badge_floating_location', 'bottomright' );
			if( 'bottomleft' === $float_location ) {
				$location_css = "left:0px;";
			} else {
				$location_css = "right:0px;";
			}

			?>
			<div id="cr_floatingtrustbadge" style="border-color: <?php echo $float_colors['border']; ?>; <?php echo $location_css; ?>">
				<div class="cr-floatingbadge-background">
					<div class="cr-floatingbadge-background-top" style="background-color: <?php echo $float_colors['top']; ?>;"></div>
					<div class="cr-floatingbadge-background-middle" style="background-color: <?php echo $float_colors['middle']; ?>;"></div>
					<div class="cr-floatingbadge-background-bottom" style="background-color: <?php echo $float_colors['bottom']; ?>;"></div>
				</div>
				<div class="cr-floatingbadge-top">
					<svg width="70" height="65" viewBox="0 0 70 65" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M34.9752 53.9001L13.3948 65L17.5124 41.4914L0 24.8173L24.2098 21.3758L34.9752 0L45.7902 21.3758L70 24.8173L52.4876 41.4914L56.6052 65L34.9752 53.9001Z" fill="#F4DB6B"></path>
						<path d="M25.8965 38.2439C25.8965 43.1395 29.9645 47.1142 34.9752 47.1142C39.9858 47.1142 44.0538 43.1395 44.0538 38.2439H25.8965Z" fill="#E98B3E"></path>
						<path d="M29.7163 30.7793C29.7163 32.2335 28.5257 33.3968 27.0374 33.3968C25.549 33.3968 24.3584 32.2335 24.3584 30.7793C24.3584 29.3252 25.549 28.1619 27.0374 28.1619C28.5257 28.1619 29.7163 29.3252 29.7163 30.7793Z" fill="#E98B3E"></path>
						<path d="M45.6411 30.7793C45.6411 32.2335 44.4505 33.3968 42.9622 33.3968C41.4739 33.3968 40.2832 32.2335 40.2832 30.7793C40.2832 29.3252 41.4739 28.1619 42.9622 28.1619C44.4505 28.1619 45.6411 29.3252 45.6411 30.7793Z" fill="#E98B3E"></path>
						<path d="M34.9752 0L24.2098 21.3758L0 24.8173L27.9305 25.5444L34.9752 0Z" fill="#F6D15A"></path>
						<path d="M13.3945 65.0001L34.975 53.9002L56.605 65.0001L34.975 48.229L13.3945 65.0001Z" fill="#F6D15A"></path>
					</svg>
					<div class="cr-floatingbadge-close" style="display:none;">
						<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
							<path d="M14.8,12l3.6-3.6c0.8-0.8,0.8-2,0-2.8c-0.8-0.8-2-0.8-2.8,0L12,9.2L8.4,5.6c-0.8-0.8-2-0.8-2.8,0   c-0.8,0.8-0.8,2,0,2.8L9.2,12l-3.6,3.6c-0.8,0.8-0.8,2,0,2.8C6,18.8,6.5,19,7,19s1-0.2,1.4-0.6l3.6-3.6l3.6,3.6   C16,18.8,16.5,19,17,19s1-0.2,1.4-0.6c0.8-0.8,0.8-2,0-2.8L14.8,12z" />
						</svg>
					</div>
				</div>
				<?php echo CR_Trust_Badge::show_html_trust_badge( $suffix, $this->store_stats, '', '', $this->verified_page ); ?>
				<?php echo CR_Trust_Badge::show_html_trust_badge( $suffix_big, $this->store_stats, '', '', $this->verified_page, false ); ?>
			</div>
			<?php
		}

	}

endif;
