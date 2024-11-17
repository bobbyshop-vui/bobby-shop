<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Messages_Settings' ) ):

	class CR_Messages_Settings {

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
		protected $wa_templates;

		public function __construct( $settings_menu ) {
			$this->settings_menu = $settings_menu;
			$this->tab = 'messages';
			$this->current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) );
			$this->wa_templates = array(
				'wa_review_reminder' => 'wa_review_reminder'
			);

			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_settings_' . $this->tab, array( $this, 'save' ) );
			add_action( 'woocommerce_admin_field_cr_whatsapp_templates', array( $this, 'list_whatsapp_templates' ) );
		}

		public function register_tab( $tabs ) {
			$verified_reviews = get_option( 'ivole_verified_reviews', 'no' );
			if (
				'no' === $verified_reviews ||
				apply_filters( 'cr_settings_messages_tab', false, $verified_reviews )
			) {
				$tabs[$this->tab] = __( 'Messages', 'customer-reviews-woocommerce' );
			}
			return $tabs;
		}

		public function display() {
			if ( in_array( $this->current_section, $this->wa_templates ) ) {
				$wa_template = new CR_WA_Template( $this->current_section );
				$wa_template->output_fields();
			} else {
				if( $this->current_section ) {
					$section = apply_filters( 'cr_settings_messages_sections', false, $this->current_section );
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
			if( in_array( $this->current_section, $this->wa_templates ) ) {
				$wa_template = new CR_WA_Template( $this->current_section );
				$wa_template->save_fields();
			}
		}

		protected function init_settings() {
			$this->settings[] = array(
				'title' => __( 'WhatsApp Templates', 'customer-reviews-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Adjust templates of WhatsApp messages that will be sent to customers.', 'customer-reviews-woocommerce' ),
				'id'    => 'cr_options_whatsapp'
			);
			$this->settings[] = array(
				'type' => 'cr_whatsapp_templates'
			);
			$this->settings[] = array(
				'type' => 'sectionend',
				'id'   => 'cr_options_whatsapp'
			);
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

		public function list_whatsapp_templates( $value ) {
			$wa_templates = $this->get_wa_templates();
			?>
			<tr valign="top">
			<td class="wc_emails_wrapper cr-emails-settings-wrapper" colspan="2">
				<table class="wc_emails widefat cr-wtsap-templates">
					<thead>
						<tr>
							<?php
							$columns = apply_filters(
								'woocommerce_email_setting_columns',
								array(
									'status' => '',
									'name' => __( 'WhatsApp Template', 'customer-reviews-woocommerce' ),
									'message' => __( 'Message', 'customer-reviews-woocommerce' ),
									'actions' => '',
								)
							);
							foreach ( $columns as $key => $column ) {
								echo '<th class="cr-wa-settings-' . esc_attr( $key ) . ' wc-email-settings-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
							}
							?>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $wa_templates as $wa_key => $wa ) {
								echo '<tr>';

								foreach ( $columns as $key => $column ) {

									switch ( $key ) {
										case 'name':
											echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
											<a href="' . esc_url( admin_url( 'admin.php?page=cr-reviews-settings&tab=messages&section=' . strtolower( $wa_key ) ) ) . '">' . esc_html( $wa->get_title() ) . '</a>
											' . wc_help_tip( $wa->get_description() ) . '
										</td>';
											break;
										case 'message':
											echo '<td class="cr-email-settings-' . esc_attr( $key ) . ' wc-email-settings-table-' . esc_attr( $key ) . '">
											' . $wa->get_message() . '
										</td>';
											break;
										case 'status':
											echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">';
											if ( $wa->is_enabled() ) {
												echo '<span class="status-enabled tips" data-tip="' . esc_attr__( 'Enabled', 'woocommerce' ) . '">' . esc_html__( 'Yes', 'woocommerce' ) . '</span>';
											} else {
												echo '<span class="status-disabled tips" data-tip="' . esc_attr__( 'Disabled', 'woocommerce' ) . '">-</span>';
											}
											echo '</td>';
											break;
										case 'actions':
											echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
											<a class="button alignright" href="' . esc_url( admin_url( 'admin.php?page=cr-reviews-settings&tab=messages&section=' . strtolower( $wa_key ) ) ) . '">' . esc_html__( 'Manage', 'woocommerce' ) . '</a>
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

		public function get_wa_templates() {
			$this->wa_templates = apply_filters( 'cr_settings_messages_templates', $this->wa_templates );
			return $wa_templates = array_map( function( $template ) {
				return new CR_WA_Template( $template );
			}, $this->wa_templates );
		}

	}

endif;
