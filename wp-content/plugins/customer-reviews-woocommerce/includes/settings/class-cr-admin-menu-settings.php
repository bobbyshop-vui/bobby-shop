<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Settings_Admin_Menu' ) ):

	require_once dirname( dirname( __FILE__ ) ) . '/misc/class-cr-license.php';
	require_once 'class-cr-email-verify.php';
	require_once 'class-cr-milestones.php';

	class CR_Settings_Admin_Menu {

		protected $page_url;
		protected $menu_slug;
		protected $current_tab = 'review_reminder';
		private $download_api;

		public function __construct() {
			$this->menu_slug = 'cr-reviews-settings';

			$this->page_url = add_query_arg( array(
				'page' => $this->menu_slug
			), admin_url( 'admin.php' ) );

			if ( isset( $_GET['tab'] ) ) {
				$this->current_tab = $_GET['tab'];
			}

			$this->download_api = 'https://api.cusrev.com/v1/production/wp-download/';

			add_action( 'admin_init', array( $this, 'save_settings' ) );
			add_action( 'admin_menu', array( $this, 'register_settings_menu' ), 11 );
			add_action( 'admin_enqueue_scripts', array( $this, 'include_scripts' ), 11 );

			add_action( 'woocommerce_admin_field_cselect', array( $this, 'show_cselect' ) );
			add_action( 'woocommerce_admin_field_htmltext', array( $this, 'show_htmltext' ) );
			add_action( 'woocommerce_admin_field_emailtest', array( $this, 'show_emailtest' ) );
			add_action( 'woocommerce_admin_field_watest', array( $this, 'show_watest' ) );
			add_action( 'woocommerce_admin_field_waapitest', array( $this, 'show_waapitest' ) );
			add_action( 'woocommerce_admin_field_license_status', array( $this, 'show_license_status' ) );
			add_action( 'woocommerce_admin_field_textvars', array( $this, 'show_textvars' ) );
			add_action( 'woocommerce_admin_field_exteditable', array( $this, 'show_exteditable' ) );
			add_action( 'cr_admin_settings_footer', array( $this, 'display_features_banner' ) );

			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_enabled_categories', array( $this, 'save_cselect' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_enabled_roles', array( $this, 'save_cselect' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_body', array( $this, 'save_htmltext' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_body_coupon', array( $this, 'save_htmltext' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_body_qna_reply', array( $this, 'save_htmltext' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_wa_message', array( $this, 'save_htmltext' ), 10, 3 );

			add_action( 'wp_ajax_ivole_send_test_email', array( $this, 'send_test_email' ) );
			add_action( 'wp_ajax_cr_send_test_wa', array( $this, 'send_test_wa' ) );
			add_action( 'wp_ajax_cr_send_test_waapi', array( $this, 'send_test_waapi' ) );
			add_action( 'wp_ajax_ivole_check_license_ajax', array( $this, 'check_license_ajax' ) );
			add_action( 'wp_ajax_cr_settings_download_addon', array( $this, 'download_addon' ) );
			add_action( 'wp_ajax_cr_settings_hide_banner', array( $this, 'hide_banner' ) );

			add_filter( 'woocommerce_screen_ids', array( $this, 'filter_woocommerce_screen_ids' ) );
		}

		public function register_settings_menu() {
			add_submenu_page(
				'cr-reviews',
				__( 'Settings', 'customer-reviews-woocommerce' ),
				__( 'Settings', 'customer-reviews-woocommerce' ),
				'manage_options',
				$this->menu_slug,
				array( $this, 'display_settings_admin_page' )
			);
		}

		public function display_settings_admin_page() {
			?>
			<div class="wrap ivole-new-settings cr-tab-<?php echo esc_attr( $this->current_tab ); ?>">
				<h1 class="wp-heading-inline" style="margin-bottom:8px;"><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<hr class="wp-header-end">
				<?php
				$tabs = apply_filters( 'cr_settings_tabs', array() );

				if ( is_array( $tabs ) && sizeof( $tabs ) > 1 ) {
					echo '<ul class="subsubsub">';

					$array_keys = array_keys( $tabs );
					$last = end( $array_keys );

					foreach ( $tabs as $tab => $label ) {
						echo '<li><a href="' . $this->page_url . '&tab=' . $tab . '" class="' . ( $this->current_tab === $tab ? 'current' : '' ) . '">' . $label . '</a> ' . ( $last === $tab ? '' : '|' ) . ' </li>';
					}

					echo '</ul><br class="clear" />';
				}
				?>
				<form action="" method="post" id="mainform" enctype="multipart/form-data">
					<?php
					WC_Admin_Settings::show_messages();

					do_action( 'ivole_settings_display_' . $this->current_tab );
					?>
					<p class="submit">
						<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
							<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
						<?php endif; ?>
						<?php wp_nonce_field( 'ivole-settings' ); ?>
					</p>
				</div>
			</form>
			<?php
			do_action( 'cr_admin_settings_footer', $this->current_tab );
			update_option( 'ivole_activation_notice', 0 );
		}

		public function save_settings() {
			if ( $this->is_this_page() && ! empty( $_POST ) ) {
				check_admin_referer( 'ivole-settings' );

				do_action( 'cr_save_settings_' . $this->current_tab );

				WC_Admin_Settings::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );

				//WPML integration
				if ( defined( 'ICL_LANGUAGE_CODE' ) && class_exists( 'CR_WPML' ) ) {
					CR_WPML::translate_admin( $_POST );
				}
			}
		}

		public function include_scripts() {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'cr-reviews-settings' ) {
				wp_enqueue_script( 'jquery-ui-accordion' );
				wp_register_script( 'cr-admin-settings', plugins_url('js/admin-settings.js', dirname( dirname( __FILE__ ) ) ), array( 'jquery' ), Ivole::CR_VERSION, false );
				wp_localize_script(
					'cr-admin-settings',
					'cr_settings_object',
					array(
						'checking' => __( 'Checking...', 'customer-reviews-woocommerce' ),
						'checking_license' => __( 'Checking license...', 'customer-reviews-woocommerce' ),
						'yes' => __( 'Yes', 'customer-reviews-woocommerce' ),
						'no' => __( 'No', 'customer-reviews-woocommerce' ),
						'max_cus_atts' => CR_Forms_Settings::get_max_cus_atts(),
						'max_rtn_crta' => CR_Forms_Settings_Rating::get_max_rating_criteria(),
						'button_manage' => CR_Forms_Settings::$button_manage,
						'modal_edit' => __( 'Edit Customer Attribute', 'customer-reviews-woocommerce' ),
						'modal_edit_rtn' => __( 'Edit Rating', 'customer-reviews-woocommerce' ),
						'no_attributes' => CR_Forms_Settings::$no_atts,
						'no_ratings' => CR_Forms_Settings_Rating::$no_atts,
						'wa_prepare_test' => __( 'Starting a test...', 'customer-reviews-woocommerce' ),
						'wa_ready_test' => __( 'A test review form is ready. Click on the button to send a WhatsApp message.', 'customer-reviews-woocommerce' ),
						'sending' => __( 'Sending...', 'customer-reviews-woocommerce' ),
						'footer_status' => sprintf( __( 'While editing the footer text please make sure to keep the unsubscribe link markup: %s', 'customer-reviews-woocommerce' ), '<a href="{{unsubscribeLink}}" style="color:#555555; text-decoration: underline; line-height: 12px; font-size: 10px;">unsubscribe</a>.' ),
						'info_from' => 'Review reminders are sent by CusRev from \'feedback@cusrev.com\'. This indicates to customers that review process is independent and trustworthy. \'From Address\' can be modified with the <a href="' . admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ) . '">Pro license</a> for CusRev.',
						'info_from_name' => 'Since review invitations are sent via CusRev, \'From Name\' will be based on \'Shop Name\' (see above) with a reference to CusRev. This field can be modified with the <a href="' . admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ) . '">Pro license</a> for CusRev.',
						'info_footer' => 'To comply with the international laws about sending emails (CAN-SPAM act, CASL laws, etc), CusRev will automatically add a footer with address of the sender and an opt-out link. The footer can be modified with the <a href="' . admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ) . '">Pro license</a> for CusRev.',
						'info_rating_bar' => 'CusRev creates review forms that support two visual styles of rating bars: smiley/frowny faces and stars. The default style is smiley/frowny faces. This option can be modified with the <a href="' . admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ) . '">Pro license</a> for CusRev.',
						'info_geolocation' => 'CusRev supports automatic determination of geolocation and gives reviewers an option to indicate where they are from. For example, "England, United Kingdom". This feature requires the <a href="' . admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ) . '">Pro license</a> for CusRev.',
						'dns_copied' => __( 'DNS record copied', 'customer-reviews-woocommerce' ),
						'dns_enabled' => __( 'Enabled', 'customer-reviews-woocommerce' ),
						'dns_disabled' => __( 'Disabled', 'customer-reviews-woocommerce' ),
						'dns_pending' => __( 'Pending', 'customer-reviews-woocommerce' )
					)
				);
				wp_enqueue_script( 'cr-admin-settings' );
			}

			if ( $this->is_this_page() ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'ivole-color-picker', plugins_url('js/admin-color-picker.js', dirname( dirname( __FILE__ ) ) ), array( 'wp-color-picker' ), Ivole::CR_VERSION, true );
			}
		}

		public function filter_woocommerce_screen_ids( $screen_ids ) {
			$reviews_screen_id = sanitize_title( __( 'Reviews', 'customer-reviews-woocommerce' ) . Ivole_Reviews_Admin_Menu::$screen_id_bubble );
			$screen_ids[] = $reviews_screen_id . '_page_cr-reviews-settings';
			$screen_ids[] = $reviews_screen_id . '_page_cr-reviews-diagnostics';
			$screen_ids[] = $reviews_screen_id . '_page_cr-reviews-product-feed';
			return $screen_ids;
		}

		public function is_this_page() {
			return ( isset( $_GET['page'] ) && $_GET['page'] === $this->menu_slug );
		}

		public function get_current_tab() {
			return $this->current_tab;
		}

		/**
		* Custom field type for categories
		*/
		public function show_cselect( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];

			$args = array(
				'number'     => 0,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false,
				'fields'     => 'id=>name'
			);

			if ( $value['id'] == 'ivole_enabled_categories' || $value['id'] == 'ivole_coupon__product_categories' || $value['id'] == 'ivole_coupon__excluded_product_categories' ) {
				$all_options = get_terms('product_cat', $args);
				// WPML filters product categories by the current language, the code below unfilters them
				if ( has_filter( 'wpml_current_language' ) && defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
					$current_lang = apply_filters( 'wpml_current_language', NULL );
					$languages = apply_filters( 'wpml_active_languages', NULL );
					if ( !empty( $languages ) ) {
						foreach( $languages as $l_key => $l_value ) {
							do_action( 'wpml_switch_language', $l_key );
							$all_options_wpml = get_terms('product_cat', $args);
							$all_options = $all_options + $all_options_wpml;
						}
					}
					do_action( 'wpml_switch_language', $current_lang );
				}
				$ph = 'categories';
				$label = 'Category';
			} elseif ($value['id'] == 'ivole_enabled_roles' || $value['id'] == 'ivole_coupon_enabled_roles') {
				global $wp_roles;
				$all_options = $wp_roles->get_names();
				$ph = 'user roles';
				$label = 'Role';
			}

			$selections = (array) WC_Admin_Settings::get_option( $value['id'] );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp">
					<select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="min-width:350px;"  data-placeholder="<?php esc_attr_e( 'Choose '.$ph.'&hellip;', 'customer-reviews-woocommerce' ); ?>" aria-label="<?php esc_attr_e( $label, 'customer-reviews-woocommerce' ) ?>" class="wc-enhanced-select">
						<option value="" selected="selected"></option>
						<?php
						if ( ! empty( $all_options ) ) {
							foreach ( $all_options as $key => $val ) {
								echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selections ), true, false ) . '>' . $val . '</option>';
							}
						}
						?>
					</select>
					<?php echo ( $description ) ? $description : ''; ?>
					<br />
					<a class="select_all button" href="#"><?php _e( 'Select all', 'customer-reviews-woocommerce' ); ?></a>
					<a class="select_none button" href="#"><?php _e( 'Select none', 'customer-reviews-woocommerce' ); ?></a>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for body email
		*/
		public function show_htmltext( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$variables = $tmp['variables'];
			$default_text = $tmp['default'];

			$body = wp_kses_post( WC_Admin_Settings::get_option( $value['id'], $default_text ) );
			$settings = array (
				'teeny' => true,
				'editor_css' => '<style>#wp-ivole_email_body-wrap {max-width: 700px !important;}</style>',
				'textarea_rows' => 20
			);
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?>
						<?php echo $tooltip_html; ?>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<?php echo $description; ?>
					<?php wp_editor( $body, esc_attr( $value['id'] ), $settings );
					echo '<div">';
					echo '<p style="font-weight:bold;margin-top:1.5em;font-size=1em;">' . __( 'Variables', 'customer-reviews-woocommerce' ) . '</p>';
					echo '<p>' . __( 'You can use the following variables in the email template:', 'customer-reviews-woocommerce' ) . '</p>';
					if( 0 < count( $variables ) ) {
						foreach ($variables as $key => $value) {
							echo '<p>' . $value . '</p>';
						}
					}
					echo '</div>';
					?>
				</td>
			</tr>
			<?php
		}

		public function show_textvars( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$variables = $tmp['variables'];
			$default_text = $tmp['default'];

			$body = wp_kses_post( WC_Admin_Settings::get_option( $value['id'], $default_text ) );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?>
						<?php echo $tooltip_html; ?>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<textarea id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>" rows="7" class="cr-admin-textvars"><?php echo $body; ?></textarea>
					<?php
					echo '<div">';
					echo '<p style="font-weight:bold;margin-top:1.5em;font-size=1em;">' . __( 'Variables', 'customer-reviews-woocommerce' ) . '</p>';
					echo '<p>' . __( 'You can use the following variables in the email template:', 'customer-reviews-woocommerce' ) . '</p>';
					if( 0 < count( $variables ) ) {
						foreach ($variables as $key => $value) {
							echo '<p>' . $value . '</p>';
						}
					}
					echo '</div>';
					?>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for email test
		*/
		public function show_emailtest( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$coupon_class = '';

			if( false !== strpos( $value['class'], 'coupon_mail' )  ) {
				$coupon_class='coupon_mail';
			}
			?>
			<tr valign="top">
				<th scope="row" class="titledesc cr-send-test-th">
					<div style="position:relative;">
						<?php echo esc_html( $value['title'] ); ?>
						<?php echo $tooltip_html; ?>
					</div>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>" />
					<?php echo $description; ?>
					<input type="button" value="<?php _e( 'Send Test', 'customer-reviews-woocommerce' ); ?>"
					data-nonce="<?php echo wp_create_nonce( 'cr-send-test-email' ); ?>"
					class="cr-test-email-button button-primary <?php echo $coupon_class; ?>" />
					<p id="ivole_test_email_status" style="font-style:italic;visibility:hidden;"></p>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for WhatsApp test (click to chat)
		*/
		public function show_watest( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];

			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="cr_wa_test"><?php echo esc_html( $value['title'] ); ?>
						<?php echo $tooltip_html; ?>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input
					id="cr_wa_test"
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>" />
					<?php echo $description; ?>
					<div class="cr-test-wa-cont">
						<a href="" data-nonce="<?php echo wp_create_nonce( 'cr-send-test-wa' ); ?>"
						class="cr-test-wa-button button-primary">
							<span class="cr-test-wa-prep"><?php _e( 'Send Test', 'customer-reviews-woocommerce' ); ?></span>
							<span class="cr-test-wa-send"><?php _e( 'Send', 'customer-reviews-woocommerce' ); ?></span>
							<img src="<?php echo plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/spinner-dots.svg'; ?>" alt="Loading" />
						</a>
						<span class="dashicons dashicons-external cr-test-wa-ext"></span>
					</div>
					<p class="cr-test-wa-status" style="font-style:italic;visibility:hidden;"></p>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for WhatsApp test (API)
		*/
		public function show_waapitest( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];

			?>
			<tr valign="top">
				<th scope="row" class="titledesc cr-send-test-th">
					<div style="position:relative;">
						<?php echo esc_html( $value['title'] ); ?>
						<?php echo $tooltip_html; ?>
					</div>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>" />
					<?php echo $description; ?>
					<input type="button" value="<?php _e( 'Send Test', 'customer-reviews-woocommerce' ); ?>"
					data-nonce="<?php echo wp_create_nonce( 'cr-send-test-waapi' ); ?>"
					data-testtype="<?php echo ( 'cr-test-wa-coupon-input' === $value['class'] ? 1 : 0 ); ?>"
					class="cr-test-waapi-button button-primary" />
					<p class="cr-test-waapi-status" style="font-style:italic;visibility:hidden;"></p>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for license status
		*/
		public function show_license_status( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?>
						<?php echo $tooltip_html; ?>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					readonly />
					<?php echo $description; ?>
					<p id="ivole_test_email_status" style="font-style:italic;visibility:hidden;">A</p>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for an externally editable field
		*/
		public function show_exteditable( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];

			?>
			<tr valign="top">
				<th scope="row" class="titledesc cr-send-test-th">
					<div style="position:relative;">
						<?php echo esc_html( $value['title'] ); ?>
						<?php echo $tooltip_html; ?>
					</div>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<p class="cr-exteditable-p"><?php echo $description; ?></p>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for saving body email
		*/
		public function save_htmltext( $value, $option, $raw_value ) {
			return wp_kses_post( $raw_value );
		}

		/**
		* Custom field type for categories
		*/
		public function save_cselect( $value, $option, $raw_value ) {
			if( is_array( $value ) ) {
				$value = array_filter( $value, function($v){ return $v != ""; } );
			} else {
				$value = array();
			}
			return $value;
		}

		/**
		* Function that sends testing email
		*/
		public function send_test_email() {
			global $q_config;

			if( ! check_ajax_referer( 'cr-send-test-email', 'nonce', false ) ) {
				wp_send_json( array( 'code' => 96, 'message' => __( 'Error: nonce expired, please reload the page and try again', 'customer-reviews-woocommerce' ) ) );
			}

			$email = strval( $_POST['email'] );
			$q_language = $_POST['q_language'];
			//integration with qTranslate
			if ( $q_language >= 0 ) {
				$q_config['language'] = $q_language;
			}

			if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$shop_name = Ivole_Email::get_blogname();
				// check that shop name field (blog name) is not empty
				if ( strlen( $shop_name ) > 0 ) {
					if( isset( $_POST['type'] ) ) {

						switch( $_POST['type'] ) {
							case 'review_reminder':
								$e = new Ivole_Email();
								$result = $e->trigger2( null, $email, false );
								break;
							case 'qna_reply':
								$qe = new CR_Qna_Email( $_POST['type'] );
								$result = $qe->send_test( $email );
								break;
							default:
								break;
						}

					}

					if ( is_array( $result ) && count( $result)  > 1 && 2 === $result[0] ) {
						wp_send_json( array( 'code' => 2, 'message' => $result[1] ) );
					} elseif( is_array( $result ) && count( $result)  > 1 && 100 === $result[0] ) {
						wp_send_json( array( 'code' => 100, 'message' => $result[1] ) );
					} elseif( is_array( $result ) && count( $result)  > 1 ) {
						wp_send_json( array( 'code' => $result[0], 'message' => $result[1] ) );
					} elseif ( 0 === $result ) {
						wp_send_json( array( 'code' => 0, 'message' => '' ) );
					} elseif ( 1 === $result ) {
						wp_send_json( array( 'code' => 1, 'message' => '' ) );
					} elseif ( 13 === $result ) {
						wp_send_json( array( 'code' => 13, 'message' => '' ) );
					}
				} else {
					wp_send_json( array( 'code' => 97, 'message' => '' ) );
				}
			} else {
				wp_send_json( array( 'code' => 99, 'message' => '' ) );
			}

			wp_send_json( array( 'code' => 98, 'message' => '' ) );
		}

		public function send_test_wa() {
			if( ! check_ajax_referer( 'cr-send-test-wa', 'nonce', false ) ) {
				wp_send_json(
					array(
						'code' => 96,
						'message' => __( 'Error: nonce expired, please reload the page and try again', 'customer-reviews-woocommerce' )
					)
				);
			}

			$phone = strval( $_POST['phone'] );

			$shop_country = '';
			$base_location = wc_get_base_location();
			if (
				$base_location &&
				is_array( $base_location ) &&
				$base_location['country']
			) {
				$shop_country = $base_location['country'];
			}

			// check if customer phone number is valid
			$vldtr = new CR_Phone_Vldtr();
			$phone = $vldtr->parse_phone_number( $phone, $shop_country );
			if ( ! $phone ) {
				wp_send_json(
					array(
						'code' => 95,
						'message' => __( 'Error: phone number is not valid. Use a full phone number in international format. Omit any zeroes, brackets, or dashes.', 'customer-reviews-woocommerce' )
					)
				);
			}

			// create a test review form
			$wa = new CR_Wtsap();
			$form = $wa->get_test_form( $phone );
			if (
				is_array( $form ) &&
				1 < count( $form )
			) {
				if ( 0 === $form[0] ) {
					wp_send_json(
						array(
							'code' => $form[0],
							'link' => $form[1]
						)
					);
				} else {
					wp_send_json(
						array(
							'code' => $form[0],
							'message' => $form[1]
						)
					);
				}
			} else {
				wp_send_json(
					array(
						'code' => 96,
						'message' => __( 'Error: a test review form could not be created.', 'customer-reviews-woocommerce' )
					)
				);
			}

			wp_send_json( array( 'code' => 98, 'message' => '' ) );
		}

		public function send_test_waapi() {
			if( ! check_ajax_referer( 'cr-send-test-waapi', 'nonce', false ) ) {
				wp_send_json(
					array(
						'code' => 96,
						'message' => __( 'Error: nonce expired, please reload the page and try again', 'customer-reviews-woocommerce' )
					)
				);
			}

			$phone = strval( $_POST['phone'] );

			$shop_country = '';
			$base_location = wc_get_base_location();
			if (
				$base_location &&
				is_array( $base_location ) &&
				$base_location['country']
			) {
				$shop_country = $base_location['country'];
			}

			// check if customer phone number is valid
			$vldtr = new CR_Phone_Vldtr();
			$phone = $vldtr->parse_phone_number( $phone, $shop_country );
			if ( ! $phone ) {
				wp_send_json(
					array(
						'code' => 95,
						'message' => __( 'Error: phone number is not valid. Use a full phone number in international format. Omit any zeroes, brackets, or dashes.', 'customer-reviews-woocommerce' )
					)
				);
			}

			// determine which WhatsApp template needs to be tested
			$test_type = intval( $_POST['test_type'] );

			// check if media files count was provided for simulation
			$media_count = 0;
			if ( isset( $_POST['media_count'] ) ) {
				$media_count = intval( $_POST['media_count'] );
			}

			// create a test review form
			$wa = new CR_Wtsap();
			$res = $wa->send_test( $phone, $test_type, $media_count, $shop_country );
			if (
				is_array( $res ) &&
				1 < count( $res )
			) {
				if ( 0 === $res[0] ) {
					wp_send_json(
						array(
							'code' => $res[0],
							'message' => $res[1]
						)
					);
				} else {
					wp_send_json(
						array(
							'code' => $res[0],
							'message' => $res[1]
						)
					);
				}
			} else {
				wp_send_json(
					array(
						'code' => 96,
						'message' => __( 'Error: a test message could not be sent.', 'customer-reviews-woocommerce' )
					)
				);
			}

			wp_send_json( array( 'code' => 98, 'message' => 'Generic error (code 98)' ) );
		}

		/**
		* Function to check status of the license
		*/
		public function check_license_ajax() {
			$license = new CR_License();
			$lval = $license->check_license();
			wp_send_json( array( 'code' => $lval['code'], 'message' => $lval['info'] ) );
		}

		public function download_addon() {
			$res = array(
				'url' => ''
			);

			$license_key = trim( get_option( 'ivole_license_key', '' ) );
			$download_res = wp_remote_get(
				$this->download_api . $license_key,
				array(
					'timeout' => 10,
					'headers' => array(
						'Accept' => 'application/json'
					)
				)
			);

			if(
				! is_wp_error( $download_res )
				&& 200 === wp_remote_retrieve_response_code( $download_res )
				&& ! empty( wp_remote_retrieve_body( $download_res ) )
			) {
				$download_res = json_decode( wp_remote_retrieve_body( $download_res ) );
				if( 'ok' === $download_res->status ) {
					$res['url'] = $download_res->downloadUrl;
				}
			}

			wp_send_json( $res );
		}

		public function display_features_banner( $current_tab ) {
			if( 'review_reminder' === $current_tab ) {
				if( ! $this->is_pro_addon_activated() ) {
					if( $this->is_banner_hidden( 'review_reminder' ) ) {
						return;
					}
					?>
						<div class="cr-features-banner">
								<div class="cr-features-bnr-col1">
									<img src="<?php echo plugins_url( 'img/reminders-banner.svg', dirname( dirname( __FILE__ ) ) ) ; ?>">
								</div>
								<div class="cr-features-bnr-col2">
									<div class="cr-features-bnr-title">Get more with a Pro version</div>
									<div class="cr-features-bnr-subtitle">Install a Pro add-on to get advanced customization options and dedicated email support</div>
									<div class="cr-features-bnr-uls">
										<ul class="cr-features-bnr-ul">
											<li>An advanced editor for review forms templates</li>
											<li>Customize colors and texts on review forms</li>
											<li>Show or hide prices and display stars on rating bars</li>
											<li>Automatic sending of a second review reminder</li>
										</ul>
										<ul class="cr-features-bnr-ul">
											<li>Set specific times of day for review reminders</li>
											<li>Set delays for reminders per destination country</li>
											<li>Exclude certain emails from review reminders</li>
											<li>Limit review invitations to one per customer</li>
										</ul>
									</div>
									<div class="cr-features-bnr-other">And other powerful features...</div>
									<div class="cr-features-bnr-buttons">
										<a class="button cr-features-bnr-pricing" href="https://www.cusrev.com/business/pricing.html?utm_source=wp_plugin&utm_medium=review_reminder" target="_blank" rel="noopener noreferrer">See pricing<img src="<?php esc_attr_e( untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link-2.svg' ); ?>"></a>
										<a class="cr-features-bnr-hide" href="#" data-banner="review_reminder">Hide this message</a>
									</div>
								</div>
						</div>
					<?php
				}
			} elseif( 'emails' === $current_tab ) {
				if( isset( $_GET['section'] ) ) {
					$section = $_GET['section'];
					if(
						'review_reminder' === $section ||
						'review_discount' === $section
					) {
						if( ! $this->is_pro_addon_activated() ) {
							if( $this->is_banner_hidden( 'emails_' . $section ) ) {
								return;
							}
							?>
								<div class="cr-features-banner">
										<div class="cr-features-bnr-col1">
											<img src="<?php echo plugins_url( 'img/emails-banner.svg', dirname( dirname( __FILE__ ) ) ) ; ?>">
										</div>
										<div class="cr-features-bnr-col2">
											<div class="cr-features-bnr-title">Get more with a Pro version</div>
											<div class="cr-features-bnr-subtitle">Install a Pro add-on to get advanced customization options and dedicated email support</div>
											<div class="cr-features-bnr-uls">
												<ul class="cr-features-bnr-ul">
													<li>A visual editor for email templates</li>
													<li>Customize colors and fonts</li>
													<li>Multiple email templates for different languages</li>
												</ul>
												<ul class="cr-features-bnr-ul">
													<li>Add an unsubscribe link to emails</li>
													<li>A shortcode to display an unsubscribe form</li>
													<li>Add a custom logo and images</li>
												</ul>
											</div>
											<div class="cr-features-bnr-other">And other powerful features...</div>
											<div class="cr-features-bnr-buttons">
												<a class="button cr-features-bnr-pricing" href="https://www.cusrev.com/business/pricing.html?utm_source=wp_plugin&utm_medium=emails_<?php esc_attr_e( $section ); ?>" target="_blank" rel="noopener noreferrer">See pricing<img src="<?php esc_attr_e( untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link-2.svg' ); ?>"></a>
												<a class="cr-features-bnr-hide" href="#" data-banner="emails_<?php esc_attr_e( $section ); ?>">Hide this message</a>
											</div>
										</div>
								</div>
							<?php
						}
					}
				}
			}
		}

		public function hide_banner() {
			$res = 1;
			$hidden_banners = get_option( 'ivole_hidden_banners', array() );
			if( isset( $_POST['banner'] ) && $_POST['banner'] ) {
				$hidden_banners[$_POST['banner']] = 1;
				update_option( 'ivole_hidden_banners', $hidden_banners, false );
				$res = 0;
			}
			wp_send_json( $res );
		}

		private function is_pro_addon_activated() {
			global $cr_activated_plugins;
			$pro_addon = 'customer-reviews-woocommerce-pro/customer-reviews-woocommerce-pro.php';
			if(
				! in_array( $pro_addon, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) &&
				! ( is_multisite() && isset( $cr_activated_plugins[$pro_addon] ) )
			) {
				return false;
			}
			return true;
		}

		private function is_banner_hidden( $banner ) {
			$hidden_banners = get_option( 'ivole_hidden_banners', array() );
			if( $hidden_banners && is_array( $hidden_banners ) ) {
				if( isset( $hidden_banners[$banner] ) && 1 === $hidden_banners[$banner] ) {
					return true;
				}
			}
			return false;
		}

	}

endif;
