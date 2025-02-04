<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Hook for Anniversary
 * @since 1.8
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Hook_Anniversary' ) ) :
	class myCRED_Hook_Anniversary extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'anniversary',
				'defaults' => array(
					'creds'   => 10,
					'log'     => '%plural% for being a member for a year',
					'mycred_check_complete' => '0',
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.8
		 * @version 1.0
		 */
		public function run() {

			add_action( 'template_redirect', array( $this, 'page_load' ) );

		}

		/**
		 * Page Load
		 * @since 1.8
		 * @version 1.0
		 */
		public function page_load() {

			if ( ! is_user_logged_in() ) return;

			$user_id  = get_current_user_id();

			// Make sure user is not excluded
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Make sure this only runs once a day
			$last_run = mycred_get_user_meta( $user_id, 'anniversary-' . $this->mycred_type, '', true );
			$today    = date( 'Y-m-d', current_time( 'timestamp' ) );
			if ( $last_run == $today ) return;

			if($this->prefs['mycred_check_complete'] == 0) {
			  $this->anniversary_reward($user_id);
			 }
			 else {
			   $this->anniversary_reward_with_previous_years($user_id);
			  }

			mycred_update_user_meta( $user_id, 'anniversary-' . $this->mycred_type, '', $today );

		}

		public function anniversary_reward_with_previous_years($user_id) {

			global $wpdb;

			$result = $wpdb->get_row( $wpdb->prepare( "SELECT user_registered, TIMESTAMPDIFF( YEAR, user_registered, CURDATE()) AS difference FROM {$wpdb->users} WHERE ID = %d;", $user_id ) );

			// If we have been a member for more then one year
			if ( isset( $result->user_registered ) && $result->difference >= 1 ) {

				$year_joined = substr( $result->user_registered, 0, 4 );
				$date_joined = strtotime( $result->user_registered );

				// First time we give points we might need to give for more then one year
				// so we give points for each year.
				for ( $i = 0; $i < $result->difference; $i++ ) {

					$year_joined++;
					if ( $this->core->has_entry( 'anniversary', $year_joined, $user_id, $date_joined, $this->mycred_type ) ) continue;

					// Execute
					$this->core->add_creds(
						'anniversary',
						$user_id,
						$this->prefs['creds'],
						$this->prefs['log'],
						$year_joined,
						$date_joined,
						$this->mycred_type
					);
				}
			}

		}

		public function anniversary_reward($user_id) {

			// Get the user's registration date
		    $user_info = get_userdata($user_id);

		    // Get the registration date in 'Y-m-d' format
		    $registration_date = strtotime($user_info->user_registered);
		    $registration_date_formatted = date('Y-m-d', $registration_date);

		    // Get the current date in 'Y-m-d' format
		    $current_date = date('Y-m-d');

		    // Get the current year
		    $current_year = date('Y');
		    
		    // Calculate the anniversary date for the current year
		    $anniversary_date = date('Y-m-d', strtotime($registration_date_formatted . ' +' . (date('Y') - date('Y', $registration_date)) . ' years'));

		    $anniversary_year = strtotime($registration_date_formatted . ' +1 year');
		    $anniversary_year_formatted = date('Y-m-d', $anniversary_year);

		    $new_current_date = strtotime($current_date);
		    $new_current_date_formatted = date('Y-m-d', $new_current_date);

		    if($anniversary_year_formatted == $new_current_date_formatted  ) {

			if ( $this->core->has_entry( 'anniversary', $new_current_date_formatted, $user_id, $registration_date_formatted, $this->mycred_type ) ){  return;
			}
					// Execute
					$this->core->add_creds(
						'anniversary',
						$user_id,
						$this->prefs['creds'],
						$this->prefs['log'],
						$new_current_date_formatted,
						$registration_date_formatted,
						$this->mycred_type
					);
			}
		}		

		/**
		 * Preference for Anniversary Hook
		 * @since 1.8
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;
			

?>
<div class="hook-instance" style="margin-bottom: 0px; padding-bottom: 14px;">

	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" placeholder="<?php esc_attr_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general' ) ) ); ?></span>
			</div>
		</div>
	</div>
	<div class="row">
	    <div class="col-lg-12">  
	        <div class="mycred-toggle-wrapper">
	        	 <label for="yCRED-General-remote"><strong><?php esc_html_e( 'Enable', 'mycred' ); ?></strong></label>
				<label class="mycred-toggle" style=" display: block; margin: 14px 0px;">
				<input type="checkbox" name="<?php echo esc_attr( $this->field_name( 'mycred_check_complete' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'mycred_check_complete' ) ); ?>" value="1" <?php if( $prefs['mycred_check_complete'] == '1') echo "checked = 'checked'"; ?> />
			        		  <span class="slider round"></span></label>      		  
			</div>
			<div class="hook-description">
	            <p><?php esc_html_e( 'Enable this option to give'. ' ' . $this->core->plural() . ' ' . 'to users for all previous years.', 'mycred' ); ?></p>
	        </div>
	    </div>
	</div>
</div>
<?php

		}

		public function sanitize_preferences($data) {
			$new_data = array();
				
			$new_data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : '';
			$new_data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : '';
			$new_data['mycred_check_complete'] = ( !empty( $data['mycred_check_complete'] ) ) ? sanitize_text_field( $data['mycred_check_complete'] ) : '';

			return $new_data;
		}

	}
endif;
