<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Forms_Settings' ) ) :

	require_once( 'class-cr-settings-forms-rating.php' );

	class CR_Forms_Settings {

		protected $settings_menu;
		protected $tab;
		protected $settings;
		protected $current_section;
		protected $templates;

		public static $button_manage;
		public static $att_types;
		public static $no_atts;
		public static $help_attribute;
		public static $help_label;
		public static $help_type;
		public static $help_required;

		public function __construct( $settings_menu ) {
			$this->settings_menu = $settings_menu;
			$this->tab = 'forms';
			$this->current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) );
			$this->templates = array(
				'onsite' => 'onsite',
				'review_discount' => 'review_discount'
			);
			self::$button_manage = '<button type="button" class="cr-cus-atts-button-manage"><span class="dashicons dashicons-ellipsis"></span></button>';
			self::$button_manage .= '<ul class="cr-cus-atts-menu cr-generic-hide">';
			self::$button_manage .= '<li class="cr-cus-atts-menu-up">' . __( 'Move up', 'customer-reviews-woocommerce' ) . '</li>';
			self::$button_manage .= '<li class="cr-cus-atts-menu-down">' . __( 'Move down', 'customer-reviews-woocommerce' ) . '</li>';
			self::$button_manage .= '<li class="cr-cus-atts-menu-edit">' . __( 'Edit', 'customer-reviews-woocommerce' ) . '</li>';
			self::$button_manage .= '<li class="cr-cus-atts-menu-delete">' . __( 'Delete', 'customer-reviews-woocommerce' ) . '</li></ul>';
			self::$att_types = CR_Reviews::$onsite_q_types;
			self::$no_atts = '<tr class="cr-cus-atts-table-empty"><td colspan="5">' . __( 'No questions added', 'customer-reviews-woocommerce' ) . '</td></tr>';
			self::$help_attribute = __( 'A question to be displayed on an on-site review form. For example, \'How old are you?\'.', 'customer-reviews-woocommerce' );
			self::$help_label = __( 'A label to be displayed on a review next to a customer\'s answer to a question. For example, \'Age\'.', 'customer-reviews-woocommerce' );
			self::$help_type = __( 'A type of question defines what kind of answers a customer can submit.', 'customer-reviews-woocommerce' );
			self::$help_required = __( 'This field can be used to require customers to answer a question.', 'customer-reviews-woocommerce' );

			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_settings_' . $this->tab, array( $this, 'save' ) );
			add_action( 'woocommerce_admin_field_cr_customer_attributes', array( $this, 'display_customer_attributes' ) );
			add_action( 'woocommerce_admin_field_cr_rating_criteria', array( 'CR_Forms_Settings_Rating', 'display_rating_criteria' ) );
			add_action( 'woocommerce_admin_field_cr_review_permissions', array( $this, 'display_review_permissions' ) );
			add_action( 'woocommerce_admin_field_cr_text_w_links', array( $this, 'display_text_w_links' ) );

			new CR_Forms_Settings_Rating();
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Review Forms', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			if( $this->current_section ) {
				$section = apply_filters( 'cr_settings_forms_sections', false, $this->current_section );
				if( $section ) {
					echo $section;
					return;
				}
			} else {
				// if( $this->current_section ) {
				// 	$section = apply_filters( 'cr_settings_emails_sections', false, $this->current_section );
				// 	if( $section ) {
				// 		echo $section;
				// 		return;
				// 	}
				// }
				$this->init_settings();
				WC_Admin_Settings::output_fields( $this->settings );
			}
		}

		public function save() {
			if( in_array( $this->current_section, $this->templates ) ) {
				// $email_template = new CR_Email_Template( $this->current_section );
				// $email_template->save_fields();
			} else {
				$this->init_settings();
				// make sure that there the maximum number of attached images is larger than zero
				if( !empty( $_POST ) && isset( $_POST['ivole_attach_image_quantity'] ) ) {
					if( $_POST['ivole_attach_image_quantity'] <= 0 ) {
						$_POST['ivole_attach_image_quantity'] = 1;
					}
				}
				// make sure that there the maximum size of attached image is larger than zero
				if( !empty( $_POST ) && isset( $_POST['ivole_attach_image_size'] ) ) {
					if( $_POST['ivole_attach_image_size'] <= 0 ) {
						$_POST['ivole_attach_image_size'] = 1;
					}
				}
				$ivole_review_forms = array(
					array(
						'rtn_crta' => '',
						'cus_atts' => ''
					)
				);
				$update_ivole_review_forms = false;
				// save the additional ratings
				if ( ! empty( $_POST ) && isset( $_POST['ivole_rating_criteria'] ) ) {
					$rtn_crta = json_decode( stripslashes( $_POST['ivole_rating_criteria'] ), true );
					$rtn_crta = array_slice( $rtn_crta, 0, CR_Forms_Settings_Rating::get_max_rating_criteria() );
					$ivole_review_forms[0]['rtn_crta'] = $rtn_crta;
					$update_ivole_review_forms = true;
				}
				// save the customer attributes
				if ( ! empty( $_POST ) && isset( $_POST['ivole_customer_attributes'] ) ) {
					$cus_atts = json_decode( stripslashes( $_POST['ivole_customer_attributes'] ), true );
					$cus_atts = array_slice( $cus_atts, 0, self::get_max_cus_atts() );
					$ivole_review_forms[0]['cus_atts'] = $cus_atts;
					$update_ivole_review_forms = true;
				}
				// save the review permissions
				if ( ! empty( $_POST ) && isset( $_POST['ivole_review_permissions'] ) ) {
					$rev_perm = strval( $_POST['ivole_review_permissions'] );
					$ivole_review_forms[0]['rev_perm'] = $rev_perm;
					$update_ivole_review_forms = true;
				}
				// save the terms and privacy checkbox
				if ( ! empty( $_POST ) ) {
					if (
						isset( $_POST['ivole_onsite_form_checkbox'] ) &&
						1 == $_POST['ivole_onsite_form_checkbox']
					) {
						$ivole_review_forms[0]['chbx'] = '1';
					} else {
						$ivole_review_forms[0]['chbx'] = '';
					}
					$update_ivole_review_forms = true;
				}
				// save the terms and privacy text
				if ( ! empty( $_POST ) && isset( $_POST['ivole_onsite_form_checkbox_text'] ) ) {
					$ivole_review_forms[0]['chbx_text'] = esc_html( $_POST['ivole_onsite_form_checkbox_text'] );
					$update_ivole_review_forms = true;
				}
				//
				if ( $update_ivole_review_forms ) {
					$_POST['ivole_review_forms'] = $ivole_review_forms;
				}
				// save the geolocation setting
				if( ! empty( $_POST ) ) {
					if( isset( $_POST['ivole_form_geolocation'] ) ) {
						$_POST['ivole_form_geolocation'] = '1' === $_POST['ivole_form_geolocation'] || 'yes' === $_POST['ivole_form_geolocation'] ? 'yes' : 'no';
					} else {
						$_POST['ivole_form_geolocation'] = 'no';
					}
				}
				// validate that the form header is not empty
				if( ! empty( $_POST ) && isset( $_POST['ivole_form_header'] ) ) {
					if( empty( $_POST['ivole_form_header'] ) ) {
						WC_Admin_Settings::add_error( __( '\'Form Header\' field cannot be empty', 'customer-reviews-woocommerce' ) );
						$_POST['ivole_form_header'] = get_option( 'ivole_form_header' );
					}
				}
				// validate that the form body is not empty
				if( ! empty( $_POST ) && isset( $_POST['ivole_form_body'] ) ) {
					if( empty( preg_replace( '#\s#isUu', '', html_entity_decode( $_POST['ivole_form_body'] ) ) ) ) {
						WC_Admin_Settings::add_error( __( '\'Form Body\' field cannot be empty', 'customer-reviews-woocommerce' ) );
						$_POST['ivole_form_body'] = get_option( 'ivole_form_body' );
					} elseif ( 1024 < strlen( $_POST['ivole_form_body'] ) ) {
						WC_Admin_Settings::add_error( __( '\'Form Body\' field cannot be longer than 1024 characters', 'customer-reviews-woocommerce' ) );
						$_POST['ivole_form_body'] = get_option( 'ivole_form_body' );
					}
				}
				// make sure that there the maximum number of attached images is larger than zero
				if( !empty( $_POST ) && isset( $_POST['ivole_attach_image_quantity'] ) ) {
					if( $_POST['ivole_attach_image_quantity'] <= 0 ) {
						$_POST['ivole_attach_image_quantity'] = 1;
					}
				}
				// make sure that there the maximum size of attached image is larger than zero
				if( !empty( $_POST ) && isset( $_POST['ivole_attach_image_size'] ) ) {
					if( $_POST['ivole_attach_image_size'] <= 0 ) {
						$_POST['ivole_attach_image_size'] = 1;
					}
				}
				// validate colors (users sometimes remove # or provide invalid hex color codes)
				if ( ! empty( $_POST ) && isset( $_POST['ivole_form_color_bg'] ) ) {
					if( ! preg_match_all( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['ivole_form_color_bg'] ) ) {
						$_POST['ivole_form_color_bg'] = '#0f9d58';
					}
				}
				if ( ! empty( $_POST ) && isset( $_POST['ivole_form_color_text'] ) ) {
					if( ! preg_match_all( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['ivole_form_color_text'] ) ) {
						$_POST['ivole_form_color_text'] = '#ffffff';
					}
				}
				WC_Admin_Settings::save_fields( $this->settings );
			}
		}

		protected function init_settings() {
			$verified_reviews = get_option( 'ivole_verified_reviews', 'no' );

			$onsite_desc = sprintf( __( 'On-site product review forms are displayed on your website. If reviews are enabled, WooCommerce displays them on product pages. However, on-site review forms can also be displayed using <a href="%s">shortcodes</a> or blocks.', 'customer-reviews-woocommerce' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=shortcodes' ) );

			$aggreg_desc = sprintf( __( 'An aggregated review form is a review form that supports collection of reviews for multiple products at the same time. The plugin will automatically create an aggregated review form when sending a review reminder. You can read more about aggregated review forms on <a href="%s">this page</a> of our Helpdesk.', 'customer-reviews-woocommerce' ), 'https://help.cusrev.com/support/solutions/articles/43000051497-what-is-an-aggregated-review-form-' );
			$aggreg_desc = '<p>' . $aggreg_desc . '</p>';
			if( 'yes' === $verified_reviews ) {
				$desc = sprintf( __( 'Adjust template of the aggregated review forms that will be created and sent to customers by CusRev. Modifications will be applied to the next review form created after saving settings. If you enable <b>advanced</b> form templates in your account on %1$sCusRev website%2$s, they will <b>override</b> the settings below.', 'customer-reviews-woocommerce' ), '<a href="https://www.cusrev.com/login.html" target="_blank" rel="noopener noreferrer">', '</a>' );
			} else {
				$desc = __( 'Adjust template of the aggregated review forms that will be created and sent to customers. Modifications will be applied to the next review form created after saving settings.', 'customer-reviews-woocommerce' );
			}
			$aggreg_desc .= '<p>' . $desc . '</p>';

			if( 'yes' === $verified_reviews ) {
				$media_desc = __( 'Enable attachment of pictures and videos on aggregated review forms. Uploaded media files are initially stored on Amazon S3 and automatically downloaded into WordPress Media Library later. This option applies only to aggregated review forms. If you would like to enable attachment of pictures to reviews submitted on WooCommerce product pages, please do it in the settings for on-site review forms.', 'customer-reviews-woocommerce' );
			} else {
				$media_desc = __( 'Enable attachment of pictures and videos on local aggregated review forms. This option applies only to aggregated review forms. If you would like to enable attachment of pictures to reviews submitted on WooCommerce product pages, please do it in the settings for on-site review forms.', 'customer-reviews-woocommerce' );
			}

			$this->settings = array(
				1 => array(
					'title' => __( 'On-site Product Review Form', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => $onsite_desc,
					'id'    => 'cr_options_onsite_forms'
				),
				3 => array(
					'title'   => __( 'Rating Criteria', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Set up additional rating criteria for on-site review forms. Use the additional criteria to let your customers rate various features of products. For example, if you are selling footwear, you might want to ask customers to rate features like comfort, value for money, and style.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_review_forms_rating',
					'type'    => 'cr_rating_criteria'
				),
				5 => array(
					'title'   => __( 'Questions', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Set up additional questions for on-site review forms. Use the additional questions to get more information from your customers beyond star ratings and reviews. For example, if you are selling skincare products, you might want to ask customers about their skin type.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_review_forms',
					'type'    => 'cr_customer_attributes'
				),
				10 => array(
					'title'   => __( 'Attach Images/Videos', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable attachment of images and videos to reviews left on WooCommerce product pages.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_attach_image',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				15 => array(
					'title'    => __( 'Quantity of Media Files', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Specify the maximum number of images and videos that can be uploaded for a single review. This setting applies only to reviews submitted on single product pages.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_attach_image_quantity',
					'default'  => 5,
					'type'     => 'number',
					'desc_tip' => true
				),
				20 => array(
					'title'    => __( 'Maximum Size of Media File', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Specify the maximum size (in MB) of an image or a video that can be uploaded with a review. This setting applies only to reviews submitted on single product pages.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_attach_image_size',
					'default'  => 25,
					'type'     => 'number',
					'desc_tip' => true
				),
				23 => array(
					'title'    => __( 'Review Permissions', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Specify review permissions for on-site review forms. This setting applies to review forms on single product pages when CusRev visual style is enabled and to review forms added via shortcodes.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_review_permissions',
					'type'     => 'cr_review_permissions',
					'desc_tip' => true,
					'options'  => array(
						'nobody'  => __( 'Nobody can submit reviews', 'customer-reviews-woocommerce' ),
						'registered' => __( 'Reviewers must be registered and logged in', 'customer-reviews-woocommerce' ),
						'verified' => __( 'Reviewers must be verified owners', 'customer-reviews-woocommerce' ),
						'anybody' => __( 'Anyone can submit reviews', 'customer-reviews-woocommerce' )
					)
				),
				24 => array(
					'title'    => __( 'Login URL', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					/* translators: keep %1$s and %2$s as is, they will be automatically replaced with a name of a WordPress function and a standard login URL correspondingly */
					'desc'     => sprintf(
						__( 'Customize the URL for the login button on on-site review forms. You can override the default URL returned by the WordPress %1$s function with a custom URL specified in this field. If left blank, the standard URL %2$s will be used.', 'customer-reviews-woocommerce' ),
						'\'wp_login_url\'',
						wp_login_url()
					),
					'default'  => '',
					'id'       => 'ivole_review_login_url',
					'desc_tip' => true,
					'autoload' => false
				),
				25 => array(
					'title'   => __( 'reCAPTCHA V2 for Reviews', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable reCAPTCHA to eliminate fake reviews. You must enter Site Key and Secret Key in the fields below if you want to use reCAPTCHA. You will receive Site Key and Secret Key after registration at reCAPTCHA website.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_enable_captcha',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				30 => array(
					'title'    => __( 'reCAPTCHA V2 Site Key', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'desc'     => __( 'If you want to use reCAPTCHA V2, insert here Site Key that you will receive after registration at reCAPTCHA website.', 'customer-reviews-woocommerce' ),
					'default'  => '',
					'id'       => 'ivole_captcha_site_key',
					'desc_tip' => true
				),
				35 => array(
					'title'    => __( 'reCAPTCHA V2 Secret Key', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'desc'     => __( 'If you want to use reCAPTCHA V2, insert here Secret Key that you will receive after registration at reCAPTCHA website.', 'customer-reviews-woocommerce' ),
					'default'  => '',
					'id'       => 'ivole_captcha_secret_key',
					'desc_tip' => true
				),
				37 => array(
					'title'    => __( 'Terms and Privacy Checkbox', 'customer-reviews-woocommerce' ),
					'type'     => 'checkbox',
					'desc'     => self::get_default_form_onsite_checkbox_text(),
					'default'  => 'no',
					'id'       => 'ivole_onsite_form_checkbox',
					'is_option' => false
				),
				38 => array(
					'title'    => __( 'Terms and Privacy Checkbox Label', 'customer-reviews-woocommerce' ),
					'type'     => 'cr_text_w_links',
					'desc'     => __( 'Tailor the text to be shown alongside the Terms and Privacy checkbox. Incorporate links directing users to the Terms and Conditions and Privacy Policy pages on your website.', 'customer-reviews-woocommerce' ),
					'default'  => 'I have read and agree to the Terms and Conditions and Privacy Policy.',
					'id'       => 'ivole_onsite_form_checkbox_text',
					'is_option' => false,
					'desc_tip' => true
				),
				40 => array(
					'type' => 'sectionend',
					'id'   => 'cr_options_onsite_forms'
				),
				45 => array(
					'title' => __( 'Aggregated Review Form', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => $aggreg_desc,
					'id'    => 'cr_options_aggregated_forms'
				),
				50 => array(
					'title'    => __( 'Form Header', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'desc'     => __( 'Header of the review form that will be sent to customers.', 'customer-reviews-woocommerce' ),
					'default'  => 'How did we do?',
					'id'       => 'ivole_form_header',
					'class'    => 'cr-admin-settings-wide-text',
					'desc_tip' => true,
					'autoload' => false
				),
				55 => array(
					'title'    => __( 'Form Body', 'customer-reviews-woocommerce' ),
					'type'     => 'textarea',
					'desc'     => __( 'Body of the review form that will be sent to customers.', 'customer-reviews-woocommerce' ),
					'default'  => 'Please review your experience with products and services that you purchased at {site_title}.',
					'id'       => 'ivole_form_body',
					'css'      => 'height:5em;',
					'class'    => 'cr-admin-settings-wide-text',
					'desc_tip' => true,
					'autoload' => false
				),
				60 => array(
					'title'    => __( 'Shop Rating', 'customer-reviews-woocommerce' ),
					'type'     => 'checkbox',
					'id'       => 'ivole_form_shop_rating',
					'default'  => 'no',
					'desc'     => __( 'Enable this option if you would like to include a separate question for a general shop review in addition to questions for product reviews.', 'customer-reviews-woocommerce' ),
					'autoload' => false
				),
				65 => array(
					'title'    => __( 'Comment Required', 'customer-reviews-woocommerce' ),
					'type'     => 'checkbox',
					'id'       => 'ivole_form_comment_required',
					'default'  => 'no',
					'desc'     => __( 'Enable this option if you would like to make it mandatory for your customers to write something in their review. This option applies only to aggregated review forms.', 'customer-reviews-woocommerce' ),
					'autoload' => false
				),
				70 => array(
					'title'    => __( 'Attach Media', 'customer-reviews-woocommerce' ),
					'type'     => 'checkbox',
					'id'       => 'ivole_form_attach_media',
					'default'  => 'no',
					'desc'     => $media_desc,
					'autoload' => false
				),
				90 => array(
					'title'    => __( 'Form Color 1', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'id'       => 'ivole_form_color_bg',
					'default'  => '#2C5E66',
					'desc'     => __( 'Background color for heading of the form and product names.', 'customer-reviews-woocommerce' ),
					'desc_tip' => true,
					'autoload' => false
				),
				95 => array(
					'title'    => __( 'Form Color 2', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'id'       => 'ivole_form_color_text',
					'default'  => '#FFFFFF',
					'desc'     => __( 'Text color for product names.', 'customer-reviews-woocommerce' ),
					'desc_tip' => true,
					'autoload' => false
				),
				100 => array(
					'title'    => __( 'Form Color 3', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'id'       => 'ivole_form_color_el',
					'default'  => '#1AB394',
					'desc'     => __( 'Color of control elements (buttons, rating bars).', 'customer-reviews-woocommerce' ),
					'desc_tip' => true,
					'autoload' => false
				),
				105 => array(
					'type' => 'sectionend',
					'id'   => 'cr_options_aggregated_forms'
				)
			);

			// some features of review forms are not available for local forms
			if( 'yes' === $verified_reviews ) {
				$this->settings[75] = array(
					'title'   => __( 'Rating Bar', 'customer-reviews-woocommerce' ),
					'type'    => 'ratingbar',
					'id'      => 'ivole_form_rating_bar',
					'default' => 'smiley',
					'desc_tip'    => __( 'Visual style of rating bars on review forms.', 'customer-reviews-woocommerce' ),
					'options' => array(
						'smiley'  => __( 'Smiley and frowny faces', 'customer-reviews-woocommerce' ),
						'star'    => __( 'Stars', 'customer-reviews-woocommerce' ),
					),
					'css'     => 'display:none;',
					'autoload' => false
				);
				$this->settings[80] = array(
					'title'   => __( 'Geolocation', 'customer-reviews-woocommerce' ),
					'type'    => 'geolocation',
					'id'      => 'ivole_form_geolocation',
					'default' => 'no',
					'desc'    => __( 'Enable geolocation on aggregated review forms. Customers will have an option to indicate where they are from. For example, "England, United Kingdom".', 'customer-reviews-woocommerce' ),
					'desc_tip'    => __( 'Automatic geolocation on review forms.', 'customer-reviews-woocommerce' ),
					'css'     => 'display:none;',
					'autoload' => false
				);
			}

			$form_settings = self::get_default_form_settings();
			if ( $form_settings ) {
				$this->settings[37]['value'] = self::get_onsite_form_checkbox( $form_settings );
				$onsite_form_checkbox_text = self::get_onsite_form_checkbox_text( $form_settings );
				if ( false !== $onsite_form_checkbox_text ) {
					$this->settings[38]['value'] = $onsite_form_checkbox_text;
				}
			}

			$this->settings = apply_filters( 'cr_settings_forms', $this->settings );
			ksort( $this->settings );
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

		public function display_customer_attributes( $field ) {
			$form_settings = self::get_default_form_settings();
			$cus_atts = array();
			if ( $form_settings ) {
				if (
					is_array( $form_settings ) &&
					isset( $form_settings['cus_atts'] ) &&
					is_array( $form_settings['cus_atts'] )
				) {
					$cus_atts = $form_settings['cus_atts'];
				}
			}
			$max_atts = self::get_max_cus_atts();
			if ( $max_atts <= count( $cus_atts ) ) {
				$td_class = ' cr-cus-atts-limit';
			} else {
				$td_class = '';
			}
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $field['desc'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $field['type'] ) . $td_class; ?>">
					<div class="cr-cus-atts-btn">
						<button type="button" class="page-title-action cr-cus-atts-add-attr">
							<?php _e( 'Add Question', 'customer-reviews-woocommerce' ); ?>
						</button>
						<span>
							<?php esc_html_e( 'The free version of the plugin supports up to 2 questions', 'customer-reviews-woocommerce' ); ?>
						</span>
					</div>
					<input type="hidden" name="ivole_customer_attributes" id="ivole_customer_attributes" value="<?php echo esc_attr( json_encode( $cus_atts ) ); ?>" />
					<table class="widefat cr-cus-atts-table" cellspacing="0">
						<thead>
							<tr>
								<?php
								$columns = array(
									'attribute' => array(
										'title' => __( 'Question', 'customer-reviews-woocommerce' ),
										'help' => self::$help_attribute
									),
									'label' => array(
										'title' => __( 'Label', 'customer-reviews-woocommerce' ),
										'help' => self::$help_label
									),
									'type' => array(
										'title' => __( 'Type', 'customer-reviews-woocommerce' ),
										'help' => self::$help_type
									),
									'required' => array(
										'title' => __( 'Required', 'customer-reviews-woocommerce' ),
										'help' => self::$help_required
									),
									'actions' => array(
										'title' => '',
										'help' => ''
									)
								);
								foreach( $columns as $key => $column ) {
									echo '<th class="cr-cus-atts-table-' . esc_attr( $key ) . '">';
									echo	esc_html( $column['title'] );
									if( $column['help'] ) {
										echo '<span class="woocommerce-help-tip" data-tip="' . esc_attr( $column['help'] ) . '"></span>';
									}
									echo '</th>';
								}
								?>
							</tr>
						</thead>
						<tbody>
							<?php
							if( 0 < count( $cus_atts ) ) {
								$counter = 1;
								foreach( $cus_atts as $attribute ) {
									if ( $counter > $max_atts ) {
										break;
									}

									echo '<tr class="cr-cus-atts-tr">';

									foreach( $columns as $key => $column ) {
										if ( $attribute['required'] ) {
											$req = __( 'Yes', 'customer-reviews-woocommerce' );
										} else {
											$req = __( 'No', 'customer-reviews-woocommerce' );
										}
										switch( $key ) {
											case 'attribute':
												echo '<td>' . $attribute['attribute'] . '</td>';
												break;
											case 'label':
												echo '<td>' . $attribute['label'] . '</td>';
												break;
											case 'type':
												echo '<td data-attype="' . $attribute['type'] . '">' . self::$att_types[$attribute['type']] . '</td>';
												break;
											case 'required':
												echo '<td data-required="' . boolval( $attribute['required'] ) . '">' . $req . '</td>';
												break;
											case 'actions':
												echo '<td class="cr-cus-atts-td-menu">' . self::$button_manage . '</td>';
												break;
											default:
												break;
										}
									}

									echo '</tr>';

									$counter++;
								}
							} else {
								// no attributes yet
								echo self::$no_atts;
							}
							?>
						</tbody>
					</table>
					<?php
						$this->display_modal_template();
						$this->display_delete_conf_template();
					?>
				</td>
			</tr>
			<?php
		}

		public function display_modal_template() {
			?>
			<div class="cr-cus-atts-modal-cont">
				<div class="cr-cus-atts-modal">
					<div class="cr-cus-atts-modal-internal">
						<div class="cr-cus-atts-modal-topbar">
							<h3 class="cr-cus-atts-modal-title"><?php _e( 'Add a Question', 'customer-reviews-woocommerce' ); ?></h3>
							<button type="button" class="cr-cus-atts-modal-close-top">
								<span>×</span>
							</button>
						</div>
						<div class="cr-cus-atts-modal-section">
							<div class="cr-cus-atts-modal-section-row-ctn">
								<div class="cr-cus-atts-modal-section-row">
									<label for="cr_cus_att_input">
										<?php _e( 'Question', 'customer-reviews-woocommerce' ); ?>
										<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::$help_attribute ); ?>"></span>
									</label>
									<input id="cr_cus_att_input" type="text" placeholder="<?php _e( 'E.g., What is your skin type?', 'customer-reviews-woocommerce' ); ?>">
								</div>
								<div class="cr-cus-atts-modal-section-err">
									<?php _e( '* Question cannot be blank', 'customer-reviews-woocommerce' ); ?>
								</div>
							</div>
							<div class="cr-cus-atts-modal-section-row">
								<label for="cr_cus_att_label">
									<?php _e( 'Label', 'customer-reviews-woocommerce' ); ?>
									<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::$help_label ); ?>"></span>
								</label>
								<input id="cr_cus_att_label" type="text" placeholder="<?php _e( 'E.g., Skin type', 'customer-reviews-woocommerce' ); ?>">
							</div>
							<div class="cr-cus-atts-modal-section-row">
								<label for="cr_cus_att_type">
									<?php _e( 'Type', 'customer-reviews-woocommerce' ); ?>
									<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::$help_type ); ?>"></span>
								</label>
								<select id="cr_cus_att_type">
									<?php
									foreach( self::$att_types as $key => $value ) {
										echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
									}
									?>
								</select>
							</div>
							<div class="cr-cus-atts-modal-section-row">
								<label for="cr_cus_att_required">
									<?php _e( 'Required', 'customer-reviews-woocommerce' ); ?>
									<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::$help_required ); ?>"></span>
								</label>
								<input type="checkbox" id="cr_cus_att_required">
							</div>
						</div>
						<div class="cr-cus-atts-modal-bottombar">
							<button type="button" class="cr-cus-atts-modal-cancel"><?php echo esc_html( __( 'Cancel', 'customer-reviews-woocommerce' ) ); ?></button>
							<button type="button" class="cr-cus-atts-modal-save"><?php echo esc_html( __( 'Confirm', 'customer-reviews-woocommerce' ) ); ?></button>
						</div>
						<input type="hidden" class="cr-cus-atts-prev-val">
					</div>
				</div>
			</div>
			<?php
		}

		public function display_delete_conf_template() {
			?>
			<div class="cr-cus-atts-del-modal-cont">
				<div class="cr-cus-atts-del-modal">
					<div class="cr-cus-atts-del-modal-internal">
						<div class="cr-cus-atts-modal-topbar">
							<h3 class="cr-cus-atts-modal-title"></h3>
							<button type="button" class="cr-cus-atts-modal-close-top">
								<span>×</span>
							</button>
						</div>
						<div class="cr-cus-atts-modal-section">
							<div class="cr-cus-atts-modal-section-row">
								<?php echo esc_html( __( 'Would you like to delete this question?', 'customer-reviews-woocommerce' ) ); ?>
							</div>
						</div>
						<div class="cr-cus-atts-modal-bottombar">
							<button type="button" class="cr-cus-atts-modal-cancel"><?php echo esc_html( __( 'Cancel', 'customer-reviews-woocommerce' ) ); ?></button>
							<button type="button" class="cr-cus-atts-modal-save"><?php echo esc_html( __( 'Confirm', 'customer-reviews-woocommerce' ) ); ?></button>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public static function get_default_form_settings() {
			$forms_settings = get_option( 'ivole_review_forms' );
			if (
				$forms_settings &&
				is_array( $forms_settings ) &&
				0 < count( $forms_settings )
			) {
				return $forms_settings[0];
			}
			return false;
		}

		public static function get_max_cus_atts() {
			return apply_filters( 'cr_onsite_questions', 2 );
		}

		public static function get_default_review_permissions() {
			$form_settings = self::get_default_form_settings();
			$permissions = '';
			if ( $form_settings ) {
				if (
					is_array( $form_settings ) &&
					isset( $form_settings['rev_perm'] )
				) {
					$permissions = $form_settings['rev_perm'];
				}
			}
			if ( ! $permissions ) {
				$ivole_ajax_reviews_form = get_option( 'ivole_ajax_reviews_form' );
				$permissions = $ivole_ajax_reviews_form === 'yes' ? 'registered' : 'nobody';
			}
			return $permissions;
		}

		public function display_review_permissions( $value ) {
			$option_value = self::get_default_review_permissions();
			$tooltip_html = CR_Admin::ivole_wc_help_tip( $value['desc'] );
			?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
					</th>
					<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
						<select
							name="<?php echo esc_attr( $value['field_name'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
							id="<?php echo esc_attr( $value['id'] ); ?>"
							style="<?php echo esc_attr( $value['css'] ); ?>"
							class="<?php echo esc_attr( $value['class'] ); ?>"
							<?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
							>
							<?php
							foreach ( $value['options'] as $key => $val ) {
								?>
								<option value="<?php echo esc_attr( $key ); ?>"
									<?php

									if ( is_array( $option_value ) ) {
										selected( in_array( (string) $key, $option_value, true ), true );
									} else {
										selected( $option_value, (string) $key );
									}

									?>
								><?php echo esc_html( $val ); ?></option>
								<?php
							}
							?>
						</select>
					</td>
				</tr>
			<?php
		}

		public function display_text_w_links( $value ) {
			$tooltip_html = CR_Admin::ivole_wc_help_tip( $value['desc'] );
			?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
					</th>
					<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?> cr-text-w-links">
						<?php
							$quicktags_settings = array( 'buttons' => 'strong,link' );
							wp_editor(
								$value['value'],
								$value['id'],
								array(
									'media_buttons' => false,
									'tinymce'       => false,
									'quicktags'     => $quicktags_settings,
									'textarea_rows' => 2
								)
							);
						?>
					</td>
				</tr>
			<?php
		}

		public static function get_onsite_form_checkbox( $form_settings ) {
			$checkbox = 'no';
			if ( $form_settings ) {
				if (
					is_array( $form_settings ) &&
					isset( $form_settings['chbx'] )
				) {
					$checkbox = ( $form_settings['chbx'] == 1 ) ? 'yes' : 'no';
				}
			}
			return $checkbox;
		}

		public static function get_default_form_onsite_checkbox_text() {
			return __( 'Add a checkbox for people to accept your Terms and Conditions, Privacy Policy, and any other legal agreements required in your jurisdiction before submitting a review via an on-site review form.', 'customer-reviews-woocommerce' );
		}

		public static function get_onsite_form_checkbox_text( $form_settings ) {
			$checkbox_text = '';
			if ( $form_settings ) {
				if (
					is_array( $form_settings ) &&
					isset( $form_settings['chbx_text'] )
				) {
					$checkbox_text = $form_settings['chbx_text'];
				} else {
					$checkbox_text = false;
				}
			}
			return $checkbox_text;
		}

	}

endif;
