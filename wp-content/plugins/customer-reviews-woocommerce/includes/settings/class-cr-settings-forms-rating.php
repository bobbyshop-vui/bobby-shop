<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Forms_Settings_Rating' ) ) :

	class CR_Forms_Settings_Rating {

		public static $no_atts;
		public static $help_attribute;
		public static $help_label;
		public static $help_required;

		public function __construct() {
			self::$help_attribute = __( 'A question to be displayed for the rating on an on-site review form. For example, \'How comfortable are these shoes?\'.', 'customer-reviews-woocommerce' );
			self::$help_label = __( 'A label to be displayed on a review next to a rating provided by a customer. For example, \'Comfort\'.', 'customer-reviews-woocommerce' );
			self::$help_required = __( 'This field can be used to require customers to submit a rating.', 'customer-reviews-woocommerce' );
			self::$no_atts = '<tr class="cr-rtn-crta-table-empty"><td colspan="4">' . __( 'No rating criteria added', 'customer-reviews-woocommerce' ) . '</td></tr>';
		}

		public static function display_rating_criteria( $field ) {
			$form_settings = CR_Forms_Settings::get_default_form_settings();
			$rtn_crta = array();
			if ( $form_settings ) {
				if (
					is_array( $form_settings ) &&
					isset( $form_settings['rtn_crta'] ) &&
					is_array( $form_settings['rtn_crta'] )
				) {
					$rtn_crta = $form_settings['rtn_crta'];
				}
			}
			$max_atts = self::get_max_rating_criteria();
			if ( $max_atts <= count( $rtn_crta ) ) {
				$td_class = ' cr-rtn-crta-limit';
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
						<button type="button" class="page-title-action cr-rtn-crta-add-rtn">
							<?php _e( 'Add Rating', 'customer-reviews-woocommerce' ); ?>
						</button>
						<span>
							<?php echo esc_html_e( 'The free version of the plugin supports up to 3 rating criteria', 'customer-reviews-woocommerce' ); ?>
						</span>
					</div>
					<input type="hidden" name="ivole_rating_criteria" id="ivole_rating_criteria" value="<?php echo esc_attr( json_encode( $rtn_crta ) ); ?>" />
					<table class="widefat cr-rtn-crta-table" cellspacing="0">
						<thead>
							<tr>
								<?php
								$columns = array(
									'attribute' => array(
										'title' => __( 'Rating', 'customer-reviews-woocommerce' ),
										'help' => self::$help_attribute
									),
									'label' => array(
										'title' => __( 'Label', 'customer-reviews-woocommerce' ),
										'help' => self::$help_label
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
									echo '<th class="cr-rtn-crta-table-' . esc_attr( $key ) . '">';
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
							if( 0 < count( $rtn_crta ) ) {
								$counter = 1;
								foreach( $rtn_crta as $attribute ) {
									if ( $counter > $max_atts ) {
										break;
									}

									echo '<tr class="cr-rtn-crta-tr">';

									foreach( $columns as $key => $column ) {
										if ( $attribute['required'] ) {
											$req = __( 'Yes', 'customer-reviews-woocommerce' );
										} else {
											$req = __( 'No', 'customer-reviews-woocommerce' );
										}
										switch( $key ) {
											case 'attribute':
												echo '<td>' . $attribute['rating'] . '</td>';
												break;
											case 'label':
												echo '<td>' . $attribute['label'] . '</td>';
												break;
											case 'required':
												echo '<td data-required="' . boolval( $attribute['required'] ) . '">' . $req . '</td>';
												break;
											case 'actions':
												echo '<td class="cr-cus-atts-td-menu">' . CR_Forms_Settings::$button_manage . '</td>';
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
						self::display_modal_template();
						self::display_delete_conf_template();
					?>
				</td>
			</tr>
			<?php
		}

		public static function display_modal_template() {
			?>
			<div class="cr-rtn-crta-modal-cont">
				<div class="cr-rtn-crta-modal">
					<div class="cr-rtn-crta-modal-internal">
						<div class="cr-rtn-crta-modal-topbar">
							<h3 class="cr-rtn-crta-modal-title"><?php _e( 'Add a Rating', 'customer-reviews-woocommerce' ); ?></h3>
							<button type="button" class="cr-rtn-crta-modal-close-top">
								<span>×</span>
							</button>
						</div>
						<div class="cr-rtn-crta-modal-section">
							<div class="cr-rtn-crta-modal-section-row-ctn">
								<div class="cr-rtn-crta-modal-section-row">
									<label for="cr_rtn_crt_input">
										<?php _e( 'Rating', 'customer-reviews-woocommerce' ); ?>
										<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::$help_attribute ); ?>"></span>
									</label>
									<input id="cr_rtn_crt_input" type="text" placeholder="<?php _e( 'E.g., How comfortable are the shoes?', 'customer-reviews-woocommerce' ); ?>">
								</div>
								<div class="cr-rtn-crta-modal-section-err">
									<?php _e( '* Rating cannot be blank', 'customer-reviews-woocommerce' ); ?>
								</div>
							</div>
							<div class="cr-rtn-crta-modal-section-row">
								<label for="cr_rtn_crt_label">
									<?php _e( 'Label', 'customer-reviews-woocommerce' ); ?>
									<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::$help_label ); ?>"></span>
								</label>
								<input id="cr_rtn_crt_label" type="text" placeholder="<?php _e( 'E.g., Comfort', 'customer-reviews-woocommerce' ); ?>">
							</div>
							<div class="cr-rtn-crta-modal-section-row">
								<label for="cr_rtn_crt_required">
									<?php _e( 'Required', 'customer-reviews-woocommerce' ); ?>
									<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::$help_required ); ?>"></span>
								</label>
								<input type="checkbox" id="cr_rtn_crt_required">
							</div>
						</div>
						<div class="cr-rtn-crta-modal-bottombar">
							<button type="button" class="cr-rtn-crta-modal-cancel"><?php echo esc_html( __( 'Cancel', 'customer-reviews-woocommerce' ) ); ?></button>
							<button type="button" class="cr-rtn-crta-modal-save"><?php echo esc_html( __( 'Confirm', 'customer-reviews-woocommerce' ) ); ?></button>
						</div>
						<input type="hidden" class="cr-rtn-crta-prev-val">
					</div>
				</div>
			</div>
			<?php
		}

		public static function display_delete_conf_template() {
			?>
			<div class="cr-rtn-crta-del-modal-cont">
				<div class="cr-rtn-crta-del-modal">
					<div class="cr-rtn-crta-del-modal-internal">
						<div class="cr-rtn-crta-modal-topbar">
							<h3 class="cr-rtn-crta-modal-title"></h3>
							<button type="button" class="cr-rtn-crta-modal-close-top">
								<span>×</span>
							</button>
						</div>
						<div class="cr-rtn-crta-modal-section">
							<div class="cr-rtn-crta-modal-section-row">
								<?php echo esc_html( __( 'Would you like to delete this rating?', 'customer-reviews-woocommerce' ) ); ?>
							</div>
						</div>
						<div class="cr-rtn-crta-modal-bottombar">
							<button type="button" class="cr-rtn-crta-modal-cancel"><?php echo esc_html( __( 'Cancel', 'customer-reviews-woocommerce' ) ); ?></button>
							<button type="button" class="cr-rtn-crta-modal-save"><?php echo esc_html( __( 'Confirm', 'customer-reviews-woocommerce' ) ); ?></button>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public static function get_max_rating_criteria() {
			return apply_filters( 'cr_onsite_ratings', 3 );
		}
	}

endif;
