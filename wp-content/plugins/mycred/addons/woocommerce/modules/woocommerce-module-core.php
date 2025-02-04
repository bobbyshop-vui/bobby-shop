<?php
if ( ! defined( 'MYCRED_WOO_VERSION' ) ) exit;

if ( ! class_exists( 'myCred_WooCommerce_Module' ) ) :
    class myCred_WooCommerce_Module extends myCRED_Module {

        function __construct( $type = MYCRED_DEFAULT_TYPE_KEY ) {

            parent::__construct( 'myCred_WooCommerce_Module', array(
                'module_name' => MYCRED_WOO_KEY,
                'option_id'   => 'mycred_pref_woo',
                'defaults'    => mycred_get_addon_defaults( 'woocommerce' ),
                'labels'      => array(
                    'menu'        => __( 'WooCommerce', 'mycred' ),
                    'page_title'  => __( 'WooCommerce', 'mycred' ),
                    'page_header' => __( 'WooCommerce', 'mycred' )
                ),
                'screen_id'   => MYCRED_WOO_SLUG,
                'register'    => true,
                'menu_pos'    => 90,
                'main_menu'   => true
            ), $type );

        }

        /**
         * Module Pre Init
         * @since 1.0
         * @version 1.2
         */
        public function module_pre_init() {

            add_action( 'before_woocommerce_init', array( $this, 'gamification_hpos_compatibility' ) );

        }

         /**
         * Woocommerce Compability
         *
         * @since   1.0
         * @version 1.0
         */
        public function gamification_hpos_compatibility() {

            if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            }

        }

        /**
         * Module Init
         * @since 1.0
         * @version 1.0.3
         */
        public function module_init() { }

        /**
         * WooCommerce menu callback
         * @since 1.0
         * @version 1.0
         */
        public function admin_page() {

            wp_enqueue_script( 'mycred-accordion' );
            wp_enqueue_style( 'mycred-bootstrap-grid' );

            $point_types = mycred_get_types();

            $reward_setting = $this->woocommerce['reward'];

            ?>
            <div class="wrap mycred-metabox" id="myCRED-wrap">
                <h1><?php esc_html_e( 'WooCommerce', 'mycred' );?></h1>
                <form class="form" method="post" action="options.php">
                    <?php settings_fields( $this->settings_name );?>
                    <div class="list-items expandable-li ui-accordion ui-widget ui-helper-reset" id="accordion" role="tablist">
                        <div class="mycred-ui-accordion">
                            <div class="mycred-ui-accordion-header">
                                <h4 class="mycred-ui-accordion-header-title">
                                    <span class="dashicons dashicons-awards static mycred-ui-accordion-header-icon"></span>
                                    <label><?php esc_html_e( 'Reward Settings', 'mycred' ); ?></label>
                                </h4>
                                <div class="mycred-ui-accordion-header-actions hide-if-no-js">
                                    <button type="button" aria-expanded="true">
                                        <span class="mycred-ui-toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="body mycred-ui-accordion-body" style="display: none;">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <h3><?php esc_html_e( 'Single Product Reward', 'mycred' );?></h3>
                                        <?php foreach( $point_types as $point_type => $value ):?>
                                        <div class="form-group">
                                            <?php 
                                            $is_enabed = ( ! empty( $reward_setting['types'][$point_type] ) ? $reward_setting['types'][$point_type] : 0 );
                                            mycred_create_toggle_field( 
                                                array(
                                                    'id' => $this->field_id( array( 'reward', 'types', $point_type ) ),
                                                    'name' => $this->field_name( array( 'reward', 'types', $point_type ) ),
                                                    'label' => $value,
                                                    'after' => true
                                                ), 
                                                $is_enabed,
                                                $is_enabed
                                            );
                                            ?>
                                        </div>
                                        <?php endforeach;?>
                                        <p><i><?php esc_html_e( 'Enable this option if you want to give a reward for single product.', 'mycred' ) ?></i></p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <h3><?php esc_html_e( 'Reward On Order Statuses', 'mycred' );?></h3>
                                        <div class="form-group">
                                            <label for="<?php echo esc_attr( $this->field_id( array( 'reward', 'status' ) ) . '[]' ) ?>"><?php echo esc_html_e( 'Status', 'mycred' ); ?></label>
                                            <?php 
                                            $order_statuses  = apply_filters( 'mycred_woocommerce_reward_order_statuses', array( 'wc-processing' => 'Processing',  'wc-completed' => 'Completed') );
                                            mycred_create_select_field( 
                                                $order_statuses, 
                                                $reward_setting['status'], 
                                                array( 
                                                    'id'       => $this->field_id( array( 'reward', 'status' ) ) . '[]',
                                                    'class'    => 'mycred-select2',
                                                    'name'     => $this->field_name( array( 'reward', 'status' ) ) . '[]', 
                                                    'multiple' => 'multiple' 
                                                )
                                            );
                                            ?>
                                        </div>
                                        <p><i><?php esc_html_e( 'Select the order status on which you want to give points.', 'mycred' )?></i></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php do_action( 'mycred_after_woocommerce_prefs', $this ); ?>
                    </div>
                    <input type="submit" name="submit" class="button mycred-ui-mt20 mycred-ui-btn-purple" value="<?php esc_attr_e( 'Update', 'mycred' ) ?>"/>
                </form>
            </div>
            <?php

        }

        /**
         * Save Settings
         * @since 0.1
         * @version 1.2
         */
        public function sanitize_settings( $data ) {

            $new_data = array();
            $new_data['reward']['types']  = mycred_sanitize_array( $data['reward']['types'] );
            $new_data['reward']['status'] = mycred_sanitize_array( $data['reward']['status'] );

            return apply_filters( 'mycred_woocommerce_sanitize_settings', $new_data, $data, $this );

        }

    }
endif;

if ( ! function_exists( 'mycred_load_woo_addon' ) ) :
    function mycred_load_woo_addon( $modules, $point_types ) {

        $modules['solo']['woocommerce'] = new myCred_WooCommerce_Module();
        $modules['solo']['woocommerce']->load();
        return $modules;

    }
endif;
add_filter( 'mycred_load_modules', 'mycred_load_woo_addon', 10, 2 );
