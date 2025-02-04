<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;


if ( ! class_exists( 'myCRED_Admin_Notices' ) ) :
    Class myCRED_Admin_Notices {
       
        /**
         * Construct
         */
        public function __construct() {

            add_action( 'mycred_admin_init', array( $this, 'mycred_init_notice' ) );
            
        }

		/**
         * Action added
         */
        public function mycred_init_notice() {

            add_action( 'admin_notices', array( $this, 'mycred_update_notice_msg' ) );
            add_action( 'wp_ajax_mycred_update_notice', array( $this, 'mycred_update_notice' ) );

            // once we get the value we will remove the notice
            $get_addons_data = new myCRED_Addons_Module();
            $woo_acknowledged = mycred_get_option( 'mycred_woo_acknowledge_notice', false );

            if( empty( $woo_acknowledged ) && in_array( 'gateway', $get_addons_data->addons['active'] ) ) {

	            add_action( 'mycred_admin_notices_for_site', array( $this, 'mycred_add_notice_for_woo' ) );
	            add_action( 'mycred_save_notice_ajax', array( $this, 'mycred_save_notice_for_woo' ) );
	        
            }


        }

		/**
         * Admin notice message
         */
        public function mycred_update_notice_msg() { 
			
			do_action( 'mycred_admin_notices_for_site' );

        }

		/**
         * Save in database
         */
        public function mycred_update_notice() {
              
        	do_action( 'mycred_save_notice_ajax' );

        }

        /**
         * Admin notice msg for woocommerce addon
         */
        public function mycred_add_notice_for_woo() { ?>
        	
        	<div class="notice notice-warning">
                <div class="mycred-notice-description" style="display:flex; justify-content: space-between; align-items: center; padding: 5px;">
	                <p class="mycred-update-description">Please note that myCred Payment Gateway for WooCommerce is no longer part of the myCred Gateway add-on. Instead, you can find it in the myCred WooCommerce add-on. For more information <a href="https://mycred.me/mycred-woocommerce/" target="_blank">click here</a>.</p>
	                <div class="mycred-notice-button" style="display:block; padding: 0 0 0 20px;">
                        <button class="mycred-update-woo-notice button button-primary button-large" >Acknowledged</button>
                    </div>
	            </div>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function(){
                    jQuery('.mycred-update-woo-notice').click(function(){
                        jQuery.ajax({
                            url: ajaxurl,
                            data: {
                                action: 'mycred_update_notice',
                            },
                            type: 'POST',
                            beforeSend: function() { },
                            success:function(data) { location.reload(); }
                        })

                    });

                });

            </script>
            <?php
        }

        /**
         * Save value for woocommerce addon
         */
        public function mycred_save_notice_for_woo() { 
            mycred_update_option( 'mycred_woo_acknowledge_notice', 'Acknowledged' );
        }


    }
endif;

new myCRED_Admin_Notices();