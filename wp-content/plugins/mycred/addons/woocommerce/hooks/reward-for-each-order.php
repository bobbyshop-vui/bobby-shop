<?php 
if ( ! defined( 'MYCRED_WOO_VERSION' ) ) exit;

/**
 * Hook for each order
 * @since 1.8
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Hook_Each_Order' ) ) :
	class myCRED_Hook_Each_Order extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'woocommerce_each_order',
				'defaults' => array(
					'reward_on_each' => 'fixed_rate',
					'creds'   => 10,
					'log'     => '%plural% for each order'
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

			$order    = wc_get_order( $order_id );

			// Make sure user is not excluded
			$user_id  = ( version_compare( $woocommerce->version, '3.0', '>=' ) ) ? $order->get_user_id() : $order->user_id;
			if ( $this->core->exclude_user( $user_id ) ) return;

			$reference = apply_filters( 'mycred_woocommerce_each_order', 'woocommerce_each_order', $order );
			$data      = array( 'ref_type' => 'post' );
			
			// Make sure this is unique
			if ( $this->core->has_entry( $reference, $order_id, $user_id, $data, $this->mycred_type ) ) return;

			// Execute
			$this->core->add_creds(
				$reference,
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
					<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'reward_on_each' ) );?>"><?php esc_html_e( 'Reward Type', 'mycred' );?></label> 
							<select name="<?php echo esc_attr( $this->field_name( 'reward_on_each' ) );?>" class="form-control" id="<?php echo esc_attr( $this->field_id( 'reward_on_each' ) ); ?>">
								<option value="fixed_rate"><?php echo esc_html( 'Fixed' ) ; ?></option>
								<option value="percentage" disabled><?php echo esc_html( 'Percentage ( PRO )' ); ?></option>
								<option value="exchange_rate" disabled><?php echo esc_html( 'Exchange Rate ( PRO )' ); ?></option>
							</select>
							<span class="description"><?php esc_html_e( 'Select the type of reward you want to give', 'mycred' );?></span>
						</div>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
							<input type="number" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" min="0" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
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
			</div>
			<?php
		}

	  	/**
	     * Sanitize Preferences
	     */
		public function sanitise_preferences( $data ) {

			$new_data = array();
			$new_data['reward_on_each'] = 'fixed_rate';
			$new_data['creds'] = ! empty( $data['creds'] ) ? (float) $data['creds'] : 0;
			$new_data['log'] = ! empty( $data['log'] ) ? wp_kses_post( $data['log'] ) : '%plural% for each order';

			return $new_data;
			
		}

	}
endif;
