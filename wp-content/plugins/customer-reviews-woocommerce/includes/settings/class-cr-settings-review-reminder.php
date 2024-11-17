<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Review_Reminder_Settings' ) ):

	class CR_Review_Reminder_Settings {

		protected $settings_menu;
		protected $tab;
		protected $current_section;
		protected $settings;
		protected $default_delay_setting;

		public function __construct( $settings_menu ) {
			$this->settings_menu = $settings_menu;

			$this->tab = 'review_reminder';
			$this->current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) );
			$this->default_delay_setting = apply_filters(
				'cr_default_delays',
				array(
					array(
						'delay' => 5,
						'channel' => 'email'
					)
				)
			);

			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_settings_' . $this->tab, array( $this, 'save' ) );

			add_action( 'woocommerce_admin_field_email_from', array( $this, 'show_email_from' ) );
			add_action( 'woocommerce_admin_field_email_from_name', array( $this, 'show_email_from_name' ) );
			add_action( 'woocommerce_admin_field_footertext', array( $this, 'show_footertext' ) );
			add_action( 'woocommerce_admin_field_ratingbar', array( $this, 'show_ratingbar' ) );
			add_action( 'woocommerce_admin_field_geolocation', array( $this, 'show_geolocation' ) );
			add_action( 'woocommerce_admin_field_twocolsradio', array( $this, 'show_twocolsradio' ) );
			add_action( 'woocommerce_admin_field_cr_sending_delay', array( $this, 'display_sending_delay' ) );

			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_from', array( $this, 'save_email_from' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_footer', array( $this, 'save_footertext' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_delay', array( $this, 'save_sending_delay' ), 10, 3 );

			add_action( 'wp_ajax_ivole_check_license_email_ajax', array( $this, 'check_license_email_ajax' ) );
			add_action( 'wp_ajax_cr_verify_email_ajax', array( $this, 'verify_email_ajax' ) );
			add_action( 'wp_ajax_cr_verify_dkim_ajax', array( $this, 'verify_dkim_ajax' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Review Reminder', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			if( $this->current_section ) {
				$section = apply_filters( 'cr_settings_review_reminder_sections', false, $this->current_section );
				if( $section ) {
					echo $section;
					return;
				}
			}
			$this->init_settings();
			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			// skip saving when in a subsection
			if( $this->current_section ) {
				$section = apply_filters( 'cr_settings_review_reminder_sections_save', false, $this->current_section );
				if( $section ) {
					return;
				}
			}

			$this->init_settings();

			// make sure that we do not save "Checking license..." in the settings
			if( ! empty( $_POST ) && isset( $_POST['ivole_email_from'] ) ) {
				if ( __( 'Checking license...', 'customer-reviews-woocommerce' ) === $_POST['ivole_email_from'] ) {
					$_POST['ivole_email_from'] = get_option( 'ivole_email_from', '' );
				}
			}
			if( ! empty( $_POST ) && isset( $_POST['ivole_email_from_name'] ) ) {
				if ( __( 'Checking license...', 'customer-reviews-woocommerce' ) === $_POST['ivole_email_from_name'] ) {
					$_POST['ivole_email_from_name'] = get_option( 'ivole_email_from_name', Ivole_Email::get_blogname() );
				}
			}
			if( ! empty( $_POST ) && isset( $_POST['ivole_email_footer'] ) ) {
				if ( __( 'Checking license...', 'customer-reviews-woocommerce' ) === $_POST['ivole_email_footer'] ) {
					$_POST['ivole_email_footer'] = get_option( 'ivole_email_footer', '' );
				}
			}

			if( ! empty( $_POST ) && isset( $_POST['ivole_shop_name'] ) ) {
				if ( !$_POST['ivole_shop_name'] ) {
					$_POST['ivole_shop_name'] = Ivole_Email::get_blogname();
				}
			}

			//check that a license key is entered when CR scheduler is enabled
			if( ! empty( $_POST ) && isset( $_POST['ivole_scheduler_type'] ) ) {
				if( 'cr' === $_POST['ivole_scheduler_type'] ) {
					$licenseKey = trim( get_option( 'ivole_license_key', '' ) );
					if( 0 === strlen( $licenseKey ) ) {
						$_POST['ivole_scheduler_type'] = 'wp';
						add_action( 'admin_notices', array( $this, 'admin_notice_scheduler' ) );
					}
				}
			}

			//check that the 'shop' page is configured in WooCommerce
			if( ! empty( $_POST ) && isset( $_POST['ivole_form_shop_rating'] ) ) {
				if( 0 >= wc_get_page_id( 'shop' ) ){
					WC_Admin_Settings::add_error( __( 'It was not possible to enable \'Shop Rating\' option because no \'Shop page\' is set in WooCommerce settings (WooCommerce > Settings) on \'Products\' tab. Please configure a \'Shop page\' in WooCommerce settings first.', 'customer-reviews-woocommerce' ) );
					$_POST['ivole_form_shop_rating'] = 'no';
				}
			}

			// if Verified Reviews option was changed, check if Mailer and Scheduler options requires an update
			if( ! empty( $_POST ) && isset( $_POST['ivole_verified_reviews'] ) ) {
				$licenseKey = trim( get_option( 'ivole_license_key', '' ) );
				// if there is no license key entered, the plugin should work only with the 'no verification' setting
				if ( ! $licenseKey ) {
					if ( 'yes' === $_POST['ivole_verified_reviews'] ) {
						$_POST['ivole_verified_reviews_error'] = true;
					}
					$_POST['ivole_verified_reviews'] = 'no';
				}
				if( 'yes' === $_POST['ivole_verified_reviews'] ) {
					update_option( 'ivole_mailer_review_reminder', 'cr', false );
				} else {
					update_option( 'ivole_mailer_review_reminder', 'wp', false );
					$_POST['ivole_scheduler_type'] = 'wp';
				}
			}

			WC_Admin_Settings::save_fields( $this->settings );
		}

		protected function init_settings() {
			$language_desc = __( 'Choose language that will be used for various elements of emails and review forms.', 'customer-reviews-woocommerce' );
			$verified_reviews = get_option( 'ivole_verified_reviews', 'no' );

			if( 'yes' === $verified_reviews ) {
				$available_languages = array(
					'AR'  => __( 'Arabic', 'customer-reviews-woocommerce' ),
					'BG'  => __( 'Bulgarian', 'customer-reviews-woocommerce' ),
					'ZHT'  => __( 'Chinese (Traditional)', 'customer-reviews-woocommerce' ),
					'ZHS'  => __( 'Chinese (Simplified)', 'customer-reviews-woocommerce' ),
					'HR'  => __( 'Croatian', 'customer-reviews-woocommerce' ),
					'CS'  => __( 'Czech', 'customer-reviews-woocommerce' ),
					'DA'  => __( 'Danish', 'customer-reviews-woocommerce' ),
					'NL'  => __( 'Dutch', 'customer-reviews-woocommerce' ),
					'EN'  => __( 'English', 'customer-reviews-woocommerce' ),
					'ET'  => __( 'Estonian', 'customer-reviews-woocommerce' ),
					'FA'  => __( 'Persian', 'customer-reviews-woocommerce' ),
					'FI'  => __( 'Finnish', 'customer-reviews-woocommerce' ),
					'FR'  => __( 'French', 'customer-reviews-woocommerce' ),
					'KA'  => __( 'Georgian', 'customer-reviews-woocommerce' ),
					'DE'  => __( 'German', 'customer-reviews-woocommerce' ),
					'DEF'  => __( 'German (Formal)', 'customer-reviews-woocommerce' ),
					'EL'  => __( 'Greek', 'customer-reviews-woocommerce' ),
					'HE'  => __( 'Hebrew', 'customer-reviews-woocommerce' ),
					'HU'  => __( 'Hungarian', 'customer-reviews-woocommerce' ),
					'IS'  => __( 'Icelandic', 'customer-reviews-woocommerce' ),
					'ID'  => __( 'Indonesian', 'customer-reviews-woocommerce' ),
					'IT'  => __( 'Italian', 'customer-reviews-woocommerce' ),
					'JA'  => __( 'Japanese', 'customer-reviews-woocommerce' ),
					'KO'  => __( 'Korean', 'customer-reviews-woocommerce' ),
					'LV'  => __( 'Latvian', 'customer-reviews-woocommerce' ),
					'LT'  => __( 'Lithuanian', 'customer-reviews-woocommerce' ),
					'MK'  => __( 'Macedonian', 'customer-reviews-woocommerce' ),
					'NO'  => __( 'Norwegian', 'customer-reviews-woocommerce' ),
					'PL'  => __( 'Polish', 'customer-reviews-woocommerce' ),
					'PT'  => __( 'Portuguese', 'customer-reviews-woocommerce' ),
					'BR'  => __( 'Portuguese (Brazil)', 'customer-reviews-woocommerce' ),
					'RO'  => __( 'Romanian', 'customer-reviews-woocommerce' ),
					'RU'  => __( 'Russian', 'customer-reviews-woocommerce' ),
					'SR'  => __( 'Serbian', 'customer-reviews-woocommerce' ),
					'SK'  => __( 'Slovak', 'customer-reviews-woocommerce' ),
					'SL'  => __( 'Slovenian', 'customer-reviews-woocommerce' ),
					'ES'  => __( 'Spanish', 'customer-reviews-woocommerce' ),
					'SV'  => __( 'Swedish', 'customer-reviews-woocommerce' ),
					'TH'  => __( 'Thai', 'customer-reviews-woocommerce' ),
					'TR'  => __( 'Turkish', 'customer-reviews-woocommerce' ),
					'UK'  => __( 'Ukrainian', 'customer-reviews-woocommerce' ),
					'VI'  => __( 'Vietnamese', 'customer-reviews-woocommerce' )
				);
				// qTranslate integration
				if ( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
					$language_desc .= ' ' . __( 'It looks like you have qTranslate-X plugin activated. You might want to choose "qTranslate-X Automatic" option to enable automatic selection of language.', 'customer-reviews-woocommerce' );
					$available_languages = array( 'QQ' => __( 'qTranslate-X Automatic', 'customer-reviews-woocommerce' ) ) + $available_languages;
				}

				// WPML integration
				if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
					$language_desc .= ' ' . __( 'It looks like you have WPML or Polylang plugins activated. You might want to choose "WPML/Polylang Automatic" option to enable automatic selection of language.', 'customer-reviews-woocommerce' );
					$available_languages = array( 'WPML' => __( 'WPML/Polylang Automatic', 'customer-reviews-woocommerce' ) ) + $available_languages;
				}
			} else {
				$available_languages = array();
			}

			$order_statuses = wc_get_order_statuses();
			$paid_statuses = wc_get_is_paid_statuses();
			$default_status = 'wc-completed';
			foreach ($order_statuses as $status => $description) {
				$status2 = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
				if( !in_array( $status2, $paid_statuses, true ) ) {
					unset( $order_statuses[ $status ] );
				}
				if( 'completed' === $status2 ) {
					$default_status = $status;
				}
			}

			if( 'yes' === $verified_reviews ) {
				$scheduler_options = array(
					'wp'  => __( 'WordPress Cron', 'customer-reviews-woocommerce' ),
					'cr' => __( 'CR Cron', 'customer-reviews-woocommerce' )
				);
			} else {
				$scheduler_options = array(
					'wp'  => __( 'WordPress Cron', 'customer-reviews-woocommerce' )
				);
			}

			$this->settings = array(
				1 => array(
					'title' => __( 'Reminders for Customer Reviews', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Configure the plugin to send automatic or manual follow-up emails (reminders) that collect store and product reviews.', 'customer-reviews-woocommerce' ),
					'id'    => 'ivole_options'
				),
				5 => array(
					'title'   => __( 'Enable Automatic Reminders', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable automatic follow-up emails with an invitation to submit a review. Before enabling this feature, you MUST update your terms and conditions and make sure that customers consent to receive invitations to review their orders. Depending on the location of your customers, it might also be necessary to receive an explicit consent to send review reminders. In this case, it is mandatory to enable the ‘Customer Consent’ option below.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_enable',
					'default' => 'no',
					'autoload' => false,
					'type'    => 'checkbox'
				),
				10 => array(
					'title' => __( 'Verified Reviews', 'customer-reviews-woocommerce' ),
					'type' => 'twocolsradio',
					'id' => 'ivole_verified_reviews',
					'default' => 'no',
					'autoload' => false
				),
				15 => array(
					'title'    => __( 'Sending Delay', 'customer-reviews-woocommerce' ),
					'type'     => 'cr_sending_delay',
					'desc'     => __( 'Sending delay settings for automatic review reminders.', 'customer-reviews-woocommerce' ),
					'default'  => 5,
					'id'       => 'ivole_delay',
					'desc_tip' => true,
					'autoload' => false
				),
				20 => array(
					'title' => __( 'Order Status', 'customer-reviews-woocommerce' ),
					'type' => 'select',
					'desc' => __( 'Review reminders will be sent N days after this order status. It is recommended to use \'Completed\' status.', 'customer-reviews-woocommerce' ),
					'default'  => $default_status,
					'id' => 'ivole_order_status',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'options'  => $order_statuses,
					'autoload' => false
				),
				25 => array(
					'title'    => __( 'Enable for', 'customer-reviews-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( 'Define if reminders will be send for all or only specific categories of products.', 'customer-reviews-woocommerce' ),
					'default'  => 'all',
					'id'       => 'ivole_enable_for',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'options'  => array(
						'all'        => __( 'All Categories', 'customer-reviews-woocommerce' ),
						'categories' => __( 'Specific Categories', 'customer-reviews-woocommerce' )
					),
					'autoload' => false
				),
				30 => array(
					'title'    => __( 'Categories', 'customer-reviews-woocommerce' ),
					'type'     => 'cselect',
					'desc'     => __( 'If reminders are enabled only for specific categories of products, this field enables you to choose these categories.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_enabled_categories',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'autoload' => false
				),
				35 => array(
					'title' => __( 'Enable for Roles', 'customer-reviews-woocommerce' ),
					'type' => 'select',
					'desc' => __( 'Define if reminders will be send for all or only specific roles of users.', 'customer-reviews-woocommerce' ),
					'default'  => 'all',
					'id' => 'ivole_enable_for_role',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'options'  => array(
						'all'  => __( 'All Roles', 'customer-reviews-woocommerce' ),
						'roles' => __( 'Specific Roles', 'customer-reviews-woocommerce' )
					),
					'autoload' => false
				),
				40 => array(
					'title' => __( 'Roles', 'customer-reviews-woocommerce' ),
					'type' => 'cselect',
					'desc' => __( 'If reminders are enabled only for specific user roles, this field enables you to choose these roles.', 'customer-reviews-woocommerce' ),
					'id' => 'ivole_enabled_roles',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'autoload' => false
				),
				45 => array(
					'title'   => __( 'Enable for Guests', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable sending of review reminders to customers who place orders without an account (guest checkout). It is recommended to enable this checkbox, if you allow customers to place orders without creating an account on your site.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_enable_for_guests',
					'default' => 'yes',
					'type'    => 'checkbox',
					'autoload' => false
				),
				50 => array(
					'title' => __( 'Reminders Scheduler', 'customer-reviews-woocommerce' ),
					'type' => 'select',
					'desc' => __( 'Define which scheduler the plugin will use to schedule automatic review reminders. The default option is to use WordPress Cron (WP-Cron) for scheduling automatic reminders. If your hosting limits WordPress Cron functionality and automatic reminders are not sent as expected, try CR Cron. CR Cron is an external service that requires a license key (free or pro).', 'customer-reviews-woocommerce' ),
					'default'  => 'wp',
					'id' => 'ivole_scheduler_type',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'options'  => $scheduler_options,
					'autoload' => false
				),
				55 => array(
					'title'   => __( 'Enable Manual Reminders', 'customer-reviews-woocommerce' ),
					'desc'    => sprintf( __( 'Enable manual sending of follow-up emails with a reminder to submit a review. Manual reminders can be sent for completed orders from %1$sOrders%2$s page after enabling this option.', 'customer-reviews-woocommerce' ), '<a href="' . admin_url( 'edit.php?post_type=shop_order' ) . '">', '</a>' ),
					'id'      => 'ivole_enable_manual',
					'default' => 'yes',
					'type'    => 'checkbox',
					'autoload' => false
				),
				60 => array(
					'title'   => __( 'Limit Number of Reminders', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable this checkbox to make sure that no more than one review reminder is sent for each order.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_limit_reminders',
					'default' => 'yes',
					'type'    => 'checkbox',
					'autoload' => false
				),
				70 => array(
					'title'   => __( 'Customer Consent Text', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Text of the message shown to customers next to the consent checkbox on the checkout page.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_customer_consent_text',
					'type'     => 'textarea',
					'default' => CR_Checkout::$def_consent_text,
					'css'      => 'height:5em;',
					'class'    => 'cr-admin-settings-wide-text',
					'desc_tip' => true
				),
				75 => array(
					'title'   => __( 'Registered Customers', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'By default, review reminders are sent to billing emails provided by customers during checkout. If you enable this option, the plugin will check if customers have accounts on your website, and review reminders will be sent to emails associated with their accounts. It is recommended to keep this option disabled.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_registered_customers',
					'default' => 'no',
					'type'    => 'checkbox',
					'autoload' => false
				),
				80 => array(
					'title'   => __( 'Moderation of Reviews', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable manual moderation of reviews submitted by your verified customers. This setting applies only to reviews submitted in response to reminders sent by this plugin.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_enable_moderation',
					'default' => 'no',
					'type'    => 'checkbox',
					'autoload' => false
				),
				85 => array(
					'title'   => __( 'Exclude Free Products', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable this checkbox to exclude free products from review invitations.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_exclude_free_products',
					'default' => 'no',
					'type'    => 'checkbox',
					'autoload' => false
				),
				90 => array(
					'title'    => __( 'Shop Name', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'desc'     => __( 'Specify your shop name that will be used in emails and review forms generated by this plugin.', 'customer-reviews-woocommerce' ),
					'default'  => Ivole_Email::get_blogname(),
					'id'       => 'ivole_shop_name',
					'css'      => 'min-width:300px;',
					'desc_tip' => true
				),
				95 => array(
					'type' => 'sectionend',
					'id'   => 'ivole_options'
				)
			);

			// an option to track email opening is currently available only for local review forms
			if ( 'yes' !== $verified_reviews ) {
				$this->settings[88] = array(
					'title'   => __( 'Track Opens', 'customer-reviews-woocommerce' ),
					'desc'    => sprintf(
						__( 'Enable this checkbox to discover when customers open emails with review invitations by embedding an invisible pixel into email templates and tracking when it is downloaded. The tracking status will be displayed on the <a href="%s">Reminders</a> page.', 'customer-reviews-woocommerce' ),
						admin_url( 'admin.php?page=cr-reviews-reminders&tab=sent' )
					),
					'id'      => 'ivole_track_reminder_open',
					'default' => 'no',
					'type'    => 'checkbox',
					'autoload' => false
				);
			}

			// some features of review forms are not available for local forms
			if( 0 < count( $available_languages ) ) {
				$this->settings[100] = array(
					'title' => __( 'Language', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => $language_desc,
					'id'    => 'ivole_options_language'
				);
				$this->settings[105] = array(
					'title'    => __( 'Language', 'customer-reviews-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( 'Choose one of the available languages.', 'customer-reviews-woocommerce' ),
					'default'  => 'EN',
					'id'       => 'ivole_language',
					'class'    => 'wc-enhanced-select',
					'desc_tip' => true,
					'options'  => $available_languages,
					'autoload' => false
				);
				$this->settings[110] = array(
					'type' => 'sectionend',
					'id'   => 'ivole_options_language'
				);
			}

			$this->settings[115] = array(
				'title' => __( 'Email Template', 'customer-reviews-woocommerce' ),
				'type'  => 'title',
				'desc' => sprintf( __( 'The email template of review reminders can be configured on the <a href="%s">Emails</a> tab.', 'customer-reviews-woocommerce' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=emails' ) ),
				'id'    => 'ivole_options_email'
			);
			$this->settings[120] = array(
				'type' => 'sectionend',
				'id'   => 'ivole_options_email'
			);
			$auto_consent = true;
			if( 'yes' === $verified_reviews ) {
				$desc = sprintf(
					__( 'Adjust template of the aggregated review forms that will be created and sent to customers by CusRev. Modifications will be applied to the next review form created after saving settings. If you enable <b>advanced</b> form templates in your account on %1$sCusRev website%2$s, they will <b>override</b> the settings below.', 'customer-reviews-woocommerce' ),
					'<a href="https://www.cusrev.com/login.html" target="_blank" rel="noopener noreferrer">', '</a>'
				);
				$auto_consent = self::get_auto_show_consent();
			} else {
				$desc = __( 'Adjust template of the aggregated review forms that will be created and sent to customers. Modifications will be applied to the next review form created after saving settings.', 'customer-reviews-woocommerce' );
				$auto_consent = false;
			}
			if ( ! $auto_consent ) {
				$this->settings[67] = array(
					'title'   => __( 'Customer Consent', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'If this option is enabled, customers will be asked to tick a checkbox on the checkout page to indicate that they would like to receive an invitation to review their order.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_customer_consent',
					'default' => 'yes',
					'type'    => 'checkbox'
				);
			}
			$this->settings[125] = array(
				'title' => __( 'Review Form Template', 'customer-reviews-woocommerce' ),
				'type'  => 'title',
				'desc'  => sprintf( __( 'The review form template for review reminders can be configured on the <a href="%s">Forms</a> tab.', 'customer-reviews-woocommerce' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=forms' ) ),
				'id'    => 'ivole_options_form'
			);
			$this->settings[180] = array(
				'type' => 'sectionend',
				'id'   => 'ivole_options_form'
			);

			$this->settings = apply_filters( 'cr_settings_review_reminder', $this->settings );
			ksort( $this->settings );
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

		public function is_other_tab( $tab ) {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $tab );
		}

		/**
		* Custom field type for from email
		*/
		public function show_email_from( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>">
						<?php
							echo esc_html( $value['title'] );
							echo $tooltip_html;
						?>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text" style="display:none;"
					class="<?php echo esc_attr( trim( 'cr-email-from-input ' . $value['class'] ) ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					/>
					<?php echo $description; ?>
					<p id="ivole_email_from_status"></p>
					<div class="cr-email-verify-status">
						<span class="cr-email-verify-status-ind"></span>
						<span class="cr-email-verify-status-lbl">
							<?php
								echo CR_Admin::ivole_wc_help_tip(
									__(
										'Before you can send emails via CusRev (AWS SES) mailer, it is necessary to verify that you own the email address. If you have not verified an email address yet, click on the Verify button to start the verification process.',
										'customer-reviews-woocommerce'
									)
								);
								_e( 'Email verification', 'customer-reviews-woocommerce' );
							?>
						</span>
						<input
							type="button"
							id="ivole_email_from_verify_button"
							value="Verify"
							class="button-primary cr-email-verify-button"
						/>
					</div>
					<div class="cr-dkim-verify-status">
						<span class="cr-dkim-verify-status-ind"></span>
						<span class="cr-email-verify-status-lbl">
							<?php
								echo CR_Admin::ivole_wc_help_tip(
									__(
											'DKIM stands for DomainKeys Identified Mail. DKIM-signed messages help receiving mail servers validate that a message was not forged or altered in transit. Enabling DKIM reduces the risk that your emails could go to SPAM.',
											'customer-reviews-woocommerce'
										)
								);
								_e( 'DKIM signature', 'customer-reviews-woocommerce' );
							?>
						</span>
						<input
							type="button"
							value="Enable DKIM"
							class="button-primary cr-dkim-enable-button"
							style="display:none;vertical-align:middle;"
						/>
					</div>
					<div class="cr-dns-records-acc">
						<h3>
							<?php
								_e( 'DNS Records', 'customer-reviews-woocommerce' );
								echo CR_Admin::ivole_wc_help_tip(
									__(
											'DNS records are used for DKIM authentication. Publish CNAME records from the table below to your domain’s DNS provider to enable DKIM signature for emails sent by CusRev mailer.',
											'customer-reviews-woocommerce'
										)
								);
							?>
						</h3>
						<div>
							<?php $this->show_dns_table(); ?>
						</div>
					</div>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for from  name
		*/
		public function show_email_from_name( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>">
						<?php
							echo esc_html( $value['title'] );
							echo $tooltip_html;
						?>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text" style="display: none;" class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"/>
					<?php echo $description; ?>
					<p id="ivole_email_from_name_status"></p>
				</td>
			</tr>
			<?php
		}

		/*
		* Custom field type for email footer text
		*/
		public function show_footertext( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$default = $tmp['default'];
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?>
						<?php echo $tooltip_html; ?>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<?php echo $description; ?>
					<textarea name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>"
						style="display: none;" class="<?php echo esc_attr( $value['class'] ); ?>" rows="3">
							<?php esc_html_e( get_option( $value['id'], $default ) ); ?>
					</textarea>
					<p id="ivole_email_footer_status"></p>
				</td>
			</tr>
			<?php
		}

		/*
		* Custom field type for rating bar style
		*/
		public function show_ratingbar( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$option_value = get_option( $value['id'], $value['default'] );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-radio">
					<fieldset style="<?php echo esc_attr( $value['css'] ); ?>" id="ivole_form_rating_bar_fs">
						<?php echo $description; ?>
						<ul>
							<?php
							foreach ( $value['options'] as $key => $val ) {
								?>
								<li>
									<label><input
										name="<?php echo esc_attr( $value['id'] ); ?>"
										value="<?php echo esc_attr( $key ); ?>"
										type="radio"
										class="<?php echo esc_attr( $value['class'] ); ?>"
										<?php checked( $key, $option_value ); ?>
										/> <?php echo esc_html( $val ); ?>
									</label>
								</li>
								<?php
							}
							?>
						</ul>
					</fieldset>
					<p id="ivole_form_rating_bar_status"></p>
				</td>
			</tr>
			<?php
		}

		public function show_geolocation( $value ) {
			$tmp = CR_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$option_value = get_option( $value['id'], $value['default'] );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset style="<?php echo esc_attr( $value['css'] ); ?>" id="ivole_form_geolocation_fs">
						<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
						<label for="<?php echo esc_attr( $value['id'] ); ?>">
							<input
							name="<?php echo esc_attr( $value['id'] ); ?>"
							id="<?php echo esc_attr( $value['id'] ); ?>"
							type="checkbox"
							value="1"
							<?php checked( $option_value, 'yes' ); ?>
							/> <?php echo $description; ?>
						</label>
					</fieldset>
					<p id="ivole_form_geolocation_status"></p>
				</td>
			</tr>
			<?php
		}

		public function show_twocolsradio( $value ) {
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
					<?php
						if (
							! empty( $_POST ) &&
							isset( $_POST['ivole_verified_reviews_error'] ) &&
							$_POST['ivole_verified_reviews_error']
						) :
					?>
							<div class="cr-twocols-error">
								<?php
									esc_html_e( 'The "Independently Verified" option cannot be activated without a license key (either Free or Pro)', 'customer-reviews-woocommerce' );
									echo wc_help_tip( __( 'A license key (either Free or Pro) is required for security purposes to ensure that only you can use CusRev to send review reminders on behalf of your business. No license key is required if you wish to collect reviews locally without third-party verification.', 'customer-reviews-woocommerce' ) );
								?>
							</div>
					<?php endif; ?>
					<div class="cr-twocols-cont">
						<input type="hidden" name="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>">
						<div class="cr-twocols-left cr-twocols-cols<?php if( 'yes' !== $option_value ) echo ' cr-twocols-sel'; ?>">
							<svg width="68" height="63" viewBox="0 0 68 63" fill="none" xmlns="http://www.w3.org/2000/svg">
								<mask id="path-1-inside-1_6_5" fill="white">
									<path d="M32.495 0.905848C33.1094 -0.301949 34.8903 -0.301949 35.5047 0.905848L44.7112 18.9641C44.9565 19.4442 45.4291 19.7767 45.9758 19.8531L66.5608 22.7499C67.9378 22.9438 68.487 24.583 67.492 25.522L52.5944 39.579C52.1997 39.9518 52.0183 40.4906 52.1128 41.017L55.6283 60.8656C55.8646 62.1934 54.425 63.2062 53.1922 62.5785L34.7817 53.2088C34.2924 52.9599 33.7073 52.9599 33.218 53.2088L14.8062 62.5785C13.5747 63.2062 12.135 62.1934 12.3713 60.8656L15.8869 41.017C15.9801 40.4906 15.8 39.9518 15.404 39.579L0.508965 25.522C-0.487444 24.583 0.0618583 22.9438 1.43895 22.7499L22.025 19.8531C22.5706 19.7767 23.0443 19.4442 23.2885 18.9641L32.495 0.905848Z"/>
								</mask>
								<path d="M32.495 0.905848C33.1094 -0.301949 34.8903 -0.301949 35.5047 0.905848L44.7112 18.9641C44.9565 19.4442 45.4291 19.7767 45.9758 19.8531L66.5608 22.7499C67.9378 22.9438 68.487 24.583 67.492 25.522L52.5944 39.579C52.1997 39.9518 52.0183 40.4906 52.1128 41.017L55.6283 60.8656C55.8646 62.1934 54.425 63.2062 53.1922 62.5785L34.7817 53.2088C34.2924 52.9599 33.7073 52.9599 33.218 53.2088L14.8062 62.5785C13.5747 63.2062 12.135 62.1934 12.3713 60.8656L15.8869 41.017C15.9801 40.4906 15.8 39.9518 15.404 39.579L0.508965 25.522C-0.487444 24.583 0.0618583 22.9438 1.43895 22.7499L22.025 19.8531C22.5706 19.7767 23.0443 19.4442 23.2885 18.9641L32.495 0.905848Z" fill="#E1E1E1" stroke="#D1D1D1" stroke-width="2" mask="url(#path-1-inside-1_6_5)"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M24.4735 57.6588L38.1044 6.00509L44.7112 18.9641C44.9565 19.4442 45.4291 19.7767 45.9758 19.8531L66.5608 22.7499C67.9378 22.9438 68.487 24.583 67.492 25.522L52.5944 39.579C52.1997 39.9518 52.0183 40.4906 52.1128 41.017L55.6282 60.8656C55.8645 62.1934 54.425 63.2062 53.1921 62.5785L34.7817 53.2088C34.2924 52.9599 33.7073 52.9599 33.218 53.2088L24.4735 57.6588Z" fill="#D1D1D1"/>
							</svg>
							<div class="cr-twocols-title">
								<?php esc_html_e( 'No verification' ) ?>
							</div>
							<div class="cr-twocols-main">
								<ul>
									<li>
										<?php esc_html_e( 'Collect reviews locally without third-party verification' ); echo wc_help_tip( 'The complete reviews collection solution hosted on your server' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'Review invitations will be sent by the default mailer from your website' ); echo wc_help_tip( 'The plugin will use the standard \'wp_mail\' function for sending emails in WordPress' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'Aggregated review forms will be hosted locally on your server' ); echo wc_help_tip( 'An aggregated review form is a review form that supports collection of reviews for multiple products at the same time.' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'No restrictions on collection of reviews for prohibited product categories' ); echo wc_help_tip( 'Since CusRev does not have to display copies of unverified reviews, there are no restrictions on allowed categories of products' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'I understand that visitors of my website are likely to consider unverified reviews to be biased or fake' ); ?>
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
							<svg width="68" height="63" viewBox="0 0 68 63" fill="none" xmlns="http://www.w3.org/2000/svg">
								<mask id="path-1-inside-1_2_5" fill="white">
									<path d="M32.495 0.905848C33.1094 -0.301949 34.8903 -0.301949 35.5047 0.905848L44.7112 18.9641C44.9565 19.4442 45.4291 19.7767 45.9758 19.8531L66.5608 22.7499C67.9378 22.9438 68.4871 24.583 67.492 25.522L52.5944 39.579C52.1997 39.9518 52.0183 40.4906 52.1128 41.017L55.6283 60.8656C55.8646 62.1934 54.425 63.2062 53.1922 62.5785L34.7817 53.2088C34.2924 52.9599 33.7073 52.9599 33.218 53.2088L14.8062 62.5785C13.5747 63.2062 12.135 62.1934 12.3713 60.8656L15.8869 41.017C15.9801 40.4906 15.8 39.9518 15.404 39.579L0.508965 25.522C-0.487444 24.583 0.0618583 22.9438 1.43895 22.7499L22.025 19.8531C22.5706 19.7767 23.0443 19.4442 23.2885 18.9641L32.495 0.905848Z"/>
								</mask>
								<path d="M32.495 0.905848C33.1094 -0.301949 34.8903 -0.301949 35.5047 0.905848L44.7112 18.9641C44.9565 19.4442 45.4291 19.7767 45.9758 19.8531L66.5608 22.7499C67.9378 22.9438 68.4871 24.583 67.492 25.522L52.5944 39.579C52.1997 39.9518 52.0183 40.4906 52.1128 41.017L55.6283 60.8656C55.8646 62.1934 54.425 63.2062 53.1922 62.5785L34.7817 53.2088C34.2924 52.9599 33.7073 52.9599 33.218 53.2088L14.8062 62.5785C13.5747 63.2062 12.135 62.1934 12.3713 60.8656L15.8869 41.017C15.9801 40.4906 15.8 39.9518 15.404 39.579L0.508965 25.522C-0.487444 24.583 0.0618583 22.9438 1.43895 22.7499L22.025 19.8531C22.5706 19.7767 23.0443 19.4442 23.2885 18.9641L32.495 0.905848Z" fill="#F4DB6B" stroke="#F5CD5B" stroke-width="2" mask="url(#path-1-inside-1_2_5)"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M24.4734 57.6588L38.1043 6.005L44.7111 18.964C44.9564 19.4441 45.429 19.7766 45.9758 19.853L66.5607 22.7499C67.9377 22.9438 68.487 24.5829 67.492 25.5219L52.5944 39.579C52.1996 39.9517 52.0182 40.4905 52.1128 41.0169L55.6282 60.8655C55.8645 62.1933 54.4249 63.2061 53.1921 62.5784L34.7816 53.2087C34.2923 52.9598 33.7072 52.9598 33.2179 53.2087L24.4734 57.6588Z" fill="#F5CD5B"/>
							</svg>
							<div class="cr-twocols-title">
								<?php esc_html_e( 'Independently verified' ) ?>
							</div>
							<div class="cr-twocols-main">
								<ul>
									<li>
										<?php echo 'Use <a href="https://www.cusrev.com/business/" target="_blank" rel="noopener noreferrer">CusRev</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon"> for collection and verification of reviews' . wc_help_tip( 'CusRev (Customer Reviews) is a service for businesses that offers a voluntary scheme for verification of reviews submitted by customers.' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'Review invitations will be sent by CusRev on behalf of your store' ); echo wc_help_tip( 'CusRev uses AWS SES (Simple Email Service) for sending emails to ensure their excellent deliverability' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'Aggregated review forms will be hosted on AWS S3 by CusRev' ); echo wc_help_tip( 'An aggregated review form is a review form that supports collection of reviews for multiple products at the same time.' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'CusRev is unable to collect and verify reviews for certain products' ); echo wc_help_tip( 'Due to regulatory restrictions, CusRev is unable to collect and verify reviews for prohibited categories of products (e.g., CBD or Kratom)' ); ?>
									</li>
									<li>
										<?php echo 'I confirm that I will send review invitations only with consent of customers and agree to CusRev’s <a href="https://www.cusrev.com/terms.html" target="_blank" rel="noopener noreferrer">terms and conditions</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon">'; ?>
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

		public function save_email_from( $value, $option, $raw_value ) {
			if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
				return strtolower( $value );
			}
			return;
		}

		public function save_footertext( $value, $option, $raw_value ) {
			return $raw_value;
		}

		/**
		* Function to check status of the license and verification of email
		*/
		public function check_license_email_ajax() {
			$license = new CR_License();
			$lval = $license->check_license();

			if ( 1 === $lval['code'] ) {
				// the license is active, so check if current from email address is verified
				$verify = new CR_Email_Verify();
				$vval = $verify->is_verified();
				$dkim = $verify->is_dkim_verified();

				wp_send_json(
					array(
						'license' => $lval['code'],
						'email' => $vval['code'],
						'fromEmail' => $vval['fromEmail'],
						'fromName' => $vval['fromName'],
						'emailFooter' => $vval['emailFooter'],
						'dkim' => $dkim
					)
				);
			} else {
				wp_send_json(
					array (
						'license' => $lval['code'],
						'email' => 0,
						'fromEmail' => '',
						'fromName' => '',
						'emailFooter' => '',
						'dkim' => ''
					)
				);
			}
		}

		public function verify_email_ajax() {
			$email = strval( $_POST['email'] );
			$verify = new CR_Email_Verify();
			$vval = $verify->verify_email( $email );
			wp_send_json(
				array(
					'verification' => $vval['res'],
					'email' => $email,
					'message' => $vval['message']
				)
			);
		}

		public function verify_dkim_ajax() {
			$email = strval( $_POST['email'] );
			$verify = new CR_Email_Verify();
			$vval = $verify->verify_dkim( $email );
			wp_send_json(
				array(
					'verification' => $vval['code'],
					'tokens' => $vval['tokens']
				)
			);
		}

		public function admin_notice_scheduler() {
			if ( current_user_can( 'manage_options' ) ) {
				$class = 'notice notice-error';
				$message = __( '<strong>CR Cron could not be enabled because no license key was entered. A license key (free or pro) is required to use CR Cron.</strong>', 'customer-reviews-woocommerce' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
			}
		}

		public static function get_auto_show_consent() {
			$auto_consent = true;
			if ( class_exists( 'WC_Countries' ) ) {
				$countries = new WC_Countries();
				$eu_countries = $countries->get_european_union_countries();
				$shop_country = wc_get_base_location();
				if (
					$shop_country &&
					is_array( $shop_country ) &&
					$shop_country['country']
				) {
					if ( ! in_array( $shop_country['country'], $eu_countries ) ) {
						$auto_consent = false;
					}
				}
			}
			return $auto_consent;
		}

		public function display_sending_delay( $field ) {
			$reminders = array();
			$delay_option = get_option( 'ivole_delay', 5 );
			if ( is_array( $delay_option ) && 0 < count( $delay_option ) ) {
				foreach ( $delay_option as $del_opt ) {
					if (
						isset( $del_opt['delay'] ) &&
						isset( $del_opt['channel'] )
					) {
						// a temporary solution to be removed in the future versions
						if ( isset( $del_opt['enabled'] ) ) {
							$reminders[] = array(
								'enabled' => $del_opt['enabled'],
								'delay' => $del_opt['delay'],
								'channel' => $del_opt['channel']
							);
						} else {
							$reminders[] = array(
								'delay' => $del_opt['delay'],
								'channel' => $del_opt['channel']
							);
						}
						//
					}
					if ( self::get_max_delays() <= count( $reminders ) ) {
						break;
					}
				}
				if ( 0 === count( $reminders ) ) {
					$reminders = $this->default_delay_setting;
				}
			} else {
				$reminders[] = array(
					'delay' => $delay_option,
					'channel' => 'email'
				);
			}
			$reminders = apply_filters( 'cr_sending_delays', $reminders );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $field['desc'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $field['type'] ); ?>">
					<table class="widefat cr-snd-dlay-table" cellspacing="0">
						<thead>
							<tr>
								<?php
								$columns = array(
									'reminder' => array(
										'title' => '',
										'help' => ''
									),
									'enabled' => array(
										'title' => __( 'Enabled', 'customer-reviews-woocommerce' ),
										'help' => __( 'Enable automatic follow-up emails with an invitation to submit a review.', 'customer-reviews-woocommerce' )
									),
									'delay' => array(
										'title' => __( 'Delay (Days)', 'customer-reviews-woocommerce' ),
										'help' => __( 'If automatic review reminders are enabled, review invitations will be sent N days after order status is changed to the value specified in the field below. N is a sending delay (in days) that needs to be defined here.', 'customer-reviews-woocommerce' )
									),
									'channel' => array(
										'title' => __( 'Channel', 'customer-reviews-woocommerce' ),
										'help' => __( 'A channel for sending review invitations to customers. For example, by email.', 'customer-reviews-woocommerce' )
									)
								);
								// a temporary solution before the checkbox is moved to the new UI element
								if ( 2 > count( $reminders ) ) {
									unset( $columns['enabled'] );
								}
								//
								foreach( $columns as $key => $column ) {
									if ( 'reminder' === $key ) {
										$th_class = '<th class="cr-snd-dlay-table-' . esc_attr( $key ) . '">';
									} else{
										$th_class = '<th class="cr-snd-dlay-table-' . esc_attr( $key ) . ' cr-snd-dlay-table-narrow">';
									}
									echo $th_class;
									echo esc_html( $column['title'] );
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
								$count = 0;
								foreach ( $reminders as $reminder ) {
									echo '<tr class="cr-snd-dlay-tr">';
									foreach ( $columns as $key => $column ) {
										switch ( $key ) {
											case 'reminder':
												$reminder_label = apply_filters(
													'cr_sending_delay_label',
													__( 'Review Reminder', 'customer-reviews-woocommerce' ),
													$count
												);
												echo '<td>' . $reminder_label . '</td>';
												break;
											case 'enabled':
												$reminder_enabled = apply_filters(
													'cr_sending_delay_enabled',
													'',
													$count,
													$field,
													$key,
													$reminder
												);
												echo '<td>' . $reminder_enabled . '</td>';
												break;
											case 'delay':
												echo '<td><input type="number" id="';
												echo esc_attr( $field['type'] . '_' . $key . '_' . $count );
												echo '" name="' . esc_attr( $field['type'] . '_' . $key . '_' . $count );
												echo '" min="0" max="999" value="' . intval( $reminder['delay'] ) . '" /></td>';
												break;
											case 'channel':
												echo '<td><select name="' . esc_attr( $field['type'] . '_' . $key . '_' . $count );
												echo '" id="' . esc_attr( $field['type'] . '_' . $key . '_' . $count ) . '">';
												echo self::output_channels( $reminder['channel'] );
												echo '</select></td>';
												break;
											default:
												break;
										}
									}
									echo '</tr>';
									$count++;
								}
							?>
						</tbody>
					</table>
				</td>
			</tr>
			<?php
		}

		public function save_sending_delay( $value, $option, $raw_value ) {
			$delays = array();
			if ( isset( $option['type'] ) && $option['type'] ) {
				$max_delays = self::get_max_delays();
				for ( $i=0; $i < $max_delays; $i++ ) {
					if (
						isset( $_POST[$option['type'] . '_delay_' . $i] ) &&
						isset( $_POST[$option['type'] . '_channel_' . $i] )
					) {
						// a temporary solution, to be removed in the future versions
						if ( 0 < $i ) {
							if ( ! isset( $_POST[$option['type'] . '_enabled_' . $i] ) ) {
								$_POST[$option['type'] . '_enabled_' . $i] = false;
							}
							$delays[] = array(
								'enabled' => boolval( $_POST[$option['type'] . '_enabled_' . $i] ),
								'delay' => intval( $_POST[$option['type'] . '_delay_' . $i] ),
								'channel' => strval( $_POST[$option['type'] . '_channel_' . $i] )
							);
						} else {
							$delays[] = array(
								'delay' => intval( $_POST[$option['type'] . '_delay_' . $i] ),
								'channel' => strval( $_POST[$option['type'] . '_channel_' . $i] )
							);
						}
						//
					}
				}
			}
			if ( 0 < count( $delays ) ) {
				return apply_filters( 'cr_save_sending_delays', $delays );
			} else {
				return $this->default_delay_setting;
			}
		}

		public static function get_max_delays() {
			return apply_filters( 'cr_max_sending_delays', 1 );
		}

		public function show_dns_table() {
			?>
			<div class="cr-dns-table-cont">
				<table class="widefat cr-dns-table" cellspacing="0">
					<thead>
						<tr>
							<th class="cr-dns-col-type">
								<?php _e( 'Type', 'customer-reviews-woocommerce' ); ?>
							</th>
							<th class="cr-dns-col-name">
								<?php _e( 'Name', 'customer-reviews-woocommerce' ); ?>
							</th>
							<th class="cr-dns-col-value">
								<?php _e( 'Value', 'customer-reviews-woocommerce' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr class="cr-dns-template-row">
							<td class="cr-dns-cell-type">
								<div class="cr-dns-cell-cont">
									CNAME
								</div>
							</td>
							<td class="cr-dns-cell-name">
								<div class="cr-dns-cell-cont">
									<span class="dashicons dashicons-clipboard"></span>
									<span class="cr-dns-cell-text"></span>
								</div>
							</td>
							<td class="cr-dns-cell-value">
								<div class="cr-dns-cell-cont">
									<span class="dashicons dashicons-clipboard"></span>
									<span class="cr-dns-cell-text"></span>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
		}

		public static function get_output_channels() {
			return apply_filters(
				'cr_available_channels',
				array(
					array(
						'id' => 'email',
						'desc' => __( 'Email', 'customer-reviews-woocommerce' )
					)
				)
			);
		}

		public static function output_channels( $selected_channel ) {
			$output = '';
			$available_channels = self::get_output_channels();
			foreach( $available_channels as $channel ) {
				if ( $channel['id'] === $selected_channel ) {
					$selected = ' selected';
				} else {
					$selected = '';
				}
				$output .= '<option value="' . esc_attr( $channel['id'] ) . '"' . $selected . '>';
				$output .= esc_html( $channel['desc'] ) . '</option>';
			}
			return $output;
		}

	}

endif;
