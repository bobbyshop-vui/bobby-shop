<?php

/**
 * Addon: WooCommerece
 * Addon URI: http://codex.mycred.me/chapter-iii
 * Version: 1.0
 */

if ( ! defined( 'myCRED_VERSION' ) ) exit;

if( class_exists( 'WooCommerce' ) ) {
    
    define( 'MYCRED_WOO_VERSION', '1.0' );
    define( 'MYCRED_WOO_SLUG', 'mycred-woocommerce' );
    define( 'MYCRED_WOO_THIS', __FILE__ );
    define( 'MYCRED_WOO_ROOT_DIR', myCRED_ADDONS_DIR . 'woocommerce/'  );
    define( 'MYCRED_WOO_INCLUDES_DIR', MYCRED_WOO_ROOT_DIR . 'includes/' );
    define( 'MYCRED_WOO_HOOKS_DIR', MYCRED_WOO_ROOT_DIR . 'hooks/' );
    define( 'MYCRED_WOO_MODULES_DIR', MYCRED_WOO_ROOT_DIR . 'modules/' );
    define( 'myCRED_WOO_SHORTCODES_DIR', MYCRED_WOO_INCLUDES_DIR . 'shortcodes/' );

    if ( ! defined( 'MYCRED_WOO_KEY' ) )
        define( 'MYCRED_WOO_KEY', 'woocommerce' );


    /**
     * Load Modules
     * @since 1.7
     * @version 1.0
     */
    require_once MYCRED_WOO_MODULES_DIR . 'woocommerce-module-core.php';

    // Adding Gatway files
    require_once MYCRED_WOO_INCLUDES_DIR . 'mycred-woocommerce-gateway.php';

    // Functions.php
    require_once MYCRED_WOO_INCLUDES_DIR . 'mycred-woo-functions.php';

    // Adding hooks file
    require_once MYCRED_WOO_HOOKS_DIR . 'woocommerce-load-hooks.php';

    // WooCommerce blocks compatibility file
    require_once MYCRED_WOO_INCLUDES_DIR . 'mycred-woo-block-compatibility.php';

} else {
    add_action( 'mycred_addon_page_before', 'mycred_woocommerce_message' );
}

function mycred_woocommerce_message() {
    echo '<div id="message" class="error"><p>' . esc_html__( 'To use myCred WooCommerce Addon, you have to install WooCommerce first.', 'mycred' ) . '</p></div>';
}