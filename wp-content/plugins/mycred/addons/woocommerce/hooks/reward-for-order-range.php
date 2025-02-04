<?php 
if ( ! defined( 'MYCRED_WOO_VERSION' ) ) exit;

/**
 * Hook for number of order
 * @since 1.8
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Hook_Order_Range' ) ) :
	class myCRED_Hook_Order_Range extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'woocommerce_order_range',
				'defaults' => array(
					'min' 	  => 1,
					'max' 	  => 100,
					'creds'   => 10,
					'log'     => '%plural% for order range'
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.8
		 * @version 1.0
		 */
		public function run() {

			add_action( 'woocommerce_order_status_changed', array( $this, 'mycred_woo_hook_rewards' ), 10, 4 );
		}

		/**
		 * Add points
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_woo_hook_rewards( $order_id, $old_status, $new_status, $_this ) {

			// status other than givenin reward-setting. return here
			if( ! in_array( 'wc-'.$new_status, mycred_get_woocommerce_settings('reward')['status'] ) ) return;

			$order = wc_get_order( $order_id );
			
            if ( numberBetween( $this->prefs['min'], $order->get_total(), $this->prefs['max'] ) ) return;

			// Make sure user is not excluded
			$user_id  = $order->get_user_id();

			if ( $this->core->exclude_user( $user_id ) ) return;

			$references = apply_filters( 'mycred_woocommerce_points_ref', 'woocommerce_order_range', $order );
			$data       = array( 'ref_type' => 'post' );
			
			// Make sure this is unique
			if ( $this->core->has_entry( $references, $order_id, $user_id, $data, $this->mycred_type ) ) return;

			// Execute
			$this->core->add_creds(
				$references,
				$user_id,
				$this->prefs['creds'],
				$this->prefs['log'],
				$order_id,
				$data,
				$this->mycred_type
			);
			
		}

		/**
		 * Preference for Anniversary Hook
		 * @since 1.8
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;
			?>
			
			<div class="hook-instance">
				<div class="row">
					<div class="col-lg-7 col-md-7 col-sm-12 col-xs-12">
						<div class="row">
							<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
								<div class="form-group">
									<label for="<?php echo esc_attr( $this->field_id( 'min' ) ); ?>"><?php echo esc_html( 'Minimum' ); ?></label>
									<input type="number" name="<?php echo esc_attr( $this->field_name( 'min' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'min' ) ); ?>" min="0" value="<?php echo esc_attr( $this->core->number( $prefs['min'] ) ); ?>" class="form-control" />
								</div>
							</div>
							<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
								<div class="form-group">
									<label for="<?php echo esc_attr( $this->field_id( 'max' ) ); ?>"><?php echo esc_html( 'Maximum' ); ?></label>
									<input type="number" name="<?php echo esc_attr( $this->field_name( 'max' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'max' ) ); ?>" min="0" value="<?php echo esc_attr( $this->core->number( $prefs['max'] ) ); ?>" class="form-control" />
								</div>
							</div>
							<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
								<div class="form-group">
									<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
									<input type="number" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" min="0" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred' ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" placeholder="<?php esc_attr_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general' ) ) ); ?></span>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 textright">
						<button class="mycred-btn-disabled-pro" disabled="disabled" type="button">Add More<span>PRO</span></button>
					</div>
				</div>
			</div>
			<?php
		}

	  	/**
	     * Sanitize Preferences
	     */
		public function sanitise_preferences( $data ) {

			$new_data = array();

			$new_data['creds'] = ! empty( $data['creds'] ) ? (float)$data['creds'] : 0;
			$new_data['min'] = ! empty( $data['min'] ) ? abs( (int)$data['min'] ) : 0;
			$new_data['max'] = ! empty( $data['max'] ) ? ( abs( (int)$data['max'] ) < abs( (int)$data['min'] ) ? abs( (int)$data['min'] ) : abs( (int)$data['max'] ) ) : 0;
			$new_data['log'] = ! empty( $data['log'] ) ? wp_kses_post( $data['log'] ) : '%plural% for number of order';
			
			return $new_data;
		}

	}
endif;
