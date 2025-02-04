<?php 
if ( ! defined( 'MYCRED_WOO_VERSION' ) ) exit;

/**
 * myCRED_WOO_HOOKS class
 * @since 0.1
 * @version 1.4.1
 */
if ( ! class_exists( 'myCRED_WOO_HOOKS' ) ) :
	class myCRED_WOO_HOOKS {

		// Instnace
		protected static $_instance = NULL;

		/**
		 * Construct
		 */
		function __construct() {
			
			// add hook
            add_filter( 'mycred_setup_hooks',    array( $this, 'register_woo_reward_hook' ), 10, 2 );
            add_filter( 'mycred_all_references', array( $this, 'register_buycred_reward_refrence' ) );
			add_action( 'mycred_load_hooks',     array( $this, 'load_woo_reward_hook' ) );

		}

		/**
		 * Setup Instance
		 * @since 1.7
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}


		 /**
         * Register hook file
         * @since 1.0
         * @version 1.0.3
         */
        public function load_woo_reward_hook() {

            require_once MYCRED_WOO_HOOKS_DIR . 'reward-for-each-order.php';
            require_once MYCRED_WOO_HOOKS_DIR . 'reward-for-first-order.php';
            require_once MYCRED_WOO_HOOKS_DIR . 'reward-for-number-of-order.php';
            require_once MYCRED_WOO_HOOKS_DIR . 'reward-for-order-range.php';

        }

        /**
         * Register hook setting
         * @since 1.0
         * @version 1.0.3
         */
        public function register_woo_reward_hook( $installed ) {

            $installed['woocommerce_each_order'] = array(
                'title'       => __('%plural% for each order (WooCommerce)', 'mycred'),
                'description' => __('Award %plural% for each order.', 'mycred'),
                'callback'    => array('myCRED_Hook_Each_Order'),
                'pro'         => 'https://mycred.me/store/mycred-woocommerce-plus/?utm_source=plugin&utm_medium=referral&utm_id=hook-each-order'
            );

            $installed['woocommerce_first_order'] = array(
                'title'       => __('%plural% for first order (WooCommerce)', 'mycred'),
                'description' => __('Award %plural% for first order.', 'mycred'),
                'callback'    => array('myCRED_Hook_First_Order'),
                'pro'         => 'https://mycred.me/store/mycred-woocommerce-plus/?utm_source=plugin&utm_medium=referral&utm_id=hook-first-order'
            );

            $installed['woocommerce_numbers_of_orders'] = array(
                'title'       => __('%plural% for numbers of order (WooCommerce)', 'mycred'),
                'description' => __('Award %plural% for numbers of order.', 'mycred'),
                'callback'    => array('myCRED_Hook_Number_Of_Order'),
                'pro'         => 'https://mycred.me/store/mycred-woocommerce-plus/?utm_source=plugin&utm_medium=referral&utm_id=hook-number-of-orders'
            );

            $installed['woocommerce_order_range'] = array(
                'title'       => __('%plural% for order range (WooCommerce)', 'mycred'),
                'description' => __('Award %plural% for order range.', 'mycred'),
                'callback'    => array('myCRED_Hook_Order_Range'),
                'pro'         => 'https://mycred.me/store/mycred-woocommerce-plus/?utm_source=plugin&utm_medium=referral&utm_id=hook-order-range'
            );

            return $installed;
        }

        public function register_buycred_reward_refrence( $list ) {

            $list['woocommerce_each_order']  = __( 'Reward for each order', 'mycred' );
            $list['woocommerce_first_order'] = __( 'Reward for first order', 'mycred' );
            $list['woocommerce_numbers_of_orders'] = __( 'Reward for number of order', 'mycred' );
            $list['woocommerce_order_range'] = __( 'Reward for order range', 'mycred' );

            return $list;
        }

	}
endif;

function mycred_woo_reward_init() {
	return myCRED_WOO_HOOKS::instance();
}
mycred_woo_reward_init();