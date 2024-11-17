<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Emails_Settings' ) ):

	class CR_Emails_Settings {

		/**
		* @var CR_Settings_Admin_Menu The instance of the settings admin menu
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
		protected $current_section;
		protected $templates;

		public function __construct( $settings_menu ) {
			$this->settings_menu = $settings_menu;
			$this->tab = 'emails';
			$this->current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) );
			$this->templates = array(
				'review_reminder' => 'review_reminder',
				'review_discount' => 'review_discount',
				'qna_reply' => 'qna_reply'
			);

			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_settings_' . $this->tab, array( $this, 'save' ) );
			add_action( 'woocommerce_admin_field_cr_email_templates', array( $this, 'list_email_templates' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Emails', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			if( in_array( $this->current_section, $this->templates ) ) {
				$email_template = new CR_Email_Template( $this->current_section );
				$email_template->output_fields();
			} else {
				if( $this->current_section ) {
					$section = apply_filters( 'cr_settings_emails_sections', false, $this->current_section );
					if( $section ) {
						echo $section;
						return;
					}
				}
				global $hide_save_button;
				$hide_save_button = true;
				$this->init_settings();
				WC_Admin_Settings::output_fields( $this->settings );
			}
		}

		public function save() {
			if( in_array( $this->current_section, $this->templates ) ) {
				$email_template = new CR_Email_Template( $this->current_section );
				$email_template->save_fields();
			}
		}

		protected function init_settings() {
			$this->settings = array(
				array(
					'title' => __( 'Email Templates', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => sprintf( __( 'Adjust templates of the emails that will be sent to customers. If you enable <b>advanced</b> email templates in your account on the %1$sCusRev website%2$s and use CusRev mailer, they will <b>override</b> the email template below.', 'customer-reviews-woocommerce' ), '<a href="https://www.cusrev.com/login.html" target="_blank" rel="noopener noreferrer">', '</a>' ),
					'id'    => 'cr_options_emails'
				),
				array(
					'type'    => 'cr_email_templates'
				),
				array(
					'type' => 'sectionend',
					'id'   => 'cr_options_emails'
				)
			);
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

		public function list_email_templates( $value ) {
			$email_templates = $this->get_emails();

			?>
			<tr valign="top">
			<td class="wc_emails_wrapper cr-emails-settings-wrapper" colspan="2">
				<table class="wc_emails widefat" cellspacing="0">
					<thead>
						<tr>
							<?php
							$columns = apply_filters(
								'woocommerce_email_setting_columns',
								array(
									'status'     => '',
									'name'       => __( 'Email', 'customer-reviews-woocommerce' ),
									'subject'    => __( 'Subject', 'customer-reviews-woocommerce' ),
									'from'    => __( 'From', 'customer-reviews-woocommerce' ),
									'actions'    => '',
								)
							);
							foreach ( $columns as $key => $column ) {
								echo '<th class="cr-email-settings-' . esc_attr( $key ) . ' wc-email-settings-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
							}
							?>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $email_templates as $email_key => $email ) {
								echo '<tr>';

								foreach ( $columns as $key => $column ) {

									switch ( $key ) {
										case 'name':
											echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
											<a href="' . esc_url( admin_url( 'admin.php?page=cr-reviews-settings&tab=emails&section=' . strtolower( $email_key ) ) ) . '">' . esc_html( $email->get_title() ) . '</a>
											' . wc_help_tip( $email->get_description() ) . '
										</td>';
											break;
										case 'subject':
											echo '<td class="cr-email-settings-' . esc_attr( $key ) . ' wc-email-settings-table-' . esc_attr( $key ) . '">
											' . $email->get_subject() . '
										</td>';
											break;
										case 'status':
											echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">';

											if ( $email->is_enabled() ) {
												echo '<span class="status-enabled tips" data-tip="' . esc_attr__( 'Enabled', 'woocommerce' ) . '">' . esc_html__( 'Yes', 'woocommerce' ) . '</span>';
											} else {
												echo '<span class="status-disabled tips" data-tip="' . esc_attr__( 'Disabled', 'woocommerce' ) . '">-</span>';
											}

											echo '</td>';
											break;
										case 'from':
											echo '<td class="cr-email-settings-' . esc_attr( $key ) . ' wc-email-settings-table-' . esc_attr( $key ) . '">
											' . esc_html( $email->get_from() ) . '
										</td>';
											break;
										case 'actions':
											echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
											<a class="button alignright" href="' . esc_url( admin_url( 'admin.php?page=cr-reviews-settings&tab=emails&section=' . strtolower( $email_key ) ) ) . '">' . esc_html__( 'Manage', 'woocommerce' ) . '</a>
										</td>';
											break;
										default:
											break;
									}
								}

								echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</td>
			</tr>
			<?php
		}

		public function get_emails() {
			return $email_templates = array_map( function( $template ) {
				return new CR_Email_Template( $template );
			}, $this->templates );
		}

	}

endif;
